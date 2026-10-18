<?php

namespace Tests\Feature\Planning;

use App\Jobs\CalculateTaskDates;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Planning\TaskDependency;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Planning\BillOfMaterialsCopier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Liens entre opérations : recopies, duplication, suppression, saisie et
 * calcul des dates de bout en bout.
 */
class TaskDependenciesTest extends TestCase
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

        // ProductsFactory pioche une unité et une famille existantes.
        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
    }

    /**
     * Gamme 10 / 20 / 30 / 40 sur un porteur ; durée = réglage (quantité 1).
     *
     * @return array<int, Task> ordre => opération
     */
    private function routing(string $key, int $id, array $hours = [10 => 2, 20 => 7, 30 => 5, 40 => 3]): array
    {
        $blank = ['quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null];
        $tasks = [];

        foreach ($hours as $ordre => $h) {
            $tasks[$ordre] = Task::factory()->create(array_merge($blank, [
                $key              => $id,
                'ordre'           => $ordre,
                'type'            => 1,
                'seting_time'     => $h,
                'unit_time'       => 0,
                'qty'             => 1,
                'not_recalculate' => 0,
                'start_date'      => null,
                'end_date'        => null,
            ]));
        }

        return $tasks;
    }

    private function link(Task $to, Task $from, string $type = 'FS', float $lag = 0, string $unit = 'hour'): TaskDependency
    {
        return TaskDependency::create([
            'task_id'             => $to->id,
            'predecessor_task_id' => $from->id,
            'type'                => $type,
            'lag_value'           => $lag,
            'lag_unit'            => $unit,
        ]);
    }

    private function openOrderLine(string $internalDelay = '2026-11-27'): OrderLines
    {
        $order = Orders::factory()->create(['statu' => 1]);

        return OrderLines::factory()->create([
            'orders_id'      => $order->id,
            'qty'            => 1,
            'line_type'      => 'article',
            'tasks_status'   => 1,
            'internal_delay' => $internalDelay,
            'delivery_date'  => $internalDelay,
        ]);
    }

    // ------------------------------------------------------------------
    // Recopies, duplication, suppression
    // ------------------------------------------------------------------

    public function test_copying_a_routing_recreates_the_links_between_the_copies(): void
    {
        $source = Products::factory()->create();
        $target = Products::factory()->create();
        $tasks  = $this->routing('products_id', $source->id);
        $this->link($tasks[30], $tasks[20], 'SS', 10, 'piece');
        $this->link($tasks[40], $tasks[20]);
        $this->link($tasks[40], $tasks[30]);

        app(BillOfMaterialsCopier::class)->copy('products_id', $source->id, 'products_id', $target->id);

        $copies = Task::where('products_id', $target->id)->get()->keyBy('ordre');
        $copied = TaskDependency::whereIn('task_id', $copies->pluck('id'))->get();

        $this->assertCount(3, $copied);
        $ss = $copied->firstWhere('task_id', $copies[30]->id);
        $this->assertSame($copies[20]->id, (int) $ss->predecessor_task_id);
        $this->assertSame('SS', $ss->type->value);
        $this->assertSame(10.0, $ss->lag_value);
        $this->assertSame('piece', $ss->lag_unit->value);
        $this->assertSame(0, TaskDependency::whereIn('task_id', $copies->pluck('id'))->whereNotIn('predecessor_task_id', $copies->pluck('id'))->count(), 'aucun lien ne pointe vers la gamme d\'origine');
        $this->assertSame(3, TaskDependency::whereIn('task_id', collect($tasks)->pluck('id'))->count(), 'la gamme d\'origine garde ses liens');
    }

    public function test_a_duplicated_operation_starts_under_the_same_conditions(): void
    {
        $line  = $this->openOrderLine();
        $tasks = $this->routing('order_lines_id', $line->id);
        $this->link($tasks[30], $tasks[20], 'SS');
        $this->link($tasks[40], $tasks[30]);

        $response = $this->postJson(route('task.manage.json.task.duplicate', [
            'id_type' => 'order_lines_id', 'id_page' => $line->orders_id, 'id' => $tasks[30]->id,
        ]))->assertCreated();

        $copyId = $response->json('task.id');
        $this->assertSame(1, TaskDependency::where('task_id', $copyId)->where('predecessor_task_id', $tasks[20]->id)->where('type', 'SS')->count());
        $this->assertSame(0, TaskDependency::where('predecessor_task_id', $copyId)->count(), 'les liens sortants ne sont pas recopiés');
    }

    public function test_deleting_an_operation_removes_its_links(): void
    {
        $line  = $this->openOrderLine();
        $tasks = $this->routing('order_lines_id', $line->id);
        $this->link($tasks[30], $tasks[20], 'SS');
        $this->link($tasks[40], $tasks[30]);

        $this->deleteJson(route('task.manage.json.task.destroy', [
            'id_type' => 'order_lines_id', 'id_page' => $line->orders_id, 'id' => $tasks[30]->id,
        ]))->assertOk();

        $this->assertSame(0, TaskDependency::count());
    }

    // ------------------------------------------------------------------
    // Saisie
    // ------------------------------------------------------------------

    private function dependenciesUrl(OrderLines $line, Task $task): string
    {
        return route('task.manage.json.task.dependencies.update', [
            'id_type' => 'order_lines_id', 'id_page' => $line->orders_id, 'id' => $task->id,
        ]);
    }

    public function test_links_are_entered_in_short_notation_and_read_back(): void
    {
        $line  = $this->openOrderLine();
        $tasks = $this->routing('order_lines_id', $line->id);

        $this->putJson($this->dependenciesUrl($line, $tasks[40]), ['notation' => '20FD;30FD'])->assertOk();
        $response = $this->putJson($this->dependenciesUrl($line, $tasks[30]), ['notation' => '20DD+10pc;20FF+10pc'])
            ->assertOk()
            ->assertJsonPath('task.implicit', false)
            ->assertJsonPath('task.links.0.predecessor_ordre', 20)
            ->assertJsonPath('task.links.0.type', 'SS')
            ->assertJsonPath('task.links.1.type', 'FF');

        $routing = collect($response->json('routing'))->keyBy('ordre');
        $this->assertTrue($routing[20]['implicit'], 'la 20, sans lien saisi, suit la 10');
        $this->assertSame('after 10', $routing[20]['display']);
        $this->assertSame($tasks[20]->label, $routing[20]['label'], 'le libellé de l\'opération n\'est pas écrasé');
        $this->assertSame('20SS+10pc;20FF+10pc', $routing[30]['display']);
        $this->assertCount(2, $routing[40]['links']);
        $this->assertSame(4, TaskDependency::count());

        $this->getJson(route('task.manage.json.task.dependencies', [
            'id_type' => 'order_lines_id', 'id_page' => $line->orders_id, 'id' => $tasks[40]->id,
        ]))->assertOk()->assertJsonCount(2, 'task.links');

        // Vider la notation : retour à l'enchaînement sur la précédente.
        $this->putJson($this->dependenciesUrl($line, $tasks[40]), ['notation' => ''])
            ->assertOk()
            ->assertJsonPath('task.implicit', true);
        $this->assertSame(2, TaskDependency::count());
    }

    public function test_invalid_links_are_refused(): void
    {
        $line  = $this->openOrderLine();
        $tasks = $this->routing('order_lines_id', $line->id);
        $other = $this->routing('order_lines_id', $this->openOrderLine()->id);

        // « 10 après 20 » alors que 20 suit 10 : boucle par le lien implicite.
        $this->putJson($this->dependenciesUrl($line, $tasks[10]), ['notation' => '20FD'])
            ->assertStatus(422)->assertJsonPath('errors.links.0', fn ($m) => str_contains($m, '→'));

        $this->putJson($this->dependenciesUrl($line, $tasks[30]), ['notation' => '20FD+5pc'])->assertStatus(422);
        $this->putJson($this->dependenciesUrl($line, $tasks[30]), ['notation' => '25FD'])->assertStatus(422);
        $this->putJson($this->dependenciesUrl($line, $tasks[30]), ['links' => [
            ['predecessor_task_id' => $other[20]->id, 'type' => 'FS'],
        ]])->assertStatus(422);

        // Décalage hors de la colonne decimal(10,3) : refusé, pas une erreur SQL.
        $this->putJson($this->dependenciesUrl($line, $tasks[30]), ['notation' => '20DD+99999999h'])->assertStatus(422);
        $this->putJson($this->dependenciesUrl($line, $tasks[30]), ['links' => [
            ['predecessor_task_id' => $tasks[20]->id, 'type' => 'SS', 'lag_value' => 99999999],
        ]])->assertStatus(422);

        $this->assertSame(0, TaskDependency::count());
    }

    public function test_links_of_a_locked_order_cannot_be_changed(): void
    {
        $line  = $this->openOrderLine();
        $tasks = $this->routing('order_lines_id', $line->id);
        Orders::whereKey($line->orders_id)->update(['statu' => 2]);

        $this->putJson($this->dependenciesUrl($line, $tasks[30]), ['notation' => '20DD'])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Calcul des dates
    // ------------------------------------------------------------------

    public function test_the_date_job_follows_the_links_and_leaves_other_lines_unchanged(): void
    {
        $linked = $this->openOrderLine();
        $tasks  = $this->routing('order_lines_id', $linked->id);
        $this->link($tasks[30], $tasks[20], 'SS');
        $this->link($tasks[40], $tasks[20]);
        $this->link($tasks[40], $tasks[30]);

        $plain      = $this->openOrderLine();
        $plainTasks = $this->routing('order_lines_id', $plain->id);

        (new CalculateTaskDates(null, 'alap', 'infinite'))->handle();

        $t = fn (Task $task) => $task->fresh();
        $this->assertSame((string) $t($tasks[40])->start_date, (string) $t($tasks[30])->end_date);
        $this->assertSame((string) $t($tasks[40])->start_date, (string) $t($tasks[20])->end_date);
        $this->assertTrue(Carbon::parse($t($tasks[20])->start_date)->lte(Carbon::parse($t($tasks[30])->start_date)), 'DD tenu');

        // Ligne sans lien : chaîne stricte, comme avant.
        $this->assertSame((string) $t($plainTasks[20])->start_date, (string) $t($plainTasks[10])->end_date);
        $this->assertSame((string) $t($plainTasks[30])->start_date, (string) $t($plainTasks[20])->end_date);
        $this->assertSame((string) $t($plainTasks[40])->start_date, (string) $t($plainTasks[30])->end_date);
    }

    public function test_links_are_ignored_when_the_feature_is_switched_off(): void
    {
        config()->set('planning.dependencies_enabled', false);

        $line  = $this->openOrderLine();
        $tasks = $this->routing('order_lines_id', $line->id);
        $this->link($tasks[30], $tasks[20], 'SS');

        (new CalculateTaskDates(null, 'alap', 'infinite'))->handle();

        $this->assertSame((string) $tasks[30]->fresh()->start_date, (string) $tasks[20]->fresh()->end_date, 'chaîne historique');
    }

    public function test_a_started_operation_keeps_its_dates_on_a_line_with_links_only(): void
    {
        $start = function (Task $task): void {
            $task->update(['start_date' => '2026-11-02 08:00:00', 'end_date' => '2026-11-02 15:00:00']);
            TaskActivities::create([
                'task_id'   => $task->id,
                'user_id'   => auth()->id(),
                'type'      => TaskActivities::TYPE_START,
                'timestamp' => now(),
            ]);
        };

        $linked = $this->openOrderLine();
        $tasks  = $this->routing('order_lines_id', $linked->id);
        $this->link($tasks[30], $tasks[20], 'SS');
        $start($tasks[20]);

        $plain      = $this->openOrderLine();
        $plainTasks = $this->routing('order_lines_id', $plain->id);
        $start($plainTasks[20]);

        (new CalculateTaskDates(null, 'alap', 'infinite'))->handle();

        $this->assertSame('2026-11-02 15:00:00', (string) $tasks[20]->fresh()->end_date, 'ligne à liens : la tâche démarrée est un point fixe');
        $this->assertNotSame('2026-11-02 15:00:00', (string) $plainTasks[20]->fresh()->end_date, 'ligne sans lien : calcul historique, inchangé');
    }

    public function test_operation_quantity_is_saved_with_the_task(): void
    {
        $line  = $this->openOrderLine();
        $tasks = $this->routing('order_lines_id', $line->id);
        $task  = $tasks[20];

        $this->putJson(route('task.manage.json.task.update', [
            'id_type' => 'order_lines_id', 'id_page' => $line->orders_id, 'id' => $task->id,
        ]), [
            'label'               => $task->label,
            'ordre'               => $task->ordre,
            'methods_services_id' => $task->methods_services_id,
            'type'                => 1,
            'seting_time'         => 1,
            'unit_time'           => 0.1,
            'unit_cost'           => 1,
            'unit_price'          => 1,
            'planned_qty'         => 60,
        ])->assertOk();

        $this->assertEquals(60, $task->fresh()->planned_qty);
    }
}
