<?php


namespace App\Classes\Segenet;


abstract class Property
{
    public $periods;

    /**
     * Constructor de la energía activa
     */
    public function __construct() {
        $this->periods = [];
    }

    public function total() {
        $total = 0;
        foreach ($this->periods as $period)
            $total += $period->price;

        return $total;
    }
}
