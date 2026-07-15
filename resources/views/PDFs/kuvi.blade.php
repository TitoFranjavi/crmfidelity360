<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe de comparativa</title>

    <style>
        /* =========================== */
        /* RESET & BASE */
        /* =========================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .red {
            color: #b91c1c;
            font-weight: bold;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111827;
            padding: 20px;
        }

        /* TITULOS */
        h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        h2.section-title {
            margin-top: 28px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            color: #111;
            margin-bottom: 6px;
        }

        p.section-sub {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 14px;
        }

        /* LAYOUT */
        .flex {
            display: flex;
            gap: 20px;
        }

        .center {
            text-align: center;
        }

        .full-width {
            width: 100%;
            text-align: center;
        }

        .pill {
            background: #eef2ff;
            color: #1d4ed8;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            margin-right: 6px;
        }

        /* TABLAS GENERALES */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background: #f3f4f6;
            padding: 6px;
            text-align: left;
            font-size: 11px;
            border-bottom: 1px solid #e5e7eb;
        }

        td {
            padding: 6px 6px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 11px;
        }

        tr:nth-child(even) td {
            background: #fafafa;
        }

        /* GRÁFICAS */
        .chart-container {
            width: 100%;
            text-align: center;
            margin: 0 auto 10px auto;
        }

        /* BLOQUES DE AHORRO */
        .highlight-offer {
            background: #b91c1c;
            padding: 14px 18px;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            margin-bottom: 6px;
        }

        .ahorro-box {
            background: #f9fafb;
            border: 1px solid #d1d5db;
            padding: 8px;
            width: 130px;
            text-align: center;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
        }

        .green {
            color: #16a34a;
            font-weight: bold;
        }

        .footer-note {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
        }

        /* TABLA MENSUAL PEQUEÑA */
        .table-small td,
        .table-small th {
            padding: 2px 3px;
            /* antes 4px 4px */
            font-size: 10px;
            white-space: nowrap;
        }

        .table-small th:not(:first-child),
        .table-small td:not(:first-child) {
            width: 38px;
            text-align: right;
        }

        .table-small th:first-child,
        .table-small td:first-child {
            width: 80px;
            white-space: normal;
        }

        .cover-page {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .cover-title {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        .cover-enterprise {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 40px;
        }

        .cover-logo {
            margin-top: 40px;
        }

        /* ===== SALTOS DE PÁGINA ===== */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        // Determinamos el periodo de facturación para el término de potencia
        $powerPricePeriod = match ($period ?? 'day') {
            'month' => 'month',
            'year' => 'year',
            default => 'day',
        };

        // Texto legible para mostrar
        $powerPriceUnit = match ($powerPricePeriod) {
            'day' => 'día',
            'month' => 'mes',
            'year' => 'año',
        };
    @endphp
    <div class="cover-page">
        <h1 class="cover-title">ANÁLISIS INICIAL DE FACTURACIÓN</h1>

        <h2 class="cover-enterprise">
            {{ $enterprise }}
        </h2>

        <div class="cover-logo">
            <img src="{{ $logoUrl }}" style="height:60px;">
        </div>
    </div>


    <div>
        <div style="font-size:10px; letter-spacing:.16em; text-transform:uppercase; color:#6b7280;">
            INFORME DE COMPARATIVA
        </div>

        <h1>ANÁLISIS INICIAL DE FACTURACIÓN</h1>
        <div style="font-size:12px; color:#4b5563; margin-bottom:20px;">
            {{ $enterprise }}
        </div>
    </div>



<h2 class="section-title">Datos del suministro analizado</h2>
<p class="section-sub">
    Información general del punto de suministro y de la oferta evaluada para la comparativa.
</p>

<table style="width:100%; margin-bottom:20px;">
    <tr>
        <td style="width:50%; vertical-align:top;">
            <table>
                <tr>
                    <th>Dirección de suministro</th>
                    <td>{{ $location ?? '-' }}</td>
                </tr>
                <tr>
                    <th>CUPS</th>
                    <td>{{ $cups }}</td>
                </tr>
                <tr>
                    <th>Tarifa</th>
                    <td>{{ $tariff }}</td>
                </tr>
                <tr>
                    <th>Periodo analizado</th>
                    <td>
                        {{ $startDate }} – {{ $endDate }}<br>
                        <span style="color:#6b7280;">({{ $periodDays }} días)</span>
                    </td>
                </tr>
            </table>
        </td>

        <td style="width:50%; vertical-align:top;">
            <table>
                <tr>
                    <th>Comercializadora actual</th>
                    <td>{{ $actualCom }}</td>
                </tr>
                <tr>
                    <th>Comercializadora ofertada</th>
                    <td>{{ $offerCom }}</td>
                </tr>
                <tr>
                    <th>Producto ofertado</th>
                    <td>{{ $product }}</td>
                </tr>
                <tr>
                    <th>Fecha del informe</th>
                    <td>{{ $currentDate }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>




    <h2 class="section-title">Situación actual</h2>
    <p class="section-sub">Resumen de las condiciones actuales del contrato eléctrico.</p>
    <p style="font-size:11px; color:#374151; margin-bottom:10px;">
        <strong>Periodo de facturación:</strong>
        {{ $startDate }} – {{ $endDate }}
        <span style="color:#6b7280;">
            ({{ $periodDays }} días)
        </span>
    </p>

    <div class="flex" style="align-items:flex-start;">

        <!-- TABLA DE SITUACIÓN -->
        <div style="flex: 1;">
            <div style="margin-bottom:8px;">
                <span class="pill">CUPS: {{ $cups }}</span>
                <span class="pill">TARIFA: {{ $tariff }}</span>
            </div>

            @php
                $powers = $cupsData['power'] ?? [];
                $pPrices = $cupsData['prices']['power'] ?? [];
                $ePrices = $cupsData['prices']['energy'] ?? [];
            @endphp

            <table>
                <thead>
                    <tr>
                        <th></th>
                        @foreach ($powerRows as $row)
                            <th>{{ $row['period'] }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    {{-- POTENCIA CONTRATADA --}}
                    <tr>
                        <td><strong>Potencia contratada (kW)</strong></td>
                        @foreach ($powerRows as $row)
                            @php $kw = (float) $row['kw']; @endphp
                            <td>
                                {{ $kw > 0 ? substr($row['kw'], 0, -2) : '-' }}
                            </td>
                        @endforeach
                    </tr>

                    {{-- PRECIO POTENCIA --}}
                    <tr>
                        <td>
                            <strong>
                                Potencia (€/kW·{{ $powerPriceUnit }})
                            </strong>
                        </td> @foreach ($powerRows as $row)
                            <td>
                                {{ $row['priceActRaw'] !== '' ? $row['priceActRaw'] : "-"}}
                            </td>
                        @endforeach
                    </tr>

                    {{-- PRECIO ENERGÍA --}}
                    <tr>
                        <td><strong>Energía (€/kWh)</strong></td>
                        @foreach ($energyRows as $row)
                            @php
                                $v = rtrim(rtrim(str_replace('.', ',', $row['priceAct']), '0'), ',');
                            @endphp

                            <td>{{ ($v === '' || $v === '0') ? '-' : $v }}</td>

                        @endforeach
                    </tr>
                </tbody>
            </table>


        </div>

        <!-- GRÁFICA DONUT -->
        <div style="width:260px; text-align:center;">
            <img src="{{ $pieEnergyImage }}" style="width:100%; max-width:240px;">
            <div class="footer-note">Gráfica 1. Consumo por periodo.</div>
        </div>

    </div>



    <h2 class="section-title">Consumo mensual</h2>
    <p class="section-sub">Evolución mensual del consumo de energía activa.</p>

    <div class="chart-container">
        <img src="{{ $barEnergyImage }}" style="width:85%; max-width:85%; margin:auto; display:block;">
    </div>

    <div class="footer-note center">Gráfica 2. Consumo mensual.</div>



    <div style="page-break-before: always;"></div>

    <h2 class="section-title">Desglose mensual de los consumos</h2>
    <p class="section-sub">Detalle mensual de energía activa, reactiva y maxímetros.</p>

    <table class="table-small">
        <thead>
            <tr>
                <th>Periodo</th>

                <th colspan="6" style="text-align:center;">Consumo activa (kWh)</th>
                <th colspan="6" style="text-align:center;">Consumo reactiva (kVArh)</th>
                <th colspan="6" style="text-align:center;">Maxímetros (kW)</th>
            </tr>

            <tr>
                <th></th>

                {{-- ACTIVA --}}
                @for ($i = 1; $i <= 6; $i++)
                    <th>P{{ $i }}</th>
                @endfor

                {{-- REACTIVA --}}
                @for ($i = 1; $i <= 6; $i++)
                    <th>P{{ $i }}</th>
                @endfor

                {{-- MAXÍMETROS --}}
                @for ($i = 1; $i <= 6; $i++)
                    <th>P{{ $i }}</th>
                @endfor
            </tr>
        </thead>

        <tbody>
            @foreach ($cupsIntervalsData as $row)
                <tr>
                    <td>
                        {{ $row['startDate'] }}<br>
                        {{ $row['endDate'] }}
                    </td>

                    {{-- ================= ENERGÍA ACTIVA ================= --}}
                    @foreach ($row['periods'] as $v)
                        @php
                            $value = (float) $v;
                        @endphp

                        <td>
                            @if ($value > 0)
                                    {{ $value == intval($value)
                                ? number_format($value, 0, ',', '.')
                                : rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',') }}
                            @else
                                -
                            @endif
                        </td>
                    @endforeach

                    {{-- ================= ENERGÍA REACTIVA ================= --}}
                    @foreach (($row['reactive'] ?? []) as $v)
                        <td>
                            {{ $v > 0 ? number_format($v, 2, ',', '.') : '-' }}
                        </td>
                    @endforeach

                    {{-- ================= MAXÍMETROS ================= --}}
                    @foreach (($row['powers'] ?? []) as $v)
                        <td>
                            {{ $v > 0 ? number_format($v, 0, ',', '.') : '-' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>


    <div class="footer-note center">Tabla 2. Desglose mensual de los consumos.</div>





    <h2 class="section-title" style="text-align:center;">Comparativa de costes</h2>

    <div class="chart-container">
        <img src="{{ $chartComparativa }}" style="width:70%; max-width:400px;">
    </div>

    <div class="footer-note center">Gráfica 3. Comparativa de costes.</div>


    <ul style="font-size:11px; margin-top:8px; line-height:1.4;">
        <li>* Todos los costes incluyen impuestos (eléctrico 5,1127% + IVA 21%).</li>
        <li>* Costes incluyen potencia, energía y reactiva.</li>
    </ul>

    <div style="page-break-before: always;"></div>

 <h2 class="section-title">Comparativa de costes: situación actual vs oferta</h2>
<p class="section-sub">
    Comparación directa de precios y costes entre la tarifa actual y la oferta propuesta.
</p>

{{-- ===================== TÉRMINO DE POTENCIA ===================== --}}
<h3 style="font-size:13px; font-weight:700; margin-bottom:6px;">
    Término de Potencia (€/kW·{{ $powerPriceUnit }})
</h3>

<table>
    <thead>
        <tr>
            <th>Periodo</th>
            <th>Valor (kW)</th>
            <th>Precio actual</th>
            <th>Precio oferta</th>
            <th>Coste actual</th>
            <th>Coste oferta</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($powerRows as $row)
            <tr>
                <td>{{ $row['period'] }}</td>
                <td>{{ substr($row['kw'], 0, -2) }} kW</td>
                <td>{{ $row['priceAct'] }}</td>
                <td>{{ $row['priceOff'] }}</td>
                <td class="money">{{ $row['costAct'] }} €</td>
                <td class="money">{{ $row['costOff'] }} €</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4">Total</th>
            <th class="money">{{ $totPotActFmt }} €</th>
            <th class="money">{{ $totPotOffFmt }} €</th>
        </tr>
    </tfoot>
</table>

{{-- ===================== TÉRMINO DE ENERGÍA ===================== --}}
<h3 style="font-size:13px; font-weight:700; margin:18px 0 6px;">
    Término de Energía (€/kWh)
</h3>

<table>
    <thead>
        <tr>
            <th>Periodo</th>
            <th>Consumo (kWh)</th>
            <th>Precio actual</th>
            <th>Precio oferta</th>
            <th>Coste actual</th>
            <th>Coste oferta</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($energyRows as $row)
            <tr>
                <td>{{ $row['period'] }}</td>
                <td>{{ substr($row['kwh'], 0, -3) }} kWh</td>
                <td>{{ $row['priceAct'] }}</td>
                <td>{{ $row['priceOff'] }}</td>
                <td class="money">{{ $row['costAct'] }} €</td>
                <td class="money">{{ $row['costOff'] }} €</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4">Total</th>
            <th class="money">{{ $totEngActFmt }} €</th>
            <th class="money">{{ $totEngOffFmt }} €</th>
        </tr>
    </tfoot>
</table>

{{-- ===================== RESUMEN ECONÓMICO FINAL ===================== --}}
@php
    $ahorroVal = $totalRealAct - $totalRealOff;
@endphp

@php
    $ahorroPot = $totPotAct - $totPotOff;
    $ahorroEng = $totEngAct - $totEngOff;
    $ahorroTot = $totalRealAct - $totalRealOff;

    $pctPot = $totPotAct > 0 ? ($ahorroPot / $totPotAct) * 100 : 0;
    $pctEng = $totEngAct > 0 ? ($ahorroEng / $totEngAct) * 100 : 0;
    $pctTot = $totalRealAct > 0 ? ($ahorroTot / $totalRealAct) * 100 : 0;
@endphp

<h2 class="section-title">Resumen económico final</h2>

<table>
    <thead>
        <tr>
            <th>Concepto</th>
            <th>Actual (€)</th>
            <th>Oferta (€)</th>
            <th>Ahorro (€)</th>
            <th>Ahorro (%)</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Potencia</td>
            <td class="money">{{ $totPotActFmt }} €</td>
            <td class="money">{{ $totPotOffFmt }} €</td>
            <td class="money {{ $ahorroPot >= 0 ? 'green' : 'red' }}">
                {{ number_format(abs($ahorroPot), 2, ',', '.') }} €
            </td>
            <td class="{{ $ahorroPot >= 0 ? 'green' : 'red' }}">
                {{ number_format(abs($pctPot), 1, ',', '.') }} %
            </td>
        </tr>

        <tr>
            <td>Energía</td>
            <td class="money">{{ $totEngActFmt }} €</td>
            <td class="money">{{ $totEngOffFmt }} €</td>
            <td class="money {{ $ahorroEng >= 0 ? 'green' : 'red' }}">
                {{ number_format(abs($ahorroEng), 2, ',', '.') }} €
            </td>
            <td class="{{ $ahorroEng >= 0 ? 'green' : 'red' }}">
                {{ number_format(abs($pctEng), 1, ',', '.') }} %
            </td>
        </tr>

        @if(($extrasAct ?? 0) > 0 || ($extrasOff ?? 0) > 0)
        <tr>
            <td>Otros conceptos</td>
            <td class="money">{{ number_format($extrasAct, 2, ',', '.') }} €</td>
            <td class="money">{{ number_format($extrasOff, 2, ',', '.') }} €</td>
            <td class="money">—</td>
            <td>—</td>
        </tr>
        @endif

        <tr>
            <th>Total con impuestos</th>
            <th class="money">{{ number_format($totalRealAct, 2, ',', '.') }} €</th>
            <th class="money">{{ number_format($totalRealOff, 2, ',', '.') }} €</th>
            <th class="money {{ $ahorroTot >= 0 ? 'green' : 'red' }}">
                {{ number_format(abs($ahorroTot), 2, ',', '.') }} €
            </th>
            <th class="{{ $ahorroTot >= 0 ? 'green' : 'red' }}">
                {{ number_format(abs($pctTot), 1, ',', '.') }} %
            </th>
        </tr>
    </tbody>
</table>

<div class="footer-note">
    * Porcentajes calculados sobre el importe actual de cada concepto.  
    * Importes con impuestos incluidos.
</div>


</body>

</html>