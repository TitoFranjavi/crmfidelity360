<?php

namespace App\Services;

use App\Http\Models\PvpcPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use MongoDB\BSON\UTCDateTime;

class EsiosPvpcHistoricalService
{
    public static function downloadRange($from, $to, bool $force = false): array
    {
        $token = config('services.esios.token');
        $baseUrl = rtrim(config('services.esios.base_url'), '/');

        if (!$token) {
            throw new \Exception('Falta configurar ESIOS_TOKEN en el .env');
        }

        $fromDate = Carbon::parse($from, 'Europe/Madrid')
            ->timezone('Europe/Madrid')
            ->startOfDay();

        $toDate = Carbon::parse($to, 'Europe/Madrid')
            ->timezone('Europe/Madrid')
            ->endOfDay();

        if ($fromDate->gt($toDate)) {
            throw new \Exception('La fecha desde no puede ser posterior a la fecha hasta.');
        }

        $totalSaved = 0;
        $totalSkipped = 0;
        $chunks = 0;

        $currentStart = $fromDate->copy();

        while ($currentStart->lte($toDate)) {
            $currentEnd = $currentStart->copy()->addDays(30)->endOfDay();

            if ($currentEnd->gt($toDate)) {
                $currentEnd = $toDate->copy();
            }

            $result = self::downloadChunk($currentStart, $currentEnd, $force);

            $totalSaved += $result['saved'];
            $totalSkipped += $result['skipped'];
            $chunks++;

            // Pausa pequeña para no hacer peticiones demasiado seguidas
            usleep(300000);

            $currentStart = $currentEnd->copy()->addSecond()->startOfDay();
        }

        return [
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
            'saved' => $totalSaved,
            'skipped' => $totalSkipped,
            'chunks' => $chunks,
            'source' => 'ESIOS indicator 1001',
        ];
    }

    private static function downloadChunk(Carbon $fromDate, Carbon $toDate, bool $force): array
    {
        $token = config('services.esios.token');
        $baseUrl = rtrim(config('services.esios.base_url'), '/');

        $response = Http::withHeaders(self::headers($token))
            ->timeout(60)
            ->get($baseUrl . '/indicators/1001', [
                'start_date' => $fromDate->copy()->timezone('UTC')->format('Y-m-d\TH:i:s\Z'),
                'end_date' => $toDate->copy()->timezone('UTC')->format('Y-m-d\TH:i:s\Z'),
                'time_trunc' => 'hour',
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando histórico PVPC ESIOS: ' .
                $response->status() . ' - ' . $response->body()
            );
        }

        $values = collect($response->json('indicator.values', []));

        $saved = 0;
        $skipped = 0;

        foreach ($values as $value) {
            $dateTimeRaw = $value['datetime'] ?? null;
            $priceEurMwh = $value['value'] ?? null;

            if (!$dateTimeRaw || $priceEurMwh === null) {
                continue;
            }

            $dateTimeMadrid = Carbon::parse($dateTimeRaw)
                ->timezone('Europe/Madrid');

            $date = $dateTimeMadrid->format('Y-m-d');
            $hour = (int) $dateTimeMadrid->format('G');

            $existing = PvpcPrice::where('date', $date)
                ->where('hour', $hour)
                ->first();

            if ($existing && !$force) {
                $skipped++;
                continue;
            }

            $priceEurMwh = self::toFloat($priceEurMwh);
            $priceCKwh = self::toCentKwh($priceEurMwh);

            $data = [
                'date' => $date,
                'hour' => $hour,

                // Alias antiguos
                'price_eur_mwh' => round($priceEurMwh, 4),
                'price_c_kwh' => $priceCKwh,

                // Total PVPC
                'total_eur_mwh' => round($priceEurMwh, 4),
                'total_c_kwh' => $priceCKwh,

                // En histórico no tenemos desglose real por componentes
                'energy_eur_mwh' => 0,
                'energy_c_kwh' => 0,

                'adjustment_eur_mwh' => 0,
                'adjustment_c_kwh' => 0,

                'futures_eur_mwh' => 0,
                'futures_c_kwh' => 0,

                'others_eur_mwh' => 0,
                'others_c_kwh' => 0,

                'raw_data' => $value,
                'updatedAt' => new UTCDateTime(Carbon::now('UTC')->getTimestampMs()),
            ];

            if ($existing) {
                $existing->update($data);
            } else {
                $data['createdAt'] = new UTCDateTime(Carbon::now('UTC')->getTimestampMs());
                PvpcPrice::create($data);
            }

            $saved++;
        }

        return [
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
            'saved' => $saved,
            'skipped' => $skipped,
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
}