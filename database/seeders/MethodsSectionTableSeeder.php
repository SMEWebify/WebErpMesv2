<?php

namespace Database\Seeders;

use App\Models\Methods\MethodsSection;
use Illuminate\Database\Seeder;

class MethodsSectionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * the user id is hard-defined. It's dangerous, 
         * but it allows to have more explicit data than a factory can do
         */
        $sections = array(
            array(
                'code' => 'ADM', 
                'ordre' => '3', 
                'label' => 'Administrative personnal',
                'user_id' => '1', 
                'color' => '#1af50a',
            ),
            array(
                'code' => 'WELDWORK', 
                'ordre' => '4', 
                'label' => 'Welding WorkShop',
                'user_id' => '1', 
                'color' => '#f40101',
            ),
            array(
                'code' => 'BENDWORK', 
                'ordre' => '5', 
                'label' => 'Bending WorkShop',
                'user_id' => '1', 
                'color' => '#411fea',
            ),
            array(
                'code' => 'LASERWORK', 
                'ordre' => '6', 
                'label' => 'Laser WorkShop',
                'user_id' => '1', 
                'color' => '#ed07e6',
            ),
    );
    
    foreach ($sections as $section) {
        MethodsSection::create([
            'ordre' => $section['ordre'],
            'code' => $section['code'],
            'label' => $section['label'],
            'user_id' => $section['user_id'],
            'color' => $section['color'],
        ]);
    }
    }
}
