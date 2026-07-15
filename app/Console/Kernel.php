<?php

namespace App\Console;


use App\Http\Controllers\LiquidationController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        //Ejecuta función para hacer las liquidaciones
        $schedule->call([LiquidationController::class, 'liquidate'])->monthlyOn(26, '8:12');

        //Ejecuta cada hora el comando para cerrar fichajes antiguos
        $schedule->command('signings:close-old')->hourly();

        $schedule->call([\App\Http\Controllers\ScrapingController::class, 'confirmarEntrada'])->everyTwoHours();


        // Fidelity360: revisión diaria de contratos para flujo Renovar -> Anulado
        $schedule->command('orders:fidelity-renewal-flow')->dailyAt('03:00');

        //Ejecuta cada minuto la cola
        $schedule->command('queue:work --once --sleep=3 --tries=3 --timeout=60')->everyMinute();

        //Ejecuta la descarga de datos ESIOS
        // Ejecuta la descarga de datos ESIOS PVPC
        $schedule->command('pvpc:download')
            ->dailyAt('20:30')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/pvpc-download.log'));

        $schedule->command('pvpc:download')
            ->dailyAt('21:30')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/pvpc-download.log'));

        $schedule->command('pvpc:download')
            ->dailyAt('22:30')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/pvpc-download.log'));

        // INDEXADO 3.0TD
        $schedule->command('indexed:download 3.0TD')
            ->dailyAt('20:40')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/indexed-download.log'));

        $schedule->command('indexed:download 3.0TD')
            ->dailyAt('21:40')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/indexed-download.log'));

        $schedule->command('indexed:download 3.0TD')
            ->dailyAt('22:40')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/indexed-download.log'));


        // INDEXADO 6.1TD
        $schedule->command('indexed:download 6.1TD')
            ->dailyAt('20:45')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/indexed-download.log'));

        $schedule->command('indexed:download 6.1TD')
            ->dailyAt('21:45')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/indexed-download.log'));

        $schedule->command('indexed:download 6.1TD')
            ->dailyAt('22:45')
            ->timezone('Europe/Madrid')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/indexed-download.log'));
            }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
