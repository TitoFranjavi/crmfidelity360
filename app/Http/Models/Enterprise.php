<?php

namespace App\Http\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Laravel\Sanctum\HasApiTokens;
use App\Http\Models\User;

class Enterprise extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable;

    protected $connection = "mongodb";
    public $timestamps = false;

    protected $guarded = [];

    public function subdomainUser()
    {
        return $this->belongsTo(User::class, 'userSubDom');
    }
}
