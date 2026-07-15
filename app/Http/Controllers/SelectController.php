<?php

namespace App\Http\Controllers;

use App\Http\Models\Select;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SelectController extends Controller
{

    //función para obtener los valores de selects de un usuario
    public static function index(){

        $selectValues = Select::where('created_by', session()->get('userLogged')->_id)->first();

        return response()->json(['selectValues' => $selectValues], 200);
    }

    //función para registrar un nuevo valor para un select
    public static function addSelectType(Request $request){

        $type = $request['type'];
        $value = $request['value'];
        $user = session()->get('userLogged');

        //Compruebo si existe ya el registro para el usuario con sesion iniciada
        $selectTypes = Select::where('created_by', $user->_id)->first();

        //Si todavia no tiene registro se crea
        if ($selectTypes === null || !$selectTypes){
            $selectTypes = Select::create([
                'sector' => [],
                'acc' => [],
                'source' => [],
                'status' => [],
                'origin' => [],
                'orderSources' => [],
                'marketerProducts' => [],
                'created_by'=> $user->_id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        }


        //Meto el valor dentro del array que haya indicado
        $arrToAdd = $selectTypes[$type];


        //Compruebo que no este ya en el array
        if (in_array($value ,$arrToAdd)) return response()->json(['error' => 'El valor que has intentado añadir ya existe en el select'], 400);

        array_push($arrToAdd, $value);

        $selectTypes[$type] = $arrToAdd;

        $selectTypes->save();

        return response()->json(['message' => "El tipo ha sido añadido correctamente"], 200);
    }

    //función para eliminar un valor de un select
    public static function delSelectType(Request $request){

        $type = $request['type'];
        $value = $request['value'];
        $user = session()->get('userLogged');


        $selectTypes = Select::where('created_by', $user->_id)->first();


        //Saco el indice del array en el que esta
        $arrToDel = $selectTypes[$type];

        $index = array_search($value, $arrToDel);

        if ($index !== -1) array_splice($arrToDel, $index, 1);

        $selectTypes[$type] = $arrToDel;

        $selectTypes->save();

        return response()->json(['message' => 'El valor ha sido eliminado correctamente'], 200);
    }
}
