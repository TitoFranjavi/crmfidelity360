@php
    $colors = [
        'zocoEnergiaColor' => '#e40613',
        'asercordColor' => '#012C68',
        'tecumColor' => '#e9511c',
        'newpulsoColor' => '#263294',
        'koasolucionesColor' => '#192249',
        'grupoNazariColor' => '#ec602d',
        'ahorroYSolutionsColor' => '#000000',
        'valereConsultoresColor' => '#000000',
        'btvinstalacionesColor' => '#18297a',
        'vtcomColor' => '#0071c1',
        'ajasesoresColor' => '#002b45',
        'ahorrodirectColor' => '#ec1d23',
        'lumigasenergiaColor' => '#4ba11e',
        'assessoria30Color' => '#9edfb9',
        'iberelectricaColor' => '#2f4392',
        'vimelColor' => '#012C68',
        'localuzColor' => '#e40613',
        'loviluzColor' => '#d78c0c',
        'vivivanColor' => '#2d2e83',
        'wconsultoresColor' => '#ff9323',
        'doiveColor' => '#2a367e',
        'tecumconsultoresColor' => '#fa4d09',
        'tweliColor' => '#38b6ff',
        'aluzygasColor' => '#1ca33c',
        'viceasesoresColor' => '#0b324b',
        'valfryxColor' => '#ff7f2a',
        'gruposuperaColor' => '#ffd100',
        'energianorteColor' => '#5ea22c',
        'ceustradeColor' => '#002060',
        'efuturaColor' => '#03989e',
        'solbyColor' => '#f07e14',
        'fotonasesoresColor' => '#4268be',
        'fidelity360Color' => '#9929dd',
        'coliseumenergiaColor' => '#884794',
        'energiaprimenoroesteColor' => '#f8b334',
        'onexenergiaColor' => '#00ab6a',
        'barriozubietaColor' => '#272453',
        'enerwatiaColor' => '#a87eb0',
    ];

    if (!function_exists('adjustColorPdf2p')) {
        function adjustColorPdf2p($hex, $percent)
        {
            $hex = str_replace('#', '', $hex);

            if (strlen($hex) !== 6) {
                $hex = '000000';
            }

            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));

            if ($percent > 0) {
                $r = $r + (255 - $r) * $percent / 100;
                $g = $g + (255 - $g) * $percent / 100;
                $b = $b + (255 - $b) * $percent / 100;
            } else {
                $r = $r * (1 + $percent / 100);
                $g = $g * (1 + $percent / 100);
                $b = $b * (1 + $percent / 100);
            }

            return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
        }
    }

    $companyColorKey = $data['basicData']['enterprise']['color'] ?? ($color ?? 'fidelity360Color');
    $baseColor = $colors[$companyColorKey] ?? '#9929dd';

    $primary = $baseColor;
    $primaryDark = adjustColorPdf2p($baseColor, -35);
    $primaryDarker = adjustColorPdf2p($baseColor, -55);
    $primaryLight = adjustColorPdf2p($baseColor, 35);
    $primaryLighter = adjustColorPdf2p($baseColor, 60);
    $primarySoft = adjustColorPdf2p($baseColor, 90);
    $primaryUltraSoft = adjustColorPdf2p($baseColor, 96);
    $primaryBorder = adjustColorPdf2p($baseColor, 80);

    if ($baseColor === '#000000') {
        $primaryLight = '#666666';
        $primarySoft = '#f2f2f2';
        $primaryUltraSoft = '#fafafa';
        $primaryBorder = '#dddddd';
    }

    $powerAct = $data['currentSubtotal']['power']['total'] ?? 0;
    $powerOff = $data['offerSelected']['subTotal']['power']['total'] ?? 0;

    $energyAct = $data['currentSubtotal']['energy']['total'] ?? 0;
    $energyOff = $data['offerSelected']['subTotal']['energy']['total'] ?? 0;

    $electricTaxAct = $data['currentSubtotal']['taxes']['electricTax'] ?? 0;
    $electricTaxOff = $data['offerSelected']['subTotal']['taxes']['electricTax'] ?? 0;

    $socialBonusAct = $data['currentSubtotal']['taxes']['socialBonus'] ?? 0;
    $socialBonusOff = $data['offerSelected']['subTotal']['taxes']['socialBonus'] ?? 0;

    $conceptsAct = $data['currentSubtotal']['otherConcepts'] ?? 0;
    $conceptsOff = $data['offerSelected']['subTotal']['otherConcepts'] ?? 0;

    $meterAct = $data['currentSubtotal']['taxes']['meterDevice'] ?? 0;
    $meterOff = $data['offerSelected']['subTotal']['taxes']['meterDevice'] ?? 0;

    $ivaAct = $data['currentSubtotal']['taxes']['iva'] ?? (($data['currentTotal'] ?? 0) - (($data['currentSubtotal']['total'] ?? 0)));
    $ivaOff = $data['offerSelected']['subTotal']['taxes']['iva'] ?? (($data['offerSelected']['total'] ?? 0) - (($data['offerSelected']['subTotal']['total'] ?? 0)));

    $currentSubtotalNoIva =
        ($powerAct ?? 0) +
        ($energyAct ?? 0) +
        ($electricTaxAct ?? 0) +
        ($socialBonusAct ?? 0) +
        ($conceptsAct ?? 0) +
        ($meterAct ?? 0);

    $offerSubtotalNoIva =
        ($powerOff ?? 0) +
        ($energyOff ?? 0) +
        ($electricTaxOff ?? 0) +
        ($socialBonusOff ?? 0) +
        ($conceptsOff ?? 0) +
        ($meterOff ?? 0);

    $consumptions = array_map(function ($v) {
        if ($v === null || $v === '') return 0;
        return (float) str_replace(',', '.', $v);
    }, $data['cupsData']['consumption'] ?? []);

    $powers = array_map(function ($v) {
        if ($v === null || $v === '') return 0;
        return (float) str_replace(',', '.', $v);
    }, $data['cupsData']['power'] ?? []);

    $currentPowerPrices = $data['prices']['power'] ?? [];

    $offerPowerBase = $data['offerSelected']['prices']['power']
        ?? $data['offer']['power']
        ?? [];

    $offerPowerPrices = array_map(function ($price, $i) use ($data) {
        $fee = isset($data['offerFees']['power'][$i]) ? (float) str_replace(',', '.', $data['offerFees']['power'][$i]) / 30 : 0;
        return ($price !== null && $price !== '') ? (float) str_replace(',', '.', $price) + $fee : $price;
    }, $offerPowerBase ?? [], array_keys($offerPowerBase ?? []));

    $currentEnergyPrices = $data['prices']['energy'] ?? [];

    $offerEnergyBase = $data['offerSelected']['prices']['energy']
        ?? $data['offerSelected']['prices']['consumption']
        ?? $data['offer']['energy']
        ?? [];

    $offerEnergyPrices = array_map(function ($price, $i) use ($data) {
        $fee = isset($data['offerFees']['energy'][$i]) ? (float) str_replace(',', '.', $data['offerFees']['energy'][$i]) / 1000 : 0;
        return ($price !== null && $price !== '') ? (float) str_replace(',', '.', $price) + $fee : $price;
    }, $offerEnergyBase ?? [], array_keys($offerEnergyBase ?? []));

    $totalConsumption = array_sum($consumptions);

    $currentTotalPdf = (float) ($data['currentTotal'] ?? 0);
    $offerTotalPdf = (float) ($data['offerSelected']['total'] ?? 0);

    $saveAmountPdf = (float) (
        $data['offerSelected']['save']
        ?? $data['offerSelected']['saveAmount']
        ?? ($currentTotalPdf - $offerTotalPdf)
    );

    $savePercentRaw = (float) ($data['offerSelected']['savePercent'] ?? 0);
    $savePercentPdf = $savePercentRaw < 1 ? $savePercentRaw * 100 : $savePercentRaw;

    if ($savePercentPdf <= 0 && $currentTotalPdf > 0) {
        $savePercentPdf = ($saveAmountPdf / $currentTotalPdf) * 100;
    }

    $footerLogo = $logoFooter ?? $logoFooterUrl ?? $logoUrl ?? null;
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Comparativa Fidelity360</title>

    <style>
        :root {
            --primary: {{ $primary }};
            --primary-dark: {{ $primaryDark }};
            --primary-darker: {{ $primaryDarker }};
            --primary-light: {{ $primaryLight }};
            --primary-lighter: {{ $primaryLighter }};
            --primary-soft: {{ $primarySoft }};
            --primary-ultra-soft: {{ $primaryUltraSoft }};
            --primary-border: {{ $primaryBorder }};
            --text: #2d2d2d;
            --text-soft: #6b6b6b;
            --success: #29a458;
            --success-soft: #e8f2ed;
        }

        @page {
            margin: 0;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: 'Montserrat', Arial, sans-serif;
            color: var(--text);
            font-size: 12px;
        }

        .document {
            width: 210mm;
            margin: 0 auto;
            background: #ffffff;
        }

        .page {
            width: 210mm;
            height: 297mm;
            position: relative;
            overflow: hidden;
            page-break-after: always;
            background: #ffffff;
        }

        .page:last-of-type {
            page-break-after: auto;
        }

        .top-bar,
        .bottom-bar {
            position: absolute;
            left: 0;
            right: 0;
            height: 7px;
            z-index: 10;
        }

        .top-bar {
            top: 0;
            background: linear-gradient(90deg, var(--primary-dark), var(--primary), var(--primary-light));
        }

        .bottom-bar {
            bottom: 0;
            background: var(--primary);
        }

        .page-inner {
            height: 100%;
            padding: 24px 24px 88px;
            position: relative;
        }

        .header-secondary {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 14px;
            margin-bottom: 8px;
        }

        .logo-wrap {
            width: 132px;
            display: flex;
            align-items: flex-start;
        }

        .logo-image {
            width: 92px;
            height: auto;
            display: block;
        }

        .secondary-title {
            text-align: right;
        }

        .secondary-title h2 {
            margin: 0;
            font-size: 25px;
            line-height: 1.05;
            font-weight: 900;
            color: var(--primary-dark);
        }

        .secondary-title .sub {
            margin-top: 5px;
            font-size: 14px;
            letter-spacing: 1.8px;
            font-weight: 800;
            color: var(--primary-light);
            text-transform: uppercase;
        }

        .soft-divider {
            height: 3px;
            background: var(--primary-border);
            margin: 8px 0 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .info-card {
            border: 2px solid var(--primary-border);
            border-radius: 12px;
            padding: 12px 18px;
            min-height: 126px;
            background: transparent;
            page-break-inside: avoid;
        }

        .info-title,
        .section-caption .txt,
        .section-line .label,
        .chart-title .txt {
            color: var(--primary-light);
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .info-title {
            font-size: 11px;
            margin-bottom: 12px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 0;
            font-size: 12px;
            color: #4a4660;
            vertical-align: top;
        }

        .info-table td:first-child {
            width: 88px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .marketer-inline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .marketer-logo {
            max-width: 70px;
            max-height: 22px;
            width: auto;
            height: auto;
            object-fit: contain;
            display: inline-block;
            vertical-align: middle;
        }

        .section-caption,
        .section-line,
        .chart-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-caption {
            margin: 10px 0 8px;
        }

        .section-caption.section-spaced {
            margin-top: 12px;
        }

        .section-caption .txt,
        .chart-title .txt {
            font-size: 12px;
        }

        .section-caption .rule,
        .chart-title .rule,
        .section-line .line {
            height: 2px;
            background: var(--primary-light);
            flex: 1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .compare-table {
            width: 100%;
            margin-bottom: 6px;
            page-break-inside: avoid;
        }

        .compare-table thead th {
            color: #ffffff;
            font-size: 8px;
            padding: 4px 5px;
            font-weight: 800;
            text-align: center;
        }

        .compare-table thead th:nth-child(1) { background: var(--primary-darker); }
        .compare-table thead th:nth-child(2) { background: var(--primary); }
        .compare-table thead th:nth-child(3) { background: var(--primary); }
        .compare-table thead th:nth-child(4) { background: var(--primary); }
        .compare-table thead th:nth-child(5) { background: var(--primary-dark); }
        .compare-table thead th:nth-child(6) { background: var(--primary-light); }

        .compare-table tbody td,
        .compare-table tfoot td {
            font-size: 9.6px;
            padding: 4px 4px;
            text-align: center;
            border-bottom: 1px solid var(--primary-border);
            color: #35314c;
        }

        .compare-table tbody tr:nth-child(odd) td,
        .compare-table tfoot tr:nth-child(odd) td {
            background: var(--primary-ultra-soft);
        }

        .compare-table tbody tr:nth-child(even) td,
        .compare-table tfoot tr:nth-child(even) td {
            background: var(--primary-soft);
        }

        .compare-table tbody td:first-child {
            font-weight: 900;
            color: var(--primary-dark);
            background: var(--primary-soft) !important;
        }

        .compare-table tfoot td {
            border-top: 1px solid var(--primary-border);
            font-weight: 700;
        }

        .compare-table tfoot tr:last-child td {
            font-weight: 900;
        }

        .totals-table thead th:nth-child(1) { background: var(--primary-darker); }
        .totals-table thead th:nth-child(2) { background: var(--primary); }
        .totals-table thead th:nth-child(3) { background: var(--primary-light); }

        .totals-table tbody td:first-child {
            font-weight: 800;
            color: var(--primary-dark);
            background: var(--primary-soft) !important;
        }

        .totals-table tbody tr:last-child td {
            font-size: 16px;
            font-weight: 900;
            color: var(--primary-dark);
        }

        .totals-table tbody tr:last-child td:last-child {
            color: var(--primary);
        }

        .section-line {
            margin: 12px 0 10px;
        }

        .section-line .label {
            font-size: 13px;
        }

        .resume-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-top: 8px;
        }

        .resume-card {
            border-radius: 12px;
            padding: 16px 18px 13px;
            text-align: center;
            background: #efeff3;
            min-height: 88px;
        }

        .resume-card.purple {
            background: var(--primary-soft);
        }

        .resume-card.green {
            background: var(--success-soft);
        }

        .resume-card .k {
            color: #8f88a5;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.7px;
            text-transform: uppercase;
        }

        .resume-card .v {
            color: var(--primary-dark);
            font-size: 25px;
            font-weight: 900;
            margin-top: 4px;
            line-height: 1.05;
        }

        .resume-card.green .k,
        .resume-card.green .v {
            color: var(--success);
        }

        .resume-card .s {
            margin-top: 4px;
            font-size: 10px;
            color: #8f88a5;
            font-weight: 700;
        }

        .resume-card.green .s {
            color: #59b47b;
            font-weight: 800;
        }

        .analysis-page .page-inner {
            padding-bottom: 96px;
        }

        .analysis-page .header-secondary {
            margin-bottom: 18px;
        }

        .analysis-page .summary-section {
            margin-top: 8px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .card {
            border: 2px solid var(--primary-border);
            border-radius: 12px;
            background: transparent;
            padding: 14px 14px 12px;
            min-height: 86px;
            page-break-inside: avoid;
        }

        .card.green {
            border-color: #32a95f;
            background: #edf8f1;
            min-height: 118px;
        }

        .card-title {
            font-size: 11px;
            letter-spacing: 2px;
            color: var(--primary-light);
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .card.green .card-title {
            color: #22a055;
            font-size: 14px;
            letter-spacing: 3px;
        }

        .card-body {
            font-size: 12px;
            line-height: 1.45;
            color: #504c68;
        }

        .card.green .saving {
            font-size: 16px;
            line-height: 1.55;
            color: #504c68;
            font-weight: 600;
        }

        .card.green .saving strong {
            color: #287f46;
            font-weight: 900;
            font-size: 19px;
        }

        .big-date {
            color: var(--primary-dark);
            font-weight: 900;
            font-size: 18px;
            line-height: 1;
            margin: 2px 0 7px;
        }

        .small-sub {
            color: var(--primary-light);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .claim {
            margin: 20px 0 14px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .claim .line {
            height: 3px;
            background: var(--primary-light);
            flex: 1;
        }

        .claim .text {
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .services-box {
            margin-top: 4px;
            background: var(--primary-soft);
            border-radius: 14px;
            padding: 16px 18px 14px;
            text-align: center;
            color: #504c68;
        }

        .services-text {
            font-size: 12px;
            line-height: 1.45;
            margin-bottom: 12px;
        }

        .services-text strong {
            font-weight: 800;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 0;
            align-items: end;
        }

        .service-item {
            min-height: 70px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            border-right: 1px solid var(--primary-border);
            color: var(--primary-dark);
            font-size: 7.4px;
            font-weight: 800;
            text-align: center;
            padding: 0 4px;
        }

        .service-item:last-child {
            border-right: 0;
        }

        .service-item svg {
            width: 34px;
            height: 34px;
            margin-bottom: 6px;
            stroke: var(--primary-dark);
            stroke-width: 1.9;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .footer {
            position: absolute;
            left: 24px;
            right: 24px;
            bottom: 24px;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .advisor {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .footer-isotipo {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            object-fit: contain;
            display: block;
        }

        .advisor-name {
            color: var(--primary-dark);
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .advisor-role {
            color: #9a97aa;
            font-size: 12px;
        }

        .contact {
            text-align: right;
            font-size: 12px;
            color: #5d5870;
            line-height: 1.65;
        }
    </style>
</head>

<body>
<div class="document">

    {{-- ========================= NUEVA HOJA 1: ESTUDIO COMPARATIVO ========================= --}}
    <section class="page">
        <div class="top-bar"></div>
        <div class="bottom-bar"></div>

        <div class="page-inner">
            <div class="header-secondary">
                <div class="logo-wrap">
                    @if(!empty($logoUrl))
                        <img class="logo-image" src="{{ $logoUrl }}" alt="Logo Empresa">
                    @endif
                </div>

                <div class="secondary-title">
                    <h2>Estudio Comparativo de Oferta</h2>
                    <div class="sub">COMPARATIVA · {{ now()->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="soft-divider"></div>

            <div class="info-grid">
                <div class="info-card">
                    <div class="info-title">Punto de suministro</div>
                    <table class="info-table">
                        <tr>
                            <td>Nombre:</td>
                            <td>{{ ucwords(strtolower($enterprise)) }}</td>
                        </tr>
                        <tr>
                            <td>CIF:</td>
                            <td>{{ $CIF }}</td>
                        </tr>
                        <tr>
                            <td>CUPS:</td>
                            <td>{{ $cups }}</td>
                        </tr>
                        <tr>
                            <td>Dirección:</td>
                            <td>{{ ucwords(strtolower($location)) }}</td>
                        </tr>
                    </table>
                </div>

                <div class="info-card">
                    <div class="info-title">Datos de la oferta</div>
                    <table class="info-table">
                        <tr>
                            <td>Tarifa:</td>
                            <td>{{ $tariff }}</td>
                        </tr>
                        <tr>
                            <td>Actual:</td>
                            <td>{{ ucwords(strtolower($actualCom)) }}</td>
                        </tr>
                        <tr>
                            <td>Oferta:</td>
                            <td>
                                <span class="marketer-inline">
                                    <span>{{ ucwords(strtolower($offerCom)) }}</span>
                                    @if(!empty($marketerLogoUrl))
                                        <img class="marketer-logo" src="{{ $marketerLogoUrl }}" alt="Logo {{ $offerCom }}">
                                    @endif
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Periodo:</td>
                            <td>{{ $periodDays }} días</td>
                        </tr>
                        <tr>
                            <td>Consumo:</td>
                            <td>{{ $totalConsumptionFmt }} kWh</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="section-caption">
                <div class="txt">Término de potencia</div>
                <div class="rule"></div>
            </div>

            <table class="compare-table">
                <thead>
                <tr>
                    <th>Periodo</th>
                    <th>€/kW/día Actual</th>
                    <th>kW contratado</th>
                    <th>Total Actual</th>
                    <th>€/kW/día Oferta</th>
                    <th>Total Oferta</th>
                </tr>
                </thead>
                <tbody>
                @foreach($powers as $i => $kw)
                    <tr>
                        <td>P{{ $i + 1 }}</td>
                        <td>{{ number_format((float) str_replace(',', '.', ($currentPowerPrices[$i] ?? 0)), 6, ',', '.') }}</td>
                        <td>{{ number_format((float) ($kw ?: 0), 2, ',', '.') }}</td>
                        <td>{{ number_format(($data['currentSubtotal']['power']['P' . ($i + 1)] ?? 0), 2, ',', '.') }} €</td>
                        <td>
                            @if(isset($offerPowerPrices[$i]) && $offerPowerPrices[$i] !== null && $offerPowerPrices[$i] !== '')
                                {{ number_format((float) $offerPowerPrices[$i], 6, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ number_format(($data['offerSelected']['subTotal']['power']['P' . ($i + 1)] ?? 0), 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                @if(!empty($data['prices']['powerDiscount']))
                    <tr>
                        <td colspan="3"><strong>Descuento potencia actual</strong></td>
                        <td>
                            {{ number_format(($data['currentSubtotal']['power']['discount'] ?? 0), 2, ',', '.') }} €
                            <br>
                            <small>({{ number_format((float) $data['prices']['powerDiscount'], 2, ',', '.') }}%)</small>
                        </td>
                        <td><strong>Descuento oferta</strong></td>
                        <td>
                            @if(!empty($data['offerSelected']['prices']['powerDiscount']))
                                -{{ number_format(($data['offerSelected']['subTotal']['power']['discount'] ?? 0), 2, ',', '.') }} €
                                <br>
                                <small>({{ number_format((float) $data['offerSelected']['prices']['powerDiscount'], 2, ',', '.') }}%)</small>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endif
                <tr>
                    <td colspan="3"><strong>Total potencia</strong></td>
                    <td><strong>{{ number_format(($data['currentSubtotal']['power']['total'] ?? 0), 2, ',', '.') }} €</strong></td>
                    <td></td>
                    <td><strong>{{ number_format(($data['offerSelected']['subTotal']['power']['total'] ?? 0), 2, ',', '.') }} €</strong></td>
                </tr>
                </tfoot>
            </table>

            <div class="section-caption section-spaced">
                <div class="txt">Término de energía</div>
                <div class="rule"></div>
            </div>

            <table class="compare-table">
                <thead>
                <tr>
                    <th>Periodo</th>
                    <th>€/kWh Actual</th>
                    <th>kWh consumidos</th>
                    <th>Total Actual</th>
                    <th>€/kWh Oferta</th>
                    <th>Total Oferta</th>
                </tr>
                </thead>
                <tbody>
                @foreach($consumptions as $i => $kwh)
                    <tr>
                        <td>P{{ $i + 1 }}</td>
                        <td>{{ number_format((float) str_replace(',', '.', ($currentEnergyPrices[$i] ?? 0)), 6, ',', '.') }}</td>
                        <td>{{ number_format((float) ($kwh ?: 0), 2, ',', '.') }}</td>
                        <td>{{ number_format(($data['currentSubtotal']['energy']['P' . ($i + 1)] ?? 0), 2, ',', '.') }} €</td>
                        <td>
                            @if(isset($offerEnergyPrices[$i]) && $offerEnergyPrices[$i] !== null && $offerEnergyPrices[$i] !== '')
                                {{ number_format((float) $offerEnergyPrices[$i], 6, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ number_format(($data['offerSelected']['subTotal']['energy']['P' . ($i + 1)] ?? 0), 2, ',', '.') }} €</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                @if(!empty($data['prices']['energyDiscount']))
                    <tr>
                        <td colspan="3"><strong>Descuento energía actual</strong></td>
                        <td>
                            {{ number_format(($data['currentSubtotal']['energy']['discount'] ?? 0), 2, ',', '.') }} €
                            <br>
                            <small>({{ number_format((float) $data['prices']['energyDiscount'], 2, ',', '.') }}%)</small>
                        </td>
                        <td><strong>Descuento oferta</strong></td>
                        <td>
                            @if(!empty($data['offerSelected']['prices']['energyDiscount']))
                                {{ number_format(($data['offerSelected']['subTotal']['energy']['discount'] ?? 0), 2, ',', '.') }} €
                                <br>
                                <small>({{ number_format((float) $data['offerSelected']['prices']['energyDiscount'], 2, ',', '.') }}%)</small>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endif
                <tr>
                    <td colspan="3"><strong>Total energía</strong></td>
                    <td><strong>{{ number_format(($data['currentSubtotal']['energy']['total'] ?? 0), 2, ',', '.') }} €</strong></td>
                    <td></td>
                    <td><strong>{{ number_format(($data['offerSelected']['subTotal']['energy']['total'] ?? 0), 2, ',', '.') }} €</strong></td>
                </tr>
                </tfoot>
            </table>

            <div class="section-caption section-spaced">
                <div class="txt">Otros conceptos y totales</div>
                <div class="rule"></div>
            </div>

            <table class="compare-table totals-table">
                <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Total Tarifa Actual</th>
                    <th>Total Oferta</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>Impuesto Electricidad</td>
                    <td>{{ number_format($electricTaxAct, 2, ',', '.') }} €</td>
                    <td>{{ number_format($electricTaxOff, 2, ',', '.') }} €</td>
                </tr>

                @if(($socialBonusAct ?? 0) != 0 || ($socialBonusOff ?? 0) != 0)
                    <tr>
                        <td>Bono social</td>
                        <td>{{ number_format($socialBonusAct, 2, ',', '.') }} €</td>
                        <td>{{ number_format($socialBonusOff, 2, ',', '.') }} €</td>
                    </tr>
                @endif

                @if(($meterAct ?? 0) != 0 || ($meterOff ?? 0) != 0)
                    <tr>
                        <td>Alquiler equipo de medida</td>
                        <td>{{ number_format($meterAct, 2, ',', '.') }} €</td>
                        <td>{{ number_format($meterOff, 2, ',', '.') }} €</td>
                    </tr>
                @endif

                @if(($conceptsAct ?? 0) != 0 || ($conceptsOff ?? 0) != 0)
                    <tr>
                        <td>Otros conceptos</td>
                        <td>{{ number_format($conceptsAct, 2, ',', '.') }} €</td>
                        <td>{{ number_format($conceptsOff, 2, ',', '.') }} €</td>
                    </tr>
                @endif

                <tr>
                    <td>Subtotal (sin IVA)</td>
                    <td>{{ number_format($currentSubtotalNoIva, 2, ',', '.') }} €</td>
                    <td>{{ number_format($offerSubtotalNoIva, 2, ',', '.') }} €</td>
                </tr>

                <tr>
                    <td>IVA</td>
                    <td>{{ number_format($ivaAct, 2, ',', '.') }} €</td>
                    <td>{{ number_format($ivaOff, 2, ',', '.') }} €</td>
                </tr>

                <tr>
                    <td>TOTAL (con IVA)</td>
                    <td>{{ number_format($data['currentTotal'] ?? 0, 2, ',', '.') }} €</td>
                    <td>{{ number_format($data['offerSelected']['total'] ?? 0, 2, ',', '.') }} €</td>
                </tr>
                </tbody>
            </table>

            @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
                <div class="footer">
                    <div class="footer-inner">
                        @if(!empty($agentName))
                            <div class="advisor">
                                @if(!empty($footerLogo))
                                    <img class="footer-isotipo" src="{{ $footerLogo }}" alt="Isotipo Fidelity360">
                                @endif
                                <div>
                                    <div class="advisor-name">{{ $agentName }}</div>
                                    <div class="advisor-role">Asesor energético</div>
                                </div>
                            </div>
                        @endif

                        @if(!empty($agentPhone) || !empty($agentEmail))
                            <div class="contact">
                                @if(!empty($agentPhone))
                                    ☎ {{ $agentPhone }}<br>
                                @endif
                                @if(!empty($agentEmail))
                                    {{ $agentEmail }}<br>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- ========================= NUEVA HOJA 2: RESUMEN ECONÓMICO + NUESTRO ANÁLISIS ========================= --}}
    <section class="page analysis-page">
        <div class="top-bar"></div>
        <div class="bottom-bar"></div>

        <div class="page-inner">
            <div class="header-secondary">
                <div class="logo-wrap">
                    @if(!empty($logoUrl))
                        <img class="logo-image" src="{{ $logoUrl }}" alt="Logo Empresa">
                    @endif
                </div>
            </div>

            <div class="section-line summary-section">
                <div class="label">Resumen económico</div>
                <div class="line"></div>
            </div>

            <div class="resume-grid">
                <div class="resume-card">
                    <div class="k">Total tarifa actual</div>
                    <div class="v">{{ number_format($currentTotalPdf, 2, ',', '.') }} €</div>
                    <div class="s">€ / periodo (IVA incl.)</div>
                </div>

                <div class="resume-card purple">
                    <div class="k">Total con oferta</div>
                    <div class="v">{{ number_format($offerTotalPdf, 2, ',', '.') }} €</div>
                    <div class="s">€ / periodo (IVA incl.)</div>
                </div>

                <div class="resume-card green">
                    <div class="k">Ahorro total</div>
                    <div class="v">{{ number_format($saveAmountPdf, 2, ',', '.') }} €</div>
                    <div class="s">{{ number_format($savePercentPdf, 2, ',', '.') }}% de ahorro</div>
                </div>
            </div>

            <div class="section-line" style="margin-top: 34px;">
                <div class="label">Nuestro análisis</div>
                <div class="line"></div>
            </div>

            <div class="grid-2">
                <div class="card">
                    <div class="card-title">Consumo actual / situación</div>
                    <div class="card-body">
                        Consumo total en el periodo ({{ $periodDays }} días): {{ $totalConsumptionFmt }} kWh
                        <br>
                        @foreach($consumptions as $i => $kwh)
                            @php
                                $percentConsumption = ($totalConsumption > 0) ? round(($kwh / $totalConsumption) * 100) : 0;
                            @endphp
                            P{{ $i + 1 }}: {{ number_format((float) $kwh, 2, ',', '.') }} kWh
                            ({{ $percentConsumption }}%)@if(!$loop->last) · @endif
                        @endforeach
                    </div>
                </div>

                <div class="card green">
                    <div class="card-title">Ahorro estimado</div>
                    <div class="saving">
                        Ahorro estimado:
                        <strong>{{ number_format($saveAmountPdf, 2, ',', '.') }} €</strong>
                        ({{ number_format($savePercentPdf, 2, ',', '.') }}%)
                        frente a {{ ucwords(strtolower($actualCom)) }}
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Recomendación</div>
                    <div class="card-body">
                        Se recomienda la oferta de {{ ucwords(strtolower($offerCom)) }}
                        @if(!empty($product))
                            · {{ ucwords(strtolower($product)) }}
                        @endif
                        por el perfil de consumo analizado.
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Observaciones</div>
                    <div class="card-body">
                        @if(!empty($data['observaciones']))
                            {{ $data['observaciones'] }}
                        @else
                            No hay observaciones
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Características de la oferta</div>
                    <div class="card-body">
                        Tarifa {{ $tariff }} ofertada por {{ ucwords(strtolower($offerCom)) }}.
                        Producto: {{ ucwords(strtolower($product)) }}.
                    </div>
                </div>

                <div class="card">
                    <div class="card-title">Fecha de la propuesta</div>
                    <div class="big-date">
                        {{ strtoupper(\Carbon\Carbon::parse(now())->locale('es')->translatedFormat('d \d\e F \d\e Y')) }}
                    </div>

                    <div class="small-sub">Validez de la oferta</div>
                    <div class="card-body">
                        Oferta calculada con los precios disponibles en la fecha del informe.
                        Sujeto a actualización comercial.
                    </div>
                </div>
            </div>

            <div class="claim">
                <div class="line"></div>
                <div class="text">Su energía, en las mejores manos.</div>
                <div class="line"></div>
            </div>

            <div class="services-box">
                <div class="services-text">
                    Descubre cómo optimizar todos tus suministros: luz y gas, placas solares, cargadores eléctricos,
                    <br>
                    <strong>fibra y móvil, alarmas y mucho más. Solicita tu estudio personalizado sin compromiso.</strong>
                </div>

                <div class="services-grid">
                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><path d="M9 21h6"/><path d="M10 17h4"/><path d="M8 14a6 6 0 1 1 8 0c-1 1-1.5 2-1.5 3h-5c0-1-.5-2-1.5-3z"/><path d="M12 1v2"/><path d="M4.2 4.2l1.4 1.4"/><path d="M1 12h2"/><path d="M20.8 4.2l-1.4 1.4"/><path d="M23 12h-2"/></svg>
                        Luz
                    </div>

                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><path d="M12 22c4 0 7-3 7-7 0-5-5-8-6-13-3 3-7 7-7 13 0 4 3 7 6 7z"/></svg>
                        Gas
                    </div>

                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><path d="M3 13h18"/><path d="M5 9h14l2 8H3z"/><path d="M8 9l-1 8"/><path d="M16 9l1 8"/><path d="M12 9v8"/><path d="M12 2v3"/><path d="M5 5l2 2"/><path d="M19 5l-2 2"/></svg>
                        Placas solares
                    </div>

                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><path d="M7 3h7v18H7z"/><path d="M9 7h3"/><path d="M17 8v6a3 3 0 0 0 3 3"/><path d="M20 5v5"/><path d="M18 5v5"/></svg>
                        Cargadores eléctricos
                    </div>

                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><path d="M5 12a10 10 0 0 1 14 0"/><path d="M8 15a6 6 0 0 1 8 0"/><path d="M11 18a2 2 0 0 1 2 0"/><path d="M12 21h.01"/></svg>
                        Fibra
                    </div>

                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/><path d="M9 5h6"/></svg>
                        Móvil
                    </div>

                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z"/><rect x="9" y="11" width="6" height="5" rx="1"/><path d="M10 11V9a2 2 0 0 1 4 0v2"/></svg>
                        Alarmas
                    </div>

                    <div class="service-item">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M7 12h10"/></svg>
                        Y mucho más
                    </div>
                </div>
            </div>

            @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
                <div class="footer">
                    <div class="footer-inner">
                        @if(!empty($agentName))
                            <div class="advisor">
                                @if(!empty($footerLogo))
                                    <img class="footer-isotipo" src="{{ $footerLogo }}" alt="Isotipo Fidelity360">
                                @endif
                                <div>
                                    <div class="advisor-name">{{ $agentName }}</div>
                                    <div class="advisor-role">Asesor energético</div>
                                </div>
                            </div>
                        @endif

                        @if(!empty($agentPhone) || !empty($agentEmail))
                            <div class="contact">
                                @if(!empty($agentPhone))
                                    ☎ {{ $agentPhone }}<br>
                                @endif
                                @if(!empty($agentEmail))
                                    {{ $agentEmail }}<br>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
</body>
</html>
