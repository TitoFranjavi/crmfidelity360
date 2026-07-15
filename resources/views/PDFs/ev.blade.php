@php
    $fmt = function ($value) {
        if ($value === '' || $value === null) return '';
        return number_format((float) $value, 2, ',', '.') . ' €';
    };

    $num = function ($value) {
        if ($value === '' || $value === null) return 0;
        return (float) str_replace(',', '.', $value);
    };

    $clientName      = $opportunity['name'] ?? $order['name'] ?? '';
    $clientPhone     = $opportunity['phone'] ?? '';
    $clientEmail     = $opportunity['email'] ?? '';
    $clientCif       = $opportunity['CIF'] ?? '';
    $address         = $order['direc'] ?? '';
    $zip             = $order['zip'] ?? '';
    $town            = $order['town'] ?? '';
    $province        = $order['province'] ?? '';
    $contractedPower = $budget['contractedPower']
        ?? $order['contractedPower']
        ?? $order['potenciaContratada']
        ?? $order['power']
        ?? '';
    $vatPercentage = $num($budget['chargerVatPercentage'] ?? 21);
    $vatRate       = $vatPercentage / 100;

    // ── Totales pre-calculados del frontend (ya tienen márgenes correctos) ──
    // CRM usa:    budget.totals.laborSubtotal / certificateSubtotal / modulationCableSubtotal / surplusOptimizationSubtotal
    // Web pública usa: budget.totals.laborSubtotal / certSubtotal   / tubeSubtotal            / surplusSubtotal
    $preTotals = !empty($budget['totals']) ? $budget['totals'] : null;

    $preCharger    = $preTotals ? $num($preTotals['chargerSubtotal'] ?? 0) : null;
    $preLabor      = $preTotals ? $num($preTotals['laborSubtotal'] ?? 0) : null;
    $preCable      = $preTotals ? $num($preTotals['cableSubtotal'] ?? 0) : null;
    $preTube       = $preTotals ? $num($preTotals['modulationCableSubtotal'] ?? $preTotals['tubeSubtotal'] ?? 0) : null;
    $preSurplus    = $preTotals ? $num($preTotals['surplusOptimizationSubtotal'] ?? $preTotals['surplusSubtotal'] ?? 0) : null;
    $preCert       = $preTotals ? $num($preTotals['certificateSubtotal'] ?? $preTotals['certSubtotal'] ?? 0) : null;
    $preCivilWork  = $preTotals ? $num($preTotals['civilWorkSubtotal'] ?? 0) : null;

    // ── Índice de budgetLines por key (para opcionales) ──
    $budgetLinesMap = [];
    foreach (($budget['budgetLines'] ?? []) as $bl) {
        if (!empty($bl['key'])) $budgetLinesMap[$bl['key']] = $bl;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CONSTRUCCIÓN DE LÍNEAS
    // Si existen preTotals usamos sus importes directamente (sin re-aplicar margen).
    // Si no, aplicamos: cargador × 1.40, resto × 1.10.
    // ─────────────────────────────────────────────────────────────────────────
    $chargerMargin = 1.40;
    $otherMargin   = 1.10;

    $lines = [];

    // ── CARGADOR ──
    $chargerPrice    = $num($budget['chargerInstallationPrice'] ?? 0);
    $chargerDiscount = $num($budget['chargerInstallationDiscount'] ?? 0);
    $chargerBase     = $chargerPrice * (1 - ($chargerDiscount / 100));

    if ($chargerPrice > 0 || !empty($budget['chargerModel']) || !empty($budget['chargerPower'])) {
        $chargerAmount = $preCharger !== null ? $preCharger : round($chargerBase * $chargerMargin, 2);
        $chargerUnitPrice = $chargerBase > 0 ? round($chargerAmount / max($chargerBase > 0 ? 1 : 1, 1), 2) : $chargerAmount;
        $lines[] = [
            'description' => 'Presupuesto de Cargador + Instalación. INCLUYE:<br>' .
                e($budget['chargerModel'] ?? '') . ' ' . e($budget['chargerPower'] ?? '') . '<br>' .
                'Modulador automático de potencia.<br>' .
                'Protecciones según normativa actual ICT-BT-52<br>' .
                'Manguera 5m',
            'qty'      => 1,
            'price'    => $chargerAmount,   // precio efectivo con margen
            'discount' => $chargerDiscount,
            'amount'   => $chargerAmount,
        ];
    }

    // ── MANO DE OBRA ──
    $laborPrice = $num($budget['laborPrice'] ?? 0);
    if ($laborPrice > 0) {
        $laborAmount = $preLabor !== null ? $preLabor : round($laborPrice * $otherMargin, 2);
        $lines[] = [
            'description' => 'Mano de obra, desplazamiento y pequeño material',
            'qty'      => 1,
            'price'    => $laborAmount,
            'discount' => 0,
            'amount'   => $laborAmount,
        ];
    }

    // ── CABLEADO ──
    $cableMeters = $num($budget['cableMeters'] ?? 0);
    $cablePrice  = $num($budget['cablePricePerMeter'] ?? 0) * 4;
    if ($cableMeters > 0 || $cablePrice > 0) {
        $cableAmount    = $preCable !== null ? $preCable : round($cableMeters * $cablePrice * $otherMargin, 2);
        $cableUnitPrice = $cableMeters > 0 ? round($cableAmount / $cableMeters, 4) : round($cablePrice * $otherMargin, 2);
        $lines[] = [
            'description' => 'Suministro e instalación de cableado sección conveniente incluido entubamiento de ser necesario entre la ubicación del cargador y la conexión.',
            'qty'      => $cableMeters,
            'price'    => $cableUnitPrice,
            'discount' => 0,
            'amount'   => $cableAmount,
        ];
    }

    // ── MANGUERA ──
    $modulationMeters = $num($budget['modulationCableMeters'] ?? 0);
    $modulationPrice  = $num($budget['modulationCablePricePerMeter'] ?? 0);
    if ($modulationMeters > 0 || $modulationPrice > 0) {
        $tubeAmount    = $preTube !== null ? $preTube : round($modulationMeters * $modulationPrice * $otherMargin, 2);
        $tubeUnitPrice = $modulationMeters > 0 ? round($tubeAmount / $modulationMeters, 4) : round($modulationPrice * $otherMargin, 2);
        $lines[] = [
            'description' => 'Suministro e instalación de manguera apantallada para la modulación de la potencia del cargador en función del consumo de la vivienda.',
            'qty'      => $modulationMeters,
            'price'    => $tubeUnitPrice,
            'discount' => 0,
            'amount'   => $tubeAmount,
        ];
    }

    // ── EXCEDENTES FOTOVOLTAICOS ──
    $surplusPrice = $num($budget['surplusOptimizationPrice'] ?? 95);
    if (($budget['hasPhotovoltaic'] ?? false) === true && ($budget['wantsSurplusOptimization'] ?? false) === true && $surplusPrice > 0) {
        $surplusAmount = $preSurplus !== null ? $preSurplus : round($surplusPrice * $otherMargin, 2);
        $lines[] = [
            'description' => 'Optimización de excedentes fotovoltaicos<br>' .
                'Configuración para que el punto de recarga pueda aprovechar los excedentes de la instalación solar fotovoltaica.',
            'qty'      => 1,
            'price'    => $surplusAmount,
            'discount' => 0,
            'amount'   => $surplusAmount,
        ];
    }

    // ── BOLETÍN / CERTIFICADO ──
    $certificatePrice = $num($budget['certificatePrice'] ?? 0);
    if ($certificatePrice <= 0 && (
        $chargerPrice > 0 || $laborPrice > 0 || $cableMeters > 0 || $modulationMeters > 0 ||
        !empty($budget['chargerModel']) || !empty($budget['chargerPower'])
    )) {
        $certificatePrice = 100;
    }
    if ($certificatePrice > 0) {
        $certAmount = $preCert !== null ? $preCert : round($certificatePrice * $otherMargin, 2);
        $lines[] = [
            'description' => 'Boletín / certificado instalación eléctrica y legalización',
            'qty'      => 1,
            'price'    => $certAmount,
            'discount' => 0,
            'amount'   => $certAmount,
        ];
    }

    // ── OBRA CIVIL ──
    $civilWorkPrice = $num($budget['civilWorkPrice'] ?? 0);
    if (($budget['needsCivilWork'] ?? false) === true) {
        $civilAmount = $preCivilWork !== null ? $preCivilWork : round($civilWorkPrice * $otherMargin, 2);
        $lines[] = [
            'description' => 'Obra civil necesaria' . (!empty($budget['civilWorkDescription']) ? '<br>' . e($budget['civilWorkDescription']) : ''),
            'qty'      => 1,
            'price'    => $civilAmount,
            'discount' => 0,
            'amount'   => $civilAmount,
        ];
    }

    // ── OPCIONALES ──
    $optionalLines = [];
    foreach (($budget['optionalItems'] ?? []) as $i => $optional) {
        $qty      = $num($optional['quantity'] ?? 0);
        $price    = $num($optional['price'] ?? 0);
        $discount = $num($optional['discount'] ?? 0);
        $baseAmt  = $qty * $price * (1 - ($discount / 100));

        $key    = 'optional_' . ($i + 1);
        $blLine = $budgetLinesMap[$key] ?? null;
        $amount = $blLine ? $num($blLine['amount'] ?? 0) : round($baseAmt * $otherMargin, 2);
        $unitP  = $qty > 0 ? round($amount / $qty, 4) : round($price * $otherMargin, 2);

        $optionalLines[] = [
            'description' => e($optional['name'] ?? '') . (!empty($optional['description']) ? '<br>' . e($optional['description']) : ''),
            'qty'      => $qty,
            'price'    => $unitP,
            'discount' => $discount,
            'amount'   => $amount,
        ];
    }

    // ── TOTALES ──
    // Usamos los del frontend si están disponibles (más precisos); sino los recalculamos.
    if ($preTotals) {
        $subtotal = $num($preTotals['subtotal'] ?? 0);
    } else {
        $subtotal = round(collect($lines)->sum('amount') + collect($optionalLines)->sum('amount'), 2);
    }

   $globalDiscount = $num($budget['globalDiscount'] ?? ($preTotals['globalDiscount'] ?? 0));

    $discountAmount = ($preTotals && isset($preTotals['discountAmount']) && $num($preTotals['discountAmount']) > 0)
        ? $num($preTotals['discountAmount'])
        : ($globalDiscount > 0 ? round($subtotal * ($globalDiscount / 100), 2) : 0);

    $subtotalAfterDiscount = $subtotal - $discountAmount;

    // IVA y total siempre sobre la base con descuento ya aplicado
    if ($preTotals) {
        $iva   = $num($preTotals['vat']   ?? round($subtotalAfterDiscount * $vatRate, 2));
        $total = $num($preTotals['total'] ?? round($subtotalAfterDiscount + $iva, 2));
    } else {
        $iva   = round($subtotalAfterDiscount * $vatRate, 2);
        $total = round($subtotalAfterDiscount + $iva, 2);
    }
    // ── Importe a cobrar ahora (lo decide el controlador; por defecto total = 100%) ──
    // El controlador pasa la fracción (0.4 = 40%, 0.6 = 60%, 1.0 = 100%) — se convierte aquí a entero.
    $depositPercentage = isset($depositPercentage) ? (int) round((float) $depositPercentage * 100) : 100;
    if ($depositPercentage < 1 || $depositPercentage > 100) $depositPercentage = 100;
    $isPartialPayment  = $depositPercentage < 100;
    $depositNow        = $isPartialPayment ? round($total * ($depositPercentage / 100), 2) : $total;
    $remainingPayment  = round($total - $depositNow, 2);
    $depositSuffix     = $isPartialPayment ? ' (' . $depositPercentage . '%)' : '';
    $transferNowLabel  = $isPartialPayment ? 'Importe a transferir ahora:' : 'Importe a transferir:';

    // ── Financiación seleccionada desde el formulario público ──
    $financing = $budget['financing'] ?? [];
    $isFinancingEnabled = !empty($financing) && (($financing['enabled'] ?? false) === true || ($financing['enabled'] ?? '') === 'true');

    $financingAmount      = $isFinancingEnabled ? $num($financing['amount'] ?? $total) : 0;
    $financingMonths      = $isFinancingEnabled ? (int) $num($financing['months'] ?? 0) : 0;
    $financingCap         = $isFinancingEnabled ? $num($financing['cap'] ?? 0) : 0;
    $financingTin         = $isFinancingEnabled ? $num($financing['tin'] ?? 0) : 0;
    $financingCoefficient = $isFinancingEnabled ? $num($financing['coefficient'] ?? 0) : 0;
    $financingMonthlyFee  = $isFinancingEnabled ? $num($financing['monthlyFee'] ?? 0) : 0;

    if ($isFinancingEnabled && $financingMonthlyFee <= 0 && $financingAmount > 0 && $financingCoefficient > 0) {
        $financingMonthlyFee = round($financingAmount * $financingCoefficient, 2);
    }

    $financingTotalPaid = $isFinancingEnabled
        ? $num($financing['totalPaid'] ?? ($financingMonthlyFee * max($financingMonths, 1)))
        : 0;

    $financingCost = $isFinancingEnabled
        ? $num($financing['cost'] ?? ($financingTotalPaid - $financingAmount))
        : 0;

    $financingCapAmount = $isFinancingEnabled
        ? $num($financing['capAmount'] ?? ($financingAmount * ($financingCap / 100)))
        : 0;

    $bankAccountHolder = 'Segenet Control SL';
    $bankAccountIban   = 'ES09 2100 1962 3002 0024 9592';
    $bankAccountBic    = '';

    $budgetNumber = 'PRE' . now()->format('ymdHis');
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuesto cargador eléctrico</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; color: #222; background: #fff; }
        .page { width: 210mm; min-height: 297mm; position: relative; overflow: hidden; page-break-after: always; background: #fff; }
        .page:last-child { page-break-after: auto; }
        .cover-image { width: 210mm; height: 297mm; object-fit: cover; display: block; }
        .content-page { padding: 12mm 13mm 14mm 13mm; }
        .top-header { width: 100%; display: table; margin-bottom: 14px; }
        .top-left, .top-right { display: table-cell; vertical-align: top; }
        .top-left { width: 60%; }
        .top-right { width: 40%; text-align: right; }
        .budget-title { font-size: 18px; font-weight: 800; color: #0078c8; margin-bottom: 4px; }
        .budget-number { font-size: 13px; font-weight: 700; color: #444; }
        .date { font-size: 11px; color: #555; margin-top: 4px; }
        .logo { max-width: 110px; max-height: 55px; object-fit: contain; }
        .info-row { display: table; width: 100%; margin-bottom: 14px; }
        .info-box { display: table-cell; width: 50%; vertical-align: top; padding-right: 8px; }
        .info-box:last-child { padding-right: 0; padding-left: 8px; }
        .box { border: 1px solid #e4e4e4; border-radius: 8px; padding: 10px 12px; min-height: 92px; }
        .box-title { font-size: 11px; font-weight: 800; color: #00b83a; text-transform: uppercase; margin-bottom: 7px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .box-line { font-size: 11px; line-height: 1.45; color: #333; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 10.3px; }
        .table th { background: #0078c8; color: white; padding: 7px 6px; text-align: left; font-size: 9.5px; text-transform: uppercase; }
        .table td { padding: 7px 6px; border-bottom: 1px solid #e5e5e5; vertical-align: top; }
        .table tbody tr:nth-child(even) { background: #fafafa; }
        .description { width: 56%; line-height: 1.35; }
        .center { text-align: center; }
        .right { text-align: right; }
        .optional-separator { background: #f3f3f3; font-weight: 800; color: #444; text-transform: uppercase; font-size: 10px; }
        .totals-wrap { width: 100%; display: table; margin-top: 14px; }
        .notes { display: table-cell; width: 58%; vertical-align: top; padding-right: 15px; }
        .totals { display: table-cell; width: 42%; vertical-align: top; }
        .totals table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .totals td { padding: 6px 8px; border-bottom: 1px solid #e5e5e5; }
        .totals .label { font-weight: 700; color: #333; }
        .totals .amount { text-align: right; font-weight: 700; }
        .totals .grand-total td { background: #00b83a; color: white; font-size: 14px; font-weight: 800; border-bottom: 0; }
        .section-small-title { font-size: 11px; font-weight: 800; text-transform: uppercase; margin-bottom: 6px; color: #0078c8; }
        .note-text { font-size: 10.5px; line-height: 1.45; color: #444; }
        .footer { position: absolute; left: 13mm; right: 13mm; bottom: 8mm; font-size: 9.5px; color: #777; border-top: 1px solid #eee; padding-top: 6px; }
        .payment-box { margin-top: 14px; border: 1px solid #e4e4e4; border-radius: 8px; padding: 9px 11px; font-size: 10.5px; }
        .payment-title { font-weight: 800; margin-bottom: 4px; text-transform: uppercase; font-size: 10px; }
        .financing-box { margin-top: 9px; border: 1px solid #cfeee0; border-radius: 8px; background: #f2fbf6; overflow: hidden; }
        .financing-head { background: #00b83a; color: #fff; font-weight: 800; font-size: 10px; text-transform: uppercase; padding: 7px 9px; }
        .financing-table { width: 100%; border-collapse: collapse; font-size: 10.2px; }
        .financing-table td { padding: 6px 8px; border-bottom: 1px solid #dff3e8; }
        .financing-table tr:last-child td { border-bottom: 0; }
        .financing-table .label { color: #345; font-weight: 700; }
        .financing-table .amount { text-align: right; font-weight: 800; color: #111; }
        .financing-note { padding: 6px 8px 8px; font-size: 9.4px; line-height: 1.35; color: #566; }
        .features { display: table; width: 100%; margin-top: 12px; }
        .feature { display: table-cell; width: 33.33%; padding-right: 8px; }
        .feature:last-child { padding-right: 0; }
        .feature-card { border-radius: 8px; background: #f8f8f8; border-top: 3px solid #00b83a; padding: 8px; min-height: 58px; }
        .feature-title { font-size: 10px; font-weight: 800; margin-bottom: 4px; color: #0078c8; }
        .feature-text { font-size: 9.5px; line-height: 1.3; color: #555; }
    </style>
</head>
<body>

    {{-- Página 1: portada PNG --}}
    <section class="page">
        <img class="cover-image" src="{{ $coverBase64 }}" alt="Portada presupuesto cargador eléctrico">
    </section>

    {{-- Página 2: presupuesto --}}
    <section class="page content-page">

        <div class="top-header">
            <div class="top-left">
                <div class="budget-title">Presupuesto de instalación</div>
                <div class="budget-number">Punto de recarga para vehículo eléctrico</div>
                <div class="date">
                    Presupuesto: {{ $budgetNumber }}<br>
                    Fecha: {{ $currentDate }}
                </div>
            </div>
            <div class="top-right">
                @if (!empty($logoUrl))
                    <img class="logo" src="{{ $logoUrl }}" alt="Logo">
                @endif
            </div>
        </div>

        <div class="info-row">
            <div class="info-box">
                <div class="box">
                    <div class="box-title">Cliente</div>
                    <div class="box-line">
                        <strong>{{ $clientName }}</strong><br>
                        @if (!empty($clientCif)) CIF/NIF: {{ $clientCif }}<br>@endif
                        @if (!empty($address)) {{ $address }}<br>@endif
                        {{ $zip }} {{ $town }} {{ $province }}<br>
                        @if (!empty($clientPhone)) Telf: {{ $clientPhone }}@endif
                        @if (!empty($clientEmail)) Mail: {{ $clientEmail }}@endif
                    </div>
                </div>
            </div>
            <div class="info-box">
                <div class="box">
                    <div class="box-title">Dirección de instalación</div>
                    <div class="box-line">
                        <strong>{{ $clientName }}</strong><br>
                        {{ $address }}<br>
                        {{ $zip }} {{ $town }} {{ $province }}<br>
                        @if (!empty($budget['installationType'])) Tipo instalación: {{ $budget['installationType'] }}<br>@endif
                        Potencia contratada: {{ $contractedPower !== '' ? $contractedPower : '-' }}<br>
                        Fotovoltaica: {{ ($budget['hasPhotovoltaic'] ?? false) ? 'Sí' : 'No' }}<br>
                        Optimización excedentes: {{ (($budget['hasPhotovoltaic'] ?? false) && ($budget['wantsSurplusOptimization'] ?? false)) ? 'Sí' : 'No' }}
                    </div>
                </div>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th class="center">Cant.</th>
                    <th class="right">Precio</th>
                    <th class="right">% DTO.</th>
                    <th class="right">Importe</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($lines as $line)
                    <tr>
                        <td class="description">{!! $line['description'] !!}</td>
                        <td class="center">{{ number_format($line['qty'], 2, ',', '.') }}</td>
                        <td class="right">{{ $fmt($line['price']) }}</td>
                        <td class="right">{{ number_format($line['discount'], 2, ',', '.') }}</td>
                        <td class="right">{{ $fmt($line['amount']) }}</td>
                    </tr>
                @endforeach

                @if (!empty($optionalLines))
                    <tr><td colspan="5" class="optional-separator">Opcionales</td></tr>
                    @foreach ($optionalLines as $line)
                        <tr>
                            <td class="description">{!! $line['description'] !!}</td>
                            <td class="center">{{ number_format($line['qty'], 2, ',', '.') }}</td>
                            <td class="right">{{ $fmt($line['price']) }}</td>
                            <td class="right">{{ number_format($line['discount'], 2, ',', '.') }}</td>
                            <td class="right">{{ $fmt($line['amount']) }}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <div class="totals-wrap">
            <div class="notes">
                <div class="section-small-title">Observaciones</div>
                <div class="note-text">
                    @if (!empty($budget['installationNotes']))
                        {{ $budget['installationNotes'] }}<br><br>
                    @endif
                    Los metros calculados son una estimación. Una vez finalizada la instalación se facturarán los metros realmente utilizados. Siempre con un mínimo de 5 m de cableado.<br><br>
                    @if (($budget['hasPhotovoltaic'] ?? false) === true && ($budget['wantsSurplusOptimization'] ?? false) === true)
                        Se incluye optimización de excedentes para priorizar el aprovechamiento de la energía solar disponible en la recarga del vehículo.<br><br>
                    @endif
                    Oferta válida durante 30 días. Fecha instalación estimada: 15 días desde aceptación del presupuesto.
                </div>
                <div class="payment-box">
                    <div class="payment-title">Forma de pago</div>
                    @if (!empty($budget['paymentMethod']))
                        <div>{{ $budget['paymentMethod'] }}</div>
                    @endif

                    @if ($isFinancingEnabled)
                        <div class="financing-box">
                            <div class="financing-head">Opción de financiación seleccionada</div>
                            <table class="financing-table">
                                <tr>
                                    <td class="label">Importe financiado</td>
                                    <td class="amount">{{ $fmt($financingAmount) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Plazo</td>
                                    <td class="amount">{{ $financingMonths }} meses</td>
                                </tr>
                                <tr>
                                    <td class="label">Cuota mensual</td>
                                    <td class="amount">{{ $fmt($financingMonthlyFee) }} / mes</td>
                                </tr>
                                <tr>
                                    <td class="label">CAP / TIN</td>
                                    <td class="amount">{{ number_format($financingCap, 2, ',', '.') }}% / {{ number_format($financingTin, 2, ',', '.') }}%</td>
                                </tr>
                                <tr>
                                    <td class="label">Total a pagar financiado</td>
                                    <td class="amount">{{ $fmt($financingTotalPaid) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Coste financiación</td>
                                    <td class="amount">{{ $fmt($financingCost) }}</td>
                                </tr>
                            </table>
                            <div class="financing-note">
                                Cálculo orientativo realizado con el coeficiente de cuota indicado para el plazo elegido. La cuota incluye la CAP financiada.
                            </div>
                        </div>
                    @endif

                    @if (!$isFinancingEnabled)
                        <div style="margin-top:6px;">
                            @if ($isPartialPayment)
                                <div><strong>A pagar ahora ({{ $depositPercentage }}%):</strong> {{ $fmt($depositNow) }}</div>
                            @else
                                <div><strong>Importe a pagar:</strong> {{ $fmt($total) }}</div>
                            @endif
                        </div>
                        <div style="margin-top:8px;">
                            <strong>Pago por transferencia bancaria:</strong><br>
                            Titular: {{ $bankAccountHolder }}<br>
                            IBAN: {{ $bankAccountIban }}<br>
                            @if (!empty($bankAccountBic))BIC/SWIFT: {{ $bankAccountBic }}<br>@endif
                            Concepto: {{ $budgetNumber }}@if (!empty($clientName)) · {{ $clientName }}@endif<br>
                            {{ $transferNowLabel }} <strong>{{ $fmt($depositNow) }}</strong>{{ $depositSuffix }}
                        </div>
                        @if (!empty($stripePaymentUrl))
                            <div style="margin-top:8px;">
                                <strong>Pago online (tarjeta):</strong><br>
                                <a href="{{ $stripePaymentUrl }}" style="color:#0078c8;word-break:break-all;">Pagar {{ $fmt($depositNow) }}{{ $depositSuffix }}</a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
            <div class="totals">
                <table>
                    <tr>
                        <td class="label">Base imponible</td>
                        <td class="amount">{{ $fmt($subtotal) }}</td>
                    </tr>
                    @if ($globalDiscount > 0)
                    <tr>
                        <td class="label">Descuento {{ number_format($globalDiscount, 2, ',', '.') }}%</td>
                        <td class="amount" style="color:#c0392b">-{{ $fmt($discountAmount) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">IVA {{ number_format($vatPercentage, 0, ',', '.') }}%</td>
                        <td class="amount">{{ $fmt($iva) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td>Total</td>
                        <td class="amount">{{ $fmt($total) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="features">
            <div class="feature"><div class="feature-card"><div class="feature-title">Seguridad</div><div class="feature-text">Instalación con protecciones conforme a normativa.</div></div></div>
            <div class="feature"><div class="feature-card"><div class="feature-title">Comodidad</div><div class="feature-text">Recarga tu vehículo en casa de forma sencilla.</div></div></div>
            <div class="feature"><div class="feature-card"><div class="feature-title">Eficiencia</div><div class="feature-text">Optimización de potencia y posibilidad de excedentes.</div></div></div>
        </div>

        <div class="footer">
            Una vez personados los técnicos en el lugar, si el cliente desiste de realizar la instalación por cualquier causa, podrá aplicarse un coste de desplazamiento. Presupuesto generado automáticamente.
        </div>

    </section>
</body>
</html>