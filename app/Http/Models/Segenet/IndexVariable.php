<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class IndexVariable extends Model
{
    protected $connection = 'segenet';

    protected $table = 'index_variables';
}
