<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción confirmada</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f9; color: #1a2a4a; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #1a2a6c, #2563eb); padding: 40px 40px 32px; text-align: center; }
        .header .logo { font-size: 28px; font-weight: 900; color: #ffffff; letter-spacing: 2px; margin-bottom: 16px; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 600; }
        .header p { color: rgba(255,255,255,0.8); margin-top: 6px; font-size: 14px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; color: #374151; margin-bottom: 24px; line-height: 1.6; }
        .plan-card { background: linear-gradient(135deg, #1a2a6c, #2563eb); border-radius: 12px; padding: 28px; color: #fff; margin-bottom: 28px; }
        .plan-card .plan-name { font-size: 13px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.8; }
        .plan-card .plan-title { font-size: 32px; font-weight: 800; margin: 4px 0 20px; }
        .plan-card .plan-details { display: grid; gap: 10px; }
        .plan-card .detail-row { display: flex; justify-content: space-between; font-size: 14px; border-top: 1px solid rgba(255,255,255,0.15); padding-top: 10px; Margin-bottom: 10px;}
        .plan-card .detail-row .label { opacity: 0.75; }
        .plan-card .detail-row .value { font-weight: 600; margin-left: 15px; }
        .badge { display: inline-block; background: rgba(255,255,255,0.2); border-radius: 20px; padding: 3px 12px; font-size: 12px; }
        .info-box { background: #eff6ff; border-left: 4px solid #2563eb; border-radius: 0 8px 8px 0; padding: 16px 20px; margin-bottom: 28px; font-size: 14px; color: #1e40af; line-height: 1.6; }
        .info-box strong { display: block; margin-bottom: 4px; }
        .footer { background: #f8fafc; padding: 28px 40px; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { font-size: 13px; color: #9ca3af; line-height: 1.7; }
        .footer a { color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="logo">ZOCO</div>
        <h1>¡Suscripción activada con éxito! 🎉</h1>
        <p>Nueva alta recibida el {{ $startDate }}</p>
    </div>

    <div class="body">
        <p class="greeting">
            Hola,<br><br>
            Se ha completado una nueva suscripción en la plataforma. A continuación encontrarás todos los detalles. El PDF con la factura está adjunto a este correo.
        </p>

        <div class="plan-card">
            <div class="plan-name">Plan contratado</div>
            <div class="plan-title">{{ $plan }}</div>
            <div class="plan-details">
                <div class="detail-row">
                    <span class="label">Empresa</span>
                    <span class="value">{{ $companyName }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Facturación</span>
                    <span class="value">
                        <span class="badge">{{ ucfirst($billingCycle) }}</span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="label">Importe</span>
                    <span class="value">€{{ number_format($amount, 2, ',', '.') }}/{{ $billingCycle === 'anual' ? 'año' : 'mes' }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">Fecha de inicio</span>
                    <span class="value">{{ $startDate }}</span>
                </div>
                <div class="detail-row">
                    <span class="label">IBAN</span>
                    <span class="value">{{ $iban }}</span>
                </div>
            </div>
        </div>

        <div class="info-box">
            <strong>📎 Factura adjunta</strong>
            Se adjunta el PDF de la factura correspondiente a esta suscripción para su registro y contabilidad.
        </div>
    </div>

    <div class="footer">
        <p>
            Este correo se ha generado automáticamente desde la plataforma Zoco.<br>
            ¿Tienes preguntas? Escríbenos a <a href="mailto:soporte@zocoenergia.com">soporte@zocoenergia.com</a>
        </p>
    </div>
</div>
</body>
</html>
