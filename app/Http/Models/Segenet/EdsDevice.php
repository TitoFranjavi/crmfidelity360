<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class EdsDevice extends Model
{
    protected $connection = 'segenet';

    protected $table = 'eds_devices';
}
