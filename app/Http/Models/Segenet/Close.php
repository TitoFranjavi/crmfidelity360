<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class Close extends Model
{
    protected $connection = 'segenet';

    protected $table = 'closes';
}
