<?php

namespace App\Classes\Segenet;

use App\Classes\Segenet\TotalBill;
use App\Helpers\SegenetHelper;
use App\Http\Models\Segenet\Calendar;
use Carbon\Carbon;

class Bill extends Invoice
{
    public $contract, $quarters, $probe, $total, $customValues, $adjust, $hours;

    public function __construct($quarters = null, $contract = null, $probe = null)
    {
        //Coge todos los valores recibidos
        $this->quarters = $quarters;
        $this->contract = (object)$contract;
        $this->probe = $probe;
        $this->hours = 0;

        //Si tiene un dispositivo emparejado se creara una variable que contenga su información
        if($this->probe['paired_device'] != null){
            $this->pairedDeviceInfo = $this->getPairedDeviceInfo($this->probe['paired_device']);
        }

        $this->active = new Active();
        $this->inductive = new Reactive();
        $this->capacitive = new Reactive();
        $this->potency = new Potency();
        $this->adjust = new Adjust();

        $this->maximumPotency = new Potency();

        $this->curveHourly = null;

        $this->customValues = [];

        $this->curveComparison = ['current' => [], 'last_term' => [], 'last_year' => []];

        parent::__construct(array_key_first($this->quarters), array_key_last($this->quarters), 0, 0, 0, '', '');
    }


