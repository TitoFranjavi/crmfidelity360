<?php


namespace App\Classes\Segenet;


/**
 * Esta clase supondrá el totalizador del precio
 * @package App\Classes
 */
class TotalBill
{
    public $consumption, $taxes, $rental, $taxBase, $iva, $total;

    /**
     * Constructor de una factura total
     * @param $consumption
     * @param $taxes
     * @param $rental
     * @param $taxBase
     * @param $iva
     * @param $total
     */
    public function __construct($consumption, $taxes, $rental, $taxBase, $iva, $total)
    {
        $this->consumption = $consumption;
        $this->taxes = $taxes;
        $this->rental = $rental;
        $this->taxBase = $taxBase;
        $this->iva = $iva;
        $this->total = $total;
    }
}
