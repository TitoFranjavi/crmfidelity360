<?php


namespace App\Helpers;

use App\Mail\RecoverPassword;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

//Google

use Google\Service\Drive;


class APIHelper
{

    //Función para enviar whatsapps
    public static function sendWhatsApp($phone, $message)
    {
        $client = new Client(['headers' => ['content-type' => 'application/x-www-form-urlencoded']]);

        $dataPost = [
            'token' => env('WA_TOKEN'),
            'to' => $phone,
            'body' => $message,
            'priority' => '1'
        ];

        $dataPost = http_build_query($dataPost);

        return $client->post("https://api.ultramsg.com/" . env('WA_INSTANCE') . "/messages/chat?$dataPost");
    }

    //Funcion para enviar codigo de recuperar contraseña
    public static function sendRecoverPasswordCode($emailToSend, $emailData, $enterprise){

        //Compruebo si se envía desde el correo de Zoco o desde el subdominio
        if (isset($enterprise['mailConfig']) && !!env("MAIL_USERNAME_" . $enterprise['mailConfig']) && !!env("MAIL_PASSWORD_" . $enterprise['mailConfig'])){

            $mailName = strtoupper($enterprise['mailConfig']);

            Config::set('mail.mailers.smtp.host', !!env('MAIL_HOST_' . $mailName) ? env('MAIL_HOST_' . $mailName) : env('MAIL_HOST'));
            Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME_' . $mailName));
            Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD_' . $mailName));
            Config::set('mail.from.address', env('MAIL_FROM_ADDRESS_' . $mailName));
            Config::set('mail.from.name', env('MAIL_FROM_NAME_' . $mailName));
        }

        Mail::to($emailToSend)->send(new RecoverPassword($emailData));
    }

    //Funcion para recibir error mediante un codigo
    public static function getResponseByCode($code)
    {
        return match ($code) {
            '000x001' => 'Este campo no puede estar vacío',
            '000x002' => 'El campo no cumple con los requisitos',
            '000x003' => 'Los campos no coinciden',
            '000x004' => 'El valor debe ser íntegramente numérico',
            '000x005' => 'El serial ya existe',

            '001x001' => 'Credenciales incorrectas',
            '001x002' => 'El correo del usuario ya existe',
            '001x003' => 'El DNI no está formado correctamente',
            '001x004' => 'El teléfono no es válido',
            '001x005' => 'El código postal no es válido',
            '001x006' => 'El código de verificación no es válido',
            '001x007' => 'El teléfono del usuario ya existe',
            '001x008' => 'El serial del sensor ya existe',

            '002x001' => 'Tu cuenta demo ha sido desactiva temporalmente',
            '002x002' => 'Cuenta demo ha caducado',
            '003x003' => 'Cuenta desactivada temporalmente',

            default => 'Error desconocido',
        };
    }

    //Función para obtener la instancia de Google drive
    public static function getInstance($jsonPath = null) {
        $client = new \Google\Client();

        if ($jsonPath) {
            $client->setAuthConfig($jsonPath); // ← carga directamente el JSON
        } else {
            $client->useApplicationDefaultCredentials();
        }

        $client->setScopes([Drive::DRIVE]);
        $service = new Drive($client);

        return $service;
    }

}