    /**
     * Esta función actualiza el objeto con una factura simulada
     */
    public function simulate()
    {
        $index = SegenetHelper::getIndexPrices(
            $this->contract->rate_id,
            Carbon::parse(array_key_first($this->quarters))->format('Y-m-d H:00:00'),
            Carbon::parse(array_key_last($this->quarters))->format('Y-m-d-H:00:00')
        );


        $groupedByHours = [];


        //  => Recorro cada cuarto de hora y voy guardando y calculando lo que vaya necesitando:
        foreach ($this->quarters as $date => $quarter) {//Saca la fecha y toda su info por partes;

            $d = Carbon::parse($date)->format('Y-m-d H:00:00');

            $groupedByHours[$d] = ($groupedByHours[$d] ?? 0) + $quarter['relative_active'];

            // ->Número de periodo
            $PERIOD = $quarter['num_period'];
            $ENERGY_TERM = sprintf('energy_term_p%s', $PERIOD);


            // ->Decide si es un contrato antiguo o de los aplicados a partir del 1 de Junio de 2021
            $is_old = SegenetHelper::isOldContract($this->contract->rate_code);

            //  => ######### Registro ENERGIA ACTIVA
            //  ->Antes compruebo si ya existe el periodo en la energía activa; si no, lo crea


            if (!isset($this->active->periods[$PERIOD]))
                $this->active->periods[$PERIOD] = new Period();

            //     -> Cuento el número de cuartos de hora que contabiliza ese periodo
            $this->active->periods[$PERIOD]->num_quarters = ($this->active->periods[$PERIOD]->num_quarters ?? 0) + 1;
            $this->active->periods[$PERIOD]->registered += $quarter['relative_active'];

            if ($this->contract->is_indexed)
                $this->active->periods[$PERIOD]->term = $this->active->periods[$PERIOD]->price / ($this->active->periods[$PERIOD]->registered > 0 ? $this->active->periods[$PERIOD]->registered : 1);
            else
                $this->active->periods[$PERIOD]->term = $this->contract->$ENERGY_TERM;
            $this->active->periods[$PERIOD]->lost = 0;


            //  => ######### Registro ENERGÍA INDUCTIVA
            //     -> Antes compruebo si ya existe el periodo en la energía inductiva; si no, lo crea
            if (!isset($this->inductive->periods[$PERIOD]))
                $this->inductive->periods[$PERIOD] = new Period();

            $this->inductive->periods[$PERIOD]->num_quarters = ($this->inductive->periods[$PERIOD]->num_quarters ?? 0) + 1;
            $this->inductive->periods[$PERIOD]->penalized = 0;
            $this->inductive->periods[$PERIOD]->term = 0;
            $this->inductive->periods[$PERIOD]->lost = 0;

            //     -> En los periodos nocturnos no penaliza
            if (($is_old && $PERIOD % 3 != 0) || (!$is_old && $PERIOD != 6))
                $this->inductive->periods[$PERIOD]->registered += $quarter['relative_inductive'];

            $this->inductive->periods[$PERIOD]->cosine = SegenetHelper::calculateCosPhi($this->active->periods[$PERIOD]->registered, $this->inductive->periods[$PERIOD]->registered);

            if ($quarter['cos_phi_inductive'] < 0.95) {
                $this->cosine_inductive == '' ? $this->cosine_inductive = $date : '';
                $this->num_cosine_inductive++;
            }

            if ($this->inductive->periods[$PERIOD]->cosine < 0.95)
                $this->inductive->periods[$PERIOD]->penalized = abs($this->active->periods[$PERIOD]->registered * (1 / 3) - $this->inductive->periods[$PERIOD]->registered);

            //  => ######### Registro ENERGÍA CAPACITIVA
            //     -> Antes compruebo si ya existe el periodo en la energía capacitiva; si no, lo crea
            if (!isset($this->capacitive->periods[$PERIOD]))
                $this->capacitive->periods[$PERIOD] = new Period();

            $this->capacitive->periods[$PERIOD]->num_quarters = ($this->capacitive->periods[$PERIOD]->num_quarters ?? 0) + 1;
            $this->capacitive->periods[$PERIOD]->lost = 0;

            //     -> Penaliza solo en P6 y a partir de diciembre de 2021
            $this->capacitive->periods[$PERIOD]->registered += $quarter['relative_capacitive'];
            $this->capacitive->periods[$PERIOD]->cosine = SegenetHelper::calculateCosPhi($this->active->periods[$PERIOD]->registered, $this->capacitive->periods[$PERIOD]->registered);

            if (!$is_old && $PERIOD == 6 && $date >= '2021-12-01') {
                if ($quarter['cos_phi_capacitive'] < 0.98) {
                    $this->cosine_capacitive == '' ? $this->cosine_capacitive = $date : '';
                    $this->num_cosine_capacitive++;
                }

                if ($this->capacitive->periods[$PERIOD]->cosine < 0.98)
                    $this->capacitive->periods[$PERIOD]->penalized = abs($this->active->periods[$PERIOD]->registered * (1 / 5) - $this->capacitive->periods[$PERIOD]->registered);
            }

            //  => ######### Otros AJUSTES
            // -> Si el punto de suministro tiene transformador, calculo las pérdidas
            if ($this->probe['power_transformer'] > 0) {
                $this->active->periods[$PERIOD]->lost = 0.04 * $this->active->periods[$PERIOD]->registered + (0.01 * $this->probe['power_transformer'] * ($this->active->periods[$PERIOD]->num_quarters / 4));
                $this->inductive->periods[$PERIOD]->lost = 0.04 * $this->inductive->periods[$PERIOD]->registered + (0.01 * $this->probe['power_transformer'] * ($this->inductive->periods[$PERIOD]->num_quarters / 4));
                $this->capacitive->periods[$PERIOD]->lost = 0.04 * $this->capacitive->periods[$PERIOD]->registered + (0.01 * $this->probe['power_transformer'] * ($this->capacitive->periods[$PERIOD]->num_quarters / 4));
            }

            //  => ######### Registro POTENCIA
            $potency = $quarter['relative_active'] * 4;
            if ($this->probe['power_transformer'] > 0) $potency *= 1.04;

            $HIRED_POTENCY = sprintf('hired_potency_p%s', $quarter['num_period']);

            //     ->Antes compruebo si ya existe el periodo en la potencia; si no, lo crea
            if (!isset($this->potency->periods[$PERIOD]))
                $this->potency->periods[$PERIOD] = new Period();

            //     ->Registro la potencia más grande encontrada
            if ($potency > $this->potency->periods[$PERIOD]->registered)
                $this->potency->periods[$PERIOD]->registered = $potency;


            if ($potency > $this->contract->$HIRED_POTENCY) {
                $this->num_excesses++;
                $this->potency->periods[$PERIOD]->excesses = ($this->potency->periods[$PERIOD]->excesses ?? 0) + SegenetHelper::calculateExcesses($potency, $this->contract->$HIRED_POTENCY);
            }

            //  => ######### Calculo PRECIOS
            $ENERGY_TERM = sprintf('energy_term_p%s', $quarter['num_period']);

            //dd($this->active->periods, 'ACA');


            //     -> Activa
            if ($this->contract->is_indexed) {
                $ERH = ($quarter['relative_active']); //diferencia de active
                $this->active->periods[$PERIOD]->price += SegenetHelper::getCFEh($index, $date, $PERIOD, $ERH, $this->contract->fee);
            } else {
                $this->active->periods[$PERIOD]->price = ($this->active->periods[$PERIOD]->registered + $this->active->periods[$PERIOD]->lost) * $this->contract->$ENERGY_TERM;
            }

            //  Debo guardar el consumo de energía activa, y el de energía inductiva/capacitiva e ir actualizando el valor del coseno de pho de cada periodo
            //      -> Inductiva
            //      -> Capacitiva



            if ($this->inductive->periods[$PERIOD]->cosine < 0.80) {
                $this->inductive->periods[$PERIOD]->price = $this->contract->coefficient_inductive_below * ($this->inductive->periods[$PERIOD]->penalized + $this->inductive->periods[$PERIOD]->lost);
                $this->inductive->periods[$PERIOD]->term = $this->contract->coefficient_inductive_below;
            } else if ($this->inductive->periods[$PERIOD]->cosine < 0.95) {
                $this->inductive->periods[$PERIOD]->price = $this->contract->coefficient_inductive * ($this->inductive->periods[$PERIOD]->penalized + $this->inductive->periods[$PERIOD]->lost);
                $this->inductive->periods[$PERIOD]->term = $this->contract->coefficient_inductive;
            }

            //     -> Capacitiva
            if ($this->capacitive->periods[$PERIOD]->cosine < 0.98 && $PERIOD == 6 && $date >= '2021-12-01') {
                $this->capacitive->periods[$PERIOD]->price = $this->contract->coefficient_capacitive * (($this->capacitive->periods[$PERIOD]->penalized ?? 0) + $this->capacitive->periods[$PERIOD]->lost);
                $this->capacitive->periods[$PERIOD]->term = $this->contract->coefficient_capacitive;
            }

            /*if(AuthSession::userSecondary()['email'] === 'franpergar02@gmail.com')
                dd($this->active);*/
        }

        /*if(AuthSession::userSecondary()['email'] === 'franpergar02@gmail.com')
            dd($this->active->periods, 'TODOS PERIODOS SACADOS DE CONSUMO ( activa )');*/


        if ($this->contract->adjust)
            self::calculateAdjust($index, $groupedByHours);

        //  => La potencia siempre factura aunque no se haya consumido ese periodo. Por ello recorro cada periodo y calculo
        // -> Número de días
        //$DAYS = Carbon::parse($this->last_date)->diffInDays(Carbon::parse($this->first_date));
        $DAYS = ceil(Carbon::parse($this->last_date)->diffInMilliseconds(Carbon::parse($this->first_date)) / (1000 * 60 * 60 * 24));
        $IS_OLD = SegenetHelper::isOldContract($this->contract->rate_code);

        for ($i = 1; $i <= $this->contract->rate_periods; $i++) {
            $HIRED_POTENCY = sprintf('hired_potency_p%s', $i);
            $POWER_TERM = sprintf('power_term_p%s', $i);


            // ->Si no existe, lo creo
            if (!isset($this->potency->periods[$i]))
                $this->potency->periods[$i] = new Period();

            $this->potency->periods[$i]->hired = $this->contract->$HIRED_POTENCY;
            $this->potency->periods[$i]->term = $this->contract->$POWER_TERM;


            $this->potency->periods[$i]->billed = $this->contract->$HIRED_POTENCY;
            $billed = $this->potency->periods[$i]->billed; //  -> Facturada inicial
            if ($this->contract->$HIRED_POTENCY > 0) {
                if ($IS_OLD) {
                    // -> En caso de ser contrato antiguo
                    $ki = [1 => 1, 2 => 0.5, 3 => 0.37, 4 => 0.37, 5 => 0.37, 6 => 0.17];

                    if ($this->contract->rate_periods < 6) {
                        // -> Si es un 3.X, la mínima facturada es el 85% de la contratada, y si se pasa del 105%, aplica fórmula
                        if ($this->potency->periods[$i]->registered < $this->contract->$HIRED_POTENCY * 0.85) {
                            //  --> Si es menor al 85%
                            $this->potency->periods[$i]->billed = $this->contract->$HIRED_POTENCY * 0.85;
                        } else if ($this->potency->periods[$i]->registered > ($this->contract->$HIRED_POTENCY * 1.05)) {
                            //  --> Si es mayor al 105%
                            $this->potency->periods[$i]->billed = $this->potency->periods[$i]->registered + 2 * ($this->potency->periods[$i]->registered - $this->contract->$HIRED_POTENCY * 1.05);
                        } else {
                            //  --> Si se encuentra entre los dos límites anteriores, se factura la registrada
                            $this->potency->periods[$i]->billed = $this->potency->periods[$i]->registered;
                        }

                        $billed = $this->potency->periods[$i]->registered; //  ->Reseteo facturada inicial

                    } else {
                        // ->Si es una 6, a la facturada se le aplica una fórmula que será 0 si no hay excesos
                        $this->potency->periods[$i]->billed += $ki[$i] * 1.4064 * sqrt($this->potency->periods[$i]->excesses ?? 0);
                    }

                    // ->El precio consiste en multiplicar la potencia por el número de días por el término de potencia (que está en €/año)
                    $this->potency->periods[$i]->price = $this->potency->periods[$i]->billed * $DAYS * ($this->contract->$POWER_TERM / 365);

                    $this->potency->periods[$i]->penalized = 0;
                    // ->La potencia penalizada consiste en restar lo que debería haberle costado y lo que le ha costado en sí
                    $this->potency->periods[$i]->penalized = $this->potency->periods[$i]->price - $billed * $DAYS * ($this->contract->$POWER_TERM / 365);
                    if ($this->potency->periods[$i]->penalized < 0) $this->potency->periods[$i]->penalized = 0;
                } else {
                    // -> En caso de ser contrato a partir del 1 de Junio 2021
                    //->Coeficientes de cálculo para excesos
                    $tep = ['30td' => 3.424853, '61td' => 3.665629, '62td' => 3.371776, '63td' => 3.234, '64td' => 3.234]; //  ->Términos de exceso de potencia
                    $ki = [
                        '30td' => [
                            1 => 1.0000,
                            2 => 0.8738,
                            3 => 0.3523,
                            4 => 0.2679,
                            5 => 0.1076,
                            6 => 0.1076
                        ],
                        '61td' => [
                            1 => 1.0000,
                            2 => 1.0000,
                            3 => 0.5427,
                            4 => 0.4103,
                            5 => 0.0264,
                            6 => 0.0264
                        ],
                        '62td' => [
                            1 => 1.0000,
                            2 => 1.0000,
                            3 => 0.4901,
                            4 => 0.4372,
                            5 => 0.0301,
                            6 => 0.0301
                        ],
                        '63td' => [
                            1 => 1.0000,
                            2 => 1.0000,
                            3 => 0.5473,
                            4 => 0.3199,
                            5 => 0.0613,
                            6 => 0.0613
                        ],
                        '64td' => [
                            1 => 1.0000,
                            2 => 0.7664,
                            3 => 0.3686,
                            4 => 0.2796,
                            5 => 0.0521,
                            6 => 0.0521
                        ]
                    ];


                    if ($this->last_date <= Carbon::parse('1 january 2022')->setTime(0, 0, 0)) {

                        $tep = ['30td' => 1.4064, '61td' => 1.4064, '62td' => 1.4064, '63td' => 1.4064, '64td' => 1.4064]; //  ->Términos de exceso de potencia
                        $ki = [
                            '30td' => [
                                1 => 1.0000,
                                2 => 0.8738,
                                3 => 0.3523,
                                4 => 0.2679,
                                5 => 0.1076,
                                6 => 0.1076
                            ],
                            '61td' => [
                                1 => 1.0000,
                                2 => 1.0000,
                                3 => 0.5427,
                                4 => 0.4103,
                                5 => 0.0264,
                                6 => 0.0264
                            ],
                            '62td' => [
                                1 => 1.0000,
                                2 => 1.0000,
                                3 => 0.4901,
                                4 => 0.4372,
                                5 => 0.0301,
                                6 => 0.0301
                            ],
                            '63td' => [
                                1 => 1.0000,
                                2 => 1.0000,
                                3 => 0.5473,
                                4 => 0.3199,
                                5 => 0.0613,
                                6 => 0.0613
                            ],
                            '64td' => [
                                1 => 1.0000,
                                2 => 0.7664,
                                3 => 0.3686,
                                4 => 0.2796,
                                5 => 0.0521,
                                6 => 0.0521
                            ]
                        ];
                    } else if ($this->last_date <= Carbon::parse('1 january 2023')->setTime(0, 0, 0)) {


                        $tep = ['30td' => 2.468725, '61td' => 2.500611, '62td' => 2.511007, '63td' => 2.268489, '64td' => 2.244925];

                        $ki = [
                            '30td' => [
                                1 => 1.000000,
                                2 => 0.977847,
                                3 => 0.258225,
                                4 => 0.224324,
                                5 => 0.134596,
                                6 => 0.134596
                            ],
                            '61td' => [
                                1 => 1.000000,
                                2 => 0.937332,
                                3 => 0.467076,
                                4 => 0.374609,
                                5 => 0.026491,
                                6 => 0.026491
                            ],
                            '62td' => [
                                1 => 1.000000,
                                2 => 0.997427,
                                3 => 0.399716,
                                4 => 0.301945,
                                5 => 0.027593,
                                6 => 0.027593
                            ],
                            '63td' => [
                                1 => 1.000000,
                                2 => 0.958607,
                                3 => 0.485508,
                                4 => 0.363556,
                                5 => 0.049296,
                                6 => 0.049296
                            ],
                            '64td' => [
                                1 => 1.000000,
                                2 => 0.862139,
                                3 => 0.425286,
                                4 => 0.325868,
                                5 => 0.041422,
                                6 => 0.041422
                            ]
                        ];
                    }else if($this->last_date < Carbon::parse('1 january 2024')->setTime(0, 0, 0)){

                        $tep = ['30td' => 3.424853, '61td' => 3.665629, '62td' => 3.371776, '63td' => 3.080419, '64td' => 2.944120];

                        $ki = [
                            '30td' => [
                                1 => 1.000000,
                                2 => 0.977847,
                                3 => 0.258225,
                                4 => 0.224324,
                                5 => 0.134596,
                                6 => 0.134596
                            ],
                            '61td' => [
                                1 => 1.000000,
                                2 => 0.937332,
                                3 => 0.467076,
                                4 => 0.374609,
                                5 => 0.026491,
                                6 => 0.026491
                            ],
                            '62td' => [
                                1 => 1.000000,
                                2 => 0.997427,
                                3 => 0.399716,
                                4 => 0.301945,
                                5 => 0.027593,
                                6 => 0.027593
                            ],
                            '63td' => [
                                1 => 1.000000,
                                2 => 0.958607,
                                3 => 0.485508,
                                4 => 0.363556,
                                5 => 0.049296,
                                6 => 0.049296
                            ],
                            '64td' => [
                                1 => 1.000000,
                                2 => 0.862139,
                                3 => 0.425286,
                                4 => 0.325868,
                                5 => 0.041422,
                                6 => 0.041422
                            ]
                        ];
                    }else if($this->last_date < Carbon::parse('1 january 2025')->setTime(0, 0, 0)){

                        $tep = ['30td' => 3.395810, '61td' => 3.566788, '62td' => 3.312680, '63td' => 3.019048, '64td' => 2.915852];

                        $ki = [
                            '30td' => [
                                1 => 1.000000,
                                2 => 0.640766,
                                3 => 0.275670,
                                4 => 0.232691,
                                5 => 0.077884,
                                6 => 0.077884
                            ],
                            '61td' => [
                                1 => 1.000000,
                                2 => 0.620828,
                                3 => 0.482845,
                                4 => 0.381770,
                                5 => 0.015816,
                                6 => 0.015816
                            ],
                            '62td' => [
                                1 => 1.000000,
                                2 => 0.666078,
                                3 => 0.427424,
                                4 => 0.355531,
                                5 => 0.018151,
                                6 => 0.018151
                            ],
                            '63td' => [
                                1 => 1.000000,
                                2 => 0.621562,
                                3 => 0.500437,
                                4 => 0.395142,
                                5 => 0.032600,
                                6 => 0.032600
                            ],
                            '64td' => [
                                1 => 1.000000,
                                2 => 0.563080,
                                3 => 0.432501,
                                4 => 0.393593,
                                5 => 0.026604,
                                6 => 0.026604
                            ]
                        ];

                    }else if($this->last_date >= Carbon::parse('1 january 2025')->setTime(0, 0, 0)){

                        $tep = ['30td' => 3.361213, '61td' => 3.332942, '62td' => 3.292963, '63td' => 3.099043, '64td' => 2.732620];

                        $ki = [
                            '30td' => [
                                1 => 1.000000,
                                2 => 0.528543,
                                3 => 0.167641,
                                4 => 0.128181,
                                5 => 0.036261,
                                6 => 0.036261
                            ],
                            '61td' => [
                                1 => 1.000000,
                                2 => 0.528704,
                                3 => 0.198416,
                                4 => 0.139813,
                                5 => 0.002956,
                                6 => 0.002632
                            ],
                            '62td' => [
                                1 => 1.000000,
                                2 => 0.567139,
                                3 => 0.149306,
                                4 => 0.090974,
                                5 => 0.003567,
                                6 => 0.003168
                            ],
                            '63td' => [
                                1 => 1.000000,
                                2 => 0.602540,
                                3 => 0.196297,
                                4 => 0.127930,
                                5 => 0.004201,
                                6 => 0.003698
                            ],
                            '64td' => [
                                1 => 1.000000,
                                2 => 0.597853,
                                3 => 0.145188,
                                4 => 0.100919,
                                5 => 0.003001,
                                6 => 0.002000
                            ]
                        ];
                    }


                    $this->potency->periods[$i]->penalized = 0;
                    $this->potency->periods[$i]->price = $this->potency->periods[$i]->billed * $DAYS * ($this->contract->$POWER_TERM / 365);

                    if ($this->potency->periods[$i]->registered > $this->contract->$HIRED_POTENCY) {
                        if (max(SegenetHelper::getKeysStartWith((array)$this->contract, 'hired_potency_p')) > 50) {
                            // -> Si la potencia más grande registrada es mayor a 50kW, usamos cuarta horaria
                            $this->potency->periods[$i]->penalized = $ki[$this->contract->rate_code][$i] * $tep[$this->contract->rate_code] * sqrt($this->potency->periods[$i]->excesses);
                        } else {
                            // -> Si la potencia más grande registrada es menor a 50kW, usamos maxímetros
//                            $this->potency->periods[$i]->penalized = 2 * $tep[$this->contract->rate_code] * (abs($this->contract->$HIRED_POTENCY - $this->potency->periods[$i]->registered));
                            $this->potency->periods[$i]->penalized = 2 * $tep[$this->contract->rate_code] * (abs($this->contract->$HIRED_POTENCY - $this->potency->periods[$i]->registered)) * $DAYS * ($this->contract->$POWER_TERM / 365);
                        }

                        //  El precio supone el precio que se ha calculado antes más lo penalizado
                        $this->potency->periods[$i]->price += $this->potency->periods[$i]->penalized;
                    }
                }
            } else {
                $this->potency->periods[$i]->penalized = 0;
            }
        }


        //recalculo el precio total de la activa
        foreach ($this->active->periods as $period){
            $period->price = ($period->registered + $period->lost) * $period->term;
        }

        ksort($this->active->periods);
        ksort($this->inductive->periods);
        ksort($this->potency->periods);

        $this->total();
    }


