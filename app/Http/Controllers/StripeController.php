<?php

namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use App\Http\Models\Enterprise;
use App\Http\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;
use Stripe\Checkout\Session;
use Stripe\Invoice;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\SubscriptionSchedule;
use Stripe\Webhook;

class StripeController extends Controller
{

    //SUSCRIPCIÓN


    public function createSession(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $user = session()->get('userLogged');
        if (!$user)
            return response()->json(['error' => 'Usuario no autenticado'], 401);


        try {
            $enterprise = $request->input('enterprise');
            if (!$enterprise)
                return response()->json(['error' => 'Empresa no encontrada'], 404);


            $plans = config('plans');
            $plan = $request->input('plan');

            if (!isset($plans[$plan])) {
                return response()->json(['error' => 'Plan no válido'], 400);
            }

            $selectedPlan = $plans[$plan];
            $taxRatesId = $this->getStripeTaxRates();

            $isAnnual = filter_var($request->input('isAnnual'), FILTER_VALIDATE_BOOLEAN);

            $billing = $isAnnual ? 'annual' : 'monthly';
            $priceId = $selectedPlan['stripe'][$billing];

            $sessionData = [
                'mode' => 'subscription',

                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],

                'success_url' => env('APP_URL') . '/profile',
                'cancel_url' => env('APP_URL') . '/zoco-one',

                'client_reference_id' => (string) $user['id'],

                'metadata' => [
                    'user_id' => (string) $user['id'],
                    'enterprise_id' => (string) $enterprise['_id'],
                    'plan_id' => (string) $selectedPlan['id'],
                    'plan_name' => $selectedPlan['name'],
                    'billing' => $billing,
                ],

                'subscription_data' => [
                    'default_tax_rates' => $taxRatesId,
                    'metadata' => [
                        'user_id' => (string) $user['id'],
                        'enterprise_id' => (string) $enterprise['_id'],
                        'plan_id' => (string) $selectedPlan['id'],
                        'plan_name' => $selectedPlan['name'],
                        'billing' => $billing,
                    ],
                ],
            ];

            $customerId = data_get($enterprise, 'stripe.customer_id');

            if ($customerId) {
                $sessionData['customer'] = $customerId;
            } else {
                $sessionData['customer_email'] = $enterprise['email'] ?? $user['email'];
            }

            $session = Session::create($sessionData);

            return response()->json([
                'url' => $session->url,
                'session_id' => $session->id,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function successSession(Request $request){
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = session()->get('userLogged');


        try {
            $session = Session::retrieve(
                $request['sessionId'],
                ['expand' => ['subscription', 'customer']]
            );

            $customerId   = $session->customer;
            $subscription = $session->subscription;

            if(!isset($user['stripe'])){
                $user['stripe'] = ['customer' => $customerId, 'subscription' => $subscription];
                $user->save();
            }

            return response()->json(["customer" => $user['customerId'], "subscription" => $user['subscription']]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getSubscription(Request $request){
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = session()->get('userLogged');

        if($user['label'] === 'Usuario subdominio'){
            $subscription = Subscription::retrieve($user['stripe']['subscription']);
        }else{
            $subscription = null;
        }

        return response()->json($subscription);
    }

    public function getSubscriptionInvoice(Request $request){
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = session()->get('userLogged');

        $subscription = Subscription::retrieve($user['stripe']['subscription']);
        $latestInvoiceId = $subscription->latest_invoice;

        $invoice = Invoice::retrieve($latestInvoiceId);

        return response()->json($invoice->invoice_pdf);
    }

    public function updateSubscription(Request $request){
        $newStatus = $request['newStatus'];

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = session()->get('userLogged');

        try {
            $canceled = !($newStatus == 'resume');
            Subscription::update($user['stripe']['subscription'],[
                'cancel_at_period_end' => $canceled,
            ]);

            return response()->json(['message' => 'Suscripción pausada correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function changeSubscriptionInterval(Request $request){
        $newInterval = $request['interval'];

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $user = session()->get('userLogged');

        $prices = Price::all([
            'product' => env('STRIPE_PRODUCT_ID'),
            'active'  => true,
        ]);

        $priceId = $newInterval === 'year'
            ? collect($prices->data)->first(fn($p)=> $p->recurring->interval==='year' && $p->recurring->interval_count === 1)->id
            : collect($prices->data)->first(fn($p)=> $p->recurring->interval==='month')->id;

        try{
            $subscription = Subscription::retrieve($user['stripe']['subscription']);

            //Si no tiene cambio programado, crearlo
            if(!$subscription->schedule){
                $schedule = SubscriptionSchedule::create([
                    'from_subscription' => $user['stripe']['subscription'],
                ]);

                SubscriptionSchedule::update($schedule->id, [
                    'phases' => [
                        [
                            'items' => [['price' => $subscription->plan->id]],
                            'start_date' => $subscription->billing_cycle_anchor,
                            'iterations' => 1,
                        ],
                        [
                            'items' => [['price' => $priceId]],
                        ],
                    ],
                ]);

                //Si ya tiene un cambio programado, borrarlo
            }else{
                SubscriptionSchedule::cancel($subscription->schedule->id);
            }

        }catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    //Acciones suscripción
    public function createPortalSession(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $userId = auth()->id();
        $userSubdomain = UserHelper::getUserSubdomain($userId);

        if (!$userSubdomain || !isset($userSubdomain['_id'])) {
            return response()->json([
                'message' => 'No se encontró el subdominio del usuario.',
            ], 404);
        }

        $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

        if (!$enterprise || empty($enterprise->stripe['customer_id'])) {
            return response()->json([
                'message' => 'No se encontró el cliente de Stripe.',
            ], 404);
        }

        $portalSession = \Stripe\BillingPortal\Session::create([
            'customer' => $enterprise->stripe['customer_id'],
            'return_url' => url('/profile'),
        ]);

        return response()->json([
            'url' => $portalSession->url,
        ]);
    }



    //Extras

        //Pasar extras a front
        public function getExtras()
        {
            return response()->json([
                'extras' => $this->hidePrivateExtraFields(config('extras')),
            ]);
        }
        private function hidePrivateExtraFields(array $items): array
    {
        foreach ($items as $key => $value) {
            if ($key === 'stripe_price_id') {
                unset($items[$key]);
                continue;
            }

            if (is_array($value)) {
                $items[$key] = $this->hidePrivateExtraFields($value);
            }
        }

        return $items;
    }


        // Comprar extra puntual
        public function createOneTimeExtraCheckout(Request $request)
        {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $user = session()->get('userLogged');
            $taxRatesId = $this->getStripeTaxRates();


            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            try {
                $category = $request->input('category');
                $type = $request->input('type');
                $quantity = (int) $request->input('quantity', 1);

                if ($quantity < 1) {
                    return response()->json(['error' => 'Cantidad no válida'], 400);
                }

                $extras = config("extras.one_time.$category");

                if (!$extras || !isset($extras[$type])) {
                    return response()->json(['error' => 'Extra no válido'], 400);
                }

                $extra = $extras[$type];

                $userSubdomain = UserHelper::getUserSubdomain($user['id']);

                if (!$userSubdomain || !isset($userSubdomain['_id'])) {
                    return response()->json(['error' => 'No se encontró el subdominio del usuario'], 404);
                }

                $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

                if (!$enterprise) {
                    return response()->json(['error' => 'Empresa no encontrada'], 404);
                }

                $customerId = $enterprise->stripe['customer_id'] ?? null;

                $sessionData = [
                    'mode' => 'payment',

                    'line_items' => [[
                        'price' => $extra['stripe_price_id'],
                        'quantity' => $quantity,
                        'tax_rates' => $taxRatesId
                    ]],

                    'success_url' => env('APP_URL') . '/profile?extra=success',
                    'cancel_url' => env('APP_URL') . '/profile?extra=cancel',

                    'client_reference_id' => (string) $user['id'],

                    'metadata' => [
                        'type' => 'one_time_extra',

                        'category' => $category,
                        'extra_type' => $type,

                        'amount' => (string) $extra['amount'],
                        'quantity' => (string) $quantity,

                        'user_id' => (string) $user['id'],
                    ],
                ];

                if ($customerId) {
                    $sessionData['customer'] = $customerId;
                } else {
                    $sessionData['customer_email'] = $user['email'];
                }

                $session = Session::create($sessionData);

                return response()->json([
                    'url' => $session->url,
                    'session_id' => $session->id,
                ]);

            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
        private function handleOneTimeExtra($session): void
    {
        $category = $session->metadata->category ?? null;
        $type = $session->metadata->extra_type ?? null;
        $quantity = (int) ($session->metadata->quantity ?? 1);
        $userId = $session->metadata->user_id ?? null;

        $extras = config("extras.one_time.$category");

        if (!$extras || !isset($extras[$type])) {
            throw new \Exception('Extra one-time inválido');
        }

        if (!$userId) {
            throw new \Exception('Extra one-time sin user_id');
        }

        if ($quantity < 1) {
            throw new \Exception('Cantidad inválida en extra one-time');
        }

        $extra = $extras[$type];
        $totalAmount = ((int) $extra['amount']) * $quantity;
        $pricePaid = ((float) ($extra['price'] ?? 0)) * $quantity;
        $currency = $session->currency ?? 'eur';
        $purchaseDate = new \MongoDB\BSON\UTCDateTime();

        $userSubdomain = UserHelper::getUserSubdomain($userId);

        if (!$userSubdomain || !isset($userSubdomain['_id'])) {
            throw new \Exception('No se encontró subdominio para user_id: ' . $userId);
        }

        $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

        if (!$enterprise) {
            throw new \Exception('Empresa no encontrada');
        }

        $receiptUrl = null;
        $chargeId = null;

        if (!empty($session->payment_intent)) {
            try {
                Stripe::setApiKey(env('STRIPE_SECRET'));

                $charges = \Stripe\Charge::all([
                    'payment_intent' => $session->payment_intent,
                    'limit' => 1,
                ]);

                $charge = $charges->data[0] ?? null;

                if ($charge) {
                    $chargeId = $charge->id ?? null;
                    $receiptUrl = $charge->receipt_url ?? null;
                }

            } catch (\Throwable $e) {
                Log::warning('No se pudo recuperar el recibo del extra one-time', [
                    'payment_intent' => $session->payment_intent,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $subscription = $enterprise->subscription ?? [];

        if (!isset($subscription['extras']['one_time'][$category])) {
            throw new \Exception('Categoría extra no encontrada: ' . $category);
        }

        $current = $subscription['extras']['one_time'][$category];

        $current['amount'] = ($current['amount'] ?? 0) + $totalAmount;
        $current['remaining'] = ($current['remaining'] ?? 0) + $totalAmount;
        $current['purchasedAt'] = $purchaseDate;
        $current['stripe_payment_intent_id'] = $session->payment_intent ?? null;

        $subscription['extras']['one_time'][$category] = $current;

        //Regularización excesos
        if ($category === 'calls') {
            $subscription = $this->regularizeCallExcesses($subscription, $totalAmount, 'extras');
        }

        if (!isset($subscription['extras_purchases']) || !is_array($subscription['extras_purchases'])) {
            $subscription['extras_purchases'] = [];
        }

        $subscription['extras_purchases'][] = [
            'category' => $category,
            'type' => $type,
            'title' => $extra['title'] ?? null,
            'amount' => $totalAmount,
            'quantity' => $quantity,
            'unit_amount' => $extra['amount'] ?? null,
            'price_paid' => $pricePaid,
            'currency' => $currency,
            'stripe_checkout_session_id' => $session->id ?? null,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'stripe_charge_id' => $chargeId,
            'receipt_url' => $receiptUrl,
            'purchasedAt' => $purchaseDate,
        ];

        $enterprise->subscription = $subscription;
        $enterprise->save();


        //Notifico
        $this->notificateStripeAction(
            'Extra puntual CRM comprado',
            'Extra puntual comprado',
            'Se ha comprado un extra puntual en el CRM.',
            [
                'Empresa' => $enterprise->name,
                'Extra' => $extra['title'] ?? $type,
                'Categoría' => $category,
                'Cantidad añadida' => $totalAmount,
                'Cantidad comprada' => $quantity,
                'Importe pagado' => $pricePaid . ' ' . strtoupper($currency),
            ],
            'Ver recibo',
            $receiptUrl
        );
    }


        // Comprar extra mensual/recurrente
        public function addRecurringExtra(Request $request)
        {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $user = session()->get('userLogged');
            $taxRatesId = $this->getStripeTaxRates();

            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            try {
                $category = $request->input('category'); // users | monitoring
                $type = $request->input('type'); // pack_30 | pack_100 | pack_50...
                $quantity = (int) $request->input('quantity', 1);

                if ($quantity < 1) {
                    return response()->json(['error' => 'Cantidad no válida'], 400);
                }

                if (!in_array($category, ['users', 'monitoring'])) {
                    return response()->json(['error' => 'Categoría recurrente no válida'], 400);
                }

                $extras = config("extras.recurring.$category");

                if (!$extras || !isset($extras[$type])) {
                    return response()->json(['error' => 'Extra recurrente no válido'], 400);
                }

                $extra = $extras[$type];

                if (empty($extra['stripe_price_id'])) {
                    return response()->json(['error' => 'El extra no tiene stripe_price_id configurado'], 400);
                }

                $userSubdomain = UserHelper::getUserSubdomain($user['id']);

                if (!$userSubdomain || !isset($userSubdomain['_id'])) {
                    return response()->json(['error' => 'No se encontró el subdominio del usuario'], 404);
                }

                $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

                if (!$enterprise) {
                    return response()->json(['error' => 'Empresa no encontrada'], 404);
                }

                $subscription = $enterprise->subscription ?? [];
                $stripe = $enterprise->stripe ?? [];

                $mainSubscriptionId = $stripe['subscription_id'] ?? null;
                $mainStripeStatus = $stripe['status'] ?? null;
                $customerId = $stripe['customer_id'] ?? null;

                if (!$mainSubscriptionId) {
                    return response()->json(['error' => 'La empresa no tiene una suscripción principal activa en Stripe'], 400);
                }

                if (!in_array($mainStripeStatus, ['active', 'trialing'])) {
                    return response()->json(['error' => 'La suscripción principal no está activa'], 400);
                }

                if (!$customerId) {
                    return response()->json(['error' => 'La empresa no tiene customer_id de Stripe'], 400);
                }

                /*
                 * Si el plan principal es mensual:
                 * - El extra se añade a la suscripción principal.
                 *
                 * Si el plan principal es anual:
                 * - El extra se añade a una suscripción mensual separada de extras.
                 */
                $billing = $subscription['billing'] ?? 'monthly';
                $isAnnualPlan = $billing === 'annual';

                $priceId = $extra['stripe_price_id'];
                $quantityToAdd = 1;
                $taxRatesId = $this->getStripeTaxRates();

                $stripeSubscriptionId = null;
                $createdInitialSubscriptionItem = null;

                if ($isAnnualPlan) {
                    $stripeSubscriptionId = $stripe['extras_subscription_id'] ?? null;

                    if (!$stripeSubscriptionId) {
                        $mainStripeSubscription = \Stripe\Subscription::retrieve($mainSubscriptionId);

                        $defaultPaymentMethod = null;

                        // 1. Intentar coger el métod de pago de la suscripción principal
                        if (!empty($mainStripeSubscription->default_payment_method)) {
                            $defaultPaymentMethod = is_string($mainStripeSubscription->default_payment_method)
                                ? $mainStripeSubscription->default_payment_method
                                : $mainStripeSubscription->default_payment_method->id;
                        }

                        // 2. Si no está en la suscripción, intentar cogerlo del customer
                        if (!$defaultPaymentMethod) {
                            $customer = \Stripe\Customer::retrieve($customerId);

                            if (!empty($customer->invoice_settings->default_payment_method)) {
                                $defaultPaymentMethod = is_string($customer->invoice_settings->default_payment_method)
                                    ? $customer->invoice_settings->default_payment_method
                                    : $customer->invoice_settings->default_payment_method->id;
                            }
                        }

                        // 3. Si no hay método de pago, no se puede crear la suscripción de extras
                        if (!$defaultPaymentMethod) {
                            return response()->json([
                                'error' => 'No se encontró un método de pago por defecto para crear la suscripción mensual de extras.',
                                'message' => 'Debe configurar un método de pago antes de contratar extras mensuales.',
                            ], 400);
                        }

                        $extrasSubscription = \Stripe\Subscription::create([
                            'customer' => $customerId,

                            'items' => [[
                                'price' => $priceId,
                                'quantity' => $quantityToAdd,
                                'tax_rates' => $taxRatesId
                            ]],

                            'default_payment_method' => $defaultPaymentMethod,

                            'metadata' => [
                                'type' => 'extras_subscription',
                                'enterprise_id' => (string) $enterprise->_id,
                                'main_subscription_id' => $mainSubscriptionId,
                            ],
                        ]);

                        $stripeSubscriptionId = $extrasSubscription->id;
                        $createdInitialSubscriptionItem = $extrasSubscription->items->data[0] ?? null;

                        $stripe['extras_subscription_id'] = $extrasSubscription->id;
                        $stripe['extras_status'] = $extrasSubscription->status ?? null;

                        $stripe['extras_current_period_start'] = !empty($createdInitialSubscriptionItem->current_period_start)
                            ? new \MongoDB\BSON\UTCDateTime($createdInitialSubscriptionItem->current_period_start * 1000)
                            : null;

                        $stripe['extras_current_period_end'] = !empty($createdInitialSubscriptionItem->current_period_end)
                            ? new \MongoDB\BSON\UTCDateTime($createdInitialSubscriptionItem->current_period_end * 1000)
                            : null;

                        $stripe['extras_updatedAt'] = new \MongoDB\BSON\UTCDateTime();
                    }
                } else {
                    $stripeSubscriptionId = $mainSubscriptionId;
                }

                if (!$stripeSubscriptionId) {
                    return response()->json(['error' => 'No se pudo determinar la suscripción donde añadir el extra'], 400);
                }

                // Preparo estructura de extras
                if (!isset($subscription['extras'])) {
                    $subscription['extras'] = [];
                }

                if (!isset($subscription['extras']['recurring'])) {
                    $subscription['extras']['recurring'] = [];
                }

                if (!isset($subscription['extras']['recurring'][$category])) {
                    $subscription['extras']['recurring'][$category] = [
                        'amount' => 0,
                        'monthly_price' => 0,
                        'items' => [],
                    ];
                }

                if (
                    !isset($subscription['extras']['recurring'][$category]['items']) ||
                    !is_array($subscription['extras']['recurring'][$category]['items'])
                ) {
                    $subscription['extras']['recurring'][$category]['items'] = [];
                }

                $items = $subscription['extras']['recurring'][$category]['items'];

                /*
                 * Caso especial:
                 * Si acabamos de crear la suscripción mensual de extras,
                 * Stripe ya ha creado el primer SubscriptionItem.
                 * No hay que crearlo otra vez.
                 */
                if ($createdInitialSubscriptionItem) {
                    $item = [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'category' => $category,
                        'type' => $type,
                        'title' => $extra['title'] ?? $type,
                        'amount' => ((int) ($extra['amount'] ?? 0)) * $quantityToAdd,
                        'quantity' => $quantityToAdd,
                        'unit_price' => (float) ($extra['price'] ?? 0),
                        'total_price' => ((float) ($extra['price'] ?? 0)) * $quantityToAdd,
                        'stripe_price_id' => $priceId,
                        'stripe_subscription_id' => $stripeSubscriptionId,
                        'stripe_subscription_item_id' => $createdInitialSubscriptionItem->id,
                        'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                        'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
                        'active' => true,
                    ];

                    $items[] = $item;

                } else {
                    $existingItemIndex = collect($items)->search(function ($item) use ($priceId, $stripeSubscriptionId) {
                        return ($item['stripe_price_id'] ?? null) === $priceId
                            && ($item['stripe_subscription_id'] ?? null) === $stripeSubscriptionId
                            && ($item['active'] ?? true) === true
                            && !($item['cancel_at_period_end'] ?? false);
                    });

                    /*
                     * Si no lo encontramos en BBDD, lo buscamos también en Stripe.
                     * Esto evita el error cuando Stripe ya tiene el price_id pero la BBDD no lo refleja bien.
                     */
                    $stripeExistingSubscriptionItem = null;

                    if ($existingItemIndex === false) {
                        $stripeSubscription = \Stripe\Subscription::retrieve($stripeSubscriptionId);

                        foreach ($stripeSubscription->items->data as $stripeItem) {
                            if (($stripeItem->price->id ?? null) === $priceId) {
                                $stripeExistingSubscriptionItem = $stripeItem;
                                break;
                            }
                        }
                    }

                    if ($existingItemIndex !== false) {
                        /*
                         * Existe en nuestra BBDD.
                         * Actualizamos quantity en Stripe y en BBDD.
                         */
                        $existingItem = $items[$existingItemIndex];

                        $currentQuantity = (int) ($existingItem['quantity'] ?? 1);
                        $newQuantity = $currentQuantity + $quantityToAdd;

                        $subscriptionItemId = $existingItem['stripe_subscription_item_id'] ?? null;

                        if (!$subscriptionItemId) {
                            return response()->json([
                                'error' => 'El extra existe en BBDD pero no tiene stripe_subscription_item_id.',
                            ], 400);
                        }

                        $subscriptionItem = \Stripe\SubscriptionItem::update($subscriptionItemId, [
                            'quantity' => $newQuantity,
                            'proration_behavior' => 'create_prorations',
                        ]);

                        $items[$existingItemIndex]['quantity'] = $newQuantity;
                        $items[$existingItemIndex]['amount'] = ((int) ($extra['amount'] ?? 0)) * $newQuantity;
                        $items[$existingItemIndex]['unit_price'] = (float) ($extra['price'] ?? 0);
                        $items[$existingItemIndex]['total_price'] = ((float) ($extra['price'] ?? 0)) * $newQuantity;
                        $items[$existingItemIndex]['stripe_subscription_id'] = $stripeSubscriptionId;
                        $items[$existingItemIndex]['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

                        $item = $items[$existingItemIndex];

                    } elseif ($stripeExistingSubscriptionItem) {
                        /*
                         * Existe en Stripe pero no en nuestra BBDD.
                         * Actualizamos quantity en Stripe y creamos/normalizamos el item en BBDD.
                         */
                        $currentQuantity = (int) ($stripeExistingSubscriptionItem->quantity ?? 1);
                        $newQuantity = $currentQuantity + $quantityToAdd;

                        $subscriptionItem = \Stripe\SubscriptionItem::update($stripeExistingSubscriptionItem->id, [
                            'quantity' => $newQuantity,
                            'proration_behavior' => 'create_prorations',
                        ]);

                        $item = [
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'category' => $category,
                            'type' => $type,
                            'title' => $extra['title'] ?? $type,
                            'amount' => ((int) ($extra['amount'] ?? 0)) * $newQuantity,
                            'quantity' => $newQuantity,
                            'unit_price' => (float) ($extra['price'] ?? 0),
                            'total_price' => ((float) ($extra['price'] ?? 0)) * $newQuantity,
                            'stripe_price_id' => $priceId,
                            'stripe_subscription_id' => $stripeSubscriptionId,
                            'stripe_subscription_item_id' => $stripeExistingSubscriptionItem->id,
                            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                            'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
                            'active' => true,
                        ];

                        $items[] = $item;

                    } else {
                        /*
                         * No existe ni en BBDD ni en Stripe.
                         * Creamos SubscriptionItem nuevo.
                         */
                        $subscriptionItem = \Stripe\SubscriptionItem::create([
                            'subscription' => $stripeSubscriptionId,
                            'price' => $priceId,
                            'quantity' => $quantityToAdd,
                            'tax_rates' => $this->getStripeTaxRates(),
                            'proration_behavior' => 'create_prorations',
                        ]);

                        $item = [
                            'id' => (string) \Illuminate\Support\Str::uuid(),
                            'category' => $category,
                            'type' => $type,
                            'title' => $extra['title'] ?? $type,
                            'amount' => ((int) ($extra['amount'] ?? 0)) * $quantityToAdd,
                            'quantity' => $quantityToAdd,
                            'unit_price' => (float) ($extra['price'] ?? 0),
                            'total_price' => ((float) ($extra['price'] ?? 0)) * $quantityToAdd,
                            'stripe_price_id' => $priceId,
                            'stripe_subscription_id' => $stripeSubscriptionId,
                            'stripe_subscription_item_id' => $subscriptionItem->id,
                            'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                            'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
                            'active' => true,
                        ];

                        $items[] = $item;
                    }
                }

                $items = array_values($items);

                $subscription['extras']['recurring'][$category]['items'] = $items;
                $subscription['extras']['recurring'][$category]['amount'] = collect($items)->sum('amount');
                $subscription['extras']['recurring'][$category]['monthly_price'] = collect($items)->sum('total_price');

                $enterprise->subscription = $subscription;
                $enterprise->stripe = $stripe;
                $enterprise->save();


                //Notifico
                $this->notificateStripeAction(
                    'Extra recurrente CRM añadido',
                    'Extra recurrente añadido',
                    'Se ha añadido un extra recurrente a una suscripción del CRM.',
                    [
                        'Empresa' => $enterprise->name,
                        'Categoría' => $category,
                        'Extra' => $extra['title'] ?? $type,
                        'Cantidad añadida' => $item['amount'] ?? 0,
                        'Precio mensual' => ($item['total_price'] ?? 0) . ' €',
                        'Suscripción Stripe' => $item['stripe_subscription_id'] ?? '-',
                    ]
                );

                return response()->json([
                    'message' => 'Extra recurrente añadido correctamente',
                    'category' => $category,
                    'type' => $type,
                    'item' => $item,
                    'extra' => $subscription['extras']['recurring'][$category],
                ]);

            } catch (\Exception $e) {
                Log::error('Error añadiendo extra recurrente', [
                    'message' => $e->getMessage(),
                    'category' => $request->input('category'),
                    'type' => $request->input('type'),
                ]);

                return response()->json([
                    'error' => 'No se pudo añadir el extra recurrente',
                    'message' => $e->getMessage(),
                ], 500);
            }
        }
        public function cancelRecurringExtra(Request $request)
        {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $user = session()->get('userLogged');

            if (!$user) {
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            try {
                $category = $request->input('category'); // users | monitoring
                $itemId = $request->input('item_id');
                $quantityToCancel = (int) $request->input('quantity', 1);

                if ($quantityToCancel < 1) {
                    return response()->json(['error' => 'Cantidad a cancelar no válida'], 400);
                }
                if (!in_array($category, ['users', 'monitoring'])) {
                    return response()->json(['error' => 'Categoría recurrente no válida'], 400);
                }

                if (!$itemId) {
                    return response()->json(['error' => 'Falta el item_id del extra'], 400);
                }

                $userSubdomain = UserHelper::getUserSubdomain($user['id']);

                if (!$userSubdomain || !isset($userSubdomain['_id'])) {
                    return response()->json(['error' => 'No se encontró el subdominio del usuario'], 404);
                }

                $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

                if (!$enterprise) {
                    return response()->json(['error' => 'Empresa no encontrada'], 404);
                }

                $subscription = $enterprise->subscription ?? [];

                $items = data_get($subscription, "extras.recurring.$category.items", []);

                if (!is_array($items) || !count($items)) {
                    return response()->json(['error' => 'No hay extras recurrentes activos para esta categoría'], 404);
                }

                $itemIndex = collect($items)->search(function ($item) use ($itemId) {
                    return ($item['id'] ?? null) === $itemId;
                });

                if ($itemIndex === false) {
                    return response()->json(['error' => 'Extra recurrente no encontrado'], 404);
                }

                $item = $items[$itemIndex];

                $currentQuantity = (int) ($item['quantity'] ?? 1);
                $currentQuantity = max($currentQuantity, 1);

                $unitAmount = $currentQuantity > 0
                    ? ((int) ($item['amount'] ?? 0)) / $currentQuantity
                    : (int) ($item['amount'] ?? 0);

                $unitPrice = $currentQuantity > 0
                    ? ((float) ($item['total_price'] ?? 0)) / $currentQuantity
                    : (float) ($item['total_price'] ?? 0);

                if ($quantityToCancel > $currentQuantity) {
                    return response()->json([
                        'error' => 'No puede cancelar más packs de los que tiene contratados.',
                    ], 400);
                }

                $amountToRemove = (int) ($unitAmount * $quantityToCancel);
                /*
                 * Validación de límites antes de cancelar
                 */
                if ($category === 'users') {
                    $includedUsers = data_get($subscription, 'included.users');

                    if ($includedUsers !== null) {
                        $currentUsers = count(UserHelper::getSubdomainUserList($userSubdomain));

                        $currentExtraUsers = data_get($subscription, 'extras.recurring.users.amount', 0);
                        $extraUsersAfterCancel = max($currentExtraUsers - $amountToRemove, 0);

                        $limitAfterCancel = $includedUsers + $extraUsersAfterCancel;

                        if ($currentUsers > $limitAfterCancel) {
                            return response()->json([
                                'error' => 'No puede cancelar este extra porque superaría el límite de usuarios permitido.',
                                'current_users' => $currentUsers,
                                'limit_after_cancel' => $limitAfterCancel,
                                'users_to_remove' => $currentUsers - $limitAfterCancel,
                            ], 422);
                        }
                    }
                }

                if ($category === 'monitoring') {
                    $includedMonitoring = data_get($subscription, 'included.monitoring');

                    if ($includedMonitoring !== null) {
                        $currentMonitoring = count($enterprise->monitoredCups ?? []);

                        $currentExtraMonitoring = data_get($subscription, 'extras.recurring.monitoring.amount', 0);
                        $extraMonitoringAfterCancel = max($currentExtraMonitoring - $amountToRemove, 0);

                        $limitAfterCancel = $includedMonitoring + $extraMonitoringAfterCancel;

                        if ($currentMonitoring > $limitAfterCancel) {
                            return response()->json([
                                'error' => 'No puede cancelar este extra porque superaría el límite de monitorizaciones permitido.',
                                'current_monitoring' => $currentMonitoring,
                                'limit_after_cancel' => $limitAfterCancel,
                                'monitoring_to_remove' => $currentMonitoring - $limitAfterCancel,
                            ], 422);
                        }
                    }
                }

                $subscriptionItemId = $item['stripe_subscription_item_id'] ?? null;

                if (!$subscriptionItemId) {
                    return response()->json([
                        'error' => 'El extra no tiene stripe_subscription_item_id.',
                    ], 400);
                }

                /*
                 * Si quantity > 1, solo bajamos quantity.
                 * Si quantity === 1, eliminamos el subscription item.
                 */
                $newQuantity = $currentQuantity - $quantityToCancel;

                if ($newQuantity > 0) {
                    \Stripe\SubscriptionItem::update($subscriptionItemId, [
                        'quantity' => $newQuantity,
                        'proration_behavior' => 'none',
                    ]);

                    $items[$itemIndex]['quantity'] = $newQuantity;
                    $items[$itemIndex]['amount'] = (int) ($unitAmount * $newQuantity);
                    $items[$itemIndex]['unit_price'] = $unitPrice;
                    $items[$itemIndex]['total_price'] = $unitPrice * $newQuantity;
                    $items[$itemIndex]['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

                    $updatedItem = $items[$itemIndex];

                } else {
                    $subscriptionItem = \Stripe\SubscriptionItem::retrieve($subscriptionItemId);

                    $subscriptionItem->delete([
                        'proration_behavior' => 'none',
                    ]);

                    unset($items[$itemIndex]);
                    $items = array_values($items);

                    $updatedItem = null;
                }

                $items = array_values($items);

                data_set($subscription, "extras.recurring.$category.items", $items);
                data_set($subscription, "extras.recurring.$category.amount", collect($items)->sum('amount'));
                data_set($subscription, "extras.recurring.$category.monthly_price", collect($items)->sum('total_price'));

                $enterprise->subscription = $subscription;
                $enterprise->save();

                return response()->json([
                    'message' => 'Extra recurrente cancelado correctamente',
                    'category' => $category,
                    'item' => $updatedItem,
                    'extra' => data_get($subscription, "extras.recurring.$category"),
                ]);

            } catch (\Exception $e) {
                Log::error('Error cancelando extra recurrente', [
                    'message' => $e->getMessage(),
                    'category' => $request->input('category'),
                    'item_id' => $request->input('item_id'),
                ]);

                return response()->json([
                    'error' => 'No se pudo cancelar el extra recurrente',
                    'message' => $e->getMessage(),
                ], 500);
            }
        }



    //CARGADOR ELÉCTRICO

    public function createElectricChargerBudgetCheckout(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $customer = $request->input('customer', []);
            $summary = $request->input('summary', []);
            $charger = $request->input('charger', []);
            $installation = $request->input('installation', []);
            // % de pago inicial a la aceptación (p.ej. 0.60 = 60%). 0 = pago completo.
            $depositPercentage = (float) $request->input('deposit_percentage', 0);

            $installationTypeLabels = [
                'house' => 'Casa / vivienda',
                'community' => 'Garaje comunitario'
            ];
            $installationType = $installation['type'] ?? '';
            $installationTypeLabel = $installationTypeLabels[$installationType] ?? $installationType;

            $email = $customer['email'] ?? null;

            if (!$email) {
                return response()->json([
                    'error' => 'Falta el email del cliente.',
                ], 400);
            }

            $taxRatesId = $this->getStripeTaxRates();


            $MARKUP = 1; 

            $items = [
                [
                    'key'    => 'charger',
                    'name'   => $charger['name'] ?? 'Cargador eléctrico',
                    'amount' => round((float)($summary['chargerSubtotal'] ?? 0) * $MARKUP, 2),
                ],
                [
                    'key'    => 'labor',
                    'name'   => 'Mano de obra y desplazamiento',
                    'amount' => round((float)($summary['laborSubtotal'] ?? 0) * $MARKUP, 2),
                ],
                [
                    'key'    => 'cable',
                    'name'   => 'Cableado eléctrico',
                    'amount' => round((float)($summary['cablesSubtotal'] ?? $summary['cableSubtotal'] ?? 0) * $MARKUP, 2),
                ],
                [
                    'key'    => 'tube',
                    'name'   => 'Tubo / manguera protectora',
                    'amount' => round((float)($summary['tubeSubtotal'] ?? 0) * $MARKUP, 2),
                ],
                [
                    'key'    => 'cert',
                    'name'   => 'Boletín / certificado y legalización',
                    'amount' => round((float)($summary['certSubtotal'] ?? 0) * $MARKUP, 2),
                ],
                [
                    'key'    => 'surplus',
                    'name'   => 'Optimización excedentes fotovoltaicos',
                    'amount' => round((float)($summary['surplusSubtotal'] ?? 0) * $MARKUP, 2),
                ],
                [
                    'key'    => 'civilWork',
                    'name'   => 'Obra civil',
                    'amount' => round((float)($summary['civilWorkSubtotal'] ?? 0) * $MARKUP, 2),
                ],
                [
                    'key'    => 'optional',
                    'name'   => 'Opcionales',
                    'amount' => round((float)($summary['optionalSubtotal'] ?? 0) * $MARKUP, 2),
                ],
            ];

            $items = array_values(array_filter($items, fn($item) => $item['amount'] > 0));

            if (!count($items)) {
                return response()->json(['error' => 'El presupuesto no tiene conceptos válidos.'], 400);
            }

            // Totales ya con el margen incluido
            $subtotal = round(collect($items)->sum('amount'), 2);
            $vat      = round($subtotal * 0.21, 2);
            $total    = round($subtotal + $vat, 2);

            $reference = 'PRES-CARGADOR-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5));

            $lineItems = collect($items)->map(function ($item) use ($taxRatesId) {
                $lineItem = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $item['name'],
                        ],
                        'unit_amount' => (int) round($item['amount'] * 100),
                    ],
                    'quantity' => 1,
                ];

                if (!empty($taxRatesId)) {
                    $lineItem['tax_rates'] = $taxRatesId;
                }

                return $lineItem;
            })->values()->toArray();

            // ── Pago inicial (depósito) ──────────────────────────────────
            // Si llega deposit_percentage (0–1) se cobra solo ese % del presupuesto.
            // El detalle se muestra con los importes COMPLETOS (IVA incluido, como en el
            // PDF) y el resto aplazado se descuenta al final con un cupón, de modo que el
            // cliente ve qué paga y solo abona ahora el pago inicial.
            $grossTotal      = round((float) ($summary['total'] ?? $total), 2);
            $isDeposit       = $depositPercentage > 0 && $depositPercentage < 1 && $grossTotal > 0;
            $depositPct      = (int) round($depositPercentage * 100);
            $depositAmount   = $isDeposit ? round($grossTotal * $depositPercentage, 2) : $grossTotal;
            $remainingAmount = round($grossTotal - $depositAmount, 2);

            $sessionDiscounts = [];

            if ($isDeposit) {
                // Desglose COMPLETO con el IVA ya incluido en cada importe (al 100%).
                $depositConcepts = [
                    ['name' => ($charger['name'] ?? '') !== '' ? $charger['name'] : 'Cargador eléctrico', 'base' => (float) ($summary['chargerSubtotal'] ?? 0)],
                    ['name' => 'Mano de obra y desplazamiento',         'base' => (float) ($summary['laborSubtotal'] ?? 0)],
                    ['name' => 'Cableado eléctrico',                    'base' => (float) ($summary['cablesSubtotal'] ?? $summary['cableSubtotal'] ?? 0)],
                    ['name' => 'Tubo / manguera protectora',            'base' => (float) ($summary['tubeSubtotal'] ?? 0)],
                    ['name' => 'Boletín / certificado y legalización',  'base' => (float) ($summary['certSubtotal'] ?? 0)],
                    ['name' => 'Optimización excedentes fotovoltaicos', 'base' => (float) ($summary['surplusSubtotal'] ?? 0)],
                    ['name' => 'Obra civil',                            'base' => (float) ($summary['civilWorkSubtotal'] ?? 0)],
                    ['name' => 'Opcionales',                            'base' => (float) ($summary['optionalSubtotal'] ?? 0)],
                ];

                $lineItems = collect($depositConcepts)
                    ->filter(fn($concept) => $concept['base'] > 0)
                    ->map(function ($concept) {
                        // Importe con el IVA (21%) ya incluido — sin tax_rates aparte.
                        return [
                            'price_data' => [
                                'currency'     => 'eur',
                                'product_data' => [
                                    'name' => $concept['name'],
                                ],
                                'unit_amount'  => (int) round($concept['base'] * 1.21 * 100),
                            ],
                            'quantity' => 1,
                        ];
                    })->values()->toArray();

                if (!count($lineItems)) {
                    return response()->json(['error' => 'El presupuesto no tiene conceptos válidos para el pago inicial.'], 400);
                }

                // Cupón = % aplazado a la finalización (p.ej. 40%). Reutilizable por id.
                $remainingPct = 100 - $depositPct;
                $couponId     = 'ev_deposit_remaining_' . $remainingPct;
                try {
                    $coupon = \Stripe\Coupon::retrieve($couponId);
                } catch (\Throwable $e) {
                    $coupon = \Stripe\Coupon::create([
                        'id'          => $couponId,
                        'percent_off' => $remainingPct,
                        'duration'    => 'once',
                        'name'        => "Pago inicial {$depositPct}% (resto al final)",
                    ]);
                }
                $sessionDiscounts = [['coupon' => $coupon->id]];

                $metaSubtotal = round((float) ($summary['subtotal'] ?? 0) * $depositPercentage, 2);
                $metaVat      = round($metaSubtotal * 0.21, 2);
                $metaTotal    = round($metaSubtotal + $metaVat, 2);
            } else {
                $metaSubtotal = $subtotal;
                $metaVat      = $vat;
                $metaTotal    = $total;
            }

            $sessionParams = [
                'mode' => 'payment',

                'line_items' => $lineItems,

                'customer_email' => $email,

                'billing_address_collection' => 'required',

                'phone_number_collection' => [
                    'enabled' => true,
                ],

                'success_url' => 'https://segenet.es/cargador-electrico/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://segenet.es/cargador-electrico',

                'metadata' => [
                    'type' => 'electric_charger_budget',

                    'reference' => $reference,

                    'customer_name' => (string) ($customer['name'] ?? ''),
                    'customer_email' => (string) $email,
                    'customer_phone' => (string) ($customer['phone'] ?? ''),

                    'charger_name' => (string) ($charger['name'] ?? ''),
                    'installation_type' => (string) ($installationTypeLabel ?? ''),
                    'cable_meters' => (string) ($installation['cableMeters'] ?? ''),

                    'payment_type' => $isDeposit ? 'deposit' : 'full',
                    'deposit_percentage' => (string) ($isDeposit ? $depositPct : ''),
                    'full_total' => (string) $grossTotal,
                    'remaining_amount' => (string) ($isDeposit ? $remainingAmount : '0'),

                    'subtotal' => (string) $metaSubtotal,
                    'vat' => (string) $metaVat,
                    'total' => (string) $metaTotal,
                ],
            ];

            // El % aplazado se aplica como descuento (solo en pagos iniciales/depósito)
            if (!empty($sessionDiscounts)) {
                $sessionParams['discounts'] = $sessionDiscounts;
            }

            $session = Session::create($sessionParams);

            return response()->json([
                'url' => $session->url,
                'session_id' => $session->id,
                'reference' => $reference,
                'payment_type' => $isDeposit ? 'deposit' : 'full',
                'deposit_amount' => $depositAmount,
                'remaining_amount' => $remainingAmount,
                'subtotal' => $subtotal,
                'vat' => $vat,
                'total' => $total,
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando checkout de presupuesto cargador eléctrico', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'No se pudo crear el pago del presupuesto.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function handleElectricChargerBudgetPayment($session): void
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $reference = $session->metadata->reference ?? null;

        $customerEmail = $session->metadata->customer_email ?? null;
        $customerName = $session->metadata->customer_name ?? null;
        $customerPhone = $session->metadata->customer_phone ?? null;

        $chargerName = $session->metadata->charger_name ?? null;
        $installationType = $session->metadata->installation_type ?? null;
        $cableMeters = $session->metadata->cable_meters ?? null;

        $subtotal = $session->metadata->subtotal ?? null;
        $vat = $session->metadata->vat ?? null;
        $total = $session->metadata->total ?? null;

        $receiptData = $this->getReceiptDataFromPaymentIntent($session->payment_intent ?? null);

        $receiptUrl = $receiptData['receipt_url'] ?? null;
        $chargeId = $receiptData['charge_id'] ?? null;


        //Notifico administración
        $this->notificateStripeAction(
            'Presupuesto cargador eléctrico pagado',
            'Presupuesto de cargador pagado',
            'Se ha realizado correctamente el pago de un presupuesto de instalación de cargador eléctrico.',
            [
                'Referencia' => $reference ?? '-',
                'Cliente' => $customerName ?? '-',
                'Email' => $customerEmail ?? '-',
                'Teléfono' => $customerPhone ?? '-',
                'Cargador' => $chargerName ?? '-',
                'Tipo instalación' => $installationType ?? '-',
                'Metros de cable' => $cableMeters ?? '-',
                'Base imponible' => ($subtotal ?? '-') . ' €',
                'IVA' => ($vat ?? '-') . ' €',
                'Total' => ($total ?? '-') . ' €',
                'Checkout Stripe' => $session->id ?? '-',
                'Payment Intent' => $session->payment_intent ?? '-',
            ],
            'Ver recibo',
            $receiptUrl
        );

        //Notifico al cliente
        $this->sendElectricChargerCustomerPaymentEmail(
        //$customerEmail,
            'franperez@segenet.es',
            $customerName,
            [
                'reference' => $reference,
                'charger_name' => $chargerName,
                'installation_type' => $installationType,
                'cable_meters' => $cableMeters,
                'subtotal' => $subtotal,
                'vat' => $vat,
                'total' => $total,
                'receipt_url' => $receiptUrl,
            ]
        );
    }

    private function getReceiptDataFromPaymentIntent(?string $paymentIntentId): array
    {
        if (!$paymentIntentId) {
            return [
                'charge_id' => null,
                'receipt_url' => null,
            ];
        }

        try {
            $charges = \Stripe\Charge::all([
                'payment_intent' => $paymentIntentId,
                'limit' => 1,
            ]);

            $charge = $charges->data[0] ?? null;

            if (!$charge) {
                return [
                    'charge_id' => null,
                    'receipt_url' => null,
                ];
            }

            return [
                'charge_id' => $charge->id ?? null,
                'receipt_url' => $charge->receipt_url ?? null,
            ];

        } catch (\Throwable $e) {
            Log::warning('No se pudo recuperar el recibo del PaymentIntent', [
                'payment_intent' => $paymentIntentId,
                'message' => $e->getMessage(),
            ]);

            return [
                'charge_id' => null,
                'receipt_url' => null,
            ];
        }
    }

    public function getElectricChargerBudgetCheckoutSession(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            $sessionId = $request->input('session_id');

            if (!$sessionId) {
                return response()->json([
                    'error' => 'Falta session_id.',
                ], 400);
            }

            $session = Session::retrieve($sessionId);

            if (($session->metadata->type ?? null) !== 'electric_charger_budget') {
                return response()->json([
                    'error' => 'La sesión no pertenece a un presupuesto de cargador.',
                ], 400);
            }

            $receiptData = $this->getReceiptDataFromPaymentIntent($session->payment_intent ?? null);

            return response()->json([
                'reference' => $session->metadata->reference ?? null,

                'customer' => [
                    'name' => $session->metadata->customer_name ?? null,
                    'email' => $session->metadata->customer_email ?? null,
                    'phone' => $session->metadata->customer_phone ?? null,
                ],

                'charger' => [
                    'name' => $session->metadata->charger_name ?? null,
                ],

                'installation' => [
                    'type' => $session->metadata->installation_type ?? null,
                    'cableMeters' => $session->metadata->cable_meters ?? null,
                ],

                'amounts' => [
                    'subtotal' => (float) ($session->metadata->subtotal ?? 0),
                    'vat' => (float) ($session->metadata->vat ?? 0),
                    'total' => (float) ($session->metadata->total ?? 0),
                    'currency' => strtoupper($session->currency ?? 'eur'),
                ],

                'stripe' => [
                    'session_id' => $session->id,
                    'payment_status' => $session->payment_status ?? null,
                    'payment_intent' => $session->payment_intent ?? null,
                    'receipt_url' => $receiptData['receipt_url'] ?? null,
                    'charge_id' => $receiptData['charge_id'] ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error recuperando checkout de presupuesto cargador', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'No se pudo recuperar la información del pago.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }




    //WEBHOOKS
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $secret
            );
        } catch (\Throwable $e) {
            return response('Webhook inválido: ' . $e->getMessage(), 400);
        }

        try {
            switch ($event->type) {
                case 'checkout.session.completed':

                    $metadataType = $event->data->object->metadata->type ?? null;

                    if ($metadataType === 'one_time_extra') {
                        $this->handleOneTimeExtra($event->data->object);

                    } elseif ($metadataType === 'electric_charger_budget') {
                        $this->handleElectricChargerBudgetPayment($event->data->object);

                    } else {
                        $this->setEnterpriseSubscription($event->data->object);
                    }

                    break;

                case 'customer.subscription.updated':
                    $this->updateEnterpriseSubscription($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->deleteEnterpriseSubscription($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->markSubscriptionPaymentFailed($event->data->object);
                    break;

                case 'invoice.paid':
                    $this->markSubscriptionPaid($event->data->object);
                    break;

                default:
                    Log::info('Evento Stripe ignorado', [
                        'type' => $event->type,
                    ]);
                    break;
            }


            return response('OK', 200);

        } catch (\Throwable $e) {
            Log::error('Error procesando webhook de Stripe', [
                'event_type' => $event->type,
                'message' => $e->getMessage(),
            ]);

            return response('Error procesando webhook: ' . $e->getMessage(), 500);
        }
    }

    //Confirmar suscripción
    private function setEnterpriseSubscription($session): void
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $plans = config('plans');

        $planId = isset($session->metadata->plan_id)
            ? (int) $session->metadata->plan_id
            : null;

        $billing = $session->metadata->billing ?? null;
        $userId = $session->metadata->user_id ?? null;

        if ($planId === null || !$billing || !$userId) {
            throw new \Exception('Metadata incompleta en checkout.session.completed');
        }

        if (!isset($plans[$planId])) {
            throw new \Exception('Plan no encontrado en config/plans.php: ' . $planId);
        }

        $plan = $plans[$planId];
        $isAnnual = $billing === 'annual';

        $userSubdomain = UserHelper::getUserSubdomain($userId);

        if (!$userSubdomain || !isset($userSubdomain['_id'])) {
            throw new \Exception('No se encontró subdominio para user_id: ' . $userId);
        }

        $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

        if (!$enterprise) {
            throw new \Exception('Empresa no encontrada para subdomainUser: ' . $userSubdomain['_id']);
        }

        $subscriptionId = is_string($session->subscription)
            ? $session->subscription
            : $session->subscription->id;

        $customerId = is_string($session->customer)
            ? $session->customer
            : $session->customer->id;

        $stripeSubscription = \Stripe\Subscription::retrieve($subscriptionId);

        $subscriptionItem = $stripeSubscription->items->data[0] ?? null;

        $priceId = $subscriptionItem && isset($subscriptionItem->price)
            ? $subscriptionItem->price->id
            : null;

        $currentPeriodStart = $subscriptionItem && isset($subscriptionItem->current_period_start)
            ? new \MongoDB\BSON\UTCDateTime($subscriptionItem->current_period_start * 1000)
            : null;

        $currentPeriodEnd = $subscriptionItem && isset($subscriptionItem->current_period_end)
            ? new \MongoDB\BSON\UTCDateTime($subscriptionItem->current_period_end * 1000)
            : null;

        $latestInvoice = null;

        if (!empty($stripeSubscription->latest_invoice)) {
            $latestInvoiceId = is_string($stripeSubscription->latest_invoice)
                ? $stripeSubscription->latest_invoice
                : $stripeSubscription->latest_invoice->id;

            $latestInvoice = \Stripe\Invoice::retrieve($latestInvoiceId);
        }

        $lastInvoiceCreated = isset($latestInvoice->created)
            ? new \MongoDB\BSON\UTCDateTime($latestInvoice->created * 1000)
            : null;

        $lastPaymentPaidAt = isset($latestInvoice->status) && $latestInvoice->status === 'paid'
            ? $lastInvoiceCreated
            : null;

        $nextPaymentAttempt = isset($latestInvoice->next_payment_attempt)
            ? new \MongoDB\BSON\UTCDateTime($latestInvoice->next_payment_attempt * 1000)
            : null;

        $enterprise->subscription = [
            'plan_id' => $planId,
            'billing' => $billing,
            'isAnnual' => $isAnnual,
            'startedAt' => new \MongoDB\BSON\UTCDateTime(),

            'included' => [
                'users' => $plan['included']['users']['amount'] ?? null,
                'scans' => $plan['included']['scans']['amount'] ?? null,
                'monitoring' => $plan['included']['monitoring']['amount'] ?? null,
                'calls' => $plan['included']['calls']['amount'] ?? 0,
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
                'calls' => 0
            ]
        ];

        $enterprise->stripe = [
            'customer_id' => $customerId,
            'subscription_id' => $subscriptionId,
            'status' => $stripeSubscription->status,
            'billing' => $billing,
            'plan_id' => $planId,
            'price_id' => $priceId,

            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ?? false,

            'last_invoice_id' => $latestInvoice->id ?? null,
            'last_invoice_status' => $latestInvoice->status ?? null,
            'last_invoice_url' => $latestInvoice->hosted_invoice_url ?? null,
            'last_invoice_pdf' => $latestInvoice->invoice_pdf ?? null,
            'last_invoice_created' => $lastInvoiceCreated,
            'last_invoice_amount_paid' => $latestInvoice->amount_paid ?? null,
            'last_invoice_amount_due' => $latestInvoice->amount_due ?? null,
            'last_invoice_amount_remaining' => $latestInvoice->amount_remaining ?? null,
            'last_invoice_currency' => $latestInvoice->currency ?? 'eur',

            'next_payment_attempt' => $nextPaymentAttempt,
            'last_payment_failed_at' => null,
            'last_payment_paid_at' => $lastPaymentPaidAt,
            'canceled_at' => null,
            'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
        ];

        $enterprise->save();

        //Notifico
        $this->notificateStripeAction(
            'Nueva suscripción CRM',
            'Nueva suscripción CRM',
            'Se ha creado una nueva suscripción en el CRM.',
            [
                'Empresa' => $enterprise->name,
                'Plan' => $plan['name'],
                'Facturación' => $billing,
                'Importe' => (($latestInvoice->amount_paid ?? 0) / 100) . ' ' . strtoupper($latestInvoice->currency ?? 'EUR'),
                'Cliente Stripe' => $customerId,
                'Suscripción Stripe' => $subscriptionId,
            ],
            'Ver factura',
            $latestInvoice->hosted_invoice_url ?? null
        );
    }

    //Actualizar suscripción
    private function updateEnterpriseSubscription($stripeSubscription): void
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));


        $subscriptionId = $stripeSubscription->id ?? null;

        if (!$subscriptionId) {
            throw new \Exception('customer.subscription.updated sin subscription_id');
        }

        $enterprise = Enterprise::where('stripe.subscription_id', $subscriptionId)->first();

        $isExtrasSubscription = false;

        if (!$enterprise) {
            $enterprise = Enterprise::where('stripe.extras_subscription_id', $subscriptionId)->first();
            $isExtrasSubscription = true;
        }

        if (!$enterprise) {
            throw new \Exception('Empresa no encontrada para subscription.updated: ' . $subscriptionId);
        }

        $stripe = $enterprise->stripe ?? [];
        $subscription = $enterprise->subscription ?? [];

        /*Log::info('Webhook customer.subscription.updated recibido', [
            'subscription_id' => $subscriptionId,
            'is_extras_subscription' => $isExtrasSubscription,
            'db_main_subscription_id' => $stripe['subscription_id'] ?? null,
            'db_extras_subscription_id' => $stripe['extras_subscription_id'] ?? null,
            'stripe_status' => $stripeSubscription->status ?? null,
            'cancel_at_period_end' => $stripeSubscription->cancel_at_period_end ?? null,
        ]);*/

        /*
         * Fechas de periodo.
         * En algunas versiones Stripe las trae en la suscripción,
         * en otras las podemos leer del primer subscription item.
         */
        $periodStart = null;
        $periodEnd = null;

        if (!empty($stripeSubscription->current_period_start)) {
            $periodStart = $stripeSubscription->current_period_start;
        }

        if (!empty($stripeSubscription->current_period_end)) {
            $periodEnd = $stripeSubscription->current_period_end;
        }

        $subscriptionItem = $stripeSubscription->items->data[0] ?? null;

        if (!$periodStart && $subscriptionItem && isset($subscriptionItem->current_period_start)) {
            $periodStart = $subscriptionItem->current_period_start;
        }

        if (!$periodEnd && $subscriptionItem && isset($subscriptionItem->current_period_end)) {
            $periodEnd = $subscriptionItem->current_period_end;
        }

        /*
         * CASO 1: Se ha actualizado la suscripción mensual de extras.
         */
        if ($isExtrasSubscription) {
            $extrasCancelAtPeriodEnd = $stripeSubscription->cancel_at_period_end ?? false;

            $stripe['extras_status'] = $stripeSubscription->status ?? ($stripe['extras_status'] ?? null);
            $stripe['extras_cancel_at_period_end'] = $extrasCancelAtPeriodEnd;

            if ($periodStart) {
                $stripe['extras_current_period_start'] = new \MongoDB\BSON\UTCDateTime($periodStart * 1000);
            }

            if ($periodEnd) {
                $stripe['extras_current_period_end'] = new \MongoDB\BSON\UTCDateTime($periodEnd * 1000);
            }

            if ($extrasCancelAtPeriodEnd) {
                /*Log::info('Suscripción de extras programada para cancelar', [
                    'extras_subscription_id' => $subscriptionId,
                    'cancel_at' => $periodEnd,
                ]);*/

                foreach (['users', 'monitoring'] as $category) {
                    $items = data_get($subscription, "extras.recurring.$category.items", []);

                    if (is_array($items)) {
                        foreach ($items as $index => $item) {
                            $items[$index]['cancel_at_period_end'] = true;
                            $items[$index]['cancelAt'] = $periodEnd
                                ? new \MongoDB\BSON\UTCDateTime($periodEnd * 1000)
                                : null;
                            $items[$index]['status'] = 'cancel_scheduled';
                            $items[$index]['updatedAt'] = new \MongoDB\BSON\UTCDateTime();
                        }

                        data_set($subscription, "extras.recurring.$category.items", $items);
                    }
                }
            } else {
                /*Log::info('Suscripción de extras reactivada / sin cancelación programada', [
                    'extras_subscription_id' => $subscriptionId,
                ]);*/

                foreach (['users', 'monitoring'] as $category) {
                    $items = data_get($subscription, "extras.recurring.$category.items", []);

                    if (is_array($items)) {
                        foreach ($items as $index => $item) {
                            if (($item['status'] ?? null) === 'cancel_scheduled') {
                                $items[$index]['cancel_at_period_end'] = false;
                                $items[$index]['cancelAt'] = null;
                                $items[$index]['status'] = 'active';
                                $items[$index]['updatedAt'] = new \MongoDB\BSON\UTCDateTime();
                            }
                        }

                        data_set($subscription, "extras.recurring.$category.items", $items);
                    }
                }
            }

            $stripe['extras_updatedAt'] = new \MongoDB\BSON\UTCDateTime();

            $enterprise->stripe = $stripe;
            $enterprise->subscription = $subscription;
            $enterprise->save();

            return;
        }

        /*
         * CASO 2: Se ha actualizado la suscripción principal.
         */
        $priceId = $subscriptionItem && isset($subscriptionItem->price)
            ? $subscriptionItem->price->id
            : ($stripe['price_id'] ?? null);

        $stripe['status'] = $stripeSubscription->status ?? ($stripe['status'] ?? null);
        $stripe['price_id'] = $priceId;

        if (!empty($stripeSubscription->metadata->plan_id)) {
            $stripe['plan_id'] = (int) $stripeSubscription->metadata->plan_id;
        }

        if (!empty($stripeSubscription->metadata->billing)) {
            $stripe['billing'] = $stripeSubscription->metadata->billing;
        }

        $mainCancelAtPeriodEnd = $stripeSubscription->cancel_at_period_end ?? false;

        $stripe['cancel_at_period_end'] = $mainCancelAtPeriodEnd;

        if ($periodStart) {
            $stripe['current_period_start'] = new \MongoDB\BSON\UTCDateTime($periodStart * 1000);
        }

        if ($periodEnd) {
            $stripe['current_period_end'] = new \MongoDB\BSON\UTCDateTime($periodEnd * 1000);
        }

        $stripe['canceled_at'] = !empty($stripeSubscription->canceled_at)
            ? new \MongoDB\BSON\UTCDateTime($stripeSubscription->canceled_at * 1000)
            : null;

        $stripe['ended_at'] = !empty($stripeSubscription->ended_at)
            ? new \MongoDB\BSON\UTCDateTime($stripeSubscription->ended_at * 1000)
            : null;

        /*
         * Si desde Stripe Portal cancelan la suscripción principal,
         * programamos también la cancelación de la suscripción mensual de extras.
         */
        if ($mainCancelAtPeriodEnd && !empty($stripe['extras_subscription_id'])) {
            try {
                /*Log::info('Programando cancelación de extras por cancelación de principal', [
                    'main_subscription_id' => $subscriptionId,
                    'extras_subscription_id' => $stripe['extras_subscription_id'],
                ]);*/

                $extrasSubscription = \Stripe\Subscription::update($stripe['extras_subscription_id'], [
                    'cancel_at_period_end' => true,
                ]);

                $extrasPeriodStart = $extrasSubscription->current_period_start ?? null;
                $extrasPeriodEnd = $extrasSubscription->current_period_end ?? null;

                $extrasSubscriptionItem = $extrasSubscription->items->data[0] ?? null;

                if (!$extrasPeriodStart && $extrasSubscriptionItem && isset($extrasSubscriptionItem->current_period_start)) {
                    $extrasPeriodStart = $extrasSubscriptionItem->current_period_start;
                }

                if (!$extrasPeriodEnd && $extrasSubscriptionItem && isset($extrasSubscriptionItem->current_period_end)) {
                    $extrasPeriodEnd = $extrasSubscriptionItem->current_period_end;
                }

                $stripe['extras_status'] = $extrasSubscription->status ?? ($stripe['extras_status'] ?? null);
                $stripe['extras_cancel_at_period_end'] = true;

                if ($extrasPeriodStart) {
                    $stripe['extras_current_period_start'] = new \MongoDB\BSON\UTCDateTime(
                        $extrasPeriodStart * 1000
                    );
                }

                if ($extrasPeriodEnd) {
                    $stripe['extras_current_period_end'] = new \MongoDB\BSON\UTCDateTime(
                        $extrasPeriodEnd * 1000
                    );
                }

                $stripe['extras_updatedAt'] = new \MongoDB\BSON\UTCDateTime();

                foreach (['users', 'monitoring'] as $category) {
                    $items = data_get($subscription, "extras.recurring.$category.items", []);

                    if (is_array($items)) {
                        foreach ($items as $index => $item) {
                            $items[$index]['cancel_at_period_end'] = true;
                            $items[$index]['cancelAt'] = $extrasPeriodEnd
                                ? new \MongoDB\BSON\UTCDateTime($extrasPeriodEnd * 1000)
                                : null;
                            $items[$index]['status'] = 'cancel_scheduled';
                            $items[$index]['updatedAt'] = new \MongoDB\BSON\UTCDateTime();
                        }

                        data_set($subscription, "extras.recurring.$category.items", $items);
                    }
                }

            } catch (\Throwable $e) {
                /*Log::error('No se pudo programar la cancelación de la suscripción de extras', [
                    'main_subscription_id' => $subscriptionId,
                    'extras_subscription_id' => $stripe['extras_subscription_id'] ?? null,
                    'message' => $e->getMessage(),
                ]);*/
            }
        }

        /*
         * Si desde Stripe Portal se pulsa "No cancelar la suscripción" en la principal,
         * reactivamos también la suscripción mensual de extras si estaba programada.
         */
        if (!$mainCancelAtPeriodEnd && !empty($stripe['extras_subscription_id']) && !empty($stripe['extras_cancel_at_period_end'])) {
            try {
                /*Log::info('Reactivando extras porque se quitó la cancelación de la principal', [
                    'main_subscription_id' => $subscriptionId,
                    'extras_subscription_id' => $stripe['extras_subscription_id'],
                ]);*/

                $extrasSubscription = \Stripe\Subscription::update($stripe['extras_subscription_id'], [
                    'cancel_at_period_end' => false,
                ]);

                $stripe['extras_status'] = $extrasSubscription->status ?? ($stripe['extras_status'] ?? null);
                $stripe['extras_cancel_at_period_end'] = false;
                $stripe['extras_updatedAt'] = new \MongoDB\BSON\UTCDateTime();

                foreach (['users', 'monitoring'] as $category) {
                    $items = data_get($subscription, "extras.recurring.$category.items", []);

                    if (is_array($items)) {
                        foreach ($items as $index => $item) {
                            if (($item['status'] ?? null) === 'cancel_scheduled') {
                                $items[$index]['cancel_at_period_end'] = false;
                                $items[$index]['cancelAt'] = null;
                                $items[$index]['status'] = 'active';
                                $items[$index]['updatedAt'] = new \MongoDB\BSON\UTCDateTime();
                            }
                        }

                        data_set($subscription, "extras.recurring.$category.items", $items);
                    }
                }

            } catch (\Throwable $e) {
                Log::error('No se pudo reactivar la suscripción de extras', [
                    'main_subscription_id' => $subscriptionId,
                    'extras_subscription_id' => $stripe['extras_subscription_id'] ?? null,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $stripe['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

        $enterprise->stripe = $stripe;
        $enterprise->subscription = $subscription;
        $enterprise->save();
    }

    //Cancelar suscripción
    private function deleteEnterpriseSubscription($stripeSubscription): void
    {
        $subscriptionId = $stripeSubscription->id ?? null;

        if (!$subscriptionId) {
            throw new \Exception('customer.subscription.deleted sin subscription_id');
        }

        $enterprise = Enterprise::where('stripe.subscription_id', $subscriptionId)->first();

        $isExtrasSubscription = false;

        if (!$enterprise) {
            $enterprise = Enterprise::where('stripe.extras_subscription_id', $subscriptionId)->first();
            $isExtrasSubscription = true;
        }

        if (!$enterprise) {
            throw new \Exception('Empresa no encontrada para subscription_id cancelada: ' . $subscriptionId);
        }

        $stripe = $enterprise->stripe ?? [];
        $subscription = $enterprise->subscription ?? [];

        $canceledAt = !empty($stripeSubscription->canceled_at)
            ? new \MongoDB\BSON\UTCDateTime($stripeSubscription->canceled_at * 1000)
            : new \MongoDB\BSON\UTCDateTime();

        $endedAt = !empty($stripeSubscription->ended_at)
            ? new \MongoDB\BSON\UTCDateTime($stripeSubscription->ended_at * 1000)
            : $canceledAt;

        /*
         * CASO 1: Se ha cancelado la suscripción mensual de extras
         */
        if ($isExtrasSubscription) {
            $stripe['extras_status'] = 'canceled';
            $stripe['extras_cancel_at_period_end'] = false;
            $stripe['extras_canceled_at'] = $canceledAt;
            $stripe['extras_ended_at'] = $endedAt;
            $stripe['extras_updatedAt'] = new \MongoDB\BSON\UTCDateTime();

            /*
             * Cuando la suscripción de extras ya se cancela realmente,
             * quitamos los límites extra del CRM.
             */
            foreach (['users', 'monitoring'] as $category) {
                data_set($subscription, "extras.recurring.$category.amount", 0);
                data_set($subscription, "extras.recurring.$category.monthly_price", 0);
                data_set($subscription, "extras.recurring.$category.items", []);
            }

            $enterprise->stripe = $stripe;
            $enterprise->subscription = $subscription;
            $enterprise->save();

            //Notifico
            $this->notificateStripeAction(
                'Suscripción de extras CRM cancelada',
                'Suscripción de extras cancelada',
                'Se ha cancelado una suscripción mensual de extras.',
                [
                    'Empresa' => $enterprise->name,
                    'Suscripción Stripe extras' => $subscriptionId,
                    'Estado' => 'canceled',
                ]
            );

            return;
        }

        /*
         * CASO 2: Se ha cancelado la suscripción principal
         */
        $stripe['status'] = 'canceled';
        $stripe['cancel_at_period_end'] = $stripeSubscription->cancel_at_period_end ?? false;
        $stripe['canceled_at'] = $canceledAt;
        $stripe['ended_at'] = $endedAt;
        $stripe['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

        /*
         * Si por lo que sea la principal se cancela de forma definitiva
         * y todavía existe una suscripción de extras, también la cancelamos.
         */
        if (!empty($stripe['extras_subscription_id']) && ($stripe['extras_status'] ?? null) !== 'canceled') {
            try {
                $extrasSubscription = \Stripe\Subscription::retrieve($stripe['extras_subscription_id']);

                $extrasSubscription->cancel([
                    'prorate' => false,
                    'invoice_now' => false,
                ]);

                $stripe['extras_status'] = 'canceled';
                $stripe['extras_cancel_at_period_end'] = false;
                $stripe['extras_canceled_at'] = new \MongoDB\BSON\UTCDateTime();
                $stripe['extras_ended_at'] = $stripe['extras_canceled_at'];
                $stripe['extras_updatedAt'] = new \MongoDB\BSON\UTCDateTime();

                foreach (['users', 'monitoring'] as $category) {
                    data_set($subscription, "extras.recurring.$category.amount", 0);
                    data_set($subscription, "extras.recurring.$category.monthly_price", 0);
                    data_set($subscription, "extras.recurring.$category.items", []);
                }

            } catch (\Throwable $e) {
                Log::error('No se pudo cancelar la suscripción de extras al cancelar la principal', [
                    'main_subscription_id' => $subscriptionId,
                    'extras_subscription_id' => $stripe['extras_subscription_id'] ?? null,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $enterprise->stripe = $stripe;
        $enterprise->subscription = $subscription;
        $enterprise->save();

        //Notifico
        $this->notificateStripeAction(
            'Suscripción CRM cancelada',
            'Suscripción cancelada',
            'Se ha cancelado una suscripción principal del CRM.',
            [
                'Empresa' => $enterprise->name,
                'Suscripción Stripe' => $subscriptionId,
                'Estado' => 'canceled',
            ]
        );
    }

    // Pago fallido
    private function markSubscriptionPaymentFailed($invoice): void
    {
        $subscriptionId = $this->getSubscriptionIdFromInvoice($invoice);

        if (!$subscriptionId) {
            throw new \Exception('invoice.payment_failed sin subscription_id');
        }

        $enterprise = Enterprise::where('stripe.subscription_id', $subscriptionId)->first();

        if (!$enterprise) {
            throw new \Exception('Empresa no encontrada para invoice.payment_failed: ' . $subscriptionId);
        }

        $stripe = $enterprise->stripe ?? [];

        $stripe['status'] = 'past_due';

        $stripe['last_invoice_id'] = $invoice->id ?? null;
        $stripe['last_invoice_status'] = $invoice->status ?? null;
        $stripe['last_invoice_url'] = $invoice->hosted_invoice_url ?? null;
        $stripe['last_invoice_pdf'] = $invoice->invoice_pdf ?? null;
        $stripe['last_invoice_created'] = isset($invoice->created)
            ? new \MongoDB\BSON\UTCDateTime($invoice->created * 1000)
            : null;

        $stripe['last_invoice_amount_paid'] = $invoice->amount_paid ?? 0;
        $stripe['last_invoice_amount_due'] = $invoice->amount_due ?? null;
        $stripe['last_invoice_amount_remaining'] = $invoice->amount_remaining ?? null;
        $stripe['last_invoice_currency'] = $invoice->currency ?? 'eur';

        $stripe['next_payment_attempt'] = isset($invoice->next_payment_attempt)
            ? new \MongoDB\BSON\UTCDateTime($invoice->next_payment_attempt * 1000)
            : null;
        $stripe['last_payment_failed_at'] = new \MongoDB\BSON\UTCDateTime();
        $stripe['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

        $enterprise->stripe = $stripe;
        $enterprise->save();


        //Notifico
        $this->notificateStripeAction(
            'Pago de suscripción CRM fallido',
            'Pago de suscripción fallido',
            'Ha fallado el pago de una suscripción del CRM.',
            [
                'Empresa' => $enterprise->name,
                'Importe pendiente' => (($invoice->amount_remaining ?? $invoice->amount_due ?? 0) / 100) . ' ' . strtoupper($invoice->currency ?? 'EUR'),
                'Estado factura' => $invoice->status ?? '-',
                'Próximo intento' => isset($invoice->next_payment_attempt)
                    ? date('d/m/Y H:i', $invoice->next_payment_attempt)
                    : '-',
                'Factura Stripe' => $invoice->id ?? '-',
            ],
            'Ver factura',
            $invoice->hosted_invoice_url ?? null
        );
    }

    //Pago correcto
    private function markSubscriptionPaid($invoice): void
    {
        $subscriptionId = $this->getSubscriptionIdFromInvoice($invoice);

        if (!$subscriptionId) {
            throw new \Exception('invoice.paid sin subscription_id');
        }

        $enterprise = Enterprise::where('stripe.subscription_id', $subscriptionId)->first();

        if (!$enterprise) {
            throw new \Exception('Empresa no encontrada para invoice.paid: ' . $subscriptionId);
        }

        $stripe = $enterprise->stripe ?? [];
        $subscription = $enterprise->subscription ?? [];

        $period = $this->getInvoicePeriod($invoice);

        if (($stripe['status'] ?? null) !== 'canceled') {
            $stripe['status'] = 'active';
        }

        $stripe['last_invoice_id'] = $invoice->id ?? null;
        $stripe['last_invoice_status'] = $invoice->status ?? null;
        $stripe['last_invoice_url'] = $invoice->hosted_invoice_url ?? null;
        $stripe['last_invoice_pdf'] = $invoice->invoice_pdf ?? null;
        $stripe['last_invoice_created'] = isset($invoice->created)
            ? new \MongoDB\BSON\UTCDateTime($invoice->created * 1000)
            : null;

        $stripe['last_invoice_amount_paid'] = $invoice->amount_paid ?? null;
        $stripe['last_invoice_amount_due'] = $invoice->amount_due ?? null;
        $stripe['last_invoice_amount_remaining'] = $invoice->amount_remaining ?? 0;
        $stripe['last_invoice_currency'] = $invoice->currency ?? 'eur';

        $stripe['last_payment_paid_at'] = new \MongoDB\BSON\UTCDateTime();

        $stripe['last_payment_failed_at'] = null;
        $stripe['next_payment_attempt'] = null;

        if (!empty($period['start'])) {
            $stripe['current_period_start'] = new \MongoDB\BSON\UTCDateTime($period['start'] * 1000);
        }

        if (!empty($period['end'])) {
            $stripe['current_period_end'] = new \MongoDB\BSON\UTCDateTime($period['end'] * 1000);
        }

        $stripe['updatedAt'] = new \MongoDB\BSON\UTCDateTime();

        if (($invoice->billing_reason ?? null) === 'subscription_cycle') {
            if (!isset($subscription['usage']) || !is_array($subscription['usage'])) {
                $subscription['usage'] = [];
            }

            $subscription['usage']['scans'] = 0;
            $subscription['usage']['calls'] = 0;

            $includedCalls = data_get($subscription, 'included.calls', 0);

            if ($includedCalls !== null) {
                $subscription = $this->regularizeCallExcesses(
                    $subscription,
                    (int) $includedCalls,
                    'plan'
                );
            }

            $subscription['lastUsageResetAt'] = new \MongoDB\BSON\UTCDateTime();
        }

        $enterprise->stripe = $stripe;
        $enterprise->subscription = $subscription;
        $enterprise->save();

        $userSubdomain = User::where('_id', $enterprise->subdomainUser)->first();
        $adminEmail = $userSubdomain->email ?? null;
        $emails = array_filter(['franperez@segenet.es']);

        //Notifico
        $this->notificateStripeAction(
            'Pago de suscripción CRM realizado',
            'Pago de suscripción realizado',
            'Se ha realizado correctamente un pago de suscripción.',
            [
                'Empresa' => $enterprise->name,
                'Importe pagado' => (($invoice->amount_paid ?? 0) / 100) . ' ' . strtoupper($invoice->currency ?? 'EUR'),
                'Estado factura' => $invoice->status ?? '-',
                'Factura Stripe' => $invoice->id ?? '-',
            ],
            'Ver factura',
            $invoice->hosted_invoice_url ?? null,
            array_values($emails)
        );
    }


    //helper
    private function getSubscriptionIdFromInvoice($invoice): ?string
    {
        // Formato antiguo / algunos eventos
        if (!empty($invoice->subscription)) {
            return is_string($invoice->subscription)
                ? $invoice->subscription
                : $invoice->subscription->id;
        }

        // Formato nuevo: invoice.parent.subscription_details.subscription
        if (
            !empty($invoice->parent) &&
            ($invoice->parent->type ?? null) === 'subscription_details' &&
            !empty($invoice->parent->subscription_details->subscription)
        ) {
            return is_string($invoice->parent->subscription_details->subscription)
                ? $invoice->parent->subscription_details->subscription
                : $invoice->parent->subscription_details->subscription->id;
        }

        // Fallback: invoice.lines.data[*].parent.subscription_item_details.subscription
        if (!empty($invoice->lines->data)) {
            foreach ($invoice->lines->data as $line) {
                if (
                    !empty($line->parent) &&
                    ($line->parent->type ?? null) === 'subscription_item_details' &&
                    !empty($line->parent->subscription_item_details->subscription)
                ) {
                    return is_string($line->parent->subscription_item_details->subscription)
                        ? $line->parent->subscription_item_details->subscription
                        : $line->parent->subscription_item_details->subscription->id;
                }
            }
        }

        return null;
    }

    private function getInvoicePeriod($invoice): array
    {
        $periodStart = null;
        $periodEnd = null;

        if (!empty($invoice->lines->data)) {
            foreach ($invoice->lines->data as $line) {
                if (!empty($line->period)) {
                    $periodStart = $line->period->start ?? null;
                    $periodEnd = $line->period->end ?? null;
                    break;
                }
            }
        }

        if (!$periodStart && isset($invoice->period_start)) {
            $periodStart = $invoice->period_start;
        }

        if (!$periodEnd && isset($invoice->period_end)) {
            $periodEnd = $invoice->period_end;
        }

        return [
            'start' => $periodStart,
            'end' => $periodEnd,
        ];
    }

    private function getStripeTaxRates(): array
    {
        $taxRateId = env('STRIPE_TAX_RATE_ID');

        return $taxRateId ? [$taxRateId] : [];
    }

    private function regularizeCallExcesses(array $subscription, int $newAvailableMinutes, string $source): array
    {
        if (!isset($subscription['excesses']) || !is_array($subscription['excesses'])) {
            $subscription['excesses'] = [];
        }

        $currentExcess = (int) ($subscription['excesses']['calls'] ?? 0);

        if ($currentExcess <= 0 || $newAvailableMinutes <= 0) {
            return $subscription;
        }

        $minutesToRegularize = min($currentExcess, $newAvailableMinutes);

        $subscription['excesses']['calls'] = $currentExcess - $minutesToRegularize;

        if ($source === 'extras') {
            $subscription['extras']['one_time']['calls']['remaining'] =
                max((int) ($subscription['extras']['one_time']['calls']['remaining'] ?? 0) - $minutesToRegularize, 0);
        }

        if ($source === 'plan') {
            $subscription['usage']['calls'] =
                (int) ($subscription['usage']['calls'] ?? 0) + $minutesToRegularize;
        }

        return $subscription;
    }

    private function notificateStripeAction(string $subject, string $title, string $description, array $rows = [], ?string $buttonText = null, ?string $buttonUrl = null, ?array $emails = []): void {
        try {
            $rowsHtml = '';

            foreach ($rows as $label => $value) {
                $rowsHtml .= "
                <tr>
                    <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;width:180px;'>
                        {$label}
                    </td>
                    <td style='padding:10px 12px;color:#111827;font-size:14px;border-bottom:1px solid #eef2f7;font-weight:600;'>
                        {$value}
                    </td>
                </tr>
            ";
            }

            $buttonHtml = '';

            if ($buttonText && $buttonUrl) {
                $buttonHtml = "
                <p style='margin:24px 0 0;'>
                    <a href='{$buttonUrl}' style='background:#0b3a75;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;display:inline-block;'>
                        {$buttonText}
                    </a>
                </p>
            ";
            }

            $html = "
            <div style='font-family:Arial,sans-serif;background:#f6f8fb;padding:28px;'>
                <div style='max-width:680px;margin:0 auto;background:#ffffff;border-radius:14px;padding:28px;border:1px solid #e5e7eb;'>
                    <h2 style='margin:0 0 10px;color:#0b2f63;font-size:22px;'>{$title}</h2>
                    <p style='margin:0 0 22px;color:#374151;font-size:15px;line-height:1.5;'>{$description}</p>

                    <table style='width:100%;border-collapse:collapse;background:#ffffff;'>
                        {$rowsHtml}
                    </table>

                    {$buttonHtml}
                </div>
            </div>
        ";

            $emails = count($emails) > 0 ? $emails : ['franperez@segenet.es'];

            Mail::html($html, function ($mail) use ($subject, $emails) {
                $mail->to($emails)
                    ->subject($subject);
            });

        } catch (\Throwable $e) {
            Log::error('Error enviando correo de registro Stripe', [
                'subject' => $subject,
                'message' => $e->getMessage(),
            ]);
        }
    }

    //Correo pago realizado cargador eléctrico
    private function sendElectricChargerCustomerPaymentEmail(?string $email, ?string $name, array $data): void
    {
        if (!$email)
            return;


        try {
            $customerName = $name ?: 'cliente';

            $reference = $data['reference'] ?? '-';
            $chargerName = $data['charger_name'] ?? '-';
            $installationType = $data['installation_type'] ?? '-';
            $cableMeters = $data['cable_meters'] ?? '-';
            $subtotal = $data['subtotal'] ?? '-';
            $vat = $data['vat'] ?? '-';
            $total = $data['total'] ?? '-';
            $receiptUrl = $data['receipt_url'] ?? null;

            $buttonHtml = '';

            if ($receiptUrl) {
                $buttonHtml = "
                <p style='margin:24px 0 0;'>
                    <a href='{$receiptUrl}' style='background:#0b3a75;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:700;display:inline-block;'>
                        Ver recibo
                    </a>
                </p>
            ";
            }

            $html = "
            <div style='font-family:Arial,sans-serif;background:#f6f8fb;padding:28px;'>
                <div style='max-width:680px;margin:0 auto;background:#ffffff;border-radius:14px;padding:28px;border:1px solid #e5e7eb;'>

                    <h2 style='margin:0 0 10px;color:#0b2f63;font-size:22px;'>
                        Pago recibido correctamente
                    </h2>

                    <p style='margin:0 0 18px;color:#374151;font-size:15px;line-height:1.5;'>
                        Hola {$customerName}, hemos recibido correctamente el pago de su presupuesto de instalación de cargador eléctrico.
                    </p>

                    <p style='margin:0 0 22px;color:#374151;font-size:15px;line-height:1.5;'>
                        Nuestro equipo revisará su solicitud y se pondrá en contacto con usted para coordinar los próximos pasos de la instalación.
                    </p>

                    <table style='width:100%;border-collapse:collapse;background:#ffffff;'>
                        <tr>
                            <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;width:180px;'>
                                Referencia
                            </td>
                            <td style='padding:10px 12px;color:#111827;font-size:14px;border-bottom:1px solid #eef2f7;font-weight:600;'>
                                {$reference}
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;'>
                                Cargador
                            </td>
                            <td style='padding:10px 12px;color:#111827;font-size:14px;border-bottom:1px solid #eef2f7;font-weight:600;'>
                                {$chargerName}
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;'>
                                Tipo de instalación
                            </td>
                            <td style='padding:10px 12px;color:#111827;font-size:14px;border-bottom:1px solid #eef2f7;font-weight:600;'>
                                {$installationType}
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;'>
                                Metros de cable
                            </td>
                            <td style='padding:10px 12px;color:#111827;font-size:14px;border-bottom:1px solid #eef2f7;font-weight:600;'>
                                {$cableMeters}
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;'>
                                Base imponible
                            </td>
                            <td style='padding:10px 12px;color:#111827;font-size:14px;border-bottom:1px solid #eef2f7;font-weight:600;'>
                                {$subtotal} €
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;'>
                                IVA
                            </td>
                            <td style='padding:10px 12px;color:#111827;font-size:14px;border-bottom:1px solid #eef2f7;font-weight:600;'>
                                {$vat} €
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:10px 12px;color:#6b7280;font-size:14px;border-bottom:1px solid #eef2f7;'>
                                Total pagado
                            </td>
                            <td style='padding:10px 12px;color:#111827;font-size:16px;border-bottom:1px solid #eef2f7;font-weight:700;'>
                                {$total} €
                            </td>
                        </tr>
                    </table>

                    {$buttonHtml}

                    <p style='margin:24px 0 0;color:#6b7280;font-size:13px;line-height:1.5;'>
                        Si tiene cualquier duda, puede responder a este correo.
                    </p>
                </div>
            </div>
        ";

            Mail::html($html, function ($mail) use ($email) {
                $mail->to($email)
                    ->subject('Confirmación de pago de su instalación de cargador');
            });

        } catch (\Throwable $e) {
            Log::error('Error enviando correo de confirmación al cliente', [
                'email' => $email,
                'message' => $e->getMessage(),
            ]);
        }
    }
}


