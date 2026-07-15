<?php

namespace App\Services;

use App\Http\Models\PvpcPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use MongoDB\BSON\UTCDateTime;

class EsiosPvpcService
{
    public static function downloadAndStoreByDate($date): array
    {
        $token = config('services.esios.token');
        $baseUrl = rtrim(config('services.esios.base_url'), '/');

        if (!$token) {
            throw new \Exception('Falta configurar ESIOS_TOKEN en el .env');
        }

        $dateMadrid = Carbon::parse($date, 'Europe/Madrid')
            ->timezone('Europe/Madrid')
            ->startOfDay();

        $dateEs = $dateMadrid->format('d/m/Y');

        $response = Http::withHeaders(self::headers($token))
            ->timeout(30)
            ->get($baseUrl . '/archives/70/download', [
                'locale' => 'es',
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando fichero PVPC ESIOS: ' . $response->status() . ' - ' . $response->body()
            );
        }

        $items = collect($response->json('PVPC', []));

        if ($items->isEmpty()) {
            throw new \Exception('ESIOS ha respondido, pero el fichero PVPC viene vacío.');
        }

        $availableDates = $items
            ->pluck('Dia')
            ->unique()
            ->values()
            ->toArray();

        $itemsForDate = $items->filter(function ($item) use ($dateEs) {
            return ($item['Dia'] ?? null) === $dateEs;
        });

        if ($itemsForDate->isEmpty()) {
            throw new \Exception(
                'El fichero PVPC no contiene datos para ' . $dateEs .
                '. Fechas disponibles en el fichero: ' . implode(', ', $availableDates)
            );
        }

        $saved = 0;

        $itemsForDate->each(function ($item) use ($dateMadrid, &$saved) {
            $hour = self::parseHour($item['Hora'] ?? null);

            $total = self::toFloat($item['PCB'] ?? 0);

            $energy = self::toFloat($item['PMHPCB'] ?? 0);

            $adjustment = self::toFloat($item['SAHPCB'] ?? 0);

            $futures = self::toFloat($item['TAHPCB'] ?? 0);

            $others = self::sumFields($item, [
                'FOMPCB',
                'FOSPCB',
                'INTPCB',
                'PCAPPCB',
                'TEUPCB',
                'CCVPCB',
                'EDSRPCB',
            ]);

            $existing = PvpcPrice::where('date', $dateMadrid->format('Y-m-d'))
                ->where('hour', $hour)
                ->first();

            $data = [
                'date' => $dateMadrid->format('Y-m-d'),
                'hour' => $hour,

                // Alias antiguos
                'price_eur_mwh' => round($total, 4),
                'price_c_kwh' => self::toCentKwh($total),

                // Total
                'total_eur_mwh' => round($total, 4),
                'total_c_kwh' => self::toCentKwh($total),

                // Energía
                'energy_eur_mwh' => round($energy, 4),
                'energy_c_kwh' => self::toCentKwh($energy),

                // Ajustes
                'adjustment_eur_mwh' => round($adjustment, 4),
                'adjustment_c_kwh' => self::toCentKwh($adjustment),

                // Mercado a plazo
                'futures_eur_mwh' => round($futures, 4),
                'futures_c_kwh' => self::toCentKwh($futures),

                // Otros
                'others_eur_mwh' => round($others, 4),
                'others_c_kwh' => self::toCentKwh($others),

                'raw_data' => $item,
                'updatedAt' => new UTCDateTime(Carbon::now('UTC')->getTimestampMs()),
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                $data['createdAt'] = new UTCDateTime(Carbon::now('UTC')->getTimestampMs());
                PvpcPrice::create($data);
            }

            $saved++;
        });

        if ($saved === 0) {
            throw new \Exception('No se ha guardado ningún registro PVPC para ' . $dateEs);
        }

        return [
            'date' => $dateMadrid->format('Y-m-d'),
            'saved' => $saved,
            'source' => 'ESIOS archives/70/download',
        ];
    }

