<?php


namespace App\Classes\Segenet;

/**
 * Hará referencia al consumo del contrato
 * @package App\Classes
 */
class Active extends Property
{
    /**
     * Constructor de la energía activa
     * @param int $registered
     * @param int $price
     * @param int $lost
     * @param int $num_quarters
     */
    public function __construct()
    {
        parent::__construct();
    }
}