    //Funcion para recibir información del dispositivo emparejado de un probe
    public function getPairedDeviceInfo($serial){
        $pairedDeviceInfo = SegenetHelper::getProbeInfo($serial);

        return $pairedDeviceInfo;
    }


    public function calculateAdjust($index, $groupedByhours)
    {
        // Recorro cda cuarto de hora
        $adjustVars = array_column($index['pb'], NULL, 'datetime');

        $counter = 0;
        $total = 0;

        $limit_day = Carbon::parse('2022-06-15 00:00:00');

        $festive_days = Calendar::all()->pluck('day')->toArray();

        foreach ($groupedByhours as $key => $value) {
            $period = SegenetHelper::getNumPeriod($this->contract->rate_code, Carbon::parse($key), $festive_days);
            if (Carbon::parse($key)->greaterThan($limit_day)) {
                if ($this->probe['power_transformer'] > 0)
                    $value += 0.04 * $value + (0.01 * $this->probe['power_transformer']);

                if (!isset($this->adjust->periods[$period]))
                    $this->adjust->periods[$period] = new Period();

                $this->adjust->periods[$period]->total = ($this->adjust->periods[$period]->total ?? 0) + ($value * ($adjustVars[$key]['adjust'] ?? 0) / 1000) + $index['index_vars'][array_key_first($index['index_vars'])]['pth_p' . $period];
                $this->adjust->periods[$period]->active = ($this->adjust->periods[$period]->active ?? 0) + $value;
            }

            if (isset($this->adjust->periods[$period]))
                $this->adjust->periods[$period]->average = SegenetHelper::calculateAverageAdjusts($this->adjust->periods[$period]);

        }

        foreach ($this->adjust->periods as $period) {
            $total += $period->total;

        }

        $this->adjust->setTotal($total);
    }


