<?php

namespace App\Services;

use Log;
use Carbon\Carbon;
use App\Http\Models\User;
use App\Http\Models\Event;
use App\Http\Models\Order;
use App\Http\Models\Comparative;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use App\Helpers\UserHelper;
use App\Mail\SendOrderInfo;
use App\Http\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\GoogleController;
use App\Helpers\ProductHelper;
use App\Services\AuditLogService;
use Symfony\Component\HttpKernel\Exception\HttpException;



class OrderService
{

    /**
     * Guarda un contrato (incluye manejo de ficheros y normalización).
     * Devuelve el _id creado como string (24 hex).
     *
     * @param array   $order       Datos del contrato (array asociativo)
     * @param mixed   $account     null | string 24hex | ObjectId | array{_id: ...}
     * @param mixed   $userLogged  ['_id' => ..., 'firstName' => ...] (lo que usted guarde en sesión)
     * @param Request $request     Para acceder a archivos (docFileN)
     */
        public function saveOne(array $order, $account, Request $request)
    {
        $userLogged = Auth::user();

        $emailUser = false;

        $userSubdomain = (array) $request->input('userSubdomain', []);
        if (!isset($userSubdomain['_id']))
            $userSubdomain = json_decode($userSubdomain[0] ?? '{}', true);

        $colors = (array) $request->input('colors', []);
        if (!isset($colors['principal']))
            $colors = json_decode($colors[0] ?? '{}', true);

        $enterprise = (array) $request->input('enterprise', []);
        if (!isset($enterprise['_id']))
            $enterprise = json_decode($enterprise[0] ?? '{}', true);






        try {
            // Normalizar ID si viene como {"$oid": "..."}
            if (isset($order['_id']) && is_array($order['_id']) && isset($order['_id']['$oid'])) {
                $order['_id'] = $order['_id']['$oid'];
            }

            // Buscar contrato anterior (si existe)
            $oldOrder = null;
            if (!empty($order['_id']) && preg_match('/^[a-f0-9]{24}$/i', (string)$order['_id']))
                $oldOrder = Order::where('_id', new ObjectId($order['_id']))->first();





            $settings   = $userSubdomain['settings'] ?? [];
            $byMarketer = !empty($settings['orderRenewalReminderByMarketer']);

            if ($byMarketer) {

                // Si no viene explícitamente, lo damos por encendido
                if (!array_key_exists('isReminderOn', $order)) {
                    $order['isReminderOn'] = true;
                }

                // 👉 Aquí usamos el nuevo helper que calcula renewalDate
                //     a partir de rCommissionPyme / rCommissionRes del marketer
                $calculatedRenewal = ProductHelper::getRenewalDateFromMarketer(
                    $order,
                    $oldOrder ? $oldOrder->toArray() : null,
                    $userSubdomain
                );

                $order['isReminderOn'] = filter_var($order['isReminderOn'] ?? true, FILTER_VALIDATE_BOOLEAN);
                if ($order['isReminderOn'] && $calculatedRenewal) {
                    $order['renewalDate'] = $calculatedRenewal;
                } else {
                    $order['renewalDate'] = null;
                    $order['eventId'] = null;
                }
            }

            // --- A partir de aquí, trabajamos con el estado ya normalizado ---
            $oldReminderOn = filter_var($oldOrder['isReminderOn'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $newReminderOn = filter_var($order['isReminderOn'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $oldRenewal = isset($oldOrder['renewalDate']) ? Carbon::parse($oldOrder['renewalDate'])->format('Y-m-d') : null;
            $newRenewal = isset($order['renewalDate']) ? Carbon::parse($order['renewalDate'])->format('Y-m-d') : null;

            $eventId = $order['eventId'] ?? ($oldOrder['eventId'] ?? null);
            // Helper para borrar evento
            $deleteEvent = function ($eventId) use ($userLogged) {
                if (empty($eventId)) return;
                try {
                    if ($userLogged && isset($userLogged->googleRefreshToken)) {
                        GoogleController::deleteEventFrom($eventId, $userLogged);
                    } else {
                        Event::destroy($eventId);
                    }
                    \Log::info("Evento eliminado correctamente: {$eventId}");
                } catch (\Throwable $e) {
                    \Log::warning("Error al borrar evento {$eventId}: " . $e->getMessage());
                }
            };

            // --- Detectar cambios reales ---
            $reminderChanged = ($oldReminderOn !== $newReminderOn);
            $dateChanged     = ($newReminderOn && $oldRenewal !== $newRenewal);

            //  Si el recordatorio está apagado, limpiar siempre renewalDate y eventId
            //  aunque no haya cambiado el booleano, porque puede venir de BD con datos antiguos.
            if (!$newReminderOn) {
                if (!empty($eventId)) {
                    $deleteEvent($eventId);
                }

                // Importante: guardar explícitamente el apagado y limpiar campos en BD
                $order['isReminderOn'] = false;
                $order['renewalDate'] = null;
                $order['eventId'] = null;

                \Log::info("Recordatorio desactivado para {$order['name']}");
            }
            //  Si está encendido y (antes estaba apagado o cambió la fecha) -> crear/recrear
            elseif ($newReminderOn && ($reminderChanged || $dateChanged || empty($eventId))) {
                if (!empty($newRenewal)) {
                    $renewalDate = Carbon::parse($newRenewal);
                    $startDate   = $renewalDate->copy()->startOfDay()->toDateString();
                    $endDate     = $renewalDate->copy()->addDay()->startOfDay()->toDateString();

                    // Resolver accountId
                    $accountId = null;
                    if (is_array($account) && isset($account['_id'])) {
                        $accountId = $account['_id'];
                    } elseif (is_string($account)) {
                        $accountId = $account;
                    } elseif (!empty($order['account'])) {
                        $accountId = $order['account'];
                    }

                    // Si existía evento anterior, lo borro
                    if (!empty($eventId)) {
                        $deleteEvent($eventId);
                        unset($order['eventId']);
                    }

                    // Crear evento
                    if ($userLogged && isset($userLogged->googleRefreshToken)) {
                        $createdEventId = GoogleController::createEventFrom((object)$order, $userLogged, $startDate, $endDate);
                        \Log::info("Evento Google Calendar creado para {$order['name']} en {$startDate}");
                    } else {
                        $createdEventId = Event::insertGetId([
                            'subject'   => 'Renovación ' . ($order['name'] ?? ''),
                            'desc'      => '',
                            'date'      => [
                                'start'      => $startDate,
                                'end'        => $endDate,
                                'recurrency' => 'none',
                            ],
                            'color'     => 'pink-cake',
                            'account'   => $accountId,
                            'createdBy' => $userLogged ? (string)$userLogged->_id : null,
                            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);
                        \Log::info("Evento interno creado para {$order['name']} (ID: {$createdEventId})");
                    }

                    $order['eventId'] = (string)$createdEventId;
                } else {
                    \Log::warning("No se creó evento: renewalDate vacío para {$order['name']}");
                }
            } else {
                \Log::info("No se modifica evento: no hubo cambios reales (reminder={$newReminderOn}, dateChanged={$dateChanged}, eventId={$eventId})");
            }
        } catch (\Throwable $e) {
            \Log::error("Error en bloque de recordatorios: " . $e->getMessage());
        }






        $accountIdStr = $account['_id'] ?? null;

        // --- UsersIds ---
        $uidsRaw = !empty($order['usersIds']) ? $order['usersIds'] : ($account['usersIds'] ?? [Auth::user()->_id]);
        if (!is_array($uidsRaw))
            $uidsRaw = [$uidsRaw];
        $uids = [];
        foreach ($uidsRaw as $v) {
            if ($v instanceof \MongoDB\BSON\ObjectId) {
                $uids[] = (string) $v;
                continue;
            }
            if (is_array($v) && isset($v['_id'])) {
                $uids[] = (string) $v['_id'];
                continue;
            }
            if (is_string($v) && preg_match('/^[a-f0-9]{24}$/i', $v)) {
                $uids[] = $v;
                continue;
            }
        }
        $order['usersIds'] = $uids;

        // --- Asignación de contrato ---
        if (isset($order['assignedTo'])) {

            // quitar duplicados primero
            $order['usersIds'] = array_values(array_unique($order['usersIds'] ?? []));

            // si no está el assignedTo, lo añadimos
            if (!in_array($order['assignedTo'], $order['usersIds'])) {
                $order['usersIds'][] = $order['assignedTo'];
            }

            // OPCIONAL: ponerlo el último (visual/orden lógico)
            $order['usersIds'] = array_values(array_filter(
                $order['usersIds'],
                fn($id) => $id !== $order['assignedTo']
            ));

            $order['usersIds'][] = $order['assignedTo'];
        }

        if ($accountIdStr !== null)
            $order['account'] = $accountIdStr;


        // --- Timestamps y estados ---
        $nowStr = Carbon::now()->format('Y-m-d H:i:s');
        if (empty($order['createdAt'])) {
            $order['createdAt'] = $nowStr;
        }

        $statuses = $order['statuses'] ?? [];
        $statuses = is_array($statuses) ? $statuses : [];
        $newStatus = $order['newStatus'] ?? null;
        $newCode = $newStatus['code'] ?? null;

        $last = null;
        foreach ($statuses as $s) {
            if (!empty($s['date'])) {
                if ($last === null || Carbon::parse($s['date'])->gt(Carbon::parse($last['date']))) {
                    $last = $s;
                }
            }
        }

        $lastCode = $last['code'] ?? null;

        if (count($statuses) === 0) {
            $statuses[] = [
                'code'    => $newCode ?: 'p',
                'date'    => $nowStr,
                'creator' => $userLogged['_id'] ?? null,
            ];
        } elseif ($newCode && $newCode !== $lastCode) {
            $statuses[] = [
                'code'    => $newCode,
                'date'    => $nowStr,
                'creator' => $userLogged['_id'] ?? null,
            ];


            //ENVÍO CORREO

            //Usuarios
            $userListDown = UserHelper::hierarchy(Auth::user()->_id);
            $userListDown[] = $userLogged->toArray();


            // Normalizar colores de estados
            $subdomainStatuses = array_map(fn($s) => (object) $s, $userSubdomain['statuses']);
            foreach ($subdomainStatuses as &$status) {
                if (!preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $status->color)) {
                    $status->color = $colors[$status->color] ?? $status->color;
                }
            }

            $statusesIndex = array_column($subdomainStatuses, null, 'code');
            $statusesIndex = array_map(fn($s) => (array) $s, $statusesIndex);

            $emailData = [
                'accName' => null,
                'content' => 'Se ha cambiado el estado de tu contrato <strong>' . $order['name'] . '</strong> de ' . $statusesIndex[$lastCode]['title'] . ' a ' . $statusesIndex[$order['newStatus']['code']]['title'] .
                    '<br>Observaciones: ' . ($order['observations'] ?? 'No hay observaciones') . '',
                'button'  => [
                    'text' => 'Ir al contrato',
                    'url'  => 'https://' . $enterprise['url'] . '/contracts?_id=' . (string)$order['_id'],
                ],
                'enterprise' => $enterprise
            ];


            //Si es incidencia y tiene mensaje
            if ($order['newStatus'] && $order['newStatus']['code'] === 'i')
                $emailData['incidence'] = $order['incidence'] ?? '';


            $mailName = null;
            if (isset($enterprise['mailConfig'])) {
                $mailName = strtoupper($enterprise['mailConfig']);

                if (env("MAIL_USERNAME_" . $mailName) && env("MAIL_PASSWORD_" . $mailName)) {
                    Config::set('mail.mailers.smtp.host', env('MAIL_HOST_' . $mailName) ?: env('MAIL_HOST'));
                    Config::set('mail.mailers.smtp.port', env('MAIL_PORT_' . $mailName, env('MAIL_PORT', 587)));
                    Config::set('mail.mailers.smtp.encryption', env('MAIL_ENCRYPTION_' . $mailName, env('MAIL_ENCRYPTION', 'tls')));
                    Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME_' . $mailName));
                    Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD_' . $mailName));
                    Config::set('mail.from.address', env('MAIL_FROM_ADDRESS_' . $mailName, env('MAIL_FROM_ADDRESS')));
                    Config::set('mail.from.name', env('MAIL_FROM_NAME_' . $mailName, env('MAIL_FROM_NAME')));

                    Mail::purge('smtp');
                }
            }



            //Saco los ids
            $usersIdsToSend = [];

            if (!empty($accountIdStr) && is_array($account) && !empty($account['usersIds'])) {
                $usersIdsToSend = $account['usersIds'];
            } elseif (!empty($order['usersIds'])) {
                $usersIdsToSend = $order['usersIds'];
            }

            // Normalizar IDs
            $usersIdsToSend = array_values(array_unique(array_filter(array_map(function ($userId) {
                if ($userId instanceof \MongoDB\BSON\ObjectId) {
                    return (string) $userId;
                }

                if (is_array($userId) && isset($userId['_id'])) {
                    return (string) $userId['_id'];
                }

                if (is_array($userId) && isset($userId['$oid'])) {
                    return (string) $userId['$oid'];
                }

                return (string) $userId;
            }, $usersIdsToSend))));

            $usersEmailsToSend = [];

            $usersForEmailLog = [];

            if (!empty($usersIdsToSend)) {
                $usersFound = User::whereIn('_id', $usersIdsToSend)->get();

                $usersEmailsToSend = $usersFound
                    ->filter(function ($user) {
                        return !empty($user->email) && !isset($user->notSendStatusEmails);
                    })
                    ->pluck('email')
                    ->unique()
                    ->values()
                    ->toArray();

                $usersForEmailLog = $usersFound
                    ->map(function ($user) {
                        return [
                            'id' => (string) $user->_id,
                            'name' => trim(($user->firstName ?? '') . ' ' . ($user->lastName ?? '')),
                            'email' => $user->email ?? null,
                            'notSendStatusEmails' => isset($user->notSendStatusEmails),
                        ];
                    })
                    ->values()
                    ->toArray();
            }

            \Log::info('Destinatarios calculados para email de cambio de estado', [
                'usersIdsToSend' => $usersIdsToSend,
                'usersFound' => $usersForEmailLog,
                'usersEmailsToSend' => $usersEmailsToSend,
                'orderId' => $order['_id'] ?? null,
            ]);

            // Si el estado tiene sendEmail = true enviar el correo
            $newStatusCode = $order['newStatus']['code'];

            $subject = "ACTUALIZACIÓN ESTADO: " . ($statusesIndex[$newStatusCode]['title'] ?? 'DESCONOCIDO') . " | " . strtoupper($order['name']) . " | " . strtoupper($order['marketer'] . " | " . $order['CUPS']);

            if (
                $newStatusCode
                && isset($statusesIndex[$newStatusCode])
                && isset($statusesIndex[$newStatusCode]['sendEmail'])
                && $statusesIndex[$newStatusCode]['sendEmail'] === true
            ) {
                try {
                    if (!empty($usersEmailsToSend)) {

                        Mail::to($usersEmailsToSend)->send(new SendOrderInfo($emailData, $mailName, $subject));

                        \Log::info('Email de cambio de estado enviado', [
                            'usersEmailsToSend' => $usersEmailsToSend,
                            'orderId'           => $order['_id'] ?? null,
                            'status'            => $newStatusCode,
                            'mailConfig'         => $mailName,
                            'from'              => config('mail.from.address'),
                            'from_name'         => config('mail.from.name'),
                            'mail_host'         => config('mail.mailers.smtp.host'),
                        ]);

                    } else {
                        \Log::warning('No se envió email de cambio de estado porque no hay destinatarios', [
                            'orderId' => $order['_id'] ?? null,
                            'status'  => $newStatusCode,
                        ]);
                    }

                } catch (\Throwable $e) {
                    \Log::error('Error al enviar correo de cambio de estado: ' . $e->getMessage(), [
                        'exception'         => $e,
                        'usersEmailsToSend' => $usersEmailsToSend ?? null,
                        'orderId'           => $order['_id'] ?? null,
                        'status'            => $newStatusCode,
                        'mailConfig'        => $mailName,
                        'from'              => config('mail.from.address'),
                        'from_name'         => config('mail.from.name'),
                        'mail_host'         => config('mail.mailers.smtp.host'),
                    ]);

                    $emailUser = true;
                }
            }
        }

        $order['statuses'] = array_values($statuses);
        $order['lastStatus'] = $this->calculateLastStatus($order['statuses']);
        $order['updatedAt'] = $nowStr;
        unset($order['newStatus']);

        $order['search_name']     = $this->normalizeSearch($order['name'] ?? '');
        $order['search_direc']    = $this->normalizeSearch($order['direc'] ?? '');
        $order['search_town']     = $this->normalizeSearch($order['town'] ?? '');
        $order['search_province'] = $this->normalizeSearch($order['province'] ?? '');
        $order['search_product']  = $this->normalizeSearch((string)($order['product'] ?? ''));
        $order['search_cups']     = $this->normalizeSearch($order['CUPS'] ?? '');
        $order['search_iban']     = $this->normalizeSearch($order['IBAN'] ?? '');
        $order['search_cif']      = $this->normalizeSearch($order['accountCIF'] ?? '');

        // --- owner / createdBy ---
        $ownerName = $userLogged['firstName'] ?? '';
        if (!empty($order['ownerId']))
            $ownerName = User::where('_id', $order['ownerId'])->value('firstName') ?? $ownerName;

        if (empty($order['owner']))
            $order['owner'] = $ownerName;

        if (!isset($order['createdBy']) || empty($order['createdBy']))
            $order['createdBy'] = $userLogged['_id'] ?? null;

        $order['updatedAt'] = $nowStr;


        // --- si no tiene fecha de creación---
        if (!isset($order['transferDate']) || $order['transferDate'] === '')
            $order['transferDate'] = Carbon::now()->format('d/m/y');

        $oldLastCode = null;
        if ($oldOrder && !empty($oldOrder['statuses']) && is_array($oldOrder['statuses'])) {
            $oldLast = null;
            foreach ($oldOrder['statuses'] as $s) {
                if (!empty($s['date'])) {
                    if ($oldLast === null || Carbon::parse($s['date'])->gt(Carbon::parse($oldLast['date']))) {
                        $oldLast = $s;
                    }
                }
            }
            $oldLastCode = $oldLast['code'] ?? null;
        }

        // Precios anteriores y actuales
        $prevPrices    = $oldOrder['pricesProduct'] ?? null;   // lo que ya tenía en BD
        $currentPrices = $order['pricesProduct'] ?? null;      // por si viene algo del front

        if (
            $newCode === 'a' &&        // estado nuevo = Activado
            $oldLastCode !== 'a' &&    // antes NO estaba en Activado
            empty($prevPrices) &&      // no había pricesProduct guardado
            empty($currentPrices)      // tampoco viene ahora rellenado
        ) {

            $order['pricesProduct'] = ProductHelper::getPricesForContract($order);

        }


        //dd($order['pricesProduct']);

        $order['lastStatus'] = $this->calculateLastStatus($order['statuses']);




        // --- Guardar contrato ---
        if (!empty($order['_id']) && is_string($order['_id'])) {
            $orderId = $order['_id'];
            unset($order['_id']);

            $filteredId = preg_match('/^[a-f0-9]{24}$/i', (string) $orderId) ? new ObjectId($orderId) : $orderId;

            $orderToSave = Order::find($filteredId);

            //Comprobación de CUPS si es distinto al que había
            if (
                isset($userSubdomain['settings']['denyDuplicateCups'])
                && $userSubdomain['settings']['denyDuplicateCups']
                && !empty($order['CUPS'])
                && !$this->isExceptionalCups($order['CUPS'])
                && $order['CUPS'] !== $orderToSave->CUPS
            ) {
                $this->checkIfCUPSExists($userSubdomain, $order['CUPS'], $order['CUPSSecondary'] ?? null, $orderToSave->_id);
            }

            // Si el front ya no envía assignedTo pero el contrato en BD sí lo tenía,
            // hay que desasignar de verdad (limpiar el campo y sacarlo de usersIds)
            if (!isset($order['assignedTo']) && !empty($orderToSave->assignedTo)) {
                $order['assignedTo'] = null; // fuerza que fill() lo limpie en BD
                $order['usersIds'] = array_values(array_filter(
                    $order['usersIds'],
                    fn($id) => $id !== $orderToSave->assignedTo
                ));
            }

            //Guardo los logs de actualización
            AuditLogService::updateOrder($orderToSave, $order, $userLogged);

            //Guardo el order
            $orderToSave->fill($order);
            $orderToSave->save();

            //Marco visualmente las comparativas guardadas con este CUPS como "ya contratadas"
            $this->markComparativesWithContract($order['CUPS'] ?? '', $userLogged);

            return (string)$orderId;
        }


        // Nuevo contrato

        //Compruebo si existe el CUPS
        if (
            isset($userSubdomain['settings']['denyDuplicateCups'])
            && $userSubdomain['settings']['denyDuplicateCups']
            && !empty($order['CUPS'])
            && !$this->isExceptionalCups($order['CUPS'])
        ) {
            $this->checkIfCUPSExists($userSubdomain, $order['CUPS'], $order['CUPSSecondary'] ?? null);
        }


        // Generar identifier incremental por enterprise
        if (!empty($enterprise['_id'])) {

            $nextIdentifier = $this->getNextEnterpriseIdentifier(
                (string) $enterprise['_id']
            );

            $order['identifier'] = $nextIdentifier;
        }

        $order = Order::create($order);

        //Guardo el log de registro de contrato
        AuditLogService::createOrDeleteOrder($order, $userLogged, 'create');

        //Marco visualmente las comparativas guardadas con este CUPS como "ya contratadas"
        $this->markComparativesWithContract($order['CUPS'] ?? '', $userLogged);




        try {
            $initialStatusCode = $order['statuses'][0]['code'] ?? null;

            // Solo enviar email si está en Borrador (bo) o Pendiente (p)
            if (in_array($initialStatusCode, ['bo', 'p'], true)) {

                // Verificar si el subdominio tiene activado el envío de correos ===
                $contractEmail = $userSubdomain['settings']['contractEmail'] ?? null;
                $statusList = $userSubdomain['statuses'] ?? [];
                $isFidelity =
                    (string)($userSubdomain['_id'] ?? '') === '68d260e6bc9e8c38f8003df2'
                    || (($enterprise['color'] ?? '') === 'fidelity360Color')
                    || str_contains(mb_strtolower((string)($enterprise['name'] ?? '')), 'fidelity');

                $normalizeRole = function ($value) {
                    $value = mb_strtolower((string) $value);

                    $value = str_replace(
                        ['á','à','ä','â','ã','å','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','õ','ú','ù','ü','û','ñ'],
                        ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','n'],
                        $value
                    );

                    return preg_replace('/[^a-z0-9]/', '', $value);
                };

                $loggedRole = $normalizeRole(
                    $userLogged->role
                    ?? $userLogged->rol
                    ?? $userLogged->type
                    ?? $userLogged->profile
                    ?? ''
                );

                $blockedFidelityRoles = array_map($normalizeRole, [
                    'Jefe administrador',
                    'Usuario subdominio',
                    'Administrador',
                ]);

                if ($isFidelity && in_array($loggedRole, $blockedFidelityRoles, true)) {
                    \Log::info('📭 Email de creación omitido para Fidelity por rol del usuario.', [
                        'userId'  => (string)($userLogged->_id ?? ''),
                        'role'    => $loggedRole,
                        'orderId' => (string)($order['_id'] ?? ''),
                    ]);

                    $contractEmail = false;
                }

                if ($contractEmail) {

                    // === 🔍 Buscar el usuario de subdominio principal ===
                    $mainUser = isset($userSubdomain['_id'])
                        ? User::where('_id', $userSubdomain['_id'])->first()
                        : null;

                    if ($mainUser && !empty($mainUser->email)) {

                        // === ⚙️ Configurar el remitente dinámico ===
                        $mailName = null;
                        if (
                            isset($enterprise['mailConfig']) &&
                            !!env("MAIL_USERNAME_" . $enterprise['mailConfig']) &&
                            !!env("MAIL_PASSWORD_" . $enterprise['mailConfig'])
                        ) {
                            $mailName = strtoupper($enterprise['mailConfig']);
                            Config::set('mail.mailers.smtp.host',    env('MAIL_HOST_' . $mailName) ?: env('MAIL_HOST'));
                            Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME_' . $mailName));
                            Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD_' . $mailName));
                            Config::set('mail.from.address',          env('MAIL_FROM_ADDRESS_' . $mailName));
                            Config::set('mail.from.name',             env('MAIL_FROM_NAME_' . $mailName));
                        }

                        // === ✉️ Preparar asunto y contenido ===
                        $clientName     = strtoupper($order['name']);
                        $enterpriseName = strtoupper($order['marketer']);
                        $commercialName = '';

                        if (!empty($order['usersIds'])) {
                            for ($i = 0; $i < count($order['usersIds']); $i++) {
                                $commercialName .= strtoupper(
                                    User::where('_id', $order['usersIds'][$i])->value('firstName') . ' ' .
                                    User::where('_id', $order['usersIds'][$i])->value('lastName')
                                );
                                if ($i < count($order['usersIds']) - 1) {
                                    $commercialName .= ', ';
                                }
                            }
                        } else {
                            $commercialName = strtoupper(Auth::user()['firstName'] . ' ' . Auth::user()['lastName']);
                        }

                        $orderStatus = $initialStatusCode;
                        $statusIndex = array_column($statusList, null, 'code');
                        $statusTitle = strtoupper($statusIndex[$orderStatus]['title'] ?? 'DESCONOCIDO');

                        $subject = "CRM NUEVO CONTRATO CON ESTADO {$statusTitle} | {$clientName} | {$enterpriseName} | {$commercialName}";

                        $message = "Se ha creado un nuevo contrato para el cliente <strong>{$clientName}</strong>.";

                        $emailData = [
                            'accName' => null,
                            'content' => $message,
                            'button'  => [
                                'text' => 'Ver contrato',
                                'url'  => 'https://' . $enterprise['url'] . '/contracts?id=' . (string)$order['_id'],
                            ],
                            'enterprise' => $enterprise,
                            ...($userSubdomain['_id'] === '68d260e6bc9e8c38f8003df2' ? [
                                'contract' => [
                                    'account'      => $account['name'],
                                    'CIF'          => $account['CIF'],
                                    'CUPS'         => $order->CUPS,
                                    'marketer'     => $order->marketer,
                                    'fee'          => $order->fee,
                                    'iban'         => $order->IBAN,
                                    'email'        => $account['email'],
                                    'phone'        => $account['phone'],
                                    'observations' => $order->observations,
                                ]
                            ] : []),
                        ];

                        // === 📤 Enviar correo solo al usuario subdominio ===

                        // === 📤 Enviar correo solo al usuario subdominio ===


                        Mail::to($mainUser->email)->send(
                            new SendOrderInfo($emailData, $mailName, $subject)
                        );

                        \Log::info("📨 Correo de nuevo contrato enviado a subdominio {$mainUser->email} (estado {$initialStatusCode})");
                    } else {
                        \Log::warning("⚠️ Subdominio sin email principal definido, no se envió correo de nuevo contrato.");
                    }
                } else {
                    \Log::info("ℹ️ Subdominio sin contratoEmail activo, no se envía notificación.");
                }
            } else {
                \Log::info("📭 Email de creación omitido (estado {$initialStatusCode})");
            }
        } catch (\Throwable $e) {
            \Log::error("❌ Error al enviar correo de nuevo contrato: " . $e->getMessage());
            $emailUser = true;
        }


        return [
            'order'      => $order,
            'emailError' => $emailUser,
        ];
    }




    /**
     * Guarda varios contratos de una misma cuenta (ideal para "crear cuenta con contratos").
     * Devuelve la lista de _id creados como strings.
     *
     * @param array<int,array> $orders
     */
    public function saveManyForAccount(array $orders, $account, Request $request): array
    {
        $ids = [];
        foreach ($orders as $idx => $ord) {
            $ids[] = $this->saveOne((array) $ord, $account, $request);
        }
        return $ids;
    }

    private function calculateLastStatus(?array $statuses): ?array
    {
        if (empty($statuses) || !is_array($statuses)) {
            return null;
        }

        $latest = null;
        $latestDate = null;

        foreach ($statuses as $status) {

            if (empty($status['date'])) {
                continue;
            }

            try {
                $date = Carbon::parse($status['date']);
            } catch (\Exception $e) {
                continue;
            }

            if (!$latestDate || $date->gt($latestDate)) {
                $latestDate = $date;
                $latest = [
                    'code' => $status['code'] ?? null,
                    'date' => $status['date']
                ];
            }
        }

        return $latest;
    }

    private function getNextEnterpriseIdentifier(string $enterpriseId): int
    {
        $collection = \DB::connection('mongodb')
            ->getMongoDB()
            ->selectCollection('enterprises');

        $result = $collection->findOneAndUpdate(
            ['_id' => new ObjectId($enterpriseId)],
            ['$inc' => ['idIncremental' => 1]],
            [
                'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
            ]
        );

        return isset($result['idIncremental'])
            ? (int) $result['idIncremental']
            : 1;
    }

    private function normalizeSearch($value): string
    {
        if (!$value) return '';

        $value = mb_strtolower((string)$value);

        $value = str_replace(
            ['á','à','ä','â','ã','å','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','õ','ú','ù','ü','û','ñ'],
            ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','n'],
            $value
        );

        return str_replace(' ', '', $value);
    }

    private function normalizeControlField(?string $value): string
    {
        return strtoupper(preg_replace('/[\s\.\-]/', '', (string) $value));
    }

    private function isExceptionalCups(?string $cups): bool
    {
        return $this->normalizeControlField($cups) === 'ES0000';
    }

    //Función para comprobar si existe
    /**
     * Marca como "contratadas" (solo visual) las comparativas guardadas que tengan
     * el mismo CUPS que el contrato recién guardado, dentro del ámbito (jerarquía /
     * subdominio) del usuario que lo guarda. Se ejecuta al guardar un contrato, no
     * sobre los contratos ya existentes.
     */
    private function markComparativesWithContract($cups, $userLogged): void
    {
        $cups = $this->normalizeControlField($cups);

        if ($cups === '' || $this->isExceptionalCups($cups)) {
            return;
        }

        $scopeUserIds = self::comparativeScopeUserIds($userLogged);

        if (empty($scopeUserIds)) {
            return;
        }

        try {
            Comparative::whereIn('createdBy', $scopeUserIds)
                ->where('cups', 'regex', new Regex('^' . preg_quote($cups) . '$', 'i'))
                ->update(['hasContract' => true]);
        } catch (\Throwable $e) {
            // El marcado del check es solo visual: nunca debe romper el guardado del contrato.
        }
    }

    /**
     * Quita el check visual ("hasContract") de las comparativas con este CUPS dentro
     * del ámbito (jerarquía / subdominio) del usuario, pero solo si ya no queda ningún
     * contrato con ese mismo CUPS en ese ámbito. Debe llamarse DESPUÉS de borrar el contrato.
     */
    public static function unmarkComparativesIfNoContract($cups, $user): void
    {
        $cups = strtoupper(preg_replace('/[\s\.\-]/', '', (string) $cups));

        if ($cups === '' || $cups === 'ES0000') {
            return;
        }

        $scopeUserIds = self::comparativeScopeUserIds($user);

        if (empty($scopeUserIds)) {
            return;
        }

        try {
            $pattern = new Regex('^' . preg_quote($cups) . '$', 'i');

            // Si todavía existe otro contrato con este CUPS en el mismo ámbito, se mantiene el check.
            if (Order::whereIn('usersIds', $scopeUserIds)->where('CUPS', 'regex', $pattern)->exists()) {
                return;
            }

            Comparative::whereIn('createdBy', $scopeUserIds)
                ->where('cups', 'regex', $pattern)
                ->update(['hasContract' => false]);
        } catch (\Throwable $e) {
            // Solo visual: nunca debe romper el borrado del contrato.
        }
    }

    /**
     * IDs de usuarios del ámbito del usuario indicado, con el MISMO criterio que la
     * visibilidad de comparativas: todo el subdominio si tiene "users.admiWhiHier",
     * o su jerarquía descendente (él mismo + subordinados) en caso contrario.
     */
    private static function comparativeScopeUserIds($user): array
    {
        $userId = (string) ($user['_id'] ?? '');

        if ($userId === '') {
            return [];
        }

        $userSubdomain = UserHelper::getUserSubdomain($userId);

        $label = $user['label'] ?? null;
        $usersPermissions = $userSubdomain['labels_permissions'][$label]['users'] ?? null;

        if (is_array($usersPermissions) && in_array('admiWhiHier', $usersPermissions, true)) {
            $subdomainUsers = UserHelper::getSubdomainUserList($userSubdomain);

            return array_values(array_map(
                fn($subUser) => is_array($subUser) ? (string) $subUser['_id'] : (string) $subUser,
                $subdomainUsers
            ));
        }

        // Jerarquía descendente: el usuario + todos sus subordinados (recursivo)
        $ids = [$userId];
        $pending = [$userId];

        while (!empty($pending)) {
            $current = array_shift($pending);
            $children = User::where('responsibles', $current)->get(['_id']);

            foreach ($children as $child) {
                $childId = (string) $child['_id'];

                if (!in_array($childId, $ids, true)) {
                    $ids[] = $childId;
                    $pending[] = $childId;
                }
            }
        }

        return $ids;
    }

    private function checkIfCUPSExists($userSubdomain, $CUPS, $CUPSSecondary = null, $orderID = null) {
        // Usuarios del subdominio
        $userList = UserHelper::hierarchy($userSubdomain['_id']);
        $userList[] = Auth::user()->toArray();
        $userList = array_map(function ($user) {
            return is_array($user) ? (string) ($user['_id'] ?? null) : (string) ($user->_id ?? null);
        }, $userList);

        // CUPS del contrato actual que hay que comprobar (principal y/o secundario)
        $cupsToCheck = array_filter([$CUPS, $CUPSSecondary], function ($v) {
            return !empty($v);
        });

        if (empty($cupsToCheck)) {
            return;
        }


        // Query base
        $query = Order::where(function ($q) use ($cupsToCheck) {
            $q->whereIn('CUPS', $cupsToCheck)
                ->orWhereIn('CUPSSecondary', $cupsToCheck);
        })
            ->whereIn('usersIds', $userList);

        // Excluir order actual (solo en edición)
        if (!empty($orderID))
            $query->where('_id', '!=', $orderID);

        $orders = $query->get();


        foreach ($orders as $order) {
            if (!empty($order->statuses)) {
                $statuses = $order->statuses;
                $lastStatus = end($statuses);

                if ($lastStatus['code'] !== 'bo' && $lastStatus['code'] !== 'b' && $lastStatus['code'] !== 'baja_anticipada_retrocomisionada' && $lastStatus['code'] !== 's' && $lastStatus['code'] !== 'an' && $lastStatus['code'] !== 'caducado' && $lastStatus['code'] !== 'renovado') {
                    throw new \Illuminate\Http\Exceptions\HttpResponseException(
                        response()->json([
                            'error' => 'El CUPS ya existe en otro contrato.'
                        ], 422)
                    );
                }
            }
        }



    }

        private function shouldSkipFidelityEmail($userSubdomain, $enterprise, $userLogged): bool
    {
        $isFidelity =
            (string)($userSubdomain['_id'] ?? '') === '68d260e6bc9e8c38f8003df2'
            || (($enterprise['color'] ?? '') === 'fidelity360Color')
            || str_contains(
                $this->normalizeRoleText((string)($enterprise['name'] ?? '')),
                'fidelity'
            );

        if (!$isFidelity) {
            return false;
        }

        $loggedRole = $this->normalizeRoleText(
            data_get($userLogged, 'role')
            ?? data_get($userLogged, 'rol')
            ?? data_get($userLogged, 'type')
            ?? data_get($userLogged, 'profile')
            ?? data_get($userLogged, 'userType')
            ?? ''
        );

        $blockedRoles = array_map([$this, 'normalizeRoleText'], [
            'Jefe administrador',
            'Usuario subdominio',
            'Administrador',
        ]);

        return in_array($loggedRole, $blockedRoles, true);
    }

    private function normalizeRoleText($value): string
    {
        $value = mb_strtolower((string) $value);

        $value = str_replace(
            ['á','à','ä','â','ã','å','é','è','ë','ê','í','ì','ï','î','ó','ò','ö','ô','õ','ú','ù','ü','û','ñ'],
            ['a','a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','n'],
            $value
        );

        return preg_replace('/[^a-z0-9]/', '', $value);
    }
}
