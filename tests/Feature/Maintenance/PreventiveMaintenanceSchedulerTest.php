<?php

namespace Tests\Feature\Maintenance;

use App\Models\Assets\Asset;
use App\Models\Maintenance\MaintenancePlan;
use App\Models\Maintenance\WorkOrder;
use App\Services\Maintenance\PreventiveMaintenanceScheduler;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreventiveMaintenanceSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->asset = Asset::create([
            'name' => 'Plieuse',
            'acquisition_value' => 1000,
            'acquisition_date' => '2020-01-01',
            'depreciation_duration' => 5,
        ]);
    }

    private function plan(array $attributes): MaintenancePlan
    {
        return MaintenancePlan::create($attributes + [
            'asset_id' => $this->asset->id,
            'title' => 'Graissage',
            'trigger_type' => 'time',
            'auto_generate' => true,
            'lead_days' => 7,
        ]);
    }

    private function generate(string $today): \Illuminate\Support\Collection
    {
        return app(PreventiveMaintenanceScheduler::class)->generateDue(Carbon::parse($today));
    }

    public function test_time_based_plan_creates_an_order_within_the_lead_time(): void
    {
        $plan = $this->plan(['interval_days' => 30, 'fixed_date' => '2026-11-01']);

        $this->assertCount(0, $this->generate('2026-10-24'));
        $created = $this->generate('2026-10-25');

        $this->assertCount(1, $created);
        $order = $created->first()['work_order'];
        $this->assertSame($plan->id, $order->maintenance_plan_id);
        $this->assertSame('2026-11-01', $order->scheduled_at->toDateString());
        $this->assertSame('planned', $order->status);
        $this->assertSame('preventive', $order->work_type);
    }

    public function test_an_open_order_blocks_the_next_one(): void
    {
        $this->plan(['interval_days' => 7, 'fixed_date' => '2026-10-01']);

        $this->assertCount(1, $this->generate('2026-10-01'));
        // Échéance suivante (8 oct.) atteinte, mais l'ordre du 1er n'est pas clos.
        $this->assertCount(0, $this->generate('2026-10-08'));
        $this->assertSame(1, WorkOrder::count());
    }

    public function test_next_order_follows_the_calendar_once_the_previous_is_closed(): void
    {
        $this->plan(['interval_days' => 30, 'fixed_date' => '2026-10-01', 'lead_days' => 0]);

        $this->generate('2026-10-01');
        WorkOrder::query()->update(['status' => 'completed']);

        $this->assertCount(0, $this->generate('2026-10-30'));
        $next = $this->generate('2026-10-31')->first()['work_order'];

        $this->assertSame('2026-10-31', $next->scheduled_at->toDateString());
    }

    public function test_fixed_date_plan_generates_only_once(): void
    {
        $this->plan(['trigger_type' => 'fixed_date', 'fixed_date' => '2026-11-10']);

        $this->assertCount(1, $this->generate('2026-11-05'));
        WorkOrder::query()->update(['status' => 'closed']);

        $this->assertCount(0, $this->generate('2027-01-01'));
    }

    public function test_plans_without_auto_generation_or_with_counters_are_ignored(): void
    {
        $this->plan(['interval_days' => 7, 'fixed_date' => '2026-10-01', 'auto_generate' => false]);
        $this->plan(['trigger_type' => 'machine_hours', 'trigger_value' => '500']);
        $this->plan(['interval_days' => null]);

        $this->assertCount(0, $this->generate('2027-01-01'));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->plan(['interval_days' => 7, 'fixed_date' => '2026-10-01']);

        $result = app(PreventiveMaintenanceScheduler::class)->generateDue(Carbon::parse('2026-10-01'), dryRun: true);

        $this->assertCount(1, $result);
        $this->assertSame(0, WorkOrder::count());
    }

    public function test_labels_are_translated(): void
    {
        app()->setLocale('fr');

        $this->assertSame('Planifié', WorkOrder::label('status', 'planned'));
        $this->assertSame('Curatif', WorkOrder::label('work_type', 'corrective'));
        $this->assertSame('Haute', WorkOrder::label('priority', 'high'));
        $this->assertSame('Date fixe', MaintenancePlan::triggerTypeLabel('fixed_date'));
        // Valeur historique inconnue : affichée lisiblement, pas la clé de traduction.
        $this->assertSame('Requested', WorkOrder::label('status', 'requested'));
    }
}
