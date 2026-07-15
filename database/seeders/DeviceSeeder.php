<?php

namespace Database\Seeders;

use App\Http\Models\User;
use App\Models\Device;
use App\Models\DeviceRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::where('email', 'email1@email.com')->first();

        $deviceDefault =
            [
                "model" => [
                    "code" => "counter",
                    "title" => "Contador"
                ],
                "name" => "Dafisa",
                "color" => "red",
                "icon" => "fa-plug-circle-bolt",
                "isActive" => true,
                "serial" => "50551265",
                "cups" => "ES0031104339726001AS0F",
                "lastUpdate" => "2022-12-01 00:00:00",
                "contracts" => [
                    [
                        "id" => Carbon::now()->getPreciseTimestamp(3),
                        "rate" => 5,
                        "init" => "2022-01-01",
                        "coefficients" => [
                            "ind80" => 0.062332,
                            "ind95" => 0.041554,
                            "cap" => 0.05
                        ],
                        "termsPotency" => [
                            "22.41711",
                            "20.370815",
                            "11.478137",
                            "9.055455",
                            "1.992116",
                            "1.185268"
                        ],
                        "termsEnergy" => [
                            "0.140484",
                            "0.127555",
                            "0.103175",
                            "0.090685",
                            "0.080988",
                            "0.080988"
                        ],
                        "isIndexed" => 0,
                        "adjust" => 0,
                        "hiredPotency" => [
                            "854",
                            "873",
                            "873",
                            "873",
                            "873",
                            "873"
                        ],
                        "powerTransform" => 0,
                        "rental" => 0,
                        "reductionTax" => 0,
                        "indexedType" => "passPool",
                        "fee" => 3,
                        "errors" => [
                        ]
                    ],
                    [
                        "id" => Carbon::now()->addSecond()->getPreciseTimestamp(3),
                        "rate" => 5,
                        "init" => "2022-07-02",
                        "coefficients" => [
                            "ind80" => 0.062332,
                            "ind95" => 0.041554,
                            "cap" => 0.05
                        ],
                        "termsPotency" => [
                            "22.41711",
                            "20.370815",
                            "11.478137",
                            "9.055455",
                            "1.992116",
                            "1.185268"
                        ],
                        "termsEnergy" => [
                            "0.140484",
                            "0.127555",
                            "0.103175",
                            "0.090685",
                            "0.080988",
                            "0.080988"
                        ],
                        "isIndexed" => 0,
                        "adjust" => 0,
                        "hiredPotency" => [
                            "854",
                            "873",
                            "873",
                            "873",
                            "873",
                            "873"
                        ],
                        "powerTransform" => 0,
                        "rental" => 0,
                        "reductionTax" => 0,
                        "indexedType" => "passPool",
                        "fee" => 3,
                        "errors" => [
                        ]
                    ]
                ],
                "createdAt" => "2023-01-09 12:35:58"
            ];

        $device = Device::insertGetId($deviceDefault);


        $user->devices = [$device];
        $user->save();


        $QUARTERS = 96;
        $days = 364;

        $date = Carbon::parse('1 january 2022 at 00:00');

        $absolute = [
            'active' => 0,
            'inductive' => 0,
            'capacitive' => 0,
        ];

        $lastUpdate = '';

        while ($date < Carbon::now()) {
            // energía activa
            $relative['active'] = random_int(0, 8);
            $absolute['active'] += $relative['active'];

            // energía inductiva
            $relative['inductive'] = random_int(0, 2);
            $absolute['inductive'] += $relative['inductive'];

            // energía capacitiva
            $relative['capacitive'] = random_int(0, 1);
            $absolute['capacitive'] += $relative['capacitive'];

            $cosinePhi['inductive'] = $relative['active'] > 0 ? $relative['active'] / (sqrt(pow($relative['active'], 2) + pow($relative['inductive'], 2))) : 1;
            $cosinePhi['capacitive'] = $relative['active'] > 0 ? $relative['active'] / (sqrt(pow($relative['active'], 2) + pow($relative['capacitive'], 2))) : 1;

            DeviceRecord::create([
                'deviceId' => $device,
                'createdAt' => $date->format('Y-m-d H:i:s'),
                'active' => ['absolute' => $absolute['active'], 'relative' => $relative['active']],
                'inductive' => ['absolute' => $absolute['inductive'], 'relative' => $relative['inductive']],
                'capacitive' => ['absolute' => $absolute['capacitive'], 'relative' => $relative['capacitive']],
                'cosinePhi' => ['inductive' => $cosinePhi['inductive'], 'capacitive' => $cosinePhi['capacitive']]
            ]);

            $lastUpdate = $date;
            $date = $date->addMinutes(15);
        }

        $deviceObject = Device::where('_id', $device)->first();
        $deviceObject->lastUpdate = $lastUpdate->format('Y-m-d H:i:s');
        $deviceObject->save();


    }
}
