<?php

namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use App\Http\Models\Account;
use App\Http\Models\Enterprise;
use App\Http\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client;

class TwilioController extends Controller
{
    //Función que realiza llamada mediante twilio
    public static function startCall(Request $request){

        $order = $request['order'];
        $orderList = $request['orderList'];
        $account = $request['account'];
        $name = $request['name'];
        $company = $request['company'];
        $enterprise = $request['enterprise'];

        // Saco credenciales de twilio
        $twilio_account_sid = env('TWILIO_ACCOUNT_SID');
        $twilio_auth_token = env('TWILIO_AUTH_TOKEN');

        // Creo instancia de Twilio
        $twilio = new Client($twilio_account_sid, $twilio_auth_token);

        // Hago encode a los valores que voy a pasar a twilio
        $nameEncoded = rawurlencode($name);
        $companyEncoded = rawurlencode($company);

        if (!isset($company)) {
            $CUPSEncoded = rawurlencode(implode(', ', str_split($order['CUPS'])));
            $orderProductEncoded = rawurlencode($order['product']);
            $addressEncoded = rawurlencode($order['direc'] . ', ' . $order['town'] . ', ' . $order['province'] . ', ' . $order['zip']);
        } else {
            $cupsArray = [];
            $productsArray = [];
            $addressesArray = [];

            foreach ($orderList as $orderItem) {
                if (!empty($orderItem['CUPS'])) {
                    $cupsArray[] = $orderItem['CUPS'];
                }

                if (!empty($orderItem['product'])) {
                    $productsArray[] = $orderItem['product'];
                }

                if (!empty($orderItem['direc'])) {
                    $addressesArray[] = $orderItem['direc'] . ' ' . $orderItem['town'] . ' ' . $orderItem['province'] . ' ' . $orderItem['zip'];
                }
            }

            $CUPS = implode(', ', $cupsArray);
            $CUPSEncoded = rawurlencode($CUPS);

            $productsArray = array_unique($productsArray);
            $products = implode(', ', $productsArray);
            $orderProductEncoded = rawurlencode($products);

            $addressesArray = array_unique($addressesArray);
            $addresses = implode(', ', $addressesArray);
            $addressEncoded = rawurlencode($addresses);
        }

        $isCompanyEncoded = isset($company) ? 'true' : 'false';

        /*
         * Datos para callback de finalización.
         * Si no hay company, la llamada pertenece a un contrato.
         * Si hay company, la llamada pertenece a una cuenta.
         */
        $callbackId = !isset($company) ? (string) $order['_id'] : (string) $account['_id'];

        $isOrderCallback = !isset($company) ? 'true' : 'false';


        //Si es tramitado con nosotros compruebo si tiene habilitado el llamar con nuestros minutos
        if(isset($order['assignedTo']) && $order['assignedTo'] === '65cb57489c2c285441086a43' && isset($enterprise['settings']['allowZocoVerificationCalls']) && $enterprise['settings']['allowZocoVerificationCalls'])
            $enterprise = Enterprise::where('_id', '67a32d1cdfbaaec2da6bf86e')->first()->toArray();


        // URL pública. En local, APP_URL debe ser la URL de ngrok.
        $query = http_build_query([
            'id' => $callbackId,
            'isOrder' => $isOrderCallback,
            'enterpriseId' => (string) $enterprise['_id']
        ]);

        $statusCallbackUrl = "https://crm.zocoenergia.com/api/twilio/countCallMinutes?$query";

        // Configuro y realizo la llamada
        $call = $twilio->calls->create(
            "+34" . $account['phone'],
            "+34516516739",
            [
                "record" => true,
                "timeout" => 15,

                "url" => "https://naturgycalls-8531.twil.io/index?name={$nameEncoded}&company={$companyEncoded}&isCompany={$isCompanyEncoded}&CUPS={$CUPSEncoded}&address={$addressEncoded}&product={$orderProductEncoded}",

                /*
                 * Cuando la llamada termine, Twilio hará POST aquí.
                 * Ahí sacaremos duración y consumiremos minutos.
                 */
                "statusCallback" => $statusCallbackUrl,
                "statusCallbackEvent" => ["completed"],
                "statusCallbackMethod" => "POST",
            ]
        );

        // GUARDO ID LLAMADA
        if (!isset($company)) {
            $orderToEdit = Order::where('_id', $order['_id'])->first();
            $orderToEdit['naturgyCallSID'] = $call->sid;
            $orderToEdit->save();
        } else {
            $accountToEdit = Account::where('_id', $account['_id'])->first();
            $accountToEdit['naturgyCallSID'] = $call->sid;
            $accountToEdit->save();
        }

        return response()->json(['naturgyCallSID' => $call->sid], 200);
    }