    /**
     * Esta función obtiene el total del contrato
     * @return TotalBill
     */
    public function total($electric_tax = 5.11269632)
    {
        if (isset($this->customValues['electric_tax'])) $electric_tax = $this->customValues['electric_tax'];
        else if ($this->contract->is_island) $electric_tax = 0.5;

        //$CONSUMPTION = $this->active->total() + $this->inductive->total() + $this->capacitive->total() + $this->potency->total();
        $CONSUMPTION = $this->active->total() + $this->inductive->total() + $this->potency->total() + $this->adjust->total;

        $ELECTRICAL_TAX = $CONSUMPTION * ($electric_tax / 100 * (100 - $this->probe['reduction_tax']) / 100);

        $RENTAL = $this->probe['rental'];
        $TAXBASE = $CONSUMPTION + $ELECTRICAL_TAX + $RENTAL;
        if ($this->contract->is_island){
            $IVA = $TAXBASE * 0.03;
            $TOTAL = $TAXBASE * 1.03;
        }else{
            $IVA = $TAXBASE * 0.21;
            $TOTAL = $TAXBASE * 1.21;
        }


        $this->total = new TotalBill($CONSUMPTION, $ELECTRICAL_TAX, $RENTAL, $TAXBASE, $IVA, $TOTAL);
    }
}
