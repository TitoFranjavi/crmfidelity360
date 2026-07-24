<?php

namespace App\Http\Controllers;

use App\Http\Models\Account;
use App\Http\Models\Contact;
use App\Http\Models\Email;
use App\Http\Models\Event;
use App\Http\Models\Opportunity;
use App\Http\Models\Order;
use App\Http\Models\Task;
use App\Http\Models\User;
use App\Mail\SendOrderInfo;
use App\Services\AuditLogService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Http\Controllers\GoogleController;
use Illuminate\Support\Str;
use function PHPUnit\Framework\isFalse;
use MongoDB\BSON\Regex;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;




class AccountController extends Controller
{
    function makeCifRegex(string $raw): Regex {
        // Normaliza tu entrada: sin espacios, puntos ni guiones y a mayúsculas
        $normalized = strtoupper(preg_replace('/[\s\.\-]/', '', $raw));

        // Convierte "A123B" -> /^A[\s.\-]*1[\s.\-]*2[\s.\-]*3[\s.\-]*B$/i
        $chars = str_split(preg_quote($normalized, '/'));
        $pattern = '^' . implode('[\s\.\-]*', $chars) . '$';

        return new Regex($pattern, 'i');
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

        // DNI / NIF persona física: 8 números + letra
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

        // NIE: X/Y/Z + 7 números + letra
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

        // CIF: letra inicial + 7 números + carácter de control
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

        // Si no ha encajado como DNI/NIE/CIF, lo tratamos como posible pasaporte.
        // No permitimos texto libre tipo nombres.
        if (!preg_match('/^[A-Z0-9]{5,20}$/', $doc)) {
            return [
                'valid' => false,
                'skipped' => false,
                'message' => 'DNI/CIF/Pasaporte no válido',
            ];
        }

        // Evita que se metan nombres: JUANPEREZ, MARIA, EMPRESA, etc.
        if (!preg_match('/\d/', $doc)) {
            return [
                'valid' => false,
                'skipped' => false,
                'message' => 'DNI/CIF/Pasaporte no válido',
            ];
        }

        // Casos que parecen documentos españoles incompletos
        if (
            preg_match('/^\d{8}$/', $doc) ||
            preg_match('/^[XYZ]\d{7}$/', $doc) ||
            preg_match('/^[ABCDEFGHJKLMNPQRSUVW]\d{7}$/', $doc)
        ) {
            return [
                'valid' => false,
                'skipped' => false,
                'message' => 'DNI/CIF/Pasaporte no válido',
            ];
        }

        // Pasaporte/documento extranjero con estructura mínima válida
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

        // Excepción de negocio: debe seguir siendo válido
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

        // ES + 16 dígitos + 2 letras de control + opcionalmente 2 caracteres finales
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

    private function validateAccountPayloadControlFields(array $account)
    {
        $documentValidation = $this->validateSpanishDocumentIfApplies($account['CIF'] ?? '');

        if (!$documentValidation['valid']) {
            return response()->json([
                'cifError' => 'DNI/CIF/Pasaporte no válido',
                'message' => 'DNI/CIF/Pasaporte no válido',
                'errors' => [
                    'CIF' => 'DNI/CIF/Pasaporte no válido',
                ],
            ], 422);
        }

        foreach (($account['orders'] ?? []) as $index => $order) {
            $ibanValidation = $this->validateIban($order['IBAN'] ?? '');

            if (!$ibanValidation['valid']) {
                return response()->json([
                    'errors' => [
                        'orders' => [
                            $index => [
                                'IBAN' => $ibanValidation['message'],
                            ],
                        ],
                    ],
                ], 422);
            }

            $cupsValidation = $this->validateCups($order['CUPS'] ?? '');

            if (!$cupsValidation['valid']) {
                return response()->json([
                    'errors' => [
                        'orders' => [
                            $index => [
                                'CUPS' => $cupsValidation['message'],
                            ],
                        ],
                    ],
                ], 422);
            }

            if (!empty($order['CUPSSecondary'])) {
                $cupsSecondaryValidation = $this->validateCups($order['CUPSSecondary']);

                if (!$cupsSecondaryValidation['valid']) {
                    return response()->json([
                        'errors' => [
                            'orders' => [
                                $index => [
                                    'CUPSSecondary' => $cupsSecondaryValidation['message'],
                                ],
                            ],
                        ],
                    ], 422);
                }
            }
        }

        return null;
    }


    //funcion para guardar una cuenta
    public function store(Request $request, OrderService $orderService)
    {

        $account = json_decode($request['account'], true);
        $userLogged = json_decode($request['userLogged'], true);
        $userSubdomain = json_decode($request['userSubdomain'], true);

        $controlFieldsValidationResponse = $this->validateAccountPayloadControlFields($account);

        if ($controlFieldsValidationResponse) {
            return $controlFieldsValidationResponse;
        }


        //CIF/NIF ( compruebo que no exista pero no sea del mismo agente )
        $normalizedCIF = strtoupper(preg_replace('/[\s\.\-]/', '', $account['CIF']));

        // Creamos un regex flexible que ignora formato y mayúsculas/minúsculas
        $regex = new \MongoDB\BSON\Regex('^' . preg_quote($normalizedCIF, '/') . '$', 'i');


        // Buscamos cuentas con ese CIF (ignorando formato)
        $usersIdsToCheck = !empty($account['usersIds']) ? $account['usersIds'] : [$userLogged['_id']];

        $cifsexists = Account::whereRaw([
            'CIF' => $regex,
            'usersIds' => ['$in' => $usersIdsToCheck]
        ])->exists();

        if ($cifsexists) {
            return response()->json(['cifError' => 'Ya existe una cuenta con el CIF/NIF'], 400);
        }


        //Añado la imagen de la cuenta ( si esq tiene )
        $imageFile = $request['imageFile'];

        if ($imageFile) {
            //Creo el nombre de la imagen para guardar
            $imageName = time() . '.' . explode('.', $imageFile->getClientOriginalName())[1];

            //Meto la imagen en local
            Storage::disk('account')->put($imageName, file_get_contents($imageFile));
        }


        //Recorro los campos customizados para ver si tiene alguno de tipo imagen
        $customFields = $account['customFields'];
        foreach ($customFields as $fieldInd => &$field) {

            //Si es imagen
            if ($field['type'] === 'image') {

                //Saco el archivo
                $file = $request['customFieldFile' . $fieldInd];

                if ($file !== null && !is_string($file)) {

                    // Evita duplicados: tiempo + ID único + nombre original limpio
                    $fieldImageName = time() . '-' . uniqid() . '-' . explode('.', $file->getClientOriginalName())[0] . '.' . explode('.', $file->getClientOriginalName())[count(explode('.', $file->getClientOriginalName())) - 1];

                    //Guardo la imagen en local
                    Storage::disk('account')->put($fieldImageName, file_get_contents($file));

                    //Meto el nombre en el campo value para registrarlo
                    $field['value'] = $fieldImageName;

                    //Borro el $field-fileImage que es donde esta en si el archivo
                    unset($field['imageFile']);
                }
            }
        }
        unset($field);
        $account['customFields'] = $customFields;


        //Si no hay usuarios asignados le meto el propio
        if (empty($account['usersIds']) || !is_array($account['usersIds'])) {
            $account['usersIds'] = [$userLogged['_id']];
        }

        //Si algún contrato está asignado a Zoco me aseguro de que la cuenta también lo esté (SI NO DEL SUBDOMINIO DE ZOCO)
        if ($userSubdomain['_id'] !== '65cb57489c2c285441086a43') {
            $assignedToZoco = false;
            $usersIds = $account['usersIds'];

            foreach ($account['orders'] as $order) {
                if (isset($order['assignedToZoco']))
                    $assignedToZoco = true;
            }

            $containsZoco = array_search('65cb57489c2c285441086a43', $usersIds);

            //Si está asignado alguno reviso si no esta y lo meto y si no esta asignado reviso si esta y lo quito SEGUIR AQUÍ
            if (!$assignedToZoco && $containsZoco !== false) //si no hay ninguno asignado y esta Zoco dentro lo elimino
                array_splice($usersIds, $containsZoco, 1);
            elseif ($assignedToZoco && $containsZoco === false)//si hay alguno asignado y la cuenta no lo tiene
                $usersIds[] = '65cb57489c2c285441086a43';

            $account['usersIds'] = $usersIds;
        }


        //Creo la cuenta
        $accountSaved = Account::create([
            'name' => $account['name'],
            'accType' => $account['accType'],
            'sector' => $account['sector'],
            'CIF' => $account['CIF'],
            'NIFRepresentative' => $account['NIFRepresentative'] ?? '',
            'NameRepresentative' => $account['NameRepresentative'] ?? '',
            //'origin' => $account->origin ?? '',
            'phone' => $account['phone'],
            'landLinePhone' => !!$account['landLinePhone'] ? $account['landLinePhone'] : '',
            'website' => $account['website'],
            'email' => $account['email'],
            'observations' => $account['observations'],
            'principalAcc' => $account['principalAcc'],
            'billingInfo' => [
                'community' => $account['billingInfo']['community'],
                'province' => $account['billingInfo']['province'],
                'locality' => $account['billingInfo']['locality'],
                'address' => $account['billingInfo']['address'],
                'zipCode' => $account['billingInfo']['zipCode']
            ],
            'customFields' => $account['customFields'],
            'profileImage' => $imageFile ? $imageName : null,
            'opportunity' => $account['opportunity'] ?? '',
            'usersIds' => $account['usersIds'],
            'createdBy' => $userLogged['_id'],
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
        ], '_id');

        $account['_id'] = (string) $accountSaved['_id'];

        //Creo el log
        AuditLogService::createOrDeleteAccount($accountSaved, $userLogged, 'create');

        //Creo los contratos relacionados a la cuenta
        if (count($account['orders']) > 0) {
            // Importante: el OrderService ya hereda usersIds desde la cuenta si no vienen en el contrato
            $createdOrderIds = $orderService->saveManyForAccount($account['orders'], $account, $request);
        }

        // Recargar cuenta con orders ya creados
        $accountWithOrders = Account::where('_id', $accountSaved->_id)->first();

        // Traer también los contratos asociados
        $orders = Order::where('account', (string)$accountSaved->_id)->get();

        $accountWithOrders['orders'] = $orders;

        return response()->json([
            'message' => 'La cuenta ha sido creado correctamente',
            'account' => $accountWithOrders
        ], 201);
    }

    //funcion para actualizar una cuenta
    public function update(Request $request, OrderService $orderService) //REVISAR
    {
        $userLogged = session()->get('userLogged');
        $account = json_decode($request['account'], true);
        $userSubdomain = json_decode($request['userSubdomain'], true);

        $controlFieldsValidationResponse = $this->validateAccountPayloadControlFields($account);

        if ($controlFieldsValidationResponse) {
            return $controlFieldsValidationResponse;
        }


        // --- Validación de duplicado de CIF ---
        $rawCif = $account['CIF'] ?? '';
        $normalizedCif = strtoupper(preg_replace('/[\s\.\-]/', '', $rawCif));

        // Si no es el CIF comodín
        if ($normalizedCif !== '11111111A' && $normalizedCif !== '') {
            // Creamos un regex tolerante que ignora formato (espacios, guiones, puntos)
            $chars = str_split(preg_quote($normalizedCif, '/'));
            $pattern = '^' . implode('[\s\.\-]*', $chars) . '$';
            $regex = new Regex($pattern, 'i');

            // Preparo la query base
            $ownerId = $account['usersIds'][0] ?? $userLogged['_id'];
            $query = Account::where('usersIds', $ownerId)->where('CIF', '!=', '11111111A')
                ->whereRaw(['CIF' => $regex]);

            // Excluir la propia cuenta si estamos editando
            if (!empty($account['_id']) && preg_match('/^[a-f0-9]{24}$/i', (string)$account['_id'])) {
                $query->where('_id', '!=', new ObjectId((string)$account['_id']));
            }

            $accountsSameCIF = $query->get();


            if ($accountsSameCIF->isNotEmpty()) {
                return response()->json(['cifError' => 'Ya existe una cuenta con el CIF/NIF'], 400);
            }
        }



        // Saco el creador de la cuenta y la cuenta de la BBDD
        $user = $account['createdBy'];
        $imageFile = $request->file('imageFile');
        $accountToSave = Account::where('_id', $account['_id'])->first();
        $copyAcountToSave = json_decode(json_encode($accountToSave), true);


        // Subida de imagen de perfil
        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $orig = $imageFile->getClientOriginalName();
            $base = pathinfo($orig, PATHINFO_FILENAME);
            $ext = $imageFile->getClientOriginalExtension();
            $imageName = time() . '_' . uniqid() . '_' . Str::slug($base) . '.' . $ext;

            if ($accountToSave['profileImage'] !== 'default.jpg' && $accountToSave['profileImage'] !== null) {
                Storage::disk('account')->delete($accountToSave['profileImage']);
            }
            Storage::disk('account')->put($imageName, file_get_contents($imageFile));
            $accountToSave['profileImage'] = $imageName;
        }

        // Actualizo campos básicos
        $accountToSave['name'] = $account['name'];
        $accountToSave['accType'] = !!$account['accType'] ? $account['accType'] : '';
        $accountToSave['sector'] = !!$account['sector'] ? $account['sector'] : '';
        $accountToSave['CIF'] = $account['CIF'];
        if (isset($account['NIFRepresentative']))
            $accountToSave['NIFRepresentative'] = $account['NIFRepresentative'];
        if (isset($account['NameRepresentative']))
            $accountToSave['NameRepresentative'] = $account['NameRepresentative'];

        //$accountToSave['origin']           = !!$account->origin ? $account->origin : '';
        //$accountToSave['contact']           = !!$account->contact ? $account->contact : '';
        $accountToSave['opportunity'] = !!$account['opportunity'] ? $account['opportunity'] : '';

        if (isset($account['observations']))
            $accountToSave['observations'] = $account['observations'];
        $accountToSave['phone'] = $account['phone'];
        if (isset($account['landLinePhone']))
            $accountToSave['landLinePhone'] = $account['landLinePhone'];
        $accountToSave['website'] = !!$account['website'] ? $account['website'] : '';
        $accountToSave['email'] = $account['email'];
        $accountToSave['principalAcc'] = !!$account['principalAcc'] ? $account['principalAcc'] : '';

        // Billing Info
        $accountBillingInfo = $accountToSave['billingInfo'];
        $accountBillingInfo['community'] = $account['billingInfo']['community'];
        $accountBillingInfo['province'] = $account['billingInfo']['province'];
        $accountBillingInfo['locality'] = $account['billingInfo']['locality'];
        if (isset($accountBillingInfo['address']))
            $accountBillingInfo['address'] = $account['billingInfo']['address'];
        if (isset($accountBillingInfo['zipCode']))
            $accountBillingInfo['zipCode'] = $account['billingInfo']['zipCode'];
        $accountToSave['billingInfo'] = $accountBillingInfo;

        // Campos customizados (imágenes)
        $customFields = !!$account['customFields'] ? $account['customFields'] : [];
        foreach ($customFields as $fieldInd => &$field) {
            if ($field['type'] === 'image') {
                $file = $request->file('customFieldFile' . $fieldInd);
                if ($file instanceof UploadedFile && $file->isValid()) {
                    $orig = $file->getClientOriginalName();
                    $parts = explode('.', $orig);
                    $base = pathinfo($orig, PATHINFO_FILENAME);
                    $ext = $file->getClientOriginalExtension();
                    $fieldImageName = time() . '-' . uniqid() . '-' . Str::slug($base) . '.' . $ext;

                    if (isset($field['imageToDelete']) && $field['imageToDelete'] !== '') {
                        Storage::disk('account')->delete($field['imageToDelete']);
                    }
                    Storage::disk('account')->put($fieldImageName, file_get_contents($file));

                    $field['value'] = $fieldImageName;
                    if (isset($field['imageToDelete']))
                        unset($field['imageToDelete']);
                }
            }
        }
        unset($field);
        $accountToSave['customFields'] = $customFields;

        // Ajustes de createdBy y usersIds
        $accountToSave['usersIds'] = (count($account['usersIds']) > 0 ? $account['usersIds'] : [$userLogged->_id]);


        //Si algún contrato está asignado a Zoco me aseguro de que la cuenta también lo esté (SI NO DEL SUBDOMINIO DE ZOCO)
        if ($userSubdomain['_id'] !== '65cb57489c2c285441086a43') {
            $assignedToZoco = false;
            $usersIds = $accountToSave['usersIds'];

            foreach ($account['orders'] as $order) {
                if (isset($order['assignedToZoco']))
                    $assignedToZoco = true;
            }

            $containsZoco = array_search('65cb57489c2c285441086a43', $usersIds);

            //Si está asignado alguno reviso si no esta y lo meto y si no esta asignado reviso si esta y lo quito SEGUIR AQUÍ
            if (!$assignedToZoco && $containsZoco !== false) //si no hay ninguno asignado y esta Zoco dentro lo elimino
                array_splice($usersIds, $containsZoco, 1);
            elseif ($assignedToZoco && $containsZoco === false)//si hay alguno asignado y la cuenta no lo tiene
                $usersIds[] = '65cb57489c2c285441086a43';

            $accountToSave['usersIds'] = $usersIds;
        }


        //Borrar contratos al editar cuenta
        $ordersRelated = Order::where('account', $account['_id'])->get();

        foreach ($ordersRelated as $order) {
            $exists = collect($account['orders'] ?? [])
                ->pluck('_id')
                ->map(fn($id) => (string) $id)
                ->contains((string) ($order->_id ?? $order->getKey()));

            if (!$exists){
                //Log contrato borrado
                AuditLogService::createOrDeleteOrder($order, Auth::user(), 'delete');

                $cupsDeleted = $order->CUPS ?? '';
                $order->delete();

                //Quito el check de las comparativas si ya no queda ningún contrato con este CUPS
                \App\Services\OrderService::unmarkComparativesIfNoContract($cupsDeleted, Auth::user());
            }

        }

        //Parte contratos
        if (count($account['orders']) > 0) {
            // Importante: el OrderService ya hereda usersIds desde la cuenta si no vienen en el contrato
            $createdOrderIds = $orderService->saveManyForAccount($account['orders'], $account, $request);
        }

        //Creo el log de actualización
        AuditLogService::updateAccount($copyAcountToSave, $account, $userLogged);

        // Guardar y responder
       $accountToSave->save();

        // Recargar cuenta ya guardada
        $accountWithOrders = Account::where('_id', $accountToSave->_id)->first();

        // Traer contratos asociados
        $orders = Order::where('account', (string) $accountToSave->_id)->get();

        // Añadirlos al payload igual que en store
        $accountWithOrders['orders'] = $orders;

        return response()->json([
            'message' => 'La cuenta ha sido actualizada con éxito',
            'account' => $accountWithOrders
        ], 200);
    }

