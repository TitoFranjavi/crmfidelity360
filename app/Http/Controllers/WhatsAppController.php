<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Log;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\User;
use App\Models\Order;
use App\Models\Signin;
use GuzzleHttp\Client;
use App\Models\Account;
use App\Models\Contact;
use MongoDB\BSON\Regex;
use App\Models\Marketer;
use App\Models\Enterprise;
use MongoDB\BSON\ObjectId;
use App\Helpers\UserHelper;
use App\Models\Opportunity;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\WhatsAppSession;
use function PHPSTORM_META\map;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use function PHPUnit\Framework\isNan;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\MarketerController;
use App\WhatsApp\UltraMsgDriver;
use App\WhatsApp\WhapiDriver;
use Illuminate\Support\Facades\Cache;


//INFO: Para sacar los precios de las comparativas se usa los consumos del PDF ya que es mensual
//INFO: Para sacar las comisiones se usa los consumos y potencias del SIPS, ya qué es anual y es por lo que busca en el módulo de productos ( cuando no saca del SIPS se calcula un estimado )


class WhatsAppController extends Controller
{

    protected static $WHATSAPP_INSTANCE_ID;
    protected static $WHATSAPP_INSTANCE_TOKEN;

    protected UltraMsgDriver|WhapiDriver|null $whatsappDriver = null;

    protected string $whatsappProvider = 'ultramsg';


