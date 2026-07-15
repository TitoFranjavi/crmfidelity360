<?php

namespace App\Http\Controllers;

use App\Http\Models\Account;
use App\Http\Models\Enterprise;
use App\Http\Models\Liquidation;
use App\Http\Models\Log;
use App\Http\Models\Order;
use App\Http\Models\Email;
use App\Http\Models\Marketer;
use App\Http\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;

use app\Helpers\UserHelper;
use Stripe\Stripe;

class HelpController extends Controller{


    public  function help() {
        $subdomains = User::where('label', 'Usuario subdominio')->where('_id', '!=', '65cb57489c2c285441086a43')->get();

        foreach ($subdomains as $subdomain) {
            $enterprise = Enterprise::where('_id', '67a32d1cdfbaaec2da6bf86e')->first();

            $commissions = $subdomain->commissions;

            foreach ($commissions as &$commission) {
                if ($commission['type'] === 'range')
                    $commission['value'] = $enterprise->commissionRanges[$commission['value'] -1]['_id'];
            }

            $subdomain->commissions = $commissions;

            $subdomain->save();

        }
        dd($subdomains);
    }

    public function helpwanchunchi()
    {
        $liquidations = Liquidation::all();

        foreach ($liquidations as $liquidation) {
            $cups = $liquidation->cups ?? [];

            $orders = array_map(fn($cup) => [
                'cups' => is_array($cup) ? (string)($cup['$id'] ?? $cup['oid'] ?? '') : (string)$cup,
            ], $cups);

            $liquidation->orders = $orders;
            $liquidation->createdBy = $liquidation->owner;
            $liquidation->status = $liquidation->liquidated ? 'paid' : 'draft';
            $liquidation->type = 'agent';
            $liquidation->totals = [
                'orders' => $liquidation->totalOrders ?? 0,
                'commissions' => $liquidation->totalCommission ?? 0,
            ];
            $liquidation->period = [
                'start' => $liquidation->dates['start'] ?? null,
                'end' => $liquidation->dates['end'] ?? null,
            ];
            $liquidation->payments = [];

            // Limpiar campos viejos
            $liquidation->save();

            // Limpiar campos viejos con query raw
            Liquidation::raw(function($collection) use ($liquidation) {
                $collection->updateOne(
                    ['_id' => new \MongoDB\BSON\ObjectId($liquidation->_id)],
                    ['$unset' => [
                        'cups' => '',
                        'owner' => '',
                        'liquidated' => '',
                        'totalOrders' => '',
                        'totalCommission' => '',
                    ]]
                );
            });

            $liquidation->save();
        }

        dd('Migration done', $liquidations->count() . ' liquidations migrated');
    }


    //syncEnterpriseStripeSubscription
    public function syncEnterpriseStripeSubscription()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $enterprise = Enterprise::find('6a26759957743add5220e9f6');

        if (!$enterprise) {
            return response()->json([
                'ok' => false,
                'message' => 'Empresa no encontrada',
            ], 404);
        }

        $stripeData = $enterprise->stripe ?? [];
        $stripeSubscriptionId = $stripeData['subscription_id'] ?? null;

        if (!$stripeSubscriptionId) {
            return response()->json([
                'ok' => false,
                'message' => 'La empresa no tiene subscription_id de Stripe guardado',
                'enterprise' => $enterprise->name ?? null,
            ], 422);
        }

        $stripeSubscription = \Stripe\Subscription::retrieve([
            'id' => $stripeSubscriptionId,
            'expand' => ['customer', 'latest_invoice'],
        ]);

        if (!$stripeSubscription || $stripeSubscription->status !== 'active') {
            return response()->json([
                'ok' => false,
                'message' => 'La suscripción de Stripe no está activa',
                'stripe_status' => $stripeSubscription->status ?? null,
            ], 422);
        }

        $customer = $stripeSubscription->customer;
        $priceId = $stripeSubscription->items->data[0]->price->id ?? null;

