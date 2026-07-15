<?php

namespace App\Services;

use App\Http\Models\Log;
use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;

class AuditLogService
{

    //Guardar el log
    private static function save($type, $event, $user, $changes = [], $metadata = [])
    {

        if ($user instanceof \stdClass) {
            $user = (array) $user;
        }


        $data = [
            "type" => $type,
            "event" => $event,
            "metadata" => $metadata,
            "createdBy" => $user['_id'],
            "createdAt" => new UTCDateTime(Carbon::now('UTC')->getTimestampMs())
        ];

        if (count($changes) > 0)
            $data['changes'] = $changes;

        Log::create($data);
    }


    //CONTRATOS

        //Crear/borrar
        public static function createOrDeleteOrder($order, $user, $type){

            self::save(
                type: "contracts",
                event: $type,
                user: $user,
                metadata: [
                    '_id' => $order['_id'],
                    'name' => $order['name'] ?? null,
                    'CUPS' => $order['CUPS'] ?? null,
                    'account' => $order['account'] ?? null,
                    'ip' => request()->ip()
                ]
            );

        }

        //Actualizar
        public static function updateOrder($before, $after, $user){

            $changes = self::detectChanges($before, $after, 'contracts');

            //Si hay cambios
            if (!empty($changes)) {
                self::save(
                    type: "contracts",
                    event: "update",
                    user: $user,
                    changes: $changes,
                    metadata: [
                        '_id' => $before['_id'],
                        'name' => $after['name'] ?? null,
                        'CUPS' => $after['CUPS'] ?? null,
                        'account' => $after['account'] ?? null,
                        'ip' => request()->ip()
                    ]
                );
            }
        }


    //CUENTAS

        //Crear/borrar
        public static function createOrDeleteAccount($account, $user, $type){
            self::save(
                type: "accounts",
                event: $type,
                user: $user,
                metadata: [
                    '_id' => $account['_id'],
                    'name' => $account['name'] ?? null,
                    'CIF' => $account['CIF'] ?? null,
                    'ip' => request()->ip()
                ]
            );
        }

        //Actualizar
        public static function updateAccount($before, $after, $user){

            $changes = self::detectChanges($before, $after, 'accounts');



            //Si hay cambios
            if (!empty($changes)) {
                self::save(
                    type: "accounts",
                    event: "update",
                    user: $user,
                    changes: $changes,
                    metadata: [
                        '_id' => $before['_id'],
                        'name' => $after['name'] ?? null,
                        'CIF' => $after['CIF'] ?? null,
                        'ip' => request()->ip()
                    ]
                );
            }
        }


    //OPORTUNIDADES

        //Crear/borrar
        public static function createOrDeleteOpportunity($oppportunity, $user, $type){
            self::save(
                type: "opportunities",
                event: $type,
                user: $user,
                metadata: [
                    '_id' => $oppportunity['_id'],
                    'name' => $oppportunity['name'] ?? null,
                    'CIF' => $oppportunity['CIF'] ?? null,
                    'ip' => request()->ip()
                ]
            );
        }

        //Actualizar
        public static function updateOpportunity($before, $after, $user){

            $before = json_decode(json_encode($before), true);
            $after  = json_decode(json_encode($after), true);

            $changes = self::detectChanges($before, $after, 'opportunities');

            //Si hay cambios
            if (!empty($changes)) {
                self::save(
                    type: "opportunities",
                    event: "update",
                    user: $user,
                    changes: $changes,
                    metadata: [
                        '_id' => $before['_id'],
                        'name' => $after['name'] ?? null,
                        'CIF' => $after['CIF'] ?? null,
                        'ip' => request()->ip()
                    ]
                );
            }
        }



    //COMPARATIVAS

        //Genero comparativa
        public static function generateComparative($status, $messageError = null, $inputType, $comparativeType, $codePart, $input, $output, $user, $witroPhone = null){

            $metadata = [
                'status' => $status,
                'name' => $inputType === 'bill' ? ($input['pdf']['titular'] ?? 'Error nombre') : null,
                'CUPS' => $inputType === 'bill' ? ($input['pdf']['cups'] ?? 'Error cups') : ($inputType === 'cups' ? $input['manual']['cups'] : null),
                'inputType' => $inputType,
                'comparativeType' => $comparativeType,
                'total' => $input['calculated']['total'] ?? 0,
                'maxSaving' => ($input['calculated']['total'] ?? 0) - ($output['total'][0]['total'] ?? 0),
                'input' => $input,
                'output' => $output,
                'ip' => request()->ip()
            ];

            if ($status === 'error'){
                $metadata['messageError'] = $messageError;
                $metadata['codePart'] = $codePart;
            }

            if (isset($witroPhone))
                $metadata['witroPhone'] = $witroPhone;

            self::save(
                type: "comparatives",
                event: "generate",
                user: $user,
                metadata: $metadata
            );
        }






