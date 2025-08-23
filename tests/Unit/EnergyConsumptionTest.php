<?php

namespace Tests\Unit;

use App\Models\EnergyConsumption;
use PHPUnit\Framework\TestCase;

class EnergyConsumptionTest extends TestCase
{
    public function test_cost_attribute_is_computed()
    {
        config(['energy.price_per_kwh' => 0.2]);
        $model = new EnergyConsumption(['kwh_consumed' => 5]);
        $this->assertSame(1.0, $model->cost);
    }
}
