<?php
/*
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OpenComparatorController extends Controller
{
    public function registerOrder(Request $request){
        $client = json_decode($request['client']);
        $offer = json_decode($request['offerSelected']);
        $invoice  = $request->file('invoice');

         dd($client, $offer, $invoice);
        //Recibo los datos del cliente
        //Valido los datos
        //A nombre de quien? Si no viene ningun referido lo pongo a witro?
        //Creo la cuenta
        //Creo el contrato
    }
}
    */

namespace App\Http\Controllers;

//use App\Http\Models\Account;
//use App\Http\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Http\Models\User;
use App\Http\Models\Opportunity;
use App\Http\Models\Enterprise;

class OpenComparatorController extends Controller
{
    private const RAFFLES = [
        '65cb57489c2c285441086a43' => [
            'campaign' => 'tablet_2026_test',
            'max_number' => 1000,
            'prize' => 'tablet',
            'email_subject' => 'Su número para el sorteo de la tablet',
        ],

        '69fb14d1b16973ee85030762' => [
            'campaign' => 'golf_2026_test',
            'max_number' => 1000,
            'prize' => 'golf',
            'email_subject' => 'Su número para el sorteo de golf',
        ],
    ];
    public function registerOrder(Request $request): JsonResponse
    {

        // 1. Validación básica de entrada
        $request->validate([
            'client' => ['required', 'string'],
            'offerSelected' => ['required', 'string'],

            // Nuevo formato, varios archivos
            'invoices' => ['nullable', 'array'],
            'invoices.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp'],

            // Mantengo compatibilidad por si algún formulario antiguo manda "invoice"
            'invoice' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        // 2. Decodificar JSON recibido desde el frontend
        $client = json_decode($request->input('client'));
        $offer = json_decode($request->input('offerSelected'));

        // Nuevo formato: varios archivos
        $invoices = $request->file('invoices', []);

        // Compatibilidad con formato antiguo: un solo archivo invoice
        if (empty($invoices) && $request->hasFile('invoice')) {
            $invoices = [$request->file('invoice')];
        }

        $type = $request->input('type');
        $serviceType = $request->input('serviceType');

        //QR
        $ref = $request->input('ref');
        $user = null;


        // 3. Validar que el JSON se ha decodificado bien
        if (!$client || !$offer) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos del cliente o de la oferta no son válidos.',
            ], 422);
        }

        // 4. Validar campos internos del cliente
        $clientValidator = Validator::make([
            'name' => $client->name ?? null,
            'email' => $client->email ?? null,
            'phone' => $client->phone ?? null,
        ], [
            'name' => ['required', 'string', 'max:255'],

            // Solo obligatorio si NO hay teléfono
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],

            // Solo obligatorio si NO hay email
            'phone' => ['nullable', 'string', 'max:30', 'required_without:email'],
        ]);

        if ($clientValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos del cliente no son válidos.',
                'errors' => $clientValidator->errors(),
            ], 422);
        }

        // 5. Validar campos mínimos de la oferta
        $offerValidator = Validator::make([
            'marketer' => $offer->marketer ?? null,
            'product' => $offer->product ?? null,
            'fee' => $offer->fee ?? null,
        ], [
            'marketer' => ['required', 'string', 'max:255'],
            'product' => ['required', 'string', 'max:255'],
            'fee' => ['required'],
        ]);

        if ($offerValidator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos de la oferta no son válidos.',
                'errors' => $offerValidator->errors(),
            ], 422);
        }

        // 6. Guardar factura si existe
// 6. Guardar facturas si existen
$invoicePath = null;
$docs = [];

// Ruta absoluta definida en el disco "order"
$directory = Storage::disk('order')->path('');

if (!is_dir($directory)) {
    mkdir($directory, 0755, true);
}

