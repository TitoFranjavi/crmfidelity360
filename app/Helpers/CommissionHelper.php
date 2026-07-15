<?php

namespace App\Helpers;

class CommissionHelper
{

    /**
     * Calcula la comisión completa incluyendo la jerarquía de usuarios.
     */
    public static function calculateCommission(array $params): array
    {
        $userListTop = $params['userListTop'];
        $assignedToZoco = $params['assignedToZoco'];
        $marketer = $params['marketer'];
        $commissionRanges = $params['commissionRanges'];
        $commissionRangesZoco = $params['commissionRangesZoco'] ?? null;
        $commissions = $params['commissions'] ?? null;
        $commissionType = $params['commissionType'] ?? null;
        $energyData = $params['energyData'] ?? null;
        $powerData = $params['powerData'] ?? null;
        $fees = $params['fees'] ?? null;
        $baseCommission = $params['baseCommission'] ?? null;
        $manualCommissions = $params['manualCommissions'] ?? false;

        // Obtenemos la jerarquía
        $userHierarchy = self::resolveUserHierarchy($userListTop, $assignedToZoco);

        $baseCommissionCtx = [
            'commissions' => $commissions,
            'commissionType' => $commissionType,
            'energyData' => $energyData,
            'powerData' => $powerData,
            'fees' => $fees,
        ];

        // Calculamos la comisión del subdominio
        if ($baseCommission === null) {
            $baseCommission = self::calculateBaseCommission($baseCommissionCtx);
        }

        // Construimos la jerarquía de usuarios con su tipo de comisión asignada
        $hierarchy = array_map(function ($user) use ($marketer, $commissionRanges, $commissionRangesZoco, $manualCommissions, $baseCommissionCtx) {
            $config = self::getUserCommissionConfig([
                'user' => $user,
                'marketer' => $marketer,
                'commissionRanges' => $commissionRanges,
                'commissionRangesZoco' => $commissionRangesZoco,
                'manualCommissions' => $manualCommissions,
                'baseCommissionCtx' => $baseCommissionCtx,
            ]);

            if ($config === null) {
                return ['id' => $user['_id'], 'type' => 'fixed', 'value' => 0];
            }

            return ['id' => $user['_id'], 'type' => $config['type'], 'value' => $config['value']];
        }, $userHierarchy);

        // Calculamos las comisiones para la jerarquía
        return self::calculateHierarchyBreakdown([
            'hierarchy' => $hierarchy,
            'baseCommission' => $baseCommission,
        ]);
    }

    /**
     * Obtener jerarquía vacía.
     */
    public static function buildEmptyBreakdown(array $userListTop, bool $assignedToZoco): array
    {
        $userHierarchy = self::resolveUserHierarchy($userListTop, $assignedToZoco);

        return array_values(array_map(function ($user, $index) {
            return [
                'userId' => $user['_id'],
                'level' => $index,
                'commission' => null,
            ];
        }, $userHierarchy, array_keys($userHierarchy)));
    }

    /**
     * Obtener jerarquía de usuarios.
     */
    private static function resolveUserHierarchy(array $userListTop, bool $assignedToZoco): array
    {
        $EXCLUDED_IDS = ['65fd4c2f05efc4aa4a050dc2', '698340c75525f31823005652'];

        $subdomainIndex = -1;
        foreach ($userListTop as $i => $u) {
            if (($u['label'] ?? '') === 'Usuario subdominio') {
                $subdomainIndex = $i;
                break;
            }
        }

        $cutIndex = $assignedToZoco ? $subdomainIndex + 1 : $subdomainIndex;
        $trimmed = $subdomainIndex !== -1 ? array_slice($userListTop, 0, $cutIndex) : $userListTop;

        $filtered = array_values(array_filter($trimmed, fn($u) => !in_array($u['_id'], $EXCLUDED_IDS)));

        return array_reverse($filtered);
    }

    /**
     * Obtener la comisión asignada a un usuario.
     */
    private static function getUserCommissionConfig(array $params): ?array
    {
        $user = $params['user'];
        $marketer = $params['marketer'];
        $commissionRanges = $params['commissionRanges'];
        $commissionRangesZoco = $params['commissionRangesZoco'];
        $manualCommissions = $params['manualCommissions'] ?? false;
        $baseCommissionCtx = $params['baseCommissionCtx'] ?? null;

        $userCommission = $user['commissions'][$marketer] ?? null;

        // Si no tiene comisión no devuelvo nada
        if ($userCommission === null) {
            return null;
        }

        $value = self::toNumber($userCommission['value']);

        // Cambiar a rangos de Zoco si el usuario es subdominio
        $ranges = ($user['label'] ?? '') === 'Usuario subdominio'
            ? $commissionRangesZoco
            : $commissionRanges;

        // Caso manualCommissions: el subdominio usa las comisiones guardadas en cada producto
        if ($userCommission['type'] === 'range' && $manualCommissions) {
            $commission = 0;
            if ($value !== null && $baseCommissionCtx !== null) {
                $commission = self::calculateBaseCommission(
                    array_merge($baseCommissionCtx, ['commissionRange' => $value])
                );
            }
            return ['type' => 'manual', 'value' => $commission];
        }

        switch ($userCommission['type']) {
            case 'fixed':
                return ['type' => 'fixed', 'value' => self::toNumber($value)];

            case 'percentage':
                return ['type' => 'percentage', 'value' => self::toNumber($value)];

            case 'range':
                $range = null;
                if (is_array($ranges)) {
                    foreach ($ranges as $r) {
                        if (($r['id'] ?? null) === $value) {
                            $range = $r;
                            break;
                        }
                    }
                }
                $percentage = isset($range['percentage']) ? self::toNumber($range['percentage']) : 0;
                return ['type' => 'range', 'value' => $percentage];

            default:
                return null;
        }
    }

