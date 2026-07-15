<?php

namespace App\Http\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Jenssegers\Mongodb\Eloquent\Model as Model;
use Jenssegers\Mongodb\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\AccountFactory;


class Account extends Model
{
    use HasApiTokens, HasFactory, Notifiable;
    use Authenticatable;

    protected $connection = "mongodb";
    public $timestamps = false;

    protected $primaryKey = '_id';

    protected $guarded = [];

    //Relación con contratos
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'account');
    }
    protected static function newFactory()
{
    return AccountFactory::new();
}

}
