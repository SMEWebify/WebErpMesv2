<?php

namespace Tests\Feature;

use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskResources;
use App\Models\Times\WorkShiftPattern;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Planning de charge : la charge est consultable par service (maille historique)
 * ou par ressource, avec la capacité réelle jour par jour.
 */
class LoadPlanningGranularityTest extends TestCase
{
    use RefreshDatabase;

    private const MONDAY = '2026-08-17';
    private const FRIDAY = '2026-08-21';

    private MethodsServices $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        Status::create(['title' => 'Open', 'order' => 1]);

        $this->service = MethodsServices::factory()->create(['type' => MethodsServices::TYPE_PRODUCTIVE]);
    }

    /** Tâche de 4 h due le lundi de la fenêtre. */
    private function task(): Task
    {
        return Task::factory()->create([
            'methods_services_id' => $this->service->id,
            'order_lines_id' => OrderLines::factory()->create()->id,
            'type' => 1,
            'start_date' => self::MONDAY,
            'end_date' => self::MONDAY,
            'seting_time' => 4,
            'unit_time' => 0,
            'qty' => 1,
        ]);
    }

    private function fetch(string $granularity): array
    {
        return $this->getJson(route('production.load.planning.data', [
            'start_date' => self::MONDAY,
            'end_date' => self::FRIDAY,
            'granularity' => $granularity,
        ]))->assertOk()->json();
    }

    public function test_service_granularity_stays_the_default()
    {
        $this->task();

        $data = $this->fetch('service');

        $this->assertSame('service', $data['granularity']);
        $this->assertSame([(string) $this->service->id], collect($data['rows'])->pluck('id')->all());
        $this->assertEqualsWithDelta(4.0, $data['hoursPerRowDay'][(string) $this->service->id][self::MONDAY], 0.001);
    }

    /** Une machine et un opérateur portent chacun leur quotité de la même tâche. */
    public function test_resource_granularity_splits_the_load_between_machine_and_labor()
    {
        $machine = MethodsRessources::factory()->create([
            'methods_services_id' => $this->service->id,
            'capacity' => 40,
            'labor_ratio' => 0.5,
        ]);
        $team = MethodsRessources::factory()->create([
            'methods_services_id' => $this->service->id,
            'capacity' => 40,
            'is_labor' => true,
        ]);

        $task = $this->task();
        $task->resources()->attach($machine->id, [
            'role' => TaskResources::ROLE_MACHINE, 'source' => TaskResources::SOURCE_AUTO, 'load_factor' => 1,
        ]);
        $task->resources()->attach($team->id, [
            'role' => TaskResources::ROLE_LABOR, 'source' => TaskResources::SOURCE_AUTO, 'load_factor' => 0.5,
        ]);

        $data = $this->fetch('resource');

        $this->assertSame('resource', $data['granularity']);
        // 4 h sur la machine, 2 h sur la main-d'œuvre (quotité 0,5).
        $this->assertEqualsWithDelta(4.0, $data['hoursPerRowDay'][(string) $machine->id][self::MONDAY], 0.001);
        $this->assertEqualsWithDelta(2.0, $data['hoursPerRowDay'][(string) $team->id][self::MONDAY], 0.001);

        $laborRow = collect($data['rows'])->firstWhere('id', (string) $team->id);
        $this->assertTrue($laborRow['isLabor']);
    }

    /** La capacité vient du régime horaire, jour par jour, pas d'une moyenne. */
    public function test_resource_rows_carry_the_real_capacity_of_each_day()
    {
        $pattern = WorkShiftPattern::create(['code' => '1X8', 'label' => '1x8']);
        foreach ([1, 2, 3, 4, 5] as $weekday) {
            $pattern->slots()->create(['weekday' => $weekday, 'start_time' => '06:00:00', 'end_time' => '14:00:00']);
        }

        $machine = MethodsRessources::factory()->create([
            'methods_services_id' => $this->service->id,
            'capacity' => 40,
            'work_shift_pattern_id' => $pattern->id,
        ]);

        $this->task();

        // Fenêtre élargie au week-end pour vérifier un jour non ouvert.
        $data = $this->getJson(route('production.load.planning.data', [
            'start_date' => self::MONDAY,
            'end_date' => '2026-08-23',
            'granularity' => 'resource',
        ]))->assertOk()->json();

        $row = collect($data['rows'])->firstWhere('id', (string) $machine->id);

        $this->assertEqualsWithDelta(8.0, $row['capacityPerDay'][self::MONDAY], 0.001);
        $this->assertEqualsWithDelta(8.0, $row['capacityPerDay'][self::FRIDAY], 0.001);
        // 2026-08-22 est un samedi : le régime 1×8 ne l'ouvre pas.
        $this->assertEqualsWithDelta(0.0, $row['capacityPerDay']['2026-08-22'], 0.001);
    }

    /** Les tâches sans ressource restent visibles sur une ligne dédiée. */
    public function test_unassigned_tasks_get_their_own_row()
    {
        MethodsRessources::factory()->create([
            'methods_services_id' => $this->service->id,
            'capacity' => 40,
        ]);

        $this->task();

        $data = $this->fetch('resource');

        $this->assertArrayHasKey('unassigned', $data['hoursPerRowDay']);
        $this->assertEqualsWithDelta(4.0, $data['hoursPerRowDay']['unassigned'][self::MONDAY], 0.001);

        $row = collect($data['rows'])->firstWhere('id', 'unassigned');
        $this->assertNotNull($row);
        $this->assertSame(0, (int) $row['capacity']);
    }

    public function test_the_page_loads_in_both_granularities()
    {
        $this->task();

        foreach (['service', 'resource'] as $granularity) {
            $this->get(route('production.load.planning', ['granularity' => $granularity]))
                 ->assertStatus(200)
                 ->assertViewHas('granularity', $granularity);
        }
    }
}
