<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Http\Models\Account;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition()
    {
        $isCompany = fake()->boolean(60); // 60% empresas

        return [
            'name' => $isCompany
                ? fake()->company() . ' SL'
                : fake()->name(),

            'accType' => '',
            'sector' => '',

    
            'CIF' => $isCompany
                ? strtoupper(fake()->bothify('B########'))
                : strtoupper(fake()->bothify('########?')),

            'origin' => '',
            'phone' => fake()->numerify('6########'),
            'landLinePhone' => '',
            'website' => '',
            'email' => fake()->safeEmail(),

            'observations' => '',
            'principalAcc' => '',

            'billingInfo' => (object) [
                'community' => 'Andalucía',
                'province' => 'Córdoba',
                'locality' => fake()->city(),
                'address' => fake()->streetAddress(),
                'zipCode' => fake()->postcode(),
            ],

            'customFields' => [],
            'profileImage' => null,
            'opportunity' => '',

            'usersIds' => [],
            'createdBy' => null,

            'createdAt' => now()->format('Y-m-d H:i:s'),
        ];
    }
}