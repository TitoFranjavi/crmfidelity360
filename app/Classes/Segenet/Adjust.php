<?php

namespace App\Classes\Segenet;

use PhpParser\Node\Expr\Cast\Double;

class Adjust extends Property
{
    public $total;
    public $average;
    public $active;

    public function __construct($date = null)
    {
        parent::__construct();
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return array_sum(array_column($this->periods, 'total'));
    }

    public function setTotal($total): void
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getAverage(): int
    {
        return $this->average;
    }

    /**
     * @param int $average
     */
    public function setAverage(int $average): void
    {
        $this->average = $average;
    }


}
