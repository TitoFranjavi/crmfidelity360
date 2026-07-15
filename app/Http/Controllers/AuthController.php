<?php

namespace App\Http\Controllers;

use App\Helpers\APIHelper;
use App\Helpers\UserHelper;
use App\Http\Models\Enterprise;
use App\Http\Models\User;
use App\Http\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use MongoDB\BSON\UTCDateTime;
use Stripe\Plan;
use Stripe\Stripe;
use Stripe\Subscription;
use Illuminate\Support\Facades\File;

class AuthController extends Controller
{

    //función para iniciar sesión
    public function auth(Request $request)
    {

        $column = is_numeric($request['emailOrPhone']) ? 'phone' : 'email';

        if ($column === 'email')
            $request['emailOrPhone'] = strtolower($request['emailOrPhone']);

        //Comprobación que se puede usar la cuenta
        $user = User::where($column, $request['emailOrPhone'])->where('subdomain', $request['enterprise'])->first();

        $canAccess = true;


        //Compruebo si tiene tiempo de expiración demo que no haya expirado el tiempo
        if (isset($user) && isset($user['demoExpiration'])) {

            //Calculo que desde la fecha de creación no hayan pasado demoExpiration días hasta hoy
            $dateStart = new Carbon($user['demoStartDate']);
            $nowDate = Carbon::now();
            $diffInDays = $dateStart->diffInDays($nowDate);

            if ($diffInDays > $user['demoExpiration'])
                $canAccess = false;
        }


        if (Auth::attempt([$column => $request['emailOrPhone'], 'password' => $request['password'], 'subdomain' => $request['enterprise']], $request['remember']) && $canAccess) {

            // Autenticación correcta: traigo el usuario autenticado (puede diferir del cargado antes)
            $loggedUser = Auth::user();

            //Comrpuebo si es de por debajo de Zoco si hace más de un mes que no mete contrato
            $userListTop = self::getAllSuperiors($loggedUser['_id']);
            $userSubdomain = collect($userListTop)
                ->first(fn ($user) => $user['label'] === 'Usuario subdominio');

            $canAccess = true;

            //Si no es un usuario inactivable
            if (!isset($loggedUser['inactivable'])){

                if ($loggedUser['isActive'] === true){

                    //Compruebo para ver si hace más de un mes que no se mete ningún contrato y si es así deshabilito la cuenta
                    if ($loggedUser['label'] !== 'Usuario subdominio' && $userSubdomain !== null && $userSubdomain['_id'] === '65cb57489c2c285441086a43') {

                        //Si la cuenta no tiene un mes de antiguedad tampoco se deshabilita
                        $registerDate = new Carbon($loggedUser['createdAt']);
                        if($registerDate->diffInMonths(Carbon::now()) >= 1){

                            //Compruebo si hay algún contrato con usersIds del último mes
                            $lastMonth = Carbon::now()->subMonth();

                            $hasRecentContract = Order::whereIn('usersIds', [$loggedUser['_id']])
                                ->whereRaw([
                                    '$expr' => [
                                        '$gte' => [
                                            [
                                                '$dateFromParts' => [
                                                    // Año: 2000 + los dos últimos dígitos
                                                    'year' => [
                                                        '$add' => [
                                                            2000,
                                                            ['$toInt' => [
                                                                '$substrCP' => ['$transferDate', 6, 2]
                                                            ]]
                                                        ]
                                                    ],
                                                    'month' => [
                                                        '$toInt' => ['$substrCP' => ['$transferDate', 3, 2]]
                                                    ],
                                                    'day' => [
                                                        '$toInt' => ['$substrCP' => ['$transferDate', 0, 2]]
                                                    ],
                                                    'timezone' => 'Europe/Madrid',
                                                ],
                                            ],
                                            new UTCDateTime($lastMonth->getTimestamp() * 1000),
                                        ],
                                    ],
                                ])
                                ->exists();

                            if (!$hasRecentContract){
                                $canAccess = false;

                                //Guardo en el usuario que esta inactivo
                                User::where('_id', $loggedUser['_id'])->update(['isActive' => false]);
                            }

                        }

                    }
                }else{

                    // isActive === false
                    // La auto-reactivación por contratos solo aplica a Zoco (mismo subdominio que la auto-desactivación)
                    if ($loggedUser['label'] !== 'Usuario subdominio' && $userSubdomain !== null && $userSubdomain['_id'] === '65cb57489c2c285441086a43') {

                        //Si se ha metido un contrato de menos de un mes vuelvo a reestablecerla
                        $lastMonth = Carbon::now()->subMonth();

                        $hasRecentContract = Order::whereIn('usersIds', [$loggedUser['_id']])
                            ->whereRaw([
                                '$expr' => [
                                    '$gte' => [
                                        [
                                            '$dateFromParts' => [
                                                'year' => [
                                                    '$add' => [
                                                        2000,
                                                        ['$toInt' => ['$substrCP' => ['$transferDate', 6, 2]]]
                                                    ]
                                                ],
                                                'month' => ['$toInt' => ['$substrCP' => ['$transferDate', 3, 2]]],
                                                'day'   => ['$toInt' => ['$substrCP' => ['$transferDate', 0, 2]]],
                                                'timezone' => 'Europe/Madrid',
                                            ],
                                        ],
                                        new UTCDateTime($lastMonth->getTimestamp() * 1000),
                                    ],
                                ],
                            ])
                            ->exists();

                        if ($hasRecentContract){
                            // Solo auto-reactivamos si la desactivación fue automática (sin temporalActive).
                            // Si el admin desactivó manualmente, temporalActive está definido → respetamos su decisión.
                            $wasManuallyDeactivated = isset($loggedUser['temporalActive']) && $loggedUser['temporalActive'] !== '';

                            if (!$wasManuallyDeactivated) {
                                $user = User::find($loggedUser['_id']);
                                $user->isActive = true;
                                $user->unset('temporalActive');
                                $user->save();
                            } else {
                                $canAccess = false;
                            }
                        } else {
                            if (!isset($loggedUser['temporalActive']) || new Carbon($loggedUser['temporalActive']) < Carbon::today())
                                $canAccess = false;
                        }

                    } else {

                        // Para otros subdominios: isActive=false significa bloqueado.
                        // Respetamos temporalActive si se estableció una fecha de acceso temporal.
                        if (!isset($loggedUser['temporalActive']) || new Carbon($loggedUser['temporalActive']) < Carbon::today())
                            $canAccess = false;

                    }
                }


                if (!$canAccess) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    $isFidelityUser = (
                        isset($userSubdomain) &&
                        isset($userSubdomain['_id']) &&
                        (string) $userSubdomain['_id'] === '6909fa3739c76284af09ec54'
                    );

                    if ($isFidelityUser) {
                        $message = "Para reactivarla, contacta con el equipo técnico.";
                    } else {
                        $message = "Para reactivarla, contacta con el equipo técnico en <a href=\"mailto:mariluz@zocoenergia.com\" target=\"_blank\">mariluz@zocoenergia.com</a>";
                    }

                    return response()->json([
                        'codeResponse' => '003x003',
                        'info' => APIHelper::getResponseByCode('003x003'),
                        'message' => $message
                    ], 401);
                }

            }




            session()->put('userLogged', Auth::user());

            return response()->json(['message' => 'El usuario es correcto'], 200);
        }

