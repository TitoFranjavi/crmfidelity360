<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Http\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Http\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = User::class;
    public function definition()
    {
        return [
            'firstName' => fake()->firstName(),
            'lastName' => fake()->lastName(),
            'gender' => fake()->randomElement(['M', 'F']),

            'profileImage' => 'default.jpg',

            'label' => fake()->randomElement(['Comercial', 'Usuario']),

            'isActive' => true,

            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('1########'),

            'password' => bcrypt('password'),

            'address' => fake()->company(),
            'postal' => fake()->postcode(),
            'locality' => 'Córdoba',
            'province' => 'Córdoba',

            'contactsArchived' => [],
            'accountsArchived' => (object) [],
            'opportunitiesArchived' => [],
            'responsibles' => [],
            'permissions' => [],

            'dni' => strtoupper(fake()->bothify('########?')),
            'verification_code' => null,

            'createdAt' => fake()->unixTime(),

            'remember_token' => Str::random(60),

            'accounts' => [], // 👈 importante

            'recover' => (object) [],
            'commissions' => (object) [],

            'marketers' => [],
            'docs' => [],

            'temporalActive' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'accountVerifiedAt' => null,
        ]);
    }
}
