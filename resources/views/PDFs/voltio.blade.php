@php
    /*
     * Plantilla exclusiva para Voltio Energía.
     * Se mantienen los mismos datos, cálculos y condiciones del Blade general;
     * únicamente se fija la identidad visual de Voltio.
     */
    $baseColor = '#0A63F6';
    $primary = '#0A63F6';
    $primaryDark = '#073B9D';
    $primaryDarker = '#062D77';
    $primaryLight = '#5A8FF5';
    $primaryLighter = '#A9C5FF';
    $primarySoft = '#EAF1FF';
    $primaryUltraSoft = '#F5F8FF';
    $primaryBorder = '#C9D9FA';
    $voltioOrange = '#F6A11A';
    $voltioOrangeSoft = '#FFF0D4';

    $commission = (float) ($data['offerSelected']['commission'] ?? 0);

    $studyDate = !empty($data['pdfForm']['studyDate'])
        ? \Carbon\Carbon::parse($data['pdfForm']['studyDate'])
        : \Carbon\Carbon::now();

    $commissionNumber = (string) intval(round($commission));
    $commissionCode = '89' . $commissionNumber . '47';

    // En la plantilla exclusiva de Voltio la URL es siempre corporativa.
    // El nombre, teléfono y correo del asesor continúan siendo dinámicos.
    $voltioWebsiteLabel = 'www.voltioenergia.es';
