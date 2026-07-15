<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class PsLuz extends Model
{
    protected $connection = 'sips';

    protected $table = 'ps_luz';
}
