{{-- resources/views/privacy-policy/show.blade.php --}}
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Política de Privacidad – {{ $enterprise->name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(145deg, #e6eaf0, #fdfdfd);
            font-family: 'Segoe UI', sans-serif;
            color: #333;
        }

        .privacy-container {
            max-width: 960px;
            margin: 4rem auto;
            padding: 3rem;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .back-btn {
            position: absolute;
            top: 2rem;
            left: 2rem;
            font-size: 1.8rem;
            color: #0d6efd;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: #084298;
            transform: scale(1.1);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 2rem;
            font-weight: 700;
            text-align: center;
            color: #0d6efd;
        }

        h5 {
            margin-top: 2rem;
            font-weight: 600;
            color: #0d6efd;
        }

        ul {
            padding-left: 1.2rem;
        }

        ul li {
            margin-bottom: 0.5rem;
        }

        a {
            color: #0d6efd;
        }

        a:hover {
            text-decoration: underline;
        }

        .last-update {
            font-style: italic;
            font-size: 0.95rem;
            color: #555;
            margin-top: 2rem;
        }
    </style>
</head>

<body>

    <div class="privacy-container">
        <a href="javascript:history.back()" class="back-btn" title="Volver atrás">
            <i class="bi bi-arrow-left-circle-fill"></i>
        </a>

        <h1><i class="bi bi-shield-lock-fill me-2"></i>Política de Privacidad</h1>

        <p>
            En <strong>{{ $enterprise->name }}</strong>, responsable de este CRM para asesorías energéticas,
            consideramos clave proteger tus datos personales.
            Esta Política de Privacidad explica cómo recogemos, utilizamos y protegemos la información a través de
            nuestro sistema de gestión de clientes y contratos energéticos.
        </p>

        <h5><i class="bi bi-person-fill-lock me-2"></i>1. Responsable del Tratamiento</h5>
        <p>
            <strong>Empresa:</strong> {{ $enterprise->name }}<br>
            <strong>NIF/CIF:</strong> {{ $user->dni }}<br>
            <strong>Dirección:</strong> {{ $user->address }}<br>
            <strong>Email:</strong> <a href="mailto:{{ $enterprise->email }}">{{ $enterprise->email }}</a><br>
            <strong>Teléfono:</strong> {{ $user->phone }}
        </p>

        <h5><i class="bi bi-collection-fill me-2"></i>2. Datos que Recopilamos</h5>
        <ul>
            <li><strong>Datos identificativos:</strong> nombre, apellidos, DNI/NIE, fecha de nacimiento.</li>
            <li><strong>Datos de contacto:</strong> dirección postal, teléfono, correo electrónico.</li>
            <li><strong>Datos de contrato energético:</strong> CUPS, tipo de tarifa, comercializadora, punto de
                suministro.</li>
            <li><strong>Datos técnicos:</strong> dirección IP, navegador, sistema operativo.</li>
            <li><strong>Datos de consumo y facturación:</strong> historiales de consumo, facturas subidas (PDF o
                imagen), lecturas de contador.</li>
            <li><strong>Datos económicos:</strong> comisiones de asesorías, importes de facturas, información bancaria
                (si aplica).</li>
            <li><strong>Otros datos:</strong> métricas de uso del CRM, interacciones con emails y notificaciones.</li>
        </ul>

        <h5><i class="bi bi-gear-fill me-2"></i>3. Finalidades del Tratamiento</h5>
        <ul>
            <li>Gestionar la relación comercial con clientes de asesorías energéticas y sus contratos.</li>
            <li>Envío de notificaciones y comunicaciones: contratos, avisos de facturación, alertas de consumo.</li>
            <li>Análisis de consumo y generación de informes personalizados de ahorro energético.</li>
            <li>Gestión de facturas y documentos: subida, almacenamiento seguro y recuperación para consultas.</li>
            <li>Cálculo y asignación de comisiones para asesores y comerciales.</li>
            <li>Integración y envío de datos a terceras partes: comercializadoras energéticas, pasarelas de pago
                (Stripe), plataformas de envío de correos (SendCloud), API de WhatsApp para notificaciones, servicio de
                OCR para extracción de datos de facturas.</li>
            <li>Cumplimiento de obligaciones legales y fiscales derivadas de la actividad de asesoría energética.</li>
            <li>Mejora continua del CRM: análisis interno de métricas de uso para optimizar funcionalidades.</li>
        </ul>

        <h5><i class="bi bi-journal-check me-2"></i>4. Base Legal</h5>
        <ul>
            <li><strong>Ejecución de un contrato:</strong> cuando se formaliza una asesoría energética o solicitud de
                servicios vinculados.</li>
            <li><strong>Consentimiento del interesado:</strong> para envíos de comunicaciones comerciales, suscripciones
                a boletines o campañas puntuales de marketing.</li>
            <li><strong>Obligación legal:</strong> conservación de datos contables, fiscales y contractuales conforme a
                la normativa vigente.</li>
            <li><strong>Interés legítimo:</strong> mejora y optimización del CRM, prevención de fraudes y gestión
                interna de la plataforma.</li>
        </ul>

        <h5><i class="bi bi-clock-history me-2"></i>5. Plazos de Conservación</h5>
        <ul>
            <li>Mientras exista relación contractual o comercial con el cliente.</li>
            <li>Hasta 6 años tras la finalización del contrato, para cumplir obligaciones fiscales y de defensa legal.
            </li>
            <li>Datos de marketing se conservarán hasta que el usuario retire su consentimiento.</li>
        </ul>

        <h5><i class="bi bi-share-fill me-2"></i>6. Cesiones y Encargados de Tratamiento</h5>
        <ul>
            <li><strong>Comercializadoras energéticas:</strong> para formalizar o gestionar el alta de suministros.</li>
            <li><strong>Proveedores de pago (Stripe):</strong> para gestionar cobros y pagos relacionados con comisiones
                o servicios contratados.</li>
            <li><strong>Servicios de email marketing y notificaciones (SendCloud):</strong> para envíos de facturas,
                avisos y comunicaciones comerciales.</li>
            <li><strong>API de WhatsApp Business:</strong> para envío de notificaciones de estado de contrato o alertas
                urgentes.</li>
            <li><strong>Servicio de OCR:</strong> para extracción de datos de facturas subidas vía WhatsApp.</li>
            <li><strong>Proveedor de hosting o infraestructura:</strong> para garantizar disponibilidad y seguridad de
                la plataforma.</li>
        </ul>

        <h5><i class="bi bi-globe2 me-2"></i>7. Transferencias Internacionales</h5>
        <p>
            Algunos de nuestros proveedores (Stripe, SendCloud, WhatsApp Business API) pueden procesar datos fuera del
            Espacio Económico Europeo (EEE).
            En dichos casos, se establecen las <strong>Cláusulas Contractuales Tipo</strong> aprobadas por la Comisión
            Europea.
        </p>

        <h5><i class="bi bi-unlock-fill me-2"></i>8. Derechos del Interesado</h5>
        <ul>
            <li><strong>Acceso:</strong> Saber si estamos tratando datos personales tuyos.</li>
            <li><strong>Rectificación:</strong> Solicitar la corrección de datos inexactos o incompletos.</li>
            <li><strong>Supresión (Derecho al olvido):</strong> Pedir la eliminación de tus datos cuando ya no sean
                necesarios.</li>
            <li><strong>Limitación del tratamiento:</strong> Pedir que se limite el uso de tus datos en determinados
                casos.</li>
            <li><strong>Portabilidad:</strong> Recibir tus datos en un formato estructurado y transmitírselos a otro
                responsable.</li>
            <li><strong>Oposición:</strong> Oponerte al tratamiento de tus datos por motivos relacionados con tu
                situación particular.</li>
            <li><strong>Retirar el consentimiento:</strong> Revocar tu consentimiento en cualquier momento.</li>
        </ul>
        <p>
            Para ejercer estos derechos, escribe a <strong><a
                    href="mailto:{{ $enterprise->email }}">{{ $enterprise->email }}</a></strong>
            o envía tu solicitud por correo postal a la dirección indicada arriba.
        </p>

        <h5><i class="bi bi-shield-check me-2"></i>9. Seguridad de los Datos</h5>
        <ul>
            <li>Cifrado SSL/TLS en todas las comunicaciones.</li>
            <li>Control de acceso mediante autenticación y roles.</li>
            <li>Copias de seguridad automáticas diarias con redundancia.</li>
            <li>Registro y monitoreo de accesos y acciones críticas.</li>
            <li>Políticas de contraseñas seguras y cambios periódicos.</li>
            <li>Protección frente a ataques comunes (inyección SQL, XSS, CSRF).</li>
        </ul>

        <h5><i class="bi bi-cookie me-2"></i>10. Uso de Cookies</h5>
        <ul>
            <li>Gestionar sesiones de usuario.</li>
            <li>Analizar métricas de uso (Google Analytics o similar).</li>
            <li>Almacenar preferencias de usuario (idioma, filtrado).</li>
        </ul>

        <h5><i class="bi bi-arrow-repeat me-2"></i>11. Cambios en esta Política</h5>
        <p>
            Nos reservamos el derecho a actualizar o modificar esta Política de Privacidad. Publicaremos la nueva
            versión en esta misma página y ajustaremos la fecha de “Última actualización”.
        </p>

        <p class="last-update"><em>Última actualización: {{ now()->format('d/m/Y') }}</em></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
