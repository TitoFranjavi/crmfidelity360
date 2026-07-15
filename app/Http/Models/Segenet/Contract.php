<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $connection = 'segenet';

    protected $table = 'contracts';
}
