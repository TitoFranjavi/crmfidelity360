<?php

namespace App\Http\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Laravel\Sanctum\HasApiTokens;

class Marketer extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable;

    protected $connection = "mongodb";
    public $timestamps = false;

    protected $guarded = [];

}