    //Funciones para llamada de conferencia con voip (para solo cobrar 1 llamada)
    public static function getVoiceToken(Request $request)
    {
        $twilio_account_sid   = env('TWILIO_ACCOUNT_SID');
        $twilio_api_key       = env('TWILIO_API_KEY');
        $twilio_api_secret    = env('TWILIO_API_SECRET');
        $twilio_twiml_app_sid = env('TWILIO_TWIML_APP_SID');

        // Generar un identity único basado en el usuario o timestamp
        $identity = 'user-' . auth()->id() . '-' . time();

        $token = new \Twilio\Jwt\AccessToken(
            $twilio_account_sid,
            $twilio_api_key,
            $twilio_api_secret,
            3600,
            $identity
        );

        $grant = new \Twilio\Jwt\Grants\VoiceGrant();
        $grant->setOutgoingApplicationSid($twilio_twiml_app_sid);
        $grant->setIncomingAllow(true);
        $token->addGrant($grant);

        return response()->json(['token' => $token->toJWT()]);
    }

    //Función que maneja las llamadas salientes VoIP
    public static function voice(Request $request)
    {
        Log::info('TWILIO VOICE PARAMS', [
            'all' => $request->all(),
            'To' => $request->input('To'),
            'id' => $request->input('id'),
            'isOrder' => $request->input('isOrder'),
            'enterpriseId' => $request->input('enterpriseId'),
        ]);

        $to = $request->input('To');
        $orderId = $request->input('id');
        $isOrder = $request->input('isOrder');
        $enterpriseId = $request->input('enterpriseId');

        if (!$to || !$orderId) {
            return response('No destination provided', 400);
        }

        $twiml = new \Twilio\TwiML\VoiceResponse();

        $dialOptions = [
            'callerId' => '+34516516739',
            'record' => 'record-from-answer',
            'recordingStatusCallback' => url('/api/twilio/recording-callback?id=' . $orderId . '&isOrder=' . $isOrder),
        ];

        if ($enterpriseId) {

            $order = Order::where('_id', $orderId)->first();
            $enterprise = Enterprise::where('_id', $enterpriseId)->first();

            //Si es tramitado con nosotros compruebo si tiene habilitado el llamar con nuestros minutos
            if(isset($order->assignedTo) && $order->assignedTo === '65cb57489c2c285441086a43' && isset($enterprise->settings['allowZocoVerificationCalls']) && $enterprise->settings['allowZocoVerificationCalls'])
                $enterpriseId = '67a32d1cdfbaaec2da6bf86e';

            $query = http_build_query([
                'id' => $orderId,
                'isOrder' => $isOrder,
                'enterpriseId' => $enterpriseId
            ]);

            // Twilio llamará a este endpoint cuando termine el <Dial>
            $dialOptions['action'] = url('/api/twilio/countCallMinutes?' . $query);
            $dialOptions['method'] = 'POST';
        }

        $dial = $twiml->dial('', $dialOptions);

        $dial->number($to);

        Log::info('TWIML GENERADO', [
            'xml' => (string) $twiml,
            'dialOptions' => $dialOptions,
        ]);

        return response($twiml, 200)->header('Content-Type', 'text/xml');
    }

