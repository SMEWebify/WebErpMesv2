<?php

namespace Tests\Feature;

use App\Jobs\CalculateTaskDates;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\TaskDateCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Calcul des dates de tâches sur une base migrée.
 *
 * La ligne de commande ne porte pas de date de début : c'est la première
 * tâche qui la reçoit. L'ASAP triait et s'ancrait sur une colonne
 * `order_lines.start_date` qu'aucune migration ne crée, et le job échouait
 * dès qu'une ligne était à planifier (issue #1200).
 */
class CalculateTaskDatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        Status::create(['title' => 'Open', 'order' => 1]);
    }

    private function makeLineWithTask(): Task
    {
        $order = Orders::factory()->create();
        $line  = OrderLines::factory()->create(['orders_id' => $order->id, 'tasks_status' => 1]);

        return Task::factory()->create([
            'order_lines_id'  => $line->id,
            'quote_lines_id'  => null,
            'products_id'     => null,
            'type'            => 1,
            'not_recalculate' => 0,
            'ordre'           => 10,
        ]);
    }

    public function test_asap_plans_from_now_without_an_order_line_start_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 19, 9, 0, 0)); // lundi 9h

        try {
            $task = $this->makeLineWithTask();

            (new CalculateTaskDates(null, TaskDateCalculator::DIRECTION_ASAP))->handle();

            $task->refresh();
            $this->assertNotNull($task->start_date);
            $this->assertNotNull($task->end_date);
            $this->assertTrue(Carbon::parse($task->start_date)->gte(Carbon::now()));
            $this->assertTrue(Carbon::parse($task->end_date)->gt(Carbon::parse($task->start_date)));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_alap_still_plans_from_the_internal_delay(): void
    {
        $task = $this->makeLineWithTask();

        (new CalculateTaskDates(null, TaskDateCalculator::DIRECTION_ALAP))->handle();

        $this->assertNotNull($task->refresh()->end_date);
    }
}
