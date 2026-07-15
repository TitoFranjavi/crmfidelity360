@php
    $colors = [
        'zocoEnergiaColor'           => '#e40613',
        'asercordColor'              => '#012C68',
        'tecumColor'                 => '#e9511c',
        'newpulsoColor'              => '#263294',
        'koasolucionesColor'         => '#192249',
        'grupoNazariColor'           => '#ec602d',
        'ahorroYSolutionsColor'      => '#000000',
        'valereConsultoresColor'     => '#000000',
        'btvinstalacionesColor'      => '#18297a',
        'vtcomColor'                 => '#0071c1',
        'ajasesoresColor'            => '#002b45',
        'ahorrodirectColor'          => '#ec1d23',
        'lumigasenergiaColor'        => '#009b94',
        'assessoria30Color'          => '#9edfb9',
        'iberelectricaColor'         => '#2f4392',
        'vimelColor'                 => '#012C68',
        'localuzColor'               => '#e40613',
        'loviluzColor'               => '#d78c0c',
        'vivivanColor'               => '#2d2e83',
        'wconsultoresColor'          => '#ff9323',
        'doiveColor'                 => '#2a367e',
        'tecumconsultoresColor'      => '#fa4d09',
        'tweliColor'                 => '#38b6ff',
        'aluzygasColor'              => '#1ca33c',
        'viceasesoresColor'          => '#0b324b',
        'valfryxColor'               => '#ff7f2a',
        'gruposuperaColor'           => '#ffd100',
        'energianorteColor'          => '#5ea22c',
        'ceustradeColor'             => '#002060',
        'efuturaColor'               => '#03989e',
        'solbyColor'                 => '#f07e14',
        'fotonasesoresColor'         => '#4268be',
        'fidelity360Color'           => '#9929dd',
        'coliseumenergiaColor'       => '#884794',
        'energiaprimenoroesteColor'  => '#f8b334',
        'onexenergiaColor'           => '#00ab6a',
        'barriozubietaColor'         => '#272453',
        'enerwatiaColor'             => '#a87eb0',
    ];

    $companyColorKey = $data['basicData']['enterprise']['color'] ?? 'zocoEnergiaColor';
    $baseColor       = $colors[$companyColorKey] ?? '#e40613';

    function adjustColorSA($hex, $percent) {
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

    $primary        = $baseColor;
    $primaryDark    = adjustColorSA($baseColor, -35);
    $primaryDarker  = adjustColorSA($baseColor, -55);
    $primaryLight   = adjustColorSA($baseColor, 35);
    $primarySoft    = adjustColorSA($baseColor, 90);
    $primaryBorder  = adjustColorSA($baseColor, 80);

    if ($baseColor === '#000000') {
        $primaryLight  = '#666666';
        $primarySoft   = '#f2f2f2';
        $primaryBorder = '#dddddd';
    }

    $cupsPower    = $data['cupsData']['power'] ?? [];
    $consumptions = array_map(
        fn($v) => (float) str_replace(',', '.', $v ?? 0),
        $data['cupsData']['consumption'] ?? []
    );
    $powers = array_map(
        fn($v) => (float) str_replace(',', '.', $v ?? 0),
        $cupsPower
    );

    $offerPowerPrices = array_map(function ($price, $i) use ($data) {
        $fee = isset($data['offerFees']['power'][$i])
            ? (float) $data['offerFees']['power'][$i] / 30
            : 0;
        return ($price !== null && $price !== '') ? (float) $price + $fee : null;
    }, $data['offerSelected']['prices']['power'] ?? [], array_keys($data['offerSelected']['prices']['power'] ?? []));

    $offerEnergyBase   = $data['offerSelected']['prices']['energy']
        ?? $data['offerSelected']['prices']['consumption']
        ?? [];
    $offerEnergyPrices = array_map(function ($price, $i) use ($data) {
        $fee = isset($data['offerFees']['energy'][$i])
            ? (float) $data['offerFees']['energy'][$i] / 1000
            : 0;
        return ($price !== null && $price !== '') ? (float) $price + $fee : null;
    }, $offerEnergyBase, array_keys($offerEnergyBase));

    $totalConsumption = array_sum($consumptions);

    $totPowerOff  = $data['offerSelected']['subTotal']['power']['total']  ?? 0;
    $totEnergyOff = $data['offerSelected']['subTotal']['energy']['total'] ?? 0;
    $ivaOff       = $data['offerSelected']['subTotal']['taxes']['iva']    ?? 0;
    $offerTotal   = $data['offerSelected']['total'] ?? 0;

    $maxRows = max(count($offerPowerPrices), count($offerEnergyPrices), count($powers), 1);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>Propuesta Energética</title>
    <style>
        :root {
            --primary:        {{ $primary }};
            --primary-dark:   {{ $primaryDark }};
            --primary-darker: {{ $primaryDarker }};
            --primary-light:  {{ $primaryLight }};
            --primary-soft:   {{ $primarySoft }};
            --primary-border: {{ $primaryBorder }};
            --text:           #2d2d2d;
            --text-soft:      #6b6b6b;
        }

        @page { margin: 0; size: A4; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Montserrat', sans-serif;
            font-size: 11px;
            color: var(--text);
            background: #fff;
        }

        .top-bar {
            height: 8px;
            background: linear-gradient(90deg, var(--primary-darker), var(--primary), var(--primary-light));
        }
        .bottom-bar {
            height: 8px;
            background: var(--primary);
            position: fixed;
            bottom: 0; left: 0; right: 0;
        }

        .page-inner { padding: 18px 24px 40px; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-border);
        }
        .logo-image { max-height: 44px; max-width: 120px; object-fit: contain; }
        .header-right { text-align: right; }
        .header-right .eyebrow {
            font-size: 9px;
            letter-spacing: 3px;
            font-weight: 700;
            color: var(--primary-light);
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .header-right .title-main {
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }
        .pill {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            border-radius: 999px;
            padding: 4px 14px;
            font-size: 11px;
            font-weight: 800;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }
        .info-card {
            border: 2px solid var(--primary-border);
            border-radius: 10px;
            padding: 10px 14px;
        }
        .info-card-title {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--primary-light);
            margin-bottom: 8px;
        }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 2px 0; font-size: 11px; color: #4a4660; vertical-align: top; }
        .info-table td:first-child { width: 80px; font-weight: 800; color: var(--primary-dark); }

        .section-caption {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 12px 0 6px;
        }
        .section-caption .txt {
            color: var(--primary-light);
            font-size: 10px;
            letter-spacing: 3px;
            font-weight: 900;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .section-caption .rule { height: 2px; background: var(--primary-light); flex: 1; }

        .period-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--primary-border);
        }
        .period-table thead th {
            background: var(--primary-dark);
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 8px;
            text-align: center;
        }
        .period-table thead th:first-child { background: var(--primary-darker); width: 60px; }
        .period-table tbody td {
            text-align: center;
            padding: 8px;
            font-size: 11px;
            font-weight: 700;
            color: #333;
            border-bottom: 1px solid var(--primary-border);
        }
        .period-table tbody tr:last-child td { border-bottom: none; }
        .period-table tbody td:first-child {
            background: var(--primary-dark);
            color: #fff;
            font-size: 14px;
            font-weight: 800;
        }
        .period-table tbody tr:nth-child(odd)  td:not(:first-child) { background: #fafafa; }
        .period-table tbody tr:nth-child(even) td:not(:first-child) { background: var(--primary-soft); }
        .period-table tfoot td {
            text-align: center;
            padding: 6px 8px;
            font-size: 11px;
            font-weight: 900;
            background: var(--primary-soft);
            border-top: 1px solid var(--primary-border);
            color: var(--primary-dark);
        }

        .obs-box {
            border: 2px solid var(--primary-border);
            border-radius: 10px;
            padding: 10px 14px;
            height: 120px;
            color: var(--text-soft);
            font-size: 11px;
            line-height: 1.5;
        }

        .claim {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 14px 0 10px;
        }
        .claim .line { height: 2px; background: var(--primary-light); flex: 1; }
        .claim .text {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--primary-dark);
            white-space: nowrap;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 10px;
        }
        .advisor { display: flex; align-items: center; gap: 10px; }
        .avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 900;
        }
        .advisor-name { font-size: 13px; font-weight: 800; color: var(--primary-dark); }
        .advisor-role { font-size: 10px; color: #9a97aa; margin-top: 2px; }
        .contact { text-align: right; font-size: 11px; color: #5d5870; line-height: 1.6; }
        .contact strong { color: var(--primary); }

        .marketer-logo {
            max-width: 60px; max-height: 18px;
            object-fit: contain; vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="top-bar"></div>
<div class="bottom-bar"></div>

<div class="page-inner">

    {{-- CABECERA --}}
    <div class="header">
        <img class="logo-image" src="{{ $logoUrl }}" alt="Logo">
        <div class="header-right">
            <div class="eyebrow">PROPUESTA DE LUZ — {{ $tariff }}</div>
            <div class="title-main">{{ ucwords(strtolower($enterprise)) }}</div>
            <div class="pill">{{ $tariff }}</div>
        </div>
    </div>

    {{-- INFO SUMINISTRO + OFERTA --}}
    <div class="info-grid">
        <div class="info-card">
            <div class="info-card-title">Punto de suministro</div>
            <table class="info-table">
                <tr><td>Nombre:</td><td>{{ ucwords(strtolower($enterprise)) }}</td></tr>
                <tr><td>CIF:</td><td>{{ $CIF }}</td></tr>
                <tr><td>CUPS:</td><td>{{ $cups }}</td></tr>
                <tr><td>Dirección:</td><td>{{ ucwords(strtolower($location)) }}</td></tr>
            </table>
        </div>
        <div class="info-card">
            <div class="info-card-title">Datos de la oferta</div>
            <table class="info-table">
                <tr><td>Tarifa:</td><td>{{ $tariff }}</td></tr>
                <tr>
                    <td>Oferta:</td>
                    <td>
                        {{ ucwords(strtolower($offerCom)) }}
                        @if(!empty($marketerLogoUrl))
                            <img class="marketer-logo" src="{{ $marketerLogoUrl }}" alt="{{ $offerCom }}">
                        @endif
                    </td>
                </tr>
                <tr><td>Periodo:</td><td>{{ $periodDays }} días</td></tr>
                <tr><td>Consumo:</td><td>{{ $totalConsumptionFmt }} kWh</td></tr>
            </table>
        </div>
    </div>

    {{-- TÉRMINO DE POTENCIA --}}
    <div class="section-caption">
        <div class="txt">Término de potencia</div>
        <div class="rule"></div>
    </div>

    <table class="period-table">
        <thead>
            <tr>
                <th>Periodo</th>
                <th>kW contratado</th>
                <th>€/kW/día Oferta</th>
                <th>Coste (€)</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 0; $i < $maxRows; $i++)
                @php
                    $kw     = $powers[$i]            ?? 0;
                    $priceO = $offerPowerPrices[$i]   ?? null;
                    $costO  = $data['offerSelected']['subTotal']['power']['P'.($i+1)] ?? 0;
                @endphp
                @if($kw > 0 || $priceO !== null)
                <tr>
                    <td>P{{ $i + 1 }}</td>
                    <td>{{ number_format($kw, 2, ',', '.') }} kW</td>
                    <td>
                        @if($priceO !== null)
                            {{ number_format($priceO, 6, ',', '.') }}
                        @else —
                        @endif
                    </td>
                    <td>{{ number_format($costO, 2, ',', '.') }} €</td>
                </tr>
                @endif
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Total potencia</strong></td>
                <td><strong>{{ number_format($totPowerOff, 2, ',', '.') }} €</strong></td>
            </tr>
        </tfoot>
    </table>

    {{-- TÉRMINO DE ENERGÍA --}}
    <div class="section-caption">
        <div class="txt">Término de energía</div>
        <div class="rule"></div>
    </div>

    <table class="period-table">
        <thead>
            <tr>
                <th>Periodo</th>
                <th>kWh consumido</th>
                <th>€/kWh Oferta</th>
                <th>Coste (€)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($consumptions as $i => $kwh)
                @php
                    $priceO = $offerEnergyPrices[$i] ?? null;
                    $costO  = $data['offerSelected']['subTotal']['energy']['P'.($i+1)] ?? 0;
                @endphp
                @if($kwh > 0 || $priceO !== null)
                <tr>
                    <td>P{{ $i + 1 }}</td>
                    <td>{{ number_format($kwh, 2, ',', '.') }} kWh</td>
                    <td>
                        @if($priceO !== null)
                            {{ number_format($priceO, 6, ',', '.') }}
                        @else —
                        @endif
                    </td>
                    <td>{{ number_format($costO, 2, ',', '.') }} €</td>
                </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3"><strong>Total energía</strong></td>
                <td><strong>{{ number_format($totEnergyOff, 2, ',', '.') }} €</strong></td>
            </tr>
            @if($ivaOff > 0)
            <tr>
                <td colspan="3">IVA incluido</td>
                <td>{{ number_format($ivaOff, 2, ',', '.') }} €</td>
            </tr>
            @endif
            <tr>
                <td colspan="3"><strong>TOTAL OFERTA (con IVA)</strong></td>
                <td><strong>{{ number_format($offerTotal, 2, ',', '.') }} €</strong></td>
            </tr>
        </tfoot>
    </table>

    {{-- OBSERVACIONES + RESUMEN ECONÓMICO --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 4px;">

        <div>
            <div class="section-caption">
                <div class="txt">Observaciones</div>
                <div class="rule"></div>
            </div>
            <div class="obs-box">
                {{ $data['pdfForm']['observaciones'] ?? '' }}
            </div>
        </div>

        <div>
            <div class="section-caption">
                <div class="txt">Resumen económico</div>
                <div class="rule"></div>
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px; height: 120px; justify-content: space-between;">
                <div style="background: #f0f0f4; border-radius: 8px; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 10px; font-weight: 800; color: #9a97aa; text-transform: uppercase; letter-spacing: 1px;">Total potencia</span>
                    <span style="font-size: 14px; font-weight: 900; color: {{ $primaryDark }};">{{ number_format($totPowerOff, 2, ',', '.') }} €</span>
                </div>
                <div style="background: {{ $primarySoft }}; border-radius: 8px; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 10px; font-weight: 800; color: {{ $primaryLight }}; text-transform: uppercase; letter-spacing: 1px;">Total energía</span>
                    <span style="font-size: 14px; font-weight: 900; color: {{ $primaryDark }};">{{ number_format($totEnergyOff, 2, ',', '.') }} €</span>
                </div>
                <div style="background: #e8f2ed; border-radius: 8px; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 10px; font-weight: 800; color: #38a95f; text-transform: uppercase; letter-spacing: 1px;">Total con IVA</span>
                    <span style="font-size: 16px; font-weight: 900; color: #287f46;">{{ number_format($offerTotal, 2, ',', '.') }} €</span>
                </div>
            </div>
        </div>

    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <div class="advisor">
            <div class="avatar">
                {{ strtoupper(substr($agentName ?? 'A', 0, 1)) }}{{ strtoupper(substr(explode(' ', trim($agentName ?? 'A B'))[1] ?? '', 0, 1)) }}
            </div>
            <div>
                <div class="advisor-name">{{ $agentName }}</div>
                <div class="advisor-role">Asesor energético</div>
            </div>
        </div>

        <div class="contact">
            @if(!empty($agentPhone) && ($data['basicData']['userSubdomain']['_id'] ?? '') !== '6909faa9232c09035a03f3b2')
                ☎ {{ $agentPhone }}
                @if(($data['basicData']['userSubdomain']['_id'] ?? null) === '65cb57489c2c285441086a43')
                    / 957855980
                @endif
                <br>
            @endif
            @if(!empty($agentEmail) && ($data['basicData']['userSubdomain']['_id'] ?? '') !== '6909faa9232c09035a03f3b2')
                {{ $agentEmail }}<br>
            @endif
            @if($isBlueTheme ?? false)
                <strong>www.vtcomenergia.com</strong><br>
            @endif
        </div>
    </div>

</div>

</body>
</html>
