<?php

namespace App\Http\Controllers;

use App\Http\Models\Account;
use App\Http\Models\Enterprise;
use App\Http\Models\Marketer;
use App\Http\Models\User;
use App\Http\Resources\MarketerResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use MongoDB\BSON\ObjectId;

class MarketerController extends Controller
{
    //guardar comercializadoras
    public function store(Request $request)
    {

        $marketer = json_decode($request['marketer'], true);

        $name = null;
        //compruebo si tiene imagen y si tiene la guardo
        if ($request['image'] && !is_string($request['image'])) {
            $name = $marketer['name'] . "-" . time() . "." . $request['image']->getClientOriginalExtension();
            Storage::disk('marketer')->put($name, file_get_contents($request['image']));
        }

        // Redondear precios antes de guardar
        $marketer = $this->roundMarketerPrices($marketer);

        //Compruebo si las tarifas tienen id, si no le asigno una
        foreach ($marketer['fees']['electricity'] as &$fee) {
            // Comprobar si el objeto tiene la propiedad 'id' y si es null
            if (!isset($fee['id'])) {
                $fee['id'] = new ObjectId();
            }
        };
        foreach ($marketer['fees']['gas'] as &$fee) {
            // Comprobar si el objeto tiene la propiedad 'id' y si es null
            if (!isset($fee['id'])) {
                $fee['id'] = new ObjectId();
            }
        };

        foreach ($marketer['extras'] as &$extra) {
            $extra['id'] = new ObjectId();
        }

        Marketer::create([
            'name' => trim($marketer['name']),
            'logo' => $name,
            'coverage' => !empty($marketer['coverage']) ? $marketer['coverage'] : null,
            'products' => [
                'electricity' => $marketer['products']['electricity'],
                'gas' => $marketer['products']['gas'],
                'dual' => $marketer['products']['dual'] ?? [],
                'telephony' => $marketer['products']['telephony'] ?? [],
                'alarm' => $marketer['products']['alarm'] ?? [],
                'selfSupply' => $marketer['products']['selfSupply'] ?? []
            ],
            'fees' => [
                'electricity' => $marketer['fees']['electricity'],
                'gas' => $marketer['fees']['gas']
            ],
            'rCommissionPyme' => $marketer['retrocommission']['first'] ?? null,
            'rCommissionRes' => $marketer['retrocommission']['second'] ?? null,
            'surplus' => $marketer['surplus'],
            'extras' => $marketer['extras'],
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
            'createdBy' => Auth::user()->_id
        ]);

        return response()->json(['message' => 'La comercializadora ha sido creada'], 200);
    }


    //obtener todas las comercializadoras
    public function index(Request $request)
    {

        $user = isset($request->user) ? ((object) $request->user) : Auth::user();

        $marketers = $this->getMarketersBySubdomain($user, $request['assignContractTo'], $request);

        if($user){
            return response()->json(['marketers' => $marketers], 200);
        }else{
            return response()->json(['marketers' => MarketerResource::collection($marketers)], 200);
        }

    }

    public function indexAll(Request $request)
    {

        $marketers = Marketer::all();

        return response()->json(['marketers' => $marketers], 200);
    }

    //obtener una las comercializadora
    public function show($id)
    {

        $marketer = Marketer::where('_id', $id)->first();

        return response()->json(['marketer' => $marketer], 200);
    }


    //actualizar una comercializadora
    public function update($id, Request $request)
    {

        $marketer = json_decode($request['marketer'], true);

        $marketerToUpdate = Marketer::where('_id', $id)->first();

        //nombre
        $marketerToUpdate['name'] = $marketer['name'];

        //logo
        if (isset($request['image']) && !is_string($request['image'])) {
            //Borro el logo antiguo y subo el nuevo
            if ($marketer['logo']) {
                Storage::disk('marketer')->delete($marketer['logo']);
            }
            $name = $marketer['name'] . "-" . time() . "." . $request['image']->getClientOriginalExtension();
            Storage::disk('marketer')->put($name, file_get_contents($request['image']));
            $marketerToUpdate['logo'] = $name;
        }

        // Redondear precios antes de actualizar
        $marketer = $this->roundMarketerPrices($marketer);

        //Ids de productos
        foreach (['electricity', 'gas', 'dual', 'telephony', 'alarm', 'selfSupply'] as $t) {
            if (!empty($marketer['products'][$t])) {
                foreach ($marketer['products'][$t] as &$product) {
                    if (!isset($product['_id']) || empty($product['_id'])) {
                        $product['_id'] = new ObjectId();
                    }
                }
                unset($product);
            }
        }

        //productos
        $marketerToUpdate['products'] = $marketer['products'];

        //Compruebo si las tarifas tienen id, si no le asigno una y si la tiene la devuelvo a un objectid

        $products = $marketerToUpdate->products;

        //Compruebo que los fees tengan id
        $typesFees = ['electricity', 'gas', 'dual', 'telephony', 'selfSupply'];
        foreach ($typesFees as &$type){
            foreach ($products[$type] as &$product) {

                if (!isset($product['fees'])) continue;

                foreach ($product['fees'] as &$fee) {
                    if (!isset($fee['id'])) {
                        $fee['id'] = new ObjectId();
                    } elseif (is_array($fee['id']) && isset($fee['id']['$oid'])) {
                        // Viene como { "$oid": "..." }
                        $fee['id'] = new ObjectId($fee['id']['$oid']);
                    } elseif (is_string($fee['id'])) {
                        // Viene como string plano "507f1f77..."
                        $fee['id'] = new ObjectId($fee['id']);
                    }
                }
                unset($fee);
            }
            unset($product);
        }

        $marketerToUpdate->products = $products;

        //tarifas
        $marketerToUpdate['fees'] = $marketer['fees'];

        //excedentes
        $marketerToUpdate['surplus'] = $marketer['surplus'];

        $marketerToUpdate['coverage'] = $marketer['coverage'] ?? null;

        $marketerToUpdate['archived'] = $marketer['archived'] ?? false;

        //extras
        if (isset($marketer['extras'])) {
            foreach ($marketer['extras'] as &$extra) {
                if (isset($extra['temporalId'])) {
                    unset($extra['temporalId']);
                    $extra['id'] = new ObjectId();
                }
            }

            $marketerToUpdate['extras'] = $marketer['extras'];
        }


        $marketerToUpdate['rCommissionPyme'] = $marketer['retrocommission']['first'] ?? null;
        $marketerToUpdate['rCommissionRes'] = $marketer['retrocommission']['second'] ?? null;

        //Añado las comisiones si tiene (BTV)
        if (isset($marketer['commissions'])) {
            $marketerToUpdate['commissions'] = $marketer['commissions'];
        }

        $marketerToUpdate->save();

        return response()->json(['message' => 'La comercializadora ha sido actualizada'], 200);
    }

    public function delete($id)
    {
        $marketer = Marketer::where('_id', $id)->first();
        $marketer->delete();
        return response()->json(['message' => 'La comercializadora ha sido eliminada'], 200);
    }


