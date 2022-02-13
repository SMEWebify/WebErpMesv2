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
        $services = array(
                array(
                    'code' => 'MAT', 
                    'ordre' => '1', 
                    'label' => 'MATERIAL',
                    'type' => '3', 
                    'hourly_rate' => '0', 
                    'margin' => '10',
                    'color' => '#7d7373',
                ),
                array(
                    'code' => 'LAS', 
                    'ordre' => '2', 
                    'label' => 'LASER',
                    'type' => '1', 
                    'hourly_rate' => '210', 
                    'margin' => '10',
                    'color' => '#f3f702',
                ),
                array(
                    'code' => 'BEND', 
                    'ordre' => '3', 
                    'label' => 'BENDING',
                    'type' => '1', 
                    'hourly_rate' => '50', 
                    'margin' => '0',
                    'color' => '#1af50a',
                ),
                array(
                    'code' => 'WELD', 
                    'ordre' => '4', 
                    'label' => 'WELDING',
                    'type' => '1', 
                    'hourly_rate' => '50', 
                    'margin' => '50',
                    'color' => '#f40101',
                ),
                array(
                    'code' => 'PAINT', 
                    'ordre' => '5', 
                    'label' => 'PAINTING',
                    'type' => '7', 
                    'hourly_rate' => '0', 
                    'margin' => '15',
                    'color' => '#411fea',
                ),
                array(
                    'code' => 'PUR', 
                    'ordre' => '6', 
                    'label' => 'PURCHASE',
                    'type' => '6', 
                    'hourly_rate' => '0', 
                    'margin' => '15',
                    'color' => '#ed07e6',
                ),
                array(
                    'code' => 'COMPO', 
                    'ordre' => '7', 
                    'label' => 'COMPONANT',
                    'type' => '8', 
                    'hourly_rate' => '0', 
                    'margin' => '0',
                    'color' => '#fda308',
                ),
        );
        
        foreach ($services as $service) {
            MethodsServices::create([
                'code' => $service['code'],
                'ordre' => $service['ordre'],
                'label' => $service['label'],
                'type' => $service['type'],
                'hourly_rate' => $service['hourly_rate'],
                'margin' => $service['margin'],
                'color' => $service['color'],
            ]);
        }
    }
}
