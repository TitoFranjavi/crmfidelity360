<?php

namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use App\Http\Models\Account;
use App\Http\Models\Order;
use App\Http\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\UTCDateTime;


class DashboardController extends Controller
{

    //funcion para obtener los datos generales para las cards
    public function getGeneralData(Request $request){

        $userLogged = Auth::user();
        $usersIds = $request->input('usersIds');
        $userSubdomain = $request->input('userSubdomain');
        $dates = $request->input('dates');

        $userSelected = $request->input('userSelected');
        $usersIds[] = $userSelected['_id'];

        $canSeeSubdomainCommission = in_array('manageCommissions', $userSubdomain['labels_permissions'][$userLogged['label']]['contracts']);

        //Convierto las fechas a timestamp para comparar
        $startDate = isset($dates['start']) ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['start'])->startOfDay()->timestamp * 1000)  : null;
        $endDate = isset($dates['end'])  ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['end'])->endOfDay()->timestamp * 1000)  : null;

        //dd($usersIds, $userSelected, $startDate, $endDate);

        $pipeline = [
            ['$match' => [
                'usersIds' => ['$in' => $usersIds]
            ]],
            ['$addFields' => [
                'createdAtTemporal' => [
                    '$dateFromString' => [
                        'dateString' => [
                            '$concat' => [
                                '20',
                                ['$substr' => ['$transferDate', 6, 2]],
                                '-',
                                ['$substr' => ['$transferDate', 3, 2]],
                                '-',
                                ['$substr' => ['$transferDate', 0, 2]]
                            ]
                        ],
                        'format' => '%Y-%m-%d'
                    ]
                ],
                'latestStatus' => [
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
                'isBaja' => ['$eq' => ['$lastStatus.code', 'b']],
                'blockByZoco' => [
                    '$and' => [
                        ['$eq' => ['$assignedTo', '65cb57489c2c285441086a43']],
                        [
                            '$not' => [[
                                '$in' => [
                                    $userSelected['_id'],
                                    ['65cb57489c2c285441086a43', '65d704c63d2a9cbfd79e549a']
                                ]
                            ]]
                        ]
                    ]
                ],
            ]],
        ];

        if ($canSeeSubdomainCommission) {
            $pipeline[] = ['$addFields' => [
                'subIdx' => [
                    '$indexOfArray' => ['$commissions.breakdown.userId', $userSubdomain['_id']]
                ],
                'hasAssignedTo' => [
                    '$gt' => [['$ifNull' => ['$assignedTo', null]], null]
                ],
                'isAssignedToUser' => [
                    '$eq' => ['$assignedTo', $userSelected['_id']]
                ],
            ]];
            $pipeline[] = ['$addFields' => [
                'subCom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                '$commissions.subdomain',
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => ['$commissions.breakdown.commission', '$subIdx']],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
                'subDecom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                ['$ifNull' => ['$decommissions.subdomain', 0]],
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], '$subIdx']],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
                'agentCom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                ['$arrayElemAt' => ['$commissions.breakdown.commission', 0]],
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => ['$commissions.breakdown.commission', ['$add' => ['$subIdx', 1]]]],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
                'agentDecom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], 0]],
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], ['$add' => ['$subIdx', 1]]]],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
            ]];
        } else {
            $pipeline[] = ['$addFields' => [
                'idx'        => ['$indexOfArray' => ['$commissions.breakdown.userId', $userLogged['_id']]],
                'idxDecom'   => ['$indexOfArray' => [['$ifNull' => ['$decommissions.breakdown.userId', []]], $userLogged['_id']]],
            ]];

            $pipeline[] = ['$addFields' => [
                'subCom'     => ['$convert' => ['input' => '$commissions.subdomain', 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
                'subDecom'   => ['$convert' => ['input' => ['$ifNull' => ['$decommissions.subdomain', 0]], 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
                'agentCom'   => ['$convert' => [
                    'input' => ['$cond' => [
                        ['$gte' => ['$idx', 0]],
                        ['$arrayElemAt' => ['$commissions.breakdown.commission', '$idx']],
                        0
                    ]],
                    'to' => 'double', 'onError' => 0, 'onNull' => 0
                ]],
                'agentDecom' => ['$convert' => [
                    'input' => ['$cond' => [
                        ['$and' => [
                            ['$gte' => ['$idxDecom', 0]],
                            ['$gt' => [['$size' => ['$ifNull' => ['$decommissions.breakdown', []]]], 0]]
                        ]],
                        ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], '$idxDecom']],
                        0
                    ]],
                    'to' => 'double', 'onError' => 0, 'onNull' => 0
                ]],
            ]];
        }

        $pipeline[] = ['$match' => [
            'createdAtTemporal' => [
                '$gte' => $startDate,
                '$lte' => $endDate
            ]
        ]];

        $pipeline[] = ['$group' => [
            '_id' => 0,
            'count' => ['$sum' => 1],
            'consumption' => [
                '$sum' => [
                    '$convert' => ['input' => '$consumption', 'to' => 'double', 'onError' => 0, 'onNull' => 0]
                ]
            ],
            'subdomainCommission' => [
                '$sum' => [
                    '$cond' => [
                        '$blockByZoco',
                        0,
                        [
                            '$cond' => [
                                '$isBaja',
                                ['$subtract' => ['$subCom', '$subDecom']],
                                '$subCom'
                            ]
                        ]
                    ]
                ]
            ],
            'agentCommission' => [
                '$sum' => [
                    '$cond' => [
                        '$isBaja',
                        ['$subtract' => ['$agentCom', '$agentDecom']],
                        '$agentCom'
                    ]
                ]
            ],
        ]];

        // Ejecutar la consulta
        $rawResults = Order::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });


        $rawResults = $rawResults->toArray();

        $rawResults = reset($rawResults);

        if (!$rawResults)
            $rawResults = [
                "_id" => 0,
                "count" => 0,
                "consumption" => 0,
                "agentCommission" => 0,
                "subdomainCommission" => 0
            ];


        //Saco el total de agentes
        $agentsCount = User::whereIn('_id', $usersIds)->count();

        return response()->json(['totalContracts' => $rawResults['count'], 'totalConsumption' => $rawResults['consumption'], 'agentCommission' => $rawResults['agentCommission'], 'subdomainCommission' => $rawResults['subdomainCommission'], 'totalAgents' => $agentsCount], 200);
    }


    //función para obtener los contratos que hay entre las fechas
    public function contractsAndConsumePerDate(Request $request)
    {
        $usersIds = $request->input('usersIds');
        $dates = $request->input('dates'); // Fechas pasadas desde el cliente

        $userSelected = $request->input('userSelected');
        $usersIds[] = $userSelected['_id'];

        //Conviero las fechas a timestamp para comparar
        $startDate = isset($dates['start']) ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['start'])->startOfDay()->timestamp * 1000)  : null;
        $endDate = isset($dates['end'])  ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['end'])->endOfDay()->timestamp * 1000)  : null;

        $pipeline = [
            // Filtrar por usuario
            ['$match' => [
                'usersIds' => ['$in' => $usersIds]
            ]],
            // Convertir las fechas de transferencia
            ['$addFields' => [
                'createdAtTemporal' => [
                    '$dateFromString' => [
                        'dateString' => [
                            '$concat' => [
                                '20',
                                ['$substr' => ['$transferDate', 6, 2]], // Año
                                '-',
                                ['$substr' => ['$transferDate', 3, 2]], // Mes
                                '-',
                                ['$substr' => ['$transferDate', 0, 2]]  // Día
                            ]
                        ],
                        'format' => '%Y-%m-%d'
                    ]
                ]

            ]],
            // Filtrar documentos dentro del rango especificado
            ['$match' => [
                'createdAtTemporal' => [
                    '$gte' => $startDate,
                    '$lte' => $endDate
                ]
            ]],
            // Calcular el rango de fechas en días
            ['$addFields' => [
                'dateRangeInDays' => [
                    '$divide' => [
                        ['$subtract' => [$endDate, $startDate]],
                        1000 * 60 * 60 * 24 // Milisegundos a días
                    ]
                ]
            ]],
            // Determinar granularidad y guardarla como variable
            ['$addFields' => [
                'granularity' => [
                    '$switch' => [
                        'branches' => [
                            ['case' => ['$lte' => ['$dateRangeInDays', 2]], 'then' => 'hour'],
                            ['case' => ['$lte' => ['$dateRangeInDays', 49]], 'then' => 'day'],
                            ['case' => ['$lte' => ['$dateRangeInDays', 365]], 'then' => 'week'],
                            ['case' => ['$lte' => ['$dateRangeInDays', 1460]], 'then' => 'month'],
                        ],
                        'default' => 'year'
                    ]
                ]
            ]],
            // Agrupar los datos según la granularidad calculada
            ['$group' => [
                '_id' => [
                    '$switch' => [
                        'branches' => [
                            [
                                'case' => ['$eq' => ['$granularity', 'hour']],
                                'then' => [
                                    '$dateToString' => ['format' => '%H:00:00', 'date' => '$createdAtTemporal']
                                ]
                            ],
                            [
                                'case' => ['$eq' => ['$granularity', 'day']],
                                'then' => ['$dateToString' => ['format' => '%d-%m-%Y', 'date' => '$createdAtTemporal']]
                            ],
                            [
                                'case' => ['$eq' => ['$granularity', 'week']],
                                'then' => ['$dateToString' => ['format' => '%G-%V', 'date' => '$createdAtTemporal']]
                            ],
                            [
                                'case' => ['$eq' => ['$granularity', 'month']],
                                'then' => ['$dateToString' => ['format' => '%m-%Y', 'date' => '$createdAtTemporal']]
                            ],
                            [
                                'case' => ['$eq' => ['$granularity', 'year']],
                                'then' => ['$dateToString' => ['format' => '%Y', 'date' => '$createdAtTemporal']]
                            ]
                        ],
                        'default' => null
                    ]
                ],
                'granularity' => ['$first' => '$granularity'],  // Guardar la granularidad en el grupo
                'count' => ['$sum' => 1],
                'consumption' => [
                    '$sum' => [
                        '$toDouble' => [
                            '$cond' => [
                                'if' => ['$eq' => ['$consumption', '']], // Si 'consume' es vacío
                                'then' => 0,  // Reemplazar por 0 si está vacío
                                'else' => ['$toDouble' => '$consumption']  // Convertir 'consume' a número
                            ]
                        ]
                    ]
                ]
            ]],
        ];

        // Ejecutar la consulta
        $rawResults = Order::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });


        // Calcular la diferencia en días
        $dateRangeInDays = (int) (($endDate->toDateTime()->getTimestamp() - $startDate->toDateTime()->getTimestamp()) / 86400); // Convertir a días

        // Determinar la granularidad
        if ($dateRangeInDays <= 2) {
            $granularity = 'hour';
        } elseif ($dateRangeInDays <= 49) {
            $granularity = 'day';
        } elseif ($dateRangeInDays <= 365) {
            $granularity = 'week';
        } elseif ($dateRangeInDays <= 1460) {
            $granularity = 'month';
        } else {
            $granularity = 'year';
        }

        // Generar todos los períodos según la granularidad
        $allPeriods = [];
        $currentDate = Carbon::parse($startDate->toDateTime());
        while ($currentDate->lte(Carbon::parse($endDate->toDateTime()))) {
            switch ($granularity) {
                case 'hour':
                    $allPeriods[] = $currentDate->format('H:00:00');
                    $currentDate->addHour();
                    break;
                case 'day':
                    $allPeriods[] = $currentDate->format('d-m-Y');
                    $currentDate->addDay();
                    break;
                case 'week':
                    $allPeriods[] = $currentDate->format('Y-W');  // Año-Semana
                    $currentDate->addWeek();
                    break;
                case 'month':
                    $allPeriods[] = $currentDate->format('m-Y');
                    $currentDate->addMonth();
                    break;
                case 'year':
                    $allPeriods[] = $currentDate->format('Y');
                    $currentDate->addYear();
                    break;
            }
        }


        // Organizar los resultados en base a los períodos generados
        $mergedResults = [];
        foreach ($allPeriods as $period) {
            $found = false;
            foreach ($rawResults->toArray() as $result) {

                if ($result['_id'] == $period) {
                    $mergedResults[] = [
                        'period' => $period,
                        'count' => $result['count'],
                        'consumption' => $result['consumption']
                    ];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $mergedResults[] = [
                    'period' => $period,
                    'count' => 0,
                    'consumption' => 0
                ];
            }
        }


        //aqui llega ya mal
        //dd($mergedResults);


        //Los modifico si es semana y le pongo del día de inicio al día final
        if ($granularity === 'week')
            foreach ($mergedResults as &$result) {

                //Meses para cambiar
                $customShortMonths = [
                    'ene.' => 'Ene',
                    'feb.' => 'Feb',
                    'mar.' => 'Mar',
                    'abr.' => 'Abr',
                    'may.' => 'May',
                    'jun.' => 'Jun',
                    'jul.' => 'Jul',
                    'ago.' => 'Ago',
                    'sep.' => 'Sep',
                    'oct.' => 'Oct',
                    'nov.' => 'Nov',
                    'dic.' => 'Dic',
                ];

                // Divide el formato Año-Semana (ejemplo: 2024-26)
                [$year, $week] = explode('-', $result['period']);

                // Obtén el inicio de la semana (lunes) y el final de la semana (domingo)
                $startOfWeek = Carbon::now()->setISODate((int)$year, (int)$week)->startOfWeek();
                $endOfWeek = $startOfWeek->copy()->endOfWeek();

                // Formatea las fechas traducidas con Carbon
                $startFormatted = $startOfWeek->translatedFormat('d');
                $endFormatted = $endOfWeek->translatedFormat('d M Y');

                // Reemplaza los nombres de los meses con tus valores personalizados
                foreach ($customShortMonths as $original => $custom) {
                    $startFormatted = str_replace($original, $custom, $startFormatted);
                    $endFormatted = str_replace($original, $custom, $endFormatted);
                }

                // Devuelve el rango formateado
                $result['period'] = "{$startFormatted} - {$endFormatted}";
            }
        elseif ($granularity === 'month')
            foreach ($mergedResults as &$result) {

                $customShortMonths = [
                    '01' => 'Ene',
                    '02' => 'Feb',
                    '03' => 'Mar',
                    '04' => 'Abr',
                    '05' => 'May',
                    '06' => 'Jun',
                    '07' => 'Jul',
                    '08' => 'Ago',
                    '09' => 'Sep',
                    '10' => 'Oct',
                    '11' => 'Nov',
                    '12' => 'Dic',
                ];

                // Suponiendo que result['period'] es "04-2023"
                list($month, $year) = explode('-', $result['period']);

                // Reemplaza el mes por su versión corta
                if (isset($customShortMonths[$month])) {
                    $monthShort = $customShortMonths[$month];

                    // Convertir el año a formato corto (dos últimos dígitos)
                    $yearShort = substr($year, -2);  // Obtiene los últimos dos dígitos del año

                    // Actualiza el valor de period
                    $result['period'] = $monthShort . ' ' . $yearShort;
                }
            }



        // Ahora $allPeriods tendrá todos los días, semanas, etc., incluso los vacíos
        return response()->json(['computedData' => $mergedResults, 'granularity' => $granularity]);
    }


    //funcion para sacar los contratos agrupados por comercializadora
    public function getContractPerMarketerData(Request $request){

        $usersIds = $request->input('usersIds');
        $dates = $request->input('dates');

        $userSelected = $request->input('userSelected');
        $usersIds[] = $userSelected['_id'];

        //Conviero las fechas a timestamp para comparar
        $startDate = isset($dates['start']) ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['start'])->startOfDay()->timestamp * 1000)  : null;
        $endDate = isset($dates['end'])  ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['end'])->endOfDay()->timestamp * 1000)  : null;


        $pipeline = [
            // Filtrar por usuario
            ['$match' => [
                'usersIds' => ['$in' => $usersIds]
            ]],
            // Convertir las fechas de transferencia
            ['$addFields' => [
                'createdAtTemporal' => [
                    '$dateFromString' => [
                        'dateString' => [
                            '$concat' => [
                                '20',
                                ['$substr' => ['$transferDate', 6, 2]], // Año
                                '-',
                                ['$substr' => ['$transferDate', 3, 2]], // Mes
                                '-',
                                ['$substr' => ['$transferDate', 0, 2]]  // Día
                            ]
                        ],
                        'format' => '%Y-%m-%d'
                    ]
                ]

            ]],
            // Filtrar documentos dentro del rango especificado
            ['$match' => [
                'createdAtTemporal' => [
                    '$gte' => $startDate,
                    '$lte' => $endDate
                ]
            ]],
            ['$group' => [
                '_id' => [
                    'marketer' => [
                        '$cond' => [
                            'if' => ['$eq' => ['$marketer', '']],
                            'then' => 'Sin comerc.', // Asignar 'Sin comercializadora'
                            'else' => '$marketer'
                        ]
                    ]
                ],
                'totalContracts' => ['$sum' => 1] // Contar contratos
            ]],
            // Formatear la salida (opcional)
            ['$project' => [
                '_id' => 0, // Eliminar el campo `_id`
                'marketer' => '$_id.marketer',
                'totalContracts' => 1
            ]]
        ];

        // Ejecutar la consulta
        $contractPerMarketerData = Order::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        return response()->json(['contractPerMarketerData' => $contractPerMarketerData], 200);
    }




    public function getContractsPerStatus(Request $request)
{
    $userLogged = Auth::user();
    $dates = $request->input('dates');
    $userSelected = $request->input('userSelected');
    $userSubdomain = $request->input('userSubdomain');

    // Permiso para ver comisión del subdominio / empresa
    $permissions = $userSubdomain['labels_permissions'][$userLogged['label']]['contracts'] ?? [];
    $canSeeSubdomainCommission = in_array('manageCommissions', $permissions);

    // 🔹 Obtener jerarquía completa del usuario
    $userHierarchy = UserHelper::hierarchy($userSelected['_id']);
    $userHierarchyIds = collect($userHierarchy)->pluck('_id')->toArray();
    $userHierarchyIds[] = $userSelected['_id']; // incluir al propio usuario
    $userHierarchyIds = array_values(array_unique(array_filter($userHierarchyIds)));

    // 📅 Rango de fechas
    $startDate = Carbon::parse($dates['start'])->startOfDay();
    $endDate = Carbon::parse($dates['end'])->endOfDay();
    $startDateUtc = new UTCDateTime($startDate->timestamp * 1000);
    $endDateUtc = new UTCDateTime($endDate->timestamp * 1000);

    $pipelineFilters = [
        [
            '$match' => [
                'usersIds' => ['$in' => $userHierarchyIds]
            ]
        ],
        [
            '$addFields' => [
                'name'     => ['$ifNull' => ['$name', '']],
                'direc'    => ['$ifNull' => ['$direc', '']],
                'town'     => ['$ifNull' => ['$town', '']],
                'province' => ['$ifNull' => ['$province', '']],
                'zip'      => ['$ifNull' => ['$zip', '']],
                'IBAN'     => ['$ifNull' => ['$IBAN', '']],
            ]
        ],
        [
            '$addFields' => [
                // 🔹 Calcular el último estado correctamente
                'latestStatus' => [
                    '$reduce' => [
                        'input' => ['$ifNull' => ['$statuses', []]],
                        'initialValue' => null,
                        'in' => [
                            '$cond' => [
                                'if' => [
                                    '$and' => [
                                        ['$ne' => ['$$this.date', null]],
                                        [
                                            '$or' => [
                                                ['$eq' => ['$$value', null]],
                                                [
                                                    '$gt' => [
                                                        [
                                                            '$dateFromString' => [
                                                                'dateString' => '$$this.date',
                                                                'format'     => '%Y-%m-%d %H:%M:%S',
                                                                'onError'    => '1970-01-01 00:00:00',
                                                                'onNull'     => '1970-01-01 00:00:00'
                                                            ]
                                                        ],
                                                        [
                                                            '$dateFromString' => [
                                                                'dateString' => '$$value.date',
                                                                'format'     => '%Y-%m-%d %H:%M:%S',
                                                                'onError'    => '1970-01-01 00:00:00',
                                                                'onNull'     => '1970-01-01 00:00:00'
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                                'then' => '$$this',
                                'else' => '$$value'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        [
            '$unwind' => [
                'path' => '$usersIds',
                'preserveNullAndEmptyArrays' => false
            ]
        ],
        [
            '$group' => [
                '_id' => null,
                'agents'       => ['$addToSet' => '$usersIds'],
                'statuses'     => ['$addToSet' => '$latestStatus.code'],
                'marketers'    => ['$addToSet' => '$marketer'],
                'products'     => ['$addToSet' => ['$toString' => '$product']],
                'productTypes' => ['$addToSet' => '$productType'],
                'fees'         => ['$addToSet' => '$fee'],
            ]
        ]
    ];

    $resultsFilters = Order::raw(function ($collection) use ($pipelineFilters) {
        return $collection->aggregate($pipelineFilters)->toArray();
    });

    // 🔹 Bloques de cálculo de comisiones reutilizados para estados y renovaciones.
    // Devuelve:
    // - agentCom / agentDecom: comisión del usuario/agente visible
    // - subCom / subDecom: comisión empresa/subdominio solo si tiene permiso manageCommissions
    $commissionStages = function () use ($canSeeSubdomainCommission, $userSubdomain, $userLogged, $userSelected) {
        $stages = [
            ['$addFields' => [
                'isBaja' => ['$eq' => ['$latestStatus.code', 'b']],
                'blockByZoco' => [
                    '$and' => [
                        ['$eq' => ['$assignedTo', '65cb57489c2c285441086a43']],
                        [
                            '$not' => [[
                                '$in' => [
                                    $userSelected['_id'],
                                    ['65cb57489c2c285441086a43', '65d704c63d2a9cbfd79e549a']
                                ]
                            ]]
                        ]
                    ]
                ],
            ]]
        ];

        if ($canSeeSubdomainCommission) {
            $stages[] = ['$addFields' => [
                'subIdx' => [
                    '$indexOfArray' => [
                        ['$ifNull' => ['$commissions.breakdown.userId', []]],
                        $userSubdomain['_id']
                    ]
                ],
                'hasAssignedTo' => [
                    '$gt' => [['$ifNull' => ['$assignedTo', null]], null]
                ],
                'isAssignedToUser' => [
                    '$eq' => ['$assignedTo', $userSelected['_id']]
                ],
            ]];

            $stages[] = ['$addFields' => [
                'subCom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                '$commissions.subdomain',
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => [['$ifNull' => ['$commissions.breakdown.commission', []]], '$subIdx']],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
                'subDecom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                ['$ifNull' => ['$decommissions.subdomain', 0]],
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], '$subIdx']],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
                'agentCom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                ['$arrayElemAt' => [['$ifNull' => ['$commissions.breakdown.commission', []]], 0]],
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => [['$ifNull' => ['$commissions.breakdown.commission', []]], ['$add' => ['$subIdx', 1]]]],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
                'agentDecom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$or' => [
                                    ['$not' => ['$hasAssignedTo']],
                                    '$isAssignedToUser'
                                ]],
                                ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], 0]],
                                [
                                    '$cond' => [
                                        ['$gte' => ['$subIdx', 0]],
                                        ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], ['$add' => ['$subIdx', 1]]]],
                                        0
                                    ]
                                ]
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
            ]];
        } else {
            $stages[] = ['$addFields' => [
                'idx' => [
                    '$indexOfArray' => [
                        ['$ifNull' => ['$commissions.breakdown.userId', []]],
                        $userLogged['_id']
                    ]
                ],
                'idxDecom' => [
                    '$indexOfArray' => [
                        ['$ifNull' => ['$decommissions.breakdown.userId', []]],
                        $userLogged['_id']
                    ]
                ],
            ]];

            $stages[] = ['$addFields' => [
                // Sin permiso no devolvemos comisión de empresa/subdominio.
                'subCom' => 0,
                'subDecom' => 0,
                'agentCom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$gte' => ['$idx', 0]],
                                ['$arrayElemAt' => [['$ifNull' => ['$commissions.breakdown.commission', []]], '$idx']],
                                0
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
                'agentDecom' => [
                    '$convert' => [
                        'input' => [
                            '$cond' => [
                                ['$gte' => ['$idxDecom', 0]],
                                ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], '$idxDecom']],
                                0
                            ]
                        ],
                        'to' => 'double', 'onError' => 0, 'onNull' => 0
                    ]
                ],
            ]];
        }

        return $stages;
    };

    $pipeline = [
        ['$match' => [
            'usersIds' => ['$in' => $userHierarchyIds],
        ]],
        ['$addFields' => [
            'createdAtTemporal' => [
                '$dateFromString' => [
                    'dateString' => [
                        '$concat' => [
                            '20',
                            ['$substr' => ['$transferDate', 6, 2]],
                            '-',
                            ['$substr' => ['$transferDate', 3, 2]],
                            '-',
                            ['$substr' => ['$transferDate', 0, 2]],
                        ],
                    ],
                    'format' => '%Y-%m-%d',
                    'onError' => null,
                    'onNull' => null,
                ],
            ],
            'latestStatus' => [
                '$reduce' => [
                    'input' => ['$ifNull' => ['$statuses', []]],
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
        ]],
    ];

    foreach ($commissionStages() as $stage) {
        $pipeline[] = $stage;
    }

    $pipeline[] = ['$match' => [
        'createdAtTemporal' => [
            '$gte' => $startDateUtc,
            '$lte' => $endDateUtc,
        ],
    ]];

    $pipeline[] = ['$group' => [
        '_id' => '$latestStatus.code',
        'value' => ['$sum' => 1],
        'agentCommission' => [
            '$sum' => [
                '$cond' => [
                    '$isBaja',
                    ['$subtract' => ['$agentCom', '$agentDecom']],
                    '$agentCom'
                ]
            ]
        ],
        'subdomainCommission' => [
            '$sum' => [
                '$cond' => [
                    '$blockByZoco',
                    0,
                    [
                        '$cond' => [
                            '$isBaja',
                            ['$subtract' => ['$subCom', '$subDecom']],
                            '$subCom'
                        ]
                    ]
                ]
            ]
        ],
    ]];

    $pipeline[] = ['$project' => [
        '_id' => 0,
        'category' => '$_id',
        'value' => 1,
        'agentCommission' => 1,
        'subdomainCommission' => 1,
    ]];

    $contractsPerStatus = Order::raw(fn($collection) => $collection->aggregate($pipeline));
    $statuses = $userSubdomain['statuses'] ?? [];

    $statusMap = collect($statuses)->mapWithKeys(fn($status) => [
        $status['code'] => [
            'title' => $status['title'] ?? $status['code'],
            'color' => $status['color'] ?? null,
        ],
    ]);

    $contractsPerStatus = collect($contractsPerStatus)
        ->map(function ($item) use ($statusMap) {
            $code = $item['category'];
            $meta = $statusMap[$code] ?? ['title' => ucfirst((string) $code), 'color' => null];

            return [
                'category' => $code,
                'title' => $meta['title'],
                'color' => $meta['color'],
                'value' => $item['value'],
                'agentCommission' => $item['agentCommission'] ?? 0,
                'subdomainCommission' => $item['subdomainCommission'] ?? 0,
            ];
        })
        ->filter(fn($item) => $item['value'] > 0)
        ->sortByDesc('value')
        ->values();

    // === 📆 Pendientes de renovación ===
    $ordersRenewals = Order::whereIn('usersIds', $userHierarchyIds)
        ->whereNotNull('renewalDate')
        ->where('renewalDate', '!=', '')
        ->where('isReminderOn', true)
        ->get(['_id', 'name', 'CUPS', 'renewalDate', 'createdAt']);

    $ordersRenewalsData = $ordersRenewals->filter(function ($order) use ($startDate, $endDate) {
        try {
            $renewal = Carbon::parse($order->renewalDate);
            return $renewal->between($startDate, $endDate);
        } catch (\Exception $e) {
            return false;
        }
    })->values();

    $pendingRenewalCount = $ordersRenewalsData->count();

    $pendingRenewalsPipeline = [
        ['$match' => [
            'usersIds' => ['$in' => $userHierarchyIds],
            'renewalDate' => ['$nin' => [null, '']],
            'isReminderOn' => true,
        ]],
        ['$addFields' => [
            'renewalDateTemporal' => [
                '$dateFromString' => [
                    'dateString' => '$renewalDate',
                    'format' => '%Y-%m-%d',
                    'onError' => null,
                    'onNull' => null,
                ],
            ],
            'latestStatus' => [
                '$reduce' => [
                    'input' => ['$ifNull' => ['$statuses', []]],
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
        ]],
    ];

    foreach ($commissionStages() as $stage) {
        $pendingRenewalsPipeline[] = $stage;
    }

    $pendingRenewalsPipeline[] = ['$match' => [
        'renewalDateTemporal' => [
            '$gte' => $startDateUtc,
            '$lte' => $endDateUtc,
        ],
    ]];

    $pendingRenewalsPipeline[] = ['$group' => [
        '_id' => null,
        'agentCommission' => [
            '$sum' => [
                '$cond' => [
                    '$isBaja',
                    ['$subtract' => ['$agentCom', '$agentDecom']],
                    '$agentCom'
                ]
            ]
        ],
        'subdomainCommission' => [
            '$sum' => [
                '$cond' => [
                    '$blockByZoco',
                    0,
                    [
                        '$cond' => [
                            '$isBaja',
                            ['$subtract' => ['$subCom', '$subDecom']],
                            '$subCom'
                        ]
                    ]
                ]
            ]
        ],
    ]];

    $pendingRenewalsCommissions = Order::raw(fn($collection) => $collection->aggregate($pendingRenewalsPipeline))->toArray();
    $pendingRenewalsCommissions = $pendingRenewalsCommissions[0] ?? [
        'agentCommission' => 0,
        'subdomainCommission' => 0,
    ];

    return response()->json([
        'contractsPerStatus' => $contractsPerStatus,
        'pendingRenewals' => [
            'title' => 'Pendientes de renovación',
            'value' => $pendingRenewalCount,
            'orders' => $ordersRenewalsData,
            'agentCommission' => $pendingRenewalsCommissions['agentCommission'] ?? 0,
            'subdomainCommission' => $pendingRenewalsCommissions['subdomainCommission'] ?? 0,
        ],
        'filtersObtained' => $resultsFilters[0] ?? [],
    ], 200);
}





    //función para sacar el consumo por agentes
    public function getConsumeAndContractsPerAgentData(Request $request)
    {
        $usersIds = $request->input('usersIds');
        $dates = $request->input('dates');
        $userSelected = $request->input('userSelected');

        //Le meto el propio usuario
        $usersIds[] = $userSelected['_id'];

        // Verificar si el usuario seleccionado es '65cb57489c2c285441086a43'
        if ($userSelected['_id'] === '65cb57489c2c285441086a43') {
            // Tratarlo como si fuera '65fd4c2f05efc4aa4a050dc2'
            $userSelected['_id'] = '65fd4c2f05efc4aa4a050dc2';
        }

        // Convertir las fechas a timestamp para comparar
        $startDate = isset($dates['start']) ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['start'])->startOfDay()->timestamp * 1000) : null;
        $endDate = isset($dates['end']) ? new UTCDateTime(Carbon::createFromFormat('d/m/Y', $dates['end'])->endOfDay()->timestamp * 1000) : null;

        $pipeline = [
            ['$match' => ['usersIds' => ['$in' => $usersIds]]],
            ['$addFields' => [
                'createdAtTemporal' => [
                    '$dateFromString' => [
                        'dateString' => [
                            '$concat' => [
                                '20',
                                ['$substr' => ['$transferDate', 6, 2]], // Año
                                '-',
                                ['$substr' => ['$transferDate', 3, 2]], // Mes
                                '-',
                                ['$substr' => ['$transferDate', 0, 2]]  // Día
                            ]
                        ],
                        'format' => '%Y-%m-%d'
                    ]
                ]
            ]],
            ['$match' => [
                'createdAtTemporal' => [
                    '$gte' => $startDate,
                    '$lte' => $endDate
                ]
            ]],
            ['$group' => [
                '_id' => ['agent' => ['$arrayElemAt' => ['$usersIds', 0]]], // Obtener el primer valor de `usersIds`
                'totalConsumption' => [
                    '$sum' => [
                        '$toDouble' => [
                            '$cond' => [
                                'if' => ['$eq' => ['$consumption', '']], // Si 'consume' está vacío
                                'then' => 0,  // Reemplazar por 0
                                'else' => ['$toDouble' => '$consumption']  // Convertir 'consume' a número
                            ]
                        ]
                    ]
                ],
                'totalContracts' => ['$sum' => 1]
            ]],
            ['$project' => [
                '_id' => 0,
                'agent' => '$_id.agent',
                'totalConsumption' => 1,
                'totalContracts' => 1
            ]]
        ];

        // Ejecutar la consulta
        $consumePerAgentData = Order::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $consumePerAgentData = $consumePerAgentData->toArray();

        //dd('data --> ', $consumePerAgentData);

        // Saco los usuarios justo por debajo del usuario seleccionado
        $usersDownSelected = User::whereIn('responsibles', [$userSelected['_id']])->get();

        $result = []; // Aquí almacenaremos el resultado final

        //Añado al usuario con sesión iniciada propia
        $userLoggedFound = array_filter($consumePerAgentData, function ($item) use ($userSelected) {
            return $item['agent'] === ($userSelected['_id'] === '65fd4c2f05efc4aa4a050dc2' ? '65cb57489c2c285441086a43' : $userSelected['_id']);
        });


        $userLoggedFound = reset($userLoggedFound);

        $userLoggedFound['agent'] = $userSelected['firstName'];


        $result[] = $userLoggedFound;


        // Para cada usuario justo debajo, procesamos sus datos
        foreach ($usersDownSelected as $user) {

            $usersDownIds = UserHelper::hierarchy($user->_id);

            // Saco IDs de los usuarios en la jerarquía
            $usersDownIds = array_map(function ($user) {
                return $user['_id'];
            }, $usersDownIds);

            // Busco la comisión del usuario principal
            $userFound = array_filter($consumePerAgentData, function ($item) use ($user) {
                return $item['agent'] === $user->_id;
            });

            $userData = reset($userFound);

            $userData['agent'] = $user->firstName;

            // Si el usuario existe, sumamos las comisiones de los usuarios debajo de él
            foreach ($usersDownIds as $userId) {
                $userDownConsumption = array_filter($consumePerAgentData, function ($item) use ($userId) {
                    return $item['agent'] === $userId;
                });


                if (!isset($userData['totalConsumption']))
                    $userData['totalConsumption'] = 0;

                if (!isset($userData['totalContracts']))
                    $userData['totalContracts'] = 0;

                if ($userDownConsumption) {
                    $userDown = reset($userDownConsumption);
                    $totalConsumptionDown = isset($userDown['totalConsumption'])
                        ? (is_string($userDown['totalConsumption'])
                            ? ($userDown['totalConsumption'] === '' ? 0 : floatval($userDown['totalConsumption']))
                            : floatval($userDown['totalConsumption']))
                        : 0;

                    $userData['totalConsumption'] += $totalConsumptionDown;

                    $userData['totalContracts'] += $userDown['totalContracts'];
                }

            }

            $result[] = $userData; // Agregamos los datos al resultado final
        }




        return response()->json(['consumePerAgentData' => $result], 200);
    }


    //saca la fecha principal en la que hay datos ( contratos )
    public function getFirstDate(Request $request)
    {
        $userSubdomainId = $request->input('userSubdomain');
        $userSubdomain = User::where('_id', $userSubdomainId)->first()->toArray();
        $userList = UserHelper::getSubdomainUserList($userSubdomain);
        $userIds = array_column($userList, '_id');

        $firstContract = Order::raw(function ($collection) use ($userIds) {
            return $collection->aggregate([
                [
                    '$match' => [
                        'usersIds' => ['$in' => $userIds]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => null,
                        'firstDate' => ['$min' => '$createdAt']
                    ]
                ]
            ]);
        })->first();

        $firstDate = $firstContract->firstDate ?? '2020-01-01 00:00:00';

        return response()->json(['firstDate' => $firstDate]);
    }

}
