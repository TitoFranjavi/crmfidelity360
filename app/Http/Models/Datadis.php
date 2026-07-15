<?php

namespace App\Http\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class Datadis extends Model
{

    protected $connection = "mongodb";

    protected $dates = ['createdAt'];

    public $timestamps = false;

    protected $guarded = [];

}
