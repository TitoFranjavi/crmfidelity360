<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnlyUser
{
    /**
     * Permite acceso solo a un usuario concreto.
     * Uso:
     *   ->middleware('onlyUser:email@dominio.com')
     *   ->middleware('onlyUserId:123')
     */
    public function handle(Request $request, Closure $next, $identifier = null)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'No autorizado'], 401);
        }

        // Si te pasan un email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            if (strtolower($user->email) !== strtolower($identifier)) {
                return response()->json(['error' => 'Acceso restringido'], 403);
            }
            return $next($request);
        }

        // Si te pasan un id
        if ($identifier !== null) {
            if ((string)$user->_id !== (string)$identifier && (string)$user->id !== (string)$identifier) {
                return response()->json(['error' => 'Acceso restringido'], 403);
            }
            return $next($request);
        }

        return response()->json(['error' => 'Acceso restringido'], 403);
    }
}
