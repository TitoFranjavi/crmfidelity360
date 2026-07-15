<?php

namespace App\Http\Models\Segenet;

use Illuminate\Database\Eloquent\Model;

class ProbeUser extends Model
{
    protected $connection = 'segenet';

    protected $table = 'probe_users';
}
