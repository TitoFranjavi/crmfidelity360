@php
    // ── Paleta de empresas (idéntica a datadisReport) ────────────────────────
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

    if (!function_exists('adjustColorPO')) {
        function adjustColorPO($hex, $percent) {
            $hex = str_replace('#', '', $hex);
            $r   = hexdec(substr($hex, 0, 2));
            $g   = hexdec(substr($hex, 2, 2));
            $b   = hexdec(substr($hex, 4, 2));
            if ($percent > 0) {
                $r = $r + (255 - $r) * $percent / 100;
                $g = $g + (255 - $g) * $percent / 100;
                $b = $b + (255 - $b) * $percent / 100;
            } else {
                $r = $r * (1 + $percent / 100);
                $g = $g * (1 + $percent / 100);
                $b = $b * (1 + $percent / 100);
            }
            return sprintf('#%02x%02x%02x',
                min(255, max(0, (int)$r)),
                min(255, max(0, (int)$g)),
                min(255, max(0, (int)$b))
            );
        }
    }


    if (!function_exists('getPowerHeatColorPO')) {
        function getPowerHeatColorPO($value, $max) {
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
    $primaryDark      = adjustColorPO($baseColor, -35);
    $primaryDarker    = adjustColorPO($baseColor, -55);
    $primaryLight     = adjustColorPO($baseColor, 35);
    $primaryLighter   = adjustColorPO($baseColor, 60);
    $primarySoft      = adjustColorPO($baseColor, 90);
    $primaryUltraSoft = adjustColorPO($baseColor, 96);
    $primaryBorder    = adjustColorPO($baseColor, 80);

    if ($baseColor === '#000000') {
        $primaryLight  = '#666666';
        $primarySoft   = '#f2f2f2';
        $primaryBorder = '#dddddd';
    }

    // ── Periodos y colores fijos ─────────────────────────────────────────────
    $periods      = ['P1', 'P2', 'P3', 'P4', 'P5', 'P6'];
    $periodColors = ['#6366f1','#8b5cf6','#a78bfa','#c4b5fd','#ddd6fe','#ede9fe'];

    $greenDark   = '#1b5e20';
    $greenMid    = '#2e7d32';
    $greenSoft   = '#e8f5e9';
    $greenBorder = '#a5d6a7';
    $redColor    = '#c62828';
    $redSoft     = '#ffebee';
    $redBorder   = '#ef9a9a';

    // ── Iniciales agente ─────────────────────────────────────────────────────
    $agentParts    = preg_split('/\s+/', trim($agentName ?? ''));
    $agentInitials = strtoupper(
        substr($agentParts[0] ?? '', 0, 1) . substr($agentParts[1] ?? '', 0, 1)
    ) ?: '?';

    // ── Helpers de formato ───────────────────────────────────────────────────
    // Formatea un número con 2 decimales separador español
    $fmtNum = fn($v, $dec = 2) => number_format((float)$v, $dec, ',', '.');

    // ── Desglose: €/kW·mes = powerPrice[p] * 30 ─────────────────────────────
    // (idéntico a la fórmula del Vue: kW × price×30 × 1.019)
    $calcFixedBreakdown = function(string $p, float $kw) use ($powerPrice, $fmtNum): array {
        $pricePerMonth = $powerPrice[$p] * 30;
        $amount        = $kw * $pricePerMonth * 1.019;
        return [
            'kw'     => $kw,
            'price'  => $pricePerMonth,
            'amount' => $amount,
        ];
    };

    // ── Totales del simulador ────────────────────────────────────────────────
    // Calculamos coste/mes y ahorro/mes por periodo para el simulador
    // (misma lógica que calcCustomCost/calcCustomSaving del Vue corregido)
    $nReadings = max(1, count($monthlyReadings));

    $simulatorRows = [];
    foreach ($periods as $p) {
        $contracted    = (float)($customPowers[$p] ?? 0);
        $curContracted = (float)($currentPowers[$p] ?? 0);

        // Coste mensual medio con potencia custom
        $totalCustom = 0;
        $totalCurrent = 0;
        foreach ($monthlyReadings as $row) {
            $days   = (float)($row['days'] ?? 30);
            $demand = (float)($row['demand'][$p] ?? 0);

            $totalCustom += $contracted * $powerPrice[$p] * $days;
            if ($demand > $contracted) {
                $totalCustom += $tepp[$p] * ($demand - $contracted) * $days;
            }
            $totalCurrent += $curContracted * $powerPrice[$p] * $days;
            if ($demand > $curContracted) {
                $totalCurrent += $tepp[$p] * ($demand - $curContracted) * $days;
            }
        }

        $customMonthly  = $totalCustom  / $nReadings;
        $currentMonthly = $totalCurrent / $nReadings;

        $simulatorRows[$p] = [
            'maxDemand'      => (float)($maxDemand[$p] ?? 0),
            'currentKw'      => $curContracted,
            'customKw'       => $contracted,
            'costPerMonth'   => $customMonthly,
            'savingPerMonth' => $currentMonthly - $customMonthly,
        ];
    }

    $totalCurrentMonthly = array_sum(array_column($simulatorRows, 'costPerMonth'))
                         + array_sum(array_column($simulatorRows, 'savingPerMonth'));
    // (= suma de costPerMonth de la situación actual)
    // Easier: just sum currentMonthly directly
    $totalCurrentMonthlyCalc = array_sum(array_map(
        fn($r) => $r['costPerMonth'] + $r['savingPerMonth'], $simulatorRows
    ));
    $totalCustomMonthlyCalc  = array_sum(array_column($simulatorRows, 'costPerMonth'));
    $totalSavingMonthly      = array_sum(array_column($simulatorRows, 'savingPerMonth'));

    // ── Columnas de la tabla histórica ───────────────────────────────────────
    $histMaxDemand = array_fill_keys($periods, 0);
    foreach ($monthlyReadings as $row) {
        foreach ($periods as $p) {
            $v = (float)($row['demand'][$p] ?? 0);
            if ($v > $histMaxDemand[$p]) $histMaxDemand[$p] = $v;
        }
    }
    $historyHeatMax = max($histMaxDemand ?: [0]);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Informe de Optimización de Potencias</title>
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
            --green-dark:         {{ $greenDark }};
            --green-mid:          {{ $greenMid }};
            --green-soft:         {{ $greenSoft }};
            --green-border:       {{ $greenBorder }};
            --red:                {{ $redColor }};
            --red-soft:           {{ $redSoft }};
            --red-border:         {{ $redBorder }};
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

        .page + .page { page-break-before: always; break-before: page; }

        .top-bar, .bottom-bar { position: fixed; left: 0; right: 0; height: 8px; }
        .top-bar    { top: 0;    background: linear-gradient(90deg, var(--primary-dark), var(--primary), var(--primary-light)); }
        .bottom-bar { bottom: 0; background: var(--primary); }

        .page-inner { padding: 20px 22px; }

        .keep-together,
        .section-box,
        .comparison-grid,
        .footer,
        .simulator-box,
        .history-box {
            page-break-inside: avoid;
            break-inside: avoid-page;
        }

        /* ─── Header principal ─────────────────────────────────────────── */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 22px;
        }
        .logo-wrap  { width: 120px; display: flex; align-items: flex-start; }
        .logo-image { width: 88px; height: auto; display: block; }
        .header-right { text-align: right; }

        .eyebrow {
            font-size: 10px; letter-spacing: 3px; font-weight: 700;
            color: var(--primary-light); text-transform: uppercase;
            margin-bottom: 5px;
        }
        .title-main {
            font-size: 17px; font-weight: 800; color: var(--primary); margin: 2px 0 6px;
        }
        .pill {
            display: inline-block; background: var(--primary); color: #fff;
            border-radius: 999px; padding: 5px 16px;
            font-size: 11px; font-weight: 800; letter-spacing: 1px;
        }

        .soft-divider { height: 3px; background: var(--primary-border); margin: 10px 0; }

        .section-caption {
            display: flex; align-items: center; gap: 10px; margin: 12px 0 8px;
        }
        .section-caption .txt {
            color: var(--primary-light); font-size: 11px; letter-spacing: 3px;
            font-weight: 900; text-transform: uppercase; white-space: nowrap;
        }
        .section-caption .rule { height: 2px; background: var(--primary-light); flex: 1; }

        /* ─── KPI banner de ahorro ─────────────────────────────────────── */
        .saving-banner {
            background: var(--green-soft);
            border: 2px solid var(--green-border);
            border-radius: 14px;
            padding: 14px 22px;
            text-align: center;
            margin: 12px 0;
        }
        .saving-banner .label {
            font-size: 10px; font-weight: 700; letter-spacing: 2.5px;
            color: var(--green-mid); text-transform: uppercase; margin-bottom: 4px;
        }
        .saving-banner .amount {
            font-size: 32px; font-weight: 900; color: var(--green-dark); line-height: 1;
        }
        .saving-banner .sub {
            font-size: 11px; color: var(--green-mid); font-weight: 600; margin-top: 3px;
        }

        /* ─── Grid comparativa actual vs optimizada ────────────────────── */
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 4px 1fr;
            gap: 0;
            margin-top: 10px;
        }
        .comp-col { padding: 0 10px; }
        .comp-divider { background: var(--primary-border); }

        .comp-title {
            font-size: 12px; font-weight: 800; text-align: center;
            color: var(--primary-dark); margin-bottom: 10px;
            display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .comp-title.green { color: var(--green-dark); }

        /* Tabla desglose periodos */
        .breakdown-table {
            width: 100%; border-collapse: collapse; font-size: 10px;
        }
        .breakdown-table thead th {
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-weight: 800; font-size: 9px; letter-spacing: 1px;
            text-transform: uppercase; padding: 5px 6px;
            text-align: center;
            border-bottom: 2px solid var(--primary-border);
        }
        .breakdown-table thead th:first-child { text-align: left; }
        .breakdown-table tbody td {
            padding: 5px 6px; border-bottom: 1px solid var(--primary-border);
            color: #333; vertical-align: middle;
        }
        .breakdown-table tbody td:first-child {
            font-weight: 800; color: var(--primary-dark); font-size: 11px;
        }
        .breakdown-table tbody td.formula {
            font-size: 9px; color: #888; font-weight: 500;
        }
        .breakdown-table tbody td.amount {
            text-align: right; font-weight: 700; white-space: nowrap;
        }
        .breakdown-table tbody tr:nth-child(odd) td { background: var(--primary-ultra-soft); }

        /* Totales bajo la tabla */
        .comp-totals { margin-top: 10px; }
        .comp-total-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 4px 6px; border-radius: 6px;
        }
        .comp-total-row.excess .val { color: var(--red); }
        .comp-total-row.grand {
            background: var(--primary-soft); margin-top: 6px;
            border-radius: 8px; padding: 7px 10px;
        }
        .comp-total-row.grand.green { background: var(--green-soft); }
        .comp-total-row .lbl { font-size: 11px; color: #6b6b6b; }
        .comp-total-row .val { font-size: 11px; font-weight: 700; }
        .comp-total-row.grand .lbl { font-size: 13px; font-weight: 700; color: var(--primary-dark); }
        .comp-total-row.grand .val { font-size: 14px; font-weight: 900; color: var(--primary-dark); }
        .comp-total-row.grand.green .lbl,
        .comp-total-row.grand.green .val { color: var(--green-dark); }

        /* ─── Footer ───────────────────────────────────────────────────── */
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 14px; }
        .advisor { display: flex; align-items: center; gap: 10px; }
        .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: var(--primary); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 900;
        }
        .advisor-name  { color: var(--primary-dark); font-size: 12px; font-weight: 800; margin-bottom: 3px; }
        .advisor-role  { color: #9a97aa; font-size: 10px; }
        .contact       { text-align: right; font-size: 10px; color: #5d5870; line-height: 1.6; }
        .contact strong { color: var(--primary); }

        /* ─── Página 2: Simulador ──────────────────────────────────────── */
        .header-secondary {
            display: flex; justify-content: space-between; align-items: flex-start; margin-top: 20px;
        }
        .secondary-title { text-align: right; }
        .secondary-title h2 { margin: 0; font-size: 20px; font-weight: 900; color: var(--primary-dark); }
        .secondary-title .sub {
            margin-top: 3px; font-size: 11px; letter-spacing: 1.4px;
            font-weight: 800; color: var(--primary-light); text-transform: uppercase;
        }

        /* Tabla simulador */
        .sim-table { width: 100%; border-collapse: collapse; font-size: 10.5px; }
        .sim-table thead th {
            background: var(--primary);
            color: #fff; font-weight: 800; font-size: 9px;
            letter-spacing: 1px; text-transform: uppercase;
            padding: 8px 8px; text-align: center;
        }
        .sim-table thead th:first-child { border-radius: 8px 0 0 0; text-align: left; }
        .sim-table thead th:last-child  { border-radius: 0 8px 0 0; }
        .sim-table tbody td {
            padding: 7px 8px; border-bottom: 1px solid var(--primary-border);
            text-align: center; font-weight: 600; color: #333;
        }
        .sim-table tbody td:first-child {
            font-weight: 900; color: var(--primary-dark); font-size: 13px; text-align: left;
        }
        .sim-table tbody tr:nth-child(odd)  td { background: var(--primary-ultra-soft); }
        .sim-table tbody tr:nth-child(even) td { background: #fff; }
        .sim-table tbody td.excess { color: var(--red); font-weight: 700; }
        .sim-table tbody td.saving-pos { color: var(--green-mid); font-weight: 700; }
        .sim-table tbody td.saving-neg { color: var(--red); font-weight: 700; }
        .sim-table tfoot td {
            padding: 8px 8px; font-weight: 900; text-align: center;
            background: var(--primary-soft); color: var(--primary-dark);
            border-top: 2px solid var(--primary-border); font-size: 11px;
        }
        .sim-table tfoot td:first-child { text-align: left; }

        /* Bajo el simulador */
        .custom-saving-banner {
            margin-top: 14px; border-radius: 12px; padding: 14px 20px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .custom-saving-banner.green { background: var(--green-soft); border: 2px solid var(--green-border); }
        .custom-saving-banner.red   { background: var(--red-soft);   border: 2px solid var(--red-border); }
        .custom-saving-banner .lbl  { font-size: 10px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 4px; }
        .custom-saving-banner.green .lbl { color: var(--green-mid); }
        .custom-saving-banner.red   .lbl { color: var(--red); }
        .custom-saving-banner .val  { font-size: 22px; font-weight: 900; line-height: 1; }
        .custom-saving-banner.green .val { color: var(--green-dark); }
        .custom-saving-banner.red   .val { color: var(--red); }

        /* Tabla histórico de demanda */
        .hist-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        .hist-table thead th {
            background: var(--primary-darker); color: #fff;
            padding: 6px 5px; font-weight: 800; font-size: 8px;
            letter-spacing: 0.8px; text-transform: uppercase; text-align: center;
        }
        .hist-table thead th:first-child { border-radius: 8px 0 0 0; text-align: left; }
        .hist-table thead th:last-child  { border-radius: 0 8px 0 0; }
        .hist-table tbody td {
            padding: 4px 5px; border-bottom: 1px solid var(--primary-border);
            text-align: center; font-weight: 600; color: #333;
        }
        .hist-table tbody td:first-child { text-align: left; font-weight: 700; color: var(--primary-dark); }
        .hist-table tbody tr:nth-child(odd)  td { background: var(--primary-ultra-soft); }
        .hist-table tbody tr:nth-child(even) td { background: #fff; }
        .hist-table tbody td.over { color: var(--red); font-weight: 800; }
        .hist-table tfoot td {
            padding: 5px 5px; font-weight: 900; text-align: center;
            background: var(--primary-soft); color: var(--primary-dark);
            border-top: 2px solid var(--primary-border); font-size: 9px;
        }
        .hist-table tfoot td:first-child { text-align: left; }

        /* Badge cambio potencia */
        .badge-down { color: var(--green-mid); font-size: 9px; font-weight: 800; }
        .badge-up   { color: #e65100;          font-size: 9px; font-weight: 800; }
        .badge-eq   { color: #888;             font-size: 9px; font-weight: 700; }

        .table-box { border-radius: 10px; overflow: hidden; border: 1px solid var(--primary-border); }
        .mt-6  { margin-top: 6px; }
        .mt-10 { margin-top: 10px; }
        .mt-14 { margin-top: 14px; }

        .intro-strip {
            margin-top: 14px;
            background: var(--primary-soft);
            border-left: 4px solid var(--primary);
            border-radius: 8px; padding: 9px 14px;
            font-size: 11px; font-style: italic; color: #555;
        }

        /* Nota al pie en rojo si hay config custom diferente de óptima */
        .note-box {
            margin-top: 8px; border-radius: 8px; padding: 8px 12px;
            font-size: 10px; font-weight: 600;
        }
        .note-box.info { background: #fff8e1; border: 1.5px solid #ffe082; color: #5d4037; }


        /* Ajustes página 2: ocupar mejor el A4 sin partir el contenido */
        .page-2 .page-inner { padding: 18px 22px 12px; }
        .page-2 .header-secondary { margin-top: 14px; align-items: center; }
        .page-2 .logo-image { width: 86px; max-height: 48px; object-fit: contain; }
        .page-2 .secondary-title h2 { font-size: 20px; line-height: 1.08; }
        .page-2 .secondary-title .sub { margin-top: 3px; font-size: 9.5px; letter-spacing: 1.1px; }
        .page-2 .soft-divider { margin: 8px 0 9px; height: 2px; }
        .page-2 .section-caption { margin: 10px 0 6px; }
        .page-2 .section-caption .txt { font-size: 9.5px; letter-spacing: 2.3px; }
        .page-2 .mt-6 { margin-top: 5px; }
        .page-2 .mt-14 { margin-top: 10px; }
        .page-2 .note-box { margin-top: 6px; padding: 6px 10px; font-size: 8.8px; }
        .page-2 .table-box { border-radius: 8px; }

        .page-2 .sim-table { font-size: 9.1px; }
        .page-2 .sim-table thead th { padding: 5.5px 6px; font-size: 7.6px; letter-spacing: .6px; }
        .page-2 .sim-table tbody td { padding: 5px 6px; }
        .page-2 .sim-table tbody td:first-child { font-size: 11px; }
        .page-2 .sim-table tfoot td { padding: 5.5px 6px; font-size: 9.4px; }

        .page-2 .custom-saving-banner { margin-top: 10px; border-radius: 10px; padding: 10px 14px; }
        .page-2 .custom-saving-banner .lbl { font-size: 8.8px; letter-spacing: 1.5px; margin-bottom: 3px; }
        .page-2 .custom-saving-banner .val { font-size: 20px; }

        .page-2 .hist-table { font-size: 8.3px; }
        .page-2 .hist-table thead th { padding: 4.2px 4px; font-size: 7.1px; letter-spacing: .5px; }
        .page-2 .hist-table tbody td { padding: 3.1px 4px; }
        .page-2 .hist-table tfoot td { padding: 3.8px 4px; font-size: 7.8px; }

        .power-heatmap-box {
            border: 1px solid var(--primary-border);
            border-radius: 9px;
            overflow: hidden;
            padding: 8px 8px 5px;
            background: #fff;
        }
        .power-heatmap-title {
            font-size: 7.5px;
            color: #666;
            font-weight: 700;
            margin: 0 0 2px;
            text-align: right;
        }
        .page-2 .footer { margin-top: 14px; }
        .page-2 .avatar { width: 34px; height: 34px; font-size: 14px; }
        .page-2 .advisor { gap: 8px; }
        .page-2 .advisor-name { font-size: 10.5px; margin-bottom: 2px; }
        .page-2 .advisor-role { font-size: 8.5px; }
        .page-2 .contact { font-size: 8.8px; line-height: 1.45; }
    </style>
</head>
<body>
<div class="document">
    <div class="top-bar"></div>
    <div class="bottom-bar"></div>

    {{-- ════════════════════════════════════════════════════════════════════
         PÁGINA 1 — Comparativa situación actual vs optimizada
    ═════════════════════════════════════════════════════════════════════ --}}
    <section class="page">
        <div class="page-inner">

            {{-- Header --}}
            <div class="header keep-together">
                <div class="logo-wrap">
                    @if($logoUrl)
                        <img class="logo-image" src="{{ $logoUrl }}" alt="Logo">
                    @endif
                </div>
                <div class="header-right">
                    <div class="eyebrow">OPTIMIZACIÓN DE POTENCIAS · TARIFA 3.0TD</div>
                    <div class="title-main">Informe de optimización eléctrica</div>
                    <div class="pill">{{ $cups }}</div>
                </div>
            </div>

            <div class="intro-strip keep-together">
                Análisis de la potencia contratada frente a la demanda real histórica,
                con la configuración óptima calculada para minimizar el coste anual del suministro
                <strong>{{ $cups }}</strong>.
                Informe generado el <strong>{{ now()->format('d/m/Y') }}</strong>.
            </div>

            {{-- Banner ahorro --}}
            <div class="saving-banner keep-together">
                <div class="label">Ahorro estimado anual</div>
                <div class="amount">
                    @if($saving >= 0)
                        {{ $fmtNum($saving) }} €
                    @else
                        −{{ $fmtNum(abs($saving)) }} €
                    @endif
                </div>
                <div class="sub">({{ $fmtNum($saving / 12) }} €/mes)</div>
            </div>

            {{-- Comparativa actual vs optimizada --}}
            <div class="section-caption">
                <div class="txt">Comparativa de costes de potencia</div>
                <div class="rule"></div>
            </div>

            <div class="comparison-grid keep-together">

                {{-- ── Situación actual ── --}}
                <div class="comp-col">
                    <div class="comp-title">
                        <span style="color:var(--red);">✕</span> Situación actual
                    </div>
                    <p style="font-size:10px;color:#888;margin:0 0 6px;">Término de potencia (kW × €/kW·mes × 1,019)</p>
                    <div class="table-box">
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>Per.</th>
                                    <th>kW</th>
                                    <th>Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($periods as $p)
                                @php
                                    $bd = $calcFixedBreakdown($p, (float)($currentPowers[$p] ?? 0));
                                @endphp
                                <tr>
                                    <td>{{ $p }}</td>
                                    <td style="text-align:center;">{{ $fmtNum($bd['kw'], 1) }}</td>
                                    <td class="amount">{{ $fmtNum($bd['amount']) }} €</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="comp-totals mt-6">
                        <div class="comp-total-row">
                            <span class="lbl">Término fijo</span>
                            <span class="val">{{ $fmtNum($currentCost['fixed']) }} €</span>
                        </div>
                        <div class="comp-total-row excess">
                            <span class="lbl">Excesos de potencia</span>
                            <span class="val" style="color:var(--red);">{{ $fmtNum($currentCost['excess']) }} €</span>
                        </div>
                        <div class="comp-total-row grand">
                            <span class="lbl">Total</span>
                            <span class="val">{{ $fmtNum($currentCost['total']) }} €</span>
                        </div>
                    </div>
                </div>

                {{-- Divisor vertical --}}
                <div class="comp-divider"></div>

                {{-- ── Situación optimizada ── --}}
                <div class="comp-col">
                    <div class="comp-title green">
                        <span>✔</span> Situación optimizada
                    </div>
                    <p style="font-size:10px;color:#888;margin:0 0 6px;">Término de potencia (kW × €/kW·mes × 1,019)</p>
                    <div class="table-box">
                        <table class="breakdown-table">
                            <thead>
                                <tr>
                                    <th>Per.</th>
                                    <th>kW</th>
                                    <th>Importe</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($periods as $p)
                                @php
                                    $bd     = $calcFixedBreakdown($p, (float)($optimizedPowers[$p] ?? 0));
                                    $curKw  = (float)($currentPowers[$p] ?? 0);
                                    $optKw  = (float)($optimizedPowers[$p] ?? 0);
                                    $delta  = $optKw - $curKw;
                                    $amtStyle = $delta < 0
                                        ? 'color:var(--green-mid);'
                                        : ($delta > 0 ? 'color:#e65100;' : '');
                                @endphp
                                <tr>
                                    <td>{{ $p }}</td>
                                    <td style="text-align:center;">
                                        {{ $fmtNum($bd['kw'], 1) }}
                                        @if($delta < 0)
                                            <span class="badge-down">▼{{ $fmtNum(abs($delta), 1) }}</span>
                                        @elseif($delta > 0)
                                            <span class="badge-up">▲{{ $fmtNum($delta, 1) }}</span>
                                        @else
                                            <span class="badge-eq">=</span>
                                        @endif
                                    </td>
                                    <td class="amount" style="{{ $amtStyle }}">{{ $fmtNum($bd['amount']) }} €</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="comp-totals mt-6">
                        <div class="comp-total-row">
                            <span class="lbl">Término fijo</span>
                            <span class="val">{{ $fmtNum($optimizedCost['fixed']) }} €</span>
                        </div>
                        <div class="comp-total-row excess">
                            <span class="lbl">Excesos de potencia</span>
                            <span class="val" style="color:var(--red);">{{ $fmtNum($optimizedCost['excess']) }} €</span>
                        </div>
                        <div class="comp-total-row grand green">
                            <span class="lbl">Total</span>
                            <span class="val">{{ $fmtNum($optimizedCost['total']) }} €</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer página 1 --}}
            <div class="footer keep-together">
                <div class="advisor">
                    <div class="avatar">{{ $agentInitials }}</div>
                    <div>
                        <div class="advisor-name">{{ $agentName ?: '—' }}</div>
                        <div class="advisor-role">Asesor energético</div>
                    </div>
                </div>
                <div class="contact">
                    @if(!empty($agentPhone)) ☎ {{ $agentPhone }}<br> @endif
                    @if(!empty($agentEmail)) ✉ {{ $agentEmail }}<br> @endif
                </div>
            </div>

        </div>
    </section>


    {{-- ════════════════════════════════════════════════════════════════════
         PÁGINA 2 — Simulador + Demanda histórica por lectura
    ═════════════════════════════════════════════════════════════════════ --}}
    <section class="page page-2">
        <div class="page-inner">

            <div class="header-secondary keep-together">
                <div class="logo-wrap">
                    @if($logoUrl)
                        <img class="logo-image" src="{{ $logoUrl }}" alt="Logo">
                    @endif
                </div>
                <div class="secondary-title">
                    <h2>Simulador de Potencias</h2>
                    <div class="sub">CONFIGURACIÓN PERSONALIZADA · {{ $cups }}</div>
                </div>
            </div>
            <div class="soft-divider"></div>

            {{-- Nota si la config personalizada difiere de la óptima --}}
            @php
                $customDiffersFromOptimal = false;
                foreach($periods as $p) {
                    if ((float)($customPowers[$p] ?? 0) !== (float)($optimizedPowers[$p] ?? 0)) {
                        $customDiffersFromOptimal = true;
                        break;
                    }
                }
            @endphp
            @if($customDiffersFromOptimal)
                <div class="note-box info keep-together">
                    ⚠ La configuración del simulador ha sido modificada por el asesor y puede diferir de los valores óptimos calculados.
                </div>
            @endif

            {{-- Tabla simulador --}}
            <div class="section-caption">
                <div class="txt">Potencias y costes por periodo</div>
                <div class="rule"></div>
            </div>

            <div class="table-box simulator-box mt-6">
                <table class="sim-table">
                    <thead>
                        <tr>
                            <th>Periodo</th>
                            <th>Demanda máx.</th>
                            <th>Contratada actual</th>
                            <th>Óptima calc.</th>
                            <th>Config. informe</th>
                            <th>Coste/mes</th>
                            <th>Ahorro/mes</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($periods as $p)
                        @php
                            $row        = $simulatorRows[$p];
                            $curKw      = $row['currentKw'];
                            $optKw      = (float)($optimizedPowers[$p] ?? 0);
                            $cusKw      = $row['customKw'];
                            $maxDem     = $row['maxDemand'];
                            $cost       = $row['costPerMonth'];
                            $saving_p   = $row['savingPerMonth'];
                            $isExcess   = $maxDem > $curKw;
                        @endphp
                        <tr>
                            <td>{{ $p }}</td>
                            <td class="{{ $isExcess ? 'excess' : '' }}">
                                {{ $fmtNum($maxDem, 1) }} kW
                                @if($isExcess) <span style="font-size:9px;"> ⚠</span> @endif
                            </td>
                            <td>{{ $fmtNum($curKw, 1) }} kW</td>
                            <td>{{ $fmtNum($optKw, 1) }} kW</td>
                            <td>
                                <strong>{{ $fmtNum($cusKw, 1) }} kW</strong>
                            </td>
                            <td>{{ $fmtNum($cost) }} €</td>
                            <td class="{{ $saving_p >= 0 ? 'saving-pos' : 'saving-neg' }}">
                                {{ $saving_p >= 0 ? '' : '-' }}{{ $fmtNum(abs($saving_p)) }} €
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total</td>
                            <td></td>
                            <td>{{ $fmtNum($totalCurrentMonthlyCalc) }} €/mes</td>
                            <td></td>
                            <td>{{ $fmtNum($totalCustomMonthlyCalc) }} €/mes</td>
                            <td>{{ $fmtNum($totalCustomMonthlyCalc) }} €</td>
                            <td class="{{ $totalSavingMonthly >= 0 ? 'saving-pos' : 'saving-neg' }}">
                                {{ $totalSavingMonthly >= 0 ? '' : '-' }}{{ $fmtNum(abs($totalSavingMonthly)) }} €
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Banner ahorro anual simulador --}}
            @php $isPositive = $customAnnualSaving >= 0; @endphp
            <div class="custom-saving-banner {{ $isPositive ? 'green' : 'red' }} keep-together">
                <div>
                    <div class="lbl">Ahorro anual estimado con esta configuración</div>
                    <div class="val">
                        {{ $isPositive ? '' : '-' }}{{ $fmtNum(abs($customAnnualSaving)) }} €/año
                    </div>
                </div>
                @if(!$isPositive)
                    <div style="font-size:10px;color:var(--red);font-weight:700;max-width:200px;text-align:right;">
                        ⚠ Esta configuración incrementa el coste respecto a la situación actual.
                    </div>
                @endif
            </div>

            {{-- Tabla demanda histórica por lectura --}}
            <div class="section-caption mt-14">
                <div class="txt">Demanda histórica por lectura</div>
                <div class="rule"></div>
            </div>

            <div class="table-box history-box mt-6">
                <table class="hist-table">
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Días</th>
                            @foreach($periods as $p)
                                <th>{{ $p }} (kW)</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($monthlyReadings as $reading)
                        <tr>
                            <td>{{ $reading['label'] }}</td>
                            <td style="text-align:center;">{{ $reading['days'] }}</td>
                            @foreach($periods as $p)
                                @php
                                    $dem     = (float)($reading['demand'][$p] ?? 0);
                                    $curKwH  = (float)($currentPowers[$p] ?? 0);
                                    $isOver  = $dem > $curKwH;
                                @endphp
                                <td class="{{ $isOver ? 'over' : '' }}">
                                    {{ $fmtNum($dem, 1) }}
                                    @if($isOver) <span style="font-size:8px;">⚠</span> @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Máx. histórico</td>
                            <td>—</td>
                            @foreach($periods as $p)
                                <td>{{ $fmtNum($histMaxDemand[$p], 1) }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Mapa de calor demanda histórica --}}
            <div class="section-caption mt-14">
                <div class="txt">Mapa de calor de demanda</div>
                <div class="rule"></div>
            </div>

            <div class="power-heatmap-box keep-together">
                @php
                    $powerHeatRows = max(count($monthlyReadings ?? []), 1);
                    $powerHeatStartX = 84;
                    $powerHeatStartY = 20;
                    $powerHeatCellW = 80;
                    $powerHeatCellH = 24;

                    if ($powerHeatRows >= 18) {
                        $powerHeatCellH = 15;
                    } elseif ($powerHeatRows >= 14) {
                        $powerHeatCellH = 17;
                    } elseif ($powerHeatRows >= 10) {
                        $powerHeatCellH = 21;
                    }

                    $powerHeatValueFontSize = $powerHeatRows >= 18 ? 8.5 : ($powerHeatRows >= 14 ? 9 : 10.2);
                    $powerHeatRowFontSize = $powerHeatRows >= 14 ? 7.4 : 8.2;
                    $powerHeatHeaderFontSize = 8.4;
                    $powerHeatSvgWidth = 610;
                    $powerHeatScaleY = $powerHeatStartY + ($powerHeatRows * $powerHeatCellH) + 18;
                    $powerHeatSvgHeight = $powerHeatScaleY + 18;
                    $powerHeatScaleW = count($periods) * $powerHeatCellW - 1;
                @endphp

                <svg width="100%" height="{{ $powerHeatSvgHeight }}" viewBox="0 0 {{ $powerHeatSvgWidth }} {{ $powerHeatSvgHeight }}" preserveAspectRatio="xMidYMid meet">
                    <defs>
                        <linearGradient id="powerHeatGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#fff36a"/>
                            <stop offset="30%" stop-color="#ffd966"/>
                            <stop offset="55%" stop-color="#ffa94d"/>
                            <stop offset="75%" stop-color="#ff7a3d"/>
                            <stop offset="100%" stop-color="#ff1f1f"/>
                        </linearGradient>
                    </defs>

                    {{-- Cabeceras P1-P6 --}}
                    @foreach($periods as $colIndex => $p)
                        <text
                            x="{{ $powerHeatStartX + ($colIndex * $powerHeatCellW) + ($powerHeatCellW / 2) }}"
                            y="12"
                            font-size="{{ $powerHeatHeaderFontSize }}"
                            fill="#333333"
                            text-anchor="middle"
                            font-weight="800">
                            {{ $p }}
                        </text>
                    @endforeach

                    {{-- Filas por lectura --}}
                    @foreach(($monthlyReadings ?? []) as $rowIndex => $reading)
                        <text
                            x="76"
                            y="{{ $powerHeatStartY + ($rowIndex * $powerHeatCellH) + ($powerHeatCellH / 2) }}"
                            font-size="{{ $powerHeatRowFontSize }}"
                            fill="#222222"
                            text-anchor="end"
                            dominant-baseline="middle"
                            font-weight="600">
                            {{ $reading['label'] }}
                        </text>

                        @foreach($periods as $colIndex => $p)
                            @php
                                $value = (float)($reading['demand'][$p] ?? 0);
                                $x = $powerHeatStartX + ($colIndex * $powerHeatCellW);
                                $y = $powerHeatStartY + ($rowIndex * $powerHeatCellH);
                                $color = getPowerHeatColorPO($value, $historyHeatMax);
                                $ratio = $historyHeatMax > 0 ? $value / $historyHeatMax : 0;
                                $textColor = $ratio >= 0.70 ? '#111827' : '#1f2937';
                                $displayValue = $value > 0 ? $fmtNum($value, 1) : '';
                            @endphp

                            <rect
                                x="{{ $x }}"
                                y="{{ $y }}"
                                width="{{ $powerHeatCellW - 1 }}"
                                height="{{ $powerHeatCellH - 1 }}"
                                rx="0"
                                fill="{{ $color }}"
                                stroke="#ffffff"
                                stroke-width="0.7"/>

                            @if($displayValue !== '')
                                <text
                                    x="{{ $x + (($powerHeatCellW - 1) / 2) }}"
                                    y="{{ $y + (($powerHeatCellH - 1) / 2) }}"
                                    font-size="{{ $powerHeatValueFontSize }}"
                                    fill="{{ $textColor }}"
                                    text-anchor="middle"
                                    dominant-baseline="middle"
                                    font-weight="900">
                                    {{ $displayValue }}
                                </text>
                            @endif
                        @endforeach
                    @endforeach

                    {{-- Escala inferior --}}
                    <text
                        x="{{ $powerHeatStartX + 4 }}"
                        y="{{ $powerHeatScaleY - 5 }}"
                        font-size="7"
                        fill="#333333"
                        text-anchor="start">
                        0 kW
                    </text>
                    <text
                        x="{{ $powerHeatStartX + $powerHeatScaleW - 4 }}"
                        y="{{ $powerHeatScaleY - 5 }}"
                        font-size="7"
                        fill="#333333"
                        text-anchor="end">
                        {{ $fmtNum($historyHeatMax, 1) }} kW
                    </text>
                    <rect
                        x="{{ $powerHeatStartX }}"
                        y="{{ $powerHeatScaleY }}"
                        width="{{ $powerHeatScaleW }}"
                        height="6"
                        rx="0"
                        fill="url(#powerHeatGradient)"/>
                </svg>
            </div>

            {{-- Footer página 2 --}}
            <div class="footer keep-together">
                <div class="advisor">
                    <div class="avatar">{{ $agentInitials }}</div>
                    <div>
                        <div class="advisor-name">{{ $agentName ?: '—' }}</div>
                        <div class="advisor-role">Asesor energético</div>
                    </div>
                </div>
                <div class="contact">
                    @if(!empty($agentPhone)) ☎ {{ $agentPhone }}<br> @endif
                    @if(!empty($agentEmail)) ✉ {{ $agentEmail }}<br> @endif
                </div>
            </div>

        </div>
    </section>

</div>
</body>
</html>