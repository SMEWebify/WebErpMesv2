<?php

namespace Tests\Feature\Planning;

use App\Models\Planning\Task;
use App\Models\Planning\TaskDependency;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le Gantt d'une commande trace les liens effectifs du calcul des dates :
 * liens saisis, sinon l'opération précédente de la gamme.
 */
class GanttDependenciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());
    }

    /**
     * @return array{0: Orders, 1: OrderLines, 2: array<int, Task>}
     */
    private function orderWithRouting(): array
    {
        $order = Orders::factory()->create(['statu' => 1]);
        $line  = OrderLines::factory()->create(['orders_id' => $order->id, 'qty' => 1, 'line_type' => 'article']);
        $tasks = [];

        foreach ([10, 20, 30] as $ordre) {
            $tasks[$ordre] = Task::factory()->create([
                'quote_lines_id'  => null,
                'products_id'     => null,
                'sub_assembly_id' => null,
                'order_lines_id'  => $line->id,
                'ordre'           => $ordre,
                'type'            => 1,
            ]);
        }

        return [$order, $line, $tasks];
    }

    /** @return array<string, string|null> id de barre => dépendances */
    private function arrows(Orders $order): array
    {
        $data = $this->getJson(route('production.gantt.order', ['order_id' => $order->id]))
            ->assertOk()
            ->json('data');

        return array_column($data, 'dependencies', 'id');
    }

    public function test_without_links_the_routing_is_a_chain(): void
    {
        [$order, $line, $t] = $this->orderWithRouting();

        $arrows = $this->arrows($order);

        $this->assertSame('order_' . $order->id, $arrows['ol_' . $line->id]);
        $this->assertSame('ol_' . $line->id, $arrows['task_' . $t[10]->id]);
        $this->assertSame('task_' . $t[10]->id, $arrows['task_' . $t[20]->id]);
        $this->assertSame('task_' . $t[20]->id, $arrows['task_' . $t[30]->id]);
    }

    public function test_entered_links_replace_the_chain(): void
    {
        [$order, $line, $t] = $this->orderWithRouting();

        // 30 démarre 10 pièces après le début de 10 et finit 10 pièces après lui.
        foreach (['SS', 'FF'] as $type) {
            TaskDependency::create([
                'task_id' => $t[30]->id, 'predecessor_task_id' => $t[10]->id,
                'type' => $type, 'lag_value' => 10, 'lag_unit' => 'piece',
            ]);
        }

        $arrows = $this->arrows($order);

        $this->assertSame('task_' . $t[10]->id, $arrows['task_' . $t[30]->id]);
        $this->assertSame('task_' . $t[10]->id, $arrows['task_' . $t[20]->id]);
    }

    public function test_links_are_ignored_when_the_feature_is_switched_off(): void
    {
        config(['planning.dependencies_enabled' => false]);
        [$order, $line, $t] = $this->orderWithRouting();

        TaskDependency::create([
            'task_id' => $t[30]->id, 'predecessor_task_id' => $t[10]->id,
            'type' => 'FS', 'lag_value' => 0, 'lag_unit' => 'hour',
        ]);

        $this->assertSame('task_' . $t[20]->id, $this->arrows($order)['task_' . $t[30]->id]);
    }
}
