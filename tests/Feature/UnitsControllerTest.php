<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Methods\MethodsUnits;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UnitsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_display_units_list()
    {
        $units = MethodsUnits::factory()->count(3)->create();

        $response = $this->get(route('methods.unit'));

        $response->assertStatus(200);
        $response->assertViewHas('MethodsUnits');
        $response->assertSee($units->first()->label);
    }

    /** @test */
    public function it_can_store_a_unit()
    {
        $data = [
            'code' => 'U001',
            'label' => 'Unit Label',
            'type' => 'Unit Type',
        ];

        $response = $this->post(route('methods.unit.create'), $data);

        $response->assertRedirect(route('methods.unit'));
        $this->assertDatabaseHas('methods_units', $data);
    }

    /** @test */
    public function it_can_update_a_unit()
    {
        $unit = MethodsUnits::factory()->create([
            'default' => 0,
        ]);

        $data = [
            'id' => $unit->id,
            'label' => 'Updated Label',
            'type' => 'Updated Type',
            'default' => 1,
        ];

        $response = $this->post(route('methods.unit.update', ['id' => $unit->id]), $data);

        $response->assertRedirect(route('methods.unit'));
        $this->assertDatabaseHas('methods_units', [
            'id' => $unit->id,
            'label' => 'Updated Label',
            'type' => 'Updated Type',
            'default' => 1,
        ]);
        $this->assertDatabaseMissing('methods_units', ['default' => 1, 'id' => '!= ' . $unit->id]);
    }
}
