<?php

namespace App\Http\Controllers;

use App\Classes\Segenet\ProbeCloseReportClass;
use App\Classes\Segenet\Resume;
use App\Exports\Segenet\ClosesExport;
use App\Exports\Segenet\MockInvoiceExport;
use App\Exports\Segenet\ProbeValuesQuartersExport;
use App\Helpers\SegenetHelper;
use App\Helpers\UserHelper;
use App\Http\Jobs\MassiveSendJob;
use App\Http\Models\Enterprise;
use App\Http\Models\Order;
use App\Http\Models\Account;
use App\Http\Models\Datadis;
use App\Http\Models\User;
use App\Http\Models\Email;
use App\Models\Segenet\Close;
use App\Models\Segenet\Probe;
use App\Models\Segenet\ProbeValuesQuarter;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use GuzzleHttp\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use MongoDB\BSON\ObjectId;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Spatie\PdfToImage\Pdf as PdfToImage;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;



class ToolsController extends Controller
{
    //Liquidar  tipo comercializadora CUPS de excel
    public function liquidateCUPS(Request $request)
    {
        $file = $request->file('file');
        $excel = Excel::toArray([], $file);

        $userLogged = json_decode($request->input('userLogged'), true);
        $userList = json_decode($request->input('userList'), true) ?? [];

        $allowedUserIds = [];

        if (!empty($userLogged['_id'])) {
            $allowedUserIds[] = $userLogged['_id'];
        }

        foreach ($userList as $u) {
            if (!empty($u['_id'])) {
                $allowedUserIds[] = $u['_id'];
            }
        }

        $allowedUserIds = array_values(array_unique($allowedUserIds));

        $startRow = 2;
        $columnCUPS = 0;
        $columnStatus = 1;

        $statusMap = [
            'no liquidado' => 'nl',
            'liquidado agente' => 'al',
            'liquidado comerc.' => 'cl',
            'liquidado comerc' => 'cl',
            'total liquidado' => 'tl',
            'decomisionado agente' => 'ad',
            'decomisionado comercializadora' => 'md',
            'total decomisionado' => 'tm',
        ];

        $result = [];
        $failedRows = [];
        $processedCups = [];

        foreach ($excel[0] as $rowIndex => $row) {

            if ($rowIndex < $startRow) {
                continue;
            }

            // 🟢 IGNORAR filas totalmente vacías (NO cuentan como aviso)
            $isEmptyRow = collect($row)->every(function ($cell) {
                return $cell === null || trim((string) $cell) === '';
            });

            if ($isEmptyRow) {
                continue;
            }

            $excelRow = $rowIndex + 1;
            $cups = $row[$columnCUPS] ?? null;

            // ❌ CUPS vacío (aquí SÍ es aviso)
            if (!$cups || trim((string) $cups) === '') {
                $failedRows[] = [
                    'row' => $excelRow,
                    'cups' => null,
                    'reason' => 'CUPS vacío'
                ];
                continue;
            }

            $cups = str_replace('0F', '', trim($cups));

            // 🔁 Duplicado en el Excel
            if (in_array($cups, $processedCups)) {
                $failedRows[] = [
                    'row' => $excelRow,
                    'cups' => $cups,
                    'reason' => 'CUPS duplicado en el Excel'
                ];
                continue;
            }

            $processedCups[] = $cups;

            // 🔍 Buscar contratos SOLO del subdominio
            $orders = Order::where('CUPS', $cups)
                ->whereIn('usersIds', $allowedUserIds)
                ->get();

            if ($orders->isEmpty()) {
                $failedRows[] = [
                    'row' => $excelRow,
                    'cups' => $cups,
                    'reason' => 'No se encontró ningún contrato con ese CUPS'
                ];
                continue;
            }

            $currentOrder = $orders
                ->sortByDesc(fn($o) => $this->getEffectiveDate($o))
                ->first();

            if (!$currentOrder) {
                $failedRows[] = [
                    'row' => $excelRow,
                    'cups' => $cups,
                    'reason' => 'No se pudo determinar el contrato vigente'
                ];
                continue;
            }

            $oldStatus = $currentOrder->liquidationStatus ?? null;

            // 📥 Leer estado desde Excel
            $excelStatusRaw = $row[$columnStatus] ?? null;
            $excelStatus = null;

            if ($excelStatusRaw !== null && trim((string) $excelStatusRaw) !== '') {
                $key = strtolower(trim($excelStatusRaw));
                $excelStatus = $statusMap[$key] ?? null;

                if (!$excelStatus) {
                    $failedRows[] = [
                        'row' => $excelRow,
                        'cups' => $cups,
                        'reason' => 'Estado de liquidación no válido'
                    ];
                    continue;
                }
            }

            // 🎯 Decidir estado final
            if ($excelStatus) {
                $newStatus = $excelStatus;
            } else {
                $newStatus = match ($oldStatus) {
                    'nl' => 'cl',
                    'al' => 'tl',
                    default => $oldStatus,
                };
            }

            $agentName = null;
            if (!empty($currentOrder->usersIds)) {
                $agent = User::find($currentOrder->usersIds[0]);
                $agentName = $agent?->firstName;
            }

            $cif = null;
            if (!empty($currentOrder->account)) {
                $account = Account::find($currentOrder->account);
                $cif = $account?->CIF;
            }

            $result[] = [
                'cups' => $cups,
                'order_id' => $currentOrder->_id,
                'account_id' => $currentOrder->account ?? null,
                'liquidationStatus' => $oldStatus,
                'agent' => $agentName,
                'cif' => $cif,
                'order' => array_merge(
                    $currentOrder->toArray(),
                    ['liquidationStatus' => $newStatus]
                ),
            ];
        }

        return response()->json([
            'orders' => $result,
            'failedRows' => $failedRows
        ]);
    }

    //función para sacar el consumo anual del SIPS
    public static function getAPIConsumption(Request $request)
    {

        $CUPS = $request['CUPS'];

        $boundaryPoint = [
            ["boundary" => "0F", "checked" => false],
            ["boundary" => "1P", "checked" => false],
            ["boundary" => "", "checked" => false],
        ];

        $data = [];

        while (array_filter($boundaryPoint, fn($boundary) => !$boundary['checked'])) {
            $boundary = null;

            foreach ($boundaryPoint as $index => $point) {
                if (!$point['checked']) {
                    $boundaryPoint[$index]['checked'] = true;
                    $boundary = $CUPS . $boundaryPoint[$index]['boundary'];
                    break;
                }
            }

            //  Hago la petición a la API
            $response = Http::withHeaders([
                'x-api-key' => '$2y$10$bcT1Ukm4V/6/z5GvitZv0unu8I91mpixuwUO6z5CyEGev9DDDY28W'
            ])->get('https://datapi.psgestion.es/cups/data/consumos', [
                'id' => 4,
                'valores' => json_encode([
                    'cups' => $boundary,
                    'tipoContrato' => 'L'
                ])
            ])->body();

            //  Convierto los datos de JSON a Array
            $data = json_decode($response, true);

            //Acabo el bucle si recibo datos
            if (count($data['consumos']) > 0)
                break;
        }

        //Si no hay datos en $data, llamo al SIPSController (Baleares)
        if (empty($data['consumos']) || count($data['consumos']) === 0) {
            $newRequest = new Request(['CUPS' => $CUPS]);
            $data = SipsController::getElectricityConsumption($newRequest);
        }

        $consumption_beta = [];
        if (count($data['consumos']) < 1) {
            return response()->json([
                "consumptionData" => null
            ]);
        }

        //  Recojo la información y la agrupo en lecturas
        foreach ($data['consumos'] as $index => $close) {
            $dateTo = Carbon::parse($close['fechaFinMesConsumo']);
            $dateFrom = Carbon::parse($close['fechaInicioMesConsumo']);
            $consumption_beta[$index]["startDate"] = $dateFrom->setTimezone('Europe/Madrid')->format("d/m/Y");
            $consumption_beta[$index]["endDate"] = $dateTo->setTimezone('Europe/Madrid')->format("d/m/Y");
            for ($i = 1; $i <= 6; $i++) {
                $consumption_beta[$index]["periods"][$i - 1] = $close[sprintf('consumoEnergiaActivaEnWhP%s', $i)] / 1000;
                $consumption_beta[$index]["powers"][$i - 1] = $close[sprintf('potenciaDemandadaEnWP%s', $i)] / 1000;
                $consumption_beta[$index]["reactive"][$i - 1] = $close[sprintf('consumoEnergiaReactivaEnVArhP%s', $i)] / 1000;

            }
            $consumption_beta[$index]["consumption"] = array_sum($consumption_beta[$index]["periods"]);
        }

        //  -> Me quedo con las facturas de los 12 meses
        // $minDate = Carbon::parse($data["consumos"][0]["fechaFinMesConsumo"])->subYear();
        $minDate = Carbon::createFromFormat("d/m/Y", $consumption_beta[0]["endDate"])->subYear();
        $consumption = [];
        foreach ($consumption_beta as $consumptionRead) {
            $consumptionDate = Carbon::createFromFormat("d/m/Y", $consumptionRead["endDate"]);
            if ($consumptionDate->gt($minDate)) {
                $consumption[] = $consumptionRead;
            }
            ;
        }

        //  -> Agrupo los consumos por periodos
        foreach ($consumption as $index => $read) {
            //Obtengo los días entre esta lectura
            $interval = Carbon::createFromFormat("d/m/Y", $read["endDate"])
                ->diffInDays(Carbon::createFromFormat("d/m/Y", $read["startDate"]));

            //Obtengo los días entre esta lectura y la fecha mínima
            $difference = Carbon::createFromFormat("d/m/Y", $read["endDate"])->diffInDays($minDate);

            foreach ($read['periods'] as $period => $value) {
                if ($difference > $interval) {
                    $periods[$period] = ($periods[$period] ?? 0) + $value;
                    //Si la diferencia es menor que el intervalo, calcular la media de consumo diario
                } else {
                    $periods[$period] = ($periods[$period] ?? 0) + $value * $difference / $interval;
                }
            }
        }

        //  Obtengo las potencias contratadas
        for ($i = 1; $i <= 6; $i++) {
            $powers[$i - 1] = $data[sprintf('potenciasContratadasEnWP%s', $i)] / 1000;
        }

        return response()->json(['consumptionData' => $consumption, "cupsData" => ["power" => $powers, "consumption" => $periods], 'fee' => $data['tarifaATR'], "lastMovement" => $data['fechaUltimoCambioComercializador']], 200);
    }

    public function prepareMassiveEmailDoc(Request $request)
{
    $request->validate([
        'pdfBase64' => 'required|string',
        'title' => 'nullable|string',
    ]);

    $base64 = $request->input('pdfBase64');

    // Por si viene como data URI: data:application/pdf;base64,....
    $base64 = preg_replace('/^data:application\/pdf;base64,/', '', $base64);

    $binary = base64_decode($base64, true);

    if ($binary === false) {
        return response()->json([
            'message' => 'PDF base64 no válido'
        ], 422);
    }

    $folder = public_path('assets/emails');

    if (!File::exists($folder)) {
        File::makeDirectory($folder, 0755, true);
    }

    $fileName = 'datadis_' . Str::uuid() . '.pdf';
    $path = $folder . '/' . $fileName;

    File::put($path, $binary);

    return response()->json([
        'doc' => [
            'title' => $request->input('title', 'Informe Datadis.pdf'),
            'defaultTitle' => $request->input('title', 'Informe Datadis.pdf'),
            'value' => $fileName,
            'icon' => 'fa-file-pdf',
            'errors' => [],
        ]
    ]);
}

    public static function getOCRData(Request $request)
    {
        $urlPDF = $request->input('urlPDF');
        $files = $request->file('files');
        $inputFiles = [];
        $userSubdomain = $request->input('userSubdomain');


        // === BLOQUE TRAÍDO DEL 1º: helpers PDF/imagen ===
        $tmpDir = storage_path('app');

        $buildImageInputs = function (array $paths) use (&$inputFiles) {
            foreach ($paths as $imgPath) {
                $mime = mime_content_type($imgPath) ?: 'image/jpeg';
                $imageBase64 = base64_encode(file_get_contents($imgPath));
                $inputFiles[] = [
                    "type" => "input_image",
                    "image_url" => 'data:' . $mime . ';base64,' . $imageBase64
                ];
            }
        };

        $convertPdfToImages = function (string $pdfPath) use ($tmpDir): array {
            $pdftoppm = env('PDFTOPPM_BIN', '/usr/bin/pdftoppm');
            $outBase = $tmpDir . '/witro_' . uniqid('page_', true);
            $cmd = sprintf('"%s" "%s" "%s" -jpeg -r 200', $pdftoppm, $pdfPath, $outBase);
            exec($cmd, $out, $ret);
            if ($ret !== 0)
                return [];
            $images = glob($outBase . '-*.jpg');
            return is_array($images) ? $images : [];
        };

        $pdfHasText = function (string $pdfPath): bool {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($pdfPath);
                $text = trim($pdf->getText() ?? '');
                return $text !== '';
            } catch (\Throwable $e) {
                return false;
            }
        };
        // === FIN BLOQUE TRAÍDO ===

        // Ingesta de archivos con lógica “detectar escaneado”
        if ($urlPDF) {
            // En el 1º simplemente guarda lo recibido (asumiendo contenido/base64) y procesa
            $tmpPdf = $tmpDir . '/witro_' . uniqid('pdf_', true) . '.pdf';
            file_put_contents($tmpPdf, $urlPDF);

            if ($pdfHasText($tmpPdf)) {
                $pdfBase64 = base64_encode(file_get_contents($tmpPdf));
                $inputFiles[] = [
                    "type" => "input_file",
                    "filename" => "factura.pdf",
                    "file_data" => 'data:application/pdf;base64,' . $pdfBase64
                ];
            } else {
                $images = $convertPdfToImages($tmpPdf);
                if (count($images) === 0) {
                    return response()->json(['error' => 'No se pudo convertir el PDF escaneado a imágenes.'], 400);
                }
                $buildImageInputs($images);
            }
        }
        elseif ($files) {
            if (!is_array($files))
                $files = [$files];

            $fileTypes = collect($files)->map(fn($f) => $f->getMimeType());
            $hasPDF = $fileTypes->contains(fn($t) => str_starts_with($t, 'application/pdf'));
            $hasImages = $fileTypes->every(fn($t) => str_starts_with($t, 'image/'));

            if ($hasPDF && $fileTypes->count() > 1) {
                return response()->json(['error' => 'No se permite enviar PDF junto con otros archivos.'], 400);
            }

            if ($hasPDF && !$hasImages) {
                if (count($files) > 1) {
                    return response()->json(['error' => 'Solo se permite un archivo PDF.'], 400);
                }

                $pdfPath = $files[0]->getRealPath();

                if ($pdfHasText($pdfPath)) {
                    $pdfBase64 = base64_encode(file_get_contents($pdfPath));
                    $inputFiles[] = [
                        "type" => "input_file",
                        "filename" => "factura.pdf",
                        "file_data" => 'data:application/pdf;base64,' . $pdfBase64
                    ];
                } else {
                    $tmpPdf = $tmpDir . '/witro_' . uniqid('pdf_', true) . '.pdf';
                    copy($pdfPath, $tmpPdf);
                    $images = $convertPdfToImages($tmpPdf);
                    if (count($images) === 0) {
                        return response()->json(['error' => 'No se pudo convertir el PDF escaneado a imágenes.'], 400);
                    }
                    $buildImageInputs($images);
                }
            } elseif ($hasImages) {
                foreach ($files as $file) {
                    $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
                    $inputFiles[] = [
                        "type" => "input_image",
                        "image_url" => 'data:' . $file->getMimeType() . ';base64,' . $imageBase64
                    ];
                }
            } else {
                return response()->json(['error' => 'Tipo de archivo no permitido. Solo PDF o imágenes.'], 400);
            }
        }
        else {
            return response()->json(['error' => 'No se ha enviado ningún archivo ni URL.'], 400);
        }