foreach ($invoices as $invoice) {
    if (!$invoice || !$invoice->isValid()) {
        continue;
    }

    $originalName = $invoice->getClientOriginalName();
    $mimeType = $invoice->getMimeType();
    $extension = $invoice->getClientOriginalExtension();

    $fileName = time() . '_open_comparator_' . Str::random(20) . '.' . $extension;

    // Guarda directamente en la ruta absoluta del disco "order"
    $invoice->move($directory, $fileName);

    @chmod($directory . DIRECTORY_SEPARATOR . $fileName, 0644);

    // Ruta absoluta final
    $invoicePath = $directory . DIRECTORY_SEPARATOR . $fileName;

    $docs[] = [
        'title' => 'Factura comparador abierto',
        'defaultTitle' => $originalName,
        'value' => $fileName,
        'fileValue' => '',
        'icon' => $this->getIconForFileType($mimeType),
        'id' => (string) Str::uuid(),
        'errors' => [],
        'createdAt' => now()->format('Y-m-d H:i:s'),
    ];
}

        // 7. Responsable por defecto
        $assignedTo = 'Witro';

        // 8. Buscar si el usuario ya existe
        //$user = User::where('email', $client->email)
        //    ->orWhere('phone', $client->phone)
        //    ->first();

        //Obtener la id del usuario responsable (por defecto Witro)
        //Si llega token, el usuario responsable es ese, si no es Witro
        //$user = User::where('firstName','Witro')->first();

        // Buscar usuario por ref (QR del comercial)
            if($ref){
                $user = User::find($ref);
            }

            // Si no existe o no viene ref → asignar Witro
            //CAMBIAR FALLO NO FUNCIONA SIN REF

            if(!$user){
               $user = User::where('firstName','Witro')->first();
            } 
        
        /*$account = Account::create([
            'name' => $client->name,
            'CIF' => $client->CIF,
            'phone' => $client->phone,
            'email' => $client->email,
            'billingInfo' => [
                'province' => $client->province ?? 'Cordoba',
                'locality' => $client->locality ?? 'Cordoba',
                'zipCode' => $client->zipCode ?? 'Cordoba',
                'address' => $client->address ?? 'Cordoba',

            ],
            'usersIds' => [
                $user->_id
            ]
        ]);
        */

        $cups = $client->CUPS
    ?? $client->order->CUPS
    ?? '';

        $opportunity = Opportunity::create([
            'name' => $client->name,
            'CIF' => $client->CIF ?? '',
            'phone' => $client->phone ?? '',
            'landLinePhone' => '',
            'email' => $client->email ?? '',
            'website' => '',
            'sector' => '',
            'source' => 'openComparator',
            'status' => '',

            'contact' => [
                'value' => '',
                'isFromContacts' => ''
            ],

            'position' => '',
            'observations' => '',

            'billingInfo' => [
                'community' => $client->community ?? '',
                'province' => $client->province ?? '',
                'locality' => $client->locality ?? '',
                'address' => $client->address ?? '',
                'postal' => $client->zipCode ?? '',
            ],

            'customFields' => [],
            'docs' => $docs,

            'order' => [
                'name' => '',
                'productType' => $type === 'telephony' ? 'ct' : 'cl',
                'marketer' => $offer->marketer ?? '',
                'fee' => $type === 'telephony'
                    ? ($serviceType ?? '')
                    : ($offer->fee ?? ''),
                'product' => $offer->product ?? '',
                'CUPS' => $cups,
                'direc' => $client->address ?? '',
                'zip' => $client->zipCode ?? '',
                'town' => $client->locality ?? '',
                'province' => $client->province ?? ''
            ],

            'usersIds' => [
                $user->_id
            ],

            'createdBy' => $user->_id,
            'createdAt' => now()->format('Y-m-d H:i:s'),

            // Sorteo QR
            'raffle_campaign' => null,
            'raffle_number' => null,
            'raffle_enabled' => false,
            'raffle_already_registered' => false,
            'raffle_email_sent_at' => null,
            'raffle_email' => null
        ]);


        $raffle = $this->handleQrRaffle($ref, $client, $opportunity);

            $currentHost = $request->getHost();
            $enterprise = Enterprise::where('url', $currentHost)->first();

            // Dominio dinámico
            $enterpriseUrl = $enterprise->url ?? $user->enterprise->url ?? 'crm.zocoenergia.com';

            // URL final a la oportunidad
            $url = 'https://' . $enterpriseUrl . '/opportunities/' . $opportunity->_id;

            // Email del subdominio
            $subdomainEmail = null;

            if ($enterprise) {
                $subdomainEmail = $enterprise->notification_email ?: $enterprise->email;
            }

            // Email personal del comercial asociado al QR
            $commercialEmail = null;

            if ($ref) {
                $qrUser = User::find($ref);

                if ($qrUser && !empty($qrUser->email)) {
                    $commercialEmail = $qrUser->email;
                }
            }

            // Destinatarios reales: subdominio + comercial
            $emailRecipients = array_values(array_unique(array_filter([
                $subdomainEmail,
                $commercialEmail,
            ])));

            // El email principal será el del subdominio.
            // Si por lo que sea no hay email de subdominio, se manda al comercial.
            // Nunca caemos a soporte@zocoenergia.com en registerOrder.
            $emailTo = $subdomainEmail ?: $commercialEmail;

            $mailName = null;

            if ($enterprise && !empty($enterprise->mailConfig)) {
                $mailName = strtoupper($enterprise->mailConfig);

                if (env("MAIL_USERNAME_" . $mailName) && env("MAIL_PASSWORD_" . $mailName)) {
                    config([
                        'mail.mailers.smtp.host' => env('MAIL_HOST_' . $mailName, env('MAIL_HOST')),
                        'mail.mailers.smtp.port' => env('MAIL_PORT_' . $mailName, env('MAIL_PORT', 587)),
                        'mail.mailers.smtp.username' => env('MAIL_USERNAME_' . $mailName),
                        'mail.mailers.smtp.password' => env('MAIL_PASSWORD_' . $mailName),
                        'mail.mailers.smtp.encryption' => env('MAIL_ENCRYPTION_' . $mailName, env('MAIL_ENCRYPTION', 'tls')),
                        'mail.from.address' => env('MAIL_FROM_ADDRESS_' . $mailName, env('MAIL_FROM_ADDRESS')),
                        'mail.from.name' => env('MAIL_FROM_NAME_' . $mailName, env('MAIL_FROM_NAME')),
                    ]);

                    Mail::purge('smtp');
                }
            }

            $isFidelity = $mailName === 'FIDELITY360';

            try {
                Mail::html(
                    "
                    <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>

                        <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                            <h2 style='color:#2c3e50; margin-bottom:20px;'>
                                Nueva oportunidad desde comparador ⚡
                            </h2>

                            <p><strong>Nombre:</strong> " . e($client->name ?? '-') . "</p>
                            <p><strong>Email:</strong> " . e($client->email ?? '-') . "</p>
                            <p><strong>Teléfono:</strong> " . e($client->phone ?? '-') . "</p>
                            <p><strong>CIF:</strong> " . e($client->CIF ?? '-') . "</p>

                            <hr style='margin:20px 0;'>

                            <p><strong>Comercializadora:</strong> " . e($offer->marketer ?? '-') . "</p>
                            <p><strong>Producto:</strong> " . e($offer->product ?? '-') . "</p>
                            <p><strong>Tarifa:</strong> " . e($offer->fee ?? '-') . "</p>
                            <p><strong>CUPS:</strong> " . e($client->CUPS ?? '-') . "</p>

                            <div style='text-align:center; margin-top:30px;'>
                                <a href='{$url}' style='
                                    display:inline-block;
                                    padding:12px 25px;
                                    background:#f59e0b;
                                    color:#ffffff;
                                    text-decoration:none;
                                    border-radius:6px;
                                    font-weight:bold;
                                    font-size:14px;
                                '>
                                    🔍 Ver oportunidad
                                </a>
                            </div>

                            <p style='margin-top:30px; font-size:12px; color:#888; text-align:center;'>
                                Sistema automático de notificaciones
                            </p>

                        </div>
                    </div>
                    ",
                    function ($message) use ($emailTo, $commercialEmail) {
                        $message->from(config('mail.from.address'), config('mail.from.name'))
                                ->to($emailTo);

                        if (!empty($commercialEmail)) {
                            $message->bcc($commercialEmail);
                        }

                        $message->subject('Nueva oportunidad desde comparador');
                    }
                );

                Log::info('Email registerOrder openComparator enviado', [
                    'host_request' => $currentHost,
                    'enterprise_id' => $enterprise->_id ?? null,
                    'enterprise_url' => $enterprise->url ?? null,
                    'enterprise_mail_config' => $enterprise->mailConfig ?? null,
                    'email_to' => $emailTo,
                    'commercial_email_bcc' => $commercialEmail,
                    'from' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'mail_host' => config('mail.mailers.smtp.host'),
                    'mail_username' => config('mail.mailers.smtp.username'),
                    'is_fidelity' => $isFidelity,
                ]);

            } catch (\Throwable $e) {
                Log::error('Fallo al enviar email comparador', [
                    'error' => $e->getMessage(),
                    'email_to' => $emailRecipients,
                    'email_principal' => $emailTo,
                    'email_comercial' => $commercialEmail,
                    'from' => config('mail.from.address'),
                    'from_name' => config('mail.from.name'),
                    'mail_host' => config('mail.mailers.smtp.host'),
                    'mail_port' => config('mail.mailers.smtp.port'),
                    'mail_username' => config('mail.mailers.smtp.username'),
                    'is_fidelity' => $isFidelity,
                ]);

                Log::warning('La oportunidad se creó pero el email no se envió correctamente');
            }

        // 10. Respuesta final
        return response()->json([
            'success' => true,
            'message' => 'Solicitud registrada correctamente.',
            'data' => [
                'assigned_to' => $assignedTo,
                'raffle' => $raffle,
                //'user_id' => $user->_id,
                'client' => [
                    'name' => $client->name ?? null,
                    'email' => $client->email ?? null,
                    'phone' => $client->phone ?? null,
                    'CIF' => $client->CIF ?? null,
                ],
                'offer' => [
                    'marketer' => $offer->marketer ?? null,
                    'product' => $offer->product ?? null,
                    'fee' => $offer->fee ?? null,
                ],
                'invoice_path' => $invoicePath,
            ],
        ], 200);
    }



    public function registerAlarmOpportunity(Request $request): JsonResponse
    {
        $request->validate([
            'data' => ['required', 'string'],
            'ref' => ['nullable', 'string'],
        ]);

        $data = json_decode($request->input('data'));

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos de la solicitud no son válidos.',
            ], 422);
        }

        $validator = Validator::make([
            'name' => $data->name ?? null,
            'email' => $data->email ?? null,
            'phone' => $data->phone ?? null,
        ], [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos de contacto no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $ref = $request->input('ref');
        $user = null;

        if ($ref) {
            $user = User::find($ref);
        }

        if (!$user) {
            $user = User::where('firstName', 'Witro')->first();
        }

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No se ha encontrado usuario responsable para asignar la oportunidad.',
            ], 422);
        }

        $name = trim($data->name ?? '');

        if ($name === '') {
            $name = 'Lead alarmas';
        }

        $observations = "Solicitud de alarma desde comparador abierto.\n\n";
        $observations .= "Tipo de protección: " . ($data->protectType ?? '-') . "\n";
        $observations .= "Tipo de inmueble: " . ($data->propertyType ?? '-') . "\n";
        $observations .= "Tiene alarma actualmente: " . (($data->hasAlarm ?? null) === true ? 'Sí' : (($data->hasAlarm ?? null) === false ? 'No' : '-')) . "\n";
        $observations .= "Tiene mascota: " . (($data->hasPet ?? null) === true ? 'Sí' : (($data->hasPet ?? null) === false ? 'No' : '-')) . "\n";
        $observations .= "Tiene suministro eléctrico: " . (($data->electricity ?? null) === true ? 'Sí' : (($data->electricity ?? null) === false ? 'No' : '-')) . "\n";
        $observations .= "Urgencia: " . ($data->urgency ?? '-') . "\n";

        if (!empty($data->notes)) {
            $observations .= "\nObservaciones del cliente:\n" . $data->notes;
        }

        $opportunity = Opportunity::create([
            'name' => $name,
            'CIF' => '',
            'phone' => $data->phone ?? '',
            'landLinePhone' => '',
            'email' => $data->email ?? '',
            'website' => '',
            'sector' => '',
            'source' => 'openComparator',
            'status' => '',

            'contact' => [
                'value' => '',
                'isFromContacts' => ''
            ],

            'position' => '',
            'observations' => $observations,

            'billingInfo' => [
                'community' => '',
                'province' => '',
                'locality' => '',
                'address' => '',
                'postal' => '',
            ],

            'customFields' => [
                [
                    'name' => 'Tipo de oportunidad',
                    'value' => 'Alarmas',
                ],
                [
                    'name' => 'Tipo de protección',
                    'value' => $data->protectType ?? '',
                ],
                [
                    'name' => 'Tipo de inmueble',
                    'value' => $data->propertyType ?? '',
                ],
                [
                    'name' => 'Tiene alarma',
                    'value' => ($data->hasAlarm ?? null) === true ? 'Sí' : (($data->hasAlarm ?? null) === false ? 'No' : ''),
                ],
                [
                    'name' => 'Tiene mascota',
                    'value' => ($data->hasPet ?? null) === true ? 'Sí' : (($data->hasPet ?? null) === false ? 'No' : ''),
                ],
                [
                    'name' => 'Suministro eléctrico',
                    'value' => ($data->electricity ?? null) === true ? 'Sí' : (($data->electricity ?? null) === false ? 'No' : ''),
                ],
                [
                    'name' => 'Urgencia',
                    'value' => $data->urgency ?? '',
                ],
            ],

            'docs' => [],

            'order' => [
                'name' => 'Solicitud alarma',
                'productType' => 'alarm',
                'marketer' => 'Alarmas',
                'fee' => $data->urgency ?? '',
                'product' => $data->protectType ?? '',
                'CUPS' => '',
                'direc' => '',
                'zip' => '',
                'town' => '',
                'province' => '',
            ],

            'usersIds' => [
                $user->_id
            ],

            'createdBy' => $user->_id,
            'createdAt' => now()->format('Y-m-d H:i:s'),
        ]);

        $enterpriseUrl = $user->enterprise->url ?? 'crm.zocoenergia.com';
        $url = 'https://' . $enterpriseUrl . '/opportunities/' . $opportunity->_id;

        $currentHost = $request->getHost();
        $enterprise = Enterprise::where('url', $currentHost)->first();

        $emailTo = null;

        if ($enterprise) {
            $emailTo = $enterprise->notification_email ?: $enterprise->email;
        }

        $emailTo = $emailTo ?: 'soporte@zocoenergia.com';

        try {
            Mail::html(
                "
                <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>
                    <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                        <h2 style='color:#2c3e50; margin-bottom:20px;'>
                            Nueva oportunidad de alarmas 🚨
                        </h2>

                        <p><strong>Nombre:</strong> " . e($data->name ?? '-') . "</p>
                        <p><strong>Email:</strong> " . e($data->email ?? '-') . "</p>
                        <p><strong>Teléfono:</strong> " . e($data->phone ?? '-') . "</p>

                        <hr style='margin:20px 0;'>

                        <p><strong>Tipo de protección:</strong> " . e($data->protectType ?? '-') . "</p>
                        <p><strong>Tipo de inmueble:</strong> " . e($data->propertyType ?? '-') . "</p>
                        <p><strong>Tiene alarma:</strong> " . ((($data->hasAlarm ?? null) === true) ? 'Sí' : ((($data->hasAlarm ?? null) === false) ? 'No' : '-')) . "</p>
                        <p><strong>Tiene mascota:</strong> " . ((($data->hasPet ?? null) === true) ? 'Sí' : ((($data->hasPet ?? null) === false) ? 'No' : '-')) . "</p>
                        <p><strong>Suministro eléctrico:</strong> " . ((($data->electricity ?? null) === true) ? 'Sí' : ((($data->electricity ?? null) === false) ? 'No' : '-')) . "</p>
                        <p><strong>Urgencia:</strong> " . e($data->urgency ?? '-') . "</p>
                        <p><strong>Observaciones:</strong> " . nl2br(e($data->notes ?? '-')) . "</p>

                        <div style='text-align:center; margin-top:30px;'>
                            <a href='{$url}' style='
                                display:inline-block;
                                padding:12px 25px;
                                background:#2563eb;
                                color:#ffffff;
                                text-decoration:none;
                                border-radius:6px;
                                font-weight:bold;
                                font-size:14px;
                            '>
                                Ver oportunidad
                            </a>
                        </div>

                        <p style='margin-top:30px; font-size:12px; color:#888; text-align:center;'>
                            Sistema automático de notificaciones
                        </p>

                    </div>
                </div>
                ",
                function ($message) use ($emailTo) {
                    $message->to($emailTo)
                        ->subject('Nueva oportunidad de alarmas');
                }
            );
        } catch (\Throwable $e) {
            Log::error('Fallo al enviar email de oportunidad de alarmas', [
                'error' => $e->getMessage(),
                'email_to' => $emailTo
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud registrada correctamente.',
            'data' => [
                'opportunity_id' => $opportunity->_id,
                'client' => [
                    'name' => $data->name ?? null,
                    'email' => $data->email ?? null,
                    'phone' => $data->phone ?? null,
                ],
            ],
        ], 200);
    }


   public function registerAutoconsumoOpportunity(Request $request): JsonResponse
{
    $request->validate([
        'data' => ['required', 'string'],
        'ref' => ['nullable', 'string'],
        'invoice' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],
    ]);

    $data = json_decode($request->input('data'));

    if (!$data) {
        return response()->json([
            'success' => false,
            'message' => 'Los datos de la solicitud no son válidos.',
        ], 422);
    }

    $validator = Validator::make([
        'name' => $data->name ?? null,
        'email' => $data->email ?? null,
        'phone' => $data->phone ?? null,
    ], [
        'name' => ['nullable', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:30'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Los datos de contacto no son válidos.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $ref = $request->input('ref');
    $user = null;

    if ($ref) {
        $user = User::find($ref);
    }

    if (!$user) {
        $user = User::where('firstName', 'Witro')->first();
    }

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'No se ha encontrado usuario responsable para asignar la oportunidad.',
        ], 422);
    }

    $docs = [];
    $invoicePath = null;

    if ($request->hasFile('invoice')) {
        $invoice = $request->file('invoice');

        if ($invoice && $invoice->isValid()) {
            $directory = public_path('assets/open-comparator/invoices');

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $originalName = $invoice->getClientOriginalName();
            $mimeType = $invoice->getMimeType();
            $extension = $invoice->getClientOriginalExtension();
            $fileName = Str::random(40) . '.' . $extension;

            $invoice->move($directory, $fileName);

            $invoicePath = 'open-comparator/invoices/' . $fileName;

            $docs[] = [
                'title' => 'Factura autoconsumo',
                'defaultTitle' => $originalName,
                'value' => $invoicePath,
                'fileValue' => '',
                'icon' => $this->getIconForFileType($mimeType),
                'id' => (string) Str::uuid(),
                'errors' => [],
                'createdAt' => now()->format('Y-m-d H:i:s'),
            ];
        }
    }

    $name = trim($data->name ?? '');

    if ($name === '') {
        $name = 'Lead autoconsumo';
    }

    $observations = "Solicitud de presupuesto de autoconsumo desde comparador abierto.\n\n";
    $observations .= "Nombre: " . ($data->name ?? '-') . "\n";
    $observations .= "Email: " . ($data->email ?? '-') . "\n";
    $observations .= "Teléfono: " . ($data->phone ?? '-') . "\n";
    $observations .= "Factura adjunta: " . ($invoicePath ? 'Sí' : 'No') . "\n";

    $opportunity = Opportunity::create([
        'name' => $name,
        'CIF' => '',
        'phone' => $data->phone ?? '',
        'landLinePhone' => '',
        'email' => $data->email ?? '',
        'website' => '',
        'sector' => '',
        'source' => 'openComparator',
        'status' => '',

        'contact' => [
            'value' => '',
            'isFromContacts' => ''
        ],

        'position' => '',
        'observations' => $observations,

        'billingInfo' => [
            'community' => '',
            'province' => '',
            'locality' => '',
            'address' => '',
            'postal' => '',
        ],

        'customFields' => [
            [
                'name' => 'Tipo de oportunidad',
                'value' => 'Autoconsumo',
            ],
            [
                'name' => 'Factura adjunta',
                'value' => $invoicePath ? 'Sí' : 'No',
            ],
        ],

        'docs' => $docs,

        'order' => [
            'name' => 'Solicitud autoconsumo',
            'productType' => 'autoconsumo',
            'marketer' => 'Autoconsumo',
            'fee' => '',
            'product' => 'Presupuesto autoconsumo',
            'CUPS' => '',
            'direc' => '',
            'zip' => '',
            'town' => '',
            'province' => '',
        ],

        'usersIds' => [
            $user->_id
        ],

        'createdBy' => $user->_id,
        'createdAt' => now()->format('Y-m-d H:i:s'),
    ]);

    $enterpriseUrl = $user->enterprise->url ?? 'crm.zocoenergia.com';
    $url = 'https://' . $enterpriseUrl . '/opportunities/' . $opportunity->_id;

    $currentHost = $request->getHost();
    $enterprise = Enterprise::where('url', $currentHost)->first();

    $emailTo = null;

    if ($enterprise) {
        $emailTo = $enterprise->notification_email ?: $enterprise->email;
    }

    $emailTo = $emailTo ?: 'soporte@zocoenergia.com';

    try {
        Mail::html(
            "
            <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>
                <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                    <h2 style='color:#2c3e50; margin-bottom:20px;'>
                        Nueva oportunidad de autoconsumo
                    </h2>

                    <p><strong>Nombre:</strong> " . e($data->name ?? '-') . "</p>
                    <p><strong>Email:</strong> " . e($data->email ?? '-') . "</p>
                    <p><strong>Teléfono:</strong> " . e($data->phone ?? '-') . "</p>

                    <hr style='margin:20px 0;'>

                    <p><strong>Factura adjunta:</strong> " . ($invoicePath ? 'Sí' : 'No') . "</p>

                    <div style='text-align:center; margin-top:30px;'>
                        <a href='{$url}' style='
                            display:inline-block;
                            padding:12px 25px;
                            background:#f59e0b;
                            color:#ffffff;
                            text-decoration:none;
                            border-radius:6px;
                            font-weight:bold;
                            font-size:14px;
                        '>
                            Ver oportunidad
                        </a>
                    </div>

                    <p style='margin-top:30px; font-size:12px; color:#888; text-align:center;'>
                        Sistema automático de notificaciones
                    </p>

                </div>
            </div>
            ",
            function ($message) use ($emailTo) {
                $message->to($emailTo)
                    ->subject('Nueva oportunidad de autoconsumo');
            }
        );
    } catch (\Throwable $e) {
        Log::error('Fallo al enviar email de oportunidad de autoconsumo', [
            'error' => $e->getMessage(),
            'email_to' => $emailTo
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Solicitud registrada correctamente.',
        'data' => [
            'opportunity_id' => $opportunity->_id,
            'invoice_path' => $invoicePath,
            'client' => [
                'name' => $data->name ?? null,
                'email' => $data->email ?? null,
                'phone' => $data->phone ?? null,
            ],
        ],
    ], 200);
}


