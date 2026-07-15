<?php

namespace App\Console\Commands;

use App\Http\Models\IndexedElectricityPrice;
use App\Services\IndexedElectricityPriceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DownloadIndexedElectricityPrices extends Command
{
    protected $signature = 'indexed:download {tariff} {date?} {--force}';

    protected $description = 'Descarga y guarda precios indexados 3.0TD / 6.1TD';

    public function handle()
    {
        try {
            $tariff = strtoupper($this->argument('tariff'));

            if (!in_array($tariff, ['3.0TD', '6.1TD'])) {
                $this->error('Tarifa no válida. Usa 3.0TD o 6.1TD.');
                return self::FAILURE;
            }

            $date = $this->argument('date')
                ? Carbon::parse($this->argument('date'), 'Europe/Madrid')
                : now('Europe/Madrid')->addDay();

            $dateString = $date->copy()
                ->timezone('Europe/Madrid')
                ->format('Y-m-d');

            if (!$this->option('force')) {
                $existing = IndexedElectricityPrice::where('date', $dateString)
                    ->where('tariff', $tariff)
                    ->count();

                if ($existing >= 24) {
                    $this->info("Indexado {$tariff} ya descargado para {$dateString}. Registros existentes: {$existing}");
                    return self::SUCCESS;
                }
            }

            $result = IndexedElectricityPriceService::downloadAndStoreByDate($date, $tariff);

            $this->info('Indexado descargado correctamente.');
            $this->info('Fecha: ' . $result['date']);
            $this->info('Tarifa: ' . $result['tariff']);
            $this->info('Registros guardados: ' . $result['saved']);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}