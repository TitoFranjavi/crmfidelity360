<?php


namespace App\Classes\Segenet;

use App\Helpers\AuthSession;
use App\Helpers\MainUtilities;
use Carbon\Carbon;
use PDF;
use MPDF;


/**
 * Esta clase recogerá un informe pero global, por lo que tendrá que guardar la información de manera agrupada
 * @package App\Classes
 */
class Resume extends Invoice
{
    private $id;
    public $contracts, $total, $maximumPotency, $hourlyCurve, $maxConsumptionDay = [], $quarters = [], $probe_close, $customValues;

    /**
     * Constructor para el resumen
     * @param array $contracts
     */
    public function __construct($contracts = [])
    {
        $this->id = uniqid();
        $this->contracts = $contracts;
        parent::__construct(null, null, 0, 0, 0, Carbon::now()->format('Y-m-d H:i:s'), '');

        $this->active = new Active();
        $this->inductive = new Reactive();
        $this->capacitive = new Reactive();
        $this->potency = new Potency();

        $this->probe_close = null;

        $this->maximumPotency = new Potency();

        $this->customValues = [];
    }


    /**
     * Agrupa la información de cada periodo de ambos contratos
     */
    public function simulate()
    {
        //this->contracts es un array de contratos creados con ReportUtilities::createBills, que crea un bill por cada curva
        foreach ($this->contracts as $bill) {

            $bill->customValues = $this->customValues;

            //Esto lo que hace es hacer una factura simulada, es para sacar los datos que más abajo se van a usar
            $bill->simulate();


            //  -> Los números de excesos
            $this->num_excesses += $bill->num_excesses;

            $this->num_cosine_inductive += $bill->num_cosine_inductive;
            $this->num_cosine_capacitive += $bill->num_cosine_capacitive;

            $this->cosine_inductive = $bill->cosine_inductive != '' && $this->cosine_inductive == '' ? $bill->cosine_inductive : $this->cosine_inductive;
            $this->cosine_capacitive = $bill->cosine_capacitive != '' && $this->cosine_capacitive == '' ? $bill->cosine_capacitive : $this->cosine_capacitive;

            //  -> Establezco las fechas inciales y finales
            is_null($this->first_date) ? $this->first_date = $bill->first_date : $this->first_date = min($bill->first_date, $this->first_date);
            is_null($this->last_date) ? $this->last_date = $bill->last_date : $this->last_date = max($bill->last_date, $this->last_date);
            //  => Recorro cada periodo


            $this->quarters = array_merge($this->quarters, $bill->quarters);
            for ($i = 1; $i <= $bill->contract->rate_periods; $i++) {
                //  => Resumen ENERGÍA ACTIVA. Si no existe registro alguno aún en el resumen, lo creo
                if (!isset($this->active->periods[$i]))
                    $this->active->periods[$i] = new Period([], [], [], [], []);



                //  -> Si existe registro del contrato que estoy recorriendo
                if (isset($bill->active->periods[$i]))
                    foreach ($bill->active->periods[$i] as $name => $value)
                        $this->active->periods[$i]->$name[] = $value;


                //  => Resumen ENERGÍA INDUCTIVA. Si no existe registro alguno aún en el resumen, lo creo
                if (!isset($this->inductive->periods[$i]))
                    $this->inductive->periods[$i] = new Period([], [], [], [], []);

                //  -> Si existe registro del contrato que estoy recorriendo
                if (isset($bill->inductive->periods[$i]))
                    foreach ($bill->inductive->periods[$i] as $name => $value)
                        $this->inductive->periods[$i]->$name[] = $value;

                //  => Resumen ENERGÍA CAPACITIVA. Si no existe registro alguno aún en el resumen, lo creo
                if (!isset($this->capacitive->periods[$i]))
                    $this->capacitive->periods[$i] = new Period([], [], [], [], []);

                //  -> Si existe registro del contrato que estoy recorriendo
                if (isset($bill->capacitive->periods[$i]))
                    foreach ($bill->capacitive->periods[$i] as $name => $value)
                        $this->capacitive->periods[$i]->$name[] = $value;

                //  => Resumen POTENCIA. Si no existe registro alguno aún en el resumen, lo creo
                if (!isset($this->potency->periods[$i]))
                    $this->potency->periods[$i] = new Period([], [], [], [], []);

                //  -> Si existe registro del contrato que estoy recorriendo
                if (isset($bill->potency->periods[$i]))
                    foreach ($bill->potency->periods[$i] as $name => $value)
                        $this->potency->periods[$i]->$name[] = $value;

                $this->adjust = $bill->adjust;
            }
        }

        $this->total();
    }


    /**
     * Obtiene el resumen total de los contratos que formen dicho informe global
     * @return TotalBill
     */
    public function total()
    {
        //  -> Un acumulador de totales
        $consumption = [];
        $electrical_tax = [];
        $rental = [];
        $taxbase = [];
        $iva = [];
        $total = [];


        foreach ($this->contracts as $contract) {
            $bill = $contract->total;
            $consumption[] = $bill->consumption;
            $electrical_tax[] = $bill->taxes;
            $rental[] = $bill->rental;
            $taxbase[] = $bill->taxBase;
            $iva[] = $bill->iva;
            $total[] = $bill->total;
        }

        $this->total = new TotalBill($consumption, $electrical_tax, $rental, $taxbase, $iva, $total);

    }
}