        if (!$canAccess)
            $codeResponse = "002x002";
        else
            $codeResponse = "001x001";

        return response()->json(['codeResponse' => $codeResponse, 'info' => APIHelper::getResponseByCode($codeResponse), 'message' => $codeResponse === '002x002' ? "Tu cuenta ha sido desactivada ya que se ha superado su tiempo límite de prueba. <br><br> Si tienes alguna duda, contacta con el equipo técnico en <a href=\"mailto:soporte@zocoenergia.com\" target=\"_blank\">soporte@zocoenergia.com</a>" : 'Comprueba tu email o número de telefono y tu contraseña'], 401);
    }


    //función para cerrar sesion
    public function logout()
    {

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        //return redirect('/');
    }


    // función para recuperar contraseña
    public function sendRecoverCode(Request $request)
    {
        $emailOrPhone = $request->input('emailOrPhone');
        $enterprise   = $request->input('enterprise', []);

        // Determinar si es teléfono o email
        $column = preg_match('/^\d+$/', $emailOrPhone) ? 'phone' : 'email';
        $user   = User::where($column, $emailOrPhone)->first();

        if (! $user) {
            return response()->json(['message' => 'No existe ese usuario'], 400);
        }

        // Generar código numérico de 5 dígitos (10000–99999)
        $code = (string) random_int(10000, 99999);

        // Guardar token en BD con timestamp
        $user->recover = [
            'token'     => $code,
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        $user->save();

        // Si es teléfono ➞ envía por WhatsApp
        if ($column === 'phone') {
            $link = "https://{$enterprise['url']}/portal/recover/update?token={$code}&credentials={$emailOrPhone}";
            $message = <<<TXT
Aquí tienes tu código de recuperación:

🔑 {$code}

Accede directamente:
{$link}
TXT;
            APIHelper::sendWhatsApp($emailOrPhone, $message);

            return response()->json(['token' => $code], 200);
        }

        // Envío por email con plantilla inline
        $userName  = $user->firstName ?? 'Usuario';
        $rojo      = '#C62828';
        $folder  = $enterprise['asset_folder'] ?? 'default';
        $logoUrl = asset("assets/enterprises/{$folder}/logos/logo-light.png");
        $logoDir = public_path("assets/enterprises/{$folder}/logos");

        if (File::exists($logoDir)) {
            // Obtener todos los ficheros de la carpeta
            $files = File::files($logoDir);

            foreach ($files as $file) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'])) {
                    // Tomamos la primera imagen válida
                    $logoUrl = asset("assets/enterprises/{$folder}/logos/" . $file->getFilename());
                    break;
                }
            }
        }


        $resetLink = "https://{$enterprise['url']}/portal/recover/update?token={$code}&credentials=" . urlencode($emailOrPhone);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Recuperar contraseña</title>
  <style>
    body,table,td { margin:0; padding:0; }
    img { border:0; height:auto; line-height:100%; text-decoration:none; }
    table { border-collapse:collapse !important; }
    @media only screen and (max-width:600px) {
      .responsive-table { width:100% !important; }
      .padding { padding:20px !important; }
      .code-box { font-size:20px !important; padding:12px 20px !important; }
      .btn { display:block !important; width:80% !important; margin:0 auto !important; }
    }
  </style>