        $planId = null;
        $billing = null;
        $planConfig = null;

        foreach (config('plans') as $plan) {
            if (($plan['stripe']['monthly'] ?? null) === $priceId) {
                $planId = $plan['id'];
                $billing = 'monthly';
                $planConfig = $plan;
                break;
            }

            if (($plan['stripe']['annual'] ?? null) === $priceId) {
                $planId = $plan['id'];
                $billing = 'annual';
                $planConfig = $plan;
                break;
            }
        }

        if ($planId === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Price ID no encontrado en config/plans.php',
                'price_id' => $priceId,
            ], 422);
        }

        $invoice = $stripeSubscription->latest_invoice ?? null;

        $previousSubscription = $enterprise->subscription ?? [];

        $enterprise->stripe = [
            'customer_id' => $customer->id ?? ($stripeData['customer_id'] ?? null),
            'subscription_id' => $stripeSubscription->id,
            'status' => $stripeSubscription->status,
            'billing' => $billing,
            'plan_id' => $planId,
            'price_id' => $priceId,

            'current_period_start' => new \MongoDB\BSON\UTCDateTime($stripeSubscription->current_period_start * 1000),
            'current_period_end' => new \MongoDB\BSON\UTCDateTime($stripeSubscription->current_period_end * 1000),

            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ?? false,

            'last_invoice_id' => $invoice->id ?? null,
            'last_invoice_status' => $invoice->status ?? null,
            'last_invoice_url' => $invoice->hosted_invoice_url ?? null,
            'last_invoice_pdf' => $invoice->invoice_pdf ?? null,
            'last_invoice_created' => isset($invoice->created)
                ? new \MongoDB\BSON\UTCDateTime($invoice->created * 1000)
                : null,
            'last_invoice_amount_paid' => $invoice->amount_paid ?? 0,
            'last_invoice_amount_due' => $invoice->amount_due ?? 0,
            'last_invoice_amount_remaining' => $invoice->amount_remaining ?? 0,
            'last_invoice_currency' => $invoice->currency ?? 'eur',

            'next_payment_attempt' => isset($invoice->next_payment_attempt)
                ? new \MongoDB\BSON\UTCDateTime($invoice->next_payment_attempt * 1000)
                : null,

            'last_payment_failed_at' => $stripeData['last_payment_failed_at'] ?? null,
            'last_payment_paid_at' => now(),
            'canceled_at' => null,
            'ended_at' => null,
            'updatedAt' => now(),
        ];

        $enterprise->subscription = [
            'plan_id' => $planId,
            'billing' => $billing,
            'isAnnual' => $billing === 'annual',
            'startedAt' => $previousSubscription['startedAt']
                ?? new \MongoDB\BSON\UTCDateTime($stripeSubscription->current_period_start * 1000),

            'included' => [
                'users' => $planConfig['included']['users']['amount'] ?? null,
                'scans' => $planConfig['included']['scans']['amount'] ?? null,
                'monitoring' => $planConfig['included']['monitoring']['amount'] ?? null,
                'calls' => $planConfig['included']['calls']['amount'] ?? 0,
            ],

            // Conservo el uso anterior para no resetear escaneos/llamadas por accidente
            'usage' => $previousSubscription['usage'] ?? [
                    'scans' => 0,
                    'calls' => 0,
                ],

            // Conservo extras anteriores para no perder compras ya hechas
            'extras' => $previousSubscription['extras'] ?? [
                    'recurring' => [
                        'users' => [
                            'amount' => 0,
                            'monthly_price' => 0,
                            'items' => [],
                        ],
                        'monitoring' => [
                            'amount' => 0,
                            'monthly_price' => 0,
                            'items' => [],
                        ],
                    ],
                    'one_time' => [
                        'scans' => [
                            'amount' => 0,
                            'remaining' => 0,
                            'purchasedAt' => null,
                            'stripe_payment_intent_id' => null,
                        ],
                        'calls' => [
                            'amount' => 0,
                            'remaining' => 0,
                            'purchasedAt' => null,
                            'stripe_payment_intent_id' => null,
                        ],
                    ],
                ],

            'excesses' => $previousSubscription['excesses'] ?? [
                    'calls' => 0,
                ],

            'lastUsageResetAt' => $previousSubscription['lastUsageResetAt'] ?? now(),
            'extras_purchases' => $previousSubscription['extras_purchases'] ?? [],
        ];

        $enterprise->save();

        return response()->json([
            'ok' => true,
            'message' => 'Empresa sincronizada correctamente con Stripe',
            'enterprise' => [
                'id' => (string) $enterprise->_id,
                'name' => $enterprise->name ?? null,
                'email' => $enterprise->email ?? null,
            ],
            'stripe' => [
                'customer_id' => $enterprise->stripe['customer_id'] ?? null,
                'subscription_id' => $enterprise->stripe['subscription_id'] ?? null,
                'status' => $enterprise->stripe['status'] ?? null,
                'plan_id' => $enterprise->stripe['plan_id'] ?? null,
                'billing' => $enterprise->stripe['billing'] ?? null,
                'price_id' => $enterprise->stripe['price_id'] ?? null,
            ],
        ]);
    }

    public function help2()
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $subscriptions = \Stripe\Subscription::all([
            'status' => 'active',
            'limit' => 100,
            'expand' => ['data.customer', 'data.latest_invoice']
        ]);

        $synced = [];
        $skipped = [];

        foreach ($subscriptions->data as $stripeSubscription) {
            $customer = $stripeSubscription->customer;
            $customerEmail = $customer->email ?? null;

            if (!$customerEmail) {
                $skipped[] = [
                    'subscription_id' => $stripeSubscription->id,
                    'reason' => 'Customer sin email',
                ];
                continue;
            }

            $enterprise = Enterprise::where('email', $customerEmail)->first();

            if (!$enterprise) {
                $skipped[] = [
                    'email' => $customerEmail,
                    'reason' => 'Empresa no encontrada en CRM',
                ];
                continue;
            }

            $priceId = $stripeSubscription->items->data[0]->price->id ?? null;

            $planId = null;
            $billing = null;
            $planConfig = null;

            foreach (config('plans') as $plan) {
                if (($plan['stripe']['monthly'] ?? null) === $priceId) {
                    $planId = $plan['id'];
                    $billing = 'monthly';
                    $planConfig = $plan;
                    break;
                }

                if (($plan['stripe']['annual'] ?? null) === $priceId) {
                    $planId = $plan['id'];
                    $billing = 'annual';
                    $planConfig = $plan;
                    break;
                }
            }

            if ($planId === null) {
                $skipped[] = [
                    'email' => $customerEmail,
                    'price_id' => $priceId,
                    'reason' => 'Price ID no encontrado en plans.php',
                ];
                continue;
            }

            $invoice = $stripeSubscription->latest_invoice ?? null;

            $enterprise->stripe = [
                'customer_id' => $customer->id,
                'subscription_id' => $stripeSubscription->id,
                'status' => $stripeSubscription->status,
                'billing' => $billing,
                'plan_id' => $planId,
                'price_id' => $priceId,

                'current_period_start' => new \MongoDB\BSON\UTCDateTime($stripeSubscription->current_period_start * 1000),
                'current_period_end' => new \MongoDB\BSON\UTCDateTime($stripeSubscription->current_period_end * 1000),

                'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ?? false,

                'last_invoice_id' => $invoice->id ?? null,
                'last_invoice_status' => $invoice->status ?? null,
                'last_invoice_url' => $invoice->hosted_invoice_url ?? null,
                'last_invoice_pdf' => $invoice->invoice_pdf ?? null,
                'last_invoice_created' => isset($invoice->created)
                    ? new \MongoDB\BSON\UTCDateTime($invoice->created * 1000)
                    : null,
                'last_invoice_amount_paid' => $invoice->amount_paid ?? 0,
                'last_invoice_amount_due' => $invoice->amount_due ?? 0,
                'last_invoice_amount_remaining' => $invoice->amount_remaining ?? 0,
                'last_invoice_currency' => $invoice->currency ?? 'eur',

                'next_payment_attempt' => isset($invoice->next_payment_attempt)
                    ? new \MongoDB\BSON\UTCDateTime($invoice->next_payment_attempt * 1000)
                    : null,

                'last_payment_failed_at' => null,
                'last_payment_paid_at' => now(),
                'canceled_at' => null,
                'ended_at' => null,
                'updatedAt' => now(),
            ];

            $enterprise->subscription = [
                'plan_id' => $planId,
                'billing' => $billing,
                'isAnnual' => $billing === 'annual',
                'startedAt' => new \MongoDB\BSON\UTCDateTime($stripeSubscription->current_period_start * 1000),

                'included' => [
                    'users' => $planConfig['included']['users']['amount'] ?? null,
                    'scans' => $planConfig['included']['scans']['amount'] ?? null,
                    'monitoring' => $planConfig['included']['monitoring']['amount'] ?? null,
                    'calls' => $planConfig['included']['calls']['amount'] ?? 0,
                ],

                'usage' => [
                    'scans' => 0,
                    'calls' => 0,
                ],

                'extras' => [
                    'recurring' => [
                        'users' => [
                            'amount' => 0,
                            'monthly_price' => 0,
                            'items' => [],
                        ],
                        'monitoring' => [
                            'amount' => 0,
                            'monthly_price' => 0,
                            'items' => [],
                        ],
                    ],
                    'one_time' => [
                        'scans' => [
                            'amount' => 0,
                            'remaining' => 0,
                            'purchasedAt' => null,
                            'stripe_payment_intent_id' => null,
                        ],
                        'calls' => [
                            'amount' => 0,
                            'remaining' => 0,
                            'purchasedAt' => null,
                            'stripe_payment_intent_id' => null,
                        ],
                    ],
                ],

                'excesses' => [
                    'calls' => 0,
                ],

                'lastUsageResetAt' => now(),
                'extras_purchases' => [],
            ];

            $enterprise->save();

            $synced[] = [
                'enterprise' => $enterprise->name,
                'email' => $customerEmail,
                'subscription_id' => $stripeSubscription->id,
                'plan_id' => $planId,
                'billing' => $billing,
            ];
        }

        return response()->json([
            'synced' => $synced,
            'skipped' => $skipped,
        ]);
    }

    private static function resolveLabelFromPermissions(array $permissions): string
    {
        sort($permissions);
        $key = implode('|', $permissions);

        return match ($key) {
            '' => 'Usuario demo',

            'GESCON' => 'Administrador',
            'DRIVE' => 'Usuario Drive',
            'RESUS' => 'Comercial',

            'DRIVE|GESCON' => 'Jefe administrador',
            'DRIVE|RESUS' => 'Comercial',
            'GESCON|RESUS' => 'Desarrollador',

            'DRIVE|GESCON|RESUS' => 'Súper usuario',

            default => 'Usuario demo',
        };
    }


    public static function hierarchy($_id)
    {

        // Obtenemos todos los usuarios de una sola vez
        $allUsers = User::get()->makeHidden(['password', 'contactsArchived', 'accountsArchived', 'opportunitiesArchived', 'verification_code', 'remember_token']);

        // Creamos un array para almacenar los resultados
        $result = [];

        // Función recursiva interna que no necesita hacer consultas adicionales
        $findSubordinates = function ($userId) use (&$findSubordinates, $allUsers, &$result) {
            foreach ($allUsers as $user) {
                // Si este usuario tiene como responsable al usuario actual
                if (in_array($userId, $user['responsibles'])) {
                    // Solo lo añadimos si no está ya en los resultados
                    if (!isset($result[$user['_id']])) {
                        $result[$user['_id']] = $user->toArray();
                        // Buscamos recursivamente los subordinados de este usuario
                        $findSubordinates($user['_id']);
                    }
                }
            }
        };

        // Iniciamos la búsqueda con el ID proporcionado
        $findSubordinates($_id);

        return array_values($result);

    }


    public function helps()
    {
        /** @var \Jenssegers\Mongodb\Connection $conn */
        $conn = DB::connection('mongodb');
        $db = $conn->getMongoDB();

        $accountsCol = $db->selectCollection('accounts'); // MongoDB\Collection
        $ordersCol = $db->selectCollection('orders');   // MongoDB\Collection

        // Trae SOLO cuentas que tienen orders no vacíos (índice recomendado en accounts.orders si fuese grande)
        $cursor = $accountsCol->find(
            ['orders' => ['$exists' => true, '$ne' => []]],
            ['projection' => ['orders' => 1, 'usersIds' => 1, 'createdBy' => 1]]
        );

        $accCount = 0;
        $ordWritten = 0;
        $duplicatesFound = 0;  // Contador para IDs duplicados encontrados
        $BULK_CHUNK = 1000; // tamaño de lote óptimo para memoria y rendimiento
        $bulkOps = [];
        $processedOrderIds = []; // Array para rastrear IDs ya procesadas

        foreach ($cursor as $account) {
            $accCount++;

            // Normalizar orders -> array PHP
            $ordersRaw = $account['orders'] ?? [];
            if ($ordersRaw instanceof BSONArray) {
                $orders = $ordersRaw->getArrayCopy();
            } elseif ($ordersRaw instanceof \Traversable) {
                $orders = iterator_to_array($ordersRaw);
            } elseif (is_array($ordersRaw)) {
                $orders = $ordersRaw;
            } else {
                $orders = [];
            }
            if (!$orders) {
                // Aun así eliminamos el campo orders para dejar consistente la cuenta
                $accountsCol->updateOne(['_id' => $account['_id']], ['$unset' => ['orders' => 1]]);
                continue;
            }

            // _id de la cuenta como string
            $accountIdStr = (string) $account['_id'];

            $usersIds = isset($account['usersIds']) ? (array) $account['usersIds'] : [];
            $createdBy = $account['createdBy'] ?? null;

            foreach ($orders as $order) {
                if ($order instanceof BSONDocument) {
                    $order = $order->getArrayCopy();
                } elseif (!is_array($order)) {
                    $order = (array) $order;
                }

                // Asegurar _id del order
                if (empty($order['_id'])) {
                    $order['_id'] = new ObjectId();
                } elseif (is_string($order['_id']) && preg_match('/^[a-f0-9]{24}$/i', $order['_id'])) {
                    $order['_id'] = new ObjectId($order['_id']);
                }

                // Verificar si la ID ya ha sido procesada
                $orderIdStr = (string) $order['_id'];

                if (in_array($orderIdStr, $processedOrderIds)) {
                    // ID duplicada encontrada - generar una nueva ID
                    $duplicatesFound++;
                    $order['_id'] = new ObjectId(); // Generar nuevo ID


                    // Log para debug
                    \Log::info("ID duplicada encontrada y cambiada: {$orderIdStr} -> {$order['_id']} en cuenta: {$accountIdStr}");
                }

                // Registrar esta ID como procesada
                $processedOrderIds[] = (string) $order['_id'];

                // Enriquecer con datos de la cuenta y normalizar a string
                $order['account'] = $accountIdStr;   // <-- SIEMPRE string
                $order['usersIds'] = $usersIds;
                $order['createdBy'] = $createdBy;

                // Ya no necesitamos 'upsert' con opción true porque las IDs siempre serán únicas
                $bulkOps[] = [
                    'replaceOne' => [
                        ['_id' => $order['_id']],
                        $order,
                        ['upsert' => true],
                    ],
                ];

                // Volcado por lotes para controlar memoria
                if (count($bulkOps) >= $BULK_CHUNK) {
                    $ordersCol->bulkWrite($bulkOps, ['ordered' => false]);
                    $ordWritten += count($bulkOps);
                    $bulkOps = [];
                }
            }

            // Quitar orders embebidos de la cuenta (siempre, para dejarla limpia)
            $accountsCol->updateOne(
                ['_id' => $account['_id']],
                ['$unset' => ['orders' => 1]]
            );
        }

        // Volcar resto de operaciones pendientes
        if ($bulkOps) {
            $ordersCol->bulkWrite($bulkOps, ['ordered' => false]);
            $ordWritten += count($bulkOps);
            $bulkOps = [];
        }

        // Índice por account (string) para acelerar consultas
        try {
            $ordersCol->createIndex(['account' => 1]);
        } catch (\Throwable $e) {
            // Silenciar si ya existe u otro warning no crítico
        }

        // Reparación post‑migración:
        // Si existen orders con 'account' como ObjectId, convertirlos a string.
        // Se procesa en lotes para minimizar RAM.
        $cursorFix = $ordersCol->find(['account' => ['$type' => 7]], ['projection' => ['_id' => 1, 'account' => 1]]); // 7 = objectId
        $fixOps = [];
        $fixedCount = 0;

        foreach ($cursorFix as $ord) {
            $accObj = $ord['account'] ?? null;
            if ($accObj instanceof ObjectId) {
                $fixOps[] = [
                    'updateOne' => [
                        ['_id' => $ord['_id']],
                        ['$set' => ['account' => (string) $accObj]],
                    ],
                ];
            }
            if (count($fixOps) >= $BULK_CHUNK) {
                $ordersCol->bulkWrite($fixOps, ['ordered' => false]);
                $fixedCount += count($fixOps);
                $fixOps = [];
            }
        }
        if ($fixOps) {
            $ordersCol->bulkWrite($fixOps, ['ordered' => false]);
            $fixedCount += count($fixOps);
        }

        return response()->json([
            'status' => 'ok',
            'processed_accounts' => $accCount,
            'migrated_or_inserted' => $ordWritten,
            'duplicates_renamed' => $duplicatesFound,
            'fixed_account_oids' => $fixedCount,
            'message' => 'Orders extraídos/normalizados con account como string, orders eliminados de accounts, índice por account creado y conversión post‑migración aplicada.',
        ]);
    }


    public function help3()
    {

        Mail::mailer('mailgun')->raw('Hola Fran, esta es una prueba desde Mailgun usando Laravel.', function ($message) {
            $message->to('franperez@segenet.es')
                ->subject('Test de Mailgun');
        });

        dd('stoppie');

        $marketers = Marketer::all();

        foreach ($marketers as $marketer) {

            $products = $marketer->products;

            $electProducts = $products['gas'];

            foreach ($electProducts as &$electProduct) {

                $feeProduct = $electProduct['fees'];

                foreach ($feeProduct as &$fee) {
                    $fee['type'] = [
                        'pyme' => true,
                        'residencial' => true
                    ];
                }

                $electProduct['fees'] = $feeProduct;
            }

            $products['gas'] = $electProducts;

            $marketer->products = $products;

            // Asignar los productos modificados de gas de vuelta al modelo
            $marketer->save();
        }

        dd('finaliza');
    }


    public function setupNotificationEmail()
    {
        $enterprises = Enterprise::all();

        foreach ($enterprises as $enterprise) {

            // Solo si no existe el campo
            if (empty($enterprise->notification_email)) {
                $enterprise->notification_email = $enterprise->email;
                $enterprise->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'notification_email añadido correctamente'
        ]);
    }
}
