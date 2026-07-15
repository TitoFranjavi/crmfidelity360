<?php

namespace App\Services;

use App\Http\Models\IndexedElectricityPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use MongoDB\BSON\UTCDateTime;

class IndexedElectricityPriceService
{
    public static function downloadAndStoreByDate($date, string $tariff): array
    {
        $tariff = strtoupper($tariff);

        if (!in_array($tariff, ['3.0TD', '6.1TD'])) {
            throw new \Exception('Tarifa no válida. Usa 3.0TD o 6.1TD.');
        }

        $token = config('services.esios.token');
        $baseUrl = rtrim(config('services.esios.base_url'), '/');

        if (!$token) {
            throw new \Exception('Falta configurar ESIOS_TOKEN en el .env');
        }

        $dateMadrid = Carbon::parse($date, 'Europe/Madrid')
            ->timezone('Europe/Madrid')
            ->startOfDay();

        $dateEs = $dateMadrid->format('d/m/Y');

        /*
         * Usamos el fichero PVPC como fuente de componentes horarios:
         * - PMHPCB: mercado diario/intradiario
         * - SAHPCB: ajustes
         * - TAHPCB: mercado a plazo
         * - otros campos del sistema para "otros"
         *
         * Luego sustituimos el bloque regulado por nuestros costes 3.0TD / 6.1TD.
         */
        $response = Http::withHeaders(self::headers($token))
            ->timeout(30)
            ->get($baseUrl . '/archives/70/download', [
                'locale' => 'es',
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando fichero ESIOS: ' . $response->status() . ' - ' . $response->body()
            );
        }

        $items = collect($response->json('PVPC', []));

        if ($items->isEmpty()) {
            throw new \Exception('ESIOS ha respondido, pero el fichero viene vacío.');
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
                'El fichero no contiene datos para ' . $dateEs .
                '. Fechas disponibles: ' . implode(', ', $availableDates)
            );
        }

        $saved = 0;

        $itemsForDate->each(function ($item) use ($dateMadrid, $tariff, &$saved) {
            $hour = self::parseHour($item['Hora'] ?? null);
            $period = self::getPeriod($tariff, $dateMadrid, $hour);

            $market = self::toFloat($item['PMHPCB'] ?? 0);
            $adjustment = self::toFloat($item['SAHPCB'] ?? 0);
            $futures = self::toFloat($item['TAHPCB'] ?? 0);

            $others = self::sumFields($item, [
                'FOMPCB',
                'FOSPCB',
                'INTPCB',
                'PCAPPCB',
                'EDSRPCB',
            ]);

            $regulated = self::getRegulatedCost($tariff, $period);
            $commercial = self::getCommercialCost($tariff);

            $total = $market
                + $adjustment
                + $futures
                + $others
                + $regulated
                + $commercial;

            $existing = IndexedElectricityPrice::where('date', $dateMadrid->format('Y-m-d'))
                ->where('hour', $hour)
                ->where('tariff', $tariff)
                ->first();

            $data = [
                'date' => $dateMadrid->format('Y-m-d'),
                'hour' => $hour,
                'tariff' => $tariff,
                'period' => $period,

                'total_eur_mwh' => round($total, 4),
                'total_c_kwh' => self::toCentKwh($total),

                'market_eur_mwh' => round($market, 4),
                'market_c_kwh' => self::toCentKwh($market),

                'adjustment_eur_mwh' => round($adjustment, 4),
                'adjustment_c_kwh' => self::toCentKwh($adjustment),

                'futures_eur_mwh' => round($futures, 4),
                'futures_c_kwh' => self::toCentKwh($futures),

                'regulated_eur_mwh' => round($regulated, 4),
                'regulated_c_kwh' => self::toCentKwh($regulated),

                'others_eur_mwh' => round($others + $commercial, 4),
                'others_c_kwh' => self::toCentKwh($others + $commercial),

                'raw_data' => $item,
                'updatedAt' => new UTCDateTime(Carbon::now('UTC')->getTimestampMs()),
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                $data['createdAt'] = new UTCDateTime(Carbon::now('UTC')->getTimestampMs());
                IndexedElectricityPrice::create($data);
            }

            $saved++;
        });

