<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Http\Models\Order;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        // 🔥 estado aleatorio
        $status = fake()->randomElement([
            'p', 'p', 'p', // más probabilidad
            't', 't',
            'a'
        ]);

        $statusDate = now()->format('Y-m-d H:i:s');

        $marketer = fake()->randomElement(['Naturgy', 'Iberdrola']);
        $iban = 'ES' . fake()->numerify('######################');


        return [
            'name' => fake()->name(),

            'direc' => fake()->streetAddress(),
            'zip' => fake()->postcode(),
            'town' => strtoupper(fake()->city()),
            'province' => strtoupper(fake()->state()),

            'source' => '',
            'observations' => '',
            'incidence' => '',

            'processingDate' => now()->format('Y-m-d'),
            'activationDate' => now()->addDays(rand(1, 10))->format('Y-m-d'),
            'lowDate' => '',

            'liquidationStatus' => fake()->randomElement(['al', 'pd']),
            'productType' => 'cl',

            'marketer' => $marketer,
            'fee' => 'Tarifa 2.0TD',
            'product' => $marketer === 'Naturgy'
                ? 'Por uso luz'
                : 'Plan Hogar Especial < 10 kW ZAR',

            'commissions' => ['subdomain' => rand(70, 120), 'breakdown' => []],

            'CUPS' => 'ES' . fake()->numerify('##################'),
            'consumption' => rand(1, 10),

            'potencyFee' => '',
            'IBAN' => implode(' ', str_split($iban, 4)),

            'docs' => [],
            'errors' => [],

            // 🔥 SOLO UN ESTADO
            'statuses' => [
                [
                    'code' => $status,
                    'date' => $statusDate,
                ]
            ],

            'lastStatus' => [
                'code' => $status,
                'date' => $statusDate
            ],

            'transferDate' => now()->format('d/m/y'),

            'consumptionData' => (object) [
                'consumption' => [1,1,1,0,0,0],
                'hiredPotency' => [1,1,0,0,0,0],
            ],

            'hiredPotency' => '1.000',

            'lastUpdate' => $statusDate,
            'owner' => fake()->firstName(),

            'createdAt' => $statusDate,

            'account' => null,
            'usersIds' => [],
            'createdBy' => null,

            'search_cif' => '',
            'search_cups' => '',
            'search_direc' => '',
            'search_iban' => '',
            'search_name' => '',
            'search_product' => '',
            'search_province' => '',
            'search_town' => '',
        ];
    }
}
