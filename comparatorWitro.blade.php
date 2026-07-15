<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Comparativa {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</title>
<style>
    /* Tipografía Inter (ajusta rutas si hace falta) */
    @font-face { font-family:'Inter'; src:url("{{ public_path('fonts/Inter/Inter-Regular.ttf') }}") format('truetype'); font-weight:400; }
    @font-face { font-family:'Inter'; src:url("{{ public_path('fonts/Inter/Inter-Bold.ttf') }}") format('truetype'); font-weight:700; }

    :root{
        --brand-dark:#181c21;
        --brand-light:#80edc0;
        --ink:#181c21;
        --muted:#64748b;
        --line:#cbd5e1; /* un poco más oscuro para que “marque” */
        --line-strong:#94a3b8;
        --bg:#fff;
        --card:#fff;
    }
    *{ box-sizing:border-box; }
    html,body{ height:100%; }
    body{
        margin:0; background:#fff; color:var(--ink);
        font: 15px/1.5 'Inter', Arial, Helvetica, sans-serif;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    h1,h2{ margin:6px 0 8px 0; }
    h1{
        font-size:32px; font-weight:700; color:var(--brand-dark);
        padding-bottom:6px; border-bottom:2px solid var(--line-strong); /* SUBRAYADO del título */
    }
    h2{
        font-size:13px; font-weight:700; color:var(--brand-dark); text-transform:uppercase; letter-spacing:.5px;
        padding-bottom:6px; border-bottom:1.5px solid var(--line); /* SUBRAYADO cabeceras bloque */
    }
    small{ color:var(--muted); }

    .wrap{ max-width:940px; margin:24px auto; padding:0 12px; }

    /* Recuadros más “marcados” */
    .card{
        background:var(--card);
        border:2px solid var(--line);            /* borde más grueso */
        border-radius:12px;
        padding:16px;
        page-break-inside: avoid;
    }

    /* HERO */
    .band{
        height:10px;
        background:#b2f0da;
        border:2px solid var(--line);            /* recuadro visible */
        border-bottom:0;
        border-radius:12px 12px 0 0;
    }
    .hero-box{
        border:2px solid var(--line);
        border-top:0;
        border-radius:0 0 12px 12px;
        background:#f2fcf7;
        padding:18px;
    }
    .logo{ height:60px; width:auto; display:block; border-radius:12px; border:1px solid var(--line); } /* logo enmarcado */

    .chips{ margin-top:10px; }
    .chip,.pill{
        display:inline-block; background:#fff; border:1.5px solid var(--line);
        border-radius:14px; padding:6px 10px; font-weight:600; font-size:13px; margin-right:6px;
    }
    .dot{ display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--brand-light); margin-right:6px; border:1px solid var(--line); }

    /* BLOQUES INFO (recuadros + subrayados internos) */
    .info-block{
        border:2px solid var(--line);
        border-radius:12px;
        overflow:hidden;
        background:#fff;
    }
    .info-header{ background:#e9fbf3; color:#000; padding:10px 14px; } /* h2 ya pone subrayado */
    .info-content{ padding:10px 14px; }
    .kv-table{ width:100%; border-collapse:collapse; }
    .kv-table td{
        padding:8px 0;
        border-bottom:1px dashed var(--line-strong); /* “subrayado” cada fila */
    }
    .kv-table tr:last-child td{ border-bottom:none; }
    .kv-k{ width:120px; color:var(--muted); font-weight:700; }
    .kv-v{ color:var(--ink); font-weight:600; }

    /* SUMMARY (recuadro destacado) */
    .highlight{
        background:#cfeee0;
        border:2px solid var(--line);
        border-radius:12px;
        padding:12px;
    }
    .big{ font-size:30px; font-weight:800; }
    .pill{
        display:inline-block; background:#fff; border:1.5px solid var(--line);
        border-radius:14px; padding:6px 10px; font-size:12px; font-weight:700;
    }

    .mini-card{
        border:2px solid var(--line);
        border-radius:12px; padding:10px; background:#fff;
    }
    .mini-label{ color:var(--muted); font-size:12px; padding-bottom:4px; border-bottom:1px solid var(--line); } /* subrayado */
    .mini-value{ font-weight:800; font-size:14px; margin-top:6px; }

    .center-title{
        font-weight:700; font-size:16px; color:var(--brand-dark); margin:6px 0;
        padding-bottom:4px; border-bottom:1.5px solid var(--line); /* subrayado título donut */
        text-align:center;
    }

    @page{ size:A4; margin:14mm 12mm 14mm 12mm; }
</style>
</head>
<body>
<div class="wrap">

    <!-- HERO -->
    <div class="band"></div>
    <div class="hero-box">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td style="vertical-align:top; width:70%;">
                    <h1>Informe de Ahorro</h1>
                    <div class="chips">
                        <span class="chip"><span class="dot"></span><strong>Comparado:</strong>&nbsp;{{ $periodDays }}&nbsp;días</span>
                        <span class="chip"><span class="dot"></span><strong>Consumo:</strong>&nbsp;{{ $totalConsumptionFmt }}&nbsp;kWh</span>
                        <span class="pill"><span class="dot"></span><strong>Periodo:</strong>&nbsp;{{ $startDate }}&nbsp;–&nbsp;{{ $endDate }}</span>
                    </div>
                </td>
                <td style="vertical-align:top; text-align:right;">
                    <img class="logo" src="{{ $logoUrl }}" alt="Logo Empresa" />
                </td>
            </tr>
        </table>
    </div>

    <!-- ESSENTIALS -->
    <div class="card">
        <table width="100%" cellspacing="16" cellpadding="0">
            <tr>
                <td width="50%" valign="top">
                    <div class="info-block">
                        <div class="info-header"><h2 style="margin:0; border:none; padding:0;">Punto</h2></div>
                        <div class="info-content">
                            <table class="kv-table">
                                <tr><td class="kv-k">Fecha</td><td class="kv-v">{{ $startDate }}&nbsp;–&nbsp;{{ $endDate }}</td></tr>
                                <tr><td class="kv-k">CUPS</td><td class="kv-v">{{ $cups }}</td></tr>
                                <tr><td class="kv-k">Nombre</td><td class="kv-v">{{ ucwords(strtolower($enterprise)) }}</td></tr>
                                <tr><td class="kv-k">CIF</td><td class="kv-v">{{ $CIF }}</td></tr>
                                <tr><td class="kv-k">Dirección</td><td class="kv-v">{{ ucwords(strtolower($location)) }}</td></tr>
                            </table>
                        </div>
                    </div>
                </td>
                <td width="50%" valign="top">
                    <div class="info-block">
                        <div class="info-header"><h2 style="margin:0; border:none; padding:0;">Oferta</h2></div>
                        <div class="info-content">
                            <table class="kv-table">
                                <tr><td class="kv-k">Tarifa</td><td class="kv-v">{{ $tariff }}</td></tr>
                                <tr><td class="kv-k">Actual</td><td class="kv-v">{{ ucwords(strtolower($actualCom)) }}</td></tr>
                                <tr><td class="kv-k">Ofertada</td><td class="kv-v">{{ ucwords(strtolower($offerCom)) }}</td></tr>
                                <tr><td class="kv-k">Producto</td><td class="kv-v">{{ ucwords(strtolower($product)) }}</td></tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $toFloat = fn($v) => (float) str_replace(',', '.', str_replace('.', '', $v));
        $potAct = $toFloat($totPotActFmt);
        $potOff = $toFloat($totPotOffFmt);
        $engAct = $toFloat($totEngActFmt);
        $engOff = $toFloat($totEngOffFmt);
        $potSave = $potAct - $potOff;
        $engSave = $engAct - $engOff;
        $totalRealSave = $totalRealAct - $totalRealOff;
        $totalRealPct  = $totalRealAct > 0 ? (($totalRealAct - $totalRealOff) / $totalRealAct) * 100 : 0;
        $unit = match ($powerPricePeriod) { 'day'=>'día','month'=>'mes','year'=>'año', default=>'día' };
    @endphp

    <!-- SUMMARY -->
    <div class="card">
        <table width="100%" cellspacing="16" cellpadding="0">
            <tr>
                <td width="55%" valign="top">
                    <div class="highlight">
                        <div><small>AHORRO TOTAL CON IMPUESTOS</small></div>
                        <div class="big">{{ number_format($totalRealSave, 2, ',', '.') }} €</div>
                        <div><strong>{{ number_format($totalRealPct, 0, ',', '.') }}%</strong> frente a la situación actual</div>
                        <table width="100%" cellspacing="8" cellpadding="0" style="margin:6px 0;">
                            <tr>
                                <td><span class="pill">Actual: {{ number_format($totalRealAct, 2, ',', '.') }} €</span></td>
                                <td style="text-align:right;"><span class="pill">Oferta: {{ number_format($totalRealOff, 2, ',', '.') }} €</span></td>
                            </tr>
                        </table>
                        <small>Importes con impuestos (IVA, etc.).</small>
                    </div>
                </td>
                <td width="45%" valign="top">
                    <table width="100%" cellspacing="8" cellpadding="0">
                        <tr>
                            <td width="50%" valign="top">
                                <div class="mini-card">
                                    <div class="mini-label">Ahorro en Poten.</div>
                                    <div class="mini-value">{{ number_format($potSave, 2, ',', '.') }} €</div>
                                    <small>Term. potencia (€/kW·{{ $unit }})</small>
                                </div>
                            </td>
                            <td width="50%" valign="top">
                                <div class="mini-card">
                                    <div class="mini-label">Ahorro en Energía</div>
                                    <div class="mini-value">{{ number_format($engSave, 2, ',', '.') }} €</div>
                                    <small>Term. energía (€/kWh)</small>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td width="50%" valign="top">
                                <div class="mini-card">
                                    <div class="mini-label">Coste puro actual</div>
                                    <div class="mini-value">{{ number_format($actualTotal, 2, ',', '.') }} €</div>
                                    <small>Sin impuestos</small>
                                </div>
                            </td>
                            <td width="50%" valign="top">
                                <div class="mini-card">
                                    <div class="mini-label">Coste puro oferta</div>
                                    <div class="mini-value">{{ number_format($offerTotal, 2, ',', '.') }} €</div>
                                    <small>Sin impuestos</small>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- DONUTS (títulos subrayados + recuadro opcional) -->
    <table width="100%" cellspacing="0" cellpadding="10" style="border:2px solid var(--line); border-radius:12px;">
        <tr>
            <td width="33.33%" align="center" valign="top">
                <div class="center-title">Ahorro Potencia</div>
                <img src="{{ $donutPotenciaImage }}" alt="Ahorro Potencia">
            </td>
            <td width="33.33%" align="center" valign="top">
                <div class="center-title">Ahorro Total</div>
                <img src="{{ $donutTotalImage }}" alt="Ahorro Total">
            </td>
            <td width="33.33%" align="center" valign="top">
                <div class="center-title">Ahorro Energía</div>
                <img src="{{ $donutEnergiaImage }}" alt="Ahorro Energía">
            </td>
        </tr>
    </table>

</div>
</body>
</html>