    //Función para guardar la grabación de la llamada una vez termine ( cuando sea personal )
    public function recordingCallback(Request $request)
    {
        $recordingSid = $request->RecordingSid;
        $recordingUrl = $request->RecordingUrl;
        $id           = $request->query('id');
        $isOrder      = filter_var($request->input('isOrder'), FILTER_VALIDATE_BOOLEAN);


        if ($isOrder) {
            Order::where('_id', $id)->update([
                '$unset' => [
                    'naturgyCallSID' => true
                ],
                '$set' => [
                    'naturgyCallRecordingSid' => $recordingSid,
                    'naturgyCallRecordingUrl' => $recordingUrl . '.mp3',
                ]
            ]);
        } else {
            Account::where('_id', $id)->update([
                '$unset' => [
                    'naturgyCallSID' => true
                ],
                '$set' => [
                    'naturgyCallRecordingSid' => $recordingSid,
                    'naturgyCallRecordingUrl' => $recordingUrl . '.mp3',
                ]
            ]);
        }

        return response()->json(['ok' => true]);
    }

    //Función para llamada de conferencia ( llamada entre agente y cliente grabada por telefono )
    public static function startConferenceCall()//Request $request
    {
        $agentPhone  = '+34957855980';//$request['agentPhone'];
        $clientPhone = '+34605581287';//$request['clientPhone'];

        $conferenceUrl = 'https://conferencecalls-9448.twil.io/index';

        // Credenciales
        $twilio_account_sid = env('TWILIO_ACCOUNT_SID');
        $twilio_auth_token  = env('TWILIO_AUTH_TOKEN');

        // Instancia Twilio
        $twilio = new Client($twilio_account_sid, $twilio_auth_token);

        // Llamada al cliente
        $callClient = $twilio->calls->create(
            $clientPhone,
            '+34516516739',
            [
                'url' => $conferenceUrl,
                'timeout' => 15,
                'record'  => true
            ]
        );

        // Llamada al agente
        $callAgent = $twilio->calls->create(
            $agentPhone,
            '+34516516739',
            ['url' => $conferenceUrl, 'timeout' => 15]
        );

        return response()->json([
            'clientCallSid' => $callClient->sid,
            'agentCallSid'  => $callAgent->sid,
        ], 200);
    }

