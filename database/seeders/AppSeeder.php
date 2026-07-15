<?php

namespace Database\Seeders;

use App\Models\Festivity;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $days = [
            '2023-12-25',
            '2023-12-08',
            '2023-12-06',
            '2023-11-09',
            '2023-11-01',
            '2023-10-12',
            '2023-08-15',
            '2023-05-15',
            '2023-05-02',
            '2023-05-01',
            '2023-04-07',
            '2023-04-06',
            '2023-03-20',
            '2023-01-06',
            '2022-12-08',
            '2022-12-06',
            '2022-11-01',
            '2022-10-12',
            '2022-08-15',
            '2022-01-01'
        ];

        $dayGrouped = [];

        foreach ($days as $day)
            $dayGrouped[Carbon::parse($day)->format('Y')][] = $day;

        foreach ($dayGrouped as $year => $days) {
            Festivity::create([
                'year' => $year,
                'days' => $days
            ]);
        }

    }
}
