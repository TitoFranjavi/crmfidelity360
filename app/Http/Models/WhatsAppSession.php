<?php

namespace App\Http\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class WhatsAppSession extends Model
{

    protected $connection = "mongodb";

    protected $table = "whatsappsession"; // Nombre de la tabla en la base de datos

    protected $dates = ['createdAt', 'expiresAt'];

    public $timestamps = false;

    protected $guarded = [];

    /*protected $fillable = [
        'phone',  // Número de WhatsApp del usuario
        'step',   // Paso en el que se encuentra el usuario
        'data',   // Datos temporales de la sesión
        'createdAt'
    ];*/

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($session) {
            $session->createdAt = now();

            if (isset($session->type) && $session->type === 'external_opportunity')
                $session->expiresAt = now()->addWeek();
            else
                $session->expiresAt = now()->addMinutes(30);
        });
    }

}
