<?php

namespace App\Http\Controllers;

use App\Events\RefreshSession;
use App\Helpers\UserHelper;
use App\Http\Models\Key;
use Carbon\Carbon;
use Illuminate\Http\Request;

class KeyController extends Controller
{

    //Crear una key para el registro de usuario
    public static function store(Request $request)
    {
        $errors = [];
        $serial = $request->get('data');

        $allKeys = Key::all()->pluck('key')->toArray();

        $key = '';

        do $key = UserHelper::generateSerial(12, false); while (in_array($key, $allKeys));


        //si el usuario esta por encima del visualizador ( Asercord y usuario desarrollador ) pondre que cuelga como

        //Le meto al propio usuario que lo crea como responsable
        $responsibles = $serial['responsibles'];

        //Si no se ha seleccionado ningún responsable, se pone de responsable el que lo ha creado ( ESTAN PUESTAS LAS IDs DE PRODUCCION, POR LO QUE PUEDE FALLAR )
        if(count($responsibles) === 0)
            array_push($responsibles, session()->get('userLogged')->_id === '65cb57489c2c285441086a43' ? '65fd4c2f05efc4aa4a050dc2' : session()->get('userLogged')->_id);//Si es Asercord, lo pongo al usuario Visualizador

        $serial['responsibles'] = $responsibles;


        $keyData = [
            'key' => $key,
            'expiration' => $serial['expiration'],
            'responsibles' => $serial['responsibles'],
            'label' => $serial['label'],
            'permissions' => $serial['permissions'],
            'replace' => $serial['replace'],
            'subdomainUser' => $serial['subdomainUser'] ?? session()->get('userLogged')->_id,
            'createdBy' => auth()->user()->getAuthIdentifier(),
            'activated_at' => '',
            'activated_by' => '',
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        if ($serial['label'] === 'Usuario demo'){
            $keyData['demoExpiration'] = $serial['demoExpiration'];
            $keyData['demoStartDate'] = $serial['demoStartDate'];
        }

        $keyModel = new Key();
        foreach ($keyData as $column => $value) $keyModel[$column] = $value;

        $keyModel->save();

        return response()->json(['key' => $key], 200);
    }


    //checkear un código para registrar un usuario
    public static function checkKey($key){

        $serial = Key::where('key', $key)->first();

        //Si no se ha encontrado el código devuelvo error
        if ($serial == null) return response()->json(['message' => 'El código no es válido'], 400);

        //Si ya se ha activado
        if ($serial['activated_at']) return response()->json(['message' => 'El código ya ha sido activado'], 400);

        //Si ha excedido el tiempo máximo devuelvo error
        $creationDate = new Carbon($serial['created_at']);
        $today = new Carbon();

        $diffInDays = $creationDate->diffInDays($today);

        if ($diffInDays > $serial['expiration']) return response()->json(['message' => 'El código ha expirado'], 400);

        //Si todo esta correcto devuelvo la key
        return response()->json(['serial' => $serial], 200);
    }
}
