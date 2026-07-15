<?php

return [
    [
        'id' => 0,
        'name' => 'Starter',
        'price' => 60,
        'annualPrice' => 600,
        'included'=> [
            'users' => [
                'amount' => 20,
                'title' => 'Usuarios activos: 20',
                'unit' => 'once'
            ],
            'scans' => [
                'amount' => 100,
                'title' => 'Estudios escaneo/mes: 100',
                'unit' => 'month'
            ],
            'monitoring' => [
                'amount' => 5,
                'title' => 'Monitorizaciones: 5',
                'unit' => 'once'
            ],
            'support' => [
                'title' => 'Soporte: Online básico',
            ],
            'comparator' => [
                'title' => 'Comparador tarifas: Limitado',
            ]
        ],
        'notIncluded' => [
            'calls' => [
                'title' => 'Llamadas de verificación',
            ],
            'whatsapp' => [
                'title' => 'WhatsApp CRM',
            ],
        ],
        'stripe' => [
            'id' => 'prod_USZMcu9FTQyIZ2',
            'monthly' => 'price_1TTeDUQhTiksrklnuMJLuQwY',
            'annual' => 'price_1TTeDUQhTiksrklnQpvcwvqB',
            'free_monthly' => 'price_1TZW2nH2rNMfHZ9KINcvw9Yw',
            'free_annual' => 'price_1TZsgdH2rNMfHZ9KUtSWQB84'
        ]
    ],
    [
        'id' => 1,
        'name' => 'Pro',
        'price' => 397,
        'annualPrice' => 3600,
        'included'=> [
            'users' => [
                'amount' => 50,
                'title' => 'Usuarios activos: 50',
                'unit' => 'once'
            ],
            'scans' => [
                'amount' => 1000,
                'title' => 'Estudios escaneo/mes: 1.000',
                'unit' => 'month'
            ],
            'monitoring' => [
                'amount' => 50,
                'title' => 'Monitorizaciones: 50',
                'unit' => 'once'
            ],
            'whatsapp' => [
                'amount' => null,
                'title' => 'WhatsApp CRM: Ilimitado',
            ],
            'support' => [
                'title' => 'Soporte: Prioritario + formación'
            ],
            'comparator' => [
                'title' => 'Comparador tarifas: Completo'
            ]
        ],
        'notIncluded' => [
            'calls' => [
                'title' => 'Llamadas de verificación',
            ]
        ],
        'stripe' => [
            'id' => 'prod_USZNgAF7joHdos',
            'monthly' => 'price_1TTeF5QhTiksrklnSrEvA7hR',
            'annual' => 'price_1TTeF5QhTiksrklnlaGd7GGz',
            'free_monthly' => 'price_1TZW3AH2rNMfHZ9KeLTICh9m',
            'free_annual' => 'price_1TZsgwH2rNMfHZ9KvhaN2vBb'
        ]
    ],
    [
        'id' => 2,
        'name' => 'Enterprise',
        'price' => 997,
        'annualPrice' => 9000,
        'included'=> [
            'users' => [
                'amount' => null,
                'title' => 'Usuarios activos: Ilimitados',
                'unit' => 'once'
            ],
            'scans' => [
                'amount' => null,
                'title' => 'Estudios escaneo/mes: Ilimitados',
                'unit' => 'month'
            ],
            'monitoring' => [
                'amount' => null,
                'title' => 'Monitorizaciones: Ilimitadas',
                'unit' => 'once'
            ],
            'whatsapp' => [
                'amount' => null,
                'title' => 'WhatsApp crm: Ilimitado',
            ],
            'support' => [
                'title' => 'Soporte: Onboarding personalizado'
            ],
            'comparator' => [
                'title' => 'Comparador tarifas: Completo + API'
            ],
            'calls' => [
                'amount' => 2000,
                'title' => 'Llamadas de verificación: Incluidas hasta 2.000 min/mes',
            ]
        ],
        'stripe' => [
            'id' => 'prod_USZOgqtSgWwym3',
            'monthly' => 'price_1TTeFhQhTiksrklnZcd2B9y5',
            'annual' => 'price_1TTeFhQhTiksrklnqzOAqcmp',
            'free_monthly' => 'price_1TZW3PH2rNMfHZ9KvKfiO37m',
            'free_annual' => 'price_1TZshTH2rNMfHZ9KzF8gthHo',
        ]
    ],
];
