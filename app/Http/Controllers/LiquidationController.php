<?php

namespace App\Http\Controllers;

use App\Exports\LiquidationsExport;
use App\Helpers\UserHelper;
use App\Http\Models\Account;
use App\Http\Models\Liquidation;
use App\Http\Models\Order;
use App\Http\Models\User;
use App\Http\Models\Marketer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use App\Mail\LiquidationMail;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use MongoDB\BSON\UTCDateTime;

class LiquidationController extends Controller
{

    //funcion para obtener un listado de liquidaciones
    public static function index(){

        $liquidations = Liquidation::where('owner', session()->get('userLogged')['_id'])->get();

        return response()->json(['liquidations' => $liquidations], 200);
    }


    //función para ejecutar las liquidaciones
    public static function liquidate(){//ESTA FUNCIÓN SE HACE EL DÍA 25 DE CADA MES Y CREA, GUARDA Y ENVIA LAS LIQUIDACIONES A CADA UNO DE LOS USUARIOS DESDE 25 DÍAS ANTES HASTA EL DÍA QUE SE HACE

        //Para cada uno de los usuarios saco los pedidos activados que se han hecho en el mes ( día 25 en 25)
        $users = User::all();

        foreach ($users as $userId => $user){

            //Creo un array en el que voy a ir metiendo los pedidos
            $orders = [
                'own' => [],
                'others' => []
            ];

            //Guardare cuantos pedidos totales hay
            $totalOrders = [
                'own' => 0,
                'others' => 0
            ];

            //Obtengo el día 25 de este mes y del anterior
            $startDate = Carbon::now()->startOfMonth()->subMonth()->addDays(24);
            $limitDate = Carbon::now()->startOfMonth()->addDays(24)->endOfDay();


            //SACO LOS PEDIDOS DEL USUARIO MISMO

            //Saco las cuentas que contengan al menos un pedido
            $userAccs = Account::where('createdBy', $user['_id'])
                ->where('orders', '>', [])
                ->get()->toArray();

            //Guardo los pedidos de las cuentas propias
            foreach ($userAccs as $account){

                //Filtro los pedidos para solo guardar los que estan en estado activado y la fecha de activación este entre el 25 del mes anterior y el 25 de este mes (Si no tiene fecha de activación no va a salir)
                $activatedStatus = array_filter($account['orders'], function ($orderNow) use($startDate, $limitDate){

                    $activationDate = new Carbon($orderNow['activationDate']);

                    return ($orderNow['status'] === 'a' && $orderNow['activationDate'] !== '' && $activationDate->isBetween($startDate, $limitDate));
                });

                $orders['own'] = array_merge($orders['own'], $activatedStatus);
            }

            $totalOrders['own'] = count($orders['own']);



            //SACO LOS PEDIDOS DE SUS AGENTES

            //Saco los usuarios que tiene por debajo
            $userList = UserHelper::hierarchy($user['_id']);

            array_unshift($userList, $user->toArray());

            //Aqui lo que hago es solo dejar el array con los ids de los usuarios
            $userList = array_reduce($userList, function ($carry, $userNow) use ($user) {
                $id = $userNow['_id'];
                if (!isset($carry[$id]) && $id !== $user['_id']) $carry[$id] = $userNow;
                return $carry;
            }, []);
            $userList = array_keys($userList);


            //Para cada uno de los usuarios
            foreach ($userList as $agentKey => $agent){

                $agentDb = User::where('_id', $agent)->first();

                //Creo el nombre para el indice de guardar pedidos
                $agentInd = $agentDb['firstName'] . ' ' . $agentDb['lastName'] . '/' . $agentDb['_id'];

                //Saco las cuentas que contengan al menos un pedido
                $agentAccs = Account::where('createdBy', $agent)
                    ->where('orders', '>', [])
                    ->get()->toArray();

                //Guardo los pedidos de las cuentas propias
                foreach ($agentAccs as $account){

                    //Filtro los pedidos para solo guardar los que estan en estado activado
                    $activatedStatusOrders = array_filter($account['orders'], function ($orderNow) use($startDate, $limitDate){

                        $activationDate = new Carbon($orderNow['activationDate']);

                        return ($orderNow['status'] === 'a' && $orderNow['activationDate'] !== '' && $activationDate->isBetween($startDate, $limitDate));
                    });

                    //Compruebo si hay un indice de ese agente y si no lo hay lo creo
                    if (!isset($orders['others'][$agentInd])){
                        $orders['others'][$agentInd] = $activatedStatusOrders;
                    }else{
                        $orders['others'][$agentInd] = array_merge($orders['others'][$agentInd], $activatedStatusOrders);
                    }

                    $totalOrders['others'] = $totalOrders['others'] + count($activatedStatusOrders);
                }
            }


            //Información que le voy a pasar al PDF
            $viewData = [
                'company' => [
                    'name' => 'Asercord Energía'
                ],
                'dates' => [
                    'start' => $startDate->format('d/m/y'),
                    'end' => $limitDate->format('d/m/y')
                ],
                'owner' => $user,
                'totalOrders' => $totalOrders,
                'orders' => $orders,
            ];


            //CREO EL PDF

            // Renderiza la vista Blade a HTML
            $html = View::make('PDFs.liquidation')->with('viewData', $viewData)->render();

            // Crea una instancia de Dompdf
            $pdf = new Dompdf();

            // Configura el tamaño de la página
            $pdf->setPaper('A4', 'landscape'); // 'A4' es el tamaño de la página y 'landscape' es la orientación horizontal

            // Crea una instancia de Options
            $options = new Options();

            // Asigna las opciones al objeto Dompdf
            $pdf->setOptions($options);

            // Carga el HTML en Dompdf
            $pdf->loadHtml($html);

            // Renderiza el PDF
            $pdf->render();

            // Descarga el pdf
            //$pdf->stream('pdfPrueba.pdf');

            $liquidationName = time() . 'liquidacion' . $user['_id'] . '.pdf';


            // Guarda el PDF generado en el almacenamiento
            Storage::disk('liquidation')->put($liquidationName, $pdf->output());


            //Info para enviar el mensaje

            $data = [
                'subject' => 'Liquidación Asercord Energía crm desde ' . $startDate->format('d/m/y') . ' hasta ' . $limitDate->format('d/m/y'),
                'content' => 'La liquidación es un resumen de los pedidos activados propios y de tus agentes entre las fechas señaladas.',
                'button' => [
                    'url' => 'https://crm.asercordenergia.com/liquidations',
                    'text' => 'Ir al los informes'
                ]
            ];

            //lo envio por correo
            //Mail::to($user['email'])->send(new LiquidationMail($data));
            Mail::to('franperez@segenet.es')->send(new LiquidationMail($data));


            //Guardo la liquidación en la bbdd
            Liquidation::create([
                'liquidationName' => $liquidationName,
                'user' => $user['_id'],
                'dates' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $limitDate->format('Y-m-d')
                ],
                'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        }
    }


    //función para liquidar un usuario
    public static function liquidateUser(Request $request){

        $user = $request['user'];
        $dates = $request['dates'];
        $userLogged = $request['userLogged'];
        $marketers = $request['marketers'];
        $liquidationStatuses = $request['liquidationStatuses'];
        $typeDownload = $request['typeDownload'];
        $seeMarketerNumbers = $request['seeMarketerNumbers'];
        $deductHierarchyCommissions = $request['deductHierarchyCommissions'];
        $cups = [];
        $enterprise = $request['enterprise'];
        $userSubdomain = $request['userSubdomain'];
        $extras = $request->input('extras', []);
        $userListOwn = UserHelper::hierarchy(Auth::user()->_id);
        $userListOwn = array_column($userListOwn, '_id');

        $userList = UserHelper::hierarchy($user['_id']);
        $userList = array_column($userList, '_id');

        // Estados por defecto
        $activeStatusCodes = ['a'];
        $downStatusCodes = ['b'];

        //Comprobación si es Kuvi para los estados
        $isKuvi = false;
        if ($user['label'] === 'Usuario subdominio' && (string) $user['_id'] === '6909faa9232c09035a03f3b2') $isKuvi = true;
        else if ($user['label'] !== 'Usuario subdominio') {
            $isKuvi = UserHelper::getUserSubdomain((string) $user['_id'])['_id'] === '6909faa9232c09035a03f3b2';
        }

        // Si es el usuario específico, cambiar estados
        if ($isKuvi) {
            $activeStatusCodes = [
                'pendiente_de_liquidar',
                'pendiente_de_liquidar_adelantado'
            ];

            $downStatusCodes = ['pendiente_de_retrocomisin'];
        }


        array_walk($marketers, function(&$marketer) {
             if ($marketer === 'Sin comercializadora') {
                $marketer = '';
            }
        });

        //Saco el primer y el último día
        $startDate = !empty($dates['start']) ? Carbon::make($dates['start']) : null;
        $limitDate = !empty($dates['end'])   ? Carbon::make($dates['end'])   : null;


        //Variable para guardar toda información pasada
        $viewData = [
            'company' => [
                'name' => $enterprise['name']
            ],
            'dates' => [
                'start' => $startDate ? $startDate->format('d/m/y') : 'Sin fecha',
                'end'   => $limitDate ? $limitDate->format('d/m/y') : 'Sin fecha'
            ],
            'owner' => $user,
            'totalOrders' => [
                'own' => 0,
                'others' => 0
            ],
            'totalCommission' => [
                'own' => 0,
                'others' => [],
                'global' => 0
            ],
            'totalSubdomainCommission' => [
                'own' => 0,
                'others' => [],
                'global' => 0
            ],
            'orders' => [
                'own' => [],
                'others' => []
            ],
            'seeMarketerNumbers' => $seeMarketerNumbers,
            'enterprise' => $enterprise,
            'userSubdomain' => $userSubdomain,
            'extras' => [],

        ];

        foreach ($extras as $extra) {

            if (!isset($extra['userId'])) continue;

            $agent = User::where('_id', $extra['userId'])->first();

            if (!$agent) continue;

            $agentInd = $agent['firstName'].' '.$agent['lastName'].'/'.$agent['_id'];

            if (!isset($viewData['extras'][$agentInd])) {
                $viewData['extras'][$agentInd] = [];
            }

            $amount = (float) $extra['amount'];

            $viewData['extras'][$agentInd][] = [
                'concept' => $extra['concept'],
                'amount' => $amount,
                'type' => $extra['type'] ?? null
            ];

            if (!isset($viewData['totalCommission']['others'][$agentInd])) {
                $viewData['totalCommission']['others'][$agentInd] = $amount;
            } else {
                $viewData['totalCommission']['others'][$agentInd] += $amount;
            }

            $viewData['totalCommission']['global'] += $amount;
        }




        //SACO LOS CONTRATOS PROPIOS


        //Si es otro distinto a Zoco saco las comercializadoras de Zoco por si tienen contratos con nosotros
        if($userLogged['_id'] !== '65cb57489c2c285441086a43'){
            $zocoMarketers = Marketer::where('createdBy', '65cb57489c2c285441086a43')->get()->pluck('name')->toArray();
            $marketers= array_values(array_unique(array_merge($zocoMarketers, $marketers)));
        }




        //Fechas
        $startISO    = !empty($dates['start']) ? date('Y-m-d', strtotime(str_replace('/', '-', $dates['start']))) : null;
        $endISO      = !empty($dates['end'])   ? date('Y-m-d', strtotime(str_replace('/', '-', $dates['end'])))   : null;
        $startDt     = $startISO ? new UTCDateTime((new \DateTime($startISO.' 00:00:00', new \DateTimeZone('UTC')))->getTimestamp()*1000) : null;
        $endDt       = $endISO   ? new UTCDateTime((new \DateTime($endISO.' 23:59:59',  new \DateTimeZone('UTC')))->getTimestamp()*1000)  : null;
        $startCarbon = $startISO ? new Carbon($dates['start']) : null;
        $endCarbon   = $endISO   ? new Carbon($dates['end'])   : null;

        //Estados de liquidación
        $allowedA = array_values(array_intersect($liquidationStatuses, ['nl','al','cl','tl']));
        $allowedB = array_values(array_intersect($liquidationStatuses, ['ad','md','tm']));

        if ($userSubdomain['_id'] === '6909faa9232c09035a03f3b2') {

            $liquidationFilterA = true;
            $liquidationFilterB = true;

        }
        else {

            $liquidationFilterA = [ '$in' => [ '$liquidationStatus', $allowedA ] ];
            $liquidationFilterB = [ '$in' => [ '$liquidationStatus', $allowedB ] ];

        }

        if ($user['_id'] === Auth::user()->_id || in_array($user['_id'], $userListOwn)) {

            $match = [
                '$or' => [
                    ['usersIds'  => $user['_id']],
                    ['assignedTo'=> $user['_id']]
                ]
            ];

        }
        else {

            $match = [
                '$and' => [
                    ['usersIds'  => $user['_id']],
                    ['assignedTo'=> Auth::user()->_id]
                ]
            ];
        }


        //Saco los contratos propios que estén activados o en baja entre las fechas
        $pipeline = [
            // 1) Contratos del usuario
            [
                '$match' => $match,
            ],

            // 2) Normalización de fechas
            [
                '$set' => [
                    'statuses' => [
                        '$map' => [
                            'input' => [ '$ifNull' => [ '$statuses', [] ] ],
                            'as' => 's',
                            'in' => [
                                '$mergeObjects' => [
                                    '$$s',
                                    [
                                        'dt' => [
                                            '$cond' => [
                                                [
                                                    '$or' => [
                                                        [ '$eq' => [ '$$s.date', null ] ],
                                                        [ '$eq' => [ '$$s.date', '' ] ],
                                                    ],
                                                ],
                                                null,
                                                [
                                                    '$let' => [
                                                        'vars' => [
                                                            'dtFull' => [
                                                                '$dateFromString' => [
                                                                    'dateString' => '$$s.date',
                                                                    'format' => '%Y-%m-%d %H:%M:%S',
                                                                    'onError' => null,
                                                                    'onNull' => null,
                                                                ],
                                                            ],
                                                        ],
                                                        'in' => [
                                                            '$ifNull' => [
                                                                '$$dtFull',
                                                                [
                                                                    '$dateFromString' => [
                                                                        'dateString' => [
                                                                            '$substrBytes' => [ '$$s.date', 0, 10 ],
                                                                        ],
                                                                        'format' => '%Y-%m-%d',
                                                                        'onError' => null,
                                                                        'onNull' => null,
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'activationDt' => [
                        '$cond' => [
                            [
                                '$or' => [
                                    [ '$eq' => [ '$activationDate', null ] ],
                                    [ '$eq' => [ '$activationDate', '' ] ],
                                ],
                            ],
                            null,
                            [
                                '$dateFromString' => [
                                    'dateString' => '$activationDate',
                                    'format' => '%Y-%m-%d',
                                    'onError' => null,
                                    'onNull' => null,
                                ],
                            ],
                        ],
                    ],

                    'lowDt' => [
                        '$cond' => [
                            [
                                '$or' => [
                                    [ '$eq' => [ '$lowDate', null ] ],
                                    [ '$eq' => [ '$lowDate', '' ] ],
                                ],
                            ],
                            null,
                            [
                                '$dateFromString' => [
                                    'dateString' => '$lowDate',
                                    'format' => '%Y-%m-%d',
                                    'onError' => null,
                                    'onNull' => null,
                                ],
                            ],
                        ],
                    ],

                    'installments' => [
                        '$map' => [
                            'input' => [
                                '$cond' => [
                                    [ '$isArray' => '$installmentCommissions' ],
                                    '$installmentCommissions',
                                    [],
                                ],
                            ],
                            'as' => 'ic',
                            'in' => [
                                '$mergeObjects' => [
                                    '$$ic',
                                    [
                                        'dt' => [
                                            '$cond' => [
                                                [
                                                    '$or' => [
                                                        [ '$eq' => [ '$$ic.date', null ] ],
                                                        [ '$eq' => [ '$$ic.date', '' ] ],
                                                    ],
                                                ],
                                                null,
                                                [
                                                    '$dateFromString' => [
                                                        'dateString' => '$$ic.date',
                                                        'format' => '%Y-%m-%d',
                                                        'onError' => null,
                                                        'onNull' => null,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // 3) Último status por fecha
            [
                '$set' => [
                    'latestStatus' => [
                        '$reduce' => [
                            'input' => [ '$ifNull' => [ '$statuses', [] ] ],
                            'initialValue' => [
                                'dt' => null,
                                'code' => null,
                            ],
                            'in' => [
                                '$cond' => [
                                    [
                                        '$gt' => [
                                            [
                                                '$ifNull' => [
                                                    '$$this.dt',
                                                    new \MongoDB\BSON\UTCDateTime(0),
                                                ],
                                            ],
                                            [
                                                '$ifNull' => [
                                                    '$$value.dt',
                                                    new \MongoDB\BSON\UTCDateTime(0),
                                                ],
                                            ],
                                        ],
                                    ],
                                    '$$this',
                                    '$$value',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // 4) Filtro final
            [
                '$match' => [
                    '$expr' => [
                        '$and' => [
                            [ '$in' => [ '$latestStatus.code', [ ...$activeStatusCodes, ...$downStatusCodes ] ] ],

                            [
                                '$or' => [
                                    [ '$in' => [ '$marketer', $marketers ] ],
                                    [ '$eq' => [ [ '$type' => '$marketer' ], 'missing' ] ],
                                    [ '$eq' => [ '$marketer', null ] ],
                                    [ '$eq' => [ '$marketer', '' ] ],
                                ],
                            ],

                            [
                                '$or' => [
                                    [
                                        '$and' => [
                                            [ '$in' => [ '$latestStatus.code', $activeStatusCodes ] ],
                                            $liquidationFilterA,
                                            [
                                                '$or' => [
                                                    [
                                                        '$and' => [
                                                            [ '$eq' => [ [ '$size' => '$installments' ], 0 ] ],
                                                            [ '$ne' => [ '$activationDt', null ] ],
                                                            ...($startDt ? [[ '$gte' => [ '$activationDt', $startDt ] ]] : []),
                                                            ...($endDt ? [[ '$lte' => [ '$activationDt', $endDt ] ]] : []),
                                                        ],
                                                    ],
                                                    [
                                                        '$gt' => [
                                                            [
                                                                '$size' => [
                                                                    '$filter' => [
                                                                        'input' => '$installments',
                                                                        'as' => 'i',
                                                                        'cond' => [
                                                                            '$and' => [
                                                                                [ '$ne' => [ '$$i.dt', null ] ],
                                                                                [ '$gte' => [ '$$i.dt', $startDt ] ],
                                                                                [ '$lte' => [ '$$i.dt', $endDt ] ],
                                                                            ],
                                                                        ],
                                                                    ],
                                                                ],
                                                            ],
                                                            0,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        '$and' => [
                                            [ '$in' => [ '$latestStatus.code', $downStatusCodes ] ],
                                            [ '$ne' => [ '$lowDt', null ] ],
                                            [ '$gte' => [ '$lowDt', $startDt ] ],
                                            [ '$lte' => [ '$lowDt', $endDt ] ],
                                            [
                                                '$or' => [
                                                    [ '$eq' => [ '$userSubdomain', $userSubdomain['_id'] ] ],
                                                    $liquidationFilterB,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // 5) Preparar account como ObjectId
            [
                '$set' => [
                    'accountObjId' => [
                        '$convert' => [
                            'input' => '$account',
                            'to' => 'objectId',
                            'onError' => null,
                            'onNull' => null,
                        ],
                    ],
                ],
            ],

            // 6) Buscar la cuenta y traer solo el CIF
            [
                '$lookup' => [
                    'from' => 'accounts',
                    'let' => [
                        'accountObjId' => '$accountObjId',
                    ],
                    'pipeline' => [
                        [
                            '$match' => [
                                '$expr' => [
                                    '$eq' => [
                                        '$_id',
                                        '$$accountObjId',
                                    ],
                                ],
                            ],
                        ],
                        [
                            '$project' => [
                                '_id' => 0,
                                'accountCIF' => [
                                    '$ifNull' => [
                                        '$CIF',
                                        [
                                            '$ifNull' => [
                                                '$cif',
                                                [
                                                    '$ifNull' => [
                                                        '$NIF',
                                                        [
                                                            '$ifNull' => [
                                                                '$nif',
                                                                '$taxId',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            '$limit' => 1,
                        ],
                    ],
                    'as' => 'accountLookup',
                ],
            ],

            // 7) Añadir accountCIF al contrato
            [
                '$set' => [
                    'accountCIF' => [
                        '$first' => '$accountLookup.accountCIF',
                    ],
                ],
            ],

            // 8) Limpiar auxiliares
            [
                '$unset' => [
                    'accountObjId',
                    'accountLookup',
                ],
            ],
        ];

        $cursor = Order::raw(fn($c) => $c->aggregate($pipeline, [
            'allowDiskUse' => true,
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]));
        $ownOrders = iterator_to_array($cursor, false);


        //Recorro los contratos
        foreach ($ownOrders as $ownOrder) {

            //Saco el último estado
            $statuses = $ownOrder['statuses'];
            usort($statuses, fn($a, $b) => strcmp($b['date'], $a['date']));
            $lastStatus = $statuses[0];


            //Compruebo si es activado y carterizado saco las comisiones de installmentCommissions, sino lo saco normal
            if (($lastStatus['code'] === 'a' || $lastStatus['code'] === 'pendiente_de_liquidar' || $lastStatus['code'] === 'pendiente_de_liquidar_adelantado') && isset($ownOrder['installmentCommissions'])){

                //Recorro cada iteración y miro a ver si cuadra entre las fechas
                foreach ($ownOrder['installmentCommissions'] as $interval){

                    $commDate = new Carbon($interval['date']);

                    //Si hay alguna que esté entre las fechas lo meto
                    $afterStart = $startCarbon ? $commDate->gte($startCarbon) : true;
                    $beforeEnd  = $endCarbon   ? $commDate->lte($endCarbon)   : true;

                    if ($afterStart && $beforeEnd){ //Añado el contrato
                        if(!$deductHierarchyCommissions){
                            $agentCommission = null;
                            $agentDecommission = null;

                            foreach (($interval['commissions']['breakdown'] ?? []) as $item) {
                                if ($item['userId'] == $user['_id']) {
                                    $agentCommission = $item['commission'];
                                    break;
                                }
                            }

                            foreach (($ownOrder['decommissions']['breakdown'] ?? []) as $item) {
                                if ($item['userId'] == $user['_id']) {
                                    $agentDecommission = $item['commission'];
                                    break;
                                }
                            }
                        }else{
                            $agentCommission = self::calculateNetCommission($interval['commissions']['breakdown'] ?? [], $user['_id']);
                            $agentDecommission = self::calculateNetCommission($ownOrder['decommissions']['breakdown'] ?? [], $user['_id']);
                        }

                        $viewData['orders']['own'][] = [
                            'identifier' => $ownOrder['identifier'],
                            'name' => $ownOrder['name'],
                            'CUPS' => $ownOrder['CUPS'],
                            'createdAt' => $ownOrder['createdAt'],
                            'province' => $ownOrder['province'],
                            'accountCIF' => $ownOrder['accountCIF'],
                            'marketerOrderNumber' => $ownOrder['marketerOrderNumber'],
                            'marketer' => $ownOrder['marketer'],
                            'fee' => $ownOrder['fee'],
                            'hiredPotency' => $ownOrder['hiredPotency'],
                            'product' => $ownOrder['product'],
                            'activationDate' => $interval['date'], //Le meto como fecha de activación la fecha de esta carterización
                            'lowDate' => $ownOrder['lowDate'],
                            'consumption' => $ownOrder['consumption'],
                            'statuses' => $ownOrder['statuses'],
                            'agentCommission' => $agentCommission,
                            'subdomainCommission' => $interval['commissions']['subdomain'],
                            'agentDecommission' => $agentDecommission,
                            'subdomainDecommission' => $ownOrder['decommissions']['subdomain'] ?? 0,
                            'extras' => $ownOrder['extras'],
                            'createdBy' => $ownOrder['createdBy'],
                            'assignedTo' => $ownOrder['assignedTo']
                        ];

                        //Sumo comisiones
                        $viewData['totalOrders']['own']++;
                        $viewData['totalCommission']['own'] += (float) ($agentCommission ?? 0);
                        $viewData['totalSubdomainCommission']['own'] += (float) ($interval['commissions']['subdomain'] ?? 0);
                    }
                }
            }else{
                if(!$deductHierarchyCommissions){
                    $agentCommission = null;
                    $agentDecommission = null;

                    foreach (($ownOrder['commissions']['breakdown'] ?? []) as $item) {
                        if ($item['userId'] == $user['_id']) {
                            $agentCommission = $item['commission'];
                            break;
                        }
                    }

                    foreach (($ownOrder['decommissions']['breakdown'] ?? []) as $item) {
                        if ($item['userId'] == $user['_id']) {
                            $agentDecommission = $item['commission'];
                            break;
                        }
                    }
                }else{
                    $agentCommission = self::calculateNetCommission($ownOrder['commissions']['breakdown'] ?? [], $user['_id']);
                    $agentDecommission = self::calculateNetCommission($ownOrder['decommissions']['breakdown'] ?? [], $user['_id']);
                }

                //Añado el contrato
                $viewData['orders']['own'][] = [
                    'identifier' => $ownOrder['identifier'],
                    'name' => $ownOrder['name'],
                    'CUPS' => $ownOrder['CUPS'],
                    'createdAt' => $ownOrder['createdAt'],
                    'province' => $ownOrder['province'],
                    'accountCIF' => $ownOrder['accountCIF'],
                    'marketerOrderNumber' => $ownOrder['marketerOrderNumber'],
                    'marketer' => $ownOrder['marketer'],
                    'fee' => $ownOrder['fee'],
                    'hiredPotency' => $ownOrder['hiredPotency'],
                    'product' => $ownOrder['product'],
                    'activationDate' => $ownOrder['activationDate'],
                    'lowDate' => $ownOrder['lowDate'],
                    'consumption' => $ownOrder['consumption'],
                    'statuses' => $ownOrder['statuses'],
                    'agentCommission' => $agentCommission,
                    'subdomainCommission' => $ownOrder['commissions']['subdomain'],
                    'agentDecommission' => $agentDecommission,
                    'subdomainDecommission' => $ownOrder['decommissions']['subdomain'] ?? 0,
                    'extras' => $ownOrder['extras'] ?? [],
                    'createdBy' => $ownOrder['createdBy'],
                    'assignedTo' => $ownOrder['assignedTo']
                ];

                if ($lastStatus['code'] === 'a' || $lastStatus['code'] === 'pendiente_de_liquidar' || $lastStatus['code'] === 'pendiente_de_liquidar_adelantado'){
                    //Sumo comisiones
                    $viewData['totalOrders']['own']++;
                    $viewData['totalCommission']['own'] += (float) ($agentCommission ?? 0);
                    $viewData['totalSubdomainCommission']['own'] += (float) ($ownOrder['commissions']['subdomain'] ?? 0);
                }else{
                    //Sumo decomisiones
                    $viewData['totalOrders']['own']++;
                    $viewData['totalCommission']['own'] += (float) ($agentDecommission ?? 0) * -1;
                    $viewData['totalSubdomainCommission']['own'] += (float) ($ownOrder['decommissions']['subdomain'] ?? 0) * -1;

                }



                //Meto el _id del contrato para dps poder liquidar
                if (!key_exists($ownOrder['_id'], $cups))
                    array_push($cups, $ownOrder['_id']);

            }

        }


        //Ordeno mis pedidos alfabeticamente
        usort($viewData['orders']['own'], function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        //Sumo las comisiones al total
        $viewData['totalCommission']['global'] += $viewData['totalCommission']['own']; //Sumo al total tb
        $viewData['totalSubdomainCommission']['global'] += $viewData['totalSubdomainCommission']['own']; //Sumo al total subdominio tb




        //SACO LOS PEDIDOS DE MIS AGENTES


        //Saco los usuarios de los otros subdominios que tengan algún contrato tramitado con Zoco durante esas fechas
        if ($user['_id'] === '65cb57489c2c285441086a43') {
            $orders = Order::whereRaw([
                'usersIds' => ['$in' => [$user['_id']]], // que incluya a Zoco
                '$expr'    => ['$gt' => [['$size' => '$usersIds'], 1]] // compartido con alguien más
            ])->get();

            $extraUsers = $orders->flatMap(function ($order) use ($user) {
                return collect($order->usersIds)
                    ->filter(fn($id) => $id !== null && $id !== $user['_id']); // me quedo con los otros subdominios
            })->unique()->toArray();

            $userList = array_merge($userList, $extraUsers);
        }


        //Para cada uno de los usuarios
        foreach ($userList as $agentKey => $agent){

            $agentDb = User::where('_id', $agent)->first();

            //Creo el nombre para el indice de guardar pedidos
            $agentInd = $agentDb['firstName'] . ' ' . $agentDb['lastName'] . '/' . $agentDb['_id'];


            if (array_search($agentDb->_id, $userListOwn)) {

                $match = [
                    '$or' => [
                        ['usersIds'  => $agentDb->_id],
                        ['assignedTo'=> $agentDb->_id]
                    ]
                ];

            }
            else {

                $match = [
                    '$and' => [
                        ['usersIds'  => $agentDb->_id],
                        ['assignedTo'=> Auth::user()->_id]
                    ]
                ];
            }


            //Saco los contratos propios que estén activados o en baja entre las fechas
            $pipeline = [
                // Contratos del usuario
                [
                    '$match' => $match
                ],

                // Fechas normales: dt en cada status y activationDt
                [
                    '$set' => [
                        'statuses' => [
                            '$map' => [
                                'input' => ['$ifNull' => ['$statuses', []]],
                                'as' => 's',
                                'in' => [
                                    '$mergeObjects' => [
                                        '$$s',
                                        [
                                            'dt' => [
                                                '$let' => [
                                                    'vars' => [
                                                        'dtFull' => [
                                                            '$dateFromString' => [
                                                                'dateString' => '$$s.date',
                                                                'format'     => '%Y-%m-%d %H:%M:%S',
                                                                'onError'    => null,
                                                                'onNull'     => null
                                                            ]
                                                        ]
                                                    ],
                                                    'in' => [
                                                        '$ifNull' => [
                                                            '$$dtFull',
                                                            [
                                                                '$dateFromString' => [
                                                                    'dateString' => [
                                                                        '$substrBytes' => ['$$s.date', 0, 10]
                                                                    ],
                                                                    'format'  => '%Y-%m-%d',
                                                                    'onError' => null,
                                                                    'onNull'  => null
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],

                        'activationDt' => [
                            '$dateFromString' => [
                                'dateString' => '$activationDate',
                                'format'     => '%Y-%m-%d',
                                'onError'    => null,
                                'onNull'     => null
                            ]
                        ],

                        'lowDt' => [
                            '$dateFromString' => [
                                'dateString' => '$lowDate',
                                'format'     => '%Y-%m-%d',
                                'onError'    => null,
                                'onNull'     => null
                            ]
                        ],

                        'installments' => [
                            '$map' => [
                                'input' => [
                                    '$cond' => [
                                        ['$isArray' => '$installmentCommissions'],
                                        '$installmentCommissions',
                                        []
                                    ]
                                ],
                                'as' => 'ic',
                                'in' => [
                                    '$mergeObjects' => [
                                        '$$ic',
                                        [
                                            'dt' => [
                                                '$dateFromString' => [
                                                    'dateString' => '$$ic.date',
                                                    'format'     => '%Y-%m-%d',
                                                    'onError'    => null,
                                                    'onNull'     => null
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],

                // Último status por fecha
                [
                    '$set' => [
                        'latestStatus' => [
                            '$reduce' => [
                                'input' => ['$ifNull' => ['$statuses', []]],
                                'initialValue' => [
                                    'dt' => null,
                                    'code' => null
                                ],
                                'in' => [
                                    '$cond' => [
                                        [
                                            '$gt' => [
                                                [
                                                    '$ifNull' => [
                                                        '$$this.dt',
                                                        new \MongoDB\BSON\UTCDateTime(0)
                                                    ]
                                                ],
                                                [
                                                    '$ifNull' => [
                                                        '$$value.dt',
                                                        new \MongoDB\BSON\UTCDateTime(0)
                                                    ]
                                                ]
                                            ]
                                        ],
                                        '$$this',
                                        '$$value'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],

                // Filtro final
                [
                    '$match' => [
                        '$expr' => [
                            '$and' => [
                                [
                                    '$in' => [
                                        '$latestStatus.code',
                                        [
                                            ...$activeStatusCodes,
                                            ...$downStatusCodes
                                        ]
                                    ]
                                ],

                                [
                                    '$or' => [
                                        [
                                            '$in' => [
                                                '$marketer',
                                                $marketers
                                            ]
                                        ],

                                        [
                                            '$eq' => [
                                                [
                                                    '$type' => '$marketer'
                                                ],
                                                'missing'
                                            ]
                                        ],

                                        [
                                            '$eq' => [
                                                '$marketer',
                                                null
                                            ]
                                        ],

                                        [
                                            '$eq' => [
                                                '$marketer',
                                                ''
                                            ]
                                        ]
                                    ]
                                ],

                                [
                                    '$or' => [
                                        // ACTIVADO
                                        [
                                            '$and' => [
                                                [
                                                    '$in' => [
                                                        '$latestStatus.code',
                                                        $activeStatusCodes
                                                    ]
                                                ],

                                                $liquidationFilterA,

                                                [
                                                    '$or' => [
                                                        // Sin carterizaciones
                                                        [
                                                            '$and' => [
                                                                [
                                                                    '$eq' => [
                                                                        [
                                                                            '$size' => '$installments'
                                                                        ],
                                                                        0
                                                                    ]
                                                                ],

                                                                [
                                                                    '$ne' => [
                                                                        '$activationDt',
                                                                        null
                                                                    ]
                                                                ],

                                                                [
                                                                    '$gte' => [
                                                                        '$activationDt',
                                                                        $startDt
                                                                    ]
                                                                ],

                                                                [
                                                                    '$lte' => [
                                                                        '$activationDt',
                                                                        $endDt
                                                                    ]
                                                                ]
                                                            ]
                                                        ],

                                                        // Con carterizaciones
                                                        [
                                                            '$gt' => [
                                                                [
                                                                    '$size' => [
                                                                        '$filter' => [
                                                                            'input' => '$installments',
                                                                            'as' => 'i',
                                                                            'cond' => [
                                                                                '$and' => [
                                                                                    [
                                                                                        '$ne' => [
                                                                                            '$$i.dt',
                                                                                            null
                                                                                        ]
                                                                                    ],

                                                                                    [
                                                                                        '$gte' => [
                                                                                            '$$i.dt',
                                                                                            $startDt
                                                                                        ]
                                                                                    ],

                                                                                    [
                                                                                        '$lte' => [
                                                                                            '$$i.dt',
                                                                                            $endDt
                                                                                        ]
                                                                                    ]
                                                                                ]
                                                                            ]
                                                                        ]
                                                                    ]
                                                                ],
                                                                0
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ],

                                        // BAJA
                                        [
                                            '$and' => [
                                                [
                                                    '$in' => [
                                                        '$latestStatus.code',
                                                        $downStatusCodes
                                                    ]
                                                ],

                                                [
                                                    '$ne' => [
                                                        '$lowDt',
                                                        null
                                                    ]
                                                ],

                                                [
                                                    '$gte' => [
                                                        '$lowDt',
                                                        $startDt
                                                    ]
                                                ],

                                                [
                                                    '$lte' => [
                                                        '$lowDt',
                                                        $endDt
                                                    ]
                                                ],

                                                $liquidationFilterB
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],

                // Normalizar account para cruzar con accounts._id
                [
                    '$set' => [
                        'accountObjId' => [
                            '$cond' => [
                                [
                                    '$eq' => [
                                        [
                                            '$type' => '$account'
                                        ],
                                        'objectId'
                                    ]
                                ],
                                '$account',
                                [
                                    '$convert' => [
                                        'input' => '$account',
                                        'to' => 'objectId',
                                        'onError' => null,
                                        'onNull' => null
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],

                // Traer solo el CIF de accounts
                [
                    '$lookup' => [
                        'from' => 'accounts',
                        'localField' => 'accountObjId',
                        'foreignField' => '_id',
                        'pipeline' => [
                            [
                                '$project' => [
                                    '_id' => 0,
                                    'accountCIF' => [
                                        '$ifNull' => [
                                            '$CIF',
                                            [
                                                '$ifNull' => [
                                                    '$cif',
                                                    [
                                                        '$ifNull' => [
                                                            '$NIF',
                                                            [
                                                                '$ifNull' => [
                                                                    '$nif',
                                                                    '$taxId'
                                                                ]
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'as' => 'accountLookup'
                    ]
                ],

                // Dejar el CIF directamente en el order
                [
                    '$set' => [
                        'accountCIF' => [
                            '$first' => '$accountLookup.accountCIF'
                        ]
                    ]
                ],

                // Limpiar campos auxiliares
                [
                    '$unset' => [
                        'accountObjId',
                        'accountLookup'
                    ]
                ]
            ];

            $cursor = Order::raw(fn($c) => $c->aggregate($pipeline, [
                'allowDiskUse' => true,
                'typeMap' => [
                    'root' => 'array',
                    'document' => 'array',
                    'array' => 'array'
                ]
            ]));
            $agentOrders = iterator_to_array($cursor, false);

            //Recorro los contratos
            foreach ($agentOrders as $agentOrder) {

                //Saco el último estado
                $statuses = $agentOrder['statuses'];
                usort($statuses, fn($a, $b) => strcmp($b['date'], $a['date']));
                $lastStatus = $statuses[0];


                //Compruebo si es activado y carterizado saco las comisiones de installmentCommissions, sino lo saco normal
                if (($lastStatus['code'] === 'a' || $lastStatus['code'] === 'pendiente_de_liquidar' || $lastStatus['code'] === 'pendiente_de_liquidar_adelantado') && isset($agentOrder['installmentCommissions'])){

                    //Recorro cada iteración y miro a ver si cuadra entre las fechas
                    foreach ($agentOrder['installmentCommissions'] as $interval){

                        $commDate = new Carbon($interval['date']);

                        //Si hay alguna que esté entre las fechas lo meto
                        if ($commDate->isBetween($startCarbon, $endCarbon)){
                            if(!$deductHierarchyCommissions){
                                $agentCommission = null;
                                $agentDecommission = null;

                                foreach (($interval['commissions']['breakdown'] ?? []) as $item) {
                                    if ($item['userId'] == $user['_id']) {
                                        $agentCommission = $item['commission'];
                                        break;
                                    }
                                }

                                foreach (($ownOrder['decommissions']['breakdown'] ?? []) as $item) {
                                    if ($item['userId'] == $user['_id']) {
                                        $agentDecommission = $item['commission'];
                                        break;
                                    }
                                }
                            }else{
                                $agentCommission = self::calculateNetCommission($interval['commissions']['breakdown'] ?? [], $user['_id']);
                                $agentDecommission = self::calculateNetCommission($agentOrder['decommissions']['breakdown'] ?? [], $user['_id']);
                            }

                            //Añado el contrato
                            $viewData['orders']['others'][$agentInd][] = [
                                'identifier' => $agentOrder['identifier'],
                                'name' => $agentOrder['name'],
                                'CUPS' => $agentOrder['CUPS'],
                                'createdAt' => $agentOrder['createdAt'],
                                'province' => $agentOrder['province'],
                                'accountCIF' => $agentOrder['accountCIF'],
                                'marketerOrderNumber' => $agentOrder['marketerOrderNumber'],
                                'marketer' => $agentOrder['marketer'],
                                'fee' => $agentOrder['fee'],
                                'hiredPotency' => $agentOrder['hiredPotency'],
                                'product' => $agentOrder['product'],
                                'activationDate' => $interval['date'], //Le meto como fecha de activación la fecha de esta carterización
                                'lowDate' => $agentOrder['lowDate'],
                                'consumption' => $agentOrder['consumption'],
                                'statuses' => $agentOrder['statuses'],
                                'agentCommission' => $agentCommission,
                                'subdomainCommission' => $interval['commissions']['subdomain'],
                                'agentDecommission' => $agentDecommission,
                                'subdomainDecommission' => $ownOrder['decommissions']['subdomain'] ?? 0,
                                'extras' => $agentOrder['extras'],
                                'createdBy' => $agentOrder['createdBy'],
                                'assignedTo' => $agentOrder['assignedTo']
                            ];


                            //Sumo comisiones
                            $viewData['totalOrders']['others']++;

                            if (!isset($viewData['totalCommission']['others'][$agentInd]))
                                $viewData['totalCommission']['others'][$agentInd] = (float) ($agentCommission ?? 0);
                            else
                                $viewData['totalCommission']['others'][$agentInd] += (float) ($agentCommission ?? 0);

                            if (!isset($viewData['totalSubdomainCommission']['others'][$agentInd]))
                                $viewData['totalSubdomainCommission']['others'][$agentInd] = (float) ($interval['commissions']['subdomain'] ?? 0);
                            else
                                $viewData['totalSubdomainCommission']['others'][$agentInd] += (float) ($interval['commissions']['subdomain'] ?? 0);
                        }
                    }


                }else{
                    if(!$deductHierarchyCommissions){
                        $agentCommission = null;
                        $agentDecommission = null;

                        foreach (($agentOrder['commissions']['breakdown'] ?? []) as $item) {
                            if ($item['userId'] == $user['_id']) {
                                $agentCommission = $item['commission'];
                                break;
                            }
                        }

                        foreach (($agentOrder['decommissions']['breakdown'] ?? []) as $item) {
                            if ($item['userId'] == $user['_id']) {
                                $agentDecommission = $item['commission'];
                                break;
                            }
                        }
                    }else{
                        $agentCommission = self::calculateNetCommission($agentOrder['commissions']['breakdown'] ?? [], $user['_id']);
                        $agentDecommission = self::calculateNetCommission($agentOrder['decommissions']['breakdown'] ?? [], $user['_id']);
                    }

                    //Añado el contrato
                    $viewData['orders']['others'][$agentInd][] = [
                        'identifier' => $agentOrder['identifier'],
                        'name' => $agentOrder['name'],
                        'CUPS' => $agentOrder['CUPS'],
                        'createdAt' => $agentOrder['createdAt'],
                        'province' => $agentOrder['province'],
                        'accountCIF' => $agentOrder['accountCIF'],
                        'marketerOrderNumber' => $agentOrder['marketerOrderNumber'],
                        'marketer' => $agentOrder['marketer'],
                        'fee' => $agentOrder['fee'],
                        'hiredPotency' => $agentOrder['hiredPotency'],
                        'product' => $agentOrder['product'],
                        'activationDate' => $agentOrder['activationDate'],
                        'lowDate' => $agentOrder['lowDate'],
                        'consumption' => $agentOrder['consumption'],
                        'statuses' => $agentOrder['statuses'],
                        'agentCommission' => $agentCommission,
                        'subdomainCommission' => $agentOrder['commissions']['subdomain'],
                        'agentDecommission' => $agentDecommission,
                        'subdomainDecommission' => $agentOrder['decommissions']['subdomain'] ?? 0,
                        'extras' => $agentOrder['extras'],
                        'createdBy' => $agentOrder['createdBy'] ?? null,
                        'assignedTo' => $agentOrder['assignedTo']
                    ];

                    if ($lastStatus['code'] === 'a' || $lastStatus['code'] === 'pendiente_de_liquidar' || $lastStatus['code'] === 'pendiente_de_liquidar_adelantado'){

                        //Sumo comisiones
                        $viewData['totalOrders']['others']++;

                        if (!isset($viewData['totalCommission']['others'][$agentInd]))
                            $viewData['totalCommission']['others'][$agentInd] = (float) ($agentCommission ?? 0);
                        else
                            $viewData['totalCommission']['others'][$agentInd] += (float) ($agentCommission ?? 0);


                        if (!isset($viewData['totalSubdomainCommission']['others'][$agentInd]))
                            $viewData['totalSubdomainCommission']['others'][$agentInd] = (float) ($agentOrder['commissions']['subdomain'] ?? 0);
                        else
                            $viewData['totalSubdomainCommission']['others'][$agentInd] += (float) ($agentOrder['commissions']['subdomain'] ?? 0);

                    }
                    else{
                        //Sumo comisiones
                        $viewData['totalOrders']['others']++;

                        //Comisión usuario
                        if (!isset($viewData['totalCommission']['others'][$agentInd]))
                            $viewData['totalCommission']['others'][$agentInd] = (float) ($agentDecommission ?? 0) * -1;
                        else
                            $viewData['totalCommission']['others'][$agentInd] += (float) ($agentDecommission ?? 0) * -1;

                        //Comisión subdominio
                        if (!isset($viewData['totalSubdomainCommission']['others'][$agentInd]))
                            $viewData['totalSubdomainCommission']['others'][$agentInd] = (float) ($agentOrder['decommissions']['subdomain'] ?? 0) * -1;
                        else
                            $viewData['totalSubdomainCommission']['others'][$agentInd] += (float) ($agentOrder['decommissions']['subdomain'] ?? 0) * -1;

                    }

                    //Meto el _id del contrato para dps poder liquidar
                    if (!key_exists($agentOrder['_id'], $cups))
                        array_push($cups, $agentOrder['_id']);
                }

            }

            if (isset($viewData['orders']['others'][$agentInd])){
                //Ordeno mis pedidos alfabeticamente
                usort($viewData['orders']['others'][$agentInd], function($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                });

                //Sumo las comisiones al total
                $viewData['totalCommission']['global'] += $viewData['totalCommission']['others'][$agentInd]; //Sumo al total tb
                $viewData['totalSubdomainCommission']['global'] += $viewData['totalSubdomainCommission']['others'][$agentInd]; //Sumo al total asercord tb
            }
        };


        //dd($viewData['orders']['others']);


        //Para probar PDF en local
        /*
         *
         * // Renderiza la vista Blade a HTML
    $html = View::make('PDFs.liquidation')
        ->with('viewData', $viewData)
        ->render();

    // Configura las opciones de Dompdf
    $options = new Options();

    // En local mejor desactivar cosas que bloquean
    $options->set('isHtml5ParserEnabled', false);
    $options->set('isRemoteEnabled', false);
    $options->setChroot(public_path()); // restringe a /public

    // Añade un contexto http/ssl con timeout corto
    $context = stream_context_create([
        'http' => ['timeout' => 5],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);

    $pdf = new Dompdf($options);
    $pdf->setHttpContext($context);

    // Configura el tamaño de la página
    $pdf->setPaper('A4', 'landscape');

    // Carga el HTML en Dompdf
    $pdf->loadHtml($html);

    // Renderiza el PDF
    // (sube límites SOLO en local para depuración)
    if (app()->environment('local')) {
        @ini_set('memory_limit','512M');
        @set_time_limit(120);
    }
    $pdf->render();

    $liquidationName = time() . 'liquidacion' . $user['_id'] . '.pdf';

// Guarda el PDF generado en el almacenamiento local
    Storage::disk('liquidation')->put($liquidationName, $pdf->output());

//Guardo la liquidación en la bbdd
    Liquidation::create([
        'liquidationName' => $liquidationName,
        'owner'           => $userLogged['_id'],
        'liquidateUser'   => $user['_id'],
        'totalOrders'     => $viewData['totalOrders']['own'] + $viewData['totalOrders']['others'],
        'totalCommision'  => $viewData['totalCommision']['global'],
        'cups'            => $cups,
        'liquidated'      => false,
        'dates'           => [
            'start' => $startDate->format('Y-m-d'),
            'end'   => $limitDate->format('Y-m-d'),
        ],
        'createdAt'       => Carbon::now()->format('Y-m-d H:i:s')
    ]);
         *
         *
         * */






        //CREACIÓN EXCEL
        if ($typeDownload === 'excel'){

            $liquidationName = time() . 'liquidacion' . $user['_id'] . '.xlsx';

            $excelData = Excel::raw(new LiquidationsExport($viewData), \Maatwebsite\Excel\Excel::XLSX);

            Storage::disk('liquidation')->put($liquidationName, $excelData);

            //Guardo la liquidación en la bbdd
            Liquidation::create([
                'liquidationName' => $liquidationName,
                'owner' => $userLogged['_id'],
                'liquidateUser' => $user['_id'],
                'totalOrders' => $viewData['totalOrders']['own'] + $viewData['totalOrders']['others'],
                'totalCommission' => $viewData['totalCommission']['global'],
                'cups' => $cups,
                'liquidated' => false,
                'dates' => [
                    'start' => $startDate ? $startDate->format('Y-m-d') : null,
                    'end'   => $limitDate ? $limitDate->format('Y-m-d') : null,
                ],
                'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
            ]);

            return Excel::download(new LiquidationsExport($viewData), 'orders.xlsx');

        }else{

            //CREO EL PDF

            // Renderiza la vista Blade a HTML
            $html = View::make('PDFs.liquidation')->with('viewData', $viewData)->render();

            // Configura las opciones de Dompdf
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            // Crea una instancia de Dompdf
            $pdf = new Dompdf();


            $pdf->setOptions($options);

            // Configura el tamaño de la página
            $pdf->setPaper('A4', 'landscape'); // 'A4' es el tamaño de la página y 'portrait' es la orientación

            // Carga el HTML en Dompdf
            $pdf->loadHtml($html);

            // Renderiza el PDF
            $pdf->render();

            $liquidationName = time() . 'liquidacion' . $user['_id'] . '.pdf';

            // Guarda el PDF generado en el almacenamiento
            Storage::disk('liquidation')->put($liquidationName, $pdf->output());

            //Guardo la liquidación en la bbdd
            Liquidation::create([
                'liquidationName' => $liquidationName,
                'owner' => $userLogged['_id'],
                'liquidateUser' => $user['_id'],
                'totalOrders' => $viewData['totalOrders']['own'] + $viewData['totalOrders']['others'],
                'totalCommission' => $viewData['totalCommission']['global'],
                'cups' => $cups,
                'liquidated' => false,
                'dates' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $limitDate->format('Y-m-d')
                ],
                'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        }

    }


    //función para obtener los usuarios para liquidar ( solo con algun contrato ) ( para zoco saco tb subdominios que tengan contratos tramitados con el)
    public static function fetchUsersToLiquidate(Request $request){


        //Saco los usuarios de los contratos que tengan assignedTo y id con _id sesión iniciada
        $pipeline = [
            [
                '$match' => [
                    '$expr' => [
                        '$gt' => [
                            ['$size' => '$usersIds'],
                            1
                        ]
                    ],
                    'assignedTo' => [
                        '$exists' => true,
                        '$eq' => Auth::user()->_id
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => null,
                    'agents' => ['$addToSet' => '$createdBy']
                ]
            ]
        ];

        $agents = Order::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });

        $agents = $agents[0]['agents'] ?? [];

        return response()->json(['agents' => $agents], 200);
    }


    //función para liquidar informes creados sin liquidar
    public static function liquidateLiquidation(Request $request){

        $id = $request['id'];

        $liquidation = Liquidation::where('_id', $id)->first();


        //Saco las cuentas para liquidar los contratos
        foreach ($liquidation->cups as $orderId){

            //Saco la cuenta
            $order = Order::where('_id', $orderId)->first();


            if ((string) $request['userSubdomain']['_id'] === '6909faa9232c09035a03f3b2') {

                $statuses = $order->statuses ?? [];

                if (!empty($statuses)) {

                    // Ordenar por fecha descendente
                    $lastStatus = collect($statuses)
                        ->sortByDesc(fn($s) => strtotime($s['date']))
                        ->first();
                    $lastCode   = $lastStatus['code'] ?? null;
                    $newCode = null;


                    switch ($lastCode) {

                        case 'pendiente_de_liquidar':
                            $newCode = 'liquidado';
                            break;

                        case 'pendiente_de_liquidar_adelantado':
                            $newCode = 'pago_adelantado';
                            break;

                        case 'pendiente_de_retrocomisin':
                            $newCode = 'baja_anticipada_retrocomisionada';
                            break;
                    }

                    if ($newCode) {

                        $statuses[] = [
                            'code'    => $newCode,
                            'date'    => Carbon::now()->format('Y-m-d H:i:s'),
                            'creator' => Auth::id(),
                        ];

                        $order->statuses = $statuses;
                    }
                }
            }
            else {
                //Cambio el estado de liquidación
                if ($order['liquidationStatus'] === 'nl')
                    $order['liquidationStatus'] = 'al';
                elseif ($order['liquidationStatus'] === 'cl')
                    $order['liquidationStatus'] = 'tl';
            }



            //Añadir estado comisionado (Assessoria 3.0)
            $alreadyClosed = collect($order['statuses'] ?? [])
                ->contains(fn ($status) => $status['code'] === 'c');

            if ($request['userSubdomain'] === '68244d08cc1c5dd113030c52' && !$alreadyClosed){
                $statuses = $order->statuses ?? [];

                $statuses[] = [
                    'code'    => 'c',
                    'date'    => Carbon::now()->format('Y-m-d H:i:s'),
                    'creator' => Auth::id(),
                ];

                $order->statuses = $statuses;
            }

            $order->save();
        }

        //Lo pongo en liquidado
        $liquidation->liquidated = true;
        $liquidation->save();


        return response()->json(['message' => 'Liquidación liquidada']);
    }


    //función para eliminar una liquidación
    public static function deleteLiquidation($id){


        $liquidation = Liquidation::where('_id', $id)->first();

        //Elimino el archivo de la liquidación
        Storage::disk('liquidation')->delete($liquidation['liquidationName']);

        //Lo elimino de la bbdd
        Liquidation::destroy($id);

        return response()->json(['message' => 'La liquidación ha sido eliminada correctamente'], 201);
    }

    private static function calculateNetCommission($breakdown, $userId){
        $agentCommission = null;
        $userLevel = null;

        foreach (($breakdown ?? []) as $item) {
            if ($item['userId'] == $userId) {
                $agentCommission = $item['commission'];
                $userLevel = $item['level'] ?? null;
                break;
            }
        }

        if ($agentCommission === null || $userLevel === null) {
            return $agentCommission;
        }

        foreach (($breakdown ?? []) as $item) {
            if (isset($item['level']) && $item['level'] > $userLevel) {
                $agentCommission -= (float) $item['commission'];
            }
        }

        return $agentCommission;
    }
}
