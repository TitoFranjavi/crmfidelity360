<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumosGas extends Model
{
    protected $connection = 'sips';

    protected $table = 'consumos_gas';
}