        return [
            'date' => $dateMadrid->format('Y-m-d'),
            'tariff' => $tariff,
            'saved' => $saved,
            'source' => 'ESIOS archives/70/download + indexed tariff config',
        ];
    }

    public static function getStoredByDate($date, string $tariff): array
    {
        $tariff = strtoupper($tariff);

        $dateMadrid = Carbon::parse($date, 'Europe/Madrid')
            ->timezone('Europe/Madrid')
            ->startOfDay();

        $hours = IndexedElectricityPrice::where('date', $dateMadrid->format('Y-m-d'))
            ->where('tariff', $tariff)
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',
                    'period' => $item->period,

                    'total_eur_mwh' => (float) $item->total_eur_mwh,
                    'total_c_kwh' => (float) $item->total_c_kwh,

                    'market_eur_mwh' => (float) $item->market_eur_mwh,
                    'market_c_kwh' => (float) $item->market_c_kwh,

                    'adjustment_eur_mwh' => (float) $item->adjustment_eur_mwh,
                    'adjustment_c_kwh' => (float) $item->adjustment_c_kwh,

                    'futures_eur_mwh' => (float) $item->futures_eur_mwh,
                    'futures_c_kwh' => (float) $item->futures_c_kwh,

                    'regulated_eur_mwh' => (float) $item->regulated_eur_mwh,
                    'regulated_c_kwh' => (float) $item->regulated_c_kwh,

                    'others_eur_mwh' => (float) $item->others_eur_mwh,
                    'others_c_kwh' => (float) $item->others_c_kwh,
                ];
            });

        if ($hours->isEmpty()) {
            throw new \Exception('No hay datos indexados guardados para ' . $tariff . ' en ' . $dateMadrid->format('d/m/Y'));
        }

        $averageTotal = round($hours->avg('total_c_kwh'), 2);
        $averageMarket = round($hours->avg('market_c_kwh'), 2);
        $averageAdjustment = round($hours->avg('adjustment_c_kwh'), 2);
        $averageFutures = round($hours->avg('futures_c_kwh'), 2);
        $averageRegulated = round($hours->avg('regulated_c_kwh'), 2);
        $averageOthers = round($hours->avg('others_c_kwh'), 2);

        return [
            'date' => $dateMadrid->format('Y-m-d'),
            'tariff' => $tariff,
            'title' => 'Indexado ' . $tariff . ': precios para ' . $dateMadrid->format('d/m/Y'),

            'average_c_kwh' => $averageTotal,

            'components' => [
                'market' => [
                    'label' => 'Mercado',
                    'average_c_kwh' => $averageMarket,
                    'percentage' => self::percentage($averageMarket, $averageTotal),
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
                'regulated' => [
                    'label' => 'Peajes y cargos',
                    'average_c_kwh' => $averageRegulated,
                    'percentage' => self::percentage($averageRegulated, $averageTotal),
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

    private static function getRegulatedCost(string $tariff, string $period): float
    {
        $energyCosts = config('indexed_tariffs.energy_costs', []);

        $data = $energyCosts[$tariff][$period] ?? null;

        if (!$data) {
            return 0;
        }

        return (float) ($data['toll_eur_mwh'] ?? 0)
            + (float) ($data['charge_eur_mwh'] ?? 0);
    }

    private static function getCommercialCost(string $tariff): float
    {
        $commercialCosts = config('indexed_tariffs.commercial_costs', []);

        $data = $commercialCosts[$tariff] ?? null;

        if (!$data) {
            return 0;
        }

        return (float) ($data['margin_eur_mwh'] ?? 0)
            + (float) ($data['losses_eur_mwh'] ?? 0)
            + (float) ($data['other_eur_mwh'] ?? 0);
    }

    private static function getPeriod(string $tariff, Carbon $date, int $hour): string
    {
        /*
        * Calendario Península 3.0TD / 6.1TD.
        *
        * Si es sábado, domingo o festivo nacional => P6 todo el día.
        * Si no, se aplica la tabla mensual P1-P6.
        */

        $dateMadrid = $date->copy()->timezone('Europe/Madrid');

        if ($dateMadrid->isWeekend() || self::isNationalHoliday($dateMadrid)) {
            return 'P6';
        }

        $month = (int) $dateMadrid->format('n');

        return self::getPeninsulaPeriodByMonthAndHour($month, $hour);
    }

    private static function getPeninsulaPeriodByMonthAndHour(int $month, int $hour): string
    {
        /*
        * Tabla Península 3.0TD / 6.1TD.
        *
        * Horas recibidas:
        * 0  = 00:00 - 01:00
        * 1  = 01:00 - 02:00
        * ...
        * 23 = 23:00 - 00:00
        */

        $middlePeriods = [
            1 => 'P2',  // Enero
            2 => 'P2',  // Febrero
            3 => 'P3',  // Marzo
            4 => 'P5',  // Abril
            5 => 'P5',  // Mayo
            6 => 'P4',  // Junio
            7 => 'P2',  // Julio
            8 => 'P4',  // Agosto
            9 => 'P4',  // Septiembre
            10 => 'P5', // Octubre
            11 => 'P3', // Noviembre
            12 => 'P2', // Diciembre
        ];

        $highPeriods = [
            1 => 'P1',  // Enero
            2 => 'P1',  // Febrero
            3 => 'P2',  // Marzo
            4 => 'P4',  // Abril
            5 => 'P4',  // Mayo
            6 => 'P3',  // Junio
            7 => 'P1',  // Julio
            8 => 'P3',  // Agosto
            9 => 'P3',  // Septiembre
            10 => 'P4', // Octubre
            11 => 'P2', // Noviembre
            12 => 'P1', // Diciembre
        ];

        // 00:00 - 08:00
        if ($hour >= 0 && $hour < 8) {
            return 'P6';
        }

        // 08:00 - 09:00
        // 14:00 - 18:00
        // 22:00 - 00:00
        if (
            ($hour >= 8 && $hour < 9) ||
            ($hour >= 14 && $hour < 18) ||
            ($hour >= 22 && $hour < 24)
        ) {
            return $middlePeriods[$month] ?? 'P6';
        }

        // 09:00 - 14:00
        // 18:00 - 22:00
        if (
            ($hour >= 9 && $hour < 14) ||
            ($hour >= 18 && $hour < 22)
        ) {
            return $highPeriods[$month] ?? 'P6';
        }

        return 'P6';
    }

    private static function isNationalHoliday(Carbon $date): bool
    {
        /*
        * Festivos nacionales comunes 2026.
        *
        * Si más adelante queréis contemplar festivos autonómicos/locales,
        * se añaden aquí o se pasa a una tabla/config.
        */

        $holidaysByYear = [
            2026 => [
                '2026-01-01', // Año Nuevo
                '2026-01-06', // Reyes
                '2026-04-03', // Viernes Santo
                '2026-05-01', // Fiesta del Trabajo
                '2026-08-15', // Asunción
                '2026-10-12', // Fiesta Nacional
                '2026-11-01', // Todos los Santos
                '2026-12-06', // Constitución
                '2026-12-08', // Inmaculada
                '2026-12-25', // Navidad
            ],
        ];

        $year = (int) $date->format('Y');
        $dateString = $date->format('Y-m-d');

        return in_array($dateString, $holidaysByYear[$year] ?? [], true);
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