@endphp



    <!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Análisis Voltio</title>
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
            --accent: {{ $voltioOrange }};
            --accent-soft: {{ $voltioOrangeSoft }};
            --text: #333247;
            --text-soft: #5F5D72;
            --text-muted: #9795A4;
        }

        @page {
            size: A4;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        body {
            font-family: 'Montserrat', Arial, sans-serif;
            color: var(--text);
            font-size: 10.5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .document {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .page {
            position: relative;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
            background: #fff;
            page-break-after: always;
        }

        .page:last-of-type {
            page-break-after: auto;
        }

        .page-inner {
            position: relative;
            width: 100%;
            height: 100%;
            padding: 0 20px 96px;
        }

        .top-bar,
        .bottom-bar {
            display: none !important;
        }

        .header,
        .header-secondary {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 88px;
            margin: 0 -20px 30px;
            padding: 16px 28px 15px 38px;
            overflow: visible;
            color: #fff;
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='900' height='130' viewBox='0 0 900 130'%3E%3Cg fill='none' stroke='%23ffffff' stroke-opacity='.14' stroke-width='1.2' stroke-dasharray='4 5'%3E%3Cpath d='M-20 15 L75 56 L170 15 L265 56 L360 15 L455 56 L550 15 L645 56 L740 15 L835 56 L930 15'/%3E%3Cpath d='M-20 48 L75 89 L170 48 L265 89 L360 48 L455 89 L550 48 L645 89 L740 48 L835 89 L930 48'/%3E%3Cpath d='M-20 81 L75 122 L170 81 L265 122 L360 81 L455 122 L550 81 L645 122 L740 81 L835 122 L930 81'/%3E%3C/g%3E%3C/svg%3E"),
                linear-gradient(106deg, #1E2F99 0%, #202D99 68%, #D38EA5 91%, #F7B787 112%);
            background-size: cover;
            background-position: center;
        }

        .header::after,
        .header-secondary::after {
            content: '';
            position: absolute;
            right: -40px;
            top: -70px;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            background: rgba(255, 196, 141, .13);
            filter: blur(2px);
            z-index: -1;
        }

        .logo-wrap {
    width: 120px;
    height: 64px;
}

.logo-image {
    position: absolute;
    top: 50%;
    width: 120px !important;
    max-width: none !important;
    max-height: none !important;
    transform: translateY(-50%);
}

        
        .logo-wrap::before,
        .logo-wrap::after {
            content: none !important;
            display: none !important;
        }

    
        .header-right {
            position: static;
            z-index: 2;
            width: calc(100% - 155px);
            max-width: 520px;
            min-width: 0;
            padding-top: 0;
            color: #fff;
            text-align: right;
        }

        .secondary-title {
            position: relative;
            z-index: 2;
            width: calc(100% - 155px);
            max-width: 520px;
            min-width: 0;
            padding-top: 0;
            color: #fff;
            text-align: right;
        }


        .eyebrow {
            margin: 0 0 4px;
            color: #fff;
            font-size: 8.5px;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .title-main {
            margin: 0;
            color: #fff;
            font-size: 18px;
            line-height: 1.12;
            font-weight: 900;
        }

        
        .pill {
            position: absolute;
            z-index: 20;
            right: 38px;
            bottom: -15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            min-width: 94px;
            min-height: 30px;
            max-width: none;
            padding: 7px 18px;
            border: 0;
            border-radius: 999px;
            color: #fff;
            background: #0A63F6;
            box-shadow: 0 2px 5px rgba(7, 59, 157, .18);
            font-size: 11px;
            line-height: 1;
            font-weight: 900;
            letter-spacing: .7px;
            text-align: center;
            white-space: nowrap;
        }


        .secondary-title h2 {
            margin: 0;
            color: #fff;
            font-size: 19px;
            line-height: 1.05;
            font-weight: 900;
        }

        .secondary-title .sub {
            margin-top: 5px;
            color: #fff;
            font-size: 8.5px;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .soft-divider {
            display: none;
        }

        .intro-strip {
            margin: 0 0 12px;
            padding: 9px 15px;
            border-left: 4px solid var(--accent);
            border-radius: 8px;
            color: #696778;
            background: #E8EEFC;
            font-size: 10.5px;
            font-style: italic;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-box {
            margin: 0;
            overflow: hidden;
            border: 1px solid #BDD2FA;
            border-radius: 10px;
        }

        .main-offer thead th {
            padding: 8px 8px;
            color: #fff;
            font-size: 9.5px;
            font-weight: 900;
            letter-spacing: 1.1px;
            text-align: center;
            text-transform: uppercase;
        }

        .main-offer thead th:first-child {
            width: 78px;
            background: var(--primary-darker);
        }

        .main-offer thead th:nth-child(2) {
            background: #0645AE;
        }

        .main-offer thead th:nth-child(3) {
            background: var(--primary-light);
        }

        .main-offer tbody td {
            padding: 8px 8px;
            border-bottom: 1px solid #CADBFA;
            color: #2E2D38;
            background: #F3F7FF;
            font-size: 10.5px;
            font-weight: 700;
            text-align: center;
        }

        .main-offer tbody tr:nth-child(even) td:not(:first-child) {
            background: #E6EEFC;
        }

        .main-offer tbody tr:last-child td {
            border-bottom: 0;
        }

        .main-offer tbody td:first-child {
            width: 78px;
            color: #fff;
            background: #0844AE !important;
            font-size: 13px;
            font-weight: 900;
        }

        .section-line,
        .section-caption,
        .chart-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 15px 0 9px;
        }

        .section-line .label,
        .section-caption .txt,
        .chart-title .txt {
            flex: 0 0 auto;
            color: var(--accent);
            font-size: 10.5px;
            font-weight: 900;
            letter-spacing: 2.4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .section-line .line,
        .section-caption .rule,
        .chart-title .rule {
            flex: 1;
            height: 2px;
            background: var(--primary-light);
        }

        .grid-2,
        .info-grid,
        .summary-chips,
        .resume-grid {
            display: grid;
        }

        .grid-2 {
            grid-template-columns: 1fr 1fr;
            gap: 9px 10px;
        }

        .card {
            min-height: 78px;
            padding: 11px 12px 10px;
            border: 3px solid #FBE8C6;
            border-radius: 11px;
            background: #fff;
        }

        .card.green {
            border-color: var(--accent);
            background: var(--accent-soft);
        }

        .card-title {
            margin-bottom: 6px;
            color: var(--primary-light);
            font-size: 9.4px;
            font-weight: 900;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .card.green .card-title {
            color: var(--accent);
        }

        .card-body,
        .saving,
        .savings {
            color: #5B596E;
            font-size: 9.7px;
            line-height: 1.42;
        }

        .saving,
        .savings {
            font-weight: 500;
        }

        .big-date {
            margin: 0 0 4px;
            color: var(--primary-dark);
            font-size: 15px;
            line-height: 1;
            font-weight: 900;
        }

        .small-sub {
            margin: 0 0 3px;
            color: var(--primary-light);
            font-size: 9px;
            font-weight: 900;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }

        .claim {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 15px 0 0;
        }

        .claim .line {
            flex: 1;
            height: 2px;
            background: var(--primary-light);
        }

        .claim .text {
            color: var(--accent);
            font-size: 10.5px;
            font-weight: 900;
            letter-spacing: 3.2px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .info-grid {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin: 0 0 12px;
        }

        .info-card {
            min-height: 120px;
            padding: 14px 16px;
            border: 2px solid var(--accent);
            border-radius: 5px;
            background: #fff;
        }

        .info-title {
            margin-bottom: 10px;
            color: var(--accent);
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }

        .info-table td {
            padding: 2px 0;
            color: #5B596E;
            font-size: 10px;
            line-height: 1.25;
            vertical-align: top;
        }

        .info-table td:first-child {
            width: 85px;
            color: var(--primary-dark);
            font-weight: 900;
        }

        .marketer-inline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }

        .marketer-logo {
            display: inline-block;
            width: auto;
            max-width: 65px;
            height: auto;
            max-height: 18px;
            object-fit: contain;
            vertical-align: middle;
        }

        .section-caption {
            margin: 8px 0 5px;
        }

        .section-caption .txt {
            font-size: 9.8px;
        }

        .section-spaced {
            margin-top: 8px;
        }

        .compare-table {
            width: 100%;
            margin-bottom: 6px;
            table-layout: fixed;
        }

        .compare-table thead th {
            padding: 4px 4px;
            color: #fff;
            background: var(--primary);
            font-size: 7.2px;
            line-height: 1.1;
            font-weight: 800;
            text-align: center;
        }

        .compare-table thead th:first-child {
            background: var(--primary-darker);
        }

        .compare-table thead th:nth-last-child(2) {
            background: #0647AF;
        }

        .compare-table thead th:last-child {
            background: var(--accent);
        }

        .compare-table tbody td,
        .compare-table tfoot td {
            padding: 3.5px 3px;
            border-bottom: 1px solid #C8D9FA;
            color: #514F64;
            background: #EEF4FF;
            font-size: 7.8px;
            line-height: 1.15;
            text-align: center;
        }

        .compare-table tbody tr:nth-child(even) td {
            background: #E2ECFD;
        }

        .compare-table tbody td:first-child {
            color: var(--primary-dark);
            font-weight: 900;
            background: #DCE8FC !important;
        }

        .compare-table tfoot td {
            border-top: 1px solid #C8D9FA;
            font-weight: 700;
        }

        .compare-table tfoot tr:last-child td {
            font-weight: 900;
        }

        .totals-table thead th:first-child {
            background: var(--primary-darker);
        }

        .totals-table thead th:nth-child(2) {
            background: var(--primary);
        }

        .totals-table thead th:nth-child(3) {
            background: var(--accent);
        }

        .totals-table tbody td:first-child {
            color: var(--primary-dark);
            font-weight: 900;
            background: #E0EAFC !important;
        }

        .totals-table tbody tr:last-child td {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 900;
        }

        .totals-table tbody tr:last-child td:last-child {
            color: var(--primary);
        }

        .summary-chips {
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin: 0 0 9px;
        }

        .chip {
            min-height: 72px;
            padding: 12px 14px;
            border-radius: 10px;
            background: var(--accent-soft);
        }

        .chip .chip-title {
            margin-bottom: 6px;
            color: #AAA4B9;
            font-size: 8.5px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .chip .chip-value {
            color: var(--primary-dark);
            font-size: 17px !important;
            line-height: 1.05;
            font-weight: 900;
        }

        .chart-title {
            margin: 8px 0 4px;
        }

        .chart-title .txt {
            color: var(--primary-light);
            font-size: 9.5px;
        }

        .chart-box {
            margin-bottom: 10px;
            padding: 0;
            text-align: center;
        }

        .chart-box svg {
            display: block;
            width: 100%;
            height: auto;
            margin: 0 auto;
        }

        .legend {
            margin-top: -2px;
            color: #777486;
            font-size: 9px;
            text-align: center;
        }

        .legend span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 0 8px;
        }

        .legend i {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 2px;
        }

        .resume-grid {
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 7px;
        }

        .resume-card {
            min-height: 75px;
            padding: 12px 10px 10px;
            border-radius: 10px;
            background: var(--accent-soft);
            text-align: center;
        }

        .resume-card.purple {
            background: var(--accent-soft);
        }

        .resume-card.green {
            border: 2px solid var(--accent);
            background: #fff;
        }

        .resume-card .k {
            color: #918BA4;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .resume-card .v {
            margin-top: 3px;
            color: var(--primary-dark);
            font-size: 19px;
            line-height: 1.05;
            font-weight: 900;
        }

        .resume-card .s {
            margin-top: 2px;
            color: #918BA4;
            font-size: 8px;
            font-weight: 700;
        }

        .resume-card.green .k,
        .resume-card.green .v {
            color: var(--primary-dark);
        }

        .resume-card.green .s {
            color: var(--primary-light);
        }

        .card,
        .table-box,
        .info-card,
        .compare-table,
        .chart-box,
        .resume-card,
        .chip {
            page-break-inside: avoid;
        }

        .footer {
            position: absolute;
            z-index: 5;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 92px;
        }

        .footer-contact {
            height: 30px;
            padding: 8px 20px 5px;
            color: #9A9A9E;
            font-size: 10.5px;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
        }

        .footer-contact strong {
            color: #84848A;
            font-weight: 600;
        }

        .footer-brand {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 62px;
            overflow: hidden;
            color: #fff;
            background-image:
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='900' height='90' viewBox='0 0 900 90'%3E%3Cg fill='none' stroke='%23ffffff' stroke-opacity='.13' stroke-width='1.1' stroke-dasharray='4 5'%3E%3Cpath d='M-20 5 L75 46 L170 5 L265 46 L360 5 L455 46 L550 5 L645 46 L740 5 L835 46 L930 5'/%3E%3Cpath d='M-20 38 L75 79 L170 38 L265 79 L360 38 L455 79 L550 38 L645 79 L740 38 L835 79 L930 38'/%3E%3C/g%3E%3C/svg%3E"),
                linear-gradient(106deg, #1E2F99 0%, #202D99 70%, #D58FA4 92%, #F8B88A 115%);
            background-size: cover;
        }

        .footer-brand::before {
            content: 'V';
            position: absolute;
            left: 25px;
            bottom: -32px;
            color: rgba(255, 255, 255, .10);
            font-size: 116px;
            line-height: 1;
            font-weight: 900;
            transform: skewX(-11deg);
        }

        .footer-url {
            position: relative;
            z-index: 2;
            color: #fff;
            font-size: 15px;
            font-weight: 900;
            letter-spacing: .1px;
            text-align: center;
        }

        .internal-commission-code {
            position: absolute;
            z-index: 20;
            right: 8px;
            bottom: 64px;
            color: #B4B4B7;
            font-size: 6px;
            line-height: 1;
            font-weight: 600;
            letter-spacing: .2px;
        }

        @media print {
            .page {
                break-after: page;
            }

            .page:last-child {
                break-after: auto;
            }
        }
    </style>
</head>

<body>
@php
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
        ($meterAct ?? 0); // ✅ añadido

    $offerSubtotalNoIva =
        ($powerOff ?? 0) +
        ($energyOff ?? 0) +
        ($electricTaxOff ?? 0) +
        ($socialBonusOff ?? 0) +
        ($conceptsOff ?? 0) +
        ($meterOff ?? 0); // ✅ añadido

    $consumptions = array_map(function ($v) {
        if ($v === null || $v === '') return 0;
        return (float) str_replace(',', '.', $v);
    }, $data['cupsData']['consumption'] ?? []);

    $powers = array_map(function ($v) {
        if ($v === null || $v === '') return 0;
        return (float) str_replace(',', '.', $v);
    }, $data['cupsData']['power'] ?? []);
    $currentPowerPrices = $data['prices']['power'] ?? [];
    $offerPowerPrices = array_map(function($price, $i) use ($data) {
        $fee = isset($data['offerFees']['power'][$i]) ? (float) $data['offerFees']['power'][$i] / 30 : 0;
        return ($price !== null && $price !== '') ? (float) $price + $fee : $price;
    }, $data['offerSelected']['prices']['power'] ?? [], array_keys($data['offerSelected']['prices']['power'] ?? []));

    $currentEnergyPrices = $data['prices']['energy'] ?? [];

    $offerEnergyBase = $data['offerSelected']['prices']['energy']
    ?? $data['offerSelected']['prices']['consumption']
    ?? [];

    $offerEnergyPrices = array_map(function($price, $i) use ($data) {
        $fee = isset($data['offerFees']['energy'][$i]) ? (float) $data['offerFees']['energy'][$i] / 1000 : 0;
        return ($price !== null && $price !== '') ? (float) $price + $fee : $price;
    }, $offerEnergyBase ?? [], array_keys($offerEnergyBase ?? []));


    $maxConsumption = !empty($consumptions) ? max($consumptions) : 1;
    $maxConsumption = $maxConsumption > 0 ? $maxConsumption : 1;

    $chartMax = ceil($maxConsumption / 5) * 5;
    if ($chartMax < 5)
        $chartMax = 5;

    $costLabels = ['Potencia', 'Energía', 'IVA'];
    $costActual = [$powerAct, $energyAct, $ivaAct];
    $costOffer = [$powerOff, $energyOff, $ivaOff];

    $maxCost = max(array_merge($costActual, $costOffer, [1]));
    $costChartMax = ceil($maxCost / 5) * 5;
    if ($costChartMax < 5)
        $costChartMax = 5;

    $offersForPdf = [];
    if ($includeOffersInPdf ?? false) {
        $offersForPdf = !empty($selectedOffers) ? $selectedOffers : ($topOffers ?? []);
    }

    $powerDiscountPercent = $data['prices']['powerDiscount'] ?? 0;
    $energyDiscountPercent = $data['prices']['energyDiscount'] ?? 0;

    $powerDiscountAmount = $data['currentSubtotal']['power']['discount'] ?? 0;
    $energyDiscountAmount = $data['currentSubtotal']['energy']['discount'] ?? 0;

    $offerPowerDiscountAmount = $data['offerSelected']['subTotal']['power']['discount'] ?? 0;
    $offerEnergyDiscountAmount = $data['offerSelected']['subTotal']['energy']['discount'] ?? 0;

    $offerPowerDiscountPercent = $data['offerSelected']['prices']['powerDiscount'] ?? 0;
    $offerEnergyDiscountPercent = $data['offerSelected']['prices']['energyDiscount'] ?? 0;

    $totalConsumption = array_sum($data['cupsData']['consumption'] ?? []);
@endphp





<div class="document">
    {{-- ========================= PAGE 1 ========================= --}}
    <section class="page">

        <div class="page-inner">

            <div class="header">
                <div class="logo-wrap">
                    <img class="logo-image" src="{{ $logoUrl }}" alt="Logo Empresa">
                </div>

                <div class="header-right">
                    <div class="eyebrow">PROPUESTA DE LUZ — {{ $tariff }}</div>
                    <div class="title-main">{{ ucwords(strtolower($enterprise)) }}</div>
                    <div class="pill">{{ $tariff }}</div>
                </div>
            </div>

            <div class="intro-strip">
                Les presentamos nuestra oferta para su punto de suministro · CUPS: {{ $cups }}
            </div>

            <div class="table-box">
                <table class="main-offer">
                    <thead>
                    <tr>
                        <th>Periodo</th>
                        <th>Potencia (€/kW/día)</th>
                        <th>Energía (€/kWh)</th>
                    </tr>
                    </thead>
                    <tbody>
                    @php
                        $maxRows = max(count($offerPowerPrices), count($offerEnergyPrices), 3);
                    @endphp

                    @for($i = 0; $i < $maxRows; $i++)
                        <tr>
                            <td>P{{ $i + 1 }}</td>
                            <td>
                                @if(isset($offerPowerPrices[$i]) && $offerPowerPrices[$i] !== null && $offerPowerPrices[$i] !== '')
                                    {{ number_format((float) $offerPowerPrices[$i], 6, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if(isset($offerEnergyPrices[$i]) && $offerEnergyPrices[$i] !== null && $offerEnergyPrices[$i] !== '')
                                    {{ number_format((float) $offerEnergyPrices[$i], 6, ',', '.') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>

            <div class="section-line">
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
                                $percent = ($totalConsumption > 0) ? round(($kwh / $totalConsumption) * 100) : 0;
                            @endphp
                            P{{ $i + 1 }}: {{ number_format((float) $kwh, 2, ',', '.') }} kWh
                            ({{ $percent }}%)@if(!$loop->last) · @endif
                        @endforeach
                    </div>
                </div>

                <div class="card green">
                    <div class="card-title">Ahorro estimado</div>
                    @php
                        $save = $data['offerSelected']['save'] ?? 0;
                    @endphp

                    <div class="saving">
                        Ahorro estimado:
                        {{ number_format($data['offerSelected']['save']?? $data['offerSelected']['saveAmount'] ?? 0, 2, ',', '.') }} €
                        ({{ number_format($data['offerSelected']['savePercent'] ?? 0, 2, ',', '.') }}%)

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

                    @if(!empty($data['observaciones']))
                        {{ $data['observaciones'] }}
                    @else
                        No hay observaciones
                    @endif

                    <div class="card-body">

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
                        {{ strtoupper($studyDate->locale('es')->translatedFormat('d \d\e F \d\e Y')) }}
                    </div>

                    <div class="small-sub">Validez de la oferta</div>
                    <div class="card-body">
                        Oferta calculada con los precios disponibles en la fecha del informe. Sujeto a actualización
                        comercial.
                    </div>
                </div>
            </div>

            <div class="claim">
                <div class="line"></div>
                <div class="text">Su energía, en las mejores manos.</div>
                <div class="line"></div>
            </div>

            <div class="np-sep"></div>
            <div class="footer">
                <div class="footer-contact">
                    @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
                        <strong>Asesor energético</strong>
                        @if(!empty($agentName))
                            {{ $agentName }}
                        @endif
                        @if(!empty($agentPhone) && ($data['basicData']['userSubdomain']['_id'] ?? null) !== '6909faa9232c09035a03f3b2')
                            @if(!empty($agentName)) · @endif{{ $agentPhone }}
                            @if(($data['basicData']['userSubdomain']['_id'] ?? null) == '65cb57489c2c285441086a43')
                                / 957855980
                            @endif
                        @endif
                        @if(!empty($agentEmail) && ($data['basicData']['userSubdomain']['_id'] ?? null) !== '6909faa9232c09035a03f3b2')
                            - {{ $agentEmail }}
                        @endif
                    @endif
                </div>
                <div class="footer-brand">
                    <div class="footer-url">{{ $voltioWebsiteLabel }}</div>
                </div>
            </div>

        </div>
    </section>

    {{-- ========================= PAGE 2 ========================= --}}
    <section class="page">


        <div class="page-inner">

            <div class="header-secondary">
                <div class="logo-wrap">
                    <img class="logo-image" src="{{ $logoUrl }}" alt="Logo Empresa">
                </div>

                <div class="secondary-title">
                    <h2>Estudio Comparativo de Oferta</h2>
                    <div class="sub">COMPARATIVA · {{ $studyDate->format('d/m/Y') }}</div>
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
                            </td>                            </tr>
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

                        <td>
                            {{ number_format(
                                (float) str_replace(',', '.', ($currentPowerPrices[$i] ?? 0)),
                                6,
                                ',',
                                '.'
                            ) }}
                        </td>

                        <td>
                            {{ number_format((float) ($kw ?: 0), 2, ',', '.') }}
                        </td>

                        <td>
                            {{ number_format(
                                ($data['currentSubtotal']['power']['P' . ($i + 1)] ?? 0),
                                2,
                                ',',
                                '.'
                            ) }} €
                        </td>

                        <td>
                            @if(isset($offerPowerPrices[$i]) && $offerPowerPrices[$i] !== null && $offerPowerPrices[$i] !== '')
                                {{ number_format((float) $offerPowerPrices[$i], 6, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            {{ number_format(
    ($data['offerSelected']['subTotal']['power']['P' . ($i + 1)] ?? 0),
    2,
    ',',
    '.'
) }} €
                        </td>
                    </tr>
                @endforeach
                </tbody>

                <tfoot>
                @if(!empty($data['prices']['powerDiscount']))
                    <tr>
                        <td colspan="3"><strong>Descuento potencia actual</strong></td>
                        <td>
                            {{ number_format(
    ($data['currentSubtotal']['power']['discount'] ?? 0),
    2,
    ',',
    '.'
) }} €
                            <br>
                            <small>({{ number_format((float) $data['prices']['powerDiscount'], 2, ',', '.') }}%)</small>
                        </td>
                        <td><strong>Descuento oferta</strong></td>
                        <td>
                            @if(!empty($data['offerSelected']['prices']['powerDiscount']))
                                -{{ number_format(
                                                                ($data['offerSelected']['subTotal']['power']['discount'] ?? 0),
                                                                2,
                                                                ',',
                                                                '.'
                                                            ) }} €
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
                    <td>
                        <strong>
                            {{ number_format(
($data['currentSubtotal']['power']['total'] ?? 0),
2,
',',
'.'
) }} €
                        </strong>
                    </td>
                    <td></td>
                    <td>
                        <strong>
                            {{ number_format(
($data['offerSelected']['subTotal']['power']['total'] ?? 0),
2,
',',
'.'
) }} €
                        </strong>
                    </td>
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

                        <td>
                            {{ number_format(
    (float) str_replace(',', '.', ($currentEnergyPrices[$i] ?? 0)),
    6,
    ',',
    '.'
) }}
                        </td>

                        <td>
                            {{ number_format((float) ($kwh ?: 0), 2, ',', '.') }}
                        </td>

                        <td>
                            {{ number_format(
    ($data['currentSubtotal']['energy']['P' . ($i + 1)] ?? 0),
    2,
    ',',
    '.'
) }} €
                        </td>

                        <td>
                            @if(isset($offerEnergyPrices[$i]) && $offerEnergyPrices[$i] !== null && $offerEnergyPrices[$i] !== '')
                                {{ number_format((float) $offerEnergyPrices[$i], 6, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>

                        <td>
                            {{ number_format(
    ($data['offerSelected']['subTotal']['energy']['P' . ($i + 1)] ?? 0),
    2,
    ',',
    '.'
) }} €
                        </td>
                    </tr>
                @endforeach
                </tbody>

                <tfoot>
                @if(!empty($data['prices']['energyDiscount']))
                    <tr>
                        <td colspan="3"><strong>Descuento energía actual</strong></td>
                        <td>
                            {{ number_format(
    ($data['currentSubtotal']['energy']['discount'] ?? 0),
    2,
    ',',
    '.'
) }} €
                            <br>
                            <small>({{ number_format((float) $data['prices']['energyDiscount'], 2, ',', '.') }}%)</small>
                        </td>
                        <td><strong>Descuento oferta</strong></td>
                        <td>
                            @if(!empty($data['offerSelected']['prices']['energyDiscount']))
                                {{ number_format(
            ($data['offerSelected']['subTotal']['energy']['discount'] ?? 0),
            2,
            ',',
            '.'
        ) }} €
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
                    <td>
                        <strong>
                            {{ number_format(
($data['currentSubtotal']['energy']['total'] ?? 0),
2,
',',
'.'
) }} €
                        </strong>
                    </td>
                    <td></td>
                    <td>
                        <strong>
                            {{ number_format(
($data['offerSelected']['subTotal']['energy']['total'] ?? 0),
2,
',',
'.'
) }} €
                        </strong>
                    </td>
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

                {{-- ✅ BONO SOCIAL --}}
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

                {{-- ⚠️ OTROS CONCEPTOS --}}
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
            <div class="footer">
                <div class="footer-contact">
                    @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
                        <strong>Asesor energético</strong>
                        @if(!empty($agentName))
                            {{ $agentName }}
                        @endif
                        @if(!empty($agentPhone) && ($data['basicData']['userSubdomain']['_id'] ?? null) !== '6909faa9232c09035a03f3b2')
                            @if(!empty($agentName)) · @endif{{ $agentPhone }}
                            @if(($data['basicData']['userSubdomain']['_id'] ?? null) == '65cb57489c2c285441086a43')
                                / 957855980
                            @endif
                        @endif
                        @if(!empty($agentEmail) && ($data['basicData']['userSubdomain']['_id'] ?? null) !== '6909faa9232c09035a03f3b2')
                            - {{ $agentEmail }}
                        @endif
                    @endif
                </div>
                <div class="footer-brand">
                    <div class="footer-url">{{ $voltioWebsiteLabel }}</div>
                </div>
            </div>

        </div>
    </section>

    {{-- ========================= PAGE 3 ========================= --}}
    <section class="page">


        <div class="page-inner">

            <div class="header-secondary">
                <div class="logo-wrap">
                    <img class="logo-image" src="{{ $logoUrl }}" alt="Logo Empresa">
                </div>

                <div class="secondary-title">
                    <h2>Análisis Visual del Consumo</h2>
                    <div class="sub">COMPARATIVA · {{ now()->format('d/m/Y') }}</div>
                </div>
            </div>

            <div class="summary-chips">
                <div class="chip">
                    <div class="chip-title">Cliente</div>
                    <div class="chip-value" style="font-size:18px;">{{ ucwords(strtolower($enterprise)) }}</div>
                </div>
                <div class="chip">
                    <div class="chip-title">Tarifa</div>
                    <div class="chip-value" style="font-size:18px;">{{ $tariff }}</div>
                </div>
                <div class="chip">
                    <div class="chip-title">Periodo</div>
                    <div class="chip-value" style="font-size:18px;">{{ $periodDays }} días</div>
                </div>
            </div>

            <div class="chart-title">
                <div class="txt">Distribución del consumo por periodo (kWh)</div>
                <div class="rule"></div>
            </div>

            <div class="chart-box">
                <svg width="720" height="220" viewBox="0 0 720 220">
                    @php
                        $baseY = 180;
                        $topY = 28;
                        $chartHeight = $baseY - $topY;
                        $barWidth = 60;
                        $gridSteps = 5;
                        $chartStartX = 110;
                        $chartEndX = 620;
                        $chartWidth = $chartEndX - $chartStartX;
                        $count = count($consumptions);
                        $spacing = $count > 0 ? $chartWidth / $count : 0;
                    @endphp

                    @for($s = 0; $s <= $gridSteps; $s++)
                        @php
                            $y = $baseY - ($chartHeight / $gridSteps) * $s;
                            $label = round(($chartMax / $gridSteps) * $s);
                        @endphp
                        <line x1="110" y1="{{ $y }}" x2="620" y2="{{ $y }}" stroke="{{ $primaryBorder }}"
                              stroke-width="1" />
                        <text x="82" y="{{ $y + 3 }}" font-size="11" fill="#9d98aa">{{ $label }}</text>
                    @endfor

                    @foreach($consumptions as $i => $value)
                        @php
                            $height = $chartMax > 0 ? ($value / $chartMax) * $chartHeight : 0;
                            $height = max($height, 2);
                            $x = $chartStartX + ($i * $spacing) + ($spacing / 2) - ($barWidth / 2);
                            $y = $baseY - $height;
                            $fill = $primary;
                        @endphp

                        <rect x="{{ $x }}" y="{{ $y }}" width="{{ $barWidth }}" height="{{ $height }}" rx="4"
                              fill="{{ $fill }}" />
                        <text x="{{ $x + ($barWidth / 2) - 12 }}" y="{{ $y - 6 }}" font-size="12" font-weight="800"
                              fill="{{ $primaryDark }}">
                            {{ number_format((float) $value, 0, ',', '.') }}
                        </text>
                        <text x="{{ $x + ($barWidth / 2) - 12 }}" y="196" font-size="13" font-weight="800"
                              fill="#47434b">
                            P{{ $i + 1 }}
                        </text>
                    @endforeach
                </svg>
            </div>

            <div class="chart-title">
                <div class="txt">Comparativa de costes por concepto (€)</div>
                <div class="rule"></div>
            </div>

            <div class="chart-box">
                <svg width="720" height="250" viewBox="0 0 720 250">
                    @php
                        $baseY2 = 190;
                        $topY2 = 25;
                        $chartHeight2 = $baseY2 - $topY2;
                        $groupX = [140, 312, 484];
                        $barW = 52;
                        $gap = 6;
                        $gridSteps2 = 5;
                    @endphp

                    @for($s = 0; $s <= $gridSteps2; $s++)
                        @php
                            $y = $baseY2 - ($chartHeight2 / $gridSteps2) * $s;
                            $label = round(($costChartMax / $gridSteps2) * $s);
                        @endphp
                        <line x1="110" y1="{{ $y }}" x2="620" y2="{{ $y }}" stroke="{{ $primaryBorder }}"
                              stroke-width="1" />
                        <text x="78" y="{{ $y + 3 }}" font-size="11" fill="#9d98aa">{{ $label }}</text>
                    @endfor

                    @foreach($costLabels as $i => $label)
                        @php
                            $x1 = $groupX[$i];
                            $x2 = $x1 + $barW + $gap;
                            $v1 = $costActual[$i];
                            $v2 = $costOffer[$i];
                            $h1 = $costChartMax > 0 ? ($v1 / $costChartMax) * $chartHeight2 : 0;
                            $h2 = $costChartMax > 0 ? ($v2 / $costChartMax) * $chartHeight2 : 0;
                            $y1 = $baseY2 - $h1;
                            $y2 = $baseY2 - $h2;
                        @endphp

                        <rect x="{{ $x1 }}" y="{{ $y1 }}" width="{{ $barW }}" height="{{ $h1 }}" rx="3"
                              fill="{{ $primary }}" />
                        <rect x="{{ $x2 }}" y="{{ $y2 }}" width="{{ $barW }}" height="{{ $h2 }}" rx="3"
                              fill="{{ $primaryLight }}" />

                        <text x="{{ $x1 + 12 }}" y="{{ $y1 - 6 }}" font-size="10" font-weight="800"
                              fill="{{ $primaryDark }}">
                            {{ number_format($v1, 2, ',', '.') }}
                        </text>
                        <text x="{{ $x2 + 12 }}" y="{{ $y2 - 6 }}" font-size="10" font-weight="800"
                              fill="{{ $primary }}">
                            {{ number_format($v2, 2, ',', '.') }}
                        </text>

                        <text x="{{ $x1 + 35 }}" y="206" font-size="13" font-weight="800" fill="#47434b">
                            {{ $label }}
                        </text>
                    @endforeach
                </svg>

                <div class="legend">
                    <span><i style="background:{{ $primary }};"></i> Tarifa actual</span>
                    <span><i style="background:{{ $primaryLight }};"></i> Oferta</span>
                </div>
            </div>

            <div class="chart-title" style="margin-top:14px;">
                <div class="txt">Resumen económico</div>
                <div class="rule"></div>
            </div>

            <div class="resume-grid">
                <div class="resume-card">
                    <div class="k">Total tarifa actual</div>
                    <div class="v">{{ number_format($data['currentTotal'] ?? 0, 2, ',', '.') }} €</div>
                    <div class="s">€ / periodo (IVA incl.)</div>
                </div>

                <div class="resume-card purple">
                    <div class="k">Total con oferta</div>
                    <div class="v">{{ number_format($data['offerSelected']['total'] ?? 0, 2, ',', '.') }} €
                    </div>
                    <div class="s">€ / periodo (IVA incl.)</div>
                </div>

                <div class="resume-card green">
                    <div class="k">Ahorro total</div>
                    <div class="v">{{ number_format($data['offerSelected']['save'] ?? 0, 2, ',', '.') }} €</div>
                    <div class="s">{{ number_format($data['offerSelected']['savePercent'] ?? 0, 2, ',', '.') }}%
                        de ahorro</div>
                </div>
            </div>


            <div class="footer">
                <div class="footer-contact">
                    @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
                        <strong>Asesor energético</strong>
                        @if(!empty($agentName))
                            {{ $agentName }}
                        @endif
                        @if(!empty($agentPhone) && ($data['basicData']['userSubdomain']['_id'] ?? null) !== '6909faa9232c09035a03f3b2')
                            @if(!empty($agentName)) · @endif{{ $agentPhone }}
                            @if(($data['basicData']['userSubdomain']['_id'] ?? null) == '65cb57489c2c285441086a43')
                                / 957855980
                            @endif
                        @endif
                        @if(!empty($agentEmail) && ($data['basicData']['userSubdomain']['_id'] ?? null) !== '6909faa9232c09035a03f3b2')
                            - {{ $agentEmail }}
                        @endif
                    @endif
                </div>
                <div class="footer-brand">
                    <div class="footer-url">{{ $voltioWebsiteLabel }}</div>
                </div>
            </div>



            @if ($data['basicData']['userLogged']['_id'] == "698340c75525f31823005652")
                <div class="internal-commission-code">
                    {{ $commissionCode }}
                </div>
            @endif
            

        </div>
    </section>

</div>
</body>

</html>
