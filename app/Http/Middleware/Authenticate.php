<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;


class Authenticate extends Middleware
{

    protected function redirectTo($request)
    {
        if (is_null(session()->get('loggedUser'))) {
            return route('portal');
        }

    }
}
