<?php

namespace App\Http\Controllers;

use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class GoogleController extends Controller
{

    //comprobar si ya inicio sesión con google calendar
    public static function checkSignedIn(Request $request) {

        $userLogged = $request['user'];

        $user = User::where('_id', $userLogged['_id'])->first();

        if (isset($user['googleRefreshToken']))
            return response()->json(['isSignedIn' => true], 200);
        else
            return response()->json(['isSignedIn' => false], 200);
    }

    //cambiar el código por tokens de y de actualización
    public static function getTokens(Request $request) {

        $code = $request['code'];
        $userLogged = $request['user'];

        //Hago petición a API de google
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'redirect_uri' => 'http://localhost:3000',
            'grant_type' => 'authorization_code',
        ]);

        $data = $response->json();

        //Le guardo el token de actualización al usuario
        $user = User::where('_id', $userLogged['_id'])->first();

        if (!isset($user['googleRefreshToken'])) {
            $user['googleRefreshToken'] = $data['refresh_token'];
            $user->save();
        }

        //Devuelvo todo
        return response()->json(['data' => $data], 200);
    }


    //obtener un nuevo código mediante el código de actualización
    public static function getNewToken(Request $request) {

        $userLogged = $request['user'];

        $user = User::where('_id', $userLogged['_id'])->first();

        $refreshToken = $user['googleRefreshToken'];

        // Realizar la solicitud para refrescar el token
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'refresh_token' => $refreshToken,
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'grant_type' => 'refresh_token',
        ]);

        // Obtener los datos de la respuesta
        $data = $response->json();

        //Devuelvo todo
        return response()->json(['data' => $data], 200);
    }


    //obtener el listado de eventos de google Calendar
    public static function listEvents(Request $request){

        $access_token = $request['token'];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $access_token",
        ])->get('https://www.googleapis.com/calendar/v3/calendars/primary/events');

        // Maneja la respuesta
        if ($response->successful()) {
            return response()->json(['events' => $response->json()], 200);
        } else {
            return response()->json([
                'error' => 'Error al obtener eventos.',
                'details' => $response->json()
            ], $response->status());
        }
    }


    //obtener un evento por la id
    public static function getEvent($id, Request $request) {

        $access_token = $request['token'];


        $response = Http::withHeaders([
            'Authorization' => "Bearer $access_token",
        ])->get("https://www.googleapis.com/calendar/v3/calendars/primary/events/" . $id);

        // Maneja la respuesta
        if ($response->successful()) {
            return response()->json(['event' => $response->json()], 200);
        } else {
            return response()->json([
                'error' => 'Error al obtener eventos.',
                'details' => $response->json()
            ], $response->status());
        }
    }


    //crear un evento en google Calendar
    public static function createEvent(Request $request) {

        $event = $request['event'];
        $access_token = $request['token'];


        $startDateTime = new \DateTime($event['date']['start']);
        $formattedStartDateTime = $startDateTime->format('Y-m-d\TH:i:sP');

        $endDateTime = new \DateTime($event['date']['end']);
        $formattedEndDateTime = $endDateTime->format('Y-m-d\TH:i:sP');


        $eventToUpload = [
            'summary' => $event['subject'],
            'description' => $event['desc'],
            'start' => [
                'dateTime' => $formattedStartDateTime,
                'timeZone' => 'Europe/Madrid'
            ],
            'end' => [
                'dateTime' => $formattedEndDateTime,
                'timeZone' => 'Europe/Madrid',
            ],
        ];

        //Ubicación


        //Meet



        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
        ])->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $eventToUpload);



        return response()->json(['message' => 'El evento ha sido creado en Google Calendar'], 200);

    }


    //crear evento desde otro controlador
    public static function createEventFrom($order, $user, $startDate, $endDate) {

        //Pongo las fechas con el formato de Google Calendar
        $startDateTime = new \DateTime($startDate);
        $formattedStartDateTime = $startDateTime->format('Y-m-d\TH:i:sP');

        $endDateTime = new \DateTime($endDate);
        $formattedEndDateTime = $endDateTime->format('Y-m-d\TH:i:sP');

        //dd($formattedStartDateTime, $formattedEndDateTime);


        //Saco el nuevo token de acceso
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'refresh_token' => $user->googleRefreshToken,
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'grant_type' => 'refresh_token',
        ]);

        // Obtener los datos de la respuesta
        $token = $response->json()['access_token'];


        $eventToUpload = [
            'summary' => 'Renovación ' . $order->name,
            'description' => '',
            'start' => [
                'dateTime' => $formattedStartDateTime,
                'timeZone' => 'Europe/Madrid'
            ],
            'end' => [
                'dateTime' => $formattedEndDateTime,
                'timeZone' => 'Europe/Madrid',
            ],
        ];

        //dd($eventToUpload, $token, $startDate, $endDate);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', $eventToUpload);


        $eventId = $response->json()['id'];

        return $eventId;
    }

    //actualizar un evento en google Calendar
    public static function updateEvent($id, Request $request) {

        $event = $request['event'];
        $access_token = $request['token'];


        $startDateTime = new \DateTime($event['date']['start']);
        $formattedStartDateTime = $startDateTime->format('Y-m-d\TH:i:sP');

        $endDateTime = new \DateTime($event['date']['end']);
        $formattedEndDateTime = $endDateTime->format('Y-m-d\TH:i:sP');


        $updatedEvent = [
            'summary' => $event['subject'],
            'description' => $event['desc'],
            'start' => [
                'dateTime' => $formattedStartDateTime,
                'timeZone' => 'Europe/Madrid'
            ],
            'end' => [
                'dateTime' => $formattedEndDateTime,
                'timeZone' => 'Europe/Madrid',
            ],
        ];

        //Ubicación


        //Meet



        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
        ])->patch('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . $id, $updatedEvent);


        return response()->json(['message' => 'El evento ha sido actualizado en Google Calendar'], 200);

    }

    //borrar un evento de google Calendar
    public static function deleteEvent($id, Request $request) {

        $access_token = $request['token'];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $access_token,
            'Accept' => 'application/json',
        ])->delete('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . $id);

        // Manejar la respuesta
        if ($response->successful()) {
            return response()->json(['message' => 'Evento eliminado exitosamente.'], 200);
        } else {
            return response()->json([
                'error' => 'Error al eliminar el evento.',
                'details' => $response->json()
            ], $response->status());
        }
    }

    //borrar un evento de google Calendar desde otro controlador
    public static function deleteEventFrom($id, $user) {

        //Saco el nuevo token de acceso
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'refresh_token' => $user->googleRefreshToken,
            'client_id' => env('OAUTH_CLIENT_ID'),
            'client_secret' => env('OAUTH_CLIENT_SECRET'),
            'grant_type' => 'refresh_token',
        ]);

        // Obtener los datos de la respuesta
        $token = $response->json()['access_token'];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->delete('https://www.googleapis.com/calendar/v3/calendars/primary/events/' . $id);

        // Manejar la respuesta
        if ($response->successful()) {
            return response()->json(['message' => 'Evento eliminado exitosamente.'], 200);
        } else {
            return response()->json([
                'error' => 'Error al eliminar el evento.',
                'details' => $response->json()
            ], $response->status());
        }
    }
}
