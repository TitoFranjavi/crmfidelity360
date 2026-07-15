<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class Probe extends Model
{
    protected $connection = 'segenet';

    protected $table = 'probes';
}