    //Comprobar cambios campos
    private static function detectChanges($before, $after, $type)
    {
        $changes = [];

        foreach ($after as $key => $newValue) {

            //Ignoro los campos no necesarios
            if (in_array($key, self::$ignoreFields[$type] ?? []))
                continue;


            //Excepciones manuales
            if ($key === 'statuses') {
                $beforeStatuses = $before['statuses'] ?? [];
                $afterStatuses  = $after['statuses'] ?? [];

                $latestBefore = end($beforeStatuses);
                $latestAfter  = end($afterStatuses);

                // Solo loguear si realmente cambió el code
                if (($latestBefore['code'] ?? null) !== ($latestAfter['code'] ?? null)) {
                    $changes['status'] = [
                        'before' => $latestBefore['code'] ?? null,
                        'after'  => $latestAfter['code'] ?? null,
                    ];
                }

                continue;
            }

            if ($key === 'billingInfo') {

                $beforeBilling = isset($before['billingInfo']) ? (array)$before['billingInfo'] : [];
                $afterBilling  = isset($after['billingInfo']) ? (array)$after['billingInfo'] : [];

                // Comparamos campo por campo dentro de billingInfo
                foreach ($afterBilling as $subKey => $subValue) {

                    $oldSubValue = $beforeBilling[$subKey] ?? null;

                    if ($oldSubValue == $subValue) {
                        continue;
                    }

                    $changes[$subKey] = [
                        'before' => $oldSubValue,
                        'after'  => $subValue
                    ];
                }

                continue;
            }

            if ($key === 'order') {

                $beforeOrder = isset($before['order']) ? (array)$before['order'] : [];
                $afterOrder  = isset($after['order']) ? (array)$after['order'] : [];

                foreach ($afterOrder as $subKey => $subValue) {

                    $oldSubValue = $beforeOrder[$subKey] ?? null;

                    if ($oldSubValue == $subValue) {
                        continue;
                    }

                    $changes["order.$subKey"] = [
                        'before' => $oldSubValue,
                        'after'  => $subValue
                    ];
                }

                continue;
            }



            $oldValue = $before[$key] ?? null;

            if ($oldValue == $newValue) continue;

            if (is_array($newValue)) {

                if (!is_array($oldValue)) {
                    $oldValue = [];
                }

                $changes[$key] = self::compareArrays($oldValue, $newValue);
                continue;
            }

            $changes[$key] = [
                "before" => $oldValue,
                "after"  => $newValue,
            ];
        }

        return $changes;
    }

    //Comprobar cambios array
    private static function compareArrays($beforeArray, $afterArray)
    {
        // Convertir a array si son Collection
        if ($beforeArray instanceof \Illuminate\Support\Collection) {
            $beforeArray = $beforeArray->toArray();
        }

        if ($afterArray instanceof \Illuminate\Support\Collection) {
            $afterArray = $afterArray->toArray();
        }

        // Si los arrays son de objetos/arrays, convertirlos a JSON para compararlos
        $beforeJSON = array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $beforeArray);
        $afterJSON  = array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $afterArray);

        $addedJSON   = array_values(array_diff($afterJSON, $beforeJSON));
        $removedJSON = array_values(array_diff($beforeJSON, $afterJSON));

        return [
            "added"   => array_map(fn($v) => json_decode($v, true) ?? $v, $addedJSON),
            "removed" => array_map(fn($v) => json_decode($v, true) ?? $v, $removedJSON),
        ];
    }

    //Campos a ignorar
    private static $ignoreFields = [
        'contracts' => [

            'renewalDateTemporal',

            'isReminderOn',
            'reminderChanged',
            'lastUpdate',
            'updatedAt',
            'userInfo',
            'accountInfo',
            'createdAtTemporal',
            'lastStatus',
            'owner',
            'accountCIF',
            'accountEmail',
            'nameNormalized',
            'CUPSNormalized',
            'IBANNormalized',
            'productNormalized',
            'CIFNormalized',
            'agentFullName',
            'direcNormalized',
            'townNormalized',
            'provinceNormalized',
            'eventId',
            'consumptionData',
            'events',
            'pricesProduct',
            'activationDateTemporal',
            'latestStatusTemporal'
            ],
        'accounts' => [
            'createdBy',
            'orders',
            'events'
        ],
        'opportunities' => [
            'createdBy'
        ],
    ];

    // COMPROBADOR DE FACTURAS
    public static function invoiceChecker(array $payload, array $user)
    {
        $status = $payload['status'] ?? 'unknown';

        $event = match ($status) {
            'ok'    => 'check_ok',
            'error' => 'check_error',
            default => 'check_unknown',
        };

        self::save(
            type: 'invoice_checker',
            event: $event,
            user: $user,
            metadata: [
                'cups'   => $payload['cups'] ?? null,
                'period' => $payload['period'] ?? null,

                // ✅ SNAPSHOT COMPLETO (FACTURA + COMPROBACIÓN)
                'checked_data' => [
                    'invoice' => [
                        'potencias_contratadas' =>
                            $payload['checked_data']['invoice']['potencias_contratadas'] ?? null,

                        'energia_consumida' =>
                            $payload['checked_data']['invoice']['energia_consumida'] ?? null,

                        'precios_potencias' =>
                            $payload['checked_data']['invoice']['precios_potencias'] ?? null,

                        'precios_energia' =>
                            $payload['checked_data']['invoice']['precios_energia'] ?? null,
                    ],

                    'check' => [
                        // SIPS
                        'potencias_contratadas' =>
                            $payload['checked_data']['check']['potencias_contratadas'] ?? null,

                        'energia_consumida' =>
                            $payload['checked_data']['check']['energia_consumida'] ?? null,

                        // CONTRATO
                        'precios_potencias' =>
                            $payload['checked_data']['check']['precios_potencias'] ?? null,

                        'precios_energia' =>
                            $payload['checked_data']['check']['precios_energia'] ?? null,
                    ],
                ],

                // ℹ️ solo informativo
                'summary' => $payload['summary'] ?? null,

                'error' => $status === 'error'
                    ? ($payload['error'] ?? 'error desconocido')
                    : null,

                'ip' => request()->ip(),
            ]
        );
    }





}
