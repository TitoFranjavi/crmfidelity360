{{--
  resources/views/PDFs/acta_instalacion.blade.php
  PDF generado dentro del ZIP adjunto a la oportunidad
  Variables recibidas:
    $clientName, $clientPhone, $clientEmail
    $opportunity  (modelo Opportunity o null)
    $cableMeters, $tuboMeters
    $horaEntrada, $horaSalida, $duration
    $currentDate, $currentDateTime
    $signatureB64  (data:image/png;base64,...)
    $consentAccepted (bool)
    $attachmentMeta  ([{ name, type }])
--}}
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Arial', 'Helvetica', sans-serif;
    color: #1a202c;
    font-size: 13px;
    line-height: 1.55;
    background: #fff;
  }

  /* ── CABECERA ── */
  .hdr {
    background: #1b2f6e;
    color: #fff;
    padding: 22px 28px 18px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }
  .hdr-brand { font-size: 11px; opacity: .65; letter-spacing: .04em; text-transform: uppercase; margin-bottom: 5px; }
  .hdr-title { font-size: 24px; font-weight: 700; letter-spacing: -.4px; line-height: 1.1; }
  .hdr-sub   { font-size: 12px; opacity: .65; margin-top: 5px; }
  .hdr-meta  { text-align: right; font-size: 11.5px; opacity: .75; line-height: 1.7; }
  .hdr-meta strong { opacity: 1; font-size: 13px; }

  .accent { height: 4px; background: linear-gradient(90deg, #1b2f6e 0%, #3cc97b 100%); }

  /* ── CUERPO ── */
  .body { padding: 22px 28px 18px; }

  /* ── TARJETA CLIENTE ── */
  .client-card {
    background: #f0f7ff;
    border: 1px solid #bee3f8;
    border-left: 4px solid #1b2f6e;
    border-radius: 7px;
    padding: 14px 18px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 14px;
  }
  .client-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: #1b2f6e;
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; font-weight: 700;
    flex-shrink: 0;
  }
  .client-name  { font-size: 17px; font-weight: 700; color: #1b2f6e; }
  .client-detail { font-size: 12px; color: #4a5568; margin-top: 3px; }

  /* ── SECCIÓN TÍTULO ── */
  .sec-title {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .08em; color: #3cc97b;
    border-bottom: 1.5px solid #3cc97b;
    padding-bottom: 5px; margin-bottom: 13px; margin-top: 20px;
  }

  /* ── GRID DATOS INSTALACIÓN ── */
  .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 18px; }
  .data-cell {
    background: #f7fafc; border: 1px solid #e2e8f0;
    border-radius: 6px; padding: 11px 14px;
  }
  .data-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #718096; margin-bottom: 4px; }
  .data-val { font-size: 17px; font-weight: 700; color: #1b2f6e; }
  .data-sub { font-size: 11px; color: #3cc97b; font-weight: 600; margin-top: 2px; }

  /* Duración destacada */
  .dur-pill {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(60,201,123,.1); border: 1px solid rgba(60,201,123,.25);
    border-radius: 50px; padding: 6px 14px;
    font-size: 12.5px; font-weight: 600; color: #276749;
    margin-bottom: 20px;
  }
  .dur-pill::before { content: '⏱'; }

  /* ── TEXTO CONSENTIMIENTO ── */
  .consent-box {
    background: #f0fff4; border: 1px solid #c6f6d5;
    border-left: 3px solid #3cc97b;
    border-radius: 6px; padding: 13px 15px;
    font-size: 12px; color: #276749; line-height: 1.7;
    margin-bottom: 20px;
  }
  .consent-box p { margin-bottom: 7px; }
  .consent-box p:last-child { margin-bottom: 0; }
  .consent-box strong { color: #1b2f6e; }

  /* ── FIRMA ── */
  .sig-wrap {
    border: 1.5px solid #e2e8f0; border-radius: 8px;
    background: #fff; padding: 10px; text-align: center;
    min-height: 110px; display: flex; align-items: center; justify-content: center;
    margin-bottom: 10px;
  }
  .sig-wrap img  { max-height: 110px; max-width: 100%; }
  .sig-empty     { color: #a0aec0; font-size: 12px; font-style: italic; }
  .consent-tick  { font-size: 11.5px; color: #3cc97b; font-weight: 600; margin-bottom: 18px; }
  .consent-tick::before { content: '✅ '; }

  /* ── ADJUNTOS ── */
  .att-list { list-style: none; }
  .att-item {
    display: flex; align-items: center; gap: 9px;
    padding: 7px 11px; border-radius: 6px;
    background: #f7fafc; border: 1px solid #e2e8f0;
    margin-bottom: 5px; font-size: 12px; color: #2d3748;
  }
  .att-icon { font-size: 14px; }
  .att-type { font-size: 10px; color: #a0aec0; margin-left: auto; }

  /* ── PIE DE PÁGINA ── */
  .footer {
    background: #1b2f6e; color: rgba(255,255,255,.65);
    text-align: center; padding: 12px 28px;
    font-size: 10.5px; line-height: 1.6;
    margin-top: 22px;
  }
  .footer strong { color: #3cc97b; }
  .footer em { color: rgba(255,255,255,.85); font-style: normal; }
</style>
</head>
<body>

{{-- CABECERA --}}
<div class="hdr">
  <div>
    <div class="hdr-brand">Segenet Movilidad</div>
    <div class="hdr-title">Acta de Instalación</div>
    <div class="hdr-sub">Cargador de Vehículo Eléctrico</div>
  </div>
  <div class="hdr-meta">
    <strong>{{ $currentDate }}</strong><br>
    Ref: ACTA-{{ \Carbon\Carbon::now()->format('YmdHis') }}<br>
    {{ $currentDateTime }}
  </div>
</div>
<div class="accent"></div>

{{-- CUERPO --}}
<div class="body">

  {{-- Cliente --}}
  <div class="client-card">
    <div class="client-avatar">
      {{ mb_strtoupper(mb_substr($clientName ?: 'C', 0, 1)) }}{{ mb_strtoupper(mb_substr(strstr($clientName.' ', ' '), 1, 1)) }}
    </div>
    <div>
      <div class="client-name">{{ $clientName ?: 'Sin nombre' }}</div>
      <div class="client-detail">
        @if($clientPhone) 📞 {{ $clientPhone }} @endif
        @if($clientPhone && $clientEmail) &nbsp;·&nbsp; @endif
        @if($clientEmail) ✉️ {{ $clientEmail }} @endif
        @if($opportunity && isset($opportunity->order['direc']) && $opportunity->order['direc'])
          <br>📍 {{ $opportunity->order['direc'] }}{{ isset($opportunity->order['town']) && $opportunity->order['town'] ? ', '.$opportunity->order['town'] : '' }}
        @endif
      </div>
    </div>
  </div>

  {{-- Datos de instalación --}}
  <div class="sec-title">Datos de la instalación</div>
  <div class="data-grid">
    <div class="data-cell">
      <div class="data-lbl">⚡ Cable eléctrico</div>
      <div class="data-val">{{ $cableMeters }} m</div>
      <div class="data-sub">metros instalados</div>
    </div>
    <div class="data-cell">
      <div class="data-lbl">📏 Tubo protector</div>
      <div class="data-val">{{ $tuboMeters }} m</div>
      <div class="data-sub">metros instalados</div>
    </div>
    <div class="data-cell">
      <div class="data-lbl">🕐 Hora de entrada</div>
      <div class="data-val">{{ $horaEntrada ?: '—' }}</div>
    </div>
    <div class="data-cell">
      <div class="data-lbl">🕔 Hora de salida</div>
      <div class="data-val">{{ $horaSalida ?: '—' }}</div>
    </div>
  </div>

  @if($duration)
    <div class="dur-pill">Duración total del trabajo: <strong>{{ $duration }}</strong></div>
  @endif

  {{-- Consentimiento --}}
  <div class="sec-title">Consentimiento</div>
  <div class="consent-box">
    <p>
      El cliente <strong>declara haber recibido e inspeccionado la instalación</strong> del punto de carga
      de vehículo eléctrico realizada por <strong>Segenet Movilidad</strong>,
      quedando conforme con los trabajos realizados.
    </p>
    <p>
      El trabajo incluye la instalación del cargador, cableado eléctrico ({{ $cableMeters }} m),
      tubería protectora ({{ $tuboMeters }} m) y puesta en marcha del equipo,
      así como la comprobación de su correcto funcionamiento.
    </p>
    <p>
      Esta firma constituye la <strong>aceptación formal del trabajo realizado</strong>
      y de las condiciones de la instalación. El cliente ha sido informado de los cuidados
      recomendados y dispone de la garantía de instalación según la oferta comercial aceptada.
    </p>
  </div>

  {{-- Firma --}}
  <div class="sec-title">Firma del cliente</div>
  <div class="sig-wrap">
    @if($signatureB64)
      <img src="{{ $signatureB64 }}" alt="Firma del cliente">
    @else
      <span class="sig-empty">Sin firma registrada en este acta</span>
    @endif
  </div>
  @if($consentAccepted)
    <div class="consent-tick">
      El cliente ha leído y aceptado el contenido del acta, confirmando su conformidad con los trabajos realizados.
    </div>
  @endif

  {{-- Archivos adjuntos --}}
  @if(count($attachmentMeta) > 0)
    <div class="sec-title">Archivos adjuntos ({{ count($attachmentMeta) }})</div>
    <ul class="att-list">
      @foreach($attachmentMeta as $att)
        @php
          $name = $att['name'] ?? 'archivo';
          $type = $att['type'] ?? '';
          $icon = str_starts_with($type, 'image/') ? '🖼️'
                : ($type === 'application/pdf' ? '📄'
                : (str_starts_with($type, 'video/') ? '🎬'
                : '📎'));
        @endphp
        <li class="att-item">
          <span class="att-icon">{{ $icon }}</span>
          <span>{{ $name }}</span>
          <span class="att-type">{{ $type ?: 'desconocido' }}</span>
        </li>
      @endforeach
    </ul>
  @endif

</div>{{-- /body --}}

{{-- PIE --}}
<div class="footer">
  Documento generado el <em>{{ $currentDateTime }}</em>
  &nbsp;·&nbsp; <strong>Segenet Movilidad</strong>
  &nbsp;·&nbsp; Este documento tiene valor legal como acta de conformidad de la instalación
</div>

</body>
</html>