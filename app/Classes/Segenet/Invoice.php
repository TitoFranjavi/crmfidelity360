<?php


namespace App\Classes\Segenet;


abstract class Invoice
{
    public $active, $inductive, $capacitive, $potency, $first_date, $last_date, $num_excesses, $cosine_inductive, $cosine_capacitive, $num_cosine_inductive, $num_cosine_capacitive;

    public function __construct($first_date, $last_date, $num_excesses, $num_cosine_inductive, $num_cosine_capacitive, $cosine_inductive, $cosine_capacitive) {
        $this->first_date = $first_date;
        $this->last_date = $last_date;
        $this->num_excesses = $num_excesses;
        $this->num_cosine_inductive = $num_cosine_inductive;
        $this->num_cosine_capacitive = $num_cosine_capacitive;
        $this->cosine_inductive = $cosine_inductive;
        $this->cosine_capacitive = $cosine_capacitive;


    }

    //abstract public function total();
    //abstract public function getTotalProperty($name);
}
