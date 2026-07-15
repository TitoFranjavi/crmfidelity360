<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsumosLuz extends Model
{
    protected $connection = 'sips';

    protected $table = 'consumos_luz';
}
