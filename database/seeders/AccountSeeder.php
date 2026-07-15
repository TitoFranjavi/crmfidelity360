<?php

namespace Database\Seeders;

use App\Http\Models\Account;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        Account::create([
            'name' => '',
            'owner' => '', //Al crearlo sera el que tiene sesión iniciada
            'phone' => '',
            'principalAcc' => '',//Se relaciona con otra cuenta
            'type' => '',
            'sector' => '',
            'moreInfo' => [
                'website' => '',
                'desc' => '',
                'phone' => '',
            ],
            'addressInfo' => [
                'billing' => [],
                'shipment' => []
            ],
            'createdAt' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

    }
}
