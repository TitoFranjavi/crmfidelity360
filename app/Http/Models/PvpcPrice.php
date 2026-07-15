<?php

namespace App\Http\Models;

use Jenssegers\Mongodb\Eloquent\Model as Model;

class PvpcPrice extends Model
{
    protected $connection = "mongodb";
    protected $collection = "pvpc_prices";

    public $timestamps = false;

    protected $fillable = [
        'date',
        'hour',

        // Alias antiguos para que no rompa lo que ya pintaba Vue
        'price_eur_mwh',
        'price_c_kwh',

        // Total PVPC
        'total_eur_mwh',
        'total_c_kwh',

        // Energía / mercado diario e intradiario
        'energy_eur_mwh',
        'energy_c_kwh',

        // Servicios de ajuste
        'adjustment_eur_mwh',
        'adjustment_c_kwh',

        // Mercado a plazo
        'futures_eur_mwh',
        'futures_c_kwh',

        // Otros costes regulados
        'others_eur_mwh',
        'others_c_kwh',

        'raw_data',
        'createdAt',
        'updatedAt',
    ];

    protected $casts = [
        'raw_data' => 'array',
    ];
}