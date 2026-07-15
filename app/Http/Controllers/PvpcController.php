<?php

namespace App\Http\Controllers;

use App\Http\Models\PvpcPrice;
use App\Services\EsiosPvpcService;
use Carbon\Carbon;
use App\Services\IndexedElectricityPriceService;
use Illuminate\Http\Request;
use App\Http\Models\IndexedElectricityPrice;

class PvpcController extends Controller
{
    public function downloadTest(Request $request)
    {
        try {
            $date = $request->get('date')
                ? Carbon::parse($request->get('date'), 'Europe/Madrid')
                : now('Europe/Madrid')->addDay();

            $dateString = $date->copy()
                ->timezone('Europe/Madrid')
                ->format('Y-m-d');

            $force = $request->boolean('force');

            $existing = PvpcPrice::where('date', $dateString)->count();

            if ($existing >= 24 && !$force) {
                return response()->json([
                    'success' => true,
                    'message' => 'PVPC ya estaba descargado para esta fecha.',
                    'date' => $dateString,
                    'existing' => $existing,
                ]);
            }

            try {
                $result = EsiosPvpcService::downloadAndStoreByDate($date);
                $usedDate = $dateString;
                $isFallback = false;
            } catch (\Throwable $e) {
                $fallbackDateString = $this->extractLatestAvailableDateFromMessage($e->getMessage(), $dateString);

                if (!$fallbackDateString) {
                    throw $e;
                }

                $fallbackDate = Carbon::parse($fallbackDateString, 'Europe/Madrid');

                $result = EsiosPvpcService::downloadAndStoreByDate($fallbackDate);
                $usedDate = $fallbackDate->format('Y-m-d');
                $isFallback = $usedDate !== $dateString;
            }

            return response()->json([
                'success' => true,
                'message' => $isFallback
                    ? 'PVPC descargado usando el último día disponible.'
                    : 'PVPC descargado correctamente.',
                'requested_date' => $dateString,
                'date' => $usedDate,
                'is_fallback' => $isFallback,
                'result' => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function today()
    {
        try {
            return response()->json(
                EsiosPvpcService::getStoredByDate(now('Europe/Madrid'))
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function tomorrow()
    {
        try {
            return response()->json(
                EsiosPvpcService::getStoredByDate(now('Europe/Madrid')->addDay())
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }


    public function byDate(Request $request)
    {
        try {
            $date = $request->get('date');

            if (!$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta indicar la fecha. Ejemplo: ?date=2026-06-30',
                ], 400);
            }

            $requestedDate = Carbon::parse($date, 'Europe/Madrid')->format('Y-m-d');
            $usedDate = $requestedDate;

            $existing = PvpcPrice::where('date', $usedDate)->count();

            if ($existing === 0) {
                $fallbackDate = PvpcPrice::where('date', '<=', $requestedDate)
                    ->orderBy('date', 'desc')
                    ->value('date');

                if (!$fallbackDate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay datos PVPC guardados para ' . Carbon::parse($requestedDate)->format('d/m/Y'),
                        'requested_date' => $requestedDate,
                    ], 404);
                }

                $usedDate = $fallbackDate;
            }

            $data = EsiosPvpcService::getStoredByDate($usedDate);

            $data['requested_date'] = $requestedDate;
            $data['date'] = $usedDate;
            $data['is_fallback'] = $usedDate !== $requestedDate;

            if ($data['is_fallback']) {
                $data['message'] = 'No había datos para ' . Carbon::parse($requestedDate)->format('d/m/Y') .
                    '. Se muestra el último día disponible: ' . Carbon::parse($usedDate)->format('d/m/Y');
            }

            return response()->json($data);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }


    public function downloadIndexedTest(Request $request)
    {
        try {
            $date = $request->get('date')
                ? Carbon::parse($request->get('date'), 'Europe/Madrid')
                : now('Europe/Madrid');

            $dateString = $date->copy()
                ->timezone('Europe/Madrid')
                ->format('Y-m-d');

            $tariff = $request->get('tariff', '3.0TD');

            try {
                $result = IndexedElectricityPriceService::downloadAndStoreByDate($date, $tariff);
                $usedDate = $dateString;
                $isFallback = false;
            } catch (\Throwable $e) {
                $fallbackDateString = $this->extractLatestAvailableDateFromMessage($e->getMessage(), $dateString);

                if (!$fallbackDateString) {
                    throw $e;
                }

                $fallbackDate = Carbon::parse($fallbackDateString, 'Europe/Madrid');

                $result = IndexedElectricityPriceService::downloadAndStoreByDate($fallbackDate, $tariff);
                $usedDate = $fallbackDate->format('Y-m-d');
                $isFallback = $usedDate !== $dateString;
            }

            return response()->json([
                'success' => true,
                'message' => $isFallback
                    ? 'Indexado descargado usando el último día disponible.'
                    : 'Indexado descargado correctamente.',
                'requested_date' => $dateString,
                'date' => $usedDate,
                'tariff' => $tariff,
                'is_fallback' => $isFallback,
                'result' => $result,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexedByDate(Request $request)
    {
        try {
            $date = $request->get('date');

            if (!$date) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta indicar la fecha.',
                ], 400);
            }

            $requestedDate = Carbon::parse($date, 'Europe/Madrid')->format('Y-m-d');
            $tariff = $request->get('tariff', '3.0TD');
            $usedDate = $requestedDate;

            $existing = IndexedElectricityPrice::where('date', $usedDate)
                ->where('tariff', $tariff)
                ->count();

            if ($existing === 0) {
                $fallbackDate = IndexedElectricityPrice::where('date', '<=', $requestedDate)
                    ->where('tariff', $tariff)
                    ->orderBy('date', 'desc')
                    ->value('date');

                if (!$fallbackDate) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No hay datos guardados para ' . $tariff . ' en ' . Carbon::parse($requestedDate)->format('d/m/Y'),
                        'requested_date' => $requestedDate,
                        'tariff' => $tariff,
                    ], 404);
                }

                $usedDate = $fallbackDate;
            }

            $data = IndexedElectricityPriceService::getStoredByDate($usedDate, $tariff);

            $data['requested_date'] = $requestedDate;
            $data['date'] = $usedDate;
            $data['tariff'] = $tariff;
            $data['is_fallback'] = $usedDate !== $requestedDate;

            if ($data['is_fallback']) {
                $data['message'] = 'No había datos para ' . Carbon::parse($requestedDate)->format('d/m/Y') .
                    '. Se muestra el último día disponible: ' . Carbon::parse($usedDate)->format('d/m/Y');
            }

            return response()->json($data);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function historicalWeekly(Request $request)
    {
        return $this->historicalGrouped($request, 'weekly');
    }

    public function historicalMonthly(Request $request)
    {
        return $this->historicalGrouped($request, 'monthly');
    }

    private function historicalGrouped(Request $request, string $groupBy)
    {
        try {
            $to = $request->get('to')
                ? Carbon::parse($request->get('to'), 'Europe/Madrid')
                : now('Europe/Madrid')->subDay();

            $from = $request->get('from')
                ? Carbon::parse($request->get('from'), 'Europe/Madrid')
                : $to->copy()->subMonths((int) $request->get('months', 6));

            $from = $from->copy()->startOfDay();
            $to = $to->copy()->endOfDay();

            $fromString = $from->format('Y-m-d');
            $toString = $to->format('Y-m-d');

            $tariff = strtoupper($request->get('tariff', 'PVPC'));

            if ($tariff === 'PVPC') {
                $prices = PvpcPrice::where('date', '>=', $fromString)
                    ->where('date', '<=', $toString)
                    ->orderBy('date', 'asc')
                    ->orderBy('hour', 'asc')
                    ->get();
            } else {
                $prices = IndexedElectricityPrice::where('tariff', $tariff)
                    ->where('date', '>=', $fromString)
                    ->where('date', '<=', $toString)
                    ->orderBy('date', 'asc')
                    ->orderBy('hour', 'asc')
                    ->get();
            }

            $groups = [];
            $allValues = [];
            $globalMin = null;
            $globalMax = null;

            foreach ($prices as $price) {
                $value = $this->getHistoricalPvpcValue($price);

                if ($value === null) {
                    continue;
                }

                $date = Carbon::parse($price->date, 'Europe/Madrid');
                $hour = (int) ($price->hour ?? 0);

                if ($groupBy === 'weekly') {
                    $start = $date->copy()->startOfWeek(Carbon::MONDAY);
                    $end = $date->copy()->endOfWeek(Carbon::SUNDAY);
                    $key = $start->format('Y-m-d');

                    $label = 'Semana ' . str_pad($start->isoWeek(), 2, '0', STR_PAD_LEFT)
                        . ' · ' . $start->format('d/m')
                        . ' - ' . $end->format('d/m');

                    $shortLabel = 'S' . str_pad($start->isoWeek(), 2, '0', STR_PAD_LEFT);
                } else {
                    $start = $date->copy()->startOfMonth();
                    $end = $date->copy()->endOfMonth();
                    $key = $start->format('Y-m');

                    $label = $this->getMonthLabel($start);
                    $shortLabel = $this->getShortMonthLabel($start);
                }

                if (!isset($groups[$key])) {
                    $groups[$key] = [
                        'key' => $key,
                        'label' => $label,
                        'short_label' => $shortLabel,
                        'start_date' => $start->format('Y-m-d'),
                        'end_date' => $end->format('Y-m-d'),
                        'values' => [],
                        'min' => null,
                        'max' => null,
                    ];
                }

                $detail = [
                    'date' => $date->format('Y-m-d'),
                    'hour' => $hour,
                    'hour_label' => sprintf('%02d:00', $hour),
                    'value' => round($value, 4),
                ];

                $groups[$key]['values'][] = $value;
                $allValues[] = $value;

                if ($groups[$key]['min'] === null || $value < $groups[$key]['min']['value']) {
                    $groups[$key]['min'] = $detail;
                }

                if ($groups[$key]['max'] === null || $value > $groups[$key]['max']['value']) {
                    $groups[$key]['max'] = $detail;
                }

                if ($globalMin === null || $value < $globalMin['value']) {
                    $globalMin = $detail;
                }

                if ($globalMax === null || $value > $globalMax['value']) {
                    $globalMax = $detail;
                }
            }

            ksort($groups);

            $periods = [];

            foreach ($groups as $group) {
                $count = count($group['values']);

                if ($count === 0) {
                    continue;
                }

                $average = array_sum($group['values']) / $count;

                $periods[] = [
                    'key' => $group['key'],
                    'label' => $group['label'],
                    'short_label' => $group['short_label'],
                    'start_date' => max($group['start_date'], $fromString),
                    'end_date' => min($group['end_date'], $toString),
                    'average_c_kwh' => round($average, 2),
                    'min_c_kwh' => round($group['min']['value'], 2),
                    'max_c_kwh' => round($group['max']['value'], 2),
                    'min' => $group['min'],
                    'max' => $group['max'],
                    'records' => $count,
                ];
            }

            return response()->json([
                'success' => true,
                'type' => $groupBy,
                'tariff' => $tariff,
                'from' => $fromString,
                'to' => $toString,
                'summary' => [
                    'average_c_kwh' => count($allValues) > 0
                        ? round(array_sum($allValues) / count($allValues), 2)
                        : 0,
                    'min' => $globalMin,
                    'max' => $globalMax,
                    'records' => count($allValues),
                    'periods' => count($periods),
                ],
                'periods' => $periods,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function getHistoricalPvpcValue($price): ?float
    {
        $value = $price->total_c_kwh ?? $price->price_c_kwh ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function getMonthLabel(Carbon $date): string
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    private function getShortMonthLabel(Carbon $date): string
    {
        $months = [
            1 => 'Ene',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Abr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Ago',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dic',
        ];

        return $months[(int) $date->format('n')];
    }

    public function historicalDaily(Request $request)
    {
        try {
            $tariff = $request->get('tariff', 'PVPC');

            $from = Carbon::parse(
                $request->get('from', now('Europe/Madrid')->startOfMonth()->format('Y-m-d')),
                'Europe/Madrid'
            )->format('Y-m-d');

            $to = Carbon::parse(
                $request->get('to', now('Europe/Madrid')->format('Y-m-d')),
                'Europe/Madrid'
            )->format('Y-m-d');

            if ($tariff === 'PVPC') {
                $records = PvpcPrice::where('date', '>=', $from)
                    ->where('date', '<=', $to)
                    ->orderBy('date', 'asc')
                    ->orderBy('hour', 'asc')
                    ->get();
            } else {
                $records = IndexedElectricityPrice::where('date', '>=', $from)
                    ->where('date', '<=', $to)
                    ->where('tariff', $tariff)
                    ->orderBy('date', 'asc')
                    ->orderBy('hour', 'asc')
                    ->get();
            }

            $grouped = $records->groupBy('date');

            $days = $grouped->map(function ($items, $date) {
                $average = $items->avg(function ($item) {
                    return (float) ($item->total_c_kwh ?? $item->price_c_kwh ?? 0);
                });

                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d/m'),
                    'average_c_kwh' => round($average, 4),
                    'hours_count' => $items->count(),
                ];
            })->values();

            return response()->json([
                'tariff' => $tariff,
                'from' => $from,
                'to' => $to,
                'title' => 'Media diaria ' . $tariff,
                'average_c_kwh' => round($days->avg('average_c_kwh'), 4),
                'min' => $days->sortBy('average_c_kwh')->first(),
                'max' => $days->sortByDesc('average_c_kwh')->first(),
                'days' => $days,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function historicalHeatmap(Request $request)
    {
        try {
            $tariff = strtoupper($request->get('tariff', 'PVPC'));

            $from = Carbon::parse(
                $request->get('from', now('Europe/Madrid')->startOfMonth()->format('Y-m-d')),
                'Europe/Madrid'
            )->format('Y-m-d');

            $to = Carbon::parse(
                $request->get('to', now('Europe/Madrid')->format('Y-m-d')),
                'Europe/Madrid'
            )->format('Y-m-d');

            if ($tariff === 'PVPC') {
                $records = PvpcPrice::where('date', '>=', $from)
                    ->where('date', '<=', $to)
                    ->orderBy('date', 'asc')
                    ->orderBy('hour', 'asc')
                    ->get();
            } else {
                $records = IndexedElectricityPrice::where('date', '>=', $from)
                    ->where('date', '<=', $to)
                    ->where('tariff', $tariff)
                    ->orderBy('date', 'asc')
                    ->orderBy('hour', 'asc')
                    ->get();
            }

            $hours = [];

            for ($hour = 0; $hour <= 23; $hour++) {
                $hours[] = sprintf('%02d:00', $hour);
            }

            $recordMap = [];
            $values = [];
            $globalMin = null;
            $globalMax = null;

            foreach ($records as $record) {
                $value = $this->getHistoricalPvpcValue($record);

                if ($value === null) {
                    continue;
                }

                $hourNumber = $this->normalizeHeatmapHour($record->hour ?? 0);
                $hourLabel = sprintf('%02d:00', $hourNumber);
                $dateString = Carbon::parse($record->date, 'Europe/Madrid')->format('Y-m-d');
                $key = $dateString . '_' . $hourLabel;

                $cell = [
                    'date' => $dateString,
                    'day_label' => $this->getHeatmapDayLabel($dateString),
                    'hour' => $hourLabel,
                    'value' => round($value, 4),
                    'period' => $record->period ?? null,
                ];

                $recordMap[$key] = $cell;
                $values[] = $value;

                if ($globalMin === null || $value < $globalMin['value']) {
                    $globalMin = $cell;
                }

                if ($globalMax === null || $value > $globalMax['value']) {
                    $globalMax = $cell;
                }
            }

            $rows = [];
            $days = [];
            $cursor = Carbon::parse($from, 'Europe/Madrid')->startOfDay();
            $end = Carbon::parse($to, 'Europe/Madrid')->startOfDay();

            while ($cursor->lte($end)) {
                $dateString = $cursor->format('Y-m-d');
                $dayLabel = $this->getHeatmapDayLabel($dateString);

                $days[] = [
                    'date' => $dateString,
                    'label' => $dayLabel,
                ];

                $cells = [];

                foreach ($hours as $hourLabel) {
                    $key = $dateString . '_' . $hourLabel;

                    $cells[] = $recordMap[$key] ?? [
                        'date' => $dateString,
                        'day_label' => $dayLabel,
                        'hour' => $hourLabel,
                        'value' => null,
                        'period' => null,
                    ];
                }

                $rows[] = [
                    'date' => $dateString,
                    'label' => $dayLabel,
                    'cells' => $cells,
                ];

                $cursor->addDay();
            }

            return response()->json([
                'success' => true,
                'tariff' => $tariff,
                'from' => $from,
                'to' => $to,
                'title' => 'Mapa de calor de precios',
                'subtitle' => 'Precio horario por día · ' . Carbon::parse($from)->format('d/m/Y') . ' - ' . Carbon::parse($to)->format('d/m/Y'),
                'average_c_kwh' => count($values) > 0
                    ? round(array_sum($values) / count($values), 4)
                    : 0,
                'min' => $globalMin,
                'max' => $globalMax,
                'min_value' => $globalMin['value'] ?? 0,
                'max_value' => $globalMax['value'] ?? 0,
                'hours' => $hours,
                'days' => $days,
                'rows' => $rows,
                'records' => count($values),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function normalizeHeatmapHour($hour): int
    {
        if (is_string($hour) && str_contains($hour, ':')) {
            return (int) substr($hour, 0, 2);
        }

        return (int) $hour;
    }

    private function getHeatmapDayLabel(string $date): string
    {
        $carbon = Carbon::parse($date, 'Europe/Madrid');

        return $carbon->format('d') . ' ' . $this->getShortMonthLabel($carbon);
    }

    private function extractLatestAvailableDateFromMessage(string $message, ?string $requestedDate = null): ?string
    {
        preg_match_all('/\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}-\d{2}/', $message, $matches);

        $dates = $matches[0] ?? [];

        if (empty($dates)) {
            return null;
        }

        $normalizedDates = [];

        foreach ($dates as $date) {
            try {
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                    $normalizedDate = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
                } else {
                    $normalizedDate = Carbon::parse($date)->format('Y-m-d');
                }

                if ($requestedDate && $normalizedDate === $requestedDate) {
                    continue;
                }

                $normalizedDates[] = $normalizedDate;

            } catch (\Throwable $e) {
                continue;
            }
        }

        if (empty($normalizedDates)) {
            return null;
        }

        rsort($normalizedDates);

        return $normalizedDates[0];
    }
}