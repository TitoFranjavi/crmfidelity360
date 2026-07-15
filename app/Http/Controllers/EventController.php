<?php

namespace App\Http\Controllers;

use App\Http\Models\Event;
use App\Http\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventController extends Controller
{
    //funcion para registrar eventos
    public function store(Request $request){

        $event = $request['event'];

        Event::create([
            'subject' => $event['subject'],
            'desc' => $event['desc'],
            'date' => [
                'start' => $event['date']['start'],
                'end' => $event['date']['end'],
                'recurrency' => $event['date']['recurrency'],
            ],
            'color' => $event['color'],
            'account' => $event['account'],
            'createdBy' => session()->get('userLogged')->_id,
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        return response()->json(['message' => 'El evento ha sido creado correctamente'], 200);
    }

    //funcion para actualizar un evento
    public function update(Request $request){

        $event = $request['event'];

        //Saco el evento de la bbdd
        $eventToSave = Event::where('_id', $event['_id'])->first();

        //Actualizo campos

            //Asunto
            $eventToSave['subject'] = $event['subject'];

            //Dsecripción
            $eventToSave['desc'] = $event['desc'];


            //Fechas
            $dates = $eventToSave['date'];

                //Fecha inicial
                $dates['start'] = $event['date']['start'];

                //Fecha final
                $dates['end'] = $event['date']['end'];

            $eventToSave['date'] = $dates;

            //Color
            $eventToSave['color'] = $event['color'];


            //Cuenta
            $eventToSave['account'] = $event['account'];


        $eventToSave->save();

        return response()->json(['message' => 'El evento ha sido actualizado correctamente'], 200);
    }

    //funcion para obtener todos los eventos
    public function index(){

        $events = Event::where('createdBy', session()->get('userLogged')->_id)->get();

        foreach ($events as $event){
            //Le meto los datos del usuario que lo ha creado
            $event['createdBy'] = User::where('_id', $event['createdBy'])->first();
        }

        return response()->json(['events' => $events], 200);
    }

    //funcion para obtener un evento
    public function show($id){

        $event = Event::where('_id', $id)->first();

        //Le meto los datos del usuario que lo ha creado
        $event['createdBy'] = User::where('_id', $event['createdBy'])->first();

        return response()->json(['event' => $event], 200);
    }

    // funcion para borrar un evento
    public function deleteEvent($id){

        Event::destroy($id);

        return response()->json(['message' => 'El evento ha sido eliminado correctamente'], 200);

    }
}
