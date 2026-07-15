<?php

namespace App\Http\Controllers;

use App\Http\Models\Objective;
use App\Http\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class ObjectivesController extends Controller
{
    public function index(Request $request)
    {
        $start = $this->parseDate(data_get($request, 'dates.start'), true);
        $end   = $this->parseDate(data_get($request, 'dates.end'), false);

        $userSubdomainId = (string) data_get($request, 'userSubdomain._id', data_get($request, 'userSubdomain'));
        $selectedUserId  = (string) data_get($request, 'userSelected._id', '');

        $usersIds = collect($request->input('usersIds', []))
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedUserId !== '' && $selectedUserId !== $userSubdomainId && !$usersIds->contains($selectedUserId)) {
            $usersIds->push($selectedUserId);
            $usersIds = $usersIds->unique()->values();
        }

        $userList = collect($request->input('userList', []));
        $isAllAgents = $selectedUserId === '' || $selectedUserId === $userSubdomainId;

        $objectives = Objective::where('userSubdomain', $userSubdomainId)
            ->get()
            ->filter(function ($objective) use ($start, $end, $usersIds, $selectedUserId, $isAllAgents) {
                if (!$this->objectiveOverlapsRange($objective, $start, $end)) {
                    return false;
                }

                $objectiveUserId = (string) ($objective['userId'] ?? '');

                if ($objectiveUserId === '') {
                    return $isAllAgents;
                }

                if ($isAllAgents) {
                    return $usersIds->isEmpty() || $usersIds->contains($objectiveUserId);
                }

                return $objectiveUserId === $selectedUserId;
            })
            ->values();

        $generalObjectives = $objectives
            ->filter(fn ($objective) => empty($objective['userId']))
            ->values();

        $personalObjectives = $objectives
            ->filter(fn ($objective) => !empty($objective['userId']))
            ->values();

        $targetUsersIds = $isAllAgents
            ? $usersIds->values()->all()
            : [$selectedUserId];

        $orders = $this->getOrdersForObjectives($targetUsersIds, $start, $end);

        if ($objectives->isEmpty()) {
            return response()->json([
                'summary' => [
                    'totalContracts'     => 0,
                    'targetContracts'    => 0,
                    'contractsPct'       => 0,
                    'totalConsumption'   => 0,
                    'targetConsumption'  => 0,
                    'consumptionPct'     => 0,
                    'globalPct'          => 0,
                ],
                'users'            => [],
                'contractsChart'   => [],
                'consumptionChart' => [],
                'objectives'       => [
                    'general'  => [],
                    'personal' => [],
                ],
            ]);
        }

        // Ámbito de objetivos: generales si se ven todos los agentes, o los del agente
        // seleccionado. El resumen y las gráficas deben usar el mismo conjunto para ser coherentes.
        $scopeObjectives = $isAllAgents
            ? $generalObjectives
            : $personalObjectives;

        $summary = $this->buildSummary($orders, $scopeObjectives, $start, $end);

        $users = $this->buildUsersSummary(
            $orders,
            $personalObjectives,
            $userList,
            $isAllAgents ? $usersIds : collect([$selectedUserId]),
            $start,
            $end
        );

        $contractsChart = $this->buildContractsChart($orders, $scopeObjectives, $start, $end);
        $consumptionChart = $this->buildConsumptionChart($orders, $scopeObjectives, $start, $end);

        return response()->json([
            'summary'          => $summary,
            'users'            => $users,
            'contractsChart'   => $contractsChart,
            'consumptionChart' => $consumptionChart,
            'objectives'       => [
                'general'  => $this->buildObjectivesList($generalObjectives, $userList, $start, $end),
                'personal' => $this->buildObjectivesList($personalObjectives, $userList, $start, $end),
            ],
        ]);
    }

    private function getOrdersForObjectives(array $usersIds, Carbon $start, Carbon $end)
    {
        $usersIds = array_values(array_filter(array_map('strval', $usersIds)));

        if (empty($usersIds)) {
            return collect();
        }

        $startUtc = new UTCDateTime($start->copy()->startOfDay()->timestamp * 1000);
        $endUtc   = new UTCDateTime($end->copy()->endOfDay()->timestamp * 1000);

        $pipeline = [
            [
                '$match' => [
                    'usersIds' => ['$in' => $usersIds],
                ],
            ],
            [
                '$addFields' => [
                    'objectiveDate' => [
                        '$switch' => [
                            'branches' => [
                                [
                                    'case' => [
                                        '$regexMatch' => [
                                            'input' => ['$ifNull' => ['$transferDate', '']],
                                            'regex' => '^[0-9]{2}/[0-9]{2}/[0-9]{2}$',
                                        ],
                                    ],
                                    'then' => [
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
                                ],
                                [
                                    'case' => [
                                        '$regexMatch' => [
                                            'input' => ['$ifNull' => ['$transferDate', '']],
                                            'regex' => '^[0-9]{2}/[0-9]{2}/[0-9]{4}$',
                                        ],
                                    ],
                                    'then' => [
                                        '$dateFromString' => [
                                            'dateString' => [
                                                '$concat' => [
                                                    ['$substr' => ['$transferDate', 6, 4]],
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
                                ],
                                [
                                    'case' => [
                                        '$regexMatch' => [
                                            'input' => ['$ifNull' => ['$processingDate', '']],
                                            'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$',
                                        ],
                                    ],
                                    'then' => [
                                        '$dateFromString' => [
                                            'dateString' => '$processingDate',
                                            'format' => '%Y-%m-%d',
                                            'onError' => null,
                                            'onNull' => null,
                                        ],
                                    ],
                                ],
                                [
                                    'case' => [
                                        '$regexMatch' => [
                                            'input' => ['$ifNull' => ['$createdAt', '']],
                                            'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}',
                                        ],
                                    ],
                                    'then' => [
                                        '$dateFromString' => [
                                            'dateString' => [
                                                '$substr' => ['$createdAt', 0, 10],
                                            ],
                                            'format' => '%Y-%m-%d',
                                            'onError' => null,
                                            'onNull' => null,
                                        ],
                                    ],
                                ],
                                [
                                    'case' => [
                                        '$regexMatch' => [
                                            'input' => ['$ifNull' => ['$lastUpdate', '']],
                                            'regex' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}',
                                        ],
                                    ],
                                    'then' => [
                                        '$dateFromString' => [
                                            'dateString' => [
                                                '$substr' => ['$lastUpdate', 0, 10],
                                            ],
                                            'format' => '%Y-%m-%d',
                                            'onError' => null,
                                            'onNull' => null,
                                        ],
                                    ],
                                ],
                            ],
                            'default' => null,
                        ],
                    ],
                ],
            ],
            [
                '$match' => [
                    'objectiveDate' => [
                        '$gte' => $startUtc,
                        '$lte' => $endUtc,
                    ],
                ],
            ],
        ];

        $orders = Order::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });

        return collect($orders)->values();
    }

    public function store(Request $request)
    {
        $data = $this->validateObjective($request);

        $objective = Objective::create([
            'type'          => $data['type'],
            'startDate'     => $data['startDate'],
            'endDate'       => $data['endDate'],
            'marketer'      => $data['marketer'],
            'value'         => (float) $data['value'],
            'userId'        => $data['userId'],
            'userSubdomain' => (string) $data['userSubdomain'],
            'createdBy'     => (string) (Auth::user()->_id ?? Auth::id()),
            'createdAt'     => Carbon::now()->format('Y-m-d H:i:s'),
            'updatedAt'     => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        return response()->json([
            'message'   => 'Objetivo creado correctamente',
            'objective' => $objective,
        ], 201);
    }

    public function update(string $id, Request $request)
    {
        $objective = $this->findObjective($id);
        $data = $this->validateObjective($request);

        $objective->type          = $data['type'];
        $objective->startDate     = $data['startDate'];
        $objective->endDate       = $data['endDate'];
        $objective->marketer      = $data['marketer'];
        $objective->value         = (float) $data['value'];
        $objective->userId        = $data['userId'];
        $objective->userSubdomain = (string) $data['userSubdomain'];
        $objective->updatedAt     = Carbon::now()->format('Y-m-d H:i:s');

        $objective->save();

        return response()->json([
            'message'   => 'Objetivo actualizado correctamente',
            'objective' => $objective,
        ]);
    }

    public function delete(string $id)
    {
        $objective = $this->findObjective($id);
        $objective->delete();

        return response()->json(['message' => 'Objetivo eliminado correctamente']);
    }

    public function destroy(string $id)
    {
        return $this->delete($id);
    }

    private function findObjective(string $id): Objective
    {
        $id = trim((string) $id);

        if ($id === '') {
            abort(404, 'Objetivo no encontrado');
        }

        $objective = Objective::where('_id', $id)->first();

        if (!$objective && preg_match('/^[a-f0-9]{24}$/i', $id)) {
            try {
                $objective = Objective::where('_id', new ObjectId($id))->first();
            } catch (\Throwable $e) {
                $objective = null;
            }
        }

        if (!$objective) {
            abort(404, 'Objetivo no encontrado');
        }

        return $objective;
    }

    private function buildSummary($orders, $objectives, Carbon $start, Carbon $end): array
    {
        $electricityOrders = $orders->filter(fn ($order) => $this->isElectricityContract($order));

        $totalContracts = $electricityOrders->count();

        $totalConsumption = round(
            $electricityOrders->sum(fn ($order) => $this->toFloat(data_get($order, 'consumption', 0))),
            2
        );

        $targetContracts = round(
            $objectives
                ->filter(fn ($objective) => ($objective['type'] ?? null) === 'contracts')
                ->sum(fn ($objective) => $this->proratedObjectiveValue($objective, $start, $end)),
            2
        );

        $targetConsumption = round(
            $objectives
                ->filter(fn ($objective) => ($objective['type'] ?? null) === 'consumption')
                ->sum(fn ($objective) => $this->proratedObjectiveValue($objective, $start, $end)),
            2
        );

        $contractsPct = $this->calcPct($totalContracts, $targetContracts);
        $consumptionPct = $this->calcPct($totalConsumption, $targetConsumption);

        // Solo se promedian las métricas que tienen objetivo definido, para no penalizar
        // el % global cuando únicamente se ha fijado objetivo de contratos o de consumo.
        $pcts = [];
        if ($targetContracts > 0) {
            $pcts[] = $contractsPct;
        }
        if ($targetConsumption > 0) {
            $pcts[] = $consumptionPct;
        }
        $globalPct = empty($pcts) ? 0 : (int) round(array_sum($pcts) / count($pcts));

        return [
            'totalContracts'    => $totalContracts,
            'targetContracts'   => $targetContracts,
            'contractsPct'      => $contractsPct,
            'totalConsumption'  => $totalConsumption,
            'targetConsumption' => $targetConsumption,
            'consumptionPct'    => $consumptionPct,
            'globalPct'         => $globalPct,
        ];
    }

    private function buildUsersSummary($orders, $objectives, $userList, $usersIds, Carbon $start, Carbon $end): array
    {
        $rows = [];

        foreach ($usersIds as $uid) {
            $uid = (string) $uid;

            $user = $userList->first(function ($row) use ($uid) {
                return (string) data_get($row, '_id') === $uid;
            });

            if (!$user) {
                continue;
            }

            $userOrders = $orders->filter(function ($order) use ($uid) {
                $assigned = collect(data_get($order, 'usersIds', []))
                    ->map(fn ($id) => (string) $id)
                    ->filter();

                return $assigned->contains($uid);
            });

            $userObjectives = $objectives->filter(function ($objective) use ($uid) {
                return (string) ($objective['userId'] ?? '') === $uid;
            });

            if ($userOrders->isEmpty() && $userObjectives->isEmpty()) {
                continue;
            }

            $electricityOrders = $userOrders->filter(fn ($order) => $this->isElectricityContract($order));

            $realContracts = $electricityOrders->count();

            $realConsumption = round(
                $electricityOrders->sum(fn ($order) => $this->toFloat(data_get($order, 'consumption', 0))),
                2
            );

            $targetContracts = round(
                $userObjectives
                    ->filter(fn ($objective) => ($objective['type'] ?? null) === 'contracts')
                    ->sum(fn ($objective) => $this->proratedObjectiveValue($objective, $start, $end)),
                2
            );

            $targetConsumption = round(
                $userObjectives
                    ->filter(fn ($objective) => ($objective['type'] ?? null) === 'consumption')
                    ->sum(fn ($objective) => $this->proratedObjectiveValue($objective, $start, $end)),
                2
            );

            $rows[] = [
                '_id'               => (string) data_get($user, '_id'),
                'name'              => trim(data_get($user, 'firstName', '') . ' ' . data_get($user, 'lastName', '')),
                'contracts'         => $realContracts,
                'targetContracts'   => $targetContracts,
                'consumption'       => $realConsumption,
                'targetConsumption' => $targetConsumption,
            ];
        }

        return $rows;
    }

    private function buildContractsChart($orders, $objectives, Carbon $start, Carbon $end): array
    {
        $targetByMarketer = $objectives
            ->filter(fn ($objective) => ($objective['type'] ?? null) === 'contracts')
            ->groupBy(function ($objective) {
                return $this->normalizeMarketerLabel($objective['marketer'] ?? null);
            })
            ->map(function ($rows) use ($start, $end) {
                return round(
                    $rows->sum(fn ($objective) => $this->proratedObjectiveValue($objective, $start, $end)),
                    2
                );
            });

        $realMarketers = $orders
            ->filter(fn ($order) => $this->isElectricityContract($order))
            ->map(fn ($order) => $this->normalizeMarketerLabel(data_get($order, 'marketer', null)))
            ->filter(fn ($marketer) => $marketer !== 'Todas')
            ->unique()
            ->values();

        $marketers = $targetByMarketer
            ->keys()
            ->merge($realMarketers)
            ->unique()
            ->values();

        if ($marketers->isEmpty()) {
            return [];
        }

        return $marketers->map(function ($marketer) use ($orders, $targetByMarketer) {
            $real = $orders
                ->filter(fn ($order) => $this->isElectricityContract($order))
                ->filter(function ($order) use ($marketer) {
                    if ($marketer === 'Todas') {
                        return true;
                    }

                    return $this->sameMarketer(data_get($order, 'marketer', ''), $marketer);
                })
                ->count();

            return [
                'marketer' => $marketer,
                'real'     => $real,
                'target'   => (float) ($targetByMarketer->get($marketer) ?? 0),
            ];
        })
        ->sortByDesc('real')
        ->values()
        ->all();
    }

    private function buildConsumptionChart($orders, $objectives, Carbon $start, Carbon $end): array
    {
        $rows = [];
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            $effectiveStart = $monthStart->copy()->lt($start) ? $start->copy() : $monthStart;
            $effectiveEnd = $monthEnd->copy()->gt($end) ? $end->copy() : $monthEnd;

            $monthReal = round(
                $orders
                    ->filter(fn ($order) => $this->isElectricityContract($order))
                    ->filter(function ($order) use ($effectiveStart, $effectiveEnd) {
                        $date = $this->resolveOrderDate($order);
                        return $date && $date->between($effectiveStart, $effectiveEnd);
                    })
                    ->sum(fn ($order) => $this->toFloat(data_get($order, 'consumption', 0))),
                2
            );

            $monthTarget = round(
                $objectives
                    ->filter(fn ($objective) => ($objective['type'] ?? null) === 'consumption')
                    ->sum(fn ($objective) => $this->proratedObjectiveValue($objective, $effectiveStart, $effectiveEnd)),
                2
            );

            $rows[] = [
                'period' => ucfirst($cursor->copy()->locale('es')->isoFormat('MMM YY')),
                'real'   => $monthReal,
                'target' => $monthTarget,
            ];

            $cursor->addMonth();
        }

        return $rows;
    }

    private function buildObjectivesList($objectives, $userList, Carbon $start, Carbon $end): array
    {
        return $objectives
            ->map(function ($objective) use ($userList, $start, $end) {
                $userId = (string) ($objective['userId'] ?? '');

                $user = $userId !== ''
                    ? $userList->first(fn ($row) => (string) data_get($row, '_id') === $userId)
                    : null;

                return [
                    '_id'           => (string) $objective['_id'],
                    'type'          => (string) ($objective['type'] ?? ''),
                    'startDate'     => (string) ($objective['startDate'] ?? ''),
                    'endDate'       => (string) ($objective['endDate'] ?? ''),
                    'marketer'      => (string) ($objective['marketer'] ?? ''),
                    'value'         => (float) ($objective['value'] ?? 0),
                    'proratedValue' => round($this->proratedObjectiveValue($objective, $start, $end), 2),
                    'userId'        => $userId,
                    'userName'      => $user ? trim(data_get($user, 'firstName', '') . ' ' . data_get($user, 'lastName', '')) : 'General',
                    'scopeLabel'    => $userId !== '' ? 'Por usuario' : 'General',
                ];
            })
            ->sortByDesc(function ($objective) {
                return strtotime((string) ($objective['startDate'] ?? ''));
            })
            ->values()
            ->all();
    }

    private function validateObjective(Request $request): array
    {
        $data = $request->validate([
            'type'          => 'required|in:contracts,consumption',
            'startDate'     => 'required|date',
            'endDate'       => 'required|date|after_or_equal:startDate',
            'marketer'      => 'nullable|string',
            'value'         => 'required|numeric|min:0.01',
            'userId'        => 'nullable|string',
            'userSubdomain' => 'required|string',
        ]);

        if (($data['type'] ?? null) === 'consumption') {
            $data['marketer'] = null;
        }

        if (trim((string) ($data['marketer'] ?? '')) === '') {
            $data['marketer'] = null;
        }

        if (trim((string) ($data['userId'] ?? '')) === '') {
            $data['userId'] = null;
        }

        return $data;
    }

    private function objectiveOverlapsRange($objective, Carbon $start, Carbon $end): bool
    {
        try {
            $objectiveStart = Carbon::parse($objective['startDate'])->startOfDay();
            $objectiveEnd = Carbon::parse($objective['endDate'])->endOfDay();

            return $objectiveStart->lte($end) && $objectiveEnd->gte($start);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function proratedObjectiveValue($objective, Carbon $rangeStart, Carbon $rangeEnd): float
    {
        try {
            $objectiveStart = Carbon::parse($objective['startDate'])->startOfDay();
            $objectiveEnd = Carbon::parse($objective['endDate'])->endOfDay();
        } catch (\Throwable $e) {
            return 0.0;
        }

        if ($objectiveStart->gt($rangeEnd) || $objectiveEnd->lt($rangeStart)) {
            return 0.0;
        }

        $overlapStart = $objectiveStart->copy()->lt($rangeStart)
            ? $rangeStart->copy()
            : $objectiveStart->copy();

        $overlapEnd = $objectiveEnd->copy()->gt($rangeEnd)
            ? $rangeEnd->copy()
            : $objectiveEnd->copy();

        $totalDays = max(1, $objectiveStart->diffInDays($objectiveEnd) + 1);
        $overlapDays = max(0, $overlapStart->diffInDays($overlapEnd) + 1);

        return round($this->toFloat($objective['value'] ?? 0) * ($overlapDays / $totalDays), 2);
    }

    private function resolveOrderDate($order): ?Carbon
    {
        $objectiveDate = data_get($order, 'objectiveDate');

        if ($objectiveDate instanceof UTCDateTime) {
            return Carbon::instance($objectiveDate->toDateTime())->startOfDay();
        }

        if ($objectiveDate instanceof \DateTimeInterface) {
            return Carbon::instance($objectiveDate)->startOfDay();
        }

        $candidates = [
            data_get($order, 'transferDate'),
            data_get($order, 'processingDate'),
            data_get($order, 'createdAt'),
            data_get($order, 'lastUpdate'),
        ];

        foreach ($candidates as $value) {
            if (empty($value)) {
                continue;
            }

            try {
                if ($value instanceof UTCDateTime) {
                    return Carbon::instance($value->toDateTime())->startOfDay();
                }

                if ($value instanceof \DateTimeInterface) {
                    return Carbon::instance($value)->startOfDay();
                }

                if (is_numeric($value)) {
                    return Carbon::createFromTimestamp((int) $value)->startOfDay();
                }

                $value = trim((string) $value);

                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
                }

                if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $value)) {
                    return Carbon::createFromFormat('Y-m-d H:i:s', $value)->startOfDay();
                }

                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                    return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
                }

                if (preg_match('/^\d{2}\/\d{2}\/\d{2}$/', $value)) {
                    return Carbon::createFromFormat('d/m/y', $value)->startOfDay();
                }

                return Carbon::parse($value)->startOfDay();
            } catch (\Throwable $e) {
                //
            }
        }

        return null;
    }

    private function parseDate(?string $date, bool $startOfDay = true): Carbon
    {
        if (!$date) {
            $now = now();

            return $startOfDay
                ? $now->copy()->startOfMonth()
                : $now->copy()->endOfMonth();
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $date);

                return $startOfDay
                    ? $dt->startOfDay()
                    : $dt->endOfDay();
            } catch (\Throwable $e) {
                //
            }
        }

        $dt = Carbon::parse($date);

        return $startOfDay
            ? $dt->startOfDay()
            : $dt->endOfDay();
    }

    private function isElectricityContract($order): bool
    {
        return in_array(strtolower((string) data_get($order, 'productType', '')), ['cl', 'cd'], true);
    }

    private function toFloat($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function calcPct($real, $objective): int
    {
        $objective = (float) $objective;

        if ($objective <= 0) {
            return 0;
        }

        return (int) min(100, round(((float) $real / $objective) * 100));
    }

    private function normalizeMarketerLabel($value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : 'Todas';
    }

    private function normalizeMarketerKey($value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    private function sameMarketer($a, $b): bool
    {
        return $this->normalizeMarketerKey($a) === $this->normalizeMarketerKey($b);
    }
}