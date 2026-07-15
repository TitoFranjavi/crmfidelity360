<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EnterpriseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Enterprise::create([
            'name' => 'Segenet',
            'email' => 'areatecnica@segenet.es',
            'phone' => '957138035',
            'mobile' => '630911922',
            'color_login' => 'blue',
            'assets' => 'segenet',
            'link' => 'localhost:3000'
        ]);

        Enterprise::create([
            'name' => 'Tecum Consultores',
            'email' => 'areatecnica@otra.es',
            'phone' => '957138035',
            'mobile' => '630911922',
            'color_login' => 'yellow',
            'assets' => 'tecum',
            'link' => 'segevue.test'
        ]);
    }
}
