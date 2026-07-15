<?php

namespace App\Http\Controllers;

use App\Http\Models\Opportunity;
use App\Http\Models\WhatsAppSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ScrapingController extends Controller
{
    public function confirmarEntrada()
    {
        $scriptPath   = base_path('scripts/descargar_excel.js');
        $downloadPath = base_path('scripts/downloads');

        $output    = [];
        $returnVar = 0;

        exec('node ' . escapeshellarg($scriptPath) . ' 2>&1', $output, $returnVar);

        if ($returnVar !== 0) {
            return response()->json([
                'success'     => false,
                'message'     => 'Error ejecutando el script Node',
                'return_code' => $returnVar,
                'output'      => $output,
            ], 500);
        }

        if (!is_dir($downloadPath)) {
            return response()->json([
                'success' => false,
                'message' => 'La carpeta de descargas no existe',
                'output'  => $output,
            ], 500);
        }

        $excelFiles = collect(scandir($downloadPath))
            ->filter(fn($f) => preg_match('/\.(xls|xlsx)$/i', $f))
            ->values();

        if ($excelFiles->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El script terminó pero no se encontró ningún archivo en la carpeta',
                'output'  => $output,
            ], 500);
        }

        $latestFile = $excelFiles
            ->sortByDesc(fn($f) => filemtime($downloadPath . '/' . $f))
            ->first();

        $fullPath = $downloadPath . '/' . $latestFile;

        try {
            $html = file_get_contents($fullPath);

            if (!$html || stripos($html, '<table') === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo descargado no contiene una tabla HTML válida',
                ], 500);
            }

            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new \DOMXPath($dom);

            $table = $xpath->query("//table[@id='tblDatos']")->item(0);

            if (!$table) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la tabla #tblDatos en el archivo',
                ], 500);
            }

            $headers = [];
            foreach ($xpath->query(".//thead/tr/th", $table) as $th) {
                $headers[] = trim($th->textContent);
            }

            $userLogged = Auth::user();
            $created    = 0;
            $skipped    = 0;
            $errors     = [];

            foreach ($xpath->query(".//tbody/tr", $table) as $index => $tr) {
                $cells = $xpath->query(".//td", $tr);

                // ── Extraer ID único y fecha del primer <td> ──────────────────
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
                        if (!$foundBr && $child->nodeName === 'a') {
                            $fechaTexto = trim($child->textContent);
                        }
                        if ($foundBr && $child->nodeType === XML_TEXT_NODE) {
                            $cargacarId = trim($child->textContent);
                            break;
                        }
                    }
                }

                // ── Construir rowData ─────────────────────────────────────────
                $rowData = [];
                foreach ($cells as $i => $td) {
                    $header          = $headers[$i] ?? 'extra_' . $i;
                    $rowData[$header] = trim($td->textContent);
                }
                $rowData['Fecha'] = $fechaTexto;

                if (empty(array_filter($rowData, fn($v) => trim((string)$v) !== ''))) {
                    continue;
                }

                $nombre   = trim($rowData['Nombre']   ?? '');
                $telefono = trim($rowData['Teléfono'] ?? '');

                if ($nombre === '' && $telefono === '') {
                    $skipped++;
                    $errors[] = ['row' => $index + 1, 'reason' => 'Fila sin nombre ni teléfono'];
                    continue;
                }

                // ── Deduplicación por cargacarId ──────────────────────────────
                if ($cargacarId !== '' && Opportunity::where('cargacarId', $cargacarId)->exists()) {
                    $skipped++;
                    $errors[] = [
                        'row'        => $index + 1,
                        'reason'     => 'Ya existe una oportunidad con este ID',
                        'cargacarId' => $cargacarId,
                    ];
                    continue;
                }

                $telefonoNormalizado = $this->normalizePhone($telefono);

                $opportunity = Opportunity::create([
                    'name'         => $nombre !== '' ? $nombre : 'Oportunidad_' . $cargacarId,
                    'CIF'          => '',
                    'phone'        => $telefonoNormalizado,
                    'landLinePhone'=> '',
                    'email'        => trim($rowData['Email']  ?? ''),
                    'website'      => '',
                    'sector'       => '',
                    'source'       => trim($rowData['Origen'] ?? ''),
                    'status'       => 'Pendiente',

                    'contact'      => [
                        'value'          => trim($rowData['Contacto'] ?? ''),
                        'isFromContacts' => false,
                    ],

                    'position'     => '',
                    'observations' => '',

                    'billingInfo'  => [
                        'community' => '',
                        'province'  => trim($rowData['Zona'] ?? ''),
                        'locality'  => trim($rowData['Zona'] ?? ''),
                        'address'   => '',
                        'postal'    => trim($rowData['CP'] ?? ''),
                    ],

                    'customFields' => [
                        [
                            'title' => 'Fecha origen',
                            'type'  => 'text',
                            'value' => $rowData['Fecha'] ?? '',
                        ],
                        [
                            'title' => 'Presupuesto',
                            'type'  => 'text',
                            'value' => $rowData['Presupuesto'] ?? '',
                        ],
                    ],

                    'order'        => [
                        'productType' => 'sp',
                        'marketer'    => 'Sin Comercializadora',
                        'fee'         => 'Sin Tarifa',
                        'product'     => 'ce',
                        'CUPS'        => '',
                        'province'    => trim($rowData['Zona'] ?? ''),
                        'town'        => trim($rowData['Zona'] ?? ''),
                        'direc'       => null,
                        'zip'         => trim($rowData['CP'] ?? ''),
                        'extras'      => [],
                    ],

                    'cargacarId'   => $cargacarId,
                    'usersIds'     => ["698340c75525f31823005652"],
                    'createdBy'    => $userLogged->_id,
                    'createdAt'    => Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                //Envío whatsapp
                $this->startWhatsapp('34' . $telefonoNormalizado, $opportunity['_id']);//653062438 paco

                try {
                    $emails = [
                        'giacomo@zocoenergia.com',
                        'pacoaguilar@segenet.es',
                    ];

                    $url = url('/opportunities/' . $opportunity->_id);

                    Mail::html(
                        "
                        <div style='font-family: Arial, sans-serif; background:#f5f7fa; padding:20px;'>
                            <div style='max-width:600px; margin:auto; background:white; border-radius:10px; padding:25px;'>

                                <h2 style='color:#2c3e50; margin-bottom:20px;'>
                                    Nueva oportunidad de Cargacar creada
                                </h2>

                                <p>Se ha creado una nueva oportunidad desde Cargacar.</p>

                                <hr style='margin:20px 0;'>

                                <p><strong>Nombre:</strong> " . e($opportunity->name ?? '-') . "</p>
                                <p><strong>Teléfono:</strong> " . e($opportunity->phone ?? '-') . "</p>
                                <p><strong>Email:</strong> " . e($opportunity->email ?? '-') . "</p>
                                <p><strong>Zona:</strong> " . e($rowData['Zona'] ?? '-') . "</p>


                                <div style='text-align:center; margin-top:30px;'>
                                    <a href='" . e($url) . "' style='
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


                            </div>
                        </div>
                        ",
                        function ($message) use ($emails) {
                            $message->to($emails)
                                ->subject('Nueva oportunidad Cargacar');
                        }
                    );

                } catch (\Throwable $e) {
                    Log::error('Error enviando email de oportunidad Cargacar', [
                        'error'         => $e->getMessage(),
                        'opportunityId' => $opportunity->_id ?? null,
                        'cargacarId'    => $cargacarId,
                    ]);
                }


                $created++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Importación completada',
                'created' => $created,
                'skipped' => $skipped,
                'errors'  => $errors,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error procesando el archivo',
                'error'   => $e->getMessage(),
            ], 500);

        } finally {
            // Se borra siempre, tanto si fue bien como si hubo error
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', trim((string) $phone));

        if (str_starts_with($phone, '34') && strlen($phone) > 9) {
            $phone = substr($phone, 2);
        }

        return $phone;
    }


    //Función para enviar whatsapp de captación o contacto con cliente
    private function startWhatsapp($to, $opportunity) {

        // Borro sesiones anteriores iguales
        WhatsAppSession::where('phone', $to)->where('instance', env('WHATSAPP_INSTANCE_ID_ZOCO'))->where('type', 'external_opportunity')->delete();

        //Creo la sesión de tipo external_opportunity
        WhatsAppSession::create(['phone' => $to, 'instance' => env('WHATSAPP_INSTANCE_ID_ZOCO'), 'step' => 'external_opp_options', 'type' => 'external_opportunity', 'opportunity_id' => $opportunity]);

        $baseUrl = "https://api.ultramsg.com/" . env('WHATSAPP_INSTANCE_ID_ZOCO') . "/messages";

        Http::post($baseUrl . '/chat', [
            'token' => env('WHATSAPP_TOKEN_ZOCO'),
            'to' => '+' . $to,
            'body' => "Hemos recibido tu solicitud para instalar un cargador de vehículo eléctrico.\n\nResponde con el número de la opción que prefieras:\n\n1️⃣ Hablar con un agente\n2️⃣ Calcular presupuesto"
        ]);
    }

}
