<?php

namespace App\Http\Models;

use Jenssegers\Mongodb\Eloquent\Model as Model;

class Comparative extends Model
{
    protected $connection = "mongodb";

    protected $guarded = [];

}
