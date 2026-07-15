<?php

namespace App\Http\Controllers;

use App\Helpers\UserHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Models\Log;

class LogController extends Controller
{
    //Función para sacar los logs filtrados
    public static function getLogs(Request $request) {

        $filters = $request->input('filters');
        $isFirstTime = filter_var($request->input('isFirstTime'), FILTER_VALIDATE_BOOLEAN);
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 50);
        $logId = $request->input('logId');


        //Base de filtro
        $query = Log::query();


        //Filtro usuarios
        if ($isFirstTime === true) {

            //Saco el listado de usuarios de mi subdominio
            $userList = UserHelper::hierarchy(Auth::user()->_id);
            $userList = array_column($userList, '_id');
            $userList[] = Auth::user()->_id;

            //Filtro por usuarios
            $query->whereIn('createdBy', $userList);

        }
        else{
            //Filtro por usuarios
            $query->whereIn('createdBy', $filters['users'] ?? []);
        }

        //Filtros categorias
        $query->whereIn('type', $filters['categories'] ?? []);


        //Filtros fechas
        if (!empty($filters['dates'])) {

            $start = !empty($filters['dates']['start'])
                ? Carbon::parse($filters['dates']['start'])->setTimezone('Europe/Madrid')->startOfMinute()
                : null;

            $end = !empty($filters['dates']['end'])
                ? Carbon::parse($filters['dates']['end'])->setTimezone('Europe/Madrid')->endOfMinute()
                : null;

            if ($start && $end) {
                $query->whereBetween('createdAt', [$start, $end]);
            } elseif ($start) {
                $query->where('createdAt', '>=', $start);
            } elseif ($end) {
                $query->where('createdAt', '<=', $end);
            }
        }

        //Ordenamos
        $query->orderBy('createdAt', 'desc');

        $logs = $query->paginate($perPage, ['*'], 'page', $page);

        //Si nos piden un log concreto (p.ej. desde el detalle de una oportunidad),
        //lo devolvemos aparte aunque no entre en los filtros/página actuales.
        $requestedLog = !empty($logId) ? Log::find($logId) : null;

        return response()->json(['logs' => $logs, 'requestedLog' => $requestedLog], 200);
    }
}
