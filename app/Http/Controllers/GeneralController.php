<?php

namespace App\Http\Controllers;

use App\Http\Models\Account;
use App\Http\Models\Enterprise;
use App\Http\Models\Order;
use App\Http\Models\Contact;
use App\Http\Models\Opportunity;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class GeneralController extends Controller
{
    //función para sacar resultados de buscador global
    public static function search(Request $request){

        $text = $request['text'];
        $type = $request['type'];
        $userList = $request['userList'];
        //Saco ids de usuarios
        $usersIds = [...array_column($userList, '_id'),\Auth::user()->_id];


        //Normalizo el texto
        $searchTextNormalized = mb_strtolower($text, 'UTF-8'); // Convertir a minúsculas
        $searchTextNormalized = preg_replace('/\s+/', '', $searchTextNormalized); // Eliminar espacios
        $searchTextNormalized = preg_replace('/[áàäâãåÁÀÄÂÃ]/u', 'a', $searchTextNormalized);
        $searchTextNormalized = preg_replace('/[éèëêÉÈËÊ]/u', 'e', $searchTextNormalized);
        $searchTextNormalized = preg_replace('/[íìïîÍÌÏÎ]/u', 'i', $searchTextNormalized);
        $searchTextNormalized = preg_replace('/[óòöôõÓÒÖÔÕ]/u', 'o', $searchTextNormalized);
        $searchTextNormalized = preg_replace('/[úùüûÚÙÜÛ]/u', 'u', $searchTextNormalized);

        // Patrón de búsqueda para MongoDB
        $regexPattern = new \MongoDB\BSON\Regex(preg_quote($searchTextNormalized), 'i');

        //dd($searchTextNormalized, $regexPattern, $text, $type);

        //Tipos
        //  0 -> 'Todos'
        //  1 -> 'Contratos' --> Busca en título, nif/cif, cups, estado
        //  2 -> 'Cuentas' --> Busca en nombre, cif, telefono, email
        //  3 -> 'Contactos' --> Busca en nombre, apellidos, email, telefono
        //  4 -> 'Oportunidades' --> Busca en nombre, cif/nif, telefono, email


        switch ($type) {
            case 1 : //Contratos

                $ordersPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        //Filtro por estado
                        'latestStatus'=> [
                            '$reduce' => [
                                'input' => '$statuses',
                                'initialValue' => null,
                                'in' => [
                                    '$cond' => [
                                        'if' => [
                                            '$and' => [
                                                '$$this.date',
                                                ['$gt' => ['$$this.date', '$$value.date']]
                                            ]
                                        ],
                                        'then' => '$$this',
                                        'else' => '$$value'
                                    ]
                                ]
                            ]
                        ],
                        'nameNormalized' => [
                            '$toLower' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => ['$range' => [0, ['$strLenCP' => '$name']]],
                                            'as' => 'idx',
                                            'in' => [
                                                '$substrCP' => ['$name', '$$idx', 1]
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$concat' => [
                                            '$$value',
                                            [
                                                '$switch' => [
                                                    'branches' => [
                                                        ['case' => ['$eq' => ['$$this', ' ']], 'then' => ''],
                                                        ['case' => ['$in' => ['$$this', ['á', 'à', 'ä', 'â', 'ã', 'å', 'Á', 'À', 'Ä', 'Â', 'Ã', 'Å']]], 'then' => 'a'],
                                                        ['case' => ['$in' => ['$$this', ['é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê']]], 'then' => 'e'],
                                                        ['case' => ['$in' => ['$$this', ['í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î']]], 'then' => 'i'],
                                                        ['case' => ['$in' => ['$$this', ['ó', 'ò', 'ö', 'ô', 'õ', 'Ó', 'Ò', 'Ö', 'Ô', 'Õ']]], 'then' => 'o'],
                                                        ['case' => ['$in' => ['$$this', ['ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û']]], 'then' => 'u'],
                                                    ],
                                                    'default' => '$$this'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'CIFNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CIF',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'CUPSNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CUPS',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'agentFullName' => [
                            '$concat' => [
                                ['$arrayElemAt' => ['$userInfo.firstName', 0]],
                                ' ',
                                ['$arrayElemAt' => ['$userInfo.lastName', 0]]
                            ]
                        ],
                        'accountId' => '$_id',
                    ]],
                    ['$addFields' => [
                        'latestStatusTitle' => [
                            '$switch' => [
                                'branches' => [
                                    ['case' => ['$eq' => ['$latestStatus.code', 'r']], 'then' => 'Recibido'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'p']], 'then' => 'Pendiente'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 't']], 'then' => 'Tramitado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'f']], 'then' => 'Firmado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'fc']], 'then' => 'Firmado - Llamada verificada'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'ac']], 'then' => 'Aceptado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'ap']], 'then' => 'Pendiente de activacion'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'a']], 'then' => 'Activado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'c']], 'then' => 'Comisionado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'i']], 'then' => 'Incidencia'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'an']], 'then' => 'Anulado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 's']], 'then' => 'Scoring'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'b']], 'then' => 'Baja'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'bo']], 'then' => 'Borrador']
                                ],
                                'default' => null
                            ]
                        ]
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['CIFNormalized' => ['$regex' => $regexPattern]], //CIF/NIF
                            ['CUPSNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['agentFullName' => ['$regex' => $regexPattern]], //email
                            ['latestStatusTitle' => ['$regex' => $regexPattern]], //estado
                        ]
                    ]]
                ];

                $results = Order::raw(function ($collection) use ($ordersPipeline) {
                    return $collection->aggregate($ordersPipeline);
                });

                break;
            case 2 : //Cuentas

                $accountsPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        'nameNormalized' => [
                            '$toLower' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => ['$range' => [0, ['$strLenCP' => '$name']]],
                                            'as' => 'idx',
                                            'in' => [
                                                '$substrCP' => ['$name', '$$idx', 1]
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$concat' => [
                                            '$$value',
                                            [
                                                '$switch' => [
                                                    'branches' => [
                                                        ['case' => ['$eq' => ['$$this', ' ']], 'then' => ''],
                                                        ['case' => ['$in' => ['$$this', ['á', 'à', 'ä', 'â', 'ã', 'å', 'Á', 'À', 'Ä', 'Â', 'Ã', 'Å']]], 'then' => 'a'],
                                                        ['case' => ['$in' => ['$$this', ['é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê']]], 'then' => 'e'],
                                                        ['case' => ['$in' => ['$$this', ['í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î']]], 'then' => 'i'],
                                                        ['case' => ['$in' => ['$$this', ['ó', 'ò', 'ö', 'ô', 'õ', 'Ó', 'Ò', 'Ö', 'Ô', 'Õ']]], 'then' => 'o'],
                                                        ['case' => ['$in' => ['$$this', ['ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û']]], 'then' => 'u'],
                                                    ],
                                                    'default' => '$$this'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'CIFNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CIF',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'phoneNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$phone',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'emailNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$email',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['CIFNormalized' => ['$regex' => $regexPattern]], //CIF/NIF
                            ['phoneNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['emailNormalized' => ['$regex' => $regexPattern]], //email
                        ]
                    ]]
                ];

                $results = Account::raw(function ($collection) use ($accountsPipeline) {
                    return $collection->aggregate($accountsPipeline);
                });

                break;
            case 3 : //Contactos

                $contactsPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        'nameNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => [
                                        '$concat' => [
                                            '$name.first',
                                            '$name.second'
                                        ]
                                    ],
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'surnameNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => [
                                        '$concat' => [
                                            '$surname.first',
                                            '$surname.second'
                                        ]
                                    ],
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'phoneNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$phone',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'emailNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$email',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['surnameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['phoneNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['emailNormalized' => ['$regex' => $regexPattern]], //email
                        ]
                    ]]
                ];

                $results = Contact::raw(function ($collection) use ($contactsPipeline) {
                    return $collection->aggregate($contactsPipeline);
                });

                break;
            case 4 : //Oportunidades

                $opportunityPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        'nameNormalized' => [
                            '$toLower' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => ['$range' => [0, ['$strLenCP' => '$name']]],
                                            'as' => 'idx',
                                            'in' => [
                                                '$substrCP' => ['$name', '$$idx', 1]
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$concat' => [
                                            '$$value',
                                            [
                                                '$switch' => [
                                                    'branches' => [
                                                        ['case' => ['$eq' => ['$$this', ' ']], 'then' => ''],
                                                        ['case' => ['$in' => ['$$this', ['á', 'à', 'ä', 'â', 'ã', 'å', 'Á', 'À', 'Ä', 'Â', 'Ã', 'Å']]], 'then' => 'a'],
                                                        ['case' => ['$in' => ['$$this', ['é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê']]], 'then' => 'e'],
                                                        ['case' => ['$in' => ['$$this', ['í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î']]], 'then' => 'i'],
                                                        ['case' => ['$in' => ['$$this', ['ó', 'ò', 'ö', 'ô', 'õ', 'Ó', 'Ò', 'Ö', 'Ô', 'Õ']]], 'then' => 'o'],
                                                        ['case' => ['$in' => ['$$this', ['ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û']]], 'then' => 'u'],
                                                    ],
                                                    'default' => '$$this'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'CIFNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CIF',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'phoneNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$phone',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'emailNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$email',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['CIFNormalized' => ['$regex' => $regexPattern]], //CIF/NIF
                            ['phoneNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['emailNormalized' => ['$regex' => $regexPattern]], //email
                        ]
                    ]]
                ];

                $results = Opportunity::raw(function ($collection) use ($opportunityPipeline) {
                    return $collection->aggregate($opportunityPipeline);
                });

                break;
            default: //Todos

                $results = [];


                //Contratos
                $ordersPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        //Filtro por estado
                        'latestStatus'=> [
                            '$reduce' => [
                                'input' => '$statuses',
                                'initialValue' => null,
                                'in' => [
                                    '$cond' => [
                                        'if' => [
                                            '$and' => [
                                                '$$this.date',
                                                ['$gt' => ['$$this.date', '$$value.date']]
                                            ]
                                        ],
                                        'then' => '$$this',
                                        'else' => '$$value'
                                    ]
                                ]
                            ]
                        ],
                        'nameNormalized' => [
                            '$toLower' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => ['$range' => [0, ['$strLenCP' => '$name']]],
                                            'as' => 'idx',
                                            'in' => [
                                                '$substrCP' => ['$name', '$$idx', 1]
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$concat' => [
                                            '$$value',
                                            [
                                                '$switch' => [
                                                    'branches' => [
                                                        ['case' => ['$eq' => ['$$this', ' ']], 'then' => ''],
                                                        ['case' => ['$in' => ['$$this', ['á', 'à', 'ä', 'â', 'ã', 'å', 'Á', 'À', 'Ä', 'Â', 'Ã', 'Å']]], 'then' => 'a'],
                                                        ['case' => ['$in' => ['$$this', ['é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê']]], 'then' => 'e'],
                                                        ['case' => ['$in' => ['$$this', ['í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î']]], 'then' => 'i'],
                                                        ['case' => ['$in' => ['$$this', ['ó', 'ò', 'ö', 'ô', 'õ', 'Ó', 'Ò', 'Ö', 'Ô', 'Õ']]], 'then' => 'o'],
                                                        ['case' => ['$in' => ['$$this', ['ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û']]], 'then' => 'u'],
                                                    ],
                                                    'default' => '$$this'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'CIFNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CIF',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'CUPSNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CUPS',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'agentFullName' => [
                            '$concat' => [
                                ['$arrayElemAt' => ['$userInfo.firstName', 0]],
                                ' ',
                                ['$arrayElemAt' => ['$userInfo.lastName', 0]]
                            ]
                        ],
                        'accountId' => '$_id'
                    ]],
                    ['$addFields' => [
                        'latestStatusTitle' => [
                            '$switch' => [
                                'branches' => [
                                    ['case' => ['$eq' => ['$latestStatus.code', 'r']], 'then' => 'Recibido'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'p']], 'then' => 'Pendiente'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 't']], 'then' => 'Tramitado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'f']], 'then' => 'Firmado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'fc']], 'then' => 'Firmado - Llamada verificada'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'ac']], 'then' => 'Aceptado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'ap']], 'then' => 'Pendiente de activacion'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'a']], 'then' => 'Activado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'c']], 'then' => 'Comisionado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'i']], 'then' => 'Incidencia'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'an']], 'then' => 'Anulado'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 's']], 'then' => 'Scoring'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'b']], 'then' => 'Baja'],
                                    ['case' => ['$eq' => ['$latestStatus.code', 'bo']], 'then' => 'Borrador']
                                ],
                                'default' => null
                            ]
                        ]
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['CIFNormalized' => ['$regex' => $regexPattern]], //CIF/NIF
                            ['CUPSNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['agentFullName' => ['$regex' => $regexPattern]], //email
                            ['latestStatusTitle' => ['$regex' => $regexPattern]], //estado
                        ]
                    ]]
                ];

                $results[0] = Order::raw(function ($collection) use ($ordersPipeline) {
                    return $collection->aggregate($ordersPipeline);
                });


                //Cuentas
                $accountsPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        'nameNormalized' => [
                            '$toLower' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => ['$range' => [0, ['$strLenCP' => '$name']]],
                                            'as' => 'idx',
                                            'in' => [
                                                '$substrCP' => ['$name', '$$idx', 1]
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$concat' => [
                                            '$$value',
                                            [
                                                '$switch' => [
                                                    'branches' => [
                                                        ['case' => ['$eq' => ['$$this', ' ']], 'then' => ''],
                                                        ['case' => ['$in' => ['$$this', ['á', 'à', 'ä', 'â', 'ã', 'å', 'Á', 'À', 'Ä', 'Â', 'Ã', 'Å']]], 'then' => 'a'],
                                                        ['case' => ['$in' => ['$$this', ['é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê']]], 'then' => 'e'],
                                                        ['case' => ['$in' => ['$$this', ['í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î']]], 'then' => 'i'],
                                                        ['case' => ['$in' => ['$$this', ['ó', 'ò', 'ö', 'ô', 'õ', 'Ó', 'Ò', 'Ö', 'Ô', 'Õ']]], 'then' => 'o'],
                                                        ['case' => ['$in' => ['$$this', ['ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û']]], 'then' => 'u'],
                                                    ],
                                                    'default' => '$$this'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'CIFNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CIF',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'phoneNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$phone',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'emailNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$email',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['CIFNormalized' => ['$regex' => $regexPattern]], //CIF/NIF
                            ['phoneNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['emailNormalized' => ['$regex' => $regexPattern]], //email
                        ]
                    ]]
                ];

                $results[1] = Account::raw(function ($collection) use ($accountsPipeline) {
                    return $collection->aggregate($accountsPipeline);
                });

                //Contactos
                $contactsPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        'nameNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => [
                                        '$concat' => [
                                            '$name.first',
                                            '$name.second'
                                        ]
                                    ],
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'surnameNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => [
                                        '$concat' => [
                                            '$surname.first',
                                            '$surname.second'
                                        ]
                                    ],
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'phoneNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$phone',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'emailNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$email',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['surnameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['phoneNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['emailNormalized' => ['$regex' => $regexPattern]], //email
                        ]
                    ]]
                ];

                $results[2] = Contact::raw(function ($collection) use ($contactsPipeline) {
                    return $collection->aggregate($contactsPipeline);
                });


                //Oportunidades
                $opportunityPipeline = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    [
                        '$lookup' => [
                            'from' => 'users',
                            'let' => ['userId' => '$createdBy'],
                            'pipeline' => [
                                ['$addFields' => ['_idString' => ['$toString' => '$_id']]],
                                ['$match' => ['$expr' => ['$eq' => ['$_idString', '$$userId']]]]
                            ],
                            'as' => 'userInfo'
                        ]
                    ],
                    ['$addFields' => [
                        'nameNormalized' => [
                            '$toLower' => [
                                '$reduce' => [
                                    'input' => [
                                        '$map' => [
                                            'input' => ['$range' => [0, ['$strLenCP' => '$name']]],
                                            'as' => 'idx',
                                            'in' => [
                                                '$substrCP' => ['$name', '$$idx', 1]
                                            ]
                                        ]
                                    ],
                                    'initialValue' => '',
                                    'in' => [
                                        '$concat' => [
                                            '$$value',
                                            [
                                                '$switch' => [
                                                    'branches' => [
                                                        ['case' => ['$eq' => ['$$this', ' ']], 'then' => ''],
                                                        ['case' => ['$in' => ['$$this', ['á', 'à', 'ä', 'â', 'ã', 'å', 'Á', 'À', 'Ä', 'Â', 'Ã', 'Å']]], 'then' => 'a'],
                                                        ['case' => ['$in' => ['$$this', ['é', 'è', 'ë', 'ê', 'É', 'È', 'Ë', 'Ê']]], 'then' => 'e'],
                                                        ['case' => ['$in' => ['$$this', ['í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î']]], 'then' => 'i'],
                                                        ['case' => ['$in' => ['$$this', ['ó', 'ò', 'ö', 'ô', 'õ', 'Ó', 'Ò', 'Ö', 'Ô', 'Õ']]], 'then' => 'o'],
                                                        ['case' => ['$in' => ['$$this', ['ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Ü', 'Û']]], 'then' => 'u'],
                                                    ],
                                                    'default' => '$$this'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'CIFNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$CIF',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'phoneNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$phone',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                        'emailNormalized' => [
                            '$toLower' => [
                                '$replaceAll' => [
                                    'input' => '$email',
                                    'find' => ' ',
                                    'replacement' => ''
                                ]
                            ]
                        ],
                    ]],
                    ['$match' => [
                        '$or' => [
                            ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                            ['CIFNormalized' => ['$regex' => $regexPattern]], //CIF/NIF
                            ['phoneNormalized' => ['$regex' => $regexPattern]], //Teléfono
                            ['emailNormalized' => ['$regex' => $regexPattern]], //email
                        ]
                    ]]
                ];

                $results[3] = Opportunity::raw(function ($collection) use ($opportunityPipeline) {
                    return $collection->aggregate($opportunityPipeline);
                });
        }


        return response()->json(['results' => $results], 200);
    }

    public function updateCommissionRanges(Request $request){
        //Con el id de la empresa, obtener el documento y actualizar el campo commissionRanges
        $enterprise = Enterprise::find($request->id);
        //Antes de actualizar commissionRanges, aseguro que los valores de los campos tienen el tipo adecuado
        //Que cada commissionRange tiene name como un string no vacío, y que percentage es un numero.
        $sanitizedCommissions = array_map(function ($range) {
            $name = isset($range['name']) ? (string) $range['name'] : '';

            // Normalizar el porcentaje
            $percentage = isset($range['percentage'])
                ? str_replace(',', '.', $range['percentage'])
                : 0;

            // Si es numérico, lo convierte a float
            $percentage = is_numeric($percentage)
                ? (float) $percentage
                : 0;

            $id = !empty($range['_id'])
                ? (string) $range['id']
                : (string) new ObjectId();

            return [
                'name' => $name,
                'percentage' => $percentage,
                '_id' => $id,
            ];
        }, $request->commissionRanges);

        $enterprise->update(['commissionRanges' => $sanitizedCommissions]);

        return response()->json(['message' => 'Rangos de comisión actualizados'], 200);
    }

}
