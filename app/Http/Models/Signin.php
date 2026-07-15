<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Jenssegers\Mongodb\Eloquent\Model as Model;

class Signin extends Model
{
    protected $connection = "mongodb";
    protected $collection = 'signings';

    public $timestamps = false;

    protected $fillable = [
        'entry',
        'exit',
        'date',
        'user_id',
        'entry_location',
        'exit_location',
        'activity_sections',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
