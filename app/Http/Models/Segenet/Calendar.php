<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    protected $connection = 'segenet';

    protected $table = 'calendar';
}
