<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura – {{ $invoiceNumber }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1a2a4a; background: #fff; }

        /* ── Header ── */
        .header { background: #1a2a6c; color: #fff; padding: 36px 48px; }
        .header-inner { display: table; width: 100%; }
        .logo-col, .invoice-col { display: table-cell; vertical-align: middle; }
        .invoice-col { text-align: right; }
        .logo { font-size: 30px; font-weight: 900; letter-spacing: 3px; }
        .logo-tagline { font-size: 11px; opacity: 0.7; margin-top: 3px; }
        .invoice-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.7; }
        .invoice-number { font-size: 24px; font-weight: 700; margin-top: 4px; }
        .invoice-date { font-size: 12px; opacity: 0.75; margin-top: 4px; }

        /* ── Status band ── */
        .status-band { background: #2563eb; color: #fff; text-align: center; padding: 10px; font-size: 12px; letter-spacing: 1px; text-transform: uppercase; font-weight: 600; }

        /* ── Body ── */
        .body { padding: 40px 48px; }

        /* ── Parties ── */
        .parties { display: table; width: 100%; margin-bottom: 36px; }
        .party { display: table-cell; width: 50%; vertical-align: top; }
        .party:last-child { text-align: right; }
        .party-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; color: #6b7280; margin-bottom: 8px; font-weight: 600; }
        .party-name { font-size: 16px; font-weight: 700; color: #1a2a6c; margin-bottom: 4px; }
        .party-detail { font-size: 12px; color: #6b7280; line-height: 1.7; }

        /* ── Divider ── */
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 0 0 28px; }

        /* ── Table ── */
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 28px; }
        table.items thead tr { background: #f1f5fe; }
        table.items thead th { padding: 12px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; font-weight: 600; text-align: left; }
        table.items thead th:last-child { text-align: right; }
        table.items tbody tr { border-bottom: 1px solid #f3f4f6; }
        table.items tbody td { padding: 14px 14px; font-size: 13px; }
        table.items tbody td:last-child { text-align: right; font-weight: 600; }
        .item-name { font-weight: 600; color: #1a2a4a; }
        .item-desc { font-size: 11px; color: #9ca3af; margin-top: 3px; }

        /* ── Totals ── */
        .totals { width: 260px; margin-left: auto; }
        .totals-row { display: table; width: 100%; padding: 7px 0; border-bottom: 1px solid #f3f4f6; }
        .totals-label, .totals-value { display: table-cell; font-size: 13px; }
        .totals-value { text-align: right; font-weight: 600; }
        .totals-row.grand { border-top: 2px solid #1a2a6c; border-bottom: none; padding-top: 12px; margin-top: 4px; }
        .totals-row.grand .totals-label { font-size: 14px; font-weight: 700; color: #1a2a6c; }
        .totals-row.grand .totals-value { font-size: 18px; font-weight: 800; color: #1a2a6c; }

        /* ── Features ── */
        .features-section { margin-top: 36px; }
        .features-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 14px; font-weight: 600; }
        .features-grid { display: table; width: 100%; }
        .feature-item { display: table-cell; width: 33.33%; padding-right: 16px; vertical-align: top; }
        .feature-check { color: #2563eb; font-weight: 700; }
        .feature-text { font-size: 12px; color: #374151; }

        /* ── Footer ── */
        .footer {
            position: fixed;  /* ← fixed lo ancla al fondo en DomPDF */
            bottom: 0;
            left: 0;
            right: 0;
            padding: 24px 48px;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
        }        .footer-inner { display: table; width: 100%; }
        .footer-left, .footer-right { display: table-cell; vertical-align: middle; font-size: 11px; color: #9ca3af; }
        .footer-right { text-align: right; }
        .security-badge { font-size: 11px; color: #2563eb; font-weight: 600; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-inner">
        <div class="logo-col">
            <div class="logo">ZOCO</div>
            <div class="logo-tagline">Plataforma de gestión empresarial</div>
        </div>
        <div class="invoice-col">
            <div class="invoice-label">Factura</div>
            <div class="invoice-number"># {{ $invoiceNumber }}</div>
            <div class="invoice-date">Emitida el {{ $startDate->format('d/m/Y') }}</div>
        </div>
    </div>
</div>

<div class="status-band">Suscripción activa y confirmada</div>

<div class="body">

    <!-- Parties -->
    <div class="parties">
        <div class="party">
            <div class="party-label">Emisor</div>
            <div class="party-name">Zoco Energía S.L.</div>
            <div class="party-detail">
                CIF: B56775372
                <br>Veterinaria María Cerrato, 4 Local 14005 Córdoba España<br>
                soporte@zocoenergia.com
            </div>
        </div>
        <div class="party">
            <div class="party-label">Cliente</div>
            <div class="party-name">{{ $companyName }}</div>
            <div class="party-detail">
                Plan {{ $plan }}<br>
                Facturación {{ ucfirst($billingCycle) }}<br>
                Inicio: {{ $startDate->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <hr class="divider">

    <!-- Items table -->
    <table class="items">
        <thead>
        <tr>
            <th style="width:50%">Descripción</th>
            <th>Periodo</th>
            <th>Cantidad</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <div class="item-name">Plan {{ $plan }} – Zoco</div>
                <div class="item-desc">Suscripción {{ $billingCycle }} a la plataforma Zoco</div>
            </td>
            <td>
                {{ $startDate->format('d/m/Y') }}<br>
                <span style="font-size:11px;color:#9ca3af;">
                    → {{ $isAnnual ? $startDate->copy()->addYear()->format('d/m/Y') : $startDate->copy()->addMonth()->format('d/m/Y') }}
                </span>
            </td>
            <td>1</td>
            <td>€{{ number_format($baseAmount, 2, ',', '.') }}</td>
        </tr>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="totals-row">
            <div class="totals-label">Subtotal</div>
            <div class="totals-value">€{{ number_format($baseAmount, 2, ',', '.') }}</div>
        </div>
        <div class="totals-row">
            <div class="totals-label">IVA (21%)</div>
            <div class="totals-value">€{{ number_format($vatAmount, 2, ',', '.') }}</div>
        </div>
        <div class="totals-row grand">
            <div class="totals-label">Total</div>
            <div class="totals-value">€{{ number_format($totalAmount, 2, ',', '.') }}</div>
        </div>
    </div>

    <!-- Features included -->
    <div class="features-section">
        <div class="features-title">Incluido en este plan</div>
        <div class="features-grid">
            @foreach($features as $index => $feature)
                @if($index % 3 === 0 && $index > 0)
        </div><div class="features-grid" style="margin-top:8px;">
            @endif
            <div class="feature-item">
                <span class="feature-check">✓ </span>
                <span class="feature-text">{{ $feature }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>

<div class="footer">
    <div class="footer-inner">
        <div class="footer-left">
            Zoco Energía S.L. · CIF B56775372<br>
            Veterinaria María Cerrato, 4 Local 14005 Córdoba España<br>
            IBAN: {{ trim(chunk_split(preg_replace('/\s+/', '', (string) $iban), 4, ' ')) }}
        </div>
        <div class="footer-right">
            <div class="security-badge">Encriptación bancaria 256-bit</div>
            <div style="margin-top:4px;">Pago procesado de forma segura</div>
        </div>
    </div>
</div>

</body>
</html>
