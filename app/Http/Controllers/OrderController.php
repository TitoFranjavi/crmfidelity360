<?php

namespace App\Http\Controllers;

use App\Helpers\CommissionHelper;
use App\Helpers\UserHelper;
use App\Http\Models\Account;
use App\Http\Models\Enterprise;
use App\Http\Models\Order;
use App\Http\Models\User;
use App\Http\Models\Marketer;
use App\Http\Requests\OrderRequest;
use App\Mail\SendOrderInfo;
use App\Services\AuditLogService;
use App\Services\OrderIndexService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use MongoDB\BSON\ObjectId;
use App\Services\OrderService;


class OrderController extends Controller
{
    public function __construct(OrderIndexService $orderIndexService)
    {
        $this->orderIndexService = $orderIndexService;
    }

    //funcion para guardar un pedido
    public function store(OrderRequest $request, OrderService $orderService)
    {
        $order       = (array) $request->input('order', []);
        $account     = $request->input('account');
        $userSubdomain  = (array) $request->input('userSubdomain', []);

        $controlFieldsValidationResponse = $this->validateOrderPayloadControlFields($order, $account, $userSubdomain);

        if ($controlFieldsValidationResponse) {
            return $controlFieldsValidationResponse;
        }

        if (isset($account) && $userSubdomain !== '65cb57489c2c285441086a43') {

            $accountToSave = Account::where('_id', $account['_id'])->first();
            $usersIds = $accountToSave['usersIds'] ?? [];

            if (!empty($order['assignedTo'])) {

                $containsAssignedTo = in_array($order['assignedTo'], $usersIds);

                // Si el contrato está asignado a alguien que no está en la cuenta, lo añadimos
                if (!$containsAssignedTo) {
                    $usersIds[] = $order['assignedTo'];
                }

        }

            $accountToSave['usersIds'] = $usersIds;
            $accountToSave->save();
        }


        $result = $orderService->saveOne($order, $account, $request);

        $savedOrder = $result['order'] ?? null;
        $emailError = $result['emailError'] ?? false;

        $message = 'El contrato ha sido creado correctamente';
        if ($emailError) {
            $message .= ' (pero no se ha podido enviar el correo al agente)';
        }

        return response()->json([
            'message'    => $message,
            'emailError' => $emailError,
            'order'      => $savedOrder,
        ], 200);
    }


    private function normalizeControlField(?string $value): string
    {
        return strtoupper(preg_replace('/[\s\.\-]/', '', (string) $value));
    }

    private function validateSpanishDocumentIfApplies(?string $value): array
    {
        $doc = $this->normalizeControlField($value);

        if ($doc === '') {
            return [
                'valid' => true,
                'skipped' => true,
                'message' => null,
            ];
        }

        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';

        // DNI / NIF persona física
        if (preg_match('/^\d{8}[A-Z]$/', $doc)) {
            $number = (int) substr($doc, 0, 8);
            $expected = $letters[$number % 23];

            return [
                'valid' => $doc[8] === $expected,
                'skipped' => false,
                'message' => $doc[8] === $expected
                    ? null
                    : 'El NIF tiene formato español, pero la letra de control no es correcta.',
            ];
        }

        // NIE
        if (preg_match('/^[XYZ]\d{7}[A-Z]$/', $doc)) {
            $prefixes = [
                'X' => '0',
                'Y' => '1',
                'Z' => '2',
            ];

            $number = (int) ($prefixes[$doc[0]] . substr($doc, 1, 7));
            $expected = $letters[$number % 23];

            return [
                'valid' => $doc[8] === $expected,
                'skipped' => false,
                'message' => $doc[8] === $expected
                    ? null
                    : 'El NIE tiene formato español, pero la letra de control no es correcta.',
            ];
        }

        // CIF
        if (preg_match('/^[ABCDEFGHJKLMNPQRSUVW]\d{7}[0-9A-J]$/', $doc)) {
            $controlLetters = 'JABCDEFGHI';
            $digits = substr($doc, 1, 7);
            $control = $doc[8];

            $evenSum = 0;
            $oddSum = 0;

            for ($i = 0; $i < strlen($digits); $i++) {
                $digit = (int) $digits[$i];

                if (($i + 1) % 2 === 0) {
                    $evenSum += $digit;
                } else {
                    $double = $digit * 2;
                    $oddSum += intdiv($double, 10) + ($double % 10);
                }
            }

            $total = $evenSum + $oddSum;
            $controlDigit = (10 - ($total % 10)) % 10;
            $controlLetter = $controlLetters[$controlDigit];

            $mustBeLetter = preg_match('/^[KPQS]$/', $doc[0]);
            $mustBeDigit = preg_match('/^[ABEH]$/', $doc[0]);

            if ($mustBeLetter) {
                $isValid = $control === $controlLetter;
            } elseif ($mustBeDigit) {
                $isValid = $control === (string) $controlDigit;
            } else {
                $isValid = $control === (string) $controlDigit || $control === $controlLetter;
            }

            return [
                'valid' => $isValid,
                'skipped' => false,
                'message' => $isValid
                    ? null
                    : 'El CIF tiene formato español, pero el carácter de control no es correcto.',
            ];
        }

        // Pasaporte/documento extranjero
        return [
            'valid' => true,
            'skipped' => true,
            'message' => null,
        ];
    }

    private function validateIban(?string $value): array
    {
        $iban = $this->normalizeControlField($value);

        if ($iban === '') {
            return [
                'valid' => true,
                'message' => null,
            ];
        }

        // Excepción de negocio
        if ($iban === 'ES0000') {
            return [
                'valid' => true,
                'skipped' => true,
                'message' => null,
            ];
        }

        if (!preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]+$/', $iban)) {
            return [
                'valid' => false,
                'message' => 'IBAN no válido.',
            ];
        }