        // === Desde aquí, todo igual que tu 2º (prompt mejorado, etc.) ===
        $client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . env("CHATGPT_API_KEY"),
                'Content-Type' => 'application/json',
            ]
        ]);

        $requestData = [
            "model" => "gpt-5.2",
            "input" => [
                [
                    "role" => "system",
                    "content" => "
                    Eres un analizador experto de facturas eléctricas españolas.

                EL USUARIO SOLO PROPORCIONA LA FACTURA (PDF o IMÁGENES).
                LA ESTRUCTURA DE SALIDA ESTÁ DEFINIDA EXCLUSIVAMENTE POR ESTE PROMPT.

                OBJETIVO:
                Extraer los datos de la factura y devolver ÚNICAMENTE un JSON válido siguiendo EXACTAMENTE la estructura definida más abajo.

                REGLAS ABSOLUTAS DE SALIDA:
                - La respuesta debe ser EXCLUSIVAMENTE:
                  { ... }
                - NO incluyas ```json, encabezados, texto previo, texto posterior, comentarios ni explicaciones.
                - NO inventes datos.
                - Si un dato no aparece claramente, devuelve null.
                - No incluyas unidades (€, kWh, %, días, etc.).
                - Usa punto decimal (.) como separador.
                - Todas las claves deben existir siempre.
                - No añadas claves adicionales.
                - No devuelvas strings vacíos: usa null.

                VALIDACIÓN FINAL OBLIGATORIA (PIENSA ANTES DE RESPONDER):
                1. El JSON es sintácticamente válido.
                2. No hay texto fuera de {}.
                3. Todas las claves existen.
                4. No hay valores inventados.
                5. La tarifa y los periodos son coherentes.
                6. El precio devuelto es el MÁS RECIENTE de la factura.

                ────────────────────────
                REGLAS DE NEGOCIO
                ────────────────────────

                PRECIOS:
                - Devuelve siempre el precio más reciente.
                - Si el precio está desglosado (peajes + consumo), súmalos.
                - Si solo hay un precio de energía, aplícalo a todos los periodos.
                - Solo existen precios de energía en periodos con consumo.

                DESCUENTOS:
                - Suma todos los descuentos explícitos por categoría.
                - Solo considera descuentos si aparece:
                  'descuento', 'dto', '%', 'descuento acordado' o texto equivalente.
                - Factores multiplicativos (x0,97 / x0,93 / x0,50…) NO son descuentos.
                - Devuelve el valor final como número (sin %).
                - Deben devolverse SIEMPRE como porcentaje ENTERO.
                - Ejemplos correctos:
                  - 10 % → 10
                  - 23 % → 23
                - Ejemplos PROHIBIDOS:
                  - 0.10
                  - 0.23
                  - 0,23
                  - '23%'
                  - '0.23''
                NO normalices porcentajes a formato decimal.
                NO conviertas porcentajes a factores.

                CAMPO 'otros' (REGLAS ABSOLUTAS):
                El objeto 'otros' SOLO puede contener los siguientes campos y con el significado exacto indicado.
                NO mezclar conceptos ni inferir valores.

                1. alquiler_equipo_medida
                - Precio diario del alquiler del contador.
                - Devuelve el valor en €/día.
                - Si la factura muestra un importe mensual o por periodo, divídelo entre los días de facturación.
                - Si no aparece, devuelve null.

                2. kwh_excedentes
                - Cantidad de energía excedentaria (autoconsumo).
                - Devuelve el valor absoluto en kWh (nunca negativo).
                - No devolver importes económicos.
                - Si no existen excedentes, devuelve null.

                3. precio_excedentes
                - Precio unitario al que se pagan los excedentes.
                - Devuelve el precio por kWh.
                - NO devolver el importe total liquidado.
                - Si no aparece claramente el precio, devuelve null.

                4. iva
                - Devuelve ÚNICAMENTE el PORCENTAJE de IVA aplicado.
                - Ejemplos:
                  - '21 %' → 21
                  - '10 %' → 10
                  - '0 %' → 0
                - ESTÁ PROHIBIDO devolver:
                  - El importe del IVA en euros.
                  - La base imponible.
                  - Un cálculo inferido.
                - Si el porcentaje no aparece explícitamente, devuelve null.

                5. bono_social
                - Precio diario del bono social (si existe).
                - Devuelve el valor en €/día.
                - Si aparece como importe por periodo, divídelo entre los días de facturación.
                - Si no existe bono social en la factura, devuelve 0.

                IMPORTANTE:
                - Ningún otro concepto puede aparecer dentro de 'otros'.
                - IVA, alquiler y bono social NUNCA deben aparecer en conceptos_extra.

                CONCEPTOS:
                conceptos_extra SOLO incluye conceptos que:
                - Sean servicios, mantenimientos o productos comerciales adicionales.
                - No formen parte de impuestos, cargos regulados ni costes obligatorios.
                - Aparezcan como una línea independiente con importe propio.
                - Usen el nombre literal del concepto como clave.

                ESTÁ PROHIBIDO incluir en conceptos_extra cualquier concepto cuyo nombre contenga:
                - bono social
                - contador
                - equipo de medida
                - impuesto eléctrico
                - IEE
                - IVA

                Estos conceptos NUNCA deben aparecer en conceptos_extra,
                aunque tengan importe y aunque sumen al total de la factura.

                Ante cualquier duda, NO incluir el concepto.

                IDENTIFICACIÓN:
                - Reconoce CIF y NIF correctamente.
                - El CUPS debe devolverse SIN espacios.
                - Obtén la comunidad autónoma a partir de la provincia si existe.

                TARIFA Y PERIODOS:
                - Identifica correctamente la tarifa.
                - Si la tarifa es 2.0TD:

                POTENCIA:
                - Solo existen P1 y P2.
                - P3 NO EXISTE.
                - Está PROHIBIDO devolver P3.
                - Si la factura muestra P3, súmalo a P2.

                ENERGÍA:
                - Existen exactamente 3 periodos.
                - Pueden llamarse P1/P2/P3 o punta/llano/valle.

                PERIODO DEL PRECIO DE POTENCIA:
                - €/kW día o €/kW/día → 'day'
                - €/kW mes o €/kW/mes → 'month'
                - €/kW año o €/kW/año → 'year'

                FORMATO DE FECHAS:
                - DD/MM/YYYY

                TOTAL FACTURA:
                - Extrae el IMPORTE TOTAL FINAL DE LA FACTURA.
                - Debe ser el total a pagar (importe final).
                - No devolver subtotales ni bases imponibles.
                - No incluir impuestos por separado: debe ser el total final.
                - Si hay varios totales, elegir el más reciente o el claramente identificado como 'Total factura', 'Importe total', 'Total a pagar'.
                - Devuelve solo el número (sin €).
                - Si no aparece claramente, devuelve null.

                ────────────────────────
                ESTRUCTURA JSON OBLIGATORIA
                ────────────────────────

                Devuelve EXACTAMENTE esta estructura:

                {
                  'titular': null,
                  'cif_nif': null,
                  'direccion_titular': {
                    'direccion': null,
                    'poblacion': null,
                    'codigo_postal': null,
                    'provincia': null,
                    'comunidad_autonoma': null
                  },
                  'direccion_suministro': {
                    'direccion': null,
                    'poblacion': null,
                    'codigo_postal': null,
                    'provincia': null,
                    'comunidad_autonoma': null
                  },
                  'periodo_facturacion': {
                    'fecha_inicio': null,
                    'fecha_fin': null
                  },
                  'dias_facturacion': null,
                  'comercializadora': null,
                  'cups': null,
                  'tarifa': null,
                  'otros': {
                    'alquiler_equipo_medida': null,
                    'kwh_excedentes': null,
                    'precio_excedentes': null,
                    'iva': null,
                    'bono_social': 0
                  },
                  'conceptos_extra': {},
                  'potencias_contratadas': {
                    'p1': null,
                    'p2': null,
                    'p3': null,
                    'p4': null,
                    'p5': null,
                    'p6': null
                  },
                  'precios_potencias': {
                    'p1': null,
                    'p2': null,
                    'p3': null,
                    'p4': null,
                    'p5': null,
                    'p6': null
                  },
                  'periodo_precio_potencia': null,
                  'descuento_potencia': null,
                  'energia_consumida': {
                    'p1': null,
                    'p2': null,
                    'p3': null,
                    'p4': null,
                    'p5': null,
                    'p6': null
                  },
                  'precios_energia': {
                    'p1': null,
                    'p2': null,
                    'p3': null,
                    'p4': null,
                    'p5': null,
                    'p6': null
                  },
                  'descuento_energia': null,
                  'total': null
                }

                NO DEVUELVAS NADA MÁS.
                "
                ],
                [
                    "role" => "user",
                    "content" => [
                        ...$inputFiles,
                    ]
                ]
            ]
        ];

        try {

            // COMPROBACIÓN PLAN
            if (!isset($userSubdomain)){ //para comparador abierto
                $host = $request->getHost();
                $subscription = Enterprise::where('url', $host)
                    ->pluck('subscription')
                    ->first();
            }else {
                $subscription = Enterprise::where('subdomainUser', $userSubdomain)
                    ->pluck('subscription')
                    ->first();
            }

            if (!$subscription) {
                return response()->json([
                    'limit' => 'No puedes realizar escaneos porque no tienes una suscripción activa.'
                ], 400);
            }

            $includedScans = data_get($subscription, 'included.scans');
            $usedScans = (int) data_get($subscription, 'usage.scans', 0);
            $extraScansRemaining = (int) data_get($subscription, 'extras.one_time.scans.remaining', 0);

            // null significa ilimitado
            $hasUnlimitedScans = $includedScans === null;

            $hasRemainingScans = $hasUnlimitedScans
                || $usedScans < (int) $includedScans
                || $extraScansRemaining > 0;

            if (!$hasRemainingScans) {
                return response()->json([
                    'limit' => 'No puedes realizar más escaneos porque has alcanzado el límite de tu plan. Cambia de plan o compra escaneos extra.'
                ], 400);
            }

            $response = $client->post('responses', ['json' => $requestData]);
            $data = json_decode($response->getBody(), true);
            return $data["output"][0]["content"][0]["text"];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $msg = "Error en la solicitud: " . $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= "\n" . $e->getResponse()->getBody();
            }
            return response()->json(['error' => $msg], 500);
        }
    }


    public static function getGasOCRData(Request $request)
    {

        $urlPDF = $request->input('urlPDF');
        $files = $request->file('files');
        $inputFiles = [];
        $userSubdomain = $request->input('userSubdomain');

        if ($urlPDF) {
            // Si viene por URL (modo externo)
            $pdfBase64 = base64_encode($urlPDF);
            $inputFiles[] = [
                "type" => "input_file",
                "filename" => "factura.pdf",
                "file_data" => 'data:application/pdf;base64,' . $pdfBase64
            ];
        } elseif ($files) {
            if (!is_array($files)) {
                $files = [$files]; // Asegura que siempre sea array
            }

            // Comprobación de tipo de archivo
            $fileTypes = collect($files)->map(function ($file) {
                return $file->getMimeType();
            });

            $hasPDF = $fileTypes->contains(fn($type) => str_starts_with($type, 'application/pdf'));
            $hasImages = $fileTypes->every(fn($type) => str_starts_with($type, 'image/'));

            if ($hasPDF && $fileTypes->count() > 1) {
                return response()->json(['error' => 'No se permite enviar PDF junto con otros archivos.'], 400);
            }

            if ($hasPDF && !$hasImages) {
                // Solo 1 PDF permitido
                if (count($files) > 1) {
                    return response()->json(['error' => 'Solo se permite un archivo PDF.'], 400);
                }
                $pdfBase64 = base64_encode(file_get_contents($files[0]->getRealPath()));

                $inputFiles[] = [
                    "type" => "input_file",
                    "filename" => "factura.pdf",
                    "file_data" => 'data:application/pdf;base64,' . $pdfBase64
                ];
            } elseif ($hasImages) {
                foreach ($files as $file) {
                    $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
                    $inputFiles[] = [
                        "type" => "input_image",
                        "image_url" => 'data:' . $file->getMimeType() . ';base64,' . $imageBase64
                    ];
                }
            } else {
                return response()->json(['error' => 'Tipo de archivo no permitido. Solo PDF o imágenes.'], 400);
            }
        } else {
            return response()->json(['error' => 'No se ha enviado ningún archivo ni URL.'], 400);
        }


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
                    Eres un experto analizador de facturas.
                    Devuelves el precio en las unidades que te pide el usuario perfectamente calculadas, y siempre el precio más reciente que aparece en la factura.
                    La información que te pide el usuario la devuelves en un JSON en un bloque de código con formato ```json.
                    No añades las unidades ni ningún comentario al json.
                    Obtienes la comunidad autónoma a partir de la provincia de la factura si hay.
                    Sabes reconocer un CIF o un NIF perfectamente.
                    "
                ],
                [
                    "role" => "user",
                    "content" => [
                        ...$inputFiles,
                        [
                            "type" => "input_text",
                            "text" => "Quiero los siguientes datos:{
                                titular:,
                                cif_nif:,
                                direccion_titular: {
                                    direccion:,
                                    poblacion:,
                                    codigo_postal:,
                                    provincia:,
                                    comunidad_autonoma:,
                                },
                                direccion_suministro: {
                                    direccion:,
                                    poblacion:,
                                    codigo_postal:,
                                    provincia:,
                                    comunidad_autonoma:,
                                },
                                periodo_facturacion:{
                                    fecha_inicio: en formato DD/MM/YYYY,
                                    fecha_fin: en formato DD/MM/YYYY,
                                },
                                dias_facturacion: (numero de dias de la factura),
                                otros: {
                                    alquiler_equipo_medida: (precio en €/dia),
                                },
                                comercializadora:,
                                cups: sin espacios,
                                tarifa: peaje de acceso normalmente RL1, RL2 o RL3, ... Si pone RLPS deja solo RL y el numero (RLPS1 => RL1),
                                termino_fijo: (precio en € / día),
                                consumo: (en kWh),
                                precio_consumo: (en € / kWh),
                                total:

                            }
                            "
                        ]
                    ]
                ]
            ]
        ];

        try {
            // COMPROBACIÓN PLAN
            if (!isset($userSubdomain)){ //para comparador abierto
                $host = $request->getHost();
                $subscription = Enterprise::where('url', $host)
                    ->pluck('subscription')
                    ->first();
            }else {
                $subscription = Enterprise::where('subdomainUser', $userSubdomain)
                    ->pluck('subscription')
                    ->first();
            }

            if (!$subscription) {
                return response()->json([
                    'limit' => 'No puedes realizar escaneos porque no tienes una suscripción activa.'
                ], 400);
            }

            $includedScans = data_get($subscription, 'included.scans');
            $usedScans = (int) data_get($subscription, 'usage.scans', 0);
            $extraScansRemaining = (int) data_get($subscription, 'extras.one_time.scans.remaining', 0);

            // null significa ilimitado
            $hasRemainingScans = $includedScans === null
                || $usedScans < (int) $includedScans
                || $extraScansRemaining > 0;

            if (!$hasRemainingScans) {
                return response()->json([
                    'limit' => 'No puedes realizar más escaneos porque has alcanzado el límite de tu plan. Cambia de plan o compra escaneos extra.'
                ], 400);
            }

            $response = $client->post('responses', [
                'json' => $requestData
            ]);

            $data = json_decode($response->getBody(), true);
            return $data["output"][0]["content"][0]["text"];
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            echo "Error en la solicitud: " . $e->getMessage();
            if ($e->hasResponse()) {
                echo "\n" . $e->getResponse()->getBody();
            }
        }
    }

    public function obtainDatadisToken()
    {
        $client = new Client();

        $username = 'B56037518';
        $password = '5676Segenet$';

        $url = 'https://datadis.es/nikola-auth/tokens/login';

        try {
            $response = $client->post($url, [
                'multipart' => [
                    [
                        'name' => 'username',
                        'contents' => $username,
                    ],
                    [
                        'name' => 'password',
                        'contents' => $password,
                    ],
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            echo $responseBody;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            echo 'Request failed: ' . $e->getMessage() . "\n";
            if ($e->hasResponse()) {
                $errorResponse = $e->getResponse();
                echo 'Error Response: ' . $errorResponse->getBody()->getContents();
            }
        }
    }

    public function getDatadisConsumptionData(Request $request)
    {
        $client = new Client();

        $supply = json_decode($request["supply"]);
        $cif    = $request['cif'];

        $startDate = '';
        $endDate = '';
        $endDateCarbon = '';

        if(isset($request['date'])){
            //Obtener los datos de la fecha actual y 2 para arriba-abajo
            $carbonDate = Carbon::parse($request['date']);

            switch ($request['dateType']) {
                case 'day':
                    $startDate = $carbonDate->copy()->subDays(2)->format('Y/m');
                    $endDateCarbon = $carbonDate->copy()->addDays(2);
                    break;
                case 'isoWeek':
                    $startDate = $carbonDate->copy()->subWeeks(2)->format('Y/m');
                    $endDateCarbon = $carbonDate->copy()->addWeeks(2);
                    break;
                case 'month':
                    $startDate = $carbonDate->copy()->subMonthsNoOverflow(2)->format('Y/m');
                    $endDateCarbon = $carbonDate->copy()->addMonths(2);
                    break;
            }
        }else{
            $startDate     = Carbon::parse($request['startDate'])->format('Y/m');
            $endDateCarbon = Carbon::parse($request['endDate']);

        }

        $endDate = $endDateCarbon->isBefore(Carbon::now())
            ? $endDateCarbon->format('Y/m')
            : Carbon::now()->format('Y/m');

        $data = Datadis::where('cups', $supply->cups)
            ->where('startDate', $startDate)
            ->where('endDate', $endDate)
            ->first();

        try {
            if ($data) {
                $consumption = $data->data;
            } else {
                $url = "https://datadis.es/api-private/api/get-consumption-data?cups=$supply->cups&distributorCode=$supply->distributorCode&startDate=$startDate&endDate=$endDate&measurementType=0&pointType=$supply->pointType&authorizedNif=$cif";

                $response = $client->get($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $request["token"],
                        'Accept'        => 'application/json',
                    ]
                ]);

                $consumption = json_decode($response->getBody()->getContents());

                Datadis::create([
                    'cups'      => $supply->cups,
                    'startDate' => $startDate,
                    'endDate'   => $endDate,
                    'data'      => $consumption,
                    'createdAt' => Carbon::now(),
                ]);
            }

            $response = $client->get("https://datadis.es/api-private/api/get-contract-detail?cups=$supply->cups&distributorCode=$supply->distributorCode&authorizedNif=$cif", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $request["token"],
                    'Accept'        => 'application/json',
                ]
            ]);

            $contract = $response->getBody()->getContents();

            return ["consumption" => $consumption, "contract" => json_decode($contract)];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            echo 'Request failed: ' . $e->getMessage() . "\n";
            if ($e->hasResponse()) {
                echo 'Error Response: ' . $e->getResponse()->getBody()->getContents();
            }
        }
    }


        public function getDatadisReportEmailTarget(Request $request)
{
    $cups = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $request->input('cups', '')));

    if (!$cups) {
        return response()->json([
            'message' => 'CUPS no válido'
        ], 422);
    }

    // Datadis puede devolver CUPS con sufijo, por ejemplo 0F.
    // En contratos normalmente guardas el CUPS base de 20 caracteres.
    $cupsBase = strlen($cups) > 20 ? substr($cups, 0, 20) : $cups;

    $order = Order::where('CUPS', $cupsBase)->first();

    if (!$order) {
        return response()->json([
            'message' => 'No se ha encontrado contrato relacionado con este CUPS',
            'cups_received' => $cups,
            'cups_searched' => $cupsBase
        ], 404);
    }

    $account = null;

    if (!empty($order['account'])) {
        $account = Account::find((string) $order['account']);
    }

    if (!$account || empty($account['email'])) {
        return response()->json([
            'message' => 'La cuenta relacionada con este contrato no tiene email',
            'order' => $order
        ], 404);
    }

    return response()->json([
        'order' => $order,
        'account' => $account,
        'email' => $account['email']
    ]);
}

    public function updateStatuses(Request $request)
    {

        $user = $request->input('user');

        User::where('_id', $user['_id'])->update(['statuses' => $user['statuses']]);

        return response()->json(['message' => 'Estados actualizados correctamente.']);
    }


    public function sendMassiveEmail(Request $request)
    {

        $email = json_decode($request->input('email'), true);
        $userLogged = json_decode($request->input('userLogged'), true);
        $enterprise = json_decode($request->input('enterprise'), true);


        //Convierto las clases de quill a estilo válido para html en gmail

        //tamaños
        $email['message'] = str_replace('class="ql-size-small"', 'style="font-size:10.5px;"', $email['message']);
        $email['message'] = str_replace('class="ql-size-large"', 'style="font-size:21px;"', $email['message']);
        $email['message'] = str_replace('class="ql-size-huge"', 'style="font-size:35px;"', $email['message']);

        //Paso las imagenes de base 64 a url
        $email['message'] = $this->extractAndSaveBase64Images(
            $email['message'],
            'email',
            'assets/emails'
        );

        //Indent
        $email['message'] = preg_replace('/class="ql-indent-\d+"/', 'style="margin-left:20px;"', $email['message']);

        //Fuentes
        $email['message'] = $this->convertQuillFonts($email['message']);

        //Formateo el correo
        $url = 'https://' . $enterprise['url'];

        $email['message'] = '
            <body style="background-color: #f8f9fb; font-family: sans-serif; padding: 40px;">
              <table width="100%" cellspacing="0" cellpadding="0">
                <tr>
                  <td align="center">
                    <table id="content" width="900" style="background-color: #ffffff; border-radius: 10px; padding: 40px; text-align: left;">
                      <tr>
                        <td style="padding-bottom: 20px;">
                            <img src="https://' . $enterprise['url'] . '/assets/enterprises/' . $enterprise['asset_folder'] . '/logos/mini-dark.png"
                                 alt="Logo Zoco Energía"
                                 style="height: 40px; max-width: 100%; display: block;">
                        </td>
                      </tr>
                      <tr>
                        <td style="font-size: 16px; color: #333;">
                          ' . $email['message'] . '
                        </td>
                      </tr>
                      <tr>
                        <td style="padding-top: 30px; padding-bottom: 30px; text-align: center;">
                          <a href="' . $url . '" target="_blank"
                             style="background-color: #2192FF; color: #ffffff; text-decoration: none;
                                    padding: 10px 24px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block;">
                            Accede al CRM
                          </a>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            </body>';

        //$email['message'] .= 'Accede al CRM de ' . $enterprise['name'] . ' <a href="https://' . $enterprise['url'] . '" target="_blank">¡Haz click aquí!</a>';
        /*email['message'] .= '<table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center" style="margin: 20px auto;">
                                  <tr>
                                    <td align="center" bgcolor="#2192FF" style="border-radius: 6px;">
                                      <a href="https://' . $enterprise['url'] . '" target="_blank"
                                         style="display: inline-block; padding: 12px 24px; font-size: 16px; font-family: sans-serif;
                                                color: #ffffff; background-color: #2192FF; text-decoration: none; border-radius: 6px;">
                                        Accede al CRM
                                      </a>
                                    </td>
                                  </tr>
                              </table>';*/



        //Si tiene archivos los guardo en el servidor
        if (count($email['docs']) > 0) {

            foreach ($email['docs'] as $docInd => &$doc) {

                //Compruebo si se ha adjuntado un documento o solo se ha puesto título
                if (isset($doc['fileValue']) && $doc['fileValue'] !== '') {

                    //Saco el archivo
                    $file = $request['docFile' . $docInd];

                    //Compruebo si existe el archivo
                    $existFile = (is_string($file) && Storage::disk('email')->exists($doc['value']));

                    if (isset($file) && !$existFile) {
                        //Creo el nombre del archivo para guardar
                        $originalName = $file->getClientOriginalName();
                        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                        // Evita duplicados: tiempo + ID único + nombre original limpio
                        $docFileName = time() . '_' . uniqid() . '_' . $baseName . '.' . $extension;


                        if (!Storage::disk('email')->exists($docFileName))
                            Storage::disk('email')->put($docFileName, file_get_contents($file));

                        //Meto el nombre en el campo value para registrarlo
                        $doc['value'] = $docFileName;

                        //Elimino fileValue que es el archivo en sí, que no lo necesito ya que estará en el FTP
                        unset($doc['fileValue']);
                    } else {
                        unset($doc['fileValue']);
                    }
                }
            }
        }

        //Recorro los destinatarios para meterle un id para luego hacer el tracking
        foreach ($email['recipients'] as $recipientInd => &$recipient) {
            $recipient['delivered_at'] = null;
            $recipient['opened_at'] = null;
            $recipient['clicked_at'] = null;
        }

        //Guardo en la bbdd el registro del
        $email['createdBy'] = $userLogged['_id'];
        $email['createdAt'] = Carbon::now()->toDateTimeString();

        $emailId = Email::insertGetId($email);

        //Quito los saltos de línea
        $email['message'] = str_replace('<p><br></p>', '', $email['message']);

        //Envío correos
        MassiveSendJob::dispatch($email, $emailId, $enterprise, $userLogged);

        return response()->json(['message' => 'Correos enviados correctamente']);
    }


    function extractAndSaveBase64Images(string $html, string $disk = 'email', string $publicPath = 'assets/emails'): string
    {
        // 1) Busca todas las Data URIs en <img src="...">
        preg_match_all(
            '/<img[^>]+src=[\'"](?P<data>data:image\/[^;]+;base64,[^\'"]+)[\'"][^>]*>/i',
            $html,
            $matches
        );

        if (empty($matches['data'])) {
            // No hay imágenes en Base64, devolvemos el HTML tal cual
            return $html;
        }

        // Asegúrate de que el directorio exista
        $storagePath = storage_path("app/{$disk}");
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Vamos reemplazando cada Data URI por su URL guardada
        foreach (array_unique($matches['data']) as $dataUri) {
            // Separa tipo MIME y datos
            list($meta, $b64data) = explode(',', $dataUri, 2);
            // Ej: "data:image/png;base64"
            preg_match('#data:(image/[^;]+);base64#', $meta, $m);
            $mime = $m[1] ?? 'application/octet-stream';
            // Extensión a partir del mime
            $ext = explode('/', $mime)[1] ?? 'bin';

            // Prepara nombre único
            $filename = 'quill_' . time() . '_' . uniqid() . '.' . $ext;

            // Guarda el fichero decodificado
            $binary = base64_decode($b64data);
            Storage::disk($disk)->put($filename, $binary);

            // Construye la URL pública
            // Asume que has hecho: php artisan storage:link
            // y expones storage/app/email en public/assets/emails
            $url = asset("{$publicPath}/{$filename}");

            // Reemplaza en el HTML
            $html = str_replace($dataUri, $url, $html);
        }

        return $html;
    }

    function convertQuillFonts($html)
    {
        // Definimos los reemplazos para cada clase de fuente Quill
        $reemplazos = [
            'ql-font-sans-serif' => 'font-family: sans-serif;',
            'ql-font-serif' => 'font-family: serif;',
            'ql-font-monospace' => 'font-family: monospace;',
            'ql-font-wide' => 'font-family: Arial, sans-serif; letter-spacing: 0.1em;',
            'ql-font-narrow' => 'font-family: Arial, sans-serif; letter-spacing: -0.05em;',
            'ql-font-comic-sans-ms' => "font-family: 'Comic Sans MS', sans-serif;",
            'ql-font-garamond' => 'font-family: Garamond, serif;',
            'ql-font-georgia' => 'font-family: Georgia, serif;',
            'ql-font-tahoma' => 'font-family: Tahoma, sans-serif;',
            'ql-font-trebuchet-ms' => 'font-family: "Trebuchet MS", sans-serif;',
            'ql-font-verdana' => 'font-family: Verdana, sans-serif;',
        ];

        // Reemplazar las clases de Quill por los estilos inline correspondientes
        foreach ($reemplazos as $clase => $style) {
            $html = preg_replace(
                '/class="([^"]*\s*)?' . preg_quote($clase, '/') . '(\s*[^"]*)?"/',
                'style="' . $style . '"',
                $html
            );
        }

        return $html;
    }

    public function statesMassive(Request $request)
    {
        $userLogged = Auth::user();

        if (!$userLogged) {
            return response()->json(['message' => 'No autorizado'], 401);
        }

        $userSubdomain = json_decode($request->input('userSubdomain'), true);
        $enterprise = json_decode($request->input('entreprise'), true);

        if (!is_array($userSubdomain) || empty($userSubdomain['statuses'])) {
            return response()->json(['message' => 'userSubdomain.statuses no recibido o inválido'], 422);
        }

        // Estados disponibles
        $statusByTitle = collect($userSubdomain['statuses'])
            ->filter(fn($s) => isset($s['title'], $s['code']))
            ->keyBy(fn($s) => strtoupper(trim($s['title'])))
            ->all();

        // Usuarios permitidos
        $hierarchyUsers = UserHelper::hierarchy($userLogged->_id) ?? [];
        $allowedUsers = collect($hierarchyUsers)
            ->pluck('_id')
            ->map(fn($id) => (string) $id)
            ->push((string) $userLogged->_id)
            ->unique()
            ->values()
            ->toArray();

        // Leer Excel
        $excel = Excel::toArray([], $request->file('file'));

        $headerRow = $excel[0][1] ?? [];
        $columnCUPS = null;
        $columnState = null;
        $columnDate = null;

        foreach ($headerRow as $i => $cell) {
            $v = strtolower(trim((string) $cell));
            if ($v === 'cups')
                $columnCUPS = $i;
            if ($v === 'estado')
                $columnState = $i;
            if ($v === 'fecha')
                $columnDate = $i;
        }

        if ($columnCUPS === null || $columnState === null) {
            return response()->json(['message' => 'Faltan columnas obligatorias'], 422);
        }

        $errors = [];
        $warnings = [];
        $updated = [];

        foreach (array_slice($excel[0], 2) as $rowIndex => $row) {

            // Fila vacía
            if (
                empty(array_filter(
                    $row,
                    fn($cell) =>
                        !is_null($cell) && trim((string) $cell) !== ''
                ))
            ) {
                continue;
            }

            $fila = $rowIndex + 3;

            $rawCups = $row[$columnCUPS] ?? null;
            $rawState = $row[$columnState] ?? null;
            $rawDate = $columnDate !== null ? ($row[$columnDate] ?? null) : null;

            if (!$rawCups || !$rawState) {
                $warnings[] = "Fila {$fila}: datos incompletos";
                continue;
            }

            $cups = preg_replace('/^0F/', '', trim((string) $rawCups));
            $stateName = strtoupper(trim((string) $rawState));

            if (!isset($statusByTitle[$stateName])) {
                $errors[] = "Fila {$fila} ({$cups}): estado '{$stateName}' no existe";
                continue;
            }

            $newCode = (string) $statusByTitle[$stateName]['code'];

            // 👉 Contrato MÁS NUEVO por CUPS
            $order = Order::where('CUPS', $cups)
                ->whereIn('usersIds', $allowedUsers)
                ->orderByDesc('_id')
                ->first();

            if (!$order) {
                $warnings[] = "Fila {$fila} ({$cups}): contrato no encontrado o sin permisos";
                continue;
            }

            // Último estado
            $statuses = is_array($order->statuses ?? null) ? $order->statuses : [];
            $lastStatus = collect($statuses)->sortByDesc('date')->first();
            $lastCode = $lastStatus['code'] ?? null;

            if ($lastCode === $newCode) {
                $warnings[] = "Fila {$fila} ({$cups}): ya estaba en estado '{$stateName}'";
                continue;
            }

            $now = Carbon::now()->format('Y-m-d H:i:s');

            try {

                // Base del update
                $update = [
                    '$push' => [
                        'statuses' => [
                            'code' => $newCode,
                            'date' => $now,
                            'creator' => (string) $userLogged->_id,
                        ]
                    ],
                    '$set' => [
                        'lastStatus' => [
                            'code' => $newCode,
                            'date' => $now,
                        ],
                        'lastUpdate' => $now,
                        'updatedAt' => $now,
                    ]
                ];

                // Fecha opcional
                if ($rawDate) {
                    try {
                        if (is_numeric($rawDate)) {
                            $date = Carbon::instance(
                                \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($rawDate)
                            );
                        } else {
                            $date = Carbon::createFromFormat('d/m/Y', trim((string) $rawDate));
                        }

                        if ($newCode === 't') {
                            $update['$set']['processingDate'] = $date->toDateString();
                        } elseif ($newCode === 'a') {
                            $update['$set']['activationDate'] = $date->toDateString();
                        } elseif ($newCode === 'b') {
                            $update['$set']['lowDate'] = $date->toDateString();
                        }

                    } catch (\Throwable $e) {
                        $warnings[] = "Fila {$fila} ({$cups}): fecha inválida '{$rawDate}'";
                    }
                }

                // 👉 Update atómico (NO sobrescribe statuses)
                $modified = Order::where('_id', $order->_id)->update($update);

                if (!$modified) {
                    $warnings[] = "Fila {$fila} ({$cups}): no hubo cambios reales";
                    continue;
                }

                $updated[] = "Fila {$fila} ({$cups}): actualizado a '{$stateName}'";

            } catch (\Throwable $e) {
                $errors[] = "Fila {$fila} ({$cups}): error al guardar → " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => 'Proceso masivo finalizado',
            'updated' => $updated,
            'warnings' => $warnings,
            'errors' => $errors,
        ]);
    }
    public function getEmails($id)
    {
        return response()->json(Email::where('createdBy', $id)->orderBy('createdAt', 'desc')->get(), 200);
    }

    private function parseNumber($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        if (!is_string($value)) {
            return 0;
        }

        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $hasComma = str_contains($value, ',');
        $hasDot   = str_contains($value, '.');

        if ($hasComma && $hasDot) {
            // 1.234,56  → europeo
            if (strrpos($value, ',') > strrpos($value, '.')) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
            // 1,234.56 → americano
            else {
                $value = str_replace(',', '', $value);
            }
        }
        elseif ($hasComma) {
            // 1112,2
            $value = str_replace(',', '.', $value);
        }

        return (float)$value;
    }

    public function generateGasPDF(Request $request)
    {
        try {

            // Recibimos directamente el JSON enviado
            $payloadRaw = $request->input('payload');

            if (!$payloadRaw) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Falta el payload'
                ], 400);
            }

            $data = json_decode($payloadRaw, true);

            if (!is_array($data)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Payload inválido (JSON mal formado)'
                ], 400);
            }
            $userSubdomainId = $data['basicData']['userSubdomain']['_id'] ?? null;

            $isBlueTheme = $userSubdomainId === '67dbec3341971220e30aebc2';

            if (!$data) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Datos inválidos'
                ], 400);
            }




            /*
            |--------------------------------------------------------------------------
            | LOGO EMPRESA
            |--------------------------------------------------------------------------
            */


            if ($request->hasFile('enterpriseImg')) {
                $img = $request->file('enterpriseImg');
                $mime = $img->getMimeType();
                $contents = file_get_contents($img->getRealPath());
                $logoUrl = "data:{$mime};base64," . base64_encode($contents);
            } else {

                $folder = $data['basicData']['enterprise']['asset_folder'] ?? '';
                $fullPath = base_path("../assets/enterprises/{$folder}/logos/mini-dark.png");

                if (file_exists($fullPath)) {
                    $mime = mime_content_type($fullPath);
                    $contents = file_get_contents($fullPath);
                    $logoUrl = "data:{$mime};base64," . base64_encode($contents);
                } else {
                    $logoUrl = null;
                }
            }


            $consumption = (float)($data['consumption'] ?? 0);

            $currentTotal = (float)($data['currentTotal'] ?? 0);
            $offerTotal = (float)($data['offer']['total'] ?? 0);

            $offerActual = $data['pdfForm']['currentMarketer'] ?? null;



            $ahorro = $currentTotal - $offerTotal;

            $percent = $currentTotal > 0
                ? round(($ahorro / $currentTotal) * 100, 1)
                : 0;

            /*
            |--------------------------------------------------------------------------
            | DONUT AHORRO
            |--------------------------------------------------------------------------
            */

            $makeDonut = function ($percent, $color) {

                $size = 160;
                $stroke = 16;
                $radius = ($size / 2) - ($stroke / 2);
                $circ = 2 * M_PI * $radius;
                $dash = max(min($percent, 100), 0) / 100 * $circ;
                $cx = $cy = $size / 2;

                $svg = "
                <svg width='{$size}' height='{$size}' viewBox='0 0 {$size} {$size}' xmlns='http://www.w3.org/2000/svg'>
                    <circle cx='{$cx}' cy='{$cy}' r='{$radius}' fill='none' stroke='#E0E0E0' stroke-width='{$stroke}'/>
                    <g transform='rotate(-90 {$cx} {$cy})'>
                        <circle cx='{$cx}' cy='{$cy}' r='{$radius}' fill='none'
                            stroke='{$color}' stroke-width='{$stroke}'
                            stroke-dasharray='{$dash},{$circ}'/>
                    </g>
                    <text x='{$cx}' y='{$cy}' text-anchor='middle' dominant-baseline='middle'
                        font-size='32' font-weight='bold' fill='{$color}'>{$percent}%</text>
                </svg>";

                return 'data:image/svg+xml;base64,' . base64_encode($svg);
            };

            $donutTotalImage = $makeDonut($percent, '#7f1d1d');

            /*
            |--------------------------------------------------------------------------
            | GRÁFICO CONSUMO
            |--------------------------------------------------------------------------
            */

            $chartConsumoImage = null;

            $intervals = $data['cupsIntervalsData'] ?? [];

            if (is_array($intervals) && count($intervals) > 0) {

                $labels = [];
                $values = [];

                foreach ($intervals as $it) {
                    $labels[] = $it['fechaFinMesConsumo'] ?? '';
                    $values[] = (float)str_replace(',', '.', (string)($it['consumoEnWhP1'] ?? 0));
                }

                $labels = array_reverse($labels);
                $values = array_reverse($values);

            } else {

                $labels = ['Consumo total'];
                $values = [round($consumption, 2)];
            }

            $chartConfig = [
                'type' => 'bar',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Consumo (kWh)',
                        'data' => $values,
                        'backgroundColor' => '#B91C1C'
                    ]]
                ],
                'options' => [
                    'plugins' => [
                        'legend' => ['display' => false]
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'title' => ['display' => true, 'text' => 'kWh']
                        ]
                    ]
                ]
            ];

            try {
                $img = (new \GuzzleHttp\Client())->get(
                    'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig))
                )->getBody();

                $chartConsumoImage = 'data:image/png;base64,' . base64_encode($img);

            } catch (\Throwable $e) {
                $chartConsumoImage = null;
            }


            $chartSavingsImage = null;

            $chartConfigSavings = [
                'type' => 'bar',
                'data' => [
                    'labels' => ['Actual', 'Oferta'],
                    'datasets' => [[
                        'label' => 'Costes (€)',
                        'data' => [$currentTotal, $offerTotal],
                        'backgroundColor' => ['#B91C1C', '#CBD5E1']
                    ]]
                ],
                'options' => [
                    'indexAxis' => 'y',
                    'plugins' => [
                        'legend' => ['display' => false]
                    ],
                    'scales' => [
                        'x' => [
                            'beginAtZero' => true
                        ]
                    ]
                ]
            ];

            try {
                $img = (new \GuzzleHttp\Client())->get(
                    'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfigSavings))
                )->getBody();

                $chartSavingsImage = 'data:image/png;base64,' . base64_encode($img);

            } catch (\Throwable $e) {
                $chartSavingsImage = null;
            }


            // ===== Fechas =====
            $startDate = $data['startDate'] ?? null;
            $endDate   = $data['endDate'] ?? null;

            if ($startDate && $endDate) {
                $start = Carbon::createFromFormat('d/m/Y', $startDate);
                $end   = Carbon::createFromFormat('d/m/Y', $endDate);
            } else {
                $end = Carbon::now();
                $start = $end->copy()->subMonth();
            }

            $periodDays = $start->diffInDays($end);



            $applyTaxes     = $data['applyTaxes'] ?? false;
            $otherConcepts  = $data['otherConcepts'] ?? [];
            $currentSubtotal = $data['currentSubtotal'] ?? [];
            $offerSubtotal   = $data['offer']['subTotal'] ?? [];

            // 🔹 Extras
            $extrasTotal = collect($otherConcepts)->sum(function ($c) {
                return (float) str_replace(',', '.', $c['value'] ?? 0);
            });

            /*
            |--------------------------------------------------------------------------
            | DATOS AGENTE
            |--------------------------------------------------------------------------
            */
            // Solo usar los datos introducidos en el formulario PDF
            $agentName = trim((string) ($data['pdfForm']['agentName'] ?? ''));
            $agentPhone = trim((string) ($data['pdfForm']['agentPhone'] ?? ''));
            $agentEmail = trim((string) ($data['pdfForm']['agent'] ?? ''));

            $viewData = [
                'isBlueTheme' => $isBlueTheme,

                'agentName'  => $agentName,
                'agentPhone' => $agentPhone,
                'agentEmail' => $agentEmail,


                // GENERALES
                'logoUrl' => $logoUrl,
                'pdfForm' => $data['pdfForm'] ?? [],
                'consumption' => $consumption,

                // TOTALES
                'currentTotal' => $currentTotal,
                'offerTotal'   => $offerTotal,
                'ahorro'       => $ahorro,
                'percent'      => $percent,

                // DESGLOSE
                'applyTaxes'       => $applyTaxes,
                'otherConcepts'    => $otherConcepts,
                'extrasTotal'      => $extrasTotal,


                // PRECIOS ACTUAL (precio unitario real)
                'fixedAct'    => $data['prices']['fixed'] ?? null,
                'variableAct' => $data['prices']['variable'] ?? null,

                // PRECIOS OFERTA (precio unitario real)
                'fixedOff'    => $data['offer']['prices']['fixed'] ?? null,
                'variableOff' => $data['offer']['prices']['variable'] ?? null,

                'fixedPricePeriod' => $data['fixedPricePeriod'] ?? 'día',
                'chartSavingsImage' => $chartSavingsImage,
                'periodDays' => $periodDays,
                'startDate' => $start->format('d/m/Y'),
                'endDate' => $end->format('d/m/Y'),
                'offerActual' => $offerActual,

            ];

            $html = view('PDFs.comparatorGas', $viewData)->render();

            /*
            |--------------------------------------------------------------------------
            | GENERACIÓN PDF
            |--------------------------------------------------------------------------
            */

            $tempPath = storage_path('app/tmp_gas_' . uniqid('', true) . '.pdf');

            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            $nodeBinary = $isWindows
                ? 'C:\\Program Files\\nodejs\\node.exe'
                : '/usr/bin/node';

            $npmBinary = $isWindows
                ? 'C:\\Program Files\\nodejs\\npm.cmd'
                : '/usr/bin/npm';

            $chromeBinary = $isWindows
                ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
                : '/usr/bin/chromium';

            $browsershot = \Spatie\Browsershot\Browsershot::html($html)
                ->setNodeBinary($nodeBinary)
                ->setChromePath($chromeBinary)
                ->format('A4')
                ->margins(10, 10, 10, 10)
                ->showBackground()
                ->scale(1);

            if (!$isWindows) {
                $browsershot
                    ->setNpmBinary($npmBinary)
                    ->setEnvironmentOptions([
                        'HOME' => '/tmp',
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
                    ->waitUntilNetworkIdle();
            } else {
                $browsershot->waitUntilNetworkIdle(false);
            }

            $browsershot->save($tempPath);

            $cups = $data['pdfForm']['order']['CUPS'] ?? 'gas';

            return response()
                ->download($tempPath, "comparativa_gas_{$cups}.pdf")
                ->deleteFileAfterSend(true);

        } catch (\Throwable $e) {

            \Log::error('Error generateGasPDF', [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No se pudo generar el PDF.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function generateDatadisReport(Request $request)
{
    $tempPath = null;
    $zipPath = null;
    $tmpDir = null;
    $createdFiles = [];
    $zip = null;

    try {

        \Log::debug('DATADIS REPORT REQUEST - entrada', [
            'bulk' => $request->boolean('bulk'),
            'has_priceComparison_single' => $request->has('priceComparison'),
            'priceComparison_single_empty' => empty($request->input('priceComparison')),
            'single_cups' => $request->input('supply.cups'),
            'reports_count' => is_array($request->input('reports')) ? count($request->input('reports')) : 0,
            'reports_summary' => collect($request->input('reports', []))->map(function ($report, $index) {
                return [
                    'index' => $index,
                    'cups' => $report['supply']['cups'] ?? null,
                    'has_priceComparison' => array_key_exists('priceComparison', $report),
                    'priceComparison_is_empty' => empty($report['priceComparison'] ?? null),
                    'has_current' => !empty($report['priceComparison']['current'] ?? null),
                    'current_total' => $report['priceComparison']['current']['total'] ?? null,
                    'best_offer' => $report['priceComparison']['bestOffer']['product'] ?? null,
                    'periodConsumption' => $report['periodConsumption'] ?? null,
                ];
            })->toArray(),
        ]);

        /*
        |--------------------------------------------------------------------------
        | MODO BULK: varios informes en ZIP
        |--------------------------------------------------------------------------
        */
        if ($request->boolean('bulk')) {

            $data = $request->validate([
                'bulk'      => 'required|boolean',
                'basicData' => 'required|array',
                'reports'   => 'required|array|min:1',
            ]);

            if (!class_exists(\ZipArchive::class)) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'ZipArchive no está disponible en el servidor. Instala la extensión php-zip.',
                ], 500);
            }

            $basicData = $data['basicData'];
            $reports   = $data['reports'];

            \Log::debug('DATADIS REPORT BULK - después validate', [
                'reports_count' => count($reports),
                'reports' => collect($reports)->map(function ($report, $index) {
                    return [
                        'index' => $index,
                        'cups' => $report['supply']['cups'] ?? null,
                        'dateType' => $report['dateType'] ?? null,
                        'dateSelected' => $report['dateSelected'] ?? null,
                        'dateLabel' => $report['dateLabel'] ?? null,
                        'has_priceComparison' => array_key_exists('priceComparison', $report),
                        'priceComparison_is_empty' => empty($report['priceComparison'] ?? null),
                        'current' => $report['priceComparison']['current'] ?? null,
                        'bestOffer' => $report['priceComparison']['bestOffer'] ?? null,
                        'periodConsumption' => $report['periodConsumption'] ?? null,
                    ];
                })->toArray(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Si quieres parar aquí con DD, descomenta esto temporalmente
            |--------------------------------------------------------------------------
            */
            /*
            dd([
                'mode' => 'bulk',
                'reports_count' => count($reports),
                'reports' => collect($reports)->map(function ($report, $index) {
                    return [
                        'index' => $index,
                        'cups' => $report['supply']['cups'] ?? null,
                        'has_priceComparison' => array_key_exists('priceComparison', $report),
                        'priceComparison' => $report['priceComparison'] ?? null,
                        'periodConsumption' => $report['periodConsumption'] ?? null,
                    ];
                })->toArray(),
            ]);
            */

            /*
            |--------------------------------------------------------------------------
            | Logo empresa
            |--------------------------------------------------------------------------
            */
            $folder   = $basicData['enterprise']['asset_folder'] ?? '';
            $fullPath = base_path("../assets/enterprises/{$folder}/logos/mini-dark.png");

            if (file_exists($fullPath)) {
                $mime     = mime_content_type($fullPath);
                $contents = file_get_contents($fullPath);
                $logoUrl  = "data:{$mime};base64," . base64_encode($contents);
            } else {
                $logoUrl = null;
            }

            /*
            |--------------------------------------------------------------------------
            | Datos agente
            |--------------------------------------------------------------------------
            */
            $agentName = trim(
                ($basicData['userSubdomain']['firstName'] ?? '') . ' ' .
                ($basicData['userSubdomain']['lastName']  ?? '')
            );

            $agentPhone = $basicData['userSubdomain']['phone'] ?? '';
            $agentEmail = $basicData['userSubdomain']['email'] ?? '';

            /*
            |--------------------------------------------------------------------------
            | Configuración Browsershot
            |--------------------------------------------------------------------------
            */
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            $node = $isWindows
                ? 'C:\\Program Files\\nodejs\\node.exe'
                : '/usr/bin/node';

            $npm = $isWindows
                ? 'C:\\Program Files\\nodejs\\npm.cmd'
                : '/usr/bin/npm';

            $chrome = $isWindows
                ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
                : '/usr/bin/chromium';

            /*
            |--------------------------------------------------------------------------
            | Crear carpeta temporal y ZIP
            |--------------------------------------------------------------------------
            */
            $tmpDir = storage_path('app/datadis_reports_' . uniqid('', true));

            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0775, true);
            }

            $zipPath = storage_path('app/informes_datadis_' . uniqid('', true) . '.zip');

            $zip = new \ZipArchive();

            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('No se pudo crear el archivo ZIP.');
            }

            /*
            |--------------------------------------------------------------------------
            | Generar PDFs y añadirlos al ZIP
            |--------------------------------------------------------------------------
            */
            foreach ($reports as $index => $report) {

                \Log::debug('DATADIS REPORT BULK ITEM - antes validación', [
                    'index' => $index,
                    'cups' => $report['supply']['cups'] ?? null,
                    'has_supply' => !empty($report['supply']),
                    'has_contract' => !empty($report['contract']),
                    'has_stackedChartSeries' => !empty($report['stackedChartSeries']),
                    'stackedChartSeries_count' => is_array($report['stackedChartSeries'] ?? null) ? count($report['stackedChartSeries']) : null,
                    'totalConsumption' => $report['totalConsumption'] ?? null,
                    'has_priceComparison' => array_key_exists('priceComparison', $report),
                    'priceComparison_is_empty' => empty($report['priceComparison'] ?? null),
                    'priceComparison' => $report['priceComparison'] ?? null,
                    'periodConsumption' => $report['periodConsumption'] ?? null,
                ]);

                // Saltar informes sin datos válidos
                if (
                    empty($report['supply']) ||
                    empty($report['contract']) ||
                    empty($report['stackedChartSeries']) ||
                    !is_array($report['stackedChartSeries']) ||
                    count($report['stackedChartSeries']) <= 1 ||
                    !isset($report['totalConsumption']) ||
                    (float) $report['totalConsumption'] <= 0
                ) {
                    \Log::warning('DATADIS REPORT BULK ITEM - saltado por datos inválidos', [
                        'index' => $index,
                        'cups' => $report['supply']['cups'] ?? null,
                    ]);

                    continue;
                }

                $dateType  = $report['dateType'] ?? 'month';
                $dateLabel = $report['dateLabel'] ?? '';

                \Log::debug('DATADIS REPORT BULK ITEM - antes view', [
                    'index' => $index,
                    'cups' => $report['supply']['cups'] ?? null,
                    'dateType' => $dateType,
                    'dateLabel' => $dateLabel,
                    'has_priceComparison' => !empty($report['priceComparison'] ?? null),
                    'priceComparison_current' => $report['priceComparison']['current'] ?? null,
                    'priceComparison_bestOffer' => $report['priceComparison']['bestOffer'] ?? null,
                    'periodConsumption' => $report['periodConsumption'] ?? null,
                ]);

                $html = view('PDFs.datadisReport', [
                    'basicData'              => $basicData,
                    'supply'                 => $report['supply'],
                    'contract'               => $report['contract'],
                    'dateType'               => $dateType,
                    'dateLabel'              => $dateLabel,
                    'periodLabel'            => [
                        'day'     => 'Diario',
                        'isoWeek' => 'Semanal',
                        'month'   => 'Mensual',
                    ][$dateType] ?? $dateType,
                    'totalConsumption'       => $report['totalConsumption'] ?? 0,
                    'consumptionPerInterval' => $report['consumptionPerInterval'] ?? 0,
                    'donutSeries'            => $report['donutSeries'] ?? [],
                    'stackedChartSeries'     => $report['stackedChartSeries'] ?? [],
                    'summaryBarsSeries'      => $report['summaryBarsSeries'] ?? [],
                    'heatmapSeries'          => $report['heatmapSeries'] ?? [],

                    // IMPORTANTE: esto faltaba en tu modo bulk
                    'periodConsumption'      => $report['periodConsumption'] ?? [],
                    'priceComparison'        => $report['priceComparison'] ?? null,

                    'agentName'              => $agentName,
                    'agentPhone'             => $agentPhone,
                    'agentEmail'             => $agentEmail,
                    'logoUrl'                => $logoUrl,
                ])->render();

                $cups = $report['supply']['cups'] ?? 'datadis';

                $safeName = Str::slug(
                    ($index + 1) . '_' . $cups . '_' . $dateLabel,
                    '_'
                );

                if (!$safeName) {
                    $safeName = 'informe_datadis_' . ($index + 1);
                }

                $pdfPath = "{$tmpDir}/{$safeName}.pdf";

                $browsershot = Browsershot::html($html)
                    ->setNodeBinary($node)
                    ->setChromePath($chrome)
                    ->format('A4')
                    ->margins(10, 5, 10, 5)
                    ->showBackground()
                    ->scale(1)
                    ->timeout(120);

                if (!$isWindows) {
                    $browsershot
                        ->setNpmBinary($npm)
                        ->setEnvironmentOptions([
                            'HOME'            => '/tmp',
                            'XDG_CONFIG_HOME' => '/tmp',
                        ])
                        ->setOption('args', [
                            '--no-sandbox',
                            '--disable-dev-shm-usage',
                            '--no-zygote',
                            '--disable-gpu',
                            '--user-data-dir=/tmp/browsershot-profile-' . uniqid('', true),
                            '--single-process',
                        ])
                        ->waitUntilNetworkIdle();
                } else {
                    $browsershot->waitUntilNetworkIdle(false);
                }

                $browsershot->save($pdfPath);

                if (file_exists($pdfPath)) {
                    $createdFiles[] = $pdfPath;
                    $zip->addFile($pdfPath, basename($pdfPath));

                    \Log::debug('DATADIS REPORT BULK ITEM - PDF creado', [
                        'index' => $index,
                        'cups' => $cups,
                        'pdfPath' => $pdfPath,
                    ]);
                } else {
                    \Log::warning('DATADIS REPORT BULK ITEM - PDF no existe tras save', [
                        'index' => $index,
                        'cups' => $cups,
                        'pdfPath' => $pdfPath,
                    ]);
                }
            }

            if (count($createdFiles) === 0) {
                $zip->close();

                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }

                if ($tmpDir && is_dir($tmpDir)) {
                    foreach (array_diff(scandir($tmpDir), ['.', '..']) as $file) {
                        $path = $tmpDir . DIRECTORY_SEPARATOR . $file;
                        if (file_exists($path)) {
                            unlink($path);
                        }
                    }
                    rmdir($tmpDir);
                }

                return response()->json([
                    'ok'      => false,
                    'message' => 'No había informes válidos para generar.',
                ], 422);
            }

            $zip->close();

            foreach ($createdFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            if ($tmpDir && is_dir($tmpDir)) {
                rmdir($tmpDir);
            }

            \Log::debug('DATADIS REPORT BULK - ZIP generado OK', [
                'zipPath' => $zipPath,
                'createdFilesCount' => count($createdFiles),
            ]);

            return response()
                ->download($zipPath, 'informes_datadis_' . Carbon::now()->format('Ymd_His') . '.zip')
                ->deleteFileAfterSend(true);
        }

        /*
        |--------------------------------------------------------------------------
        | MODO NORMAL: un PDF individual
        |--------------------------------------------------------------------------
        */
        $data = $request->validate([
            'supply'                 => 'required|array',
            'contract'               => 'required|array',
            'dateType'               => 'required|string',
            'dateLabel'              => 'required|string',
            'totalConsumption'       => 'required|numeric',
            'consumptionPerInterval' => 'required|numeric',
            'donutSeries'            => 'required|array',
            'heatmapSeries'          => 'required|array',
            'stackedChartSeries'     => 'required|array',
            'summaryBarsSeries'      => 'required|array',
            'basicData'              => 'required|array',
            'periodConsumption'      => 'nullable|array',
            'priceComparison'        => 'nullable|array',
        ]);

        \Log::debug('DATADIS REPORT SINGLE - después validate', [
            'cups' => $data['supply']['cups'] ?? null,
            'dateType' => $data['dateType'] ?? null,
            'dateLabel' => $data['dateLabel'] ?? null,
            'has_priceComparison' => array_key_exists('priceComparison', $data),
            'priceComparison_is_empty' => empty($data['priceComparison'] ?? null),
            'priceComparison' => $data['priceComparison'] ?? null,
            'periodConsumption' => $data['periodConsumption'] ?? null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Si quieres parar aquí con DD, descomenta esto temporalmente
        |--------------------------------------------------------------------------
        */
        /*
        dd([
            'mode' => 'single',
            'cups' => $data['supply']['cups'] ?? null,
            'has_priceComparison' => array_key_exists('priceComparison', $data),
            'priceComparison' => $data['priceComparison'] ?? null,
            'periodConsumption' => $data['periodConsumption'] ?? null,
        ]);
        */

        /*
        |--------------------------------------------------------------------------
        | Logo empresa
        |--------------------------------------------------------------------------
        */
        $folder   = $data['basicData']['enterprise']['asset_folder'] ?? '';
        $fullPath = base_path("../assets/enterprises/{$folder}/logos/mini-dark.png");

        if (file_exists($fullPath)) {
            $mime     = mime_content_type($fullPath);
            $contents = file_get_contents($fullPath);
            $logoUrl  = "data:{$mime};base64," . base64_encode($contents);
        } else {
            $logoUrl = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Datos agente
        |--------------------------------------------------------------------------
        */
        $agentName = trim(
            ($data['basicData']['userSubdomain']['firstName'] ?? '') . ' ' .
            ($data['basicData']['userSubdomain']['lastName']  ?? '')
        );

        $agentPhone = $data['basicData']['userSubdomain']['phone'] ?? '';
        $agentEmail = $data['basicData']['userSubdomain']['email'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Render Blade
        |--------------------------------------------------------------------------
        */
        \Log::debug('DATADIS REPORT SINGLE - antes view', [
            'cups' => $data['supply']['cups'] ?? null,
            'has_priceComparison' => !empty($data['priceComparison'] ?? null),
            'priceComparison_current' => $data['priceComparison']['current'] ?? null,
            'priceComparison_bestOffer' => $data['priceComparison']['bestOffer'] ?? null,
            'periodConsumption' => $data['periodConsumption'] ?? null,
        ]);

        $html = view('PDFs.datadisReport', [
            'basicData'              => $data['basicData'],
            'supply'                 => $data['supply'],
            'contract'               => $data['contract'],
            'dateType'               => $data['dateType'],
            'dateLabel'              => $data['dateLabel'],
            'periodLabel'            => [
                'day'     => 'Diario',
                'isoWeek' => 'Semanal',
                'month'   => 'Mensual',
            ][$data['dateType']] ?? $data['dateType'],
            'totalConsumption'       => $data['totalConsumption'],
            'consumptionPerInterval' => $data['consumptionPerInterval'],
            'donutSeries'            => $data['donutSeries'],
            'stackedChartSeries'     => $data['stackedChartSeries'],
            'summaryBarsSeries'      => $data['summaryBarsSeries'],
            'heatmapSeries'          => $data['heatmapSeries'],
            'agentName'              => $agentName,
            'agentPhone'             => $agentPhone,
            'agentEmail'             => $agentEmail,
            'logoUrl'                => $logoUrl,
            'periodConsumption'      => $data['periodConsumption'] ?? [],
            'priceComparison'        => $data['priceComparison'] ?? null,
        ])->render();

        /*
        |--------------------------------------------------------------------------
        | Browsershot
        |--------------------------------------------------------------------------
        */
        $tempPath = storage_path('app/tmp_datadis_' . uniqid('', true) . '.pdf');

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        $node = $isWindows
            ? 'C:\\Program Files\\nodejs\\node.exe'
            : '/usr/bin/node';

        $npm = $isWindows
            ? 'C:\\Program Files\\nodejs\\npm.cmd'
            : '/usr/bin/npm';

        $chrome = $isWindows
            ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe'
            : '/usr/bin/chromium';

        $browsershot = Browsershot::html($html)
            ->setNodeBinary($node)
            ->setChromePath($chrome)
            ->format('A4')
            ->margins(10, 5, 10, 5)
            ->showBackground()
            ->scale(1)
            ->timeout(120);

        if (!$isWindows) {
            $browsershot
                ->setNpmBinary($npm)
                ->setEnvironmentOptions([
                    'HOME'            => '/tmp',
                    'XDG_CONFIG_HOME' => '/tmp',
                ])
                ->setOption('args', [
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--no-zygote',
                    '--disable-gpu',
                    '--user-data-dir=/tmp/browsershot-profile-' . uniqid('', true),
                    '--single-process',
                ])
                ->waitUntilNetworkIdle();
        } else {
            $browsershot->waitUntilNetworkIdle(false);
        }

        $browsershot->save($tempPath);

        $cups = $data['supply']['cups'] ?? 'datadis';
        $safeDateLabel = Str::slug($data['dateLabel'], '_') ?: Carbon::now()->format('Ymd_His');
        $filename = "consumo_{$cups}_{$safeDateLabel}.pdf";

        \Log::debug('DATADIS REPORT SINGLE - PDF generado OK', [
            'cups' => $cups,
            'filename' => $filename,
            'tempPath' => $tempPath,
        ]);

        return response()
            ->download($tempPath, $filename)
            ->deleteFileAfterSend(true);

    } catch (\Throwable $e) {

        if ($zip instanceof \ZipArchive) {
            try {
                $zip->close();
            } catch (\Throwable $ignored) {}
        }

        foreach ($createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        if ($tempPath && file_exists($tempPath)) {
            unlink($tempPath);
        }

        if ($zipPath && file_exists($zipPath)) {
            unlink($zipPath);
        }

        if ($tmpDir && is_dir($tmpDir)) {
            foreach (array_diff(scandir($tmpDir), ['.', '..']) as $file) {
                $path = $tmpDir . DIRECTORY_SEPARATOR . $file;
                if (file_exists($path)) {
                    unlink($path);
                }
            }
            rmdir($tmpDir);
        }

        \Log::error('Error generateDatadisReport', [
            'type'    => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        try {
            $errorMessage  = "❌ *Datadis Report*\n";
            $errorMessage .= ($e->getMessage() ?: get_class($e)) . "\n";
            $errorMessage .= basename($e->getFile()) . ':' . $e->getLine();

            $baseUrl = "https://api.ultramsg.com/" . env('WHATSAPP_INSTANCE_ID') . "/messages";

            Http::asForm()->post($baseUrl . '/chat', [
                'token' => env('WHATSAPP_TOKEN'),
                'to'    => '+34642118237',
                'body'  => $errorMessage,
            ]);
        } catch (\Throwable $ignored) {
            \Log::warning('No se pudo enviar WhatsApp de error Datadis', [
                'message' => $ignored->getMessage(),
            ]);
        }

        return response()->json([
            'ok'      => false,
            'message' => $request->boolean('bulk')
                ? 'No se pudo generar el paquete de informes.'
                : 'No se pudo generar el informe de consumo.',
            'error'   => $e->getMessage(),
            'line'    => $e->getLine(),
        ], 500);
    }
}

    public function getDatadisContractByCups(Request $request)
    {
        $cupsRaw = $request->input('cups', '');

        $cups = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $cupsRaw));

        if (strlen($cups) > 20) {
            $cups = substr($cups, 0, 20);
        }

        $searchCups = strtolower($cups);

        \Log::debug('GET DATADIS CONTRACT BY CUPS - DEBUG ENTRADA', [
            'cupsRaw' => $cupsRaw,
            'cups' => $cups,
            'searchCups' => $searchCups,
        ]);

        $orderByCUPS = Order::query()
            ->where('CUPS', $cups)
            ->first();

        $orderBySearchCups = Order::query()
            ->where('search_cups', $searchCups)
            ->first();

        \Log::debug('GET DATADIS CONTRACT BY CUPS - DEBUG RESULTADOS', [
            'by_CUPS_found' => !!$orderByCUPS,
            'by_search_cups_found' => !!$orderBySearchCups,
            'by_CUPS' => $orderByCUPS ? [
                '_id' => (string) ($orderByCUPS->_id ?? ''),
                'CUPS' => $orderByCUPS->CUPS ?? null,
                'search_cups' => $orderByCUPS->search_cups ?? null,
                'name' => $orderByCUPS->name ?? null,
                'fee' => $orderByCUPS->fee ?? null,
                'marketer' => $orderByCUPS->marketer ?? null,
                'product' => $orderByCUPS->product ?? null,
                'pricesProduct' => $orderByCUPS->pricesProduct ?? null,
            ] : null,
            'by_search_cups' => $orderBySearchCups ? [
                '_id' => (string) ($orderBySearchCups->_id ?? ''),
                'CUPS' => $orderBySearchCups->CUPS ?? null,
                'search_cups' => $orderBySearchCups->search_cups ?? null,
                'name' => $orderBySearchCups->name ?? null,
                'fee' => $orderBySearchCups->fee ?? null,
                'marketer' => $orderBySearchCups->marketer ?? null,
                'product' => $orderBySearchCups->product ?? null,
                'pricesProduct' => $orderBySearchCups->pricesProduct ?? null,
            ] : null,
        ]);

        $order = $orderByCUPS ?: $orderBySearchCups;

        return response()->json([
            'order' => $order,
        ]);
    }


    //Generar pdf optimizador
    public function generatePowerOptimizerReport(Request $request)
    {
        try {
            $data = $request->validate([
                'cups'               => 'required|string',
                'currentPowers'      => 'required|array',
                'optimizedPowers'    => 'required|array',
                'customPowers'       => 'required|array',
                'currentCost'        => 'required|array',
                'optimizedCost'      => 'required|array',
                'saving'             => 'required|numeric',
                'customAnnualSaving' => 'required|numeric',
                'maxDemand'          => 'required|array',
                'monthlyReadings'    => 'required|array',
                'basicData'          => 'required|array',
            ]);

            // ── Logo empresa (idéntico al resto de métodos) ──────────────────────
            $folder   = $data['basicData']['enterprise']['asset_folder'] ?? '';
            $fullPath = base_path("../assets/enterprises/{$folder}/logos/mini-dark.png");

            $logoUrl = null;
            if (file_exists($fullPath)) {
                $mime     = mime_content_type($fullPath);
                $contents = file_get_contents($fullPath);
                $logoUrl  = "data:{$mime};base64," . base64_encode($contents);
            }

            // ── Datos del agente ─────────────────────────────────────────────────
            $agentName  = trim(
                ($data['basicData']['userSubdomain']['firstName'] ?? '') . ' ' .
                ($data['basicData']['userSubdomain']['lastName']  ?? '')
            );
            $agentPhone = $data['basicData']['userSubdomain']['phone'] ?? '';
            $agentEmail = $data['basicData']['userSubdomain']['email'] ?? '';

            // ── Precios tarifa 3.0TD (€/kW·día) — espejo exacto del Vue ─────────
            $powerPrice = [
                'P1' => 0.055827, 'P2' => 0.029089, 'P3' => 0.012278,
                'P4' => 0.010647, 'P5' => 0.006887,  'P6' => 0.003951,
            ];
            $tepp = [
                'P1' => 0.171373, 'P2' => 0.090584, 'P3' => 0.028721,
                'P4' => 0.021891, 'P5' => 0.006142,  'P6' => 0.006142,
            ];

            // ── Render Blade ─────────────────────────────────────────────────────
            $html = view('PDFs.powerOptimizerReport', [
                'basicData'          => $data['basicData'],
                'cups'               => $data['cups'],
                'currentPowers'      => $data['currentPowers'],
                'optimizedPowers'    => $data['optimizedPowers'],
                'customPowers'       => $data['customPowers'],
                'currentCost'        => $data['currentCost'],
                'optimizedCost'      => $data['optimizedCost'],
                'saving'             => $data['saving'],
                'customAnnualSaving' => $data['customAnnualSaving'],
                'maxDemand'          => $data['maxDemand'],
                'monthlyReadings'    => $data['monthlyReadings'],
                'powerPrice'         => $powerPrice,
                'tepp'               => $tepp,
                'agentName'          => $agentName,
                'agentPhone'         => $agentPhone,
                'agentEmail'         => $agentEmail,
                'logoUrl'            => $logoUrl,
            ])->render();

            // ── Browsershot (idéntico configuración al resto) ────────────────────
            $tempPath  = storage_path('app/tmp_poweropt_' . uniqid('', true) . '.pdf');
            $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

            $node   = $isWindows ? 'C:\\Program Files\\nodejs\\node.exe'            : '/usr/bin/node';
            $npm    = $isWindows ? 'C:\\Program Files\\nodejs\\npm.cmd'             : '/usr/bin/npm';
            $chrome = $isWindows ? 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe' : '/usr/bin/chromium';

            $browsershot = Browsershot::html($html)
                ->setNodeBinary($node)
                ->setChromePath($chrome)
                ->format('A4')
                ->margins(10, 5, 10, 5)
                ->showBackground()
                ->scale(1);

            if (!$isWindows) {
                $browsershot
                    ->setNpmBinary($npm)
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
                    ->waitUntilNetworkIdle();
            } else {
                $browsershot->waitUntilNetworkIdle(false);
            }

            $browsershot->save($tempPath);

            $filename = "optimizacion_potencia_{$data['cups']}_" . now()->format('Y-m-d') . '.pdf';

            return response()
                ->download($tempPath, $filename)
                ->deleteFileAfterSend(true);

        } catch (\Throwable $e) {

            \Log::error('Error generatePowerOptimizerReport', [
                'type'    => get_class($e),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            // Notificación WhatsApp (idéntico al resto de métodos)
            $errorMessage  = "❌ *Power Optimizer Report*\n";
            $errorMessage .= ($e->getMessage() ?: get_class($e)) . "\n";
            $errorMessage .= basename($e->getFile()) . ':' . $e->getLine();

            $baseUrl = "https://api.ultramsg.com/" . env('WHATSAPP_INSTANCE_ID') . "/messages";
            Http::asForm()->post($baseUrl . '/chat', [
                'token' => env('WHATSAPP_TOKEN'),
                'to'    => '+34642118237',
                'body'  => $errorMessage,
            ]);

            return response()->json([
                'ok'      => false,
                'message' => 'No se pudo generar el informe de optimización.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine(),
            ], 500);
        }
    }

    //Función para sacar el PDF de una comparativa
    public static function generateElectricityPDF(Request $request)
    {

        try {
            $validated = $request->validate([
                'payload' => 'required|string',
                'enterpriseImg' => 'nullable|file|image|max:2048',
            ]);

            $data = json_decode($validated['payload'], true);


            $powerDiscountPercent = isset($data['prices']['powerDiscount'])
                ? (float) str_replace(',', '.', $data['prices']['powerDiscount'])
                : 0;



            $energyDiscountPercent = isset($data['prices']['energyDiscount'])
                ? (float) str_replace(',', '.', $data['prices']['energyDiscount'])
                : 0;

            $marketerLogoUrl = null;

            $marketerLogoFile = $data['pdfForm']['order']['marketerLogo'] ?? null;




            if (!empty($marketerLogoFile)) {
                $marketerLogoPath = base_path('..' . $marketerLogoFile);




                if (file_exists($marketerLogoPath)) {
                    $mime = mime_content_type($marketerLogoPath);
                    $contents = file_get_contents($marketerLogoPath);
                    $base64 = base64_encode($contents);
                    $marketerLogoUrl = "data:{$mime};base64,{$base64}";
                }

            }

            if ($request->hasFile('enterpriseImg')) {
                $img = $request->file('enterpriseImg');
                $mime = $img->getMimeType();
                $contents = file_get_contents($img->getRealPath());
                $base64 = base64_encode($contents);
                $logoUrl = "data:{$mime};base64,{$base64}";
            } else {
                $folder = $data['basicData']['enterprise']['asset_folder'] ?? '';
                $fullPath = base_path("../assets/enterprises/{$folder}/logos/mini-dark.png");
                if (file_exists($fullPath)) {
                    $mime = mime_content_type($fullPath);
                    $contents = file_get_contents($fullPath);
                    $base64 = base64_encode($contents);
                    $logoUrl = "data:{$mime};base64,{$base64}";
                } else {
                    $altPath = base_path("../assets/enterprises/{$folder}/logos/mini-dark.png");
                    if (file_exists($altPath)) {
                        $mime = mime_content_type($altPath);
                        $contents = file_get_contents($altPath);
                        $base64 = base64_encode($contents);
                        $logoUrl = "data:{$mime};base64,{$base64}";
                    } else {
                        $logoUrl = null;
                    }
                }
            }

            $logoFooterUrl = null;

            $folder = $data['basicData']['enterprise']['asset_folder'] ?? '';

            $footerPath = base_path("../assets/enterprises/{$folder}/logos/mini-dark.png");

            if (file_exists($footerPath)) {
                $mime = mime_content_type($footerPath);
                $contents = file_get_contents($footerPath);
                $base64 = base64_encode($contents);
                $logoFooterUrl = "data:{$mime};base64,{$base64}";
            }



            $pathWitro = base_path('../assets/enterprises/witro/witro.png');
            if (file_exists($pathWitro)) {
                $mime = mime_content_type($pathWitro);
                $contents = file_get_contents($pathWitro);
                $base64 = base64_encode($contents);
                $logoUrlWitro = "data:{$mime};base64,{$base64}";
            } else {
                $logoUrlWitro = null; // O ruta alternativa si no existe
            }
            $currentDate = Carbon::now()->format('d/m/Y');
            $date = $data['studyDate'] ?? $currentDate ?? $currentDate;
            $year = Carbon::now()->year;

            // 3) Periodos y formateo decimal
            $cupsPower = $data['cupsData']['power'] ?? [];
            $periodLabels = array_map(fn($i) => 'P' . ($i + 1), array_keys($cupsPower));
            $fmtDecimal = fn(?float $v, int $d = 2) => (floor($v ?? 0) == ($v ?? 0))
                ? number_format($v, 0, ',', '.')
                : number_format($v, $d, ',', '.');

            // 4) Cálculo de start/end (intervalos o fallback mes)
            $end = Carbon::now();

            if (($data['period'] ?? '') === 'year') {
                $start = $end->copy()->subYear();
            } else {
                $start = $end->copy()->subMonth();
            }

            $daysInclusive = $start->diffInDays($end);

            if (isset($data['totalDays'])) {
                $daysInclusive = $data['totalDays'];
            }

            if (isset($data['totalDays'])) {
                $daysInclusive = $data['totalDays'];
            }



            $lastPeriods = $data['cupsData']['consumption'] ?? [];

            $powerRows = [];
            $totPotAct = 0;
            $totPotOff = 0;
            $totPotActRaw = 0;

            foreach ($cupsPower as $i => $kwRaw) {
                $kw = (float) str_replace(',', '.', $kwRaw);
                $rawA = $data['prices']['power'][$i] ?? '0';
                $rawO = $data['offer']['power'][$i] ?? '0';

                $feeP = !empty($data['offerFees']['power'][$i])
                    ? (float) str_replace(',', '.', $data['offerFees']['power'][$i]) / 30
                    : 0.0;

                switch ($data['powerPricePeriod'] ?? '') {
                    case 'month':
                        $dispA = (float) str_replace(',', '.', $rawA) / 30;
                        $dispO = (float) str_replace(',', '.', $rawO) + $feeP;
                        break;
                    case 'year':
                        $dispA = (float) str_replace(',', '.', $rawA) / 365;
                        $dispO = (float) str_replace(',', '.', $rawO) + $feeP;
                        break;
                    default:
                        $dispA = (float) str_replace(',', '.', $rawA);
                        $dispO = (float) str_replace(',', '.', $rawO) + $feeP;
                }

                // Coste SIN descuento
                $costA = round($dispA * $kw * $daysInclusive, 2);
                $totPotActRaw += $costA;

                // Coste CON descuento
                $discountedCostA = $powerDiscountPercent > 0
                    ? round($costA * (1 - ($powerDiscountPercent / 100)), 2)
                    : $costA;

                $costO = round($dispO * $kw * $daysInclusive, 2);

                $totPotAct += $discountedCostA;
                $totPotOff += $costO;

                $powerRows[] = [
                    'period' => $periodLabels[$i] ?? '',
                    'kw' => number_format($kw, 2, ',', '.') . ' kW',
                    'priceActRaw' => (string) $rawA,
                    'priceOffRaw' => (string) $rawO,
                    'priceAct' => number_format($dispA, 6, ',', '.'),
                    'priceOff' => number_format($dispO, 6, ',', '.'),
                    'costActRaw' => number_format($costA, 2, ',', '.'),
                    'costAct' => number_format($discountedCostA, 2, ',', '.'),
                    'discount' => number_format($costA - $discountedCostA, 2, ',', '.'),
                    'costOff' => number_format($costO, 2, ',', '.'),
                ];
            }

            // € descontados en potencia
            $powerDiscountAmount = round($totPotActRaw - $totPotAct, 2);
            $totPotActFmt = number_format($totPotAct, 2, ',', '.');
            $totPotOffFmt = number_format($totPotOff, 2, ',', '.');

            $energyRows = [];
            $totEngAct = 0;
            $totEngOff = 0;
            $totEngActRaw = 0;

            foreach ($lastPeriods as $i => $kwhRaw) {
                $kwh = (float) str_replace(',', '.', $kwhRaw);

                $rateA = (float) str_replace(',', '.', $data['prices']['energy'][$i] ?? '0');
                $rateO = (float) str_replace(',', '.', $data['offer']['energy'][$i] ?? '0');

                $feeE = !empty($data['offerFees']['energy'][$i])
                    ? (float) str_replace(',', '.', $data['offerFees']['energy'][$i]) / 1000
                    : 0.0;

                $rateO += $feeE;

                // Coste SIN descuento
                $costA = round($rateA * $kwh, 2);
                $totEngActRaw += $costA;

                // Coste CON descuento
                $discountedCostA = $energyDiscountPercent > 0
                    ? round($costA * (1 - ($energyDiscountPercent / 100)), 2)
                    : $costA;

                $costO = round($rateO * $kwh, 2);

                $totEngAct += $discountedCostA;
                $totEngOff += $costO;

                $energyRows[] = [
                    'period' => $periodLabels[$i] ?? '',
                    'kwh' => number_format($kwh, 2, ',', '.') . ' kWh',
                    'priceAct' => number_format($rateA, 6, ',', '.'),
                    'priceOff' => number_format($rateO, 6, ',', '.'),
                    'costActRaw' => number_format($costA, 2, ',', '.'),
                    'costAct' => number_format($discountedCostA, 2, ',', '.'),
                    'discount' => number_format($costA - $discountedCostA, 2, ',', '.'),
                    'costOff' => number_format($costO, 2, ',', '.'),
                ];
            }

            // € descontados en energía
            $energyDiscountAmount = round($totEngActRaw - $totEngAct, 2);

            $totEngActFmt = number_format($totEngAct, 2, ',', '.');
            $totEngOffFmt = number_format($totEngOff, 2, ',', '.');

            // 8) Totales generales
            $actualTotal = $totPotAct + $totEngAct;
            $offerTotal = $totPotOff + $totEngOff;
            $extrasAct = collect($data['currentSubtotal'] ?? [])
                ->except(['power', 'energy', 'otherConceptsDetail'])
                ->filter(fn($v) => is_numeric($v))
                ->sum();

            $extrasOff = collect($data['offer']['subTotal'] ?? [])
                ->filter(function ($v) {
                    return is_array($v) && isset($v['total']);
                })
                ->sum('total');
            $totalRealAct = $actualTotal + $extrasAct;
            $totalRealOff = $offerTotal + $extrasOff;
            $percent = $actualTotal > 0
                ? round((($actualTotal - $offerTotal) / $actualTotal) * 100, 0)
                : 0;
            $totalRealAct = $actualTotal + $extrasAct;
            $totalRealOff = $offerTotal + $extrasOff;
            $percent = $actualTotal > 0
                ? round((($actualTotal - $offerTotal) / $actualTotal) * 100, 0)
                : 0;

            // 9) Datos de consumo mensual usando SOLO cupsData
            $months = $periodLabels;

            $monthlyKwh = array_map(
                fn($v) => (float) str_replace(',', '.', $v),
                $lastPeriods
            );
            $months = array_reverse($months);
            $monthlyKwh = array_reverse($monthlyKwh);

            $totalConsumptionRaw = array_sum(
                array_map(fn($v) => (float) str_replace(',', '.', $v), $lastPeriods)
            );

            // formateo: si es entero, sin decimales; si no, con hasta 2 dígitos
            $totalConsumptionFmt = rtrim(
                rtrim(number_format($totalConsumptionRaw, 2, ',', '.'), '0'),
                ','
            );

            $userSubdomainId = $data['basicData']['userSubdomain']['_id'] ?? null;

            $isBlueTheme = $userSubdomainId === '67dbec3341971220e30aebc2';
            $isVoltioPdf = $userSubdomainId === '6a26778e57743add5220e9f8';

            if ($isBlueTheme) {

                $barEnergyColor = '#93C5FD'; // azul claro

                $pieEnergyColors = [
                    '#DBEAFE',
                    '#BFDBFE',
                    '#93C5FD',
                    '#60A5FA',
                    '#3B82F6',
                    '#1D4ED8',
                ];

            } else {

                $barEnergyColor = '#FCA5A5';

                $pieEnergyColors = [
                    '#FECACA',
                    '#FCA5A5',
                    '#EF4444',
                    '#DC2626',
                    '#B91C1C',
                    '#7F1D1D',
                ];
            }

            $barEnergyImage = 'data:image/png;base64,' . base64_encode(
                    (new Client())->get(
                        'https://quickchart.io/chart?c=' . urlencode(json_encode([
                            'type' => 'bar',
                            'data' => [
                                'labels' => $months,
                                'datasets' => [
                                    [
                                        'label' => 'Consumo (kWh)',
                                        'data' => $monthlyKwh,
                                        'backgroundColor' => $barEnergyColor
                                    ]
                                ]
                            ],
                            'options' => [
                                'scales' => [
                                    'y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'kWh']]
                                ],
                                'plugins' => ['legend' => ['display' => false]]
                            ]
                        ]))
                    )->getBody()
                );

            // 11) Gráfica comparativa anual vs oferta (sólo si period=year)
            $chartImage = null;
            if (($data['period'] ?? '') === 'year') {

                // Usamos los periodos P1–P6 como etiquetas
                $labels = $periodLabels;

                // Coste actual por periodo (potencia + energía)
                $dataAct = [];
                $dataOff = [];

                foreach ($periodLabels as $i => $label) {

                    $kw  = (float) str_replace(',', '.', $data['cupsData']['power'][$i] ?? 0);
                    $kwh = (float) str_replace(',', '.', $data['cupsData']['consumption'][$i] ?? 0);

                    $ratePowerAct = (float) str_replace(',', '.', $data['prices']['power'][$i] ?? 0);
                    $rateEnergyAct = (float) str_replace(',', '.', $data['prices']['energy'][$i] ?? 0);

                    $ratePowerOff = (float) str_replace(',', '.', $data['offer']['power'][$i] ?? 0);
                    $rateEnergyOff = (float) str_replace(',', '.', $data['offer']['energy'][$i] ?? 0);

                    // Coste actual estimado
                    $costAct = round(($ratePowerAct * $kw * 365) + ($rateEnergyAct * $kwh), 2);

                    // Coste oferta estimado
                    $costOff = round(($ratePowerOff * $kw * 365) + ($rateEnergyOff * $kwh), 2);

                    $dataAct[] = $costAct;
                    $dataOff[] = $costOff;
                }

                $compConfig = [
                    'type' => 'bar',
                    'data' => [
                        'labels' => $labels,
                        'datasets' => [
                            [
                                'label' => 'Coste actual (€)',
                                'data' => $dataAct,
                                'stack' => 'stack1'
                            ],
                            [
                                'label' => 'Coste oferta (€)',
                                'data' => $dataOff,
                                'stack' => 'stack1'
                            ],
                        ],
                    ],
                    'options' => [
                        'scales' => [
                            'x' => ['stacked' => true],
                            'y' => ['stacked' => true, 'beginAtZero' => true],
                        ],
                        'plugins' => [
                            'legend' => ['position' => 'top']
                        ],
                    ],
                ];

                $chartImage = 'data:image/png;base64,' . base64_encode(
                        (new Client())->get(
                            'https://quickchart.io/chart?c=' . urlencode(json_encode($compConfig))
                        )->getBody()
                    );
            }

            // 12) Línea de potencia
            $powerLineConfig = [
                'type' => 'line',
                'data' => [
                    'labels' => $periodLabels,
                    'datasets' => [
                        [
                            'label' => 'Potencia (kW)',
                            'data' => array_map(fn($v) => (float) str_replace(',', '.', $v), $cupsPower),
                            'fill' => false,
                            'tension' => 0.2
                        ]
                    ]
                ],
                'options' => [
                    'scales' => [
                        'y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'kW']]
                    ]
                ]
            ];
            $powerLineImage = 'data:image/png;base64,' . base64_encode(
                    (new Client())->get('https://quickchart.io/chart?c=' . urlencode(json_encode($powerLineConfig)))->getBody()
                );

            // 13) Pastel con % en etiquetas
            $pastelColors = ['#FFCDD2', '#FFF9C4', '#BBDEFB', '#E1BEE7', '#FFE0B2', '#C8E6C9'];
            $pieLabels = [];
            $pieData = [];
            $pieColors = [];
            foreach ($periodLabels as $i => $lbl) {
                $v = (float) str_replace(',', '.', ($lastPeriods[$i] ?? 0));
                if ($v > 0) {
                    $pieLabels[] = $lbl;
                    $pieData[] = $v;
                    $pieColors[] = $pastelColors[$i] ?? '#ccc';
                }
            }
            $sum = array_sum($pieData);
            foreach ($pieData as $i => $v) {
                $pieLabels[$i] .= ' (' . number_format($sum ? ($v / $sum * 100) : 0, 2, ',', '.') . '%)';
            }
            $pieConfig = [
                'type' => 'pie',
                'data' => ['labels' => $pieLabels, 'datasets' => [['data' => $pieData, 'backgroundColor' => $pieEnergyColors]]],
                'options' => ['plugins' => ['legend' => ['position' => 'bottom']]]
            ];
            $pieEnergyImage = 'data:image/png;base64,' . base64_encode(
                    (new Client())->get('https://quickchart.io/chart?c=' . urlencode(json_encode($pieConfig)))->getBody()
                );

            // 14) Barras P1–P6
            $barP1P6Config = [
                'type' => 'bar',
                'data' => [
                    'labels' => $periodLabels,
                    'datasets' => [['label' => 'P1–P6 (kWh)', 'data' => array_map(fn($v) => (float) str_replace(',', '.', $v), $lastPeriods), 'backgroundColor' => $pastelColors]]
                ],
                'options' => ['scales' => ['y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'kWh']]]]
            ];
            $barP1P6Image = 'data:image/png;base64,' . base64_encode(
                    (new Client())->get('https://quickchart.io/chart?c=' . urlencode(json_encode($barP1P6Config)))->getBody()
                );

            $all = collect($data['filteredOffers'])
                ->map(function ($offer) use ($data) {
                    $save = round($data['currentTotal'] - $offer['total'], 2);
                    $savePercent = $data['currentTotal'] > 0
                        ? round($save / $data['currentTotal'] * 100, 2)
                        : 0;
                    return array_merge($offer, [
                        'save' => $save,
                        'savePercent' => $savePercent,
                    ]);
                });






            if (!empty($data['selectedOffers']) && is_array($data['selectedOffers'])) {
                $sel = $data['offerSelected'];

                // 1) Encuentra la oferta principal en $all
                $mainOffer = $all->first(function ($o) use ($sel) {
                    return $o['marketer'] === $sel['marketer']
                        && $o['product'] === $sel['product'];
                });

                $others = collect($data['selectedOffers'])
                    ->map(function ($o) use ($all) {
                        return $all->first(function ($x) use ($o) {
                            return $x['marketer'] === $o['marketer']
                                && $x['product'] === $o['product'];
                        });
                    })
                    ->filter()           // quita nulls
                    ->reject(function ($o) use ($mainOffer) {
                        // quita la principal si apareciera en este array
                        return $o['marketer'] === $mainOffer['marketer']
                            && $o['product'] === $mainOffer['product'];
                    })
                    ->sortByDesc('save'); // ordena según ahorro descendente

                $topOffers = collect([$mainOffer])
                    ->merge($others)
                    ->values()
                    ->all();
            } else {
                $sel = $data['offerSelected'];
                $idx = $all->search(function ($o) use ($sel) {
                    return $o['marketer'] === $sel['marketer']
                        && $o['product'] === $sel['product'];
                });

                $topOffers = $idx !== false
                    ? $all->slice($idx, 5)
                    : $all->slice(0, 5);
                $topOffers = $topOffers->values()->all();
            }

            if (!empty($data['selectedOffers'])) {
                $showTopOffers = false;
            } else {
                $userNoTopOffers = ['67f775594a1890c57b9d6841'];
                $enterpriseId = $data['enterpriseId'] ?? null;
                $showTopOffers = in_array($enterpriseId, $userNoTopOffers, true);
            }

            //Si el usuario introdujo el total a mano
            if (isset($data['manualTotal'])) {
                $totalRealAct = $data['currentTotal'];
            }

            $percentPotencia = $totPotAct > 0 ? round((($totPotAct - $totPotOff) / $totPotAct) * 100, 1) : 0;
            $percentEnergia = $totEngAct > 0 ? round((($totEngAct - $totEngOff) / $totEngAct) * 100, 1) : 0;
            $percentTotal = $totalRealAct > 0 ? round((($totalRealAct - $totalRealOff) / $totalRealAct) * 100, 1) : 0;


            // ===== Donuts SVG Base64 =====
            $makeDonutSvg = function ($percent, $color, $size = 140, $stroke = 14, $fontSize = 28) {
                $radius = ($size / 2) - ($stroke / 2);
                $circ = 2 * M_PI * $radius;
                $dash = max(min($percent, 100), 0) / 100 * $circ;
                $cx = $cy = $size / 2;

                $svg = '
                    <svg width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" xmlns="http://www.w3.org/2000/svg">
                      <circle cx="' . $cx . '" cy="' . $cy . '" r="' . $radius . '" fill="none" stroke="#E0E0E0" stroke-width="' . $stroke . '" stroke-linecap="round"/>
                      <g transform="rotate(-90 ' . $cx . ' ' . $cy . ')">
                        <circle cx="' . $cx . '" cy="' . $cy . '" r="' . $radius . '" fill="none"
                                stroke="' . $color . '" stroke-width="' . $stroke . '" stroke-linecap="round"
                                stroke-dasharray="' . $dash . ',' . $circ . '"/>
                      </g>
                      <text x="' . $cx . '" y="' . $cy . '" text-anchor="middle" dominant-baseline="middle"
                            font-family="Inter, Arial, sans-serif" font-weight="800" font-size="' . $fontSize . '" fill="' . $color . '">' . round($percent) . '%</text>
                    </svg>';

                return 'data:image/svg+xml;base64,' . base64_encode($svg);
            };

            if ($isBlueTheme) {
                $donutPotenciaImage = $makeDonutSvg($percentPotencia, '#93C5FD', 100, 10, 18);
                $donutEnergiaImage  = $makeDonutSvg($percentEnergia,  '#60A5FA', 100, 10, 18);
                $donutTotalImage    = $makeDonutSvg($percentTotal,    '#1D4ED8', 160, 16, 32);

            } else {
                $donutPotenciaImage = $makeDonutSvg($percentPotencia, '#ca9999', 100, 10, 18);
                $donutEnergiaImage  = $makeDonutSvg($percentEnergia,  '#ca9999', 100, 10, 18);
                $donutTotalImage    = $makeDonutSvg($percentTotal,    '#7f1d1d', 160, 16, 32);

            }


            // ===== GRÁFICA COMPARATIVA DE COSTES =====
            $comparativaConfig = [
                'type' => 'bar',
                'data' => [
                    'labels' => ['Coste actual', $data['pdfForm']['name']],
                    'datasets' => [
                        [
                            'label' => 'Costes (€)',
                            'data' => [
                                round($actualTotal, 2),
                                round($offerTotal, 2),
                            ],
                            'backgroundColor' => [
                                '#FACC15', // amarillo
                                '#B91C1C', // rojo
                            ],
                        ],
                    ],
                ],
                'options' => [
                    'responsive' => true,
                    'plugins' => [
                        'legend' => [
                            'display' => true,
                            'position' => 'top',
                        ],
                        'datalabels' => [
                            'anchor' => 'end',
                            'align' => 'end',
                            'color' => '#000',
                            'font' => [
                                'weight' => 'bold',
                                'size' => 12,
                            ],
                            'formatter' => "function(value) {
                                return value.toLocaleString('es-ES', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }) + ' €';
                            }",
                        ],
                    ],
                    'scales' => [
                        'y' => [
                            'beginAtZero' => true,
                            'title' => [
                                'display' => true,
                                'text' => '€',
                            ],
                        ],
                    ],
                ],
                'plugins' => ['chartjs-plugin-datalabels'],
            ];

            // Imagen base64
            $chartComparativa = 'data:image/png;base64,' . base64_encode((new Client())->get('https://quickchart.io/chart?c=' . urlencode(json_encode($comparativaConfig)))->getBody());


            $selectedOffersEnriched = collect($data['selectedOffers'] ?? [])
                ->map(function ($offer) use ($data) {
                    $save = round($data['currentTotal'] - $offer['total'], 2);
                    $savePercent = $data['currentTotal'] > 0
                        ? round($save / $data['currentTotal'] * 100, 2)
                        : 0;

                    return array_merge($offer, [
                        'save' => $save,
                        'savePercent' => $savePercent,
                    ]);
                })
                ->values()
                ->all();





            if (!isset($data['whats'])) {


                if ($data['basicData']['userSubdomain']["_id"] == "67fcc5f6520d2c085f00e512") {

                    $monthlyData = []; // tabla vacía


                    $html = View::make('PDFs.kuvi', [
                        'cupsIntervalsData' => $data['cupsIntervalsData'],
                        'logoUrl' => $logoUrl,
                        'currentDate' => $currentDate,
                        'year' => $year,
                        'cups' => $data['pdfForm']['order']['CUPS'],
                        'tariff' => $data['fee'],
                        'actualCom' => $data['pdfForm']['currentMarketer'],
                        'offerCom' => str_replace(' (ZOCO)', '', $data['pdfForm']['order']['marketer']),
                        'product' => $data['pdfForm']['order']['product'],
                        'periodLabels' => $periodLabels,
                        'powerRows' => $powerRows,
                        'energyRows' => $energyRows,
                        'totPotActFmt' => $totPotActFmt,
                        'totPotOffFmt' => $totPotOffFmt,
                        'totEngActFmt' => $totEngActFmt,
                        'totEngOffFmt' => $totEngOffFmt,
                        'totPotAct' => $totPotAct,
                        'totPotOff' => $totPotOff,
                        'totEngAct' => $totEngAct,
                        'totEngOff' => $totEngOff,
                        'actualTotal' => $actualTotal,
                        'offerTotal' => $offerTotal,
                        'extrasAct' => $extrasAct,
                        'extrasOff' => $extrasOff,
                        'totalRealAct' => $totalRealAct,
                        'totalRealOff' => $totalRealOff,
                        'percentFmt' => number_format($percent, 2, ',', '.') . '%',
                        'periodDays' => $daysInclusive,
                        'barEnergyImage' => $barEnergyImage,
                        'chartImage' => $chartImage,
                        'powerLineImage' => $powerLineImage,
                        'pieEnergyImage' => $pieEnergyImage,
                        'barP1P6Image' => $barP1P6Image,
                        'enterprise' => $data['pdfForm']['name'],
                        'location' => $data['pdfForm']['order']['direc'],
                        'CIF' => $data['pdfForm']['CIF'],
                        'startDate' => $start->format('d/m/Y'),
                        'endDate' => $end->format('d/m/Y'),
                        'totalDays' => $data['totalDays'] ?? null,
                        'topOffers' => $topOffers,
                        'period' => $data['period'],
                        'powerPricePeriod' => $data['powerPricePeriod'],
                        'priceActrow' => $data['prices']['power'],
                        'priceOffrow' => $data['offer']['power'],
                        'totalConsumptionFmt' => $totalConsumptionFmt,
                        'showTopOffers' => $showTopOffers,
                        'monthlyData' => $monthlyData,
                        'chartComparativa' => $chartComparativa,
                        'powerDiscountPercent' => $powerDiscountPercent,
                        'energyDiscountPercent' => $energyDiscountPercent,
                        'powerDiscountAmount' => $powerDiscountAmount,
                        'energyDiscountAmount' => $energyDiscountAmount,
                        'otherConceptsDetail' => $data['currentSubtotal']['otherConceptsDetail'] ?? [],
                        'data' => $data

                    ])->render();

                    $fileName = "comparativa_kuvi_{$data['pdfForm']['order']['CUPS']}.pdf";

                    // Ruta temporal segura
                    $tempPath = storage_path('app/tmp_pdf_' . uniqid('', true) . '.pdf');

                    // MISMO Chrome que ya funciona en Witro
                    $chromeExec = '/usr/bin/chromium';

                    Browsershot::html($html)
                        ->setChromePath($chromeExec)
                        ->setNodeBinary('/usr/bin/node')
                        ->setNpmBinary('/usr/bin/npm')
                        ->setEnvironmentOptions([
                            'HOME' => '/tmp',
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
                        ->margins(5, 7, 7, 7)
                        ->showBackground()
                        ->waitUntilNetworkIdle()
                        ->scale(1)
                        ->save($tempPath);

                    // Descargar el PDF ya cerrado (NO corrupto)
                    return response()->download($tempPath, $fileName)->deleteFileAfterSend(true);


                }
                else {

                    $subdomainId = $data['basicData']['userSubdomain']['_id'] ?? null;

                    $useFidelity360TwoPagesPdf = $subdomainId === '6909faa9232c09035a03f3b2';


                    $viewData = [
                        'isBlueTheme' => ($data['basicData']['userSubdomain']['_id'] ?? null) === '67dbec3341971220e30aebc2',
                        'logoUrl' => $logoUrl,
                        'currentDate' => $currentDate,
                        'year' => $year,
                        'cups' => $data['pdfForm']['order']['CUPS'] ?? '',
                        'tariff' => $data['fee'],
                        'actualCom' => $data['pdfForm']['currentMarketer'],
                        'offerCom' => str_replace(' (ZOCO)', '', $data['pdfForm']['order']['marketer']),
                        'product' => $data['pdfForm']['order']['product'],
                        'enterprise' => $data['pdfForm']['name'],
                        'location' => $data['pdfForm']['order']['direc'],
                        'CIF' => $data['pdfForm']['CIF'],

                        // Fechas
                        'startDate' => $start->format('d/m/Y'),
                        'endDate' => $end->format('d/m/Y'),
                        'periodDays' => $daysInclusive,
                        'period' => $data['period'],
                        'powerPricePeriod' => $data['powerPricePeriod'],

                        // Datos energía / potencia
                        'periodLabels' => $periodLabels,
                        'powerRows' => $powerRows,
                        'energyRows' => $energyRows,

                        // Totales
                        'totPotAct' => $totPotAct,
                        'totPotOff' => $totPotOff,
                        'totEngAct' => $totEngAct,
                        'totEngOff' => $totEngOff,

                        'totPotActFmt' => $totPotActFmt,
                        'totPotOffFmt' => $totPotOffFmt,
                        'totEngActFmt' => $totEngActFmt,
                        'totEngOffFmt' => $totEngOffFmt,

                        'actualTotal' => $actualTotal,
                        'offerTotal' => $offerTotal,
                        'extrasAct' => $extrasAct,
                        'extrasOff' => $extrasOff,
                        'totalRealAct' => $totalRealAct,
                        'totalRealOff' => $totalRealOff,
                        'percentFmt' => number_format($percent, 2, ',', '.') . '%',

                        // Descuentos
                        'powerDiscountPercent' => $powerDiscountPercent,
                        'energyDiscountPercent' => $energyDiscountPercent,
                        'powerDiscountAmount' => $powerDiscountAmount,
                        'energyDiscountAmount' => $energyDiscountAmount,

                        // Gráficas
                        'barEnergyImage' => $barEnergyImage,
                        'pieEnergyImage' => $pieEnergyImage,
                        'barP1P6Image' => $barP1P6Image,
                        'powerLineImage' => $powerLineImage,
                        'chartImage' => $chartImage,
                        'chartComparativa' => $chartComparativa,

                        // Donuts
                        'donutPotenciaImage' => $donutPotenciaImage,
                        'donutEnergiaImage' => $donutEnergiaImage,
                        'donutTotalImage' => $donutTotalImage,
                        'agentName' => trim((string) ($data['pdfForm']['agentName'] ?? '')),

                        'agentPhone' => trim((string) ($data['pdfForm']['agentPhone'] ?? '')),

                        'agentEmail' => trim((string) ($data['pdfForm']['agent'] ?? $data['pdfForm']['agentEmail'] ?? '')),

                        // Ofertas
                        'topOffers' => $topOffers,
                        'selectedOffers' => $selectedOffersEnriched,
                        'includeOffersInPdf' => $data['includeOffersInPdf'] ?? false,

                        'totalConsumptionFmt' => $totalConsumptionFmt,
                        'otherConceptsDetail' => $data['currentSubtotal']['otherConceptsDetail'] ?? [],
                        'logoFooter' => $logoFooterUrl,
                        'data' => $data,
                        'color' => $data['basicData']['enterprise']['color'],
                        'marketerLogoUrl' => $marketerLogoUrl
                    ];
                    // Render HTML
                    $subdomainId = $data['basicData']['userSubdomain']['_id'] ?? null;
                    $useFidelity360TwoPagesPdf = $subdomainId === '6909faa9232c09035a03f3b2';

                    if ($useFidelity360TwoPagesPdf) {
                        $folder = $data['basicData']['enterprise']['asset_folder'] ?? '';

                        // Solo usar profile.jpg si NO vino imagen del formulario
                        if (!$request->hasFile('enterpriseImg')) {
                            $fidelityProfilePath = base_path("../assets/enterprises/{$folder}/logos/profile.jpg");

                            if (file_exists($fidelityProfilePath)) {
                                $mime     = mime_content_type($fidelityProfilePath);
                                $contents = file_get_contents($fidelityProfilePath);
                                $base64   = base64_encode($contents);

                                $logoUrl      = "data:{$mime};base64,{$base64}";
                                $logoFooterUrl = $logoUrl;
                            }
                        }
                        // Si vino enterpriseImg, $logoUrl ya está seteado arriba correctamente

                        $viewData['logoUrl']    = $logoUrl;
                        $viewData['logoFooter'] = $logoUrl;
                        $logoFooterUrl          = $logoUrl;

                        $viewName = 'PDFs.fidelity360';
                    } else {
                        $viewName = !empty($data['excludeCurrentFromPdf'])
                            ? 'PDFs.comparatorOffer'
                            : 'PDFs.comparator';
                    }

                    $html = view($viewName, $viewData)->render();

                    // Archivo temporal
                    $tempPath = storage_path('app/tmp_pdf_' . uniqid('', true) . '.pdf');

                    // Chrome (el mismo que ya usas arriba)
                    $chromeExec = '/usr/bin/chromium';
                    Browsershot::html($html)
                        ->setChromePath($chromeExec)
                        ->setNodeBinary('/usr/bin/node')
                        ->setNpmBinary('/usr/bin/npm')
                        ->setEnvironmentOptions([
                            'HOME' => '/tmp',
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
                        ->margins(10, 5, 10, 5)
                        ->showBackground()
                        ->waitUntilNetworkIdle()
                        ->scale(1)
                        ->save($tempPath);

                    return response()
                    ->download(
                        $tempPath,
                        'comparativa_' .
                        (is_array($data['pdfForm']['name'] ?? null) ? 'cliente' : ($data['pdfForm']['name'] ?? 'cliente')) .
                        '_' .
                        (is_array($data['pdfForm']['order']['CUPS'] ?? null) ? 'sin_cups' : ($data['pdfForm']['order']['CUPS'] ?? 'sin_cups')) .
                        '.pdf'
                    )->deleteFileAfterSend(true);

                }





            }
            elseif (($data['origin'] ?? null) === 'crm') {

                $blade = (($data['basicData']['userSubdomain']['_id'] ?? null) === '6909faa9232c09035a03f3b2')
                ? 'PDFs.fidelity360'
                : 'PDFs.comparator';




                $html = view($blade, [
                    'isBlueTheme' => ($data['basicData']['userSubdomain']['_id'] ?? null) === '67dbec3341971220e30aebc2',
                    'logoUrl' => $logoUrl,
                    'currentDate' => $currentDate,
                    'year' => $year,

                    'cups' => $data['pdfForm']['order']['CUPS'],
                    'tariff' => $data['fee'],
                    'actualCom' => $data['pdfForm']['currentMarketer'],
                    'offerCom' => str_replace(' (ZOCO)', '', $data['pdfForm']['order']['marketer']),
                    'product' => $data['pdfForm']['order']['product'],
                    'enterprise' => $data['pdfForm']['name'],
                    'location' => $data['pdfForm']['order']['direc'],
                    'CIF' => $data['pdfForm']['CIF'],

                    // Fechas
                    'startDate' => $start->format('d/m/Y'),
                    'endDate' => $end->format('d/m/Y'),
                    'periodDays' => $daysInclusive,
                    'period' => $data['period'],
                    'powerPricePeriod' => $data['powerPricePeriod'],

                    // Datos energía / potencia
                    'periodLabels' => $periodLabels,
                    'powerRows' => $powerRows,
                    'energyRows' => $energyRows,

                    // Totales
                    'totPotAct' => $totPotAct,
                    'totPotOff' => $totPotOff,
                    'totEngAct' => $totEngAct,
                    'totEngOff' => $totEngOff,

                    'totPotActFmt' => $totPotActFmt,
                    'totPotOffFmt' => $totPotOffFmt,
                    'totEngActFmt' => $totEngActFmt,
                    'totEngOffFmt' => $totEngOffFmt,

                    'actualTotal' => $actualTotal,
                    'offerTotal' => $offerTotal,
                    'extrasAct' => $extrasAct,
                    'extrasOff' => $extrasOff,
                    'totalRealAct' => $totalRealAct,
                    'totalRealOff' => $totalRealOff,
                    'percentFmt' => number_format($percent, 2, ',', '.') . '%',

                    // Descuentos
                    'powerDiscountPercent' => $powerDiscountPercent,
                    'energyDiscountPercent' => $energyDiscountPercent,
                    'powerDiscountAmount' => $powerDiscountAmount,
                    'energyDiscountAmount' => $energyDiscountAmount,

                    // Gráficas
                    'barEnergyImage' => $barEnergyImage,
                    'pieEnergyImage' => $pieEnergyImage,
                    'barP1P6Image' => $barP1P6Image,
                    'powerLineImage' => $powerLineImage,
                    'chartImage' => $chartImage,
                    'chartComparativa' => $chartComparativa,

                    // Donuts
                    'donutPotenciaImage' => $donutPotenciaImage,
                    'donutEnergiaImage' => $donutEnergiaImage,
                    'donutTotalImage' => $donutTotalImage,

                    'agentName' => trim((string) ($data['pdfForm']['agentName'] ?? '')),

                    'agentPhone' => trim((string) ($data['pdfForm']['agentPhone'] ?? '')),

                    'agentEmail' => trim((string) ($data['pdfForm']['agent'] ?? $data['pdfForm']['agentEmail'] ?? '')),

                    // Ofertas
                    'topOffers' => $topOffers,
                    'selectedOffers' => $selectedOffersEnriched,
                    'includeOffersInPdf' => $data['includeOffersInPdf'] ?? false,

                    'totalConsumptionFmt' => $totalConsumptionFmt,
                    'otherConceptsDetail' => $data['currentSubtotal']['otherConceptsDetail'] ?? [],
                    'logoFooter' => $logoFooterUrl,
                    'data' => $data,
                    'color' => $data['basicData']['enterprise']['color'],
                    'marketerLogoUrl' => $marketerLogoUrl
                ])->render();

                $filename = 'offerGenerated_' . ($data['from'] ?? 'unknown') . '_' . uniqid() . '.pdf';
                $path = Storage::disk('temporal_comparatives')->path($filename);

                $chromeExec = '/usr/bin/chromium';

                Browsershot::html($html)
                    ->setChromePath($chromeExec)
                    ->setNodeBinary('/usr/bin/node')
                    ->setNpmBinary('/usr/bin/npm')
                    ->setEnvironmentOptions([
                        'HOME' => '/tmp',
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
                    ->margins(10, 10, 10, 10)
                    ->showBackground()
                    ->save($path);

                return $filename;


            }
            else {

                $html = view('PDFs.comparatorWitro', [
                    'logoUrl' => $logoUrlWitro,
                    'currentDate' => $currentDate,
                    'year' => $year,
                    'cups' => $data['pdfForm']['order']['CUPS'],
                    'tariff' => $data['fee'],
                    'actualCom' => $data['pdfForm']['currentMarketer'],
                    'offerCom' => $data['pdfForm']['order']['marketer'],
                    'product' => $data['pdfForm']['order']['product'],
                    'periodLabels' => $periodLabels,
                    'powerRows' => $powerRows,
                    'energyRows' => $energyRows,
                    'totPotActFmt' => $totPotActFmt,
                    'totPotOffFmt' => $totPotOffFmt,
                    'totEngActFmt' => $totEngActFmt,
                    'totEngOffFmt' => $totEngOffFmt,
                    'actualTotal' => $actualTotal,
                    'offerTotal' => $offerTotal,
                    'extrasAct' => $extrasAct,
                    'extrasOff' => $extrasOff,
                    'totalRealAct' => $totalRealAct,
                    'totalRealOff' => $totalRealOff,
                    'percentFmt' => number_format($percent, 2, ',', '.') . '%',
                    'periodDays' => $daysInclusive,
                    'barEnergyImage' => $barEnergyImage,
                    'chartImage' => $chartImage,
                    'powerLineImage' => $powerLineImage,
                    'pieEnergyImage' => $pieEnergyImage,
                    'barP1P6Image' => $barP1P6Image,
                    'enterprise' => $data['pdfForm']['name'],
                    'location' => $data['pdfForm']['order']['direc'],
                    'CIF' => $data['pdfForm']['CIF'],
                    'startDate' => $start->format('d/m/Y'),
                    'endDate' => $end->format('d/m/Y'),
                    'topOffers' => $topOffers,
                    'period' => $data['period'],
                    'powerPricePeriod' => $data['powerPricePeriod'],
                    'priceActrow' => $data['prices']['power'],
                    'priceOffrow' => $data['offer']['power'],
                    'totalConsumptionFmt' => $totalConsumptionFmt,
                    'showTopOffers' => $showTopOffers,
                    'donutPotenciaImage' => $donutPotenciaImage,
                    'donutEnergiaImage' => $donutEnergiaImage,
                    'donutTotalImage' => $donutTotalImage,
                    'agentName' => trim((string) ($data['pdfForm']['agentName'] ?? '')),
                    'agentPhone' => trim((string) ($data['pdfForm']['agentPhone'] ?? '')),
                    'agentEmail' => trim((string) ($data['pdfForm']['agent'] ?? $data['pdfForm']['agentEmail'] ?? '')),
                    'data' => $data
                ])->render();

                $filename = 'offerGenerated_' . ($data['from'] ?? 'unknown') . '_' . uniqid() . '.pdf';
                $path = Storage::disk('temporal_comparatives')->path($filename);

                $chromeExec = '/usr/bin/chromium';

                Browsershot::html($html)
                    ->setChromePath($chromeExec)
                    ->setNodeBinary('/usr/bin/node')
                    ->setNpmBinary('/usr/bin/npm')
                    ->setEnvironmentOptions([
                        'HOME' => '/tmp',
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
                    ->margins(10, 10, 10, 10)
                    ->showBackground()
                    ->save($path);

                return $filename;
            }



        } catch (\Throwable $e) {
            // 1) Log completo
            \Log::error('Error en ToolsController::generateElectricityPDF', [
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),

            ]);

            // 2) Guardar payload para revisar luego
            $payloadPath = 'debug/last-pdf-payload-' . date('Ymd_His') . '.json';
            Storage::disk('local')->put(
                $payloadPath,
                json_encode([
                    'error' => $e->getMessage(),
                    // 'data' => $data ?? null,
                    // 'request' => request()->all() ?? null,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            // 3) Mensaje corto para WhatsApp (no saturar con el trace)
            $errorMessage = "❌ *Tools Controller*\n";
            $errorMessage .= ($e->getMessage() ?: get_class($e)) . "\n";
            $errorMessage .= basename($e->getFile()) . ':' . $e->getLine();

            // Extra: si hay excepción encadenada
            if ($e->getPrevious()) {
                $prev = $e->getPrevious();
                $errorMessage .= "\n↪ Prev: " . ($prev->getMessage() ?: get_class($prev));
            }

            // 4) Enviar a WhatsApp
            $baseUrl = "https://api.ultramsg.com/" . env('WHATSAPP_INSTANCE_ID') . "/messages";
            Http::asForm()->post($baseUrl . '/chat', [
                'token' => env('WHATSAPP_TOKEN'),
                'to' => '+34614011145',
                'body' => $errorMessage . "\n\n📎 payload: storage/app/{$payloadPath}",
            ]);

            // 5) Respuesta HTTP
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo generar el PDF.',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ], 500);
        }
    }


    public static function generateComparativeLog(Request $request)
    {
        //Guardo el log
        AuditLogService::generateComparative($request->input('status'), $request->input('messageError'), $request->input('inputType'), $request->input('comparativeType'), $request->input('codePart'), $request->input('inputData'), $request->input('output'), Auth::user());
    }


    //SEGENET

    //Función para sacar los contadores disponibles con mis contratos
    public function getProbesAvailable()
    {
        $userLogged = Auth::user();

        $userList = UserHelper::hierarchy($userLogged->_id);
        $userList = array_merge(array_column($userList, '_id'), [$userLogged->_id]);

        $orders = Order::whereIn('usersIds', $userList)
            ->whereNotNull('CUPS')
            ->get(['_id', 'CUPS']);

        if ($orders->isEmpty()) {
            return response()->json(['probes' => []], 200);
        }

        // Sufijos a añadir al final del CUPS
        $suffixes = ['0F', '1P'];

        $cupsMap = [];
        $allCupsToSearch = [];

        foreach ($orders as $order) {
            $cups = $order->CUPS;

            // CUPS original
            $cupsMap[$cups] = $order->_id;
            $allCupsToSearch[] = $cups;

            // CUPS con sufijos añadidos
            foreach ($suffixes as $suffix) {
                $variant = $cups . $suffix;
                $cupsMap[$variant] = $order->_id;
                $allCupsToSearch[] = $variant;
            }
        }

        $probes = Probe::whereIn('cups', $allCupsToSearch)
            ->get(['serial', 'name', 'cups']);

        $probesWithOrder = $probes->map(function ($probe) use ($cupsMap) {
            return [
                'order_id' => $cupsMap[$probe->cups] ?? null,
                'serial'   => $probe->serial,
                'name'     => $probe->name,
                'cups'     => $probe->cups,
            ];
        });

        return response()->json(['probes' => $probesWithOrder], 200);
    }


    //Función para obtener la información de un contador
    public function getProbeInfo(Request $request)
    {

        //Saco además de la info del contador la fecha del primer y último cierre
        $probe = Probe::select(
            'probes.*',
            DB::raw('(SELECT MIN(c.from_date) FROM closes c WHERE c.probe_serial = probes.serial) as min_closes_from_date'),
            DB::raw('(SELECT MAX(c.from_date) FROM closes c WHERE c.probe_serial = probes.serial) as max_closes_from_date')
        )
            ->where('probes.serial', $request->input('serial'))
            ->first();

        return response()->json(['probe' => $probe], 200);
    }

    //Función para obtener los datos de un contador
    public function getProbeData(Request $request)
    {
        $serial = $request->input('serial');
        $dates = $request->input('dates');

        //Saco los datos de la curva
        $curve = SegenetHelper::getCurve($serial, $dates);

        return response()->json($curve, 200);
    }


    //Función sacar excel cuarto-horario
    public function excelQuarters(Request $request)
    {

        $serial = $request->input('serial');
        $dates = $request->input('dates');

        //Saco los cuartos
        $quarters = ProbeValuesQuarter::where('probe_serial', $serial)->whereBetween('inserted_at', [$dates['start'], $dates['end']])->get()->toArray();

        //Descargo el excel
        return Excel::download(new ProbeValuesQuartersExport($quarters), 'excel_cuarto-horario.xlsx');
    }


    //Función sacar excel cierres
    public function excelCloses(Request $request)
    {

        $serial = $request->input('serial');
        $date = $request->input('date');

        [$year, $month] = explode('-', $date);

        //Saco los cierres
        $closes_periods = Close::select('close_periods.*', 'closes.id as close_id', 'closes.probe_serial as probe_serial', 'closes.from_date as from_date', 'closes.to_date as to_date', 'closes.file_path as file_path', 'file_statuses.id as status_id', 'file_statuses.name as status_name', 'file_statuses.color as status_color', 'file_statuses.icon as status_icon')
            ->whereYear('from_date', $year)
            ->whereMonth('from_date', $month)
            ->where('probe_serial', $serial)
            ->join('close_periods', 'close_periods.close_id', 'closes.id')
            ->leftJoin('file_statuses', 'file_statuses.id', 'closes.file_status_id')
            ->orderBy('from_date', 'DESC')
            ->get()
            ->toArray();

        $close = new ProbeCloseReportClass();
        foreach ($closes_periods as $c)
            $close->addClosePeriod($c);

        //Saco el excel
        return Excel::download(new ClosesExport($close), 'excel_cierres.xlsx');
    }


    //Función sacar excel informe simulado
    public function excelMockInvoice(Request $request)
    {
        $serial = $request->input('serial');
        $dates = $request->input('dates');

        //Saco la información del dispositivo
        $probeInfo = SegenetHelper::getProbeInfo($serial);

        //Saco los cuartos
        $curve = SegenetHelper::getCurve($serial, $dates);

        //Creo el objeto resumen
        $RESUME = new Resume(SegenetHelper::createBills($curve, $probeInfo['probe']));

        //Realizo la simulación
        $RESUME->simulate();

        //Saco el excel
        return Excel::download(new MockInvoiceExport($RESUME), 'excel_informe_masivo.xlsx');
    }

    public function invoiceChecker(Request $request)
    {
        $request->validate([
            'status' => 'required|in:ok,error,differences',
            'cups' => 'nullable|string',
        ]);

        AuditLogService::invoiceChecker(
            $request->all(),
            auth()->user()->toArray()
        );

        return response()->json(['ok' => true]);
    }

    public function getAPIDataForInvoice(Request $request)
    {
        $cups = $request->input('cups');
        $fechaInicio = $request->input('fecha_inicio'); // "YYYY-MM-DD"
        $fechaFin = $request->input('fecha_fin');    // "YYYY-MM-DD"

        if (!$cups || !$fechaInicio || !$fechaFin) {
            return response()->json([
                'exists' => false,
                'message' => 'Faltan datos para la consulta',
            ], 400);
        }

        $start = Carbon::createFromFormat('Y-m-d', $fechaInicio);
        $end = Carbon::createFromFormat('Y-m-d', $fechaFin);

        $datadisRow = Datadis::where('cups', $cups)
            ->where('startDate', '<=', $end->format('Y/m'))   // <= mes FIN factura
            ->where('endDate', '>=', $start->format('Y/m'))   // >= mes INICIO factura
            ->orderByDesc('createdAt')
            ->first();

        $datadisAgg = null;
        $datadisTarifa = null;

        if ($datadisRow) {
            $consumption = $datadisRow->data ?? [];
            $codeFare = $datadisRow->codeFare ?? '2T'; // ajusta si en tu colección se llama distinto

            $datadisAgg = $this->aggregateDatadisByPeriods($consumption, $start, $end, $codeFare);
            $datadisTarifa = $codeFare;
        }

        // ===========================================================
        // 2) POTENCIAS / TARIFA / COMERCIALIZADORA DESDE SIPS
        //    (reutilizando la lógica que ya tienes en getAPIDataForInvoices)
        // ===========================================================
        $sipsInfo = $this->fetchSipsForInvoice($cups, $start, $end); // helper de abajo

        if (!$datadisAgg && !$sipsInfo) {
            return response()->json([
                'exists' => false,
                'message' => 'No hay datos ni en Datadis ni en SIPS para ese periodo',
            ]);
        }

        // ===========================================================
        // 3) Construir el objeto "sips" que usará el FRONT
        //     - potencias + comercializadora + tarifa => SIEMPRE SIPS (si hay)
        //     - consumos => Datadis si existe; si no, SIPS
        // ===========================================================
        $tarifa = $sipsInfo['tarifa'] ?? $datadisTarifa;
        $comercializadora = $sipsInfo['comercializadora'] ?? null;
        $potencias = $sipsInfo['potencias_contratadas'] ?? [];

        $consumos = [];

        $source = null;

        if ($datadisAgg) {
            // Consumo desde Datadis
            $consumos[] = [
                'startDate' => $start->format('Y-m-d'),
                'endDate' => $end->format('Y-m-d'),
                'periods' => $datadisAgg['periods'],   // P1..P6 => kWh
                'total' => $datadisAgg['total_kwh'],
            ];
            $source = $sipsInfo ? 'datadis+sips' : 'datadis';
        } elseif ($sipsInfo) {
            // Fallback: sin Datadis, consumos tal y como vienen de SIPS
            $consumos = $sipsInfo['consumos'];
            $source = 'sips';
        }

        $sips = [
            'tarifa' => $tarifa,
            'comercializadora' => $comercializadora,
            'potencias_contratadas' => $potencias,
            'consumos' => $consumos,
        ];

        return response()->json([
            'exists' => true,
            'source' => $source,
            'sips' => $sips,
        ]);
    }

    public function obtainDatadisTokenInvoice()
    {
        $client = new Client();

        $username = "B56037518";
        $password = "5676Segenet$";   // idem

        $url = 'https://datadis.es/nikola-auth/tokens/login';

        try {
            $response = $client->post($url, [
                'multipart' => [
                    [
                        'name' => 'username',
                        'contents' => $username,
                    ],
                    [
                        'name' => 'password',
                        'contents' => $password,
                    ],
                ]
            ]);

            // 1) Cuerpo crudo
            $body = (string) $response->getBody();
            $bodyTrim = trim($body);

            // 2) Intentamos JSON por si acaso
            $json = json_decode($bodyTrim, true);

            $token = null;

            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                // Caso respuesta JSON
                $token = $json['accessToken'] ?? $json['token'] ?? null;
            } else {
                // Caso respuesta = token plano (lo que te está pasando)
                $token = $bodyTrim;
            }

            if (!$token) {
                \Log::error('obtainDatadisTokenInvoice: respuesta sin token válida', [
                    'raw_body' => $bodyTrim,
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => 'No se pudo obtener el token de Datadis (respuesta inválida)',
                    'raw' => $bodyTrim,
                ], 500);
            }

            // Devolvemos token
            return response()->json([
                'ok' => true,
                'token' => $token,
                'type' => 'Bearer',
                // Esto ya no lo sabemos si viene plano, así que inventamos un valor razonable
                'expires' => 900,
            ], 200);

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errorMsg = $e->getMessage();
            $errorRes = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null;

            \Log::error('Error al obtener token de Datadis (Invoice)', [
                'message' => $errorMsg,
                'response' => $errorRes,
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener el token de Datadis',
                'error' => $errorMsg,
                'body' => $errorRes,
            ], 500);
        }
    }

    protected function fetchSipsForInvoice(string $CUPS, Carbon $invoiceStart, Carbon $invoiceEnd): ?array
    {
        $boundaryPoint = [
            ["boundary" => "0F", "checked" => false],
            ["boundary" => "1P", "checked" => false],
            ["boundary" => "",   "checked" => false],
        ];

        $data = [];
        $ibertrola = str_starts_with($CUPS, 'ES0021');

        while (array_filter($boundaryPoint, fn($b) => !$b['checked'])) {
            $boundary = null;
            foreach ($boundaryPoint as $index => $point) {
                if (!$point['checked']) {
                    $boundaryPoint[$index]['checked'] = true;
                    $boundary = $CUPS . $point['boundary'];
                    break;
                }
            }

            $response = Http::withHeaders([
                'x-api-key' => '$2y$10$bcT1Ukm4V/6/z5GvitZv0unu8I91mpixuwUO6z5CyEGev9DDDY28W'
            ])->get('https://datapi.psgestion.es/cups/data/consumos', [
                'id' => 4,
                'valores' => json_encode([
                    'cups'         => $boundary,
                    'tipoContrato' => 'L'
                ])
            ])->body();

            $data = json_decode($response, true);
            if (!empty($data['consumos'])) break;
        }

        if (empty($data['consumos'])) {
            return null;
        }

        // Filtrar por el mes de la factura
        $filtered = array_filter($data['consumos'], function ($consumo) use ($invoiceStart, $invoiceEnd) {
            $from = Carbon::parse($consumo['fechaInicioMesConsumo']);
            $to   = Carbon::parse($consumo['fechaFinMesConsumo']);

            return $from->month === $invoiceStart->month
                && $from->year  === $invoiceStart->year
                && $to->month   === $invoiceEnd->month
                && $to->year    === $invoiceEnd->year;
        });

        if (empty($filtered)) {
            // Si no hay del mismo mes, coge el más cercano
            $filtered = collect($data['consumos'])
                ->sortBy(fn($c) => abs(Carbon::parse($c['fechaInicioMesConsumo'])->diffInDays($invoiceStart)))
                ->take(1)
                ->values()
                ->all();
        }

        $consumosProcesados = [];

        $hasValidData = false;

        foreach ($consumosProcesados as $c) {
            foreach ($c['periods'] as $value) {
                if ($value > 0) {
                    $hasValidData = true;
                    break 2;
                }
            }
        }



        foreach ($filtered as $index => $c) {
            $to   = Carbon::parse($c['fechaFinMesConsumo']);
            $from = Carbon::parse($c['fechaInicioMesConsumo']);
            $periods = [];
            $total   = 0;

            for ($i = 1; $i <= 6; $i++) {
                $valor = $c["consumoEnergiaActivaEnWhP$i"] ?? 0;
                $valor = $ibertrola ? $valor : $valor / 1000; // otros en kWh
                $periods["p$i"] = round($valor, 3);
                $total += $valor;
            }

            $consumosProcesados[$index] = [
                "startDate" => $from->format("Y-m-d"),
                "endDate"   => $to->format("Y-m-d"),
                "periods"   => $periods,
                "total"     => round($total, 3)
            ];
        }

        // Potencias contratadas
        $potencias = [];
        for ($i = 1; $i <= 6; $i++) {
            $potencias["p$i"] = ($data[sprintf('potenciasContratadasEnWP%s', $i)] ?? 0) / 1000;
        }

        $tarifa = $data['tarifaATR'] ?? null;

        return [
            "tarifa"               => $tarifa,
            "comercializadora"     => $data['nombreComercializadora'] ?? null,
            "potencias_contratadas"=> $potencias,
            "consumos"             => array_values($consumosProcesados),
        ];
    }

    protected function aggregateDatadisByPeriods($consumption, Carbon $start, Carbon $end, string $codeFare): array
    {
        if ($consumption instanceof \Illuminate\Support\Collection) {
            $consumption = $consumption->all();
        }

        $holidayDates = [
            '2026/01/01', // Año Nuevo
            '2026/01/06', // Reyes Magos
            '2026/04/03', // Viernes Santo
            '2026/05/01', // Día del Trabajo
            '2026/08/15', // Asunción
            '2026/10/12', // Hispanidad
            '2026/11/01', // Todos los Santos
            '2026/12/06', // Constitución
            '2026/12/08', // Inmaculada
            '2026/12/25', // Navidad
            '2025/12/25','2025/12/08','2025/08/15','2025/05/01','2025/01/01',
            '2024/12/25','2024/12/06','2024/11/01','2024/10/12','2024/08/15','2024/05/01','2024/01/01',
            '2023/12/25','2023/12/08','2023/12/06','2023/11/01','2023/10/12','2023/08/15','2023/05/01',
        ];

        $fee2T = [
            'intervals' => [
                'low'  => [1,2,3,4,5,6,7,8],
                'mid'  => [9,10,15,16,17,18,23,24],
                'high' => [11,12,13,14,19,20,21,22],
            ],
        ];

        $fee3T = [
            'intervals' => [
                'low'  => [1,2,3,4,5,6,7,8],
                'mid'  => [9,15,16,17,18,23,24],
                'high' => [10,11,12,13,14,19,20,21,22],
            ],
            'months' => [
                ['mid' => 'p2','high' => 'p1'],
                ['mid' => 'p2','high' => 'p1'],
                ['mid' => 'p3','high' => 'p2'],
                ['mid' => 'p5','high' => 'p4'],
                ['mid' => 'p5','high' => 'p4'],
                ['mid' => 'p4','high' => 'p3'],
                ['mid' => 'p2','high' => 'p1'],
                ['mid' => 'p4','high' => 'p3'],
                ['mid' => 'p4','high' => 'p3'],
                ['mid' => 'p5','high' => 'p4'],
                ['mid' => 'p3','high' => 'p2'],
                ['mid' => 'p2','high' => 'p1'],
            ],
        ];

        $totals = ['p1'=>0.0,'p2'=>0.0,'p3'=>0.0,'p4'=>0.0,'p5'=>0.0,'p6'=>0.0];

        $start = $start->copy()->startOfDay();
        $end   = $end->copy()->endOfDay(); // excluye el día de lectura final

        foreach ($consumption as $row) {
            $dateStr = is_array($row) ? ($row['date'] ?? null) : ($row->date ?? null);
            $timeStr = is_array($row) ? ($row['time'] ?? null) : ($row->time ?? null);
            $kwh     = is_array($row)
                ? (isset($row['consumptionKWh']) ? (float)$row['consumptionKWh'] : 0.0)
                : (isset($row->consumptionKWh)   ? (float)$row->consumptionKWh   : 0.0);

            if (!$dateStr || !$timeStr || $kwh <= 0) continue;

            if (preg_match('/^\d{1,2}$/', $timeStr)) {
                $timeStr = str_pad($timeStr, 2, '0', STR_PAD_LEFT) . ':00';
            }

            // 24:00 = última hora del día, Carbon no puede parsearla directamente
            $is2400    = ($timeStr === '24:00');
            $parseTime = $is2400 ? '23:59' : $timeStr;

            try {
                $dt = Carbon::createFromFormat('Y/m/d H:i', "{$dateStr} {$parseTime}");
            } catch (\Exception $e) {
                continue;
            }

            if ($dt->lt($start) || $dt->gt($end)) continue;

            $isWeekend = $dt->isWeekend();
            $isHoliday = in_array($dt->format('Y/m/d'), $holidayDates, true);

            // 24:00 es la hora 24 → pertenece a 'mid' en 2T y 3T
            $hourInt = $is2400 ? 24 : (int) substr($timeStr, 0, 2);

            if ($codeFare === '2T') {
                if ($isWeekend || $isHoliday) {
                    $totals['p3'] += $kwh;
                } else {
                    if (in_array($hourInt, $fee2T['intervals']['low'], true)) {
                        $totals['p3'] += $kwh;
                    } elseif (in_array($hourInt, $fee2T['intervals']['mid'], true)) {
                        $totals['p2'] += $kwh;
                    } elseif (in_array($hourInt, $fee2T['intervals']['high'], true)) {
                        $totals['p1'] += $kwh;
                    }
                }
            } else {
                $monthIndex  = $dt->month - 1;
                $monthConfig = $fee3T['months'][$monthIndex] ?? ['mid' => 'p4','high' => 'p3'];

                if ($isWeekend || $isHoliday) {
                    $totals['p6'] += $kwh;
                } else {
                    if (in_array($hourInt, $fee3T['intervals']['low'], true)) {
                        $totals['p6'] += $kwh;
                    } elseif (in_array($hourInt, $fee3T['intervals']['mid'], true)) {
                        $p = $monthConfig['mid'] ?? 'p4';
                        $totals[$p] += $kwh;
                    } elseif (in_array($hourInt, $fee3T['intervals']['high'], true)) {
                        $p = $monthConfig['high'] ?? 'p3';
                        $totals[$p] += $kwh;
                    }
                }
            }
        }

        return [
            'periods'   => $totals,
            'total_kwh' => array_sum($totals),
        ];
    }

    private static function getEffectiveDate($order): Carbon
    {
        // Si es un modelo Eloquent → a array
        if (is_object($order)) {
            $order = $order->toArray();
        }

        // 1️⃣ transferDate
        if (!empty($order['transferDate'])) {
            return Carbon::createFromFormat('d/m/y', $order['transferDate']);
        }

        // 2️⃣ processingDate
        if (!empty($order['processingDate'])) {
            return Carbon::parse($order['processingDate']);
        }

        // 3️⃣ activationDate
        if (!empty($order['activationDate'])) {
            return Carbon::parse($order['activationDate']);
        }

        // 4️⃣ fallback
        return Carbon::createFromTimestamp(0);
    }
}


