<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Http\Models\Signin;
use Illuminate\Http\Request;
use App\Exports\SigningsExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SigningController extends Controller
{

    public function index()
    {
        return Signin::all();
    }

    public function show($id)
    {
        return Signin::find($id);
    }

    public function showAllByUser($userId, $perPage = 10)
    {
        return Signin::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->orderBy('entry', 'asc')
            ->paginate($perPage);
    }

    public function showByUserAndDate($userId, $date1, $date2, $perPage = 10)
    {
        //Ordenar por fecha y si es la misma fecha, por entry ascendente
        return Signin::where('user_id', $userId)
            ->whereBetween('date', [$date1, $date2])
            ->orderBy('date', 'desc')
            ->orderBy('entry', 'asc')
            ->paginate($perPage);
    }

    // Obtener fichajes paginados
    public function getPaginatedSignins(Request $request)
    {
        /*// Parámetros opcionales
        $perPage = $request->input('per_page', 10); // Número de fichajes por página
        $userId = $request->input('user_id');       // Para filtrar por usuario
        $startDate = $request->input('start_date'); // Filtro desde
        $endDate = $request->input('end_date');     // Filtro hasta
        $page = $request->input('page', 1);         // Página actual

        $query = Signin::query();

        // 🔹 Filtro por usuario
        if ($userId) {
            $query->where('user_id', $userId)->limit($perPage);
        }

        // 🔹 Filtro por rango de fechas
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate) {
            $query->where('date', '>=', $startDate);
        } elseif ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        // 🔹 Ordenar por fecha descendente
        $query->orderBy('date', 'desc');

        return response()->json($query->get());*/
    }



   public static function getLastStatus($userId)
{
    $today = now()->format('Y-m-d');

    $ultimo = Signin::where('user_id', $userId)
        ->orderBy('date', 'desc')
        ->orderBy('_id', 'desc')
        ->first();

    if (!$ultimo) {
        return response()->json([
            'should_sign_in' => true,
            'type' => 'no_previous_signings',
            'message' => 'No hay fichajes previos',
            'last_signin' => null,
        ]);
    }

    /*
     * Si existe una entrada abierta de otro día,
     * la cerramos automáticamente a final de ese mismo día.
     */
    if (is_null($ultimo->exit) && $ultimo->date !== $today) {
        $autoNote = 'Salida cerrada automáticamente porque el usuario no registró salida el mismo día.';

        $ultimo->exit = '23:59';
        $ultimo->exit_location = null;

        $ultimo->auto_closed = true;
        $ultimo->auto_closed_reason = 'missing_exit';
        $ultimo->auto_closed_at = now()->toISOString();
        $ultimo->auto_closed_exit = '23:59';

        $ultimo->notes = trim(
            ($ultimo->notes ? $ultimo->notes . "\n" : '') . $autoNote
        );

        $ultimo->save();

        return response()->json([
            'should_sign_in' => true,
            'type' => 'auto_closed_previous_day',
            'message' => 'Había un fichaje anterior sin salida. Se ha cerrado automáticamente a las 23:59.',
            'last_signin' => [
                'date' => $ultimo->date,
                'entry' => $ultimo->entry,
                'exit' => $ultimo->exit,
            ],
        ]);
    }

    $shouldSignIn = !is_null($ultimo->exit);

    return response()->json([
        'should_sign_in' => $shouldSignIn,
        'type' => $shouldSignIn ? 'ready_for_entry' : 'ready_for_exit',
        'last_signin' => [
            'date' => $ultimo->date,
            'entry' => $ultimo->entry,
            'exit' => $ultimo->exit,
        ],
    ]);
}

    public static function createSigning($userId, $date, $entry, $exit, $activity_sections = [], $notes = '')
    {
        if (empty($activity_sections)) {
            $signin = Signin::create([
                'user_id' => $userId,
                'date' => $date,
                'entry' => $entry,
                'exit' => $exit,
                'activity_sections' => [],
                'notes' => $notes,
            ]);

            return response()->json([
                'message' => 'Fichaje creado correctamente',
                'data' => [$signin]
            ], 201);
        }

        // 🧩 Paso 1: Ordenar los tramos por hora de inicio
        usort($activity_sections, function ($a, $b) {
            [$startA] = explode('-', explode(',', $a)[0]);
            [$startB] = explode('-', explode(',', $b)[0]);
            return strcmp($startA, $startB);
        });

        // 🧩 Paso 2: Agrupar tramos consecutivos
        $grupos = [];
        $grupoActual = [];
        $ultimoFin = null;

        foreach ($activity_sections as $section) {
            [$rango, $desc] = explode(',', $section);
            [$start, $end] = explode('-', $rango);

            if ($ultimoFin === null || $start === $ultimoFin) {
                // Continuación del grupo actual
                $grupoActual[] = $section;
            } else {
                // Nuevo grupo (hay salto horario)
                $grupos[] = $grupoActual;
                $grupoActual = [$section];
            }

            $ultimoFin = $end;
        }

        // Agregar el último grupo si existe
        if (!empty($grupoActual)) {
            $grupos[] = $grupoActual;
        }

        // 🧩 Paso 3: Crear un fichaje por cada grupo
        $signins = [];

        foreach ($grupos as $grupo) {
            // Primer tramo del grupo → hora de entrada
            [$firstRange] = explode(',', $grupo[0]);
            [$entryStart] = explode('-', $firstRange);

            // Último tramo del grupo → hora de salida
            [$lastRange] = explode(',', $grupo[count($grupo) - 1]);
            [, $exitEnd] = explode('-', $lastRange);

            $signin = Signin::create([
                'user_id' => $userId,
                'date' => $date,
                'entry' => trim($entryStart),
                'exit' => trim($exitEnd),
                'activity_sections' => $grupo, // solo los de este grupo
                'notes' => $notes,
            ]);

            $signins[] = $signin;
        }

        return response()->json([
            'message' => count($signins) > 1
                ? 'Fichajes creados (por salto horario detectado)'
                : 'Fichaje creado correctamente',
            'data' => $signins,
        ], 201);
    }



    public static function attachWorkOrder($signinId, $work_order_file = null)
    {
        $file = $work_order_file;
        if (!$file) {
            $file = request()->file('work_order_file');
        }

        $signin = Signin::where('_id', $signinId)->first();

        if (!$signin) {
            return response()->json(['message' => 'Fichaje no encontrado'], 404);
        }

        $workOrderId = uniqid();

        $original = $file->getClientOriginalName();
        $base = pathinfo($original, PATHINFO_FILENAME);
        $ext = pathinfo($original, PATHINFO_EXTENSION);

        $storeName = time() . '_' . $workOrderId . '_' . $base . '.' . $ext;

        if (!Storage::disk('work_order')->exists($storeName)) {
            Storage::disk('work_order')->put($storeName, file_get_contents($file));
        }

        $signin->work_order_id = $workOrderId;
        $signin->work_order_file = $storeName;
        $signin->save();

        return response()->json([
            'message' => 'Orden de trabajo adjuntada correctamente al fichaje',
            'data' => $signin
        ], 200);
    }

    public function generateReportPdf(Request $request)
    {
        // 📅 Recoger filtros
        $employeeIds = $request->input('employee_ids', []);
        $startDate   = $request->input('start_date');
        $endDate     = $request->input('end_date');

        // 🔹 Validar empleados
        if (empty($employeeIds)) {
            return response('No se seleccionaron empleados.', 400);
        }

        // 🔹 Si no hay fecha de inicio, error
        if (!$startDate) {
            return response('Debes especificar una fecha de inicio.', 400);
        }

        // 🔹 Si no hay fecha de fin, se usa hoy
        if (!$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // 🧾 Consultar fichajes
        $signins = Signin::with('user') // asumiendo relación user() en el modelo
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        // 📆 Datos auxiliares
        $currentDate = Carbon::now()->format('d/m/Y');

        // 🧩 Crear PDF con Barryvdh/Dompdf
        $pdf = Pdf::loadView('PDFs.signin', [
            'signins' => $signins,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currentDate' => $currentDate,
        ])
            ->setPaper('A4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);

        // 📤 Mostrar PDF directamente en navegador
        return $pdf->stream("informe_fichajes_{$startDate}_{$endDate}.pdf");
    }

    public function generateReportExcel(Request $request)
    {
        // 📅 Recoger filtros
        $employeeIds = $request->input('employee_ids', []);
        $startDate   = $request->input('start_date');
        $endDate     = $request->input('end_date');

        // 🔹 Validar empleados
        if (empty($employeeIds)) {
            return response('No se seleccionaron empleados.', 400);
        }

        // 🔹 Si no hay fecha de inicio, error
        if (!$startDate) {
            return response('Debes especificar una fecha de inicio.', 400);
        }

        // 🔹 Si no hay fecha de fin, se usa hoy
        if (!$endDate) {
            $endDate = Carbon::now()->format('Y-m-d');
        }

        // 🧾 Consultar fichajes
        $signins = Signin::with('user') // asumiendo relación user() en el modelo
            ->whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return Excel::download(new SigningsExport($startDate, $endDate, $employeeIds), "informe_fichajes_{$startDate}_{$endDate}.xlsx");
    }

    public static function saveSigningsFromWhatsapp($userId, $location = null)
    {
        try {
            $now = now();
            $date = $now->format('Y-m-d');
            $time = $now->format('H:i');

            $locationData = null;

            /*
            * Ubicación desde WhatsApp.
            * Preferimos array:
            * [
            *   'lat' => ...,
            *   'lng' => ...,
            *   'address' => ...
            * ]
            *
            * Pero dejamos compatibilidad con string "lat, lng".
            */
            if (is_array($location)) {
                $lat = $location['lat'] ?? $location['latitude'] ?? null;
                $lng = $location['lng'] ?? $location['longitude'] ?? null;

                if (!is_null($lat) && !is_null($lng)) {
                    $locationData = [
                        'lat' => (float) $lat,
                        'lng' => (float) $lng,
                        'accuracy' => isset($location['accuracy']) ? (float) $location['accuracy'] : null,
                        'altitude' => isset($location['altitude']) ? (float) $location['altitude'] : null,
                        'altitude_accuracy' => isset($location['altitude_accuracy']) ? (float) $location['altitude_accuracy'] : null,
                        'heading' => isset($location['heading']) ? (float) $location['heading'] : null,
                        'speed' => isset($location['speed']) ? (float) $location['speed'] : null,
                        'source' => $location['source'] ?? 'whatsapp',
                        'captured_at' => $location['captured_at'] ?? now()->toISOString(),
                        'address' => $location['address'] ?? null,
                    ];
                }
            } elseif (is_string($location)) {
                preg_match_all('/-?\d+(?:\.\d+)?/', $location, $matches);

                if (count($matches[0]) >= 2) {
                    $locationData = [
                        'lat' => (float) $matches[0][0],
                        'lng' => (float) $matches[0][1],
                        'accuracy' => null,
                        'altitude' => null,
                        'altitude_accuracy' => null,
                        'heading' => null,
                        'speed' => null,
                        'source' => 'whatsapp',
                        'captured_at' => now()->toISOString(),
                        'address' => null,
                    ];
                }
            }

            $ultimo = Signin::where('user_id', $userId)
                ->orderBy('date', 'desc')
                ->orderBy('_id', 'desc')
                ->first();

            // Si no hay fichajes previos, registramos ENTRADA
            if (!$ultimo) {
                $signin = Signin::create([
                    'user_id' => $userId,
                    'date' => $date,
                    'entry' => $time,
                    'entry_location' => $locationData,
                ]);

                return response()->json([
                    'message' => 'Fichaje de entrada registrado correctamente.',
                    'type' => 'entry',
                    'data' => $signin,
                ], 201);
            }

            // Si el último fichaje ya tiene salida, registramos nueva ENTRADA
            if (!is_null($ultimo->exit)) {
                $signin = Signin::create([
                    'user_id' => $userId,
                    'date' => $date,
                    'entry' => $time,
                    'entry_location' => $locationData,
                ]);

                return response()->json([
                    'message' => 'Nueva entrada registrada correctamente.',
                    'type' => 'entry',
                    'data' => $signin,
                ], 201);
            }

            /*
            * Si hay una entrada abierta de otro día, la cerramos automáticamente,
            * igual que en la web, pero NO creamos entrada nueva automáticamente.
            */
            if ($ultimo->date !== $date) {
                $autoNote = 'Salida cerrada automáticamente porque el usuario no registró salida el mismo día.';

                $ultimo->exit = '23:59';
                $ultimo->exit_location = null;

                $ultimo->auto_closed = true;
                $ultimo->auto_closed_reason = 'missing_exit';
                $ultimo->auto_closed_at = now()->toISOString();
                $ultimo->auto_closed_exit = '23:59';

                $ultimo->notes = trim(
                    ($ultimo->notes ? $ultimo->notes . "\n" : '') . $autoNote
                );

                $ultimo->save();

                return response()->json([
                    'message' => 'Había un fichaje anterior sin salida. Se ha cerrado automáticamente. Vuelve a fichar para registrar una nueva entrada.',
                    'type' => 'auto_closed_previous_day',
                    'data' => $ultimo,
                ], 200);
            }

            // Si el último fichaje no tiene salida y es del mismo día, registramos SALIDA
            $ultimo->exit = $time;
            $ultimo->exit_location = $locationData;
            $ultimo->save();

            return response()->json([
                'message' => 'Fichaje de salida registrado correctamente.',
                'type' => 'exit',
                'data' => $ultimo,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar el fichaje desde WhatsApp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public static function saveSignings(Request $request)
    {
        try {
            $user = auth()->user();
            $userId = $user->getAuthIdentifier();

            $now = now();
            $date = $now->format('Y-m-d');
            $time = $now->format('H:i');

            // 🔹 Estructuramos la ubicación (del request o nula)
            $location = $request->input('location');
            $locationError = $request->input('location_error');

            $locationData = null;

            if (is_array($location)) {
                $lat = $location['lat'] ?? $location['latitude'] ?? null;
                $lng = $location['lng'] ?? $location['longitude'] ?? null;

                if (!is_null($lat) && !is_null($lng)) {
                    $locationData = [
                        'lat' => (float) $lat,
                        'lng' => (float) $lng,
                        'accuracy' => isset($location['accuracy']) ? (float) $location['accuracy'] : null,
                        'altitude' => isset($location['altitude']) ? (float) $location['altitude'] : null,
                        'altitude_accuracy' => isset($location['altitude_accuracy']) ? (float) $location['altitude_accuracy'] : null,
                        'heading' => isset($location['heading']) ? (float) $location['heading'] : null,
                        'speed' => isset($location['speed']) ? (float) $location['speed'] : null,
                        'source' => $location['source'] ?? 'browser',
                        'captured_at' => $location['captured_at'] ?? null,
                        'address' => $location['address'] ?? null,
                    ];
                }
            }

            // 🟢 Obtenemos el último fichaje del usuario
            $ultimo = Signin::where('user_id', $userId)
                ->orderBy('date', 'desc')
                ->orderBy('_id', 'desc')
                ->first();

            // 📥 Si no hay fichajes previos → registrar ENTRADA
            if (!$ultimo) {
                $signin = Signin::create([
                    'user_id' => $userId,
                    'date' => $date,
                    'entry' => $time,
                    'entry_location' => $locationData,
                ]);

                return response()->json([
                    'message' => 'Fichaje de entrada registrado correctamente.',
                    'type' => 'entry',
                    'data' => $signin
                ], 201);
            }

            // 🔁 Si el último fichaje ya tiene salida → nueva ENTRADA
            if (!is_null($ultimo->exit)) {
                $signin = Signin::create([
                    'user_id' => $userId,
                    'date' => $date,
                    'entry' => $time,
                    'entry_location' => $locationData,
                ]);

                return response()->json([
                    'message' => 'Nueva entrada registrada correctamente.',
                    'type' => 'entry',
                    'data' => $signin
                ], 201);
            }

            // Si hay una entrada abierta de otro día, la cerramos,
            // pero NO creamos una entrada nueva automáticamente.
            if ($ultimo->date !== $date) {
                $autoNote = 'Salida cerrada automáticamente porque el usuario no registró salida el mismo día.';

                $ultimo->exit = '23:59';
                $ultimo->exit_location = null;

                $ultimo->auto_closed = true;
                $ultimo->auto_closed_reason = 'missing_exit';
                $ultimo->auto_closed_at = now()->toISOString();
                $ultimo->auto_closed_exit = '23:59';

                $ultimo->notes = trim(
                    ($ultimo->notes ? $ultimo->notes . "\n" : '') . $autoNote
                );

                $ultimo->save();

                return response()->json([
                    'message' => 'Había un fichaje anterior sin salida. Se ha cerrado automáticamente a las 23:59. Pulsa de nuevo para fichar entrada.',
                    'type' => 'auto_closed_previous_day',
                    'data' => $ultimo,
                ], 200);
            }

            // 🚪 Si el último fichaje NO tiene salida y es del mismo día → registrar SALIDA
            $ultimo->exit = $time;
            $ultimo->exit_location = $locationData;
            $ultimo->save();

            return response()->json([
                'message' => 'Fichaje de salida registrado correctamente.',
                'type' => 'exit',
                'data' => $ultimo
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar el fichaje',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function editSigning($id)
    {
        $signin = Signin::where('_id', $id)->first();

        if (!$signin) {
            return response()->json(['message' => 'Fichaje no encontrado'], 404);
        }

        $request = request();

        $editableFields = [
            'date',
            'entry',
            'exit',
            'entry_location',
            'exit_location',
            'notes',
            'activity_sections',
        ];

        $data = $request->only($editableFields);

        $oldValues = [];
        $newValues = [];
        $changes = [];

        foreach ($editableFields as $field) {
            if (!$request->has($field)) {
                continue;
            }

            $oldValue = $signin->{$field} ?? null;
            $newValue = $data[$field] ?? null;

            if ($this->normalizeAuditValue($oldValue) !== $this->normalizeAuditValue($newValue)) {
                $oldValues[$field] = $oldValue;
                $newValues[$field] = $newValue;

                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        $wasAutoClosed = (bool) ($signin->auto_closed ?? false);
        $oldAutoClosedData = [
            'auto_closed' => $signin->auto_closed ?? null,
            'auto_closed_reason' => $signin->auto_closed_reason ?? null,
            'auto_closed_at' => $signin->auto_closed_at ?? null,
            'auto_closed_exit' => $signin->auto_closed_exit ?? null,
        ];

        /*
        * Si el fichaje estaba cerrado automáticamente y el admin introduce
        * una salida real distinta a 23:59, limpiamos el cierre automático.
        */
        $newExit = $data['exit'] ?? null;

        if (
            $wasAutoClosed &&
            $request->has('exit') &&
            !empty($newExit) &&
            $newExit !== '23:59'
        ) {
            $data['auto_closed'] = false;
            $data['auto_closed_reason'] = null;
            $data['auto_closed_at'] = null;
            $data['auto_closed_exit'] = null;
            $data['corrected_by_admin'] = true;
            $data['corrected_at'] = now()->toISOString();
            $data['corrected_by'] = auth()->id();

            $changes['auto_closed'] = [
                'old' => $oldAutoClosedData['auto_closed'],
                'new' => false,
            ];

            $changes['auto_closed_reason'] = [
                'old' => $oldAutoClosedData['auto_closed_reason'],
                'new' => null,
            ];

            $changes['auto_closed_at'] = [
                'old' => $oldAutoClosedData['auto_closed_at'],
                'new' => null,
            ];

            $changes['auto_closed_exit'] = [
                'old' => $oldAutoClosedData['auto_closed_exit'],
                'new' => null,
            ];

            $changes['corrected_by_admin'] = [
                'old' => $signin->corrected_by_admin ?? null,
                'new' => true,
            ];

            $changes['corrected_at'] = [
                'old' => $signin->corrected_at ?? null,
                'new' => $data['corrected_at'],
            ];

            $changes['corrected_by'] = [
                'old' => $signin->corrected_by ?? null,
                'new' => $data['corrected_by'],
            ];
        }

        if (empty($changes)) {
            return response()->json([
                'message' => 'No se detectaron cambios.',
                'data' => $signin,
            ], 200);
        }

        $signin->fill($data);
        $signin->save();

        DB::collection('signin_audit_logs')->insert([
            'signin_id' => (string) $signin->_id,
            'user_id' => $signin->user_id ?? null,
            'edited_by' => auth()->id(),
            'edited_at' => now()->toISOString(),
            'reason' => $request->input('audit_reason'),
            'changes' => $changes,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => now()->toISOString(),
        ]);

        return response()->json([
            'message' => 'Fichaje actualizado correctamente.',
            'data' => $signin,
        ], 200);
    }

    private function normalizeAuditValue($value)
    {
        if ($value instanceof \MongoDB\Model\BSONArray || $value instanceof \MongoDB\Model\BSONDocument) {
            return json_encode($value);
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    public function getSigninAuditLogs($signinId)
    {
        $logs = DB::collection('signin_audit_logs')
            ->where('signin_id', (string) $signinId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $logs,
        ], 200);
    }

    public function deleteSigning($id)
    {
        $signin = Signin::where('_id', $id)->first();

        if (!$signin) {
            return response()->json(['message' => 'Fichaje no encontrado'], 404);
        }

        $signin->delete();

        return response()->json(['message' => 'Fichaje eliminado correctamente'], 200);
    }


    public function getWorkCalendarEvents(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $userId = $request->input('user_id');
        $type = $request->input('type');

        $query = DB::collection('work_calendar_events');

        if ($startDate && $endDate) {
            $query->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                ->orWhereNull('user_id');
            });
        }

        $events = $query
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'data' => $events,
        ], 200);
    }

    public function createWorkCalendarEvent(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $allowedTypes = [
            'company_holiday',
            'vacation',
            'medical_leave',
            'personal_day',
            'justified_absence',
        ];

        if (!in_array($validated['type'], $allowedTypes, true)) {
            return response()->json([
                'message' => 'Tipo de evento no válido.',
            ], 422);
        }

        if ($validated['type'] === 'company_holiday') {
            $validated['user_id'] = null;
        }

        if ($validated['type'] !== 'company_holiday' && empty($validated['user_id'])) {
            return response()->json([
                'message' => 'Debe seleccionar un empleado para vacaciones o ausencias.',
            ], 422);
        }

        $now = now()->toISOString();

        $event = array_merge($validated, [
            'status' => $validated['status'] ?? 'approved',
            'created_by' => auth()->id(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $id = DB::collection('work_calendar_events')->insertGetId($event);

        $event['_id'] = (string) $id;

        return response()->json([
            'message' => 'Evento creado correctamente.',
            'data' => $event,
        ], 201);
    }

    public function updateWorkCalendarEvent(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $allowedTypes = [
            'company_holiday',
            'vacation',
            'medical_leave',
            'personal_day',
            'justified_absence',
        ];

        if (!in_array($validated['type'], $allowedTypes, true)) {
            return response()->json([
                'message' => 'Tipo de evento no válido.',
            ], 422);
        }

        if ($validated['type'] === 'company_holiday') {
            $validated['user_id'] = null;
        }

        if ($validated['type'] !== 'company_holiday' && empty($validated['user_id'])) {
            return response()->json([
                'message' => 'Debe seleccionar un empleado para vacaciones o ausencias.',
            ], 422);
        }

        $event = DB::collection('work_calendar_events')
            ->where('_id', $id)
            ->first();

        if (!$event) {
            return response()->json([
                'message' => 'Evento no encontrado.',
            ], 404);
        }

        DB::collection('work_calendar_events')
            ->where('_id', $id)
            ->update(array_merge($validated, [
                'updated_at' => now()->toISOString(),
            ]));

        $updatedEvent = DB::collection('work_calendar_events')
            ->where('_id', $id)
            ->first();

        return response()->json([
            'message' => 'Evento actualizado correctamente.',
            'data' => $updatedEvent,
        ], 200);
    }

    public function deleteWorkCalendarEvent($id)
    {
        $event = DB::collection('work_calendar_events')
            ->where('_id', $id)
            ->first();

        if (!$event) {
            return response()->json([
                'message' => 'Evento no encontrado.',
            ], 404);
        }

        DB::collection('work_calendar_events')
            ->where('_id', $id)
            ->delete();

        return response()->json([
            'message' => 'Evento eliminado correctamente.',
        ], 200);
    }

    public function getWorkCalendarSummary(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $userId = $request->input('user_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $events = DB::collection('work_calendar_events')
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                ->orWhere('user_id', $userId);
            })
            ->get();

        return response()->json([
            'data' => $events,
        ], 200);
    }


    public function getMonthlySummary(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $userId = $request->input('user_id');
        $year = (int) $request->input('year');
        $month = (int) $request->input('month');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $startDateString = $startDate->format('Y-m-d');
        $endDateString = $endDate->format('Y-m-d');

        $signins = Signin::where('user_id', $userId)
            ->whereBetween('date', [$startDateString, $endDateString])
            ->orderBy('date', 'asc')
            ->orderBy('entry', 'asc')
            ->get()
            ->groupBy('date');

        $calendarEvents = DB::collection('work_calendar_events')
            ->where('start_date', '<=', $endDateString)
            ->where('end_date', '>=', $startDateString)
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                ->orWhere('user_id', $userId);
            })
            ->get();

        $workedDays = 0;
        $totalMinutes = 0;
        $companyHolidays = 0;
        $vacationDays = 0;
        $absenceDays = 0;
        $autoClosedCount = 0;
        $incidentsCount = 0;

        $days = [];

        $period = Carbon::parse($startDateString);

        while ($period->lte($endDate)) {
            $date = $period->format('Y-m-d');
            $daySignins = $signins->get($date, collect());

            $dayEvents = collect($calendarEvents)->filter(function ($event) use ($date) {
                $status = $event['status'] ?? 'approved';

                if ($status === 'rejected') {
                    return false;
                }

                return $event['start_date'] <= $date && $event['end_date'] >= $date;
            })->values();

            $mainEvent = null;

            if ($dayEvents->count()) {
                $mainEvent = $dayEvents->firstWhere('type', 'company_holiday')
                    ?? $dayEvents->firstWhere('type', 'vacation')
                    ?? $dayEvents->firstWhere('type', 'medical_leave')
                    ?? $dayEvents->firstWhere('type', 'personal_day')
                    ?? $dayEvents->firstWhere('type', 'justified_absence')
                    ?? $dayEvents->first();
            }

            $dayMinutes = 0;
            $hasAutoClosed = false;
            $hasOpenSignin = false;

            foreach ($daySignins as $signin) {
                if (!empty($signin->entry) && !empty($signin->exit)) {
                    try {
                        $entry = Carbon::createFromFormat('H:i', $signin->entry);
                        $exit = Carbon::createFromFormat('H:i', $signin->exit);

                        if ($exit->greaterThan($entry)) {
                            $dayMinutes += $entry->diffInMinutes($exit);
                        }
                    } catch (\Exception $e) {
                        // Si alguna hora viene mal formada, no rompemos el resumen.
                    }
                }

                if (!empty($signin->auto_closed)) {
                    $hasAutoClosed = true;
                }

                if (empty($signin->exit)) {
                    $hasOpenSignin = true;
                }
            }

            $isWeekend = $period->isWeekend();

            $status = 'no_work';

            if ($daySignins->count() > 0) {
                $status = 'worked';
                $workedDays++;

                if ($hasAutoClosed) {
                    $status = 'auto_closed';
                    $autoClosedCount++;
                    $incidentsCount++;
                } elseif ($hasOpenSignin) {
                    $status = 'open_signin';
                    $incidentsCount++;
                }
            } elseif ($mainEvent) {
                if ($mainEvent['type'] === 'company_holiday') {
                    $status = 'company_holiday';
                    $companyHolidays++;
                } elseif ($mainEvent['type'] === 'vacation') {
                    $status = 'vacation';
                    $vacationDays++;
                } else {
                    $status = 'absence';
                    $absenceDays++;
                }
            } elseif (!$isWeekend) {
                $status = 'missing_signin';
                $incidentsCount++;
            }

            $totalMinutes += $dayMinutes;

            $days[] = [
                'date' => $date,
                'day_name' => $period->locale('es')->isoFormat('dddd'),
                'is_weekend' => $isWeekend,
                'status' => $status,
                'event' => $mainEvent,
                'signins' => $daySignins->values(),
                'minutes' => $dayMinutes,
                'hours' => $this->formatMinutesAsHours($dayMinutes),
            ];

            $period->addDay();
        }

        return response()->json([
            'data' => [
                'user_id' => $userId,
                'year' => $year,
                'month' => $month,
                'start_date' => $startDateString,
                'end_date' => $endDateString,
                'worked_days' => $workedDays,
                'total_minutes' => $totalMinutes,
                'total_hours' => $this->formatMinutesAsHours($totalMinutes),
                'company_holidays' => $companyHolidays,
                'vacation_days' => $vacationDays,
                'absence_days' => $absenceDays,
                'auto_closed_count' => $autoClosedCount,
                'incidents_count' => $incidentsCount,
                'days' => $days,
            ],
        ], 200);
    }

    public function createVacationRequest(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:1000',
        ]);

        $userId = (string) $user->getAuthIdentifier();

        $existingPending = DB::collection('work_calendar_events')
            ->where('type', 'vacation')
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'Ya tiene una solicitud de vacaciones pendiente que coincide con esas fechas.',
            ], 422);
        }

        $employeeName = trim(
            ($user->firstName ?? '') . ' ' . ($user->lastName ?? '')
        );

        if ($employeeName === '') {
            $employeeName = $user->email ?? 'Empleado';
        }

        $now = now()->toISOString();

        $event = [
            'user_id' => $userId,
            'type' => 'vacation',
            'title' => 'Solicitud de vacaciones - ' . $employeeName,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'pending',
            'notes' => $validated['reason'] ?? null,
            'requested_by' => $userId,
            'requested_by_name' => $employeeName,
            'requested_by_email' => $user->email ?? null,
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $id = DB::collection('work_calendar_events')->insertGetId($event);

        $event['_id'] = (string) $id;

        $mailSent = false;
        $masterEmail = env('VACATION_REQUEST_MASTER_EMAIL');

        if ($masterEmail) {
            try {
                $emailBody =
                    "Nueva solicitud de vacaciones pendiente de revisión.\n\n" .
                    "Empleado: {$employeeName}\n" .
                    "Email: " . ($user->email ?? '-') . "\n" .
                    "Desde: {$validated['start_date']}\n" .
                    "Hasta: {$validated['end_date']}\n" .
                    "Motivo / notas: " . ($validated['reason'] ?? '-') . "\n\n" .
                    "Puede revisar la solicitud desde el apartado Calendario laboral del CRM.";

                Mail::raw($emailBody, function ($message) use ($masterEmail, $employeeName) {
                    $message->to($masterEmail)
                        ->subject('Nueva solicitud de vacaciones - ' . $employeeName);
                });

                $mailSent = true;
            } catch (\Exception $e) {
                $mailSent = false;
            }
        }

        return response()->json([
            'message' => $mailSent
                ? 'Solicitud de vacaciones enviada correctamente. Se ha avisado al gestor.'
                : 'Solicitud de vacaciones enviada correctamente. No se pudo enviar el correo al gestor.',
            'data' => $event,
            'mail_sent' => $mailSent,
        ], 201);
    }

    public function createForgottenSigningRequest(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'entry' => 'required|date_format:H:i',
            'exit' => 'nullable|date_format:H:i',
            'reason' => 'required|string|min:5|max:1000',
        ]);

        if (!empty($validated['exit']) && $validated['exit'] <= $validated['entry']) {
            return response()->json([
                'message' => 'La hora de salida debe ser posterior a la hora de entrada.',
            ], 422);
        }

        $userId = (string) $user->getAuthIdentifier();

        $existingPending = DB::collection('signin_requests')
            ->where('type', 'forgotten_signing')
            ->where('user_id', $userId)
            ->where('date', $validated['date'])
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'Ya tiene una solicitud pendiente para ese día.',
            ], 422);
        }

        $existingSignins = Signin::where('user_id', $userId)
            ->where('date', $validated['date'])
            ->get();

        $now = now()->toISOString();

        $requestData = [
            'type' => 'forgotten_signing',
            'user_id' => $userId,
            'date' => $validated['date'],
            'entry' => $validated['entry'],
            'exit' => $validated['exit'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'pending',
            'has_existing_signins' => $existingSignins->count() > 0,
            'existing_signins_count' => $existingSignins->count(),
            'created_at' => $now,
            'updated_at' => $now,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'approved_signin_id' => null,
        ];

        $id = DB::collection('signin_requests')->insertGetId($requestData);

        $requestData['_id'] = (string) $id;

        return response()->json([
            'message' => 'Solicitud enviada correctamente. Queda pendiente de revisión por el gestor.',
            'data' => $requestData,
        ], 201);
    }

    public function getForgottenSigningRequests(Request $request)
    {
        $status = $request->input('status');
        $userId = $request->input('user_id');

        $query = DB::collection('signin_requests')
            ->where('type', 'forgotten_signing');

        if ($status) {
            $query->where('status', $status);
        }

        if ($userId) {
            $query->where('user_id', (string) $userId);
        }

        $requests = $query
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $requests,
        ], 200);
    }

    public function approveForgottenSigningRequest($id)
    {
        $requestData = DB::collection('signin_requests')
            ->where('_id', $id)
            ->first();

        if (!$requestData) {
            return response()->json([
                'message' => 'Solicitud no encontrada.',
            ], 404);
        }

        if (($requestData['status'] ?? null) !== 'pending') {
            return response()->json([
                'message' => 'Esta solicitud ya fue revisada.',
            ], 422);
        }

        $signin = Signin::create([
            'user_id' => $requestData['user_id'],
            'date' => $requestData['date'],
            'entry' => $requestData['entry'],
            'exit' => $requestData['exit'] ?? null,
            'activity_sections' => [],
            'notes' => trim(
                "Fichaje creado desde solicitud de fichaje olvidado.\n" .
                "Motivo del empleado: " . ($requestData['reason'] ?? '')
            ),
            'created_from_request' => true,
            'request_id' => (string) $id,
            'approved_by' => auth()->id(),
            'approved_at' => now()->toISOString(),
        ]);

        DB::collection('signin_requests')
            ->where('_id', $id)
            ->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()->toISOString(),
                'approved_signin_id' => (string) $signin->_id,
                'updated_at' => now()->toISOString(),
            ]);

        return response()->json([
            'message' => 'Solicitud aprobada y fichaje creado correctamente.',
            'data' => $signin,
        ], 200);
    }

    public function rejectForgottenSigningRequest(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:3|max:1000',
        ]);

        $requestData = DB::collection('signin_requests')
            ->where('_id', $id)
            ->first();

        if (!$requestData) {
            return response()->json([
                'message' => 'Solicitud no encontrada.',
            ], 404);
        }

        if (($requestData['status'] ?? null) !== 'pending') {
            return response()->json([
                'message' => 'Esta solicitud ya fue revisada.',
            ], 422);
        }

        DB::collection('signin_requests')
            ->where('_id', $id)
            ->update([
                'status' => 'rejected',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now()->toISOString(),
                'rejection_reason' => $validated['rejection_reason'],
                'updated_at' => now()->toISOString(),
            ]);

        return response()->json([
            'message' => 'Solicitud rechazada correctamente.',
        ], 200);
    }
    private function formatMinutesAsHours($minutes)
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $remainingMinutes);
    }
}