public function registerCarChargerOpportunity(Request $request): JsonResponse
{
    $request->validate([
        'data' => ['required', 'string'],
        'ref' => ['nullable', 'string'],
    ]);

    $data = json_decode($request->input('data'));

    if (!$data) {
        return response()->json([
            'success' => false,
            'message' => 'Los datos de la solicitud no son válidos.',
        ], 422);
    }

    $cableMeters = '';

    if (isset($data->cableMeters) && $data->cableMeters !== null && $data->cableMeters !== '') {
        $cableMeters = (string) $data->cableMeters;
    }

    $validator = Validator::make([
        'installationType' => $data->installationType ?? null,
        'cableMeters' => $cableMeters,
        'hasAutoconsumo' => $data->hasAutoconsumo ?? null,
        'name' => $data->name ?? null,
        'email' => $data->email ?? null,
        'phone' => $data->phone ?? null,
    ], [
        'installationType' => ['nullable', 'string', 'max:100'],
        'cableMeters' => ['nullable', 'string', 'max:50'],
        'hasAutoconsumo' => ['nullable', 'string', 'max:50'],
        'name' => ['nullable', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:30'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Los datos de contacto no son válidos.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $ref = $request->input('ref');
    $user = null;

    if ($ref) {
        $user = User::find($ref);
    }

    if (!$user) {
        $user = User::where('firstName', 'Witro')->first();
    }

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'No se ha encontrado usuario responsable para asignar la oportunidad.',
        ], 422);
    }

    $installationTypeText = '-';

    if (($data->installationType ?? '') === 'house') {
        $installationTypeText = 'Casa / vivienda';
    }

    if (($data->installationType ?? '') === 'community') {
        $installationTypeText = 'Garaje comunitario';
    }

    $hasAutoconsumoText = '-';

    if (($data->hasAutoconsumo ?? '') === 'yes') {
        $hasAutoconsumoText = 'Sí';
    }

    if (($data->hasAutoconsumo ?? '') === 'no') {
        $hasAutoconsumoText = 'No';
    }

    if (($data->hasAutoconsumo ?? '') === 'unknown') {
        $hasAutoconsumoText = 'No lo sé';
    }

    $name = trim($data->name ?? '');

    if ($name === '') {
        $name = 'Lead cargador coche eléctrico';
    }


    $observations = "Solicitud de presupuesto de cargador de coche eléctrico desde comparador abierto.\n\n";
    $observations .= "Nombre: " . ($data->name ?? '-') . "\n";
    $observations .= "Email: " . ($data->email ?? '-') . "\n";
    $observations .= "Teléfono: " . ($data->phone ?? '-') . "\n";
    $observations .= "Tipo de instalación: " . $installationTypeText . "\n";
    $observations .= "Metros aproximados de cable: " . ($cableMeters !== '' ? $cableMeters : '-') . "\n";
    $observations .= "Tiene autoconsumo: " . $hasAutoconsumoText . "\n";

    $opportunity = Opportunity::create([
        'name' => $name,
        'CIF' => '',
        'phone' => $data->phone ?? '',
        'landLinePhone' => '',
        'email' => $data->email ?? '',
        'website' => '',
        'sector' => '',
        'source' => 'openComparator',
        'status' => '',

        'contact' => [
            'value' => '',
            'isFromContacts' => ''
        ],

        'position' => '',
        'observations' => $observations,

        'billingInfo' => [
            'community' => '',
            'province' => '',
            'locality' => '',
            'address' => '',
            'postal' => '',
        ],

        'customFields' => [
            [
                'name' => 'Tipo de oportunidad',
                'value' => 'Cargador coche eléctrico',
            ],
            [
                'name' => 'Tipo de instalación',
                'value' => $installationTypeText,
            ],
            [
                'name' => 'Metros aproximados de cable',
                'value' => $cableMeters,
            ],
            [
                'name' => 'Tiene autoconsumo',
                'value' => $hasAutoconsumoText,
            ],
        ],

        'docs' => [],

        'order' => [
            'name' => 'Solicitud cargador coche eléctrico',
            'productType' => 'cargador_coche',
            'marketer' => 'Cargador coche eléctrico',
            'fee' => '',
            'product' => 'Presupuesto cargador coche eléctrico',
            'CUPS' => '',
            'direc' => '',
            'zip' => '',
            'town' => '',
            'province' => '',
        ],

        'usersIds' => [
            $user->_id
        ],

        'createdBy' => $user->_id,
        'createdAt' => now()->format('Y-m-d H:i:s'),
    ]);

    $enterpriseUrl = $user->enterprise->url ?? 'crm.zocoenergia.com';
    $url = 'https://' . $enterpriseUrl . '/opportunities/' . $opportunity->_id;

    $currentHost = $request->getHost();
    $enterprise = Enterprise::where('url', $currentHost)->first();

    $emailTo = null;

    if ($enterprise) {
        $emailTo = $enterprise->notification_email ?: $enterprise->email;
    }

    $emailTo = $emailTo ?: 'soporte@zocoenergia.com';

    try {
        Mail::html(
            "
            <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>
                <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                    <h2 style='color:#2c3e50; margin-bottom:20px;'>
                        Nueva oportunidad de cargador de coche eléctrico
                    </h2>

                    <p><strong>Nombre:</strong> " . e($data->name ?? '-') . "</p>
                    <p><strong>Email:</strong> " . e($data->email ?? '-') . "</p>
                    <p><strong>Teléfono:</strong> " . e($data->phone ?? '-') . "</p>

                    <hr style='margin:20px 0;'>

                    <p><strong>Tipo de instalación:</strong> " . e($installationTypeText) . "</p>
                    <p><strong>Metros aproximados de cable:</strong> " . e($cableMeters !== '' ? $cableMeters : '-') . "</p>
                    <p><strong>Tiene autoconsumo:</strong> " . e($hasAutoconsumoText) . "</p>

                    <div style='text-align:center; margin-top:30px;'>
                        <a href='{$url}' style='
                            display:inline-block;
                            padding:12px 25px;
                            background:#2563eb;
                            color:#ffffff;
                            text-decoration:none;
                            border-radius:6px;
                            font-weight:bold;
                            font-size:14px;
                        '>
                            Ver oportunidad
                        </a>
                    </div>

                    <p style='margin-top:30px; font-size:12px; color:#888; text-align:center;'>
                        Sistema automático de notificaciones
                    </p>

                </div>
            </div>
            ",
            function ($message) use ($emailTo) {
                $message->to($emailTo)
                    ->subject('Nueva oportunidad de cargador de coche eléctrico');
            }
        );
    } catch (\Throwable $e) {
        Log::error('Fallo al enviar email de oportunidad de cargador de coche eléctrico', [
            'error' => $e->getMessage(),
            'email_to' => $emailTo
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Solicitud registrada correctamente.',
        'data' => [
            'opportunity_id' => $opportunity->_id,
            'client' => [
                'name' => $data->name ?? null,
                'email' => $data->email ?? null,
                'phone' => $data->phone ?? null,
            ],
        ],
    ], 200);
}



public function registerClaimOpportunity(Request $request): JsonResponse
{
    $request->validate([
        'data' => ['required', 'string'],
        'ref' => ['nullable', 'string'],
    ]);

    $data = json_decode($request->input('data'));

    if (!$data) {
        return response()->json([
            'success' => false,
            'message' => 'Los datos de la reclamación no son válidos.',
        ], 422);
    }

    $validator = Validator::make([
        'name' => $data->name ?? null,
        'email' => $data->email ?? null,
        'phone' => $data->phone ?? null,
        'claimType' => $data->claimType ?? null,
        'message' => $data->message ?? null,
    ], [
        'name' => ['nullable', 'string', 'max:255'],
        'email' => ['nullable', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:30'],
        'claimType' => ['nullable', 'string', 'max:100'],
        'message' => ['nullable', 'string', 'max:3000'],
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Los datos de contacto no son válidos.',
            'errors' => $validator->errors(),
        ], 422);
    }

    $ref = $request->input('ref');
    $user = null;

    if ($ref) {
        $user = User::find($ref);
    }

    if (!$user) {
        $user = User::where('firstName', 'Witro')->first();
    }

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'No se ha encontrado usuario responsable para asignar la oportunidad.',
        ], 422);
    }

    $claimTypeText = '-';

    if (($data->claimType ?? '') === 'billing') {
        $claimTypeText = 'Factura';
    }

    if (($data->claimType ?? '') === 'contract') {
        $claimTypeText = 'Contrato';
    }

    if (($data->claimType ?? '') === 'supply') {
        $claimTypeText = 'Suministro';
    }

    if (($data->claimType ?? '') === 'other') {
        $claimTypeText = 'Otro';
    }

    $name = trim($data->name ?? '');

    if ($name === '') {
        $name = 'Lead reclamación';
    }

    $messageText = trim($data->message ?? '');

    $observations = "Reclamación recibida desde comparador abierto.\n\n";
    $observations .= "Nombre: " . ($data->name ?? '-') . "\n";
    $observations .= "Email: " . ($data->email ?? '-') . "\n";
    $observations .= "Teléfono: " . ($data->phone ?? '-') . "\n";
    $observations .= "Tipo de reclamación: " . $claimTypeText . "\n\n";
    $observations .= "Mensaje de la reclamación:\n";
    $observations .= $messageText !== '' ? $messageText : '-';

    $opportunity = Opportunity::create([
        'name' => $name,
        'CIF' => '',
        'phone' => $data->phone ?? '',
        'landLinePhone' => '',
        'email' => $data->email ?? '',
        'website' => '',
        'sector' => '',
        'source' => 'openComparator',
        'status' => '',

        'contact' => [
            'value' => '',
            'isFromContacts' => ''
        ],

        'position' => '',
        'observations' => $observations,

        'billingInfo' => [
            'community' => '',
            'province' => '',
            'locality' => '',
            'address' => '',
            'postal' => '',
        ],

        'customFields' => [
            [
                'name' => 'Tipo de oportunidad',
                'value' => 'Reclamación',
            ],
            [
                'name' => 'Tipo de reclamación',
                'value' => $claimTypeText,
            ],
        ],

        'docs' => [],

        'order' => [
            'name' => 'Reclamación',
            'productType' => 'reclamacion',
            'marketer' => 'Reclamaciones',
            'fee' => '',
            'product' => 'Gestión de reclamación',
            'CUPS' => '',
            'direc' => '',
            'zip' => '',
            'town' => '',
            'province' => '',
        ],

        'usersIds' => [
            $user->_id
        ],

        'createdBy' => $user->_id,
        'createdAt' => now()->format('Y-m-d H:i:s'),
    ]);

    $enterpriseUrl = $user->enterprise->url ?? 'crm.zocoenergia.com';
    $url = 'https://' . $enterpriseUrl . '/opportunities/' . $opportunity->_id;

    $currentHost = $request->getHost();
    $enterprise = Enterprise::where('url', $currentHost)->first();

    $emailTo = null;

    if ($enterprise) {
        $emailTo = $enterprise->notification_email ?: $enterprise->email;
    }

    $emailTo = $emailTo ?: 'soporte@zocoenergia.com';

    try {
        Mail::html(
            "
            <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>
                <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                    <h2 style='color:#2c3e50; margin-bottom:20px;'>
                        Nueva reclamación recibida
                    </h2>

                    <p><strong>Nombre:</strong> " . e($data->name ?? '-') . "</p>
                    <p><strong>Email:</strong> " . e($data->email ?? '-') . "</p>
                    <p><strong>Teléfono:</strong> " . e($data->phone ?? '-') . "</p>

                    <hr style='margin:20px 0;'>

                    <p><strong>Tipo de reclamación:</strong> " . e($claimTypeText) . "</p>
                    <p><strong>Mensaje:</strong></p>
                    <p style='white-space:pre-line;'>" . nl2br(e($messageText !== '' ? $messageText : '-')) . "</p>

                    <div style='text-align:center; margin-top:30px;'>
                        <a href='{$url}' style='
                            display:inline-block;
                            padding:12px 25px;
                            background:#2563eb;
                            color:#ffffff;
                            text-decoration:none;
                            border-radius:6px;
                            font-weight:bold;
                            font-size:14px;
                        '>
                            Ver oportunidad
                        </a>
                    </div>

                    <p style='margin-top:30px; font-size:12px; color:#888; text-align:center;'>
                        Sistema automático de notificaciones
                    </p>

                </div>
            </div>
            ",
            function ($message) use ($emailTo) {
                $message->to($emailTo)
                    ->subject('Nueva reclamación recibida');
            }
        );
    } catch (\Throwable $e) {
        Log::error('Fallo al enviar email de reclamación', [
            'error' => $e->getMessage(),
            'email_to' => $emailTo
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Reclamación registrada correctamente.',
        'data' => [
            'opportunity_id' => $opportunity->_id,
            'client' => [
                'name' => $data->name ?? null,
                'email' => $data->email ?? null,
                'phone' => $data->phone ?? null,
            ],
        ],
    ], 200);
}


