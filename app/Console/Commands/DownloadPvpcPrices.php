<?php

namespace App\Console\Commands;

use App\Http\Models\PvpcPrice;
use App\Services\EsiosPvpcService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DownloadPvpcPrices extends Command
{
    protected $signature = 'pvpc:download {date?} {--force}';

    protected $description = 'Descarga y guarda los precios PVPC desde ESIOS';

    public function handle()
    {
        try {
            $date = $this->argument('date')
                ? Carbon::parse($this->argument('date'), 'Europe/Madrid')
                : now('Europe/Madrid')->addDay();

            $dateString = $date->copy()
                ->timezone('Europe/Madrid')
                ->format('Y-m-d');

            if (!$this->option('force')) {
                $existing = PvpcPrice::where('date', $dateString)->count();

                if ($existing >= 24) {
                    $this->info('PVPC ya descargado para ' . $dateString . '. Registros existentes: ' . $existing);
                    return self::SUCCESS;
                }
            }

            $result = EsiosPvpcService::downloadAndStoreByDate($date);

            $this->info('PVPC descargado correctamente.');
            $this->info('Fecha: ' . $result['date']);
            $this->info('Registros guardados: ' . $result['saved']);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}