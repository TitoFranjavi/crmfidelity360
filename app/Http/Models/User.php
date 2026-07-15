<?php

namespace App\Http\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;


//use Illuminate\Database\Eloquent\Model;


class User extends Model implements AuthenticatableContract
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable;

    protected $connection = "mongodb";
    public $timestamps = false;

    protected $hidden = ['password', 'remember_token'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'label',
        'demoExpiration',
        'demoStartDate',
    ];

    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
