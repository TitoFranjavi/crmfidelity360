<?php

namespace App\Helpers;


use App\Classes\Segenet\Bill;
use App\Http\Models\Segenet\Calendar;
use App\Http\Models\Segenet\IndexPrice;
use App\Http\Models\Segenet\IndexVariable;
use App\Http\Models\Segenet\Probe;
use App\Http\Models\Segenet\ProbeUser;
use App\Http\Models\Segenet\Contract;
use App\Http\Models\Segenet\EdsDevice;
use App\Http\Models\Segenet\ProbeValuesQuarter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SegenetHelper
{

    public static function getCurve($serial, $dates){

        //Si se han pasado fechas es porque es la primera vez, por lo que es establece por defecto desde el último registro una semana hacia atrás
        if (!isset($dates)) {
            $lastDate = ProbeValuesQuarter::where('probe_serial', $serial)->max('inserted_at');

            $dates = [
                'start' => Carbon::parse($lastDate)->subWeek()->toDateTimeString(),
                'end'   => $lastDate
            ];
        }

        //Saco los valores necesarios de la bbdd
        $values = ProbeValuesQuarter::select(
            'probe_values_quarters.*',
            'contracts.id AS contract_id',
            'contracts.from AS contract_from',
            'contracts.to AS contract_to',
            'contracts.is_indexed AS contract_is_indexed',
            'contracts.fee AS contract_fee',
            'contracts.adjust AS contract_adjust',
            'contracts.hired_potency_p1 AS contract_hired_potency_p1',
            'contracts.hired_potency_p2 AS contract_hired_potency_p2',
            'contracts.hired_potency_p3 AS contract_hired_potency_p3',
            'contracts.hired_potency_p4 AS contract_hired_potency_p4',
            'contracts.hired_potency_p5 AS contract_hired_potency_p5',
            'contracts.hired_potency_p6 AS contract_hired_potency_p6',
            'contracts.energy_term_p1 AS contract_energy_term_p1',
            'contracts.energy_term_p2 AS contract_energy_term_p2',
            'contracts.energy_term_p3 AS contract_energy_term_p3',
            'contracts.energy_term_p4 AS contract_energy_term_p4',
            'contracts.energy_term_p5 AS contract_energy_term_p5',
            'contracts.energy_term_p6 AS contract_energy_term_p6',
            'contracts.power_term_p1 AS contract_power_term_p1',
            'contracts.power_term_p2 AS contract_power_term_p2',
            'contracts.power_term_p3 AS contract_power_term_p3',
            'contracts.power_term_p4 AS contract_power_term_p4',
            'contracts.power_term_p5 AS contract_power_term_p5',
            'contracts.power_term_p6 AS contract_power_term_p6',
            'contracts.coefficient_inductive_below AS contract_coefficient_inductive_below',
            'contracts.coefficient_inductive AS contract_coefficient_inductive',
            'contracts.coefficient_capacitive AS contract_coefficient_capacitive',
            'contracts.is_island AS contract_is_island',
            'rates.id AS rate_id',
            'rates.name AS rate_name',
            'rates.periods AS rate_periods',
            'rates.code AS rate_code'
        )
            ->leftJoin('probes', 'probes.serial', 'probe_values_quarters.probe_serial')
            ->leftJoin('contracts', function ($join) {
                $join->on('contracts.probe_serial', '=', 'probes.serial');
                $join->on('contracts.from', '<=', DB::raw('DATE_SUB(probe_values_quarters.inserted_at, INTERVAL 15 MINUTE)'));
                $join->on('contracts.to', '>=', DB::raw('DATE_SUB(probe_values_quarters.inserted_at, INTERVAL 15 MINUTE)'));
            })
            ->leftJoin('rates', 'rates.id', 'contracts.rate_id')
            ->where('probe_values_quarters.probe_serial', $serial)
            ->whereBetween('probe_values_quarters.inserted_at', [$dates['start'], $dates['end']])
            ->orderBy('probe_values_quarters.inserted_at', 'ASC')
            ->get()->toArray();



        //Saco demás datos necesarios
        $festive_days = Calendar::all()->pluck('day')->toArray();

        //  Información adicional
        $info['num_excesses'] = 0;
        $info['first_inductive'] = null;
        $info['first_capacitive'] = null;
        $info['first_value_date'] = $values[array_key_first($values)]['inserted_at'];
        $info['last_value_date'] = $values[array_key_last($values)]['inserted_at'];

        if (isset($lastDate)){
            $info['first_value_date_database'] = ProbeValuesQuarter::where('probe_serial', $serial)->min('inserted_at');
            $info['last_value_date_database'] = $lastDate;
        }


        $resume = [];
        $quarters = [];
        $contracts = [];

        //Establezco los datos para mostrar
        foreach ($values as $quarter){

            // FECHA --> Establezco la fecha con un cuarto de hora menos ya qué la fecha que viene es de cuando se registra pero los datos son durante esos 15min
            $date = Carbon::parse($quarter['inserted_at'])->subMinutes('15')->toDateTimeString();

            // Nº PERIODO
            $quarters[$date]['num_period'] = SegenetHelper::getNumPeriod($quarter['rate_code'], Carbon::parse($date), $festive_days, $quarter['contract_is_island']);


            // DATOS DEL CONTRATO
            $quarters[$date]['hired_potency'] = $quarter[sprintf('contract_hired_potency_p%s', $quarters[$date]['num_period'])];
            $quarters[$date]['power_term'] = $quarter[sprintf('contract_power_term_p%s', $quarters[$date]['num_period'])];
            $quarters[$date]['energy_term'] = $quarter[sprintf('contract_energy_term_p%s', $quarters[$date]['num_period'])];

            $quarters[$date]['contract']['contract_id'] = $quarter['contract_id'];
            $quarters[$date]['contract']['contract_from'] = $quarter['contract_from'];
            $quarters[$date]['contract']['contract_to'] = $quarter['contract_to'];
            $quarters[$date]['contract']['rate_id'] = $quarter['rate_id'];
            $quarters[$date]['contract']['rate_code'] = $quarter['rate_code'];
            $quarters[$date]['contract']['rate_name'] = $quarter['rate_name'];
            $quarters[$date]['contract']['rate_periods'] = $quarter['rate_periods'];

            //Lo meto en el propio array de contratos
            $contracts[$quarter['contract_id']]['id'] = $quarter['contract_id'];
            $contracts[$quarter['contract_id']]['from'] = $quarter['contract_from'];
            $contracts[$quarter['contract_id']]['to'] = $quarter['contract_to'];
            $contracts[$quarter['contract_id']]['is_indexed'] = $quarter['contract_is_indexed'];
            $contracts[$quarter['contract_id']]['adjust'] = $quarter['contract_adjust'];
            $contracts[$quarter['contract_id']]['fee'] = $quarter['contract_fee'] ?? 3;
            $contracts[$quarter['contract_id']]['hired_potency_p1'] = $quarter['contract_hired_potency_p1'];
            $contracts[$quarter['contract_id']]['hired_potency_p2'] = $quarter['contract_hired_potency_p2'];
            $contracts[$quarter['contract_id']]['hired_potency_p3'] = $quarter['contract_hired_potency_p3'];
            $contracts[$quarter['contract_id']]['hired_potency_p4'] = $quarter['contract_hired_potency_p4'];
            $contracts[$quarter['contract_id']]['hired_potency_p5'] = $quarter['contract_hired_potency_p5'];
            $contracts[$quarter['contract_id']]['hired_potency_p6'] = $quarter['contract_hired_potency_p6'];
            $contracts[$quarter['contract_id']]['energy_term_p1'] = $quarter['contract_energy_term_p1'];
            $contracts[$quarter['contract_id']]['energy_term_p2'] = $quarter['contract_energy_term_p2'];
            $contracts[$quarter['contract_id']]['energy_term_p3'] = $quarter['contract_energy_term_p3'];
            $contracts[$quarter['contract_id']]['energy_term_p4'] = $quarter['contract_energy_term_p4'];
            $contracts[$quarter['contract_id']]['energy_term_p5'] = $quarter['contract_energy_term_p5'];
            $contracts[$quarter['contract_id']]['energy_term_p6'] = $quarter['contract_energy_term_p6'];
            $contracts[$quarter['contract_id']]['power_term_p1'] = $quarter['contract_power_term_p1'];
            $contracts[$quarter['contract_id']]['power_term_p2'] = $quarter['contract_power_term_p2'];
            $contracts[$quarter['contract_id']]['power_term_p3'] = $quarter['contract_power_term_p3'];
            $contracts[$quarter['contract_id']]['power_term_p4'] = $quarter['contract_power_term_p4'];
            $contracts[$quarter['contract_id']]['power_term_p5'] = $quarter['contract_power_term_p5'];
            $contracts[$quarter['contract_id']]['power_term_p6'] = $quarter['contract_power_term_p6'];
            $contracts[$quarter['contract_id']]['rate_id'] = $quarter['rate_id'];
            $contracts[$quarter['contract_id']]['rate_name'] = $quarter['rate_name'];
            $contracts[$quarter['contract_id']]['rate_code'] = $quarter['rate_code'];
            $contracts[$quarter['contract_id']]['rate_periods'] = $quarter['rate_periods'];
            $contracts[$quarter['contract_id']]['coefficient_inductive_below'] = $quarter['contract_coefficient_inductive_below'];
            $contracts[$quarter['contract_id']]['coefficient_inductive'] = $quarter['contract_coefficient_inductive'];
            $contracts[$quarter['contract_id']]['coefficient_capacitive'] = $quarter['contract_coefficient_capacitive'];
            $contracts[$quarter['contract_id']]['is_island'] = $quarter['contract_is_island'] ?? 0;


            //CONSUMO
            $quarters[$date]['active'] = $quarter['active'];
            $quarters[$date]['inductive'] = $quarter['inductive'];
            $quarters[$date]['capacitive'] = $quarter['capacitive'];
            $quarters[$date]['active_out'] = $quarter['active_out'];

            $quarters[$date]['inductive'] = $quarter['inductive'];
            $quarters[$date]['capacitive'] = $quarter['capacitive'];


            //  |-> Acumulo en caso de que estuviera agrupando, pues la relativa consiste en eso, acumularse según lo establecido
            isset($quarters[$date]['relative_active']) ?
                $quarters[$date]['relative_active'] += $quarter['relative_active'] :
                $quarters[$date]['relative_active'] = $quarter['relative_active'];

            isset($quarters[$date]['relative_inductive']) ?
                $quarters[$date]['relative_inductive'] += $quarter['relative_inductive'] :
                $quarters[$date]['relative_inductive'] = $quarter['relative_inductive'];

            isset($quarters[$date]['relative_capacitive']) ?
                $quarters[$date]['relative_capacitive'] += $quarter['relative_capacitive'] :
                $quarters[$date]['relative_capacitive'] = $quarter['relative_capacitive'];


            // POTENCIA
            $potency = $quarter['relative_active'] * 4;
            //Si no se ha establecido o llega una mayor se establece esta
            !isset($quarters[$date]['potency']) || (isset($quarters[$date]['potency']) && $potency > $quarters[$date]['potency']) ? $quarters[$date]['potency'] = $potency : null;

            //  |-> Excesos de potencia
            if ($potency > ($quarter[sprintf('contract_hired_potency_p%s', $quarters[$date]['num_period'])])) {
                //  Aumento el contador de número de excesos
                $info['num_excesses']++;
                //  Guardo por si necesitamos el dato porque penaliza por cuarta horaria
                !isset($quarters[$date]['power_excesses']) ? $quarters[$date]['power_excesses'] = SegenetHelper::calculateExcesses($potency, $quarter[sprintf('contract_hired_potency_p%s', $quarters[$date]['num_period'])]) :
                    $quarters[$date]['power_excesses'] += SegenetHelper::calculateExcesses($potency, $quarter[sprintf('contract_hired_potency_p%s', $quarters[$date]['num_period'])]);
            }
            else if (!isset($quarters[$date]['power_excesses'])) {
                $quarters[$date]['power_excesses'] = 0;
            }


            // COSENO DE PHI
            $quarters[$date]['cos_phi_inductive'] = SegenetHelper::calculateCosPhi($quarters[$date]['relative_active'], $quarters[$date]['relative_inductive']);
            $quarters[$date]['cos_phi_capacitive'] = SegenetHelper::calculateCosPhi($quarters[$date]['relative_active'], $quarters[$date]['relative_capacitive']);

            if ($quarters[$date]['cos_phi_inductive'] < 0.95) $info['first_inductive'] = $date;
            if ($quarters[$date]['cos_phi_capacitive'] < 0.98) $info['first_capacitive'] = $date;


            // PRECIO DE CONSUMO
            $quarters[$date]['consumption_price'] = $quarter[sprintf('contract_energy_term_p%s', $quarters[$date]['num_period'])] * $quarters[$date]['relative_active'];


            //  RESUMEN POR PERIODOS
            isset($resume['xº'][$quarters[$date]['num_period']]['active']) ?
                $resume['periods'][$quarters[$date]['num_period']]['active'] += $quarter['relative_active'] :
                $resume['periods'][$quarters[$date]['num_period']]['active'] = $quarter['relative_active'];

            isset($resume['periods'][$quarters[$date]['num_period']]['potency_penalized']) ?
                $resume['periods'][$quarters[$date]['num_period']]['potency_penalized'] += $quarters[$date]['power_excesses'] :
                $resume['periods'][$quarters[$date]['num_period']]['potency_penalized'] = $quarters[$date]['power_excesses'];

            isset($resume['periods'][$quarters[$date]['num_period']]['inductive']) ?
                $resume['periods'][$quarters[$date]['num_period']]['inductive'] += $quarter['relative_inductive'] :
                $resume['periods'][$quarters[$date]['num_period']]['inductive'] = $quarter['relative_inductive'];

            isset($resume['periods'][$quarters[$date]['num_period']]['capacitive']) ?
                $resume['periods'][$quarters[$date]['num_period']]['capacitive'] += $quarter['relative_capacitive'] :
                $resume['periods'][$quarters[$date]['num_period']]['capacitive'] = $quarter['relative_capacitive'];

            if (!isset($resume['periods'][$quarters[$date]['num_period']]['potency']) || (isset($resume['periods'][$quarters[$date]['num_period']]['potency']) && $potency > $resume['periods'][$quarters[$date]['num_period']]['potency'])) {
                $resume['periods'][$quarters[$date]['num_period']]['potency'] = $quarters[$date]['potency'];
                $resume['periods'][$quarters[$date]['num_period']]['potency_date'] = $date;
            }
            $resume['periods'][$quarters[$date]['num_period']]['consumption_price'] = ($resume['periods'][$quarters[$date]['num_period']]['consumption_price'] ?? 0) + $quarters[$date]['consumption_price'];


            $resume['periods'][$quarters[$date]['num_period']]['cos_phi_inductive'] = SegenetHelper::calculateCosPhi($resume['periods'][$quarters[$date]['num_period']]['active'], $resume['periods'][$quarters[$date]['num_period']]['inductive']);
            $resume['periods'][$quarters[$date]['num_period']]['cos_phi_capacitive'] = SegenetHelper::calculateCosPhi($resume['periods'][$quarters[$date]['num_period']]['active'], $resume['periods'][$quarters[$date]['num_period']]['capacitive']);
        }

        ksort($resume['periods']);

        $resume['global']['active'] = array_sum(array_column($resume['periods'], 'active'));
        $resume['global']['inductive'] = array_sum(array_column($resume['periods'], 'inductive'));
        $resume['global']['capacitive'] = array_sum(array_column($resume['periods'], 'capacitive'));
        $resume['global']['potency'] = max(array_column($resume['periods'], 'potency'));
        $resume['global']['consumption_price'] = array_sum(array_column($resume['periods'], 'consumption_price'));
        $resume['global']['cos_phi_inductive'] = SegenetHelper::calculateCosPhi($resume['global']['active'], $resume['global']['inductive']);
        $resume['global']['cos_phi_capacitive'] = SegenetHelper::calculateCosPhi($resume['global']['active'], $resume['global']['capacitive']);;


        return ['quarters' => $quarters, 'info' => $info, 'resume' => $resume, 'contracts' => $contracts];
    }

    //Obtener el número de periodo de un cuarto de hora
    public static function getNumPeriod($rate_code, $date, $festive, $isIsland = false)
    {

        //Compruebo si es isla o no
        if ($isIsland) {

            //Si es festivo, fin de semana, lunes 00:00 o cualquier otro día entre las 00:00 y las 07:59:59
            if (in_array($date->format('Y-m-d'), $festive) || $date->isWeekend() || ($date->dayOfWeek == Carbon::MONDAY && $date == $date->copy()->setTime(0, 0)) || ($date >= $date->copy()->setTime(0, 0) && $date < $date->copy()->setTime(8, 0))) {
                return 6;
            } else {
                switch ($date->month) {
                    case 1:
                    case 2:
                    case 3:
                        if (($date >= $date->copy()->setTime(10, 0) && $date < $date->copy()->setTime(15, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {
                            return 2;
                        } else {
                            return 4;
                        }
                        break;

                    case 4:
                    case 5:
                    case 6:
                        if (($date >= $date->copy()->setTime(10, 0) && $date < $date->copy()->setTime(15, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {

                            return 4;
                        } else {
                            return 5;
                        }
                        break;

                    case 7:
                    case 8:
                    case 9:
                    case 10:

                        // Si la hora es mayor a las 9 y menor que las 14 o mayor que las 18 y menor que las 22 es 4
                        if (($date >= $date->copy()->setTime(10, 0) && $date < $date->copy()->setTime(15, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {

                            return 1;
                        } else {
                            return 3;
                        }
                        break;

                    default:
                        if (($date >= $date->copy()->setTime(10, 0) && $date < $date->copy()->setTime(15, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {

                            return 2;
                        } else {
                            return 3;
                        }
                        break;
                }
            }

        }else{

            //  => Tarifas TD ( las antiguas no las contemplo )
            switch ($rate_code) {

                // 2.0TD
                case "20td":
                    if (in_array($date->format('Y-m-d'), $festive) || $date->isWeekend()) {
                        return 3;
                    } else {
                        if ($date > $date->copy()->setTime(0, 0) && $date <= $date->copy()->setTime(8, 0)) {
                            return 3;
                        } else if (($date > $date->copy()->setTime(10, 0) && $date <= $date->copy()->setTime(14, 0)) || ($date > $date->copy()->setTime(18, 0) && $date <= $date->copy()->setTime(22, 0))) {
                            return 1;
                        } else {
                            return 2;
                        }
                    }
                    break;

                // 3.0TD y 6.1TD
                default:
                    //Si es festivo, fin de semana, lunes 00:00 o cualquier otro día entre las 00:00 y las 07:59:59
                    if (in_array($date->format('Y-m-d'), $festive) || $date->isWeekend() || ($date->dayOfWeek == Carbon::MONDAY && $date == $date->copy()->setTime(0, 0)) || ($date >= $date->copy()->setTime(0, 0) && $date < $date->copy()->setTime(8, 0))) {
                        return 6;
                    }
                    else {
                        switch ($date->month) {
                            case 1:
                            case 2:
                            case 7:
                            case 12:
                                //  => Enero, Febrero, Julio, Diciembre
                                if (($date >= $date->copy()->setTime(9, 0) && $date < $date->copy()->setTime(14, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {
                                    return 1;
                                } else {
                                    return 2;
                                }
                                break;

                            case 3:
                            case 11:
                                //  Marzo, Noviembre
                                if (($date >= $date->copy()->setTime(9, 0) && $date < $date->copy()->setTime(14, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {
                                    return 2;
                                } else {
                                    return 3;
                                }
                                break;

                            case 4:
                            case 5:
                            case 10:
                                //  Abril, Mayo, Octubre

                                // Si la hora es mayor a las 9 y menor que las 14 o mayor que las 18 y menor que las 22 es 4
                                if (($date >= $date->copy()->setTime(9, 0) && $date < $date->copy()->setTime(14, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {
                                    return 4;
                                } else {
                                    return 5;
                                }
                                break;

                            case 6:
                            case 8:
                            case 9:
                                //  => Junio, Agosto, Septiembre
                                if (($date >= $date->copy()->setTime(9, 0) && $date < $date->copy()->setTime(14, 0)) || ($date >= $date->copy()->setTime(18, 0) && $date < $date->copy()->setTime(22, 0))) {
                                    return 3;
                                } else {
                                    return 4;
                                }
                                break;
                        }
                    }
                    break;
            }
        }
    }


    //Calcular excesos
    public static function calculateExcesses($potency, $hired_potency){
        return pow(abs($potency - $hired_potency), 2);
    }


    //Calcular coseno de phi
    public static function calculateCosPhi($active, $reactive)
    {
        if ($active > 0) return cos(atan($reactive / $active));

        return 1;
    }


    public static function calculateAverageAdjusts($adjust)
    {
        if ($adjust->active > 0)
            return $adjust->total / $adjust->active;
        else
            return 0;
    }

    public static function getCFEh($index, $date, $num_period, $ERH, $FEE)
    {
        $date = Carbon::parse($date);
        $date_format = $date->copy()->format('Y-m-d H:i:s');
        $date_format_hour = $date->copy()->format('Y-m-d H:00:00');
        $vars = self::getIndexVars($index['index_vars'], $date);


        // $TOTAL = ($ERH * $CEH) * ($PTH) * (1 + $IM)) + ($ATR * $ERH) + ($FEE * $ERH / 1000);
        $PRICE_MARKET = ($index['pb'][array_search($date_format_hour, array_column($index['pb'], 'datetime'))]['market'] ?? 0);
        $PB = $PRICE_MARKET / 1000;
        $FOM = $vars['fom'] ?? 0;
        $FOS = $vars['fos'] ?? 0;
        $PCH = $vars["pch_p$num_period"] ?? 0;
        $OTHERS = $vars['others'] ?? 0;

        $PTH = $vars["pth_p$num_period"] ?? 0;
        $IM = $vars["im"] ?? 0;

        $CEH = $PB + $FOM + $FOS + $PCH + $OTHERS;

        $ATR = $vars["atr_p$num_period"] ?? 0;
        //$FEE = $vars["fee"];


        $TOTAL = (($ERH * $CEH) * ($PTH) * (1 + $IM)) + ($ATR * $ERH) + ($FEE * $ERH / 1000);

        /*if(AuthSession::userSecondary()['email'] === 'franpergar02@gmail.com')
            dd($TOTAL, $PRICE_MARKET, $index, $ERH, $CEH, $PTH, $IM, $ATR, $ERH, $FEE, $ERH);*/


        //FALLABA PQ MARKET_PRICE NO SACABA NADA YA QUE DEJO DE ACTUALIZARSE LA APP

        return $TOTAL;
    }


    public static function getIndexVars($index_vars, $date)
    {
        foreach ($index_vars as $key => $value) {
            $d = Carbon::parse($key);
            if ($date->isBefore($d) || $date->isSameAs($d)) return $value;
        }
    }


    public static function createBills($curve, $probe)
    {

        //  -> Aquí se guardarán los contratos creados
        $contracts = [];

        //curve['contracts'] son los contratos que tiene
        //Array key lo que hace es en la variable $c le para la key
        foreach (array_keys($curve['contracts']) as $c) {
            //  -> Recorro cada contrato encontrado en cada cuarto de hora
            $contract = $curve['contracts'][$c];

            //  -> Filtro los cuartos de hora que pertenezcan al contrato que está recorriendo
            $quarters = array_filter($curve['quarters'], function ($value) use ($c) {
                return $value['contract']['contract_id'] == $c;
            });

            //  -> Creo un objeto contrato
            $contracts[$c] = new Bill($quarters, $contract, $probe);
        }

        return $contracts;
    }


    //Sacar la info del suministro
    public static function getProbeInfo($serial = null)
    {
        if (is_null($serial)) $serial = session()->get('probe')->serial;

        $probe = Probe::select('probes.*', 'probe_models.name as probe_model', 'probe_models.code as probe_model_code')
            ->where('serial', $serial)
            ->join('probe_models', 'probe_models.id', 'probes.probe_model_id')
            ->first()->toArray();

        $contracts = [];
        $functionalities = [];

        switch ($probe['probe_model_id']) {
            case 1:
            case 6:
                $contracts = Contract::select(
                    'contracts.*',
                    DB::raw('IF(CAST(NOW() as time) BETWEEN `from` AND `to`, 1, 0) as "is_current"'),
                    'rates.name as rate_name',
                    'rates.periods as rate_periods')
                    ->join('rates', 'rates.id', 'contracts.rate_id')
                    ->where('probe_serial', $serial)
                    ->orderBy('from', 'ASC')
                    ->get()->toArray();
                break;

            case 3:
                $functionalities = SegenetHelper::getEDSInfo($serial);
                break;
        }


        $users = ProbeUser::select('users.id',
            'users.name',
            'users.email',
            'users.phone',
            'users.profile_image',
            'users.dni',
            'users.cif',
            'users.address',
            'users.postal_code',
            'users.province',
            'users.town',
            'users.rol_id',
            'users.created_at',
            'users.is_verified',
            'users.is_active',
            'users.last_connection',
            'users.number_connections',
            'users.created_at',
            'roles.name as rol_name',
            'roles.icon as rol_icon',
            'roles.color as rol_color',
            'roles.code as rol_code',
            'enterprises.id as enterprise_id',
            'enterprises.name as enterprise_name',
            'probe_users.is_owner')
            ->join('users', 'probe_users.user_id', 'users.id')
            ->leftJoin('roles', 'users.rol_id', 'roles.id')
            ->leftJoin('user_enterprises', 'users.id', 'user_enterprises.user_id')
            ->leftJoin('enterprises', 'enterprises.id', 'user_enterprises.enterprise_id')
            ->where('probe_serial', $serial)
            ->orderBy('is_owner', 'DESC')
            ->get()->toArray();

        return ['probe' => $probe, 'contracts' => $contracts, 'users' => $users, 'functionalities' => $functionalities];
    }

    //
    public static function getEDSInfo($serial, $ra = true)
    {
        $functionalities = EdsDevice::select(
            'eds_devices.*',
            'eds_functionalities.id as functionality_id',
            'eds_functionalities.name as functionality_name',
            'eds_functionalities.code as functionality_code',
            'eds_functionality_types.id as eds_functionality_types_id',
            'eds_functionality_types.name as eds_functionality_types_name',
            'eds_functionality_types.unit as eds_functionality_types_unit',
            'eds_functionality_types.code as eds_functionality_types_code',
        )
            ->where('probe_serial', $serial)
            ->join('eds_functionalities', 'eds_functionalities.eds_device_id', 'eds_devices.id')
            ->join('eds_functionality_types', 'eds_functionalities.eds_functionality_type_id', 'eds_functionality_types.id')
            ->get();

        if ($ra)
            return $functionalities->toArray();

        return $functionalities;
    }


    //Obtener los precios de indexado
    public static function getIndexPrices($rate_id, $from, $to)
    {
        /*
         SELECT iv.finish, iv.fom, iv.fos, iv.im, iv.fee, iv.others, irv.atr_p1, irv.atr_p2, irv.atr_p3, irv.atr_p4, irv.atr_p5, irv.atr_p6, irv.pch_p1, irv.pch_p2, irv.pch_p3, irv.pch_p4, irv.pch_p5, irv.pch_p6, irv.pth_p1, irv.pth_p2, irv.pth_p3, irv.pth_p4, irv.pth_p5, irv.pth_p6
            FROM index_rate_variable irv
            JOIN index_variables iv ON irv.index_variable_id = iv.id
            WHERE
                irv.rate_id = 9 AND
                iv.finish > '2022-01-01'
            GROUP BY iv.finish
            ORDER BY iv.finish ASC
         */

        $index = IndexVariable::selectRaw('
            index_variables.finish,
            MAX(index_variables.fom) as fom,
            MAX(index_variables.fos) as fos,
            MAX(index_variables.im) as im,
            MAX(index_variables.others) as others,
            MAX(index_rate_variables.atr_p1) as atr_p1,
            MAX(index_rate_variables.atr_p2) as atr_p2,
            MAX(index_rate_variables.atr_p3) as atr_p3,
            MAX(index_rate_variables.atr_p4) as atr_p4,
            MAX(index_rate_variables.atr_p5) as atr_p5,
            MAX(index_rate_variables.atr_p6) as atr_p6,
            MAX(index_rate_variables.pch_p1) as pch_p1,
            MAX(index_rate_variables.pch_p2) as pch_p2,
            MAX(index_rate_variables.pch_p3) as pch_p3,
            MAX(index_rate_variables.pch_p4) as pch_p4,
            MAX(index_rate_variables.pch_p5) as pch_p5,
            MAX(index_rate_variables.pch_p6) as pch_p6,
            MAX(index_rate_variables.pth_p1) as pth_p1,
            MAX(index_rate_variables.pth_p2) as pth_p2,
            MAX(index_rate_variables.pth_p3) as pth_p3,
            MAX(index_rate_variables.pth_p4) as pth_p4,
            MAX(index_rate_variables.pth_p5) as pth_p5,
            MAX(index_rate_variables.pth_p6) as pth_p6
        ')
            ->join('index_rate_variables', 'index_rate_variables.index_variable_id', '=', 'index_variables.id')
            ->where('index_rate_variables.rate_id', $rate_id)
            ->where('index_variables.finish', '>=', $from)
            ->groupBy('index_variables.finish')
            ->orderBy('index_variables.finish', 'ASC')
            ->get()
            ->toArray();

        $vars = [];
        foreach ($index as $i) $vars[$i['finish']] = $i;


        $diary = IndexPrice::select('market', 'adjust', 'datetime')
            ->where('datetime', '>=', $from)
            ->where('datetime', '<=', $to)
            ->get()->toArray();



        return ['index_vars' => $vars, 'pb' => $diary];
    }

    //Función para saber si es un contrato viejo o es uno nuevo TD
    public static function isOldContract($code)
    {
        switch ($code) {
            case '30':
            case '31':
            case '61':
            case '62':
                return true;

            default:
                return false;
        }
    }

    public static function getKeysStartWith($array, $string)
    {
        return array_filter($array, function ($key) use ($string) {
            return strpos($key, $string) === 0;
        }, ARRAY_FILTER_USE_KEY);
    }
}