        if (!preg_match('/^ES\d{22}$/', $iban)) {
            return [
                'valid' => false,
                'message' => 'El IBAN debe tener estructura española: ES + 22 dígitos.',
            ];
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $numeric = '';

        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char)
                ? (string) (ord($char) - 55)
                : $char;
        }

        $remainder = 0;

        foreach (str_split($numeric) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return [
            'valid' => $remainder === 1,
            'message' => $remainder === 1
                ? null
                : 'Los dígitos de control del IBAN no son correctos.',
        ];
    }

    private function modStringNumber(string $number, int $mod): int
    {
        $remainder = 0;

        foreach (str_split($number) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % $mod;
        }

        return $remainder;
    }

    private function validateCups(?string $value): array
    {
        $cups = $this->normalizeControlField($value);

        if ($cups === '') {
            return [
                'valid' => true,
                'message' => null,
            ];
        }

        // Excepción de negocio
        if ($cups === 'ES0000') {
            return [
                'valid' => true,
                'skipped' => true,
                'message' => null,
            ];
        }

        if (!preg_match('/^ES\d{16}[A-Z]{2}([A-Z0-9]{2})?$/', $cups)) {
            return [
                'valid' => false,
                'message' => 'CUPS no válido.',
            ];
        }

        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $numericPart = substr($cups, 2, 16);
        $remainder = $this->modStringNumber($numericPart, 529);

        $expected = $letters[intdiv($remainder, 23)] . $letters[$remainder % 23];
        $current = substr($cups, 18, 2);

        return [
            'valid' => $current === $expected,
            'message' => $current === $expected
                ? null
                : 'Las letras de control del CUPS no son correctas.',
        ];
    }

    private function validateOrderPayloadControlFields(array $order, $account, array $userSubdomain = [])
        {
            $ibanValidation = $this->validateIban($order['IBAN'] ?? '');

            if (!$ibanValidation['valid']) {
                return response()->json([
                    'errors' => ['IBAN' => $ibanValidation['message']],
                ], 422);
            }

            $settings = $userSubdomain['settings'] ?? [];

            $shouldValidateCups = !array_key_exists('orderCupsValidation', $settings)
                || $settings['orderCupsValidation'] !== false;

            // ← BORRAR el if (!$cupsValidation['valid']) que había aquí

            $cupsValidation          = ['valid' => true, 'message' => null];
            $cupsSecondaryValidation = ['valid' => true, 'message' => null];

            if ($shouldValidateCups) {
                $cupsValidation = $this->validateCups($order['CUPS'] ?? '');

                if (!isset($cupsValidation['valid']) || !$cupsValidation['valid']) {
                    return response()->json([
                        'errors' => ['CUPS' => $cupsValidation['message']],
                    ], 422);
                }

                if (!empty($order['CUPSSecondary'])) {
                    $cupsSecondaryValidation = $this->validateCups($order['CUPSSecondary']);

                    if (!$cupsSecondaryValidation['valid']) {
                        return response()->json([
                            'errors' => ['CUPSSecondary' => $cupsSecondaryValidation['message']],
                        ], 422);
                    }
                }
            }

            $accountCif = '';

            if (is_array($account)) {
                $accountCif = $account['CIF'] ?? '';
            }

            if ($accountCif === '' && is_string($account) && preg_match('/^[a-f0-9]{24}$/i', $account)) {
                $accountModel = Account::where('_id', $account)->first();
                $accountCif = $accountModel['CIF'] ?? '';
            }

            if ($accountCif === '' && !empty($order['account']) && is_string($order['account']) && preg_match('/^[a-f0-9]{24}$/i', $order['account'])) {
                $accountModel = Account::where('_id', $order['account'])->first();
                $accountCif = $accountModel['CIF'] ?? '';
            }

            if ($accountCif === '') {
                $accountCif = $order['accountCIF'] ?? '';
            }

            $documentValidation = $this->validateSpanishDocumentIfApplies($accountCif);

            if (!$documentValidation['valid']) {
                return response()->json([
                    'errors' => ['accountCIF' => $documentValidation['message']],
                ], 422);
            }

            return null;
        }


    private function normalizeMongoId($id): string
    {
        if ($id instanceof \MongoDB\BSON\ObjectId) {
            return (string) $id;
        }

        if (is_array($id) && isset($id['$oid'])) {
            return (string) $id['$oid'];
        }

        return (string) $id;
    }

    private function normalizeMongoSearchExpression($input): array
    {
        return [
            '$toLower' => [
                '$replaceAll' => [
                    'input' => ['$ifNull' => [$input, '']],
                    'find' => ' ',
                    'replacement' => ''
                ]
            ]
        ];
    }

    private function accountLookupStagesForOrders(): array
    {
        $accountCifNormalized = $this->normalizeMongoSearchExpression('$accountCIF');

        return [
            [
                '$lookup' => [
                    'from' => 'accounts',
                    'let' => ['accountId' => '$account'],
                    'pipeline' => [
                        [
                            '$match' => [
                                '$expr' => [
                                    '$eq' => [
                                        '$_id',
                                        [
                                            '$convert' => [
                                                'input' => '$$accountId',
                                                'to' => 'objectId',
                                                'onError' => null,
                                                'onNull' => null
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            '$project' => [
                                '_id' => 0,
                                'CIF' => 1,
                                'email' => 1
                            ]
                        ],
                        ['$limit' => 1]
                    ],
                    'as' => 'accountInfo'
                ]
            ],
            [
                '$set' => [
                    'accountCIF' => [
                        '$ifNull' => [
                            [
                                '$getField' => [
                                    'field' => 'CIF',
                                    'input' => ['$first' => '$accountInfo']
                                ]
                            ],
                            ['$ifNull' => ['$accountCIF', '']]
                        ]
                    ],
                    'accountEmail' => [
                        '$ifNull' => [
                            [
                                '$getField' => [
                                    'field' => 'email',
                                    'input' => ['$first' => '$accountInfo']
                                ]
                            ],
                            ['$ifNull' => ['$accountEmail', null]]
                        ]
                    ]
                ]
            ],
            [
                '$set' => [
                    'accountCIFNormalized' => $accountCifNormalized,
                    'CIFNormalized' => $accountCifNormalized,
                    'search_cif' => $accountCifNormalized
                ]
            ]
        ];
    }


    public function update($id, OrderRequest $request, OrderService $orderService) {
        // Usuario con sesión
        $userLogged = session()->get('userLogged');
        $userSubdomain  = (array) $request->input('userSubdomain', []);
        $user = null;
        if (!empty($userLogged['_id']))
            $user = User::where('_id', $userLogged['_id'])->first();

        // Datos del cliente (Vue)
        $order   = (array) $request->input('order', []);
        $account = $request->input('account'); // puede venir null / string / array

        $controlFieldsValidationResponse = $this->validateOrderPayloadControlFields($order, $account, $userSubdomain);

        if ($controlFieldsValidationResponse) {
            return $controlFieldsValidationResponse;
        }

        // --- Cargar el contrato actual para comparar (activationDate, etc.)
        $filteredId = preg_match('/^[a-f0-9]{24}$/i', (string) $id) ? new ObjectId($id) : $id;
        $oldOrder = Order::find($id);
        if ($oldOrder) {
            $order['_id'] = (string)$oldOrder->_id;
            $order['activationDate'] = $order['activationDate'] ?? $oldOrder['activationDate'];
        }


        // Guardar contrato (incluye statuses, docs, etc.)
        $result = $orderService->saveOne($order, $account, $request);

        $savedOrder = $result['order'] ?? $order;
        $emailError = $result['emailError'] ?? false;

        $message = 'El contrato ha sido actualizado correctamente';
        if ($emailError) {
            $message .= ' (pero no se ha podido enviar el correo al agente)';
        }

        return response()->json([
            'message'    => $message,
            'emailError' => $emailError,
            'data'       => $savedOrder['docs'] ?? [],
            'order'      => $savedOrder,
        ], 200);
    }

    private function resolveAllowedUserIds($userLogged,$userList,$userSubdomain = null,$subdomainUserList = []): array
    {
        $canViewSubdomain = false;

        if (
            $userLogged &&
            $userSubdomain &&
            !empty($userLogged['label']) &&
            !empty($userSubdomain['labels_permissions'])
        ) {
            $label = $userLogged['label'];
            $labelsPermissions = $userSubdomain['labels_permissions'];


            if (
                isset($labelsPermissions[$label]['users']) &&
                is_array($labelsPermissions[$label]['users']) &&
                in_array('admiWhiHier', $labelsPermissions[$label]['users'])
            ) {

                $canViewSubdomain = true;
            }
        }



        if ($canViewSubdomain && !empty($subdomainUserList)) {


            return array_values(array_map(function ($user) {
                return is_array($user) ? (string) $user['_id'] : (string) $user;
            }, $subdomainUserList));


        }

        return array_values(array_merge(
            [(string) $userLogged['_id']],
            array_map(fn($u) => (string) $u['_id'], $userList)
        ));
    }

    public function index(Request $request){
        $result = $this->orderIndexService->getOrders([
            'perPage'       => $request->get('perPage', 50),
            'currentPage'   => $request->get('page', 1),
            'sortBy'        => $request->get('sortBy'),
            'sortDirection' => $request->get('sortDirection', 'desc'),
            'search'        => $request->get('search', ''),
            'searchOption'  => $request->get('searchOption', 'all'),
            'filters'       => $request->get('filters', []),
        ], Auth::user());

        return response()->json($result);
    }

    public function indexFilters(Request $request){
        $userLogged = Auth::user();
        $userSubdomain = UserHelper::getUserSubdomain($userLogged['_id']);
        $canSeeAllOrders = in_array('admiWhiHier', $userSubdomain['labels_permissions'][$userLogged['label']]['users']);

        $userList  = UserHelper::hierarchy($canSeeAllOrders ? $userSubdomain['_id'] : $userLogged['_id']);
        $usersIds = array_column($userList, '_id');
        $usersIds[] = (string) $userLogged['_id'];

        $match = [
            'usersIds' => ['$in' => $usersIds],
        ];

        $filters = Order::raw(fn($c) => $c->aggregate([
            ['$match' => $match],
            ['$unwind' => '$usersIds'],
            [
                '$group' => [
                    '_id'      => null,
                    'statuses' => ['$addToSet' => '$lastStatus.code'],
                    'productTypes' => ['$addToSet' => '$productType'],
                    'usersIds' => ['$addToSet' => '$usersIds'],
                    'fees' => ['$addToSet' => '$fee'],
                    'marketerProducts' => ['$addToSet' => ['marketer' => '$marketer', 'product' => '$product', 'productSecondary' => '$productSecondary']],
                    'liquidationStatuses' => ['$addToSet' => '$liquidationStatus'],
                ]
            ],
        ], ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']])
        ->toArray())[0] ?? [
            'statuses' => [],
            'productTypes' => [],
            'usersIds' => [],
            'fees' => [],
            'marketerProducts' => [],
            'liquidationStatuses' => [],
        ];

        $usersMap = User::whereIn('_id', $filters['usersIds'])
            ->select('_id', 'firstName', 'lastName')
            ->get()
            ->map(fn($u) => [
                'id'   => (string) $u['_id'],
                'name' => trim("{$u->firstName} {$u->lastName}"),
            ]);

        $statusesMap = collect($userSubdomain['statuses'])
            ->keyBy('code')
            ->map(fn($s) => [
                'code' => $s['code'],
                'name' => $s['title'],
            ])
            ->values();

        $productTypesMap = collect(config('product_types'))
            ->whereIn('code', $filters['productTypes'])
            ->map(fn($p) => [
                'code' => $p['code'],
                'name' => $p['title'],
            ])
            ->values();

        $feesMap = collect($filters['fees'])
            ->map(fn($fee) => [
                'id'   => $fee,
                'name' => $fee === '' ? 'Sin tarifa' : $fee,
            ])
            ->values();

        $marketerProducts = collect($filters['marketerProducts']);

        $marketersMap = $marketerProducts
            ->pluck('marketer')
            ->unique()
            ->map(fn($marketer) => [
                'id'   => $marketer,
                'name' => $marketer === '' ? 'Sin comercializadora' : $marketer,
            ])
            ->values();

        $productsMap = $marketerProducts
            ->flatMap(fn($mp) => array_filter([
                [
                    'id'         => $mp['product'],
                    'name'       => $mp['product'] === '' ? 'Sin producto' : $mp['product'],
                    'marketerId' => $mp['marketer'],
                ],
                !empty($mp['productSecondary']) ? [
                    'id'         => $mp['productSecondary'],
                    'name'       => $mp['productSecondary'],
                    'marketerId' => $mp['marketer'],
                ] : null,
            ]))
            ->unique('id')
            ->values();

        $liquidationStatusesMap = collect(config('liquidation_statuses'))
            ->whereIn('code', $filters['liquidationStatuses'])
            ->map(fn($p) => [
                'code' => $p['code'],
                'name' => $p['title'],
            ])
            ->values();

        return response()->json([
            'agents'   => $usersMap,
            'statuses' => $statusesMap,
            'productTypes' => $productTypesMap,
            'fees' => $feesMap,
            'marketers' => $marketersMap,
            'products' => $productsMap,
            'liquidationStatuses' => $liquidationStatusesMap,
        ]);
    }

    public function getAccountEmails(Request $request){
        $result = $this->orderIndexService->getAccountEmails([
            'search'        => $request->get('search', ''),
            'searchOption'  => $request->get('searchOption', 'all'),
            'filters'       => $request->get('filters', []),
        ], Auth::user());

        return response()->json($result);
    }

    public static function show($id){
        return response()->json(['order' => Order::where('_id', $id)->first()], 200);
    }

    public function saveLiquidation(Request $request, OrderService $orderService)
    {
        $order = $request->input('order');
        $account = $request->input('account');
        $order['lastUpdate'] = now()->format('Y-m-d H:i:s');


        $orderService->saveOne(
            (array) $order,
            $account,
            $request
        );

        return response()->json(['status' => 'ok']);
    }

    //funcion para eliminar un pedido
    public static function delete($id, Request $request){

        $order = Order::where('_id', $id)->first();

        //Creo el log
        AuditLogService::createOrDeleteOrder($order, Auth::user(), 'delete');

        //Borro el contrato
        $order->delete();

        //Quito el check de las comparativas si ya no queda ningún contrato con este CUPS
        \App\Services\OrderService::unmarkComparativesIfNoContract($order->CUPS ?? '', Auth::user());

        return response()->json(['message' => 'El contrato ha sido eliminado correctamente'],200);
    }

    //funcion para enviar el correo de incidencia
    public static function sendIncidenceMail(Request $request) {

        $account = $request['account'];
        $order = $request['order'];
        $enterprise = $request['enterprise'];


        //si la cuenta es el id
        if (is_string($account))
            $account = Account::where('_id', $account)->first();


        $emailData = [
            'accName' => $account['name'],
            'content' => 'Nueva incidencia en su contrato ' . $order['name'] . ' de la cuenta ' . $account['name'],
            'button' => [
                'url' => 'https://crm.asercordenergia.com/accounts/' . $account['_id'] . '?id=' . $order['_id']['$oid'],
                'text' => 'Ir al contrato'
            ]
        ];

        $userEmail = '';

        if (is_string($account['createdBy']))
            $userEmail = User::where('_id', $account['createdBy']['email'])->first()['email'];
        else
            $userEmail = $account['createdBy']['email'];


        //Compruebo si se envía desde el correo de Zoco o desde el subdominio
        if (isset($enterprise['mailConfig']) && !!env("MAIL_USERNAME_" . $enterprise['mailConfig']) && !!env("MAIL_PASSWORD_" . $enterprise['mailConfig'])){

            $mailName = strtoupper($enterprise['mailConfig']);

            Config::set('mail.mailers.smtp.host', !!env('MAIL_HOST_' . $mailName) ? env('MAIL_HOST_' . $mailName) : env('MAIL_HOST'));
            Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME_' . $mailName));
            Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD_' . $mailName));
            Config::set('mail.from.address', env('MAIL_FROM_ADDRESS_' . $mailName));
            Config::set('mail.from.name', env('MAIL_FROM_NAME_' . $mailName));

        }

        Mail::to($userEmail)->send(new SendOrderInfo($emailData));

        return response()->json(['message' => 'El correo ha sido mandado'], 200);
    }

    //funcion para eliminar varios pedidos
    public static function deleteAllSelectedOrders(Request $request){

        $ordersToRemove = $request['ordersToRemove'];

        foreach ($ordersToRemove as $orderInd => $order){

            //Saco la cuenta
            $account = Account::where('_id', $order['account'])->first();

            $accountOrders = $account['orders'];

            $accountOrders = array_filter($accountOrders, function ($acc) use ($order) {
                //return (string) $acc['_id'] !== $order['_id']['$oid'];
                return $acc['_id']['$oid'] !== $order['_id']['$oid'];
            });

            $account['orders'] = $accountOrders;

            $account->save();
        }

        return response()->json(['message' => 'Los pedidos han sido eliminados correctamente'],200);
    }

    public function uploadDocument(Request $request, $id)
{
    $order = Order::find($id);

    if (!$order) {
        return response()->json(['error' => 'Contrato no encontrado'], 404);
    }

    $existingDocs = $order->docs ?? [];

    $existingDocs = array_filter($existingDocs, function ($doc) {
        return !empty($doc['value']);
    });

    $uploadedDocs = [];

    if ($request->hasFile('files')) {

        $files = $request->file('files');
        $titles = $request->input('titles', []);

        // Datos para reflejar la subida en el histórico del contrato
        $userLogged = Auth::user();
        $creatorId  = $userLogged ? (string) $userLogged->_id : null;
        $uploadDate = Carbon::now()->format('Y-m-d H:i:s');

        foreach ($files as $index => $file) {

            $fileName = time().'_'.$file->getClientOriginalName();

            Storage::disk('order')->put($fileName, file_get_contents($file));

            $doc = [
                'title'        => $titles[$index] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'defaultTitle' => $file->getClientOriginalName(),
                'value'        => $fileName,
                'fileValue'    => [],
                'icon'         => $this->resolveIcon($file->getClientOriginalExtension()),
                'id'           => (string) \Str::uuid(),
                'errors'       => [],
                'createdAt'    => now(),
                'creator'      => $creatorId,
                'date'         => $uploadDate,
            ];

            $uploadedDocs[] = $doc;
        }
    }

    $order->docs = array_values(array_merge($existingDocs, $uploadedDocs));
    $order->save();

    return response()->json([
        'message' => 'Documentos sincronizados correctamente',
        'docs' => $order->docs
    ], 200);
}

    private function resolveIcon($extension)
{
    return match(strtolower($extension)) {
        'pdf'  => 'far fa-file-pdf',
        'doc', 'docx' => 'far fa-file-word',
        'xls', 'xlsx' => 'far fa-file-excel',
        'png', 'jpg', 'jpeg' => 'far fa-file-image',
        default => 'far fa-file'
    };
}

    //funcion para obtener el consumo automáticamente mediante un CUPS de una API
    public static function getAPIConsumption(Request $request) {

        $CUPS = $request['CUPS'];

        $boundaryPoint = [
            ["boundary" => "0F", "checked" => false],
            ["boundary" => "1P", "checked" => false],
            ["boundary" => "", "checked" => false],
        ];

        $data = [];

        while (array_filter($boundaryPoint, fn($boundary) => !$boundary['checked'])) {
            $boundary = null;

            foreach ($boundaryPoint as $index => $point) {
                if (!$point['checked']) {
                    $boundaryPoint[$index]['checked'] = true;
                    $boundary = $CUPS . $boundaryPoint[$index]['boundary'];
                    break;
                }
            }

            //  Hago la petición a la API
            $response = Http::withHeaders([
                'x-api-key' => '$2y$10$bcT1Ukm4V/6/z5GvitZv0unu8I91mpixuwUO6z5CyEGev9DDDY28W'
            ])->get('https://datapi.psgestion.es/cups/data/consumos', [
                'id' => 4,
                'valores' => json_encode([
                    'cups' => $boundary,
                    'tipoContrato' => 'L'
                ])
            ])->body();

            //  Convierto los datos de JSON a Array
            $data = json_decode($response, true);

            //Acabo el bucle si recibo datos
            if (count($data['consumos']) > 0)
                break;
        }


        //Si no hay datos en $data, llamo al SIPSController (Baleares)
        if (empty($data['consumos']) || count($data['consumos']) === 0) {
            $newRequest = new Request(['CUPS' => $CUPS]);
            $data = SipsController::getElectricityConsumption($newRequest);
        }

        $consumption_beta = [];
        if (count($data['consumos']) < 1) {
            return response()->json([
                'content' => [
                    'message' => '¡No hay datos suficientes para estudio!'
                ]
            ], 404);
        }

        //  Recojo la información y la agrupo en lecturas
        foreach ($data['consumos'] as $index => $close) {
            $dateTo = Carbon::parse($close['fechaFinMesConsumo']);
            $dateFrom = Carbon::parse($close['fechaInicioMesConsumo']);
            $consumption_beta[$index]["fechaFin"] = $dateTo->format("d/m/Y");
            $consumption_beta[$index]["fechaInicio"] = $dateFrom->format("d/m/Y");
            for ($i = 1; $i <= 6; $i++)
                $consumption_beta[$index]["periods"][$i - 1] = ($consumption_beta[$index]["periods"][$i - 1] ?? 0) + $close[sprintf('consumoEnergiaActivaEnWhP%s', $i)] / 1000;
        }


        //  -> Me quedo con las facturas de los últimos 12 meses (sin pasarse de un año atrás)
        $minDate = Carbon::parse($data["consumos"][0]["fechaFinMesConsumo"])->subYear();
        $consumption = [];
        foreach($consumption_beta as $consumptionRead){
            $consumptionDate = Carbon::createFromFormat("d/m/Y",$consumptionRead["fechaFin"]);
            if($consumptionDate->gt($minDate)){
                $consumption[] = $consumptionRead;
            };
        }

        //  -> Agrupo los consumos por periodos
        foreach ($consumption as $index => $read){
            //Obtengo los días entre esta lectura
            $interval = Carbon::createFromFormat("d/m/Y",$read["fechaFin"])
                ->diffInDays(Carbon::createFromFormat("d/m/Y",$read["fechaInicio"]));

            //Obtengo los días entre esta lectura y la fecha mínima
            $difference = Carbon::createFromFormat("d/m/Y", $read["fechaFin"])->diffInDays($minDate);

            foreach ($read['periods'] as $period => $value){
                if ($difference > $interval){
                    $return['consumption'][$period] = ($return['consumption'][$period] ?? 0) + $value;
                    //Si la diferencia es menor que el intervalo, calcular la media de consumo diario
                }else{
                    $return['consumption'][$period] = ($return['consumption'][$period] ?? 0) + $value * $difference / $interval;
                }
            }
        }

        //  Obtengo las potencias contratadas
        for ($i = 1; $i <= 6; $i++)
            $return['hiredPotency'][$i - 1] = $data[sprintf('potenciasContratadasEnWP%s', $i)] / 1000;


        //Le hago el 95% al consumo
        /*foreach ($return['consumption'] as &$consumptionRead){
            $consumptionRead = $consumptionRead * 0.95;
        }*/

        return response()->json(['consumptionData' => $return, 'fee' => $data['tarifaATR']], 200);
    }

    public function dumpOrders(Request $request, OrderService $orderService)
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');
        @ini_set('default_socket_timeout', '600');
        ignore_user_abort(true);

        $file = $request->file('file');
        $userSubdomain = json_decode($request->input('userSubdomain'), true);
        $subdomainSettings = $userSubdomain['settings'] ?? [];
        $userLogged = Auth::user();

        $userList = json_decode($request->input('userList'), true) ?? [];
        $userListIds = array_values(array_filter(array_column($userList, '_id')));
        $userListIds[] = $userLogged['_id'] ?? null;
        $userListIds = array_values(array_filter($userListIds));

        if (!$file || !$file->isValid()) {
            return response()->json(['error' => 'No se ha recibido un archivo válido.'], 400);
        }

        $report = [
            'summary' => [
                'fileName'            => $file->getClientOriginalName(),
                'totalRowsExcel'      => 0,
                'processedRows'       => 0,
                'inserted'            => 0,
                'appendedCommission'  => 0,
                'failed'              => 0,
            ],
            'failedRows' => [],
        ];

        try {
            $excel = Excel::toArray([], $file);

            if (empty($excel[0]) || !is_array($excel[0]) || count($excel[0]) === 0)
                return response()->json(['error' => 'No se pudieron obtener datos válidos del archivo Excel.'], 400);


            if (($excel[0][0][0] ?? null) !== "ZocoContratos")
                return response()->json(['error' => 'Por favor usa la plantilla de contratos.'], 400);


            // Saltamos 2 filas de cabecera
            $excelDataRaw = array_slice($excel[0], 2, null, true);

            $excelItems = [];
            foreach ($excelDataRaw as $k => $row) {
                $limitedRow = array_slice($row, 0, 53); // columnas A hasta BA (53 columnas)
                $isEmpty = array_filter($limitedRow, fn($value) => trim((string)$value) !== '') === [];
                if ($isEmpty) continue;

                $excelItems[] = [
                    'rowNumber' => $k + 1,
                    'row'       => $row,
                ];
            }

            $report['summary']['totalRowsExcel'] = count($excelItems);

            // Mapeo de columnas (según tu código)
            $fieldMap = [
                0  => 'CIF',
                1  => 'name',
                2  => 'direc',
                3  => 'town',
                4  => 'province',
                5  => 'zip',
                6  => 'IBAN',
                7  => 'productType',
                8  => 'marketer',
                9  => 'fee',
                10 => 'product',
                11 => 'CUPS',
                18 => 'activationDate',
            ];

            $reasonMap = [
                'campo_obligatorio_faltante'      => 'Falta campo obligatorio',
                'estado_invalido'                 => 'Estado inválido',
                'fecha_activacion_invalida'       => 'Fecha activación inválida',
                'fecha_baja_invalida'             => 'Fecha baja inválida',
                'cuenta_no_encontrada'            => 'Cuenta no encontrada/accesible',
                'comercializadora_no_encontrada'  => 'Comercializadora no encontrada',
                'producto_no_encontrado'          => 'Producto no encontrado',
                'tarifa_no_encontrada'            => 'Tarifa no encontrada',
                'tarifa_no_asociado_a_producto'   => 'Tarifa no asociada a producto',
                'tipo_producto_invalido'          => 'Tipo de producto inválido',
                'cups_invalido'                   => 'CUPS inválido',
            ];

            $failCells = [];
            $actions = [];
            $hasValidationErrors = false;

            $colLetter = function ($colIndex) {
                $colIndex = (int)$colIndex;
                $letters = '';
                while ($colIndex >= 0) {
                    $letters = chr($colIndex % 26 + 65) . $letters;
                    $colIndex = intdiv($colIndex, 26) - 1;
                }
                return $letters;
            };

            $normalizeMongoId = function ($id) {
                if ($id === null) return '';
                if (is_array($id)) {
                    if (isset($id['$oid'])) return (string)$id['$oid'];
                    if (isset($id['oid']))  return (string)$id['oid'];
                    if (isset($id['_id']))  return (string)$id['_id'];
                    return (string)reset($id);
                }
                if (is_object($id)) {
                    if (isset($id->{'$oid'})) return (string)$id->{'$oid'};
                    if (isset($id->oid))      return (string)$id->oid;
                    if (isset($id->_id))      return (string)$id->_id;
                }
                return (string)$id;
            };

            $normalizeText = function ($v) {
                return mb_strtolower(trim((string)$v), 'UTF-8');
            };

            $isValidCups = function ($cups) {
                $cups = strtoupper(trim((string)$cups));
                // CUPS típico ES + 18-20 alfanum => total 20-22 chars
                return (bool)preg_match('/^ES[0-9A-Z]{18,20}$/', $cups);
            };

            // ✅ PERFORMANCE: precargamos marketers una vez (si hace falta)
            $marketersAll = Marketer::where('createdBy', $userSubdomain['_id'])->get()->toArray();

            foreach ($excelItems as $item) {
                $row = $item['row'];
                $rowNumber = $item['rowNumber'];
                $report['summary']['processedRows']++;

                // Si es borrador -> saltar fila completa (sin errores)
                if (($row[17] ?? null) === 'Borrador')
                    continue;


                // ---- Normalizar tipo producto ----
                $productTypeRaw  = $row[7] ?? '';
                $productTypeNorm = $normalizeText($productTypeRaw);

                $isLight = in_array($productTypeNorm, ['contrato de luz', 'luz', 'electricidad', 'contrato luz'], true);
                $isGas   = in_array($productTypeNorm, ['contrato de gas', 'gas', 'contrato gas'], true);
                $isDual   = in_array($productTypeNorm, ['contrato dual', 'dual', 'contrato dual'], true);
                $isTelephony   = in_array($productTypeNorm, ['servicio de telefonia', 'contrato de telefonia', 'contrato de telefonía', 'telefonia', 'contrato telefonia'], true);
                $isAlarm   = in_array($productTypeNorm, ['servicio de alarmas', 'contrato de alarmas', 'alarmas', 'contrato alarmas'], true);
                $isSelfSupply   = in_array($productTypeNorm, ['contrato de autoconsumo', 'autoconsumo', 'contrato autoconsumo'], true);

                // Si quieres permitir más tipos, añade aquí.
                $knownTypes = [
                    'contrato de luz','luz','electricidad','contrato luz',
                    'contrato de gas','gas','contrato gas',
                    'contrato dual', 'dual', 'contrato dual',
                    'servicio de telefonia', 'contrato de telefonia',  'contrato de telefonía', 'telefonia', 'contrato telefonia',
                    'servicio de alarmas', 'contrato de alarmas', 'alarmas', 'contrato alarmas',
                    'contrato de autoconsumo', 'autoconsumo', 'contrato autoconsumo',
                    'batería de condensadores','bateria de condensadores',
                    'coche eléctrico','coche electrico',
                    'contador',
                    'iluminación','iluminacion',
                ];

                if ($productTypeNorm === '' || !in_array($productTypeNorm, $knownTypes, true)) {
                    $report['summary']['failed']++;
                    $report['failedRows'][] = [
                        'rowNumber' => $rowNumber,
                        'reason'    => 'tipo_producto_invalido',
                        'details'   => "Tipo de producto inválido: '{$productTypeRaw}'",
                    ];
                    $failCells[$rowNumber][] = ['col' => 7, 'msg' => "Tipo de producto inválido"];
                    $hasValidationErrors = true;
                    continue;
                }

                // ---- Validación de campos obligatorios base (siempre) ----
                // OJO: tu bucle original empezaba en 1, y no validaba 'productType' (7).
                $requiredCols = [1, 7]; // name, productType, CIF
                // Si CIF no es obligatorio siempre, quita el 0 y deja solo [1,7].

                // address/town/province/zip según settings
                if (!empty($subdomainSettings['orderAddress']))  $requiredCols[] = 2;
                if (!empty($subdomainSettings['orderTown']))     $requiredCols[] = 3;
                if (!empty($subdomainSettings['orderProvince'])) $requiredCols[] = 4;
                if (!empty($subdomainSettings['orderPostal']))   $requiredCols[] = 5;

                // IBAN obligatorio si setting y si NO está anulado/scoring (según tu lógica)
                if (!empty($subdomainSettings['orderIBAN']) && ($isLight || $isGas || $isDual)) {
                    $status15 = (string)($row[15] ?? '');
                    if ($status15 !== 'Scoring' && $status15 !== 'Anulado') {
                        $requiredCols[] = 6;
                    }
                }

                // Para luz/gas/telefonía/placas: marketer, fee, product, CUPS obligatorios
                if ($isLight || $isGas || $isTelephony || $isSelfSupply) {
                    $requiredCols[] = 8;
                    $requiredCols[] = 9;
                    $requiredCols[] = 10;
                    if ($isLight || $isGas)$requiredCols[] = 11;
                }

                //Para dual: marketer, fee, product, CUPS, fee Secondary, productSecondary, CUPSSecondary obligatorios
                if ($isDual) {
                    $requiredCols[] = 8;
                    $requiredCols[] = 9;
                    $requiredCols[] = 10;
                    $requiredCols[] = 11;
                    $requiredCols[] = 40;
                    $requiredCols[] = 41;
                    $requiredCols[] = 42;
                }

                //Para alarmas: marketer, product
                if ($isSelfSupply) {
                    $requiredCols[] = 8;
                    $requiredCols[] = 10;
                }

                $requiredCols = array_values(array_unique($requiredCols));

                foreach ($requiredCols as $col) {
                    if (!isset($row[$col]) || $row[$col] === '' || $row[$col] === null) {
                        $missing = $fieldMap[$col] ?? "col_$col";
                        $report['summary']['failed']++;
                        $report['failedRows'][] = [
                            'rowNumber' => $rowNumber,
                            'reason'    => 'campo_obligatorio_faltante',
                            'details'   => "Falta el campo obligatorio: {$missing}",
                        ];
                        $failCells[$rowNumber][] = ['col' => $col, 'msg' => "Falta {$missing}"];
                        $hasValidationErrors = true;
                        continue 2; // ✅ saltar fila completa
                    }
                }

                // Validar CUPS para luz/gas
                if (($isLight || $isGas) && !$isValidCups($row[11] ?? '')) {
                    $report['summary']['failed']++;
                    $report['failedRows'][] = [
                        'rowNumber' => $rowNumber,
                        'reason'    => 'cups_invalido',
                        'details'   => "CUPS inválido: '{$row[11]}'",
                    ];
                    $failCells[$rowNumber][] = ['col' => 11, 'msg' => "CUPS inválido"];
                    $hasValidationErrors = true;
                    continue;
                }

                // ---- Validación marketer/product/fee (solo luz/gas) ----
                $existingMarketer = null;
                $existingProduct  = null;
                $marketerInFee    = null;

                if ($isLight || $isGas || $isDual || $isTelephony || $isAlarm || $isSelfSupply) {
                    $existingMarketer = collect($marketersAll)->first(function ($m) use ($row, $normalizeText) {
                        return isset($m['name']) && $normalizeText($m['name']) === $normalizeText($row[8] ?? '');
                    });

                    if (!$existingMarketer) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = [
                            'rowNumber' => $rowNumber,
                            'reason'    => 'comercializadora_no_encontrada',
                            'details'   => "No se encontró una comercializadora con el nombre '{$row[8]}' en el subdominio.",
                        ];
                        $failCells[$rowNumber][] = ['col' => 8, 'msg' => "Comercializadora no encontrada"];
                        $hasValidationErrors = true;
                        continue;
                    }

                    $categoryMap = [
                        'electricity' => $isLight,
                        'gas'         => $isGas,
                        'dual'         => $isDual,
                        'telephony'   => $isTelephony,
                        'alarm' => $isAlarm,
                        'selfSupply' => $isSelfSupply,
                    ];
                    $category = array_key_first(
                        array_filter($categoryMap)
                    );

                    //Si es dual
                    if ($isDual) {

                        //Producto
                        $existingProduct = collect($existingMarketer['products'][$category] ?? [])
                            ->first(function ($p) use ($row, $normalizeText) {
                                return (isset($p['electricity']) && $normalizeText($p['electricity']) === $normalizeText($row[10] ?? '')) &&
                                    (isset($p['gas']) && $normalizeText($p['gas']) === $normalizeText($row[41] ?? ''));
                            });

                        if (!$existingProduct) {
                            $report['summary']['failed']++;
                            $report['failedRows'][] = [
                                'rowNumber' => $rowNumber,
                                'reason'    => 'producto_no_encontrado',
                                'details'   => "No se encontró un producto dual con el nombre de electricidad'{$row[10]}' y nombre de gas '{$row[41]}' en el subdominio.",
                            ];
                            $failCells[$rowNumber][] = ['col' => 10, 'msg' => "Producto no encontrado"];
                            $failCells[$rowNumber][] = ['col' => 41, 'msg' => "Producto no encontrado"];
                            $hasValidationErrors = true;
                            continue;
                        }


                        //Fees
                        $existingFee = collect($existingProduct['fees'] ?? [])
                            ->first(function ($f) use ($row, $normalizeText) {
                                return ((isset($f['electricity']['name']) && $normalizeText($f['electricity']['name']) === $normalizeText($row[9] ?? '')) &&
                                    (isset($f['gas']['name']) && $normalizeText($f['gas']['name']) === $normalizeText($row[40] ?? '')));
                            });

                        if (!$existingFee) {
                            $report['summary']['failed']++;
                            $report['failedRows'][] = [
                                'rowNumber' => $rowNumber,
                                'reason'    => 'tarifa_no_encontrada',
                                'details'   => "No se encontró una tarifa de electricidad con el nombre '{$row[9]}' o una tarifa de gas con el nombre '{$row[40]}' en la comercializadora.",
                            ];
                            $failCells[$rowNumber][] = ['col' => 9, 'msg' => "Tarifa no encontrada"];
                            $failCells[$rowNumber][] = ['col' => 40, 'msg' => "Tarifa no encontrada"];
                            $hasValidationErrors = true;
                            continue;
                        }


                        // Canonical names
                        $canonicalMarketer= $existingMarketer['name'] ?? ($row[8] ?? '');
                        $canonicalProduct = $existingProduct['electricity']  ?? ($row[10] ?? '');
                        $canonicalProductSecondary = $existingProduct['gas']  ?? ($row[41] ?? '');
                        $canonicalFee = $existingFee['electricity']['name']    ?? ($row[9] ?? '');
                        $canonicalFeeSecondary = $existingFee['gas']['name']    ?? ($row[40] ?? '');
                    }
                    else{

                        $existingProduct = collect($existingMarketer['products'][$category] ?? [])
                            ->first(fn($p) => isset($p['name']) && $normalizeText($p['name']) === $normalizeText($row[10] ?? ''));

                        if (!$existingProduct) {
                            $report['summary']['failed']++;
                            $report['failedRows'][] = [
                                'rowNumber' => $rowNumber,
                                'reason'    => 'producto_no_encontrado',
                                'details'   => "No se encontró un producto con el nombre '{$row[10]}' en el subdominio.",
                            ];
                            $failCells[$rowNumber][] = ['col' => 10, 'msg' => "Producto no encontrado"];
                            $hasValidationErrors = true;
                            continue;
                        }

                        if ($isLight || $isGas) {

                            $marketerInFee = collect($existingMarketer['fees'][$category] ?? [])
                                ->first(fn($p) => isset($p['name']) && $normalizeText($p['name']) === $normalizeText($row[9] ?? ''));

                            if (!$marketerInFee) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = [
                                    'rowNumber' => $rowNumber,
                                    'reason'    => 'tarifa_no_encontrada',
                                    'details'   => "No se encontró una tarifa con el nombre '{$row[9]}' en la comercializadora.",
                                ];
                                $failCells[$rowNumber][] = ['col' => 9, 'msg' => "Tarifa no encontrada"];
                                $hasValidationErrors = true;
                                continue;
                            }

                            $existsFeeInProduct = collect($existingProduct['fees'] ?? [])
                                ->first(function ($p) use ($marketerInFee, $normalizeMongoId) {
                                    return isset($p['id']) &&
                                        strtolower(trim($normalizeMongoId($p['id']))) === strtolower(trim($normalizeMongoId($marketerInFee['id'])));
                                });

                            if (!$existsFeeInProduct) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = [
                                    'rowNumber' => $rowNumber,
                                    'reason'    => 'tarifa_no_asociado_a_producto',
                                    'details'   => "La tarifa '{$row[9]}' no está asociada al producto.",
                                ];
                                $failCells[$rowNumber][] = ['col' => 9, 'msg' => "Tarifa no asociada a producto"];
                                $hasValidationErrors = true;
                                continue;
                            }

                            // Canonical names
                            $canonicalMarketer = $existingMarketer['name'] ?? ($row[8] ?? '');
                            $canonicalProduct  = $existingProduct['name']  ?? ($row[10] ?? '');
                            $canonicalFee      = $marketerInFee['name']    ?? ($row[9] ?? '');

                        }else if (!$isAlarm){

                            $existingFee = collect($existingProduct['fees'] ?? [])
                                ->first(function ($f) use ($row, $normalizeText) {
                                    return (isset($f['name']) && $normalizeText($f['name']) === $normalizeText($row[9] ?? ''));
                                });

                            if (!$existingFee) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = [
                                    'rowNumber' => $rowNumber,
                                    'reason'    => 'tarifa_no_asociado_a_producto',
                                    'details'   => "La tarifa '{$row[9]}' no está asociada al producto.",
                                ];
                                $failCells[$rowNumber][] = ['col' => 9, 'msg' => "Tarifa no asociada a producto"];
                                $hasValidationErrors = true;
                                continue;
                            }

                            $canonicalFee = $marketerInFee['name']    ?? ($row[9] ?? '');
                        }

                        // Canonical names
                        $canonicalMarketer = $existingMarketer['name'] ?? ($row[8] ?? '');
                        $canonicalProduct = $existingProduct['name']  ?? ($row[10] ?? '');
                    }
                }

                $transferDate = null;

                if ($row[35]) {
                    if (is_numeric($row[35])) {
                        $transferDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[35]));
                    } elseif ($row[35] instanceof \DateTime) {
                        $transferDate = Carbon::instance($row[35]);
                    } else {
                        $transferDate = Carbon::createFromFormat('d/m/Y', trim($row[35]));
                    }
                }

                $transferDate = $transferDate ?? Carbon::now();
                $createdAtDate = $transferDate->format('Y-m-d H:i:s');
                $transferDate = $transferDate->format('d/m/y');

                // ---- Construcción order ----
                $order = [
                    "name"               => $row[1] ?? '',
                    "direc"              => $row[2] ?? '',
                    "zip"                => (string)($row[5] ?? ''),
                    "town"               => $row[3] ?? '',
                    "province"           => $row[4] ?? '',
                    "source"             => "",
                    "processingDate"     => $this->safeExcelDate($row[34] ?? null),
                    "activationDate"     => "",
                    "liquidationStatus"  => "nl",
                    "marketer"           => $canonicalMarketer,
                    "fee"                => !$isAlarm ? $canonicalFee : '',
                    "product"            => $canonicalProduct,
                    "commissions"        => [ "subdomain" => null, "breakdown" => []],
                    "CUPS"               => $row[11] ?? "",
                    "consumption"        => $row[12] ?? "",
                    "hiredPotency"       => $row[13] ?? "",
                    "IBAN"               => $row[6] ?? "",
                    "observations"       => $row[20] ?? "",
                    "docs"               => [],
                    "statuses"           => [["code" => "p", "date" => date('Y-m-d H:i:s'), "creator" => $userLogged['_id'] ?? null]],
                    "newStatus"          => [],
                    "errors"             => (object) [],
                    "transferDate"       => $transferDate,
                    "lastUpdate"         => Carbon::now()->format('Y-m-d H:i:s'),
                    "createdAt"          => $createdAtDate
                ];

                //Si es dual meto lo secundario
                if ($isDual) {
                    $order['productSecondary'] = $canonicalProductSecondary;
                    $order['feeSecondary'] = $canonicalFeeSecondary;
                    $order['CUPSSecondary'] = $row[42] ?? "";
                }

                // productType final fiable (según normalizado)
                if ($isLight) $order["productType"] = "cl";
                elseif ($isGas) $order["productType"] = "cg";
                elseif ($isDual) $order["productType"] = "cd";
                elseif ($isTelephony) $order["productType"] = "ct";
                elseif ($isAlarm) $order["productType"] = "sa";
                elseif ($isSelfSupply) $order["productType"] = "a";
                elseif (in_array($productTypeNorm, ['batería de condensadores','bateria de condensadores'], true)) $order["productType"] = "bc";
                elseif (in_array($productTypeNorm, ['coche eléctrico','coche electrico'], true)) $order["productType"] = "ce";
                elseif ($productTypeNorm === 'contador') $order["productType"] = "c";
                elseif (in_array($productTypeNorm, ['iluminación','iluminacion'], true)) $order["productType"] = "i";

                // Estado
                if (isset($row[17]) && $row[17] !== '') {
                    $statuses = array_map(fn($s) => (object)$s, $userSubdomain['statuses'] ?? []);
                    $statusMap = [];
                    foreach ($statuses as $s) { $statusMap[strtolower($s->title)] = $s->code; }

                    if (!isset($statusMap[strtolower($row[17])])) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = [
                            'rowNumber' => $rowNumber,
                            'reason'    => 'estado_invalido',
                            'details'   => "Estado '{$row[17]}' no existe en el mapa.",
                        ];
                        $failCells[$rowNumber][] = ['col' => 17, 'msg' => "Estado inválido"];
                        $hasValidationErrors = true;
                        continue;
                    }

                    $order["statuses"] = [[
                        'code'    => ($statusMap[strtolower($row[17])]),
                        'date'    => date('Y-m-d H:i:s'),
                        'creator' => $userLogged['_id'] ?? null
                    ]];

                    if (isset($row[18]) && $row[18] !== '') {
                        $activationDate = $this->safeExcelDate($row[18]);

                        if ($activationDate === null) {
                            $report['summary']['failed']++;
                            $report['failedRows'][] = [
                                'rowNumber' => $rowNumber,
                                'reason'    => 'fecha_activacion_invalida',
                                'details'   => 'No se pudo convertir la fecha de activación.',
                            ];
                            $failCells[$rowNumber][] = ['col' => 18, 'msg' => "Fecha activación inválida"];
                            $hasValidationErrors = true;
                            continue;
                        }
                        $order['activationDate'] = $activationDate;
                    }

                    if (isset($row[19]) && $row[19] !== '') {
                        $lowDate = $this->changeExcelDate($row[19]);

                        if ($lowDate === null) {
                            $report['summary']['failed']++;
                            $report['failedRows'][] = [
                                'rowNumber' => $rowNumber,
                                'reason'    => 'fecha_baja_invalida',
                                'details'   => 'No se pudo convertir la fecha de baja.',
                            ];
                            $failCells[$rowNumber][] = ['col' => 18, 'msg' => "Fecha baja inválida"];
                            $hasValidationErrors = true;
                            continue;
                        }
                        $order['lowDate'] = $lowDate;
                    }
                }

                // Verificaciones (se deja igual)
                $verifications = [];

                if (mb_strtolower($row[21] ?? '', "UTF-8") === "si") {
                    if (isset($row[9]) && (
                            (
                                $row[9] === 'Tarifa 2.0TD' && is_numeric($row[26] ?? null) && is_numeric($row[27] ?? null)
                            ) || (
                                ($row[9] === 'Tarifa 3.0TD' || $row[9] === 'Tarifa 6.1TD') &&
                                is_numeric($row[26] ?? null) && is_numeric($row[27] ?? null) &&
                                is_numeric($row[28] ?? null) && is_numeric($row[29] ?? null) &&
                                is_numeric($row[30] ?? null) && is_numeric($row[31] ?? null) &&
                                is_numeric($row[32] ?? null)
                            )
                        )) {
                        $verifications[] = "nw";
                        if (($order["productType"] ?? null) === "cl") {
                            $order["newRegistrationPeriods"] = [
                                "p1" => $row[26] ?? null, "p2" => $row[27] ?? null, "p3" => $row[28] ?? null,
                                "p4" => $row[29] ?? null, "p5" => $row[30] ?? null, "p6" => $row[31] ?? null
                            ];
                        }
                    }
                }

                if (mb_strtolower($row[22] ?? '', "UTF-8") === "si" && isset($row[23], $row[24])) {
                    $verifications[] = "pc";
                    $order["currentPotencyVerification"]   = $row[32] ?? null;
                    $order["requestedPotencyVerification"] = $row[33] ?? null;
                }
                if (mb_strtolower($row[23] ?? '', "UTF-8") === "si") $verifications[] = "tc";
                if (mb_strtolower($row[24] ?? '', "UTF-8") === "si") $verifications[] = "vb";
                if (mb_strtolower($row[25] ?? '', "UTF-8") === "si") $verifications[] = "mc";
                if (!empty($verifications)) $order["verifications"] = $verifications;



                //Normalizo emails indicados en excel
                $emailsFromRow = collect(explode(',', (string)($row[36] ?? '')))
                    ->map(fn($e) => trim($e))
                    ->filter()
                    ->map(fn($e) => mb_strtolower($e, 'UTF-8'))
                    ->unique()
                    ->values();

                $loggedId = (string)($userLogged['_id'] ?? '');


                //Busco la cuenta
                $account = null;

                if (!empty($row[0])) {
                    $cif = strtoupper(trim((string)$row[0]));

                    // Usuarios candidatos según emails o usuario logueado
                    $userIdsFromEmails = [];
                    if ($emailsFromRow->isNotEmpty()) {
                        $userIdsFromEmails = collect($userList)
                            ->filter(fn($u) =>
                                !empty($u['email']) &&
                                in_array(strtolower($u['email']), $emailsFromRow->all(), true)
                            )
                            ->pluck('_id')
                            ->map(fn($id) => (string)$id)
                            ->all();
                    }

                    $candidateIds = !empty($userIdsFromEmails)
                        ? $userIdsFromEmails
                        : $userListIds;

                    $account = Account::where('CIF', 'regex', "/^{$cif}$/i")
                        ->where(function ($q) use ($candidateIds) {
                            foreach ($candidateIds as $uid) {
                                $q->orWhere('usersIds', $uid);
                            }
                        })
                        ->first();

                    // No existe la cuenta
                    if (!$account) {
                        $report['summary']['failed']++;
                        $report['failedRows'][] = [
                            'rowNumber' => $rowNumber,
                            'reason'    => 'cuenta_no_encontrada',
                            'details'   => "No se encontró Account con CIF '{$cif}' accesible para los usuarios indicados.",
                        ];
                        $failCells[$rowNumber][] = ['col' => 0, 'msg' => 'Cuenta no encontrada'];
                        $hasValidationErrors = true;
                        continue;
                    }

                    $order['account'] = (string)data_get($account, '_id');
                }

                //USERSIDS
                    //Existe la cuenta
                    if ($account) {

                        $accountUserIds = collect($account['usersIds'] ?? [])
                            ->map(fn($id) => (string)$id)
                            ->values();

                        // A1: Account + NO emails → usersIds de la account
                        if ($emailsFromRow->isEmpty()) {
                            $order['usersIds'] = $accountUserIds->all();
                        }

                        // A2: Account + emails → intersección
                        else {
                            $accountEmails = User::whereIn('_id', $accountUserIds->all())
                                ->pluck('email')
                                ->map('strtolower');

                            $validEmails = $emailsFromRow->intersect($accountEmails)->values();

                            if ($validEmails->isEmpty()) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = [
                                    'rowNumber' => $rowNumber,
                                    'reason'    => 'emails_sin_permiso',
                                    'details'   => 'Los emails indicados no pertenecen a la cuenta.',
                                ];
                                $failCells[$rowNumber][] = ['col' => 36, 'msg' => 'Emails sin permiso en la cuenta'];
                                $hasValidationErrors = true;
                                continue;
                            }

                            $order['usersIds'] = User::whereIn('email', $validEmails->all())
                                ->pluck('_id')
                                ->map(fn($id) => (string)$id)
                                ->all();
                        }
                    }
                    else { //Si no existe la cuenta

                        // B1: NO CIF + NO emails → usuario logueado
                        if ($emailsFromRow->isEmpty()) {
                            if ($loggedId === '') {
                                $report['summary']['failed']++;
                                $hasValidationErrors = true;
                                continue;
                            }

                            $order['usersIds'] = [$loggedId];
                        }

                        // B2: NO CIF + emails → emails existentes
                        else {
                            $users = User::whereIn('email', $emailsFromRow->all())
                                ->get(['_id', 'email']);

                            if ($users->isEmpty()) {
                                $report['summary']['failed']++;
                                $report['failedRows'][] = [
                                    'rowNumber' => $rowNumber,
                                    'reason'    => 'emails_no_existentes',
                                    'details'   => 'Ningún email indicado existe en el sistema.',
                                ];
                                $failCells[$rowNumber][] = ['col' => 36, 'msg' => 'Emails no existentes'];
                                $hasValidationErrors = true;
                                continue;
                            }

                            $order['usersIds'] = $users->pluck('_id')
                                ->map(fn($id) => (string)$id)
                                ->all();
                        }
                    }



                // Bills
                $bills = $order['billsNumbers'] ?? [];
                if (!empty($row[37])) $bills[0] = trim((string)$row[37]);
                if (!empty($row[38])) $bills[1] = trim((string)$row[38]);
                if (!empty($row[39])) $bills[2] = trim((string)$row[39]);
                if (!empty($bills)) {
                    $order['billsNumbers'] = $bills;
                }

                // Comisiones
                $marketerId = $existingMarketer['_id'] ?? null;
                $hierarchy = AuthController::getAllSuperiors($order['usersIds'][0]);
                $commissionRanges = Enterprise::where('subdomainUser',$userSubdomain['_id'])->first()->commissionRanges;

                if (!$isDual) {
                    $commissions = (isset($row[16]) && $row[16] !== '' && is_numeric($row[16]))
                        ? CommissionHelper::calculateCommission([
                            "userListTop"         => $hierarchy,
                            "assignedToZoco"      => false,
                            "marketer"            => $marketerId,
                            "commissionRanges"    => $commissionRanges,
                            "commissionRangesZoco" => null,
                            "baseCommission"      => $row[16]
                        ])
                        : [
                            "subdomain"  => null,
                            "breakdown"  => CommissionHelper::buildEmptyBreakdown($hierarchy, false)
                        ];

                    if (!empty($row[14])) {
                        $order['installmentCommissions'] = [[
                            'date'        => $this->changeExcelDate($row[14]) ?? Carbon::now()->format('Y-m-d'),
                            'commissions' => $commissions,
                        ]];
                    } else {
                        $order['commissions'] = $commissions;
                    }

                } else {
                    if (!empty($row[43])) $order['consumptionSecondary'] = $row[43];

                    $electricityCommission = (isset($row[16]) && $row[16] !== '' && is_numeric($row[16]))
                        ? CommissionHelper::calculateCommission([
                            "userListTop"         => $hierarchy,
                            "assignedToZoco"      => false,
                            "marketer"            => $marketerId,
                            "commissionRanges"    => $commissionRanges,
                            "commissionRangesZoco" => null,
                            "baseCommission"      => $row[16]
                        ])
                        : [
                            "subdomain"  => null,
                            "breakdown"  => CommissionHelper::buildEmptyBreakdown($hierarchy, false)
                        ];

                    $gasCommission = (isset($row[45]) && $row[45] !== '' && is_numeric($row[45]))
                        ? CommissionHelper::calculateCommission([
                            "userListTop"         => $hierarchy,
                            "assignedToZoco"      => false,
                            "marketer"            => $marketerId,
                            "commissionRanges"    => $commissionRanges,
                            "commissionRangesZoco" => null,
                            "baseCommission"      => $row[45]
                        ])
                        : [
                            "subdomain"  => null,
                            "breakdown"  => CommissionHelper::buildEmptyBreakdown($hierarchy, false)
                        ];

                    //Calculo la comisión total
                    $breakdownMap = [];
                    foreach ([...$electricityCommission['breakdown'], ...$gasCommission['breakdown']] as $item) {
                        $id = $item['id'];
                        if (isset($breakdownMap[$id])) {
                            $breakdownMap[$id]['commission'] = round(
                                    ($breakdownMap[$id]['commission'] + $item['commission']) * 100
                                ) / 100;
                        } else {
                            $breakdownMap[$id] = $item;
                        }
                    }

                    $order['commissions'] = [
                        'subdomain'   => round((($electricityCommission['subdomain'] ?? 0) + ($gasCommission['subdomain'] ?? 0)) * 100) / 100,
                        'breakdown'   => array_values($breakdownMap),
                        'electricity' => $electricityCommission,
                        'gas'         => $gasCommission,
                    ];

                }

                // Duplicados / appendCommission (solo luz/gas)
                $existing = null;
                if (in_array(($order["productType"] ?? null), ['cl','cg'], true) && !empty($order["CUPS"])) {
                    $existing = Order::query()
                        ->where('CUPS', $order['CUPS'])
                        ->when(isset($account), fn($q) => $q->where('account', $order['account']))
                        ->when(!isset($account), fn($q) => $q->whereNull('account')->whereIn('usersIds', $order['usersIds'] ?? [$userLogged['_id'] ?? null]))
                        ->first();

                    if ($existing && !empty($row[14]) && isset($existing['installmentCommissions'])) {
                        $actions[] = [
                            'type'       => 'appendCommission',
                            'existingId' => (string)($existing->_id ?? ''),
                            'payload'    => $order['installmentCommissions'][0],
                        ];
                        continue;
                    }
                }

                $actions[] = [
                    'type'    => 'insert',
                    'order'   => $order,
                    'account' => $account ?? null,
                ];
            }

            // ✅ Si hay errores -> devolvemos Excel con incidencias SIEMPRE
            if ($hasValidationErrors) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();

                foreach ($failCells as $rNum => $cells) {
                    foreach ($cells as $c) {
                        $coord = $colLetter($c['col']).$rNum;

                        $sheet->getStyle($coord)->getFill()->applyFromArray([
                            'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFFFC7CE']
                        ]);

                        $comment = $sheet->getComment($coord);
                        $comment->getText()->createTextRun($c['msg']);
                        $comment->setAuthor('Importación');
                    }
                }

                // Hoja incidencias
                $incSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, 'Incidencias');
                $spreadsheet->addSheet($incSheet);

                $incSheet->setCellValue('A1', 'Fila');
                $incSheet->setCellValue('B1', 'Motivo');
                $incSheet->setCellValue('C1', 'Detalle');
                $incSheet->getStyle('A1:C1')->getFont()->setBold(true);

                $r = 2;
                foreach ($report['failedRows'] as $f) {
                    $motivo = $reasonMap[$f['reason']] ?? $f['reason'];
                    $incSheet->setCellValue("A{$r}", $f['rowNumber']);
                    $incSheet->setCellValue("B{$r}", $motivo);
                    $incSheet->setCellValue("C{$r}", $f['details']);
                    $r++;
                }

                foreach (['A' => 10, 'B' => 35, 'C' => 90] as $col => $w) {
                    $incSheet->getColumnDimension($col)->setWidth($w);
                }

                $tmpPath = storage_path('app/tmp_import_errores_'.uniqid().'.xlsx');
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save($tmpPath);

                return response()->download(
                    $tmpPath,
                    'import_errores.xlsx',
                    ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
                )->deleteFileAfterSend(true);
            }

            // Ejecutar acciones si todo está OK
            foreach ($actions as $act) {
                if ($act['type'] === 'appendCommission') {
                    $existing = Order::where('_id', $act['existingId'])->first();
                    if ($existing) {
                        $installmentCommissions = $existing['installmentCommissions'] ?? [];
                        $installmentCommissions[] = $act['payload'];
                        $existing['installmentCommissions'] = $installmentCommissions;
                        $existing['updatedAt'] = Carbon::now()->format('Y-m-d H:i:s');
                        $existing->save();

                        $report['summary']['appendedCommission']++;
                    }
                    continue;
                }

                if ($act['type'] === 'insert') {
                    $orderService->saveOne($act['order'], $act['account'], $request);
                    $report['summary']['inserted']++;
                }
            }

            return response()->json($report, 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Error al procesar el archivo Excel: '.$e->getMessage().' '.$e->getLine()
            ], 500);
        }
    }

    private function safeExcelDate($value): string
    {
        if ($value === null || $value === '') return '';

        // Numérico: serial de Excel
        if (is_numeric($value)) {
            $unix = ((float)$value - 25569) * 86400;   // base Excel -> UNIX
            return gmdate('Y-m-d', (int)round($unix));
        }

        // Texto: intenta formatos comunes
        if (is_string($value)) {
            $value = trim($value);
            foreach (['Y-m-d','d/m/Y','d-m-Y','d.m.Y'] as $fmt) {
                $dt = \DateTime::createFromFormat($fmt, $value);
                if ($dt instanceof \DateTime) return $dt->format('Y-m-d');
            }
        }

        return '';
    }

    private function changeExcelDate($excelDate)
    {
        if (empty($excelDate)) {
            return null;
        }

        // Si viene como número de serie (ej. 45678)
        if (is_numeric($excelDate)) {
            return Carbon::createFromTimestampUTC(($excelDate - 25569) * 86400)
                ->format('Y-m-d'); // <--- aquí ya devuelves en formato YYYY-MM-DD
        }

        // Si viene como string de fecha normal (ej. "2025-02-01")
        return Carbon::parse(trim($excelDate))->format('Y-m-d');
    }

    public function checkIfHasStatus(Request $request){

        $statusCode = $request->input("statusCode");
        $usersDown = $request->input("usersDown");
        $userLogged = $request->input("userLogged");


        //Lo tengo que hacer en Account
        $usersDown = array_column($usersDown, "_id");

        // Añade el userLogged al array si no está
        if ($userLogged && isset($userLogged['_id'])) {
            $usersDown[] = $userLogged['_id'];
        }

        // Elimina duplicados por si acaso
        $usersDown = array_unique($usersDown);

        $exists = Order::whereIn('usersIds', $usersDown)
            ->where('statuses.code', $statusCode)
            ->exists();

        return response()->json($exists);
    }

    public function createNewMessage(Request $request)
    {
        $orderId = $request['order'];
        $message = $request['message'];
        $enterprise = $request['enterprise'];

        $order = Order::find($orderId);

        if (isset($order->messages))
            $messages = $order->messages;
        else
            $messages = [];

        $newMessage = [
            'message' => $message,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'creator' => Auth::user()->id
        ];

        $messages[] = $newMessage;

        $order->messages = $messages ?? [];
        $order->save();

        $recipientEmail = null;
        $userSubdomain = UserHelper::getUserSubdomain(Auth::user()->_id);
        $isFidelity = isset($userSubdomain['_id']) && (string) $userSubdomain['_id'] === '6909faa9232c09035a03f3b2';

        if ($isFidelity) {
            $excludedLabels = ['Usuario subdominio', 'Jefe administrador', 'Administrador'];

            $usersIds = collect(is_array($order['usersIds'] ?? null) ? $order['usersIds'] : [])
                ->map(function ($userId) {
                    if (is_array($userId) && isset($userId['_id'])) {
                        return (string) $userId['_id'];
                    }

                    return (string) $userId;
                })
                ->filter()
                ->unique()
                ->values()
                ->all();

            $usersById = User::whereIn('_id', $usersIds)
                ->get()
                ->keyBy(fn ($user) => (string) $user->_id);

            foreach ($usersIds as $userIdNow) {
                $candidateUser = $usersById->get($userIdNow);

                if (!$candidateUser || empty($candidateUser->email)) {
                    continue;
                }

                if ((string) $candidateUser->_id === (string) Auth::user()->_id) {
                    continue;
                }

                if (isset($candidateUser->notSendStatusEmails)) {
                    continue;
                }

                $labelSlug = $candidateUser->label;
                if( in_array($labelSlug, $excludedLabels) ) {
                    continue;
                }

                $recipientEmail = $candidateUser->email;
                break;
            }

            if (!$recipientEmail) {
                Log::warning('No se encontró destinatario válido para el correo de nuevo mensaje en Fidelity.', [
                    'orderId' => $order['_id'] ?? null,
                    'usersIds' => $usersIds,
                    'authUserId' => Auth::user()->_id ?? null,
                ]);
            }
        } else {
            //tramitado con zoco ( tener en cuenta )
            $adminUser = null;

            if (isset($order['assignedTo']) && $order['assignedTo'] === '65cb57489c2c285441086a43')
                $adminUser = ['email' => 'mariluz@zocoenergia.com'];
            else if (Auth::user()->label === 'Usuario subdominio')
                $adminUser = Auth::user()->_id === '65cb57489c2c285441086a43' ? ['email' => 'mariluz@zocoenergia.com'] : ['email' => Auth::user()->email];
            else {

                $userSubdomain = UserHelper::getUserSubdomain(Auth::user()->_id);

                //Al no ser subdominio saco el usuario subdominio
                $adminUser = $userSubdomain['_id'] === '65cb57489c2c285441086a43' ? ['email' => 'mariluz@zocoenergia.com'] : ['email' => $userSubdomain['email']];
            }

            //Envío el mensaje por correo //De soporte a
            $recipient = $order['usersIds'][0] === Auth::user()->_id ? $adminUser : User::where('_id', $order['usersIds'][0])->first();
            $recipientEmail = $recipient['email'] ?? null;
        }

        $emailData = [
            //'accName' => $account['name'],
            'content' => 'Nuevo mensaje en su contrato ' . $order['name'] . '(' . $order['CUPS']. ')' . ':<br><br>' . $message,
            'button' => [
                'url' => 'https://' . $enterprise['url'] . '/contracts/?id=' . $this->normalizeMongoId($order['_id']),
                'text' => 'Ir al contrato'
            ],
            'enterprise' => $enterprise
        ];

        if (isset($order['account'])){
            $account = Account::where('_id', $order['account'])->first();
            if (!isset($account)) return null;
            $emailData['accName'] = $account->name;
            $emailData['button']['url'] = 'https://' . $enterprise['url'] . '/accounts/' . $account->_id . '?id=' . $this->normalizeMongoId($order['_id']);
        }

        $mailName = null;
        if (isset($enterprise['mailConfig']) && !!env("MAIL_USERNAME_" . $enterprise['mailConfig']) && !!env("MAIL_PASSWORD_" . $enterprise['mailConfig'])) {
            $mailName = strtoupper($enterprise['mailConfig']);
            Config::set('mail.mailers.smtp.host',    env('MAIL_HOST_' . $mailName) ?: env('MAIL_HOST'));
            Config::set('mail.mailers.smtp.username', env('MAIL_USERNAME_' . $mailName));
            Config::set('mail.mailers.smtp.password', env('MAIL_PASSWORD_' . $mailName));
            Config::set('mail.from.address',          env('MAIL_FROM_ADDRESS_' . $mailName));
            Config::set('mail.from.name',             env('MAIL_FROM_NAME_' . $mailName));
        }

        if ($recipientEmail) {
            Mail::to($recipientEmail)->send(new SendOrderInfo($emailData, $mailName, 'Nuevo mensaje en su contrato ' . $order['name'] . " | " . $order['CUPS']));
        }

        return response()->json(['message' => 'Mensaje guardado correctamente', 'data' => $newMessage], 200);
    }

    public function exportTemplate(Request $request)
    {
        $userLogged = Auth::user();
        if (!$userLogged) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $filePath = $this->orderIndexService->exportOrders([
            'sortBy'        => $request->get('sortBy', 'lastUpdate'),
            'sortDirection' => $request->get('sortDirection', 'desc'),
            'search'        => $request->get('search', ''),
            'searchOption'  => $request->get('searchOption', 'all'),
            'filters'       => $request->get('filters', []),
            'userSubdomain' => $request->get('userSubdomain'),
        ], $userLogged);

        $filename = 'Contratos_' . now()->format('Ymd_His') . '.xlsx';

        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ])->deleteFileAfterSend(true);
    }

    public static function getActiveAndCommissionedContracts()
    {
        $subordinates = UserHelper::hierarchy(Auth::user()->_id);

        $userIds = collect($subordinates)
            ->pluck('_id')
            ->push(Auth::user()->_id)
            ->unique()
            ->toArray();

        $statusAllowed      = ['a'];                 // solo Activado
        $liquidationAllowed = ['al', 'cl', 'tl'];

        return Order::whereIn('usersIds', $userIds)
            ->whereIn('liquidationStatus', $liquidationAllowed)
            ->get(['CUPS', 'pricesProduct', 'statuses', 'transferDate'])

            ->filter(function ($order) use ($statusAllowed) {

                $statuses = $order->statuses ?? [];

                if (is_object($statuses)) {
                    $statuses = (array) $statuses;
                }

                if (!is_array($statuses) || empty($statuses)) {
                    return false;
                }

                $last = null;

                foreach ($statuses as $s) {
                    $code = is_array($s) ? ($s['code'] ?? null) : ($s->code ?? null);
                    $date = is_array($s) ? ($s['date'] ?? null) : ($s->date ?? null);

                    if (!$code) continue;

                    if ($last === null) {
                        $last = ['code' => $code, 'date' => $date];
                        continue;
                    }

                    if ($date && $last['date']) {
                        if (Carbon::parse($date)->gt(Carbon::parse($last['date']))) {
                            $last = ['code' => $code, 'date' => $date];
                        }
                    } elseif ($date && !$last['date']) {
                        $last = ['code' => $code, 'date' => $date];
                    }
                }

                if ($last === null) {
                    return false;
                }

                return in_array($last['code'], $statusAllowed, true);
            })

            /* ========= 🔥 NUEVO: AGRUPAR POR CUPS ========= */
            ->groupBy(function ($order) {
                return strtoupper(str_replace(' ', '', $order->CUPS));
            })

            /* ========= 🔥 NUEVO: ELEGIR POR transferDate ========= */
            ->map(function ($ordersByCups) {

                return $ordersByCups
                    ->sortByDesc(function ($order) {

                        if (!$order->transferDate) {
                            return 0;
                        }

                        try {
                            // formato: 18/03/24
                            return Carbon::createFromFormat(
                                'd/m/y',
                                $order->transferDate
                            )->timestamp;
                        } catch (\Exception $e) {
                            return 0;
                        }
                    })
                    ->first();
            })

            /* ========= MAP FINAL ORIGINAL ========= */
            ->map(function ($order) {

                $cups = strtoupper(str_replace(' ', '', $order->CUPS));

                $pricesProduct = $order->pricesProduct ?? [];
                if (is_object($pricesProduct)) {
                    $pricesProduct = (array) $pricesProduct;
                }

                $prices = $pricesProduct['prices'] ?? [];
                if (is_object($prices)) {
                    $prices = (array) $prices;
                }

                $power   = $prices['power']   ?? [];
                $consume = $prices['consume'] ?? [];

                if (is_object($power)) {
                    $power = (array) $power;
                }
                if (is_object($consume)) {
                    $consume = (array) $consume;
                }

                return [
                    'CUPS' => $cups,

                    'precios_energia' => [
                        'p1' => $consume['P1'] ?? null,
                        'p2' => $consume['P2'] ?? null,
                        'p3' => $consume['P3'] ?? null,
                        'p4' => $consume['P4'] ?? null,
                        'p5' => $consume['P5'] ?? null,
                        'p6' => $consume['P6'] ?? null,
                    ],

                    'precios_potencias' => [
                        'p1' => $power['P1'] ?? null,
                        'p2' => $power['P2'] ?? null,
                        'p3' => $power['P3'] ?? null,
                        'p4' => $power['P4'] ?? null,
                        'p5' => $power['P5'] ?? null,
                        'p6' => $power['P6'] ?? null,
                    ],
                ];
            })
            ->values()
            ->toArray();
    }

    public function createFromInvoiceQuick(Request $request, OrderService $orderService)
    {
        try {
            $userLogged = Auth::user();

            if (!$userLogged) {
                return response()->json([
                    'error' => 'Usuario no autenticado.'
                ], 401);
            }

            $userSubdomain = (array) $request->input('userSubdomain', []);

            if (!isset($userSubdomain['_id'])) {
                $userSubdomain = json_decode($userSubdomain[0] ?? '{}', true);
            }

            if (!isset($userSubdomain['_id'])) {
                return response()->json([
                    'error' => 'No se ha podido identificar el subdominio del usuario.'
                ], 400);
            }

            // COMPROBACIÓN PLAN
            $subscription = Enterprise::where('subdomainUser', $userSubdomain['_id'])
                ->pluck('subscription')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'limit' => 'No puedes realizar escaneos porque no tienes una suscripción activa.'
                ], 400);
            }

            $includedScans = data_get($subscription, 'included.scans');
            $usedScans = (int) data_get($subscription, 'usage.scans', 0);
            $extraScansRemaining = (int) data_get($subscription, 'extras.one_time.scans.remaining', 0);

            // null significa ilimitado
            $hasUnlimitedScans = $includedScans === null;

            $hasRemainingScans = $hasUnlimitedScans
                || $usedScans < (int) $includedScans
                || $extraScansRemaining > 0;

            if (!$hasRemainingScans) {
                return response()->json([
                    'limit' => 'No puedes realizar más escaneos porque has alcanzado el límite de tu plan. Cambia de plan o compra escaneos extra.'
                ], 400);
            }

            $enterprise = (array) $request->input('enterprise', []);

            if (!isset($enterprise['_id'])) {
                $enterprise = json_decode($enterprise[0] ?? '{}', true);
            }

            $colors = (array) $request->input('colors', []);

            if (!isset($colors['principal'])) {
                $colors = json_decode($colors[0] ?? '{}', true);
            }

            // OCR rápido
            $ocrRaw = self::getInvoiceQuickData($request);

            if ($ocrRaw instanceof \Illuminate\Http\JsonResponse) {
                return $ocrRaw;
            }

            $ocrData = is_array($ocrRaw) ? $ocrRaw : json_decode($ocrRaw, true);

            if (!$ocrData || !is_array($ocrData)) {
                return response()->json([
                    'error' => 'No se pudieron interpretar los datos de la factura.'
                ], 422);
            }

            // Construcción de datos
            $accountData = $this->buildQuickAccountFromInvoice($ocrData, $userLogged);
            $orderData = $this->buildQuickOrderFromInvoice($ocrData, $userLogged, $userSubdomain);

            // Buscar cuenta existente SOLO del usuario logueado
            $existingAccount = null;
            $normalizedCif = strtoupper(preg_replace('/[\s\.\-]/', '', $accountData['CIF'] ?? ''));
            $loggedId = (string) $userLogged->_id;

            if (!empty($normalizedCif) && $normalizedCif !== '11111111A') {
                $chars = str_split(preg_quote($normalizedCif, '/'));
                $pattern = '^' . implode('[\s\.\-]*', $chars) . '$';
                $regex = new \MongoDB\BSON\Regex($pattern, 'i');

                $existingAccount = Account::where('usersIds', $loggedId)
                    ->whereRaw([
                        'CIF' => $regex
                    ])
                    ->first();
            }

            if ($existingAccount) {
                $account = $existingAccount;
            } else {
                $account = Account::create($accountData);
            }

            // Preparar datos que espera saveOne
            $request->merge([
                'userSubdomain' => json_encode($userSubdomain),
                'enterprise' => json_encode($enterprise),
                'colors' => json_encode($colors),
            ]);

            // Guardar contrato vinculado a la cuenta encontrada o creada
            $result = $orderService->saveOne($orderData, $account->toArray(), $request);

            $savedOrder = $result['order'] ?? null;
            $emailError = $result['emailError'] ?? false;

            // Contabilizar/descontar escaneo SOLO cuando el contrato ya se ha creado correctamente
            $scanSource = $this->consumeScan($userSubdomain['_id']);

            return response()->json([
                'message' => $existingAccount
                    ? 'Se encontró una cuenta existente para ese CIF/NIF y se creó el contrato asociado.'
                    : 'Se creó la cuenta y el contrato correctamente desde factura.',
                'emailError' => $emailError,
                'account' => $account,
                'order' => $savedOrder,
                'ocrData' => $ocrData,
                'scanSource' => $scanSource,
            ], 200);

        } catch (\Throwable $e) {
            \Log::error('Error createFromInvoiceQuick: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Se produjo un error procesando la factura.',
                'detail' => $e->getMessage()
            ], 500);
        }
    }


    private function consumeScan(string $subdomainUserId): string
    {
        $enterprise = Enterprise::where('subdomainUser', $subdomainUserId)->first();

        if (!$enterprise || empty($enterprise->subscription)) {
            throw new \Exception('No tienes una suscripción activa.');
        }

        $subscription = $enterprise->subscription;

        $includedScans = $subscription['included']['scans'] ?? 0;
        $usedScans = $subscription['usage']['scans'] ?? 0;
        $extraScansRemaining = $subscription['extras']['one_time']['scans']['remaining'] ?? 0;

        // Plan ilimitado
        if ($includedScans === null) {
            return 'unlimited';
        }

        // Primero consume escaneos mensuales del plan
        if ($usedScans < $includedScans) {
            $subscription['usage']['scans'] = $usedScans + 1;

            $enterprise->subscription = $subscription;
            $enterprise->save();

            return 'plan';
        }

        // Después consume escaneos extra puntuales
        if ($extraScansRemaining > 0) {
            $subscription['extras']['one_time']['scans']['remaining'] = $extraScansRemaining - 1;

            $enterprise->subscription = $subscription;
            $enterprise->save();

            return 'extra';
        }

        throw new \Exception('No quedan escaneos disponibles.');
    }

    public static function getInvoiceQuickData(Request $request)
    {
        $urlPDF = $request->input('urlPDF');
        $files  = $request->file('files');
        $inputFiles = [];


        $tmpDir = storage_path('app');

        $buildImageInputs = function (array $paths) use (&$inputFiles) {
            foreach ($paths as $imgPath) {
                $mime = mime_content_type($imgPath) ?: 'image/jpeg';
                $imageBase64 = base64_encode(file_get_contents($imgPath));
                $inputFiles[] = [
                    "type" => "input_image",
                    "image_url" => 'data:' . $mime . ';base64,' . $imageBase64
                ];
            }
        };

        $convertPdfToImages = function (string $pdfPath) use ($tmpDir): array {
            $pdftoppm = env('PDFTOPPM_BIN', '/usr/bin/pdftoppm');
            $outBase  = $tmpDir . '/invoice_quick_' . uniqid('page_', true);
            $cmd      = sprintf('"%s" "%s" "%s" -jpeg -r 180', $pdftoppm, $pdfPath, $outBase);
            exec($cmd, $out, $ret);
            if ($ret !== 0) return [];
            $images = glob($outBase . '-*.jpg');
            return is_array($images) ? $images : [];
        };

        $pdfHasText = function (string $pdfPath): bool {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($pdfPath);
                $text   = trim($pdf->getText() ?? '');
                return $text !== '';
            } catch (\Throwable $e) {
                return false;
            }
        };

        if ($urlPDF) {
            $tmpPdf = $tmpDir . '/invoice_quick_' . uniqid('pdf_', true) . '.pdf';
            file_put_contents($tmpPdf, $urlPDF);

            if ($pdfHasText($tmpPdf)) {
                $pdfBase64 = base64_encode(file_get_contents($tmpPdf));
                $inputFiles[] = [
                    "type" => "input_file",
                    "filename" => "factura.pdf",
                    "file_data" => 'data:application/pdf;base64,' . $pdfBase64
                ];
            } else {
                $images = $convertPdfToImages($tmpPdf);
                if (count($images) === 0) {
                    return response()->json(['error' => 'No se pudo convertir el PDF escaneado a imágenes.'], 400);
                }
                $buildImageInputs($images);
            }
        } elseif ($files) {
            if (!is_array($files)) $files = [$files];

            $fileTypes = collect($files)->map(fn($f) => $f->getMimeType());

            $hasPDF    = $fileTypes->contains(fn($t) => str_starts_with($t, 'application/pdf'));
            $hasImages = $fileTypes->every(fn($t) => str_starts_with($t, 'image/'));

            if ($hasPDF && $fileTypes->count() > 1) {
                return response()->json(['error' => 'No se permite enviar PDF junto con otros archivos.'], 400);
            }

            if ($hasPDF && !$hasImages) {
                if (count($files) > 1) {
                    return response()->json(['error' => 'Solo se permite un archivo PDF.'], 400);
                }

                $pdfPath = $files[0]->getRealPath();

                if ($pdfHasText($pdfPath)) {
                    $pdfBase64 = base64_encode(file_get_contents($pdfPath));
                    $inputFiles[] = [
                        "type" => "input_file",
                        "filename" => "factura.pdf",
                        "file_data" => 'data:application/pdf;base64,' . $pdfBase64
                    ];
                } else {
                    $tmpPdf = $tmpDir . '/invoice_quick_' . uniqid('pdf_', true) . '.pdf';
                    copy($pdfPath, $tmpPdf);
                    $images = $convertPdfToImages($tmpPdf);
                    if (count($images) === 0) {
                        return response()->json(['error' => 'No se pudo convertir el PDF escaneado a imágenes.'], 400);
                    }
                    $buildImageInputs($images);
                }
            } elseif ($hasImages) {
                foreach ($files as $file) {
                    $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));
                    $inputFiles[] = [
                        "type" => "input_image",
                        "image_url" => 'data:' . $file->getMimeType() . ';base64,' . $imageBase64
                    ];
                }
            } else {
                return response()->json(['error' => 'Tipo de archivo no permitido. Solo PDF o imágenes.'], 400);
            }
        } else {
            return response()->json(['error' => 'No se ha enviado ningún archivo ni URL.'], 400);
        }

        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . env("CHATGPT_API_KEY"),
                'Content-Type' => 'application/json',
            ]
        ]);

        $requestData = [
            "model" => "gpt-4.1",
            "input" => [
                [
                    "role" => "system",
                    "content" => "
    Eres un analizador experto de facturas energéticas españolas.

    OBJETIVO:
    Extraer SOLO los datos mínimos necesarios para crear una cuenta y un contrato en un CRM.

    REGLAS:
    - Responde SOLO con JSON válido.
    - No añadas texto fuera del JSON.
    - No uses markdown.
    - No inventes datos.
    - Si un dato no aparece claro, devuelve null.
    - No añadas claves extra.
    - El CUPS debe devolverse sin espacios.
    - Si la factura parece de luz devuelve product_type = cl.
    - Si la factura parece de gas devuelve product_type = cg.
    - Si no se puede saber con claridad, devuelve cl.
    - El campo fee debe contener la tarifa textual más útil detectada.
    - El campo marketer debe contener la comercializadora.
    - El campo owner_name debe ser el titular o nombre principal del cliente.
    - El campo account_name debe ser el mejor nombre para la cuenta. Normalmente el titular.
    - zipcode debe devolverse sin espacios.
    - consumption debe ser el total consumido si aparece claramente.
    - iban solo si aparece claramente.
    - address debe ser la dirección del suministro.
    - start_date y end_date deben ir en formato DD/MM/YYYY si aparecen.

    DEVUELVE EXACTAMENTE ESTE JSON:
    {
    \"account_name\": null,
    \"owner_name\": null,
    \"cif_nif\": null,
    \"address\": null,
    \"town\": null,
    \"province\": null,
    \"zipcode\": null,
    \"marketer\": null,
    \"cups\": null,
    \"fee\": null,
    \"product\": null,
    \"product_type\": null,
    \"iban\": null,
    \"consumption\": null,
    \"start_date\": null,
    \"end_date\": null
    }
    "
                ],
                [
                    "role" => "user",
                    "content" => [
                        ...$inputFiles,
                    ]
                ]
            ]
        ];

        try {

            $response = $client->post('responses', ['json' => $requestData]);
            $data = json_decode($response->getBody(), true);

            $text = $data["output"][0]["content"][0]["text"] ?? null;

            if (!$text) {
                return response()->json(['error' => 'No se pudo leer la respuesta del OCR rápido.'], 422);
            }

            return $text;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $msg = "Error en la solicitud: " . $e->getMessage();
            if ($e->hasResponse()) {
                $msg .= "\n" . $e->getResponse()->getBody();
            }
            return response()->json(['error' => $msg], 500);
        }
    }

    public function changeStatus(Request $request) {

        $orderId = $request->input('id');
        $status  = $request->input('status');

        $order = Order::where('_id', $orderId)->first();
        $statuses = $order->statuses ?? [];

        $statuses[] = [
            'code' => $status,
            'date' => Carbon::now()->format('Y-m-d H:i:s'),
            'creator' => Auth::user()->_id
        ];

        // Calcular lastStatus a partir del array actualizado
        $latest = null;
        $latestDate = null;

        foreach ($statuses as $s) {
            if (empty($s['date'])) {
                continue;
            }

            try {
                $date = Carbon::parse($s['date']);
            } catch (\Exception $e) {
                continue;
            }

            if (!$latestDate || $date->gt($latestDate)) {
                $latestDate = $date;
                $latest = [
                    'code' => $s['code'] ?? null,
                    'date' => $s['date']
                ];
            }
        }

        $order->statuses = $statuses;
        $order->lastStatus = $latest; // ⭐ AÑADIDO
        $order->save();

        return response()->json(['message' => 'Estado del contrato actualizado'], 200);
    }

    private function buildQuickAccountFromInvoice(array $ocrData, $userLogged): array
    {
        $accountName = $ocrData['account_name'] ?? null;
        $cifNif      = $ocrData['cif_nif'] ?? null;

        return [
            'name' => $accountName ?: ('Cuenta factura ' . now()->format('YmdHis')),
            'accType' => '',
            'sector' => '',
            'CIF' => $cifNif ?: '11111111Ñ',
            'NIFRepresentative' => '',
            'NameRepresentative' => '',
            'opportunity' => '',
            'observations' => 'Cuenta creada desde factura, puede haber campos obligatorios los cuáles no tiene la factura que se deben introducir',
            'phone' => '',
            'landLinePhone' => '',
            'website' => '',
            'email' => '',
            'principalAcc' => '',
            'profileImage' => 'default.jpg',
            'customFields' => [],
            'usersIds' => [(string)$userLogged->_id],
            'createdBy' => (string)$userLogged->_id,
            'createdAt' => now()->format('Y-m-d H:i:s'),
            'billingInfo' => [
                'community' => '',
                'province' => $ocrData['province'] ?? '',
                'locality' => $ocrData['town'] ?? '',
                'address' => $ocrData['address'] ?? '',
                'zipCode' => $ocrData['zipcode'] ?? '',
            ],
        ];
    }

    private function buildQuickOrderFromInvoice(array $ocrData, $userLogged, array $userSubdomain): array
    {
        $statusCode = (
            $userSubdomain['_id'] ?? null
        ) === '6909faa9232c09035a03f3b2' ? 'bo' : 'p';

        $docs = [];
        $files = request()->file('files');

        if ($files) {
            if (!is_array($files)) $files = [$files];

            foreach ($files as $file) {
                if ($file) {
                    $docs[] = [
                        'title' => 'Factura subida',
                        'defaultTitle' => 'Factura subida',
                        'value' => null,
                        'fileValue' => $file
                    ];
                }
            }
        }

        return [
            'name' => $ocrData['owner_name'] ?: ($ocrData['account_name'] ?: 'Contrato desde factura'),
            'direc' => $ocrData['address'] ?? '',
            'zip' => $ocrData['zipcode'] ?? '',
            'town' => $ocrData['town'] ?? '',
            'province' => $ocrData['province'] ?? '',
            'source' => '',
            'processingDate' => '',
            'activationDate' => '',
            'lowDate' => '',
            'liquidationStatus' => 'nl',
            'productType' => $ocrData['product_type'] ?? 'cl',
            'marketer' => '',
            'fee' => '',
            'product' => "",
            'commissions' => [
                'subdomain' => null,
                'breakdown' => []
            ],
            'CUPS' => $ocrData['cups'] ?? '',
            'consumption' => $ocrData['consumption'] ?? '',
            'IBAN' => $ocrData['iban'] ?? '',
            'docs' => [],
            'statuses' => [],
            'newStatus' => [
                'code' => "bo",
                'date' => now()->format('Y-m-d H:i:s')
            ],
            'errors' => [],
            'usersIds' => [(string)$userLogged->_id],
            'createdBy' => (string)$userLogged->_id,
            'owner' => trim(($userLogged->firstName ?? '') . ' ' . ($userLogged->lastName ?? '')),
            'ownerId' => (string)$userLogged->_id,
            'accountCIF' => $ocrData['cif_nif'] ?? '',
            'transferDate' => now()->format('d/m/y'),
            'observations' => '',
        ];
    }

}
