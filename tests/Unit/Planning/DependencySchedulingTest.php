<?php

namespace Tests\Unit\Planning;

use App\Models\Planning\Task;
use App\Models\Planning\TaskDependency;
use App\Models\Workflow\OrderLines;
use App\Services\Planning\FiniteCapacityScheduler;
use App\Support\WorkingTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Ordonnancement le long des liens entre opérations.
 *
 * Le cas de référence est le tableau de l'atelier : 10 Débit → 20 Tournage
 * côté 1 (Tour 1) et 30 Tournage côté 2 (Tour 2) en même temps → 40 Fraisage,
 * qui attend les deux → 50 Ajustage → 60 Traitement (sous-traitance).
 * Calendrier : lundi–vendredi 8 h–18 h, sans férié.
 */
class DependencySchedulingTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/../../Fixtures/planning/legacy-dates.json';

    private const FORMAT = 'Y-m-d H:i:s';

    // ------------------------------------------------------------------
    // Rétrocompatibilité
    // ------------------------------------------------------------------

    public function test_without_links_the_new_path_gives_the_reference_dates(): void
    {
        $expected = json_decode(file_get_contents(self::FIXTURE), true);

        foreach (PlanningScenarios::all() as $name => $scenario) {
            $actual = PlanningScenarios::run(
                $scenario,
                fn (FiniteCapacityScheduler $scheduler, OrderLines $line, string $direction, string $mode, ?callable $delayFn, ?Carbon $asapFrom) =>
                    $scheduler->chainOrderLineWithDependencies($line, $line->Task, $direction, $mode, $delayFn, $asapFrom),
            );

            $this->assertSame($expected[$name], $actual, "Le chemin à dépendances diverge de la référence : {$name}");
        }
    }

    public function test_planned_hours_equal_total_time_without_operation_quantity(): void
    {
        foreach ([[0, 0, 1], [1.5, 0.25, 4], [0.333, 0.017, 37], [2, 0.75, 1], [0, 1.2, 3]] as [$setup, $unit, $qty]) {
            $task = PlanningScenarios::task(1, 10, $setup, $unit, $qty, null, []);

            $this->assertSame((float) $task->TotalTime(), $task->plannedHours());
        }
    }

    public function test_operation_quantity_changes_planned_hours_only(): void
    {
        $task = PlanningScenarios::task(1, 10, 1, 0.1, 100, null, []);
        $task->planned_qty = 60;

        $this->assertSame(7.0, $task->plannedHours());
        $this->assertSame(11.0, (float) $task->TotalTime(), 'les coûts et l\'avancement restent sur la quantité de la ligne');
    }

    // ------------------------------------------------------------------
    // Le tableau de l'atelier
    // ------------------------------------------------------------------

    /**
     * Durées d'essai (le tableau les dit « calculées ») : 2,5 / 7 / 5 / 6,5 / 3 / 20 h.
     *
     * @param  array<int, array{0: int, 1: string, 2?: float, 3?: string}>  $links  successeur => [[prédécesseur, type, décalage, unité]]
     * @return array<int, Task>
     */
    private function workshopTasks(array $links = []): array
    {
        $tasks = [
            1 => PlanningScenarios::task(1, 10, 2.5, 0, 1, null, []),
            2 => PlanningScenarios::task(2, 20, 7, 0, 1, null, []),
            3 => PlanningScenarios::task(3, 30, 5, 0, 1, null, []),
            4 => PlanningScenarios::task(4, 40, 6.5, 0, 1, null, []),
            5 => PlanningScenarios::task(5, 50, 3, 0, 1, null, []),
            6 => PlanningScenarios::task(6, 60, 20, 0, 1, null, []),
        ];

        $this->addLinks($tasks, $links);

        return $tasks;
    }

    private function addLinks(array $tasks, array $links): void
    {
        foreach ($links as $to => $list) {
            foreach ($list as $link) {
                $tasks[$to]->getRelation('predecessorLinks')->push(new TaskDependency([
                    'task_id'             => $to,
                    'predecessor_task_id' => $link[0],
                    'type'                => $link[1],
                    'lag_value'           => $link[2] ?? 0,
                    'lag_unit'            => $link[3] ?? 'hour',
                ]));
            }
        }
    }

    private function line(int $id, array $tasks, string $anchor): OrderLines
    {
        $line = new OrderLines();
        $line->id = $id;
        $line->orders_id = 100 + $id;
        $line->internal_delay = $anchor;
        $line->exists = true;
        $line->setRelation('Task', Collection::make(array_values($tasks)));

        return $line;
    }

    /** @return array<int, array{0: string, 1: string}> id => [début, fin] */
    private function schedule(array $tasks, string $direction, string $mode = 'infinite', string $anchor = '2026-11-16 08:00:00', ?FiniteCapacityScheduler $scheduler = null): array
    {
        $scheduler ??= PlanningScenarios::scheduler([]);
        $line = $this->line(1, $tasks, $anchor);
        $dates = [];

        // ASAP : départ du calcul = l'ancre (le job fige « maintenant » pour tout le run).
        foreach ($scheduler->chainOrderLineWithDependencies($line, $line->Task, $direction, $mode, null, Carbon::parse($anchor)) as $entry) {
            $dates[$entry['task']->id] = [$entry['start']->format(self::FORMAT), $entry['end']->format(self::FORMAT)];
        }
        ksort($dates);

        return $dates;
    }

    public function test_the_two_lathes_start_together_and_milling_waits_for_both(): void
    {
        $dates = $this->schedule($this->workshopTasks([
            3 => [[2, 'SS']],
            4 => [[2, 'FS'], [3, 'FS']],
        ]), 'asap');

        $this->assertSame([
            1 => ['2026-11-16 08:00:00', '2026-11-16 10:30:00'],
            2 => ['2026-11-16 10:30:00', '2026-11-16 17:30:00'],
            3 => ['2026-11-16 10:30:00', '2026-11-16 15:30:00'],
            4 => ['2026-11-16 17:30:00', '2026-11-17 14:00:00'],
            5 => ['2026-11-17 14:00:00', '2026-11-17 17:00:00'],
            6 => ['2026-11-17 17:00:00', '2026-11-19 17:00:00'],
        ], $dates);
    }

    public function test_without_links_the_same_routing_ends_five_hours_later(): void
    {
        $tasks = $this->workshopTasks();
        $line = $this->line(1, $tasks, '2026-11-16 08:00:00');
        $legacy = PlanningScenarios::scheduler([])->chainOrderLine($line, 'asap', 'infinite', null, Carbon::parse('2026-11-16 08:00:00'));

        $this->assertSame('2026-11-16 17:30:00', $legacy[2]['start']->format(self::FORMAT), 'sans lien, le Tour 2 attend la fin du Tour 1');
        $this->assertSame('2026-11-20 12:00:00', $legacy[5]['end']->format(self::FORMAT));
    }

    public function test_a_single_predecessor_column_would_start_milling_too_early(): void
    {
        // Le tableau tel qu'écrit : 40 après 30 seulement.
        $dates = $this->schedule($this->workshopTasks([
            3 => [[2, 'SS']],
            4 => [[3, 'FS']],
        ]), 'asap');

        $this->assertSame('2026-11-16 15:30:00', $dates[4][0]);
        $this->assertLessThan($dates[2][1], $dates[4][0], 'le fraisage démarrerait pendant que le Tour 1 usine encore');
    }

    public function test_backward_scheduling_keeps_the_links(): void
    {
        $dates = $this->schedule($this->workshopTasks([
            3 => [[2, 'SS']],
            4 => [[2, 'FS'], [3, 'FS']],
        ]), 'alap', 'infinite', '2026-11-27 00:00:00');

        $this->assertSame('2026-11-27 00:00:00', $dates[6][1]);
        $this->assertSame($dates[6][0], $dates[5][1]);
        $this->assertSame($dates[5][0], $dates[4][1]);
        $this->assertSame($dates[4][0], $dates[3][1]);
        $this->assertSame($dates[4][0], $dates[2][1]);
        $this->assertLessThanOrEqual($dates[3][0], $dates[2][0], 'le Tour 1 ne démarre pas après le Tour 2');
        $this->assertSame($dates[2][0], $dates[1][1]);
    }

    public function test_second_side_overlaps_by_transfer_batch(): void
    {
        // Le vrai fonctionnement : le Tour 2 usine le côté 2 dès qu'une pièce sort du
        // Tour 1, et ne peut pas finir avant d'avoir reçu la dernière.
        // Tour 1 : 1 h + 100 × 0,06 h = 7 h ; Tour 2 : 1 h + 100 × 0,04 h = 5 h.
        $tasks = $this->workshopTasks();
        $tasks[2] = PlanningScenarios::task(2, 20, 1, 0.06, 100, null, []);
        $tasks[3] = PlanningScenarios::task(3, 30, 1, 0.04, 100, null, []);
        $this->addLinks($tasks, [
            3 => [[2, 'SS', 1, 'piece'], [2, 'FF', 1, 'piece']],
            4 => [[2, 'FS'], [3, 'FS']],
        ]);

        $dates = $this->schedule($tasks, 'asap');
        $start20 = Carbon::parse($dates[2][0]);
        $end20 = Carbon::parse($dates[2][1]);

        $this->assertGreaterThanOrEqual(WorkingTime::addWorkingHours($start20, 1 + 0.06)->format(self::FORMAT), $dates[3][0], 'DD : une pièce sortie du Tour 1');
        $this->assertGreaterThanOrEqual(WorkingTime::addWorkingHours($end20, 0.04)->format(self::FORMAT), $dates[3][1], 'FF : la dernière pièce usinée sur le Tour 2');
        $this->assertSame($dates[3][1], $dates[4][0], 'le fraisage attend la fin du côté 2');
    }

    public function test_finite_capacity_shifts_the_second_order_without_breaking_links(): void
    {
        $scheduler = PlanningScenarios::scheduler(['default' => 8.0]);
        $links = [3 => [[2, 'SS']], 4 => [[2, 'FS'], [3, 'FS']]];

        $routing = function (int $offset) use ($links): array {
            $tasks = [];
            foreach ([[10, 2.5, 7], [20, 7, 1], [30, 5, 2], [40, 6.5, 3], [50, 3, 4], [60, 20, null]] as $i => [$ordre, $hours, $resource]) {
                $id = $offset + $i + 1;
                $tasks[$i + 1] = PlanningScenarios::task($id, $ordre, $hours, 0, 1, null, $resource ? [$resource => 1.0] : []);
            }
            foreach ($links as $to => $list) {
                foreach ($list as $link) {
                    $tasks[$to]->getRelation('predecessorLinks')->push(new TaskDependency([
                        'task_id' => $offset + $to, 'predecessor_task_id' => $offset + $link[0], 'type' => $link[1],
                    ]));
                }
            }

            return $tasks;
        };

        $line1 = $this->line(1, $routing(0), '2026-11-16 08:00:00');
        $line2 = $this->line(2, $routing(100), '2026-11-16 08:00:00');
        $monday = Carbon::parse('2026-11-16 08:00:00');
        $first = $scheduler->chainOrderLineWithDependencies($line1, $line1->Task, 'asap', 'finite', null, $monday);
        $second = $scheduler->chainOrderLineWithDependencies($line2, $line2->Task, 'asap', 'finite', null, $monday);

        foreach ([$first, $second] as $result) {
            $at = [];
            foreach ($result as $entry) {
                $at[$entry['task']->ordre] = $entry;
            }
            $this->assertTrue($at[30]['start']->gte($at[20]['start']), 'DD tenu');
            $this->assertTrue($at[40]['start']->gte($at[20]['end']) && $at[40]['start']->gte($at[30]['end']), 'FD tenus');
            $this->assertTrue($at[50]['start']->gte($at[40]['end']));
        }

        $this->assertTrue($second[1]['start']->gt($first[1]['start']), 'le Tour 1 du second OF attend que le premier libère la machine');
    }

    public function test_a_shared_batch_is_planned_with_each_machine_quantity(): void
    {
        $tasks = $this->workshopTasks();
        $tasks[2] = PlanningScenarios::task(2, 20, 1, 0.1, 100, null, []);
        $tasks[3] = PlanningScenarios::task(3, 30, 1, 0.1, 100, null, []);
        $tasks[2]->planned_qty = 60;
        $tasks[3]->planned_qty = 40;
        $this->addLinks($tasks, [3 => [[2, 'SS']], 4 => [[2, 'FS'], [3, 'FS']]]);

        $dates = $this->schedule($tasks, 'asap');

        $this->assertSame(WorkingTime::addWorkingHours(Carbon::parse($dates[2][0]), 7)->format(self::FORMAT), $dates[2][1], '1 h + 60 × 0,1 h');
        $this->assertSame(WorkingTime::addWorkingHours(Carbon::parse($dates[3][0]), 5)->format(self::FORMAT), $dates[3][1], '1 h + 40 × 0,1 h');
    }

    // ------------------------------------------------------------------
    // Tâches figées, boucles
    // ------------------------------------------------------------------

    public function test_a_frozen_task_is_a_fixed_point_for_its_successors(): void
    {
        $tasks = [
            1 => PlanningScenarios::task(1, 10, 2, 0, 1, null, []),
            2 => PlanningScenarios::task(2, 20, 3, 0, 1, null, []),
            3 => PlanningScenarios::task(3, 30, 1, 0, 1, null, []),
        ];
        $tasks[2]->not_recalculate = 1;
        $tasks[2]->start_date = '2026-11-18 09:00:00';
        $tasks[2]->end_date = '2026-11-18 12:00:00';
        $this->addLinks($tasks, [3 => [[2, 'FS']]]);

        $scheduler = PlanningScenarios::scheduler([]);
        $line = $this->line(1, $tasks, '2026-11-16 08:00:00');
        $result = $scheduler->chainOrderLineWithDependencies($line, $line->Task, 'asap', 'infinite', null, Carbon::parse('2026-11-16 08:00:00'));

        $ids = array_map(fn ($entry) => $entry['task']->id, $result);
        $this->assertNotContains(2, $ids, 'une tâche figée n\'est ni déplacée ni réécrite');
        $byId = array_column(array_map(fn ($e) => ['id' => $e['task']->id, 'start' => $e['start']->format(self::FORMAT)], $result), 'start', 'id');
        $this->assertSame('2026-11-18 12:00:00', $byId[3]);
        $this->assertSame([], $scheduler->conflictMessages());
    }

    public function test_a_frozen_task_placed_too_early_is_reported(): void
    {
        $tasks = [
            1 => PlanningScenarios::task(1, 10, 4, 0, 1, null, []),
            2 => PlanningScenarios::task(2, 20, 3, 0, 1, null, []),
        ];
        $tasks[2]->not_recalculate = 1;
        $tasks[2]->start_date = '2026-11-16 09:00:00';
        $tasks[2]->end_date = '2026-11-16 12:00:00';
        $this->addLinks($tasks, [2 => [[1, 'FS']]]);

        $scheduler = PlanningScenarios::scheduler([]);
        $line = $this->line(1, $tasks, '2026-11-16 08:00:00');
        $scheduler->chainOrderLineWithDependencies($line, $line->Task, 'asap', 'infinite', null, Carbon::parse('2026-11-16 08:00:00'));

        $this->assertCount(1, $scheduler->conflictMessages());
        $this->assertStringContainsString('10 FS 20 non tenu', $scheduler->conflictMessages()[0]);
    }

    public function test_circular_links_fall_back_to_the_routing_order(): void
    {
        $tasks = [
            1 => PlanningScenarios::task(1, 10, 2, 0, 1, null, []),
            2 => PlanningScenarios::task(2, 20, 3, 0, 1, null, []),
        ];
        $this->addLinks($tasks, [1 => [[2, 'FS']]]);

        $scheduler = PlanningScenarios::scheduler([]);
        $line = $this->line(1, $tasks, '2026-11-16 08:00:00');
        $result = $scheduler->chainOrderLineWithDependencies($line, $line->Task, 'asap', 'infinite', null, Carbon::parse('2026-11-16 08:00:00'));
        $legacy = PlanningScenarios::scheduler([])->chainOrderLine($line, 'asap', 'infinite', null, Carbon::parse('2026-11-16 08:00:00'));

        $this->assertSame(
            array_map(fn ($e) => $e['end']->format(self::FORMAT), $legacy),
            array_map(fn ($e) => $e['end']->format(self::FORMAT), $result),
        );
        $this->assertStringContainsString('liens circulaires (20 → 10 → 20)', $scheduler->conflictMessages()[0]);
    }
}
