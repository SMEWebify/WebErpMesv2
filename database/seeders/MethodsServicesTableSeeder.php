<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Methods\MethodsServices;

class MethodsServicesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $services = [
            [
                'code' => 'Politics',
                'ordre' => 'politics',
                'label' => 'To discuss politics',
                'type' => 'To discuss politics',
                'hourly_rate' => 'Politics',
                'margin' => 'politics',
                'color' => 'To discuss politics',
                'picture' => 'To discuss politics',
                'compannie_id' => 'To discuss politics',
            ],
        ];

        foreach ($services as $service) {
            MethodsServices::create($service);
        }
    }
}
