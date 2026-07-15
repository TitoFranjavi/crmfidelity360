<?php

namespace App\Services;

use App\Http\Models\Order;
use App\Http\Models\Account;
use App\Http\Models\User;
use App\Helpers\UserHelper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OrderIndexService
{
    public function getOrders(array $params, $userLogged): array
    {
        $userSubdomain = UserHelper::getUserSubdomain($userLogged['_id']);
        $canSeeSubdomainCommission = in_array(
            'manageCommissions',
            $userSubdomain['labels_permissions'][$userLogged['label']]['contracts']
        );
        $canSeeAllOrders = in_array('admiWhiHier', $userSubdomain['labels_permissions'][$userLogged['label']]['users']);

        $userList  = UserHelper::hierarchy($canSeeAllOrders ? $userSubdomain['_id'] : $userLogged['_id']);
        $usersIds  = array_column($userList, '_id');
        $usersIds[] = (string) $userLogged['_id'];

        $sortBy = $params['sortBy'] ?? $userSubdomain['_id'] === '6909faa9232c09035a03f3b2' ? 'id' : 'lastUpdate';

        $perPage     = $params['perPage'];
        $currentPage = $params['currentPage'];
        $skip        = ($currentPage - 1) * $perPage;
        $sort        = $this->buildSort($sortBy, $params['sortDirection']);
        $match       = $this->buildMatch($usersIds, $params['search'], $params['searchOption'], $params['filters']);

        $summaryPipeline = $this->buildSummaryPipeline($match, $canSeeSubdomainCommission, $userSubdomain, $userLogged);
        $summary         = $this->fetchSummary($summaryPipeline);
        $totalPages      = (int) ceil($summary['total'] / $perPage);

        $orders = $this->fetchOrders($match, $sort, $skip, $perPage);
        $orders = $this->hydrateOrders($orders);

        return [
            'orders'     => $orders,
            'totalPages' => $totalPages,
            'summary'    => $summary,
        ];
    }

    public function getAccountEmails(array $params, $userLogged): array
    {
        $userList   = UserHelper::hierarchy($userLogged['_id']);
        $usersIds   = array_column($userList, '_id');
        $usersIds[] = (string) $userLogged['_id'];

        $match = $this->buildMatch(
            $usersIds,
            $params['search'],
            $params['searchOption'],
            $params['filters']
        );

        $accountIds = Order::raw(fn($c) => $c->aggregate([
            ['$match'   => $match],
            ['$group'   => ['_id' => '$account']],
            ['$project' => ['_id' => 1]],
        ])->toArray());

        $ids = array_column($accountIds, '_id');

        return Account::whereIn('_id', $ids)
            ->whereNotNull('email')
            ->pluck('email')
            ->toArray();
    }

    public function exportOrders(array $params, $userLogged): string
    {
        $userSubdomain = UserHelper::getUserSubdomain($userLogged['_id']);
        $canSeeAllOrders = in_array('admiWhiHier', $userSubdomain['labels_permissions'][$userLogged['label']]['users']);

        $userList   = UserHelper::hierarchy($canSeeAllOrders ? $userSubdomain['_id'] : $userLogged['_id']);
        $usersIds   = array_column($userList, '_id');
        $usersIds[] = (string) $userLogged['_id'];

        $match  = $this->buildMatch($usersIds, $params['search'], $params['searchOption'], $params['filters']);
        $sort   = $this->buildSort($params['sortBy'], $params['sortDirection']);

        $orders = $this->fetchAllOrders($match, $sort);
        $orders = $this->hydrateOrdersForExport($orders);

        return $this->writeExcel($orders, $params['userSubdomain'], $userLogged);
    }

    private function buildSort(string $sortBy, string $sortDirection): array
    {
        $sortFields = [
            'id'             => 'identifier',
            'activationDate' => 'activationDate',
            'lastUpdate'     => 'lastUpdate',
        ];

        $sortField = $sortFields[$sortBy] ?? 'lastUpdate';
        $sortOrder = strtolower($sortDirection) === 'asc' ? 1 : -1;

        return [$sortField => $sortOrder];
    }

    private function buildMatch(array $usersIds, ?string $search, string $searchOption, array $filters = []): array
    {
        $searchOption = $this->normalizeSearchOptionValue($searchOption);

        $searchFields = [
            'name' => ['search_name'],
            'cups' => ['CUPS', 'CUPSSecondary'],
            'identifier' => ['identifier'],
            'verificationPhone' => ['verificationPhone'],
            'contractEmail' => ['contractEmail'],
        ];

        // Si viene filtro de agents no vacío, se filtra por ambos
        if (!empty($filters['agents'])) {
            // El agente debe estar en el contrato y el contrato debe ser visible para mí
            $match = [
                '$and' => [
                    ['usersIds' => ['$in' => $filters['agents']]],
                    ['usersIds' => ['$in' => $usersIds]]
                ]
            ];
        } else {
            $match = ['usersIds' => ['$in' => $usersIds]];
        }

        if (!empty($search)) {
            $normalizedSearch = $this->normalizeSearch($search);
            $searchRegex = ['$regex' => preg_quote($normalizedSearch, '/'), '$options' => 'i'];

            $needsCIFSearch = in_array($searchOption, ['CIF', 'all'], true);
            $accountIds = [];

            if ($needsCIFSearch) {
                $normalizedDocument = strtoupper(preg_replace('/[\s\.\-]/', '', (string) $normalizedSearch));

                $chars = str_split(preg_quote($normalizedDocument, '/'));
                $pattern = '^' . implode('[\s\.\-]*', $chars) . '$';

                $accountIds = Account::where('CIF', 'regexp', "/{$pattern}/i")
                    ->pluck('_id')
                    ->toArray();
            }


            if ($searchOption === 'CIF') {
                $match['account'] = ['$in' => $accountIds];

            } elseif ($searchOption === 'all') {
                $orConditions = [];

                foreach ($searchFields as $fields) {
                    foreach ($fields as $field) {
                        if ($field === 'identifier') {
                            $orConditions = array_merge(
                                $orConditions,
                                $this->buildIdentifierSearchConditions($search)
                            );

                            continue;
                        }

                        $orConditions[] = [
                            $field => $searchRegex,
                        ];
                    }
                }

                $orConditions[] = [
                    'account' => ['$in' => $accountIds],
                ];

                $match['$or'] = $orConditions;

            } elseif ($searchOption === 'identifier') {
                $match['$or'] = $this->buildIdentifierSearchConditions($search);

            } elseif (isset($searchFields[$searchOption])) {
                $fields = $searchFields[$searchOption];

                $match['$or'] = array_map(
                    fn($field) => [$field => $searchRegex],
                    $fields
                );
            }
        }

        if (!empty($filters['statuses'])) {
            $match['lastStatus.code'] = ['$in' => $filters['statuses']];
        }

        if (!empty($filters['productTypes'])) {
            $productTypeFilter = array_map(fn($f) => $f === null ? '' : $f, $filters['productTypes']);
            $match['productType'] = ['$in' => $productTypeFilter];
        }

        if (!empty($filters['fees'])) {
            $feeFilter = array_map(fn($f) => $f === null ? '' : $f, $filters['fees']);
            $match['$or'] = [
                ['fee' => ['$in' => $feeFilter]],
                ['feeSecondary' => ['$in' => $feeFilter]],
            ];
        }

        if (!empty($filters['marketers'])) {
            $marketerFilter = array_map(fn($f) => $f === null ? '' : $f, $filters['marketers']);
            $match['marketer'] = ['$in' => $marketerFilter];
        }

        if (!empty($filters['products'])) {
            $productFilter = array_map(fn($f) => $f === null ? '' : $f, $filters['products']);
            $match['$or'] = [
                ['product' => ['$in' => $productFilter]],
                ['productSecondary' => ['$in' => $productFilter]],
            ];
        }

        $dateMappings = [
            'creationDates'   => ['field' => 'createdAt',       'isDatetime' => true],
            'activationDates' => ['field' => 'activationDate',  'isDatetime' => false],
            'lowDates'        => ['field' => 'lowDate',         'isDatetime' => false],
            'renewalDates'    => ['field' => 'renewalDate',     'isDatetime' => false],
        ];

        foreach ($dateMappings as $filterKey => $config) {
            if (!empty($filters[$filterKey])) {
                $constraints = $this->buildDateConstraint(
                    $filters[$filterKey]['start'] ?? null,
                    $filters[$filterKey]['end'] ?? null,
                    $config['isDatetime']
                );

                if (!empty($constraints)) {
                    $match[$config['field']] = $constraints;
                }
            }
        }

        if (is_bool($filters['subdomains'])) {
            $match['assignedTo'] = $filters['subdomains']
                ? '65cb57489c2c285441086a43'
                : ['$ne' => '65cb57489c2c285441086a43'];
        }

        return $match;
    }

    private function normalizeSearchOptionValue(?string $searchOption): string
    {
        $value = Str::ascii((string) $searchOption);
        $value = strtolower(trim($value));
        $value = str_replace([' ', '-', '_', '/', '\\'], '', $value);

        return match ($value) {
            'todo', 'todos', 'all' => 'all',

            'cif',
            'nif',
            'nifcif',
            'cifnif',
            'dninif',
            'dninifcif',
            'documento',
            'document' => 'CIF',

            'cups' => 'cups',
            'identificador',
            'identifier',
            'id',
            'idcontrato',
            'contratoid',
            'ncontrato',
            'numerocontrato',
            'numcontrato',
            'numero',
            'codigo',
            'codigocontrato' => 'identifier',
            'telefono', 'verificationphone', 'telefonoverificacion' => 'verificationPhone',
            'email', 'contractemail', 'emailcontrato' => 'contractEmail',
            'nombre', 'name' => 'name',

            default => $searchOption ?: 'all',
        };
    }

    private function buildIdentifierSearchConditions(string $search): array
    {
        $normalizedSearch = $this->normalizeSearch($search);
        $identifierRegex = preg_quote($normalizedSearch, '/');

        $conditions = [
            // Caso normal: identifier guardado como string
            [
                'identifier' => [
                    '$regex' => $identifierRegex,
                    '$options' => 'i',
                ],
            ],

            // Caso robusto: identifier guardado como número u otro tipo
            [
                '$expr' => [
                    '$regexMatch' => [
                        'input' => [
                            '$toString' => [
                                '$ifNull' => ['$identifier', '']
                            ]
                        ],
                        'regex' => $identifierRegex,
                        'options' => 'i',
                    ],
                ],
            ],
        ];



        return $conditions;
    }

    private function normalizeSearch(string $search): string
    {
        $search = Str::ascii($search);
        return str_replace(' ', '', $search);
    }

    private function buildSummaryPipeline(array $match, bool $canSeeSubdomainCommission, $userSubdomain, $userLogged): array
    {
        $pipeline = [['$match' => $match]];

        if ($canSeeSubdomainCommission) {
            $pipeline = array_merge($pipeline, $this->subdomainCommissionStages($userSubdomain, $userLogged));
        } else {
            $pipeline = array_merge($pipeline, $this->agentCommissionStages($userLogged));
        }

        $pipeline[] = $this->groupStage($canSeeSubdomainCommission);

        return $pipeline;
    }

    private function fetchSummary(array $pipeline): array
    {
        $result = Order::raw(fn($c) => $c->aggregate($pipeline)->toArray());

        if (empty($result)) {
            return [
                'total'                => 0,
                'totalConsumption'     => 0,
                'agentCommission'      => 0,
                'agentBelowCommission' => 0,
                'subdomainCommission'  => 0,
            ];
        }

        return (array) $result[0];
    }

    private function buildDateConstraint(?string $start, ?string $end, bool $isDatetime = false): array
    {
        $constraints = [];

        if (!empty($start)) {
            $constraints['$gte'] = $isDatetime ? $start . ' 00:00:00' : $start;
        }

        if (!empty($end)) {
            $constraints['$lte'] = $isDatetime ? $end . ' 23:59:59' : $end;
        }

        return $constraints;
    }

    private function fetchOrders(array $match, array $sort, int $skip, int $perPage): array
    {
        return Order::raw(fn($c) => $c->aggregate([
            ['$match' => $match],
            ['$sort'  => $sort],
            ['$skip'  => $skip],
            ['$limit' => $perPage],
        ])->toArray());
    }

    private function fetchAllOrders(array $match, array $sort): array
    {
        return Order::raw(fn($c) => $c->aggregate([
            ['$match' => $match],
            ['$sort'  => $sort],
        ], ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']])->toArray());
    }

    private function hydrateOrders(array $orders): array
    {
        $accountIds  = array_unique(array_column($orders, 'account'));
        $accountsMap = Account::whereIn('_id', $accountIds)->pluck('CIF', '_id');

        $userIds = array_unique(
            array_filter(
                array_map(fn($o) => (string) ($o['usersIds'][0] ?? null), $orders)
            )
        );

        $usersMap = User::whereIn('_id', $userIds)
            ->select('_id', 'firstName', 'lastName')
            ->get()
            ->keyBy('_id');

        return array_map(function ($order) use ($accountsMap, $usersMap) {
            $order['accountCIF'] = isset($order['account'])
                ? ($accountsMap[(string) $order['account']] ?? null)
                : null;

            $user          = $usersMap[(string) ($order['usersIds'][0] ?? '')] ?? null;
            $order['owner'] = $user ? trim("$user->firstName $user->lastName") : null;

            return $order;
        }, $orders);
    }

    private function subdomainCommissionStages($userSubdomain, $userLogged): array
    {
        return [
            ['$addFields' => [
                'subIdx' => [
                    '$indexOfArray' => ['$commissions.breakdown.userId', $userSubdomain['_id']]
                ],
                'hasAssignedTo' => [
                    '$gt' => [['$ifNull' => ['$assignedTo', null]], null]
                ],
                'isAssignedToUser' => [
                    '$eq' => ['$assignedTo', $userLogged['_id']]
                ],
            ]],
            ['$addFields' => [
                'subCom' => ['$convert' => ['input' => ['$cond' => [
                    ['$or' => [['$not' => ['$hasAssignedTo']], '$isAssignedToUser']],
                    '$commissions.subdomain',
                    ['$cond' => [['$gte' => ['$subIdx', 0]], ['$arrayElemAt' => ['$commissions.breakdown.commission', '$subIdx']], 0]]
                ]], 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
                'subDecom' => ['$convert' => ['input' => ['$cond' => [
                    ['$or' => [['$not' => ['$hasAssignedTo']], '$isAssignedToUser']],
                    ['$ifNull' => ['$decommissions.subdomain', 0]],
                    ['$cond' => [['$gte' => ['$subIdx', 0]], ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], '$subIdx']], 0]]
                ]], 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
                'agentCom' => ['$convert' => ['input' => ['$cond' => [
                    ['$or' => [['$not' => ['$hasAssignedTo']], '$isAssignedToUser']],
                    ['$arrayElemAt' => ['$commissions.breakdown.commission', 0]],
                    ['$cond' => [['$gte' => ['$subIdx', 0]], ['$arrayElemAt' => ['$commissions.breakdown.commission', ['$add' => ['$subIdx', 1]]]], 0]]
                ]], 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
                'agentDecom' => ['$convert' => ['input' => ['$cond' => [
                    ['$or' => [['$not' => ['$hasAssignedTo']], '$isAssignedToUser']],
                    ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], 0]],
                    ['$cond' => [['$gte' => ['$subIdx', 0]], ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], ['$add' => ['$subIdx', 1]]]], 0]]
                ]], 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
            ]],
        ];
    }

    private function agentCommissionStages($userLogged): array
    {
        return [
            ['$addFields' => [
                'idx' => [
                    '$indexOfArray' => ['$commissions.breakdown.userId', $userLogged['_id']]
                ],
            ]],
            ['$addFields' => [
                'agentCom' => ['$convert' => ['input' => [
                    '$cond' => [
                        ['$gte' => ['$idx', 0]],
                        ['$arrayElemAt' => ['$commissions.breakdown.commission', '$idx']],
                        0
                    ]
                ], 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
                'agentDecom' => ['$convert' => ['input' => [
                    '$cond' => [
                        ['$and' => [
                            ['$gte' => ['$idx', 0]],
                            ['$gt' => [['$size' => ['$ifNull' => ['$decommissions.breakdown', []]]], 0]]
                        ]],
                        ['$arrayElemAt' => [['$ifNull' => ['$decommissions.breakdown.commission', []]], '$idx']],
                        0
                    ]
                ], 'to' => 'double', 'onError' => 0, 'onNull' => 0]],
                'belowCom' => [
                    '$reduce' => [
                        'input' => [
                            '$map' => [
                                'input' => [
                                    '$cond' => [
                                        ['$and' => [
                                            ['$gte' => ['$idx', 0]],
                                            ['$lt' => ['$idx', ['$subtract' => [['$size' => '$commissions.breakdown'], 1]]]]
                                        ]],
                                        ['$slice' => [
                                            '$commissions.breakdown',
                                            ['$add' => ['$idx', 1]],
                                            ['$max' => [1, ['$subtract' => [['$size' => '$commissions.breakdown'], ['$add' => ['$idx', 1]]]]]]
                                        ]],
                                        []
                                    ]
                                ],
                                'as' => 'item',
                                'in' => [
                                    '$convert' => ['input' => '$$item.commission', 'to' => 'double', 'onError' => 0, 'onNull' => 0]
                                ]
                            ]
                        ],
                        'initialValue' => 0,
                        'in' => ['$add' => ['$$value', '$$this']]
                    ]
                ],
                'belowDecom' => [
                    '$reduce' => [
                        'input' => [
                            '$map' => [
                                'input' => [
                                    '$cond' => [
                                        ['$and' => [
                                            ['$gte' => ['$idx', 0]],
                                            ['$gt' => [['$size' => ['$ifNull' => ['$decommissions.breakdown', []]]], 0]],
                                            ['$lt' => ['$idx', ['$subtract' => [['$size' => ['$ifNull' => ['$decommissions.breakdown', []]]], 1]]]]
                                        ]],
                                        ['$slice' => [
                                            ['$ifNull' => ['$decommissions.breakdown', []]],
                                            ['$add' => ['$idx', 1]],
                                            ['$max' => [1, ['$subtract' => [['$size' => ['$ifNull' => ['$decommissions.breakdown', []]]], ['$add' => ['$idx', 1]]]]]]
                                        ]],
                                        []
                                    ]
                                ],
                                'as' => 'item',
                                'in' => [
                                    '$convert' => ['input' => '$$item.commission', 'to' => 'double', 'onError' => 0, 'onNull' => 0]
                                ]
                            ]
                        ],
                        'initialValue' => 0,
                        'in' => ['$add' => ['$$value', '$$this']]
                    ]
                ],
            ]],
        ];
    }

    private function groupStage(bool $canSeeSubdomainCommission): array
    {
        return ['$group' => [
            '_id'                  => null,
            'total'                => ['$sum' => 1],
            'totalConsumption'     => ['$sum' => ['$convert' => ['input' => '$consumption', 'to' => 'double', 'onError' => 0, 'onNull' => 0]]],
            'subdomainCommission'  => ['$sum' => $canSeeSubdomainCommission ? ['$cond' => [
                '$blockByZoco',
                0,
                ['$cond' => [
                    '$isBaja',
                    ['$subtract' => ['$subCom', '$subDecom']],
                    '$subCom'
                ]]
            ]] : 0],
            'agentCommission'      => ['$sum' => ['$cond' => [
                '$isBaja',
                ['$subtract' => ['$agentCom', '$agentDecom']],
                '$agentCom'
            ]]],
            'agentBelowCommission' => ['$sum' => ['$cond' => [
                '$isBaja',
                ['$subtract' => ['$belowCom', '$belowDecom']],
                '$belowCom'
            ]]],
        ]];
    }

    private function hydrateOrdersForExport(array $orders): array
    {
        // CIFs
        $accountIds  = array_unique(array_column($orders, 'account'));
        $accountsMap = Account::whereIn('_id', $accountIds)->pluck('CIF', '_id');

        // Emails
        $allUserIds = [];
        foreach ($orders as $order) {
            array_push($allUserIds, ...($order['usersIds'] ?? []));
        }

        $emailsMap = User::whereIn('_id', array_unique($allUserIds))
            ->pluck('email', '_id')
            ->map(fn($e) => strtolower(trim($e)))
            ->toArray();

        return array_map(function ($order) use ($accountsMap, $emailsMap) {
            $order['accountCIF'] = isset($order['account'])
                ? ($accountsMap[(string) $order['account']] ?? null)
                : null;

            $order['ownerEmails'] = collect($order['usersIds'] ?? [])
                ->filter(fn($id) => !empty($emailsMap[$id]))
                ->map(fn($id) => $emailsMap[$id])
                ->unique()
                ->implode(',');

            return $order;
        }, $orders);
    }

    private function writeExcel(array $orders, $userSubdomain, $userLogged): string
    {
        $templatePath = public_path('assets/templates/orders_export.xlsx');
        $spreadsheet  = IOFactory::load($templatePath);
        $sheet        = $spreadsheet->getActiveSheet();

        $statusMap = collect($userSubdomain['statuses'] ?? [])
            ->keyBy('code')
            ->map(fn($s) => $s['title'] ?? $s['code'])
            ->toArray();

        $pTypeTable = collect(config('product_types'))
            ->pluck('title', 'code')
            ->toArray();

        $canSeeSubdomainCommission = in_array('manageCommissions', $userSubdomain['labels_permissions'][$userLogged['label']]['contracts']);

        $row = 2;
        foreach ($orders as $o) {
            $sheet->fromArray($this->buildExcelRow($o, $pTypeTable, $statusMap, $canSeeSubdomainCommission, $userLogged), null, "A{$row}");
            $row++;
        }

        $filename = 'Contratos_' . uniqid() . '.xlsx';
        $tmpPath  = storage_path('app/tmp/' . $filename);
        @mkdir(dirname($tmpPath), 0775, true);

        (new Xlsx($spreadsheet))->save($tmpPath);

        return $tmpPath;
    }

    private function buildExcelRow(array $o, array $pTypeTable, array $statusMap, $canSeeSubdomainCommission, $userLogged): array
    {
        $formatDate = function ($v) {
            if (empty($v)) return '';
            try {
                if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $v)) {
                    return Carbon::createFromFormat('d/m/y', $v)->format('d/m/Y');
                }
                return Carbon::make($v)?->format('d/m/Y') ?? (string) $v;
            } catch (\Throwable $e) {
                return (string) $v;
            }
        };

        $statuses = $o['statuses'] ?? [];
        usort($statuses, fn($a, $b) => strtotime($b['date'] ?? '') <=> strtotime($a['date'] ?? ''));
        $lastCode  = $statuses[0]['code'] ?? null;
        $lastTitle = $lastCode ? ($statusMap[$lastCode] ?? $lastCode) : '';

        $verifs = collect($o['verifications'] ?? []);
        $p      = $o['newRegistrationPeriods'] ?? [];

        $userCommission = '';
        $subdomainCommission = '';

        if($canSeeSubdomainCommission){
            $userCommission = $o['commissions']['breakdown'][0]['commission'] ?? '';
            $subdomainCommission = $o['commissions']['subdomain'] ?? '';
        }else{
            $breakdown = $o['commissions']['breakdown'] ?? [];
            $index     = array_search($userLogged['_id'], array_column($breakdown, 'userId'));
            $userCommission = $index !== false ? ($breakdown[$index]['commission'] ?? '') : '';
        }

        return [
            $o['accountCIF']                  ?? '',
            $o['name']                        ?? '',
            $o['direc']                       ?? '',
            $o['town']                        ?? '',
            $o['province']                    ?? '',
            $o['zip']                         ?? '',
            $o['IBAN']                        ?? '',
            $pTypeTable[$o['productType'] ?? ''] ?? '',
            $o['marketer']                    ?? '',
            $o['fee']                         ?? '',
            $o['product']                     ?? '',
            $o['CUPS']                        ?? '',
            $o['consumption']                 ?? '',
            $o['hiredPotency']                ?? '',
            $formatDate($o['processingDate']  ?? ''),
            $userCommission,
            $subdomainCommission,
            $lastTitle,
            $formatDate($o['activationDate']  ?? ''),
            $formatDate($o['lowDate']         ?? ''),
            $o['observations']                ?? '',
            $verifs->contains('nw') ? 'Si' : '',
            $verifs->contains('pc') ? 'Si' : '',
            $verifs->contains('tc') ? 'Si' : '',
            $verifs->contains('vb') ? 'Si' : '',
            $verifs->contains('mc') ? 'Si' : '',
            $p['p1'] ?? '', $p['p2'] ?? '', $p['p3'] ?? '',
            $p['p4'] ?? '', $p['p5'] ?? '', $p['p6'] ?? '',
            $o['currentPotencyVerification']   ?? '',
            $o['requestedPotencyVerification'] ?? '',
            $formatDate($o['processingDate']   ?? ''),
            $formatDate($o['transferDate']   ?? ''),
            $o['ownerEmails']                  ?? '',
        ];
    }
}
