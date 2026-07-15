<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Costes regulados de energía
    |--------------------------------------------------------------------------
    |
    | Valores en €/MWh.
    |
    */

    'energy_costs' => [

        '3.0TD' => [
            'P1' => [
                'toll_eur_mwh' => 27.511,
                'charge_eur_mwh' => 35.841,
            ],
            'P2' => [
                'toll_eur_mwh' => 12.376,
                'charge_eur_mwh' => 26.538,
            ],
            'P3' => [
                'toll_eur_mwh' => 4.943,
                'charge_eur_mwh' => 14.336,
            ],
            'P4' => [
                'toll_eur_mwh' => 2.627,
                'charge_eur_mwh' => 7.168,
            ],
            'P5' => [
                'toll_eur_mwh' => 0.111,
                'charge_eur_mwh' => 4.595,
            ],
            'P6' => [
                'toll_eur_mwh' => 0.031,
                'charge_eur_mwh' => 2.867,
            ],
        ],

        '6.1TD' => [
            'P1' => [
                'toll_eur_mwh' => 26.785,
                'charge_eur_mwh' => 19.489,
            ],
            'P2' => [
                'toll_eur_mwh' => 12.281,
                'charge_eur_mwh' => 14.436,
            ],
            'P3' => [
                'toll_eur_mwh' => 5.133,
                'charge_eur_mwh' => 7.795,
            ],
            'P4' => [
                'toll_eur_mwh' => 2.780,
                'charge_eur_mwh' => 3.898,
            ],
            'P5' => [
                'toll_eur_mwh' => 0.120,
                'charge_eur_mwh' => 2.499,
            ],
            'P6' => [
                'toll_eur_mwh' => 0.029,
                'charge_eur_mwh' => 1.559,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extras comerciales opcionales
    |--------------------------------------------------------------------------
    |
    | Valores en €/MWh.
    |
    |
    */

    'commercial_costs' => [

        '3.0TD' => [
            'margin_eur_mwh' => 0,
            'losses_eur_mwh' => 0,
            'other_eur_mwh' => 0,
        ],

        '6.1TD' => [
            'margin_eur_mwh' => 0,
            'losses_eur_mwh' => 0,
            'other_eur_mwh' => 0,
        ],
    ],
];