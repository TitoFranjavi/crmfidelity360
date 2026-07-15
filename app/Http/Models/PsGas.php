<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PsGas extends Model
{
    protected $connection = 'sips';

    protected $table = 'ps_gas';
}
