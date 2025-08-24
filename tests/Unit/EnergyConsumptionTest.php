<?php

namespace Tests\Unit;

use App\Models\EnergyConsumption;
use PHPUnit\Framework\TestCase;

class EnergyConsumptionTest extends TestCase
{
    public function test_total_cost_attribute_is_computed()

    {
        $model = new EnergyConsumption([
            'kwh' => 5,
            'cost_per_kwh' => 0.2,
        ]);


        $this->assertSame(1.0, $model->total_cost);
    }
}
