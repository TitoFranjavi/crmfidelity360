<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class ProbeValuesQuarter extends Model
{
    protected $connection = 'segenet';

    protected $table = 'probe_values_quarters';
}
