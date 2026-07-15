<?php

namespace App\Http\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Liquidation extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable;

    protected $connection = "mongodb";
    public $timestamps = false;

    protected $guarded = [];
}
