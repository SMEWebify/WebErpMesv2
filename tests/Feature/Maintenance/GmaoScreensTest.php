<?php

namespace Tests\Feature\Maintenance;

use App\Models\Assets\Asset;
use App\Models\Maintenance\MaintenancePlan;
use App\Models\Maintenance\WorkOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GmaoScreensTest extends TestCase
{
    use RefreshDatabase;

    private MaintenancePlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('asset_manager', 'web'));
        $this->actingAs($user);
        app()->setLocale('fr');

        $asset = Asset::create([
            'name' => 'Plieuse',
            'acquisition_value' => 1000,
            'acquisition_date' => '2020-01-01',
            'depreciation_duration' => 5,
        ]);
        $this->plan = MaintenancePlan::create([
            'asset_id' => $asset->id,
            'title' => 'Graissage',
            'trigger_type' => 'time',
            'interval_days' => 30,
            'auto_generate' => true,
            'lead_days' => 7,
        ]);
    }

    public function test_screens_render_translated_labels(): void
    {
        $this->post(route('gmao.maintenance-plans.generate-work-order', $this->plan->id))->assertRedirect();
        $order = WorkOrder::firstOrFail();
        $this->assertSame($this->plan->id, $order->maintenance_plan_id);

        $this->get(route('gmao.maintenance-plans.show', $this->plan->id))->assertOk()
            ->assertSee('Périodique (calendrier)')
            ->assertSee('Planifié');
        $this->get(route('gmao.work-orders.show', $order->id))->assertOk()
            ->assertSee('Préventif')
            ->assertSee('Graissage');
        $this->get(route('gmao.work-orders.index'))->assertOk()->assertSee('Moyenne');
        $this->get(route('gmao.maintenance-plans.create'))->assertOk()->assertSee('Périodicité (jours)');
        $this->get(route('gmao.maintenance-plans.edit', $this->plan->id))->assertOk();
        $this->get(route('gmao.work-orders.create'))->assertOk()->assertSee('Curatif');
        $this->get(route('gmao.dashboard'))->assertOk();
    }

    public function test_auto_generation_requires_an_interval_but_plain_edit_does_not(): void
    {
        $payload = [
            'asset_id' => $this->plan->asset_id,
            'title' => 'Graissage',
            'trigger_type' => 'time',
            'interval_days' => '',
            'lead_days' => 7,
        ];

        $this->put(route('gmao.maintenance-plans.update', $this->plan->id), $payload + ['auto_generate' => 1])
            ->assertSessionHasErrors('interval_days');

        $this->put(route('gmao.maintenance-plans.update', $this->plan->id), $payload + ['auto_generate' => 0])
            ->assertSessionHasNoErrors();
        $this->assertFalse($this->plan->fresh()->auto_generate);
    }
}
