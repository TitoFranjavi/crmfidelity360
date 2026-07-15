<?php

namespace App\Console\Commands;

use App\Services\EsiosPvpcHistoricalService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillPvpcHistoricalPrices extends Command
{
    protected $signature = 'pvpc:backfill 
                            {--from=} 
                            {--to=} 
                            {--months=6}
                            {--force}';

    protected $description = 'Descarga histórico PVPC horario desde ESIOS y lo guarda en MongoDB';

    public function handle()
    {
        try {
            $to = $this->option('to')
                ? Carbon::parse($this->option('to'), 'Europe/Madrid')
                : now('Europe/Madrid')->subDay();

            $from = $this->option('from')
                ? Carbon::parse($this->option('from'), 'Europe/Madrid')
                : $to->copy()->subMonths((int) $this->option('months'));

            $this->info('Descargando histórico PVPC...');
            $this->info('Desde: ' . $from->format('Y-m-d'));
            $this->info('Hasta: ' . $to->format('Y-m-d'));

            $result = EsiosPvpcHistoricalService::downloadRange(
                $from,
                $to,
                $this->option('force')
            );

            $this->info('Histórico descargado correctamente.');
            $this->info('Desde: ' . $result['from']);
            $this->info('Hasta: ' . $result['to']);
            $this->info('Registros guardados: ' . $result['saved']);
            $this->info('Registros saltados: ' . $result['skipped']);
            $this->info('Bloques consultados: ' . $result['chunks']);

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}