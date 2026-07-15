<?php

namespace App\Console\Commands;

use App\Http\Models\IndexedElectricityPrice;
use App\Http\Models\PvpcPrice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use MongoDB\BSON\UTCDateTime;

class BackfillIndexedElectricityPrices extends Command
{
    protected $signature = 'indexed:backfill 
                            {--from=} 
                            {--to=} 
                            {--months=6}
                            {--tariff=}
                            {--force}';

    protected $description = 'Genera histórico estimado 3.0TD / 6.1TD desde el histórico PVPC guardado';

    public function handle()
    {
        try {
            $to = $this->option('to')
                ? Carbon::parse($this->option('to'), 'Europe/Madrid')
                : now('Europe/Madrid')->subDay();

            $from = $this->option('from')
                ? Carbon::parse($this->option('from'), 'Europe/Madrid')
                : $to->copy()->subMonths((int) $this->option('months'));

            $from = $from->copy()->startOfDay();
            $to = $to->copy()->endOfDay();

            $fromString = $from->format('Y-m-d');
            $toString = $to->format('Y-m-d');

            $tariffOption = $this->option('tariff');

            $tariffs = $tariffOption
                ? [strtoupper($tariffOption)]
                : ['3.0TD', '6.1TD'];

            foreach ($tariffs as $tariff) {
                if (!in_array($tariff, ['3.0TD', '6.1TD'], true)) {
                    $this->error("Tarifa no válida: {$tariff}. Usa 3.0TD o 6.1TD.");
                    return self::FAILURE;
                }
            }

            $this->info('Generando histórico indexado estimado...');
            $this->info('Desde: ' . $fromString);
            $this->info('Hasta: ' . $toString);
            $this->info('Tarifas: ' . implode(', ', $tariffs));

            $pvpcPrices = PvpcPrice::where('date', '>=', $fromString)
                ->where('date', '<=', $toString)
                ->orderBy('date', 'asc')
                ->orderBy('hour', 'asc')
                ->get();

            if ($pvpcPrices->isEmpty()) {
                $this->error('No hay histórico PVPC en pvpc_prices para ese rango.');
                return self::FAILURE;
            }

            $saved = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($pvpcPrices as $pvpcPrice) {
                $date = $pvpcPrice->date;
                $hour = (int) ($pvpcPrice->hour ?? 0);

                $pvpcTotalCKwh = $this->getPvpcValue($pvpcPrice);

                if ($pvpcTotalCKwh === null) {
                    continue;
                }

                $dateCarbon = Carbon::parse($date, 'Europe/Madrid');

                $pvpc20Period = $this->getPvpc20Period($dateCarbon, $hour);
                $pvpc20RegulatedCKwh = $this->getPvpc20RegulatedCost($pvpc20Period);

                /*
                 * Estimación:
                 * quitamos el regulado 2.0TD aproximado del PVPC histórico
                 * y añadimos el regulado de 3.0TD / 6.1TD según calendario P1-P6.
                 */
                $baseWithoutPvpc20Regulated = max($pvpcTotalCKwh - $pvpc20RegulatedCKwh, 0);

                foreach ($tariffs as $tariff) {
                    $period = $this->getIndexedPeriod($dateCarbon, $hour);
                    $targetRegulatedCKwh = $this->getIndexedRegulatedCost($tariff, $period);

                    $totalCKwh = $baseWithoutPvpc20Regulated + $targetRegulatedCKwh;

                    $existing = IndexedElectricityPrice::where('date', $date)
                        ->where('hour', $hour)
                        ->where('tariff', $tariff)
                        ->first();

                    if ($existing && !$this->option('force')) {
                        $skipped++;
                        continue;
                    }

                    $now = new UTCDateTime((int) round(microtime(true) * 1000));

                    $data = [
                        'date' => $date,
                        'hour' => $hour,
                        'tariff' => $tariff,
                        'period' => $period,

                        'total_eur_mwh' => round($totalCKwh * 10, 4),
                        'total_c_kwh' => round($totalCKwh, 4),

                        /*
                         * No tenemos desglose real histórico.
                         * Guardamos una base estimada y el regulado separado.
                         */
                        'market_eur_mwh' => round($baseWithoutPvpc20Regulated * 10, 4),
                        'market_c_kwh' => round($baseWithoutPvpc20Regulated, 4),

                        'regulated_eur_mwh' => round($targetRegulatedCKwh * 10, 4),
                        'regulated_c_kwh' => round($targetRegulatedCKwh, 4),

                        'adjustment_eur_mwh' => 0,
                        'adjustment_c_kwh' => 0,

                        'futures_eur_mwh' => 0,
                        'futures_c_kwh' => 0,

                        'others_eur_mwh' => 0,
                        'others_c_kwh' => 0,

                        'raw_data' => [
                            'source' => 'estimated_from_pvpc_historical',
                            'pvpc_total_c_kwh' => round($pvpcTotalCKwh, 4),
                            'pvpc_20_period' => $pvpc20Period,
                            'pvpc_20_regulated_c_kwh' => round($pvpc20RegulatedCKwh, 4),
                            'base_without_pvpc_20_regulated_c_kwh' => round($baseWithoutPvpc20Regulated, 4),
                            'target_period' => $period,
                            'target_regulated_c_kwh' => round($targetRegulatedCKwh, 4),
                        ],

                        'updatedAt' => $now,
                    ];

                    if ($existing) {
                        $existing->update($data);
                        $updated++;
                    } else {
                        $data['createdAt'] = $now;
                        IndexedElectricityPrice::create($data);
                        $saved++;
                    }
                }
            }

            $this->info('Proceso terminado.');
            $this->info('Registros creados: ' . $saved);
            $this->info('Registros actualizados: ' . $updated);
            $this->info('Registros saltados: ' . $skipped);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function getPvpcValue($price): ?float
    {
        $value = $price->total_c_kwh ?? $price->price_c_kwh ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function getIndexedRegulatedCost(string $tariff, string $period): float
    {
        $energyCosts = config('indexed_tariffs.energy_costs', []);

        $data = $energyCosts[$tariff][$period] ?? null;

        if (!$data) {
            return 0;
        }

        $eurMwh = (float) ($data['toll_eur_mwh'] ?? 0)
            + (float) ($data['charge_eur_mwh'] ?? 0);

        return $eurMwh / 10;
    }

    private function getPvpc20RegulatedCost(string $period): float
    {
        /*
         * Peajes + cargos energía 2.0TD 2026.
         * Valores originales en €/kWh:
         * P1: 0,097553
         * P2: 0,029267
         * P3: 0,003292
         *
         * Aquí los usamos en c€/kWh.
         */
        $costs = [
            'P1' => 9.7553,
            'P2' => 2.9267,
            'P3' => 0.3292,
        ];

        return $costs[$period] ?? 0;
    }

    private function getPvpc20Period(Carbon $date, int $hour): string
    {
        $dateMadrid = $date->copy()->timezone('Europe/Madrid');

        if ($dateMadrid->isWeekend() || $this->isNationalHoliday($dateMadrid)) {
            return 'P3';
        }

        if ($hour >= 0 && $hour < 8) {
            return 'P3';
        }

        if (
            ($hour >= 8 && $hour < 10) ||
            ($hour >= 14 && $hour < 18) ||
            ($hour >= 22 && $hour < 24)
        ) {
            return 'P2';
        }

        if (
            ($hour >= 10 && $hour < 14) ||
            ($hour >= 18 && $hour < 22)
        ) {
            return 'P1';
        }

        return 'P3';
    }

    private function getIndexedPeriod(Carbon $date, int $hour): string
    {
        $dateMadrid = $date->copy()->timezone('Europe/Madrid');

        if ($dateMadrid->isWeekend() || $this->isNationalHoliday($dateMadrid)) {
            return 'P6';
        }

        $month = (int) $dateMadrid->format('n');

        return $this->getPeninsulaPeriodByMonthAndHour($month, $hour);
    }

    private function getPeninsulaPeriodByMonthAndHour(int $month, int $hour): string
    {
        $middlePeriods = [
            1 => 'P2',
            2 => 'P2',
            3 => 'P3',
            4 => 'P5',
            5 => 'P5',
            6 => 'P4',
            7 => 'P2',
            8 => 'P4',
            9 => 'P4',
            10 => 'P5',
            11 => 'P3',
            12 => 'P2',
        ];

        $highPeriods = [
            1 => 'P1',
            2 => 'P1',
            3 => 'P2',
            4 => 'P4',
            5 => 'P4',
            6 => 'P3',
            7 => 'P1',
            8 => 'P3',
            9 => 'P3',
            10 => 'P4',
            11 => 'P2',
            12 => 'P1',
        ];

        if ($hour >= 0 && $hour < 8) {
            return 'P6';
        }

        if (
            ($hour >= 8 && $hour < 9) ||
            ($hour >= 14 && $hour < 18) ||
            ($hour >= 22 && $hour < 24)
        ) {
            return $middlePeriods[$month] ?? 'P6';
        }

        if (
            ($hour >= 9 && $hour < 14) ||
            ($hour >= 18 && $hour < 22)
        ) {
            return $highPeriods[$month] ?? 'P6';
        }

        return 'P6';
    }

    private function isNationalHoliday(Carbon $date): bool
    {
        $holidaysByYear = [
            2026 => [
                '2026-01-01',
                '2026-01-06',
                '2026-04-03',
                '2026-05-01',
                '2026-08-15',
                '2026-10-12',
                '2026-11-01',
                '2026-12-06',
                '2026-12-08',
                '2026-12-25',
            ],
        ];

        $year = (int) $date->format('Y');
        $dateString = $date->format('Y-m-d');

        return in_array($dateString, $holidaysByYear[$year] ?? [], true);
    }
}