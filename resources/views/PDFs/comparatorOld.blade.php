<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        /* Evitar cortes dentro de secciones y tablas */
        .section,
        table {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #333;
        }

        /* Cabecera superior */
        .top-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .top-table td {
            border: none;
            padding: 0 5px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 50%;
            text-align: left;
        }

        .date-cell {
            width: 50%;
            text-align: right;
            font-size: 11px;
            color: #555;
        }

        .top-separator {
            height: 1px;
            background: #e4e4e4;
            border: none;
            margin: 0 0 5px 0;
        }

        /* Header principal */
        header {
            margin-bottom: 5px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        /* Definimos dos columnas iguales */
        .header-table colgroup col {
            width: 50%;
        }

        /* Título centrado en rojo */
        .title-cell {
            font-size: 1.8rem;
            font-weight: 600;
            color: #b93e3e;
            padding-bottom: 8px;
            text-align: center;
        }

        /* Datos del header: centrados, más pequeños */
        .header-table td.data-cell {
            text-align: left;
            font-size: 0.85rem;
            padding: 3px 8px;
            vertical-align: top;
        }

        /* Titulillos de sección en rojo */
        .header-table .data-cell h4 {
            margin: 0 0 5px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #b93e3e;
        }

        /* Etiquetas (strong) en rojo */
        .header-table .data-cell strong {
            color: #b93e3e;
        }

        .header-table .data-cell p {
            margin: 2px 0;
            color: #333;
        }

        .separator {
            height: 2px;
            background: #444;
            border: none;
            margin: 0 0 10px 0;
        }

        /* Encabezados de sección centrados */
        h2 {
            text-align: center;
            margin: 20px 0 10px;
            font-size: 18px;
            color: #b93e3e;
        }

        h3 {
            text-align: center;
            margin: 15px 0 8px;
            font-size: 16px;
            color: #444;
        }

        /* Tablas unificadas */
        .single-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
        }

        .single-table th,
        .single-table td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        .single-table th {
            background: #b93e3e;
            color: #fff;
            font-weight: 600;
        }

        .single-table tbody tr:nth-child(odd) {
            background: #f9eaea;
        }

        /* Gráficas */
        .main-graph {
            text-align: center;
            margin-bottom: 15px;
        }

        .main-graph img {
            max-width: 80%;
            height: auto;
        }

        .two-graphs {
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-bottom: 20px;
        }

        .two-graphs .graph {
            display: table-cell;
            vertical-align: top;
            padding: 0 5px;
            text-align: center;
        }

        .two-graphs img {
            max-width: 100%;
            height: auto;
        }

        /* Totalizador */
        .totalizer {
            width: 80%;
            margin: 20px auto 40px;
            border: 1px solid #444;
            border-radius: 6px;
            overflow: hidden;
            font-size: 0.95rem;
            margin-top: 130px;
        }

        .totalizer table {
            width: 100%;
            border-collapse: collapse;
        }

        .totalizer th {
            background: #444;
            color: #fff;
            padding: 8px;
            font-weight: 600;
            text-align: center;
        }

        .totalizer td {
            padding: 6px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            background: #f9f9f9;
            white-space: nowrap;
        }

        .totalizer tr:nth-child(odd) td {
            background: #f9f9f9;
        }

        .totalizer tr.save-row td {
            background: #444;
            color: #fff;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 20px;
            width: 100%;
            text-align: center;
            font-size: 11px;
            color: #777;
        }

        .totalizer.top-offers {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 0;
            margin: 20px 0;
            border-radius: 6px;
            /* sin sombra aquí, para homogeneidad con totalizador */
            box-shadow: none;
            border: 1px solid #444;
        }

        /* Tabla ocupa todo el ancho y layout fijo */
        .top-offers-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            margin: 0;
        }

        /* Columnas repartidas (ajusta % si quieres) */
        .top-offers-table colgroup col:nth-child(1) {
            width: 20%;
        }

        .top-offers-table colgroup col:nth-child(2) {
            width: 40%;
        }

        .top-offers-table colgroup col:nth-child(3) {
            width: 15%;
        }

        .top-offers-table colgroup col:nth-child(4) {
            width: 12.5%;
        }

        .top-offers-table colgroup col:nth-child(5) {
            width: 12.5%;
        }

        /* Encabezado: fondo #444, texto blanco */
        .top-offers-table thead th {
            background: #444;
            color: #fff;
            font-weight: 600;
            padding: 12px;
            text-align: center;
        }

        /* Celdas: mismo estilo que totalizador */
        .top-offers-table td {
            background: #f9f9f9;
            color: #333;
            padding: 12px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            box-sizing: border-box;
            white-space: normal;
            overflow-wrap: break-word;
        }

        /* Filas pares en blanco para zebra stripes */
        .top-offers-table tbody tr:nth-child(even) td {
            background: #fff;
        }

        /* Hover sutil */
        .top-offers-table tbody tr:hover td {
            background: #ececec;
        }

        /* Si tuvieras fila “save-row” */
        .top-offers-table tbody tr.save-row td {
            background: #444;
            color: #fff;
            font-weight: 600;
            border-bottom: none;
        }

        /* Quitar el border-bottom de la última fila para que no deje línea extra */
        .top-offers-table tbody tr:last-child td {
            border-bottom: none;
        }

        .top-offers-table th:first-child,
        .top-offers-table td:first-child {
            font-weight: bold;
            color: #000;
        }

        .top-offers-table thead th:first-child {
            font-weight: bold;
            color: #fff;
        }

        /* Celdas de la primera columna en negrita y negro */
        .top-offers-table tbody td:first-child {
            font-weight: bold;
            color: #000;
        }
    </style>
    <title>Comparativa {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</title>
</head>

<body>

    {{-- Cabecera superior --}}
    <table class="top-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ $logoUrl }}" alt="Logo Empresa" style="max-height:40px; object-fit:contain;">
            </td>
            <td class="date-cell">
                <strong>Fecha:</strong> {{ $currentDate }}
            </td>
        </tr>
    </table>
    <hr class="top-separator">

    <header>
        <table class="header-table">
            <colgroup>
                <col>
                <col>
            </colgroup>
            <tr>
                <td class="title-cell" colspan="2">
                    Comparativa de Oferta Eléctrica
                </td>
            </tr>
            <tr>
                <td class="data-cell">
                    <h4>Comparativa para:</h4>
                    @if (!isset($totalDays))
                        <p><strong>Fecha:</strong> {{ $startDate }} – {{ $endDate }}</p>
                    @endif
                    <p><strong>CUPS:</strong> {{ $cups }}</p>
                    <p><strong>Nombre:</strong> {{ ucwords(strtolower($enterprise)) }}</p>
                    <p><strong>CIF:</strong> {{ $CIF }}</p>
                    <p><strong>Dirección:</strong> {{ ucwords(strtolower($location)) }}</p>
                </td>
                <td class="data-cell">
                    <h4>Datos Oferta:</h4>

                    <p><strong>Tarifa:</strong> {{ $tariff }}</p>
                    <p><strong>Actual:</strong> {{ ucwords(strtolower($actualCom)) }}</p>
                    <p><strong>Ofertada:</strong> {{ ucwords(strtolower($offerCom)) }}</p>
                    <p><strong>Producto:</strong> {{ ucwords(strtolower($product)) }}</p>
                </td>
            </tr>
        </table>
        <hr class="separator">
    </header>

    <div class="content">
        <p>
            <strong>Periodo comparado:</strong> {{ $data['totalDays'] }} días&nbsp;&nbsp;&nbsp;&nbsp;
            <strong>Consumo total en este periodo:</strong> {{ $totalConsumptionFmt }} kWh
        </p>
        <div class="section">
            <h2>
                @if ($period === 'year')
                    Consumo mensual
                @else
                    Consumo por periodos
                @endif
            </h2>

            @if ($period === 'month')
                <div class="main-graph">
                    <img src="{{ $barP1P6Image }}" alt="Consumo por periodos">
                </div>

                {{-- Solo mostramos la gráfica de pastel si hay consumo --}}
                @if ($totalConsumptionFmt > 0)
                    <div class="graph">
                        <h3>Distribución P1–P6</h3>
                        <img src="{{ $pieEnergyImage }}" alt="Distribución P1–P6" style="max-width:70%; margin-left:100px;">
                    </div>
                @endif
            @else
                <div class="main-graph">
                    <img src="{{ $barEnergyImage }}" alt="Consumo mensual">
                </div>

                <div class="two-graphs">
                    {{-- Solo mostramos la gráfica de pastel si hay consumo --}}
                    @if ($totalConsumptionFmt > 0)
                        <div class="graph">
                            <h3>Distribución P1–P6</h3>
                            <img src="{{ $pieEnergyImage }}" alt="Distribución P1–P6">
                        </div>
                    @endif

                    <div class="graph">
                        <h3>Consumo P1–P6 (barras)</h3>
                        <img src="{{ $barP1P6Image }}" alt="Consumo P1–P6">
                    </div>
                </div>
            @endif
        </div>
    </div>


    <div style="margin-top:30px;font-family:'Arial, sans-serif',serif;">

        <table style="width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed;">

            <colgroup>
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:12%">
                <col style="width:3%">
                <col style="width:10%">
                <col style="width:10%">
                <col style="width:12%">
            </colgroup>

            <tr>
                <td colspan="9"
                    style="border:1px solid #444;background:#efefef;font-weight:bold;text-align:center;font-size:18px;padding:10px;">
                    ESTUDIO PERSONALIZADO DE SUMINISTRO
                </td>
            </tr>

            <tr>
                <td colspan="5" style="border:1px solid #444;background:#d96b6b;font-weight:bold;padding:8px;">
                    Estudio personalizado de suministro:
                </td>

                <td colspan="4" style="border:1px solid #444;background:#d96b6b;font-weight:bold;padding:8px;">
                    OFERTA PERSONALIZADA
                </td>
            </tr>

            <tr>
                <td colspan="5" style="border:1px solid #444;text-align:center;padding:8px;font-weight:bold;">
                    {{ $actualCom ?: 'ACTUAL' }}
                </td>

                <td colspan="4" style="border:1px solid #444;text-align:center;padding:8px;font-weight:bold;">
                    {{ $offerCom }}
                </td>
            </tr>

            <tr style="background:#d96b6b;font-weight:bold;">
                <td style="border:1px solid #444;padding:7px;">POTENCIA</td>
                <td style="border:1px solid #444;padding:7px;">€/kW</td>
                <td style="border:1px solid #444;padding:7px;">kW</td>
                <td style="border:1px solid #444;padding:7px;">días</td>
                <td style="border:1px solid #444;padding:7px;">TOTAL</td>

                <td style="border:1px solid #444;"></td>

                <td style="border:1px solid #444;padding:7px;">€/kW</td>
                <td style="border:1px solid #444;"></td>
                <td style="border:1px solid #444;padding:7px;">TOTAL</td>
            </tr>

            @foreach($data['cupsData']['power'] as $i => $kw)

                @if($kw > 0)

                    @php $period = 'P' . ($i + 1); @endphp

                    <tr>

                        <td style="border:1px solid #444;padding:7px;">{{ $period }}</td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ number_format($data['prices']['power'][$i], 6, '.', '') }}
                        </td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ $kw }}
                        </td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ $data['totalDays'] }}
                        </td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ number_format($data['currentSubtotal']['power'][$period], 2, ',', '.') }} €
                        </td>

                        <td style="border:1px solid #444;"></td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ number_format($data['offerSelected']['prices']['power'][$i], 6, '.', '') }}
                        </td>

                        <td style="border:1px solid #444;"></td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ number_format($data['offerSelected']['subTotal']['power'][$period], 2, ',', '.') }} €
                        </td>

                    </tr>

                @endif
            @endforeach

            <tr>
                <td colspan="4" style="border:1px solid #444;text-align:right;font-weight:bold;padding:7px;">
                    Dto potencia
                </td>

                <td style="border:1px solid #444;padding:7px;">
                    {{ number_format($data['currentSubtotal']['power']['discount'], 2, ',', '.') }} €
                </td>

                <td style="border:1px solid #444;"></td>

                <td colspan="3" style="border:1px solid #444;padding:7px;"></td>
            </tr>
            <tr style="background:#efefef;font-weight:bold;">
                <td colspan="4" style="border:1px solid #444;text-align:right;padding:7px;">
                    Total potencia
                </td>

                <td style="border:1px solid #444;padding:7px;">
                    {{ number_format($data['currentSubtotal']['power']['total'], 2, ',', '.') }} €
                </td>

                <td style="border:1px solid #444;"></td>

                <td colspan="3" style="border:1px solid #444;padding:7px;">
                    {{ number_format($data['offerSelected']['subTotal']['power']['total'], 2, ',', '.') }} €
                </td>
            </tr>

            <tr>
                <td colspan="9" style="height:6px;border:none;"></td>
            </tr>

            @php $interval = $data['cupsIntervalsData'][0]; @endphp

            <tr style="background:#d96b6b;font-weight:bold;">
                <td style="border:1px solid #444;padding:7px;">ENERGÍA</td>
                <td style="border:1px solid #444;padding:7px;">€/kWh</td>
                <td style="border:1px solid #444;padding:7px;">kWh</td>
                <td style="border:1px solid #444;"></td>
                <td style="border:1px solid #444;padding:7px;">TOTAL</td>

                <td style="border:1px solid #444;"></td>

                <td style="border:1px solid #444;padding:7px;">€/kWh</td>
                <td style="border:1px solid #444;"></td>
                <td style="border:1px solid #444;padding:7px;">TOTAL</td>
            </tr>

            @foreach($interval['periods'] as $i => $kwh)

                @if($kwh > 0)

                    @php $period = 'P' . ($i + 1); @endphp

                    <tr>

                        <td style="border:1px solid #444;padding:7px;">{{ $period }}</td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ $data['prices']['energy'][$i] }}
                        </td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ number_format($kwh, 2, ',', '.') }}
                        </td>

                        <td style="border:1px solid #444;"></td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ number_format($data['currentSubtotal']['energy'][$period], 2, ',', '.') }} €
                        </td>

                        <td style="border:1px solid #444;"></td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ $data['offerSelected']['prices']['energy'][$i] }}
                        </td>

                        <td style="border:1px solid #444;"></td>

                        <td style="border:1px solid #444;padding:7px;">
                            {{ number_format($data['offerSelected']['subTotal']['energy'][$period], 2, ',', '.') }} €
                        </td>

                    </tr>

                @endif
            @endforeach

            <tr>
                <td colspan="4" style="border:1px solid #444;text-align:right;font-weight:bold;padding:7px;">
                    Dto consumo
                </td>

                <td style="border:1px solid #444;padding:7px;">
                    {{ number_format($data['currentSubtotal']['energy']['discount'], 2, ',', '.') }} €
                </td>

                <td style="border:1px solid #444;"></td>

                <td colspan="3" style="border:1px solid #444;padding:7px;"></td>
            </tr>
            <tr style="background:#efefef;font-weight:bold;">
                <td colspan="4" style="border:1px solid #444;text-align:right;padding:7px;">
                    Total energía
                </td>

                <td style="border:1px solid #444;padding:7px;">
                    {{ number_format($data['currentSubtotal']['energy']['total'], 2, ',', '.') }} €
                </td>

                <td style="border:1px solid #444;"></td>

                <td colspan="3" style="border:1px solid #444;padding:7px;">
                    {{ number_format($data['offerSelected']['subTotal']['energy']['total'], 2, ',', '.') }} €
                </td>
            </tr>

            <tr>
                <td colspan="2" style="border:1px solid #444;background:#d96b6b;font-weight:bold;padding:7px;">
                    Consumo mensual
                </td>

                <td colspan="3" style="border:1px solid #444;padding:7px;">
                    {{ number_format($interval['consumption'], 2, ',', '.') }}
                </td>

                <td style="border:none;"></td>


            </tr>

            @if(!empty($data['cupsIntervalsData']))
                @php
                    $annualConsumption = collect($data['cupsIntervalsData'])->sum('consumption');
                @endphp

                @if($annualConsumption > 0)
                    <tr>
                        <td colspan="2" style="border:1px solid #444;background:#d96b6b;font-weight:bold;padding:7px;">
                            Consumo anual
                        </td>

                        <td colspan="3" style="border:1px solid #444;padding:7px;">
                            {{ number_format($annualConsumption, 2, ',', '.') }}
                        </td>

                        <td style="border:none;"></td>
                    </tr>
                @endif
            @endif

        </table>

        <table style="width:100%;margin-top:15px;font-size:13px;">

            <tr>
                <td style="width:50%">Impuesto electricidad</td>

                <td style="width:25%">
                    {{ number_format($data['currentSubtotal']['taxes']['electricTax'], 2, ',', '.') }} €
                </td>

                <td style="width:25%">
                    {{ number_format($data['offerSelected']['subTotal']['taxes']['electricTax'], 2, ',', '.') }} €
                </td>
            </tr>

            <tr>
                <td>Financiación bono social</td>

                <td>
                    {{ number_format($data['currentSubtotal']['taxes']['socialBonus'], 2, ',', '.') }} €
                </td>

                <td>
                    {{ number_format($data['offerSelected']['subTotal']['taxes']['socialBonus'], 2, ',', '.') }} €
                </td>
            </tr>

            {{-- CONCEPTOS ADICIONALES --}}
            @if(!empty($data['currentSubtotal']['otherConceptsDetail']))
                @foreach($data['currentSubtotal']['otherConceptsDetail'] as $concept)
                    <tr>
                        <td>{{ $concept['name'] }}</td>

                        <td>
                            {{ number_format($concept['value'], 2, ',', '.') }} €
                        </td>

                        <td>
                            {{ number_format($data['offerSelected']['subTotal']['otherConcepts'] ?? 0, 2, ',', '.') }} €
                        </td>
                    </tr>
                @endforeach
            @endif


            <tr style="font-weight:bold;">
                <td>Subtotal</td>

                <td>
                    {{ number_format(
    $data['currentSubtotal']['power']['total'] +
    $data['currentSubtotal']['energy']['total'] +
    $data['currentSubtotal']['taxes']['electricTax'] +
    $data['currentSubtotal']['taxes']['socialBonus'] +
    ($data['currentSubtotal']['otherConcepts'] ?? 0),
    2,
    ',',
    '.'
) }} €
                </td>

                <td>
                    {{ number_format(
    $data['offerSelected']['subTotal']['power']['total'] +
    $data['offerSelected']['subTotal']['energy']['total'] +
    $data['offerSelected']['subTotal']['taxes']['electricTax'] +
    $data['offerSelected']['subTotal']['taxes']['socialBonus'] +
    ($data['offerSelected']['subTotal']['otherConcepts'] ?? 0),
    2,
    ',',
    '.'
) }} €
                </td>
            </tr>

            <tr>
                <td>IVA</td>

                <td>
                    {{ number_format($data['currentSubtotal']['taxes']['iva'], 2, ',', '.') }} €
                </td>

                <td>
                    {{ number_format($data['offerSelected']['subTotal']['taxes']['iva'], 2, ',', '.') }} €
                </td>
            </tr>

            <tr style="background:#e7e7e7;font-weight:bold;">
                <td>TOTAL</td>

                <td>
                    {{ number_format($data['currentTotal'], 2, ',', '.') }} €
                </td>

                <td>
                    {{ number_format($data['offerSelected']['total'], 2, ',', '.') }} €
                </td>
            </tr>

        </table>


        <div style="margin-top:20px;text-align:center;">

            <div style="font-size:28px;font-weight:bold;color:#1ca64c;">
                AHORRO
            </div>

            <div style="font-size:14px;color:#1ca64c;font-weight:bold;margin-top:8px;">
                % {{ number_format($data['offerSelected']['savePercent'], 2, ',', '.') }}
            </div>

            <div style="font-size:14px;color:#1ca64c;font-weight:bold;">
                €/año {{ number_format($data['offerSelected']['save'], 2, ',', '.') }} €
            </div>

        </div>

    </div>



    @if ($showTopOffers)
        <div class="section" style="page-break-before: always;">
            <h2>TOP 5 OFERTAS MÁS BARATAS</h2>
            <div class="totalizer top-offers">
                <table class="top-offers-table">
                    <colgroup>
                        <col>
                        <col>
                        <col>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Comercializadora</th>
                            <th>Producto</th>
                            <th>Precio total</th>
                            <th>Ahorro (€)</th>
                            <th>Ahorro (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topOffers as $offer)
                            <tr>
                                <td>{{ $offer['marketer'] }}</td>
                                <td>{{ $offer['product'] }}</td>
                                <td>{{ number_format($offer['total'], 2, ',', '.') }} €</td>
                                <td>{{ number_format($offer['save'], 2, ',', '.') }} €</td>
                                <td>{{ number_format($offer['savePercent'], 0, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif


    </div>
    @if(!empty($observaciones))
        <div class="section" style="margin-top:40px;">
            <h2>Observaciones</h2>

            <div style="
                                                    border:1px solid #444;
                                                    padding:15px;
                                                    font-size:13px;
                                                    background:#f9f9f9;
                                                    border-radius:6px;
                                                ">
                {!! nl2br(e($observaciones)) !!}
            </div>
        </div>
    @endif


</body>

</html>