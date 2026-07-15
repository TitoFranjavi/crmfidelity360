<?php

namespace App\Http\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Laravel\Sanctum\HasApiTokens;

class Log extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable;

    protected $connection = "mongodb";
    protected $dates = ['createdAt'];
    public $timestamps = false;

    protected $guarded = [];
}
