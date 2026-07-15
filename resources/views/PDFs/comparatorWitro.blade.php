<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <title>Comparativa {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <style>
        :root {
            /* Paleta verde */
            --brand-dark: #181c21;
            /* verde oscuro (texto/acento) */
            --brand-light: #80edc0;
            /* verde claro (acento) */

            --ink: #181c21;
            --muted: #64748b;
            --line: #e2e8f0;
            --bg: #f8fafc;
            --card: #ffffff;

            --radius: 20px;
            --shadow: 0 14px 40px rgba(24, 28, 33, .08);
            --band-h: 10px;

            --fs-base: 15px;
            --fs-h1: 2.2rem;
            --fs-chip: .85rem;

            --space-1: 10px;
            --space-2: 14px;
            --space-3: 18px;
            --space-4: 24px;
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%
        }

        body {
            margin: 0;
            color: var(--ink);
            background: var(--bg);
            font: var(--fs-base)/1.55 "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        h1,
        h2,
        h3 {
            margin: .15rem 0 .5rem
        }

        h1 {
            font-size: var(--fs-h1);
            font-weight: 800;
            letter-spacing: .2px
        }

        h2 {
            font-size: .95rem;
            font-weight: 700;
            color: var(--brand-dark);
            text-transform: uppercase;
            letter-spacing: .5px
        }

        h3 {
            font-size: 1rem;
            font-weight: 700
        }

        small {
            color: var(--muted)
        }

        .wrap {
            max-width: 940px;
            margin: 24px auto;
            padding: 0 16px
        }

        .stack {
            display: flex;
            flex-direction: column;
            gap: var(--space-3)
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: var(--space-3);
            page-break-inside: avoid;
        }

        /* ===== HERO (verde) ===== */
        .hero {
            position: relative;
            overflow: hidden;
            padding: 0;
            border: 0
        }

        .hero .band {
            height: var(--band-h);
            background: linear-gradient(90deg, rgba(24, 28, 33, 0.65), rgba(128, 237, 192, 0.65));
            border-radius: var(--radius) var(--radius) 0 0
        }

        .hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: var(--space-4);
            padding: 28px 24px 24px;
            background: linear-gradient(180deg, rgba(128, 237, 192, .08), rgba(255, 255, 255, 0));
            border: 1px solid var(--line);
            border-top: 0;
            border-radius: 0 0 var(--radius) var(--radius);
        }

        /* Título con logo al lado */
        .brand-side {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 0
        }

        .title-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: nowrap;
            width: 100%;
            justify-content: space-between;
        }

        .logo {
            height: 60px;
            /* más grande que los 36px que tienes */
            width: auto;
            object-fit: contain;
            display: block;
            border-radius: 12px;
            /* bordes redondeados */
                    }

        .brand-gradient {
            background: linear-gradient(90deg, var(--brand-dark) 0%, var(--brand-light) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Chips debajo del título */
        .chips {
            display: flex;
            flex-wrap: nowrap;
            gap: 6px
        }

        .chip,
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            border-radius: 14px;
            background: #fff;
            border: 1px solid var(--line);
            font-weight: 600;
            color: var(--ink);
            font-size: var(--fs-chip);
            box-shadow: 0 6px 16px rgba(2, 6, 23, .04);
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand-light)
        }

        .chip.positive .dot {
            background: #16a34a
        }

        /* ===== Essentials ===== */
        .essentials {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .info-block {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(24, 28, 33, 0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--line);
        }

        .info-header {
            background: linear-gradient(90deg, var(--brand-dark), var(--brand-light));
            color: #fff;
            padding: 14px 16px;
            font-weight: 700;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-content {
            padding: 14px 16px;
            display: grid;
            gap: 10px;
        }

        /* Flexible + evitar saltos de fecha */
        .kv {
            display: grid;
            grid-template-columns: max-content 1fr;
            gap: 6px 12px;
            align-items: start;
            padding: 6px 0;
            border-bottom: 1px dashed var(--line);
        }

        .kv:last-child {
            border-bottom: 0
        }

        .kv .k {
            font-weight: 700;
            color: var(--muted)
        }

        .kv .v {
            color: var(--ink);
            font-weight: 600;
            white-space: normal;
        }

        /* ===== Resumen Ahorro =====
           Importante a la IZQUIERDA, resto a la DERECHA (compactado localmente) */
        .summary {
            display: grid;
            grid-template-columns: 1.15fr .85fr !important;
            /* forzar 2 columnas siempre */
            gap: 16px;
            align-items: start;
            align-items: stretch;
            /* <-- Clave: ambas columnas misma altura */
        }

        .highlight {
            grid-column: 1;
            order: 1;
            grid-row: 1;
            border-radius: 20px;
            padding: 14px;
            /* Compacto */
            background: linear-gradient(135deg, var(--brand-light), #b9f5df);
            /* Fondo verde degradado */
            color: var(--ink);
            display: grid;
            gap: 8px;
            align-content: start;
        }

        .side {
            grid-column: 2;
            /* siempre segunda columna */
        }

        .highlight .big {
            font-size: 1.9rem;
            /* Reducido */
            font-weight: 900;
            line-height: 1.1;
        }

        .highlight .pair {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .highlight .pill {
            background: rgba(255, 255, 255, .55);
            border-radius: 14px;
            padding: 6px 10px;
            border: 0;
            box-shadow: none;
            font-size: 0.9rem;
        }

        /* DERECHA (resto indicadores) */


        .mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            /* Dos columnas compactas */
            gap: 8px;
        }

        .mini-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 10px;
            /* Compacto */
            background: #fff;
            display: grid;
            gap: 2px;
        }

        .mini-card .label {
            color: var(--muted);
            font-size: 0.85rem;
        }

        .mini-card .value {
            font-weight: 800;
            font-size: 1rem;
        }

        .money {
            font-variant-numeric: tabular-nums
        }

        /* Compactación opcional global existente */
        .wrap.one-page .card {
            padding: 16px
        }

        .wrap.one-page .mini-card {
            padding: 12px
        }

        .footer {
            font-size: 11px;
            color: #7c899a;
            text-align: center;
            margin-top: 6px
        }

        /* Responsive: en móvil, 1 columna (izquierda encima, derecha debajo) */
        @media (max-width:900px) {
            .hero-content {
                flex-direction: column;
                align-items: flex-start
            }



            /* Stack */
            .mini-grid {
                grid-template-columns: 1fr 1fr
            }

            /* Mantener dos columnas cuando quepa */
        }

        @media (max-width:680px) {
            .essentials {
                grid-template-columns: 1fr
            }
        }

        @media (max-width:640px) {
            .mini-grid {
                grid-template-columns: 1fr
            }
        }

        /* Impresión básica */
        @page {
            size: A4;
            margin: 14mm 12mm 14mm 12mm;
        }

        @media print {
            body {
                background: #fff
            }

            .wrap {
                max-width: 100%;
                margin: 0;
                padding: 0
            }

            .card {
                box-shadow: none;
                border-color: #e6eaf1
            }

            .hero-content {
                padding: 24px 18px 20px
            }

            .highlight {
                outline: 1px solid rgba(24, 28, 33, .15)
            }
        }
    </style>
</head>

<body>
    <div class="wrap stack one-page">

        <!-- HERO -->
        <section class="hero" role="banner" aria-label="Cabecera del informe">
            <div class="band"></div>
            <div class="hero-content">
                <div class="brand-side">
                    <div class="title-row">
                        <h1 class="brand-gradient">Informe de Ahorro</h1>
                        <img class="logo" src="{{ $logoUrl }}" alt="Logo Empresa" />

                    </div>
                    <div class="chips">
                        <span class="chip"><span
                                class="dot"></span><strong>Comparado:</strong>&nbsp;{{ $periodDays }}&nbsp;días</span>
                        <span class="chip positive"><span
                                class="dot"></span><strong>Consumo:</strong>&nbsp;{{ $totalConsumptionFmt }}&nbsp;kWh</span>
                        <span class="pill"><span
                                class="dot"></span><strong>Periodo:</strong>&nbsp;{{ $startDate }}&nbsp;–&nbsp;{{ $endDate }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ESSENTIALS -->
        <section class="essentials" aria-label="Datos esenciales">
            <div class="info-block" aria-label="Datos del punto">
                <div class="info-header">Punto</div>
                <div class="info-content">
                    <div class="kv">
                        <div class="k">Fecha</div>
                        <div class="v">{{ $startDate }}&nbsp;–&nbsp;{{ $endDate }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">CUPS</div>
                        <div class="v">{{ $cups }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">Nombre</div>
                        <div class="v">{{ ucwords(strtolower($enterprise)) }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">CIF</div>
                        <div class="v">{{ $CIF }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">Dirección</div>
                        <div class="v">{{ ucwords(strtolower($location)) }}</div>
                    </div>
                </div>
            </div>

            <div class="info-block" aria-label="Datos de la oferta">
                <div class="info-header">Oferta</div>
                <div class="info-content">
                    <div class="kv">
                        <div class="k">Tarifa</div>
                        <div class="v">{{ $tariff }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">Actual</div>
                        <div class="v">{{ ucwords(strtolower($actualCom)) }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">Ofertada</div>
                        <div class="v">{{ ucwords(strtolower($offerCom)) }}</div>
                    </div>
                    <div class="kv">
                        <div class="k">Producto</div>
                        <div class="v">{{ ucwords(strtolower($product)) }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cálculos (Blade) -->
        @php
            $toFloat = function ($value) {
                return (float) str_replace(',', '.', str_replace('.', '', $value));
            };

            $potAct = $toFloat($totPotActFmt);
            $potOff = $toFloat($totPotOffFmt);
            $engAct = $toFloat($totEngActFmt);
            $engOff = $toFloat($totEngOffFmt);

            $potSave = $potAct - $potOff;
            $engSave = $engAct - $engOff;

            $totalPureSave = $actualTotal - $offerTotal;
            $totalRealSave = $totalRealAct - $totalRealOff;
            $totalRealPct = $totalRealAct > 0 ? (($totalRealAct - $totalRealOff) / $totalRealAct) * 100 : 0;

            $unit = match ($powerPricePeriod) {
                'day' => 'día',
                'month' => 'mes',
                'year' => 'año',
                default => 'día',
            };
        @endphp

        <section class="card summary" aria-label="Resumen de ahorro e indicadores clave">
            <!-- IZQUIERDA -->
            <div class="highlight">
                <div><small>AHORRO TOTAL CON IMPUESTOS</small></div>
                <div class="big">{{ number_format($totalRealSave, 2, ',', '.') }} €</div>
                <div><strong>{{ number_format($totalRealPct, 0, ',', '.') }}%</strong> frente a la situación actual
                </div>
                <div class="pair">
                    <div class="pill" aria-label="Coste actual">Actual:
                        {{ number_format($totalRealAct, 2, ',', '.') }} €</div>
                    <div class="pill" aria-label="Coste oferta">Oferta:
                        {{ number_format($totalRealOff, 2, ',', '.') }} €</div>
                </div>
                <small>Importes con impuestos (IVA, etc.).</small>
            </div>

            <!-- DERECHA -->
            <div class="side">
                <div class="mini-grid">
                    <div class="mini-card">
                        <div class="label">Ahorro en Poten.</div>
                        <div class="value money">{{ number_format($potSave, 2, ',', '.') }} €</div>
                        <small>Term. potencia (€/kW·{{ $unit }})</small>
                    </div>
                    <div class="mini-card">
                        <div class="label">Ahorro en Energía</div>
                        <div class="value money">{{ number_format($engSave, 2, ',', '.') }} €</div>
                        <small>Term. energía (€/kWh)</small>
                    </div>
                    <div class="mini-card">
                        <div class="label">Coste puro actual</div>
                        <div class="value money">{{ number_format($actualTotal, 2, ',', '.') }} €</div>
                        <small>Sin impuestos</small>
                    </div>
                    <div class="mini-card">
                        <div class="label">Coste puro oferta</div>
                        <div class="value money">{{ number_format($offerTotal, 2, ',', '.') }} €</div>
                        <small>Sin impuestos</small>
                    </div>
                </div>
            </div>
        </section>
        <div style="display:flex; justify-content:center; align-items:center; gap:40px; margin-top:10px;">

            <div style="display:flex; flex-direction:column; align-items:center;">
                <div
                    style="font-family:Inter, Arial, sans-serif; font-weight:700; font-size:18px; color:var(--brand-dark); margin-bottom:4px;">
                    Ahorro Potencia
                </div>
                <img src="{{ $donutPotenciaImage }}" alt="Ahorro Potencia">
            </div>

            <div style="display:flex; flex-direction:column; align-items:center;">
                <div
                    style="font-family:Inter, Arial, sans-serif; font-weight:700; font-size:18px; color:var(--brand-dark); margin-bottom:4px;">
                    Ahorro Total
                </div>
                <img src="{{ $donutTotalImage }}" alt="Ahorro Total">
            </div>

            <div style="display:flex; flex-direction:column; align-items:center;">
                <div
                    style="font-family:Inter, Arial, sans-serif; font-weight:700; font-size:18px; color:var(--brand-dark); margin-bottom:4px;">
                    Ahorro Energía
                </div>
                <img src="{{ $donutEnergiaImage }}" alt="Ahorro Energía">
            </div>

        </div>



    </div>
</body>

</html>
