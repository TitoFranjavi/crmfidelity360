<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Configurar Carbon para que utilice el idioma español
        Carbon::setLocale('es');

        // Configurar locales para PHP (formato de fechas y tiempos)
        setlocale(LC_TIME, 'es_ES.UTF-8'); // Español (España)

        // Opcional: También configurar la clase Date de Laravel para español
        Date::setLocale('es');
    }
}