    //Funcion que recibe mensajes
    public function receiveMessage(Request $request){

        try {
            $payload = $request->all();

            $enterprise = $this->bootWhatsAppDriver($payload);

            $incoming = $this->parseIncomingMessage($payload);

            $from = $incoming['from'];
            $message = $incoming['message'];
            $mediaUrl = $incoming['mediaUrl'];
            $mediaType = $incoming['mediaType'];
            $fileName = $incoming['fileName'];
            $quotedId = $incoming['quotedId'] ?? null;

            if (!$from || ($message === '' && !$mediaUrl && $mediaType !== 'location'))
                return response()->json(['ignored' => true]);



            /*$type = $payload['data']['type'] ?? $payload['type'] ?? 'text';

            if ($type === 'location') {
                $latitude  = $payload['data']['latitude']  ?? $payload['latitude']  ?? null;
                $longitude = $payload['data']['longitude'] ?? $payload['longitude'] ?? null;
                $address   = $payload['data']['address']   ?? $payload['address']   ?? null;
            }*/

            //Tipos de productos ( contratos )
            $productTypes = [
                [
                    'code' => 'cl',
                    'title' => 'Contrato de luz'
                ],
                [
                    'code' => 'cg',
                    'title' => 'Contrato de gas'
                ],
                [
                    'code' => 'a',
                    'title' => 'Autoconsumo'
                ],
                [
                    'code' => 'bc',
                    'title' => 'Batería de condensadores'
                ],
                [
                    'code' => 'ce',
                    'title' => 'Coche eléctrico'
                ],
                [
                    'code' => 'c',
                    'title' => 'Contador'
                ],
                [
                    'code' => 'i',
                    'title' => 'Iluminación'
                ]
            ];
            //Productos básicos
            $basicProducts = ['Sin excedentes', 'Con excedentes', 'Compartido'];



            if ($from && strpos($from, '@c.us') !== false) {
                $from = str_replace('@c.us', '', $from);
            }

            //Compruebo si tiene un usuario en el crm para crear
            $cleanFrom = preg_replace('/\D+/', '', $from);
            $phoneWithoutPrefix = $cleanFrom;

            if (str_starts_with($phoneWithoutPrefix, '34') && strlen($phoneWithoutPrefix) > 9) {
                $phoneWithoutPrefix = substr($phoneWithoutPrefix, 2);
            }

            $phoneVariants = array_values(array_unique([
                $cleanFrom,
                $phoneWithoutPrefix,
                '+34' . $phoneWithoutPrefix,
                '34' . $phoneWithoutPrefix,
            ]));

            $user = User::where(function ($query) use ($phoneVariants) {
                foreach ($phoneVariants as $phoneVariant) {
                    $query->orWhere('phone', $phoneVariant)
                        ->orWhere('secondaryPhones', $phoneVariant);
                }
            })->first();

            //Saco la sesión 🔍
            $session = WhatsAppSession::where('phone', $from)->where('instance', self::$WHATSAPP_INSTANCE_ID)->first();

            if ($session && $quotedId) {
                $guardResponse = $this->guardInteractiveReply($from, $quotedId, $message);

                if ($guardResponse)
                    return $guardResponse;

            }

            //Si no la hay la creo
            if (!$session) {
                if ($user) {
                    $session = WhatsAppSession::create(['phone' => $from, 'instance' => self::$WHATSAPP_INSTANCE_ID, 'step' => 'start_crm', 'app' => 'crm']);

                    if (self::$WHATSAPP_INSTANCE_ID === 'instance168959') {
                        $message = "Hola " . $user->firstName . "\n" .
                            "¿Qué quieres hacer?\n\n" .
                            "_En cualquier momento puedes cancelar el flujo escribiendo '*Cancelar*'_";

                        $options = [
                            ['id' => '1', 'title' => 'Realizar comparativa'],
                            ['id' => '2', 'title' => 'Crear oportunidad'],
                            ['id' => '3', 'title' => 'Crear cuenta'],
                            ['id' => '4', 'title' => 'Crear contrato'],
                            ['id' => '5', 'title' => 'Fichar'],
                        ];
                    } else {

                        $message = "Hola " . $user->firstName . "\n" .
                            "¿Qué quieres hacer?\n\n" .
                            "_En cualquier momento puedes cancelar el flujo escribiendo '*Cancelar*'_";

                        $options = [
                            ['id' => '1', 'title' => 'Crear contacto'],
                            ['id' => '2', 'title' => 'Realizar comparativa'],
                            ['id' => '3', 'title' => 'Crear oportunidad'],
                            ['id' => '4', 'title' => 'Crear cuenta'],
                            ['id' => '5', 'title' => 'Crear contrato'],
                            ['id' => '6', 'title' => 'Fichar']
                        ];
                    }

                    return $this->sendOptions($from, $message, $options);

                }
                else {
                    $session = WhatsAppSession::create(['phone' => $from, 'instance' => self::$WHATSAPP_INSTANCE_ID, 'step' => 'invoice_or_data', 'app' => 'witro']);
                    $enterpriseUserId = $this->getEnterpriseWhatsappUserId($enterprise);
                    $witroName = User::where('_id', $enterpriseUserId)->pluck('firstName')->first();
                    $message = "*¡Hola 👋!* \n" .
                        "Bienvenido a *". $witroName . "*, tu asistente para encontrar la *tarifa de luz más barata.⚡️*\n\n" .
                        "¿Qué tipo de comparativa quieres hacer?\n\n" .
                        "_En cualquier momento puedes cancelar el flujo escribiendo '*Cancelar*'_";

                    $options = [
                        ['id' => '1', 'title' => 'Envía tu factura en PDF y ahorra al instante. 📄'],
                        ['id' => '2', 'title' => 'Introduce los datos de manera manual'],
                    ];

                    return $this->sendOptions($from,$message, $options);
                }
            }

            //Mensaje de cancelar el flujo
            if (strtolower($message) === 'cancelar') {
                $session->delete();
                return $this->sendMessage($from, '👋¡Hasta pronto!');
            }

            //Si no existe usuario se selecciona el Witro de la empresa que toque
            if (!isset($user)){
                $enterpriseUserId = $this->getEnterpriseWhatsappUserId($enterprise);
                $user = User::where('_id', $enterpriseUserId)->first();
            }


            //Saco todas las comercializadoras
            if ($session->step == 'contract_productType' || $session->step == 'contract_marketer' || $session->step == 'invoice_upload' || $session->step == 'comparative_when' || $session->step == 'comparative_options')
                $marketers = (new MarketerController())->getMarketersBySubdomain($user);



            switch ($session->step) {

                //Selección de creación
                case 'start_crm':

                    if (self::$WHATSAPP_INSTANCE_ID === 'instance168959') {
                        $options = [
                            '1' => 'comparative',
                            '2' => 'opportunity',
                            '3' => 'account',
                            '4' => 'contract',
                        ];
                    } else {
                        $options = [
                            '1' => 'contact',
                            '2' => 'comparative',
                            '3' => 'opportunity',
                            '4' => 'account',
                            '5' => 'contract',
                            '6' => 'signing',
                        ];
                    }

                    if (!isset($options[$message])) {
                        return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }

                    switch ($options[$message]) {
                        case 'contact':
                            $session->update([
                                'step' => 'contact_options',
                                'type' => 'contact'
                            ]);



                            return $this->sendOptions(
                                $from,
                                "Elige una opción",
                                [
                                    ['id' => '1', 'title' => 'Crear manualmente'],
                                    ['id' => '2', 'title' => 'Crear desde tarjeta de contacto con IA']
                                ]
                            );

                        case 'comparative':
                            $session->update([
                                'step' => 'type_filter',
                                'type' => 'comparative'
                            ]);

                            return $this->sendOptions(
                                $from,
                                "¿Para qué tipo de inmueble es el suministro?",
                                [
                                    ['id' => '1', 'title' => 'Vivienda'],
                                    ['id' => '2', 'title' => 'Negocio']
                                ]
                            );

                        case 'opportunity':
                            $session->update([
                                'step' => 'opp_name',
                                'type' => 'opportunity'
                            ]);

                            return $this->sendMessage(
                                $from,
                                "Introduce el nombre de la oportunidad"
                            );

                        case 'account':
                            $session->update([
                                'step' => 'account_options',
                                'type' => 'account'
                            ]);

                            return $this->sendOptions(
                                $from,
                                "Elige una opción",
                                [
                                    ['id' => '1', 'title' => 'Crear cuenta nueva'],
                                    ['id' => '2', 'title' => 'Crear desde oportunidad']
                                ]
                            );

                        case 'contract':
                            $session->update([
                                'step' => 'contract_option',
                                'type' => 'contract'
                            ]);

                            return $this->sendOptions(
                                $from,
                                "Elige una opción",
                                [
                                    ['id' => '1', 'title' => 'Añadir a cuenta existente'],
                                    ['id' => '2', 'title' => 'Crear nueva cuenta']
                                ]
                            );

                        case 'signing':
                            $signingClient = new SigningController();

                            $response = $signingClient->getLastStatus($user->id);
                            $data = $response->getData(true);

                            $session->update([
                                'step' => 'signing_options'
                            ]);

                            if (($data['type'] ?? null) === 'auto_closed_previous_day') {
                                return $this->sendOptions(
                                    $from,
                                    "Había un fichaje anterior sin salida y se ha cerrado automáticamente.\n\n" . "Elige una opción",
                                    [
                                        ['id' => '1', 'title' => 'Fichar entrada'],
                                        ['id' => '2', 'title' => 'Adjuntar parte de trabajo']
                                    ]
                                );
                            }

                            if ($data['should_sign_in']) {
                                return $this->sendOptions(
                                    $from,
                                    "Elige una opción",
                                    [
                                        ['id' => '1', 'title' => 'Fichar entrada'],
                                        ['id' => '2', 'title' => 'Adjuntar parte de trabajo']
                                    ]
                                );
                            }

                            return $this->sendOptions(
                                $from,
                                "Elige una opción",
                                [
                                    ['id' => '1', 'title' => 'Fichar salida'],
                                    ['id' => '2', 'title' => 'Adjuntar parte de trabajo']
                                ]
                            );
                    }

                    break;


                //🙋🏼CONTACTO
                case 'contact_options':

                    switch ($message) {
                        case 1:
                            $session->update(['step' => 'contact_name']);
                            return $this->sendMessage($from, "️Introduce el nombre del contacto *( sin apellidos )*:");
                            break;

                        case 2:

                            $session->update(['step' => 'card_upload']);
                            return $this->sendMessage($from, "Adjunta la tarjeta de contacto");


                        default:
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }

                //manual
                case 'contact_name':

                    //Separo el nombre por espacios por si es compuesto
                    $message = explode(' ', $message);

                    $name = [
                        'first' => $message[0],
                        'second' => $message[1] ?? ''
                    ];

                    $data = $session->data;
                    $data['contact']['name'] = $name;

                    //Si no tiene los apellidos
                    if (!isset($data['contact']['surname']['first']) || $data['contact']['surname']['first'] === '') {
                        $session->update(['data' => $data, 'step' => 'contact_surname']);
                        return $this->sendMessage($from, "Introduce los apellidos del contacto:");
                    } elseif (!isset($data['contact']['phone']) || $data['contact']['phone'] === '') { //si no tiene el telefono
                        $session->update(['data' => $data, 'step' => 'contact_phone']);
                        return $this->sendMessage($from, "Introduce el teléfono del contacto:");
                    } else {
                        $session->update(['data' => $data, 'step' => 'contact_confirm']);
                        return $this->resumeContact($session, $from);
                    }
                case 'contact_surname':

                    //Separo los apellidos por espacios por si ha puesto más de uno
                    $message = explode(' ', $message);

                    $surname = [
                        'first' => $message[0],
                        'second' => $message[1] ?? ''
                    ];

                    $data = $session->data;
                    $data['contact']['surname'] = $surname;

                    if (!isset($data['contact']['phone']) || $data['contact']['phone'] === '') { //si no tiene el telefono
                        $session->update(['data' => $data, 'step' => 'contact_phone']);
                        return $this->sendMessage($from, "Introduce el teléfono del contacto:");
                    } else {
                        $session->update(['data' => $data, 'step' => 'contact_confirm']);
                        return $this->resumeContact($session, $from);
                    }
                case 'contact_phone':
                    if (strlen($message) !== 9) {
                        return $this->sendMessage($from, "❌ El teléfono no esta bien formado. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['contact']['phone'] = $message;

                    //Ya que he pedido los campos obligatorios mando al resumen de contacto, si quiere añadir más campos lo podrá hacer desde allí
                    $session->update(['data' => $data, 'step' => 'contact_confirm']);
                    return $this->resumeContact($session, $from);
                    break;

                //por IA
                case 'card_upload':

                    try {

                        $data = $session->data;

                        if (!$mediaUrl || !isset($mediaUrl))
                            return $this->sendMessage($from, "❌ No detecté un archivo adjunto. Asegúrate de enviarlo correctamente.");


                        //Paso imagen a chatgpt
                        $this->getCardData($data, $mediaUrl, $from);


                        //Compruebo si hace falta algún campo obligatorio
                        if (!isset($data['contact']['name']['first']) || $data['contact']['name']['first'] === '') {
                            $session->update(['data' => $data, 'step' => 'contact_name']);
                            return $this->sendMessage($from, "Introduce el nombre del contacto *( sin apellidos )*:");
                        }

                        if (!isset($data['contact']['surname']) || $data['contact']['surname'] === '') {
                            $session->update(['data' => $data, 'step' => 'contact_surname']);
                            return $this->sendMessage($from, "Introduce los apellidos:");
                        }

                        if (!isset($data['contact']['phone']) || $data['contact']['phone'] === '') {
                            $session->update(['data' => $data, 'step' => 'contact_phone']);
                            return $this->sendMessage($from, "Introduce el teléfono del contacto:");
                        }


                        //Muestro el resumen del contacto
                        $session->update(['data' => $data, 'step' => 'contact_confirm']);
                        return $this->resumeContact($session, $from);
                    } catch (\Throwable $e) {

                        $errorMessage = "❌ *Error con la imagen*\n";
                        $errorMessage .= "Asegurate que la imagen adjuntada es válida";
                        $errorMessage .= $e->getMessage() . '  ';
                        $errorMessage .= $e->getFile() . '  ';
                        $errorMessage .= $e->getLine();

                        // Limitar longitud si lo envías por WhatsApp
                        return $this->sendMessage($from, Str::limit($errorMessage, 3900));
                    }


                    break;


                //🔍OPORTUNIDAD
                case 'existing_opp':
                    $data = $session->data;

                    $opp = Opportunity::where('CIF', 'regex', new Regex('^' . preg_quote($message) . '$', 'i'))->where('usersIds', $user->_id)->first();

                    if (!$opp) {
                        return $this->sendMessage($from, "Oportunidad no encontrada. Escribe un CIF válido.");
                    }

                    //Creo los datos de la cuenta ( puede hacer falta campos adicionales que no tenga metidos)
                    $data['name'] = $opp['name'];
                    $data['cif'] = $opp['CIF'];
                    $data['phone'] = $opp['phone']; //add
                    $data['email'] = $opp['email']; //add

                    $data['community'] = $opp['billingInfo']['community'];
                    $data['province'] = $opp['billingInfo']['province'];
                    $data['locality'] = $opp['billingInfo']['locality'];
                    $data['address'] = $opp['billingInfo']['address'];
                    $data['postal'] = $opp['billingInfo']['postal'];


                    //Le meto la oportunidad para dejarlo relacionado
                    $data['opportunity'] = $opp['_id'];

                    //Campos personalizados
                    $data['documents'] = $opp['customFields'];

                    foreach ($data['documents'] as $key => &$value) {
                        $value['fromOpp'] = true;
                    }


                    //Si tiene datos de contrato
                    if (isset($opp['order']['CUPS']) && $opp['order']['CUPS'] !== '') {

                        //Saco el usuario subdominio para mirar por sus comercializadoras
                        $userListTop = AuthController::getAllSuperiors($user['_id']);

                        $userFound = $user;


                        //Los recorro hasta encontrar uno que sea subdominio, si es subdominio coje las propias, si es Fran coje las de Zoco
                        if ($userFound['label'] !== 'Usuario subdominio' && $userFound['_id'] !== '65d704c63d2a9cbfd79e549a') {
                            do {
                                $userFound = collect($userListTop)->firstWhere('_id', $userFound['responsibles'][0]);
                            } while ($userFound['label'] !== 'Usuario subdominio');
                        } elseif ($userFound['_id'] === '65d704c63d2a9cbfd79e549a') {
                            $userFound = User::where('_id', '65cb57489c2c285441086a43')->first();
                        }


                        //Saco la comercializadora
                        $marketer = Marketer::where('name', $opp['order']['marketer'])->where('createdBy', $this->extractId($userFound['_id']))->first();

                        //Saco la tarifa para obtener la id posteriormente
                        $feeId = null;
                        foreach ($marketer['fees']['electricity'] as $fee) {
                            if (isset($fee['name']) && ($fee['name'] === $opp['order']['fee'])) {
                                $feeId = $this->extractId($fee['id']);
                                break;
                            }
                        }


                        $data['contract'] = [
                            'CUPS' => $opp['order']['CUPS'],
                            'productType' => 'cl',
                            'marketer' => [
                                'id' => $this->extractId($marketer['_id']),
                                'name' => $opp['order']['marketer']
                            ],
                            'fee' => [
                                'id' => $feeId,
                                'name' => $opp['order']['fee']
                            ],
                            'product' => $opp['order']['product'],
                            'address' => $opp['order']['direc'] ?? '',
                            'postal' => $opp['order']['zip'] ?? '',
                            'town' => $opp['order']['town'] ?? '',
                            'province' => $opp['order']['province'] ?? '',
                        ];
                    }


                    //Lo guardo y redirijo para comprobar si hay datos obligatorios que faltan
                    $session->update(['data' => $data,]); //, 'step' => 'check_acc_required_data'
                    //Si hacen falta campos obligatorios de cuenta o de contrato los pido y después muestro resumen de contrato y posterior de cuenta SEGUIR AQUÍ
                    $this->checkMissingObligatoryFields($session, $from, $user);

                    break;
                case 'opp_name':
                    $data['opportunity']['name'] = $message;
                    $session->update(['data' => $data, 'step' => 'opp_cif']);
                    return $this->sendMessage($from, "Introduce el CIF de la oportunidad:");
                case 'opp_cif':

                    //$cifExists = Opportunity::where('CIF', 'regex', new Regex('^' . preg_quote($message) . '$', 'i'))->where('usersIds', $user->_id)->first();

                    /*if (isset($cifExists)) {
                        return $this->sendMessage($from, "❌ Ya tienes una cuenta con ese CIF. Introduce un CIF diferente:");
                    }*/

                    $regex = '/^([KLMXYZ][0-9]{7}[A-Z]|[0-9]{8}[A-Z]|[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J])$/i';

                    if (!preg_match($regex, $message)) {
                        return $this->sendMessage($from, "❌ El CIF está mal formado. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['opportunity']['cif_nif'] = $message;
                    $session->update(['data' => $data, 'step' => 'opp_phone']);
                    return $this->sendMessage($from, "Introduce el teléfono de la oportunidad:");
                    break;
                case 'opp_phone':
                    if (strlen($message) !== 9) {
                        return $this->sendMessage($from, "❌ El teléfono no esta bien formado. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['opportunity']['phone'] = $message;

                    $session->update(['data' => $data, 'step' => 'opp_email']);
                    return $this->sendMessage($from, "Introduce el email de la oportunidad:");
                    break;
                case 'opp_email':

                    if (!filter_var($message, FILTER_VALIDATE_EMAIL)) {
                        return $this->sendMessage($from, "❌ El email no es válido. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['opportunity']['email'] = $message;
                    $session->update(['data' => $data, 'step' => 'opp_community']);

                    return $this->sendMessage($from, "Introduce la comunidad autónoma:");
                case 'opp_community':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message))) {
                        return $this->sendMessage($from, "❌ La comunidad autónoma no es válida. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['opportunity']['community'] = $message;
                    $session->update(['data' => $data, 'step' => 'opp_province']);
                    return $this->sendMessage($from, "Introduce la provincia:");
                case 'opp_province':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message))) {
                        return $this->sendMessage($from, "❌ La provincia no es válida. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['opportunity']['province'] = $message;
                    $session->update(['data' => $data, 'step' => 'opp_locality']);
                    return $this->sendMessage($from, "Introduce la localidad:");
                case 'opp_locality':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message))) {
                        return $this->sendMessage($from, "❌ La localidad no es válida. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['opportunity']['locality'] = $message;
                    $session->update(['data' => $data, 'step' => 'opp_address']);
                    return $this->sendMessage($from, "Introduce la dirección:");
                case 'opp_address':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message)))
                        return $this->sendMessage($from, "❌ La direccion no es válida. Vuelve a intentarlo.");


                    $data = $session->data;
                    $data['opportunity']['address'] = $message;
                    $session->update(['data' => $data, 'step' => 'opp_zip']);
                    return $this->sendMessage($from, "Introduce el código postal");
                case 'opp_zip':
                    if (!is_numeric($message) || strlen($message) !== 5)
                        return $this->sendMessage($from, "❌ El código postal no es válido. Vuelve a intentarlo.");


                    $data = $session->data;
                    $data['opportunity']['postal'] = $message;
                    $session->update(['data' => $data, 'step' => 'attach_document', 'documentTemporal' => 'opportunity']); //Creo una variable temporal para ver si los documentos se asignan a la cuenta o al contrato
                    return $this->sendOptions($from,
                        "¿Quieres adjuntar documentos?",
                        [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No']
                        ]);
                case 'opp_order_options':

                    switch ($message) {
                        case '1':
                            $session->update(['step' => 'opp_order_address_options']);
                            return $this->sendOptions($from,
                                "¿Quieres establecer la misma dirección de facturación de la oportunidad para el contrato?",
                                [
                                    ['id' => '1', 'title' => 'Sí'],
                                    ['id' => '2', 'title' => 'No'],
                                ]
                            );
                            break;

                        case '2':
                            $session->update(['step' => 'opportunity_confirm']);
                            //Hago el resumen de opp
                            $this->resumeOpportunity($session, $from, $productTypes, $user);
                            break;
                    }

                    break;
                case 'opp_order_address_options':
                    $data = $session->data;

                    switch ($message) {
                        case '1':

                            //Asigno todos los campos de direccion para el contrato igual que de la opp
                            $data['opportunity']['order']['province'] = $data['opportunity']['province'];
                            $data['opportunity']['order']['locality'] = $data['opportunity']['locality'];
                            $data['opportunity']['order']['address'] = $data['opportunity']['address'];
                            $data['opportunity']['order']['postal'] = $data['opportunity']['postal'];

                            $session->update(['data' => $data, 'step' => 'contract_productType']);
                            $options = collect($productTypes)
                                ->map(fn($product, $index) => [
                                    'id' => (string) ($index + 1),
                                    'title' => $product['title'],
                                ])
                                ->values()
                                ->toArray();
                            return $this->sendOptions($from, "Selecciona el tipo de producto:", $options);

                            break;
                        case '2':
                            $session->update(['data' => $data, 'step' => 'opp_order_province']);
                            return $this->sendMessage($from, "Introduce la provincia del contrato");
                            break;
                    }
                    break;
                case 'opp_order_province':
                    $data = $session->data;

                    $data['opportunity']['order']['province'] = $message;

                    $session->update(['data' => $data, 'step' => 'opp_order_locality']);
                    return $this->sendMessage($from, "Introduce la localidad del contrato");
                    break;
                case 'opp_order_locality':
                    $data = $session->data;

                    $data['opportunity']['order']['locality'] = $message;

                    $session->update(['data' => $data, 'step' => 'opp_order_address']);
                    return $this->sendMessage($from, "Introduce la dirección del contrato");
                    break;
                case 'opp_order_address':
                    $data = $session->data;

                    $data['opportunity']['order']['address'] = $message;

                    $session->update(['data' => $data, 'step' => 'opp_order_postal']);
                    return $this->sendMessage($from, "Introduce el código postal del contrato");
                    break;
                case 'opp_order_postal':
                    $data = $session->data;

                    if (!is_numeric($message) || strlen($message) !== 5)
                        return $this->sendMessage($from, "❌ El código postal no es válido. Vuelve a intentarlo.");

                    $data['opportunity']['order']['postal'] = $message;

                    $session->update(['data' => $data, 'step' => 'contract_productType']);
                    $options = collect($productTypes)
                        ->map(fn($product, $index) => [
                            'id' => (string) ($index + 1),
                            'title' => $product['title'],
                        ])
                        ->values()
                        ->toArray();

                    return $this->sendOptions($from, "Selecciona el tipo de producto:", $options);



                //📒CUENTA
                case 'account_options':

                    switch ($message) {
                        case 1:
                            $session->update(['step' => 'account_name']);
                            return $this->sendMessage($from, "️Introduce el nombre de la cuenta");
                            break;

                        case 2:
                            $opportunities = Opportunity::where('createdBy', $user->_id)->get();

                            //Si no tiene ninguna cuenta
                            if ($opportunities->isEmpty()) {
                                $session->delete();
                                return $this->sendMessage($from, "Parece que no tienes ninguna oportunidad registrada.");
                            }

                            $oppList = $opportunities->map(fn($opportunity) => "📌 {$opportunity->name} | CIF: {$opportunity->CIF}")
                                ->join("\n\n");

                            $session->update(['step' => 'existing_opp']);
                            return $this->sendMessage($from, "📂 Elige una oportunidad ( Introduce el CIF ):\n\n" . $oppList . "\n\n");
                            break;

                        default:
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }
                case 'account_name':

                    $session->update(['data' => ['name' => $message], 'step' => 'account_cif']);
                    return $this->sendMessage($from, "Introduce el CIF de la cuenta:");
                case 'account_cif':

                    $cifExists = Account::where('CIF', 'regex', new Regex('^' . preg_quote($message) . '$', 'i'))->where('usersIds', $user->_id)->first();

                    if (isset($cifExists)) {
                        return $this->sendMessage($from, "❌ Ya tienes una cuenta con ese CIF. Introduce un CIF diferente:");
                    }

                    $regex = '/^([KLMXYZ][0-9]{7}[A-Z]|[0-9]{8}[A-Z]|[ABCDEFGHJNPQRSUVW][0-9]{7}[0-9A-J])$/i';

                    if (!preg_match($regex, $message)) {
                        return $this->sendMessage($from, "❌ El CIF está mal formado. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['cif'] = $message;
                    $session->update(['data' => $data, 'step' => 'account_phone']);
                    return $this->sendMessage($from, "Introduce el teléfono de la cuenta:");
                case 'account_phone':

                    if (strlen($message) !== 9) {
                        return $this->sendMessage($from, "❌ El teléfono no esta bien formado. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['phone'] = $message;

                    $session->update(['data' => $data, 'step' => 'account_email']);
                    return $this->sendMessage($from, "Introduce el email de la cuenta:");
                case 'account_email':

                    if (!filter_var($message, FILTER_VALIDATE_EMAIL)) {
                        return $this->sendMessage($from, "❌ El email no es válido. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['email'] = $message;
                    $session->update(['data' => $data, 'step' => 'account_community']);

                    return $this->sendMessage($from, "Introduce la comunidad autónoma:");
                case 'account_community':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message))) {
                        return $this->sendMessage($from, "❌ La comunidad autónoma no es válida. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['community'] = $message;
                    $session->update(['data' => $data, 'step' => 'account_province']);
                    return $this->sendMessage($from, "Introduce la provincia:");
                case 'account_province':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message))) {
                        return $this->sendMessage($from, "❌ La provincia no es válida. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['province'] = $message;
                    $session->update(['data' => $data, 'step' => 'account_locality']);
                    return $this->sendMessage($from, "Introduce la localidad:");
                case 'account_locality':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message))) {
                        return $this->sendMessage($from, "❌ La localidad no es válida. Vuelve a intentarlo.");
                    }

                    $data = $session->data;
                    $data['locality'] = $message;
                    $session->update(['data' => $data, 'step' => 'account_zip']);
                    return $this->sendMessage($from, "Introduce el código postal:");
                case 'account_zip':
                    if (!is_numeric($message) || strlen($message) !== 5)
                        return $this->sendMessage($from, "❌ El código postal no es válido. Vuelve a intentarlo.");


                    $data = $session->data;
                    $data['postal'] = $message;
                    $session->update(['data' => $data, 'step' => 'account_address']);
                    return $this->sendMessage($from, "Introduce la dirección:");
                case 'account_address':
                    if (!is_string($message) || is_numeric($message) || empty(trim($message)))
                        return $this->sendMessage($from, "❌ La direccion no es válida. Vuelve a intentarlo.");


                    $data = $session->data;
                    $data['address'] = $message;
                    $session->update(['data' => $data, 'step' => 'attach_document', 'documentTemporal' => 'account']); //Creo una variable temporal para ver si los documentos se asignan a la cuenta o al contrato
                    return $this->sendOptions($from,
                        "¿Quieres adjuntar documentos?",
                        [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No'],
                        ]
                    );



                //📝CONTRATO
                case 'contract_option':
                    if ($message === '1') {
                        $accounts = Account::where('createdBy', $user->_id)->get();

                        //Si no tiene ninguna cuenta
                        if ($accounts->isEmpty()) {
                            $session->update(['step' => 'account_name']);
                            return $this->sendMessage($from, "Parece que no tienes ninguna cuenta registrada. Vamos a crear una nueva. \nPor favor, introduce el nombre de la cuenta que quieres crear:");
                        }

                        $session->update(['step' => 'check_existing_account']);

                        return $this->sendMessage($from, "Introduce el CIF de la cuenta:\n");
                    } elseif ($message === '2') {
                        $session->update(['step' => 'account_name']);
                        return $this->sendMessage($from, "Introduce el nombre de la nueva cuenta:");
                    } else {
                        return $this->sendOptions($from, "Opción no válida. Vuelve a seleccionar", [
                            ['id' => '1', 'title' => 'Añadir a cuenta existente'],
                            ['id' => '2', 'title' => 'Crear nueva cuenta'],
                        ]);
                    }
                    break;
                case 'check_existing_account':
                    $account = Account::where('CIF', 'regex', new Regex('^' . preg_quote($message) . '$', 'i'))->where('usersIds', $user->_id)->first();

                    if (!$account)
                        return $this->sendMessage($from, "Cuenta no encontrada. Escribe un CIF válido.");

                    $data = $session->data;
                    $data['CIF'] = $message;

                    $session->update(['data' => $data, 'step' => 'existing_account']);
                    return $this->sendOptions($from, "La cuenta encontrada es: *" . trim($account->name) . "*\n\n ¿Es la cuenta que buscas?",
                        [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No']
                        ]);
                    break;
                case 'existing_account':

                    $data = $session->data;

                    switch ($message) {
                        case '1':
                            $account = Account::where('CIF', 'regex', new Regex('^' . preg_quote($data['CIF']) . '$', 'i'))->where('usersIds', $user->_id)->first();

                            if (!$account)
                                return $this->sendMessage($from, "Cuenta no encontrada. Escribe un CIF válido.");

                            $data = $session->data;
                            $data['selected_account'] = $account->_id;

                            $session->update(['data' => $data, 'step' => 'contract_name']);
                            return $this->sendMessage($from, "Introduce el nombre del contrato:");
                            break;

                        case '2':
                            $session->update(['step' => 'check_existing_account']);
                            return $this->sendMessage($from, "Introduce el CIF de la cuenta:\n");
                            break;
                    }

                case 'contract_name':
                    $data = $session->data;
                    $data['contract']['name'] = $message;
                    $session->update(['data' => $data, 'step' => 'contract_address']);
                    return $this->sendMessage($from, "Introduce la dirección del contrato:");
                case 'contract_address':
                    $data = $session->data;
                    $data['contract']['address'] = $message;
                    $session->update(['data' => $data, 'step' => 'contract_town']);
                    return $this->sendMessage($from, "Introduce el población del contrato:");
                case 'contract_town':
                    $data = $session->data;
                    $data['contract']['town'] = $message;
                    $session->update(['data' => $data, 'step' => 'contract_province']);
                    return $this->sendMessage($from, "Introduce la provincia del contrato:");
                case 'contract_province':
                    $data = $session->data;
                    $data['contract']['province'] = $message;
                    $session->update(['data' => $data, 'step' => 'contract_postal']);
                    return $this->sendMessage($from, "Introduce el código postal del contrato:");
                case 'contract_postal':
                    if (!is_numeric($message) || strlen($message) !== 5)
                        return $this->sendMessage($from, "❌ El código postal no es válido. Vuelve a intentarlo.");

                    $data = $session->data;
                    $data['contract']['postal'] = $message;
                    $session->update(['data' => $data, 'step' => 'contract_IBAN']);
                    return $this->sendMessage($from, "Introduce el IBAN asignado al contrato:");
                case 'contract_IBAN':
                    if (!empty($message) && strlen($message) !== 29)
                        return $this->sendMessage($from, "❌ El IBAN no es válido. Vuelve a intentarlo.");


                    $data = $session->data;
                    $data['contract']['IBAN'] = $message;
                    $session->update(['data' => $data, 'step' => 'contract_productType']);

                    //Saco los productos
                    $options = collect($productTypes)
                        ->map(fn($product, $index) => [
                            'id' => (string) ($index + 1),
                            'title' => $product['title'],
                        ])
                        ->values()
                        ->toArray();

                    return $this->sendOptions($from, "Selecciona el tipo de producto:\n\n", $options);
                case 'contract_productType':

                    try {

                        $data = $session->data;

                        //Si es tipo contrato o oportunidad
                        if ($session->type === 'opportunity' || $session->type === 'comparative') {
                            $data['opportunity']['order']['productType'] = $productTypes[(int) $message - 1];
                            $contract = $data['opportunity']['order'];
                        } else {
                            $data['contract']['productType'] = $productTypes[(int) $message - 1]['code'];
                            $contract = $data['contract'];
                        }

                        $productTypeCode = is_array($contract['productType'])
                            ? ($contract['productType']['code'] ?? null)
                            : $contract['productType'];

                        //Si es cl o cg
                        if ($productTypeCode === 'cl' || $productTypeCode === 'cg') {
                            $session->update(['data' => $data, 'step' => 'contract_marketer']);
                            $options = collect($marketers)
                                ->map(fn($marketer, $index) => [
                                    'id' => (string) ($index + 1),
                                    'title' => $marketer['name'],
                                ])
                                ->values()
                                ->toArray();
                            return $this->sendOptions($from, "Selecciona la comercializadora:", $options);
                        } else {
                            $session->update(['data' => $data, 'step' => 'contract_product']);
                            $options = collect($basicProducts)
                                ->map(fn($product, $index) => [
                                    'id' => (string) ($index + 1),
                                    'title' => $product,
                                ])
                                ->values()
                                ->toArray();
                            return $this->sendOptions($from, "Selecciona el producto:", $options);
                        }
                    } catch (\Throwable $e) {
                        $mensajeError = "❌ *Error inesperado*\n";
                        $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
                        $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
                        $mensajeError .= "*Línea:* " . $e->getLine();
                        $mensajeError .= "*Parte:* " . 'contract_productType';

                        // Limitar longitud si lo envías por WhatsApp
                        return $this->sendMessage($from, Str::limit($mensajeError, 3900));
                    }
                case 'contract_marketer':

                    try {
                        $data = $session->data;

                        $selectedIndex = (int) $message - 1;

                        if (!isset($marketers[$selectedIndex]))
                            return $this->sendMessage($from, "Comercializadora incorrecta, vuelva a intentarlo.");


                        $selectedMarketer = $marketers[$selectedIndex];

                        // Si es tipo contrato o oportunidad
                        if ($session->type === 'opportunity' || $session->type === 'comparative') {
                            $data['opportunity']['order']['marketer'] = [
                                'id' => $selectedMarketer['_id'],
                                'name' => $selectedMarketer['name'],
                            ];

                            $contract = $data['opportunity']['order'];
                        } else {
                            $data['contract']['marketer'] = [
                                'id' => $selectedMarketer['_id'],
                                'name' => $selectedMarketer['name'],
                            ];

                            $contract = $data['contract'];
                        }

                        $productTypeCode = is_array($contract['productType'])
                            ? ($contract['productType']['code'] ?? null)
                            : $contract['productType'];

                        $fees = $selectedMarketer['fees'][$productTypeCode === 'cl' ? 'electricity' : 'gas'] ?? [];

                        if (empty($fees)) {
                            return $this->sendMessage($from, "Esta comercializadora no tiene tarifas disponibles para este tipo de producto.");
                        }

                        $options = collect($fees)
                            ->map(fn($fee, $index) => [
                                'id' => (string) ($index + 1),
                                'title' => $fee['name'],
                            ])
                            ->values()
                            ->toArray();

                        $session->update(['data' => $data, 'step' => 'contract_fee']);

                        return $this->sendOptions($from, "Selecciona la tarifa:", $options);
                    } catch (\Throwable $e) {
                        $mensajeError = "❌ *Error inesperado*\n";
                        $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
                        $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
                        $mensajeError .= "*Línea:* " . $e->getLine();
                        $mensajeError .= "*Parte:* " . 'contract_marketer';

                        return $this->sendMessage($from, Str::limit($mensajeError, 3900));
                    }
                case 'contract_fee':

                    try {

                        $data = $session->data;

                        $contract = ($session->type === 'opportunity' || $session->type === 'comparative') ? $data['opportunity']['order'] : $data['contract'];


                        //Saco comercializadora
                        $marketer = Marketer::where('_id', $contract['marketer']['id'])->first();

                        //Saco el tipo de producto
                        $productTypeCode = is_array($contract['productType'])
                            ? ($contract['productType']['code'] ?? null)
                            : $contract['productType'];

                        //Si no es correcta
                        if (!isset($marketer['fees'][$productTypeCode === 'cl' ? 'electricity' : 'gas'][(int) $message - 1]))
                            return $this->sendMessage($from, "Tarifa incorrecta, vuelva a intentarlo.");


                        if ($session->type === 'opportunity' || $session->type === 'comparative') {
                            $data['opportunity']['order']['fee'] = [
                                'id' => $this->extractId($marketer['fees'][$productTypeCode === 'cl' ? 'electricity' : 'gas'][(int) $message - 1]['id']),
                                'name' => $marketer['fees'][$productTypeCode === 'cl' ? 'electricity' : 'gas'][(int) $message - 1]['name'],
                            ];
                            $contract = $data['opportunity']['order'];
                        } else {
                            $data['contract']['fee'] = [
                                'id' => $this->extractId($marketer['fees'][$productTypeCode === 'cl' ? 'electricity' : 'gas'][(int) $message - 1]['id']),
                                'name' => $marketer['fees'][$productTypeCode === 'cl' ? 'electricity' : 'gas'][(int) $message - 1]['name'],
                            ];
                            $contract = $data['contract'];
                        }


                        //Saco todos los productos de esa comercializadora
                        $products = $marketer['products'][$productTypeCode === 'cl' ? 'electricity' : 'gas'];



                        //Filtro los productos para solo sacar los que tengan la tarifa seleccionada y no estén archivados
                        $filteredProducts = array_filter($products, function ($product) use ($data, $contract) {
                            return isset($product['fees']) &&
                                is_array($product['fees']) &&
                                in_array($this->extractId($contract['fee']['id']), $this->extractIds($product['fees'])) &&
                                empty($product['archived']);
                        });


                        // Asegurar que el array tiene índices consecutivos antes de mapear
                        $filteredProducts = array_values($filteredProducts);

                        $session->update(['data' => $data, 'step' => 'contract_product']);

                        //Saco las tarifas de la comercializadora
                        $options = collect($filteredProducts)
                            ->map(fn($product, $index) => [
                                'id' => (string) ($index + 1),
                                'title' => $product['name'],
                            ])
                            ->values()
                            ->toArray();
                        return $this->sendOptions($from, "Selecciona el producto:\n\n", $options);
                    } catch (\Throwable $e) {
                        $mensajeError = "❌ *Error inesperado*\n";
                        $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
                        $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
                        $mensajeError .= "*Línea:* " . $e->getLine();
                        $mensajeError .= "*Parte:* " . 'contract_fee';

                        // Limitar longitud si lo envías por WhatsApp
                        return $this->sendMessage($from, Str::limit($mensajeError, 3900));
                    }
                case 'contract_product':

                    try {

                        $data = $session->data;

                        $contract = ($session->type === 'opportunity' || $session->type === 'comparative') ? $data['opportunity']['order'] : $data['contract'];

                        $productTypeCode = is_array($contract['productType'])
                            ? ($contract['productType']['code'] ?? null)
                            : $contract['productType'];

                        //Si es cl o cg se cogeran los productos de la comercializadora, sino los básicos
                        if ($productTypeCode === 'cl' || $productTypeCode === 'cg') {

                            //Saco comercializadora
                            $marketer = Marketer::where('_id', $contract['marketer']['id'])->first();

                            //Saco todos los productos de esa comercializadora
                            $products = $marketer['products'][$productTypeCode === 'cl' ? 'electricity' : 'gas'];

                            //Filtro los productos para solo sacar los que tengan la tarifa seleccionada y no estén archivados
                            $filteredProducts = array_filter($products, function ($product) use ($data, $contract) {
                                return isset($product['fees']) &&
                                    is_array($product['fees']) &&
                                    in_array($contract['fee']['id'], $this->extractIds($product['fees'])) &&
                                    empty($product['archived']);
                            });

                            // Asegurar que el array tiene índices consecutivos antes de mapear
                            $filteredProducts = array_values($filteredProducts);

                            //Si no es correcta
                            if (!isset($filteredProducts[$message - 1]))
                                return $this->sendMessage($from, "Producto incorrecto, vuelva a intentarlo.");

                            //Guardo el producto
                            if ($session->type === 'opportunity' || $session->type === 'comparative')
                                $data['opportunity']['order']['product'] = $filteredProducts[$message - 1]['name'];
                            else
                                $data['contract']['product'] = $filteredProducts[$message - 1]['name'];


                            //Si viene de la edición se corta el flujo
                            if (isset($data['stopOnFinishSelects']) && (isset($contract['CUPS']) || isset($contract['cups']))) {

                                if ($session->type === 'opportunity' || $session->type === 'comparative') {
                                    $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                                    return $this->resumeOpportunity($session, $from, $productTypes, $user);
                                } else {
                                    unset($data['stopOnFinishSelects']);
                                    $session->update(['data' => $data, 'step' => 'contract_confirm']);
                                    return $this->resumeContract($session, $from, $productTypes);
                                }
                            } else {
                                $session->update(['data' => $data, 'step' => 'contract_CUPS']);
                                return $this->sendMessage($from, "Introduce el CUPS:");
                            }
                        }
                        else {

                            //Si no es correcta
                            if (!isset($basicProducts[$message - 1]))
                                return $this->sendMessage($from, "Producto incorrecto, vuelva a intentarlo.");

                            //Guardo el producto
                            if ($session->type === 'opportunity' || $session->type === 'comparative')
                                $data['opportunity']['order']['product'] = $basicProducts[$message - 1];
                            else
                                $data['contract']['product'] = $basicProducts[$message - 1];


                            //Borro el CUPS
                            if (isset($contract['CUPS']))
                                unset($contract['CUPS']);

                            //Borro los datos no necesarios
                            unset($contract['marketer']);
                            unset($contract['fee']);

                            if ($session->type === 'opportunity' || $session->type === 'comparative') {
                                $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                                return $this->resumeOpportunity($session, $from, $productTypes, $user);
                            } else {
                                //Si viene de la edición se corta el flujo
                                if (isset($data['stopOnFinishSelects'])) {
                                    unset($data['stopOnFinishSelects']);
                                    $session->update(['data' => $data, 'step' => 'contract_confirm']);
                                    return $this->resumeContract($session, $from, $productTypes);
                                } else {
                                    $session->update(['data' => $data, 'step' => 'attach_document', 'documentTemporal' => 'order']); //Creo una variable temporal para ver si los documentos se asignan a la cuenta o al contrato
                                    return $this->sendOptions($from, "¿Quieres adjuntar documentos?",
                                        [
                                            ['id' => '1', 'title' => 'Sí'],
                                            ['id' => '2', 'title' => 'No']
                                        ]);
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        $mensajeError = "❌ *Error inesperado*\n";
                        $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
                        $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
                        $mensajeError .= "*Línea:* " . $e->getLine();
                        $mensajeError .= "*Parte:* " . 'contract_product';

                        // Limitar longitud si lo envías por WhatsApp
                        return $this->sendMessage($from, Str::limit($mensajeError, 3900));
                    }
                case 'contract_CUPS':

                    try {

                        if (strlen($message) === 22 && (str_ends_with($message, '0F') || str_ends_with($message, 'RZ')))
                            $message = substr($message, 0, -2);

                        if (!empty($message) && strlen($message) !== 20)
                            return $this->sendMessage($from, "❌ El CUPS no es válido. Vuelve a intentarlo.");

                        $data = $session->data;

                        if ($session->type === 'opportunity' || $session->type === 'comparative')
                            $data['opportunity']['order']['CUPS'] = $message;
                        else
                            $data['contract']['CUPS'] = $message;


                        if ($session->type === 'opportunity' || $session->type === 'comparative') {
                            $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                            $this->resumeOpportunity($session, $from, $productTypes, $user);
                        } else {
                            //Si se corta el flujo
                            if (isset($data['stopOnFinishSelects'])) {
                                if (isset($data['stopOnFinishSelects']))
                                    unset($data['stopOnFinishSelects']);

                                $session->update(['data' => $data, 'step' => 'contract_confirm']);
                                return $this->resumeContract($session, $from, $productTypes);
                            } else {
                                $session->update(['data' => $data, 'step' => 'attach_document', 'documentTemporal' => 'order']); //Creo una variable temporal para ver si los documentos se asignan a la cuenta o al contrato
                                return $this->sendOptions($from, "¿Quieres adjuntar documentos?",
                                    [
                                        ['id' => '1', 'title' => 'Sí'],
                                        ['id' => '2', 'title' => 'No']
                                    ]);
                            }
                        }
                    } catch (\Throwable $e) {
                        $mensajeError = "❌ *Error inesperado*\n";
                        $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
                        $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
                        $mensajeError .= "*Línea:* " . $e->getLine();
                        $mensajeError .= "*Parte:* " . 'contract_CUPS';


                        // Limitar longitud si lo envías por WhatsApp
                        return $this->sendMessage($from, Str::limit($mensajeError, 3900));
                    }



                //📊COMPARATIVA
                case 'type_filter':
                    $data = $session->data;

                    if ($message === '1') {
                        $data['type'] = 'residential';
                    } elseif ($message === '2') {
                        $data['type'] = 'pyme';
                    } else {
                        return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }

                    $session->update(['data' => $data, 'step' => 'invoice_or_data']);
                    return $this->sendOptions($from, "¿Dispone de una factura del suministro?",
                        [
                            ['id' => '1', 'title' => 'Sí, subir factura'],
                            ['id' => '2', 'title' => 'No, continuar sin factura']
                        ]);

                    break;
                case 'invoice_or_data':
                    $data = $session->data;

                    if ($message === '1') {
                        $session->update(['step' => 'invoice_upload', 'type' => 'comparative']);
                        return $this->sendMessage($from, "️Adjunta el PDF de la factura\n\n _⏱️ El reconocimiento de la factura puede tomarse alrededor de 30 segundos_");
                    } elseif ($message === '2') {
                        $data['manualData'] = true;
                        $session->update(['data' => $data, 'step' => 'comparative_total', 'type' => 'comparative']);
                        return $this->sendMessage($from, "¿️Cuanto pagas actualmente?");
                    } else {
                        return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }
                    break;
                case 'comparative_total':
                    $data = $session->data;

                    if (!is_numeric($message))
                        return $this->sendMessage($from, "No es un número valido. Vuelve a intentarlo.");
                    else
                        $data['comparative']['total'] = $message;

                    $session->update(['data' => $data, 'step' => 'comparative_days', 'type' => 'comparative']);
                    return $this->sendMessage($from, "¿Nº días totales de la factura?");
                    break;
                case 'comparative_days':
                    $data = $session->data;

                    if (!is_numeric($message))
                        return $this->sendMessage($from, "No es un número valido. Vuelve a intentarlo.");
                    else
                        $data['comparative']['days'] = $message;

                    $session->update(['data' => $data, 'step' => 'comparative_energy', 'type' => 'comparative']);
                    return $this->sendMessage($from, "¿kWh consumidos (por factura)?");
                    break;
                case 'comparative_energy':
                    $data = $session->data;

                    if (!is_numeric($message))
                        return $this->sendMessage($from, "No es un número valido. Vuelve a intentarlo.");
                    else
                        $data['comparative']['energy'] = [(float) $message];

                    $session->update(['data' => $data, 'step' => 'comparative_potency', 'type' => 'comparative']);
                    return $this->sendMessage($from, "¿Potencia contratada (kW)?");
                    break;
                case 'comparative_potency':
                    $data = $session->data;

                    if (!is_numeric($message))
                        return $this->sendMessage($from, "No es un número valido. Vuelve a intentarlo.");
                    else
                        $data['comparative']['potency'] = [(float) $message];

                    $session->update(['data' => $data, 'step' => 'comparative_when', 'type' => 'comparative']);
                    return $this->sendOptions($from, "¿Cuándo consume mas luz?",
                        [
                            ['id' => '1', 'title' => 'Principalmente de día'],
                            ['id' => '2', 'title' => 'Principalmente de noche'],
                            ['id' => '3', 'title' => 'Consumo equilibrado']
                        ]);
                    break;
                case 'comparative_when':
                    $data = $session->data;

                    if ($message === '1')
                        $data['comparative']['when'] = 'day';
                    elseif ($message === '2')
                        $data['comparative']['when'] = 'night';
                    elseif ($message === '3')
                        $data['comparative']['when'] = 'balanced';
                    else
                        return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");


                    //Inicializo
                    if (!isset($data['pdfFormatted'])) $data['pdfFormatted'] = [];


                    //Establezco los valores por defecto por si no están definidos
                    if (!isset($data['pdfFormatted']['diffInDays'])) $data['pdfFormatted']['diffInDays'] = $data['comparative']['days'] ?? 30;
                    if (!isset($data['currentTotal'])) $data['currentTotal'] = $data['comparative']['total'] ?? 0;


                    if (!isset($data['pdfFormatted']['totalConsumption'])) $data['pdfFormatted']['totalConsumption'] = $data['comparative']['energy'][0];
                    if (!isset($data['pdfFormatted']['consumptionData']['consumption'])) $data['pdfFormatted']['consumptionData']['consumption'] =  $data['comparative']['energy'] ?? [250];

                    //Distribuyo las potencias
                    if (!isset($data['SIPSData']['fee'])) $data['SIPSData']['fee'] = $data['comparative']['potency'][0] >= 15 ? '3.0TD' : '2.0TD' ;
                    if (!isset($data['pdfFormatted']['consumptionData']['power'])) $data['pdfFormatted']['consumptionData']['power'] = $data['comparative']['potency'] ?? [3.7];
                    $power = $data['pdfFormatted']['consumptionData']['power'][0];


                    //Distribución de potencias
                    if ($power >= 15)
                        $data['pdfFormatted']['consumptionData']['power'] = [$power, $power, $power, $power, $power, $power];
                    else
                        $data['pdfFormatted']['consumptionData']['power'] = [$power, $power];

                    $data['pdfFormatted']['maxPower'] = $power;


                    //Distribución de energías
                    $punta = 0;
                    $llano = 0;
                    $valle = 0;
                    $consumptionProfile = $data['comparative']['when'];
                    $consumptionTotal = $data['pdfFormatted']['consumptionData']['consumption'][0];


                    // Perfil de consumo base
                    if ($consumptionProfile === 'day') {
                        $punta = $consumptionTotal * 0.5;
                        $llano = $consumptionTotal * 0.3;
                        $valle = $consumptionTotal * 0.2;
                    }

                    if ($consumptionProfile === 'night') {
                        $punta = $consumptionTotal * 0.2;
                        $llano = $consumptionTotal * 0.3;
                        $valle = $consumptionTotal * 0.5;
                    }

                    if ($consumptionProfile === 'balanced') {
                        $punta = $consumptionTotal * 0.33;
                        $llano = $consumptionTotal * 0.33;
                        $valle = $consumptionTotal * 0.34;
                    }


                    if ($power >= 15) {

                        $month = (int) date('n');

                        // TEMPORADA
                        if (in_array($month, [6,7,8,9])) {
                            $season = 'high';
                        } elseif (in_array($month, [12,1,2])) {
                            $season = 'low';
                        } else {
                            $season = 'medium';
                        }

                        $distribution = [];

                        // PERFIL + TEMPORADA
                        if ($consumptionProfile === 'day') {
                            if ($season === 'high')   $distribution = [0.32, 0.22, 0.16, 0.12, 0.10, 0.08];
                            if ($season === 'medium') $distribution = [0.28, 0.22, 0.18, 0.14, 0.10, 0.08];
                            if ($season === 'low')    $distribution = [0.24, 0.20, 0.18, 0.16, 0.12, 0.10];
                        }

                        if ($consumptionProfile === 'night') {
                            if ($season === 'high')   $distribution = [0.08, 0.10, 0.12, 0.18, 0.22, 0.30];
                            if ($season === 'medium') $distribution = [0.08, 0.12, 0.14, 0.18, 0.22, 0.26];
                            if ($season === 'low')    $distribution = [0.10, 0.14, 0.16, 0.18, 0.20, 0.22];
                        }

                        if ($consumptionProfile === 'balanced') {
                            $distribution = [0.17, 0.17, 0.16, 0.16, 0.17, 0.17];
                        }

                        // Aplicar distribución
                        $data['pdfFormatted']['consumptionData']['consumption'] = array_map(function ($p) use ($consumptionTotal) {
                            return round($consumptionTotal * $p, 2);
                        }, $distribution);


                    }
                    else {
                        $data['pdfFormatted']['consumptionData']['consumption'] = [
                            round($punta, 2),
                            round($llano, 2),
                            round($valle, 2)
                        ];
                    }




                    //Calculo comparativa
                    $this->calcComparative($data, $from, $marketers, $user);

                    //Saco las ofertas (saco las 5 con más ahorro y las 5 con más comisión)
                    /*$data['offers'] = $this->calcOffers($data, $marketers, $user);

                    //Filtro para obtener solo las 5 ofertas con mayor ahorro y las 5 con mayor commission
                    $data['filteredOffers'] = $this->filterOffers($data['offers']);*/

                    //return $this->sendMessage($from, 'llega --> ' . Str::limit(json_encode($data['filteredOffers'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));


                    $comparative = $this->getOffersMessage($session, $data, $from, $user);


                    $session->update(['data' => $data, 'step' => 'comparative_options']);
                    return $this->sendOptions(
                        $from,
                        $comparative,
                        [
                            [
                                'id' => '1',
                                'title' => (isset($data['seeCommissions']) && $data['seeCommissions'] === true)
                                    ? 'Ocultar comisiones'
                                    : 'Mostrar comisiones',
                            ],
                            [
                                'id' => '2',
                                'title' => 'Crear oportunidad',
                            ],
                            [
                                'id' => '3',
                                'title' => 'Crear informe',
                            ],
                        ]
                    );

                    /*$session->update(['data' => $data, 'step' => 'comparative_potency', 'type' => 'comparative']);
                    return $this->sendMessage($from, "¿Potencia contratada (kW)?");*/
                    break;
                case 'invoice_upload':

                    try {

                        $data = $session->data;

                        if (!$mediaUrl || !isset($mediaUrl))
                            return $this->sendMessage($from, "❌ No detecté un archivo adjunto. Asegúrate de enviarlo correctamente.");


                        // SACO LOS DATOS MODO OCR
                        $this->getOCRData($data, $mediaUrl, $from, $user);

                        //SACO DATOS DEL CUPS SIPS
                        $this->getSIPSData($data, $session, $from, $user);

                        //REALIZO EL CÁLCULO
                        $this->calcComparative($data, $from, $marketers, $user);


                        //Guardo el log de comparativa hecha
                        $inputData['pdf'] = $data['pdfData'];
                        $inputData['sips'] = $data['SIPSData'];
                        $inputData['calculated'] = [
                            'total' => $data['currentTotal'],
                            'subTotal' => $data['currentSubTotal'],
                            'days' => $data['pdfFormatted']['diffInDays'],
                            'maxPower' => $data['pdfFormatted']['maxPower'],
                            'consumption' => $data['pdfFormatted']['totalConsumption'],
                            'estimatedConsumptionAnual' => null,
                        ];
                        $output = [
                            'total' => $data['filteredOffers']['saving'],
                            'commission' => $data['filteredOffers']['commission'],
                            'efficiency' => $data['filteredOffers']['efficiency'],
                        ];

                        AuditLogService::generateComparative(
                            status: 'success',
                            messageError: null,
                            inputType: 'bill',
                            comparativeType: null,
                            codePart: null,
                            input: $inputData,
                            output: $output,
                            user: $user,
                            witroPhone: $from
                        );


                        $comparative = $this->getOffersMessage($session, $data, $from, $user);

                        //return $this->sendMessage($from, Str::limit(json_encode($comparative, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));


                        //Si no hay ninguna oferta corto el flujo
                        if (!isset($data['filteredOffers']['saving'][0]))
                            return $session->delete();

                        //ConsumptionData --> factura | cupsData -> SIPS

                        //Si es witro envío directamente el PDF también
                        if ($session->app === 'crm') {
                            $session->update(['data' => $data, 'step' => 'comparative_options']);

                            return $this->sendOptions(
                                $from,
                                $comparative,
                                [
                                    [
                                        'id' => '1',
                                        'title' => (isset($data['seeCommissions']) && $data['seeCommissions'] === true)
                                            ? 'Ocultar comisiones'
                                            : 'Mostrar comisiones',
                                    ],
                                    [
                                        'id' => '2',
                                        'title' => 'Crear oportunidad',
                                    ],
                                    [
                                        'id' => '3',
                                        'title' => 'Crear informe',
                                    ],
                                ]
                            );
                        } else {
                            $filename = 'invoice_' . $from . '_' . uniqid() . '.pdf';
                            Storage::disk('temporal_comparatives')->put($filename, file_get_contents($mediaUrl));
                            $data['invoice_name'] = $filename;

                            $this->sendComparativePDF($data, $user, $from, $session);

                            $session->update(['data' => $data, 'step' => 'comparative_options']);

                            return $this->sendOptions(
                                $from,
                                $comparative,
                                [
                                    [
                                        'id' => '1',
                                        'title' => 'Crear contrato',
                                    ],
                                    [
                                        'id' => '2',
                                        'title' => 'Hablar con agente',
                                    ],
                                ]
                            );
                        }
                    } catch (\Throwable $e) {

                        $mensajeError = "❌ *Error con el PDF*\n";
                        $mensajeError .= "Asegurate que el PDF adjuntado es válido y no es escaneado ni una imagen  ";
                        $mensajeError .= $e->getMessage() . '  ';
                        $mensajeError .= $e->getLine() . '  ';
                        $mensajeError .= $e->getFile();

                        //Log comparativa erronea
                        AuditLogService::generateComparative('error', $e->getMessage(), 'bill', 'invoice_upload',  null, null, null, $user, $from);

                        // Limitar longitud si lo envías por WhatsApp
                        return $this->sendMessage($from, Str::limit($mensajeError, 3900));
                    }


                    break;
                case 'comparative_options':

                    $data = $session->data;

                    //Si es del CRM
                    if ($session->app === 'crm') {

                        if ($message === '1') {
                            //Vuelvo a mostrar las ofertas
                            $this->calcComparative($data, $from, $marketers, $user);
                            $comparative = $this->getOffersMessage($session, $data, $from, $user);
                            $session->update(['data' => $data, 'step' => 'comparative_options']);
                            return $this->sendOptions(
                                $from,
                                $comparative,
                                [
                                    [
                                        'id' => '1',
                                        'title' => (isset($data['seeCommissions']) && $data['seeCommissions'] === true)
                                            ? 'Ocultar comisiones'
                                            : 'Mostrar comisiones',
                                    ],
                                    [
                                        'id' => '2',
                                        'title' => 'Crear oportunidad',
                                    ],
                                    [
                                        'id' => '3',
                                        'title' => 'Crear informe',
                                    ],
                                ]);
                        } elseif ($message === '2') {

                            //Si es una comparativa manual pido los datos primero
                            if (!isset($data['manualData']) || !$data['manualData']){
                                $session->update(['data' => $data, 'step' => 'choose_offer']);
                                return $this->sendMessage($from, "Selecciona una de las ofertas ( Por número ):");
                            } else {
                                $session->update(['data' => $data, 'step' => 'comparative_manual_name']);
                                return $this->sendMessage($from, "Introduce un nombre para la oportunidad:");
                            }

                            /*$oppExists = Opportunity::where('CIF', $data['pdfData']['cif_nif'])->where('createdBy', $user['_id'])->where('order.productType', 'cl')->first();

                            if (isset($oppExists)) {

                                $data['opportunity'] = [
                                    'name' => $oppExists['name'],
                                    'cif_nif' => $oppExists['CIF'],
                                    'phone' => $oppExists['phone'] ?? '',
                                    'email' => $oppExists['email'] ?? '',
                                    'community' => $oppExists['billingInfo']['community'],
                                    'province' => $oppExists['billingInfo']['province'],
                                    'locality' => $oppExists['billingInfo']['locality'],
                                    'address' => $oppExists['billingInfo']['address'],
                                    'postal' => $oppExists['billingInfo']['postal'],
                                    'order' => [
                                        'productType' => [
                                            'code' => 'cl',
                                            'title' => 'Contrato de luz'
                                        ],
                                        'marketer' => $oppExists['order']['marketer'],
                                        'fee' => $oppExists['order']['fee'],
                                        'product' => $oppExists['order']['product'],
                                        'cups' => $oppExists['order']['CUPS'],
                                        'province' => $oppExists['order']['province'],
                                        'locality' => $oppExists['order']['town'],
                                        'address' => $oppExists['order']['direc'],
                                        'postal' => $oppExists['order']['zip']
                                    ],
                                ];

                                $session->update(['data' => $data, 'step' => 'opportunity_confirm']);

                                $this->resumeOpportunity($session, $from, $productTypes, $user);
                            }
                            else {
                                $session->update(['data' => $data, 'step' => 'choose_offer']);
                                return $this->sendMessage($from, "Selecciona una de las ofertas ( Por número ):");
                            }*/
                        } elseif ($message === '3') {

                            $data['to'] = 'select_offer_to_pdf';

                            //Si es una comparativa manual pido los datos primero
                            if (!isset($data['manualData']) || !$data['manualData']){
                                $session->update(['data' => $data, 'step' => 'select_offer_to_pdf']);
                                return $this->sendMessage($from, "Selecciona una de las ofertas ( Por número ):");
                            } else {
                                $session->update(['data' => $data, 'step' => 'comparative_manual_name']);
                                return $this->sendMessage($from, "Introduce un nombre para la oportunidad:");
                            }



                        } else {
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                        }
                    }
                    else {

                        if ($message === '1') {

                            //Selecciono la primera oferta
                            $offerSelected = $data['filteredOffers']['saving'][0];

                            //Guardo los datos para posteriormente crear la cuenta con el contrato

                            //Datos cuenta
                            $data['name'] = $data['pdfData']['titular'];
                            $data['cif'] = $data['pdfData']['cif_nif'];
                            $data['community'] = $data['pdfData']['direccion_titular']['comunidad_autonoma'];
                            $data['province'] = $data['pdfData']['direccion_titular']['provincia'];
                            $data['locality'] = $data['pdfData']['direccion_titular']['poblacion'];
                            $data['address'] = $data['pdfData']['direccion_titular']['direccion'];
                            $data['postal'] = $data['pdfData']['direccion_titular']['codigo_postal'];


                            //Datos contrato
                            $data['contract'] = [
                                'name' => $data['pdfData']['titular'] . ' - ' . substr($data['pdfData']['cif_nif'], -6),
                                'address' => $data['pdfData']['direccion_titular']['direccion'],
                                'postal' => $data['pdfData']['direccion_titular']['codigo_postal'],
                                'town' => $data['pdfData']['direccion_titular']['poblacion'],
                                'province' => $data['pdfData']['direccion_titular']['provincia'],
                                'productType' => 'cl',
                                'marketer' => [
                                    'id' => $offerSelected['marketerId'],
                                    'name' => $offerSelected['marketer']
                                ],
                                'fee' => [
                                    'name' => $offerSelected['fee']
                                ],
                                'product' => $offerSelected['product'],
                                'CUPS' => $data['pdfFormatted']['cups'],
                            ];


                            /*$data['opportunity'] = [
                                'name' => $data['pdfData']['titular'],
                                'cif_nif' => $data['pdfData']['cif_nif'],
                                'phone' => '',
                                'email' => '',
                                'community' => $data['pdfData']['direccion_titular']['comunidad_autonoma'],
                                'province' => $data['pdfData']['direccion_titular']['provincia'],
                                'locality' => $data['pdfData']['direccion_titular']['poblacion'],
                                'address' => $data['pdfData']['direccion_titular']['direccion'],
                                'postal' => $data['pdfData']['direccion_titular']['codigo_postal'],
                                'order' => [
                                    'productType' => [
                                        'code' => 'cl',
                                        'title' => 'Contrato de luz'
                                    ],
                                    'marketer' => $offerSelected['marketer'],
                                    'fee' => $data['SIPSData']['fee'],
                                    'product' => $offerSelected['product'],
                                    'cups' => $data['pdfData']['cups'],
                                    'province' => $data['pdfData']['direccion_suministro']['provincia'],
                                    'locality' => $data['pdfData']['direccion_suministro']['poblacion'],
                                    'address' => $data['pdfData']['direccion_suministro']['direccion'],
                                    'postal' => $data['pdfData']['direccion_suministro']['codigo_postal']
                                ],
                            ];*/


                            //Pido un PDF del DNI y de la factura
                            $session->update(['data' => $data, 'step' => 'document_upload', 'documentTemporal' => 'order']);
                            return $this->sendMessage($from, 'Adjunta fotos/documentos del DNI y de la factura. ( De 1 en 1 )');

                        }
                        elseif ($message === '2') {

                            //Si es Zoco pongo el telefono de Paco, sino el teléfono del usuario subdominio
                            if (self::$WHATSAPP_INSTANCE_ID === 'instance24114')
                                $agentPhone = '34' . '653062438';
                            else{

                                if ($user['label'] === 'Usuario subdominio')
                                    $agentPhone = '34' . $user['phone'];
                                else
                                    $agentPhone = '34' . $this->getUserSubdomainOrEnterprise($user)['phone'];
                            }



                            //Envío factura al agente
                            $this->sendMessage(
                                $agentPhone,
                                'https://crm.zocoenergia.com/assets/temporal_comparatives/' . $data['invoice_name'],
                                'document',
                                'Factura adjuntada',
                                'Telefono usuario: ' . $this->formatSpanishNumber($from)
                            );


                            //Envío el contacto de Paco al agente
                            $vcard = implode("\n", [
                                'BEGIN:VCARD',
                                'VERSION:3.0',
                                'N:Witro;Agente',
                                'FN:Agente ' . $user['firstName'],
                                'TEL;TYPE=CELL;waid=' . $agentPhone . ':' . $agentPhone,
                                'ADR;TYPE=work:;;;;;;',
                                'END:VCARD',
                            ]);

                            $this->sendMessage($from, $vcard, 'vcard');

                            //Borro factura temporal
                            Storage::disk('temporal_comparatives')->delete($data['invoice_name']);

                            //Termino la sesión
                            return $session->delete();
                        } else {
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                        }
                    }


                    break;
                case 'comparative_manual_name':
                    $data = $session->data;
                    $name = trim($message);

                    if (empty($name) || strlen($name) < 2)
                        return $this->sendMessage($from, "❌ Nombre no válido. Introduce un nombre correcto:");

                    $data['comparative']['name'] = $name;

                    $session->update(['data' => $data, 'step' => 'comparative_manual_nif']);
                    return $this->sendMessage($from, "Introduce el CIF/NIF:");
                    break;
                case 'comparative_manual_nif':
                    $data = $session->data;
                    $nif = strtoupper(trim($message));

                    if (!$this->isValidNifCif($nif)) {
                        return $this->sendMessage($from, "❌ CIF/NIF no válido. Ejemplo válido: 12345678A o B12345678");
                    }

                    $data['comparative']['nif'] = $nif;

                    $session->update(['data' => $data, 'step' => 'comparative_manual_email']);
                    return $this->sendMessage($from, "Introduce un email para la oportunidad:");
                    break;
                case 'comparative_manual_email':
                    $data = $session->data;
                    $email = trim($message);

                    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
                        return $this->sendMessage($from, "❌ Email no válido. Introduce un email correcto:");


                    $data['comparative']['email'] = $email;

                    $session->update(['data' => $data, 'step' => 'comparative_manual_phone']);
                    return $this->sendMessage($from, "Introduce un teléfono para la oportunidad:");
                    break;
                case 'comparative_manual_phone':
                    $data = $session->data;
                    $phone = preg_replace('/\s+/', '', $message);

                    // Validación básica: solo números y longitud razonable
                    if (!preg_match('/^[0-9]{9,15}$/', $phone))
                        return $this->sendMessage($from, "❌ Teléfono no válido. Introduce un teléfono correcto (solo números):");

                    $data['comparative']['phone'] = $phone;
                    $session->update(['data' => $data, 'step' => isset($data['to']) ? $data['to'] :'choose_offer']);
                    return $this->sendMessage($from, "Selecciona una de las ofertas ( Por número ):");

                    break;
                case 'pdf_options':

                    $data = $session->data;

                    switch ($message) {
                        case 1:

                            //Saco la oferta seleccionada
                            $indexSelected = $message - 1;
                            $groupInd = intdiv($indexSelected, 5);
                            $offerInd = $indexSelected % 5;
                            $offersFiltered = array_values($data['filteredOffers']);
                            $offerSelected = $offersFiltered[$groupInd][$offerInd];

                            //Guardo los datos para crear la oportunidad posteriormente
                            $data['opportunity'] = [
                                'name' => !isset($data['manualData']) ? $data['pdfData']['titular'] : $data['comparative']['name'],
                                'cif_nif' => !isset($data['manualData']) ? $data['pdfData']['cif_nif'] : $data['comparative']['nif'],
                                'phone' => !isset($data['manualData']) ? '' : $data['comparative']['phone'],
                                'email' => !isset($data['manualData']) ? '' : $data['comparative']['email'],
                                'community' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['comunidad_autonoma'] : '',
                                'province' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['provincia'] : '',
                                'locality' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['poblacion'] : '',
                                'address' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['direccion'] : '',
                                'postal' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['codigo_postal'] : '',
                                'order' => [
                                    'productType' => [
                                        'code' => 'cl',
                                        'title' => 'Contrato de luz'
                                    ],
                                    'marketer' => $offerSelected['marketer'],
                                    'fee' => $data['SIPSData']['fee'],
                                    'product' => $offerSelected['product'],
                                    'cups' => !isset($data['manualData']) ? $data['pdfData']['cups'] : '',
                                    'province' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['provincia'] : '',
                                    'locality' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['poblacion'] : '',
                                    'address' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['direccion'] : '',
                                    'postal' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['codigo_postal'] : ''
                                ],
                            ];


                            //return $this->sendMessage($from, Str::limit(json_encode($data['opportunity'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));

                            $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                            $this->resumeOpportunity($session, $from, $productTypes, $user);

                            break;
                        case 2:

                            $session->delete();
                            return $this->sendMessage($from, '👋¡Hasta pronto!');

                            break;
                        default:
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }

                    break;
                case 'choose_offer':
                    $data = $session->data;

                    if (intval($message) && ($message > 0 && $message <= 15)) {

                        //Saco la oferta seleccionada
                        $indexSelected = $message - 1;
                        $groupInd = intdiv($indexSelected, 5);
                        $offerInd = $indexSelected % 5;
                        $offersFiltered = array_values($data['filteredOffers']);
                        $offerSelected = $offersFiltered[$groupInd][$offerInd];

                        //Guardo los datos para crear la oportunidad posteriormente
                        $data['opportunity'] = [
                            'name' => !isset($data['manualData']) ? $data['pdfData']['titular'] : $data['comparative']['name'],
                            'cif_nif' => !isset($data['manualData']) ? $data['pdfData']['cif_nif'] : $data['comparative']['nif'],
                            'phone' => !isset($data['manualData']) ? '' : $data['comparative']['phone'],
                            'email' => !isset($data['manualData']) ? '' : $data['comparative']['email'],
                            'community' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['comunidad_autonoma'] : '',
                            'province' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['provincia'] : '',
                            'locality' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['poblacion'] : '',
                            'address' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['direccion'] : '',
                            'postal' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['codigo_postal'] : '',
                            'order' => [
                                'productType' => [
                                    'code' => 'cl',
                                    'title' => 'Contrato de luz'
                                ],
                                'marketer' => $offerSelected['marketer'],
                                'fee' => $data['SIPSData']['fee'],
                                'product' => $offerSelected['product'],
                                'cups' => !isset($data['manualData']) ? $data['pdfData']['cups'] : '',
                                'province' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['provincia'] : '',
                                'locality' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['poblacion'] : '',
                                'address' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['direccion'] : '',
                                'postal' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['codigo_postal'] : ''
                            ],
                        ];

                        //return $this->sendMessage($from, Str::limit(json_encode($data['opportunity'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));

                        $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                        $this->resumeOpportunity($session, $from, $productTypes, $user);

                    } else
                        return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");

                    break;
                case 'select_offer_to_pdf':
                    $data = $session->data;

                    if (intval($message) && ($message > 0 && $message <= 15)) {

                        //Saco la oferta seleccionada
                        $indexSelected = $message - 1;
                        $groupInd = intdiv($indexSelected, 5);
                        $offerInd = $indexSelected % 5;
                        $offersFiltered = array_values($data['filteredOffers']);
                        $offerSelected = $offersFiltered[$groupInd][$offerInd];

                        //Guardo los datos para crear la oportunidad posteriormente
                        $data['offerSelected'] = $offerSelected;

                        //Envío el pdf
                        $this->sendComparativePDF($data, $user, $from, $session);

                        //Cambio el paso
                        //$session->update(['data' => $data, 'step' => 'pdf_options']);
                        return $this->sendOptions($from, "¿Quieres crear una oportunidad a partir de este informe o terminar la comparativa?", [
                            ['id' => '1', 'title' => 'Crear'],
                            ['id' => '2', 'title' => 'Terminar'],
                        ]);
                    } else
                        return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    break;



                //📅FICHAJES
                case 'signing_options':
                    switch ($message) {
                        case '1':
                            $session->update(['step' => 'signing_send_location']);
                            return $this->sendMessage(
                                $from,
                                "📍 *Envía tu ubicación actual para registrar el fichaje.*\n\n" .
                                "Por seguridad, debes enviarla usando la ubicación nativa de WhatsApp:\n\n" .
                                "📎 Adjuntar → Ubicación → Enviar ubicación actual\n\n" .
                                "No escribas las coordenadas manualmente."
                            );
                        /*$signingClient = new SigningController();
                        $response = $signingClient->saveSigningsFromWhatsapp($user->id);
                        $data = $response->getData(true);

                        return $this->sendMessage($from, $data['message']);*/
                        case '2':
                            $session->update(['step' => 'signing_work_part']);
                            return $this->sendMessage($from, "Adjunta el parte de trabajo:");
                        default:
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }
                case 'signing_work_part':
                    try {
                        // 📎 Comprobar que hay un archivo adjunto
                        if (!$mediaUrl || !isset($mediaUrl)) {
                            return $this->sendMessage(
                                $from,
                                "❌ No detecté un archivo adjunto.\nPor favor, envía un PDF o una imagen del parte de trabajo."
                            );
                        }

                        // 1️⃣ Analizar el archivo con ChatGPT
                        $data = [];
                        $this->getWorkPartData($data, $mediaUrl, $from);

                        // 2️⃣ Obtener la respuesta literal del modelo
                        $rawResponse = $data['workPart']['raw_response'] ?? '';

                        if (!$rawResponse) {
                            return $this->sendMessage($from, "⚠️ No recibí respuesta del modelo. Asegúrate de que el archivo sea legible.");
                        }

                        // 🔍 Limpiar la respuesta (por si incluye ```json o markdown)
                        $cleaned = preg_replace('/^```(json)?/i', '', $rawResponse);
                        $cleaned = preg_replace('/```$/', '', $cleaned);
                        $cleaned = trim($cleaned);

                        // 3️⃣ Intentar decodificar el JSON
                        $decoded = json_decode($cleaned, true);

                        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                            // ❌ Si no es JSON válido, mostrar literal para depurar
                            return $this->sendMessage(
                                $from,
                                "⚠️ No se pudo interpretar el JSON devuelto por la IA. Respuesta literal:\n" . Str::limit($rawResponse, 3800)
                            );
                        }

                        $actividad = $decoded['tramos_actividad'] ?? null;
                        //$actividad = $activityStr ? explode(',', $activityStr) : [];

                        // 4️⃣ Mostrar los datos extraídos
                        $info = "📄 *Datos extraídos del parte de trabajo:*\n";
                        $info .= "🕒 *Hora entrada:* " . ($decoded['hora_entrada'] ?? 'No detectada') . "\n";
                        $info .= "🕕 *Hora salida:* " . ($decoded['hora_salida'] ?? 'No detectada') . "\n";
                        $info .= "⏱️ *Horas totales:* " . ($decoded['horas_totales'] ?? 'No detectadas') . "\n";
                        $info .= "\n*Tramos de actividad:*\n\n";
                        foreach ($actividad as $index => $tramo) {
                            $info .= "🕒 *Tramo de actividad " . ($index + 1) . ":* " . trim($tramo) . "\n";
                        }
                        $info .= "\n📅 *Día:* " . ($decoded['fecha'] ?? 'No detectado') . "\n";
                        $info .= "👷‍♂️ *Técnico:* " . ($decoded['técnico'] ?? 'No detectado') . "\n";
                        $info .= "🏢 *Cliente:* " . ($decoded['cliente'] ?? 'No detectado') . "\n";
                        $info .= "🚗 *Matrícula:* " . ($decoded['matrícula'] ?? 'No detectada') . "\n";
                        $info .= "📍 *Dirección:* " . ($decoded['dirección'] ?? 'No detectada') . "\n";
                        $info .= "📝 *Observaciones:* " . ($decoded['observaciones'] ?? '-') . "\n\n";

                        $session->update([
                            'data' => [
                                'work_part_data' => $decoded,
                                'file' => [
                                    'url' => $mediaUrl,
                                    'type' => $mediaType,
                                    'name' => $fileName,
                                ],
                            ],
                            'step' => 'signing_confirm_from_work_part',
                        ]);

                        return $this->sendOptions($from, $info . "\n¿Deseas guardar un fichaje con los datos del parte?",
                            [
                                ['id' => '1', 'title' => 'Sí'],
                                ['id' => '2', 'title' => 'No']
                            ]);

                    } catch (\Throwable $e) {
                        $errorMessage = "❌ *Error procesando el archivo*\n";
                        $errorMessage .= "Asegúrate de que el archivo adjuntado sea un PDF o imagen válida.\n\n";
                        $errorMessage .= "*Mensaje:* " . $e->getMessage() . "\n";
                        $errorMessage .= "*Archivo:* " . basename($e->getFile()) . "\n";
                        $errorMessage .= "*Línea:* " . $e->getLine();

                        return $this->sendMessage($from, Str::limit($errorMessage, 3900));
                    }
                case 'signing_confirm_from_work_part':
                    switch ($message) {
                        case '1':
                            $data = $session->data;
                            $workPartData = $data['work_part_data'] ?? [];
                            $fileData = $data['file'] ?? [];

                            // Validar que se tienen los datos mínimos
                            if (empty($workPartData['fecha']) || empty($workPartData['hora_entrada'])) {
                                return $this->sendMessage(
                                    $from,
                                    "⚠️ No se pudieron extraer los datos mínimos (fecha y hora de entrada) del parte. No se puede guardar el fichaje."
                                );
                            }

                            // Formatear fecha y hora
                            $dateStr = $workPartData['fecha'];
                            $entryStr = $workPartData['hora_entrada'];
                            $exitStr = $workPartData['hora_salida'];
                            $notes = $workPartData['observaciones'] ?? '';
                            $activitySections = $workPartData['tramos_actividad'] ?? [];

                            // Guardar el fichaje
                            $signingClient = new SigningController();
                            $response = $signingClient::createSigning(
                                $user->id,
                                $dateStr,
                                $entryStr,
                                $exitStr,
                                $activitySections,
                                $notes,
                            );

                            $signingClient::attachWorkOrder(
                                $response->getData(true)['signing_id'] ?? null,
                                $fileData
                            );

                            $dataResp = $response->getData(true);

                            // Reset flujo y respuesta al usuario
                            $session->update(['step' => 'start_crm', 'data' => null]);
                            $session->delete();
                            return $this->sendMessage($from, $dataResp['message'] ?? 'Fichaje registrado.');
                        case '2':
                            // Cancelar guardado
                            $session->update(['step' => 'start_crm', 'data' => null]);
                            return $this->sendMessage($from, "Operación cancelada. ¿Qué deseas hacer ahora?");
                        default:
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarloss.");
                    }
                case 'signing_send_location':

                    $payloadAll = $request->all();

                    $messageData = $this->normalizeWhatsappLocation($payloadAll);

                    if (!$messageData) {
                        return $this->sendMessage(
                            $from,
                            "⚠️ No pude leer el mensaje recibido. Intenta enviar la ubicación de nuevo."
                        );
                    }

                    $from = $messageData['from'] ?? $from;
                    $type = $messageData['type'] ?? 'text';

                    if ($type !== 'location') {
                        return $this->sendMessage(
                            $from,
                            "⚠️ Para registrar el fichaje necesito que envíes la ubicación desde WhatsApp.\n\n" .
                            "Usa:\n" .
                            "📎 Adjuntar → Ubicación → Enviar ubicación actual\n\n" .
                            "No escribas las coordenadas manualmente."
                        );
                    }

                    $latitude = $messageData['location']['latitude'] ?? null;
                    $longitude = $messageData['location']['longitude'] ?? null;
                    $address = $messageData['location']['address'] ?? null;

                    if (is_null($latitude) || is_null($longitude)) {
                        return $this->sendMessage(
                            $from,
                            "⚠️ No pude obtener las coordenadas de la ubicación.\n\n" .
                            "Intenta enviarla de nuevo usando:\n" .
                            "📎 Adjuntar → Ubicación → Enviar ubicación actual"
                        );
                    }

                    $isSelectedPlace =
                        !empty($messageData['location']['name']) ||
                        !empty($messageData['location']['title']) ||
                        !empty($messageData['location']['url']) ||
                        !empty($messageData['location']['description']) ||
                        !empty($messageData['location']['place_id']);

                    if ($isSelectedPlace) {
                        return $this->sendMessage(
                            $from,
                            "⚠️ No puedo registrar el fichaje con una ubicación seleccionada manualmente o buscada.\n\n" .
                            "Por seguridad, debes enviar tu ubicación actual desde WhatsApp:\n\n" .
                            "📎 Adjuntar → Ubicación → *Enviar ubicación actual*\n\n" .
                            "No selecciones un sitio del mapa ni escribas coordenadas manualmente."
                        );
                    }

                    $location = [
                        'lat' => $latitude,
                        'lng' => $longitude,
                        'address' => $address,
                        'source' => 'whatsapp_current_location',
                        'captured_at' => now()->toISOString(),
                    ];

                    $response = SigningController::saveSigningsFromWhatsapp($user->id, $location);
                    $dataResp = $response->getData(true);

                    $session->update(['step' => 'start_crm', 'data' => null]);
                    $session->delete();

                    /*
                    * Caso especial: había un fichaje antiguo abierto.
                    * Se cierra automáticamente, pero NO se crea una entrada nueva.
                    */
                    if (($dataResp['type'] ?? null) === 'auto_closed_previous_day') {
                        $closed = $dataResp['data'] ?? [];

                        $closedDate = $closed['date'] ?? null;
                        $closedEntry = $closed['entry'] ?? null;

                        $messageToSend = "⚠️ *Fichaje anterior cerrado automáticamente*\n\n";

                        if ($closedDate || $closedEntry) {
                            $messageToSend .= "Tenías una entrada abierta";
                            if ($closedDate) {
                                $messageToSend .= " del día *{$closedDate}*";
                            }
                            if ($closedEntry) {
                                $messageToSend .= " a las *{$closedEntry}*";
                            }
                            $messageToSend .= ".\n\n";
                        }

                        $messageToSend .= "Se ha marcado como *sin salida registrada*.\n\n";
                        $messageToSend .= "Ahora vuelve a iniciar el fichaje y envía tu ubicación para registrar la entrada de hoy.";

                        return $this->sendMessage($from, $messageToSend);
                    }

                    /*
                    * Mensaje resumen después de fichar.
                    */
                    $signing = $dataResp['data'] ?? [];

                    $typeText = match ($dataResp['type'] ?? null) {
                        'entry' => 'entrada',
                        'exit' => 'salida',
                        default => 'fichaje',
                    };

                    $hour = ($dataResp['type'] ?? null) === 'entry'
                        ? ($signing['entry'] ?? now()->format('H:i'))
                        : ($signing['exit'] ?? now()->format('H:i'));

                    $locationSaved = !empty($location['lat']) && !empty($location['lng']);

                    $messageToSend = "✅ *Fichaje de {$typeText} registrado correctamente.*\n\n";
                    $messageToSend .= "🕒 Hora: *{$hour}*\n";
                    $messageToSend .= $locationSaved
                        ? "📍 Ubicación: *registrada*"
                        : "📍 Ubicación: *no registrada*";

                    if (!empty($address)) {
                        $messageToSend .= "\n📌 Dirección aproximada: {$address}";
                    }

                    return $this->sendMessage($from, $messageToSend);



                //OPORTUNIDADES EXTERNAS DE FACEBOOK Y COCHE ELECTRICO
                case 'external_opp_options':
                    switch ($message) {
                        case '1':
                            $session->update(['step' => 'no_action']);

                            $link = "https://calendly.com/giacomo-zocoenergia/cargador-coche";

                            return $this->sendMessage(
                                $from,
                                "Perfecto. Puedes elegir el día y la hora que mejor te venga para hablar con un agente desde este enlace:\n\n" .
                                $link,
                                'chat_link',
                                '',
                                '',
                                [
                                    'no_link_preview' => false,
                                ]
                            );

                        case '2':
                            $session->update(['step' => 'no_action']);

                            $link = "https://segenet.es/cargador-electrico?id=" . $session->opportunity_id;

                            return $this->sendMessage(
                                $from,
                                "Perfecto, puedes calcular tu presupuesto estimado para la instalación del punto de recarga desde este enlace:\n\n" .
                                $link,
                                'link_preview',
                                'Calcula tu presupuesto estimado',
                                'Instalación de punto de recarga para vehículo eléctrico',
                                [
                                    'canonical' => $link,
                                    'media' => 'https://movilidad.segenet.es/electric-car.jpg',
                                    'mime_type' => 'image/jpeg',
                                ]
                            );

                        default:
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }

                //✍️Edición y guardar en bbdd
                case 'contact_confirm':
                    if ($message === '1') {
                        return $this->saveContact($session, $from, $user);
                    } elseif ($message === '2') {
                        $session->update(['step' => 'contact_edit']);
                        return $this->sendMessage($from, "¿Qué campo del contacto deseas editar? (Escribe el nombre del campo igual que aparece arriba)");
                    } else {
                        return $this->sendOptions($from, "Opción no válida.", [
                            ['id' => '1', 'title' => 'Guardar'],
                            ['id' => '2', 'title' => 'Editar']
                        ]);
                    }
                case 'contact_edit':
                    $message = $this->normalizeString(trim(mb_strtolower($message, 'UTF-8')));

                    $validFields = ['nombre' => 'name', 'apellidos' => 'surname', 'dni' => 'dni', 'email' => 'email', 'telefono' => 'phone', 'comunidad' => 'community', 'provincia' => 'province', 'localidad' => 'locality', 'direccion' => 'address', 'codigo postal' => 'postal'];

                    if (!array_key_exists($message, $validFields)) {
                        return $this->sendMessage($from, "Campo no válido. Escribelo exactamente igual que aparece arriba");
                    }

                    // Guardar el campo a editar y manejar las dependencias
                    $session->update(['step' => 'contact_edit_value', 'data' => array_merge($session->data, ['edit_field' => $validFields[$message]])]);
                    return $this->sendMessage($from, "Introduce el nuevo valor para " . $message . ":");
                case 'contact_edit_value':
                    $data = $session->data;
                    $field = $data['edit_field'];

                    //Validaciones
                    if ($field === 'postal' && (!is_numeric($message) || strlen($message) !== 5))
                        return $this->sendMessage($from, "❌ El código postal no es válido. Vuelve a intentarlo.");

                    //Si es nombre o apellidos
                    if (in_array($field, ['name', 'surname'])) {
                        $message = explode(' ', $message);

                        if ($field === 'name') {

                            $name = [
                                'first' => $message[0],
                                'second' => $message[1] ?? ''
                            ];

                            $data['contact']['name'] = $name;
                        } else {
                            $surname = [
                                'first' => $message[0],
                                'second' => $message[1] ?? ''
                            ];

                            $data['contact']['surname'] = $surname;
                        }
                    } else {
                        $data['contact'][$field] = $message;
                    }

                    $session->update(['data' => $data]); //, 'step' => 'contract_confirm'
                    return $this->resumeContact($session, $from);

                case 'account_confirm':
                    if ($message === '1') {
                        $data = $session->data;

                        //Compruebo si hay que hacer el resumen de contrato o guardar directamente
                        if ($session->type === 'account' && !isset($data['contract'])) {
                            return $this->saveAcc($session, $from, $user);
                        } else {
                            return $this->resumeContract($session, $from, $productTypes);
                        }
                    } elseif ($message === '2') {
                        $session->update(['step' => 'account_edit']);
                        return $this->sendMessage($from, "¿Qué campo de la cuenta deseas editar? (Escribe el nombre del campo igual que aparece arriba)");
                    } elseif ($message === '3') {

                        $opportunities = $this->getRelatedOpportunities($from, $user);

                        //Muestro mensaje para elegir contacto a relacionar
                        $messageToSend = "Elige una oportunidad para relacionar por CIF/NIF:\n\n";

                        foreach ($opportunities as $opp) {
                            $messageToSend .= '📌' . $opp['name'] . ' | ' . $opp['CIF'] . "\n\n";
                        }

                        $session->update(['step' => 'select_account_opportunity']);
                        return $this->sendMessage($from, Str::limit($messageToSend, 3900));
                    } else {
                        return $this->sendOptions($from, "Opción no válida.",
                            [
                                ['id' => '1', 'title' => 'Guardar'],
                                ['id' => '2', 'title' => 'Editar']
                            ]);
                    }
                case 'account_edit':
                    $message = $this->normalizeString(trim(mb_strtolower($message, 'UTF-8')));
                    $validFields = ['nombre' => 'name', 'cif' => 'cif', 'telefono' => 'phone', 'email' => 'email', 'direccion' => 'address', 'comunidad autonoma' => 'community', 'provincia' => 'province', 'poblacion' => 'locality', 'codigo postal' => 'postal'];
                    if (!array_key_exists($message, $validFields)) {
                        return $this->sendMessage($from, "Campo no válido. Escribelo exactamente igual.");
                    }

                    $session->update(['step' => 'account_edit_value', 'data' => array_merge($session->data, ['edit_field' => $validFields[$message]])]);
                    return $this->sendMessage($from, "Introduce el nuevo valor para " . $message . ":");
                case 'account_edit_value':
                    $data = $session->data;
                    $field = $data['edit_field'];
                    $data[$field] = $message;
                    unset($data['edit_field']);
                    $session->update(['data' => $data, 'step' => 'account_confirm']);
                    return $this->resumeAcc($session, $from);
                case 'select_account_opportunity':

                    $data = $session->data;

                    //Saco contactos
                    $opps = $this->getRelatedOpportunities($from, $user);

                    //Filtro para buscar el seleccionado
                    $oppSelected = collect($opps)->first(function ($opp) use ($message) {
                        return ($opp['CIF'] ?? null) === $message;
                    });

                    if ($oppSelected) {
                        $data['opportunity'] = $oppSelected['_id'];
                        $session->update(['data' => $data, 'step' => 'account_confirm']);
                        return $this->resumeAcc($session, $from);
                    } else
                        return $this->sendMessage($from, "❌Contacto no encontrado. Vuelve a intentarlo.");
                    break;

                case 'contract_confirm':
                    if ($message === '1') {
                        //Guardo
                        return $this->saveAcc($session, $from, $user);
                    } elseif ($message === '2') {
                        $session->update(['step' => 'contract_edit']);
                        return $this->sendMessage($from, "¿Qué campo del contrato deseas editar? (Escribe el nombre del campo igual que aparece arriba)");
                    } else {
                        return $this->sendOptions($from, "Opción no válida.", [
                            ['id' => '1', 'title' => 'Guardar'],
                            ['id' => '2', 'title' => 'Editar']
                        ]);
                    }
                case 'contract_edit':
                    $message = $this->normalizeString(trim(mb_strtolower($message, 'UTF-8')));

                    $data = $session->data;

                    $validFields = ['nombre' => 'name', 'direccion' => 'address', 'poblacion' => 'town', 'provincia' => 'province', 'codigo postal' => 'postal', 'iban' => 'IBAN', 'tipo de producto' => 'productType', 'producto' => 'product'];

                    //Si es cl o cg se incluiran las nuevas
                    if ($data['contract']['productType'] === 'cl' || $data['contract']['productType'] === 'cg')
                        $validFields = array_merge($validFields, ['comercializadora' => 'marketer', 'tarifa' => 'fee']);


                    if (!array_key_exists($message, $validFields)) {
                        return $this->sendMessage($from, "Campo no válido. Escribelo exactamente igual que aparece arriba");
                    }

                    $editField = $validFields[$message];

                    // Guardar el campo a editar y manejar las dependencias
                    return $this->handleContractEdit($session, $from, $editField, $user, $productTypes, $basicProducts);
                case 'contract_edit_value':
                    $data = $session->data;
                    $field = $data['edit_field'];

                    // Si se cambió un campo que tiene dependencias, llamar a `handleContractEdit()`
                    if (in_array($field, ['productType', 'marketer', 'fee', 'product'])) {
                        return $this->handleContractEdit($session, $from, $field, $user, $productTypes, $basicProducts);
                    }

                    //Si es el IBAN
                    if ($field === 'IBAN' && (!empty($message) && strlen($message) !== 29))
                        return $this->sendMessage($from, "❌ El IBAN no es válido. Vuelve a intentarlo.");

                    // Guardar el valor si no hay dependencias
                    $data['contract'][$field] = $message;
                    $session->update(['data' => $data]); //, 'step' => 'contract_confirm'
                    return $this->resumeContract($session, $from, $productTypes);

                case 'opportunity_confirm':

                    $data = $session->data;

                    //$oppExists = $session->type !== 'opportunity' ? Opportunity::where('CIF', $data['pdfData']['cif_nif'])->where('createdBy', $user['_id'])->where('order.productType', 'cl')->first() : null;

                    if ($message === '1') {
                        return $this->saveOpp($session, $from, $user);
                    } elseif ($message === '2') {

                        $session->update(['step' => 'opportunity_edit']);
                        return $this->sendMessage($from, "¿Qué campo deseas editar? (Escribe el nombre del campo igual que aparece arriba)");

                        /*if (isset($oppExists) && $session->type !== 'opportunity') {
                            $session->update(['step' => 'opportunity_existing_edit_options']);
                            return $this->sendMessage($from, "¿Qué deseas editar?\n 1️⃣Cambiar oferta\n 2️⃣Editar otros campos");
                        } else {
                            $session->update(['step' => 'opportunity_edit']);
                            return $this->sendMessage($from, "¿Qué campo deseas editar? (Escribe el nombre del campo igual que aparece arriba)");
                        }*/
                    } elseif ($message === '3') {

                        //Muestro mensaje para elegir contacto a relacionar
                        $messageToSend = "Escribe el teléfono o email de un contacto para relacionarlo con esta oportunidad:";

                        $session->update(['step' => 'check_existing_contact']);
                        return $this->sendMessage($from, Str::limit($messageToSend, 3900));
                    } else {
                        return $this->sendMessage($from, "Opción no válida. Escribe 1 para guardar o 2 para editar.");
                    }

                    break;
                case 'opportunity_existing_edit_options':
                    $data = $session->data;

                    switch ($message) {
                        case 1:
                            //Le saco las oferta
                            $session->update(['step' => 'choose_offer']);
                            $comparative = $this->getOffersMessage($session, $data, $from, $user, false);
                            return $this->sendMessage($from, $comparative);
                            break;

                        case 2:
                            $session->update(['step' => 'opportunity_edit']);
                            return $this->sendMessage($from, "¿Qué campo deseas editar? (Escribe el nombre del campo igual que aparece arriba)");
                            break;

                        default:
                            return $this->sendMessage($from, "Opción no válida. Vuelve a intentarlo.");
                    }
                    break;
                case 'opportunity_edit':
                    $message = $this->normalizeString(trim(mb_strtolower($message, 'UTF-8')));
                    $validFields = ['nombre' => 'account.name', 'cif' => 'account.cif_nif', 'nif' => 'account.cif_nif', 'cif/nif' => 'account.cif_nif', 'movil' => 'account.phone', 'email' => 'account.email', 'direccion oportunidad' => 'account.address', 'comunidad oportunidad' => 'account.community', 'provincia oportunidad' => 'account.province', 'localidad oportunidad' => 'account.town', 'codigo postal oportunidad' => 'account.postal', 'provincia contrato' => 'order.province', 'localidad contrato' => 'order.locality', 'direccion contrato' => 'order.address', 'codigo postal contrato' => 'order.postal', 'tipo de producto' => 'order.productType', 'comercializadora' => 'order.marketer', 'tarifa' => 'order.fee', 'producto' => 'order.product'];
                    if (!array_key_exists($message, $validFields))
                        return $this->sendMessage($from, "Campo no válido. Escribelo exactamente igual.");


                    //$session->update(['step' => 'opportunity_edit_value', 'data' => array_merge($session->data, ['edit_field' => $validFields[$message]])]);
                    return $this->handleContractEdit($session, $from, $validFields[$message], $user, $productTypes, $basicProducts);
                    break;
                case 'opportunity_edit_value':
                    $data = $session->data;
                    $field = $data['edit_field'];

                    //Divido el campo
                    $type = explode('.', $field)[0];
                    $toSearch = explode('.', $field)[1];

                    // Si se cambió un campo que tiene dependencias, llamar a `handleContractEdit()`
                    if (in_array($toSearch, ['productType', 'marketer', 'fee', 'product'])) {
                        return $this->handleContractEdit($session, $from, $toSearch, $user, $productTypes, $basicProducts);
                    }

                    switch ($type) {
                        case 'account':
                            $data['opportunity'][$toSearch] = $message;
                            break;

                        case 'order':
                            $data['opportunity']['order'][$toSearch] = $message;
                            break;
                    }

                    unset($data['edit_field']);

                    // Guardar el valor si no hay dependencias
                    $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                    return $this->resumeOpportunity($session, $from, $productTypes, $user);
                /*case 'select_opportunity_contact':

                    $data = $session->data;

                    //Saco contactos
                    $contacts = $this->getRelatedContacts($from, $user);

                    //Filtro para buscar el seleccionado
                    $contactSelected = collect($contacts)->first(function ($contact) use ($message) {
                        return ($contact['email'] ?? null) === $message || ($contact['phone'] ?? null) === $message;
                    });

                    if ($contactSelected) {
                        $data['opportunity']['contact'] = $contactSelected['_id'];
                        $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                        return $this->resumeOpportunity($session, $from, $productTypes, $user);
                    } else
                        return $this->sendMessage($from, "❌Contacto no encontrado. Vuelve a intentarlo.");
                    break;*/
                case 'check_existing_contact':
                    $contact = Contact::where('usersIds', $user->_id)
                        ->where(function ($query) use ($message) {
                            $query->where('email', $message)
                                ->orWhere('phone', $message);
                        })->first();

                    if (!$contact)
                        return $this->sendMessage($from, "Contacto no encontrado. Escribe un teléfono o email válido.");

                    $data = $session->data;
                    $data['contactEmailOrPhone'] = $message;

                    $session->update(['data' => $data, 'step' => 'existing_contact']);

                    $fullName = trim(implode(' ', array_filter([
                        data_get($contact, 'name.first'),
                        data_get($contact, 'name.second'),
                        data_get($contact, 'surname.first'),
                        data_get($contact, 'surname.second'),
                    ])));

                    return $this->sendOptions($from, "El contacto encontrado es: *" . $fullName . "*\n\n ¿Es el contacto que buscas?",
                        [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No']
                        ]);
                    break;

                case 'existing_contact':

                    $data = $session->data;

                    switch ($message) {
                        case '1':
                            $contact = Contact::where('usersIds', $user->_id)
                                ->where(function ($query) use ($data) {
                                    $query->where('email', $data['contactEmailOrPhone'])
                                        ->orWhere('phone', $data['contactEmailOrPhone']);
                                })->first();



                            if (!$contact)
                                return $this->sendMessage($from, "Contacto no encontrado. Escribe un teléfono o email válido.");

                            $data['opportunity']['contact'] = $contact->_id;

                            $session->update(['data' => $data, 'step' => 'opportunity_confirm']);
                            return $this->resumeOpportunity($session, $from, $productTypes, $user);
                            break;

                        case '2':
                            $session->update(['step' => 'check_existing_contact']);
                            return $this->sendMessage($from, "Introduce el teléfono o el email del contacto:");
                            break;

                        default:
                            $this->sendOptions($from, "Opción no valida. ¿Es el contacto que buscas?",
                                [
                                    ['id' => '1', 'title' => 'Sí'],
                                    ['id' => '2', 'title' => 'No']
                                ]);
                            break;
                    }
                    break;


                case 'required_field_value':
                    $data = $session->data;
                    $pendingField = $data['pendingField'];

                    $regexFields = [
                        'acc.phone' => '/^(?:\34\s?|34\s?|0)?[6-9]\d{2}[\s-]?\d{3}[\s-]?\d{3}$/',
                        'acc.email' => '/^[\w\.\-]+@([\w\-]+\.)+[a-zA-Z]{2,}$/',
                        'order.zip' => '/^(?:0[1-9]|[1-4][0-9]|5[0-2])\d{3}$/',
                        'order.IBAN' => '/^ES\d{2}([ -]?\d{4}){5}$/i',
                    ];

                    //Si el campo tiene un regex lo compruebo, sino lo guardo directamente
                    if (isset($regexFields[$pendingField])) {
                        if (!preg_match($regexFields[$pendingField], $message)) {
                            return $this->sendMessage($from, "⚠️ El formato del dato introducido no es válido. Inténtalo de nuevo.");
                        }
                    }

                    // Guardar el valor
                    [$group, $key] = explode('.', $pendingField);

                    if ($group === 'acc') {
                        $data[$key] = $message;
                    } elseif ($group === 'order') {
                        switch ($key) {
                            case 'direc':
                                $data['contract']['address'] = $message;
                                break;
                            case 'zip':
                                $data['contract']['postal'] = $message;
                                break;
                            default:
                                $data['contract'][$key] = $message;
                                break;
                        }
                    }

                    // Limpiar campo pendiente y actualizar sesión
                    unset($data['pendingField']);
                    $session->update(['data' => $data]);

                    //Sigo comprobando los campos
                    $this->checkMissingObligatoryFields($session, $from, $user);

                    break;


                //📁Docs
                case 'attach_document':

                    $data = $session->data;

                    if ($message === '1') {
                        $session->update(['step' => 'document_upload']);
                        return $this->sendMessage($from, "Adjunta el archivo ahora. ( De 1 en 1)");
                    } elseif ($message === '2') {

                        //Si es type contract y no tiene datos de contract todavia se irá a la creación del contrato
                        if ($session->type === 'contract' && !isset($data['contract'])) {

                            $session->update(['step' => 'contract_name']);
                            return $this->sendMessage($from, "Introduce el nombre del contrato:");
                        } else if ($session->type === 'account' || $session->type === 'contract'){

                            //Resumen
                            if ($session->type === 'account' || ($session->type === 'contract' && isset($data['name'])))
                                return $this->resumeAcc($session, $from);
                            else {
                                //Si se han metido datos de la cuenta se vera primero el resumen de la cuenta y dps del contrato
                                return $this->resumeContract($session, $from, $productTypes);
                            }
                        } else{
                            //Oportunidad
                            $session->update(['step' => 'opp_order_options']);
                            return $this->sendOptions($from, "¿Quieres añadir datos del contrato?", [
                                ['id' => '1', 'title' => 'Sí'],
                                ['id' => '2', 'title' => 'No']
                            ]);
                        }
                    } else {
                        return $this->sendOptions($from, "Opción no válida.", [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No']
                        ]);
                    }
                    break;
                case 'document_upload':

                    if (!$mediaUrl) {
                        return $this->sendMessage($from, "❌ No detecté un archivo adjunto. Asegúrate de enviarlo correctamente.");
                    }

                    // Descargar y subir el archivo al FTP
                    $uploadedFilePath = $this->downloadAndUploadToFTP($mediaUrl, $fileName, $mediaType, $session);
                    if (!$uploadedFilePath) {
                        return $this->sendMessage($from, "❌ No pude guardar el archivo en el servidor.");
                    }

                    // Guardar la referencia del archivo en la sesión
                    $data = $session->data;

                    //Si es de cuenta
                    if ($session->documentTemporal === 'account')
                        $data['documents'][] = ['name' => $fileName, 'file' => $uploadedFilePath];
                    else if ($session->documentTemporal === 'order')//si es de contrato
                        $data['contract']['documents'][] = ['name' => $fileName, 'file' => $uploadedFilePath];
                    else
                        $data['documents'][] = ['name' => $fileName, 'file' => $uploadedFilePath];


                    $session->update(['data' => $data, 'step' => 'document_options']);

                    return $this->sendOptions($from, "✅ Archivo guardado en el servidor.\n\n📂 ¿Qué quieres hacer con el archivo?", [
                        ['id' => '1', 'title' => 'Cambiar nombre'],
                        ['id' => '2', 'title' => 'Eliminar'],
                        ['id' => '3', 'title' => 'Continuar']
                    ]);
                    break;
                case 'document_options':
                    if ($message === '1') {
                        $session->update(['step' => 'document_rename']);
                        return $this->sendMessage($from, "Escribe el nuevo nombre para el documento:");
                    } elseif ($message === '2') {
                        $data = $session->data;

                        if ($session->documentTemporal === 'account') {
                            //Según si es contrato o cuenta
                            $lastFile = array_pop($data['documents']);
                            Storage::disk('account')->delete($lastFile['file']);
                        } else if ($session->documentTemporal === 'order'){
                            $lastFile = array_pop($data['contract']['documents']);
                            Storage::disk('order')->delete($lastFile['file']);
                        } else{
                            $lastFile = array_pop($data['documents']);
                            Storage::disk('opportunity')->delete($lastFile['file']);
                        }


                        $session->update(['data' => $data, 'step' => 'more_documents']);
                        return $this->sendOptions($from, "✅ Archivo eliminado.\n¿Quieres subir otro documento?", [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No']
                        ]);
                    } elseif ($message === '3') {
                        $data = $session->data;
                        $session->update(['data' => $data, 'step' => 'more_documents']);
                        return $this->sendOptions($from, "¿Quieres subir otro documento?", [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No']
                        ]);
                    } else {
                        return $this->sendOptions($from, "Opción no válida.", [
                            ['id' => '1', 'title' => 'Cambiar nombre'],
                            ['id' => '2', 'title' => 'Eliminar'],
                            ['id' => '3', 'title' => 'Continuar'],
                        ]);
                    }
                    break;
                case 'document_rename':
                    $data = $session->data;

                    if ($session->documentTemporal === 'account') {
                        $lastIndex = count($data['documents']) - 1;
                        $data['documents'][$lastIndex]['name'] = $message;
                    } else if ($session->documentTemporal === 'order'){
                        $lastIndex = count($data['contract']['documents']) - 1;
                        $data['contract']['documents'][$lastIndex]['name'] = $message;
                    }else{
                        $lastIndex = count($data['documents']) - 1;
                        $data['documents'][$lastIndex]['name'] = $message;
                    }

                    $session->update(['data' => $data, 'step' => 'more_documents']);
                    return $this->sendOptions($from, "✅ Nombre cambiado.\n¿Quieres subir otro documento?",
                        [
                            ['id' => '1', 'title' => 'Sí'],
                            ['id' => '2', 'title' => 'No']
                        ]);
                    break;
                case 'more_documents':
                    if ($message === '1') {
                        $session->update(['step' => 'document_upload']);
                        return $this->sendMessage($from, "Adjunta el siguiente documento.");
                    }

                    $data = $session->data;

                    if ($session->app === 'crm') {

                        //Si se esta creando los docs de cuenta y es tipo contrato
                        if ($session->type === 'contract' && !isset($data['contract'])) {

                            $session->update(['step' => 'contract_name']);
                            return $this->sendMessage($from, "Introduce el nombre del contrato:");
                        } else if ($session->type === 'account' || $session->type === 'contract'){

                            //Resumen
                            if ($session->type === 'account' || ($session->type === 'contract' && isset($data['name']))) { //Si es cuenta o es contrato pero tiene datos de cuenta ( sería creada nueva)
                                return $this->resumeAcc($session, $from);
                            } else {
                                //Si se han metido datos de la cuenta se vera primero el resumen de la cuenta y dps del contrato
                                return $this->resumeContract($session, $from, $productTypes);
                            }
                        } else{
                            //Oportunidad
                            $session->update(['step' => 'opp_order_options']);
                            return $this->sendOptions($from, "¿Quieres añadir datos del contrato?", [
                                ['id' => '1', 'title' => 'Sí'],
                                ['id' => '2', 'title' => 'No']
                            ]);
                        }
                    } else {
                        //Si es witro comprobará si hay algún campo que falte y los pedirá si es necesario
                        $this->checkMissingObligatoryFields($session, $from, $user);
                    }

                    break;


                //Paso sin hablar bot
                case 'no_action':
                    break;


                default:
                    $session->delete();
                    return $this->sendMessage($from, "Reiniciando...");
            }
        } catch (\Throwable $e) {
            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'receiveMessage';
            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }


    //Inicializo la API que voy a usar
    private function bootWhatsAppDriver(array $payload)
    {

        // ULTRAMSG
        if (isset($payload['instanceId'])) {
            $enterprise = Enterprise::where('ultramsg.instanceId', $payload['instanceId'])->firstOrFail();

            $name = $enterprise['ultramsg']['name'];

            self::$WHATSAPP_INSTANCE_ID = env('WHATSAPP_INSTANCE_ID_' . $name);
            self::$WHATSAPP_INSTANCE_TOKEN = env('WHATSAPP_TOKEN_' . $name);

            $this->whatsappProvider = 'ultramsg';

            $this->whatsappDriver = new UltraMsgDriver(
                self::$WHATSAPP_INSTANCE_ID,
                self::$WHATSAPP_INSTANCE_TOKEN
            );

            return $enterprise;
        }

        // WHAPI
        $channelId = $payload['channel_id']
            ?? $payload['channel']['id']
            ?? $payload['messages'][0]['channel_id']
            ?? $payload['messages'][0]['channel']['id']
            ?? null;

        if ($channelId) {
            $enterprise = Enterprise::where('whapi.channel_id', $channelId)->firstOrFail();

            $name = $enterprise['whapi']['name'];

            self::$WHATSAPP_INSTANCE_ID = $enterprise['whapi']['channel_id'];
            self::$WHATSAPP_INSTANCE_TOKEN = env('WHATSAPP_TOKEN_' . $name);

            $this->whatsappProvider = 'whapi';

            $this->whatsappDriver = new WhapiDriver(
                self::$WHATSAPP_INSTANCE_TOKEN
            );

            return $enterprise;
        }

        throw new \Exception('No se pudo detectar el proveedor de WhatsApp.');
    }


    //Para extraer correctamente los mensajes de todas las APIs
    private function parseIncomingMessage(array $payload): array
    {
        // ULTRAMSG
        if (isset($payload['instanceId']) || isset($payload['data'])) {
            $from = $payload['data']['from'] ?? $payload['from'] ?? null;

            if ($from && strpos($from, '@c.us') !== false) {
                $from = str_replace('@c.us', '', $from);
            }

            return [
                'from' => $from,
                'message' => trim($payload['data']['body'] ?? $payload['body'] ?? ''),
                'mediaUrl' => $payload['data']['media'] ?? null,
                'mediaType' => $payload['data']['type'] ?? null,
                'fileName' => $payload['data']['filename'] ?? 'documento_' . time(),
                'quotedId' => null,
            ];
        }

        // WHAPI
        if (isset($payload['messages'][0])) {
            $msg = $payload['messages'][0];
        } elseif (isset($payload['chats_updates'][0])) {
            $chatUpdate = $payload['chats_updates'][0];

            $afterMessage = $chatUpdate['after_update']['last_message'] ?? null;
            $beforeMessage = $chatUpdate['before_update']['last_message'] ?? null;

            // Elegimos siempre el mensaje del usuario, no el mensaje del bot
            if ($afterMessage && (($afterMessage['from_me'] ?? false) === false)) {
                $msg = $afterMessage;
            } elseif ($beforeMessage && (($beforeMessage['from_me'] ?? false) === false)) {
                $msg = $beforeMessage;
            } else {
                $msg = $afterMessage ?? $beforeMessage ?? $payload;
            }
        } else {
            $msg = $payload;
        }

        if (($msg['from_me'] ?? false) === true)
            return [
                'from' => null,
                'message' => '',
                'mediaUrl' => null,
                'mediaType' => null,
                'fileName' => 'documento_' . time(),
                'quotedId' => null,
            ];


        $from = $msg['from']
            ?? $msg['chat_id']
            ?? $msg['chat']['id']
            ?? null;

        if ($from && strpos($from, '@') !== false)
            $from = explode('@', $from)[0];


        if ($from)
            $from = preg_replace('/\D+/', '', $from);


        // IDs de listas/botones de Whapi
        $optionId = $msg['reply']['list_reply']['id']
            ?? $msg['reply']['buttons_reply']['id']
            ?? $msg['reply']['button_reply']['id']
            ?? $msg['reply']['id']
            ?? $msg['button']['id']
            ?? $msg['button_reply']['id']
            ?? $msg['interactive']['button_reply']['id']
            ?? $msg['interactive']['list_reply']['id']
            ?? $msg['list_reply']['id']
            ?? null;

        // Whapi devuelve IDs tipo "ListV3:1" o "ButtonsV3:1"
        if ($optionId && str_contains($optionId, ':')) {
            $parts = explode(':', $optionId);
            $optionId = end($parts);
        }

        $messageText = $msg['text']['body']
            ?? $msg['body']
            ?? $msg['reply']['list_reply']['title']
            ?? $msg['reply']['buttons_reply']['title']
            ?? $msg['reply']['button_reply']['title']
            ?? '';

        $message = $optionId ?? $messageText;

        // Fallback por si llega "1. Guardar"
        if (!$optionId && preg_match('/^\s*(\d+)[\.\-\)]\s+/', $message, $matches)) {
            $message = $matches[1];
        }

        $mediaUrl = $msg['document']['link']
            ?? $msg['document']['url']
            ?? $msg['image']['link']
            ?? $msg['image']['url']
            ?? $msg['video']['link']
            ?? $msg['video']['url']
            ?? $msg['audio']['link']
            ?? $msg['audio']['url']
            ?? $msg['file']['link']
            ?? $msg['file']['url']
            ?? $msg['media']['link']
            ?? $msg['media']['url']
            ?? null;

        $mediaType = $msg['type'] ?? null;

        $fileName = $msg['document']['filename']
            ?? $msg['document']['file_name']
            ?? $msg['image']['filename']
            ?? $msg['image']['file_name']
            ?? $msg['file']['filename']
            ?? $msg['file']['file_name']
            ?? $msg['media']['filename']
            ?? $msg['media']['file_name']
            ?? match ($mediaType) {
                'image' => 'imagen_' . time() . '.jpg',
                'document' => 'documento_' . time() . '.pdf',
                default => 'documento_' . time(),
            };

        $quotedId = $msg['context']['quoted_id']
            ?? $msg['quoted_id']
            ?? null;

        return [
            'from' => $from,
            'message' => trim((string) $message),
            'mediaUrl' => $mediaUrl,
            'mediaType' => $mediaType,
            'fileName' => $fileName,
            'quotedId' => $quotedId,
        ];
    }

    //Función para sacar el usuario
    private function getEnterpriseWhatsappUserId($enterprise)
    {
        return $enterprise['ultramsg']['user'] ?? $enterprise['whapi']['user'] ?? null;
    }


    //Envio de mensajes al usuario
    private function sendMessage(
        $to,
        $message,
        $endpoint = 'chat',
        $title = 'Documento',
        $description = '',
        array $options = []
    ) {
        try {
            if (!$this->whatsappDriver) {
                throw new \Exception('WhatsApp driver no inicializado.');
            }

            $response = match ($endpoint) {
                'document' => $this->whatsappDriver->sendDocument(
                    $to,
                    $message,
                    $title,
                    $description
                ),

                'vcard' => $this->whatsappDriver->sendVCard(
                    $to,
                    $message
                ),

                // Texto normal, pero forzando que NO se desactive la preview.
                // Ideal para Calendly, porque WhatsApp cogerá la preview de la propia web.
                'chat_link' => $this->whatsappDriver->sendText(
                    $to,
                    $message,
                    [
                        'no_link_preview' => false,
                        'wide_link_preview' => $options['wide_link_preview'] ?? true,
                    ]
                ),

                // Preview personalizada.
                // Ideal para su enlace de Segenet con imagen, título y descripción propios.
                'link_preview' => $this->whatsappDriver->sendLinkPreview(
                    $to,
                    $message,
                    $title,
                    $description,
                    $options['canonical'] ?? null,
                    $options['media'] ?? null,
                    $options['mime_type'] ?? null
                ),

                default => $this->whatsappDriver->sendText(
                    $to,
                    $message
                ),
            };

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Error en sendMessage WhatsApp', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'endpoint' => $endpoint,
                'to' => $to,
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'part' => 'sendMessage',
            ], 500);
        }
    }

    //Envío de opciones al usuario
    private function sendOptions($to, $message, array $options = [], ?string $footer = null)
    {
        try {
            if (!$this->whatsappDriver) {
                throw new \Exception('WhatsApp driver no inicializado.');
            }

            if (empty($options)) {
                return $this->sendMessage($to, $message);
            }

            $response = $this->whatsappDriver->sendOptions(
                $to,
                $message,
                $options,
                $footer
            );

            $sentMessageId = $this->extractSentMessageId($response);

            if ($sentMessageId) {
                $this->rememberActiveInteractiveMessage($to, $sentMessageId);
            } else {
                Log::warning('WA - No se pudo guardar interactive activo porque no se encontró ID en respuesta', [
                    'to' => $to,
                    'provider' => $this->whatsappProvider,
                    'response' => $response,
                ]);
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            Log::error('Error en sendOptions WhatsApp', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'to' => $to,
            ]);

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'part' => 'sendOptions',
            ], 500);
        }
    }


    private function guardInteractiveReply(string $from, string $quotedId, string $message)
    {
        $activeInteractiveKey = $this->getActiveInteractiveCacheKey($from);
        $activeInteractiveId = Cache::get($activeInteractiveKey);

        /*
         * Si tenemos un mensaje interactivo activo guardado y el usuario
         * responde a otro mensaje anterior, lo ignoramos.
         */
        if ($activeInteractiveId && $activeInteractiveId !== $quotedId) {
            Log::info('WA - Opción antigua ignorada', [
                'from' => $from,
                'message' => $message,
                'quotedId' => $quotedId,
                'activeInteractiveId' => $activeInteractiveId,
                'instance' => self::$WHATSAPP_INSTANCE_ID,
            ]);

            return response()->json([
                'ignored' => true,
                'reason' => 'old_interactive_message',
            ]);
        }

        /*
         * Si el usuario pulsa dos opciones del mismo mensaje interactivo,
         * solo aceptamos la primera.
         */
        $processedKey = $this->getProcessedInteractiveCacheKey($from, $quotedId);

        if (!Cache::add($processedKey, $message, now()->addHours(24))) {
            Log::info('WA - Opción interactiva ya procesada', [
                'from' => $from,
                'message' => $message,
                'quotedId' => $quotedId,
                'instance' => self::$WHATSAPP_INSTANCE_ID,
            ]);

            return response()->json([
                'ignored' => true,
                'reason' => 'interactive_already_processed',
            ]);
        }

        return null;
    }

    private function rememberActiveInteractiveMessage(string $to, string $sentMessageId): void
    {
        Cache::put(
            $this->getActiveInteractiveCacheKey($to),
            $sentMessageId,
            now()->addHours(24)
        );

        Log::info('WA - Mensaje interactivo activo guardado', [
            'to' => $to,
            'sentMessageId' => $sentMessageId,
            'instance' => self::$WHATSAPP_INSTANCE_ID,
        ]);
    }

    private function getActiveInteractiveCacheKey(string $phone): string
    {
        return 'wa_active_interactive:' . self::$WHATSAPP_INSTANCE_ID . ':' . $phone;
    }

    private function getProcessedInteractiveCacheKey(string $phone, string $quotedId): string
    {
        return 'wa_processed_interactive:' . self::$WHATSAPP_INSTANCE_ID . ':' . $phone . ':' . $quotedId;
    }

    private function extractSentMessageId($response): ?string
    {
        if ($response instanceof \Illuminate\Http\Client\Response) {
            $response = $response->json();
        }

        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response = $response->getData(true);
        }

        if (is_string($response)) {
            $decoded = json_decode($response, true);
            $response = $decoded ?: [];
        }

        if (!is_array($response)) {
            return null;
        }

        $paths = [
            'id',
            'message.id',
            'messages.0.id',
            'data.id',
            'data.message.id',
            'response.id',
            'sent.id',
            'result.id',
        ];

        foreach ($paths as $path) {
            $value = data_get($response, $path);

            if ($value) {
                return (string) $value;
            }
        }

        return null;
    }


    //Descarga de archivos
    private function downloadAndUploadToFTP($fileUrl, $fileName, $mediaType, $session)
    {
        try {
            // Descargar el archivo desde la URL de UltraMsg
            $fileContents = file_get_contents($fileUrl);
            if (!$fileContents)
                return null;

            // Determinar la extensión del archivo
            $extensions = [
                'image' => 'jpg',
                'video' => 'mp4',
                'audio' => 'mp3',
                'document' => 'pdf'
            ];
            $extension = $extensions[$mediaType] ?? 'dat';

            // Generar un nombre de archivo único
            $fileNameToSave = time() . '.' . pathinfo($fileName, PATHINFO_FILENAME) . '.' . $extension;

            // Subir a FTP ( de cuentas o contratos)
            Storage::disk($session->documentTemporal)->put($fileNameToSave, $fileContents);

            return $fileNameToSave;
        } catch (\Exception $e) {
            Log::error("Error al subir el archivo al FTP: " . $e->getMessage());
            return null;
        }
    }


    //Resumen contacto
    private function resumeContact($session, $from)
    {
        $data = $session->data;

        $session->update(['step' => 'contact_confirm']);

        $msg = "✅ *Resumen del contacto:*\n\n" .
            "📌 *Nombre:* " . (isset($data['contact']['name']) ? ($data['contact']['name']['first'] . ' ' . $data['contact']['name']['second']) : "No especificado") . "\n" .
            "📌 *Apellidos:* " . (isset($data['contact']['surname']) ? ($data['contact']['surname']['first'] . ' ' . $data['contact']['surname']['second']) : "No especificado") . "\n" .
            "📌 *DNI:* " . ($data['contact']['dni'] ?? "No especificado") . "\n" .
            "📌 *Teléfono:* " . ($data['contact']['phone'] ?? "No especificado") . "\n" .
            "📌 *Email:* " . ($data['contact']['email'] ?? "No especificado") . "\n" .
            "📌 *Nombre empresa:* " . ($data['contact']['companyName'] ?? "No especificado") . "\n" .
            "📌 *Cargo en empresa:* " . ($data['contact']['position'] ?? "No especificado") . "\n" .
            "📌 *Dirección:* " . ($data['contact']['address'] ?? "No especificado") . "\n" .
            "📌 *Comunidad autónoma:* " . ($data['contact']['community'] ?? "No especificado") . "\n" .
            "📌 *Provincia:* " . ($data['contact']['province'] ?? "No especificado") . "\n" .
            "📌 *Localidad:* " . ($data['contact']['locality'] ?? "No especificado") . "\n" .
            "📌 *Código postal:* " . ($data['contact']['postal'] ?? "No especificado");

        $options = [
            ['id' => '1', 'title' => ($session->type === 'contact') ? 'Guardar' : 'Continuar',],
            ['id' => '2', 'title' => 'Editar',],
        ];

        return $this->sendOptions($from, $msg, $options);
    }

    //Resumen cuenta
    private function resumeAcc($session, $from)
    {
        $data = $session->data;

        $session->update(['step' => 'account_confirm']);

        $msg = "✅ *Resumen de la Cuenta:*\n\n" .
            "📌 *Nombre:* " . ($data['name'] ?? "No especificado") . "\n" .
            "📌 *CIF:* " . ($data['cif'] ?? "No especificado") . "\n" .
            "📌 *Teléfono:* " . ($data['phone'] ?? "No especificado") . "\n" .
            "📌 *Email:* " . ($data['email'] ?? "No especificado") . "\n" .
            "📌 *Dirección:* " . ($data['address'] ?? "No especificado") . "\n" .
            "📌 *Comunidad autónoma:* " . ($data['community'] ?? "No especificado") . "\n" .
            "📌 *Provincia:* " . ($data['province'] ?? "No especificado") . "\n" .
            "📌 *Población:* " . ($data['locality'] ?? "No especificado") . "\n" .
            "📌 *Código postal:* " . ($data['postal'] ?? "No especificado") . "\n" .
            "📌 *Documentos Adjuntos:* " . (isset($data['documents']) ? count($data['documents']) : 0);

        $hasOpportunity = isset($data['opportunity']) && $data['opportunity'] !== '';

        if ($hasOpportunity) {
            $opp = Opportunity::where('_id', $data['opportunity'])->first();

            $msg .= "\n📌 *Oportunidad relacionada:* " . ($opp['name'] ?? 'No especificado');
        }

        $options = [
            ['id' => '1', 'title' => ($session->type === 'account') ? 'Guardar' : 'Continuar',],
            ['id' => '2', 'title' => 'Editar',],
            ['id' => '3', 'title' => $hasOpportunity ? 'Cambiar oportunidad' : 'Relacionar con oportunidad',],
        ];

        return $this->sendOptions(
            $from,
            $msg . "\n\n¿Qué quieres hacer?",
            $options
        );
    }

    //Resumen cuenta
    private function resumeContract($session, $from, $productTypes)
    {
        try {
            $data = $session->data;
            $session->update(['step' => 'contract_confirm']);

            $contractDetails = '';

            if ($data['contract']['productType'] === 'cl' || $data['contract']['productType'] === 'cg') {
                $contractDetails .= "📌 *Comercializadora:* " . ($data['contract']['marketer']['name'] ?? "No especificado") . "\n";
                $contractDetails .= "📌 *Tarifa:* " . ($data['contract']['fee']['name'] ?? "No especificado") . "\n";
            }

            $contractCUPS = ($data['contract']['productType'] === 'cl' || $data['contract']['productType'] === 'cg')
                ? "📌 *CUPS:* " . ($data['contract']['CUPS'] ?? "No especificado") . "\n"
                : '';

            $productType = collect($productTypes)->firstWhere('code', $data['contract']['productType'])['title'] ?? "No especificado";

            $msg = "✅ *Resumen del contrato:*\n\n" .
                "📌 *Nombre:* " . ($data['contract']['name'] ?? "No especificado") . "\n" .
                "📌 *Dirección:* " . ($data['contract']['address'] ?? "No especificado") . "\n" .
                "📌 *Población:* " . ($data['contract']['town'] ?? "No especificado") . "\n" .
                "📌 *Provincia:* " . ($data['contract']['province'] ?? "No especificado") . "\n" .
                "📌 *Código postal:* " . ($data['contract']['postal'] ?? "No especificado") . "\n" .
                "📌 *IBAN:* " . ($data['contract']['IBAN'] ?? "No especificado") . "\n" .
                "📌 *Tipo de producto:* " . $productType . "\n" .
                $contractDetails .
                "📌 *Producto:* " . ($data['contract']['product'] ?? "No especificado") . "\n" .
                $contractCUPS .
                "📌 *Documentos Adjuntos:* " . (isset($data['contract']['documents']) ? count($data['contract']['documents']) : 0);

            $options = [
                ['id' => '1', 'title' => 'Guardar'],
                ['id' => '2', 'title' => 'Editar'],
            ];

            return $this->sendOptions(
                $from,
                $msg . "\n\n¿Qué quieres hacer?",
                $options
            );
        } catch (\Throwable $e) {
            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'resumeContract';

            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }

    //Resumen oportunidad
    private function resumeOpportunity($session, $from, $productTypes, $user)
    {
        try {
            $data = $session->data;

            $oppResumeMessage = "✅ *Resumen de la oportunidad:*\n\n" .
                "📌 *Nombre:* " . ($data['opportunity']['name'] ?? "No especificado") . "\n" .
                "📌 *CIF/NIF:* " . ($data['opportunity']['cif_nif'] ?? "No especificado") . "\n" .
                "📌 *Móvil:* " . ($data['opportunity']['phone'] ?? "No especificado") . "\n" .
                "📌 *Email:* " . ($data['opportunity']['email'] ?? "No especificado") . "\n" .
                "📌 *Comunidad oportunidad:* " . ($data['opportunity']['community'] ?? "No especificado") . "\n" .
                "📌 *Provincia oportunidad:* " . ($data['opportunity']['province'] ?? "No especificado") . "\n" .
                "📌 *Localidad oportunidad:* " . ($data['opportunity']['locality'] ?? "No especificado") . "\n" .
                "📌 *Dirección oportunidad:* " . ($data['opportunity']['address'] ?? "No especificado") . "\n" .
                "📌 *Código postal oportunidad:* " . ($data['opportunity']['postal'] ?? "No especificado") . "\n\n";

            if (isset($data['opportunity']['order'])) {
                $contractData = '';
                $CUPSData = '';

                $productType = is_array($data['opportunity']['order']['productType'])
                    ? ($data['opportunity']['order']['productType']['title'] ?? '')
                    : (collect($productTypes)->firstWhere('code', $data['opportunity']['order']['productType'])['title'] ?? '');

                if ($productType === 'Contrato de luz' || $productType === 'Contrato de gas') {
                    $marketerName = is_array($data['opportunity']['order']['marketer'] ?? null)
                        ? ($data['opportunity']['order']['marketer']['name'] ?? '')
                        : ($data['opportunity']['order']['marketer'] ?? '');

                    $feeName = is_array($data['opportunity']['order']['fee'] ?? null)
                        ? ($data['opportunity']['order']['fee']['name'] ?? '')
                        : ($data['opportunity']['order']['fee'] ?? '');

                    $formattedFee = stripos($feeName, 'Tarifa') === 0 ? $feeName : 'Tarifa ' . $feeName;

                    $contractData = "📌 *Comercializadora:* " . $marketerName . "\n" .
                        "📌 *Tarifa:* " . $formattedFee . "\n";

                    $CUPSData = "📌 *CUPS:* " . (
                            $data['opportunity']['order']['CUPS']
                            ?? $data['opportunity']['order']['cups']
                            ?? $data['pdfData']['cups']
                            ?? 'No especificado'
                        ) . "\n";
                }

                if ($productType) {
                    $oppResumeMessage .= "*📄DATOS DEL CONTRATO*\n\n" .
                        "📌 *Tipo de producto:* " . $productType . "\n" .
                        $contractData .
                        "📌 *Producto:* " . ($data['opportunity']['order']['product'] ?? "No especificado") . "\n" .
                        $CUPSData .
                        "📌 *Provincia contrato:* " . ($data['opportunity']['order']['province'] ?? "No especificado") . "\n" .
                        "📌 *Localidad contrato:* " . ($data['opportunity']['order']['locality'] ?? "No especificado") . "\n" .
                        "📌 *Dirección contrato:* " . ($data['opportunity']['order']['address'] ?? "No especificado") . "\n" .
                        "📌 *Código postal contrato:* " . ($data['opportunity']['order']['postal'] ?? "No especificado") . "\n\n";
                }
            }

            $hasContact = isset($data['opportunity']['contact']) && $data['opportunity']['contact'] !== '';

            if ($hasContact) {
                $contactSelected = Contact::where('usersIds', $user->_id)
                    ->where('_id', $data['opportunity']['contact'])->first()->toArray();

                if ($contactSelected) {
                    $oppResumeMessage .= "📌 *Contacto relacionado:* " .
                        ($contactSelected['name']['first'] ?? '') . ' ' .
                        ($contactSelected['surname']['first'] ?? '') . "\n";
                }
            }

            $options = [
                ['id' => '1', 'title' => 'Guardar'],
                ['id' => '2', 'title' => 'Editar'],
                ['id' => '3', 'title' => $hasContact ? 'Editar el contacto' : 'Relacionar con contacto',],
            ];

            return $this->sendOptions(
                $from,
                $oppResumeMessage . "\n¿Qué quieres hacer?",
                $options
            );
        } catch (\Throwable $e) {
            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'resumeOpp';

            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }


    //Sacar listado de usuarios
    function getUserList($from, $userLogged)
    {
        try {

            $userList = UserHelper::hierarchy($userLogged->_id);
            array_unshift($userList, $userLogged->toArray());

            //Aqui lo que hago es solo dejar el array con los ids de los usuarios
            $userList = array_reduce($userList, function ($carry, $user) use ($userLogged) {
                $id = $user['_id'];
                if (!isset($carry[$id]) && $id !== $userLogged->_id)
                    $carry[$id] = $user;
                return $carry;
            }, []);

            $userList = array_values($userList);

            return $userList;
        } catch (\Throwable $e) {

            $errorMessage = "❌ *Error al cargar listado de usuarios*\n";
            $errorMessage .= $e->getMessage() . '  ';
            $errorMessage .= $e->getFile() . '  ';
            $errorMessage .= $e->getLine();

            // Limitar longitud si lo envías por WhatsApp
            return $this->sendMessage($from, Str::limit($errorMessage, 3900));
        }
    }

    //Sacar contactos para relacionar
    function getRelatedContacts($from, $user)
    {
        $userList = $this->getUserList($from, $user);

        $request = new Request([
            'userList' => $userList
        ]);

        $response = OportunityController::getRelatedContacts($user, $request);
        $contacts = $response->getData(true)['contacts'];

        return $contacts;
    }

    //Sacar contactos para relacionar
    function getRelatedOpportunities($from, $user)
    {
        $userList = $this->getUserList($from, $user);


        $request = new Request([
            'userList' => json_encode($userList)
        ]);

        $response = OportunityController::indexWithoutPagination($user['_id'], $request);


        $opportunities = $response->getData(true)['opportunities'];

        return $opportunities;
    }



    private function handleContractEdit($session, $from, $editField, $user, $productTypes, $basicProducts)
    {

        try {

            $data = $session->data;
            $data['stopOnFinishSelects'] = true;

            $session->update(['data' => $data]);

            if ($session->type === 'comparative' || $session->type === 'opportunity') {
                $temporalEditField = $editField;
                $editField = explode('.', $editField)[1];
            }


            // Si se cambia el tipo de producto, debe cambiar todo lo que hay debajo
            if ($editField === 'productType') {
                $session->update(['step' => 'contract_productType']);

                $options = collect($productTypes)
                    ->map(fn($product, $index) => [
                        'id' => (string) ($index + 1),
                        'title' => $product['title'],
                    ])
                    ->values()
                    ->toArray();

                return $this->sendOptions($from, "Selecciona el tipo de producto:", $options);
            }

            // Si se cambia la comercializadora, hay que cambiar tarifa y producto
            if ($editField === 'marketer') {
                $session->update(['step' => 'contract_marketer']);

                $marketers = (new MarketerController())->getMarketersBySubdomain($user);

                $options = collect($marketers)
                    ->map(fn($marketer, $index) => [
                        'id' => (string) ($index + 1),
                        'title' => $marketer['name'],
                    ])
                    ->values()
                    ->toArray();

                return $this->sendOptions($from, "Selecciona la comercializadora:", $options);
            }

            // Si se cambia la tarifa, hay que cambiar el producto
            if ($editField === 'fee') {

                // Saco el contrato
                if ($session->type === 'opportunity' || $session->type === 'comparative')
                    $contract = $data['opportunity']['order'];
                else
                    $contract = $data['contract'];

                $marketer = Marketer::where('_id', $contract['marketer']['id'])->first();

                $productTypeCode = is_array($contract['productType']) ? ($contract['productType']['code'] ?? null) : $contract['productType'];

                $fees = $marketer['fees'][$productTypeCode === 'cl' ? 'electricity' : 'gas'];

                $options = collect($fees)
                    ->map(fn($fee, $index) => [
                        'id' => (string) ($index + 1),
                        'title' => $fee['name'],
                    ])
                    ->values()
                    ->toArray();

                $session->update(['step' => 'contract_fee']);

                return $this->sendOptions($from, "Selecciona la tarifa:", $options);
            }

            // Si se cambia el producto, mostrar opciones de producto
            if ($editField === 'product') {

                // Saco el contrato
                if ($session->type === 'opportunity' || $session->type === 'comparative')
                    $contract = $data['opportunity']['order'];
                else
                    $contract = $data['contract'];

                $productTypeCode = is_array($contract['productType']) ? ($contract['productType']['code'] ?? null) : $contract['productType'];

                if ($productTypeCode === 'cl' || $productTypeCode === 'cg') {
                    $marketer = Marketer::where('_id', $contract['marketer']['id'])->first();
                    $products = $marketer['products'][$productTypeCode === 'cl' ? 'electricity' : 'gas'];

                    $filteredProducts = array_filter($products, function ($product) use ($data, $contract) {
                        return isset($product['fees']) &&
                            is_array($product['fees']) &&
                            in_array($contract['fee']['id'], $this->extractIds($product['fees'])) &&
                            empty($product['archived']);
                    });

                    $filteredProducts = array_values($filteredProducts);

                    $options = collect($filteredProducts)
                        ->map(fn($product, $index) => [
                            'id' => (string) ($index + 1),
                            'title' => $product['name'],
                        ])
                        ->values()
                        ->toArray();

                    $session->update(['step' => 'contract_product']);

                    return $this->sendOptions($from, "Selecciona el producto:", $options);
                } else {
                    $options = collect($basicProducts)
                        ->map(fn($product, $index) => [
                            'id' => (string) ($index + 1),
                            'title' => $product,
                        ])
                        ->values()
                        ->toArray();

                    $session->update(['step' => 'contract_product']);

                    return $this->sendOptions($from, "Selecciona el producto:", $options);
                }
            }
            if ($session->type === 'comparative' || $session->type === 'opportunity')
                $editField = $temporalEditField;


            // Para otros campos, pedir el nuevo valor
            if ($session->type === 'opportunity' || $session->type === 'comparative')
                $session->update(['step' => 'opportunity_edit_value', 'data' => array_merge($data, ['edit_field' => $editField])]);
            else
                $session->update(['step' => 'contract_edit_value', 'data' => array_merge($data, ['edit_field' => $editField])]);

            return $this->sendMessage($from, "Introduce el nuevo valor:");
        } catch (\Throwable $e) {
            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'handleContractEdit';


            // Limitar longitud si lo envías por WhatsApp
            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }

    function normalizeString($string)
    {
        $unwanted = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'à' => 'a',
            'è' => 'e',
            'ì' => 'i',
            'ò' => 'o',
            'ù' => 'u',
            'ä' => 'a',
            'ë' => 'e',
            'ï' => 'i',
            'ö' => 'o',
            'ü' => 'u',
            'â' => 'a',
            'ê' => 'e',
            'î' => 'i',
            'ô' => 'o',
            'û' => 'u',
            'ã' => 'a',
            'õ' => 'o',
            'ñ' => 'n',
            'ç' => 'c'
        ];
        return strtr($string, $unwanted);
    }

    private function formatSpanishNumber($phone)
    {
        $phone = preg_replace('/^34/', '', $phone);

        return substr($phone, 0, 3) . ' ' .
            substr($phone, 3, 2) . ' ' .
            substr($phone, 5, 2) . ' ' .
            substr($phone, 7, 2);
    }

    //Pedir datos obligatorios faltantes contrato y cuenta
    private function checkMissingObligatoryFields(&$session, $from, $user)
    {
        $data = $session->data;

        $hasCUPS = !empty($data['contract']['CUPS'] ?? null);

        $requiredFields = ['acc.name', 'acc.cif', 'acc.phone', 'acc.email', 'acc.community', 'acc.province', 'acc.locality', 'acc.address', 'acc.postal'];

        if ($hasCUPS) {
            $requiredFields = array_merge($requiredFields, [
                'order.name',
                'order.direc',
                'order.town',
                'order.province',
                'order.zip',
                'order.IBAN'
            ]);
        }
        $fieldTitles = [
            'acc.name' => 'Nombre de la cuenta',
            'acc.cif' => 'CIF o NIF',
            'acc.phone' => 'Teléfono cuenta',
            'acc.email' => 'Correo electrónico cuenta',
            'acc.community' => 'Comunidad cuenta',
            'acc.province' => 'Provincia cuenta',
            'acc.locality' => 'Localidad cuenta',
            'acc.address' => 'Dirección cuenta',
            'acc.postal' => 'Código postal cuenta',
            'order.name' => 'Nombre del contrato',
            'order.direc' => 'Dirección de suministro',
            'order.town' => 'Localidad contrato',
            'order.province' => 'Provincia contrato',
            'order.zip' => 'Código postal contrato',
            'order.IBAN' => 'IBAN'
        ];


        foreach ($requiredFields as $field) {
            [$group, $key] = explode('.', $field);

            $value = null;

            if ($group === 'acc') {
                $value = $data[$key] ?? null;
            } elseif ($group === 'order') {
                // Mapeo específico para contract
                switch ($key) {
                    case 'direc':
                        $value = $data['contract']['address'] ?? null;
                        break;
                    case 'zip':
                        $value = $data['contract']['postal'] ?? null;
                        break;
                    default:
                        $value = $data['contract'][$key] ?? null;
                        break;
                }
            }

            // Si el valor no existe o está vacío
            if (empty($value)) {
                // Guardamos cuál campo está pendiente
                $data['pendingField'] = $field;

                $session->update(['data' => $data, 'step' => 'required_field_value']);
                return $this->sendMessage($from, "Introduce el valor para el campo *$fieldTitles[$field]*:");
            }
        }

        // Todos los campos están completos
        unset($data['pendingField']);

        //Muestro resumen cuenta
        $this->resumeAcc($session, $from);
    }


    //Guardar contacto
    private function saveContact($session, $from, $user)
    {

        try {
            $data = $session->data;

            Contact::create([
                'name' => [
                    'first' => $data['contact']['name']['first'] ?? '',
                    'second' => $data['contact']['name']['second'] ?? ''
                ],
                'surname' => [
                    'first' => $data['contact']['surname']['first'] ?? '',
                    'second' => $data['contact']['surname']['second'] ?? ''
                ],
                'email' => $data['contact']['email'] ?? '',
                'phone' => $data['contact']['phone'] ?? '',
                'DNI' => $data['contact']['dni'] ?? '',
                //'nickname' => '',
                'companyName' => $data['contact']['companyName'] ?? '',
                'accounts' => [],
                'position' => $data['contact']['position'] ?? '',
                'billingInfo' => [
                    'community' => $data['contact']['community'] ?? '',
                    'province' => $data['contact']['province'] ?? '',
                    'locality' => $data['contact']['locality'] ?? '',
                    'address' => $data['contact']['address'] ?? '',
                    'postal' => $data['contact']['postal'] ?? ''
                ],
                'customFields' => [],
                'profileImage' => null,
                'usersIds' => [$user['_id']],
                'createdBy' => $user['_id'],
                'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
            ]);


            //Mensaje final y borro datos de sesión
            $session->delete();
            return $this->sendMessage($from, '✅ Contacto creado correctamente.');
        } catch (\Throwable $e) {
            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'saveContact';


            // Limitar longitud si lo envías por WhatsApp
            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }

    //Guardar cuenta / contrato

    private function saveAcc($session, $from, $user)
    {

        try {

            $data = $session->data;

            //Creo el contrato
            $order = [];

            if (isset($data['contract'])) {

                //📜Agrego documentos de contrato
                $contractDocs = [];
                if (isset($data['contract']['documents']))
                    foreach ($data['contract']['documents'] as $contractDocument) {

                        array_push($contractDocs, [
                            'title' => $contractDocument['name'],
                            'defaultTitle' => $contractDocument['name'],
                            'value' => $contractDocument['file'],
                            'errors' => (object) []
                        ]);
                    }

                //Variables temporales
                $orderToModify = [
                    'consumption' => '',
                    'potency' => '',
                    'salesCommision' => '',
                    'asercordCommision' => '',
                ];


                //Si es de tipo cl o cg hago consulta a SIPS para sacar datos consumo, potencia y comisiones
                if ($data['contract']['productType'] === 'cl' || $data['contract']['productType'] === 'cg') {

                    //Creo instancia de OrderControler para sacar datos del CUPS
                    $cupsData = [
                        'CUPS' => $data['contract']['CUPS']
                    ];

                    $request = new Request($cupsData);
                    $apiData = (new OrderController())->getAPIConsumption($request);


                    // Si la respuesta no es 200, salir del if sin hacer nada
                    if ($apiData->status() === 200) {

                        $apiData = $apiData->getOriginalContent();

                        //Consumo
                        $orderToModify['consumption'] = floor(
                            $apiData['consumptionData']['consumption'][0] +
                            $apiData['consumptionData']['consumption'][1] +
                            $apiData['consumptionData']['consumption'][2] +
                            $apiData['consumptionData']['consumption'][3] +
                            $apiData['consumptionData']['consumption'][4] +
                            $apiData['consumptionData']['consumption'][5]
                        );

                        //Potencia
                        $orderToModify['potency'] = max(...$apiData['consumptionData']['hiredPotency']);
                        //$orderToModify['potency'] = round($orderToModify['potency'], 3);
                        //$orderToModify['potency'] = rtrim(rtrim((string)$orderToModify['potency'], '0'), '.');


                        //Calculo comisiones
                        $this->checkOrderCommission($from, $session, $orderToModify, $user);
                    }
                }


                //Contrato
                $order = [
                    'name' => $data['contract']['name'],
                    'direc' => $data['contract']['address'],
                    'zip' => $data['contract']['postal'],
                    'town' => $data['contract']['town'],
                    'province' => $data['contract']['province'],
                    'source' => '',
                    'observations' => '',
                    'incidence' => '',
                    'transferDate' => date('d/m/y'),
                    'processingDate' => '',
                    'activationDate' => '',
                    'lowDate' => '',
                    'liquidationStatus' => 'nl',
                    'productType' => $data['contract']['productType'],
                    'marketer' => $data['contract']['marketer']['name'] ?? '',
                    'fee' => $data['contract']['fee']['name'] ?? '',
                    'product' => $data['contract']['product'],
                    'salesCommision' => $orderToModify['salesCommision'] ?? '',
                    'asercordCommision' => $orderToModify['asercordCommision'] ?? '',
                    'CUPS' => $data['contract']['CUPS'] ?? '',
                    'consumption' => $orderToModify['consumption'],
                    'hiredPotency' => $orderToModify['potency'],
                    'IBAN' => $data['contract']['IBAN'],
                    'docs' => $contractDocs,
                    'owner' => $user->firstName,
                    'statuses' => [
                        (object) [
                            'code' => "p",
                            'date' => date('Y-m-d H:i:s')
                        ]
                    ],
                    'lastUpdate' => date('Y-m-d H:i:s'),
                    '_id' => new ObjectId(),
                ];

                //Si es metido desde witro se añade el telefono del usuario que lo ha creado para tener un registro
                if ($session->app === 'witro' || $user->firstName === 'Witro') {
                    $order['whatsPhone'] = substr($from, -9);
                }
            }




            //📜Documentos cuenta
            $accDocs = [];
            if (isset($data['documents']))
                foreach ($data['documents'] as $accDocument) {

                    if (!isset($accDocument['fromOpp'])){
                        array_push($accDocs, [
                            'title' => $accDocument['name'],
                            'type' => 'image',
                            'fileType' => 'application',
                            'value' => $accDocument['file'],
                        ]);
                    }else{
                        //Quito el label temporal
                        unset($accDocument['fromOpp']);

                        //Meto el documento
                        array_push($accDocs, $accDocument);

                        //Compruebo si existe en oportunidades y lo meto en cuentas
                        $filePath = $accDocument['value'];

                        if (Storage::disk('opportunity')->exists($filePath)) {
                            // Leemos el contenido
                            $fileContent = Storage::disk('opportunity')->get($filePath);

                            // Guardamos en el disco "account" (puedes mantener misma ruta o modificarla)
                            Storage::disk('account')->put($filePath, $fileContent);

                            // Actualizamos el valor para apuntar al nuevo disco
                            $accDocument['value'] = $filePath;
                        }

                    }
                }



            //Compruebo si la se va a guardar contrato en cuenta existente o va a ser creación nueva
            if (isset($data['selected_account']) && isset($data['contract'])) {

                $account = Account::where('_id', $data['selected_account'])->first();

                //Si ya existe la cuenta solo le meto contratos
                $order['account'] = (string) $data['selected_account'];
                $order['usersIds'] = $account->usersIds;
                $order['createdBy'] = $account->usersIds[0];

                $orderModel = new Order($order);
                $orderModel->save();

                //Log creación contrato
                AuditLogService::createOrDeleteOrder($order, $user, 'create');

            } else {
                //Se guarda
                $account = Account::create([
                    'name' => $data['name'],
                    'accType' => '',
                    'sector' => '',
                    'CIF' => $data['cif'],
                    'origin' => '',
                    'phone' => $data['phone'],
                    'landLinePhone' => '',
                    'website' => '',
                    'email' => $data['email'],
                    'observations' => '',
                    'principalAcc' => '',
                    'billingInfo' => (object) [
                        'community' => $data['community'],
                        'province' => $data['province'],
                        'locality' => $data['locality'],
                        'address' => $data['address'],
                        'zipCode' => $data['postal'],
                    ],
                    'customFields' => $accDocs,
                    'profileImage' => null,
                    'opportunity' => $data['opportunity'] ?? '',
                    'usersIds' => [$user->_id],
                    'createdBy' => $user->_id,
                    'createdAt' => Carbon::now()->format('Y-m-d H:i:s')

                ]);

                //Log creación cuenta
                AuditLogService::createOrDeleteAccount($account, $user, 'create');

                if (isset($data['contract'])) {
                    $order['account'] = $account->_id;
                    $order['usersIds'] = $account->usersIds;

                    // Guarda el contrato
                    $orderModel = new Order($order);
                    $orderModel->save();

                    //Log creación contrato
                    AuditLogService::createOrDeleteOrder($order, $user, 'create');
                }
            }

            //Mensaje final y borro datos de sesión
            $session->delete();
            return $this->sendMessage($from, "✅ Creada con éxito.");
        } catch (\Throwable $e) {
            // Capturar cualquier error inesperado y mostrarlo por WhatsApp
            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'saveAcc';


            // Limitar longitud si lo envías por WhatsApp
            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }

    //Guardar oportunidad
    private function saveOpp($session, $from, $user)
    {

        try {

            $data = $session->data;
            //$opp = Opportunity::where('CIF', $data['opportunity']['cif_nif'])->where('createdBy', $user['_id'])->first();

            $dynamic = [
                'name' => $data['opportunity']['name'],
                'phone' => $data['opportunity']['phone'],
                'email' => $data['opportunity']['email'],
                'billingInfo' => [
                    'community' => $data['opportunity']['community'],
                    'province' => $data['opportunity']['province'],
                    'locality' => $data['opportunity']['locality'],
                    'address' => $data['opportunity']['address'],
                    'postal' => $data['opportunity']['postal'],
                ],
                'contact' => [
                    'value' => $data['opportunity']['contact'] ?? '',
                    'isFromContacts' => true,
                ]
            ];

            if (isset($data['opportunity']['order'])) {

                $productTypeCode = is_array($data['opportunity']['order']['productType']) ? ($data['opportunity']['order']['productType']['code'] ?? '') : $data['opportunity']['order']['productType'];
                $marketerName = is_array($data['opportunity']['order']['marketer']) ? ($data['opportunity']['order']['marketer']['name'] ?? '') : $data['opportunity']['order']['marketer'];
                $feeName = is_array($data['opportunity']['order']['fee']) ? ($data['opportunity']['order']['fee']['name'] ?? '') : $data['opportunity']['order']['fee'];
                $feeName = $feeName && stripos($feeName, 'Tarifa') !== 0 ? 'Tarifa ' . $feeName : $feeName;
                $CUPS = (isset($data['opportunity']['order']['CUPS']) ? $data['opportunity']['order']['CUPS'] : (isset($data['opportunity']['order']['cups']) ? $data['opportunity']['order']['cups'] : (isset($data['pdfData']['cups']) ? $data['pdfData']['cups'] : '')));

                $dynamic['order'] = [
                    'productType' => $productTypeCode,
                    'direc' => $data['opportunity']['order']['address'] ?? '',
                    'zip' => $data['opportunity']['order']['postal'] ?? '',
                    'town' => $data['opportunity']['order']['locality'] ?? '',
                    'province' => $data['opportunity']['order']['province'] ?? '',
                    'marketer' => $marketerName,
                    'fee' => $feeName,
                    'product' => $data['opportunity']['order']['product'] ?? '',
                    'CUPS' => $CUPS,
                ];
            }
            else {
                $dynamic['order'] = [
                    'productType' => 'sp',
                    'marketer' => 'Sin Comercializadora',
                    'fee' => 'Sin Tarifa',
                    'product' => '',
                    'CUPS' => '',
                    'direc' => '',
                    'zip' => '',
                    'town' => '',
                    'province' => '',

                ];
            }


            //Si hay docs
            $oppDocs = [];
            if (isset($data['documents'])){
                foreach ($data['documents'] as $oppDocument) {
                    array_push($oppDocs, [
                        'title' => $oppDocument['name'],
                        'type' => 'image',
                        'fileType' => 'application',
                        'value' => $oppDocument['file']
                    ]);
                }
            }



            // Mezclamos los campos dinámicos con los fijos para la creación
            $toCreate = array_merge($dynamic, [
                'CIF' => $data['opportunity']['cif_nif'],
                'landLinePhone' => '',
                'website' => '',
                'sector' => '',
                'source' => '',
                'status' => '',
                'position' => '',
                'observations' => '',
                'customFields' => (isset($data['documents']) ? $oppDocs : []),
                'usersIds' => [(string) $user['_id']],
                'createdBy' => (string) $user['_id'],
                'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            $opportunity = Opportunity::create($toCreate);

            //Guardo log creación oportunidad
            AuditLogService::createOrDeleteOpportunity($opportunity, $user, 'create');


            /*if ($opp) {
                if (isset($data['documents']))
                    $dynamic['customFields'] = $oppDocs;

                // Solo actualizamos los campos dinámicos
                $opp->update($dynamic);

            }
            else {

                // Mezclamos los campos dinámicos con los fijos para la creación
                $toCreate = array_merge($dynamic, [
                    'CIF' => $data['opportunity']['cif_nif'],
                    'landLinePhone' => '',
                    'website' => '',
                    'sector' => '',
                    'source' => '',
                    'status' => '',
                    'position' => '',
                    'observations' => '',
                    'customFields' => (isset($data['documents']) ? $oppDocs : []),
                    'usersIds' => [(string) $user['_id']],
                    'createdBy' => (string) $user['_id'],
                    'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $opportunity = Opportunity::create($toCreate);

                //Guardo log creación oportunidad
                AuditLogService::createOrDeleteOpportunity($opportunity, $user, 'create');
            }*/
            /*Opportunity::create([
                'name' => $data['opportunity']['name'],
                'CIF' => $data['opportunity']['cif_nif'],
                'phone' => $data['opportunity']['phone'],
                'landLinePhone' => '',
                'email' => $data['opportunity']['email'],
                'website' => '',
                'sector' => '',
                'source' => '',
                'status' => '',
                'contact' => [
                    'value'=> '',
                    'isFromContacts'=> false,
                ],
                'position' => '',
                'observations' => '',
                'billingInfo' => [
                    'community' => $data['opportunity']['community'],
                    'province' => $data['opportunity']['province'],
                    'locality' => $data['opportunity']['locality'],
                    'address' => $data['opportunity']['address'],
                    'postal' => $data['opportunity']['postal'],
                ],
                'customFields' => [],
                'order' => [
                    'productType' => $data['opportunity']['order']['productType']['code'],
                    'direc' => $data['opportunity']['order']['address'],
                    'zip' => $data['opportunity']['order']['postal'],
                    'town' => $data['opportunity']['order']['locality'],
                    'province' => $data['opportunity']['order']['province'],
                    'marketer' => $data['opportunity']['order']['marketer'],
                    'fee' => "Tarifa " . $data['opportunity']['order']['fee'],
                    'product' => $data['opportunity']['order']['product'],
                    'CUPS' => $data['opportunity']['order']['cups'],
                ],
                'usersIds' => [$user['_id']],
                'createdBy' => $user['_id'],
                'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
            ]);*/


            //Creación de PDF


            //Mensaje final y borro datos de sesión
            $session->delete();
            return $this->sendMessage($from, "✅ Oportunidad creada correctamente.");
            //return $this->sendMessage($from, $opp ? '✅ Oportunidad actualizada correctamente.' : "✅ Oportunidad creada correctamente.");
        } catch (\Throwable $e) {
            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'saveOpp';


            // Limitar longitud si lo envías por WhatsApp
            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }


    private function checkOrderCommission($from, $session, &$orderToModify, $user)
    {

        $data = $session->data;

        // 1. Obtener la comercializadora
        $marketerInfo = Marketer::where('_id', $data['contract']['marketer']['id'])->first();
        if (!$marketerInfo)
            return $this->clearCommissions($orderToModify);


        // 2. Obtener tipo de producto
        $productTypeKey = $data['contract']['productType'] === 'cl' ? 'electricity' : 'gas';



        // 3. Obtener producto
        $productInfo = collect($marketerInfo['products'][$productTypeKey])->firstWhere('name', $data['contract']['product']);
        if (!$productInfo)
            return $this->sendMessage($from, 'check product');


        // 4. Obtener tarifa
        $feeName = $data['contract']['fee'];
        $feeFromMarketer = collect($marketerInfo['fees'][$productTypeKey])->firstWhere('name', $feeName['name']);
        $feeInfo = collect($productInfo['fees'])->firstWhere(fn($fee) => $this->extractId($fee['id']) === ($this->extractId($feeFromMarketer['id']) ?? null));
        if (!$feeInfo)
            return $this->sendMessage($from, 'check fee');


        //Diferencio entre indexados y no indexados
        // 🔁 Ahora según comercializadora
        if ($marketerInfo['name'] === 'IberEléctrica') {
            //hacer
            //return $this->handleIberCommission($orderToModify, $consumptionData);
            return null;
        } elseif ($marketerInfo['name'] === 'VM') {
            return null;
        } else {
            return $this->handleModularCommission($from, $session, $orderToModify, $marketerInfo, $productInfo, $feeInfo, $user);
        }
    }


    private function handleModularCommission($from, $session, &$orderToModify, $marketerInfo, $productInfo, $feeInfo, $user)
    {

        $data = $session->data;

        if (!isset($feeInfo['consumptionBreakdown'])) {
            return $this->getCommission([
                'type' => 'basic',
                'productInfo' => $productInfo,
                'feeInfo' => $feeInfo,
                'marketerInfo' => $marketerInfo,
                'orderToModify' => &$orderToModify
            ], $from, $user);
        }


        $interval = null;
        $consumption = (int) $orderToModify['consumption'] ?? 0;
        $potency = (int) $orderToModify['potency'] ?? 0;

        switch ($feeInfo['commissionType']) {
            case 'c':
                $interval = collect($feeInfo['consumptionBreakdown'])->first(function ($int) use ($consumption) {
                    return $consumption >= $int['con1'] &&
                        (($int['con2'] === '>' || $consumption <= $int['con2']));
                });
                break;

            case 'p':
                $interval = collect($feeInfo['consumptionBreakdown'])->first(function ($int) use ($potency) {
                    return $potency >= $int['pot1'] &&
                        (($int['pot2'] === '>' || $potency <= $int['pot2']));
                });
                break;

            case 'cyp':
                $interval = collect($feeInfo['consumptionBreakdown'])->first(function ($int) use ($consumption, $potency) {
                    return $potency >= $int['pot1'] &&
                        ($int['pot2'] === '>' || $potency <= $int['pot2']) &&
                        $consumption >= $int['con1'] &&
                        ($int['con2'] === '>' || $consumption <= $int['con2']);
                });
                break;
        }

        if ($interval) {
            return $this->getCommission([
                'type' => 'breakdown',
                'interval' => $interval,
                'marketerInfo' => $marketerInfo,
                'orderToModify' => &$orderToModify
            ], $from, $user);
        }


        return $this->clearCommissions($orderToModify);
    }


    private function getCommission($data, $from, $user)
    {
        $order = &$data['orderToModify'];

        $isSubdominio = in_array($user->_id, [
                '65d47559aa2d0448c308e252',
                '65d48ac808c6cf0254066c42',
                '6617a7ffc4f2475a7a010d32',
                '67a4b5f728300393f408ff32'
            ]) || $user->label === 'Usuario subdominio';


        //Comisión subdominio
        $order['asercordCommision'] = $data['type'] === 'basic'
            ? $data['feeInfo']['consumptionBasic']['comAs']
            : $data['interval']['comAs'];


        //Comisión usuario según si el usuario las tiene como rango, porcentaje o fijo
        if (!$isSubdominio){
            $commissionUser = $user->commissions[$data['marketerInfo']['_id']]['value'] ?? 1;

            //si es rango
            if ($user->commissions[$data['marketerInfo']['_id']]['type'] === 'range') {
                $key = 'com' . $commissionUser;

                $order['salesCommision'] = $data['type'] === 'basic'
                    ? $data['feeInfo']['consumptionBasic'][$key]
                    : $data['interval'][$key];

            } elseif ($user->commissions[$data['marketerInfo']['_id']]['type'] === 'percentage') {//si es porcentaje
                $order['salesCommision'] = $order['asercordCommision'] * ($commissionUser / 100);
            } else {
                $order['salesCommision'] = $commissionUser;
            }

        }else
            $order['salesCommision'] = $order['asercordCommision'];

    }


    private function getOCRData(&$data, $mediaUrl, $from, $user)
    {

        try {

            $pdfContent = file_get_contents($mediaUrl);

            //Creo el request para pasarlo al otro controlador
            $requestOCR = new Request([
                'urlPDF' => $pdfContent,
                'userSubdomain' => $this->getUserSubdomainOrEnterprise($user)['_id']
            ]);

            //Llamo a la función de lectura OCR con ChatGPT
            $pdfData = ToolsController::getOCRData($requestOCR);
            $pdfData = json_decode($pdfData, true);

            //⚠️TEMPORAL
            /*$pdfData = [
                "titular" => "ANTONIO RODRIGUEZ CARRILLO",
                "cif_nif" => "27188375Y",
                "direccion_titular" => [
                    "direccion" => "Avda PLAYA SERENA, 97-II, Z URBANIZACION ROQUETAS DE MAR",
                    "poblacion" => "ROQUETAS DE MAR",
                    "codigo_postal" => "04740",
                    "provincia" => "ALMERIA",
                    "comunidad_autonoma" => "Andalucía"
                ],
                "direccion_suministro" => [
                    "direccion" => "Avda PLAYA SERENA, 97-II, Z URBANIZACION ROQUETAS DE MAR",
                    "poblacion" => "ROQUETAS DE MAR",
                    "codigo_postal" => "04740",
                    "provincia" => "ALMERIA",
                    "comunidad_autonoma" => "Andalucía"
                ],
                "periodo_facturacion" => [
                    "fecha_inicio" => "22/12/2024",
                    "fecha_fin" => "25/01/2025"
                ],
                "cups" => "ES0031103409198116MN",
                "tarifa" => "2.0TD",
                "potencias_contratadas" => [
                    "p1" => "4.4",
                    "p2" => "4.4",
                    "p3" => null,
                    "p4" => null,
                    "p5" => null,
                    "p6" => null
                ],
                "precios_potencias" => [
                    "p1" => "0.115102",
                    "p2" => "0.042668",
                    "p3" => null,
                    "p4" => null,
                    "p5" => null,
                    "p6" => null
                ],
                "energia_consumida" => [
                    "p1" => "6.28",
                    "p2" => "6.06",
                    "p3" => "16.56",
                    "p4" => null,
                    "p5" => null,
                    "p6" => null
                ],
                "precios_energia" => [
                    "p1" => "0.142738",
                    "p2" => "0.142738",
                    "p3" => "0.142738",
                    "p4" => null,
                    "p5" => null,
                    "p6" => null
                ]
            ];*/


            //Compruebo los precios de potencia para ajustarlos al periodo
            if (isset($pdfData['precios_potencias'], $pdfData['periodo_precio_potencia'])) {

                switch ($pdfData['periodo_precio_potencia']) {
                    case 'month':
                        foreach ($pdfData['precios_potencias'] as $key => $price) {
                            if (!is_null($price)) {
                                $pdfData['precios_potencias'][$key] = round(
                                    floatval($price) * 12 / 365,
                                    6
                                );
                            }
                        }
                        break;

                    case 'year':
                        foreach ($pdfData['precios_potencias'] as $key => $price) {
                            if (!is_null($price)) {
                                $pdfData['precios_potencias'][$key] = round(
                                    floatval($price) / 365,
                                    6
                                );
                            }
                        }
                        break;

                    case 'day':
                    default:
                        // No se hace nada, ya están en €/kW/día
                        break;
                }
            }


            $data['pdfData'] = $pdfData;


            //FORMATEO LOS DATOS
            $data['pdfFormatted']['cups'] = str_replace(' ', '', $pdfData['cups'] ?? '');


            //Precios
            $data['pdfFormatted']['prices'] = [
                'power' => array_values($pdfData['precios_potencias'] ?? ["", "", "", "", "", ""]),
                'consumption' => array_values($pdfData['precios_energia'] ?? ["", "", "", "", "", ""])
            ];

            //Consumo
            $data['pdfFormatted']['consumptionData'] = [
                'power' => array_map(fn($val) => $val ?? 0, array_values($pdfData['potencias_contratadas'] ?? [])),
                'consumption' => array_values($pdfData['energia_consumida'] ?? [])
            ];



            //Fechas contrato
            $fechaInicio = $pdfData['periodo_facturacion']['fecha_inicio'];
            $fechaFin = $pdfData['periodo_facturacion']['fecha_fin'];

            if (!is_string($fechaInicio) || !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaInicio)) {
                return $this->sendMessage($from, "❌ La fecha de inicio no es válida: \"$fechaInicio\"" . ' | ' . Str::limit(json_encode($pdfData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));
            }
            if (!is_string($fechaFin) || !preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaFin)) {
                return $this->sendMessage($from, "❌ La fecha de fin no es válida: \"$fechaFin\"");
            }


            $startDate = Carbon::createFromFormat('d/m/Y', $fechaInicio);
            $endDate = Carbon::createFromFormat('d/m/Y', $fechaFin);

            // Calcular la diferencia en días entre las fechas
            $daysDifference = $startDate->diffInDays($endDate);

            // Verificar si la diferencia en días no coincide con data.dias_facturacion
            if ($daysDifference !== $data['pdfData']['dias_facturacion']) {
                // Ajustar la fecha de inicio
                $startDate->subDay(); // Restar un día a la fecha de inicio
            }


            $data['pdfFormatted']['dates'] = [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y')
            ];


            $data['pdfData']['periodo_facturacion']['fecha_inicio'] = $startDate->format('d/m/Y');
            $data['pdfData']['periodo_facturacion']['fecha_fin'] = $endDate->format('d/m/Y');


            /*$data['pdfFormatted']['dates'] = [
                'start' => Carbon::createFromFormat('d/m/Y', $pdfData['periodo_facturacion']['fecha_inicio'] ?? null),
                'end' => Carbon::createFromFormat('d/m/Y', $pdfData['periodo_facturacion']['fecha_fin'] ?? null)
            ];*/

            //Total días contrato
            $data['pdfFormatted']['diffInDays'] = $pdfData['dias_facturacion'];

            //Excendentes
            $data['pdfFormatted']['surplus']['amount'] = $pdfData['otros']['kwh_excedentes'] ?? 0;
            $data['pdfFormatted']['surplus']['price'] = $pdfData['otros']['precio_excedentes'] ?? 0;

            //Impuestos
            $data['pdfFormatted']['taxes']['meterDevice'] = $pdfData['otros']['alquiler_equipo_medida'];
            $data['pdfFormatted']['taxes']['iva'] = $pdfData['otros']['iva'];
            $data['pdfFormatted']['taxes']['socialBonus'] = $pdfData['otros']['bono_social'] ?? 0;

            $data['pdfFormatted']['fee'] = $pdfData['tarifa'];


            //Otros conceptos
            if (!empty($pdfData['conceptos_extra'])) {
                foreach ($pdfData['conceptos_extra'] as $name => $value) {
                    $data['pdfFormatted']['otherConcepts'][] = [
                        'name' => $name,
                        'value' => $value,
                        'offers' => false,
                        'electricTax' => false,
                    ];
                }
            }


        } catch (\Throwable $e) {

            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'getOCRData';

            //Log comparativa erronea
            AuditLogService::generateComparative('error', $e->getMessage(), 'bill', 'getOCRData',  null, null, null, $user, $from);

            // Limitar longitud si lo envías por WhatsApp
            return $this->sendMessage('34605581287', Str::limit($mensajeError, 3900));
        }
    }

    private function getWorkPartData(&$data, $mediaUrl, $from)
    {
        try {
            // 1️⃣ Descargar archivo desde la URL de UltraMsg
            $content = @file_get_contents($mediaUrl);
            if ($content === false || strlen($content) < 100) {
                return $this->sendMessage(
                    $from,
                    "❌ No se pudo descargar el archivo o está vacío. Inténtalo de nuevo.\n" .
                    "Si el error persiste, contacta con franperez@segenet.es"
                );
            }

            // 2️⃣ Detectar tipo MIME (PDF o imagen)
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_buffer($finfo, $content);
            finfo_close($finfo);

            // 3️⃣ Codificar en base64
            $base64 = base64_encode($content);

            // 4️⃣ Guardar datos básicos del archivo en $data
            $data['workPart'] = [
                'file' => "data:$mimeType;base64,$base64",
                'name' => 'work_part_' . time() . '.' . ($mimeType === 'application/pdf' ? 'pdf' : 'jpg'),
            ];

            // 5️⃣ Preparar cliente HTTP para OpenAI
            $client = new Client([
                'base_uri' => 'https://api.openai.com/v1/',
                'headers' => [
                    'Authorization' => 'Bearer ' . env("CHATGPT_API_KEY"), // ⚠️ asegúrate de tenerlo en .env
                    'Content-Type' => 'application/json',
                ],
            ]);

            // 6️⃣ Armar la solicitud a GPT-4-turbo con vision (acepta PDFs e imágenes)
            $requestData = [
                "model" => "gpt-4o-mini", // o gpt-4o si lo prefieres
                "messages" => [
                    [
                        "role" => "system",
                        "content" => [
                            [
                                "type" => "text",
                                "text" => "Eres un experto en partes de trabajo (hojas de intervención).
                                Analiza el archivo PDF o imagen que te envío y devuelve un JSON limpio con los
                                campos si puedes extraerlos. Si no puedes extraer algún campo, déjalo vacío. Las fechas trátalas siempre en formato
                                yyyy-mm-dd y las horas en formato HH:MM (24 horas). Serás capaz de leer todas las observaciones que incluya el documento
                                tales como materiales usados, etc. Todo lo que no encaje en los campos específicos, irá en observaciones.
                                También extraeras los tramos horarios junto con la actividad realizada en ese mismo tramo. Las actividades estarán marcadas en la imagen
                                por puntos o asteriscos al inicio de cada actividad, la primera actividad descrita en observaciones irá
                                asociada al primer tramo horario la segunda al segundo, y así sucesivamente. Devuelve los tramos horarios junto a la
                                actividad realizada en el siguiente formato: HH:MM-HH:MM,Actividad realizada, mételos en un array llamado tramos_actividad."
                            ]
                        ]
                    ],
                    [
                        "role" => "user",
                        "content" => [
                            [
                                "type" => "text",
                                "text" => "Extrae los datos básicos del parte adjunto (hora_entrada, hora_salida, horas_totales, fecha, técnico, cliente, dirección, matricula, observaciones, tramos_actividad) y devuelve
                                *únicamente* un JSON válido, sin explicaciones ni texto adicional. Abre llave { y cierra llave } y dentro los campos con sus valores,
                                nada más de texto. Si no puedes extraer algún campo, déjalo vacío."
                            ],
                            [
                                "type" => "image_url",
                                "image_url" => [
                                    "url" => "data:$mimeType;base64,$base64"
                                ]
                            ]
                        ]
                    ]
                ],
                "temperature" => 0.2,
            ];

            // 7️⃣ Ejecutar la solicitud a OpenAI
            $response = $client->post('chat/completions', [
                'json' => $requestData,
            ]);

            $dataResponse = json_decode($response->getBody(), true);

            // 8️⃣ Leer literalmente lo que devuelve el modelo
            $messageContent = trim($dataResponse['choices'][0]['message']['content'] ?? '[sin contenido]');

            // 9️⃣ Enviar literalmente la respuesta por WhatsApp
            /*$this->sendMessage(
                $from,
                "📤 *Respuesta literal del modelo:*\n\n" .
                    Str::limit($messageContent, 3900) // límite de WhatsApp
            );*/

            // 🔟 (Opcional) guardar la respuesta literal en $data por si la necesitas más tarde
            $data['workPart']['raw_response'] = $messageContent;
            // 8️⃣ Procesar la respuesta y decodificar el JSON que devuelve GPT
            /*$messageContent = $dataResponse['choices'][0]['message']['content'] ?? '{}';
            $extractedData = json_decode($messageContent, true);

            // Validar que sea JSON válido
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->sendMessage($from, "❌ Error interpretando la respuesta del modelo. Asegúrate de que el archivo sea legible.");
            }

            // 9️⃣ Guardar los datos extraídos en $data
            $data['workPart']['entryTime'] = $extractedData['hora_entrada'] ?? '';
            $data['workPart']['exitTime'] = $extractedData['hora_salida'] ?? '';
            $data['workPart']['date'] = $extractedData['fecha'] ?? '';
            $data['workPart']['technician'] = $extractedData['técnico'] ?? '';
            $data['workPart']['client'] = $extractedData['cliente'] ?? '';
            $data['workPart']['address'] = $extractedData['dirección'] ?? '';
            $data['workPart']['observations'] = $extractedData['observaciones'] ?? '';

            // 🔟 Responder al usuario (por WhatsApp)
            $jsonPretty = json_encode($extractedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $this->sendMessage($from, "📄 Datos extraídos del parte:\n```\n$jsonPretty\n```");*/
        } catch (\Throwable $e) {
            $mensajeError = "❌ *Error inesperado en getWorkPartData*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            return $this->sendMessage($from, Str::limit($mensajeError, 3900));
        }
    }


    //Sacar datos tarjeta de contacto
    private function getCardData(&$data, $mediaUrl, $from)
    {

        //Descargo el contenido de la imagen para pasar a chatgpt
        $content = @file_get_contents($mediaUrl);

        if ($content === false || strlen($content) < 100) {
            return $this->sendMessage($from, "❌ No se pudo descargar el archivo o está vacío, intentalo de nuevo y si el error persiste ponte en contacto con franperez@segenet.es");
        }

        // Detectar tipo de archivo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($content);

        // Codificar a base64 para chatgpt
        $base64 = base64_encode($content);

        $inputFiles[] = [
            "type" => "input_image",
            "image_url" => "data:$mime;base64,$base64"
        ];

        //Hago la consulta a chatgpt

        //Creo el cliente para la consulta
        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . env("CHATGPT_API_KEY"),
                'Content-Type' => 'application/json',
            ]
        ]);


        $requestData = [
            "model" => "gpt-4.1",
            "input" => [
                [
                    "role" => "system",
                    "content" => "
                                        Eres un experto leyendo tarjetas de contacto (fotos de tarjetas de visita o similares).
                                        Extraes siempre los datos relevantes en formato JSON.
                                        Si hay múltiples teléfonos o correos, solo el principal.
                                        No pongas comentarios fuera del JSON.
                                        No inventes datos que no aparecen en la imagen.
                                        El formato de respuesta debe ser un bloque json sin encabezados ni explicaciones, que sea simplemente {} con el contenido dentro.
                                        ES MUY IMPORTANTE QUE EL FOMRATO QUE SAQUES SEA SIEMPRE ASÍ, los {} y dentro los datos sin nada más fuera, ni json, ni ´´ni nada porque me fallaría.
                                        Si hay más de un teléfono saca el que más destaque, si los dos están igual de destacados coge el primero que aparezca.
                                        Los números de telefonos quiero que me los saques sin prefijos y sin espacios.
                                        Si algún dato de la dirección no está, por ejemplo comunidad, sacala con respecto a la provincia, localidad, codigo postal, etc."
                ],
                [
                    "role" => "user",
                    "content" => [
                        ...$inputFiles,
                        [
                            "type" => "input_text",
                            "text" => "Quiero los siguientes datos en JSON:
                                                {
                                                    nombre:,
                                                    apellidos: ,
                                                    telefono:,
                                                    email:,
                                                    empresa:,
                                                    dni/nie:,
                                                    cargo en empresa:,
                                                    comunidad:,
                                                    provincia:,
                                                    localidad:,
                                                    direccion:,
                                                    postal:
                                                }"
                        ]
                    ]
                ],
            ]
        ];


        try {
            $response = $client->post('responses', [
                'json' => $requestData
            ]);

            $dataImg = json_decode($response->getBody(), true);


            //Saco el json
            $dataImg = json_decode($dataImg["output"][0]["content"][0]["text"], true);


            $nameExploded = explode(' ', $dataImg['nombre']);
            $surnameExploded = explode(' ', $dataImg['apellidos']);

            //Meto los datos al contacto
            $data['contact'] = [];

            $data['contact']['name'] = [
                'first' => $nameExploded[0] ?? '',
                'second' => $nameExploded[1] ?? '',
            ];

            $data['contact']['surname'] = [
                'first' => $surnameExploded[0] ?? '',
                'second' => $surnameExploded[1] ?? '',
            ];

            // Telefono
            if (isset($dataImg['telefono']) && $dataImg['telefono'] !== '')
                $data['contact']['phone'] = $dataImg['telefono'];

            // Email
            if (isset($dataImg['email']) && $dataImg['email'] !== '')
                $data['contact']['email'] = $dataImg['email'];

            // dni
            if (isset($dataImg['dni/nie']) && $dataImg['dni/nie'] !== '')
                $data['contact']['dni'] = $dataImg['dni/nie'];

            // Cargo en la empresa
            if (isset($dataImg['cargo en empresa']) && $dataImg['cargo en empresa'] !== '')
                $data['contact']['position'] = $dataImg['cargo en empresa'];

            // Nombre en la empresa
            if (isset($dataImg['empresa']) && $dataImg['empresa'] !== '')
                $data['contact']['companyName'] = $dataImg['empresa'];

            // Comunidad
            if (isset($dataImg['comunidad']) && $dataImg['comunidad'] !== '')
                $data['contact']['community'] = $dataImg['comunidad'];

            // Provincia
            if (isset($dataImg['provincia']) && $dataImg['provincia'] !== '')
                $data['contact']['province'] = $dataImg['provincia'];

            // Localidad
            if (isset($dataImg['localidad']) && $dataImg['localidad'] !== '')
                $data['contact']['locality'] = $dataImg['localidad'];

            // Dirección
            if (isset($dataImg['direccion']) && $dataImg['direccion'] !== '')
                $data['contact']['address'] = $dataImg['direccion'];

            // Código postal
            if (isset($dataImg['postal']) && $dataImg['postal'] !== '')
                $data['contact']['postal'] = $dataImg['postal'];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return $this->sendMessage($from, Str::limit($e->getMessage() . ' ' . $e->getLine(), 3900));
        }
    }



    //Saco los datos del SIPS
    private function getSIPSData(&$data, &$session, $from, $user)
    {

        try {

            $cups = (preg_match('/^ES\d{16}[a-z]{2}[0-9][a-z]$/i', $data['pdfFormatted']['cups'] ?? ''))
                ? substr($data['pdfFormatted']['cups'], 0, -2)
                : ($data['pdfFormatted']['cups'] ?? '');

            $data['pdfFormatted']['cups'] = $cups;

            //Creo el request para pasarlo al otro controlador
            $requestSIPS = new Request([
                'CUPS' => $cups
            ]);


            //Llamo a la función de lectura OCR con ChatGPT
            $SIPSResponse = ToolsController::getAPIConsumption($requestSIPS);
            //Recojo solo el contenido, no el header
            $SIPSResponse = json_decode($SIPSResponse->getContent(), true);


            //Si no hay datos en el SIPS ( calculo un estimado de lo que daría )
            if (is_null($SIPSResponse['consumptionData'])) {

                //Tengo que sacar el consumo anual aproximado ( calculando con el mensual cuanto sería por día y sacando de 365 días )
                $estimatedAnualConsumption = [];

                /*foreach ($data['pdfFormatted']['consumptionData']['consumption'] as $key => $period) {

                    //Saco lo que es por día
                    $consumptionDay = doubleval($period) / $data['pdfFormatted']['diffInDays'];

                    //Calculo el estimado en 1 año
                    $estimatedAnualConsumption[$key] = ($consumptionDay * 365);
                }*/

                $data['SIPSData']['cupsData'] = [
                    'power' => $data['pdfFormatted']['consumptionData']['power'],
                    'consumption' => $data['pdfFormatted']['consumptionData']['consumption'],
                ];

                //trunco
                $data['SIPSData']['cupsData']['consumption'] = array_map('intval', $data['SIPSData']['cupsData']['consumption']);


                //Saco tarifa
                $data['SIPSData']['fee'] = $data['pdfData']['tarifa'];


                //Meto el máximo de potencia y el total de consumo para así poder filtrar luego por producto
                $data['pdfFormatted']['maxPower'] = max($data['pdfFormatted']['consumptionData']['power']);

                $data['pdfFormatted']['totalConsumption'] = array_sum($data['SIPSData']['cupsData']['consumption']);

                //return $this->sendMessage('34605581287', Str::limit(json_encode($data['SIPSData'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));


            } else {

                //Guardo los datos del SIPS
                $data['SIPSData']['cupsData'] = $SIPSResponse['cupsData'];
                //$data['SIPSData']['cupsIntervalsData'] = $SIPSResponse['consumptionData'];
                $data['SIPSData']['fee'] = $SIPSResponse['fee'];


                //Trunco el valor de la energia consumida
                $data['SIPSData']['cupsData']['consumption'] = array_map('intval', $data['SIPSData']['cupsData']['consumption']);


                $data['SIPSData']['cupsIntervalsData'] = $SIPSResponse['consumptionData'];

                //Compruebo si entre los registros que ha sacado están las fechas de la factura, sino lo meto
                $startDate = Carbon::createFromFormat('d/m/Y', $data['pdfData']['periodo_facturacion']['fecha_inicio']);
                $endDate = Carbon::createFromFormat('d/m/Y', $data['pdfData']['periodo_facturacion']['fecha_fin']);


                $exists = !empty(array_filter($data['SIPSData']['cupsIntervalsData'], function ($item) use ($startDate, $endDate) {
                    $itemStartDate = Carbon::createFromFormat('d/m/Y', $item['startDate']);
                    $itemEndDate = Carbon::createFromFormat('d/m/Y', $item['endDate']);

                    return $itemStartDate->isSameDay($startDate) && $itemEndDate->isSameDay($endDate);
                }));

                $startDate = $startDate->format('d/m/Y');
                $endDate = $endDate->format('d/m/Y');

                // Si no existe, agregar el nuevo intervalo
                if (!$exists) {

                    $consumption = array_map(function ($value) {
                        return $value === "" ? 0 : $value;  // Si el valor es vacío, poner 0, sino dejar el valor original
                    }, $data['pdfFormatted']['consumptionData']['consumption']);

                    $newInterval = [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'periods' => $consumption,
                        'powers' => null,
                        'consumption' => 0
                    ];
                    array_unshift($data['SIPSData']['cupsIntervalsData'], $newInterval);
                }





                //Meto los valores de potencia
                $data['pdfFormatted']['consumptionData']['power'] = $data['SIPSData']['cupsData']['power'];

                //Meto el máximo de potencia y el total de consumo para así poder filtrar luego por producto
                $data['pdfFormatted']['maxPower'] = max($data['pdfFormatted']['consumptionData']['power']);

                $data['pdfFormatted']['totalConsumption'] = array_sum($data['SIPSData']['cupsData']['consumption']);
            }
        } catch (\Throwable $e) {

            $mensajeError = "❌ *Error inesperado*\n";
            $mensajeError .= "*Mensaje:* " . $e->getMessage() . "\n";
            $mensajeError .= "*Archivo:* " . basename($e->getFile()) . "\n";
            $mensajeError .= "*Línea:* " . $e->getLine();
            $mensajeError .= "*Parte:* " . 'getSIPSData';


            //Log comparativa erronea
            AuditLogService::generateComparative('error', $e->getMessage(), 'bill', 'getSIPSData',  null, null, null, $user, $from);


            // Limitar longitud si lo envías por WhatsApp
            return $this->sendMessage('34642118237', Str::limit($mensajeError, 3900));
        }
    }


    //Cálculo comparativa
    private function calcComparative(&$data, $from, $marketers, $user){

        try {

            //Saco el total a pagar de la factura
            if (!isset($data['manualData']) || !$data['manualData']){
                $data['currentSubTotal'] = $this->calcTotal($data, $data['pdfFormatted']['prices'], true);
                $data['currentTotal'] = array_reduce($data['currentSubTotal'],
                    function ($acc, $value) {
                        if (is_array($value)) return $acc + ($value['total'] ?? 0);
                        return $acc + $value;
                    }, 0);
            }

            //Por si hubiese error y no sacase número
            if (!is_numeric($data['currentTotal'])){

                //Log comparativa erronea
                AuditLogService::generateComparative('error', '❌Ha habido un error con la factura. Vuelve a mandarla.', 'bill', 'calcComparative currentTotal',  null, null, null, $user, $from);

                return $this->sendMessage($from, '❌Ha habido un error con la factura. Vuelve a mandarla.');
            }

            //Saco las ofertas (saco las 5 con más ahorro y las 5 con más comisión)
            $data['offers'] = $this->calcOffers($data, $marketers, $user);

            //Filtro para obtener solo las 5 ofertas con mayor ahorro y las 5 con mayor commission
            $data['filteredOffers'] = $this->filterOffers($data['offers']);


            //Guardar cosas necesarias para posterior manejo
            if (!isset($data['seeCommissions']))
                $data['seeCommissions'] = false;
            else
                $data['seeCommissions'] = !$data['seeCommissions'];

        }catch (\Throwable $e) {
            return $this->sendMessage('34614011145', Str::limit(json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));
        }

    }

    //Mostrar ofertas
    private function getOffersMessage($session, &$data, $from, $user, $seeOpts = true)
    {
        $showCommissions = isset($data['seeCommissions']) && $data['seeCommissions'] === true;

        // Si es una comparativa del CRM
        if ($session->app === 'crm') {

            if (!isset($data['manualData']) || !$data['manualData']) {
                $messageResumeComparative =
                    "📊 *Comparativa de factura*\n\n" .
                    "👤 *Titular:* {$data['pdfData']['titular']}\n" .
                    "🔌 *CUPS:* {$data['pdfData']['cups']}\n" .
                    "📦 *Tarifa actual:* {$data['SIPSData']['fee']}\n" .
                    "📆 *Periodo:* {$data['pdfData']['periodo_facturacion']['fecha_inicio']} - {$data['pdfData']['periodo_facturacion']['fecha_fin']}\n" .
                    "💰 *Total factura:* " . number_format($data['currentTotal'], 2) . " €\n\n";
            } else {
                $messageResumeComparative =
                    "📊 *Comparativa manual*\n\n" .
                    "💰 *Precio actual:* " . number_format($data['currentTotal'], 2) . " €\n" .
                    "💡 *Consumo:* {$data['comparative']['energy'][0]} kWh\n" .
                    "⚡️ *Potencia:* {$data['comparative']['potency'][0]} kW\n" .
                    "📆 *Total días:* {$data['comparative']['days']}\n\n";
            }

            if (empty($data['filteredOffers'])) {
                return $messageResumeComparative . "No hay ofertas disponibles.";
            }

            $messageResumeComparative .= "📈 *OFERTAS*\n\n";

            $count = 1;

            foreach ($data['filteredOffers'] as $offerTypeKey => $offerType) {
                if (empty($offerType)) {
                    continue;
                }

                switch ($offerTypeKey) {
                    case 'saving':
                        $messageResumeComparative .= "*_Mayor ahorro_*\n\n";
                        break;

                    case 'commission':
                        $messageResumeComparative .= "*_Mayor comisión_*\n\n";
                        break;

                    case 'efficiency':
                        $messageResumeComparative .= "*_Mayor eficiencia_*\n\n";
                        break;

                    default:
                        continue 2;
                }

                foreach ($offerType as $offer) {
                    $messageResumeComparative .=
                        "*" . $count . ".* " .
                        $offer['marketer'] . " | " . $offer['product'] . "\n" .
                        "_Ahorro: " . round($offer['saveAmount']) . "€_";

                    if ($showCommissions) {
                        $messageResumeComparative .=
                            "\n_Comisión: " . round($offer['commission'], 2) . "€_";
                    }

                    $messageResumeComparative .= "\n\n";

                    $count++;
                }

                $messageResumeComparative .= "\n";
            }

            if ($seeOpts === true) {
                $messageResumeComparative .= "Elige una opción:";
            } else {
                $messageResumeComparative .= "Selecciona una de las ofertas por número:";
            }

            return $messageResumeComparative;
        }

        // Con Witro
        if (!isset($data['filteredOffers']['saving'][0])) {
            return "No hay ofertas disponibles.";
        }

        if ($data['filteredOffers']['saving'][0]['saveAmount'] > 0) {
            $messageResumeComparative =
                "¡Has conseguido un ahorro máximo de *" .
                round($data['filteredOffers']['saving'][0]['saveAmount']) .
                "€ en esta factura*!\n\n";
        } else {
            $messageResumeComparative =
                "¡No has conseguido ahorro, inténtalo de nuevo más adelante!\n\n";
        }

        $messageResumeComparative .=
            "*_¿Quieres crear un contrato a partir de la oferta o prefieres hablar directamente con un agente?_*";

        return $messageResumeComparative;
    }

    //Sacar precio total mensual (se calcula en vez de sacarlo del pdf)
    private function calcTotal(&$data, $prices, $isCurrent = false) {
        $power = ['total' => 0, 'discount' => 0];
        $energy = ['total' => 0, 'discount' => 0, 'adjustmentService' => 0];
        $surplus = ['total' => 0, 'virtualBattery' => 0, 'compensation' => 0];
        $taxes = ['total' => 0, 'iva' => 0, 'electricTax' => 0, 'socialBonus' => 0, 'meterDevice' => 0];

        $days = $data['pdfFormatted']['diffInDays'];


        for ($i = 0; $i < 6; $i++) {

            // Potencia
            $fee = isset($prices['fees']['power'][$i]) ? (float)$prices['fees']['power'][$i] / 30 : 0;
            $price = (float)($prices['power'][$i] ?? 0) + $fee;
            $consumption = (float)($data['pdfFormatted']['consumptionData']['power'][$i] ?? 0);

            $totalPeriod = $price * $consumption * $days;

            if ($isCurrent && isset($data['pdfData']['descuento_potencia'])) {
                $power['discount'] -= $totalPeriod * $data['pdfData']['descuento_potencia'] / 100;
            }

            $power["P" . ($i + 1)] = $totalPeriod;
            $power['total'] += $totalPeriod;


            // Energía
            $fee = isset($prices['fees']['energy'][$i]) ? (float)$prices['fees']['energy'][$i] / 1000 : 0;
            $price = (float)($prices['consumption'][$i] ?? 0) + $fee;
            $consumption = (float)($data['pdfFormatted']['consumptionData']['consumption'][$i] ?? 0);

            $totalPeriod = $price * $consumption;

            if ($isCurrent && isset($data['pdfData']['descuento_energia'])) {
                $energy['discount'] -= $totalPeriod * $data['pdfData']['descuento_energia'] / 100;
            }

            $energy["P" . ($i + 1)] = $totalPeriod;
            $energy['total'] += $totalPeriod;
        }

        // Redondeo
        $power['total'] = round($power['total'] + $power['discount'], 2);
        $energy['total'] = round($energy['total'] + $energy['discount'], 2);

        // Excedentes
        $surplus['virtualBattery'] = round(((float)($prices['surplus']['virtualBattery'] ?? 0) / 30) * $days, 2);

        $surplus['compensation'] = round(
            (float)($data['pdfFormatted']['surplus']['amount'] ?? 0) *
            (float)($data['pdfFormatted']['surplus']['price'] ?? 0) * -1,
            2
        );

        $surplus['total'] = $surplus['virtualBattery'] + $surplus['compensation'];

        // Otros conceptos
        $otherConcepts = 0;
        $otherConceptsElectricTax = 0;
        $otherConceptsDetail = [];

        if (!empty($data['pdfFormatted']['otherConcepts'])) {
            foreach ($data['pdfFormatted']['otherConcepts'] as $concept) {

                if ($isCurrent) {
                    $value = (float)$concept['value'];
                    $otherConcepts += $value;

                    if (!empty($concept['electricTax'])) {
                        $otherConceptsElectricTax += $value;
                    }

                    $otherConceptsDetail[] = [
                        'name' => $concept['name'] ?? '',
                        'value' => $value
                    ];
                }
            }
        }

        // Impuestos
        $taxConfig = [
            'iva' => $data['pdfData']['otros']['iva'] ?? 21,
            'electricTax' => 5.11269632,
            'meterDevice' => $data['pdfData']['otros']['alquiler_equipo_medida'] ?? 0,
            'socialBonus' => $data['pdfData']['otros']['bono_social'] ?? 0.012742
        ];

        $taxes['socialBonus'] = round($taxConfig['socialBonus'] * $days, 2);
        $taxes['meterDevice'] = round($taxConfig['meterDevice'] * $days, 2);

        $taxes['electricTax'] = round(
            $taxConfig['electricTax'] *
            ($power['total'] + $energy['total'] + $surplus['compensation'] + $taxes['socialBonus'] + $otherConceptsElectricTax) / 100,
            2
        );

        $taxes['iva'] = round(
            $taxConfig['iva'] *
            ($power['total'] + $energy['total'] + $surplus['total'] + $otherConcepts + $taxes['socialBonus'] + $taxes['meterDevice'] + $taxes['electricTax']) / 100,
            2
        );

        $taxes['total'] = $taxes['iva'] + $taxes['electricTax'] + $taxes['socialBonus'] + $taxes['meterDevice'];

        //Devuelvo todos los datos para la suma
        return [
            'power' => $power,
            'energy' => $energy,
            'surplus' => $surplus,
            'taxes' => $taxes,
            'otherConcepts' => $otherConcepts,
            'otherConceptsDetail' => $otherConceptsDetail
        ];
    }

    //Sacar ofertas (5 con más ahorro y las 5 con más comisión)
    private function calcOffers(&$data, $marketers, $user)
    {

        try {

            $offers = [];

            //Compruebo con cada producto de cada comercializadora
            foreach ($marketers as $marketer) {

                //Compruebo que el usuario tenga la comercializadora visible
                if (!in_array($this->extractId($marketer['_id']), $user['marketers']) && $user['label'] !== 'Usuario subdominio')
                    continue;


                //Compruebo que la comercializadora tenga la Tarifa habilitada para meter en sus productos
                $marketerFeeInd = array_search(('Tarifa ' . $data['SIPSData']['fee']), array_column($marketer['fees']['electricity'], 'name'));


                if ($marketerFeeInd !== false) {

                    foreach ($marketer['products']['electricity'] as $product) {

                        foreach ($product['fees'] as $fee) {

                            $feeId = $this->extractId($fee['id']);
                            $marketerFeeId = $this->extractId($marketer['fees']['electricity'][$marketerFeeInd]['id']);



                            if ($feeId === $marketerFeeId && (!isset($fee['archived']) || $fee['archived'] === false)) { //Si la tarifa es la misma que la del contrato y el producto no esta archivado

                                $marketerFee = $marketer['fees']['electricity'][$marketerFeeInd];


                                //Solo lo meto si tiene datos de precios de potencia y energia y si es de precio fijo
                                if (floatval($fee['prices']['power']['P1']) && floatval($fee['prices']['consume']['P1']) && $fee['priceType'] === 'fixed') {

                                    $prices = [
                                        'power' => array_values($fee['prices']['power']),
                                        'consumption' => array_values($fee['prices']['consume'])
                                    ];


                                    $subTotal = $this->calcTotal($data, $prices);
                                    $total = array_reduce($subTotal,
                                        function ($acc, $value) {
                                            if (is_array($value)) return $acc + ($value['total'] ?? 0);

                                            return $acc + $value;
                                        },
                                        0);

                                    if (!isset($fee['commissionType']))
                                        return $this->sendMessage('34614011145', "Fee sin commissionType --> " . $marketer['name'] . Str::limit(json_encode($fee, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));

                                    $commission = $this->calcCommission($this->extractId($marketer['_id']), $fee, $fee['commissionType'], $user, $data);


                                    $offerNow = [
                                        'marketer' => $marketer['name'],
                                        'marketerId' => (string) $marketer['_id'],
                                        'fee' => $marketerFee['name'],
                                        'product' => $product['name'],
                                        'prices' => $prices,
                                        'total' => $total,
                                        'subTotal' => $subTotal,
                                        'savePercent' => $data['currentTotal'] === 0 ? -1000 : (($data['currentTotal'] - $total) / $data['currentTotal']),
                                        'commissionType' => $fee['commissionType'],
                                        'saveAmount' => $data['currentTotal'] - $total,
                                        'efficiency' => (isset($commission) && $commission > 0 && $commission !== null && $commission !== '') ? ($commission / $total) : -10000000000000, //total producto
                                        'commission' => $commission,
                                        'feeCommission' => $fee,
                                        'viewPrices' => false
                                    ];

                                    $offers[] = $offerNow;
                                }
                            }
                        }
                    }
                }
            }

            return $offers;
        } catch (\Throwable $e) {

            // mando el mensaje de error completo
            $msg = "Error en calcOffers:\n";
            $msg .= $e->getMessage() . "\n";
            $msg .= "En " . $e->getFile() . " línea " . $e->getLine();
            $msg .= "*Parte:* " . 'saveOpp';

            $this->sendMessage('34614011145', $msg);
            return [];
        }

        return $offers;
    }


    //Calculo comisión
    private function calcCommission($marketerId, $fee, $commissionType, $user, $data)
    {
        return 0;

        try {

            //Miro el usuario para ver el rango de comisión
            $commRange = null;
            $commPercentaje = null;

            //Busco el rango de comisión que le pertenece
            if ($user['label'] === 'Usuario subdominio' || $user['_id'] === '65d47559aa2d0448c308e252' || $user['_id'] === '65d48ac808c6cf0254066c42' || $user['_id'] === '6617a7ffc4f2475a7a010d32' || $user['_id'] === '65d704c63d2a9cbfd79e549a') {
                $commRange = 'comAs';
            } else {

                //Compruebo si el usuario tiene comisiones propias
                if (!isset($user['commissions'])) {
                    //Busco usuarios hasta arriba hasta ver uno que tenga commissions
                    $userListTop = AuthController::getAllSuperiors($user['_id']);

                    $userFound = $user;

                    //Los recorro hasta encontrar uno que tenga comisiones
                    do {
                        $userFound = collect($userListTop)->firstWhere('_id', $userFound['responsibles'][0]);
                    } while (!isset($userFound['commissions']));

                    $user = $userFound;
                }

                //Compruebo si las comisiones son porcentuales o por rangos
                if ($user['commissions'][$marketerId]['type'] === 'range') {
                    $commRange = 'com' . $user['commissions'][$marketerId]['value'];
                } else if($user['commissions'][$marketerId]['type'] === 'percentage') {
                    //Pongo la comisión superior
                    $commRange = 'comAs';
                    //Guardo el porcentaje para sacar la comisión
                    $commPercentaje = $user['commissions'][$marketerId]['value'];
                } else{
                    return $user['commissions'][$marketerId]['value'];
                }



                /*
                 *
                 * if ($user->commissions[$data['marketerInfo']['_id']]['type'] === 'range') {
                        $key = 'com' . $commissionUser;

                        $order['salesCommision'] = $data['type'] === 'basic'
                            ? $data['feeInfo']['consumptionBasic'][$key]
                            : $data['interval'][$key];

                    } elseif ($user->commissions[$data['marketerInfo']['_id']]['type'] === 'percentage') {//si es porcentaje
                        $order['salesCommision'] = $order['asercordCommision'] * ($commissionUser / 100);
                    } else {
                        $order['salesCommision'] = $commissionUser;
                    }
                 *
                 * */
            }

            $commission = 0;

            if (isset($fee['consumptionBasic']))
                $commission = $fee['consumptionBasic'][$commRange];
            else {
                //Busco en el desglose según sea comisión, consumo o los dos

                $potency = $data['pdfFormatted']['maxPower'];
                $consumption = $data['pdfFormatted']['totalConsumption'];

                switch ($commissionType) {
                    case 'c':
                        foreach ($fee['consumptionBreakdown'] as $interval) {
                            if ($interval['con1'] <= $consumption && ($interval['con2'] >= $consumption || $interval['con2'] === '>')) {
                                $commission = $interval[$commRange] ?? null;
                                break;
                            }
                        }
                        break;

                    case 'p':
                        foreach ($fee['consumptionBreakdown'] as $interval) {
                            if ($interval['pot1'] <= $potency && ($interval['pot2'] >= $potency || $interval['pot2'] === '>')) {
                                $commission = $interval[$commRange] ?? null;
                                break;
                            }
                        }
                        break;

                    case 'cyp':
                        foreach ($fee['consumptionBreakdown'] as $interval) {
                            if (
                                $interval['pot1'] <= $potency &&
                                ($interval['pot2'] >= $potency || $interval['pot2'] === '>') &&
                                $interval['con1'] <= $consumption &&
                                ($interval['con2'] >= $consumption || $interval['con2'] === '>')
                            ) {
                                $commission = $interval[$commRange] ?? null;
                                break;
                            }
                        }
                        break;
                }
            }

            //Si es porcentual saco el total con el porcentaje
            if ($commPercentaje !== null) {
                $commission = doubleval($commission) * ($commPercentaje / 100);
            }


            return $commission;
        } catch (\Throwable $e) {

            // mando el mensaje de error completo
            $msg = "Error en calcOffers:\n";
            $msg .= $e->getMessage() . "\n";
            $msg .= "En " . $e->getFile() . " línea " . $e->getLine();
            $msg .= "*Parte:* " . 'saveOpp';
            $this->sendMessage('34605581287', $msg);
            return [];
        }
    }


    //Sacar las 5 ofertas con más ahorro y las 5 con más comisión
    private function filterOffers($offers)
    {

        try {

            //Saco los 5 con mayor ahorro
            usort($offers, function ($a, $b) {
                return $b['saveAmount'] <=> $a['saveAmount'];
            });
            $offersSaving = array_slice($offers, 0, 5);


            //Saco los 5 con mayor comisión
            usort($offers, function ($a, $b) {
                return $b['commission'] <=> $a['commission'];
            });
            $offersCommission = array_slice($offers, 0, 5);

            //Saco los 5 con mejor relación de eficiencia = total precio / comisión
            usort($offers, fn($a, $b) => $b['efficiency'] <=> $a['efficiency']);
            $offersEfficient = array_slice($offers, 0, 5);

            //SALIDA PARA VER PRODUCTOS SEGÚN CADA UNA DE LAS EFICIENCIAS
            //return $this->sendMessage('34605581287', Str::limit(json_encode(array_column($offersCommission, 'product'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900) . ' | ' . Str::limit(json_encode(array_column($offersCommission, 'commission'), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));

            return ['saving' => $offersSaving, 'commission' => $offersCommission, 'efficiency' => $offersEfficient];

        }catch (\Throwable $e) {
            // mando el mensaje de error completo
            $msg = "Error en filterOffers:\n";
            $msg .= $e->getMessage() . "\n";
            $msg .= "En " . $e->getFile() . " línea " . $e->getLine();
            $this->sendMessage('34614011145', $msg);
            return [];
        }

    }


    //Función objectIds
    private function extractId($raw)
    {
        // Si es array y tiene '$oid', úsalo; si no, si es array devuelve su primer elemento
        if (is_array($raw)) {
            if (isset($raw['$oid'])) {
                return (string) $raw['$oid'];
            }
            // fallback: primer valor
            $vals = array_values($raw);
            return (string) $vals[0];
        }
        // si ya era string/int/float
        return (string) $raw;
    }


    //Función para sacar el array de ids
    private function extractIds(array $fees, string $column = 'id'): array
    {
        return array_filter(array_map(function ($fee) use ($column) {
            if (!isset($fee[$column])) {
                return null;
            }

            $id = $fee[$column];

            // Caso 1: Mongo devuelve {"$oid": "..."}
            if (is_array($id) && isset($id['$oid'])) {
                return (string) $id['$oid'];
            }

            // Caso 2: Mongo devuelve ObjectId(...)
            if ($id instanceof ObjectId) {
                return (string) $id;
            }

            // Caso 3: ya es string normal
            return (string) $id;
        }, $fees));
    }

    //Funcíon para sacar los números bonitos para el whats
    private function numberToEmoji($number) {
        $digits = str_split((string) $number);
        $emojiDigits = array_map(fn($d) => $d . "\u{FE0F}\u{20E3}", $digits);
        return implode('', $emojiDigits);
    }

    //Función para eliminar la comisión
    private function clearCommissions($orderToModify)
    {
        $orderToModify['salesCommision'] = '';
        $orderToModify['asercordCommision'] = '';
    }

    protected function cleanUtf8($value)
    {
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->cleanUtf8($v);
            }
            return $value;
        }

        if (is_string($value)) {
            // Normaliza a UTF-8 y elimina bytes inválidos
            $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
            $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        }

        return $value;
    }


    //Función para sacar el pdf de la comparativa sendComparativePDF
    private function sendComparativePDF($data, $user, $from, $session)
    {

        try {

            $userNow = $user;

            //Saco el enterprise desde user
            if ($user['label'] !== 'Usuario subdominio' && $user['_id'] !== '65d704c63d2a9cbfd79e549a') {

                do {
                    $userNow = User::where('_id', $userNow['responsibles'][0])->first();
                } while ($userNow['label'] !== 'Usuario subdominio');

            }

            $enterprise = Enterprise::where('subdomainUser', $userNow['_id'])->first();


            //Fechas
            /*$startDate = \DateTime::createFromFormat('d/m/Y', $data['SIPSData']['cupsIntervalsData'][0]['startDate']);
            $endDate = \DateTime::createFromFormat('d/m/Y', $data['SIPSData']['cupsIntervalsData'][0]['endDate']);

            // Formatear las fechas al formato ISO 8601
            $dates = [
                'start' => $startDate->format('Y-m-d\TH:i:s\Z'),
                'end' => $endDate->format('Y-m-d\TH:i:s\Z')
            ];*/

            if (!isset($data['manualData']) || !$data['manualData']){

                $factStart = Carbon::createFromFormat('d/m/Y', $data['pdfData']['periodo_facturacion']['fecha_inicio']);
                $factEnd = Carbon::createFromFormat('d/m/Y', $data['pdfData']['periodo_facturacion']['fecha_fin']);

                $dates = [
                    'start' => $factStart->format('Y-m-d\TH:i:s\Z'),
                    'end' => $factEnd->format('Y-m-d\TH:i:s\Z')
                ];

                // 2) Buscar el índice del intervalo que coincide con la factura
                $cupsInterval = 0; // fallback
                if (isset($data['SIPSData']['cupsIntervalsData']))
                    foreach ($data['SIPSData']['cupsIntervalsData'] as $i => $it) {
                        $itStart = Carbon::createFromFormat('d/m/Y', $it['startDate']);
                        $itEnd = Carbon::createFromFormat('d/m/Y', $it['endDate']);
                        if ($itStart->isSameDay($factStart) && $itEnd->isSameDay($factEnd)) {
                            $cupsInterval = $i;
                            break;
                        }
                    }

            }


            // 2) Decide el origen (por defecto: witro si no hay sesión o no es CRM)
            $origin = ($session && ($session->app ?? null) === 'crm') ? 'crm' : 'witro';



            // 3) Construye el Request incluyendo 'origin'
            $request = new Request([
                'payload' => json_encode(
                    $this->cleanUtf8([
                        'basicData' => [
                            'userSubdomain' => $this->getUserSubdomainOrEnterprise($user),
                            'enterprise' => $enterprise,
                            'userLogged' => $user
                        ],
                        'pdfForm' => [
                            'name' => !isset($data['manualData']) ? $data['pdfData']['titular'] : '',
                            'CIF' => !isset($data['manualData']) ? $data['pdfData']['cif_nif'] : '',
                            'currentMarketer' => !isset($data['manualData']) ? $data['pdfData']['comercializadora'] : '',
                            'billingInfo' => [
                                'community' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['comunidad_autonoma'] : '',
                                'province' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['provincia'] : '',
                                'locality' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['poblacion'] : '',
                                'address' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['direccion'] : '',
                                'postal' => !isset($data['manualData']) ? $data['pdfData']['direccion_titular']['codigo_postal'] : ''
                            ],
                            'studyDate' => Carbon::now()->format('Y-m-d'),
                            'order' => [
                                'productType' => 'cl',
                                'marketer' => $data['offerSelected']['marketer'] ?? $data['filteredOffers']['saving'][0]['marketer'],
                                'fee' => $data['offerSelected']['fee'] ?? $data['filteredOffers']['saving'][0]['fee'],
                                'product' => $data['offerSelected']['product'] ?? $data['filteredOffers']['saving'][0]['product'],
                                'CUPS' => !isset($data['manualData']) ? $data['pdfFormatted']['cups'] : '',
                                'direc' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['direccion'] : '',
                                'zip' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['codigo_postal'] : '',
                                'town' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['poblacion'] : '',
                                'province' => !isset($data['manualData']) ? $data['pdfData']['direccion_suministro']['provincia'] : ''
                            ]
                        ],
                        'fee' => $data['pdfData']['tarifa'] ?? $data['SIPSData']['fee'],
                        'cupsData' => [
                            'power' => !isset($data['manualData']) ? $data['SIPSData']['cupsData']['power'] : null,
                            'consumption' => !isset($data['manualData']) ? $data['SIPSData']['cupsData']['consumption'] : null,
                        ],
                        'cupsIntervalsData' => $data['SIPSData']['cupsIntervalsData'] ?? null,
                        'prices' => [
                            'power' => !isset($data['manualData']) ? array_map('strval', $data['pdfFormatted']['prices']['power']) : [],
                            'energy' => !isset($data['manualData']) ? array_map('strval', $data['pdfFormatted']['prices']['consumption']) : [],
                            'energyDiscount' => !isset($data['manualData']) ? $data['pdfData']['descuento_energia'] : 0,
                            'powerDiscount' => !isset($data['manualData']) ? $data['pdfData']['descuento_potencia'] : 0,
                        ],
                        'offer' => [
                            'power' => $data['offerSelected']['prices']['power'] ?? $data['filteredOffers']['saving'][0]['prices']['power'],
                            'energy' => $data['offerSelected']['prices']['consumption'] ?? $data['filteredOffers']['saving'][0]['prices']['consumption'],
                            'subTotal' => $data['offerSelected']['subTotal'] ?? $data['filteredOffers']['saving'][0]['subTotal'],
                            'fees' => $data['offerSelected']['fees'] ?? $data['filteredOffers']['saving'][0]['fees'] ?? null,
                        ],
                        'period' => 'month',
                        'topOffers' => $data['filteredOffers']['saving'], //Cambiar y dejar la seleccionada y 4 más por debajo, no desde la primera
                        'currentSubtotal' => $data['currentSubTotal'] ?? [],
                        'powerPricePeriod' => 'day',
                        'offerSelected' => $data['offerSelected'] ?? $data['filteredOffers']['saving'][0],
                        'filteredOffers' => $data['filteredOffers']['saving'],
                        'currentTotal' => $data['currentTotal'],
                        'manualTotal' => $data['currentTotal'],
                        'adjustmentServiceValue' => 0,
                        'cupsInterval' => $cupsInterval ?? [],
                        'enterpriseId' => $enterprise['_id'],
                        'dates' => $dates ?? null,
                        'from' => $from,
                        'origin' => $origin,
                        'totalDays' => $data['pdfFormatted']['diffInDays'],
                        'userLogged' => $user,
                        'whats' => true
                    ])
                ),
                'enterpriseImg' => null
            ]);


            $pdfName = ToolsController::generateElectricityPDF($request);


            $this->sendMessage($from, 'https://crm.zocoenergia.com/assets/temporal_comparatives/' . $pdfName, 'document', 'Ofertas');

            //Borro el pdf de local
            Storage::disk('temporal_comparatives')->delete($pdfName);
        } catch (\Throwable $e) {

            $errorMessage = "❌ *Tools Controller*\n";
            $errorMessage .= "*Mensaje:* " . $e->getMessage() . "\n";
            $errorMessage .= "*Archivo:* " . $e->getFile() . "\n";
            $errorMessage .= "*Línea:* " . $e->getLine() . "\n";
            $errorMessage .= "*Código:* " . $e->getCode() . "\n";
            $errorMessage .= "*Trace:*\n" . $e->getTraceAsString() . "\n";

            // Si la excepción tiene una 'previous' (encadenada), la mostramos también
            if ($e->getPrevious()) {
                $prev = $e->getPrevious();
                $errorMessage .= "\n--- Excepción previa ---\n";
                $errorMessage .= "Mensaje: " . $prev->getMessage() . "\n";
                $errorMessage .= "Archivo: " . $prev->getFile() . "\n";
                $errorMessage .= "Línea: " . $prev->getLine() . "\n";
            }


            Log::error('WHATSAPP BOT ERROR', [
                'message' => $errorMessage,
                'from' => $from ?? null,
                'provider' => $this->whatsappProvider ?? null,
                'instance' => self::$WHATSAPP_INSTANCE_ID ?? null,
                'step' => $session->step ?? null,
            ]);
        }
    }


    //Sacar el usuario subdominio o el enterprise
    private function getUserSubdomainOrEnterprise($user, $type = 'user'){
        //if ($user['label'] !== 'Usuario subdominio' && $user['_id'] !== '65d704c63d2a9cbfd79e549a') TENER EN CUENTA

        if ($user['label'] === 'Usuario subdominio')
            return $user;

        $userNow = $user;

        do {
            if (!isset($userNow['responsibles'][0]))
                return $this->sendMessage('34605581287', Str::limit(json_encode($userNow, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3900));

            $userNow = User::where('_id', $userNow['responsibles'][0])->first();
        } while ($userNow['label'] !== 'Usuario subdominio');

        if ($type === 'user')
            return $userNow;
        else
            return Enterprise::where('subdomainUser', $userNow['_id'])->first();
    }


    function isValidNifCif($value)
    {
        $value = strtoupper(trim($value));

        // DNI: 8 números + letra
        if (preg_match('/^[0-9]{8}[A-Z]$/', $value)) {
            return true;
        }

        // CIF: letra + 7 números + (letra o número)
        if (preg_match('/^[A-Z][0-9]{7}[A-Z0-9]$/', $value)) {
            return true;
        }

        return false;
    }


    //Función para obtener la localización desde las distintas APIs
    private function normalizeWhatsappLocation(array $payload): ?array
    {
        /*
        |--------------------------------------------------------------------------
        | Posibles ubicaciones del mensaje según proveedor
        |--------------------------------------------------------------------------
        */
        $message =
            // Whapi /messages
            data_get($payload, 'messages.0')

            // Whapi /chats_updates
            ?? data_get($payload, 'chats_updates.0.after_update.last_message')
            ?? data_get($payload, 'chats_updates.0.before_update.last_message')

            // UltraMsg
            ?? data_get($payload, 'data')

            // Otros formatos simples
            ?? $payload;

        if (!$message || !is_array($message)) {
            return null;
        }

        $chatId = data_get($message, 'chat_id')
            ?? data_get($payload, 'chat_id')
            ?? data_get($payload, 'from')
            ?? data_get($message, 'from');

        $from = $chatId ?: data_get($message, 'from');

        $type = data_get($message, 'type')
            ?? data_get($payload, 'type')
            ?? 'text';

        return [
            'id' => data_get($message, 'id'),
            'from' => $from,
            'type' => $type,

            'location' => [
                'latitude' => data_get($message, 'location.latitude')
                    ?? data_get($message, 'latitude')
                        ?? data_get($payload, 'location.latitude')
                        ?? data_get($payload, 'latitude'),

                'longitude' => data_get($message, 'location.longitude')
                    ?? data_get($message, 'longitude')
                        ?? data_get($payload, 'location.longitude')
                        ?? data_get($payload, 'longitude'),

                'address' => data_get($message, 'location.address')
                    ?? data_get($message, 'address')
                        ?? data_get($payload, 'location.address')
                        ?? data_get($payload, 'address'),

                'name' => data_get($message, 'location.name')
                    ?? data_get($message, 'name'),

                'title' => data_get($message, 'location.title')
                    ?? data_get($message, 'title'),

                'url' => data_get($message, 'location.url')
                    ?? data_get($message, 'url'),

                'description' => data_get($message, 'location.description')
                    ?? data_get($message, 'description'),

                'place_id' => data_get($message, 'location.place_id')
                    ?? data_get($message, 'place_id'),
            ],

            'raw' => $message,
        ];
    }
}
