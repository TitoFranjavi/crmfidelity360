<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plantilla crm</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;500;700&display=swap"
        rel="stylesheet">

    <style type="text/css">
        * {
            padding: 0;
            margin: 0;
            font-family: Helvetica;
        }

        .parent-body {
            width: 100%;
            padding: 50px;
            box-sizing: border-box;
            color: #012C68;
        }

        a {
            color: #012C68;
            text-decoration: none;
        }

        .centered {
            align-items: center;
            justify-content: center;
        }

        .column {
            flex-direction: column;
        }

        .align-center {
            align-items: center;
        }

        .justify-center {
            justify-content: center;
        }

        .justify-between {
            justify-content: space-between !important;
        }

        .justify-around {
            justify-content: space-around;
        }

        .justify-start {
            justify-content: flex-start;
        }

        .justify-end {
            justify-content: flex-end;
        }


        /*TEXTO*/
        .bold {
            font-weight: bold;
        }

        .thin {
            font-weight: lighter;
        }

        .italic {
            font-style: italic;
        }

        .blueColor {
            color: #026DA5;
        }

        .ellipsis {
            overflow: hidden !important;
            white-space: nowrap !important;
            text-overflow: ellipsis !important;
        }

        /*DISTRIBUCIÓN*/
        .w-100 {
            width: 100%;
        }

        .w-50 {
            width: 50%;
        }

        .mr-auto {
            margin-right: auto !important;
        }

        .mt-auto {
            margin-top: auto !important;
        }

        .mb-auto {
            margin-bottom: auto !important;
        }

        .ml-auto {
            margin-left: auto !important;
        }

        .mx-auto {
            margin-right: auto !important;
            margin-left: auto !important;
        }

        .my-auto {
            margin-top: auto !important;
            margin-bottom: auto !important;
        }

        /*Logo*/
        .logo {
            width: 100%;
            text-align: center;
            padding: 20px 0 20px 0;
        }

        .logo img {
            width: 107px;
            height: 107px;
        }


        /*TABLAS*/
        table {
            margin: auto;
            border: none;
            border-collapse: collapse;
            text-align: center;
        }

        tr {
            border: none;
        }

        th {
            border: none;
            padding: 3px;
        }

        td {
            border: none;
            padding: 3px;
            width: auto;
            font-size: 13px;
        }

        .text-center {
            text-align: center;
        }

        .no-Borders {
            border: none;
        }

        .no-Borders * {
            border: none;
        }


        /* ESTILO APARTE */
        .princ-div {
            width: 70%;
            margin: auto;

            /*sombra*/
            /*-webkit-box-shadow: 5px 5px 12px 0px rgba(0,0,0,0.75);
            -moz-box-shadow: 5px 5px 12px 0px rgba(0,0,0,0.75);
            box-shadow: 5px 5px 12px 0px rgba(0,0,0,0.75);*/

            border: 1px solid #F5F6F8;
            border-radius: 20px;
            overflow: hidden;
        }

        .princ-div>div {
            padding: 30px;
        }

        .logo-div {
            background-color: #F5F6F8;

            display: flex;
            justify-content: center;

        }

        .logo-div img {
            width: 200px;
            max-width: 200px;
            height: auto;
        }

        .button {
            color: #2192FF;
            border: 1px solid #2192FF;
            background-color: rgba(214, 235, 255, 0.7);
            border-radius: 20px;
            padding: 10px;
            cursor: pointer;
        }


        @media screen and (max-width: 600px) {


            .princ-div {
                width: 100%;
                margin: auto;

                /*sombra*/
                /*-webkit-box-shadow: 5px 5px 12px 0px rgba(0,0,0,0.75);
                -moz-box-shadow: 5px 5px 12px 0px rgba(0,0,0,0.75);
                box-shadow: 5px 5px 12px 0px rgba(0,0,0,0.75);*/

                border: 1px solid #F5F6F8;
                border-radius: 20px;
                overflow: hidden;
            }

            .empty-column {
                width: 0;
            }

            .footer-contact {
                width: 100%;
            }

            .date-column {
                display: none;
            }

            .notifications-resume tr td {
                width: 50%;
            }

            .header,
            .content {
                padding: 10px;
            }
        }

        @media screen and (min-width: 600px) and (max-width: 1500px) {
            .empty-column {
                width: 10%;
            }
        }
    </style>
</head>

<body>
    <div class="parent-body">


        <div class="princ-div">

            <div class="logo-div">
                <img class="mx-auto" src="https://crm.asercordenergia.com/assets/logo/crm-logo-entero.png" alt="">
            </div>

            <div class="info-div">


                <p style="margin: 5px 0">{!! $data['content'] !!}</p>

                @yield('content')

                @if(isset($data->incidence))
                <h2 style="margin: 5px 0">Información de la incidencia</h2>
                <p style="margin: 5px 0">{!! $data['incidence'] !!}</p>
                @endif

                {{-- Tabla de datos del contrato --}}
                @if(!empty($data['contract']))
                    <table style="width: 100%; margin-top: 20px; border-collapse: collapse; font-size: 13px;">
                        <thead>
                        <tr>
                            <td colspan="2" style="background-color: #F5F6F8; padding: 10px 14px; font-weight: bold; color: #012C68; border: 1px solid #e5e5e5;">
                                📋 Datos del contrato
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach([
                            'Cliente'          => $data['contract']['account'],
                            'CIF'              => $data['contract']['CIF'],
                            'CUPS'             => $data['contract']['CUPS'],
                            'Comercializadora' => $data['contract']['marketer'],
                            'Tarifa'           => $data['contract']['fee'],
                            'IBAN'             => $data['contract']['iban'],
                            'Email'            => $data['contract']['email'],
                            'Teléfono'         => $data['contract']['phone'],
                            'Observaciones'    => $data['contract']['observations'],
                        ] as $label => $value)
                            @if(!empty($value))
                                <tr>
                                    <td style="padding: 8px 14px; border: 1px solid #e5e5e5; background-color: #fafafa; color: #012C68; font-weight: bold; width: 40%; text-align: left;">
                                        {{ $label }}
                                    </td>
                                    <td style="padding: 8px 14px; border: 1px solid #e5e5e5; color: #333; text-align: left;">
                                        {{ $value }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                @endif


                <!--accede al pedio-->
                <table style="margin-top: 50px">

                    <tr>
                        <td>
                            <p style="margin: 15px 0; font-size: 12px; opacity: .4">Si quieres revisar tu contrato accede mediante el siguiente enlace.</p>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <a href="{{ $data['button']['url'] }}" class="button" target="_blank">
                                {{ $data['button']['text'] }}
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 20px 0;">
                            <hr style="border: none; border-top: 1px solid #e5e5e5;">
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <p style="margin: 15px 0; font-size: 14px; color: #555;">
                                <strong>⚠ Este es un correo automático.</strong><br>
                                Por favor, no responda a este mensaje, ya que esta dirección no está monitorizada.<br/>
                                Si necesita ayuda, contacte con nuestro equipo de atención al cliente.
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>

</html>
