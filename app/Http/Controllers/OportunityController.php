<?php

namespace App\Http\Controllers;

use App\Http\Models\Account;
use App\Http\Models\Contact;
use App\Http\Models\Email;
use App\Http\Models\Opportunity;
use App\Http\Models\User;
use App\Http\Models\WhatsAppSession;
use App\Http\Models\Enterprise;
use App\WhatsApp\WhapiDriver;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use MongoDB\BSON\UTCDateTime;
use App\Http\Models\Log as AuditLog;
use Illuminate\Support\Facades\Mail;

use App\Http\Models\Marketer;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class OportunityController extends Controller
{


    // funcion para guardar una oportunidad
    public function store(Request $request)
{
    $oportunity = json_decode($request['oportunity']);
    $userLogged = json_decode($request['userLogged']);

    // Recorro los campos customizados para ver si tiene alguno de tipo imagen
    $customFields = $oportunity->customFields ?? [];

    foreach ($customFields as $fieldInd => $field) {

        // Si es imagen
        if (($field->type ?? null) === 'image') {

            // Saco el archivo
            $file = $request['customFieldFile' . $fieldInd] ?? null;

            if ($file !== null && !is_string($file)) {
                // Creo el nombre de la imagen para guardar
                $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                $fieldImageName = time() . '_' . $fieldInd . '.' . $extension;

                // Guardo la imagen en local
                Storage::disk('opportunity')->put($fieldImageName, file_get_contents($file));

                // Meto el nombre en el campo value para registrarlo
                $field->value = $fieldImageName;

                // Borro el $field-fileImage que es donde está el archivo
                unset($field->imageFile);
            }
        }
    }

    $usersIds = !empty($oportunity->usersIds) ? $oportunity->usersIds : [$userLogged->_id];

    // --- Documentos (incluyendo PDF de presupuesto si se adjuntó) ---
    $docs = $oportunity->docs ?? [];
    foreach ($docs as $idx => $doc) {
        $docFile = $request->file("docFiles.$idx");
        if ($docFile) {
            $extension = pathinfo($docFile->getClientOriginalName(), PATHINFO_EXTENSION);
            $fileName = time() . '_' . $idx . '.' . $extension;
            Storage::disk('order')->put($fileName, file_get_contents($docFile));
            $docs[$idx]->value = $fileName;
        }
    }

    $opportunity = Opportunity::create([
        'name' => $oportunity->name ?? '',
        'CIF' => $oportunity->CIF ?? '',
        'phone' => $oportunity->phone ?? '',
        'landLinePhone' => $oportunity->landLinePhone ?? '',
        'email' => $oportunity->email ?? '',
        'website' => $oportunity->website ?? '',
        'sector' => $oportunity->sector ?? '',
        'source' => $oportunity->source ?? '',
        'status' => $oportunity->status ?? '',
        'contact' => [
            'value' => $oportunity->contact->value ?? '',
            'isFromContacts' => $oportunity->contact->isFromContacts ?? false,
        ],
        'position' => $oportunity->position ?? '',
        'observations' => $oportunity->observations ?? '',
        'billingInfo' => [
            'community' => $oportunity->billingInfo->community ?? '',
            'province' => $oportunity->billingInfo->province ?? '',
            'locality' => $oportunity->billingInfo->locality ?? '',
            'address' => $oportunity->billingInfo->address ?? '',
            'postal' => $oportunity->billingInfo->postal ?? '',
        ],
        'customFields' => $customFields,
        'docs' => $docs,
        'order' => $oportunity->order ?? [],
        'usersIds' => $usersIds,
        'createdBy' => $userLogged->_id,
        'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
    ]);

    // Creo el log
    AuditLogService::createOrDeleteOpportunity($opportunity, $userLogged, 'create');

    // Enviar email al propietario principal si corresponde al subdominio indicado
    if (!empty($usersIds[0])) {
        $this->sendOpportunityCreatedEmailToOwner($opportunity, $usersIds[0]);
    }

    return response()->json(['message' => 'La oportunidad ha sido creada correctamente'], 200);
}


        public function indexFilters($id, Request $request)
    {
        $user     = User::where('_id', $id)->first();
        if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 404);

        $userList = json_decode($request->input('userList', '[]'), true) ?? [];
        $usersIds = array_merge([$user['_id']], array_column($userList, '_id'));

        $pipeline = [
            ['$match' => ['usersIds' => ['$in' => $usersIds]]],
            ['$addFields' => [
                'originType' => [
                    '$switch' => [
                        'branches' => [
                            ['case' => ['$and' => [['$ne' => ['$metaAdId', null]], ['$ne' => ['$metaAdId', '']]]], 'then' => 'facebook'],
                            ['case' => ['$and' => [['$ne' => ['$cargacarId', null]], ['$ne' => ['$cargacarId', '']]]], 'then' => 'cargacar'],
                        ],
                        'default' => 'crm'
                    ]
                ],
                'sourceOrigin' => [
                    '$trim' => ['input' => ['$ifNull' => [
                        ['$switch' => [
                            'branches' => [
                                ['case' => ['$eq' => [['$type' => '$source'], 'object']], 'then' => ['$cond' => [['$eq' => ['$source.title', 'Personalizado']], ['$ifNull' => ['$source.custom', '']], ['$ifNull' => ['$source.title', '']]]]],
                                ['case' => ['$eq' => [['$type' => '$source'], 'string']], 'then' => '$source'],
                            ],
                            'default' => ''
                        ]],
                        ''
                    ]]]
                ],
            ]],
            ['$unwind' => '$usersIds'],
            ['$group' => [
                '_id'          => null,
                'agents'       => ['$addToSet' => '$usersIds'],
                'marketers'    => ['$addToSet' => '$order.marketer'],
                'productTypes' => ['$addToSet' => '$order.productType'],
                'originTypes'  => ['$addToSet' => '$originType'],
                'sourceOrigins'=> ['$addToSet' => '$sourceOrigin'],
                'fees'         => ['$addToSet' => '$order.fee'],
                'products'     => ['$addToSet' => '$order.product'],
            ]],
        ];

        $typeMap = ['root' => 'array', 'document' => 'array', 'array' => 'array'];
        $raw  = Opportunity::raw(fn($col) => $col->aggregate($pipeline, ['typeMap' => $typeMap])->toArray());
        $data = $raw[0] ?? ['agents' => [], 'marketers' => [], 'productTypes' => [], 'originTypes' => [], 'sourceOrigins' => [], 'fees' => [], 'products' => []];

        // Agentes con nombre
        $agents = [];
        foreach ($data['agents'] as $aid) {
            $aidStr = (string)$aid;
            $u = $aidStr === (string)$user['_id']
                ? $user
                : collect($userList)->first(fn($x) => (string)$x['_id'] === $aidStr);
            if ($u) {
                $agents[] = [
                    '_id'  => $aidStr,
                    'name' => trim(($u['firstName'] ?? '') . ' ' . ($u['lastName'] ?? ''))
                ];
            }
        }
        usort($agents, fn($a, $b) => strcmp($a['name'], $b['name']));

        // Origen
        $fixedOriginTypes = [
            ['code' => 'crm',      'title' => 'CRM'],
            ['code' => 'cargacar', 'title' => 'Cargacar'],
            ['code' => 'facebook', 'title' => 'Facebook'],
        ];
        $sourceOriginTypes = collect($data['sourceOrigins'] ?? [])
            ->map(fn($s) => trim((string)$s))->filter()->unique()->sort()
            ->map(fn($s) => ['code' => 'source::' . $s, 'title' => $s])
            ->values()->all();

        $fixedStatuses = [
            'Pendiente','Contactado','Contactado Whatssap','Mensaje enviado',
            'No contesta','Presupuesto Enviado','Pre. Bot','En seguimiento',
            'Aceptado','Fallido','Falso','Repetido','Sin estado',
            'Checklist terminado', 'Stand-by', 'Perdidos'
        ];

        return response()->json([
            'agents'       => $agents,
            'statuses'     => $fixedStatuses,
            'marketers'    => array_values(array_filter($data['marketers'] ?? [])),
            'productTypes' => array_values(array_filter($data['productTypes'] ?? [])),
            'tariffs'      => array_values(array_filter($data['fees'] ?? [])),
            'products'     => $data['products'] ?? [],
            'originTypes'  => array_values(array_merge($fixedOriginTypes, $sourceOriginTypes)),
        ]);
    }


    public function createNewMessage(Request $request)
    {
        $opportunityId = $request->input('opportunityId');
        $message       = $request->input('message');

        if (!$opportunityId || !$message || trim($message) === '') {
            return response()->json(['error' => 'Datos incompletos'], 422);
        }

        $opportunity = Opportunity::find($opportunityId);

        if (!$opportunity) {
            return response()->json(['error' => 'Oportunidad no encontrada'], 404);
        }

        $messages = $opportunity->messages ?? [];

        $newMessage = [
            'message' => trim($message),
            'date'    => Carbon::now()->format('Y-m-d H:i:s'),
            'creator' => (string) Auth::user()->_id,
        ];

        $messages[] = $newMessage;

        $opportunity->messages = $messages;
        $opportunity->save();

        return response()->json([
            'message' => 'Mensaje guardado correctamente',
            'data'    => $newMessage
        ], 200);
    }


    public function importCargacarHtmlExcel(Request $request)
    {

        $file = $request->file('file');
        $userLogged = Auth::user();
        $fullPath = $file->getRealPath();

        try {
            if (!$file || !$file->isValid()) {
                return response()->json(['success' => false, 'message' => 'No se ha recibido un archivo válido.'], 400);
            }

            $html = file_get_contents($fullPath);

            if (!$html || stripos($html, '<table') === false) {
                return response()->json(['success' => false, 'message' => 'El archivo no contiene una tabla HTML válida.'], 400);
            }

            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);

            $table = $xpath->query("//table[@id='tblDatos']")->item(0);

            if (!$table) {
                return response()->json(['success' => false, 'message' => 'No se encontró la tabla #tblDatos en el archivo.'], 400);
            }

            $headers = [];
            foreach ($xpath->query(".//thead/tr/th", $table) as $th) {
                $headers[] = trim($th->textContent);
            }

            if (empty($headers)) {
                return response()->json(['success' => false, 'message' => 'No se encontraron cabeceras en la tabla.'], 400);
            }

            $created = 0;
            $skipped = 0;
            $errors  = [];

            foreach ($xpath->query(".//tbody/tr", $table) as $index => $tr) {
                $cells = $xpath->query(".//td", $tr);

                // ── Extraer ID único del primer <td> (texto después del <br>) ──────
                $cargacarId = '';
                $fechaTexto = '';
                $firstTd    = $cells->item(0);

                if ($firstTd) {
                    $foundBr = false;
                    foreach ($firstTd->childNodes as $child) {
                        if ($child->nodeName === 'br') {
                            $foundBr = true;
                            continue;
                        }
                        // Fecha: texto del <a>
                        if (!$foundBr && $child->nodeName === 'a') {
                            $fechaTexto = trim($child->textContent);
                        }
                        // ID único: nodo texto después del <br>
                        if ($foundBr && $child->nodeType === XML_TEXT_NODE) {
                            $cargacarId = trim($child->textContent);
                            break;
                        }
                    }
                }

                // ── Construir rowData con el resto de columnas ────────────────────
                $rowData = [];
                foreach ($cells as $i => $td) {
                    $header          = $headers[$i] ?? 'extra_' . $i;
                    $rowData[$header] = trim($td->textContent);
                }

                // Sobreescribir Fecha con solo la parte limpia (sin el ID pegado)
                $rowData['Fecha'] = $fechaTexto;

                if (empty(array_filter($rowData, fn($v) => trim((string)$v) !== ''))) {
                    continue;
                }

                $nombre   = trim($rowData['Nombre']    ?? '');
                $telefono = trim($rowData['Teléfono']  ?? '');

                if ($nombre === '' && $telefono === '') {
                    $skipped++;
                    $errors[] = ['row' => $index + 1, 'reason' => 'Fila sin nombre ni teléfono'];
                    continue;
                }

                // ── Deduplicación por cargacarId ──────────────────────────────────
                if ($cargacarId !== '') {
                    $exists = Opportunity::where('cargacarId', $cargacarId)->exists();

                    if ($exists) {
                        $skipped++;
                        $errors[] = [
                            'row'        => $index + 1,
                            'reason'     => 'Ya existe una oportunidad con este ID',
                            'cargacarId' => $cargacarId,
                        ];
                        continue;
                    }
                }

                $telefonoNormalizado = $this->normalizeImportedPhone($telefono);

                $customFields = [
                    ['title' => 'Fecha origen',      'type' => 'text', 'value' => $rowData['Fecha']             ?? ''],
                    ['title' => 'Presupuesto',        'type' => 'text', 'value' => $rowData['Presupuesto']       ?? ''],
                    ['title' => 'Contactado',         'type' => 'text', 'value' => $rowData['Contactado']        ?? ''],
                    ['title' => 'Varias Provincias',  'type' => 'text', 'value' => $rowData['Varias Provincias'] ?? ''],
                    ['title' => 'Email destino',      'type' => 'text', 'value' => $rowData['Email destino']     ?? ''],
                    ['title' => 'Asociado',           'type' => 'text', 'value' => $rowData['Asociado']          ?? ''],
                    ['title' => 'ID Cargacar',        'type' => 'text', 'value' => $cargacarId],
                ];

                Opportunity::create([
                    'name'         => $nombre !== '' ? $nombre : 'Sin nombre',
                    'CIF'          => '',
                    'phone'        => $telefonoNormalizado,
                    'landLinePhone'=> '',
                    'email'        => trim($rowData['Email'] ?? ''),
                    'website'      => trim($rowData['Url']   ?? ''),
                    'sector'       => '',
                    'source'       => trim($rowData['Origen'] ?? ''),
                    'status'       => 'Pendiente',
                    'contact'      => ['value' => trim($rowData['Contacto'] ?? ''), 'isFromContacts' => false],
                    'position'     => '',
                    'observations' => $this->buildImportedOpportunityObservations($rowData),
                    'billingInfo'  => [
                        'community' => '',
                        'province'  => trim($rowData['Zona'] ?? ''),
                        'locality'  => trim($rowData['Zona'] ?? ''),
                        'address'   => '',
                        'postal'    => trim($rowData['CP']   ?? ''),
                    ],
                    'customFields' => $customFields,
                    'order'        => [
                        'productType' => 'sp',
                        'marketer'    => 'Sin Comercializadora',
                        'fee'         => 'Sin Tarifa',
                        'product'     => '',
                        'CUPS'        => '',
                        'province'    => trim($rowData['Zona'] ?? ''),
                        'town'        => trim($rowData['Zona'] ?? ''),
                        'direc'       => null,
                        'zip'         => trim($rowData['CP']  ?? ''),
                        'extras'      => [],
                    ],
                    'cargacarId'   => $cargacarId,   // ← campo raíz para búsquedas rápidas
                    'usersIds'     => ["698340c75525f31823005652"],
                    'createdBy'    => $userLogged->_id,
                    'createdAt'    => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $created++;
            }

            return response()->json([
                'success'          => true,
                'message'          => 'Importación completada',
                'created'          => $created,
                'skipped'          => $skipped,
                'errors'           => $errors,
                'headers_detected' => $headers,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importando oportunidades',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);

        } finally {
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }


    private function normalizeImportedPhone(?string $phone): string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return '';
        }

        // Quitar espacios y caracteres raros
        $phone = preg_replace('/\D+/', '', $phone);

        // Si viene con prefijo 34 y tiene más de 9 dígitos, quitamos el prefijo
        if (str_starts_with($phone, '34') && strlen($phone) > 9) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }

    private function buildImportedOpportunityObservations(array $rowData): string
    {
        $parts = [];

        if (!empty($rowData['Fecha'])) {
            $parts[] = 'Fecha origen: ' . $rowData['Fecha'];
        }

        if (!empty($rowData['Presupuesto'])) {
            $parts[] = 'Presupuesto: ' . $rowData['Presupuesto'];
        }

        if (!empty($rowData['Contactado'])) {
            $parts[] = 'Contactado: ' . $rowData['Contactado'];
        }

        if (!empty($rowData['Asociado'])) {
            $parts[] = 'Asociado: ' . $rowData['Asociado'];
        }

        if (!empty($rowData['Email destino'])) {
            $parts[] = 'Email destino: ' . $rowData['Email destino'];
        }

        if (!empty($rowData['Varias Provincias'])) {
            $parts[] = 'Varias Provincias: ' . $rowData['Varias Provincias'];
        }

        return implode(' | ', $parts);
    }

    public static function sendEvChargerPDF(Request $request)
    {
        try {
            $validated = $request->validate([
                'payload' => 'required|string',
            ]);

            $data = json_decode($validated['payload'], true);

            if (!$data) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Payload no válido',
                ], 422);
            }

            $opportunity       = $data['opportunity'] ?? [];
            $basicData         = $data['basicData'] ?? [];
            $userLogged        = $data['userLogged'] ?? null;
            $sendChannel       = $data['sendChannel'] ?? 'email'; // email | whatsapp
            $recipientEmail    = $data['recipientEmail'] ?? null;
            $recipientPhone    = $data['recipientPhone'] ?? null;
            $depositPercentage = isset($data['depositPercentage'])
                ? (float) $data['depositPercentage']
                : 1.0;

            $order  = $opportunity['order'] ?? [];
            $budget = $order['evChargerBudget'] ?? [];

            if (!in_array($sendChannel, ['email', 'whatsapp'], true)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Canal de envío no válido',
                ], 422);
            }

            if ($sendChannel === 'email' && empty($recipientEmail)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Email destinatario no proporcionado',
                ], 422);
            }

            if ($sendChannel === 'whatsapp' && empty($recipientPhone)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Teléfono destinatario no proporcionado',
                ], 422);
            }

            /*
            |--------------------------------------------------------------------------
            | Imagen portada
            |--------------------------------------------------------------------------
            */
            $coverBase64 = null;

            try {
                $coverPath = base_path('../assets/enterprises/zocoenergia/logos/EV.jpeg');

                if (file_exists($coverPath)) {
                    $coverMime     = mime_content_type($coverPath);
                    $coverContents = file_get_contents($coverPath);
                    $coverBase64   = 'data:' . $coverMime . ';base64,' . base64_encode($coverContents);
                }
            } catch (\Throwable $e) {
                $coverBase64 = null;
            }

            /*
            |--------------------------------------------------------------------------
            | Logo empresa
            |--------------------------------------------------------------------------
            */
            $logoUrl = null;
            $folder  = $basicData['enterprise']['asset_folder'] ?? '';

            if (!empty($folder)) {
                $logoPath = base_path("../assets/enterprises/{$folder}/logos/segenet.png");

                if (file_exists($logoPath)) {
                    $mime     = mime_content_type($logoPath);
                    $contents = file_get_contents($logoPath);
                    $logoUrl  = 'data:' . $mime . ';base64,' . base64_encode($contents);
                }
            }

            $currentDateView = \Carbon\Carbon::now()->format('d/m/Y');
            $currentDate     = \Carbon\Carbon::now()->format('d-m-Y');

            $clientName = $opportunity['name']
                ?? $order['name']
                ?? 'cliente';

            $safeClientName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $clientName);

            $fileName = "presupuesto_cargador_{$safeClientName}_{$currentDate}.pdf";

            /*
            |--------------------------------------------------------------------------
            | Generar link de pago Stripe
            |--------------------------------------------------------------------------
            */
            $stripePaymentUrl = null;

            try {
                $stripeResponse = app(StripeController::class)
                    ->createElectricChargerBudgetCheckout(new Request([
                        'deposit_percentage' => $depositPercentage,
                        'customer' => [
                            'name'  => $opportunity['name'] ?? '',
                            'email' => $opportunity['email'] ?? $recipientEmail ?? '',
                            'phone' => $opportunity['phone'] ?? $recipientPhone ?? '',
                        ],
                        'charger' => [
                            'name' => $budget['charger']['name']
                                ?? $budget['chargerModel']
                                    ?? 'Cargador eléctrico',
                        ],
                        'installation' => [
                            'type'        => $budget['installationType'] ?? '',
                            'cableMeters' => $budget['cableMeters'] ?? 0,
                        ],
                        'summary' => [
                            'chargerSubtotal'   => $budget['totals']['chargerSubtotal'] ?? 0,
                            'laborSubtotal'     => $budget['totals']['laborSubtotal'] ?? 0,
                            'cableSubtotal'     => $budget['totals']['cableSubtotal'] ?? 0,
                            // CRM: 'modulationCableSubtotal'; formulario público: 'tubeSubtotal'
                            'tubeSubtotal'      => $budget['totals']['modulationCableSubtotal']
                                                    ?? $budget['totals']['tubeSubtotal'] ?? 0,
                            // CRM: 'certificateSubtotal'; formulario público: 'certSubtotal'
                            'certSubtotal'      => $budget['totals']['certificateSubtotal']
                                                    ?? $budget['totals']['certSubtotal'] ?? 0,
                            // CRM: 'surplusOptimizationSubtotal'; formulario público: 'surplusSubtotal'
                            'surplusSubtotal'   => $budget['totals']['surplusOptimizationSubtotal']
                                                    ?? $budget['totals']['surplusSubtotal'] ?? 0,
                            'civilWorkSubtotal' => $budget['totals']['civilWorkSubtotal'] ?? 0,
                            'optionalSubtotal'  => $budget['totals']['optionalSubtotal'] ?? 0,
                            'subtotal'          => $budget['totals']['subtotal'] ?? 0,
                            'vat'               => $budget['totals']['vat'] ?? 0,
                            'total'             => $budget['totals']['total'] ?? 0,
                        ],
                    ]));

                $stripeData       = json_decode($stripeResponse->getContent(), true);
                $stripePaymentUrl = $stripeData['url'] ?? null;
            } catch (\Throwable $e) {
                Log::warning('No se pudo generar URL de pago Stripe para PDF EV', [
                    'message' => $e->getMessage(),
                    'channel' => $sendChannel,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Render HTML del PDF
            |--------------------------------------------------------------------------
            */
            $html = view('PDFs.ev', [
                'data'              => $data,
                'opportunity'       => $opportunity,
                'basicData'         => $basicData,
                'userLogged'        => $userLogged,
                'order'             => $order,
                'budget'            => $budget,
                'coverBase64'       => $coverBase64,
                'logoUrl'           => $logoUrl,
                'currentDate'       => $currentDateView,
                'stripePaymentUrl'  => $stripePaymentUrl,
                'depositPercentage' => $depositPercentage,
            ])->render();

            /*
            |--------------------------------------------------------------------------
            | Generar PDF temporal
            |--------------------------------------------------------------------------
            */
            $tempPath = storage_path('app/tmp_pdf_' . uniqid('', true) . '.pdf');

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $node   = 'C:\\Program Files\\nodejs\\node.exe';
                $npm    = 'C:\\Program Files\\nodejs\\npm.cmd';
                $chrome = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
            } else {
                $node   = '/usr/bin/node';
                $npm    = '/usr/bin/npm';
                $chrome = '/usr/bin/chromium';
            }

            \Spatie\Browsershot\Browsershot::html($html)
                ->setNodeBinary($node)
                ->setNpmBinary($npm)
                ->setChromePath($chrome)
                ->setEnvironmentOptions([
                    'HOME'            => '/tmp',
                    'XDG_CONFIG_HOME' => '/tmp',
                ])
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--no-zygote',
                    '--disable-gpu',
                    '--user-data-dir=/tmp/browsershot-profile',
                    '--single-process',
                ])
                ->format('A4')
                ->margins(0, 0, 0, 0)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->scale(1)
                ->save($tempPath);

            $pdfContent = file_get_contents($tempPath);

            /*
            |--------------------------------------------------------------------------
            | Envío por WhatsApp
            |--------------------------------------------------------------------------
            */
            if ($sendChannel === 'whatsapp') {
                $publicPdfName = 'ev_budget_' . now()->format('Ymd_His') . '_' . uniqid() . '.pdf';

                Storage::disk('temporal_comparatives')->put($publicPdfName, $pdfContent);

                $pdfUrl = 'https://crm.zocoenergia.com/assets/temporal_comparatives/' . $publicPdfName;

                $whapiResponse = self::sendEvChargerBudgetByWhapi(
                    $recipientPhone,
                    $pdfUrl,
                    $fileName,
                    $clientName
                );

                @unlink($tempPath);

                Log::info('EV charger budget sent by WhatsApp', [
                    'to' => $recipientPhone,
                    'pdfUrl' => $pdfUrl,
                    'fileName' => $fileName,
                    'clientName' => $clientName,
                    'response' => $whapiResponse,
                ]);

                return response()->json([
                    'ok' => true,
                    'message' => 'Presupuesto enviado correctamente por WhatsApp',
                    'channel' => 'whatsapp',
                    'to' => $recipientPhone,
                    'pdfUrl' => $pdfUrl,
                    'whapiResponse' => $whapiResponse,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Envío por Email
            |--------------------------------------------------------------------------
            */
            Mail::mailer('segenet')
                ->send([], [], function ($message) use ($recipientEmail, $pdfContent, $fileName, $clientName) {
                    $message
                        ->to($recipientEmail)
                        ->subject('Presupuesto Cargador Eléctrico - Segenet Movilidad')
                        ->html('
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset="UTF-8">
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                background-color: #f4f4f4;
                                margin: 0;
                                padding: 0;
                            }
                            .wrapper {
                                max-width: 600px;
                                margin: 30px auto;
                                background-color: #ffffff;
                                border-radius: 12px;
                                overflow: hidden;
                                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
                            }
                            .header {
                                background-color: #2e7d32;
                                padding: 35px 40px;
                                text-align: center;
                            }
                            .header h1 {
                                color: #ffffff;
                                font-size: 24px;
                                margin: 0;
                                letter-spacing: 0.5px;
                            }
                            .header p {
                                color: #a5d6a7;
                                margin: 6px 0 0;
                                font-size: 14px;
                            }
                            .body {
                                padding: 35px 40px;
                                color: #333333;
                            }
                            .body p {
                                font-size: 15px;
                                line-height: 1.7;
                                margin: 0 0 14px;
                            }
                            .highlight-box {
                                background-color: #e8f5e9;
                                border-left: 4px solid #2e7d32;
                                border-radius: 6px;
                                padding: 16px 20px;
                                margin: 24px 0;
                            }
                            .highlight-box p {
                                margin: 0;
                                color: #1b5e20;
                                font-size: 14px;
                            }
                            .footer {
                                background-color: #f1f8f1;
                                border-top: 1px solid #c8e6c9;
                                padding: 20px 40px;
                                text-align: center;
                            }
                            .footer p {
                                color: #777;
                                font-size: 12px;
                                margin: 0;
                            }
                            .footer strong {
                                color: #2e7d32;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="wrapper">
                            <div class="header">
                                <h1>Presupuesto Cargador Eléctrico</h1>
                                <p>Segenet Movilidad</p>
                            </div>
                            <div class="body">
                                <p>Estimado/a <strong>' . htmlspecialchars($clientName) . '</strong>,</p>
                                <p>Nos complace enviarle el presupuesto personalizado para la instalación de su cargador eléctrico.</p>

                                <div class="highlight-box">
                                    <p>Adjunto a este correo encontrará el presupuesto en formato PDF con todos los detalles de la instalación.</p>
                                </div>

                                <p>Si tiene cualquier pregunta o desea modificar algún aspecto del presupuesto, no dude en ponerse en contacto con nosotros. Estaremos encantados de ayudarle.</p>

                                <p>Gracias por confiar en <strong>Segenet Movilidad</strong>.</p>

                                <p>Un cordial saludo,<br><strong style="color: #2e7d32;">El equipo de Segenet Movilidad</strong></p>
                            </div>
                            <div class="footer">
                                <p>© ' . date('Y') . ' <strong>Segenet Movilidad</strong> · Todos los derechos reservados</p>
                                <p style="margin-top: 6px;">Este correo ha sido enviado automáticamente, por favor no responda a este mensaje.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ')
                        ->attachData($pdfContent, $fileName, [
                            'mime' => 'application/pdf',
                        ]);
                });

            @unlink($tempPath);

            Log::info('EV charger budget sent by email', [
                'to' => $recipientEmail,
                'fileName' => $fileName,
                'clientName' => $clientName,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Presupuesto enviado correctamente por email',
                'channel' => 'email',
                'to' => $recipientEmail,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en sendEvChargerPDF', [
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo enviar el presupuesto.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    private static function sendEvChargerBudgetByWhapi($to, $pdfUrl, $fileName, $clientName) {
        $to = preg_replace('/\D+/', '', $to);

        if (strlen($to) === 9) {
            $to = '34' . $to;
        }

        if (!$to || strlen($to) < 11) {
            throw new \Exception('Teléfono de WhatsApp no válido.');
        }

        $enterprise = Enterprise::where('whapi.name', 'ZOCO')
            ->where('whapi.enabled', true)
            ->firstOrFail();

        $whapi = $enterprise['whapi'] ?? [];

        $name = $whapi['name'] ?? null;

        if (!$name) {
            throw new \Exception('La empresa no tiene whapi.name configurado.');
        }

        $token = env('WHATSAPP_TOKEN_' . $name);

        if (!$token) {
            throw new \Exception('No existe token Whapi en .env: WHATSAPP_TOKEN_' . $name);
        }

        $driver = new WhapiDriver($token);

        $introMessage =
            "Hola {$clientName}, te enviamos tu presupuesto personalizado para la instalación de tu cargador eléctrico.\n\n" .
            "En el PDF encontrarás todos los detalles de la instalación y el enlace de pago.";

        $textResponse = $driver->sendText($to, $introMessage);

        if (!str_ends_with(strtolower($fileName), '.pdf')) {
            $fileName .= '.pdf';
        }

        $documentResponse = $driver->sendDocument(
            $to,
            $pdfUrl,
            $fileName,
            'Presupuesto cargador eléctrico'
        );

        Log::info('EV charger budget Whapi document sent', [
            'to' => $to,
            'pdfUrl' => $pdfUrl,
            'fileName' => $fileName,
            'textResponse' => $textResponse,
            'documentResponse' => $documentResponse,
        ]);

        return [
            'text' => $textResponse,
            'document' => $documentResponse,
        ];
    }

        public static function generateEvChargerPDF(Request $request)
    {
        try {
            $validated = $request->validate([
                'payload' => 'required|string',
            ]);

            $data = json_decode($validated['payload'], true);

            if (!$data) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Payload no válido',
                ], 422);
            }

            $opportunity       = $data['opportunity'] ?? [];
            $basicData         = $data['basicData']   ?? [];
            $userLogged        = $data['userLogged']  ?? null;
            $depositPercentage = isset($data['depositPercentage']) ? (float) $data['depositPercentage'] : 1.0;

            $order  = $opportunity['order'] ?? [];
            $budget = $order['evChargerBudget'] ?? [];


            $coverPath = base_path('../assets/enterprises/zocoenergia/logos/EV.jpeg');

            $coverBase64 = null;

            try {
                if (file_exists($coverPath)) {
                    $coverMime     = mime_content_type($coverPath);
                    $coverContents = file_get_contents($coverPath);
                    $coverBase64   = 'data:' . $coverMime . ';base64,' . base64_encode($coverContents);
                }
            } catch (\Throwable $e) {
                $coverBase64 = null;
            }




            $logoUrl = null;
            $folder  = $basicData['enterprise']['asset_folder'] ?? '';

            if (!empty($folder)) {
                $logoPath = base_path("../assets/enterprises/{$folder}/logos/segenet.png");

                if (file_exists($logoPath)) {
                    $mime     = mime_content_type($logoPath);
                    $contents = file_get_contents($logoPath);
                    $logoUrl  = 'data:' . $mime . ';base64,' . base64_encode($contents);
                }
            }


            $currentDate = \Carbon\Carbon::now()->format('d-m-Y');
            $currentDateView = \Carbon\Carbon::now()->format('d/m/Y');

            $clientName     = $opportunity['name'] ?? $order['name'] ?? 'cliente';
            $safeClientName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $clientName);
            $fileName       = "presupuesto_cargador_{$safeClientName}_{$currentDate}.pdf";

            // Creo el checkout de stripe para la url
            $stripePaymentUrl = null;
            try {
                $budget = $order['evChargerBudget'] ?? [];

                $stripeResponse = app(StripeController::class)
                    ->createElectricChargerBudgetCheckout(new Request([
                        'deposit_percentage' => $depositPercentage,
                        'customer' => [
                            'name'  => $opportunity['name']  ?? '',
                            'email' => $opportunity['email'] ?? '',
                            'phone' => $opportunity['phone'] ?? '',
                        ],
                        'charger' => [
                            'name' => $budget['charger']['name'] ?? 'Cargador eléctrico',
                        ],
                        'installation' => [
                            'type'        => $budget['installationType'] ?? '',
                            'cableMeters' => $budget['cableMeters'] ?? 0,
                        ],
                        'summary' => [
                            'chargerSubtotal'   => $budget['totals']['chargerSubtotal'] ?? 0,
                            'laborSubtotal'     => $budget['totals']['laborSubtotal']   ?? 0,
                            'cableSubtotal'     => $budget['totals']['cableSubtotal']   ?? 0,
                            // CRM: 'modulationCableSubtotal'; formulario público: 'tubeSubtotal'
                            'tubeSubtotal'      => $budget['totals']['modulationCableSubtotal']
                                                    ?? $budget['totals']['tubeSubtotal'] ?? 0,
                            // CRM: 'certificateSubtotal'; formulario público: 'certSubtotal'
                            'certSubtotal'      => $budget['totals']['certificateSubtotal']
                                                    ?? $budget['totals']['certSubtotal'] ?? 0,
                            // CRM: 'surplusOptimizationSubtotal'; formulario público: 'surplusSubtotal'
                            'surplusSubtotal'   => $budget['totals']['surplusOptimizationSubtotal']
                                                    ?? $budget['totals']['surplusSubtotal'] ?? 0,
                            'civilWorkSubtotal' => $budget['totals']['civilWorkSubtotal'] ?? 0,
                            'optionalSubtotal'  => $budget['totals']['optionalSubtotal']  ?? 0,
                            'subtotal'          => $budget['totals']['subtotal']           ?? 0,
                            'vat'               => $budget['totals']['vat']                ?? 0,
                            'total'             => $budget['totals']['total']              ?? 0,
                        ],
                    ]));

                $stripeData       = json_decode($stripeResponse->getContent(), true);
                $stripePaymentUrl = $stripeData['url'] ?? null;

            } catch (\Throwable $e) {
                Log::warning('No se pudo generar URL de pago Stripe para PDF', [
                    'message' => $e->getMessage()
                ]);
            }

            $html = view('PDFs.ev', [
                'data'              => $data,
                'opportunity'       => $opportunity,
                'basicData'         => $basicData,
                'userLogged'        => $userLogged,
                'order'             => $order,
                'budget'            => $budget,
                'coverBase64'       => $coverBase64,
                'logoUrl'           => $logoUrl,
                'currentDate'       => $currentDateView,
                'stripePaymentUrl'  => $stripePaymentUrl,
                'depositPercentage' => $depositPercentage,
            ])->render();


            $tempPath = storage_path('app/tmp_pdf_' . uniqid('', true) . '.pdf');


            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $node   = 'C:\\Program Files\\nodejs\\node.exe';
                $npm    = 'C:\\Program Files\\nodejs\\npm.cmd';
                $chrome = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
            } else {
                $node   = '/usr/bin/node';
                $npm    = '/usr/bin/npm';
                $chrome = '/usr/bin/chromium';


            }

            \Spatie\Browsershot\Browsershot::html($html)
                ->setNodeBinary($node)
                ->setNpmBinary($npm)
                ->setChromePath($chrome)
                ->setEnvironmentOptions([
                    'HOME'            => '/tmp',
                    'XDG_CONFIG_HOME' => '/tmp',
                ])
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--no-zygote',
                    '--disable-gpu',
                    '--user-data-dir=/tmp/browsershot-profile',
                    '--single-process',
                ])
                ->format('A4')
                ->margins(0, 0, 0, 0)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->scale(1)
                ->save($tempPath);

            $opportunity = $data['opportunity'] ?? [];
            $basicData   = $data['basicData']   ?? [];
            $userLogged  = $data['userLogged']  ?? null;
            $order       = $opportunity['order'] ?? [];
            $budget      = $order['evChargerBudget'] ?? [];

            $oppId = $opportunity['_id'] ?? null;

            if (empty($oppId)) {
                $phone         = trim($opportunity['phone'] ?? '');
                $alreadyExists = !empty($phone) && Opportunity::where('phone', $phone)->exists();

                if (!$alreadyExists) {
                    $newOpp = Opportunity::create([
                        'name'          => $opportunity['name']  ?? '',
                        'phone'         => $phone,
                        'email'         => $opportunity['email'] ?? '',
                        'CIF'           => '',
                        'landLinePhone' => '',
                        'website'       => '',
                        'sector'        => '',
                        'source'        => 'Configurador cargador eléctrico',
                        'status'        => 'Pre. Bot',
                        'contact'       => ['value' => '', 'isFromContacts' => false],
                        'position'      => '',
                        'observations'  => '',
                        'billingInfo'   => [
                            'community' => '',
                            'province'  => '',
                            'locality'  => '',
                            'address'   => $order['direc'] ?? '',
                            'postal'    => '',
                        ],
                        'customFields'  => [],
                        'order'         => $order,
                        'usersIds'      => ['698340c75525f31823005652'],
                        'createdBy'     => '698340c75525f31823005652',
                        'createdAt'     => now()->format('Y-m-d H:i:s'),
                    ]);
                    $oppId = (string) $newOpp->_id;
                } else {
                    $existingOpp = Opportunity::where('phone', $phone)->first();
                    if ($existingOpp) {
                        $oppId = (string) $existingOpp->_id;
                    }
                }
            }

            $pdfFileName = null;
            if ($oppId) {
                try {
                    $dbOpp = Opportunity::find($oppId);
                    if ($dbOpp) {
                        $pdfFileName = 'ev_' . now()->format('Ymd_His') . '_' . uniqid() . '.pdf';
                        Storage::disk('order')->put($pdfFileName, file_get_contents($tempPath));
                        $docs   = is_array($dbOpp->docs) ? $dbOpp->docs : [];
                        $docs[] = [
                            'title'        => 'Presupuesto cargador eléctrico',
                            'defaultTitle' => $fileName,
                            'value'        => $pdfFileName,
                            'icon'         => 'fa-file-pdf',
                            'id'           => 'ev-' . uniqid(),
                            'errors'       => (object)[],
                        ];
                        $dbOpp->docs   = $docs;
                        $dbOpp->status = 'Pre. Bot';
                        $dbOpp->save();
                    }
                } catch (\Throwable $e) {
                    \Log::error('Error guardando PDF en oportunidad', ['error' => $e->getMessage()]);
                }
            }

            // Crear log
            try {
                $logRecord = AuditLog::create([
                    'type'      => 'ev_charger_budget',
                    'event'     => 'generate',
                    'createdBy' => '65cb57489c2c285441086a43',
                    'createdAt' => now()->format('Y-m-d H:i:s'),
                    'metadata'  => [
                        '_id'                      => $oppId,
                        'name'                     => $opportunity['name']  ?? '',
                        'phone'                    => $opportunity['phone'] ?? '',
                        'email'                    => $opportunity['email'] ?? '',
                        'chargerModel'             => $budget['chargerModel']             ?? '',
                        'chargerBrand'             => $budget['chargerBrand']             ?? '',
                        'chargerPower'             => $budget['chargerPower']             ?? '',
                        'installationType'         => $budget['installationType']         ?? '',
                        'cableMeters'              => $budget['cableMeters']              ?? 0,
                        'hasPhotovoltaic'          => $budget['hasPhotovoltaic']          ?? false,
                        'wantsSurplusOptimization' => $budget['wantsSurplusOptimization'] ?? false,
                        'totals'                   => $budget['totals']                   ?? [],
                        'pdfFileName'              => $pdfFileName,
                        'depositPercentage'        => $depositPercentage,
                        'generatedAt'              => now()->format('Y-m-d H:i:s'),
                    ],
                ]);

                if ($oppId && $logRecord) {
                    $dbOpp2 = Opportunity::find($oppId);
                    if ($dbOpp2) {
                        $logId = isset($logRecord->_id)
                            ? (string) $logRecord->_id
                            : (string) ($logRecord->id ?? '');
                        if ($logId) {
                            $dbOpp2->evChargerBudgetLogId = $logId;
                            $dbOpp2->save();
                        }
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Error creando log de presupuesto EV', [
                    'type'      => get_class($e),
                    'message'   => $e->getMessage(),
                    'code'      => $e->getCode(),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'oppId'     => $oppId,
                    'oppIdType' => gettype($oppId),
                    'trace'     => $e->getTraceAsString(),
                ]);
            }

            return response()
                ->download($tempPath, $fileName)
                ->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            \Log::error('Error en ToolsController::generateEvChargerPDF', [
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $payloadPath = 'debug/last-ev-charger-pdf-payload-' . date('Ymd_His') . '.json';
            \Storage::disk('local')->put(
                $payloadPath,
                json_encode([
                    'error' => $e->getMessage(),
                    'line'  => $e->getLine(),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo generar el PDF del cargador eléctrico.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    public function showPublic($id)
    {
        $opportunity = Opportunity::where('_id', $id)->first();

        if (!$opportunity) {
            return response()->json(['error' => 'No encontrada'], 404);
        }

        return response()->json([
            'opportunity' => [
                'name'  => $opportunity->name,
                'email' => $opportunity->email,
                'phone' => $opportunity->phone,
            ]
        ], 200);
    }


    public function dumpOpportunities(Request $request)
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        ignore_user_abort(true);

        $file          = $request->file('file');
        $userLogged    = Auth::user();
        $userList      = json_decode($request->input('userList', '[]'), true) ?? [];
        $userSubdomain = json_decode($request->input('userSubdomain', '{}'), true) ?? [];

        if (!$file || !$file->isValid()) {
            return response()->json(['error' => 'No se ha recibido un archivo válido.'], 400);
        }

        $productTypeMap = [
            'contrato de luz'          => 'cl', 'luz' => 'cl', 'electricidad' => 'cl',
            'contrato de gas'          => 'cg', 'gas' => 'cg',
            'contrato dual'            => 'cd', 'dual' => 'cd',
            'telefonía'                => 'ct', 'telefonia' => 'ct',
            'contrato de telefonía'    => 'ct', 'contrato de telefonia' => 'ct',
            'servicio de telefonia'    => 'ct', 'servicio de telefonía' => 'ct',
            'servicio de alarmas'      => 'sa', 'alarmas' => 'sa',
            'contrato de alarmas'      => 'sa',
            'autoconsumo'              => 'a',  'contrato de autoconsumo' => 'a',
            'batería de condensadores' => 'bc', 'bateria de condensadores' => 'bc',
            'coche eléctrico'          => 'ce', 'coche electrico' => 'ce',
            'contador'                 => 'c',
            'iluminación'              => 'i',  'iluminacion' => 'i',
        ];

        $reasonMap = [
            'campo_obligatorio_faltante'     => 'Falta campo obligatorio',
            'estado_invalido'                => 'Estado inválido',
            'tipo_producto_invalido'         => 'Tipo de producto inválido',
            'cups_invalido'                  => 'CUPS inválido',
            'usuario_no_encontrado'          => 'Usuario propietario no encontrado',
            'comercializadora_no_encontrada' => 'Comercializadora no encontrada',
            'comercializadora_sin_acceso'    => 'Sin acceso a esa comercializadora',
            'producto_no_encontrado'         => 'Producto no encontrado',
            'tarifa_no_encontrada'           => 'Tarifa no encontrada',
            'tarifa_no_asociada_a_producto'  => 'Tarifa no asociada al producto',
        ];

        $colLetter = function (int $idx): string {
            $letters = '';
            while ($idx >= 0) {
                $letters = chr($idx % 26 + 65) . $letters;
                $idx     = intdiv($idx, 26) - 1;
            }
            return $letters;
        };

        $normalize = fn($v) => mb_strtolower(trim((string)$v), 'UTF-8');
        $str       = fn($v) => trim((string)($v ?? ''));

        $report = ['summary' => ['total' => 0, 'inserted' => 0, 'failed' => 0], 'failedRows' => []];

        $marketersAll = !empty($userSubdomain['_id'])
            ? Marketer::where('createdBy', $userSubdomain['_id'])->get()->toArray()
            : [];

        // Mapa de estados válidos (normalizado => canónico)
        $statusMap = [
            'pendiente' => 'Pendiente',
            'aceptado'  => 'Aceptado',
            'fallido'   => 'Fallido',
        ];

        try {
            $excel = \Maatwebsite\Excel\Facades\Excel::toArray([], $file);

            if (empty($excel[0])) {
                return response()->json(['error' => 'El archivo no contiene datos.'], 400);
            }

            if (($excel[0][0][0] ?? null) !== 'ZocoCuentas') {
                return response()->json(['error' => 'Por favor usa la plantilla oficial de oportunidades.'], 400);
            }

            $rawRows   = array_slice($excel[0], 2);
            $failCells = [];
            $toProcess = [];

            foreach ($rawRows as $rowIndex => $row) {
                $rowNum  = $rowIndex + 3;
                $limited = array_slice($row, 0, 23);

                if (array_filter($limited, fn($v) => trim((string)$v) !== '') === []) {
                    continue;
                }

                $report['summary']['total']++;

                // ── col 0: Nombre (obligatorio) ──────────────────────────────────
                if ($str($row[0]) === '') {
                    $report['summary']['failed']++;
                    $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'campo_obligatorio_faltante', 'details' => 'El campo Nombre es obligatorio.'];
                    $failCells[$rowNum][]   = ['col' => 0, 'msg' => 'Nombre obligatorio'];
                    continue;
                }

                // ── col 4: Estado (opcional, pero si viene debe ser válido) ──────
                $statusRaw      = $str($row[4]);
                $statusNorm     = $normalize($statusRaw);
                $canonicalStatus = '';

                if ($statusRaw !== '') {
                    if (!array_key_exists($statusNorm, $statusMap)) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = [
                            'rowNumber' => $rowNum,
                            'reason'    => 'estado_invalido',
                            'details'   => "Estado inválido: '{$statusRaw}'. Valores permitidos: Pendiente, Aceptado, Fallido.",
                        ];
                        $failCells[$rowNum][] = ['col' => 4, 'msg' => 'Estado inválido'];
                        continue;
                    }
                    $canonicalStatus = $statusMap[$statusNorm];
                }

                // ── col 13: Tipo de producto ──────────────────────────────────────
                $productTypeRaw  = $str($row[13]);
                $productTypeCode = '';
                if ($productTypeRaw !== '') {
                    $productTypeCode = $productTypeMap[$normalize($productTypeRaw)] ?? null;
                    if ($productTypeCode === null) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'tipo_producto_invalido', 'details' => "Tipo de producto inválido: '{$productTypeRaw}'."];
                        $failCells[$rowNum][]   = ['col' => 13, 'msg' => 'Tipo de producto inválido'];
                        continue;
                    }
                }

                // ── col 17: CUPS ─────────────────────────────────────────────────
                $cups = strtoupper($str($row[17]));
                if ($cups !== '' && !preg_match('/^ES[0-9A-Z]{18,20}$/', $cups)) {
                    $report['summary']['failed']++;
                    $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'cups_invalido', 'details' => "CUPS inválido: '{$cups}'."];
                    $failCells[$rowNum][]   = ['col' => 17, 'msg' => 'CUPS inválido'];
                    continue;
                }

                // ── col 22: Email propietario ─────────────────────────────────────
                $ownerEmail    = $normalize($row[22] ?? '');
                $usersIds      = [];
                $effectiveUser = $userLogged;

                if ($ownerEmail !== '') {
                    $userListEmails = collect($userList)->map(fn($u) => mb_strtolower(trim($u['email'] ?? ''), 'UTF-8'));

                    if (!$userListEmails->contains($ownerEmail)) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'usuario_no_encontrado', 'details' => "El usuario '{$ownerEmail}' no pertenece a tu lista de usuarios."];
                        $failCells[$rowNum][]   = ['col' => 22, 'msg' => 'Usuario no pertenece a tu lista'];
                        continue;
                    }

                    $ownerUser = User::where('email', $ownerEmail)->first();
                    if (!$ownerUser) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'usuario_no_encontrado', 'details' => "No se encontró el usuario con email '{$ownerEmail}'."];
                        $failCells[$rowNum][]   = ['col' => 22, 'msg' => 'Usuario no encontrado'];
                        continue;
                    }

                    $usersIds      = [(string)$ownerUser->_id];
                    $effectiveUser = $ownerUser;
                } else {
                    $usersIds = [(string)$userLogged->_id];
                }

                $effectiveUserMarketerIds = collect($effectiveUser->marketers ?? [])->map(fn($id) => (string)$id)->toArray();
                $effectiveIsSubdomainUser = ($effectiveUser->label ?? '') === 'Usuario subdominio';

                // ── col 14: Comercializadora ──────────────────────────────────────
                $marketerRaw       = $str($row[14]);
                $canonicalMarketer = $marketerRaw;
                $existingMarketer  = null;

                if ($marketerRaw !== '') {
                    $existingMarketer = collect($marketersAll)->first(
                        fn($m) => isset($m['name']) && $normalize($m['name']) === $normalize($marketerRaw)
                    );

                    if (!$existingMarketer) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'comercializadora_no_encontrada', 'details' => "Comercializadora '{$marketerRaw}' no encontrada."];
                        $failCells[$rowNum][]   = ['col' => 14, 'msg' => 'Comercializadora no encontrada'];
                        continue;
                    }

                    $canonicalMarketer = $existingMarketer['name'];

                    if (!$effectiveIsSubdomainUser) {
                        $mid = $existingMarketer['_id'] ?? '';
                        if ($mid instanceof \MongoDB\BSON\ObjectId) {
                            $marketerId = (string)$mid;
                        } elseif (is_array($mid) && isset($mid['$oid'])) {
                            $marketerId = (string)$mid['$oid'];
                        } else {
                            $marketerId = (string)$mid;
                        }

                        if (!in_array($marketerId, $effectiveUserMarketerIds, true)) {
                            $report['summary']['failed']++;
                            $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'comercializadora_sin_acceso', 'details' => "Sin acceso a la comercializadora '{$marketerRaw}'."];
                            $failCells[$rowNum][]   = ['col' => 14, 'msg' => 'Sin acceso a esta comercializadora'];
                            continue;
                        }
                    }
                }

                // ── col 15: Tarifa  /  col 16: Producto ──────────────────────────
                $feeRaw           = $str($row[15]);
                $productRaw       = $str($row[16]);
                $canonicalFee     = $feeRaw;
                $canonicalProduct = $productRaw;

                if ($existingMarketer && $productTypeCode && $productRaw !== '') {
                    $categoryMap = [
                        'cl' => 'electricity', 'cg' => 'gas', 'cd' => 'dual',
                        'ct' => 'telephony',   'sa' => 'alarm', 'a' => 'selfSupply',
                    ];
                    $category = $categoryMap[$productTypeCode] ?? null;

                    if ($category && in_array($productTypeCode, ['cl', 'cg'], true)) {
                        if ($feeRaw !== '') {
                            $marketerFee = collect($existingMarketer['fees'][$category] ?? [])
                                ->first(fn($f) => $normalize($f['name'] ?? '') === $normalize($feeRaw));

                            if (!$marketerFee) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'tarifa_no_encontrada', 'details' => "Tarifa '{$feeRaw}' no encontrada en la comercializadora."];
                                $failCells[$rowNum][]   = ['col' => 15, 'msg' => 'Tarifa no encontrada'];
                                continue;
                            }

                            $canonicalFee = $marketerFee['name'];

                            $existingProduct = collect($existingMarketer['products'][$category] ?? [])
                                ->first(fn($p) => $normalize($p['name'] ?? '') === $normalize($productRaw));

                            if (!$existingProduct) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'producto_no_encontrado', 'details' => "Producto '{$productRaw}' no encontrado."];
                                $failCells[$rowNum][]   = ['col' => 16, 'msg' => 'Producto no encontrado'];
                                continue;
                            }

                            $feeInProduct = collect($existingProduct['fees'] ?? [])
                                ->first(fn($pf) => (string)($pf['id']['$oid'] ?? $pf['id'] ?? '') === (string)($marketerFee['id']['$oid'] ?? $marketerFee['id'] ?? ''));

                            if (!$feeInProduct) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'tarifa_no_asociada_a_producto', 'details' => "La tarifa '{$feeRaw}' no está asociada al producto '{$productRaw}'."];
                                $failCells[$rowNum][]   = ['col' => 15, 'msg' => 'Tarifa no asociada al producto'];
                                continue;
                            }

                            $canonicalProduct = $existingProduct['name'];
                        }
                    } elseif ($category) {
                        $existingProduct = collect($existingMarketer['products'][$category] ?? [])
                            ->first(fn($p) => $normalize($p['name'] ?? '') === $normalize($productRaw));

                        if (!$existingProduct) {
                            $report['summary']['failed']++;
                            $report['failedRows'][] = ['rowNumber' => $rowNum, 'reason' => 'producto_no_encontrado', 'details' => "Producto '{$productRaw}' no encontrado."];
                            $failCells[$rowNum][]   = ['col' => 16, 'msg' => 'Producto no encontrado'];
                            continue;
                        }

                        $canonicalProduct = $existingProduct['name'];
                    }
                }

                $toProcess[] = [
                    'row'               => $row,
                    'productType'       => $productTypeCode,
                    'cups'              => $cups,
                    'usersIds'          => $usersIds,
                    'canonicalMarketer' => $canonicalMarketer,
                    'canonicalFee'      => $canonicalFee,
                    'canonicalProduct'  => $canonicalProduct,
                    'canonicalStatus'   => $canonicalStatus,
                ];
            }

            if (!empty($failCells)) {
                return $this->returnOpportunityErrorExcel($file, $failCells, $report['failedRows'], $reasonMap, $colLetter);
            }

            foreach ($toProcess as $item) {
                $row = $item['row'];

                Opportunity::create([
                    'name'          => $str($row[0]),
                    'CIF'           => strtoupper($str($row[1])),
                    'phone'         => $str($row[2]),
                    'landLinePhone' => $str($row[3]),
                    'status'        => $item['canonicalStatus'],           // col 4
                    'email'         => mb_strtolower($str($row[5]), 'UTF-8'), // col 5
                    'website'       => '',
                    'sector'        => '',
                    'source'        => '',
                    'contact'       => ['value' => '', 'isFromContacts' => false],
                    'position'      => $str($row[6]),                      // col 6
                    'observations'  => $str($row[7]),                      // col 7
                    'billingInfo'   => [
                        'community' => $str($row[8]),                      // col 8
                        'province'  => $str($row[9]),                      // col 9
                        'locality'  => $str($row[10]),                     // col 10
                        'address'   => $str($row[11]),                     // col 11
                        'postal'    => $str($row[12]),                     // col 12
                    ],
                    'customFields'  => [],
                    'order'         => [
                        'productType' => $item['productType'] ?: 'sp',
                        'marketer'    => $item['canonicalMarketer'] ?: 'Sin Comercializadora',
                        'fee'         => $item['canonicalFee']      ?: 'Sin Tarifa',
                        'product'     => $item['canonicalProduct']  ?: 'Sin Producto',
                        'CUPS'        => $item['cups'],
                        'direc'       => $str($row[18]),                   // col 18
                        'province'    => $str($row[19]),                   // col 19
                        'town'        => $str($row[20]),                   // col 20
                        'zip'         => $str($row[21]),                   // col 21
                        'extras'      => [],
                    ],
                    'usersIds'      => $item['usersIds'],
                    'createdBy'     => (string)$userLogged->_id,
                    'createdAt'     => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $report['summary']['inserted']++;
            }

            return response()->json($report, 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al procesar el archivo: ' . $e->getMessage() . ' (línea ' . $e->getLine() . ')',
            ], 500);
        }
    }

    public function exportOpportunities(Request $request)
{
    try {
        $userLogged    = Auth::user();
        $userList      = json_decode($request->input('userList', '[]'), true) ?? [];
        $searchRaw     = (string) $request->input('searchOpportunityText', '');
        $filters       = $request->input('filters', []);
        $sortType      = (int) $request->input('sortType', 7);

        // ── IDs de usuarios accesibles ──────────────────────────────────────
        $usersIds = array_merge(
            [$userLogged->_id],
            array_column($userList, '_id')
        );

        // ── Query base ──────────────────────────────────────────────────────
        $query = Opportunity::whereIn('usersIds', $usersIds);

        // Fechas
        $dates = $filters['dates'] ?? [];
        if (!empty($dates['start'])) {
            $query->where('createdAt', '>=', $dates['start'] . ' 00:00:00');
        }
        if (!empty($dates['end'])) {
            $query->where('createdAt', '<=', $dates['end'] . ' 23:59:59');
        }

        // Agentes
        if (!empty($filters['agents'])) {
            $query->whereIn('createdBy', $filters['agents']);
        }


        // Comercializadoras
        $marketers = array_filter($filters['marketers'] ?? [], fn($v) => !is_null($v));
        if (!empty($marketers)) {
            $query->whereIn('order.marketer', $marketers);
        }

        // Tarifas
        if (!empty($filters['tariffs'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereIn('order.fee', $filters['tariffs'])
                  ->orWhereIn('order.feeSecondary', $filters['tariffs']);
            });
        }

        // Productos
        if (!empty($filters['products'])) {
            $cleanProducts = array_map(fn($p) => $p === 'Sin Producto' ? '' : $p, $filters['products']);
            $query->where(function ($q) use ($cleanProducts) {
                $q->whereIn('order.product', $cleanProducts)
                  ->orWhereIn('order.productSecondary', $cleanProducts);
            });
        }

        // Tipo producto
        if (!empty($filters['productTypes'])) {
            $nonEmpty = array_filter($filters['productTypes'], fn($v) => $v !== '');
            if (!empty($nonEmpty)) {
                $query->whereIn('order.productType', $nonEmpty);
            }
        }

        // Búsqueda
        if (trim($searchRaw) !== '') {
            $searchNorm = mb_strtolower(preg_replace('/\s+/', '', $searchRaw), 'UTF-8');
            $regex = new \MongoDB\BSON\Regex(preg_quote($searchNorm), 'i');
            $query->where(function ($q) use ($regex) {
                $q->where('name', 'regex', $regex)
                  ->orWhere('CIF', 'regex', $regex)
                  ->orWhere('order.name', 'regex', $regex);
            });
        }

        // Ordenación
        $sortMap = [
            0 => ['name',      'asc'],
            1 => ['name',      'desc'],
            2 => ['email',     'asc'],
            3 => ['email',     'desc'],
            4 => ['status',    'asc'],
            5 => ['status',    'desc'],
            6 => ['createdAt', 'asc'],
            7 => ['createdAt', 'desc'],
        ];
        if (isset($sortMap[$sortType])) {
            [$col, $dir] = $sortMap[$sortType];
            $query->orderBy($col, $dir);
        }

        $opportunities = $query->get();

        if ($opportunities->isEmpty()) {
            return response()->json(['error' => 'No hay oportunidades para exportar.'], 404);
        }

        // ── Mapa tipo producto ──────────────────────────────────────────────
        $productTypeText = [
            'cl' => 'Contrato de luz',
            'cg' => 'Contrato de gas',
            'cd' => 'Contrato dual',
            'ct' => 'Telefonía',
            'sa' => 'Alarmas',
            'a'  => 'Autoconsumo',
            'bc' => 'Batería de condensadores',
            'ce' => 'Coche eléctrico',
            'c'  => 'Contador',
            'i'  => 'Iluminación',
            'sp' => '',
        ];

        // ── Cargar plantilla ────────────────────────────────────────────────
        $templatePath = public_path('assets/templates/opportunities.xlsx');
        if (!file_exists($templatePath)) {
            return response()->json(['error' => 'Plantilla no encontrada.'], 404);
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templatePath);
        $sheet       = $spreadsheet->getActiveSheet();

        // ── Escribir filas desde la fila 3 ─────────────────────────────────
        $rowNum = 3;
        foreach ($opportunities as $opp) {

            // Email del propietario principal
            $ownerEmail = '';
            if (!empty($opp->usersIds[0])) {
                $owner = User::where('_id', $opp->usersIds[0])->first();
                $ownerEmail = $owner->email ?? '';
            }

            $order = $opp->order ?? [];

            $sheet->fromArray([
                $opp->name           ?? '',                          // A - Nombre
                $opp->CIF            ?? '',                          // B - CIF/NIF
                $opp->phone          ?? '',                          // C - Teléfono
                $opp->landLinePhone  ?? '',                          // D - Teléfono fijo
                $opp->status         ?? '',                          // E - Estado
                $opp->email          ?? '',                          // F - Email
                $opp->position       ?? '',                          // G - Cargo
                $opp->observations   ?? '',                          // H - Observaciones
                $opp->billingInfo['community'] ?? '',                // I - Comunidad
                $opp->billingInfo['province']  ?? '',                // J - Provincia facturación
                $opp->billingInfo['locality']  ?? '',                // K - Localidad facturación
                $opp->billingInfo['address']   ?? '',                // L - Dirección facturación
                $opp->billingInfo['postal']    ?? '',                // M - CP facturación
                $productTypeText[$order['productType'] ?? ''] ?? '', // N - Tipo producto
                $order['marketer'] ?? '',                            // O - Comercializadora
                $order['fee']      ?? '',                            // P - Tarifa
                $order['product']  ?? '',                            // Q - Producto
                $order['CUPS']     ?? '',                            // R - CUPS
                $order['direc']    ?? '',                            // S - Dirección suministro
                $order['province'] ?? '',                            // T - Provincia suministro
                $order['town']     ?? '',                            // U - Localidad suministro
                $order['zip']      ?? '',                            // V - CP suministro
                $ownerEmail,                                         // W - Usuario propietario
            ], null, "A{$rowNum}");

            $rowNum++;
        }

        // ── Guardar y descargar ─────────────────────────────────────────────
        $filename = 'Oportunidades_' . now()->format('Ymd_His') . '.xlsx';
        $tmpPath  = storage_path('app/tmp/' . $filename);
        @mkdir(dirname($tmpPath), 0775, true);

        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);

    } catch (\Throwable $e) {
        \Log::error('[exportOpportunities] Error', [
            'msg'  => $e->getMessage(),
            'line' => $e->getLine(),
        ]);
        return response()->json(['error' => 'Error generando Excel: ' . $e->getMessage()], 500);
    }
}

    private function returnOpportunityErrorExcel($file, array $failCells, array $failedRows, array $reasonMap, callable $colLetter)
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet       = $spreadsheet->getActiveSheet();

        foreach ($failCells as $rNum => $cells) {
            foreach ($cells as $c) {
                $coord = $colLetter($c['col']) . $rNum;
                $sheet->getStyle($coord)->getFill()->applyFromArray([
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFFFC7CE'],
                ]);
                $comment = $sheet->getComment($coord);
                $comment->getText()->createTextRun($c['msg']);
                $comment->setAuthor('Importación');
            }
        }

        $incSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Incidencias');
        $spreadsheet->addSheet($incSheet);
        $incSheet->setCellValue('A1', 'Fila');
        $incSheet->setCellValue('B1', 'Motivo');
        $incSheet->setCellValue('C1', 'Detalle');
        $incSheet->getStyle('A1:C1')->getFont()->setBold(true);

        $r = 2;
        foreach ($failedRows as $f) {
            $incSheet->setCellValue("A{$r}", $f['rowNumber']);
            $incSheet->setCellValue("B{$r}", $reasonMap[$f['reason']] ?? $f['reason']);
            $incSheet->setCellValue("C{$r}", $f['details']);
            $r++;
        }

        foreach (['A' => 8, 'B' => 35, 'C' => 80] as $col => $width) {
            $incSheet->getColumnDimension($col)->setWidth($width);
        }

        $tmpPath = storage_path('app/tmp/opp_errores_' . uniqid() . '.xlsx');
        @mkdir(dirname($tmpPath), 0775, true);
        (new Xlsx($spreadsheet))->save($tmpPath);

        return response()->download($tmpPath, 'opp_import_errores.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
    private function sendOpportunityCreatedEmailToOwner($opportunity, $ownerId): void
{
    try {
        $owner = User::where('_id', $ownerId)->first();

        if (!$owner) {
            return;
        }

        // Solo para este subdominio
        $allowedSubdomainId = '65cb57489c2c285441086a43';

        $ownerSubdomainId =
            $owner->userSubdomain
            ?? $owner->subdomain
            ?? $owner->subdomainId
            ?? $owner->enterpriseSubdomain
            ?? null;

        // Normalizar si viene como ObjectId/array raro
        if (is_array($ownerSubdomainId) && isset($ownerSubdomainId['$oid'])) {
            $ownerSubdomainId = $ownerSubdomainId['$oid'];
        }

        if ((string) $ownerSubdomainId !== $allowedSubdomainId) {
            return;
        }

        if (empty($owner->email)) {
            return;
        }

        $url = url('/opportunities/' . $opportunity->_id);

        Mail::html(
            "
            <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>
                <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                    <h2 style='color:#2c3e50; margin-bottom:20px;'>
                        Nueva oportunidad creada
                    </h2>

                    <p>Se ha creado una nueva oportunidad asignada a usted.</p>

                    <hr style='margin:20px 0;'>

                    <p><strong>Nombre:</strong> " . e($opportunity->name ?? '-') . "</p>
                    <p><strong>CIF:</strong> " . e($opportunity->CIF ?? '-') . "</p>
                    <p><strong>Teléfono:</strong> " . e($opportunity->phone ?? '-') . "</p>
                    <p><strong>Email:</strong> " . e($opportunity->email ?? '-') . "</p>

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
            function ($message) use ($owner) {
                $message->to($owner->email)
                    ->subject('Nueva oportunidad asignada');
            }
        );
    } catch (\Throwable $e) {
        Log::error('Error enviando email de oportunidad al propietario', [
            'error' => $e->getMessage(),
            'ownerId' => $ownerId,
            'opportunityId' => $opportunity->_id ?? null,
        ]);
    }
}

    public function update(Request $request)
    {
        // Decodifico el objeto enviado desde el cliente
        $opportunity = json_decode($request['opportunity']);

        // Cargo la oportunidad existente de la BBDD
        $opportunityToSave = Opportunity::where('_id', $opportunity->_id)->first();
        $copyOpportunityToSave = json_decode(json_encode($opportunityToSave), true);

        // --- Campos básicos ---
        $opportunityToSave->name    = $opportunity->name ?? '';
        $opportunityToSave->CIF     = $opportunity->CIF ?? '';
        $opportunityToSave->phone   = $opportunity->phone ?? '';
        $opportunityToSave->email   = $opportunity->email ?? '';
        $opportunityToSave->website = $opportunity->website ?? '';
        $opportunityToSave->sector  = $opportunity->sector ?? '';
        $opportunityToSave->source  = $opportunity->source ?? '';
        $opportunityToSave->status  = $opportunity->status ?? '';

        // --- usersIds ---
        $opportunityToSave->usersIds = $opportunity->usersIds ?? $opportunityToSave->usersIds ?? [];

        // --- Billing info ---
        $billing = $opportunityToSave->billingInfo ?? [];
        $billing['community'] = $opportunity->billingInfo->community ?? '';
        $billing['province']  = $opportunity->billingInfo->province ?? '';
        $billing['locality']  = $opportunity->billingInfo->locality ?? '';
        $billing['address']   = $opportunity->billingInfo->address ?? '';
        $billing['postal']    = $opportunity->billingInfo->postal ?? '';
        $opportunityToSave->billingInfo = $billing;

        // --- Campos personalizados (imágenes) ---
        $customFields = $opportunity->customFields ?? [];
        foreach ($customFields as $i => $field) {
            if (($field->type ?? null) === 'image') {
                $file = $request['customFieldFile' . $i] ?? null;

                if ($file && !is_string($file)) {
                    $name = time() . '.' . pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                    // Si cambia la imagen, borro la anterior y guardo la nueva
                    if (!empty($field->imageToDelete)) {
                        Storage::disk('opportunity')->delete($field->imageToDelete);
                    }

                    Storage::disk('opportunity')->put($name, file_get_contents($file));
                    $field->value = $name;
                    unset($field->imageToDelete);
                }
            }
        }
        $opportunityToSave->customFields = $customFields;

        // --- Documentos ---
        $docs = $opportunity->docs ?? $opportunityToSave->docs ?? [];
        foreach ($docs as $idx => $doc) {
            $docFile = $request->file("docFiles.$idx");
            if ($docFile) {
                $extension = pathinfo($docFile->getClientOriginalName(), PATHINFO_EXTENSION);
                $fileName = time() . '_' . $idx . '.' . $extension;
                Storage::disk('order')->put($fileName, file_get_contents($docFile));
                $docs[$idx]->value = $fileName;
            }
        }
        $opportunityToSave->docs = $docs;

        // --- Otros campos ---
        $opportunityToSave->contact      = $opportunity->contact ?? null;
        $opportunityToSave->position     = $opportunity->position ?? '';
        $opportunityToSave->observations = $opportunity->observations ?? '';

        // --- Order / suministro completo ---
        $order = $opportunityToSave->order ?? [];

        $pt        = $opportunity->order->productType ?? '';
        $mk        = $opportunity->order->marketer ?? '';
        $fee       = $opportunity->order->fee ?? '';
        $prod      = $opportunity->order->product ?? '';
        $nameOrder = $opportunity->order->name ?? '';

        $order['productType'] = $pt === '' ? 'sp' : $pt;
        $order['marketer']    = $mk === '' ? 'Sin Comercializadora' : $mk;
        $order['fee']         = $fee === '' ? 'Sin Tarifa' : $fee;
        $order['product']     = $prod === '' ? 'Sin Producto' : $prod;
        $order['name']        = $nameOrder;

        // Campos opcionales / faltantes
        $order['CUPS']            = $opportunity->order->CUPS ?? $order['CUPS'] ?? null;
        $order['province']        = $opportunity->order->province ?? $order['province'] ?? null;
        $order['town']            = $opportunity->order->town ?? $order['town'] ?? null;
        $order['direc']           = $opportunity->order->direc ?? $order['direc'] ?? null;
        $order['zip']             = $opportunity->order->zip ?? $order['zip'] ?? null;
        $order['extras']          = $opportunity->order->extras ?? $order['extras'] ?? [];
        $order['usersIds']        = $opportunity->order->usersIds ?? $order['usersIds'] ?? [];
        $order['feeSecondary']    = $opportunity->order->feeSecondary ?? $order['feeSecondary'] ?? null;
        $order['productSecondary']= $opportunity->order->productSecondary ?? $order['productSecondary'] ?? null;
        $order['CUPSSecondary']   = $opportunity->order->CUPSSecondary ?? $order['CUPSSecondary'] ?? null;

        if (isset($opportunity->order->evChargerBudget)) {
            $order['evChargerBudget'] = json_decode(
                json_encode($opportunity->order->evChargerBudget),
                true
            );
        }

        $opportunityToSave->order = $order;

        // Guardar cambios
        $opportunityToSave->save();

        // Invalida caché del listado para que la próxima carga sea fresca
        Cache::increment('opportunities:index:version');

        $opportunityLog = json_decode(json_encode($opportunityToSave), true);

        // Creo el log de actualización
        AuditLogService::updateOpportunity($copyOpportunityToSave, $opportunityLog, Auth::user());

        return response()->json(['message' => 'La oportunidad ha sido actualizada con éxito'], 200);
    }

    // funcion para sacar el listado de oportunidades
    public function index($id, Request $request)
{
    $forceFresh = $request->boolean('forceFresh', false);

    $cacheVersion  = Cache::get('opportunities:index:version', 1);
    $indexCacheKey = $this->buildIndexCacheKey('opportunities:index', $id, $request, ['version' => $cacheVersion]);

    if (!$forceFresh) {
        $cachedPayload = Cache::get($indexCacheKey);
        if ($cachedPayload !== null) return response()->json($cachedPayload, 200);
    }

    $user = User::where('_id', $id)->first();
    if (!$user) return response()->json(['error' => 'Usuario no encontrado'], 404);

    $userList = json_decode($request->input('userList', '[]'), true) ?? [];
    $filters  = $request->input('filters', []);
    if (!is_array($filters)) $filters = [];

    $sortType         = intval($request->input('sortType', 7));
    $searchRaw        = (string) $request->input('searchOpportunityText', '');
    $searchNormalized = mb_strtolower(preg_replace('/\s+/', '', $searchRaw), 'UTF-8');

    $usersIds = array_merge([$user['_id']], array_column($userList, '_id'));

    $baseQuery = Opportunity::whereIn('usersIds', $usersIds);

    $forceNoResults = function () use ($baseQuery) {
        $baseQuery->whereIn('_id', ['__NO_RESULTS__']);
    };

    // Fechas
    $dates = isset($filters['dates']) && is_array($filters['dates']) ? $filters['dates'] : [];
    if (!empty($dates['start'])) $baseQuery->where('createdAt', '>=', $dates['start'] . ' 00:00:00');
    if (!empty($dates['end']))   $baseQuery->where('createdAt', '<=', $dates['end']   . ' 23:59:59');

    // Agentes
    $agentFilter = isset($filters['agents']) && is_array($filters['agents']) ? $filters['agents'] : null;
    if ($agentFilter !== null) {
        if (count($agentFilter) === 0) { $forceNoResults(); }
        else {
            $ids = array_merge($agentFilter, array_filter(array_map(function($id) {
                try { return new \MongoDB\BSON\ObjectId($id); } catch (\Throwable $e) { return null; }
            }, $agentFilter)));
            $baseQuery->whereIn('usersIds', $ids);
        }
    }

    // Estados
    $statusFilter = isset($filters['statuses']) && is_array($filters['statuses']) ? $filters['statuses'] : null;
    if ($statusFilter !== null) {
        if (count($statusFilter) === 0) { $forceNoResults(); }
        else {
            $includeEmpty = in_array('Sin estado', $statusFilter, true);
            $real = array_values(array_filter($statusFilter, fn($s) => $s !== 'Sin estado'));
            $baseQuery->where(function ($q) use ($real, $includeEmpty) {
                if (!empty($real)) $q->whereIn('status', $real);
                if ($includeEmpty) {
                    $m = !empty($real) ? 'orWhere' : 'where';
                    $q->{$m}(fn($s) => $s->whereNull('status')->orWhere('status', ''));
                }
            });
        }
    }

    // Comercializadoras
    $marketerFilter = isset($filters['marketers']) && is_array($filters['marketers']) ? $filters['marketers'] : null;
    if ($marketerFilter !== null) {
        if (count($marketerFilter) === 0) $forceNoResults();
        else $baseQuery->whereIn('order.marketer', $marketerFilter);
    }

    // Tarifas
    $tariffFilter = isset($filters['tariffs']) && is_array($filters['tariffs']) ? $filters['tariffs'] : null;
    if ($tariffFilter !== null) {
        if (count($tariffFilter) === 0) $forceNoResults();
        else $baseQuery->where(fn($q) => $q->whereIn('order.fee', $tariffFilter)->orWhereIn('order.feeSecondary', $tariffFilter));
    }

    // Productos
    $productFilter = isset($filters['products']) && is_array($filters['products']) ? $filters['products'] : null;
    if ($productFilter !== null) {
        if (count($productFilter) === 0) $forceNoResults();
        else {
            $clean = array_map(fn($p) => $p === 'Sin Producto' ? '' : $p, $productFilter);
            $baseQuery->where(fn($q) => $q->whereIn('order.product', $clean)->orWhereIn('order.productSecondary', $clean));
        }
    }

    // Tipos de producto
    $productTypeFilter = isset($filters['productTypes']) && is_array($filters['productTypes']) ? $filters['productTypes'] : null;
    if ($productTypeFilter !== null) {
        if (count($productTypeFilter) === 0) $forceNoResults();
        else $baseQuery->whereIn('order.productType', $productTypeFilter);
    }

    // Origen
    $originFilter = isset($filters['originTypes']) && is_array($filters['originTypes']) ? $filters['originTypes'] : null;
    if ($originFilter !== null) {
        if (count($originFilter) === 0) { $forceNoResults(); }
        else {
            $baseQuery->where(function ($q) use ($originFilter) {
                $first = true;
                $add = function ($cond) use ($q, &$first) {
                    $first ? $q->where($cond) : $q->orWhere($cond);
                    $first = false;
                };
                if (in_array('facebook', $originFilter)) $add(fn($s) => $s->whereNotNull('metaAdId')->where('metaAdId', '!=', ''));
                if (in_array('cargacar', $originFilter)) $add(fn($s) => $s->whereNotNull('cargacarId')->where('cargacarId', '!=', ''));
                if (in_array('crm', $originFilter))      $add(fn($s) => $s->where(fn($i) => $i->whereNull('metaAdId')->orWhere('metaAdId', ''))->where(fn($i) => $i->whereNull('cargacarId')->orWhere('cargacarId', '')));
                $sourceOrigins = array_filter(array_map(fn($c) => str_starts_with($c, 'source::') ? substr($c, 8) : null, $originFilter));
                if (!empty($sourceOrigins)) {
                    $add(function ($s) use ($sourceOrigins) {
                        $s->where(function ($sq) use ($sourceOrigins) {
                            foreach ($sourceOrigins as $i => $src) {
                                $m = $i === 0 ? 'where' : 'orWhere';
                                $sq->{$m}(fn($sub) => $sub->where('source', $src)->orWhere('source.title', $src)->orWhere(fn($cs) => $cs->where('source.title', 'Personalizado')->where('source.custom', $src)));
                            }
                        });
                    });
                }
            });
        }
    }

    // Búsqueda
    if ($searchNormalized !== '') {
        $regex = new \MongoDB\BSON\Regex(preg_quote($searchNormalized), 'i');
        $baseQuery->where(fn($q) => $q->where('name', 'regex', $regex)->orWhere('CIF', 'regex', $regex)->orWhere('order.name', 'regex', $regex));
    }

    // Ordenación texto
    $sortMap = [0=>['name','asc'],1=>['name','desc'],2=>['email','asc'],3=>['email','desc'],4=>['status','asc'],5=>['status','desc']];
    if (isset($sortMap[$sortType])) {
        [$col, $dir] = $sortMap[$sortType];
        $baseQuery->orderBy($col, $dir);
    }

    // Separar archivadas
    $archivedIds = json_decode(json_encode($user->opportunitiesArchived), true) ?: [];
    $notArchived = (clone $baseQuery)->whereNotIn('_id', $archivedIds)->get();
    $archived    = (clone $baseQuery)->whereIn('_id', $archivedIds)->get();

    // Ordenación por actividad
    if (in_array($sortType, [6, 7], true)) {
        $getTs = function ($item): int {
            foreach (['updatedAt','updated_at','createdAt','created_at'] as $field) {
                $v = data_get($item, $field);
                if ($v instanceof \MongoDB\BSON\UTCDateTime) return $v->toDateTime()->getTimestamp();
                if ($v instanceof \DateTimeInterface) return $v->getTimestamp();
                if (!empty($v)) { $ts = strtotime((string)$v); if ($ts !== false) return $ts; }
            }
            return 0;
        };
        $desc = $sortType === 7;
        $notArchived = $notArchived->sortBy($getTs, SORT_REGULAR, $desc)->values();
        $archived    = $archived->sortBy($getTs, SORT_REGULAR, $desc)->values();
    } elseif (isset($sortMap[$sortType])) {
        [$col, $dir] = $sortMap[$sortType];
        $fn = fn($item) => mb_strtolower((string) data_get($item, $col, ''), 'UTF-8');
        $notArchived = $notArchived->sortBy($fn, SORT_REGULAR, $dir === 'desc')->values();
        $archived    = $archived->sortBy($fn, SORT_REGULAR, $dir === 'desc')->values();
    }

    // Batch-fetch usuarios: una sola query en vez de N queries individuales
    $all = collect($notArchived)->concat($archived);

    $userIds = $all->flatMap(fn($opp) => array_filter(array_merge(
        [$opp->createdBy],
        is_array($opp->usersIds) ? $opp->usersIds : []
    )))->map(fn($id) => (string)$id)->unique()->values()->all();

    $usersById = User::whereIn('_id', $userIds)
        ->select('_id', 'firstName', 'lastName', 'profileImage')
        ->get()
        ->keyBy(fn($u) => (string)$u->_id);

    foreach ($all as $opp) {
        $opp->createdBy = $usersById->get((string)$opp->createdBy);
        // account no se usa en el listado — no se carga
    }

    // Opciones de filtros a partir de los datos ya cargados (sin queries extra)
    $agentOptions = $all
        ->flatMap(fn($opp) => is_array($opp->usersIds) ? $opp->usersIds : [])
        ->map(fn($id) => (string)$id)->unique()
        ->map(fn($id) => $usersById->get($id))
        ->filter()
        ->map(fn($u) => ['_id' => (string)$u->_id, 'name' => trim($u->firstName . ' ' . $u->lastName)])
        ->unique('_id')->values();

    $resultsFilters = [
        'agents'       => $agentOptions,
        'marketers'    => $all->pluck('order.marketer')->filter()->unique()->values(),
        'productTypes' => $all->pluck('order.productType')->filter()->unique()->values(),
        'tariffs'      => $all->flatMap(fn($o) => array_filter([data_get($o, 'order.fee'), data_get($o, 'order.feeSecondary')]))->filter()->unique()->values(),
        'products'     => $all->flatMap(fn($o) => array_filter([data_get($o, 'order.product'), data_get($o, 'order.productSecondary')]))->unique()->values(),
        'originTypes'  => $all->map(fn($o) => !empty($o->metaAdId) ? 'facebook' : (!empty($o->cargacarId) ? 'cargacar' : 'crm'))->unique()->values(),
    ];

    $responsePayload = [
        'opportunities'  => ['notArchived' => $notArchived, 'archived' => $archived],
        'resultsFilters' => $resultsFilters,
    ];

    if (!$forceFresh) Cache::put($indexCacheKey, $responsePayload, now()->addSeconds(30));

    return response()->json($responsePayload, 200);
}








    public function saveActa(Request $request)
    {
        $pdfTmp = null;
        $zipTmp = null;

        try {
            // ── 1. Parsear JSON de metadatos ──────────────────────────────────
            $json = json_decode($request->input('json', '{}'), true) ?? [];

            $opportunityId   = $json['opportunityId']   ?? null;
            $cableMeters     = (int)  ($json['cableMeters']     ?? 0);
            $tuboMeters      = (int)  ($json['tuboMeters']      ?? 0);
            $horaEntrada     = $json['horaEntrada']     ?? '';
            $horaSalida      = $json['horaSalida']      ?? '';
            $duration        = $json['duration']        ?? '';
            $signatureB64    = $json['signature']       ?? null; // data:image/png;base64,...
            $consentAccepted = (bool) ($json['consentAccepted'] ?? false);
            $savedAt         = $json['savedAt']         ?? now()->toISOString();
            $attachmentMeta  = $json['attachmentMeta']  ?? [];

            // ── 2. Cargar oportunidad ─────────────────────────────────────────
            if (!$opportunityId) {
                return response()->json(['ok' => false, 'message' => 'Se necesita el id de la oportunidad'], 422);
            }

            $opportunity = Opportunity::find($opportunityId);

            if (!$opportunity) {
                return response()->json(['ok' => false, 'message' => 'Oportunidad no encontrada'], 404);
            }

            $clientName  = trim($json['clientName']  ?: ($opportunity->name  ?? ''));
            $clientPhone = trim($json['clientPhone'] ?: ($opportunity->phone ?? ''));
            $clientEmail = trim($json['clientEmail'] ?: ($opportunity->email ?? ''));

            $currentDate     = \Carbon\Carbon::parse($savedAt)->format('d/m/Y');
            $currentDateTime = \Carbon\Carbon::parse($savedAt)->format('d/m/Y H:i');
            $fileSuffix      = \Carbon\Carbon::now()->format('Ymd_His');
            $safeName        = preg_replace('/[^A-Za-z0-9_]/u', '_', $clientName ?: 'cliente');

            // ── 3. Generar PDF del acta ───────────────────────────────────────
            $html = view('PDFs.saveAct', [
                'clientName'      => $clientName,
                'clientPhone'     => $clientPhone,
                'clientEmail'     => $clientEmail,
                'opportunity'     => $opportunity,
                'cableMeters'     => $cableMeters,
                'tuboMeters'      => $tuboMeters,
                'horaEntrada'     => $horaEntrada,
                'horaSalida'      => $horaSalida,
                'duration'        => $duration,
                'currentDate'     => $currentDate,
                'currentDateTime' => $currentDateTime,
                'signatureB64'    => $signatureB64,
                'consentAccepted' => $consentAccepted,
                'attachmentMeta'  => $attachmentMeta,
            ])->render();

            $pdfTmp = storage_path('app/tmp_acta_' . uniqid('', true) . '.pdf');
            $isWin  = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            \Spatie\Browsershot\Browsershot::html($html)
                ->setNodeBinary($isWin ? 'C:\\Program Files\\nodejs\\node.exe'                      : '/usr/bin/node')
                ->setNpmBinary ($isWin ? 'C:\\Program Files\\nodejs\\npm.cmd'                       : '/usr/bin/npm')
                ->setChromePath($isWin ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe' : '/usr/bin/chromium')
                ->setEnvironmentOptions(['HOME' => '/tmp', 'XDG_CONFIG_HOME' => '/tmp'])
                ->setOption('args', [
                    '--no-sandbox', '--disable-dev-shm-usage', '--no-zygote',
                    '--disable-gpu', '--user-data-dir=/tmp/browsershot-profile', '--single-process',
                ])
                ->format('A4')
                ->margins(12, 14, 12, 14)
                ->showBackground()
                ->waitUntilNetworkIdle()
                ->save($pdfTmp);

            // ── 4. Crear ZIP ──────────────────────────────────────────────────
            $zipName = "acta_{$safeName}_{$fileSuffix}.zip";
            $zipTmp  = storage_path("app/{$zipName}");

            $zip = new \ZipArchive();
            if ($zip->open($zipTmp, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('No se pudo crear el archivo ZIP');
            }

            // 4a. PDF del acta
            $zip->addFile($pdfTmp, 'saveAct.pdf');

            // 4b. Firma como PNG
            if ($signatureB64 && str_contains($signatureB64, 'base64,')) {
                $zip->addFromString(
                    'firma_cliente.png',
                    base64_decode(explode('base64,', $signatureB64)[1])
                );
            }

            // 4c. Archivos adjuntos subidos (files[0], files[1], …)
            $uploadedFiles = $request->file('files') ?: [];
            foreach ((array) $uploadedFiles as $file) {
                if (!$file || !$file->isValid()) continue;
                $fn = preg_replace('/[^\w.\-]/u', '_', $file->getClientOriginalName());
                $zip->addFile($file->getRealPath(), "adjuntos/{$fn}");
            }

            $zip->close();

            // ── 5. Guardar ZIP en Storage y añadir a docs de la oportunidad ──
            Storage::disk('order')->put($zipName, file_get_contents($zipTmp));

            $docs   = is_array($opportunity->docs) ? $opportunity->docs : [];
            $docs[] = [
                'title'        => 'Acta de instalación · ' . $currentDate,
                'defaultTitle' => $zipName,
                'value'        => $zipName,
                'icon'         => 'fa-file-zipper',
                'id'           => 'acta-' . uniqid('', true),
                'errors'       => (object) [],
            ];
            $opportunity->docs = $docs;
            $opportunity->save();

            return response()->json([
                'ok'      => true,
                'message' => 'Acta guardada y ZIP adjuntado a la oportunidad',
            ]);

        } catch (\Throwable $e) {
            Log::error('[saveActa] Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);
            return response()->json([
                'ok'      => false,
                'message' => 'Error guardando el acta: ' . $e->getMessage(),
            ], 500);

        } finally {
            // Limpiar temporales siempre, haya error o no
            if ($pdfTmp && file_exists($pdfTmp)) @unlink($pdfTmp);
            if ($zipTmp && file_exists($zipTmp)) @unlink($zipTmp);
        }
    }
    private function buildIndexCacheKey(string $prefix, string $id, Request $request, array $extra = []): string
    {
        $payload = array_merge($request->all(), $extra);
        $payload = $this->normalizeCachePayload($payload);

        return $prefix . ':' . $id . ':' . md5(json_encode($payload));
    }

    private function normalizeCachePayload($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeCachePayload($item);
        }

        ksort($value);

        return $value;
    }



    public function indexWithoutPagination($id, Request $request)
    {

        $user = User::where('_id', $id)->first();
        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $userList = json_decode($request->input('userList', '[]'), true);

        $userList = array_column($userList, '_id');

        if (!collect($userList)->pluck('_id')->contains($user['_id'])) {
            $userList[] = $user['_id'];
        }

        $opportunities = Opportunity::whereIn('usersIds', $userList)->get();

        return response()->json(['opportunities'  => $opportunities], 200);
    }



    //funcion para sacar unas oportunidad en particular
    public function show($id)
    {

        $opportunity = Opportunity::where('_id', $id)->first();

        //Correos relacionados
        $opportunity['mails'] = Email::where('recipients.email', $opportunity['email'])->get()->toArray();

        //Le meto los datos del usuario que lo ha creado
        $opportunity['createdBy'] = User::where('_id', $opportunity['createdBy'])->first();

        return response()->json(['opportunity' => $opportunity], 200);
    }


    //funcion para eliminar una oportunidad
    public function deleteOpportunity($id)
    {

        //Lo saco de el array de oportunidades del usuario
        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        //Tengo que comprobar si esta la id de la oportunidad dentro de el array de oportunidadse archivadas, y si lo esta, borrarlo
        $opportunitiesArchived = $userToModify->opportunitiesArchived;

        $position = array_search($id, $opportunitiesArchived);

        if ($position)
            unset($opportunitiesArchived[$position]);


        $userToModify->opportunitiesArchived = $opportunitiesArchived;

        $userToModify->save();

        //Borro la oportunidad de la bbdd
        $opportunity = Opportunity::find($id); // obtienes el modelo ANTES
        AuditLogService::createOrDeleteOpportunity($opportunity->toArray(), Auth::user(), 'delete');
        $opportunity->delete();

        return response()->json(['message' => 'La oportunidad ha sido eliminado correctamente'], 200);
    }

    //funcion para eliminar todas las oportunidades seleccionados
    public function deleteAllSelectedOpportunities(Request $request)
    {

        $idsToRemove = $request['idsToRemove'];

        //Lo saco de el array de oportunidades del usuario
        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $opportunitiesArchived = $userToModify->opportunitiesArchived;

        //Para cada uno compruebo si esta en el array de archivados, si esta lo saco y este o no lo borro
        foreach ($idsToRemove as $id) {

            if (in_array($id, $opportunitiesArchived))
                unset($opportunitiesArchived[$id]);

            //Borro la oportunidad de la bbdd
            Opportunity::destroy($id);
        }

        $userToModify->opportunitiesArchived = $opportunitiesArchived;

        $userToModify->save();

        return response()->json(['message' => 'Las oportunidades han sido eliminadas correctamente'], 200);
    }


    //funcion para archivar una oportunidad
    public function toggleArchiveOpportunity($id, Request $request)
    {

        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $opportunitiesArchived = $userToModify->opportunitiesArchived;


        if (in_array($id, $opportunitiesArchived)) {
            $key = array_search($id, $opportunitiesArchived);
            unset($opportunitiesArchived[$key]);
        } else {
            array_push($opportunitiesArchived, $id);
        }


        $userToModify->opportunitiesArchived = $opportunitiesArchived;

        $userToModify->save();

        return response()->json(['message' => 'La archivación ha sido alterada'], 200);
    }

    //funcion para archivar todas las oportunidades seleccionadas
    public function toggleArchiveSelectedOpportunities(Request $request)
    {

        $idsToToggle = $request['idsToToggle'];

        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $opportunitiesArchived = $userToModify->opportunitiesArchived;

        //si es para archivar
        foreach ($idsToToggle as $id) {
            if ($request['isForArchiving']) {
                array_push($opportunitiesArchived, $id);
            } else {

                $key = array_search($id, $opportunitiesArchived);

                unset($opportunitiesArchived[$key]);
            }
        }

        $userToModify->opportunitiesArchived = $opportunitiesArchived;

        $userToModify->save();

        return response()->json(['message' => 'La archivación ha sido alterada'], 200);
    }


    //funcion para crear una cuenta desde un oportunidad
    public function createAccFromOpp(Request $request)
    {

        $opportunity = json_decode($request['opportunity']);

        //paso las imagenes a accounts
        $customFields = $opportunity->customFields;

        foreach ($customFields as $field) {
            // Si el campo tiene un valor que corresponde al nombre de un archivo de imagen
            if ($field->type === 'image' && !empty($field->value)) {
                $imageName = $field->value;

                // Comprobar si la imagen existe en 'opportunity'
                if (Storage::disk('opportunity')->exists($imageName)) {

                    // Obtener el contenido del archivo de 'opportunity'
                    $imageContent = Storage::disk('opportunity')->get($imageName);

                    // Guardar la imagen en el disco 'account'
                    Storage::disk('account')->put($imageName, $imageContent);

                    // Eliminar la imagen del disco 'opportunity'
                    Storage::disk('opportunity')->delete($imageName);
                }
            }
        }

        //Creo la cuenta
        $accountId = Account::insertGetId([
            'name' => $opportunity->name,
            'accType' => '',
            'sector' => $opportunity->sector,
            'CIF' => $opportunity->CIF,
            'origin' => $opportunity->source,
            'phone' => $opportunity->phone,
            'website' => $opportunity->website,
            'email' => $opportunity->email,
            'observations' => '',
            'principalAcc' => '',
            'billingInfo' => [
                'community' => $opportunity->billingInfo->community,
                'province' => $opportunity->billingInfo->province,
                'locality' => $opportunity->billingInfo->locality,
                'address' => $opportunity->billingInfo->address,
                'zipCode' => $opportunity->billingInfo->postal
            ],
            'customFields' => $opportunity->customFields,
            'profileImage' => null,
            'orders' => [],
            'usersIds' => $opportunity->usersIds,
            'createdBy' => $opportunity->createdBy,
            'createdAt' => $opportunity->createdAt
        ], '_id');

        //Elimino la oportunidad
        Opportunity::destroy($opportunity->_id);

        return response()->json(['accId' => $accountId], 200);
    }


    //función para sacar los contactos relacionados
    public static function getRelatedContacts($id, Request $request){

        $userList = $request['userList'];

        //Saco las cuentas que tengas en usersId la id del usuario con sesion iniciada
        $contactsRelated = Contact::whereIn('usersIds', [$id])->orWhere('createdBy', $id)->get();

        //Saco tb las cuentas de los usuarios de por debajo tuya
        if ($userList && count($userList) > 0) {
            foreach ($userList as $userInd => $user) {
                $contactsRelated = [...$contactsRelated, ...Contact::whereIn('usersIds', [$user['_id']])->orWhere('createdBy', $user['_id'])->get()];
            }
        }

        return response()->json(['contacts' => $contactsRelated]);
    }


    //función para obtener las cuentas relacionadas a una oportunidad
    public function getRelatedAccounts($id, Request $request)
    {

        $accounts = Account::where('opportunity', $id)->get();

        return response()->json(['opportunityAccounts' => $accounts]);
    }

    public function importFacebookLeads(Request $request)
    {
        try {
            $userLogged = Auth::user();

            if (!$userLogged) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $pageId = env('META_PAGE_ID', '102839272433421');
            $token  = $this->getMetaPageAccessToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Falta META_PAGE_ACCESS_TOKEN en .env',
                ], 500);
            }

            $ownerFixedId = '698340c75525f31823005652';
            $owner = User::where('_id', $ownerFixedId)->first();

            if (!$owner) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existe el usuario propietario configurado',
                    'ownerId' => $ownerFixedId,
                ], 500);
            }

            if (empty($owner->email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario propietario no tiene email',
                    'ownerId' => $ownerFixedId,
                ], 500);
            }

            $formMap = [
                '1681170659970164' => [
                    'label'       => 'Cargadores eléctricos',
                    'productType' => 'ce',
                    'source'      => 'Facebook Lead Ads - Cargadores eléctricos',
                ],
                '946641331614671' => [
                    'label'       => 'Autoconsumo',
                    'productType' => 'a',
                    'source'      => 'Facebook Lead ',
                ],
                '1999652884256678' => [
                    'label'       => 'Contratos de Autoconsumo',
                    'productType' => 'a',
                    'source'      => 'Facebook Lead Ads - Autoconsumo',
                ],
                '2206192880196231' => [
                    'label'       => 'Contratos de Autoconsumo',
                    'productType' => 'a',
                    'source'      => 'Facebook Lead Ads - Autoconsumo',
                ],
                '947850204819761' => [
                    'label'       => 'Facebook Lead Ads',
                    'productType' => 'sp',
                    'source'      => 'Facebook Lead Ads',
                ],
                '1291966882303684' => [
                    'label'       => 'Facebook Lead Ads',
                    'productType' => 'sp',
                    'source'      => 'Facebook Lead Ads',
                ],
                '950712647775233' => [
                    'label'       => 'Facebook Lead Ads',
                    'productType' => 'sp',
                    'source'      => 'Facebook Lead Ads',
                ],
                '1504605114378101' => [
                    'label'       => 'Coches eléctricos',
                    'productType' => 'ce',
                    'source'      => 'Facebook Lead Ads',
                ],

            ];

            $created = 0;
            $skipped = 0;
            $errors  = [];
            $createdOpportunities = [];

            $formsResponse = Http::get("https://graph.facebook.com/v20.0/{$pageId}/leadgen_forms", [
                'fields' => 'id,name,status,created_time',
                'limit' => 100,
                'access_token' => $token,
            ]);

            if (!$formsResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error obteniendo formularios de Meta',
                    'details' => $formsResponse->json(),
                ], $formsResponse->status());
            }

            $forms = $formsResponse->json('data') ?? [];

            foreach ($forms as $form) {
                $formId = (string) ($form['id'] ?? '');

                if (!$formId) {
                    continue;
                }

                $after = null;

                do {
                    $leadParams = [
                        'fields' => implode(',', [
                            'id',
                            'created_time',
                            'field_data',
                            'form_id',
                            'ad_id',
                            'ad_name',
                            'adset_id',
                            'adset_name',
                            'campaign_id',
                            'campaign_name',
                        ]),
                        'limit' => 100,
                        'access_token' => $token,
                    ];

                    if ($after) {
                        $leadParams['after'] = $after;
                    }

                    $leadsResponse = Http::get("https://graph.facebook.com/v20.0/{$formId}/leads", $leadParams);

                    if (!$leadsResponse->successful()) {
                        $errors[] = [
                            'form_id' => $formId,
                            'form_name' => $form['name'] ?? '',
                            'error' => $leadsResponse->json(),
                        ];

                        break;
                    }

                    $leads = $leadsResponse->json('data') ?? [];

                    foreach ($leads as $lead) {

                        $leadId = $lead['id'] ?? null;

                        if (!$leadId) {
                            $skipped++;
                            continue;
                        }

                        $createdTime = $lead['created_time'] ?? null;

                        if (!$createdTime) {
                            $skipped++;
                            continue;
                        }

                        $fields = collect($lead['field_data'] ?? [])
                            ->mapWithKeys(function ($field) {
                                return [
                                    strtoupper($field['name'] ?? '') => $field['values'][0] ?? null,
                                ];
                            })
                            ->toArray();

                        $name  = $fields['FULL_NAME'] ?? $fields['NAME'] ?? 'Sin nombre';
                        $email = $fields['EMAIL'] ?? '';
                        $phone = $this->normalizeImportedPhone($fields['PHONE'] ?? $fields['PHONE_NUMBER'] ?? '');

                        if (Opportunity::where('metaLeadId', $leadId)->orWhere('phone', $phone)->exists()) {
                            $skipped++;
                            continue;
                        }

                        $formIdForMap = (string) ($lead['form_id'] ?? $formId);


                        $campaignInfo = $formMap[$formIdForMap] ?? [
                            'label'       => 'Facebook Lead Ads',
                            'productType' => 'sp',
                            'source'      => 'Facebook Lead Ads'
                        ];

                        $opportunity = Opportunity::create([
                            'name'          => $name,
                            'CIF'           => '',
                            'phone'         => $phone,
                            'landLinePhone' => '',
                            'email'         => $email,
                            'website'       => '',
                            'sector'        => '',
                            'source'        => $campaignInfo['source'],
                            'status'        => 'Pendiente',

                            'contact' => [
                                'value' => $name,
                                'isFromContacts' => false,
                            ],

                            'position' => '',

                            'observations' => "",

                            'billingInfo' => [
                                'community' => '',
                                'province'  => '',
                                'locality'  => '',
                                'address'   => '',
                                'postal'    => '',
                            ],

                            'customFields' => [
                                [
                                    'title' => 'Origen Facebook',
                                    'type'  => 'text',
                                    'value' => 'Lead Ads',
                                ],

                            ],

                            'order' => [
                                'productType' => $campaignInfo['productType'],
                                'marketer'    => 'Sin Comercializadora',
                                'fee'         => 'Sin Tarifa',
                                'CUPS'        => '',
                                'province'    => '',
                                'town'        => '',
                                'direc'       => null,
                                'zip'         => '',
                                'extras'      => [],
                            ],

                            'metaLeadId'       => $leadId,
                            'metaFormId'       => $formIdForMap,
                            'metaFormName'     => $form['name'] ?? '',
                            'metaCampaignId'   => $lead['campaign_id'] ?? '',
                            'metaCampaignName' => $lead['campaign_name'] ?? '',
                            'metaAdsetId'      => $lead['adset_id'] ?? '',
                            'metaAdsetName'    => $lead['adset_name'] ?? '',
                            'metaAdId'         => $lead['ad_id'] ?? '',
                            'metaAdName'       => $lead['ad_name'] ?? '',
                            'metaCreatedTime'  => $createdTime,
                            'metaRaw'          => $lead,

                            'usersIds'  => [$ownerFixedId],
                            'createdBy' => $userLogged->_id,
                            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
                        ]);

                        //Envío whatsapp
                        $this->startWhatsapp('34' . $phone, $opportunity['_id']);

                        try {
                            AuditLogService::createOrDeleteOpportunity($opportunity, $userLogged, 'create');
                        } catch (\Throwable $e) {
                        }

                        try {
                            Mail::raw(
                                "Nueva oportunidad creada desde Facebook\n\n" .
                                "Nombre: " . ($opportunity->name ?? '-') . "\n" .
                                "Teléfono: " . ($opportunity->phone ?? '-') . "\n" .
                                "Email: " . ($opportunity->email ?? '-') . "\n" .
                                "Origen: " . ($opportunity->source ?? '-') . "\n" .
                                "Campaña: " . ($opportunity->metaCampaignName ?? '-') . "\n" .
                                "Formulario: " . ($opportunity->metaFormName ?? '-') . "\n" .
                                "URL: " . url('/opportunities/' . $opportunity->_id),
                                function ($message) use ($owner) {
                                    $message->to([
                                        $owner->email,
                                        'pacoaguilar@segenet.es',
                                    ])->subject('Nueva oportunidad de Facebook asignada');
                                }
                            );
                        } catch (\Throwable $e) {
                            return response()->json([
                                'success' => false,
                                'message' => 'La oportunidad se ha creado pero el email NO se ha enviado',
                                'error' => $e->getMessage(),
                                'line' => $e->getLine(),
                                'to' => [
                                    $owner->email,
                                    'pacoaguilar@segenet.es',
                                ],
                                'opportunity_id' => (string) $opportunity->_id,
                            ], 500);
                        }

                        $created++;

                        $createdOpportunities[] = [
                            'opportunity_id' => (string) $opportunity->_id,
                            'lead_id' => $leadId,
                            'name' => $name,
                            'email' => $email,
                            'phone' => $phone,
                            'form_id' => $formIdForMap,
                            'campaign' => $campaignInfo['label'],
                            'productType' => $campaignInfo['productType'],
                            'created_time' => $createdTime,
                            'owner_id' => $ownerFixedId,
                            'owner_email' => $owner->email,
                        ];
                    }

                    $after = $leadsResponse->json('paging.cursors.after') ?? null;
                    $hasNext = !empty($leadsResponse->json('paging.next'));

                } while ($after && $hasNext);
            }

            return response()->json([
                'success' => true,
                'message' => 'Importación de leads de Facebook de hoy completada',
                'created' => $created,
                'skipped' => $skipped,
                'errors' => $errors,
                'opportunities' => $createdOpportunities,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importando leads de Facebook',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }


        public function logEvChargerProgress(Request $request)
    {
        try {
            AuditLog::create([
                'type'      => 'ev_charger_budget',
                'event'     => 'progress',
                'createdBy' => "65cb57489c2c285441086a43",
                'createdAt' => now()->format('Y-m-d H:i:s'),
                'metadata'  => [
                    'step'          => $request->input('step'),
                    'stepLabel'     => $request->input('stepLabel'),
                    'name'          => $request->input('clientName'),
                    'phone'         => $request->input('clientPhone'),
                    'email'         => $request->input('clientEmail'),
                    'chargerModel'  => $request->input('chargerModel'),
                    'chargerPower'  => $request->input('chargerPower'),
                    'cableMeters'   => $request->input('cableMeters'),
                    'hasAutoconsumo'=> $request->input('hasAutoconsumo'),
                    '_id'           => $request->input('opportunityId'),
                ],
            ]);
        } catch (\Throwable $e) { /* silencioso */ }

        return response()->json(['ok' => true]);
    }


    //Función para enviar whatsapp de captación o contacto con cliente
    private function startWhatsapp($to, $opportunity)
    {
        // SOLO PARA TEST
        $to = '34605581287';

        $to = preg_replace('/\D+/', '', $to);

        if (strlen($to) === 9) {
            $to = '34' . $to;
        }

        $enterprise = Enterprise::where('whapi.name', 'ZOCO')
            ->where('whapi.enabled', true)
            ->firstOrFail();

        $whapi = $enterprise['whapi'] ?? [];

        $channelId = $whapi['channel_id'] ?? null;
        $name = $whapi['name'] ?? null;

        if (!$channelId) {
            throw new \Exception('La empresa no tiene whapi.channel_id configurado.');
        }

        if (!$name) {
            throw new \Exception('La empresa no tiene whapi.name configurado.');
        }

        $token = env('WHATSAPP_TOKEN_' . $name);

        if (!$token) {
            throw new \Exception('No existe token Whapi en .env: WHATSAPP_TOKEN_' . $name);
        }

        /*
         * MUY IMPORTANTE:
         * receiveMessage() busca sesión por phone + instance.
         * Por eso borramos cualquier sesión anterior de ese teléfono con este channel_id.
         */
        WhatsAppSession::where('phone', $to)
            ->where('instance', $channelId)
            ->delete();

        /*
         * Limpiamos interactivo activo anterior.
         * Así evitamos que guardInteractiveReply() compare contra botones antiguos.
         */
        Cache::forget('wa_active_interactive:' . $channelId . ':' . $to);

        $session = WhatsAppSession::create([
            'phone' => $to,
            'instance' => $channelId,
            'step' => 'external_opp_options',
            'type' => 'external_opportunity',
            'opportunity_id' => (string) $opportunity,
            'app' => 'crm',
        ]);

        $message = "Hemos recibido tu solicitud para instalar un cargador de vehículo eléctrico.\n\nElige una opción:";

        $options = [
            [
                'id' => '1',
                'title' => 'Hablar con agente',
            ],
            [
                'id' => '2',
                'title' => 'Calcular presupuesto',
            ],
        ];

        $driver = new WhapiDriver($token);

        $response = $driver->sendOptions(
            $to,
            $message,
            $options
        );

        Log::info('WHAPI external opportunity start', [
            'to' => $to,
            'opportunity' => (string) $opportunity,
            'session_id' => (string) ($session->_id ?? ''),
            'channel_id' => $channelId,
            'response' => $response,
        ]);

        $sentMessageId = $this->extractWhapiSentMessageId($response);

        if ($sentMessageId) {
            Cache::put(
                'wa_active_interactive:' . $channelId . ':' . $to,
                $sentMessageId,
                now()->addHours(24)
            );

            Log::info('WHAPI external active interactive saved', [
                'to' => $to,
                'channel_id' => $channelId,
                'sentMessageId' => $sentMessageId,
            ]);
        } else {
            Log::warning('WHAPI external no sent message id found', [
                'to' => $to,
                'channel_id' => $channelId,
                'response' => $response,
            ]);
        }

        return $response;
    }

    private function extractWhapiSentMessageId($response): ?string
    {
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

}
