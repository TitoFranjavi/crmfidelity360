<?php

return [

    'one_time' => [

        'scans' => [

            'unit' => [
                'id' => 'unit',
                'title' => 'Estudios sueltos',
                'description' => 'Compra solo los estudios que necesites.',
                'amount' => 1,
                'price' => 0.25,
                'min_quantity' => 10,
                'max_quantity' => 3999996,
                'icon' => 'far fa-file',
                'highlighted' => true,
                'badge' => null,
                'features' => [
                    'Compra mínima: 10 estudios',
                    'Sin caducidad',
                ],
                'stripe_price_id' => env('STRIPE_SCAN_UNIT_PRICE_ID'),
            ],

            'pack_100' => [
                'id' => 'pack_100',
                'title' => 'Pack 100 estudios',
                'description' => 'Ideal para ampliar el saldo de forma puntual.',
                'amount' => 100,
                'price' => 20,
                'icon' => 'far fa-layer-group',
                'highlighted' => false,
                'badge' => 'Más popular',
                'features' => [
                    '100 estudios adicionales',
                    '0,20 €/estudio',
                    'Sin caducidad',
                ],
                'stripe_price_id' => env('STRIPE_SCAN_PACK_100_PRICE_ID'),
            ],

            'pack_500' => [
                'id' => 'pack_500',
                'title' => 'Pack 500 estudios',
                'description' => 'La mejor opción para equipos con mucho volumen.',
                'amount' => 500,
                'price' => 80,
                'icon' => 'far fa-boxes-stacked',
                'highlighted' => false,
                'badge' => 'Mejor precio',
                'features' => [
                    '500 estudios adicionales',
                    '0,16 €/estudio',
                    'Sin caducidad',
                ],
                'stripe_price_id' => env('STRIPE_SCAN_PACK_500_PRICE_ID'),
            ],

        ],

        'calls' => [

            'pack_600' => [
                'id' => 'pack_600',
                'title' => 'Pack 600 minutos',
                'description' => 'Ideal para ampliar el saldo de forma puntual.',
                'amount' => 600,
                'price' => 140,
                'icon' => 'far fa-layer-group',
                'highlighted' => false,
                'badge' => null,
                'features' => [
                    '0,233 €/minuto',
                    'Sin caducidad',
                ],
                'stripe_price_id' => env('STRIPE_CALLS_PACK_600_PRICE_ID'),
            ],

            'pack_2000' => [
                'id' => 'pack_2000',
                'title' => 'Pack 2.000 minutos',
                'description' => 'Para empresas en crecimiento con más necesidades de minutos.',
                'amount' => 2000,
                'price' => 440,
                'icon' => 'far fa-layer-group',
                'highlighted' => true,
                'badge' => 'Más popular',
                'features' => [
                    '0,22 €/minuto',
                    'Sin caducidad',
                ],
                'stripe_price_id' => env('STRIPE_CALLS_PACK_2000_PRICE_ID'),
            ],

            'pack_6000' => [
                'id' => 'pack_6000',
                'title' => 'Pack 6.000 minutos',
                'description' => 'Para grandes empresas con alto consumo de minutos.',
                'amount' => 6000,
                'price' => 1200,
                'icon' => 'far fa-layer-group',
                'highlighted' => false,
                'badge' => 'Mejor precio',
                'features' => [
                    '0,20 €/minuto',
                    'Sin caducidad',
                ],
                'stripe_price_id' => env('STRIPE_CALLS_PACK_6000_PRICE_ID'),
            ],

        ],

    ],

    'recurring' => [

        'users' => [

            'pack_30' => [
                'id' => 'pack_30',
                'title' => 'Pack Extra 30',
                'description' => 'Ideal para equipos en crecimiento.',
                'amount' => 30,
                'price' => 55,
                'icon' => 'far fa-users',
                'highlighted' => false,
                'badge' => null,
                'features' => [
                    '30 usuarios adicionales',
                    '1,83 €/usuario/mes',
                    'Pago mensual recurrente',
                ],
                'stripe_price_id' => env('STRIPE_USER_PACK_30_PRICE_ID'),
            ],

            'pack_100' => [
                'id' => 'pack_100',
                'title' => 'Pack Extra 100',
                'description' => 'Para equipos comerciales con más volumen.',
                'amount' => 100,
                'price' => 150,
                'icon' => 'far fa-users',
                'highlighted' => true,
                'badge' => 'Más popular',
                'features' => [
                    '100 usuarios adicionales',
                    '1,50 €/usuario/mes',
                    'Pago mensual recurrente',
                ],
                'stripe_price_id' => env('STRIPE_USER_PACK_100_PRICE_ID'),
            ],

            'pack_500' => [
                'id' => 'pack_500',
                'title' => 'Pack Empresa 500',
                'description' => 'Para empresas grandes con muchos usuarios.',
                'amount' => 500,
                'price' => 500,
                'icon' => 'far fa-building-user',
                'highlighted' => false,
                'badge' => 'Mejor precio',
                'features' => [
                    '500 usuarios adicionales',
                    '1,00 €/usuario/mes',
                    'Pago mensual recurrente',
                ],
                'stripe_price_id' => env('STRIPE_USER_PACK_500_PRICE_ID'),
            ],

        ],

        'monitoring' => [

            'pack_50' => [
                'id' => 'pack_50',
                'title' => 'Pack 50 monitorizaciones',
                'description' => 'Ideal para ampliar la monitorización en equipos pequeños.',
                'amount' => 50,
                'price' => 50,
                'icon' => 'far fa-chart-line',
                'highlighted' => false,
                'badge' => null,
                'features' => [
                    '50 monitorizaciones adicionales',
                    '0,98 €/monitorización',
                    'Pago mensual recurrente',
                ],
                'stripe_price_id' => env('STRIPE_MONITORING_PACK_50_PRICE_ID'),
            ],

            'pack_200' => [
                'id' => 'pack_200',
                'title' => 'Pack 200 monitorizaciones',
                'description' => 'Pensado para equipos con una cartera de suministros más amplia.',
                'amount' => 200,
                'price' => 150,
                'icon' => 'far fa-layer-group',
                'highlighted' => true,
                'badge' => 'Más popular',
                'features' => [
                    '200 monitorizaciones adicionales',
                    '0,75 €/monitorización',
                    'Pago mensual recurrente',
                ],
                'stripe_price_id' => env('STRIPE_MONITORING_PACK_200_PRICE_ID'),
            ],

            'pack_1000' => [
                'id' => 'pack_1000',
                'title' => 'Pack 1000 monitorizaciones',
                'description' => 'Para empresas con alto volumen de monitorizaciones activas.',
                'amount' => 1000,
                'price' => 450,
                'icon' => 'far fa-building',
                'highlighted' => false,
                'badge' => 'Mejor precio',
                'features' => [
                    '1000 monitorizaciones adicionales',
                    '0,50 €/monitorización',
                    'Pago mensual recurrente',
                ],
                'stripe_price_id' => env('STRIPE_MONITORING_PACK_1000_PRICE_ID'),
            ],

        ]

    ],

];
