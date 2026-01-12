<?php

namespace Tests\Feature;

use App\Models\EnergyConsumption;
use App\Models\Methods\MethodsRessources;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnergyConsumptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_cost_is_calculated()
    {
        $resource = MethodsRessources::factory()->create();

        $consumption = EnergyConsumption::create([
            'methods_ressource_id' => $resource->id,
            'kwh' => 20,
            'cost_per_kwh' => 0.5,
        ]);

        $this->assertEquals(10.0, $consumption->total_cost);
    }

    public function test_can_create_energy_consumption_record()
    {
        $resource = MethodsRessources::factory()->create();

        $this->authenticateApiUser();
        $response = $this->postJson('/api/energy-consumptions', [
            'methods_ressource_id' => $resource->id,
            'kwh' => 10,
            'cost_per_kwh' => 0.5,
        ]);

        $response->assertCreated()
                 ->assertJsonFragment(['total_cost' => 5.0]);

        $this->assertDatabaseHas('energy_consumptions', [
            'methods_ressource_id' => $resource->id,
            'total_cost' => 5.0,
        ]);
    }

    public function test_it_displays_energy_consumptions()
    {
        $consumption = EnergyConsumption::factory()->create();

        $this->authenticateApiUser();
        $response = $this->getJson('/api/energy-consumptions');

        $response->assertOk()
                 ->assertJsonFragment([
                     'id' => $consumption->id,
                     'total_cost' => $consumption->total_cost,
                 ]);
    }
}
