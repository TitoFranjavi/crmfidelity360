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
        'viasolenergiasColor'=> '#051D66',
        'ajasesoresColor' => '#002b45',
        'ahorrodirectColor' => '#ec1d23',
        'lumigasenergiaColor' => '#009b94',
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
        'onexenergiaColor' => '#2d2e83',
        'barriozubietaColor' => '#272453',
        'enerwatiaColor' => '#a87eb0',
        'selectraColor' => '#0A64C8',
        'voltioenergiaColor' => '#215CFF',
        'callTalentColor'=> '#fed22b',
        'conesurColor'=> '#3b8023',
    ];

    $companyColorKey = $data['basicData']['enterprise']['color'] ?? 'zocoEnergiaColor';
    $baseColor = $colors[$companyColorKey] ?? '#000000';


    $commission = (float) ($data['offerSelected']['commission'] ?? 0);

    $studyDate = !empty($data['pdfForm']['studyDate'])
    ? \Carbon\Carbon::parse($data['pdfForm']['studyDate'])
    : \Carbon\Carbon::now();

    
    $commissionNumber = (string) intval(round($commission));

    $commissionCode = '89' . $commissionNumber . '47';

    /* ===== FUNCIÓN COLOR PRO ===== */
    function adjustColor($hex, $percent)
    {
        $hex = str_replace('#', '', $hex);

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

        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /* ===== ESCALA COMPLETA ===== */

    $primary = $baseColor;

    $primaryDark = adjustColor($baseColor, -35);
    $primaryDarker = adjustColor($baseColor, -55);

    $primaryLight = adjustColor($baseColor, 35);
    $primaryLighter = adjustColor($baseColor, 60);

    $primarySoft = adjustColor($baseColor, 90);
    $primaryUltraSoft = adjustColor($baseColor, 96);

    $primaryBorder = adjustColor($baseColor, 80);

    if ($baseColor === '#000000') {
        $primaryLight = '#666666';
        $primarySoft = '#f2f2f2';
        $primaryBorder = '#dddddd';
    }

@endphp



    <!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    </style>
    <title>Análisis</title>
    <style>
        :root {
            --primary:
        {{ $primary }}
;
            --primary-dark:
        {{ $primaryDark }}
;
            --primary-darker:
        {{ $primaryDarker }}
;

            --primary-light:
        {{ $primaryLight }}
;
            --primary-lighter:
        {{ $primaryLighter }}
;

            --primary-soft:
        {{ $primarySoft }}
;
            --primary-ultra-soft:
        {{ $primaryUltraSoft }}
;

            --primary-border:
        {{ $primaryBorder }}
;

            --text: #2d2d2d;
            --text-soft: #6b6b6b;
            --text-muted: #8a8a8a;

            --bg-page: #f7f7f8;
            --bg-card: #ffffff;
            --bg-soft: #fafafa;

            --success: #42b86b;
            --success-soft: #e8f6ec;

            --danger: #d64545;
            --danger-soft: #fdecec;
        }

        @page {
            margin: 0;
            size: A4;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff;
            font-family: 'Montserrat', sans-serif;
            color: var(--text);
            font-size: 12px;
        }

        .document {
            width: 100%;
            padding: 10px 0 30px;
        }

        .page {
            width: 100%;
            height: auto;
            page-break-after: always;
        }

        .page:last-of-type {
            page-break-after: auto;
        }

        .top-bar,
        .bottom-bar {
            position: fixed;
            left: 0;
            right: 0;
            height: 8px;
        }
        .internal-commission-code {
            position: fixed;
            right: 12px;
            bottom: 9px;
            font-size: 12px;
            color: #0a0a0a;
            font-weight: 600;
            letter-spacing: 0.4px;
            line-height: 1;
            z-index: 9999;
        }
        .top-bar {
            top: 0;
            background: linear-gradient(90deg,
            var(--primary-dark),
            var(--primary),
            var(--primary-light));
        }

        .bottom-bar {
            bottom: 0;
            background: var(--primary);
        }

        .title {
            color: var(--primary);
        }

        .card {
            border-color: var(--primary-light);
        }

        .page-inner {
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 22px;
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

        .header-right {
            text-align: right;
            color: var(--primary);
            padding-top: 8px;
        }

        .eyebrow {
            font-size: 11px;
            letter-spacing: 3px;
            font-weight: 700;
            color: var(--primary-light);
            text-transform: uppercase;
            line-height: 1;
            margin-bottom: 6px;
        }

        .title-main {
            font-size: 18px;
            line-height: 1.1;
            margin: 2px 0 6px;
            font-weight: 800;
            color: var(--primary);
        }

        .pill {
            display: inline-block;
            background: var(--primary);
            color: white;
            border-radius: 999px;
            padding: 6px 18px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            line-height: 1;
            min-width: 78px;
            text-align: center;
        }

        .logo-wrap {
            width: 132px;
            display: flex;
            align-items: flex-start;
        }

        .logo-mark {
            width: 58px;
            height: 58px;
            border: 5px solid var(--primary-light);
            border-radius: 50%;
            position: relative;
            margin-left: 22px;
            margin-bottom: 6px;
        }

        .logo-mark:before {
            content: "";
            position: absolute;
            left: 20px;
            top: 6px;
            width: 10px;
            height: 28px;
            background: var(--primary);
            clip-path: polygon(58% 0, 100% 0, 58% 44%, 100% 44%, 30% 100%, 42% 58%, 0 58%);
        }

        .logo-text {
            color: var(--primary-dark);
            font-weight: 800;
            font-size: 13px;
            line-height: 1;
            letter-spacing: .4px;
            text-align: left;
            margin-left: 2px;
        }

        .logo-text small {
            display: block;
            font-size: 5px;
            letter-spacing: 1.2px;
            margin-top: 3px;
            color: var(--primary-light);
            font-weight: 700;
        }

        .logo-image {
            width: 92px;
            height: auto;
            display: block;
        }

        .intro-strip {
            margin-top: 18px;
            background: var(--primary-soft);
            border-left: 4px solid var(--primary);
            color: var(--text-soft);
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 12px;
            font-style: italic;
        }

        .table-box {
            margin-top: 12px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--primary-border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .main-offer thead th {
            color: #fff;
            font-size: 11px;
            letter-spacing: 1.5px;
            padding: 10px 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .main-offer thead th:first-child {
            background: var(--primary-darker);
            width: 74px;
        }

        .main-offer thead th:nth-child(2) {
            background: var(--primary-dark);
        }

        .main-offer thead th:nth-child(3) {
            background: var(--primary-light);
        }

        .main-offer tbody td {
            text-align: center;
            padding: 12px 10px;
            font-size: 12px;
            color: #333;
            border-bottom: 1px solid var(--primary-border);
            font-weight: 700;
        }

        .main-offer tbody tr:last-child td {
            border-bottom: none;
        }

        .main-offer tbody td:first-child {
            background: var(--primary-dark);
            color: #fff;
            width: 74px;
            font-size: 16px;
            font-weight: 800;
        }

        .main-offer tbody tr:nth-child(odd) td:not(:first-child) {
            background: var(--primary-ultra-soft);
        }

        .main-offer tbody tr:nth-child(even) td:not(:first-child) {
            background: var(--primary-soft);
        }

        .section-line {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 12px 0 10px;
        }

        .section-line .label {
            color: var(--primary-light);
            font-weight: 800;
            letter-spacing: 3px;
            font-size: 12px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .section-line .line {
            height: 3px;
            background: var(--primary-light);
            flex: 1;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .card {
            border: 3px solid var(--primary-border);
            border-radius: 12px;
            background: transparent;
            padding: 14px 14px 12px;
            min-height: 70px;
        }

        .card.green {
            border-color: #6ace8c;
            background: #edf8f1;
        }

        .card-title {
            font-size: 11px;
            letter-spacing: 2px;
            color: var(--primary-light);
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .card.green .card-title {
            color: #38a95f;
        }

        .card-body {
            font-size: 12px;
            line-height: 1.45;
            color: #504c68;
        }

        .savings {
            font-size: 14px;
            font-weight: 800;
            color: #287f46;
            line-height: 1.5;
        }

        .savings * {
            color: #287f46;
            font-weight: 800;
        }

        .savings-red {
            font-size: 14px;
            font-weight: 800;
            color: #d64545;
        }

        .big-date {
            color: var(--primary-dark);
            font-weight: 900;
            font-size: 18px;
            line-height: 1;
            margin: 2px 0 6px;
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
            margin: 18px 0 14px;
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

        .footer-sep {
            height: 3px;
            background: var(--primary-border);
            margin-bottom: 18px;
        }

        .card,
        .table-box,
        .info-card,
        .chart-box,
        .compare-table {
            page-break-inside: avoid;
        }

        @media print {
            .footer {
                position: fixed;
                bottom: 20px;
                left: 20px;
                right: 20px;
            }
        }

        .advisor {
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateY(15px);
        }

        .avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 900;
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
            line-height: 1.6;
        }

        .contact strong {
            color: var(--primary);
        }

        .contact .web {
            color: var(--primary);
            font-weight: 800;
        }

        .contact .visit {
            color: #6e65a2;
            font-size: 11px;
            letter-spacing: 2px;
            font-weight: 800;
        }

        /* PAGE 2 */

        .header-secondary {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .secondary-title {
            text-align: right;
            color: var(--primary);
        }

        .secondary-title h2 {
            margin: 0;
            font-size: 25px;
            font-weight: 900;
            color: var(--primary-dark);
        }

        .secondary-title .sub {
            margin-top: 3px;
            font-size: 14px;
            letter-spacing: 1.6px;
            font-weight: 800;
            color: var(--primary-light);
            text-transform: uppercase;
        }

        .soft-divider {
            height: 3px;
            background: var(--primary-border);
            margin: 6px 0 10px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 12px;
        }

        .info-card {
            border: 3px solid var(--primary-border);
            border-radius: 12px;
            padding: 14px 18px;
            min-height: 132px;
            background: transparent;
        }

        .info-title {
            color: var(--primary-light);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 0;
            font-size: 13px;
            color: #4a4660;
            vertical-align: top;
        }

        .info-table td:first-child {
            width: 90px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .section-spaced {
            margin-top: 8px;
        }

        .section-caption {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 10px 0 8px;
        }

        .section-caption .txt {
            color: var(--primary-light);
            font-size: 12px;
            letter-spacing: 3px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .section-caption .rule {
            height: 2px;
            background: var(--primary-light);
            flex: 1;
        }

        .compare-table {
            width: 100%;
            margin-bottom: 8px;
        }

        .compare-table thead th {
            color: #fff;
            font-size: 8px;
            padding: 4px 5px;
            font-weight: 800;
            text-align: center;
        }

        .compare-table thead th:nth-child(1) {
            background: var(--primary-darker);
        }

        .compare-table thead th:nth-child(2) {
            background: var(--primary);
        }

        .compare-table thead th:nth-child(3) {
            background: var(--primary);
        }

        .compare-table thead th:nth-child(4) {
            background: var(--primary);
        }

        .compare-table thead th:nth-child(5) {
            background: var(--primary-dark);
        }

        .compare-table thead th:nth-child(6) {
            background: var(--primary-light);
        }

        .compare-table tbody td {
            font-size: 10px;
            padding: 4px 4px;
            text-align: center;
            border-bottom: 1px solid var(--primary-border);
            color: #35314c;
        }

        .compare-table tbody tr:nth-child(odd) td {
            background: var(--primary-ultra-soft);
        }

        .compare-table tbody tr:nth-child(even) td {
            background: var(--primary-soft);
        }

        .compare-table tbody td:first-child {
            font-weight: 900;
            color: var(--primary-dark);
            background: var(--primary-soft) !important;
        }

        .totals-table thead th:nth-child(1) {
            background: var(--primary-darker);
        }

        .totals-table thead th:nth-child(2) {
            background: var(--primary);
        }

        .totals-table thead th:nth-child(3) {
            background: var(--primary-light);
        }

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

        /* PAGE 3 */

        .summary-chips {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin: 18px 0 12px;
        }

        .chip {
            background: #efeff2;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .chip .chip-title {
            color: #b6b2c2;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .chip .chip-value {
            color: var(--primary-dark);
            font-weight: 900;
            font-size: 26px;
            line-height: 1;
        }

        .chart-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 10px 0 10px;
        }

        .chart-title .txt {
            color: var(--primary-light);
            font-size: 12px;
            letter-spacing: 3px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .chart-title .rule {
            height: 2px;
            background: var(--primary-light);
            flex: 1;
        }

        .chart-box {
            padding: 8px 8px 0;
            margin-bottom: 28px;
        }

        .legend {
            text-align: center;
            margin-top: 4px;
            font-size: 11px;
            color: #6a6780;
        }

        .legend span {
            display: inline-flex;
            align-items: center;
            margin: 0 10px;
            gap: 5px;
        }

        .legend i {
            width: 11px;
            height: 11px;
            border-radius: 2px;
            display: inline-block;
        }

        .resume-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }

        .resume-card {
            border-radius: 12px;
            padding: 14px 18px 12px;
            text-align: center;
            background: #efeff3;
            min-height: 80px;
        }

        .resume-card.purple {
            background: var(--primary-soft);
        }

        .resume-card.green {
            background: #e8f2ed;
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
            font-size: 22px;
            font-weight: 900;
            margin-top: 2px;
            line-height: 1.1;
        }

        .resume-card.green .k,
        .resume-card.green .v {
            color: #29a458;
        }

        .resume-card .s {
            margin-top: 2px;
            font-size: 10px;
            color: #8f88a5;
            font-weight: 700;
        }

        .resume-card.green .s {
            color: #59b47b;
        }

        .footer-inner {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .compare-table tfoot td {
            font-size: 10px;
            padding: 4px 4px;
            text-align: center;
            border-top: 1px solid var(--primary-border);
            background: var(--primary-ultra-soft);
            color: #35314c;
        }

        .compare-table tfoot tr:last-child td {
            font-weight: 900;
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
    <div class="top-bar"></div>
    <div class="bottom-bar"></div>
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
            @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
            <div class="footer">
                @if(!empty($agentName))
                <div class="advisor">
                    <div class="avatar">
                        {{ strtoupper(substr($agentName ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', trim($agentName ?? 'A B'))[1] ?? '', 0, 1)) }}
                    </div>
                    <div>
                        <div class="advisor-name">{{ $agentName }}</div>
                        <div class="advisor-role">Asesor energético</div>
                    </div>
                </div>
                @endif

                @if(!empty($agentPhone) || !empty($agentEmail))
                <div class="contact">
                    @if(!empty($agentPhone) && $data['basicData']['userSubdomain']['_id'] !== '6909faa9232c09035a03f3b2')
                        ☎ {{ $agentPhone }}

                        @if(($data['basicData']['userSubdomain']['_id'] ?? null) == '65cb57489c2c285441086a43')
                            / 957855980
                        @endif

                        <br>
                    @endif

                    @if(!empty($agentEmail) && $data['basicData']['userSubdomain']['_id'] !== '6909faa9232c09035a03f3b2')
                        {{ $agentEmail }}<br>
                    @endif

                </div>
                @endif
            </div>
            @endif

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
            @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
            <div class="footer">
                @if(!empty($agentName))
                <div class="advisor">
                    <div class="avatar">
                        {{ strtoupper(substr($agentName ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', trim($agentName ?? 'A B'))[1] ?? '', 0, 1)) }}
                    </div>
                    <div>
                        <div class="advisor-name">{{ $agentName }}</div>
                        <div class="advisor-role">Asesor energético</div>
                    </div>
                </div>
                @endif

                @if(!empty($agentPhone) || !empty($agentEmail))
                <div class="contact">
                    @if(!empty($agentPhone) && $data['basicData']['userSubdomain']['_id'] !== '6909faa9232c09035a03f3b2')
                        ☎ {{ $agentPhone }}

                        @if(($data['basicData']['userSubdomain']['_id'] ?? null) == '65cb57489c2c285441086a43')
                            / 957855980
                        @endif

                        <br>
                    @endif

                    @if(!empty($agentEmail) && $data['basicData']['userSubdomain']['_id'] !== '6909faa9232c09035a03f3b2')
                        {{ $agentEmail }}<br>
                    @endif

                </div>
                @endif
            </div>
            @endif

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


            @if(!empty($agentName) || !empty($agentPhone) || !empty($agentEmail))
            <div class="footer">
                @if(!empty($agentName))
                <div class="advisor">
                    <div class="avatar">
                        {{ strtoupper(substr($agentName ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', trim($agentName ?? 'A B'))[1] ?? '', 0, 1)) }}
                    </div>
                    <div>
                        <div class="advisor-name">{{ $agentName }}</div>
                        <div class="advisor-role">Asesor energético</div>
                    </div>
                </div>
                @endif

                @if(!empty($agentPhone) || !empty($agentEmail))
                <div class="contact">
                    @if(!empty($agentPhone) && $data['basicData']['userSubdomain']['_id'] !== '6909faa9232c09035a03f3b2')
                        ☎ {{ $agentPhone }}

                        @if(($data['basicData']['userSubdomain']['_id'] ?? null) == '65cb57489c2c285441086a43' )
                            / 957855980
                        @endif

                        <br>
                    @endif

                    @if(!empty($agentEmail) && $data['basicData']['userSubdomain']['_id'] !== '6909faa9232c09035a03f3b2')
                        {{ $agentEmail }}<br>
                    @endif

                </div>
                @endif
            </div>
            @endif



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
