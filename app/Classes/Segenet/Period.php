<?php


namespace App\Classes\Segenet;

/**
 * Este objeto será cada uno de los periodos que forman a un contrato o factura
 * @package App\Classes
 */
class Period
{
    public $registered, $price;

    /**
     * Constructor del periodo
     */
    public function __construct($registered = 0, $price = 0)
    {
        $this->registered = $registered;
        $this->price = $price;
    }

    public function isEmpty()
    {
        if (count((array)$this) > 2) return false;
        return true;
    }
}
