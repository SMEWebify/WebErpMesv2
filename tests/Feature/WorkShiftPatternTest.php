<?php

namespace Tests\Feature;

use App\Jobs\CalculateTaskResources;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Times\WorkShiftPattern;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Support\WorkingTime;
use App\Services\ResourceCapacityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Régimes horaires : plages travaillées par jour de semaine, y compris le poste
 * de nuit qui franchit minuit (3×8).
 */
class WorkShiftPatternTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        Status::create(['title' => 'Open', 'order' => 1]);
    }

    /** 3×8 du lundi au vendredi, nuit incluse. */
    private function threeShiftPattern(bool $isDefault = false): WorkShiftPattern
    {
        $pattern = WorkShiftPattern::create([
            'code' => '3X8',
            'label' => '3x8',
            'is_default' => $isDefault,
        ]);

        foreach ([1, 2, 3, 4, 5] as $weekday) {
            foreach ([['06:00:00', '14:00:00'], ['14:00:00', '22:00:00'], ['22:00:00', '06:00:00']] as [$start, $end]) {
                $pattern->slots()->create([
                    'weekday' => $weekday,
                    'start_time' => $start,
                    'end_time' => $end,
                ]);
            }
        }

        return $pattern->load('slots');
    }

    public function test_a_three_shift_pattern_opens_twenty_four_hours_on_working_days()
    {
        $pattern = $this->threeShiftPattern();

        // Lundi : 3 plages de 8 h, la dernière franchissant minuit.
        $this->assertSame(24.0, $pattern->hoursForDate(Carbon::parse('2026-08-10')));
        // Dimanche : aucune plage déclarée.
        $this->assertSame(0.0, $pattern->hoursForDate(Carbon::parse('2026-08-16')));
        $this->assertSame(120.0, $pattern->weeklyHours());
    }

    public function test_a_night_slot_crosses_midnight_and_covers_the_next_morning()
    {
        $pattern = $this->threeShiftPattern();

        $night = $pattern->slots->firstWhere('start_time', '22:00:00');

        $this->assertTrue($night->crossesMidnight());
        $this->assertSame(8.0, $night->durationHours());

        // Mardi 02h00 est couvert par le poste de nuit rattaché au lundi.
        $this->assertTrue($pattern->coversInstant(Carbon::parse('2026-08-11 02:00:00')));
        // Samedi 02h00 ne l'est pas : le vendredi soir est ouvert, mais pas le samedi.
        $this->assertTrue($pattern->coversInstant(Carbon::parse('2026-08-15 02:00:00')));
        $this->assertFalse($pattern->coversInstant(Carbon::parse('2026-08-16 02:00:00')));
    }

    public function test_resource_capacity_follows_its_pattern_day_by_day()
    {
        $pattern = $this->threeShiftPattern();

        $machine = MethodsRessources::factory()->create([
            'capacity' => 35,
            'work_shift_pattern_id' => $pattern->id,
        ]);

        $this->assertSame(24.0, $machine->dailyCapacity(Carbon::parse('2026-08-10')));
        $this->assertSame(0.0, $machine->dailyCapacity(Carbon::parse('2026-08-16')));
    }

    /** Sans régime horaire, on garde le comportement historique. */
    public function test_resource_without_pattern_keeps_the_weekly_capacity_spread_over_five_days()
    {
        $machine = MethodsRessources::factory()->create(['capacity' => 35]);

        $this->assertSame(7.0, $machine->dailyCapacity(Carbon::parse('2026-08-16')));
        $this->assertSame(7.0, $machine->dailyCapacity());
    }

    /**
     * Une tâche qui démarre un jour fermé n'est pas refusée : elle est reportée
     * sur les jours ouverts suivants — c'est la répartition multi-jours.
     */
    public function test_a_task_starting_on_a_closed_day_is_scheduled_on_the_next_open_days()
    {
        $pattern = $this->threeShiftPattern();
        $service = MethodsServices::factory()->create(['type' => MethodsServices::TYPE_PRODUCTIVE]);

        MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 100,
            'work_shift_pattern_id' => $pattern->id,
        ]);

        // 2026-08-16 est un dimanche : le régime 3×8 n'ouvre pas ce jour-là.
        $sundayTask = Task::factory()->create([
            'methods_services_id' => $service->id,
            'order_lines_id' => OrderLines::factory()->create()->id,
            'start_date' => '2026-08-16',
            'seting_time' => 2,
            'unit_time' => 0,
            'qty' => 1,
        ]);

        (new CalculateTaskResources())->handle();

        $this->assertSame(1, $sundayTask->resources()->count());

        // La charge tombe le lundi, pas le dimanche.
        $plan = app(ResourceCapacityService::class)->spreadHours(
            2,
            Carbon::parse('2026-08-16'),
            fn (string $day) => $day === '2026-08-16' ? 0.0 : 24.0
        );

        $this->assertSame(['2026-08-17' => 2.0], $plan);
    }

    /** Une ressource sans capacité déclarée ne reçoit rien. */
    public function test_a_resource_without_capacity_receives_no_assignment()
    {
        $service = MethodsServices::factory()->create(['type' => MethodsServices::TYPE_PRODUCTIVE]);

        MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 0,
        ]);

        $task = Task::factory()->create([
            'methods_services_id' => $service->id,
            'order_lines_id' => OrderLines::factory()->create()->id,
            'start_date' => '2026-08-17',
            'seting_time' => 2,
            'unit_time' => 0,
            'qty' => 1,
        ]);

        (new CalculateTaskResources())->handle();

        $this->assertSame(0, $task->resources()->count());
    }

    /** Le régime par défaut pilote aussi le calcul des dates de tâches. */
    public function test_default_pattern_drives_working_hours()
    {
        $this->assertFalse(WorkingTime::isWorkingInstant(Carbon::parse('2026-08-11 02:00:00')));

        $this->threeShiftPattern(true);

        // La nuit devient travaillée, le dimanche ne l'est toujours pas.
        $this->assertTrue(WorkingTime::isWorkingInstant(Carbon::parse('2026-08-11 02:00:00')));
        $this->assertFalse(WorkingTime::isWorkingInstant(Carbon::parse('2026-08-16 10:00:00')));
    }

    public function test_shift_pattern_page_lists_patterns_and_accepts_a_new_slot()
    {
        $pattern = $this->threeShiftPattern();

        $this->get(route('times.shift'))
             ->assertStatus(200)
             ->assertSee('3x8');

        $this->post(route('times.shift.slot.create', ['id' => $pattern->id]), [
            'weekday' => 6,
            'start_time' => '06:00',
            'end_time' => '12:00',
            'label' => 'Samedi matin',
        ])->assertRedirect(route('times.shift'));

        $this->assertSame(6.0, $pattern->fresh()->load('slots')->hoursForDate(Carbon::parse('2026-08-15')));
    }

    /**
     * Un régime créé mais dont les plages ne sont pas encore saisies ne doit pas
     * faire disparaître la ressource du planning : c'est une configuration
     * inachevée, pas un atelier fermé.
     */
    public function test_a_pattern_without_any_slot_falls_back_on_the_weekly_capacity()
    {
        $empty = WorkShiftPattern::create(['code' => '3X', 'label' => '3 8']);

        $machine = MethodsRessources::factory()->create([
            'capacity' => 35,
            'work_shift_pattern_id' => $empty->id,
        ]);

        $this->assertSame(7.0, $machine->dailyCapacity(Carbon::parse('2026-08-17')));
        $this->assertSame(7.0, app(ResourceCapacityService::class)->availableHours($machine, Carbon::parse('2026-08-17')));
    }

    /** Et la tâche est bien affectée, au lieu de tomber en « aucune ressource ». */
    public function test_a_resource_on_an_empty_pattern_still_receives_tasks()
    {
        $empty = WorkShiftPattern::create(['code' => 'VIDE', 'label' => 'Vide']);
        $service = MethodsServices::factory()->create(['type' => MethodsServices::TYPE_PRODUCTIVE]);

        $team = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 11,
            'is_labor' => true,
            'labor_ratio' => 1,
            'work_shift_pattern_id' => $empty->id,
        ]);

        $task = Task::factory()->create([
            'methods_services_id' => $service->id,
            'order_lines_id' => OrderLines::factory()->create()->id,
            'start_date' => '2026-08-17',
            'seting_time' => 0.033,
            'unit_time' => 0.001,
            'qty' => 1,
        ]);

        (new CalculateTaskResources())->handle();

        $this->assertSame($team->id, $task->fresh()->laborResource()->first()?->id);
    }
}