    public static function sendSms(Request $request)
    {
        $phone = $request['phone'];
        $order = $request['order'];

        if (!$phone) {
            return response()->json(['error' => 'Teléfono requerido'], 400);
        }

        // Normalizar teléfono
        if (!str_starts_with($phone, '+')) {
            $phone = '+34' . preg_replace('/[^0-9]/', '', $phone);
        }

        // Credenciales
        $twilio_account_sid = config('services.twilio.sid');
        $twilio_auth_token = config('services.twilio.token');
        $twilio_phone_number = config('services.twilio.phone');

        $twilio = new Client($twilio_account_sid, $twilio_auth_token);

        // 🔥 GENERAR ENLACE DINÁMICO
        $frontendUrl = config('app.url');
        $documentsLink = $frontendUrl . "/orders/documents/" . $order['_id'];

        $messageBody = "Hola 👋\n\n"
            . "Puede subir la documentación necesaria (DNI, factura, etc.) en el siguiente enlace:\n\n"
            . $documentsLink . "\n\n"
            . "Gracias.";

        try {

            $message = $twilio->messages->create(
                $phone,
                [
                    "from" => $twilio_phone_number,
                    "body" => $messageBody
                ]
            );

            return response()->json([
                'success' => true,
                'sid' => $message->sid
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //Función que devuelve estado de llamada twilio
    public static function getCallStatus(Request $request){

        //Obtengo datos contrato
        if (!isset($request['account']))
            $callSID = $request['order']['naturgyCallSID'];
        else
            $callSID = $request['account']['naturgyCallSID'];


        //Saco credenciales de twilio
        $twilio_account_sid = env('TWILIO_ACCOUNT_SID');
        $twilio_auth_token = env('TWILIO_AUTH_TOKEN');

        //Creo instancia de Twilio
        $twilio = new Client($twilio_account_sid, $twilio_auth_token);

        // Recupero la info
        try {
            $call = $twilio->calls($callSID)->fetch();
        } catch (\Twilio\Exceptions\RestException $e) {
            //Si no encuentra la llamada devolvera el fallo
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['status' => $call->status], 200);
    }

    //Función para descargar llamada y adjuntarla a contrato
    public static function downloadNaturgyCall(Request $request)
    {
        $order = $request['order'];
        $account = $request['account'];

        $twilio_account_sid = env('TWILIO_ACCOUNT_SID');
        $twilio_auth_token = env('TWILIO_AUTH_TOKEN');

        $client = new \GuzzleHttp\Client();

        // Determinar origen de datos
        $source = $order ?? $account;


        //Si es con el bot y se descarga de la propia llamada ( se almacena junto a la llamada )
        if (!empty($source['naturgyCallSID'])) {

            $twilio = new Client($twilio_account_sid, $twilio_auth_token);

            $recordings = $twilio->recordings->read([
                'callSid' => $source['naturgyCallSID']
            ], 1);

            if (count($recordings) === 0) {
                return response()->json(['error' => 'No recordings found for that call.'], 404);
            }

            $recordingSid = $recordings[0]->sid;

            $recordingUrl = "https://api.twilio.com/2010-04-01/Accounts/{$twilio_account_sid}/Recordings/{$recordingSid}.mp3";
        }

        //Si es llamada personal ( va aparte, cuando termina la llamada con el webhook )
        elseif (!empty($source['naturgyCallRecordingUrl'])) {

            // Ya viene casi lista, solo aseguramos .mp3
            $recordingUrl = $source['naturgyCallRecordingUrl'];

            if (!str_ends_with($recordingUrl, '.mp3')) {
                $recordingUrl .= '.mp3';
            }
        }


        //Si no encuentra nada
        else {
            return response()->json([
                'error' => 'No hay datos de llamada disponibles'
            ], 404);
        }


        //Descargo el archivo
        try {
            $response = $client->request('GET', $recordingUrl, [
                'auth' => [$twilio_account_sid, $twilio_auth_token],
                'timeout' => 20,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error requesting the recording: ' . $e->getMessage()
            ], 500);
        }

        if ($response->getStatusCode() !== 200) {
            return response()->json([
                'error' => 'Non-200 response: ' . $response->getStatusCode()
            ], 500);
        }

        $audioData = $response->getBody()->getContents();
        $audioName = time() . "naturgyVerification.mp3";


        //Guardo en contrato o en cuenta
        if (isset($order)) {

            Storage::disk('order')->put($audioName, $audioData);

            $orderToEdit = Order::where('_id', $order['_id'])->first();

            $doc = [
                'title' => 'Llamada verificación Naturgy',
                'defaultTitle' => 'Llamada verificación Naturgy',
                'value' => $audioName,
                'errors' => []
            ];

            $docs = $orderToEdit->docs ?? [];
            $docs[] = $doc;

            $orderToEdit->docs = collect($docs)->unique('value')->values()->toArray();
            $orderToEdit->save();

        } else {

            Storage::disk('account')->put($audioName, $audioData);

            $accountToEdit = Account::where('_id', $account['_id'])->first();

            $doc = [
                'title' => 'Llamada verificación Naturgy',
                'type' => 'image',
                'value' => $audioName,
                'fileType' => 'audio/mpeg'
            ];

            $customFields = $accountToEdit->customFields ?? [];
            $customFields[] = $doc;

            $accountToEdit->customFields = collect($customFields)->unique('value')->values()->toArray();
            $accountToEdit->save();
        }

        return response()->json(['message' => 'La llamada de verificación ha sido adjuntada'], 200);
    }

    //Función para sacar los minutos que ha tardado la llamada y contabilizarlo en el subdominio
    public function countCallMinutes(Request $request)
    {
        $id = $request->query('id');
        $isOrder = filter_var($request->query('isOrder'), FILTER_VALIDATE_BOOLEAN);
        $enterpriseId = $request->query('enterpriseId');

        if (!$id) {
            return response()->json([
                'error' => 'Falta el id',
            ], 400);
        }

        if (!$enterpriseId) {
            return response()->json([
                'error' => 'Falta enterpriseId',
            ], 400);
        }

        $model = null;

        if ($isOrder) {
            $model = Order::find($id);
        } else {
            $model = Account::find($id);
        }

        if (!$model) {
            return response()->json([
                'error' => $isOrder ? 'Pedido no encontrado' : 'Cuenta no encontrada',
            ], 404);
        }

        $callSid = $request->input('CallSid') ?? ($model->naturgyCallSID ?? null);

        if (!$callSid) {
            return response()->json([
                'minutes' => 0,
                'seconds' => 0,
                'calls_count' => 0,
            ]);
        }

        /*
         * Twilio suele enviar CallDuration en el statusCallback.
         * Si no viene, lo recuperamos desde la API.
         */
        $seconds = (int) $request->input('DialCallDuration', 0);

        if ($seconds <= 0) {
            $seconds = (int) $request->input('CallDuration', 0);
        }

        if ($seconds <= 0) {
            $twilio = new \Twilio\Rest\Client(
                env('TWILIO_ACCOUNT_SID'),
                env('TWILIO_AUTH_TOKEN')
            );

            $call = $twilio->calls($callSid)->fetch();

            $seconds = (int) ($call->duration ?? 0);
        }

        /*
         * 4 segundos = 1 minuto facturable.
         * 0 segundos = 0 minutos.
         */
        $minutes = $seconds > 0
            ? (int) ceil($seconds / 60)
            : 0;

        $enterprise = Enterprise::find($enterpriseId);

        if (!$enterprise) {
            return response()->json([
                'error' => 'Empresa no encontrada',
            ], 404);
        }

        $subscription = $enterprise->subscription ?? [];

        /*
         * Inicializo estructura base si falta.
         */
        if (!isset($subscription['usage']) || !is_array($subscription['usage'])) {
            $subscription['usage'] = [];
        }

        if (!isset($subscription['usage']['calls'])) {
            $subscription['usage']['calls'] = 0;
        }

        if (!isset($subscription['extras']) || !is_array($subscription['extras'])) {
            $subscription['extras'] = [];
        }

        if (!isset($subscription['extras']['one_time']) || !is_array($subscription['extras']['one_time'])) {
            $subscription['extras']['one_time'] = [];
        }

        if (!isset($subscription['extras']['one_time']['calls']) || !is_array($subscription['extras']['one_time']['calls'])) {
            $subscription['extras']['one_time']['calls'] = [
                'amount' => 0,
                'remaining' => 0,
                'purchasedAt' => null,
                'stripe_payment_intent_id' => null,
            ];
        }

        if (!isset($subscription['excesses']) || !is_array($subscription['excesses'])) {
            $subscription['excesses'] = [];
        }

        if (!isset($subscription['excesses']['calls'])) {
            $subscription['excesses']['calls'] = 0;
        }

        /*
         * Datos actuales.
         */
        $includedCalls = data_get($subscription, 'included.calls', 0);
        $currentUsageCalls = (int) data_get($subscription, 'usage.calls', 0);

        $callsExtraRemaining = (int) data_get($subscription, 'extras.one_time.calls.remaining', 0);

        $minutesToConsume = $minutes;
        $consumedFromParts = [];

        /*
         * 1. Primero consumo de los minutos incluidos del plan.
         */
        if ($includedCalls === null) {
            $subscription['usage']['calls'] = $currentUsageCalls + $minutesToConsume;

            $minutesToConsume = 0;
            $consumedFromParts[] = 'plan';
        } else {
            $includedCalls = (int) $includedCalls;

            $availablePlanMinutes = max($includedCalls - $currentUsageCalls, 0);

            $consumeFromPlan = min($minutesToConsume, $availablePlanMinutes);

            if ($consumeFromPlan > 0) {
                $subscription['usage']['calls'] = $currentUsageCalls + $consumeFromPlan;

                $minutesToConsume -= $consumeFromPlan;
                $consumedFromParts[] = 'plan';
            } else {
                $subscription['usage']['calls'] = $currentUsageCalls;
            }
        }

        /*
         * 2. Si la llamada no ha quedado cubierta por el plan,
         * consumo de los minutos extra comprados.
         */
        if ($minutesToConsume > 0) {
            $availableExtraMinutes = max($callsExtraRemaining, 0);

            $consumeFromExtras = min($minutesToConsume, $availableExtraMinutes);

            if ($consumeFromExtras > 0) {
                $subscription['extras']['one_time']['calls']['remaining'] =
                    $callsExtraRemaining - $consumeFromExtras;

                $minutesToConsume -= $consumeFromExtras;
                $consumedFromParts[] = 'extras';
            } else {
                $subscription['extras']['one_time']['calls']['remaining'] = $callsExtraRemaining;
            }
        }

        /*
         * 3. Si no ha quedado cubierta ni por plan ni por extras,
         * el sobrante se guarda en excesos.
         */
        if ($minutesToConsume > 0) {
            $subscription['excesses']['calls'] =
                (int) ($subscription['excesses']['calls'] ?? 0) + $minutesToConsume;

            $consumedFromParts[] = 'excesses';
        }

        $consumedFrom = count($consumedFromParts)
            ? implode('_and_', $consumedFromParts)
            : 'none';

        $enterprise->subscription = $subscription;
        $enterprise->save();

        /*
         * Guardo datos de la última llamada, pero ya NO bloqueo futuras llamadas.
         */
        $model->naturgyCallSID = $callSid;
        $model->naturgyCallSeconds = $seconds;
        $model->naturgyCallBillableMinutes = $minutes;
        $model->naturgyCallConsumedFrom = $consumedFrom;
        $model->naturgyCallCountedAt = new \MongoDB\BSON\UTCDateTime();
        $model->save();

        return response()->json([
            'minutes' => $minutes,
            'seconds' => $seconds,
            'calls_count' => 1,
            'consumed_from' => $consumedFrom,

            'usage_calls' => (int) data_get($subscription, 'usage.calls', 0),

            'extra_calls_amount' => (int) data_get($subscription, 'extras.one_time.calls.amount', 0),
            'extra_calls_remaining' => (int) data_get($subscription, 'extras.one_time.calls.remaining', 0),

            'excess_calls' => (int) data_get($subscription, 'excesses.calls', 0),
        ]);
    }


    public function availableCallMinutes(Request $request)
    {
        $enterpriseId = $request->query('enterpriseId');
        $orderId = $request->query('orderId');

        $order = Order::where('_id', $orderId)->first();

        $enterprise = Enterprise::find($enterpriseId);

        //Si es tramitado con nosotros compruebo si tiene habilitado el llamar con nuestros minutos
        if(isset($order->assignedTo) && $order->assignedTo === '65cb57489c2c285441086a43' && isset($enterprise->settings['allowZocoVerificationCalls']) && $enterprise->settings['allowZocoVerificationCalls'])
            $enterprise = Enterprise::find('67a32d1cdfbaaec2da6bf86e');


        if (!$enterprise) {
            return response()->json(['message' => 'Empresa no encontrada'], 404);
        }

        $subscription = $enterprise->subscription ?? [];

        $includedCalls = data_get($subscription, 'included.calls', 0);
        $usedCalls = (int) data_get($subscription, 'usage.calls', 0);
        $extraRemaining = (int) data_get($subscription, 'extras.one_time.calls.remaining', 0);

        if ($includedCalls === null) {
            return response()->json([
                'available' => true,
                'available_minutes' => null,
                'plan_remaining' => null,
                'extra_remaining' => $extraRemaining,
            ]);
        }

        $planRemaining = max(((int) $includedCalls) - $usedCalls, 0);
        $availableMinutes = $planRemaining + max($extraRemaining, 0);

        return response()->json([
            'available' => $availableMinutes > 0,
            'available_minutes' => $availableMinutes,
            'plan_remaining' => $planRemaining,
            'extra_remaining' => $extraRemaining,
        ]);
    }

}
