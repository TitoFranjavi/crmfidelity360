<?php

namespace App\Http\Models;

use Jenssegers\Mongodb\Eloquent\Model as Model;

class IndexedElectricityPrice extends Model
{
    protected $connection = "mongodb";
    protected $collection = "indexed_electricity_prices";

    public $timestamps = false;

    protected $fillable = [
        'date',
        'hour',
        'tariff',
        'period',

        'total_eur_mwh',
        'total_c_kwh',

        'market_eur_mwh',
        'market_c_kwh',

        'adjustment_eur_mwh',
        'adjustment_c_kwh',

        'futures_eur_mwh',
        'futures_c_kwh',

        'regulated_eur_mwh',
        'regulated_c_kwh',

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