public function getFloatingContact(Request $request): JsonResponse
{
    $ref = $request->input('ref');

    $user = null;

    if ($ref) {
        $user = User::find($ref);
    }

    if (!$user) {
        $user = User::where('firstName', 'Witro')->first();
    }

    $phone = $user?->phone ?: '653062438';

    return response()->json([
        'success' => true,
        'data' => [
            'phone' => $phone,
            'whatsapp' => $phone,
            'user_id' => $user->_id ?? null,
        ],
    ]);
}

    private function getIconForFileType(?string $mime): string
    {
        if (!$mime) {
            return 'fa-file';
        }

        if (str_contains($mime, 'pdf')) {
            return 'fa-file-pdf';
        }

        if (str_contains($mime, 'image')) {
            return 'fa-file-image';
        }

        if (str_contains($mime, 'word')) {
            return 'fa-file-word';
        }

        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) {
            return 'fa-file-excel';
        }

        if (str_contains($mime, 'zip') || str_contains($mime, 'compressed')) {
            return 'fa-file-zipper';
        }

        return 'fa-file';
    }


    private function handleQrRaffle($ref, $client, Opportunity $opportunity): array
{
    $result = [
        'enabled' => false,
        'number' => null,
        'campaign' => null,
        'already_registered' => false,
        'limit_reached' => false,
        'email_sent' => false,
    ];

    try {
        $ref = (string) $ref;

        // Si el ref no tiene sorteo configurado, no se hace nada
        if (!isset(self::RAFFLES[$ref])) {
            return $result;
        }

        $raffleConfig = self::RAFFLES[$ref];

        $campaign = $raffleConfig['campaign'];
        $maxNumber = $raffleConfig['max_number'];
        $prize = $raffleConfig['prize'];
        $emailSubject = $raffleConfig['email_subject'];

        $email = strtolower(trim($client->email ?? ''));

        // Sin email no se puede enviar el número del sorteo
        if (empty($email)) {
            return $result;
        }

        $result['enabled'] = true;
        $result['campaign'] = $campaign;

        // Comprobar si ese email ya tiene número en esta campaña concreta
        $existingOpportunity = Opportunity::where('raffle_campaign', $campaign)
            ->where('raffle_email', $email)
            ->whereNotNull('raffle_number')
            ->first();

        if ($existingOpportunity) {
            $opportunity->raffle_campaign = $campaign;
            $opportunity->raffle_number = $existingOpportunity->raffle_number;
            $opportunity->raffle_enabled = false;
            $opportunity->raffle_already_registered = true;
            $opportunity->raffle_email = $email;
            $opportunity->save();

            $result['number'] = $existingOpportunity->raffle_number;
            $result['already_registered'] = true;

            return $result;
        }

        // Contar solo participaciones válidas de esta campaña concreta
        $currentCount = Opportunity::where('raffle_campaign', $campaign)
            ->where('raffle_enabled', true)
            ->whereNotNull('raffle_number')
            ->count();

        $nextNumber = $currentCount + 1;

        // Si ya se ha alcanzado el límite de esta campaña, no se asigna número
        if ($nextNumber > $maxNumber) {
            $opportunity->raffle_campaign = $campaign;
            $opportunity->raffle_enabled = false;
            $opportunity->raffle_already_registered = false;
            $opportunity->raffle_email = $email;
            $opportunity->save();

            $result['limit_reached'] = true;

            return $result;
        }

        // Asignar número a esta oportunidad
        $opportunity->raffle_campaign = $campaign;
        $opportunity->raffle_number = $nextNumber;
        $opportunity->raffle_enabled = true;
        $opportunity->raffle_already_registered = false;
        $opportunity->raffle_email = $email;
        $opportunity->save();

        $result['number'] = $nextNumber;

        // Enviar email al cliente con su número
        Mail::html(
            "
            <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>
                <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                    <h2 style='color:#2c3e50; margin-bottom:20px;'>
                        Participación registrada
                    </h2>

                    <p>Hola " . e($client->name ?? '') . ",</p>

                    <p>
                        Gracias por usar nuestro comparador.
                        Su número para el sorteo de " . e($prize) . " es:
                    </p>

                    <div style='text-align:center; margin:30px 0;'>
                        <span style='display:inline-block; font-size:42px; font-weight:bold; color:#f59e0b;'>
                            Nº " . str_pad($nextNumber, 4, '0', STR_PAD_LEFT) . "
                        </span>
                    </div>

                    <p>
                        Guarde este correo como justificante de participación.
                    </p>

                    <p style='margin-top:30px; font-size:12px; color:#888; text-align:center;'>
                        Sistema automático de participación
                    </p>

                </div>
            </div>
            ",
            function ($message) use ($email, $emailSubject) {
                $message->to($email)
                    ->subject($emailSubject);
            }
        );

        $opportunity->raffle_email_sent_at = now()->format('Y-m-d H:i:s');
        $opportunity->save();

        $result['email_sent'] = true;

        return $result;

    } catch (\Throwable $e) {
        Log::error('Error al gestionar sorteo QR', [
            'error' => $e->getMessage(),
            'ref' => $ref,
            'email' => $client->email ?? null,
            'opportunity_id' => $opportunity->_id ?? null,
        ]);

        return $result;
    }
    }
}