    //actualizar entera de golpe
    public function generalUpdateMarketers(Request $request)
    {

        $marketers = $request['marketers'];
        $marketersToEdit = $this->getMarketersBySubdomain();

        //Recorro la comercializadora
        foreach ($marketersToEdit as $marketerNow) {

            $marketerIndex = array_search($marketerNow->name, array_column($marketers, 'name'));
            //Si no está el marketer para actualizar, salto al siguiente
            if ($marketerIndex === false) {
                continue;
            }
            $marketerData = $marketers[$marketerIndex];

            // Redondear precios antes de actualizar
            $marketerData = $this->roundMarketerPrices($marketerData);

            //Actualizo los productos de gas o de luz de cada comercializadora
            $marketerProducts = $marketerNow['products'];

            //Actualizo los productos de luz, gas, duales, alarmas, autoconsumo, telefonia
            $productTypes = ['electricity', 'gas', 'telephony', 'alarm', 'selfSupply'];

            //Transformo las ids de fees a ObjectId de Mongo
            foreach ($productTypes as $productType){

                if (!isset($marketerData['products'][$productType])) $marketerData['products'][$productType] = [];

                foreach ($marketerData['products'][$productType] as &$product) {

                    if ($productType !== 'alarm') {
                        foreach ($product['fees'] as &$fee) {
                            if (!$fee['id'] instanceof ObjectId) {
                                if (is_array($fee['id']) && isset($fee['id']['$oid'])) {
                                    $fee['id'] = new ObjectId($fee['id']['$oid']);
                                } else if (is_string($fee['id'])) {
                                    $fee['id'] = new ObjectId($fee['id']);
                                }
                            }
                        }
                        unset($fee);
                    }

                    // Ordenar fees alfabéticamente por nombre
                    if ($productType === 'electricity' || $productType === 'gas')
                        $product['fees'] = $this->sortFeesByName($product['fees'], $marketerData['fees'][$productType]);
                    else if ($productType !== 'alarm')
                        usort($product['fees'], function ($a, $b) {
                            return strcasecmp($a['name'], $b['name']);
                        });

                }
                unset($product);

                $marketerProducts[$productType] = $marketerData['products'][$productType];
            }



            //Transformo las ids de fees a ObjectId de Mongo
            if(!isset($marketerData['products']['dual'])) $marketerData['products']['dual'] = [];

            foreach ($marketerData['products']['dual'] as &$product) {
                foreach ($product['fees'] as &$fee) {
                    if (!$fee['id'] instanceof ObjectId) {
                        if (is_array($fee['id']) && isset($fee['id']['$oid'])) {
                            $fee['id'] = new ObjectId($fee['id']['$oid']);
                        } else if (is_string($fee['id'])) {
                            $fee['id'] = new ObjectId($fee['id']);
                        }
                    }
                }
                // Ordenar fees alfabéticamente por nombre
                //$product['fees'] = $this->sortFeesByName($product['fees'], $marketerData['fees']['dual']);
            };

            $marketerProducts['dual'] = $marketerData['products']['dual'];



            $marketerNow['products'] = $marketerProducts;


            //Actualizo las fees
            $marketerNow['fees'] = $marketerData['fees'];

            if (isset($marketerData['validDates'])) {
                $marketerNow['validDates'] = $marketerData['validDates'];
            }

            $marketerNow->save();
        }

        return response()->json(['success' => 'Product moved successfully']);
    }

    //eliminar tarifas de producto
    public function deleteProductFee(Request $request)
    {

        $marketer = $request['marketer'];
        $productInd = $request['productInd'];
        $feeInd = $request['feeInd'];
        $deleteProd = $request['deleteProd'];
        $type = $request['type'];

        $marketerToEdit = Marketer::where('_id', $marketer['_id'])->first();

        $products = $marketerToEdit['products'];

        //producto de luz
        //si hay que eliminar el producto entero
        if ($deleteProd)
            array_splice($products[$type === 'elect' ? 'electricity' : $type], $productInd, 1);
        else
            array_splice($products[$type === 'elect' ? 'electricity' : $type][$productInd]['fees'], $feeInd, 1);

        $marketerToEdit->products = $products;

        $marketerToEdit->save();

        return response()->json(['success' => 'updated!']);
    }


    //vincular tarifas de producto
    public function addProductFee(Request $request)
    {

        $marketer = $request['marketer'];
        $productInd = $request['productInd'];
        $fee = $request['fee'];
        $type = $request['type'];


        $marketerToEdit = Marketer::where('_id', $marketer['_id'])->first();

        $products = $marketerToEdit['products'];

        //Actualizo id de fee a ObjectId de Mongo
        if (isset($fee['id']) && isset($fee['id']['$oid']))
            $fee['id'] = new ObjectId($fee['id']['$oid']);
        else
            $fee['id'] = new ObjectId();


        array_push($products[$type][$productInd]['fees'], $fee);

        if ($type === 'electricity' || $type === 'gas') {

            $nameMap = [];

            foreach ($marketerToEdit['fees'][$type] as $fee) {

                $id = $this->getmongoId($fee['id'] ?? null);

                if ($id) {
                    $nameMap[$id] = $fee['name'];
                }
            }

            usort(
                $products[$type][$productInd]['fees'],
                function ($a, $b) use ($nameMap) {

                    $idA = $this->getmongoId($a['id'] ?? null);
                    $idB = $this->getmongoId($b['id'] ?? null);

                    if (!$idA || !$idB) {
                        throw new \Exception(
                            'IDs no válidos: ' . json_encode(compact('a', 'b'))
                        );
                    }

                    return strcmp(
                        $nameMap[$idA] ?? '',
                        $nameMap[$idB] ?? ''
                    );
                }
            );

        }

        $marketerToEdit->products = $products;

        $marketerToEdit->save();

        return response()->json(['success' => 'updated!']);
    }

    public function haveMarketerOrders(Request $request)
    {
        $userList = $request['userList'];
        $marketer = $request['marketer'];

        return Account::whereIn('usersIds', $userList)->where('orders.marketer', $marketer)->count();
    }

    /*
    public static function getMarketersBySubdomain($user = null, $assignContractTo = null, $request = null)
    {
        // Si se pasa un usuario, usarlo. Si no, usar Auth::user()
        $user = $user ?? Auth::user();

        // En caso de tener que mostrar las comercializadoras de Zoco
        $subdomainUser = null;
        if ($assignContractTo) {
            $subdomainUser = User::where('_id', $assignContractTo)->first();
            $superiors = AuthController::getAllSuperiors($user->_id);
            $user = array_values(array_filter($superiors, function ($u) {
                return $u["label"] === "Usuario subdominio";
            }))[0] ?? null;
        } else if ($user && $user->label === "Usuario subdominio") {
            $subdomainUser = $user;
        } else if ($user) {
            $superiors = AuthController::getAllSuperiors($user->_id);
            $user = $subdomainUser = array_values(array_filter($superiors, function ($u) {
                return $u["label"] === "Usuario subdominio";
            }))[0] ?? null;
        }

        if (!$subdomainUser){
            $currentHost = $request->getHost();
            $enterprise = Enterprise::where('url', $currentHost)->first();
            if($enterprise){
                $subdomainUser = ["_id" => $enterprise->getRawOriginal('subdomainUser')];
            }else{
                return collect([]); // Devolver una colección vacía si no se encuentra un subdominio
            }
        }


        if (gettype($subdomainUser) === "array")
            $subdomainUser = (object) $subdomainUser;

        $marketers = Marketer::where('createdBy', $subdomainUser->_id)->get();

        //Solo filtrar si está asignado y lo estás mirando desde un subdominio distinto al asignado
        //Solo filtrar si el agente tiene marketers configurados
            if ($assignContractTo && $subdomainUser->_id !== $user['_id']) {

                $allowed = $user['comparatorMarketers'] ?? [];

                // Si el agente ha configurado marketers para su comparador
                if (count($allowed) > 0) {

                    $marketers = $marketers->filter(function ($marketer) use ($allowed) {
                        return in_array($marketer->_id, $allowed);
                    })->values();

                }

            }

        return $marketers;
    }
        */

