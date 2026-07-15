<?php

namespace App\Http\Controllers;

use App\Events\RefreshSession;
use App\Helpers\APIHelper;
use App\Helpers\UserHelper;
use App\Http\Models\Account;
use App\Http\Models\Contact;
use App\Http\Models\Enterprise;
use App\Http\Models\Key;
use App\Http\Models\Order;
use App\Http\Models\User;
use App\Mail\MasiveSubdomainMail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Image;
use MongoDB\BSON\ObjectId;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Models\Opportunity;

class UserController extends Controller
{


    public function indexAll(Request $request)
    {
        $users = User::all();

        return response()->json(['users' => $users], 200);
    }


    public function index(Request $request)
    {
        $users = UserHelper::hierarchy(session()->get('selectedUser')->_id);

        return response()->json(['users' => $users], 200);
    }

    public function show($id)
    {
        return response()->json(['user' => User::where('_id', $id)->first()->toArray()], 200);
    }


    //cambio de contraseña
    public function updatePassword($id, Request $request)
    {

        $inputs = $request->only(['current_password', 'password', 'password_confirmation']);
        $errors = [];

        // contraseña actual vacía
        if (isset($inputs['current_password']) && $inputs['current_password'] == '') {
            $code = '000x001';
            $errors['current_password'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        // nueva contraseña vacía
        if ($inputs['password'] == '') {
            $code = '000x001';
            $errors['password'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        // contraseña confirmación vacía
        if ($inputs['password_confirmation'] == '') {
            $code = '000x001';
            $errors['password_confirmation'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        $user = User::where('_id', $id)->first();

        //coincide contraseña actual
        if (isset($inputs['current_password']) && !Hash::check($inputs['current_password'], $user->password)) {
            $code = '001x001';
            $errors['current_password'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        //contraseña cumple requisitos
        if (!preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $inputs['password'])) {
            $code = '000x002';
            $errors['password'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        //contraseñas coinciden
        if ($inputs['password'] !== $inputs['password_confirmation']) {
            $code = '000x003';
            $errors['password_confirmation'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        if (count($errors) > 0)
            return response()->json(['errors' => $errors], 400);

        $user->password = Hash::make($inputs['password']);
        $user->save();

        return response()->json(['message' => 'La contraseña ha sido actualizada'], 200);
    }

    //no usada ahora mismo
    public function store(Request $request)
    {
        $requiredFields = ['firstName', 'lastName', 'password', 'password_confirmation', 'email', 'users'];
        $required = $request->only($requiredFields);
        $optional = $request->except($requiredFields);

        $errors = [];

        unset($optional['errors']);

        //compruebo si existe un usuario con el mismo email
        $userDuplicatedEmail = User::where('email', $required['email'])->first();
        if (!is_null($userDuplicatedEmail)) {
            $code = '001x002';
            $errors['email'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        //compruebo si existe un usuario con el mismo teléfono
        if (isset($optional['phone']) && $optional['phone'] && $optional['phone'] !== '') {
            $userDuplicatedPhone = User::where('phone', $optional['phone'])->first();
            if (!is_null($userDuplicatedPhone)) {
                $code = '001x007';
                $errors['phone'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
            }
        }

        // email vacío
        if ($required['email'] == '') {
            $code = '000x001';
            $errors['email'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        // nombre vacío
        if ($required['firstName'] == '') {
            $code = '000x001';
            $errors['firstName'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        // apellido vacío
        if ($required['lastName'] == '') {
            $code = '000x001';
            $errors['lastName'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        // teléfono valido
        if (isset($required['phone']) && $required['phone'] !== '' && !APIHelper::validatePhone($required['phone'])) {
            $code = '001x004';
            $errors['phone'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        if (!preg_match('/^\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])\S*$/', $required['password'])) {
            $code = '000x002';
            $errors['password'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        if ($required['password'] !== $required['password_confirmation']) {
            $code = '000x003';
            $errors['password_confirmation'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        if (count($required['users']) < 1) {
            $code = '000x001';
            $errors['users'] = ['code' => $code, 'message' => APIHelper::getResponseByCode($code)];
        }

        if (count($errors) > 0)
            return response()->json(['errors' => $errors], 400);



        //Compruebo con el plan y los extras si se puede crear el usuario


        $userCreated = User::insert([
            'firstName' => $required['firstName'],
            'lastName' => $required['lastName'],
            'gender' => $optional['gender'],
            'accountVerifiedAt' => '',
            'profileImage' => 'default.jpg',
            'isActive' => true,
            'label' => $optional['label'],
            'email' => $required['email'],
            'phone' => $optional['phone'] ?? '',
            'marketers' => [],
            'password' => Hash::make($required['password']),
            'province' => '',
            'town' => '',
            'address' => '',
            'postal' => '',
            'dni' => '',
            'responsible' => $required['users'],
            'verification_code' => '',
            'devices' => [],
            'favDevices' => [],
            'favRoutes' => [],
            'docs' => [],
            'createdAt' => Carbon::now()->timestamp
        ]);

        return response()->json(['message' => 'El usuario ha sido registrado en la aplicación', 'user' => $userCreated], 200);
    }

    //funcion para registrar un usuario mediante una key
    public function storeWithKey(Request $request)
    {
        $faker = Faker::create();

        $user = json_decode($request->get('user'));
        $serialFromRequest = json_decode($request->get('serial'));

        // Aceptamos key directa o dentro de serial, pero NO confiamos en el serial del frontend.
        $keyValue = $request->get('key') ?: data_get($serialFromRequest, 'key');

        if (!$user || !$keyValue) {
            return response()->json([
                'message' => 'Faltan datos para registrar el usuario.'
            ], 400);
        }

        // Buscar key real en BBDD
        $serial = Key::where('key', $keyValue)->first();

        if ($serial === null) {
            return response()->json([
                'message' => 'El código no es válido'
            ], 400);
        }

        if ($serial['activated_at']) {
            return response()->json([
                'message' => 'El código ya ha sido activado'
            ], 400);
        }

        $creationDate = new Carbon($serial['created_at']);
        $today = new Carbon();

        if ($creationDate->diffInDays($today) > (int) $serial['expiration']) {
            return response()->json([
                'message' => 'El código ha expirado'
            ], 400);
        }

        /*
         |--------------------------------------------------------------------------
         | COMPROBACIÓN PLAN
         |--------------------------------------------------------------------------
         | Importante: esto evita que la creación por key se salte el límite del plan.
         */
        $subdomainUserId = $serial['subdomainUser']
            ?? $serial['subdomain_user']
            ?? $serial['replace']
            ?? null;

        if (!$subdomainUserId) {
            return response()->json([
                'limit' => 'No se ha podido determinar el subdominio asociado a esta key.'
            ], 400);
        }

        $userList = UserHelper::hierarchy($subdomainUserId);

        $activeUserList = collect($userList)
            ->filter(function ($userItem) {
                return data_get($userItem, 'isActive') !== false;
            })
            ->values();

        $subscription = Enterprise::where('subdomainUser', $subdomainUserId)
            ->pluck('subscription')
            ->first();

        if (!$subscription) {
            return response()->json([
                'limit' => 'No puedes crear usuarios porque no tienes una suscripción activa.'
            ], 400);
        }

        $includedUsers = data_get($subscription, 'included.users');

        // null significa ilimitado
        if ($includedUsers !== null) {
            $extraUsers = (int) data_get($subscription, 'extras.recurring.users.amount', 0);
            $userLimit = (int) $includedUsers + $extraUsers;

            if ($activeUserList->count() >= $userLimit) {
                return response()->json([
                    'limit' => 'No puedes crear más usuarios debido al límite de tu plan. Cambia de plan o compra paquetes extra.'
                ], 400);
            }
        }

        /*
         |--------------------------------------------------------------------------
         | VALIDACIONES USUARIO
         |--------------------------------------------------------------------------
         */
        $errors = [];

        $user->email = strtolower($user->email);

        $userDuplicatedEmail = User::where('email', $user->email)->first();

        if (!is_null($userDuplicatedEmail)) {
            $code = '001x002';
            $errors['email'] = APIHelper::getResponseByCode($code);
        }

        if (isset($user->phone) && $user->phone !== '') {
            $userDuplicatedPhone = User::where('phone', $user->phone)->first();

            if (!is_null($userDuplicatedPhone)) {
                $code = '001x007';
                $errors['phone'] = APIHelper::getResponseByCode($code);
            }
        }

        if (count($errors) > 0) {
            return response()->json(['errors' => $errors], 400);
        }

        /*
         |--------------------------------------------------------------------------
         | CREACIÓN USUARIO
         |--------------------------------------------------------------------------
         | Mismo formato que createDirectly.
         */
        $userData = [
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'gender' => $user->gender,
            'profileImage' => 'default.jpg',
            'label' => $serial['label'],
            'isActive' => true,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'address' => $user->address ?? '',
            'postal' => $user->postal ?? $user->zip ?? '',
            'province' => $user->province ?? '',
            'locality' => $user->locality ?? '',
            'marketers' => [],
            'password' => Hash::make($user->password),
            'contactsArchived' => [],
            'accountsArchived' => [],
            'opportunitiesArchived' => [],
            'responsibles' => $serial['responsibles'] ?? [],
            'dni' => $user->dni ?? $user->DNI ?? '',
            'verification_code' => '',
            'permissions' => $serial['permissions'] ?? [],
            'docs' => [],
            'createdAt' => Carbon::now()->timestamp,
        ];

        if ($serial['label'] === 'Usuario demo') {
            $userData['demoExpiration'] = $serial['demoExpiration'];
            $userData['demoStartDate'] = $serial['demoStartDate'];
        }

        if ($serial['label'] === 'Usuario subdominio') {
            $userData['commissions'] = (object) [];

            $userData['agentsCanSeeZoco'] = true;

            $userData['settings'] = [
                "accountEmail" => true,
                "accountPhone" => true,
                "orderIBAN" => true,
                "orderMarketerChange" => false,
                "orderRenovation" => false,
                "accountAddress" => true,
                "accountLocality" => true,
                "accountPostal" => true,
                "accountProvince" => true,
                "orderAddress" => true,
                "orderPostal" => true,
                "orderProvince" => true,
                "orderTown" => true,
                "orderCupsValidation" => true,
            ];

            $userData['statuses'] = [
                [
                    "code" => "r",
                    "title" => "Recibido",
                    "color" => "receivedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "p",
                    "title" => "Pendiente",
                    "color" => "pendingStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "t",
                    "title" => "Tramitado",
                    "color" => "processedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "f",
                    "title" => "Firmado",
                    "color" => "signedStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "fc",
                    "title" => "Verificado",
                    "color" => "#D41492",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "ac",
                    "title" => "Aceptado",
                    "color" => "aceptedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "ap",
                    "title" => "Pendiente de activacion",
                    "color" => "activatedPendingStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "a",
                    "title" => "Activado",
                    "color" => "activatedStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "c",
                    "title" => "Comisionado",
                    "color" => "commissionedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "i",
                    "title" => "Incidencia",
                    "color" => "incidenceStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "s",
                    "title" => "Scoring",
                    "color" => "scoringStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "b",
                    "title" => "Baja",
                    "color" => "lowStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "bo",
                    "title" => "Borrador",
                    "color" => "lowStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "an",
                    "title" => "Anulado",
                    "color" => "morado",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
            ];
        }

        $userCreatedId = User::insertGetId($userData);

        /*
         |--------------------------------------------------------------------------
         | DEMO
         |--------------------------------------------------------------------------
         | Aquí NO debe guardar los contratos dentro de Account.orders.
         | Debe crear Account y luego Order::create(), como createDirectly.
         */
        if ($serial['label'] === 'Usuario demo') {
            $accountId = Account::insertGetId([
                'name' => 'Cuenta demo ' . $user->firstName . ' ' . $user->lastName,
                'accType' => '',
                'sector' => '',
                'CIF' => '11111111A',
                'origin' => '',
                'phone' => '666666666',
                'landLinePhone' => '999999999',
                'website' => strtolower(str_replace(' ', '', $user->firstName . $user->lastName . '.com')),
                'email' => strtolower(str_replace(' ', '', $user->firstName . $user->lastName . 'demo@gmail.com')),
                'observations' => '',
                'principalAcc' => '',
                'billingInfo' => [
                    'community' => 'Andalucía',
                    'province' => 'Córdoba',
                    'locality' => 'Castro del Río',
                    'address' => 'Avenida Jaen',
                    'zipCode' => '14840',
                ],
                'customFields' => [],
                'profileImage' => null,
                'usersIds' => [(string) $userCreatedId],
                'createdBy' => (string) $userCreatedId,
                'createdAt' => now()->subWeeks(2)->toDateTimeString()
            ]);

            $orders = [
                [
                    "name" => 'Cuenta demo ' . $user->firstName . ' ' . $user->lastName . ' - 2001RW',
                    "direc" => "C/ Palma 14",
                    "zip" => "14840",
                    "town" => "Córdoba",
                    "province" => "Castro del Río",
                    "source" => "",
                    "processingDate" => "",
                    "activationDate" => "",
                    "liquidationStatus" => "nl",
                    "productType" => "cl",
                    "marketer" => "Naturgy",
                    "fee" => "Tarifa 2.0TD",
                    "product" => "Por uso luz",
                    'commissions' => [
                        'subdomain' => 0,
                        'breakdown' => []
                    ],
                    "CUPS" => "ES0031101751102001RW",
                    "consumption" => 1382,
                    "IBAN" => "ES11 1111 1111 1111 1111 1111",
                    "docs" => [],
                    "statuses" => [
                        ["code" => "t", "date" => "2024-12-24 12:23:29", "creator" => (string) $userCreatedId],
                        ["code" => "s", "date" => "2024-12-31 02:02:02", "creator" => (string) $userCreatedId]
                    ],
                    'account' => (string) $accountId,
                    "errors" => [],
                    "transferDate" => "12/12/24",
                    "consumptionData" => [
                        "consumption" => [1000, 500, 200, 300, 200, 150],
                        "hiredPotency" => [3.810, 3.500, 4.100, 3.600, 3.400, 3.900]
                    ],
                    "observations" => "Sin llamada con cambio de titular",
                    "_id" => new ObjectId(),
                    "owner" => $user->firstName . ' ' . $user->lastName,
                    "createdAt" => (new \DateTime())->modify('-1 week -2 day')->format('Y-m-d H:i:s')
                ],
                [
                    'name' => 'Cuenta demo ' . $user->firstName . ' ' . $user->lastName . ' - 5001KZ',
                    'direc' => 'Avenida Castro del Río 66A',
                    'zip' => '14840',
                    'town' => 'Córdoba',
                    'province' => 'Castro del Río',
                    'source' => '',
                    'processingDate' => '2024-12-03',
                    'activationDate' => '2024-12-12',
                    'liquidationStatus' => 'nl',
                    'productType' => 'cl',
                    'marketer' => 'Endesa',
                    'fee' => 'Tarifa 3.0TD',
                    'product' => 'Tempo Open',
                    'CUPS' => 'ES0031103632455001KZ',
                    'consumption' => 4687,
                    'IBAN' => 'ES11 1111 1111 1111 1111 1111',
                    'docs' => [],
                    'statuses' => [
                        [
                            'code' => 'ac',
                            'date' => '2024-12-24 10:59:48',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 't',
                            'date' => '2024-12-24 09:39:47',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'p',
                            'date' => '2024-12-23 17:36:12',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'bo',
                            'date' => '2024-12-23 17:33:17',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'a',
                            'date' => '2024-12-27 09:01:24',
                            'creator' => (string) $userCreatedId
                        ]
                    ],
                    'account' => (string) $accountId,
                    'errors' => [],
                    'transferDate' => '03/12/24',
                    'consumptionData' => [
                        'consumption' => [1000, 1100, 1300, 700, 200, 800],
                        'hiredPotency' => [3.4, 3.5, 3.3, 3.4, 3.2, 3.5]
                    ],
                    'hiredPotency' => '3.450',
                    'observations' => '',
                    '_id' => new ObjectId(),
                    'owner' => $user->firstName . ' ' . $user->lastName,
                    'createdAt' => date("Y-m-d H:i:s"),
                    'commissions' => [
                        'subdomain' => 75,
                        'breakdown' => [
                            [
                                'id' => (string) $userCreatedId,
                                'level' => 0,
                                'commission' => 60
                            ]
                        ]
                    ],
                ],
                [
                    'name' => 'Cuenta demo ' . $user->firstName . ' ' . $user->lastName . ' - 4002XH',
                    'direc' => 'Plaza Madre Isabel 5A',
                    'zip' => '14840',
                    'town' => 'Córdoba',
                    'province' => 'Castro del Río',
                    'source' => '',
                    'processingDate' => '2024-12-11',
                    'activationDate' => '2024-12-18',
                    'liquidationStatus' => 'nl',
                    'productType' => 'cg',
                    'marketer' => 'Naturgy',
                    'fee' => 'Tarifa RL3',
                    'product' => 'Compromiso gas ONE',
                    'commissions' => [
                        'subdomain' => 60,
                        'breakdown' => [
                            [
                                'id' => (string) $userCreatedId,
                                'level' => 0,
                                'commission' => 48
                            ]
                        ]
                    ],
                    'CUPS' => 'ES0031101692744002XH',
                    'consumption' => 1665,
                    'IBAN' => 'ES11 1111 1111 1111 1111 1111',
                    'docs' => [],
                    'statuses' => [
                        [
                            'code' => 'ac',
                            'date' => '2024-12-18 12:54:18',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 't',
                            'date' => '2024-12-11 13:41:03',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'p',
                            'date' => '2024-12-11 13:08:34',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'a',
                            'date' => '2024-12-27 09:31:23',
                            'creator' => (string) $userCreatedId
                        ]
                    ],
                    'account' => (string) $accountId,
                    'transferDate' => '11/12/24',
                    'observations' => 'SOLAR  CON BATERIA',
                    'consumptionData' => [
                        'consumption' => [
                            754.5736363636365,
                            528.340606060606,
                            382.2250303030303,
                            0,
                            0,
                            0
                        ],
                        'hiredPotency' => [
                            3.3,
                            3.3,
                            0,
                            0,
                            0,
                            0
                        ]
                    ],
                    'hiredPotencyString' => "3.300",
                    '_id' => new ObjectId(),
                    'owner' => $user->firstName . ' ' . $user->lastName,
                    'createdAt' => date("Y-m-d H:i:s")
                ]
            ];

            foreach ($orders as $order) {
                Order::create($order);
            }

            $firstName = $faker->firstName;
            $lastName = $faker->lastName;

            $nameParts = explode(' ', $firstName);
            $surnameParts = explode(' ', $lastName);

            Contact::insertGetId([
                "name" => [
                    "first" => $nameParts[0],
                    "second" => isset($nameParts[1]) ? $nameParts[1] : ''
                ],
                "surname" => [
                    "first" => $surnameParts[0],
                    "second" => isset($surnameParts[1]) ? $surnameParts[1] : ''
                ],
                "email" => $nameParts[0] . $surnameParts[0] . 'demo@gmail.com',
                "phone" => "666666666",
                "nickname" => "",
                "accounts" => [(string) $accountId],
                "position" => "Administrador",
                "billingInfo" => [
                    "community" => "Andalucía",
                    "province" => "Córdoba",
                    "locality" => "Córdoba",
                    "address" => "Avda Isla Fuerteventura, 37",
                    "postal" => "14011"
                ],
                "customFields" => [],
                "profileImage" => null,
                "usersIds" => [
                    (string) $userCreatedId
                ],
                "createdBy" => (string) $userCreatedId,
                "createdAt" => date("Y-m-d H:i:s")
            ]);
        }

        /*
         |--------------------------------------------------------------------------
         | MARCAR KEY COMO USADA
         |--------------------------------------------------------------------------
         */
        $serialToUpdate = Key::where('key', $keyValue)->first();

        if (!$serialToUpdate || $serialToUpdate['activated_at']) {
            return response()->json([
                'message' => 'El código ya ha sido activado'
            ], 400);
        }

        $serialToUpdate->activated_at = Carbon::now()->format('Y-m-d H:i:s');
        $serialToUpdate->activated_by = (string) $userCreatedId;
        $serialToUpdate->save();

        return response()->json([
            'message' => 'El usuario ha sido registrado en la aplicación'
        ], 200);
    }

    //funcion para registrar usuario directamente desde network
    public function createDirectly(Request $request)
    {

        $faker = Faker::create();

        $data = json_decode($request['user']);
        $userSubdomain = json_decode($request['userSubdomain'], true);
        $userList = UserHelper::hierarchy($userSubdomain['_id']);
        $activeUserList = collect($userList)
            ->filter(function ($user) {
                return data_get($user, 'isActive') !== false;
            })
            ->values();

        $enterprise = json_decode($request['enterprise'], true);


        // COMPROBACIÓN PLAN
        // Compruebo si tiene el máximo de usuarios permitidos por plan + extras
            $subscription = Enterprise::where('subdomainUser', $userSubdomain['_id'])
                ->pluck('subscription')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'limit' => 'No puedes crear usuarios porque no tienes una suscripción activa.'
                ], 400);
            }

            $includedUsers = data_get($subscription, 'included.users');

            // null significa ilimitado
            if ($includedUsers !== null) {
                $extraUsers = (int) data_get($subscription, 'extras.recurring.users.amount', 0);

                $userLimit = (int) $includedUsers + $extraUsers;

                if ($activeUserList->count() >= $userLimit) {
                    return response()->json([
                        'limit' => 'No puedes crear más usuarios debido al límite de tu plan. Cambia de plan o compra paquetes extra.'
                    ], 400);
                }
            }


        $errors = [];

        //Transformo el email a minusculas
        $data->userData->email = strtolower($data->userData->email);

        //compruebo si existe un usuario con el mismo email
        $userDuplicatedEmail = User::where('email', $data->userData->email)->where('subdomain', $enterprise['_id'])->first();
        if (!is_null($userDuplicatedEmail)) {
            $code = '001x002';
            $errors['email'] = APIHelper::getResponseByCode($code);
        }

        //compruebo si existe un usuario con el mismo teléfono
        if (isset($data->userData->phone) && $data->userData->phone && $data->userData->phone !== '') {
            $userDuplicatedPhone = User::where('phone', $data->userData->phone)->where('subdomain', $enterprise['_id'])->first();
            if (!is_null($userDuplicatedPhone)) {
                $code = '001x007';
                $errors['phone'] = APIHelper::getResponseByCode($code);
            }
        }

        //Compruebo si hay errores y si los hay los devuelvo
        if (count($errors) > 0)
            return response()->json(['errors' => $errors], 400);


        $responsibles = $data->responsibles;

            //Si no se ha seleccionado ningún responsable, se pone de responsable el que lo ha creado ( ESTAN PUESTAS LAS IDs DE PRODUCCION, POR LO QUE PUEDE FALLAR )
            if (count($responsibles) === 0) {
                array_push($responsibles, session()->get('userLogged')->_id === '65cb57489c2c285441086a43' ? '65fd4c2f05efc4aa4a050dc2' : session()->get('userLogged')->_id);
            } else {
                $removeId = '65cb57489c2c285441086a43';
                $replaceId = '65fd4c2f05efc4aa4a050dc2';

                // Buscar y eliminar $removeId si existe
                $removeIndex = array_search($removeId, $responsibles);
                if ($removeIndex !== false) {
                    unset($responsibles[$removeIndex]); // Elimina Asercord

                    // Reindexa el array para mantenerlo continuo
                    $responsibles = array_values($responsibles);

                    // Agrega $replaceId si aún no está en el array
                    if (!in_array($replaceId, $responsibles)) {
                        $responsibles[] = $replaceId;
                    }
                }
            }

            $data->responsibles = $responsibles;



            //Creo el usuario
            $userData = [
                'firstName' => $data->userData->firstName,
                'lastName' => $data->userData->lastName,
                'gender' => $data->userData->gender,
                'profileImage' => 'default.jpg',
                'label' => $data->label,
                'isActive' => true,
                'email' => $data->userData->email,
                'phone' => $data->userData->phone ?? '',
                'address' => $data->userData->address,
                'postal' => $data->userData->zip,
                'province' => $data->userData->province,
                'locality' => $data->userData->locality,
                'marketers' => [],
                'password' => Hash::make($data->userData->password),
                'contactsArchived' => [],
                'accountsArchived' => [],
                'opportunitiesArchived' => [],
                'responsibles' => $data->responsibles,
                'dni' => $data->userData->DNI,
                'verification_code' => '',
                'permissions' => $data->permissions,
                'docs' => [],
                'subdomain' => $enterprise['_id'],
                'createdAt' => Carbon::now()->timestamp,
            ];

            //si es demo le meto el tiempo de expiración
            if ($data->label === 'Usuario demo') {
                $userData['demoExpiration'] = $data->demoExpiration;
                $userData['demoStartDate'] = $data->demoStartDate;
            }


            //si es Usuario subdominio o tiene uno de estos como responsable se añadira commission vacío ( para seleccionar cada comision de usuario posteriormente )
            /*$hasCommission = false;

            foreach ($responsibles as $responsible) {

                $temporalUser = User::where('_id', $responsible)->first();

                if ($temporalUser->label === 'Usuario subdominio')
                    $hasCommission = true;
            }

            if ($data->label === 'Usuario subdominio' || $hasCommission) {
                $userData['commissions'] = [];
            }
            */



        //Si es usuario subdominio le meto lo necesario para este
        if ($data->label === 'Usuario subdominio') {
            $userData['commissions'] = (object) [];

            $userData['agentsCanSeeZoco'] = true;

            $userData['settings'] = [
                "accountEmail" => true,
                "accountPhone" => true,
                "orderIBAN" => true,
                "orderMarketerChange" => false,
                "orderRenovation" => false,
                "accountAddress" => true,
                "accountLocality" => true,
                "accountPostal" => true,
                "accountProvince" => true,
                "orderAddress" => true,
                "orderPostal" => true,
                "orderProvince" => true,
                "orderTown" => true,
                "orderCupsValidation" => true,
            ];

            //estados por defecto
            $userData['statuses'] =
            [
                [
                    "code" => "r",
                    "title" => "Recibido",
                    "color" => "receivedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "p",
                    "title" => "Pendiente",
                    "color" => "pendingStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "t",
                    "title" => "Tramitado",
                    "color" => "processedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "f",
                    "title" => "Firmado",
                    "color" => "signedStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "fc",
                    "title" => "Verificado",
                    "color" => "#D41492",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "ac",
                    "title" => "Aceptado",
                    "color" => "aceptedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "ap",
                    "title" => "Pendiente de activacion",
                    "color" => "activatedPendingStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "a",
                    "title" => "Activado",
                    "color" => "activatedStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "c",
                    "title" => "Comisionado",
                    "color" => "commissionedStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "i",
                    "title" => "Incidencia",
                    "color" => "incidenceStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "s",
                    "title" => "Scoring",
                    "color" => "scoringStatus",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ],
                [
                    "code" => "b",
                    "title" => "Baja",
                    "color" => "lowStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "bo",
                    "title" => "Borrador",
                    "color" => "lowStatus",
                    "archived" => false,
                    "default" => true,
                    "limitedTo" => []
                ],
                [
                    "code" => "an",
                    "title" => "Anulado",
                    "color" => "morado",
                    "archived" => false,
                    "default" => false,
                    "limitedTo" => []
                ]
            ];
        }


        // Crear al usuario
        $userCreatedId = User::insertGetId($userData);


        //Le creo una cuenta con contratos de prueba si es demo
        if ($data->label === 'Usuario demo') {

            //Creo cuenta demo
            $accountId = Account::insertGetId([
                'name' => 'Cuenta demo ' . $data->userData->firstName . ' ' . $data->userData->lastName,
                'accType' => '',
                'sector' => '',
                'CIF' => '11111111A',
                'origin' => '',
                'phone' => '666666666',
                'landLinePhone' => '999999999',
                'website' => strtolower(str_replace(' ', '', $data->userData->firstName . $data->userData->lastName . '.com')),
                'email' => strtolower(str_replace(' ', '', $data->userData->firstName . $data->userData->lastName . 'demo@gmail.com')),
                'observations' => '',
                'principalAcc' => '',
                'billingInfo' => [
                    'community' => 'Andalucía',
                    'province' => 'Córdoba',
                    'locality' => 'Castro del Río',
                    'address' => 'Avenida Jaen',
                    'zipCode' => '14840',
                ],
                'customFields' => [],
                'profileImage' => null,
                'usersIds' => [(string) $userCreatedId],
                'createdBy' => (string) $userCreatedId,
                'createdAt' => now()->subWeeks(2)->toDateTimeString()
            ]);


            $orders = [
                [
                    "name" => 'Cuenta demo ' . $data->userData->firstName . ' ' . $data->userData->lastName . ' - 2001RW',
                    "direc" => "C/ Palma 14",
                    "zip" => "14840",
                    "town" => "Córdoba",
                    "province" => "Castro del Río",
                    "source" => "",
                    "processingDate" => "",
                    "activationDate" => "",
                    "liquidationStatus" => "nl",
                    "productType" => "cl",
                    "marketer" => "Naturgy",
                    "fee" => "Tarifa 2.0TD",
                    "product" => "Por uso luz",
                    'commissions' => [
                        'subdomain' => 0,
                        'breakdown' => []
                    ],
                    "CUPS" => "ES0031101751102001RW",
                    "consumption" => 1382,
                    "IBAN" => "ES11 1111 1111 1111 1111 1111",
                    "docs" => [],
                    "statuses" => [
                        ["code" => "t", "date" => "2024-12-24 12:23:29", "creator" => (string) $userCreatedId],
                        ["code" => "s", "date" => "2024-12-31 02:02:02", "creator" => (string) $userCreatedId]
                    ],
                    'account' => (string) $accountId,
                    "errors" => [],
                    "transferDate" => "12/12/24",
                    "consumptionData" => [
                        "consumption" => [1000, 500, 200, 300, 200, 150],
                        "hiredPotency" => [3.810, 3.500, 4.100, 3.600, 3.400, 3.900]
                    ],
                    "observations" => "Sin llamada con cambio de titular",
                    "_id" => new ObjectId(),
                    "owner" => $data->userData->firstName . ' ' . $data->userData->lastName,
                    "createdAt" => (new \DateTime())->modify('-1 week -2 day')->format('Y-m-d H:i:s')
                ],
                [
                    'name' => 'Cuenta demo ' . $data->userData->firstName . ' ' . $data->userData->lastName . ' - 5001KZ',
                    'direc' => 'Avenida Castro del Río 66A',
                    'zip' => '14840',
                    'town' => 'Córdoba',
                    'province' => 'Castro del Río',
                    'source' => '',
                    'processingDate' => '2024-12-03',
                    'activationDate' => '2024-12-12',
                    'liquidationStatus' => 'nl',
                    'productType' => 'cl',
                    'marketer' => 'Endesa',
                    'fee' => 'Tarifa 3.0TD',
                    'product' => 'Tempo Open',
                    'CUPS' => 'ES0031103632455001KZ',
                    'consumption' => 4687,
                    'IBAN' => 'ES11 1111 1111 1111 1111 1111',
                    'docs' => [],
                    'statuses' => [
                        [
                            'code' => 'ac',
                            'date' => '2024-12-24 10:59:48',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 't',
                            'date' => '2024-12-24 09:39:47',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'p',
                            'date' => '2024-12-23 17:36:12',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'bo',
                            'date' => '2024-12-23 17:33:17',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'a',
                            'date' => '2024-12-27 09:01:24',
                            'creator' => (string) $userCreatedId
                        ]
                    ],
                    'account' => (string) $accountId,
                    'errors' => [],
                    'transferDate' => '03/12/24',
                    'consumptionData' => [
                        'consumption' => [1000, 1100, 1300, 700, 200, 800],
                        'hiredPotency' => [3.4, 3.5, 3.3, 3.4, 3.2, 3.5]
                    ],
                    'hiredPotency' => '3.450',
                    'observations' => '',
                    '_id' => new ObjectId(),
                    'owner' => $data->userData->firstName . ' ' . $data->userData->lastName,
                    'createdAt' => date("Y-m-d H:i:s"),
                    'commissions' => [
                        'subdomain' => 75,
                        'breakdown' => [['userId' => $data->userData->_id, 'level' => 0, 'commission' => 60]]
                    ],
                ],
                [
                    'name' => 'Cuenta demo ' . $data->userData->firstName . ' ' . $data->userData->lastName . ' - 4002XH',
                    'direc' => 'Plaza Madre Isabel 5A',
                    'zip' => '14840',
                    'town' => 'Córdoba',
                    'province' => 'Castro del Río',
                    'source' => '',
                    'processingDate' => '2024-12-11',
                    'activationDate' => '2024-12-18',
                    'liquidationStatus' => 'nl',
                    'productType' => 'cg',
                    'marketer' => 'Naturgy',
                    'fee' => 'Tarifa RL3',
                    'product' => 'Compromiso gas ONE',
                    'commissions' => [
                        'subdomain' => 60,
                        'breakdown' => [['userId' => $data->userData->_id, 'level' => 0, 'commission' => 48]]
                    ],
                    'CUPS' => 'ES0031101692744002XH',
                    'consumption' => 1665,
                    'IBAN' => 'ES11 1111 1111 1111 1111 1111',
                    'docs' => [],
                    'statuses' => [
                        [
                            'code' => 'ac',
                            'date' => '2024-12-18 12:54:18',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 't',
                            'date' => '2024-12-11 13:41:03',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'p',
                            'date' => '2024-12-11 13:08:34',
                            'creator' => (string) $userCreatedId
                        ],
                        [
                            'code' => 'a',
                            'date' => '2024-12-27 09:31:23',
                            'creator' => (string) $userCreatedId
                        ]
                    ],
                    'account' => (string) $accountId,
                    'transferDate' => '11/12/24',
                    'observations' => 'SOLAR  CON BATERIA',
                    'consumptionData' => [
                        'consumption' => [
                            754.5736363636365,
                            528.340606060606,
                            382.2250303030303,
                            0,
                            0,
                            0
                        ],
                        'hiredPotency' => [
                            3.3,
                            3.3,
                            0,
                            0,
                            0,
                            0
                        ]
                    ],
                    'hiredPotencyString' => "3.300",
                    '_id' => new ObjectId(),
                    'owner' => $data->userData->firstName . ' ' . $data->userData->lastName,
                    'createdAt' => date("Y-m-d H:i:s")
                ]
            ];

            foreach ($orders as $order) {
                Order::create($order);
            }




            $firstName = $faker->firstName;
            $lastName = $faker->lastName;
            $nameParts = explode(' ', $firstName);
            $surnameParts = explode(' ', $lastName);

            //Creo contacto demo
            $contactId = Contact::insertGetId([
                "name" => [
                    "first" => $nameParts[0],
                    "second" => isset($nameParts[1]) ? $nameParts[1] : ''
                ],
                "surname" => [
                    "first" => $surnameParts[0],
                    "second" => isset($surnameParts[1]) ? $surnameParts[1] : ''
                ],
                "email" => $nameParts[0] . $surnameParts[0] . 'demo@gmail.com',
                "phone" => "666666666",
                "nickname" => "",
                "accounts" => [(string) $accountId],
                "position" => "Administrador",
                "billingInfo" => [
                    "community" => "Andalucía",
                    "province" => "Córdoba",
                    "locality" => "Córdoba",
                    "address" => "Avda Isla Fuerteventura, 37",
                    "postal" => "14011"
                ],
                "customFields" => [],
                "profileImage" => null,
                "usersIds" => [
                    (string) $userCreatedId
                ],
                "createdBy" => (string) $userCreatedId,
                "createdAt" => date("Y-m-d H:i:s")
            ]);
        }


        //Compruebo si por subdominio hay que enviar correo de bienvenida
        if (isset($userSubdomain['welcomeEmail']) && $userSubdomain['welcomeEmail']['active'] && $userSubdomain['welcomeEmail']['subject'] !== '' && $userSubdomain['welcomeEmail']['message'] !== '') {

            //Si tiene imagenes la convierto
            $userSubdomain['welcomeEmail']['message'] = $this->extractAndSaveBase64Images(
                $userSubdomain['welcomeEmail']['message'],
                'email',
                'assets/emails',
                $enterprise
            );

            $htmlMessage = '
                <body style="background-color: #f8f9fb; font-family: sans-serif; padding: 40px;">
                  <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="center">
                        <table id="content" width="900" style="background-color: #ffffff; border-radius: 10px; padding: 40px; text-align: left;">
                          <tr>
                            <td style="padding-bottom: 20px;">
                                <img src="https://' . $enterprise['url'] . '/assets/enterprises/' . $enterprise['asset_folder'] . '/logos/mini-dark.png"
                                     alt="Logo Zoco Energía"
                                     style="height: 80px; max-width: 100%; display: block;">
                            </td>
                          </tr>
                          <tr>
                            <td style="font-size: 16px; color: #333;">
                              ' . $userSubdomain['welcomeEmail']['message'] . '
                            </td>
                          </tr>
                          <tr>
                            <td style="padding-top: 30px; padding-bottom: 30px; text-align: center;">
                              <a href="' . 'https://' . $enterprise['url'] . '" target="_blank"
                                 style="background-color: #2192FF; color: #ffffff; text-decoration: none;
                                        padding: 10px 24px; border-radius: 6px; font-size: 16px; font-weight: bold; display: inline-block;">
                                Accede al CRM
                              </a>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </body>';

            //Mail::to($data->userData->email)->send(new MasiveSubdomainMail($userSubdomain['welcomeEmail']['subject'], $htmlMessage, []));

            $to = $data->userData->email;
            $subject = $userSubdomain['welcomeEmail']['subject'];

            //Compruebo si se envía desde el correo de Zoco o desde el subdominio
            if (isset($enterprise['mailConfig']) && !!env("MAIL_USERNAME_" . $enterprise['mailConfig']) && !!env("MAIL_PASSWORD_" . $enterprise['mailConfig'])) {

                $mailName = strtoupper($enterprise['mailConfig']);

                Config::set('mail.mailers.smtp.host', !!env('MAIL_HOST_' . $mailName) ? env('MAIL_HOST_' . $mailName) : env('MAIL_HOST'));
                Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME_' . $mailName));
                Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD_' . $mailName));
                Config::set('mail.from.address', env('MAIL_FROM_ADDRESS_' . $mailName));
                Config::set('mail.from.name', env('MAIL_FROM_NAME_' . $mailName));
            }

            /*Mail::send([], [], function ($message) use ($to, $subject, $htmlMessage) {
                $message->to($to)
                    ->subject($subject)
                    ->html($htmlMessage);
            });*/
        }


        return response()->json(['message' => 'Usuario ' . $data->userData->firstName . ' ' . $data->userData->lastName . ' registrado!'], 200);
    }

    function extractAndSaveBase64Images(string $html, $disk = 'email', $publicPath = 'assets/emails', $enterprise): string
    {
        // 1) Busca todas las Data URIs en <img src="...">
        preg_match_all(
            '/<img[^>]+src=[\'"](?P<data>data:image\/[^;]+;base64,[^\'"]+)[\'"][^>]*>/i',
            $html,
            $matches
        );

        if (empty($matches['data'])) {
            // No hay imágenes en Base64, devolvemos el HTML tal cual
            return $html;
        }

        // Asegúrate de que el directorio exista
        $storagePath = storage_path("app/{$disk}");
        if (! file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }

        // Vamos reemplazando cada Data URI por su URL guardada
        foreach (array_unique($matches['data']) as $dataUri) {
            // Separa tipo MIME y datos
            list($meta, $b64data) = explode(',', $dataUri, 2);
            // Ej: "data:image/png;base64"
            preg_match('#data:(image/[^;]+);base64#', $meta, $m);
            $mime = $m[1] ?? 'application/octet-stream';
            // Extensión a partir del mime
            $ext = explode('/', $mime)[1] ?? 'bin';

            // Prepara nombre único
            $filename = 'welcome_quill_image' . '_' .
                strtolower(preg_replace('/\s+/', '-', trim($enterprise['name']))) .
                '.' . $ext;

            // Guarda el fichero decodificado
            $binary = base64_decode($b64data);

            //solo lo meto si no existe
            if (!Storage::disk($disk)->exists($filename))
                Storage::disk($disk)->put($filename, $binary);

            // Construye la URL pública
            // Asume que has hecho: php artisan storage:link
            // y expones storage/app/email en public/assets/emails
            $url = asset("{$publicPath}/{$filename}");

            // Reemplaza en el HTML
            $html = str_replace($dataUri, $url, $html);
        }

        return $html;
    }

    //funcion para actualizar la información de un usuario
    public function update(Request $request)
    {

        $user = json_decode($request['user'], true);
        $enterprise = json_decode($request['enterprise'], true);
        $newPassword = (string) $request['newPassword'];

        $errors = [];

        //Transformo el email a minusculas
        $user['email'] = strtolower($user['email']);

        //Compruebo que el email y el telefono no pertenezca a ningún usuario
        if ($user['phone'] && $user['phone'] !== '') {
            $userDuplicatedPhone = User::where('_id', '!=', $user['_id'])
                ->where('subdomain', $request['enterprise'])
                ->where(function ($query) use ($user) {
                    $query->where('phone', $user['phone'])
                        ->orWhere('secondaryPhones', $user['phone']);
                })->first();

            if ($userDuplicatedPhone )
                $errors['phone'] = 'El teléfono ya existe en la base de datos';
        }

        $userDuplicatedEmail = User::where('email', $user['email'])->where('subdomain', $request['enterprise'])->first();
        if (!is_null($userDuplicatedEmail) && $userDuplicatedEmail->_id != $user['_id']) {
            $errors['phone'] = 'El email ya existe en la base de datos';
        }

        $userToModify = User::where('_id', $user['_id'])->first();
        $userToModify->firstName = $user['firstName'];
        $userToModify->lastName = $user['lastName'];
        $userToModify->dni = $user['dni'];
        $userToModify->gender = $user['gender'];
        $userToModify->email = $user['email'];
        $userToModify->phone = $user['phone'];
        $userToModify->address = $user['address'];
        $userToModify->postal = $user['postal'];
        $userToModify->marketers = $user['marketers'];
        $userToModify->responsibles = $user['responsibles'];


        if (isset($user['isActive']))
            $userToModify->isActive = $user['isActive'];

        if(isset($user['secondaryPhones']))
            $userToModify->secondaryPhones = $user['secondaryPhones'];

        if(isset($user['comparatorMarketers']))
            $userToModify->comparatorMarketers = $user['comparatorMarketers'];


        if(isset($user['comparatorHiddenProducts']))
            $userToModify->comparatorHiddenProducts = $user['comparatorHiddenProducts'];


        if (isset($userToModify->settings)) {
            $userToModify->settings = $user['settings'];
        }


        if (isset($user['welcomeEmail']))
            $userToModify->welcomeEmail = $user['welcomeEmail'];

        if (isset($user['canAssignTo']))
            $userToModify->canAssignTo = $user['canAssignTo'];

        if (!isset($user['province']))
            $errors['province'] = 'La provincia no puede estar vacía';
        else
            $userToModify->province = $user['province'];

        if (!isset($user['locality']))
            $errors['locality'] = 'La población no puede estar vacía';
        else
            $userToModify->locality = $user['locality'];

        $userToModify->permissions = $user['permissions'];


        $userToModify->label = $user['label'];

        if (isset($userToModify->demoExpiration) && !isset($user['demoExpiration'])) {
            $userToModify->unset('demoExpiration');
            $userToModify->unset('demoStartDate');
        } else if (isset($user['demoExpiration'])) {
            $userToModify->demoExpiration = $user['demoExpiration'];
            $userToModify->demoStartDate = $user['demoStartDate'];
        }


        // Cuenta inhabilitable / deshabilitada temporalmente
        $isInactivable = isset($user['inactivable']) && $user['inactivable'] === true;

        // Si la cuenta es inhabilitable, siempre debe estar activa y sin temporalActive
        if ($isInactivable) {
            $userToModify->inactivable = true;
            $userToModify->isActive = true;

            if (isset($userToModify->temporalActive)) {
                $userToModify->unset('temporalActive');
            }
        } else {
            // Si ya no es inhabilitable, quitamos la propiedad
            if (isset($userToModify->inactivable)) {
                $userToModify->unset('inactivable');
            }

            // Si el usuario está activo, no necesita temporalActive
            if (isset($user['isActive']) && $user['isActive'] === true) {
                if (isset($userToModify->temporalActive)) {
                    $userToModify->unset('temporalActive');
                }
            } else {
                // Si está desactivado:
                // - Sin fecha seleccionada => guardamos una fecha antigua para que bloquee
                // - Con fecha seleccionada => acceso temporal hasta esa fecha
                $userToModify->temporalActive = !empty($user['temporalActive'])
                    ? $user['temporalActive']
                    : '2000-01-01';

                $userToModify->isActive = false;
            }
        }

        //Contraseña
        if (isset($newPassword) && $newPassword !== '')
            $userToModify->password = Hash::make($newPassword);


        //Más opciones
        //--> Permitir agentes tramitar con Zoco Energía
        if ($user['label'] === 'Usuario subdominio' && $user['email'] !== 'soporte@zocoenergia.com')
            $userToModify->agentsCanSeeZoco = $user['agentsCanSeeZoco'] ?? false;

        //Correo de notificaciones
        $enterpriseToModify = null;


        if (isset($enterprise['_id'])) {
            $enterpriseToModify = Enterprise::where('_id', $enterprise['_id'])->first();
        }

        if ($enterpriseToModify && isset($enterprise['notification_email'])) {
            $enterpriseToModify->notification_email = $enterprise['notification_email'];
            $enterpriseToModify->save();
        }


        //Si el usuario es subdominio o esta por debajo de un subdominio o por debajo de visualizador
        $isDownSubdomain = false;

        $userListComplete = User::all();

        if ($user['label'] !== 'Usuario subdominio' && !in_array('65fd4c2f05efc4aa4a050dc2', $user['responsibles']) && $user['_id'] !== '65d704c63d2a9cbfd79e549a') {
            // Iterar sobre los responsables
            foreach ($user['responsibles'] as $responsible) {
                // Buscar el usuario correspondiente en $userListComplete
                foreach ($userListComplete as $nowUser) {
                    if ($nowUser['_id'] === $responsible && $nowUser['label'] === 'Usuario subdominio') {
                        $isDownSubdomain = true;
                        break 2; // Salir del bucle de responsables y búsqueda de usuario
                    }
                }
            }
        }

        //Listas guardadas de correos
        if (isset($user['emailSavedLists']))
            $userToModify->emailSavedLists = $user['emailSavedLists'];

        //Asignar comisiones por puntos
        if (isset($user['commInPoints']))
            $userToModify->commInPoints = $user['commInPoints'];
        else
            $userToModify->unset('commInPoints');

        if (isset($user['notSendStatusEmails']))
            $userToModify->notSendStatusEmails = $user['notSendStatusEmails'];
        else
            $userToModify->unset('notSendStatusEmails');

        //Autofactura
        if (isset($user['selfInvoicing']))
            $userToModify->selfInvoicing = $user['selfInvoicing'];

        if (isset($user['commissions'])) {
            //Si se le asigna comisión
            $userToModify->commissions = $user['commissions'];
        }


        //Documentos de usuario ( públicos y privados )
        if (isset($user['docs'])) {

            $userDocs = $user['docs'];

            foreach ($userDocs as $docInd => &$doc) {

                //Saco el archivo
                $file = $request->file('docFile' . $docInd);

                //Compruebo si se ha adjuntado un documento o solo se ha puesto título
                if (isset($doc['fileValue']) && isset($file)) {

                    //Compruebo si existe el archivo
                    $existFile = (is_string($file) && Storage::disk('profile')->exists($doc['value']));

                    if (!$existFile) {

                        //Creo el nombre del archivo para guardar
                        $originalName = $file->getClientOriginalName();
                        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                        // Evita duplicados: tiempo + ID único + nombre original limpio
                        $docFileName = time() . '_' . uniqid() . '_' . $baseName . '.' . $extension;

                        if (!Storage::disk('profile')->exists($docFileName)) {
                            Storage::disk('profile')->put($docFileName, file_get_contents($file));
                        }


                        //Meto el nombre en el campo value para registrarlo
                        $doc['value'] = $docFileName;

                        //Elimino fileValue que es el archivo en sí, que no lo necesito ya que estará en el FTP
                        unset($doc['fileValue']);
                    } else {
                        unset($doc['fileValue']);
                    }
                }
            }

            $user['docs'] = $userDocs;

            $userToModify->docs = $user['docs'];
        }


        if (count($errors) > 0)
            return response()->json(['errors' => $errors], 400);


        $userToModify->save();
        if (isset($enterpriseToModify)) {
            $enterpriseToModify->save();
        }

        return response()->json(['user' => $userToModify], 200);
    }

    //funcion para actualizar la imagen de un usuario
    public function updateImage($id, Request $request)
    {
        $user = User::where('_id', $id)->first();


        $file = $request->file('file');
        $fileName = sprintf("profile_%s_%s.jpg", Carbon::now()->timestamp, $user->_id);

        $image = Image::make($file)->orientate();

        //logica de recorte
        $size = min($image->width(), $image->height());
        $position = (object)['x' => 0, 'y' => 0];
        $margin = abs($image->width() - $image->height()) / 2;
        if ($image->width() > $image->height()) $position->x = $margin;
        else $position->y = $margin;
        $image->crop($size, $size, intval($position->x), intval($position->y));

        if ($user->profileImage != 'default.jpg')
            Storage::disk('profile')->delete($user->profileImage);

        Storage::disk('profile')->put($fileName, $image->encode());

        $user->profileImage = $fileName;
        $user->save();

        return response()->json(['user' => $user], 200);
    }

    //funcion para eliminar un usuario
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        Opportunity::where('userId', $user->id)->delete();
        Contact::where('userId', $user->id)->delete();
        Account::where('userId', $user->id)->delete();

        $user->update([
            'contactsArchived' => null,
            'accountsArchived' => null,
            'opportunitiesArchived' => null,
        ]);

        $user->delete();

        return response()->json(['message' => 'El usuario y sus relaciones han sido eliminados correctamente'], 200);
    }


    //funcion para eliminar todos los usuarios seleccionados
    public function deleteAllSelectedUsers(Request $request)
    {

        $idsToRemove = $request['idsToRemove'];

        //Lo saco de el array de contactos del usuario
        foreach ($idsToRemove as $id) {
            User::destroy($id);
        }

        return response()->json(['message' => 'Los usuarios han sido eliminados correctamente'], 200);
    }


    //funcion para comprobar si es correcta la antigua contraseña para cambiarla
    public function checkOldPassword(Request $request)
    {

        $id = $request['id'];
        $password = $request['password'];

        $user = User::where('_id', $id)->first();

        $isCorrect = password_verify($password, $user['password']);

        if ($isCorrect)
            return response()->json(['isCorrect' => $isCorrect], 200);
        else
            return response()->json(['isCorrect' => $isCorrect], 400);
    }


    //funcion para cambiar contraseña
    public function changePassword(Request $request)
    {

        $id = $request['id'];
        $password = $request['password'];

        $user = User::where('_id', $id)->first();

        $user->password = Hash::make($password);

        $user->save();

        return response()->json(['message' => 'La contraseña ha sido actualizada correctamente'], 200);
    }


    //función para obtener el creador de una cuenta
    public static function getAccountCreator(Request $request)
    {

        $account = Account::where('_id', $request['accountId'])->first();

        $user = User::where('_id', $account->createdBy)->first();

        return response()->json(['userCreator' => $user], 200);
    }


    public static function getUserHierarchy($id)
    {
        return response()->json(['userHierarchy' => UserHelper::hierarchy($id)], 200);
    }


    public function bulk(Request $request) {

        $data = $request->all();
        if (! isset($data['users']) || ! is_array($data['users'])) {
            return response()->json([
                'message' => "Debe enviarse un array 'users' en el cuerpo de la petición."
            ], 400);
        }

        $userLogged   = session()->get('userLogged');
        $creatorId    = $userLogged?->_id ?? null;
        $creatorEmail = strtolower($userLogged->email ?? '');


        $userSubdomain = UserHelper::getUserSubdomain($userLogged->_id);
        $userList = UserHelper::hierarchy($userSubdomain['_id']);



        // COMPROBACIÓN PLAN
            // Compruebo si tiene el máximo de usuarios permitidos por plan + extras
            $subscription = Enterprise::where('subdomainUser', $userSubdomain['_id'])
                ->pluck('subscription')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'limit' => 'No puedes importar usuarios porque no tienes una suscripción activa.'
                ], 400);
            }

            $currentUsers = count($userList);
            $usersToImport = count($data['users'] ?? []);

            $includedUsers = data_get($subscription, 'included.users');

            // null significa ilimitado
            if ($includedUsers !== null) {
                $extraUsers = (int) data_get($subscription, 'extras.recurring.users.amount', 0);

                $userLimit = (int) $includedUsers + $extraUsers;
                $totalAfterImport = $currentUsers + $usersToImport;
                $availableUsers = max($userLimit - $currentUsers, 0);

                if ($totalAfterImport > $userLimit) {
                    return response()->json([
                        'limit' => "Se ha intentado importar un nº de usuarios mayor al máximo disponible en tu plan. Puedes importar {$availableUsers} usuario(s) más. Cambia de plan o compra usuarios extra."
                    ], 400);
                }
            }




        // Responsable por defecto
        $defaultResponsible = $creatorEmail === 'soporte@zocoenergia.com'
            ? '65fd4c2f05efc4aa4a050dc2'
            : ($creatorId ? (string)$creatorId : null);

        $errors  = [];
        $created = [];

        foreach ($data['users'] as $index => $rawRow) {
            $rowIndex = $index + 1;

            // 1) Normalizar todos los valores string: trim + null → ''
            $row = array_map(function($v) {
                if (is_null($v)) return '';
                if (is_string($v)) return trim($v);
                return $v;
            }, $rawRow);

            // 2) Lowercase en email para evitar duplicados por mayúsculas
            $row['email'] = strtolower($row['email'] ?? '');

            // 3) Validaciones manuales de unicidad
            $rowErrors = [];
            if (! $row['email']) {
                $rowErrors[] = "Fila $rowIndex: Falta 'email'.";
            } elseif (User::where('email', $row['email'])->where('subdomain', $request['enterprise'])->exists()) {
                $rowErrors[] = "Fila $rowIndex: El correo ya está registrado.";
            }
            if (! $row['dni']) {
                $rowErrors[] = "Fila $rowIndex: Falta 'dni'.";
            } elseif (User::where('dni', $row['dni'])->where('subdomain', $request['enterprise'])->exists()) {
                $rowErrors[] = "Fila $rowIndex: El DNI ya está registrado.";
            }
            if ($row['phone'] && $row['phone'] !== '' && User::where('phone', $row['phone'])->where('subdomain', $request['enterprise'])->exists()) {
                $rowErrors[] = "Fila $rowIndex: El teléfono ya está registrado.";
            }

            // Si ya hubo error de unicidad, saltar
            if (count($rowErrors)) {
                $errors = array_merge($errors, $rowErrors);
                continue;
            }

            // 4) Intentar crear usuario
            try {
                $user = new User();
                $user->_id      = new ObjectId();
                $user->firstName = $row['firstName'] ?? null;
                $user->lastName  = $row['lastName']  ?? null;
                $user->email     = $row['email'];
                $user->dni       = $row['dni'];
                $user->gender    = $row['gender'] ?? null;
                $user->phone     = $row['phone'] ?? '';
                $user->address   = $row['address'] ?? null;
                $user->postal    = $row['postal']  ?? null;
                $user->province  = $row['province'] ?? null;
                $user->locality  = $row['locality'] ?? null;
                $user->subdomain = $request['enterprise'];

                // Responsables
                if (! empty($row['responsibles']) && is_array($row['responsibles'])) {
                    $respIds = [];
                    foreach ($row['responsibles'] as $respEmail) {
                        $respEmail = strtolower(trim($respEmail));
                        $respUser = User::where('email', $respEmail)->where('subdomain', $request['enterprise'])->first();
                        if ($respUser) {
                            $respIds[] = (string)$respUser->_id;
                        } else {
                            $errors[] = "Fila $rowIndex: No existe usuario con email $respEmail para responsable.";
                        }
                    }
                    $user->responsibles = $respIds;
                } else {
                    $user->responsibles = $defaultResponsible ? [$defaultResponsible] : [];
                }

                // Resto de campos por defecto
                $user->password              = Hash::make($row['password'] ?? '');
                $user->profileImage          = 'default.jpg';
                $user->label                 = 'Usuario';
                $user->isActive              = true;
                $user->contactsArchived      = [];
                $user->accountsArchived      = [];
                $user->opportunitiesArchived = [];
                $user->verification_code     = "";
                $user->permissions           = [];
                $user->createdAt             = Carbon::now()->timestamp;
                $user->accounts              = null;
                $user->remember_token        = null;
                $user->recover               = (object)[];
                $user->commissions           = (object)[];
                $user->drive                 = (object)[];
                $user->marketers             = [];
                $user->docs                  = [];

                $user->save();

                $created[] = [
                    'row'       => $rowIndex,
                    '_id'       => (string)$user->_id,
                    'firstName' => $user->firstName,
                    'lastName'  => $user->lastName,
                    'email'     => $user->email,
                ];

            } catch (\Exception $e) {
                $errors[] = "Fila $rowIndex: Error al guardar usuario ({$e->getMessage()}).";
            }
        }

        // Respuesta
        if (! empty($errors)) {
            \Log::warning('BulkUser errores detectados:', $errors);
            return response()->json([
                'created' => $created,
                'errors'  => $errors,
                'message' => 'Algunos usuarios no se pudieron crear.'
            ], 207);
        }

        return response()->json([
            'created' => $created,
            'message' => 'Todos los usuarios fueron creados correctamente.'
        ], 200);
    }



    public function updateEmail($type, Request $request)
    {

        $email = json_decode($request->input('email'), true);
        $userLogged = json_decode($request->input('userLogged'), true);

        $user = User::where('_id', $userLogged['_id'])->first();

        $welcomeEmail = $user->welcomeEmail;

        $welcomeEmail['subject'] = $email['subject'];
        $welcomeEmail['message'] = $email['message'];

        $user->welcomeEmail = $welcomeEmail;

        $user->save();

        return response()->json(['message' => '¡Email de bienvenida actualizado!'], 200);
    }


    //función testeo
    public static function test()
    {

        //$visualizer
        $visualizer = User::where('firstName', 'Visualizador')->first();

        //saco el usuario principal
        $userMain = User::where('firstName', 'Francisco Javier')->first()->toArray();

        //Saco los usuarios que tienen al usuario principal como responsable
        $users = User::whereIn('responsibles', [$userMain['_id']])->get();

        //Le meto a esos usuarios el id del usuario visualizador tb
        foreach ($users as $userInd => $user) {

            $responsibles = $user->responsibles;

            if (!in_array($visualizer->_id, $responsibles))
                array_push($responsibles, $visualizer->_id);

            $user->responsibles = $responsibles;

            //$user->save();
        }

        dd(vars: $users);
    }

        public function saveLabelModulePermissions(Request $request)
    {
        $user = session()->get('userLogged');

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if ($user->label !== 'Usuario subdominio') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'label'   => 'required|string',
            'modules' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $label        = $request->label;
        $modulesInput = $request->modules;

        $userToUpdate = User::where('_id', $user->_id)->first();

        if (!$userToUpdate) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $ALL_MODULES = [
            'accounts',
            'contracts',
            'documents',
            'liquidations',
            'tools',
            'products',
            'users'
        ];

        $labelsPermissions = $userToUpdate->labels_permissions ?? [];

        $labelsPermissions[$label] = [];

        foreach ($ALL_MODULES as $module) {
            $labelsPermissions[$label][$module] = [];
        }

        foreach ($modulesInput as $module => $permissions) {
            $labelsPermissions[$label][$module] = array_values(
                array_unique($permissions)
            );
        }

        $userToUpdate->labels_permissions = $labelsPermissions;
        $userToUpdate->save();

        return response()->json([
            'message' => 'Permisos guardados correctamente',
            'label'   => $label,
            'modules' => $labelsPermissions[$label]
        ], 200);
    }



    public function haveContractOrOrder(Request $request)
    {
        $userId = $request->input('idToRemove');

        // Obtener el usuario principal
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Obtener los subordinados (aquí asumo que hay una relación 'subordinados' en el modelo)
        $subordinates = User::whereIn('id', $user->subordinates->pluck('id'))->get();

        // Verificar si el usuario o los subordinados tienen cuentas o contratos
        $hasAccounts = $user->accounts()->count() > 0 || $subordinates->some(function ($subordinate) {
            return $subordinate->accounts()->count() > 0;
        });

        $hasContracts = $user->contracts()->count() > 0 || $subordinates->some(function ($subordinate) {
            return $subordinate->contracts()->count() > 0;
        });

        return response()->json([
            'hasAccounts' => $hasAccounts,
            'hasContracts' => $hasContracts,
            'accountsCount' => $user->accounts()->count() + $subordinates->sum(function ($subordinate) {
                return $subordinate->accounts()->count();
            }),
            'contractsCount' => $user->contracts()->count() + $subordinates->sum(function ($subordinate) {
                return $subordinate->contracts()->count();
            }),
        ]);
    }
}