</head>
<body style="background:#f2f2f2;">

  <!-- Preheader -->
  <div style="display:none;font-size:1px;color:#f2f2f2;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
    Código para restablecer tu contraseña en {$enterprise['name']}
  </div>

  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td align="center" class="padding" style="padding:40px 0;">
        <table class="responsive-table" width="600" bgcolor="#fff" style="border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.1);">
          <tr style="background:{$rojo};">
            <td align="center" style="padding:20px;">
              <img src="{$logoUrl}" alt="{$enterprise['name']} Logo" style="max-height:60px;">
            </td>
          </tr>
          <tr>
            <td class="padding" style="padding:40px;font-family:Arial,sans-serif;color:#333;">
              <h2 style="margin-top:0;">¡Hola {$userName}!</h2>
              <p>Has solicitado restablecer tu contraseña en <strong>{$enterprise['name']}</strong>. Utiliza el siguiente código:</p>
              <div style="text-align:center;margin:30px 0;">
                <span class="code-box" style="background:{$rojo};color:#fff;font-size:24px;font-weight:bold;padding:15px 30px;border-radius:4px;display:inline-block;">
                  {$code}
                </span>
              </div>
              <p style="text-align:center;">
                <!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{$resetLink}" style="height:45px;v-text-anchor:middle;width:260px;" arcsize="8%" stroke="f" fillcolor="{$rojo}">
                  <w:anchorlock/>
                  <center style="color:#fff;font-family:Arial,sans-serif;font-size:16px;">Recuperar mi contraseña</center>
                </v:roundrect>
                <![endif]-->
                <![if !mso]>
                <a href="{$resetLink}" class="btn" style="background:{$rojo};color:#fff;text-decoration:none;font-family:Arial,sans-serif;font-size:16px;padding:12px 24px;border-radius:4px;display:inline-block;">
                  Recuperar mi contraseña
                </a>
                <![endif]>
              </p>
              <p style="font-size:13px;color:#777;margin-top:30px;">
                Si no solicitaste este cambio, ignora este correo.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>

