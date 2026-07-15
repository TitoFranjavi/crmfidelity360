<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pdf liquidación</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@300;500;700&display=swap"
          rel="stylesheet">

    <style type="text/css">
        * {
            font-family: Helvetica;
        }

        @page {
            margin: 50px;
        }

        .parent-body {
            width: 100%;
        //padding: 40px;
            box-sizing: border-box;
            color: #012C68;
        }

        a {
            color: #012C68;
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
        .bold{
            font-weight: bold;
        }

        .thin{
            font-weight: lighter;
        }

        .italic{
            font-style: italic;
        }

        .blueColor{
            color: #026DA5;
        }

        .ellipsis{
            overflow: hidden !important;
            white-space: nowrap !important;
            text-overflow: ellipsis !important;
        }

        /*DISTRIBUCIÓN*/
        .w-100{
            width: 100%;
        }

        .w-50{
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
        }

        .logo img {
            width: 90px;
            height: 90px;
        }


        /*TABLAS*/
        table{
            margin: auto;
            border: 1px solid #012C68;
            border-collapse: collapse;

        }

        tr{
            border: 1px solid #012C68;
        }

        th{
            border: 1px solid #012C68;
            padding: 3px;
        }

        td{
            border: 1px solid #012C68;
            padding: 3px;
            width: auto;
            font-size: 13px;
        }

        .text-center{
            text-align: center;
        }

        .no-Borders{
            border: none;
        }

        .no-Borders *{
            border: none;
        }

        @media screen and (max-width: 600px) {
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

            .header, .content {
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

@php
    function getLastState($statuses) {
            usort($statuses, function ($a, $b) {
                return strtotime($b['date']) <=> strtotime($a['date']);
            });

            // Retorna el primer elemento después de ordenar (el más reciente)
            return $statuses[0] ?? null; // Maneja el caso en el que no haya estados
        }

    $currentSubdomainId = (string) (
        data_get($viewData, 'userSubdomain._id')
        ?? data_get($viewData, 'userSubdomain.id')
        ?? ''
    );

    $hideConsumptionInLiquidation = in_array(
        $currentSubdomainId,
        [
            '6a2bd038dbe1be73f40da058',
        ],
        true
    );
@endphp

<body>
<div class="parent-body">

    <!-- HEADER -->
    <div class="logo">

        <table class="no-Borders w-100">

            <tr style="border: none">
                <td>
                    <table class="w-100" style="margin-left: 0">
                        <tr>
                            <td>
                                <!--REVISAR-->
                                <img
                                    src="{{ asset('assets/enterprises/'. $viewData['enterprise']['asset_folder'] .'/logos/mini-dark.png') }}"
                                    alt=""
                                    style="max-height: 150px; width: auto; max-width: 100%; object-fit: contain;"
                                >
                            </td>

                            {{--                                <td class="w-100">--}}
                            {{--                                    <p class="title bold my-auto blueColor mx-auto" style="padding-left:5px; font-size: 25px">Asercord Energía</p>--}}
                            {{--                                </td>--}}
                        </tr>
                    </table>
                </td>

                <td style="width: 40%">

                    <p class="bold italic" style="font-size: 22px; margin-bottom: 10px">Liquidación</p>

                    <p class="thin"><strong>Fechas:</strong> {{ $viewData['dates']['start'] }} - {{ $viewData['dates']['end'] }}</p>
                </td>
            </tr>
        </table>
    </div>


    <!--Info usuario y resumen-->
    <div style="margin-top: 15px">

        <table class="w-100 no-Borders">
            <tr>
                <td>
                    <div>
                        <!--Título-->
                        <p class="thin italic" style="font-size: 18px;">Liquidación para:</p>

                        <!--Contenido-->
                        <div style="margin-left: 30px">
                            <p style="margin: 10px 0;"><strong>{{ $viewData['owner']['firstName'] }} {{ $viewData['owner']['lastName'] }}</strong></p>
                            <p style="margin: 10px 0;">{{ $viewData['owner']['address'] }}</p>
                            <p style="margin: 10px 0;">{{ $viewData['owner']['phone'] }}</p>
                        </div>
                    </div>
                </td>

                <td>
                    <div>
                        <!--Título-->
                        <p class="thin italic" style="font-size: 18px;">Resumen:</p>

                        <!--Contenido-->
                        <div style="margin-left: 30px">
                            <p style="margin: 10px 0;"><strong>Contratos propios totales: </strong> {{ $viewData['totalOrders']['own'] }}</p>
                            <p style="margin: 10px 0;"><strong>Contratos de agentes totales: </strong> {{ $viewData['totalOrders']['others'] }}</p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>


    <!--Contenido-->

    <!--Contratos propios-->
    @if(count($viewData['orders']['own']) > 0)

        <p class="bold" style="font-size: 18px; margin-top: 25px; margin-bottom: 25px">Mis contratos</p>
        <!--<p class="bold" style="font-size: 18px; margin-top: 25px; margin-bottom: 25px">-</p>-->

        <table class="orderTable" style="margin-left: 0">

            <!--header-->
            <tr>
                @if($viewData['userSubdomain'] && $viewData['userSubdomain']['settings'] && $viewData['userSubdomain']['settings']['contractsIds'])
                    <th>id</th>
                @endif
                <th>Nombre contrato</th>
                <th>CUPS</th>
                @if($viewData['seeMarketerNumbers'])
                    <th>Nº pedido comerc.</th>
                @endif
                <th>Producto</th>
                <th>Fec. activación</th>
                <th>Fec. baja</th>
                @if(!$hideConsumptionInLiquidation)
                    <th>Consumo</th>
                @endif
                <th>Comisión</th>
                <!--<th>Comisión subdominio</th>-->
            </tr>


            <!--Contenido-->
            @foreach($viewData['orders']['own'] as $myOrderKey => $myOrder)

                @php
                    $lastStatus = getLastState($myOrder['statuses']);
                @endphp

                <tr>
                    <!--ID-->
                    @if($viewData['userSubdomain'] && $viewData['userSubdomain']['settings'] && $viewData['userSubdomain']['settings']['contractsIds'])
                        <td class="text-center ellipsis" style="width: 10%; padding: 5px 15px">{{ $myOrder['identifier'] ?? '-' }}</td>
                    @endif                    <!--Nombre de contrato-->
                    <td class="ellipsis" style="
                        max-width: {{ ($viewData['seeMarketerNumbers']) ? '100px' : '200px' }};
                        width: {{ ($viewData['seeMarketerNumbers']) ? '100px' : '200px' }};
                    padding: 5px 5px"><p class="ellipsis">{{ $myOrder['name'] }}</p></td>
                    <!--CUPS-->
                    <td class="text-center ellipsis" style="max-width: 150px; padding: 5px 15px">{{ $myOrder['CUPS'] }}</td>
                    <!--Nº pedido Comercializadora-->
                    @if($viewData['seeMarketerNumbers'])
                        <td class="text-center ellipsis" style="max-width: 180px;  padding: 5px 15px; white-space: nowrap">{{ $myOrder['marketerOrderNumber'] ?? '' }}</td>
                    @endif
                    <!--Comercializadora-->
                    <!--<td class="text-center ellipsis" style="max-width: 180px;  padding: 5px 15px; white-space: nowrap"> $myOrder['marketer'] </td>-->
                    <!--Producto-->
                    <td class="ellipsis" style="width: 160px; max-width: 160px;  padding: 5px 15px; white-space: nowrap">{{ $myOrder['marketer'] . ' ' . $myOrder['product'] }}</td>
                    <!--Fecha de activación ( días, mes, año )-->
                    <td class="text-center ellipsis" style="width: 80px; max-width: 80px; padding: 5px 15px">{{ date('d/m/Y', strtotime($myOrder['activationDate'])) }}</td>
                    <!--Fecha de baja ( días, mes, año )-->
                    <td class="text-center ellipsis" style="width: 80px; max-width: 80px; padding: 5px 15px">{{  (isset($myOrder['lowDate']) && $myOrder['lowDate'] !== '') ? date('d/m/Y', strtotime($myOrder['lowDate'])) : '' }}</td>
                    <!--Consumo-->
                    @if(!$hideConsumptionInLiquidation)
                        <td class="text-center ellipsis" style="width: auto; padding: 5px 15px">
                            {{ floor(doubleval($myOrder['consumption'])) }}
                        </td>
                    @endif
                    <td class="text-center ellipsis" style="width: auto; padding: 5px 15px">{{ $lastStatus['code'] === 'b' ? '-' : '' }}{{ $lastStatus['code'] === 'b' ? $myOrder['agentDecommission'] : $myOrder['agentCommission'] }}</td>
                    <!--<td class="text-center ellipsis" style="width: 80px; padding: 5px 15px">/*$lastStatus['code'] === 'b' ? '-' : ''}}$lastStatus['code'] === 'b' ? $myOrder['subdomainDecommission'] : $myOrder['subdomainCommission']}}*/</td>-->

                </tr>
            @endforeach

            <!--total comision-->
            <tr>
                <td style="font-weight: bold; text-align: center;">TOTAL</td>
                @if($viewData['userSubdomain'] && $viewData['userSubdomain']['settings'] && $viewData['userSubdomain']['settings']['contractsIds'])
                    <td></td>
                @endif
                <td></td>
                @if($viewData['seeMarketerNumbers'])
                    <td></td>
                @endif
                <td></td>
                <td></td>
                <td></td>

                @if(!$hideConsumptionInLiquidation)
                    <td></td>
                @endif

                <td class="text-center ellipsis" style="width: auto; padding: 5px 15px; font-weight: bold;">{{ $viewData['totalCommission']['own'] }}</td>
                <!--<td class="text-center ellipsis" style="width: 80px; padding: 5px 15px; font-weight: bold;">{{ $viewData['totalSubdomainCommission']['own'] }}</td>-->
            </tr>
        </table>

    @endif


    <!--Contratos de agentes-->
    @foreach($viewData['orders']['others'] as $agent => $agentOrders)

        <!--Si el cliente tiene al menos un contrato-->
        @if(count($agentOrders) > 0)
            <!--Titulo agente-->
            <p class="bold" style="font-size: 18px; margin-top: 25px; margin-bottom: 25px">Contratos de {{ explode('/', $agent)[0] }}</p>
            <!--<p class="bold" style="font-size: 18px; margin-top: 25px; margin-bottom: 25px">-</p>-->


            <!--Contratos-->
            <table style="margin-left: 0">

                <!--header-->
                <tr>
                    @if($viewData['userSubdomain'] && $viewData['userSubdomain']['settings'] && $viewData['userSubdomain']['settings']['contractsIds'])
                        <th>id</th>
                    @endif
                    <th>Nombre contrato</th>
                    <th>CUPS</th>
                    @if($viewData['seeMarketerNumbers'])
                        <th>Nº pedido comerc.</th>
                    @endif
                    <th>Producto</th>
                    <th>Fec. activación</th>
                    <th>Fec. baja</th>
                    @if(!$hideConsumptionInLiquidation)
                        <th>Consumo</th>
                    @endif
                    <th>Comisión</th>
                    <!--<th>Comisión subdominio</th>-->
                </tr>

                <!--Contenido-->
                @foreach($agentOrders as $orderKey => $order)

                    @php
                        $lastStatus = getLastState($order['statuses']);
                    @endphp


                    <tr>
                        <!--ID-->
                        @if($viewData['userSubdomain'] && $viewData['userSubdomain']['settings'] && $viewData['userSubdomain']['settings']['contractsIds'])
                            <td class="text-center ellipsis" style="padding: 5px 15px">{{ $order['identifier'] ?? '-' }}</td>
                        @endif
                        <!--Nombre de contrato-->
                        <td class="ellipsis" style="
                        max-width: {{ ($viewData['seeMarketerNumbers']) ? '100px' : '200px' }};
                        width: {{ ($viewData['seeMarketerNumbers']) ? '100px' : '200px' }};
                        padding: 5px 5px"><p class="ellipsis">{{ $order['name'] }}</p></td>
                        <!--CUPS-->
                        <td class="text-center ellipsis" style="width: 150px; padding: 5px 15px">{{ $order['CUPS'] }}</td>
                        <!--Nº pedido comercializadora-->
                        @if($viewData['seeMarketerNumbers'])
                            <td class="text-center ellipsis" style="max-width: 180px;  padding: 5px 15px; white-space: nowrap">{{ $order['marketerOrderNumber'] ?? '' }}</td>
                        @endif
                        <!--Comercializadora-->
                        <!--<td class="text-center ellipsis" style="max-width: 180px; width: auto; padding: 5px 15px"> $order['marketer'] }}</td>-->
                        <!--Producto-->
                        <td class="ellipsis" style="width: 160px; max-width: 160px; padding: 5px 15px">{{ $order['marketer'] . ' ' . $order['product'] }}</td>
                        <!--Fecha de activación ( días, mes, año )-->
                        <td class="text-center ellipsis" style="width: 80px; max-width: 80px; padding: 5px 15px">{{ date('d/m/Y', strtotime($order['activationDate'])) }}</td>
                        <!--Fecha de baja ( días, mes, año )-->
                        <td class="text-center ellipsis" style="width: 80px; max-width: 80px; padding: 5px 15px">{{  (isset($order['lowDate']) && $order['lowDate'] !== '') ? date('d/m/Y', strtotime($order['lowDate'])) : '' }}</td>
                        <!--Consumo-->
                        @if(!$hideConsumptionInLiquidation)
                            <td class="text-center ellipsis" style="width: auto; padding: 5px 15px">
                                {{ floor(doubleval($order['consumption'])) }}
                            </td>
                        @endif
                        <td class="text-center ellipsis" style="width: auto; padding: 5px 15px">{{ $lastStatus['code'] === 'b' ? '-' : '' }}{{ $lastStatus['code'] === 'b' ? ($order['agentDecommission'] ?? '') : ($order['agentCommission'] ?? '') }}</td>
                        <!--<td class="text-center ellipsis" style="width: 80px; padding: 5px 15px"> /*$lastStatus['code'] === 'b' ? '-' : ''}} $lastStatus['code'] === 'b' ? (isset($order['subdomainDecommission']) ? $order['subdomainCommission'] : 0) : (isset($order['subdomainCommission']) ? $order['subdomainCommission'] : 0)}}*/ </td>-->
                    </tr>
                @endforeach

                <!--total comision-->
                <tr>
                    <td style="font-weight: bold; text-align: center;">TOTAL</td>
                    @if($viewData['userSubdomain'] && $viewData['userSubdomain']['settings'] && $viewData['userSubdomain']['settings']['contractsIds'])
                        <td></td>
                    @endif
                    <td></td>
                    @if($viewData['seeMarketerNumbers'])
                        <td></td>
                    @endif
                    <td></td>
                    <td></td>
                    <td></td>

                    @if(!$hideConsumptionInLiquidation)
                        <td></td>
                    @endif

                    <td class="text-center ellipsis" style="width: auto; padding: 5px 15px; font-weight: bold;">{{ $viewData['totalCommission']['others'][$agent] }}</td>
                    <!--<td class="text-center ellipsis" style="width: 80px; padding: 5px 15px; font-weight: bold;">{{$viewData['totalSubdomainCommission']['others'][$agent]}}</td>-->
                </tr>
            </table>
        @endif
    @endforeach

    <!--Total a liquidar-->
    <table class="no-Borders" style="width: 50%; margin-top: 25px">
        <tr>
            <td style="font-size: 25px; font-weight: 600">TOTAL A LIQUIDAR</td>
            <td style="font-size: 25px; font-weight: 900">{{ $viewData['totalCommission']['global'] }}€</td>
        </tr>
    </table>

    <!--Total a liquidar subdominio-->
    <!--<table class="no-Borders" style="width: 50%; margin-top: 25px">
        <tr>
            <td style="font-size: 25px; font-weight: 600">TOTAL A LIQUIDAR SUBDOMINIO</td>
            <td style="font-size: 25px; font-weight: 900">{{ $viewData['totalSubdomainCommission']['global'] }}€</td>
        </tr>
    </table>-->
</div>
</body>
</html>