    public static function getMarketersBySubdomain($user = null, $assignContractTo = null, $request = null)
    {
        if (!$assignContractTo && $request instanceof Request) {
            $assignContractTo =
                $request->input('assignContractTo')
                ?: $request->input('ref');
        }

        $authUser = $user ?? Auth::user();

        $agentUser = null;
        $subdomainUser = null;

        // CASO 1: viene del comparador con ref de agente
        if ($assignContractTo) {
            $agentUser = User::where('_id', $assignContractTo)->first();

            if (!$agentUser) {
                return collect([]);
            }

            // Buscar el usuario subdominio superior de ese agente
            if ($agentUser->label === "Usuario subdominio") {
                $subdomainUser = $agentUser;
            } else {
                $superiors = AuthController::getAllSuperiors($agentUser->_id);

                $subdomainUser = array_values(array_filter($superiors, function ($u) {
                    return $u["label"] === "Usuario subdominio";
                }))[0] ?? null;

                if (is_array($subdomainUser)) {
                    $subdomainUser = (object) $subdomainUser;
                }
            }
        }

        // CASO 2: uso normal dentro del CRM
        if (!$subdomainUser) {
            if ($authUser && $authUser->label === "Usuario subdominio") {
                $subdomainUser = $authUser;
            } else if ($authUser) {
                $superiors = AuthController::getAllSuperiors($authUser->_id);

                $subdomainUser = array_values(array_filter($superiors, function ($u) {
                    return $u["label"] === "Usuario subdominio";
                }))[0] ?? null;

                if (is_array($subdomainUser)) {
                    $subdomainUser = (object) $subdomainUser;
                }
            }
        }

        // CASO 3: comparador público sin usuario autenticado
        if (!$subdomainUser && $request) {
            $currentHost = $request->getHost();
            $enterprise = Enterprise::where('url', $currentHost)->first();

            if ($enterprise) {
                $subdomainUser = User::where('_id', $enterprise->getRawOriginal('subdomainUser'))->first();
            }
        }

        if (!$subdomainUser || empty($subdomainUser->_id)) {
            return collect([]);
        }

        // Marketers disponibles de la cuenta / subdominio
        $marketers = Marketer::where('createdBy', $subdomainUser->_id)->get();

        // Si viene del comparador con agente, solo filtrar por comparatorMarketers
        // cuando ese agente tenga algo configurado
        // Si viene del comparador con agente
        if ($request instanceof Request && $request->input('from') === 'openComparator') {
            if (!$agentUser) {
                $agentUser = $subdomainUser;
            }

            \Log::info('DEBUG OPEN COMPARATOR USERS', [
                'assignContractTo' => $assignContractTo,
                'from' => $request->input('from'),
                'agentUser_id' => $agentUser->_id ?? null,
                'agentUser_label' => $agentUser->label ?? null,
                'subdomainUser_raw' => $subdomainUser,
                'subdomainUser_id' => $subdomainUser->_id ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Comercializadoras visibles del QR
            |--------------------------------------------------------------------------
            | Esto sigue funcionando igual: usa las comercializadoras configuradas
            | en el usuario del QR.
            */
            $qrAllowed = $agentUser->comparatorMarketers ?? [];

            if ($qrAllowed instanceof \Illuminate\Support\Collection) {
                $qrAllowed = $qrAllowed->toArray();
            }

            $qrAllowed = array_map('strval', $qrAllowed);

            $marketers = $marketers->filter(function ($marketer) use ($qrAllowed) {

                if (!empty($qrAllowed) && !in_array((string) $marketer->_id, $qrAllowed, true)) {
                    return false;
                }

                return true;

            })->values();


            /*
            |--------------------------------------------------------------------------
            | Productos ocultos heredados en QR
            |--------------------------------------------------------------------------
            | En el QR se eliminan directamente:
            | - productos ocultos por superiores
            | - productos ocultos por el propio usuario del QR
            */
            $agentHiddenProducts = self::normalizeComparatorHiddenProducts(
                $agentUser->comparatorHiddenProducts ?? []
            );

            $inheritedHiddenProducts = self::getInheritedComparatorHiddenProducts($agentUser);

            $qrHiddenProducts = self::normalizeComparatorHiddenProducts(
                array_merge($inheritedHiddenProducts, $agentHiddenProducts)
            );

            $marketers = self::removeHiddenProductsFromMarketers(
                $marketers,
                $qrHiddenProducts
            );
            \Log::info('OPEN COMPARATOR QR FILTER', [
                'agentUser' => $agentUser->_id ?? null,
                'subdomainUser' => $subdomainUser->_id ?? null,
                'comparatorMarketers' => $qrAllowed,
                'comparatorHiddenProductsInherited' => $qrHiddenProducts,
                'marketersCount' => $marketers->count(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Productos ocultos heredados en perfil
        |--------------------------------------------------------------------------
        | En el perfil del usuario inferior también se eliminan directamente.
        | No se muestran con ojo bloqueado: directamente no aparecen.
        */
        if ($request instanceof Request && $request->input('from') === 'profileComparator') {

            if (!$agentUser) {
                $agentUser = $authUser;
            }

            $inheritedHiddenProducts = self::getInheritedComparatorHiddenProducts($agentUser);

            $marketers = self::removeHiddenProductsFromMarketers(
                $marketers,
                $inheritedHiddenProducts
            );
        }

        return $marketers;
    }

    private static function normalizeComparatorHiddenProducts($products): array
    {
        if ($products instanceof \Illuminate\Support\Collection) {
            $products = $products->toArray();
        }

        if (!is_array($products)) {
            return [];
        }

        $normalized = [];

        foreach ($products as $key) {
            if (!$key || is_array($key) || is_object($key)) {
                continue;
            }

            $key = (string) $key;

            if (str_contains($key, '[object Object]')) {
                continue;
            }

            $parts = explode('|', $key);

            if (count($parts) >= 2) {
                $key = $parts[0] . '|' . $parts[1];
            }

            $normalized[] = $key;
        }

        return array_values(array_unique($normalized));
    }

    private static function normalizeVisibilityValue($value): string
    {
        if (!$value) {
            return '';
        }

        if ($value instanceof ObjectId) {
            return (string) $value;
        }

        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            if (isset($value['$oid'])) {
                return (string) $value['$oid'];
            }

            if (isset($value['oid'])) {
                return (string) $value['oid'];
            }
        }

        if (is_object($value)) {
            $arrayValue = (array) $value;

            if (isset($arrayValue['$oid'])) {
                return (string) $arrayValue['$oid'];
            }

            if (isset($arrayValue['oid'])) {
                return (string) $arrayValue['oid'];
            }
        }

        return '';
    }

    private static function getProductVisibilityId($product): string
    {
        if (is_string($product) || is_numeric($product)) {
            return (string) $product;
        }

        $productArray = is_object($product) ? (array) $product : $product;

        if (!is_array($productArray)) {
            return '';
        }

        $possibleIds = [
            $productArray['_id'] ?? null,
            $productArray['id'] ?? null,
            $productArray['productId'] ?? null,
            $productArray['product_id'] ?? null,
            $productArray['name'] ?? null,
            $productArray['product'] ?? null,
            $productArray['productName'] ?? null,
            $productArray['product_name'] ?? null,
            $productArray['title'] ?? null,
            $productArray['label'] ?? null,
            $productArray['nombre'] ?? null,
            $productArray['rateName'] ?? null,
            $productArray['tariffName'] ?? null,
            $productArray['tariff'] ?? null,
            $productArray['tarifa'] ?? null,
            $productArray['rate'] ?? null,
            $productArray['feeName'] ?? null,
            $productArray['fee'] ?? null,
        ];

        foreach ($possibleIds as $id) {
            $normalized = self::normalizeVisibilityValue($id);

            if ($normalized) {
                return $normalized;
            }
        }

        return '';
    }

    private static function getComparatorProductKey($marketer, $product): ?string
    {
        $marketerId = self::normalizeVisibilityValue($marketer->_id ?? null);
        $productId = self::getProductVisibilityId($product);

        if (!$marketerId || !$productId) {
            return null;
        }

        return $marketerId . '|' . $productId;
    }

    private static function getInheritedComparatorHiddenProducts($user): array
    {
        if (!$user || empty($user->_id)) {
            return [];
        }

        if (($user->label ?? null) === 'Usuario subdominio') {
            return [];
        }

        $hiddenProducts = [];

        $superiors = AuthController::getAllSuperiors($user->_id);

        foreach ($superiors as $superior) {
            $superiorId = is_array($superior)
                ? ($superior['_id'] ?? null)
                : ($superior->_id ?? null);

            if (!$superiorId) {
                continue;
            }

            $superiorUser = User::where('_id', $superiorId)->first();

            if (!$superiorUser) {
                continue;
            }

            $hiddenProducts = array_merge(
                $hiddenProducts,
                self::normalizeComparatorHiddenProducts(
                    $superiorUser->comparatorHiddenProducts ?? []
                )
            );
        }

        return self::normalizeComparatorHiddenProducts($hiddenProducts);
    }

    private static function removeHiddenProductsFromMarketers($marketers, array $hiddenProducts)
    {
        $hiddenProducts = self::normalizeComparatorHiddenProducts($hiddenProducts);

        if (empty($hiddenProducts)) {
            return $marketers;
        }

        return $marketers->map(function ($marketer) use ($hiddenProducts) {

            $products = $marketer->products ?? [];

            if ($products instanceof \Illuminate\Support\Collection) {
                $products = $products->toArray();
            }

            if (!is_array($products)) {
                return $marketer;
            }

            foreach ($products as $type => $productList) {

                if ($productList instanceof \Illuminate\Support\Collection) {
                    $productList = $productList->toArray();
                }

                if (!is_array($productList)) {
                    continue;
                }

                $products[$type] = array_values(array_filter($productList, function ($product) use ($marketer, $hiddenProducts) {

                    $key = self::getComparatorProductKey($marketer, $product);

                    if (!$key) {
                        return true;
                    }

                    return !in_array($key, $hiddenProducts, true);

                }));
            }

            $marketer->products = $products;

            return $marketer;

        })->values();
    }


    function dumpMarketers(Request $request)
    {
        $file = $request->file('file');
        $dates = json_decode($request->input('dates'), true);
        $archiveProductsNotListed = filter_var( $request->input('archiveProductsNotListed', false), FILTER_VALIDATE_BOOLEAN);        $warnings = [];

        if (!$file || !$file->isValid()) {
            return response()->json(['error' => 'Hubo un error al subir el archivo. Por favor inténtalo de nuevo.'], 400);
        }


        try {

            $marketers = $this->getMarketersBySubdomain();

            // Guardamos el estado original de los marketers antes de procesar el Excel
            $originalMarketers = $marketers->keyBy(function($item) {
                return strtolower(preg_replace('/\s+/', '', trim($item['name'])));
            })->toArray();

            set_time_limit(0);
            ini_set('memory_limit', '1024M');

            $excel = Excel::toArray([], $file);


            //Compruebo que el excel tiene datos
            if (empty($excel[0]) || !is_array($excel[0]) || count($excel[0]) == 0) {
                return response()->json(['error' => 'No se pudieron obtener datos válidos del archivo Excel.'], 400);
            }

            //Comprobar que es la plantilla
            if (!str_contains($excel[0][0][0],"ZocoProductos")) {
                return response()->json(['error' => 'Por favor usa la plantilla de comercializadoras.'], 400);
            }

            if (!str_contains($excel[0][0][0],"V1.3")) {
                return response()->json(['error' => 'Por favor usa la última versión de la plantilla.'], 422);
            }

            $excelData = array_slice($excel[0], 2);

            $newMarketers = [];
            $processedTypes = [];
            $processedFees = [];

            //Recorro los registros del excel
            foreach ($excelData as $index => $row) {

                //Compruebo que los campos obligatorios tengan datos
                $allDefined = true;
                for ($i = 0; $i < 4; $i++) {
                    if (!isset($row[$i]) && !($i === 2 && $row[1] === 'Alarmas')) {
                        $allDefined = false;
                        break;
                    }
                }
                if (!$allDefined)
                    continue;

                $marketerData = [];
                $productData = [];
                $productFee = [];


                $productTypes = [
                    'Electricidad' => 'electricity',
                    'Gas' => 'gas',
                    'Dual' => 'dual',
                    'Telefonia' => 'telephony',
                    'Alarmas' => 'alarm',
                    'Autoconsumo' => 'selfSupply'
                ];

                $productType = $productTypes[$row[1]];


                //Obtengo la comercializadora de la lista editada o de la lista original si todavía no está, o la creo
                $marketerIndex = null;
                foreach ($newMarketers as $index => $marketer) {
                    if (strtolower(preg_replace('/\s+/', '', trim($marketer['name']))) === strtolower(preg_replace('/\s+/', '', trim($row[0])))) {
                        $marketerIndex = $index;
                        break;
                    }
                }

                if ($marketerIndex === null) {
                    $marketerDB = $marketers->first(function ($item) use ($row) {
                        return strtolower(trim($item['name'])) === strtolower(trim($row[0]));
                    });

                    //Si no está en marketers, la creo
                    if (!isset($marketerDB)) {
                        $marketerData = Marketer::create([
                            'name' => trim($row[0]),
                            'logo' => null,
                            'coverage' => !empty($marketer['coverage']) ? $marketer['coverage'] : null,
                            'products' => [
                                'electricity' => [],
                                'gas' => [],
                                'dual' => [],
                                'telephony' => [],
                                'alarm' => [],
                                'selfSupply' => []
                            ],
                            'fees' => [
                                'electricity' => [],
                                'gas' => []
                            ],
                            'surplus' => [
                                'virtualBattery' => null,
                                'priceWithVB' => null,
                                'priceWithoutVB' => null,
                            ],
                            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
                            'createdBy' => Auth::user()->_id
                        ]);
                        $marketerData = $marketerData->toArray();
                    } else {
                        $marketerData = $marketerDB->toArray();
                    }
                }
                else {
                    $marketerData = $newMarketers[$marketerIndex];
                    unset($newMarketers[$marketerIndex]);
                    $newMarketers = array_values($newMarketers);
                }


                $feeData = null;

                //Obtengo o creo la tarifa ( solo para luz o gas que lo saca de fees directamente, sino lo miro cuando ya saque el producto ya que esta dentro de este )
                if ($productType === "electricity" || $productType === "gas") {

                    foreach ($marketerData['fees'][$productType] ?? [] as $fee) {
                        if (strtolower(trim($fee['name'])) === strtolower(trim($row[2]))) {
                            $feeData = $fee;
                            break;
                        }
                    }

                    if (!$feeData) {
                        $feeData = ['id' => new ObjectId(), 'name' => trim($row[2])];
                        $marketerData['fees'][$productType][] = $feeData;
                    }
                }


                //Obtengo o creo el producto
                $productIndex = null;

                foreach ($marketerData['products'][$productType] as $index => $product) {
                    if ($productType !== 'dual'){
                        if (strtolower(preg_replace('/\s+/', '', trim($product['name']))) === strtolower(preg_replace('/\s+/', '', trim($row[3])))) {
                            $productIndex = $index;
                            break;
                        }
                    }
                    else {
                        if (strtolower(preg_replace('/\s+/', '', trim($product['electricity']))) === strtolower(preg_replace('/\s+/', '', trim($row[3]))) && strtolower(preg_replace('/\s+/', '', trim($product['gas']))) === strtolower(preg_replace('/\s+/', '', trim($row[30])))) {
                            $productIndex = $index;
                            break;
                        }
                    }
                }

                if ($productIndex !== null) {
                    $productData = $marketerData['products'][$productType][$productIndex];
                }
                else {

                    if ($productType === "dual") {
                        $productData = [
                            '_id' => new ObjectId(),
                            'electricity' => trim($row[3]),
                            'gas' => trim($row[30]),
                            'errors' => [],
                            'fees' => []
                        ];

                    }
                    else if ($productType === "alarm") {

                        $commissionRow = [
                            'con1'      => null,
                            'con2'      => null,
                            'pot1'      => null,
                            'pot2'      => null,
                            'multiply'  => false,
                            'base'      => null,
                            'breakdown' => [],
                        ];

                        $productData = [
                            '_id' => new ObjectId(),
                            'name' => trim($row[3]),
                            'commissionType' => 'f',
                            'commissions' => [$commissionRow],
                            'errors' => []
                        ];
                    }
                    else {

                        $productData = [
                            '_id' => new ObjectId(),
                            'name' => trim($row[3]),
                            'fees' => [],
                            'errors' => []
                        ];
                    }

                }


                // Por seguridad, si viene sin _id (casos antiguos), asígnalo:
                if (!isset($productData['_id']) || empty($productData['_id'])) {
                    $productData['_id'] = new ObjectId();
                }


                //Obtengo el id de la tarifa si existe - usando comparación correcta de ObjectId
                $productFeeIndex = null;

                if ($productType !== 'alarm') {
                    foreach ($productData['fees'] as $index => $fee) {
                        if ($productType === 'electricity' || $productType === 'gas'){

                            // Comparar ObjectId correctamente - convertir ambos a string
                            if (is_array($fee['id']) && isset($fee['id']['$oid'])) {
                                $feeIdString = $fee['id']['$oid'];
                            } else {
                                $feeIdString = (string) $fee['id'];
                            }

                            if (is_array($feeData['id']) && isset($feeData['id']['$oid'])) {
                                $searchIdString = $feeData['id']['$oid'];
                            } else {
                                $searchIdString = (string) $feeData['id'];
                            }

                            if ($feeIdString === $searchIdString) {
                                $productFeeIndex = $index;
                                break;
                            }
                        }
                        else if($productType === 'dual') {
                            if (strtolower(preg_replace('/\s+/', '', trim($fee['electricity']['name']))) === strtolower(preg_replace('/\s+/', '', trim($row[2]))) && strtolower(preg_replace('/\s+/', '', trim($fee['gas']['name']))) === strtolower(preg_replace('/\s+/', '', trim($row[29]))))
                                $productFeeIndex = $index;
                        }
                        else if ($productType !== 'alarm'){
                            if (strtolower(preg_replace('/\s+/', '', trim($fee['name']))) === strtolower(preg_replace('/\s+/', '', trim($row[2]))))
                                $productFeeIndex = $index;
                        }
                    }
                }


                //Si la tarifa no existe, inicializo su id y comisiones
                if ($productType !== 'alarm') {
                    if ($productFeeIndex === null) {

                        $commissionRow = [
                            'con1'      => null,
                            'con2'      => null,
                            'pot1'      => null,
                            'pot2'      => null,
                            'multiply'  => false,
                            'base'      => null,
                            'breakdown' => [],
                        ];

                        $baseFee = [
                            'commissionType' => 'f',
                            'commissions'    => [$commissionRow],
                            'type'           => ['pyme' => true, 'residencial' => true],
                            'prices'         => [],
                        ];

                        if ($productType === 'electricity' || $productType === 'gas') {
                            $productFee = array_merge($baseFee, ['id' => $feeData['id'], 'priceType' => 'fixed']);

                            if ($productType === 'electricity') {
                                $productFee['surplus']   = 'none';
                            }
                        } elseif ($productType === 'dual') {
                            $productFee = [
                                'id'          => new ObjectId(),
                                'electricity' => array_merge($baseFee, [
                                    'name'      => trim($row[2]),
                                    'surplus'   => 'none',
                                    'priceType' => 'fixed',
                                ]),
                                'gas'         => array_merge($baseFee, [
                                    'name'      => trim($row[29]),
                                    'priceType' => 'fixed',
                                ]),
                                'type'        => ['pyme' => true, 'residencial' => true],
                            ];
                        } else {
                            $productFee = array_merge($baseFee, [
                                'id'   => new ObjectId(),
                                'name' => trim($row[2]),
                            ]);
                        }
                    }
                    else {
                        $productFee = $productData['fees'][$productFeeIndex];
                    }
                }


                //Actualizo los precios según el tipo de producto
                if ($productType === "electricity") {
                    $powerPrices = [
                        'P1' => $this->normalizePrice($row[6] ?? 0),
                        'P2' => $this->normalizePrice($row[7] ?? 0),
                        'P3' => $this->normalizePrice($row[8] ?? 0),
                        'P4' => $this->normalizePrice($row[9] ?? 0),
                        'P5' => $this->normalizePrice($row[10] ?? 0),
                        'P6' => $this->normalizePrice($row[11] ?? 0)
                    ];
                    $consumePrices = [
                        'P1' => $this->normalizePrice($row[12] ?? 0),
                        'P2' => $this->normalizePrice($row[13] ?? 0),
                        'P3' => $this->normalizePrice($row[14] ?? 0),
                        'P4' => $this->normalizePrice($row[15] ?? 0),
                        'P5' => $this->normalizePrice($row[16] ?? 0),
                        'P6' => $this->normalizePrice($row[17] ?? 0)
                    ];

                    $zeroPrices = ['P1'=>0,'P2'=>0,'P3'=>0,'P4'=>0,'P5'=>0,'P6'=>0];

                    $isIndexed = (strtolower($row[21] ?? '') === 'indexado') || (($productFee['priceType'] ?? null) === 'indexed');
                    $monthKey = trim((string)($row[28] ?? ''));
                    $hasValidMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $monthKey);

                    if ($isIndexed && $hasValidMonth) {
                        //Si es nuevo, inicializar power y consume a 0
                        $productFee['prices']['power']   = $productFee['prices']['power']   ?? $zeroPrices;
                        $productFee['prices']['consume'] = $productFee['prices']['consume'] ?? $zeroPrices;

                        //Guardar en history por mes
                        $productFee['prices']['history'] = $productFee['prices']['history'] ?? [];
                        $productFee['prices']['history'][$monthKey] = [
                            'power'   => $powerPrices,
                            'consume' => $consumePrices,
                        ];

                    } else {
                        $productFee['prices']['power']   = $powerPrices;
                        $productFee['prices']['consume'] = $consumePrices;
                    }

                    if (isset($row[20])) {
                        $productFee['surplus'] = (strtolower($row[20]) === 'obligatorio' ? 'required' :
                            (strtolower($row[20]) === 'opcional' ? 'optional' : 'none'));
                    }
                    if (isset($row[21])) {
                        $productFee['priceType'] = (strtolower($row[21]) === 'indexado' ? 'indexed' :
                            (strtolower($row[21]) === 'fijo-variable' ? 'variable' : 'fixed'));

                        //Si el tipo de precio es variable o indexado, compruebo que los fees estén rellenos y sean válidos
                        if($productFee['priceType'] === 'indexed' || $productFee['priceType'] === 'variable'){
                            $map = [
                                'power' => [22, 23, 24, 1],
                                'energy' => [25, 26, 27, 5],
                            ];

                            foreach ($map as $type => [$unique, $min, $max, $defaultMax]) {
                                $productFee['fees'][$type] ??= [
                                    'unique'  => true,
                                    'minimum' => 0,
                                    'maximum' => $defaultMax,
                                ];

                                //Si el producto es nuevo
                                if ($productIndex === null) {
                                    $productFee['fees'][$type]['unique']  = ($row[$unique] ?? 'si') === 'si';
                                    $productFee['fees'][$type]['minimum'] = $row[$min] ?? 0;
                                    $productFee['fees'][$type]['maximum'] = $row[$max] ?? $defaultMax;
                                } else {
                                    if (isset($row[$unique])) $productFee['fees'][$type]['unique']  = $row[$unique] === 'si';
                                    if (isset($row[$min])) $productFee['fees'][$type]['minimum'] = $row[$min];
                                    if (isset($row[$max])) $productFee['fees'][$type]['maximum'] = $row[$max];
                                }

                                //Valido los datos
                                if ($productFee['fees'][$type]['minimum'] > $productFee['fees'][$type]['maximum']) {
                                    $productFee['fees'][$type]['minimum'] = 0;
                                    $warnings[] = 'fees';
                                }
                            }
                        }
                    }
                }
                else if($productType === "gas"){
                    $productFee['prices'] = [
                        "fixed" => $row[4] ?? 0,
                        "variable" => $row[5] ?? 0
                    ];

                    if (isset($row[21])) {
                        $productFee['priceType'] = (strtolower($row[21]) === 'indexado' ? 'indexed' :
                            (strtolower($row[21]) === 'fijo-variable' ? 'variable' : 'fixed'));

                        //Si el tipo de precio es variable o indexado, compruebo que los fees estén rellenos y sean válidos
                        if($productFee['priceType'] === 'indexed' || $productFee['priceType'] === 'variable'){
                            $map = [
                                'power' => [22, 23, 24, 1],
                                'energy' => [25, 26, 27, 5],
                            ];

                            foreach ($map as $type => [$unique, $min, $max, $defaultMax]) {
                                $productFee['fees'][$type] ??= [
                                    'unique'  => true,
                                    'minimum' => 0,
                                    'maximum' => $defaultMax,
                                ];

                                //Si el producto es nuevo
                                if ($productIndex === null) {
                                    $productFee['fees'][$type]['unique']  = ($row[$unique] ?? 'si') === 'si';
                                    $productFee['fees'][$type]['minimum'] = $row[$min] ?? 0;
                                    $productFee['fees'][$type]['maximum'] = $row[$max] ?? $defaultMax;
                                } else {
                                    if (isset($row[$unique])) $productFee['fees'][$type]['unique']  = $row[$unique] === 'si';
                                    if (isset($row[$min])) $productFee['fees'][$type]['minimum'] = $row[$min];
                                    if (isset($row[$max])) $productFee['fees'][$type]['maximum'] = $row[$max];
                                }

                                //Valido los datos
                                if ($productFee['fees'][$type]['minimum'] > $productFee['fees'][$type]['maximum']) {
                                    $productFee['fees'][$type]['minimum'] = 0;
                                    $warnings[] = 'fees';
                                }
                            }
                        }
                    }
                }
                else if($productType === "dual"){

                    //PARTE LUZ
                    $productFee['electricity']['prices']['power'] = [
                        'P1' => $this->normalizePrice($row[6] ?? 0),
                        'P2' => $this->normalizePrice($row[7] ?? 0),
                        'P3' => $this->normalizePrice($row[8] ?? 0),
                        'P4' => $this->normalizePrice($row[9] ?? 0),
                        'P5' => $this->normalizePrice($row[10] ?? 0),
                        'P6' => $this->normalizePrice($row[11] ?? 0)
                    ];
                    $productFee['electricity']['prices']['consume'] = [
                        'P1' => $this->normalizePrice($row[12] ?? 0),
                        'P2' => $this->normalizePrice($row[13] ?? 0),
                        'P3' => $this->normalizePrice($row[14] ?? 0),
                        'P4' => $this->normalizePrice($row[15] ?? 0),
                        'P5' => $this->normalizePrice($row[16] ?? 0),
                        'P6' => $this->normalizePrice($row[17] ?? 0)
                    ];
                    if (isset($row[20])) {
                        $productFee['electricity']['surplus'] = (strtolower($row[20]) === 'obligatorio' ? 'required' :
                            (strtolower($row[20]) === 'opcional' ? 'optional' : 'none'));
                    }
                    if (isset($row[21])) {
                        $productFee['electricity']['priceType'] = (strtolower($row[21]) === 'indexado' ? 'indexed' :
                            (strtolower($row[21]) === 'fijo-variable' ? 'variable' : 'fixed'));

                        //Si el tipo de precio es variable o indexado, compruebo que los fees estén rellenos y sean válidos
                        if($productFee['electricity']['priceType'] === 'indexed' || $productFee['electricity']['priceType'] === 'variable'){

                            $map = [
                                'power' => [22, 23, 24, 1],
                                'energy' => [25, 26, 27, 5],
                            ];

                            foreach ($map as $type => [$unique, $min, $max, $defaultMax]) {
                                $productFee['electricity']['fees'][$type] ??= [
                                    'unique'  => true,
                                    'minimum' => 0,
                                    'maximum' => $defaultMax,
                                ];

                                //Si el producto es nuevo
                                if ($productIndex === null) {
                                    $productFee['electricity']['fees'][$type]['unique']  = ($row[$unique] ?? 'si') === 'si';
                                    $productFee['electricity']['fees'][$type]['minimum'] = $row[$min] ?? 0;
                                    $productFee['electricity']['fees'][$type]['maximum'] = $row[$max] ?? $defaultMax;
                                } else {
                                    if (isset($row[$unique])) $productFee['electricity']['fees'][$type]['unique']  = $row[$unique] === 'si';
                                    if (isset($row[$min])) $productFee['electricity']['fees'][$type]['minimum'] = $row[$min];
                                    if (isset($row[$max])) $productFee['electricity']['fees'][$type]['maximum'] = $row[$max];
                                }

                                //Valido los datos
                                if ($productFee['electricity']['fees'][$type]['minimum'] > $productFee['electricity']['fees'][$type]['maximum']) {
                                    $productFee['electricity']['fees'][$type]['minimum'] = 0;
                                    $warnings[] = 'fees';
                                }
                            }
                        }
                    }

                    //PARTE GAS
                    $productFee['gas']['prices'] = [
                        "fixed" => $row[4] ?? 0,
                        "variable" => $row[5] ?? 0
                    ];
                }


                //PYME y RESIDENCIAL
                if ($productType !== 'alarm') {
                    if (isset($row[18])) {
                        $productFee['type']['residencial'] = strtolower($row[18]) === 'si';
                    }
                    if (isset($row[19])) {
                        $productFee['type']['pyme'] = strtolower($row[19]) === 'si';
                    }
                }
                else {
                    if (isset($row[18])) {
                        $productData['type']['residencial'] = strtolower($row[18]) === 'si';
                    }
                    if (isset($row[19])) {
                        $productData['type']['pyme'] = strtolower($row[19]) === 'si';
                    }
                }



                //Añado o actualizo la tarifa al producto
                if ($productType !== 'alarm') {
                    if ($productFeeIndex !== null) {
                        $productData['fees'][$productFeeIndex] = $productFee;
                    } else {
                        $productData['fees'][] = $productFee;
                    }
                }


                //Añado el producto a la comercializadora o lo actualizo
                if ($productIndex !== null) {
                    $marketerData['products'][$productType][$productIndex] = $productData;
                } else {
                    $marketerData['products'][$productType][] = $productData;
                }

                if (!empty($dates['start'])) {
                    $marketerData['validDates'][$productType] = ['start' => $dates['start'] ?? null, 'end' => $dates['end'] ?? null];
                }

                $marketerNameKey = strtolower(preg_replace('/\s+/', '', trim($row[0])));
                $processedTypes[$marketerNameKey][$productType] = true;

                $productId = (string)(is_array($productData['_id']) && isset($productData['_id']['$oid']) ? $productData['_id']['$oid'] : $productData['_id']);
                if ($productType === 'electricity' || $productType === 'gas') {
                    $feeIdProcessed = (string)(is_array($feeData['id']) && isset($feeData['id']['$oid']) ? $feeData['id']['$oid'] : $feeData['id']);
                } else {
                    $feeIdProcessed = isset($productFee['id']) ? (string)(is_array($productFee['id']) && isset($productFee['id']['$oid']) ? $productFee['id']['$oid'] : $productFee['id']) : null;
                }
                if ($feeIdProcessed) {
                    $processedFees[$marketerNameKey][$productType][$productId][] = $feeIdProcessed;
                }

                //Añado la comercializadora a la lista
                $newMarketers[] = $marketerData;
            }

            // Archivar fees no presentes en el Excel para los tipos procesados
            if ($archiveProductsNotListed) {
                foreach ($newMarketers as &$updatedMarketer) {
                    $marketerNameKey = strtolower(preg_replace('/\s+/', '', trim($updatedMarketer['name'])));

                    if (!isset($originalMarketers[$marketerNameKey])) continue;

                    $typesToArchive = array_keys($updatedMarketer['products'] ?? []);

                    foreach ($typesToArchive as $type) {
                        foreach ($updatedMarketer['products'][$type] as &$updatedProduct) {
                            $productId = (string)(is_array($updatedProduct['_id']) && isset($updatedProduct['_id']['$oid']) ? $updatedProduct['_id']['$oid'] : $updatedProduct['_id']);
                            $processedFeeIds = $processedFees[$marketerNameKey][$type][$productId] ?? [];

                            foreach ($updatedProduct['fees'] as &$fee) {
                                $feeId = $fee['id'] ?? $fee['_id'] ?? null;
                                $feeIdStr = (string)(is_array($feeId) && isset($feeId['$oid']) ? $feeId['$oid'] : $feeId);

                                if (!in_array($feeIdStr, $processedFeeIds)) {
                                    $fee['archived'] = true;
                                }else{
                                    $fee['archived'] = false;
                                }
                            }
                            unset($fee);
                        }
                        unset($updatedProduct);
                    }
                }
                unset($updatedMarketer);
            }

            $request = Request::create('/dummy-url', 'POST', ['marketers' => $newMarketers]);
            $this->generalUpdateMarketers($request);

            return response()->json(['success' => 'Comercializadoras actualizadas correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al procesar el archivo Excel: ' . $e->getMessage() . ' ' . $e->getLine()], 500);
        }
    }

    function dumpMarketerCommissions(Request $request){
        //Obtengo la comercializadora y los productos a agregar las comisiones, así como un excel con las comisiones
        //Actualizo el marketer en cuestión con las nuevas comisiones
        $marketerId = $request['marketer'];
        $typeProduct = $request['typeProduct'];
        $products = json_decode($request['products']);
        $excelFile = $request['excel'];
        $enterpriseId = $request['enterprise'];

        if (!$excelFile || !$excelFile->isValid()) {
            return response()->json(['error' => 'No se ha recibido un archivo válido.'], 422);
        }

        try {
            $excel = Excel::toArray([], $excelFile);

            //Compruebo que el excel tiene datos
            if (empty($excel[0]) || !is_array($excel[0]) || count($excel[0]) == 0) {
                return response()->json(['error' => 'No se pudieron obtener datos válidos del archivo Excel.'], 422);
            }

            //Comprobar que es la plantilla
            if (!str_contains($excel[0][0][0],"ZocoComisiones")) {
                return response()->json(['error' => 'Por favor usa la plantilla de comisiones.'], 422);
            }

            if (!str_contains($excel[0][0][0],"V1.0")) {
                return response()->json(['error' => 'Por favor usa la última versión de la plantilla.'], 422);
            }

            $excelData = array_slice($excel[0], 2);

            //Obtengo el tipo de comisión
            $commissionType = match ($excelData[0][2]) {
                "Fijo" => "f",
                "Consumo" => "c",
                "Potencia" => "p",
                "Consumo y potencia" => "cyp",
                default => null
            };

            if(!$commissionType){
                return response()->json(['error' => 'Por favor introduce un tipo de comisión válido.'],422);
            }

            //Compruebo si es de gas que no sea de comisión por potencia o consumo y potencia
            if($typeProduct === 'gas' && ($commissionType === 'p' || $commissionType === 'cyp')){
                return response()->json(['error' => 'No se pueden agregar comisiones por potencia o consumo a productos de gas.'], 422);
            }

            //Obtengo el subdominio
            $enterprise = Enterprise::where('_id', $enterpriseId)->first();

            $commissionTable = [];

            //Creo la estructura de las comisiones

            //En caso de ser fija
            if($commissionType === 'f'){
                $commission = $excelData[4][4] ?? null;

                // Validación comisión
                if ($commission === '' || !is_numeric($commission)) {
                    return response()->json(['error' => "La comisión está vacía o no es numérica."], 422);
                }

                $commissionRow = [
                    'con1'     => null,
                    'con2'     => null,
                    'pot1'     => null,
                    'pot2'     => null,
                    'multiply' => false,
                    'base'     => (float) $commission,
                    'breakdown' => [],
                ];

                foreach ($enterprise['commissionRanges'] as $range) {
                    $multiplier = ($range['percentage'] ?? 60) / 100;
                    $value = round($commissionRow['base'] * $multiplier, 2);
                    $commissionRow['breakdown'][(string) $range['id']] = intval($value) == $value ? intval($value) : $value;
                }

                $commissionTable[] = $commissionRow;

            //Si tiene desglose
            }else{
                $rows = array_slice($excelData, 4);
                $excelRow = 7;

                foreach ($rows as $row) {

                    // Validación de consumos
                    if ($commissionType === 'c' || $commissionType === 'cyp') {

                        if ($row[0] === '' || $row[1] === '') {
                            return response()->json(['error' => "Error en fila $excelRow: consumo mínimo o máximo está vacío."], 422);
                        }

                        if (!is_numeric($row[0]) || !is_numeric($row[1])) {
                            return response()->json(['error' => "Error en fila $excelRow: consumo mínimo o máximo no es numérico."], 422);
                        }

                        if ($row[0] > $row[1]) {
                            return response()->json(['error' => "Error en fila $excelRow: consumo mínimo ({$row[0]}) es mayor que consumo máximo ({$row[1]})."], 422);
                        }
                    }

                    // Validación de potencias
                    if ($commissionType === 'p' || $commissionType === 'cyp') {

                        if ($row[2] === '' || $row[3] === '') {
                            return response()->json(['error' => "Error en fila $excelRow: potencia mínima o máxima está vacía."], 422);
                        }

                        if (!is_numeric($row[2]) || !is_numeric($row[3])) {
                            return response()->json(['error' => "Error en fila $excelRow: potencia mínima o máxima no es numérica."], 422);
                        }

                        if ($row[2] > $row[3]) {
                            return response()->json(['error' => "Error en fila $excelRow: potencia mínima ({$row[2]}) es mayor que potencia máxima ({$row[3]})."], 422);
                        }
                    }

                    // Validación de comisión
                    if ($row[4] === '' || !is_numeric($row[4])) {
                        return response()->json(['error' => "Error en fila $excelRow: comisión vacía o no numérica."], 422);
                    }

                    //Creación de la fila
                    $commissionRow = [
                        'con1'     => $commissionType === 'c' || $commissionType === 'cyp' ? $row[0] : null,
                        'con2'     => $commissionType === 'c' || $commissionType === 'cyp' ? $row[1] : null,
                        'pot1'     => $commissionType === 'p' || $commissionType === 'cyp' ? $row[2] : null,
                        'pot2'     => $commissionType === 'p' || $commissionType === 'cyp' ? $row[3] : null,
                        'multiply' => strtolower($row[5] ?? '') === 'si',
                        'base'     => (float) $row[4],
                        'breakdown' => [],
                    ];

                    foreach ($enterprise['commissionRanges'] as $range) {
                        $multiplier = ($range['percentage'] ?? 60) / 100;
                        $value = round($commissionRow['base'] * $multiplier, 2);
                        $commissionRow['breakdown'][(string) $range['id']] = intval($value) == $value ? intval($value) : $value;
                    }

                    $commissionTable[] = $commissionRow;
                    $excelRow++;
                }
            }

            //Estructuro el marketer y los products para actualizar las comisiones
            $marketer = Marketer::where('_id', $marketerId)->first()->toArray();
            $marketerProducts = $marketer['products'];

            foreach ($products as $feeId => $productIds) {

                foreach ($marketerProducts[$typeProduct] as &$product) {

                    // Recorro los productos y compruebo si hay que actualizar las comisiones para este producto
                    $productId = $product['_id'] instanceof \MongoDB\BSON\ObjectId
                        ? (string) $product['_id']
                        : (is_array($product['_id'])
                            ? $product['_id']['$oid']
                            : (string) $product['_id']);


                    if (in_array((string) $productId, $productIds, true)) {

                        // Actualizo la fee correspondiente
                        if (!empty($product['fees'])) {
                            foreach ($product['fees'] as &$fee) {

                                if ((string) $fee['id'] === $feeId) {
                                    $fee['commissions'] = $commissionTable;
                                    $fee['commissionType'] = $commissionType;
                                }
                            }
                        }
                    }

                }
            }

            $marketer['products'] = $marketerProducts;

            $request = Request::create('/dummy-url', 'POST', [
                'marketers' => [$marketer],
            ]);

            $this->generalUpdateMarketers($request);

            return response()->json(['success' => 'Comisiones actualizadas correctamente'], 200);

        }catch (\Exception $e){
            return response()->json(['error' => 'Error al procesar el archivo Excel ', 'data' => $e->getMessage() . $e->getLine()], 500);
        }
    }

    private function roundMarketerPrices($marketer)
    {
        // Redondear precios en productos de electricidad
        if (isset($marketer['products']['electricity'])) {
            foreach ($marketer['products']['electricity'] as &$product) {
                if (isset($product['fees'])) {
                    foreach ($product['fees'] as &$fee) {
                        if (isset($fee['prices']['power'])) {
                            foreach ($fee['prices']['power'] as &$price) {
                                $price = round(floatval($price), 6);
                            }
                        }
                        if (isset($fee['prices']['consume'])) {
                            foreach ($fee['prices']['consume'] as &$price) {
                                $price = round(floatval($price), 6);
                            }
                        }
                    }
                }
            }
        }

        // Redondear precios en productos de gas
        if (isset($marketer['products']['gas'])) {
            foreach ($marketer['products']['gas'] as &$product) {
                if (isset($product['fees'])) {
                    foreach ($product['fees'] as &$fee) {
                        if (isset($fee['prices']['fixed'])) {
                            $fee['prices']['fixed'] = round(floatval($fee['prices']['fixed']), 6);
                        }
                        if (isset($fee['prices']['variable'])) {
                            $fee['prices']['variable'] = round(floatval($fee['prices']['variable']), 6);
                        }
                    }
                }
            }
        }

        // Redondear precios en productos duales
        if (isset($marketer['products']['dual'])) {
            foreach ($marketer['products']['dual'] as &$product) {
                if (isset($product['fees'])) {
                    foreach ($product['fees'] as &$fee) {

                        //Precios electricidad
                        if (isset($fee['prices']['electricity'])) {

                            //Precios potencia
                            foreach ($fee['prices']['electricity']['power'] as &$price) {
                                $price = round(floatval($price), 6);
                            }

                            //Precios consumo
                            if (isset($fee['prices']['electricity']['consume'])) {
                                foreach ($fee['prices']['electricity']['consume'] as &$price) {
                                    $price = round(floatval($price), 6);
                                }
                            }
                        }

                        //Precios gas
                        if (isset($fee['prices']['gas'])) {
                            if (isset($fee['prices']['gas']['fixed'])) {
                                $fee['prices']['gas']['fixed'] = round(floatval($fee['prices']['gas']['fixed']), 6);
                            }
                            if (isset($fee['prices']['gas']['variable'])) {
                                $fee['prices']['gas']['variable'] = round(floatval($fee['prices']['gas']['variable']), 6);
                            }
                        }

                    }
                }
            }
        }


        return $marketer;
    }

    private function sortFeesByName($productFees, $allFees)
    {
        // Crear un mapa de ID -> nombre para búsqueda rápida
        $nameMap = [];
        foreach ($allFees as $fee) {
            if (isset($fee['id'])) {
                // Manejar ObjectId correctamente
                $feeId = $fee['id'];
                if ($feeId instanceof ObjectId) {
                    $idString = (string) $feeId;
                } else if (is_array($feeId) && isset($feeId['$oid'])) {
                    $idString = $feeId['$oid'];
                } else {
                    $idString = (string) $feeId;
                }
                $nameMap[$idString] = $fee['name'] ?? '';
            }
        }


        // Ordenar los fees del producto por nombre
        usort($productFees, function ($a, $b) use ($nameMap) {
            $idA = (string) $a['id'];
            $idB = (string) $b['id'];

            $nameA = $nameMap[$idA] ?? '';
            $nameB = $nameMap[$idB] ?? '';

            return strcasecmp($nameA, $nameB); // Comparación case-insensitive
        });

        return $productFees;
    }


    public function checkIfProductExists(Request $request)
    {
        $marketerId = $request->query('marketerId');
        $name = $request->query('name');
        $typeProduct = $request->query('typeProduct');

        if (!$marketerId || !$name) {
            return response()->json(['exists' => false], 400);
        }

        $cleanName = strtolower(trim($name));

        $marketer = Marketer::find($marketerId);

        if (!$marketer) {
            return response()->json(['exists' => false], 404);
        }

        $products = $marketer->products[$typeProduct] ?? [];

        $exists = collect($products)->contains(function ($product) use ($cleanName) {
            return isset($product['name']) &&
                strtolower(trim($product['name'])) === $cleanName;
        });

        return response()->json(['exists' => $exists], 200);
    }


    public function checkIfDualProductExists(Request $request){

        $marketerId = $request->input('marketerId');
        $elecName = $request->input('elecName');
        $gasName = $request->input('gasName');

        $exists = Marketer::where('_id', $marketerId)
            ->where('products.dual', 'elemMatch', [
                'electricity' => $elecName,
                'gas' => $gasName
            ])
            ->exists();

        return response()->json(['exists' => $exists], 200);
    }

    public function getEnterpiseRanges(Request $request){
        $entreprise = Enterprise::where('subdomainUser', $request->input('userSubdomainId'))->first();
        return response()->json(['enterpriseRanges' => $entreprise['commissionRanges']]);
    }


    function getmongoId($id): ?string
    {
        if ($id instanceof ObjectId) {
            return (string) $id; // ObjectId → string
        }

        if (is_array($id)) {
            return $id['$oid'] ?? null;
        }

        if (is_string($id)) {
            return $id;
        }

        return null;
    }


    private function normalizePrice($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return (float) str_replace(
            [',', "\u{A0}", ' '],
            ['.', '', ''],
            $value
        );
    }
}