    //funcion para sacar las cuentas del usuario logueado
    public function index($id, Request $request)
    {
        $user = User::where('_id', $id)->first();

        if (!$user) {
            return response()->json(['accounts' => [], 'totalResults' => 0]);
        }

        $userList = json_decode($request['userList'], true);

        $filters = $request->input('filters') ?? [];
        if (!is_array($filters)) $filters = [];

        $page    = intval($request->input('page', 1));
        $perPage = intval($request->input('perPage', 20));
        $skip    = ($page - 1) * $perPage;
        $sortBy  = intval($filters['sortBy'] ?? 11);
        $view    = intval($filters['view'] ?? 1);
        $search  = (string) $request->input('searchAccountText', '');

        $usersIds = array_values(array_filter(array_merge(
            [(string)$user['_id']],
            array_map(fn($u) => (string)$u['_id'], $userList ?? [])
        )));

        $agents = isset($filters['agents']) && is_array($filters['agents'])
            ? $filters['agents']
            : [];

        $effectiveUsersIds = !empty($agents) ? $agents : $usersIds;

        $s = Str::ascii($search);
        $s = preg_replace('/\s+/', '', $s);

        $regex = new \MongoDB\BSON\Regex(preg_quote($s, '/'), 'i');

        $dates    = isset($filters['dates']) && is_array($filters['dates']) ? $filters['dates'] : [];
        $startUtc = !empty($dates['start'])
            ? new UTCDateTime(strtotime($dates['start'] . ' 00:00:00') * 1000)
            : null;
        $endUtc   = !empty($dates['end'])
            ? new UTCDateTime(strtotime($dates['end'] . ' 23:59:59') * 1000)
            : null;

        $sortMap = [
            0  => ['nameNormalized'    => 1],
            1  => ['nameNormalized'    => -1],
            2  => ['agentNormalized'   => 1],
            3  => ['agentNormalized'   => -1],
            4  => ['CIFNormalized'     => 1],
            5  => ['CIFNormalized'     => -1],
            6  => ['phone'             => 1],
            7  => ['phone'             => -1],
            8  => ['email'             => 1],
            9  => ['email'             => -1],
            10 => ['createdAtTemporal' => 1],
            11 => ['createdAtTemporal' => -1],
        ];
        $sort = $sortMap[$sortBy] ?? ['createdAtTemporal' => -1];

        // Helper para normalizar un campo: minúsculas + sin espacios + sin tildes
        $normalizeField = function (string $field) {
            $expr = ['$toLower' => ['$ifNull' => ["\$$field", '']]];
            $expr = ['$replaceAll' => ['input' => $expr, 'find' => ' ', 'replacement' => '']];

            $accents = [
                'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a',
                'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
                'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
                'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
                'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            ];

            foreach ($accents as $from => $to) {
                $expr = ['$replaceAll' => ['input' => $expr, 'find' => $from, 'replacement' => $to]];
            }

            return $expr;
        };

        // Pipeline base: match + campos calculados + filtros (sin $lookup)
        $basePipeline = [
            ['$match' => ['usersIds' => ['$in' => $effectiveUsersIds]]],
            ['$addFields' => [
                'createdAtTemporal' => [
                    '$dateFromString' => [
                        'dateString' => '$createdAt',
                        'format'     => '%Y-%m-%d %H:%M:%S',
                    ]
                ],
                '_idString'       => ['$toString' => '$_id'],
                'nameNormalized'  => $normalizeField('name'),
                'CIFNormalized'   => $normalizeField('CIF'),
                'phoneNormalized' => $normalizeField('phone'),
                'emailNormalized' => $normalizeField('email'),
            ]],
        ];

        $archived = array_values($user['accountsArchived'] ?? []);
        $basePipeline[] = ['$addFields' => ['archived' => ['$in' => ['$_idString', $archived]]]];
        if ($view === 1) {
            $basePipeline[] = ['$match' => ['_idString' => ['$nin' => $archived]]];
        } elseif ($view === 2) {
            $basePipeline[] = ['$match' => ['_idString' => ['$in'  => $archived]]];
        }

        if ($startUtc || $endUtc) {
            $dateCond = [];
            if ($startUtc) $dateCond['createdAtTemporal']['$gte'] = $startUtc;
            if ($endUtc)   $dateCond['createdAtTemporal']['$lte'] = $endUtc;
            $basePipeline[] = ['$match' => $dateCond];
        }

        if ($search !== '') {
            $basePipeline[] = ['$match' => ['$or' => [
                ['nameNormalized'  => ['$regex' => $regex]],
                ['CIFNormalized'   => ['$regex' => $regex]],
                ['phoneNormalized' => ['$regex' => $regex]],
                ['emailNormalized' => ['$regex' => $regex]],
            ]]];
        }

        // Count sin $lookup: mucho más rápido
        $countResult = Account::raw(fn($c) => $c->aggregate(
            array_merge($basePipeline, [['$count' => 'total']])
        )->toArray());
        $total = $countResult[0]['total'] ?? 0;

        // Stages del $lookup (solo se aplican a la página)
        $lookupStages = [
            ['$lookup' => [
                'from'     => 'users',
                'let'      => ['userIds' => '$usersIds'],
                'pipeline' => [[
                    '$match' => ['$expr' => ['$in' => [
                        '$_id',
                        ['$map' => [
                            'input' => '$$userIds',
                            'as'    => 'uid',
                            'in'    => ['$toObjectId' => '$$uid'],
                        ]],
                    ]]],
                ]],
                'as' => 'agent',
            ]],
            ['$addFields' => [
                'agentFullName' => ['$cond' => [
                    ['$gt' => [['$size' => '$agent'], 0]],
                    ['$concat' => [
                        ['$arrayElemAt' => ['$agent.firstName', 0]],
                        ' ',
                        ['$arrayElemAt' => ['$agent.lastName',  0]],
                    ]],
                    '',
                ]],
            ]],
            ['$addFields' => [
                'agentNormalized' => ['$toLower' => '$agentFullName'],
            ]],
        ];

        $projectStage = ['$project' => [
            '_id'                => 1,
            'name'               => 1,
            'CIF'                => 1,
            'email'              => 1,
            'phone'              => 1,
            'createdAt'          => 1,
            'agent'              => 1,
            'agentFullName'      => 1,
            'billingInfo'        => 1,
            'observations'       => 1,
            'NIFRepresentative'  => 1,
            'NameRepresentative' => 1,
            'archived'           => 1,
        ]];

        // Si se ordena por agente, el $lookup debe ir antes del $sort
        // En el resto de casos, se pagina primero y el $lookup solo toca la página
        $sortsByAgent = in_array($sortBy, [2, 3]);

        if ($sortsByAgent) {
            $dataPipeline = array_merge(
                $basePipeline,
                $lookupStages,
                [
                    ['$sort'  => $sort],
                    ['$skip'  => $skip],
                    ['$limit' => $perPage],
                    $projectStage,
                ]
            );
        } else {
            $dataPipeline = array_merge(
                $basePipeline,
                [
                    ['$sort'  => $sort],
                    ['$skip'  => $skip],
                    ['$limit' => $perPage],
                ],
                $lookupStages,
                [$projectStage]
            );
        }

        $accounts = Account::raw(fn($c) => $c->aggregate($dataPipeline));

        return response()->json([
            'accounts'     => $accounts,
            'totalResults' => $total,
        ]);
    }

    public function indexFilters($id, Request $request)
    {
        $user     = User::where('_id', $id)->first();
        $userList = json_decode($request->input('userList'), true);

        $usersIds = array_values(array_filter(array_merge(
            [(string)$user['_id']],
            array_map(fn($u) => (string)$u['_id'], $userList ?? [])
        )));

        $agentIds = Account::raw(fn($c) => $c->aggregate([
            ['$match' => ['usersIds' => ['$in' => $usersIds]]],
            ['$group' => ['_id' => ['$arrayElemAt' => ['$usersIds', 0]]]],
        ])->toArray());

        $agentIdList = array_values(array_filter(array_column($agentIds, '_id')));

        $users = User::whereIn('_id', $agentIdList)
            ->select('_id', 'firstName', 'lastName')
            ->get();

        $agentOptions = $users->map(fn($u) => [
            '_id'  => (string)$u->_id,
            'name' => trim($u->firstName . ' ' . $u->lastName),
        ])->sortBy('name')->values();

        return response()->json([
            'agents' => $agentOptions,
        ]);
    }

    public function indexWithoutPagination($id, Request $request)
    {

        $user = User::where('_id', $id)->first();

        //Saco cada uno de las cuentas que tenga en la columna usersIds la id del usuario con sesion iniciada
        $accountsToShow = Account::whereIn('usersIds', [$id])->get();


        $accounts = [
            'archived' => [],
            'notArchived' => []
        ];


        //Ahora basandome en el array de cuentas archivadas del usuario los separo en archivados y no archivados
        foreach ($accountsToShow as $account) {

            $isArchived = in_array($account['_id'], $user['accountsArchived']);

            if ($isArchived)
                array_push($accounts['archived'], $account);
            else
                array_push($accounts['notArchived'], $account);
        }

        //A cada cuenta le meto su principal Account si la tiene
        foreach ($accounts['archived'] as $archivedAccount) {
            if ($archivedAccount['principalAcc'])
                $archivedAccount['principalAcc'] = Account::where('_id', $archivedAccount['principalAcc'])->first();
        }

        foreach ($accounts['notArchived'] as $notArchivedAccount) {
            if ($notArchivedAccount['principalAcc'])
                $notArchivedAccount['principalAcc'] = Account::where('_id', $notArchivedAccount['principalAcc'])->first();
        }


        //AHORA HAGO LO MISMO PARA LOS USUARIOS QUE TENGO POR DEBAJO
        $userList = json_decode($request['userList'], true);

        if (is_array($userList)) {

            //Para cada uno de los usuarios
            foreach ($userList as $userInd => $userNow) {

                //Saco las cuentas del usuario
                $accountsToShowUser = Account::whereIn('usersIds', [$userNow['_id']])->get();

                $accountsUsers = [
                    'archived' => [],
                    'notArchived' => []
                ];

                //Saco los ids
                foreach ($accountsToShowUser as $account) {

                    $isArchived = in_array($account['_id'], $userNow['accountsArchived'] ?? []);

                    if ($isArchived)
                        array_push($accountsUsers['archived'], $account);
                    else
                        array_push($accountsUsers['notArchived'], $account);
                }

                //A cada cuenta le meto su principal Account si la tiene
                foreach ($accountsUsers['archived'] as $archivedAccount) {
                    if ($archivedAccount['principalAcc'])
                        $archivedAccount['principalAcc'] = Account::where('_id', $archivedAccount['principalAcc'])->first();
                }

                foreach ($accountsUsers['notArchived'] as $notArchivedAccount) {
                    if ($notArchivedAccount['principalAcc'])
                        $notArchivedAccount['principalAcc'] = Account::where('_id', $notArchivedAccount['principalAcc'])->first();
                }


                //Lo meto al array primero
                $accounts['notArchived'] = [...$accounts['notArchived'], ...$accountsUsers['notArchived']];
                $accounts['archived'] = [...$accounts['archived'], ...$accountsUsers['archived']];
            }
        }


        //A cada una de las cuentas le meto su información relacionada ( para archivados y no archivados )
        if ($accounts['notArchived'] && count($accounts['notArchived']) > 0) {

            foreach ($accounts['notArchived'] as $account) {

                //LE METO LOS CONTACTOS RELACIONADOS
                $account['contacts'] = [];

                $contactsToShow = Contact::whereIn('accounts', [$account['_id']])->get();

                $account['contacts'] = [...$account['contacts'], ...$contactsToShow];

                //LE METO LAS OPORTUNIDADES RELACIONADAS
                $account['opportunities'] = [];

                $opportunitiesToShow = Opportunity::where('account', $account['_id'])->get();

                $account['opportunities'] = [...$account['opportunities'], ...$opportunitiesToShow];


                //LE METO LAS TAREAS RELACIONADAS
                $account['tasks'] = [];

                $tasksToShow = Task::where('account', $account['_id'])->get();

                $account['tasks'] = [...$account['tasks'], ...$tasksToShow];


                //LE METO LOS EVENTOS RELACIONADOS
                $account['events'] = [];

                $eventsToShow = Event::where('account', $account['_id'])->get();

                $account['events'] = [...$account['events'], ...$eventsToShow];

                //Le meto los datos del usuario que lo ha creado
                $account['createdBy'] = User::where('_id', $account['createdBy'])->first();
            }
        }

        if ($accounts['archived'] && count($accounts['archived']) > 0) {

            foreach ($accounts['archived'] as $account) {

                //LE METO LOS CONTACTOS RELACIONADOS
                $account['contacts'] = [];

                $contactsToShow = Contact::whereIn('accounts', [$account['_id']])->get();

                $account['contacts'] = [...$account['contacts'], ...$contactsToShow];

                //LE METO LAS OPORTUNIDADES RELACIONADAS
                $account['opportunities'] = [];

                $opportunitiesToShow = Opportunity::where('account', $account['_id'])->get();

                $account['opportunities'] = [...$account['opportunities'], ...$opportunitiesToShow];


                //LE METO LAS TAREAS RELACIONADAS
                $account['tasks'] = [];

                $tasksToShow = Task::where('account', $account['_id'])->get();

                $account['tasks'] = [...$account['tasks'], ...$tasksToShow];


                //LE METO LOS EVENTOS RELACIONADOS
                $account['events'] = [];

                $eventsToShow = Event::where('account', $account['_id'])->get();

                $account['events'] = [...$account['events'], ...$eventsToShow];

                //Le meto los datos del usuario que lo ha creado
                $account['createdBy'] = User::where('_id', $account['createdBy'])->first();
            }
        }


        return response()->json(['accounts' => $accounts], 200);
    }

    //funcion para sacar una cuenta en particular
    public function show($id)
    {

        $account = Account::where('_id', $id)->first()->load('orders');

        //Saco tb los datos relacionados con la cuenta

        //LE METO LOS CONTACTOS RELACIONADOS
        $account['contacts'] = Contact::whereIn('accounts', [$account['_id']])->get()->toArray();


        //LE METO LAS OPORTUNIDADES RELACIONADAS
        $account['opportunities'] = Opportunity::where('account', $account['_id'])->get()->toArray();


        //LE METO LAS TAREAS RELACIONADAS
        $account['tasks'] = Task::where('account', $account['_id'])->get()->toArray();


        //LE METO LOS EVENTOS RELACIONADOS
        $account['events'] = Event::where('account', $account['_id'])->get()->toArray();


        //LE METO LOS CORREOS RELACIONADOS ( filtrar por subdominio )
        $account['mails'] = Email::where('recipients.email', $account['email'])->get()->toArray();

        //Le meto los datos del usuario que lo ha creado
        $account['createdBy'] = User::where('_id', $account['createdBy'])->first();

        return response()->json(['account' => $account], 200);
    }

    //funcion para eliminar una cuenta
    public function deleteAccount($id)
    {

        //Lo saco de el array de cuentas del usuario
        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $accounts = $userToModify->accounts;

        unset($accounts[$id]);

        $userToModify->accounts = $accounts;

        $userToModify->save();

        //Compruebo si hay algún contacto que tenga relacionada
        $contacts = Contact::whereIn('accounts', [$id])->get();

        if ($contacts && count($contacts) > 0) {

            //Para cada uno
            foreach ($contacts as $contactInd => $contact) {

                //Saco del array de cuentas la que se va a borrar
                $contactAccounts = $contact['accounts'];

                $contactAccounts = array_diff($contactAccounts, [$id]);

                $contactAccounts = json_encode($contactAccounts);

                $contactAccounts = json_decode($contactAccounts, true);

                $contact['accounts'] = $contactAccounts;

                $contact->save();
            }
        }


        //Compruebo si esta como cuenta principal dentro de otra cuenta
        $accounts = Account::whereIn('principalAcc', [$id])->get();

        if ($accounts && count($accounts) > 0) {

            //Para cada una de las cuentas
            foreach ($accounts as $accountInd => $account) {

                //compruebo el id
                if ($account['principalAcc'] === $id)
                    $account['principalAcc'] = '';

                $account->save();
            }
        }


        //Compruebo si esta relacionada con alguna oportunidad
        $opportunities = Opportunity::whereIn('account', [$id])->get();

        if ($opportunities && count($opportunities) > 0) {

            //Para cada una de las cuentas
            foreach ($opportunities as $opportunityInd => $opportunity) {

                //compruebo el id
                if ($opportunity['account'] === $id)
                    $opportunity['account'] = '';

                $opportunity->save();
            }
        }


        //Compruebo si esta relacionada con alguna tarea
        $tasks = Task::whereIn('account', [$id])->get();

        if ($tasks && count($tasks) > 0) {

            //Para cada una de las cuentas
            foreach ($tasks as $taskInd => $task) {

                //compruebo el id
                if ($task['account'] === $id)
                    $task['account'] = '';

                $task->save();
            }
        }


        //Compruebo si esta relacionada con algún evento del calendario
        $events = Event::whereIn('account', [$id])->get();

        if ($events && count($events) > 0) {

            //Para cada una de las cuentas
            foreach ($events as $eventInd => $event) {

                //compruebo el id
                if ($event['account'] === $id)
                    $event['account'] = '';

                $event->save();
            }
        }


        $ordersToDelete = Order::where('account', (string) $id)->get();

        if (count($ordersToDelete) > 0) {
            foreach ($ordersToDelete as $orderToDelete) {
                //Log borrar contrato
                AuditLogService::createOrDeleteOrder($orderToDelete, Auth::user(), 'delete');
                $cupsDeleted = $orderToDelete->CUPS ?? '';
                $orderToDelete->delete();

                //Quito el check de las comparativas si ya no queda ningún contrato con este CUPS
                \App\Services\OrderService::unmarkComparativesIfNoContract($cupsDeleted, Auth::user());
            }
        }



        //Borro la cuenta de la bbdd
        $account = Account::find($id);
        //Log borrar cuenta
        AuditLogService::createOrDeleteAccount($account, Auth::user(), 'delete');
        $account->delete();


        return response()->json(['message' => 'La cuenta ha sido eliminada correctamente'], 200);
    }

    //funcion para eliminar todos las cuentas seleccionadas
    public function deleteAllSelectedAccounts(Request $request)
    {

        $idsToRemove = $request['idsToRemove'];

        //Lo saco de el array de cuentas del usuario
        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $accountsArchived = $userToModify->accountsArchived;

        //Para cada uno compruebo si esta en el array de archivados, si esta lo saco y este o no lo borro
        foreach ($idsToRemove as $id) {

            if (in_array($id, $accountsArchived))
                unset($accountsArchived[$id]);

            //Borro la cuenta de la bbdd
            Account::destroy($id);


            //Compruebo si hay algún contacto que tenga relacionada
            $contacts = Contact::whereIn('accounts', [$id])->get();

            if ($contacts && count($contacts) > 0) {

                //Para cada uno
                foreach ($contacts as $contactInd => $contact) {

                    //Saco del array de cuentas la que se va a borrar
                    $contactAccounts = $contact['accounts'];

                    $contactAccounts = array_diff($contactAccounts, [$id]);

                    $contactAccounts = json_encode($contactAccounts);

                    $contactAccounts = json_decode($contactAccounts, true);

                    $contact['accounts'] = $contactAccounts;

                    $contact->save();
                }
            }

            //Compruebo si esta como cuenta principal dentro de otra cuenta
            $accounts = Account::whereIn('principalAcc', [$id])->get();

            if ($accounts && count($accounts) > 0) {

                //Para cada una de las cuentas
                foreach ($accounts as $accountInd => $account) {

                    //compruebo el id
                    if ($account['principalAcc'] === $id)
                        $account['principalAcc'] = '';

                    $account->save();
                }
            }


            //Compruebo si esta relacionada con alguna oportunidad
            $opportunities = Opportunity::whereIn('account', [$id])->get();

            if ($opportunities && count($opportunities) > 0) {

                //Para cada una de las cuentas
                foreach ($opportunities as $opportunityInd => $opportunity) {

                    //compruebo el id
                    if ($opportunity['account'] === $id)
                        $opportunity['account'] = '';

                    $opportunity->save();
                }
            }


            //Compruebo si esta relacionada con alguna tarea
            $tasks = Task::whereIn('account', [$id])->get();

            if ($tasks && count($tasks) > 0) {

                //Para cada una de las cuentas
                foreach ($tasks as $taskInd => $task) {

                    //compruebo el id
                    if ($task['account'] === $id)
                        $task['account'] = '';

                    $task->save();
                }
            }


            //Compruebo si esta relacionada con algún evento del calendario
            $events = Event::whereIn('account', [$id])->get();

            if ($events && count($events) > 0) {

                //Para cada una de las cuentas
                foreach ($events as $eventInd => $event) {

                    //compruebo el id
                    if ($event['account'] === $id)
                        $event['account'] = '';

                    $event->save();
                }
            }
        }

        $userToModify->accountsArchived = $accountsArchived;

        $userToModify->save();

        return response()->json(['message' => 'Las cuentas han sido eliminadas correctamente'], 200);
    }

    //funcion para archivar una cuenta
    public function toggleArchiveAccount($id)
    {

        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $accountsArchived = $userToModify->accountsArchived;


        if (in_array($id, $accountsArchived)) {
            $key = array_search($id, $accountsArchived);
            unset($accountsArchived[$key]);
        } else {
            array_push($accountsArchived, $id);
        }


        $userToModify->accountsArchived = $accountsArchived;

        $userToModify->save();

        return response()->json(['message' => 'La archivación ha sido alterada'], 200);
    }

    //funcion para archivar todas las cuentas seleccionados
    public function toggleArchiveSelectedAccounts(Request $request)
    {

        $accountsToToggle = $request['accountsToToggle'];

        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $accountsArchived = $userToModify->accountsArchived;

        //si es para archivar
        foreach ($accountsToToggle as $account) {
            if (!$account['archived']) {
                $accountsArchived[] = $account['_id'];
            } else {

                $key = array_search($account['_id'], $accountsArchived);

                unset($accountsArchived[$key]);
            }
        }

        $userToModify->accountsArchived = $accountsArchived;

        $userToModify->save();

        return response()->json(['message' => 'La archivación ha sido alterada'], 200);
    }

    //funcion para obtener todas las cuentas relacionadas
    public function getRelatedAccounts($id, Request $request)
    {
        // Crear un array con todos los IDs de usuario a buscar
        $userIds = [$id];

        // Decodificar la lista de usuarios adicionales
        $userList = json_decode($request['userList'], true);

        // Agregar los IDs de los usuarios adicionales al array
        if (is_array($userList)) {
            foreach ($userList as $user) {
                $userIds[] = $user['_id'];
            }
        }

        // Realizar una sola consulta con todos los IDs
        $relatedAccounts = Account::whereIn('usersIds', $userIds)->get();

        return response()->json(['relatedAccounts' => $relatedAccounts], 200);

    }

    public function getDatadisAccounts($id, Request $request){
        // Crear un array con todos los IDs de usuario a buscar
        $userIds = [$id];

        // Decodificar la lista de usuarios adicionales
        $userList = json_decode($request['userList'], true);

        // Agregar los IDs de los usuarios adicionales al array
        if (is_array($userList)) {
            foreach ($userList as $user) {
                $userIds[] = $user['_id'];
            }
        }

        //Obtengo los CUPS monitorizados por Datadis
        $cups = json_decode($request['cups']);

        //Obtengo las cuentas que tengan un id de usuario válido y el CUPS aparezca en monitorizados
        $orders = Order::whereIn('usersIds', $userIds)->WhereIn('CUPS', $cups)->pluck('account')->unique()->toArray();
        $accounts = Account::whereIn('_id',$orders)->get();

        return response()->json(['accounts' => $accounts], 200);
    }

    public function checkByUserAndIdentifier($userId, Request $request)
    {
        $identifier = trim($request->get('identifier'));
        $found = Account::whereIn('usersIds', [$userId])
            ->where('CIF', $identifier)
            ->first();
        if ($found) {
            return response()->json([
                'exists' => true,
                'accountId' => (string) $found->_id
            ], 200);
        }
        return response()->json(['exists' => false], 200);
    }

    //función para comprobar si existe un CIF
    public function checkCIF(Request $request)
    {

        $account = $request['account'];

        if (isset($account['_id'])) {
            $accountGetted = Account::where('CIF', $account['CIF'])
                ->where('id', '!=', $account['_id'])
                ->first();
        } else {
            $accountGetted = Account::where('CIF', $account['CIF'])->first();
        }

        return response()->json(['account' => $accountGetted], 200);
    }

    public function dumpAccounts(Request $request, OrderService $orderService)
    {

        $file = $request->file('file');
        $userSubdomain = json_decode($request->input('userSubdomain'), true);
        $userList = json_decode($request->input('userList'), true);
        array_push($userList, Auth::user()->toArray());

        if (!$file || !$file->isValid()) {
            return response()->json(['error' => 'No se ha recibido un archivo válido.'], 400);
        }

        // Para el informe
        $skippedRows = [];
        $insertedCount = 0;

        try {
            $excel = Excel::toArray([], $file);

            //Compruebo que el excel tiene datos
            if (empty($excel[0]) || !is_array($excel[0]) || count($excel[0]) === 0) {
                return response()->json(['error' => 'No se pudieron obtener datos válidos del archivo Excel.'], 400);
            }

            //Comprobar que es la plantilla
            if (!isset($excel[0][0][0]) || $excel[0][0][0] !== "ZocoCuentas") {
                return response()->json(['error' => 'Por favor usa la plantilla de cuentas.'], 400);
            }

            // Datos sin las 2 filas de cabecera
            $excelData = array_slice($excel[0], 2);

            // Obtengo los CIF ya existentes para evitar duplicados
            // Opción A (si usersIds es un array JSON):
            try {
                $accounts = Account::whereJsonContains('usersIds', Auth::id())
                    ->pluck('CIF')->map(fn($c) => (string)$c)->toArray();
            } catch (\Throwable $t) {
                // Opción B (fallback): todos los CIF
                $accounts = [];
            }

            // Mapa de campos obligatorios (índice => nombre) para mensajes
            $requiredMap = [
                0 => 'name',
                1 => 'CIF',
                2 => 'phone',
                3 => 'email',
                4 => 'community',
                5 => 'province',
                6 => 'locality',
                7 => 'address',
                8 => 'zipCode',
            ];

            // Helpers de columnas opcionales por subdominio
            $subId = $userSubdomain['_id'] ?? null;
            $optionalFor = [
                // Para estos subdominios las columnas 2 y 3 (phone, email) son opcionales
                'phonesEmailsOptional' => [
                    '67f67098c320e515ce0687b2',
                    '6834407af876e6dfda02bd52',
                    '68343f94db5f34c767000572',
                    '683440e3c920fae42309f3e2',
                ],
                // Opcionales para Doive: 2..8
                'doiveAllOptional' => [
                    '683d658761231bd1080b4802',
                ],
            ];

            $isPhonesEmailsOptional = $subId && in_array($subId, $optionalFor['phonesEmailsOptional'], true);
            $isDoiveOptional = $subId && in_array($subId, $optionalFor['doiveAllOptional'], true);
            $processedCount = 0;

            foreach ($excelData as $index => $row) {
                $isEmptyRow = collect($row)->every(function ($cell) {
                    return $cell === null || trim((string) $cell) === '';
                });

                if ($isEmptyRow) {
                    continue;
                }

                $processedCount++;

                $rowNumber = $index + 3;

                // Normalizo fila a array
                if (!is_array($row)) {
                    $skippedRows[] = [
                        'rowNumber' => $rowNumber,
                        'reason'    => 'fila_invalida',
                        'details'   => 'La fila no es un array de valores.',
                    ];
                    continue;
                }

                // 1) Validación de campos obligatorios (respetando opcionales por subdominio)
                $missingField = null;

                if (!$isDoiveOptional) {
                    for ($i = 0; $i <= 8; $i++) {
                        if ($isPhonesEmailsOptional && ($i === 2 || $i === 3)) {
                            continue; // columnas 2 y 3 opcionales para ciertos subdominios
                        }
                        if (!array_key_exists($i, $row) || $row[$i] === null || $row[$i] === '') {
                            $missingField = $requiredMap[$i] ?? "col_$i";
                            break;
                        }
                    }
                }
                // Doive: 2..8 opcionales, pero 0 y 1 (name, CIF) siguen siendo clave
                if ($isDoiveOptional) {
                    foreach ([0, 1] as $i) {
                        if (!array_key_exists($i, $row) || $row[$i] === null || $row[$i] === '') {
                            $missingField = $requiredMap[$i] ?? "col_$i";
                            break;
                        }
                    }
                }

                if ($missingField) {
                    $skippedRows[] = [
                        'rowNumber' => $rowNumber,
                        'reason'    => 'campo_obligatorio_faltante',
                        'details'   => "Falta el campo obligatorio: {$missingField}",
                        'row'       => $row,
                    ];
                    continue;
                }

                // 2) Propietario (email en col 14) — admite varios correos
                $validOwners = [];

                if (isset($row[14]) && trim($row[14]) !== '') {
                    $ownerRaw = (string)$row[14];

                    // Divide por coma, punto y coma o saltos de línea
                    $candidates = preg_split('/[,\;\r\n]+/u', $ownerRaw);

                    foreach ($candidates as $c) {
                        $email = mb_strtolower(trim($c));
                        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }

                        // Verifica que está en la lista permitida
                        $emailsLista = array_column($userList ?? [], 'email');
                        $emailsListaLower = array_map(fn($e) => mb_strtolower(trim((string)$e)), $emailsLista);

                        if (!in_array($email, $emailsListaLower, true)) {
                            continue;
                        }

                        // Búsqueda case-insensitive en Mongo
                        $regex = new \MongoDB\BSON\Regex('^' . preg_quote($email, '/') . '$', 'i');
                        $user  = User::where('email', 'regexp', $regex)->first();

                        if ($user) {
                            $validOwners[] = (string)$user->_id; // guardamos el ObjectId en string
                        }
                    }

                    if (empty($validOwners)) {
                        $skippedRows[] = [
                            'rowNumber' => $rowNumber,
                            'reason'    => 'propietarios_no_validos',
                            'details'   => "No se encontró ningún usuario válido en '{$ownerRaw}'.",
                            'row'       => $row,
                        ];
                        continue;
                    }
                }


                // 3) Duplicados por CIF
                $rowCif = isset($row[1]) ? (string)$row[1] : '';

                $duplicateExists = Account::where('CIF', $rowCif)
                    ->whereIn('usersIds', $validOwners)
                    ->exists();

                if ($rowCif === '' || $duplicateExists) {
                    $skippedRows[] = [
                        'rowNumber' => $rowNumber,
                        'reason'    => 'duplicado_cif',
                        'details'   => "Ya existe una cuenta con el CIF/NIF {$rowCif}.",
                        'row'       => $row,
                    ];
                    continue;
                }

                // --- Normalizar código postal ---
                $zipCode = (string)($row[8] ?? '');
                if ($zipCode !== '' && strlen($zipCode) === 4 && ctype_digit($zipCode)) {
                    $zipCode = '0' . $zipCode;
                }

                // 4) Construcción de la cuenta
                $account = [
                    "name"            => $row[0] ?? '',
                    "accType"         => "",
                    "sector"          => "",
                    "CIF"             => (string)($row[1] ?? ''),
                    "origin"          => "",
                    "phone"           => isset($row[2]) ? (string)$row[2] : '',
                    "landLinePhone"   => $row[10] ?? '',
                    "website"         => $row[11] ?? '',
                    "email"           => $row[3] ?? '',
                    "observations"    => $row[9] ?? '',
                    "principalAcc"    => "",
                    "billingInfo"     => [
                        "community" => $row[4] ?? '',
                        "province"  => $row[5] ?? '',
                        "locality"  => $row[6] ?? '',
                        "address"   => $row[7] ?? '',
                        "zipCode"   => $zipCode,
                    ],
                    "customFields"    => [],
                    "orders"          => [],
                    "opportunity"     => "",
                    "usersIds"        => $validOwners
                ];


                if (!empty($row[12])) {
                    $account['NIFRepresentative'] = $row[12];
                }
                if (!empty($row[13])) {
                    $account['NameRepresentative'] = $row[13];
                }

                // 5) Llamada a store() capturando errores por fila
                try {
                    $fakeRequest = Request::create('/dummy-url', 'POST', [
                        'account'    => json_encode($account),
                        'userLogged' => Auth::user(),
                        'userSubdomain' => json_encode($userSubdomain)
                    ]);

                    $this->store($fakeRequest, $orderService,);

                    // Añadimos CIF a la lista para evitar duplicados dentro del mismo batch
                    $accounts[] = $rowCif;
                    $insertedCount++;
                } catch (\Throwable $e) {


                    $skippedRows[] = [
                        'rowNumber' => $rowNumber,
                        'reason'    => 'error_store',
                        'details'   => 'Fallo al guardar la cuenta: ' . $e->getMessage(),
                        'row'       => $row,
                    ];
                    continue;
                }
            }

            return response()->json([
                'summary' => [
                    'fileName'       => $file->getClientOriginalName(),
                    'inserted'       => $insertedCount,
                    'skipped'        => count($skippedRows),
                    'totalProcessed' => $processedCount,
                ],
                'skippedRows' => $skippedRows,
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar el archivo Excel: ' . $e->getMessage()], 500);
        }

    }

    public function exportAccountsTemplate(Request $request)
    {
        // 1) Entradas
        $userId   = data_get($request->user(), '_id') ?? $request->input('userId');
        $user     = User::where('_id', $userId)->first();
        $userList = json_decode($request->input('userList'), true) ?? [];
        $filters  = $request->input('filters', []);
        $sortType = (int) $request->input('sortType', 0);
        $search   = (string) $request->input('searchAccountText', '');
        $view    = intval($filters['view'] ?? 1);

        if (!$user) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // 2) Cuentas como en index() pero sin paginar
        $accounts = $this->getAccountsForExportQuery($user, $userList, $filters, $sortType, $search, $view);

        // 3) Localizar plantilla
        $candidates = [
            public_path('/assets/templates/accounts.xlsx'),
            public_path('/assets/templates/accounts.xls'),
            base_path('public/assets/templates/accounts.xlsx'),
            base_path('public/assets/templates/accounts.xls'),
        ];
        $tplPath = collect($candidates)->first(fn($p) => is_file($p));
        if (!$tplPath) {
            return response()->json(['error' => 'No se encontró la plantilla accounts.xls/xlsx'], 404);
        }

        // 4) Cargar plantilla
        $reader = IOFactory::createReaderForFile($tplPath);
        if (method_exists($reader, 'setReadDataOnly')) $reader->setReadDataOnly(false);
        if (method_exists($reader, 'setIncludeCharts')) $reader->setIncludeCharts(true);
        $spreadsheet = $reader->load($tplPath);
        $sheet = $spreadsheet->getActiveSheet();

        // 5) Preparar duplicado de estilo de la fila plantilla (fila 3)
        $templateRowIdx   = 3;
        $lastColLetter    = 'O'; // ahora hay una columna extra para el propietario
        $templateRowStyle = $sheet->getStyle("A{$templateRowIdx}:{$lastColLetter}{$templateRowIdx}");
        $templateRowHeight = $sheet->getRowDimension($templateRowIdx)->getRowHeight();

        // 6) Volcar datos
        $row = $templateRowIdx;

        foreach ($accounts as $acc) {
            if ($row > $templateRowIdx) {
                $sheet->duplicateStyle($templateRowStyle, "A{$row}:{$lastColLetter}{$row}");
                if (!is_null($templateRowHeight)) {
                    $sheet->getRowDimension($row)->setRowHeight($templateRowHeight);
                }
            }

            // Buscar propietario principal (primer userId del array)
            $ownerEmail = '';
            if (!empty($acc['agent'])) {
                $firstAgent = $acc['agent'][0] ?? null;


                if (!empty($firstAgent['email'])) {
                    $ownerEmail = $firstAgent['email'];
                }
            }

            // Asignar columnas A..N como ya tienes
            $sheet->setCellValue("A{$row}", $acc['name'] ?? '');
            $sheet->setCellValue("B{$row}", $acc['CIF'] ?? '');
            $sheet->setCellValue("C{$row}", $acc['phone'] ?? '');
            $sheet->setCellValue("D{$row}", $acc['email'] ?? '');
            $sheet->setCellValue("E{$row}", data_get($acc, 'billingInfo.community', '') ?? '');
            $sheet->setCellValue("F{$row}", data_get($acc, 'billingInfo.province', '') ?? '');
            $sheet->setCellValue("G{$row}", data_get($acc, 'billingInfo.locality', '') ?? '');
            $sheet->setCellValue("H{$row}", data_get($acc, 'billingInfo.address', '') ?? '');
            $sheet->setCellValue("I{$row}", data_get($acc, 'billingInfo.zipCode', '') ?? '');
            $sheet->setCellValue("J{$row}", $acc['observations'] ?? '');
            $sheet->setCellValue("K{$row}", $acc['landLinePhone'] ?? '');
            $sheet->setCellValue("L{$row}", $acc['website'] ?? '');
            $sheet->setCellValue("M{$row}", $acc['NIFRepresentative'] ?? '');
            $sheet->setCellValue("N{$row}", $acc['NameRepresentative'] ?? '');
            $sheet->setCellValue("O{$row}", $ownerEmail); // nueva columna Propietario

            $row++;
        }

        // 7) Exportar
        $filenameBase = 'Cuentas_' . date('Y-m-d');
        $writer = new XlsxWriter($spreadsheet);
        $filename    = $filenameBase . '.xlsx';
        $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return Response::make($content, 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function getAccountsForExportQuery($user, array $userList, array $filters, int $sortType, string $searchText, int $view)
    {
        // === Usuarios visibles ===
        $usersIds = array_values(array_filter(array_merge(
            [(string)$user['_id']],
            array_map(fn($u) => (string)$u['_id'], $userList ?? [])
        )));

        // === Normaliza búsqueda como en index() ===
        $s = mb_strtolower($searchText, 'UTF-8');
        $s = preg_replace('/\s+/', '', $s);
        $s = preg_replace('/[áàäâãåÁÀÄÂÃÅ]/u', 'a', $s);
        $s = preg_replace('/[éèëêÉÈËÊ]/u', 'e', $s);
        $s = preg_replace('/[íìïîÍÌÏÎ]/u', 'i', $s);
        $s = preg_replace('/[óòöôõÓÒÖÔÕ]/u', 'o', $s);
        $s = preg_replace('/[úùüûÚÙÜÛ]/u', 'u', $s);
        $regex = new \MongoDB\BSON\Regex(preg_quote($s), 'i');

        // === Rango de fechas (createdAt) ===
        $start = data_get($filters, 'dates.start');
        $end   = data_get($filters, 'dates.end');
        $startUtc = $start ? new UTCDateTime(strtotime(date('Y-m-d 00:00:00', strtotime($start))) * 1000) : null;
        $endUtc   = $end   ? new UTCDateTime(strtotime(date('Y-m-d 23:59:59', strtotime($end)))   * 1000) : null;

        // === Orden ===
        $sort = ['nameNormalized' => 1];
        switch ($sortType) {
            case 1:  $sort = ['nameNormalized' => -1]; break;
            case 2:  $sort = ['agentNormalized' => 1]; break;
            case 3:  $sort = ['agentNormalized' => -1]; break;
            case 4:  $sort = ['CIFNormalized' => 1]; break;
            case 5:  $sort = ['CIFNormalized' => -1]; break;
            case 6:  $sort = ['phone' => 1]; break;
            case 7:  $sort = ['phone' => -1]; break;
            case 8:  $sort = ['email' => 1]; break;
            case 9:  $sort = ['email' => -1]; break;
            case 10: $sort = ['createdAtTemporal' => 1]; break;
            case 11: $sort = ['createdAtTemporal' => -1]; break;
        }

        $pipeline = [
            ['$match' => ['usersIds' => ['$in' => $usersIds]]],
            ['$addFields' => [
                'createdAtTemporal' => [
                    '$dateFromString' => ['dateString' => '$createdAt','format' => '%Y-%m-%d %H:%M:%S']
                ],
                '_idString' => ['$toString' => '$_id'],
                'nameNormalized' => ['$toLower' => [
                    '$replaceAll' => ['input' => [
                        '$replaceAll' => ['input' => [
                            '$replaceAll' => ['input' => [
                                '$replaceAll' => ['input' => [
                                    '$replaceAll' => ['input' => '$name','find' => 'á','replacement' => 'a']
                                ],'find' => 'é','replacement' => 'e']
                            ],'find' => 'í','replacement' => 'i']
                        ],'find' => 'ó','replacement' => 'o']
                    ],'find' => 'ú','replacement' => 'u']
                ]],
                'CIFNormalized' => ['$toLower' => ['$replaceAll' => ['input' => '$CIF','find' => ' ','replacement' => '']]],
                'phoneNormalized' => ['$toLower' => ['$replaceAll' => ['input' => '$phone','find' => ' ','replacement' => '']]],
                'emailNormalized' => ['$toLower' => ['$replaceAll' => ['input' => '$email','find' => ' ','replacement' => '']]],
            ]],
        ];

        // Vista (agenda/archivadas)
        if ($view === 1) {
            $pipeline[] = ['$match' => ['_idString' => ['$nin' => array_values($user['accountsArchived'] ?? [])]]];
        } elseif ($view === 2) {
            $pipeline[] = ['$match' => ['_idString' => ['$in' => array_values($user['accountsArchived'] ?? [])]]];
        }

        // Fechas
        if ($startUtc || $endUtc) {
            $dateCond = [];
            if ($startUtc) $dateCond['createdAtTemporal']['$gte'] = $startUtc;
            if ($endUtc)   $dateCond['createdAtTemporal']['$lte'] = $endUtc;
            $pipeline[] = ['$match' => $dateCond];
        }

        // Filtro de agentes (si llega)
        $agentsFilter = $filters['agents'] ?? [];
        if (!empty($agentsFilter)) {
            $pipeline[] = ['$match' => ['usersIds' => ['$in' => $agentsFilter]]];
        }

        // Búsqueda de texto
        if ($searchText !== '') {
            $pipeline[] = ['$match' => [
                '$or' => [
                    ['nameNormalized' => ['$regex' => $regex]],
                    ['CIFNormalized'  => ['$regex' => $regex]],
                    ['phoneNormalized'=> ['$regex' => $regex]],
                    ['emailNormalized'=> ['$regex' => $regex]],
                ]
            ]];
        }

        // Lookup de agente
        $pipeline[] = [
            '$lookup' => [
                'from' => 'users',
                'let' => ['userIds' => '$usersIds'],
                'pipeline' => [[
                    '$match' => [
                        '$expr' => [
                            '$in' => ['$_id', [
                                '$map' => [
                                    'input' => '$$userIds',
                                    'as' => 'uid',
                                    'in' => ['$toObjectId' => '$$uid']
                                ]
                            ]]
                        ]
                    ]
                ]],
                'as' => 'agent'
            ]
        ];

        // Nombre completo del agente (primer agente)
        $pipeline[] = [
            '$addFields' => [
                'agentFullName' => [
                    '$cond' => [
                        ['$gt' => [['$size' => '$agent'], 0]],
                        ['$concat' => [
                            ['$arrayElemAt' => ['$agent.firstName', 0]],
                            ' ',
                            ['$arrayElemAt' => ['$agent.lastName', 0]],
                        ]],
                        ''
                    ]
                ]
            ]
        ];

        // Normalización para ordenar por agente si toca
        $pipeline[] = [
            '$addFields' => [
                'agentNormalized' => ['$toLower' => '$agentFullName']
            ]
        ];

        // Orden y fields que realmente exportas
    $pipeline[] = ['$sort' => array_merge(['createdAtTemporal' => -1, '_id' => -1], $sort)];
        $pipeline[] = ['$project' => [
            '_id' => 1,
            'name' => 1,
            'CIF' => 1,
            'email' => 1,
            'phone' => 1,
            'createdAt' => 1,
            'billingInfo' => 1,
            'observations' => 1,
            'NameRepresentative' => 1,
            'NIFRepresentative' => 1,
            'agent' => 1,
            'agentFullName' => 1
        ]];

        $cursor = Account::raw(fn($c) => $c->aggregate($pipeline, ['allowDiskUse' => true]));
        return iterator_to_array($cursor, false);
    }


    public function getCIFByCUPS(Request $request)
    {
        $cups = strtoupper(trim($request->input('cups')));

        $order = Order::where('CUPS', $cups)->first();

        if (!$order)
            return response()->json(['message' => 'No existe el contrato'], 400);


        $attributes = $order->getAttributes();

        $accountId = $attributes['account'] ?? null;

        if (!$accountId)
            return response()->json(['message' => 'Contrato sin cuenta'], 400);


        $account = Account::where('_id', new ObjectId($accountId))->first();

        if (!$account)
            return response()->json(['message' => 'Cuenta no encontrada'], 400);

        $cif = $account->CIF ?? null;

        return response()->json(['cif' => $cif], 200);
    }
}
