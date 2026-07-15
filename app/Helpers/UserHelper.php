<?php

namespace App\Helpers;

use App\Http\Controllers\AuthController;
use App\Http\Models\User;
use Carbon\Carbon;

class UserHelper
{
    public static function hierarchy($_id)
    {

        // Obtenemos todos los usuarios de una sola vez
        $allUsers = User::get()->makeHidden(['password', 'contactsArchived', 'accountsArchived', 'opportunitiesArchived', 'verification_code', 'remember_token']);

        // Creamos un array para almacenar los resultados
        $result = [];

        // Función recursiva interna que no necesita hacer consultas adicionales
        $findSubordinates = function($userId) use (&$findSubordinates, $allUsers, &$result) {
            foreach ($allUsers as $user) {
                // Si este usuario tiene como responsable al usuario actual
                if (in_array($userId, $user['responsibles'])) {
                    // Solo lo añadimos si no está ya en los resultados
                    if (!isset($result[$user['_id']])) {
                        $result[$user['_id']] = $user->toArray();
                        // Buscamos recursivamente los subordinados de este usuario
                        $findSubordinates($user['_id']);
                    }
                }
            }
        };

        // Iniciamos la búsqueda con el ID proporcionado
        $findSubordinates($_id);

        return array_values($result);

    }

    public static function otherProperties($user)
    {
        $properties = [
            'accountVerifiedAt' => '',
            'profileImage' => 'default.jpg',
            'isActive' => true,
            'favDevices' => [],
            'favRoutes' => [],
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s'),
            'layout' => [],
            'permissions' => [],
            'remember_token' => '',
        ];

        foreach ($properties as $property => $value)
            $user[$property] = $value;

        return $user;
    }

    public static function generatePassword()
    {
        $pass = "";
        $regex = "/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])[0-9a-zA-Z]{8,}$/";
        do $pass = self::newPassword(8); while (!preg_match($regex, $pass));

        return $pass;
    }


    public static function generateSerial($length, $isAdvanced = false)
    {
        $serial = '';

        $regex = "/^(?=.*\d)(?=.*[A-Z])[0-9A-Z]{8,}$/";
        do {
            $serial = self::newPassword(10, true);
        } while (!preg_match($regex, $serial));

        return $isAdvanced ? 'AD' : 'TR' . $serial;
    }

    private static function newPassword($length = 8, $onlyUppercase = false)
    {
        if ($onlyUppercase)
            return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);

        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }

    public static function getUserSubdomain($userId) {
        $userListTop = AuthController::getAllSuperiors($userId);
        $key = array_search('Usuario subdominio', array_column($userListTop, 'label'));
        return $userListTop[$key];
    }

    public static function getSubdomainUserList($userSubdomain) {

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

            // EXCLUIR USUARIOS CONCRETOS
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

        return $subdomainUserList;
    }
}