</body>
</html>
HTML;

        $emailData = [
            'header'  => 'Recupera tu contraseña en ' . ($enterprise['name'] ?? config('app.name')),
            'content' => $html,
        ];
        APIHelper::sendRecoverPasswordCode($emailOrPhone, $emailData, $enterprise);

        return response()->json(['token' => $code], 200);
    }




    //funcion para checkear credenciales de cambio de contraseña
    public function checkCredentials(Request $request)
    {

        $credentials = $request->only(['token', 'credentials']);

        $column = is_numeric($credentials['credentials']) ? 'phone' : 'email';


        //Saco el usuario asignado si esque lo hay
        if ($column === 'phone') {
            $user = User::where('phone', $credentials['credentials'])->first();
        } else {
            $user = User::where('email', $credentials['credentials'])->first();
        }

        //Si lo hay
        if ($user !== null) {

            //Compruebo si el token es el correcto
            if ($user->recover['token'] === $credentials['token']) {

                //Compruebo si esta dentro de tiempo
                $nowTime = Carbon::now();

                $tokenTime = new Carbon($user->recover['createdAt']);

                $timeDiff = $tokenTime->diffInMinutes($nowTime);

                //si han pasado menos de 5 minutos
                if ($timeDiff <= 5) {
                    return response()->json(['message' => 'Las credenciales son correctas'], 200);
                } else {
                    return response()->json(['message' => 'El token ha expirado'], 400);
                }
            } else {
                return response()->json(['message' => 'Las credenciales no son validas'], 400);
            }
        } else {
            return response()->json(['message' => 'Las credenciales no son validas'], 400);
        }
    }


    //funcion para cambiar la contraseña
    public function changePassword(Request $request)
    {

        $newPassword = $request['credentials'];

        $access = $request['access'];


        $column = is_numeric($access['credentials']) ? 'phone' : 'email';


        //Saco el usuario asignado si esque lo hay
        if ($column === 'phone') {
            $user = User::where('phone', $access['credentials'])->first();
        } else {
            $user = User::where('email', $access['credentials'])->first();
        }

        $user->password = trim(Hash::make($newPassword['new']));

        $user->save();

        //Inicio sesión con el usuario
        if (Auth::attempt([$column => $access['credentials'], 'password' => $newPassword['new']], false)) {
            session()->put('userLogged', Auth::user());
        }

        return response()->json(['message' => 'La contraseña ha sido cambiada correctamente'], 200);
    }


    //funcion para obtener los datos de sesion
    public function getSessionData() {

        //SACO EL USUARIO CON SESIÓN INICIADA
        session()->put('userLogged', User::where('_id', session()->get('userLogged')->_id)->first());
        $sessionData['userLogged'] = session()->get('userLogged');


        //LISTA DE USUARIOS QUE TENGO POR DEBAJO
        $userList = UserHelper::hierarchy($sessionData['userLogged']->_id);
        array_unshift($userList, $sessionData['userLogged']->toArray());

        //Aqui lo que hago es solo dejar el array con los ids de los usuarios
        $userList = array_reduce($userList, function ($carry, $user) {
            $id = $user['_id'];
            if (!isset($carry[$id]) && $id !== session()->get('userLogged')->_id) $carry[$id] = $user;
            return $carry;
        }, []);

        $userList = array_values($userList);

        $sessionData['userList'] = $userList;


        //LISTA DE USUARIOS QUE TENGO POR ENCIMA
        $userListTop = self::getAllSuperiors($sessionData['userLogged']->_id);

        $sessionData['userListTop'] = $userListTop;

        $userSubdomain = collect($userListTop)
        ->first(fn ($user) => $user['label'] === 'Usuario subdominio');

        $sessionData['userSubdomain'] = $userSubdomain;

        $enterprise = null;

        if ($userSubdomain && isset($userSubdomain['_id'])) {

            // Buscar por subdomainUser
            $enterprise = Enterprise::where(
                'subdomainUser',
                (string) $userSubdomain['_id']
            )->first();

            if (!$enterprise) {
                $enterprise = Enterprise::where(
                    '_id',
                    (string) $userSubdomain['_id']
                )->first();
            }

            if ($enterprise) {
                $enterprise = collect($enterprise->toArray())
                    ->except([
                        'commissionRanges',
                        'ultramsg'
                    ])
                    ->toArray();
            }
        }

        $sessionData['enterprise'] = $enterprise;


        //Saco el plan al que se esta suscrito
        $sessionData['subdomainEnterprise'] = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();
        $sessionData['subscription'] = $sessionData['subdomainEnterprise']->subscription ?? null;

        $subdomainUserList = [];

        if ($userSubdomain && isset($userSubdomain['_id'])) {

            $subdomainId = $userSubdomain['_id'];

            // Sacar todos los usuarios por debajo del subdominio
            $descendants = UserHelper::hierarchy($subdomainId);

            // Añadir el propio subdominio al inicio
            array_unshift($descendants, $userSubdomain);

            // Quitar duplicados
            $subdomainUserList = array_values(array_reduce($descendants, function ($carry, $user) {
                $carry[(string)$user['_id']] = $user;
                return $carry;
            }, []));

            // 🔥 EXCLUIR USUARIOS CONCRETOS
            $excludedIds = [
                '65fd4c2f05efc4aa4a050dc2',
                '65d704c63d2a9cbfd79e549a'
            ];

            $subdomainUserList = array_values(array_filter($subdomainUserList, function ($user) use ($excludedIds) {

                $id = $user['_id'] ?? null;

                // Por si viene en formato BSON
                if (is_array($id) && isset($id['$oid'])) {
                    $id = $id['$oid'];
                }

                return !in_array((string)$id, $excludedIds);
            }));
        }

        $sessionData['subdomainUserList'] = $subdomainUserList;




        $subdomainUserList = [];

        if ($userSubdomain && isset($userSubdomain['_id'])) {

            $subdomainId = $userSubdomain['_id'];

            // Sacar todos los usuarios por debajo del subdominio
            $descendants = UserHelper::hierarchy($subdomainId);

            // Añadir el propio subdominio al inicio
            array_unshift($descendants, $userSubdomain);

            // Quitar duplicados por si acaso
            $subdomainUserList = array_values(array_reduce($descendants, function ($carry, $user) {
                $carry[$user['_id']] = $user;
                return $carry;
            }, []));
        }


        $sessionData['subdomainUserList'] = $subdomainUserList;


        //LISTA DE TODOS LOS USUARIOS
        $userListComplete = User::all();

        $sessionData['userListComplete'] = $userListComplete;


        //PARTE COMPROBACIÓN JOAN
        if ($sessionData['userLogged']->email === 'naturgytest@crm.com') {



            //Compruebo si es contacto@informaenergia.com o uno de sus agentes para meterle comercializadoras especificas
            $informaenergiaId = '66167f6a81cff47c5e08b322';
            $isJoanAgent = false;

            dd(in_array($informaenergiaId, array_column($sessionData['userList'], '_id')));

            //Si el usuario con sesión iniciada no es Joan ( id arriba ) o uno superior ( lo compruebo viendo si esta el id de Joan dentro ) saco la lista de usuarios
            if ($sessionData['userLogged']->_id !== $informaenergiaId  && !in_array($informaenergiaId, array_column($sessionData['userList'], '_id'))) {
                //Saco la lista de usuarios de Joan y si esta la id ahí dentro se le asignan las comercializadoras
                $checkIdUserList = UserHelper::hierarchy($informaenergiaId);
            }

            if ($sessionData['userLogged']->_id === $informaenergiaId)
                $sessionData['customMarketers'] = ['Naturgy'];
        }


        //Saco los planes de Zoco
        $sessionData['zocoPlans'] = config('plans');

        return response()->json(['sessionData' => $sessionData], 200);
    }


    public static function getAllSuperiors($userId, $visited = [], $includeSelf = true)
    {
        // Evitar ciclos en la jerarquía
        if (in_array($userId, $visited)) {
            return [];
        }

        // Agregar el usuario actual a la lista de visitados
        $visited[] = $userId;

        // Obtener el usuario actual
        $user = User::find($userId);

        // Si no se encuentra el usuario, retornar vacío
        if (!$user) {
            return [];
        }

        $allSuperiors = [];

        // Solo incluir el propio usuario en la primera llamada
        if ($includeSelf) {
            $allSuperiors[] = $user->toArray();
        }

        // Si no tiene responsables, retornar la lista con el propio usuario
        if (empty($user->responsibles)) {
            return $allSuperiors;
        }

        // Obtener responsables directos
        $superiors = User::whereIn('_id', $user->responsibles)->get();

        // Convertir responsables actuales a un array
        $allSuperiors = array_merge($allSuperiors, $superiors->toArray());

        // Llamar recursivamente para cada responsable
        foreach ($superiors as $superior) {
            $allSuperiors = array_merge(
                $allSuperiors,
                self::getAllSuperiors($superior->_id, $visited, false) // Evitar duplicados con $visited
            );
        }

        return $allSuperiors;
    }



    //función para obtener los datos de la empresa
    public function getEnterpriseData(Request $request)
    {

        $url = $request['url'];

        $enterprise = Enterprise::where('url', $url)->first();

        return response()->json(['enterprise' => $enterprise], 200);
    }



    //funcion para checkearsi hay sesión de usuario
    public function checkUserLoggedSesion()
    {


        $userLogged = Auth::user();

        $userLogged = User::where('_id', $userLogged->_id)->first();


        if (!$userLogged) {
            Auth::logout();
            session()->flush();

            return response()->json([
                'authenticated' => false,
                'userLogged' => null,
                'userSubdomain' => null,
            ], 200);
        }


        $userListTop = self::getAllSuperiors($userLogged->_id);

        $userSubdomain = collect($userListTop)
            ->first(fn ($user) => $user['label'] === 'Usuario subdominio');


        session()->put('userLogged', $userLogged);
        session()->put('userSubdomain', $userSubdomain);



        return response()->json([
            'authenticated' => true,
            'userLogged' => $userLogged,
            'userSubdomain' => $userSubdomain,
        ], 200);
    }

    public function checkSubscriptionStatus()
    {
        $userLogged = session()->get('userLogged');

        if (!$userLogged) {
            return response()->json([
                'active' => false,
                'status' => null,
                'reason' => 'no_user_logged',
            ]);
        }

        $userSubdomain = UserHelper::getUserSubdomain($userLogged->_id);

        if (!$userSubdomain || !isset($userSubdomain['_id'])) {
            return response()->json([
                'active' => false,
                'status' => null,
                'reason' => 'no_user_subdomain',
            ]);
        }

        $enterprise = Enterprise::where('subdomainUser', $userSubdomain['_id'])->first();

        if (!$enterprise) {
            return response()->json([
                'active' => false,
                'status' => null,
                'reason' => 'enterprise_not_found',
            ]);
        }

        $subscription = $enterprise->subscription ?? null;
        $stripe = $enterprise->stripe ?? [];

        $status = $stripe['status'] ?? null;

        $active = !empty($subscription) && in_array($status, ['active', 'trialing'], true);

        return response()->json([
            'active' => $active,
            'status' => $status,
            'reason' => null,
        ]);
    }
}
