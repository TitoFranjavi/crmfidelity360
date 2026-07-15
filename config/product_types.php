<?php

return [
    [
        'code'                   => 'cl',
        'title'                  => 'Contrato de luz',
        'inDatabase'             => 'electricity',
        'verificationsAvailable' => ['nw', 'pc', 'tc', 'vb', 'mc', 're'],
    ],
    [
        'code'                   => 'cg',
        'title'                  => 'Contrato de gas',
        'inDatabase'             => 'gas',
        'verificationsAvailable' => ['nw', 'pc', 'tc', 'vb', 'mc', 're'],
    ],
    [
        'code'                   => 'cd',
        'title'                  => 'Contrato dual',
        'inDatabase'             => 'dual',
        'verificationsAvailable' => ['nw', 'pc', 'tc', 'vb', 'mc', 're'],
    ],
    [
        'code'                   => 'ct',
        'title'                  => 'Contrato de telefonía',
        'inDatabase'             => 'telephony',
        'verificationsAvailable' => ['nw', 'tc'],
    ],
    [
        'code'                   => 'sa',
        'title'                  => 'Servicio de alarmas',
        'inDatabase'             => 'alarm',
        'verificationsAvailable' => [],
    ],
    [
        'code'                   => 'a',
        'title'                  => 'Autoconsumo',
        'inDatabase'             => 'selfSupply',
        'verificationsAvailable' => [],
    ],
    [
        'code'                   => 'bc',
        'title'                  => 'Bateria de condensadores',
        'productToSee'           => 'n',
        'verificationsAvailable' => [],
    ],
    [
        'code'                   => 'ce',
        'title'                  => 'Coche eléctrico',
        'productToSee'           => 'electricCar',
        'verificationsAvailable' => [],
    ],
    [
        'code'                   => 'c',
        'title'                  => 'Contador',
        'productToSee'           => 'electricityMeter',
        'verificationsAvailable' => [],
    ],
    [
        'code'                   => 'i',
        'title'                  => 'Iluminación',
        'productToSee'           => 'n',
        'verificationsAvailable' => [],
    ],
    [
        'code'                   => 'crm',
        'title'                  => 'Servicios CRM',
        'productToSee'           => 'crm',
        'verificationsAvailable' => [],
    ],
    [
        'code'                   => '',
        'title'                  => 'Sin tipo de producto',
        'verificationsAvailable' => [],
    ],
];
