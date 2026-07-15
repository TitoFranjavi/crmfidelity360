<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Model;


class Key extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;

    protected $connection = "mongodb";
}
