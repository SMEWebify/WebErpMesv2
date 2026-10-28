<?php

namespace Tests\Feature\Production;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Integrations\Handlers\HandleTaskEvent;
use App\Services\Production\ProductionRelease;
use App\Services\Production\ProductionReleaseException;
use Tests\TestCase;

/**
 * Bon pour fabrication : une tâche de production ne démarre qu'après le BPF de
 * sa ligne quand `production.require_release` est vrai ; achats, matière et
 * sous-traitance ne sont jamais bloqués ; option inactive = comportement historique.
 */
class ProductionReleaseTest extends TestCase
{
    private User $user;
    private Companies $customer;
    private MethodsServices $laser;
    private MethodsServices $sheet;
    private MethodsServices $subcontracting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        foreach (['Open', 'Started', 'In progress', 'Finished'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }

        $this->customer = Companies::factory()->create(['statu_customer' => 2]);
        $this->laser = MethodsServices::factory()->create(['code' => 'LASER', 'label' => 'Laser', 'type' => MethodsServices::TYPE_PRODUCTIVE]);
        $this->sheet = MethodsServices::factory()->create(['code' => 'TOLE', 'label' => 'Tôle', 'type' => MethodsServices::TYPE_RAW_SHEET]);
        $this->subcontracting = MethodsServices::factory()->create(['code' => 'ST', 'label' => 'Traitement', 'type' => MethodsServices::TYPE_SUB_CONTRACTING]);

        config(['production.require_release' => true]);
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function line(array $order = [], array $line = []): OrderLines
    {
        $order = Orders::factory()->create($order + ['companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1]);

        return OrderLines::factory()->create($line + [
            'orders_id' => $order->id, 'product_id' => Products::factory()->create()->id, 'qty' => 4,
            'delivered_qty' => 0, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ind' => 'A',
        ]);
    }

    private function task(OrderLines $line, MethodsServices $service, array $attributes = []): Task
    {
        return Task::factory()->create($attributes + [
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'methods_services_id' => $service->id, 'type' => $service->type, 'ordre' => 1,
        ]);
    }

    private function released(OrderLines $line): OrderLines
    {
        $line->forceFill(['released_for_production_at' => now(), 'released_for_production_by' => $this->user->id])->save();

        return $line;
    }

    private function startCount(Task $task): int
    {
        return TaskActivities::where('task_id', $task->id)->where('type', TaskActivities::TYPE_START)->count();
    }

    // ------------------------------------------------------------------
    // Démarrage
    // ------------------------------------------------------------------

    public function test_a_production_task_cannot_start_without_release_when_the_option_is_on(): void
    {
        $line = $this->line();
        $task = $this->task($line, $this->laser);

        $this->postJson(route('production.task.statu.api.start', $task->id))
            ->assertStatus(422)
            ->assertJsonPath('production_release', false)
            ->assertJsonFragment(['message' => __('production_release.blocked_start', ['line' => $line->code])]);

        $this->assertSame(0, $this->startCount($task));
        $this->assertNotSame(Status::where('title', 'In progress')->value('id'), $task->fresh()->status_id);
    }

    public function test_a_released_production_task_starts(): void
    {
        $task = $this->task($this->released($this->line()), $this->laser);

        $this->postJson(route('production.task.statu.api.start', $task->id))->assertOk();

        $this->assertSame(1, $this->startCount($task));
    }

    public function test_without_the_option_a_task_starts_without_release(): void
    {
        config(['production.require_release' => false]);
        $task = $this->task($this->line(), $this->laser);

        $this->postJson(route('production.task.statu.api.start', $task->id))->assertOk();

        $this->assertSame(1, $this->startCount($task));
        $this->getJson(route('production.task.statu.api.show', $task->id))->assertJsonPath('release_blocked', null);
    }

    public function test_material_and_subcontracting_tasks_are_never_blocked(): void
    {
        $line = $this->line();
        $material = $this->task($line, $this->sheet);
        $outsourced = $this->task($line, $this->subcontracting, ['ordre' => 2]);

        $this->postJson(route('production.task.statu.api.start', $material->id))->assertOk();
        $this->postJson(route('production.task.statu.api.start', $outsourced->id))->assertOk();

        $this->assertSame(1, $this->startCount($material));
        $this->assertSame(1, $this->startCount($outsourced));
    }

    public function test_the_task_screen_reports_the_missing_release(): void
    {
        $line = $this->line();
        $task = $this->task($line, $this->laser);

        $this->getJson(route('production.task.statu.api.show', $task->id))
            ->assertOk()
            ->assertJsonPath('release_blocked', __('production_release.blocked_start', ['line' => $line->code]));
    }

    public function test_a_sub_assembly_task_follows_the_release_of_its_order_line(): void
    {
        $line = $this->line();
        $sub = SubAssembly::create(['order_lines_id' => $line->id, 'ordre' => 1, 'qty' => 1, 'unit_price' => 0, 'child_id' => $line->product_id]);
        $task = $this->task($line, $this->laser, ['order_lines_id' => null, 'sub_assembly_id' => $sub->id]);

        $this->postJson(route('production.task.statu.api.start', $task->id))->assertStatus(422);

        $this->released($line);
        $this->postJson(route('production.task.statu.api.start', $task->id))->assertOk();
    }

    public function test_the_kanban_refuses_the_whole_move_when_a_task_is_not_released(): void
    {
        $inProgress = Status::create(['title' => 'In Progress', 'order' => 9]);
        $open = Status::where('title', 'Open')->first();
        $blocked = $this->task($this->line(), $this->laser, ['status_id' => $open->id]);
        $allowed = $this->task($this->released($this->line()), $this->laser, ['status_id' => $open->id]);

        $this->putJson(route('task.sync'), ['columns' => [
            ['id' => $inProgress->id, 'tasks' => [
                ['id' => $blocked->id, 'status_id' => $open->id],
                ['id' => $allowed->id, 'status_id' => $open->id],
            ]],
        ]])->assertStatus(422);

        $this->assertSame($open->id, $blocked->fresh()->status_id);
        $this->assertSame($open->id, $allowed->fresh()->status_id);
        $this->assertSame(0, $this->startCount($allowed));
    }

    public function test_the_kanban_started_column_counts_as_a_start(): void
    {
        $started = Status::where('title', 'Started')->first();
        $open = Status::where('title', 'Open')->first();
        $blocked = $this->task($this->line(), $this->laser, ['status_id' => $open->id]);
        $material = $this->task($this->line(), $this->sheet, ['status_id' => $open->id]);

        $this->putJson(route('task.sync'), ['columns' => [
            ['id' => $started->id, 'tasks' => [['id' => $blocked->id, 'status_id' => $open->id]]],
        ]])->assertStatus(422);
        $this->assertSame($open->id, $blocked->fresh()->status_id);

        // Matière : jamais bloquée, même posée en « Started ».
        $this->putJson(route('task.sync'), ['columns' => [
            ['id' => $started->id, 'tasks' => [['id' => $material->id, 'status_id' => $open->id]]],
        ]])->assertOk();
        $this->assertSame($started->id, $material->fresh()->status_id);
    }

    public function test_an_n2p_start_event_fails_without_release(): void
    {
        $line = $this->line();
        $task = $this->task($line, $this->laser);
        $endpoint = new \App\Models\Integrations\IntegrationEndpoint();

        try {
            app(HandleTaskEvent::class)->handle(
                $endpoint,
                ['of_code' => 'OF' . $line->id, 'line_ref' => (string) $line->id, 'operation_code' => 'LASER'],
                ['event_type' => HandleTaskEvent::EVENT_STARTED],
            );
            $this->fail('Le démarrage N2P aurait dû être refusé.');
        } catch (ProductionReleaseException) {
            $this->assertSame(0, $this->startCount($task));
        }

        $this->released($line);
        app(HandleTaskEvent::class)->handle(
            $endpoint,
            ['of_code' => 'OF' . $line->id, 'line_ref' => (string) $line->id, 'operation_code' => 'LASER'],
            ['event_type' => HandleTaskEvent::EVENT_STARTED],
        );
        $this->assertSame(1, $this->startCount($task));
    }

    public function test_putting_the_order_in_progress_leaves_unreleased_production_tasks_alone(): void
    {
        $open = Status::where('title', 'Open')->first();
        $line = $this->line();
        $production = $this->task($line, $this->laser, ['status_id' => $open->id]);
        $material = $this->task($line, $this->sheet, ['status_id' => $open->id, 'ordre' => 2]);

        $this->postJson(route('orders.json.statu', ['id' => $line->orders_id]), ['statu' => 2])->assertOk();

        $this->assertSame($open->id, $production->fresh()->status_id);
        $this->assertSame(Status::where('title', 'Started')->value('id'), $material->fresh()->status_id);
    }

    // ------------------------------------------------------------------
    // BPF : donner, annuler, revue et changement
    // ------------------------------------------------------------------

    public function test_releasing_requires_the_permission(): void
    {
        $line = $this->line();

        $this->postJson(route('order-lines.json.release', $line->id))->assertForbidden();
        $this->assertNull($line->fresh()->released_for_production_at);
    }

    public function test_the_engineering_office_releases_and_revokes_a_line(): void
    {
        $this->user->givePermissionTo('production-release');
        $line = $this->line();

        $this->postJson(route('order-lines.json.release', $line->id))
            ->assertOk()
            ->assertJsonPath('release.released_by', $this->user->name);
        $this->assertNotNull($line->fresh()->released_for_production_at);
        $this->assertSame($this->user->id, (int) $line->fresh()->released_for_production_by);

        $this->deleteJson(route('order-lines.json.release.revoke', $line->id))
            ->assertOk()
            ->assertJsonPath('release.released_at', null);
        $this->assertNull($line->fresh()->released_for_production_at);
    }

    public function test_the_release_cannot_be_revoked_once_a_task_started(): void
    {
        $this->user->givePermissionTo('production-release');
        $line = $this->released($this->line());
        $task = $this->task($line, $this->laser);
        $this->postJson(route('production.task.statu.api.start', $task->id))->assertOk();

        $this->deleteJson(route('order-lines.json.release.revoke', $line->id))
            ->assertStatus(422)
            ->assertJsonPath('message', __('production_release.already_started'));
        $this->assertNotNull($line->fresh()->released_for_production_at);
    }

    public function test_a_rejected_review_or_a_pending_change_prevents_the_release(): void
    {
        $this->user->givePermissionTo('production-release');

        $rejected = $this->line(['review_decision' => 'rejected']);
        $this->postJson(route('order-lines.json.release', $rejected->id))
            ->assertStatus(422)
            ->assertJsonPath('message', __('production_release.review_rejected'));

        $pending = $this->line(['change_requested_by' => $this->user->id, 'change_reason' => 'Nouvel indice client']);
        $this->postJson(route('order-lines.json.release', $pending->id))
            ->assertStatus(422)
            ->assertJsonPath('message', __('production_release.change_pending'));

        $approved = $this->line(['change_requested_by' => $this->user->id, 'change_approved_at' => now()]);
        $this->postJson(route('order-lines.json.release', $approved->id))->assertOk();
    }

    public function test_the_checked_lines_of_an_order_are_released_together(): void
    {
        $this->user->givePermissionTo('production-release');
        $first = $this->line();
        $second = OrderLines::factory()->create([
            'orders_id' => $first->orders_id, 'product_id' => $first->product_id, 'qty' => 2,
            'delivered_qty' => 0, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ordre' => 2,
        ]);
        $otherOrder = $this->line();

        $response = $this->postJson(route('orders.lines.json.release', ['orderId' => $first->orders_id]), [
            'line_ids' => [$first->id, $second->id, $otherOrder->id],
        ])->assertOk()->assertJsonPath('refused', []);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $response->json('released'));

        $this->assertNotNull($first->fresh()->released_for_production_at);
        $this->assertNotNull($second->fresh()->released_for_production_at);
        // Une ligne d'une autre commande n'est jamais touchée par cette porte.
        $this->assertNull($otherOrder->fresh()->released_for_production_at);
    }

    public function test_bulk_release_reports_refusals_without_stopping_the_others(): void
    {
        $this->user->givePermissionTo('production-release');
        $line = $this->line(['change_requested_by' => $this->user->id]);

        $this->postJson(route('orders.lines.json.release', ['orderId' => $line->orders_id]), ['line_ids' => [$line->id]])
            ->assertOk()
            ->assertJsonPath('released', [])
            ->assertJsonPath('refused.0.message', __('production_release.change_pending'));

        $this->assertNull($line->fresh()->released_for_production_at);
    }

    public function test_bulk_release_requires_the_permission(): void
    {
        $line = $this->line();

        $this->postJson(route('orders.lines.json.release', ['orderId' => $line->orders_id]), ['line_ids' => [$line->id]])
            ->assertForbidden();
    }

    public function test_a_new_revision_revokes_the_release(): void
    {
        $line = $this->released($this->line());

        $line->update(['label' => 'Libellé corrigé']);
        $this->assertNotNull($line->fresh()->released_for_production_at);

        $line->update(['ind' => 'B']);
        $this->assertNull($line->fresh()->released_for_production_at);
        $this->assertNull($line->fresh()->released_for_production_by);
    }

    public function test_a_duplicated_line_is_not_released(): void
    {
        $line = $this->released($this->line());

        $this->postJson(route('orders.lines.json.duplicate', ['orderId' => $line->orders_id, 'id' => $line->id]))->assertSuccessful();

        $copy = OrderLines::where('orders_id', $line->orders_id)->where('id', '!=', $line->id)->firstOrFail();
        $this->assertNull($copy->released_for_production_at);
    }

    public function test_the_task_list_shows_the_lock_on_unreleased_production_tasks(): void
    {
        $line = $this->line();
        $production = $this->task($line, $this->laser);
        $material = $this->task($line, $this->sheet, ['ordre' => 2]);
        $releasedTask = $this->task($this->released($this->line()), $this->laser);

        $tasks = collect($this->get(route('production.task'))->assertOk()->viewData('tasksData'))->keyBy('id');

        $this->assertTrue($tasks[$production->id]['release_blocked']);
        $this->assertFalse($tasks[$material->id]['release_blocked']);
        $this->assertFalse($tasks[$releasedTask->id]['release_blocked']);

        config(['production.require_release' => false]);
        $tasks = collect($this->get(route('production.task'))->viewData('tasksData'))->keyBy('id');
        $this->assertFalse($tasks[$production->id]['release_blocked']);
    }

    public function test_the_planning_lists_unreleased_production_tasks_only_when_required(): void
    {
        $line = $this->line();
        $production = $this->task($line, $this->laser);
        $material = $this->task($line, $this->sheet, ['ordre' => 2]);
        $releasedTask = $this->task($this->released($this->line()), $this->laser);
        $service = app(ProductionRelease::class);

        $this->assertSame([$production->id], $service->unreleasedTaskIds([$production->id, $material->id, $releasedTask->id]));

        config(['production.require_release' => false]);
        $this->assertSame([], $service->unreleasedTaskIds([$production->id]));
    }
}
