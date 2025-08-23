<?php

namespace Tests\Feature;

use App\Models\EnergyConsumption;
use App\Models\Machine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnergyConsumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_cost_is_calculated()
    {
        $machine = Machine::factory()->create();

        $consumption = EnergyConsumption::create([
            'machine_id' => $machine->id,
            'kwh' => 20,
            'cost_per_kwh' => 0.5,
        ]);

        $this->assertEquals(10.0, $consumption->total_cost);
    }

    public function test_can_create_energy_consumption_record()
    {
        $machine = Machine::factory()->create();

        $response = $this->postJson('/api/energy-consumptions', [
            'machine_id' => $machine->id,
            'kwh' => 10,
            'cost_per_kwh' => 0.5,
        ]);

        $response->assertCreated()
                 ->assertJsonFragment(['total_cost' => 5.0]);

        $this->assertDatabaseHas('energy_consumptions', [
            'machine_id' => $machine->id,
            'total_cost' => 5.0,
        ]);
    }

    public function test_it_displays_energy_consumptions()
    {
        $consumption = EnergyConsumption::factory()->create();

        $response = $this->getJson('/api/energy-consumptions');

        $response->assertOk()
                 ->assertJsonFragment([
                     'id' => $consumption->id,
                     'total_cost' => $consumption->total_cost,
                 ]);
    }
}