    /**
     * Calcular la comisión base del subdominio.
     */
    private static function calculateBaseCommission(array $params): float
    {
        $commissions = $params['commissions'];
        $commissionType = $params['commissionType'];
        $energyData = $params['energyData'];
        $powerData = $params['powerData'];
        $fees = $params['fees'];
        $commissionRange = $params['commissionRange'] ?? null;

        $consumptionByPeriods = $energyData['byPeriods'] ?? [];
        $totalConsumption = isset($energyData['annual'])
            ? self::toNumber($energyData['annual'])
            : array_reduce($consumptionByPeriods, fn($acc, $v) => $acc + self::toNumber($v), 0);


        $powerByPeriods = $powerData['byPeriods'] ?? [];
        $powerByPeriodsNumbers = array_map(fn($v) => self::toNumber($v), $powerByPeriods);
        $maxPower = isset($powerData['max'])
            ? self::toNumber($powerData['max'])
            : (count($powerByPeriodsNumbers) > 0 ? max($powerByPeriodsNumbers) : 0);

        $base = 0;
        $interval = null;

        switch ($commissionType) {
            case 'f':
                $base = $commissionRange !== null
                    ? self::toNumber($commissions[0]['breakdown'][$commissionRange] ?? 0)
                    : self::toNumber($commissions[0]['base'] ?? 0);
                break;

            case 'c':
                foreach ($commissions as $i) {
                    if (
                        self::toNumber($i['con1']) <= $totalConsumption &&
                        (self::toNumber($i['con2']) >= $totalConsumption || $i['con2'] === '>')
                    ) {
                        $interval = $i;
                        break;
                    }
                }
                $base = $commissionRange !== null
                    ? self::toNumber($interval['breakdown'][$commissionRange] ?? 0)
                    : self::toNumber($interval['base'] ?? 0);
                break;

            case 'p':
                foreach ($commissions as $i) {
                    if (
                        self::toNumber($i['pot1']) <= $maxPower &&
                        (self::toNumber($i['pot2']) >= $maxPower || $i['pot2'] === '>')
                    ) {
                        $interval = $i;
                        break;
                    }
                }
                $base = $commissionRange !== null
                    ? self::toNumber($interval['breakdown'][$commissionRange] ?? 0)
                    : self::toNumber($interval['base'] ?? 0);
                break;

            case 'cyp':
                foreach ($commissions as $i) {
                    if (
                        self::toNumber($i['pot1']) <= $maxPower &&
                        (self::toNumber($i['pot2']) >= $maxPower || $i['pot2'] === '>') &&
                        self::toNumber($i['con1']) <= $totalConsumption &&
                        (self::toNumber($i['con2']) >= $totalConsumption || $i['con2'] === '>')
                    ) {
                        $interval = $i;
                        break;
                    }
                }
                $base = $commissionRange !== null
                    ? self::toNumber($interval['breakdown'][$commissionRange] ?? 0)
                    : self::toNumber($interval['base'] ?? 0);
                break;

            case 'fp':
                $powerCommission = 0;
                foreach ($powerByPeriods as $index => $value) {
                    $powerCommission += self::toNumber($value) * self::toNumber($fees['power'][$index]) / 30 * 365;
                }

                if (isset($energyData['annual'])) {
                    $energyCommission = self::toNumber($energyData['annual']) * self::toNumber($fees['energy'][0]) / 1000;
                } else {
                    $energyCommission = 0;
                    foreach ($consumptionByPeriods as $index => $value) {
                        $energyCommission += self::toNumber($value) * self::toNumber($fees['energy'][$index]) / 1000;
                    }
                }

                $percentagePower = self::toNumber(
                        $commissionRange !== null
                            ? ($commissions[0]['breakdown'][$commissionRange] ?? 0)
                            : ($commissions[0]['base'] ?? 0)
                    ) / 100;

                $percentageEnergy = self::toNumber(
                        $commissionRange !== null
                            ? ($commissions[1]['breakdown'][$commissionRange] ?? 0)
                            : ($commissions[1]['base'] ?? 0)
                    ) / 100;

                $base = $powerCommission * $percentagePower + $energyCommission * $percentageEnergy;
                break;
        }

        // Multiplicar por consumo
        if (!empty($interval['multiply'])) {
            $base = ($base * $totalConsumption) / 1000;
        }

        return $base;
    }

    /**
     * Calcular comisiones jerarquizadas.
     */
    private static function calculateHierarchyBreakdown(array $params): array
    {
        $hierarchy = $params['hierarchy'];
        $baseCommission = $params['baseCommission'];

        $currentValue = $baseCommission;
        $breakdown = [];

        foreach ($hierarchy as $index => $level) {
            if ($level['type'] === 'fixed' || $level['type'] === 'manual') {
                $currentValue = $level['value'];
            } else {
                $currentValue = $currentValue * ($level['value'] / 100);
            }

            $breakdown[] = [
                'userId' => $level['id'],
                'level' => $index,
                'commission' => self::roundCommission($currentValue),
            ];
        }

        return [
            'subdomain' => self::roundCommission($baseCommission),
            'breakdown' => $breakdown,
        ];
    }

    // ─── Funciones auxiliares ────────────────────────────────────────────────────

    private static function toNumber(mixed $value): float
    {
        if ($value === null) return 0.0;

        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') return 0.0;
            return (float)str_replace(',', '.', $trimmed) ?: 0.0;
        }

        return 0.0;
    }

    private static function roundCommission(mixed $num): float
    {
        $n = (float)$num;
        return is_nan($n) ? 0.0 : round($n, 2);
    }
}