    public static function getStoredByDate($date): array
    {
        $dateMadrid = Carbon::parse($date, 'Europe/Madrid')
            ->timezone('Europe/Madrid')
            ->startOfDay();

        $hours = PvpcPrice::where('date', $dateMadrid->format('Y-m-d'))
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',

                    // Alias antiguos
                    'price_eur_mwh' => (float) ($item->price_eur_mwh ?? $item->total_eur_mwh),
                    'price_c_kwh' => (float) ($item->price_c_kwh ?? $item->total_c_kwh),

                    // Total
                    'total_eur_mwh' => (float) ($item->total_eur_mwh ?? $item->price_eur_mwh),
                    'total_c_kwh' => (float) ($item->total_c_kwh ?? $item->price_c_kwh),

                    // Componentes
                    'energy_eur_mwh' => (float) ($item->energy_eur_mwh ?? 0),
                    'energy_c_kwh' => (float) ($item->energy_c_kwh ?? 0),

                    'adjustment_eur_mwh' => (float) ($item->adjustment_eur_mwh ?? 0),
                    'adjustment_c_kwh' => (float) ($item->adjustment_c_kwh ?? 0),

                    'futures_eur_mwh' => (float) ($item->futures_eur_mwh ?? 0),
                    'futures_c_kwh' => (float) ($item->futures_c_kwh ?? 0),

                    'others_eur_mwh' => (float) ($item->others_eur_mwh ?? 0),
                    'others_c_kwh' => (float) ($item->others_c_kwh ?? 0),
                ];
            });

        if ($hours->isEmpty()) {
            throw new \Exception('No hay datos PVPC guardados para ' . $dateMadrid->format('d/m/Y'));
        }

        $averageTotal = round($hours->avg('total_c_kwh'), 2);
        $averageEnergy = round($hours->avg('energy_c_kwh'), 2);
        $averageAdjustment = round($hours->avg('adjustment_c_kwh'), 2);
        $averageFutures = round($hours->avg('futures_c_kwh'), 2);
        $averageOthers = round($hours->avg('others_c_kwh'), 2);

        return [
            'date' => $dateMadrid->format('Y-m-d'),
            'title' => 'Tarifa PVPC: precios para ' . $dateMadrid->format('d/m/Y'),

            'average_c_kwh' => $averageTotal,

            'components' => [
                'energy' => [
                    'label' => 'Energía',
                    'average_c_kwh' => $averageEnergy,
                    'percentage' => self::percentage($averageEnergy, $averageTotal),
                ],
                'adjustment' => [
                    'label' => 'Ajustes',
                    'average_c_kwh' => $averageAdjustment,
                    'percentage' => self::percentage($averageAdjustment, $averageTotal),
                ],
                'futures' => [
                    'label' => 'Mercado a plazo',
                    'average_c_kwh' => $averageFutures,
                    'percentage' => self::percentage($averageFutures, $averageTotal),
                ],
                'others' => [
                    'label' => 'Otros',
                    'average_c_kwh' => $averageOthers,
                    'percentage' => self::percentage($averageOthers, $averageTotal),
                ],
            ],

            'min' => $hours->sortBy('total_c_kwh')->first(),
            'max' => $hours->sortByDesc('total_c_kwh')->first(),

            'hours' => $hours->values(),
        ];
    }

    private static function headers($token): array
    {
        return [
            'Accept' => 'application/json; application/vnd.esios-api-v1+json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Token token="' . $token . '"',
            'x-api-key' => $token,
        ];
    }

    private static function toFloat($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private static function toCentKwh($eurMwh): float
    {
        return round(((float) $eurMwh) / 10, 4);
    }

    private static function parseHour($hour): int
    {
        if (!$hour) {
            return 0;
        }

        // Formato típico: 00-01, 01-02, 23-24
        $parts = explode('-', $hour);

        return (int) ($parts[0] ?? 0);
    }

    private static function sumFields(array $item, array $fields): float
    {
        $sum = 0;

        foreach ($fields as $field) {
            $sum += self::toFloat($item[$field] ?? 0);
        }

        return $sum;
    }

    private static function percentage($part, $total): float
    {
        if (!$total) {
            return 0;
        }

        return round(($part / $total) * 100);
    }
}