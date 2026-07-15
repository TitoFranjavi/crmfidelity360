@php
    $colors = [
        'zocoEnergiaColor'          => '#e40613',
        'asercordColor'             => '#012C68',
        'tecumColor'                => '#e9511c',
        'newpulsoColor'             => '#263294',
        'koasolucionesColor'        => '#192249',
        'grupoNazariColor'          => '#ec602d',
        'ahorroYSolutionsColor'     => '#000000',
        'valereConsultoresColor'    => '#000000',
        'btvinstalacionesColor'     => '#18297a',
        'vtcomColor'                => '#0071c1',
        'ajasesoresColor'           => '#002b45',
        'ahorrodirectColor'         => '#ec1d23',
        'lumigasenergiaColor'       => '#4ba11e',
        'assessoria30Color'         => '#9edfb9',
        'iberelectricaColor'        => '#2f4392',
        'vimelColor'                => '#012C68',
        'localuzColor'              => '#e40613',
        'loviluzColor'              => '#d78c0c',
        'vivivanColor'              => '#2d2e83',
        'wconsultoresColor'         => '#ff9323',
        'doiveColor'                => '#2a367e',
        'tecumconsultoresColor'     => '#fa4d09',
        'tweliColor'                => '#38b6ff',
        'aluzygasColor'             => '#1ca33c',
        'viceasesoresColor'         => '#0b324b',
        'valfryxColor'              => '#ff7f2a',
        'gruposuperaColor'          => '#ffd100',
        'energianorteColor'         => '#5ea22c',
        'ceustradeColor'            => '#002060',
        'efuturaColor'              => '#03989e',
        'solbyColor'                => '#f07e14',
        'fotonasesoresColor'        => '#4268be',
        'fidelity360Color'          => '#9929dd',
        'coliseumenergiaColor'      => '#884794',
        'energiaprimenoroesteColor' => '#f8b334',
        'onexenergiaColor'          => '#00ab6a',
        'barriozubietaColor'        => '#272453',
        'enerwatiaColor'            => '#a87eb0',
    ];

    $companyColorKey = $basicData['enterprise']['color'] ?? 'zocoEnergiaColor';
    $baseColor       = $colors[$companyColorKey] ?? '#000000';

    if (!function_exists('adjustColor')) {
        function adjustColor($hex, $percent) {
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

            $r = min(255, max(0, (int)$r));
            $g = min(255, max(0, (int)$g));
            $b = min(255, max(0, (int)$b));

            return sprintf("#%02x%02x%02x", $r, $g, $b);
        }
    }

    if (!function_exists('getHeatColor')) {
        function getHeatColor($value, $max) {
            $ratio = $max > 0 ? $value / $max : 0;

            if ($ratio >= 0.85) return '#ff1f1f';
            if ($ratio >= 0.70) return '#ff3b2f';
            if ($ratio >= 0.55) return '#ff7a3d';
            if ($ratio >= 0.40) return '#ffa94d';
            if ($ratio >= 0.25) return '#ffd966';
            if ($ratio > 0)     return '#fff36a';

            return '#fffde7';
        }
    }

    $primary          = $baseColor;
    $primaryDark      = adjustColor($baseColor, -35);
    $primaryDarker    = adjustColor($baseColor, -55);
    $primaryLight     = adjustColor($baseColor, 35);
    $primaryLighter   = adjustColor($baseColor, 60);
    $primarySoft      = adjustColor($baseColor, 90);
    $primaryUltraSoft = adjustColor($baseColor, 96);
    $primaryBorder    = adjustColor($baseColor, 80);

    if ($baseColor === '#000000') {
        $primaryLight  = '#666666';
        $primarySoft   = '#f2f2f2';
        $primaryBorder = '#dddddd';
    }

    $valleColor = '#38bdf8';
    $llanoColor = '#fbbf24';
    $puntaColor = '#f97316';

    $periodLabels = ['day' => 'Diario', 'isoWeek' => 'Semanal', 'month' => 'Mensual'];
    $periodLabel  = $periodLabels[$dateType] ?? $dateType;

    $totalKwh = array_sum(array_column($donutSeries, 'value'));

    $stackedMax = 0;
    foreach (($stackedChartSeries ?? []) as $bar) {
        $sum = ($bar['low'] ?? 0) + ($bar['mid'] ?? 0) + ($bar['high'] ?? 0);
        if ($sum > $stackedMax) $stackedMax = $sum;
    }
    $stackedMax = $stackedMax > 0 ? $stackedMax : 1;

    $heatMax = 0;
    foreach (($heatmapSeries ?? []) as $row) {
        foreach (($row['values'] ?? []) as $value) {
            if ($value > $heatMax) $heatMax = $value;
        }
    }
    $heatMax = $heatMax > 0 ? $heatMax : 1;

    $chartBaseY  = 145;
    $chartTopY   = 24;
    $chartHeight = $chartBaseY - $chartTopY;
    $chartStartX = 50;
    $chartEndX   = 690;
    $chartWidth  = $chartEndX - $chartStartX;
    $barCount    = count($stackedChartSeries ?? []);
    $spacing     = $barCount > 0 ? $chartWidth / $barCount : 1;
    $barW        = max(min($spacing * 0.7, 24), 4);

    $xAxisLabel = ['day' => 'Hora', 'isoWeek' => 'Día de la semana', 'month' => 'Día del mes'][$dateType] ?? '';

    $agentParts    = preg_split('/\s+/', trim($agentName ?? ''));
    $agentInitials = strtoupper(
        substr($agentParts[0] ?? '', 0, 1) . substr($agentParts[1] ?? '', 0, 1)
    ) ?: '?';

    $comparison = $comparison ?? ($priceComparison ?? null);
    $comparisonPeriods = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6'];

    $fmtMoney = function ($value, $decimals = 2) {
        return number_format((float)($value ?? 0), $decimals, ',', '.');
    };

    $fmtKwh = function ($value, $decimals = 2) {
        return number_format((float)($value ?? 0), $decimals, ',', '.');
    };

    $getSavingClass = function ($value) {
        $value = (float)($value ?? 0);

        if ($value > 0) {
            return 'saving-positive';
        }

        if ($value < 0) {
            return 'saving-negative';
        }

        return 'saving-neutral';
    };

    $getComparisonValue = function ($array, $key, $default = 0) {
        if ($array instanceof \Illuminate\Support\Collection) {
            $array = $array->toArray();
        }

        if (is_object($array)) {
            $array = (array)$array;
        }

        if (!is_array($array)) {
            return $default;
        }

        return $array[$key] ?? $array[strtolower($key)] ?? $default;
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Informe de Consumo</title>

    <style>
        :root {
            --primary:            {{ $primary }};
            --primary-dark:       {{ $primaryDark }};
            --primary-darker:     {{ $primaryDarker }};
            --primary-light:      {{ $primaryLight }};
            --primary-lighter:    {{ $primaryLighter }};
            --primary-soft:       {{ $primarySoft }};
            --primary-ultra-soft: {{ $primaryUltraSoft }};
            --primary-border:     {{ $primaryBorder }};
            --text:               #2d2d2d;
            --text-soft:          #6b6b6b;
            --text-muted:         #8a8a8a;
            --bg-card:            #ffffff;
            --valle:              {{ $valleColor }};
            --llano:              {{ $llanoColor }};
            --punta:              {{ $puntaColor }};
        }

        @page { margin: 0; size: A4; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #fff;
            font-family: 'Montserrat', sans-serif;
            color: var(--text);
            font-size: 12px;
        }

        .document { width: 100%; padding: 10px 0 30px; }

        .page { width: 100%; }
        .page + .page {
            page-break-before: always;
            break-before: page;
        }

        .top-bar, .bottom-bar { position: fixed; left: 0; right: 0; height: 8px; }
        .top-bar { top: 0; background: linear-gradient(90deg, var(--primary-dark), var(--primary), var(--primary-light)); }
        .bottom-bar { bottom: 0; background: var(--primary); }

        .page-inner { padding: 20px; }

        .keep-together,
        .table-box,
        .info-card,
        .chart-box,
        .resume-grid,
        .footer,
        .kpi-grid {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 22px;
        }

        .logo-wrap { width: 132px; display: flex; align-items: flex-start; }
        .logo-image { width: 92px; height: auto; display: block; }
        .header-right { text-align: right; color: var(--primary); padding-top: 8px; }

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
        }

        .header-secondary {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 20px;
            margin-bottom: 8px;
        }

        .secondary-title { text-align: right; color: var(--primary); }

        .secondary-title h2 {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
            color: var(--primary-dark);
        }

        .secondary-title .sub {
            margin-top: 3px;
            font-size: 13px;
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

        .kpi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 12px;
            margin-bottom: 12px;
        }

        .kpi-card {
            border: 3px solid var(--primary-border);
            border-radius: 12px;
            padding: 14px 18px;
            text-align: center;
            background: var(--primary-ultra-soft);
        }

        .kpi-label {
            font-size: 10px;
            letter-spacing: 2px;
            font-weight: 800;
            color: var(--primary-light);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--primary-dark);
            line-height: 1;
        }

        .kpi-unit {
            font-size: 11px;
            color: var(--primary-light);
            font-weight: 700;
            margin-top: 4px;
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
            min-height: 120px;
        }

        .info-title {
            color: var(--primary-light);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .info-table { width: 100%; border-collapse: collapse; }

        .info-table td {
            padding: 2px 0;
            font-size: 12px;
            color: #4a4660;
            vertical-align: top;
        }

        .info-table td:first-child {
            width: 90px;
            font-weight: 800;
            color: var(--primary-dark);
        }

        .table-box {
            margin-top: 10px;
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid var(--primary-border);
        }

        table { width: 100%; border-collapse: collapse; }

        .tariff-table thead th {
            color: #fff;
            font-size: 9px;
            letter-spacing: 1.5px;
            padding: 8px 10px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
        }

        .tariff-table thead th:nth-child(1) { background: var(--primary-darker); }
        .tariff-table thead th:nth-child(2) { background: var(--primary); }
        .tariff-table thead th:nth-child(3) { background: var(--primary); }
        .tariff-table thead th:nth-child(4) { background: var(--primary-light); }

        .tariff-table tbody td {
            text-align: center;
            padding: 9px 10px;
            font-size: 11px;
            color: #333;
            border-bottom: 1px solid var(--primary-border);
            font-weight: 700;
        }

        .tariff-table tbody tr:last-child td { border-bottom: none; }

        .tariff-table tbody td:first-child {
            background: var(--primary-dark);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
            width: 70px;
        }

        .tariff-table tbody tr:nth-child(odd) td:not(:first-child) { background: var(--primary-ultra-soft); }
        .tariff-table tbody tr:nth-child(even) td:not(:first-child) { background: var(--primary-soft); }

        .tariff-table tfoot td {
            padding: 8px 10px;
            text-align: center;
            font-size: 11px;
            font-weight: 900;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border-top: 2px solid var(--primary-border);
        }

        .tariff-table tfoot td:first-child { background: var(--primary); color: #fff; }

        .dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 4px;
            vertical-align: middle;
        }

        .chart-box { padding: 4px 6px 0; margin-bottom: 6px; }

        .legend {
            text-align: center;
            margin-top: 4px;
            font-size: 10px;
            color: #6a6780;
        }

        .legend span {
            display: inline-flex;
            align-items: center;
            margin: 0 8px;
            gap: 5px;
        }

        .legend i {
            width: 10px;
            height: 10px;
            border-radius: 2px;
            display: inline-block;
        }

        .resume-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .resume-card {
            border-radius: 12px;
            padding: 14px 18px 12px;
            text-align: center;
            background: #efeff3;
            min-height: 72px;
        }

        .resume-card.purple { background: var(--primary-soft); }
        .resume-card.green  { background: #e8f2ed; }

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

        .resume-card.green .s { color: #59b47b; }

        .detail-table thead th {
            color: #fff;
            font-size: 9px;
            letter-spacing: 1.2px;
            padding: 8px 6px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
        }

        .detail-table thead th:nth-child(1) { background: var(--primary-darker); }
        .detail-table thead th:nth-child(2) { background: {{ $valleColor }}; }
        .detail-table thead th:nth-child(3) { background: {{ $llanoColor }}; color: #3d2e00; }
        .detail-table thead th:nth-child(4) { background: {{ $puntaColor }}; }
        .detail-table thead th:nth-child(5) { background: var(--primary-dark); }

        .detail-table tbody td {
            text-align: center;
            padding: 6px 6px;
            font-size: 10px;
            color: #333;
            border-bottom: 1px solid var(--primary-border);
            font-weight: 600;
        }

        .detail-table tbody tr:last-child td { border-bottom: none; }
        .detail-table tbody td:first-child { font-weight: 800; color: var(--primary-dark); }
        .detail-table tbody tr:nth-child(odd) td { background: var(--primary-ultra-soft); }
        .detail-table tbody tr:nth-child(even) td { background: var(--primary-soft); }

        .detail-table tfoot td {
            padding: 8px 6px;
            text-align: center;
            font-size: 11px;
            font-weight: 900;
            background: var(--primary-soft);
            color: var(--primary-dark);
            border-top: 2px solid var(--primary-border);
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 12px;
        }

        .advisor {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
        }

        .advisor-name {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .advisor-role {
            color: #9a97aa;
            font-size: 11px;
        }

        .contact {
            text-align: right;
            font-size: 11px;
            color: #5d5870;
            line-height: 1.6;
        }

        .contact strong { color: var(--primary); }

        .claim {
            margin: 16px 0 12px;
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
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 4px;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .page-compact .page-inner { padding: 14px 18px; }
        .page-compact .header-secondary { margin-top: 10px; margin-bottom: 6px; }
        .page-compact .logo-wrap { width: 110px; }
        .page-compact .logo-image { width: 78px; }

        .page-compact .secondary-title h2 {
            font-size: 18px;
            line-height: 1.05;
        }

        .page-compact .secondary-title .sub {
            font-size: 10px;
            margin-top: 2px;
            letter-spacing: 1.2px;
        }

        .page-compact .soft-divider {
            height: 2px;
            margin: 4px 0 8px;
        }

        .page-compact .resume-grid {
            grid-template-columns: 1fr 1fr 1fr;
            gap: 7px;
            margin-top: 6px;
            margin-bottom: 8px !important;
        }

        .page-compact .resume-card {
            border-radius: 9px;
            padding: 8px 10px;
            min-height: 52px;
        }

        .page-compact .resume-card .k {
            font-size: 8px;
            letter-spacing: 1.2px;
        }

        .page-compact .resume-card .v {
            font-size: 16px;
            margin-top: 1px;
            line-height: 1.05;
        }

        .page-compact .resume-card .s {
            margin-top: 1px;
            font-size: 8px;
        }

        .page-compact .section-caption {
            margin: 5px 0 6px;
            gap: 8px;
        }

        .page-compact .section-caption .txt {
            font-size: 10px;
            letter-spacing: 2px;
        }

        .page-compact .section-caption .rule { height: 1.5px; }
        .page-compact .table-box { margin-top: 6px; border-radius: 8px; }

        .page-compact .detail-table thead th {
            padding: 5px 4px;
            font-size: 7.5px;
            letter-spacing: 0.8px;
            line-height: 1.15;
        }

        .page-compact .detail-table tbody td {
            padding: 3.5px 4px;
            font-size: 8.5px;
            line-height: 1.15;
        }

        .page-compact .detail-table tfoot td {
            padding: 5px 4px;
            font-size: 9px;
            line-height: 1.15;
        }

        .page-compact .footer { margin-top: 12px !important; }

        .page-compact .avatar {
            width: 34px;
            height: 34px;
            font-size: 16px;
        }

        .page-compact .advisor { gap: 9px; }

        .page-compact .advisor-name {
            font-size: 11px;
            margin-bottom: 2px;
        }

        .page-compact .advisor-role { font-size: 9px; }

        .page-compact .contact {
            font-size: 9px;
            line-height: 1.4;
        }

        /* ─── Página comparativa precios Datadis ─── */
        .price-page .page-inner {
            padding: 16px 18px;
        }

        .price-note {
            margin-top: 8px;
            background: var(--primary-ultra-soft);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 9px 12px;
            color: var(--text-soft);
            font-size: 10px;
            line-height: 1.45;
        }

        .price-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 8px;
            margin: 10px 0;
        }

        .price-summary-card {
            border: 2px solid var(--primary-border);
            border-radius: 10px;
            padding: 9px 10px;
            background: var(--primary-ultra-soft);
            min-height: 64px;
        }

        .price-summary-card .label {
            color: var(--primary-light);
            font-size: 8px;
            font-weight: 900;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .price-summary-card .value {
            color: var(--primary-dark);
            font-size: 15px;
            font-weight: 900;
            line-height: 1.15;
        }

        .price-summary-card .small-value {
            color: #4a4660;
            font-size: 9.5px;
            font-weight: 800;
            line-height: 1.25;
        }

        .best-product-box {
            border: 2px solid var(--primary-border);
            background: var(--primary-soft);
            border-radius: 12px;
            padding: 10px 12px;
            margin: 8px 0 10px;
        }

        .best-product-title {
            color: var(--primary-dark);
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1.4px;
        }

        .price-table {
            width: 100%;
            border-collapse: collapse;
        }

        .price-table th {
            background: var(--primary-dark);
            color: #fff;
            font-size: 8px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 6px 5px;
            text-align: center;
            font-weight: 900;
        }

        .price-table td {
            font-size: 8.8px;
            padding: 5px 5px;
            text-align: center;
            border-bottom: 1px solid var(--primary-border);
            color: #333;
            font-weight: 600;
        }

        .price-table tbody tr:nth-child(odd) td { background: var(--primary-ultra-soft); }
        .price-table tbody tr:nth-child(even) td { background: var(--primary-soft); }

        .price-table td:first-child {
            text-align: left;
            font-weight: 900;
            color: var(--primary-dark);
        }

        .price-table .total-row td {
            background: var(--primary);
            color: #fff;
            font-weight: 900;
        }

        .price-table td.saving-positive,
        .price-table td.saving-negative,
        .price-table td.saving-neutral {
            font-weight: 900;
        }

        .price-saving-badge {
            display: inline-block;
            min-width: 78px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 8.5px;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
        }

        .saving-positive .price-saving-badge {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .saving-negative .price-saving-badge {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .saving-neutral .price-saving-badge {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .price-muted {
            color: var(--text-muted);
            font-size: 9px;
            line-height: 1.4;
            margin-top: 6px;
        }
    </style>
</head>

<body>
<div class="document">
    <div class="top-bar"></div>
    <div class="bottom-bar"></div>

    <section class="page">
        <div class="page-inner">
            <div class="header keep-together">
                <div class="logo-wrap">
                    <img class="logo-image" src="{{ $logoUrl }}" alt="Logo">
                </div>

                <div class="header-right">
                    <div class="eyebrow">INFORME DE CONSUMO — {{ strtoupper($periodLabel) }}</div>
                    <div class="title-main">{{ $dateLabel }}</div>
                    <div class="pill">{{ $supply['cups'] }}</div>
                </div>
            </div>

            <div class="intro-strip keep-together">
                Informe de consumo eléctrico para el suministro ·
                <strong>{{ $supply['address'] }}</strong> ·
                {{ $supply['municipality'] }}, {{ $supply['province'] }}
            </div>

            <div class="info-grid keep-together" style="margin-top:14px;">
                <div class="info-card">
                    <div class="info-title">Punto de suministro</div>
                    <table class="info-table">
                        <tr><td>CUPS:</td><td>{{ $supply['cups'] }}</td></tr>
                        <tr><td>Dirección:</td><td>{{ $supply['address'] }}</td></tr>
                        <tr><td>Municipio:</td><td>{{ $supply['municipality'] }} ({{ $supply['province'] }})</td></tr>
                        <tr><td>C. Postal:</td><td>{{ $supply['postalCode'] }}</td></tr>
                        <tr><td>Tarifa:</td><td>{{ $contract['codeFare'] ?? '—' }}</td></tr>
                    </table>
                </div>

                <div class="info-card">
                    <div class="info-title">Período analizado</div>
                    <table class="info-table">
                        <tr><td>Tipo:</td><td>{{ $periodLabel }}</td></tr>
                        <tr><td>Período:</td><td>{{ $dateLabel }}</td></tr>
                        <tr><td>Generado:</td><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
                        <tr><td>Intervalos:</td><td>{{ count($stackedChartSeries) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="section-caption">
                <div class="txt">Resumen de consumo</div>
                <div class="rule"></div>
            </div>

            <div class="kpi-grid keep-together">
                <div class="kpi-card">
                    <div class="kpi-label">Consumo total</div>
                    <div class="kpi-value">{{ number_format($totalConsumption, 2, ',', '.') }}</div>
                    <div class="kpi-unit">kWh</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Consumo medio {{ $dateType === 'day' ? 'por hora' : 'diario' }}</div>
                    <div class="kpi-value">{{ number_format($consumptionPerInterval, 2, ',', '.') }}</div>
                    <div class="kpi-unit">kWh / {{ $dateType === 'day' ? 'hora' : 'día' }}</div>
                </div>
            </div>

            <div class="section-caption" style="margin-top:6px;">
                <div class="txt">Desglose por períodos tarifarios</div>
                <div class="rule"></div>
            </div>

            <div class="table-box keep-together">
                <table class="tariff-table">
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>kWh</th>
                            <th>%</th>
                            <th>Distribución</th>
                        </tr>
                    </thead>

                    <tbody>
                    @php $tarifColors = [$valleColor, $llanoColor, $puntaColor]; @endphp

                    @foreach($donutSeries as $i => $period)
                        @php $pct = $totalKwh > 0 ? ($period['value'] / $totalKwh) * 100 : 0; @endphp

                        <tr>
                            <td>
                                <span class="dot" style="background:{{ $tarifColors[$i] ?? $primary }};"></span>
                                {{ $period['category'] }}
                            </td>
                            <td>{{ number_format($period['value'], 2, ',', '.') }}</td>
                            <td>{{ number_format($pct, 1, ',', '.') }}%</td>
                            <td style="padding: 4px 10px;">
                                <div style="background:#e5e7eb; border-radius:4px; height:10px; width:100%;">
                                    <div style="background:{{ $tarifColors[$i] ?? $primary }}; width:{{ number_format($pct, 1, '.', '') }}%; height:10px; border-radius:4px;"></div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <td>TOTAL</td>
                            <td>{{ number_format($totalKwh, 2, ',', '.') }}</td>
                            <td>100%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="claim keep-together">
                <div class="line"></div>
                <div class="text">Datos de consumo eléctrico</div>
                <div class="line"></div>
            </div>

            <div class="footer keep-together">
                <div class="advisor">
                    <div class="avatar">{{ $agentInitials }}</div>
                    <div>
                        <div class="advisor-name">{{ $agentName ?? '—' }}</div>
                        <div class="advisor-role">Asesor energético</div>
                    </div>
                </div>

                <div class="contact">
                    @if(!empty($agentPhone)) ☎ {{ $agentPhone }}<br> @endif
                    @if(!empty($agentEmail)) {{ $agentEmail }}<br> @endif
                </div>
            </div>
        </div>
    </section>

    <section class="page">
        <div class="page-inner">
            <div class="header-secondary keep-together">
                <div class="logo-wrap">
                    <img class="logo-image" src="{{ $logoUrl }}" alt="Logo">
                </div>

                <div class="secondary-title">
                    <h2>Análisis Visual del Consumo</h2>
                    <div class="sub">{{ strtoupper($periodLabel) }} · {{ $dateLabel }}</div>
                </div>
            </div>

            <div class="soft-divider"></div>

            <div class="section-caption">
                <div class="txt">Consumo por {{ $xAxisLabel }} (kWh)</div>
                <div class="rule"></div>
            </div>

            <div class="chart-box keep-together">
                <svg width="740" height="172" viewBox="0 0 740 172">
                    @php
                        $gridSteps = 4;
                        $axisX     = 44;
                    @endphp

                    @for($s = 0; $s <= $gridSteps; $s++)
                        @php
                            $gy   = $chartBaseY - ($chartHeight / $gridSteps) * $s;
                            $gval = ($stackedMax / $gridSteps) * $s;
                        @endphp

                        <line x1="{{ $axisX }}" y1="{{ $gy }}" x2="{{ $chartEndX + 4 }}" y2="{{ $gy }}"
                              stroke="{{ $primaryBorder }}" stroke-width="1"/>

                        <text x="{{ $axisX - 4 }}" y="{{ $gy + 3 }}" font-size="8" fill="#9d98aa" text-anchor="end">
                            {{ number_format($gval, 0, ',', '.') }}
                        </text>
                    @endfor

                    @foreach($stackedChartSeries as $i => $bar)
                        @php
                            $bX    = $axisX + 6 + $i * $spacing + ($spacing - $barW) / 2;
                            $low   = $bar['low']  ?? 0;
                            $mid   = $bar['mid']  ?? 0;
                            $high  = $bar['high'] ?? 0;
                            $lowH  = ($low  / $stackedMax) * $chartHeight;
                            $midH  = ($mid  / $stackedMax) * $chartHeight;
                            $highH = ($high / $stackedMax) * $chartHeight;
                            $bY    = $chartBaseY;
                        @endphp

                        @if($lowH > 0.1)
                            <rect x="{{ $bX }}" y="{{ $bY - $lowH }}" width="{{ $barW }}" height="{{ $lowH }}" rx="2" fill="{{ $valleColor }}"/>
                            @php $bY -= $lowH; @endphp
                        @endif

                        @if($midH > 0.1)
                            <rect x="{{ $bX }}" y="{{ $bY - $midH }}" width="{{ $barW }}" height="{{ $midH }}" rx="2" fill="{{ $llanoColor }}"/>
                            @php $bY -= $midH; @endphp
                        @endif

                        @if($highH > 0.1)
                            <rect x="{{ $bX }}" y="{{ $bY - $highH }}" width="{{ $barW }}" height="{{ $highH }}" rx="2" fill="{{ $puntaColor }}"/>
                        @endif

                        @if($barCount <= 31)
                            <text x="{{ $bX + $barW / 2 }}" y="{{ $chartBaseY + 12 }}" font-size="7"
                                  fill="#6b6b6b" text-anchor="middle">{{ $bar['date'] }}</text>
                        @endif
                    @endforeach

                    <line x1="{{ $axisX + 6 }}" y1="{{ $chartTopY }}" x2="{{ $axisX + 6 }}" y2="{{ $chartBaseY }}"
                          stroke="#2d2d2d" stroke-width="1"/>

                    <line x1="{{ $axisX + 6 }}" y1="{{ $chartBaseY }}" x2="{{ $chartEndX + 4 }}" y2="{{ $chartBaseY }}"
                          stroke="#2d2d2d" stroke-width="1"/>
                </svg>

                <div class="legend">
                    <span><i style="background:{{ $valleColor }};"></i> {{ $donutSeries[0]['category'] ?? 'Valle' }}</span>
                    <span><i style="background:{{ $llanoColor }};"></i> {{ $donutSeries[1]['category'] ?? 'Llano' }}</span>
                    <span><i style="background:{{ $puntaColor }};"></i> {{ $donutSeries[2]['category'] ?? 'Punta' }}</span>
                </div>
            </div>

            <div class="section-caption" style="margin-top:6px;">
                <div class="txt">Mapa de calor por hora</div>
                <div class="rule"></div>
            </div>

            <div class="chart-box keep-together">
                @php
                    $heatRows = max(count($heatmapSeries ?? []), 1);
                    $heatStartX = 86;
                    $heatStartY = 30;
                    $heatCellW = 27;
                    $heatCellH = 20;

                    if ($heatRows >= 20) {
                        $heatCellH = 15;
                    } elseif ($heatRows >= 14) {
                        $heatCellH = 17;
                    }

                    $heatValueFontSize = $heatRows <= 10 ? 8 : ($heatRows <= 18 ? 7 : 6);
                    $heatRowFontSize   = $heatRows <= 16 ? 8 : 7;
                    $heatHourFontSize  = 7;
                    $heatSvgHeight = $heatStartY + ($heatRows * $heatCellH) + 42;
                @endphp

                <svg width="780" height="{{ $heatSvgHeight }}" viewBox="0 0 780 {{ $heatSvgHeight }}" preserveAspectRatio="xMidYMid meet">
                    @for($h = 0; $h < 24; $h++)
                        <text
                            x="{{ $heatStartX + ($h * $heatCellW) + ($heatCellW / 2) }}"
                            y="18"
                            font-size="{{ $heatHourFontSize }}"
                            fill="#333333"
                            text-anchor="middle"
                            font-weight="500">
                            {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}
                        </text>
                    @endfor

                    @foreach(($heatmapSeries ?? []) as $rowIndex => $row)
                        <text
                            x="72"
                            y="{{ $heatStartY + ($rowIndex * $heatCellH) + ($heatCellH / 2) + 3 }}"
                            font-size="{{ $heatRowFontSize }}"
                            fill="#222222"
                            text-anchor="end"
                            font-weight="500">
                            {{ $row['rowLabel'] }}
                        </text>

                        @foreach(($row['values'] ?? []) as $hour => $value)
                            @php
                                $x = $heatStartX + ($hour * $heatCellW);
                                $y = $heatStartY + ($rowIndex * $heatCellH);
                                $color = getHeatColor($value, $heatMax);
                                $ratio = $heatMax > 0 ? $value / $heatMax : 0;
                                $textColor = '#1f2937';

                                if ($value >= 100) {
                                    $displayValue = number_format($value, 0, ',', '.');
                                } elseif ($value > 0) {
                                    $displayValue = number_format($value, 0, ',', '.');
                                } else {
                                    $displayValue = '';
                                }
                            @endphp

                            <rect
                                x="{{ $x }}"
                                y="{{ $y }}"
                                width="{{ $heatCellW - 1 }}"
                                height="{{ $heatCellH - 1 }}"
                                rx="0"
                                fill="{{ $color }}"
                                stroke="#ffffff"
                                stroke-width="0.7"/>

                            @if($displayValue !== '')
                                <text
                                    x="{{ $x + (($heatCellW - 1) / 2) }}"
                                    y="{{ $y + (($heatCellH - 1) / 2) - 1 }}"
                                    font-size="{{ $heatValueFontSize }}"
                                    fill="{{ $textColor }}"
                                    text-anchor="middle"
                                    font-weight="800">
                                    {{ $displayValue }}
                                </text>

                                <text
                                    x="{{ $x + (($heatCellW - 1) / 2) }}"
                                    y="{{ $y + (($heatCellH - 1) / 2) + 8 }}"
                                    font-size="{{ max($heatValueFontSize - 1, 5) }}"
                                    fill="#4b5563"
                                    text-anchor="middle"
                                    font-weight="600">
                                    {{ $row['periods'][$hour] ?? '' }}
                                </text>
                            @endif
                        @endforeach
                    @endforeach

                    @php
                        $scaleX = $heatStartX;
                        $scaleY = $heatStartY + ($heatRows * $heatCellH) + 24;
                        $scaleW = 24 * $heatCellW - 1;
                    @endphp

                    <defs>
                        <linearGradient id="heatGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#fff36a"/>
                            <stop offset="30%" stop-color="#ffd966"/>
                            <stop offset="55%" stop-color="#ffa94d"/>
                            <stop offset="75%" stop-color="#ff7a3d"/>
                            <stop offset="100%" stop-color="#ff1f1f"/>
                        </linearGradient>
                    </defs>

                    <text x="{{ $scaleX + 4 }}" y="{{ $scaleY - 6 }}" font-size="7" fill="#333333" text-anchor="start">
                        {{ number_format(0, 0, ',', '.') }}
                    </text>

                    <text x="{{ $scaleX + $scaleW - 4 }}" y="{{ $scaleY - 6 }}" font-size="7" fill="#333333" text-anchor="end">
                        {{ number_format($heatMax, 0, ',', '.') }}
                    </text>

                    <rect x="{{ $scaleX }}" y="{{ $scaleY }}" width="{{ $scaleW }}" height="8" rx="0" fill="url(#heatGradient)"/>
                </svg>
            </div>

            <div class="footer keep-together">
                <div class="advisor">
                    <div class="avatar">{{ $agentInitials }}</div>
                    <div>
                        <div class="advisor-name">{{ $agentName ?? '—' }}</div>
                        <div class="advisor-role">Asesor energético</div>
                    </div>
                </div>

                <div class="contact">
                    @if(!empty($agentPhone)) ☎ {{ $agentPhone }}<br> @endif
                    @if(!empty($agentEmail)) {{ $agentEmail }}<br> @endif
                </div>
            </div>
        </div>
    </section>

    @if(count($stackedChartSeries) <= 31)
        <section class="page page-compact">
            <div class="page-inner">
                <div class="header-secondary keep-together">
                    <div class="logo-wrap">
                        <img class="logo-image" src="{{ $logoUrl }}" alt="Logo">
                    </div>

                    <div class="secondary-title">
                        <h2>Detalle de Consumo</h2>
                        <div class="sub">{{ strtoupper($xAxisLabel) }} · {{ $dateLabel }}</div>
                    </div>
                </div>

                <div class="soft-divider"></div>

                <div class="resume-grid keep-together" style="margin-bottom:8px;">
                    <div class="resume-card">
                        <div class="k">Consumo total</div>
                        <div class="v">{{ number_format($totalConsumption, 2, ',', '.') }} kWh</div>
                        <div class="s">período completo</div>
                    </div>

                    <div class="resume-card purple">
                        <div class="k">Consumo medio</div>
                        <div class="v">{{ number_format($consumptionPerInterval, 2, ',', '.') }} kWh</div>
                        <div class="s">por {{ $dateType === 'day' ? 'hora' : 'día' }}</div>
                    </div>

                    <div class="resume-card green">
                        <div class="k">Intervalos</div>
                        <div class="v">{{ count($stackedChartSeries) }}</div>
                        <div class="s">{{ $xAxisLabel }}</div>
                    </div>
                </div>

                <div class="section-caption">
                    <div class="txt">Datos por {{ $xAxisLabel }}</div>
                    <div class="rule"></div>
                </div>

                <div class="table-box keep-together">
                    <table class="detail-table">
                        <thead>
                            <tr>
                                <th>{{ $xAxisLabel }}</th>
                                <th>{{ $donutSeries[0]['category'] ?? 'Valle' }} (kWh)</th>
                                <th>{{ $donutSeries[1]['category'] ?? 'Llano' }} (kWh)</th>
                                <th>{{ $donutSeries[2]['category'] ?? 'Punta' }} (kWh)</th>
                                <th>Total (kWh)</th>
                            </tr>
                        </thead>

                        <tbody>
                        @foreach($stackedChartSeries as $bar)
                            @php
                                $low  = $bar['low']  ?? 0;
                                $mid  = $bar['mid']  ?? 0;
                                $high = $bar['high'] ?? 0;
                                $rowT = $low + $mid + $high;
                            @endphp

                            <tr>
                                <td>{{ $bar['date'] }}</td>
                                <td>{{ number_format($low,  2, ',', '.') }}</td>
                                <td>{{ number_format($mid,  2, ',', '.') }}</td>
                                <td>{{ number_format($high, 2, ',', '.') }}</td>
                                <td><strong>{{ number_format($rowT, 2, ',', '.') }}</strong></td>
                            </tr>
                        @endforeach
                        </tbody>

                        <tfoot>
                            @php
                                $totLow  = array_sum(array_column($stackedChartSeries, 'low'));
                                $totMid  = array_sum(array_column($stackedChartSeries, 'mid'));
                                $totHigh = array_sum(array_column($stackedChartSeries, 'high'));
                            @endphp

                            <tr>
                                <td>TOTAL</td>
                                <td>{{ number_format($totLow,  2, ',', '.') }}</td>
                                <td>{{ number_format($totMid,  2, ',', '.') }}</td>
                                <td>{{ number_format($totHigh, 2, ',', '.') }}</td>
                                <td>{{ number_format($totLow + $totMid + $totHigh, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="footer keep-together" style="margin-top:12px;">
                    <div class="advisor">
                        <div class="avatar">{{ $agentInitials }}</div>

                        <div>
                            <div class="advisor-name">{{ $agentName ?? '—' }}</div>
                            <div class="advisor-role">Asesor energético</div>
                        </div>
                    </div>

                    <div class="contact">
                        @if(!empty($agentPhone)) ☎ {{ $agentPhone }}<br> @endif
                        @if(!empty($agentEmail)) {{ $agentEmail }}<br> @endif
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if(!empty($comparison) && !empty($comparison['current']))
        <section class="page page-compact price-page">
            <div class="page-inner">
                <div class="header-secondary keep-together">
                    <div class="logo-wrap">
                        <img class="logo-image" src="{{ $logoUrl }}" alt="Logo">
                    </div>

                    <div class="secondary-title">
                        <h2>Comparativa de Precios</h2>
                        <div class="sub">{{ strtoupper($periodLabel) }} · {{ $dateLabel }}</div>
                    </div>
                </div>

                <div class="soft-divider"></div>

                <div class="price-note keep-together">
                    Comparativa calculada con el consumo real obtenido desde Datadis para el periodo
                    <strong>{{ $comparison['dateLabel'] ?? $dateLabel }}</strong>.
                    Los importes son estimados y no incluyen impuestos, alquileres ni otros conceptos si no están contemplados en el producto.
                </div>

                <div class="price-summary-grid keep-together">
                    <div class="price-summary-card">
                        <div class="label">Contrato actual</div>
                        <div class="small-value">
                            {{ $comparison['current']['marketer'] ?? '—' }}<br>
                            {{ $comparison['current']['product'] ?? 'Producto actual' }}
                        </div>
                    </div>

                    <div class="price-summary-card">
                        <div class="label">Tarifa</div>
                        <div class="value">{{ $comparison['fee'] ?? ($contract['codeFare'] ?? '—') }}</div>
                    </div>

                    <div class="price-summary-card">
                        <div class="label">Coste actual</div>
                        <div class="value">{{ $fmtMoney($comparison['current']['total'] ?? 0) }} €</div>
                    </div>

                    <div class="price-summary-card">
                        <div class="label">Mejor producto</div>
                        <div class="value">
                            @if(!empty($comparison['bestOffer']))
                                {{ $fmtMoney($comparison['bestOffer']['total'] ?? 0) }} €
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if(!empty($comparison['bestOffer']))
                    <div class="best-product-box keep-together">
                        <div class="best-product-title">Producto recomendado</div>

                        <table class="price-table">
                            <tbody>
                                <tr>
                                    <td>Comercializadora</td>
                                    <td>{{ $comparison['bestOffer']['marketer'] ?? '—' }}</td>
                                </tr>

                                <tr>
                                    <td>Producto</td>
                                    <td>{{ $comparison['bestOffer']['product'] ?? '—' }}</td>
                                </tr>

                                <tr>
                                    <td>Ahorro estimado</td>
                                    <td class="{{ $getSavingClass($comparison['bestOffer']['save'] ?? 0) }}">
                                        <span class="price-saving-badge">
                                            {{ $fmtMoney($comparison['bestOffer']['save'] ?? 0) }} €
                                            —
                                            {{ $fmtMoney($comparison['bestOffer']['savePercent'] ?? 0) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="section-caption">
                    <div class="txt">Consumo usado para comparar</div>
                    <div class="rule"></div>
                </div>

                <div class="table-box keep-together">
                    <table class="price-table">
                        <thead>
                            <tr>
                                @foreach($comparisonPeriods as $period)
                                    <th>{{ $period }}</th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                @foreach($comparisonPeriods as $period)
                                    <td>{{ $fmtKwh($getComparisonValue($comparison['periodConsumption'] ?? [], $period)) }} kWh</td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section-caption">
                    <div class="txt">Detalle contrato actual</div>
                    <div class="rule"></div>
                </div>

                <div class="table-box keep-together">
                    <table class="price-table">
                        <thead>
                            <tr>
                                <th>Concepto</th>
                                @foreach($comparisonPeriods as $period)
                                    <th>{{ $period }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Potencia</td>

                                @foreach($comparisonPeriods as $period)
                                    <td>{{ $fmtMoney($getComparisonValue($comparison['current']['subTotal']['power'] ?? [], $period)) }} €</td>
                                @endforeach

                                <td>{{ $fmtMoney($getComparisonValue($comparison['current']['subTotal']['power'] ?? [], 'total')) }} €</td>
                            </tr>

                            <tr>
                                <td>Energía</td>

                                @foreach($comparisonPeriods as $period)
                                    <td>{{ $fmtMoney($getComparisonValue($comparison['current']['subTotal']['energy'] ?? [], $period)) }} €</td>
                                @endforeach

                                <td>{{ $fmtMoney($getComparisonValue($comparison['current']['subTotal']['energy'] ?? [], 'total')) }} €</td>
                            </tr>

                            <tr class="total-row">
                                <td colspan="7">Total contrato actual</td>
                                <td>{{ $fmtMoney($comparison['current']['total'] ?? 0) }} €</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="section-caption">
                    <div class="txt">Productos comparados</div>
                    <div class="rule"></div>
                </div>

                @if(!empty($comparison['topOffers']))
                    <div class="table-box keep-together">
                        <table class="price-table">
                            <thead>
                                <tr>
                                    <th>Comercializadora</th>
                                    <th>Producto</th>
                                    <th>Potencia</th>
                                    <th>Energía</th>
                                    <th>Total</th>
                                    <th>Ahorro</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($comparison['topOffers'] as $offer)
                                    <tr>
                                        <td>{{ $offer['marketer'] ?? '—' }}</td>
                                        <td>{{ $offer['product'] ?? '—' }}</td>
                                        <td>{{ $fmtMoney($getComparisonValue($offer['subTotal']['power'] ?? [], 'total')) }} €</td>
                                        <td>{{ $fmtMoney($getComparisonValue($offer['subTotal']['energy'] ?? [], 'total')) }} €</td>
                                        <td><strong>{{ $fmtMoney($offer['total'] ?? 0) }} €</strong></td>
                                        <td class="{{ $getSavingClass($offer['save'] ?? 0) }}">
                                            <span class="price-saving-badge">
                                                {{ $fmtMoney($offer['save'] ?? 0) }} €
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="price-muted">
                        No se han encontrado productos disponibles para comparar con esta tarifa.
                    </div>
                @endif

                <div class="price-muted">
                    Esta comparativa se realiza contra los productos configurados en el sistema y usando los precios aplicables
                    al consumo real del periodo seleccionado.
                </div>

                <div class="footer keep-together" style="margin-top:12px;">
                    <div class="advisor">
                        <div class="avatar">{{ $agentInitials }}</div>

                        <div>
                            <div class="advisor-name">{{ $agentName ?? '—' }}</div>
                            <div class="advisor-role">Asesor energético</div>
                        </div>
                    </div>

                    <div class="contact">
                        @if(!empty($agentPhone)) ☎ {{ $agentPhone }}<br> @endif
                        @if(!empty($agentEmail)) {{ $agentEmail }}<br> @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
</body>
</html>