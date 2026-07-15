<?php

namespace Database\Seeders;

use App\Helpers\GeneralHelper;
use App\Http\Models\User;
use Carbon\Carbon;
use Faker\Provider\es_ES\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //Usuario GOD
        $godId = User::insertGetId([
            'firstName' => 'Francisco Javier',
            'lastName' => 'Pérez García',
            'gender' => 'M',
            'profileImage' => 'default.jpg',
            'label' => 'desarrollador',
            'isActive' => true,
            'email' => "franpergar02@gmail.com",
            'phone' =>'605581287',
            'password' => Hash::make("1234"),
            'address' => 'Dirección de casa',
            'postal' => '14010',
            'contactsArchived' => [],
            'accountsArchived' => [],
            'opportunitiesArchived' => [],
            'responsibles' => [],
            'dni' => Person::dni(),
            'verification_code' => '',
            'permissions' => ['RESUS', 'GESCON', 'DRIVE'],
            'createdAt' => Carbon::now()->timestamp
        ]);

        //Usuario asercord
        $asercordId = User::insertGetId([
            'firstName' => 'Asercord',
            'lastName' => 'Energía',
            'gender' => 'M',
            'profileImage' => 'default.jpg',
            'label' => 'desarrollador',
            'isActive' => true,
            'email' => "comercial@asercordenergia.com",
            'phone' =>'1',
            'password' => Hash::make("1234"),
            'address' => 'Dirección de casa',
            'postal' => '14010',
            'contactsArchived' => [],
            'accountsArchived' => [],
            'opportunitiesArchived' => [],
            'responsibles' => [(string) $godId],
            'dni' => Person::dni(),
            'verification_code' => '',
            'permissions' => ['RESUS', 'GESCON', 'DRIVE'],
            'createdAt' => Carbon::now()->timestamp
        ]);


        //Usuario visualizador
         $visualizer = User::insertGetId([
            'firstName' => 'Visualizador',
            'lastName' => 'crm',
            'gender' => 'M',
            'profileImage' => 'default.jpg',
            'label' => 'usuario',
            'isActive' => true,
            'email' => "visualizer@crm.com",
            'phone' =>'2',
            'password' => Hash::make("1234"),
            'address' => 'Dirección de casa',
            'postal' => '14010',
            'contactsArchived' => [],
            'accountsArchived' => [],
            'opportunitiesArchived' => [],
            'responsibles' => [(string) $godId],
            'dni' => Person::dni(),
            'verification_code' => '',
            'permissions' => ['READONLY'],
            'createdAt' => Carbon::now()->timestamp
        ]);


        //Usuario de cliente
        User::create([
            'firstName' => 'Cliente',
            'lastName' => 'crm',
            'gender' => 'F',
            'profileImage' => 'default.jpg',
            'label' => 'usuario',
            'isActive' => true,
            'email' => "client@crm.com",
            'phone' =>'3',
            'password' => Hash::make("1234"),
            'address' => 'Dirección de casa',
            'postal' => '14010',
            'contactsArchived' => [],
            'accountsArchived' => [],
            'opportunitiesArchived' => [],
            'responsibles' => [(string) $asercordId, (string) $visualizer],
            'dni' => Person::dni(),
            'verification_code' => '',
            'permissions' => ['READONLY'],
            'createdAt' => Carbon::now()->timestamp
        ]);
    }
}
