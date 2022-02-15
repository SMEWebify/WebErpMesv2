<?php

namespace Database\Seeders;

use App\Models\Methods\MethodsUnits;
use Illuminate\Database\Seeder;
use SebastianBergmann\CodeCoverage\Report\Xml\Unit;

class MethodsUnitTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $units = array(
                array(
                    'code' => 'KG',  
                    'label' => 'Kilogramme',
                    'type' => '1', 
                ),
                array(
                    'code' => 'MM', 
                    'label' => 'Millimeter',
                    'type' => '2', 
                ),
                array(
                    'code' => 'UNIT', 
                    'label' => 'Unit',
                    'type' => '5', 
                ),
        );
        
        foreach ($units as $unit) {
            MethodsUnits::create([
                'code' => $unit['code'],
                'label' => $unit['label'],
                'type' => $unit['type'],
            ]);
        }
    }
}
