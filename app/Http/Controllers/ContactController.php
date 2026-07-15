<?php

namespace App\Http\Controllers;

use App\Http\Models\Account;
use App\Http\Models\Contact;
use App\Http\Models\Email;
use App\Http\Models\Opportunity;
use App\Http\Models\Task;
use App\Http\Models\User;
use Carbon\Carbon;
use Dflydev\DotAccessData\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContactController extends Controller
{

    //funcion para guardar un contacto
    public function store(Request $request) {

        $contact = json_decode($request['contact']);

        $userLogged = json_decode($request['userLogged']);

        $imageFile = $request['imageFile'];

        if ($imageFile){
            //Creo el nombre de la imagen para guardar
            $imageName = time() . '.' . explode('.', $imageFile->getClientOriginalName())[1];

            //Meto la imagen en local
            Storage::disk('contact')->put($imageName, file_get_contents($imageFile));
        }

        //Recorro los campos customizados para ver si tiene alguno de tipo imagen
        $customFields = $contact->customFields;

        foreach ($customFields as $fieldInd => $field){

            //Si es imagen
            if ($field->type === 'image'){

                //Saco el archivo
                $file= $request['customFieldFile' . $fieldInd];

                if ($file !== null && !is_string($file)){
                    //Creo el nombre de la imagen para guardar
                    $fieldImageName = time() . '.' . explode('.', $file->getClientOriginalName())[1];

                    //Guardo la imagen en local
                    Storage::disk('contact')->put($fieldImageName, file_get_contents($file));

                    //Meto el nombre en el campo value para registrarlo
                    $field->value = $fieldImageName;

                    //Borro el $field-fileImage que es donde esta en si el archivo
                    unset($field->imageFile);
                }
            }

        }

        //Creo el contacto
        $contactId = Contact::insertGetId([
            'name' => $contact->name,
            'surname' => $contact->surname,
            'email' => $contact->email,
            'phone' => $contact->phone,
            //'nickname' => $contact->nickname ,
            'companyName' => $contact->companyName ?? '' ,
            'accounts' => $contact->accounts ,
            'position' => $contact->position,
            'billingInfo' => [
                'community' => $contact->billingInfo->community,
                'province' => $contact->billingInfo->province,
                'locality' => $contact->billingInfo->locality,
                'address' => $contact->billingInfo->address,
                'postal' => $contact->billingInfo->postal,
            ],
            'customFields' => $contact->customFields,
            'profileImage' => $imageFile ? $imageName : null,
            'usersIds' => [$userLogged->_id],
            'createdBy' => $userLogged->_id,
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
        ], '_id');

        return response()->json(['message' => 'El contacto ha sido creado correctamente']);
    }

    //funcion para actualizar un contacto
    public function update(Request $request){

        $contact = json_decode($request['contact']);

        $imageFile = $request['imageFile'];


        //Saco el contacto de la bbdd
        $contactToSave = Contact::where('_id', $contact->_id)->first();


        if ($imageFile){
            //Creo el nombre de la imagen para guardar
            $imageName = time() . '.' . explode('.', $imageFile->getClientOriginalName())[1];

            //Borro la imagen que había antes
            if ($contactToSave['profileImage'] !== 'default.jpg' || $contactToSave['profileImage'] === null)
                Storage::disk('contact')->delete($contactToSave['profileImage']);

            //Meto la imagen en local
            Storage::disk('contact')->put($imageName, file_get_contents($imageFile));
        }



        //Actualizo campos

            //Imagen
            if ($imageFile)
                $contactToSave['profileImage'] = $imageName;

            //Nombre
            $contactName = $contactToSave['name'];


            $contactName['first'] = $contact->name->first;

            $contactName['second'] = $contact->name->second;

            $contactToSave['name'] = $contactName;



            //Apellidos
            $contactSurname = $contactToSave['surname'];

            $contactSurname['first'] = $contact->surname->first;

            $contactSurname['second'] = $contact->surname->second;

            $contactToSave['surname'] = $contactSurname;


            //Correo electronico
            $contactToSave['email'] = $contact->email;

            //Teléfono
            $contactToSave['phone'] = $contact->phone;

            //Apodo
            //$contactToSave['nickname'] = $contact->nickname;

            //Nombre empresa
            $contactToSave['companyName'] = $contact->companyName ?? '';

            //Cuentas
            $contactToSave['accounts'] = $contact->accounts;


            //Cargo
            $contactToSave['position'] = $contact->position;


            //billingInfo
            $contactBillingInfo = $contactToSave->billingInfo;

            //Comunidad
            $contactBillingInfo['community'] = $contact->billingInfo->community;

            //Provincia
            $contactBillingInfo['province'] = $contact->billingInfo->province;

            //Localidad
            $contactBillingInfo['locality'] = $contact->billingInfo->locality;

            //Dirección
            $contactBillingInfo['address'] = $contact->billingInfo->address;

            //cod postal
            $contactBillingInfo['postal'] = $contact->billingInfo->postal;

            $contactToSave['billingInfo'] = $contactBillingInfo;


            //Recorro los campos customizados para ver si tiene alguno de tipo imagen
            $customFields = $contact->customFields;

            foreach ($customFields as $fieldInd => $field){

                //Si es imagen
                if ($field->type === 'image'){

                    //Saco el archivo
                    $file= $request['customFieldFile' . $fieldInd];

                    if ($file !== null && !is_string($file)){
                        //Creo el nombre de la imagen para guardar
                        $fieldImageName = time() . '.' . explode('.', $file->getClientOriginalName())[1];

                        //dd('$fieldImageName --> ',$fieldImageName);

                        if ($fieldImageName !== $field->value){

                            //Elimino la imagen antigua
                            if (isset($field->imageToDelete) && $field->imageToDelete !== '')
                                Storage::disk('contact')->delete($field->imageToDelete);

                            //Guardo la imagen en local
                            Storage::disk('contact')->put($fieldImageName, file_get_contents($file));

                            //Meto el nombre en el campo value para registrarlo
                            $field->value = $fieldImageName;

                            //Borro el campo imageToDelete si esque lo tiene
                            if (isset($field->imageToDelete))
                                unset($field->imageToDelete);
                        }
                    }

                }
            }

            $contactToSave->customFields = $customFields;

        $contactToSave->save();

        return response()->json(['message' => 'El contacto ha sido actualizada con exito'], 200);
    }


    //funcion para sacar los contactos de el usuario logeado
    public function index($id, Request $request){

        $user = User::where('_id', $id)->first();
        $userList = json_decode($request['userList']);
        $filters = $request['filters'];
        $isSeeingArchived =  $request['isSeeingArchived'];
        $filtersFromCookies = $request['filtersFromCookies'];
        $isFirstTime = $request['isFirstTime'];
        $sortType = $request['sortType'];
        $paginate = $request['paginate'] ?? true;


        //Saco ids de usuarios
        $usersIds = [$user['_id'], ...array_column($userList, '_id')];


        //CONSULTA PARA SACAR FILTROS ( Solo se sacara si es la primera vez, después ya se tendrán )
            if($isFirstTime === true) {

                $pipelineFilters = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    ['$addFields' => [
                        'accounts' => [
                            '$cond' => [
                                'if' => ['$eq' => [['$size' => '$accounts'], 0]],
                                'then' => [''],
                                'else' => '$accounts'
                            ]
                        ]
                    ]],
                    ['$unwind' => '$accounts'],
                    ['$group' => [
                        '_id' => '$accounts'
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'accounts' => ['$addToSet' => '$_id']
                    ]]
                ];

                $rawAccounts = Contact::raw(function ($collection) use ($pipelineFilters) {
                    return $collection->aggregate($pipelineFilters)->toArray();
                });

                $resultsFilters = [];
                $resultsFilters['sortBy'] = $sortType;

                $accountsInfo = [];
                $accountsIds = $rawAccounts[0]['accounts'] ?? [];

                foreach ($accountsIds as $account) {
                    if ($account !== '' && $account !== null) {
                        $accountsInfo[] = Account::where('_id', $account)->first();
                    } else {
                        $accountsInfo[] = ['_id' => '', 'name' => 'Sin cuenta'];
                    }
                }

                $resultsFilters['accounts'] = $accountsIds;
                $resultsFilters['accountsInfo'] = $accountsInfo;

                $pipelineAgents = [
                    ['$match' => [
                        'usersIds' => ['$in' => $usersIds]
                    ]],
                    ['$group' => [
                        '_id' => '$createdBy'
                    ]],
                    ['$group' => [
                        '_id' => null,
                        'agents' => ['$addToSet' => '$_id']
                    ]]
                ];

                $rawAgents = Contact::raw(function ($collection) use ($pipelineAgents) {
                    return $collection->aggregate($pipelineAgents)->toArray();
                });

                $agentsInfo = [];
                $agentsIds = $rawAgents[0]['agents'] ?? [];

                foreach ($agentsIds as $agentId) {
                    $u = User::where('_id', $agentId)->first();
                    if ($u) {
                        $agentsInfo[] = [
                            '_id'  => $u->_id,
                            'name' => trim(($u->firstName ?? '') . ' ' . ($u->lastName ?? ''))
                        ];
                    }
                }

                $resultsFilters['agentsInfo'] = $agentsInfo;
            }




        //Normalización de texto de busqueda
        $searchTextNormalized = mb_strtolower($request['searchContactText'], 'UTF-8'); // Convertir a minúsculas
        $searchTextNormalized = preg_replace('/\s+/', '', $searchTextNormalized); // Eliminar espacios
        $searchTextNormalized = preg_replace('/[áàäâãåÁÀÄÂÃ]/u', 'a', $searchTextNormalized); // Eliminar tildes
        $searchTextNormalized = preg_replace('/[éèëêÉÈËÊ]/u', 'e', $searchTextNormalized); // Eliminar tildes
        $searchTextNormalized = preg_replace('/[íìïîÍÌÏÎ]/u', 'i', $searchTextNormalized); // Eliminar tildes
        $searchTextNormalized = preg_replace('/[óòöôõÓÒÖÔÕ]/u', 'o', $searchTextNormalized); // Eliminar tildes
        $searchTextNormalized = preg_replace('/[úùüûÚÙÜÛ]/u', 'u', $searchTextNormalized); // Eliminar tildes

        // Patrón de búsqueda para MongoDB
        $regexPattern = new \MongoDB\BSON\Regex(preg_quote($searchTextNormalized), 'i');

        //CONSULTA PARA SACAR CONTACTOS ( La primera vez se sacaran sin filtros ( a mns que venga con filtros de las cookies ), a partir de la primera ya tendre los filtros y se podrá filtrar )


            /* if ($isFirstTime === false) dd($filters);*/

            // PARÁMETROS DE PAGINACIÓN
            $page = intval($request->input('page', 1));
            $perPage = intval($request->input('perPage', 20));
            $skip = ($page - 1) * $perPage;


            //ORDENAMIENTO
            $sort = ['createdAtTemporal' => -1];

            switch ($sortType){

                case 1:
                    $sort = ['name' => -1];
                    break;

                case 2:
                    $sort = ['account' => 1];
                    break;

                case 3:
                    $sort = ['account' => -1];
                    break;

                case 4:
                    $sort = ['email' => 1];
                    break;

                case 5:
                    $sort = ['email' => -1];
                    break;

                case 6:
                    $sort = ['createdAtTemporal' => 1];
                    break;

                case 7:
                    $sort = ['createdAtTemporal' => -1];
                    break;

                default:
                    $sort = ['createdAtTemporal' => -1];
                    break;
            }



            //BASE FILTROS PARA CONSULTA
            $pipeline = [
                ['$match' => [
                    'usersIds' => ['$in' => $usersIds]
                ]],
                ['$addFields' => [
                    'createdAtTemporal' => [
                        '$dateFromString' => [
                            'dateString' => '$createdAt',
                            'format' => '%Y-%m-%d %H:%M:%S'
                        ]
                    ],
                    'accounts' => [
                        '$cond' => [
                            'if' => ['$eq' => ['$accounts', []]],
                            'then' => [null],
                            'else' => '$accounts'
                        ]
                    ],
                    'nameNormalized' => [
                        '$toLower' => [
                            '$replaceAll' => [
                                'input' => [
                                    '$concat' => [
                                        '$name.first',
                                        '$name.second'
                                    ]
                                ],
                                'find' => ' ',
                                'replacement' => ''
                            ]
                        ]
                    ],
                    'surnameNormalized' => [
                        '$toLower' => [
                            '$replaceAll' => [
                                'input' => [
                                    '$concat' => [
                                        '$surname.first',
                                        '$surname.second'
                                    ]
                                ],
                                'find' => ' ',
                                'replacement' => ''
                            ]
                        ]
                    ],
                    'positionNormalized' => [
                        '$toLower' => [
                            '$replaceAll' => [
                                'input' => '$position',
                                'find' => ' ',
                                'replacement' => ''
                            ]
                        ]
                    ],
                    'emailNormalized' => [
                        '$toLower' => [
                            '$replaceAll' => [
                                'input' => '$email',
                                'find' => ' ',
                                'replacement' => ''
                            ]
                        ]
                    ],
                    'phoneNormalized' => [
                        '$toLower' => [
                            '$replaceAll' => [
                                'input' => '$phone',
                                'find' => ' ',
                                'replacement' => ''
                            ]
                        ]
                    ],
                ]],
                ['$addFields' => [
                    '_idString' => ['$toString' => '$_id'],
                ]]

            ];


            if ($filtersFromCookies === true || $isFirstTime === false){
               if (array_key_exists('agents', $filters)) {

                    if (empty($filters['agents'])) {
                        // Si no hay agentes activos → no devolver nada
                        $pipeline[] = [
                            '$match' => [
                                'createdBy' => ['$in' => ['___no_match___']]
                            ]
                        ];
                    } else {
                        $pipeline[] = [
                            '$match' => [
                                'createdBy' => ['$in' => $filters['agents']]
                            ]
                        ];
                    }
                }

            }


            if ($filtersFromCookies === true || $isFirstTime === false){
                $pipeline[] = ['$addFields' => [
                    '_idString' => ['$toString' => '$_id'],
                ]];
            }



            //dd($user['contactsArchived']);

            //Comprobación si es Agenda o archivado
            if ($isSeeingArchived === true){

                //Compruebo que esten dentro de las archivadas mias
                $pipeline[] = [
                    '$match' => [
                        '_idString' => ['$in' => $user['contactsArchived']]
                    ]
                ];

            }else if($isSeeingArchived === false){

                //Compruebo que no esten dentro de las archivadas mias
                $pipeline[] = [
                    '$match' => [
                        '_idString' => ['$nin' => $user['contactsArchived']]
                    ]
                ];
            }



        // Añadir condición de búsqueda por texto al pipeline
        $pipeline[] = [
            '$match' => [
                '$or' => [
                    ['nameNormalized' => ['$regex' => $regexPattern]], //Nombre
                    ['surnameNormalized' => ['$regex' => $regexPattern]], //apellidos
                    ['positionNormalized' => ['$regex' => $regexPattern]], //cargo
                    ['emailNormalized' => ['$regex' => $regexPattern]], //email
                    ['phoneNormalized' => ['$regex' => $regexPattern]], //telefono
                ]
            ]
        ];

            //dd($isFirstTime,$isFirstTime === null,$isFirstTime == null);


            //CONSULTA NUMERO TOTAL DE CONTACTOS
            $countPipeline = array_merge($pipeline, [['$count' => 'totalResults']]);

            // Obtener el número total de resultados
            $totalResults = Contact::raw(function ($collection) use ($countPipeline) {
                return $collection->aggregate($countPipeline)->toArray();
            });

            $totalResultsCount = $totalResults[0]['totalResults'] ?? 0;

            // Añado parte paginación
            $pipeline = array_merge($pipeline, [
                ['$sort' => $sort]
            ]);

            //Añado paginación solo si se quiere $paginate
            if ($paginate) {
                $pipeline = array_merge($pipeline, [
                    ['$skip' => $skip],
                    ['$limit' => $perPage]
                ]);
            }

            $contacts = Contact::raw(function ($collection) use ($pipeline) {
                return $collection->aggregate($pipeline);
            });

            foreach ($contacts as $contact) {

                $accounts = array_filter((array) $contact->accounts, fn($acc) => $acc !== null);

                $accounts = array_map(function ($acc) {
                    return Account::where('_id', $acc)->first();
                }, $accounts);

                // Reasignar el array ya procesado
                $contact->accounts = $accounts;
            }

        /*if ($isFirstTime === false)
            dd($filters, $contacts);*/




        return response()->json(['contacts' => $contacts, 'totalResults' => $totalResultsCount, 'filtersObtained' => $isFirstTime === true ? $resultsFilters : null], 200);
    }


    public function indexWithoutPagination($id, Request $request){

        $user = User::where('_id', $id)->first();
        $filters = $request['filters'];
        $filtersFromCookies = $request['filtersFromCookies'];

        //Saco cada uno de los contactos que tenga en la columna usersIds la id del usuario con sesion iniciada
        $contactsToShow = Contact::whereIn('usersIds', [$id])->get();

        $contacts = [
            'archived' => [],
            'notArchived' => []
        ];

        //Ahora basandome en el array de contactos archivados del usuario los separo en archivados y no archivados
        foreach ($contactsToShow as $contact){

            $isArchived = in_array($contact['_id'], $user['contactsArchived']);

            if ($isArchived)
                array_push($contacts['archived'], $contact);
            else
                array_push($contacts['notArchived'], $contact);
        }


        //Meto las cuenta de los contactos no archivados
        $contactNotArchived = $contacts['notArchived'];

        // Recorro cada uno de los contactos no archivados
        foreach ($contactNotArchived as $contactInd => $contact){
            // Si tiene alguna cuenta
            if (count($contact['accounts']) > 0){
                // Creo una nueva colección para almacenar las cuentas modificadas
                $updatedAccounts = [];
                // Las recorro
                foreach ($contact['accounts'] as $accountInd => $account){
                    // Modifico y agrego a la nueva colección
                    $updatedAccounts[$accountInd] = Account::where('_id', $account)->first();
                }
                // Asigno la nueva colección a la original
                $contactNotArchived[$contactInd]['accounts'] = $updatedAccounts;
            }
        }

        // Repito el mismo proceso para los contactos archivados
        $contactArchived = $contacts['archived'];

        foreach ($contactArchived as $contactInd => $contact){
            if (count($contact['accounts']) > 0){
                $updatedAccounts = [];
                foreach ($contact['accounts'] as $accountInd => $account){
                    $updatedAccounts[$accountInd] = Account::where('_id', $account)->first();
                }
                $contactArchived[$contactInd]['accounts'] = $updatedAccounts;
            }
        }

        $contacts['notArchived'] = $contactNotArchived;
        $contacts['archived'] = $contactArchived;



        //AHORA HAGO LO MISMO PARA LOS USUARIOS QUE TENGO POR DEBAJO
        $userList = json_decode($request['userList'], true);

        if (is_array($userList)){

            //Para cada uno de los usuarios
            foreach ($userList as $userInd => $userNow){

                //Saco los contactos del usuario
                $contactsToShowUser = Contact::whereIn('usersIds', [$userNow['_id']])->get();


                $contactsUsers = [
                    'archived' => [],
                    'notArchived' => []
                ];


                //Meto las ids
                foreach ($contactsToShowUser as $contact){

                    $isArchived = in_array($contact['_id'], $userNow['contactsArchived'] ?? []);

                    if ($isArchived)
                        array_push($contactsUsers['archived'], $contact);
                    else
                        array_push($contactsUsers['notArchived'], $contact);
                }


                //Meto las cuenta de los contactos no archivados
                $contactNotArchived = $contacts['notArchived'];

                //Recorro los contactos no archivados
                $contactNotArchivedUsers = $contactsUsers['notArchived'];

                // Recorro cada uno de los contactos no archivados
                foreach ($contactNotArchivedUsers as $contactInd => $contact){
                    // Si tiene alguna cuenta
                    if (count($contact['accounts']) > 0){
                        // Creo una nueva colección para almacenar las cuentas modificadas
                        $updatedAccounts = [];
                        // Las recorro
                        foreach ($contact['accounts'] as $accountInd => $account){
                            // Modifico y agrego a la nueva colección
                            $updatedAccounts[$accountInd] = Account::where('_id', $account)->first();
                        }
                        // Asigno la nueva colección a la original
                        $contactNotArchivedUsers[$contactInd]['accounts'] = $updatedAccounts;
                    }
                }

                // Repito el mismo proceso para los contactos archivados
                $contactArchivedUsers = $contactsUsers['archived'];

                foreach ($contactArchivedUsers as $contactInd => $contact){
                    if (count($contact['accounts']) > 0){
                        $updatedAccounts = [];
                        foreach ($contact['accounts'] as $accountInd => $account){
                            $updatedAccounts[$accountInd] = Account::where('_id', $account)->first();
                        }
                        $contactArchivedUsers[$contactInd]['accounts'] = $updatedAccounts;
                    }
                }


                //Lo meto al array primero
                $contacts['notArchived'] = [...$contacts['notArchived'], ...$contactsUsers['notArchived']];
                $contacts['archived'] = [...$contacts['archived'], ...$contactsUsers['archived']];
            }
        }



        //A cada una de las cuentas le meto su información relacionada ( para archivados y no archivados )
        if($contacts['notArchived'] && count($contacts['notArchived']) > 0){

            foreach ($contacts['notArchived'] as $contact) {

                //LE METO LAS TAREAS RELACIONADAS
                $contact['tasks'] = [];

                $tasksToShow = Task::where('contact', $contact['_id'])->get();

                $contact['tasks'] = [...$contact['tasks'], ...$tasksToShow];

                //Le meto los datos del usuario que lo ha creado
                $contact['createdBy'] = User::where('_id', $contact['createdBy'])->first();
            }
        }

        if($contacts['archived'] && count($contacts['archived']) > 0){

            foreach ($contacts['archived'] as $contact) {

                //LE METO LAS TAREAS RELACIONADAS
                $contact['tasks'] = [];

                $tasksToShow = Task::where('contact', $contact['_id'])->get();

                $contact['tasks'] = [...$contact['tasks'], ...$tasksToShow];

                //Le meto los datos del usuario que lo ha creado
                $contact['createdBy'] = User::where('_id', $contact['createdBy'])->first();
            }
        }


        return response()->json(['contacts' => $contacts], 200);
    }

    //funcion para sacar un contacto en particular
    public function show($id){

        $contact = Contact::where('_id', $id)->first();

        //Saco tb los datos relacionados con la cuenta

        //LE METO LAS TAREAS RELACIONADAS
        $contact['tasks'] = [];

        $tasksToShow = Task::where('contact', $contact['_id'])->get();

        $contact['tasks'] = [...$contact['tasks'], ...$tasksToShow];

        //Correos relacionados
        $contact['mails'] = Email::where('recipients.email', $contact['email'])->get()->toArray();

        //Le meto los datos del usuario que lo ha creado
        $contact['createdBy'] = User::where('_id', $contact['createdBy'])->first();

        return response()->json(['contact' => $contact], 200);
    }


    //funcion para eliminar un contacto
    public function deleteContact($id){

        //Lo saco de el array de contactos del usuario
        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        //Tengo que comprobar si esta la id del contacto dentro de el array de contactos archivados, y si lo esta, borrarlo
        $contactsArchived = $userToModify->contactsArchived;

        $position = array_search($id, $contactsArchived);

        if ($position)
            unset($contactsArchived[$position]);


        $userToModify->contactsArchived = $contactsArchived;

        $userToModify->save();


        //Compruebo si el contacto tenia alguna tarea relacionada
        $tasks = Task::whereIn('contact',[$id])->get();

        if ($tasks && count($tasks) > 0){

            //Para cada una de las cuentas
            foreach ($tasks as $taskInd => $task){

                //compruebo el id
                if ($task['contact'] === $id)
                    $task['contact'] = '';

                $task->save();
            }
        }

        //Borro el contacto de la bbdd
        Contact::destroy($id);

        return response()->json(['message' => 'El contacto ha sido eliminado correctamente'],200);
    }

    //funcion para eliminar todos los contactos seleccionados
    public function deleteAllSelectedContact(Request $request){

        $idsToRemove = $request['idsToRemove'];

        //Lo saco de el array de contactos del usuario
        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $contactsArchived = $userToModify->contactsArchived;

        //Para cada uno compruebo si esta en el array de archivados, si esta lo saco y este o no lo borro
        foreach ($idsToRemove as $id){

            if (in_array($id, $contactsArchived))
                unset($contactsArchived[$id]);

            //Borro el contacto de la bbdd
            Contact::destroy($id);


            //Borro si tiene tareas relacionadas
            $tasks = Task::whereIn('contact',[$id])->get();

            if ($tasks && count($tasks) > 0){

                //Para cada una de las cuentas
                foreach ($tasks as $taskInd => $task){

                    //compruebo el id
                    if ($task['contact'] === $id)
                        $task['contact'] = '';

                    $task->save();
                }
            }
        }

        $userToModify->contactsArchived = $contactsArchived;

        $userToModify->save();

        return response()->json(['message' => 'El contacto ha sido eliminado correctamente'],200);
    }

    //funcion para archivar contacto
    public function toggleArchiveContact($id, Request $request){

        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $contactsArchived = $userToModify->contactsArchived;


        if (in_array($id ,$contactsArchived)){
            $key = array_search($id, $contactsArchived);
            unset($contactsArchived[$key]);
        }else{
            array_push($contactsArchived, $id);
        }


        $userToModify->contactsArchived = $contactsArchived;

        $userToModify->save();

        return response()->json(['message' => 'La archivación ha sido alterada'], 200);
    }

    //funcion para archivar todos los contactos seleccionados
    public function toggleArchiveSelectedContacts(Request $request){

        $idsToToggle = $request['idsToToggle'];

        $userToModify = User::where('_id', session()->get('userLogged')->_id)->first();

        $contactsArchived = $userToModify->contactsArchived;

        //si es para archivar
        foreach ($idsToToggle as $id){
            if ($request['isForArchiving']) {
                array_push($contactsArchived, $id);
            }else{

                $key = array_search($id, $contactsArchived);

                unset($contactsArchived[$key]);
            }
        }

        $userToModify->contactsArchived = $contactsArchived;

        $userToModify->save();

        return response()->json(['message' => 'La archivación ha sido alterada'], 200);
    }


    //funcion para obtener las cuentas relacionadas con un contacto
    public function getAccountsRelated($id, Request $request){

        $userList = $request['userList'];

        //Saco las cuentas que tengas en usersId la id del usuario con sesion iniciada
        $accountsRelated = Account::whereIn('usersIds', [$id])->get();

        //Saco tb las cuentas de los usuarios de por debajo tuya
        if ($userList && count($userList) > 0){
            foreach ($userList as $userInd => $user){
                $accountsRelated = [...$accountsRelated, ...Account::whereIn('usersIds', [$user['_id']])->get()];
            }
        }

        return response()->json(['accounts' => $accountsRelated]);
    }


    //funcion para obtener las cuentas relacionadas con un contacto
    public function getContactOpportunities($id){

        $opportunities = Opportunity::where('contact.value', $id)->get();

        return response()->json(['opportunities' => $opportunities]);
    }

}
