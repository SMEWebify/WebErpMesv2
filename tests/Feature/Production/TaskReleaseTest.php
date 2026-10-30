<?php

namespace Tests\Feature\Production;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Planning\TaskDependency;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Production\TaskRelease;
use App\Services\Production\TaskReleaseException;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Visa de libération des phases : sur un poste de charge qui le demande, une
 * tâche terminée n'est libérée qu'après le visa d'un porteur de `task-release`.
 * La tâche suivante est seulement avertie, jamais bloquée ; sans poste à visa,
 * rien n'est calculé ni affiché.
 */
class TaskReleaseTest extends TestCase
{
    private User $user;
    private Companies $customer;
    private MethodsServices $bending;
    private MethodsServices $laser;
    private array $statuses = [];

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
            $this->statuses[$title] = Status::create(['title' => $title, 'order' => $order])->id;
        }

        $this->customer = Companies::factory()->create(['statu_customer' => 2]);
        $this->bending = MethodsServices::factory()->create([
            'code' => 'PLI', 'label' => 'Pliage', 'type' => MethodsServices::TYPE_PRODUCTIVE, 'requires_release' => true,
        ]);
        $this->laser = MethodsServices::factory()->create([
            'code' => 'LASER', 'label' => 'Laser', 'type' => MethodsServices::TYPE_PRODUCTIVE,
        ]);
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function line(): OrderLines
    {
        $order = Orders::factory()->create(['companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1]);

        return OrderLines::factory()->create([
            'orders_id' => $order->id, 'product_id' => Products::factory()->create()->id, 'qty' => 4,
            'delivered_qty' => 0, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ind' => 'A',
        ]);
    }

    private function task(OrderLines $line, MethodsServices $service, int $ordre, string $status = 'Open', array $attributes = []): Task
    {
        return Task::factory()->create($attributes + [
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'methods_services_id' => $service->id, 'type' => $service->type, 'ordre' => $ordre,
            'label' => $service->label, 'status_id' => $this->statuses[$status],
        ]);
    }

    private function grant(): void
    {
        $this->user->givePermissionTo(TaskRelease::PERMISSION);
    }

    // ------------------------------------------------------------------
    // Visa
    // ------------------------------------------------------------------

    public function test_release_without_the_permission_is_refused(): void
    {
        $task = $this->task($this->line(), $this->bending, 10, 'Finished');

        $this->postJson(route('production.task.statu.api.release', $task->id))->assertForbidden();

        $this->assertNull($task->fresh()->released_at);
    }

    public function test_the_service_also_refuses_a_manual_release_without_the_permission(): void
    {
        $task = $this->task($this->line(), $this->bending, 10, 'Finished');

        $this->expectException(TaskReleaseException::class);
        app(TaskRelease::class)->release($task->load('status'), $this->user, TaskRelease::SOURCE_MANUAL);
    }

    public function test_a_holder_releases_a_finished_task(): void
    {
        $this->grant();
        $task = $this->task($this->line(), $this->bending, 10, 'Finished');

        $this->postJson(route('production.task.statu.api.release', $task->id), ['comment' => '  Angles conformes  '])
            ->assertOk()
            ->assertJsonPath('message', __('task_release.released_ok'));

        $task->refresh();
        $this->assertNotNull($task->released_at);
        $this->assertSame($this->user->id, (int) $task->released_by);
        $this->assertSame('Angles conformes', $task->release_comment);

        $log = Activity::where('description', 'task_released')->latest('id')->first();
        $this->assertSame($task->id, (int) $log->subject_id);
        $this->assertSame(TaskRelease::SOURCE_MANUAL, $log->properties['source']);

        // Les colonnes du visa sont aussi au journal du modèle, avec leur ancienne valeur.
        $change = Activity::forSubject($task)->where('event', 'updated')->latest('id')->first();
        $this->assertSame($this->user->id, (int) $change->properties['attributes']['released_by']);
        $this->assertSame('Angles conformes', $change->properties['attributes']['release_comment']);
        $this->assertNull($change->properties['old']['released_by']);

        $this->getJson(route('production.task.statu.api.show', $task->id))
            ->assertJsonPath('phase_release.state', TaskRelease::STATE_RELEASED)
            ->assertJsonPath('phase_release.comment', 'Angles conformes');
    }

    public function test_an_unfinished_task_cannot_be_released(): void
    {
        $this->grant();
        $task = $this->task($this->line(), $this->bending, 10, 'In progress');

        $this->postJson(route('production.task.statu.api.release', $task->id))
            ->assertStatus(422)
            ->assertJsonPath('message', __('task_release.not_finished'));

        $this->assertNull($task->fresh()->released_at);
    }

    public function test_a_task_on_a_service_without_release_is_not_signed_off(): void
    {
        $this->grant();
        $task = $this->task($this->line(), $this->laser, 10, 'Finished');

        $this->postJson(route('production.task.statu.api.release', $task->id))
            ->assertStatus(422)
            ->assertJsonPath('message', __('task_release.not_required'));
    }

    public function test_an_external_flow_releases_through_the_same_method(): void
    {
        $task = $this->task($this->line(), $this->bending, 10, 'Finished');

        app(TaskRelease::class)->release($task->load('status'), null, TaskRelease::SOURCE_SITEFLOW);

        $this->assertNotNull($task->fresh()->released_at);
        $this->assertSame(TaskRelease::SOURCE_SITEFLOW, Activity::where('description', 'task_released')->latest('id')->first()->properties['source']);
    }

    public function test_a_holder_revokes_the_release(): void
    {
        $this->grant();
        $task = $this->task($this->line(), $this->bending, 10, 'Finished');
        $task->forceFill(['released_at' => now(), 'released_by' => $this->user->id])->save();

        $this->deleteJson(route('production.task.statu.api.release.revoke', $task->id))->assertOk();

        $this->assertNull($task->fresh()->released_at);
        $this->assertNull($task->fresh()->released_by);
    }

    public function test_a_copied_task_is_never_released(): void
    {
        $task = $this->task($this->line(), $this->bending, 10, 'Finished');
        $task->forceFill(['released_at' => now(), 'released_by' => $this->user->id, 'release_comment' => 'ok'])->save();

        $copy = $task->replicate();

        $this->assertNull($copy->released_at);
        $this->assertNull($copy->released_by);
        $this->assertNull($copy->release_comment);
    }

    // ------------------------------------------------------------------
    // Tâche suivante : avertissement, jamais de blocage
    // ------------------------------------------------------------------

    public function test_the_next_task_warns_while_the_previous_one_is_not_released(): void
    {
        $line = $this->line();
        $bending = $this->task($line, $this->bending, 10, 'Finished');
        $laser = $this->task($line, $this->laser, 20);

        $response = $this->getJson(route('production.task.statu.api.show', $laser->id))->assertOk();
        $this->assertSame($bending->id, $response->json('phase_release.pending.0.id'));
        $this->assertNotNull($response->json('phase_release.warning'));
        $this->assertNull($response->json('phase_release.state'));

        $this->grant();
        $this->postJson(route('production.task.statu.api.release', $bending->id))->assertOk();

        // Précédente libérée, poste sans visa : l'écran redevient celui d'avant.
        $this->getJson(route('production.task.statu.api.show', $laser->id))->assertJsonPath('phase_release', null);
    }

    public function test_the_next_task_starts_even_when_the_previous_one_is_not_released(): void
    {
        $line = $this->line();
        $this->task($line, $this->bending, 10, 'Finished');
        $laser = $this->task($line, $this->laser, 20);

        $this->postJson(route('production.task.statu.api.start', $laser->id))->assertOk();

        $this->assertSame(1, TaskActivities::where('task_id', $laser->id)->where('type', TaskActivities::TYPE_START)->count());
        $this->assertSame($this->statuses['In progress'], (int) $laser->fresh()->status_id);
    }

    public function test_the_kanban_moves_the_next_task_even_when_the_previous_one_is_not_released(): void
    {
        $line = $this->line();
        $this->task($line, $this->bending, 10, 'Finished');
        $laser = $this->task($line, $this->laser, 20);

        $response = $this->putJson(route('task.sync'), ['columns' => [[
            'id' => $this->statuses['In progress'],
            'tasks' => [['id' => $laser->id, 'status_id' => $this->statuses['Open']]],
        ]]])->assertOk();

        $this->assertSame($this->statuses['In progress'], (int) $laser->fresh()->status_id);

        $cards = collect($response->json())->flatMap(fn ($status) => $status['tasks'])->keyBy('id');
        $this->assertNotNull($cards[$laser->id]['phase_release']['warning']);
    }

    public function test_an_explicit_link_designates_the_previous_task(): void
    {
        $line = $this->line();
        $bending = $this->task($line, $this->bending, 10, 'Finished');
        $this->task($line, $this->laser, 20);
        $welding = $this->task($line, $this->laser, 30);
        $deburring = $this->task($line, $this->laser, 40);

        // 30 dépend de 10 en fin-début ; 40 démarre avec 10 (début-début) : pas d'avertissement.
        TaskDependency::create(['task_id' => $welding->id, 'predecessor_task_id' => $bending->id, 'type' => 'FS']);
        TaskDependency::create(['task_id' => $deburring->id, 'predecessor_task_id' => $bending->id, 'type' => 'SS']);

        $states = app(TaskRelease::class)->states(Task::whereIn('id', [$welding->id, $deburring->id])->get());

        $this->assertSame($bending->id, $states[$welding->id]['pending'][0]['id']);
        $this->assertArrayNotHasKey($deburring->id, $states);
    }

    // ------------------------------------------------------------------
    // Fonctionnement sans visa
    // ------------------------------------------------------------------

    public function test_without_any_service_requiring_release_nothing_changes(): void
    {
        $this->bending->update(['requires_release' => false]);
        $line = $this->line();
        $first = $this->task($line, $this->bending, 10, 'Finished');
        $second = $this->task($line, $this->laser, 20);

        $this->getJson(route('production.task.statu.api.show', $second->id))->assertJsonPath('phase_release', null);
        $this->getJson(route('production.task.statu.api.show', $first->id))->assertJsonPath('phase_release', null);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $states = app(TaskRelease::class)->states(Task::whereIn('id', [$first->id, $second->id])->get());
        $queries = count(DB::getQueryLog()) - 1; // la lecture des tâches du test
        DB::disableQueryLog();

        $this->assertSame([], $states);
        $this->assertSame(1, $queries);

        $response = $this->putJson(route('task.sync'), ['columns' => [
            ['id' => $this->statuses['Open'], 'tasks' => []],
        ]])->assertOk();
        $cards = collect($response->json())->flatMap(fn ($status) => $status['tasks']);
        $this->assertTrue($cards->every(fn ($card) => ! array_key_exists('phase_release', $card)));
    }

    public function test_the_query_count_does_not_grow_with_the_board(): void
    {
        $count = function (int $lines) {
            for ($i = 0; $i < $lines; $i++) {
                $line = $this->line();
                $this->task($line, $this->bending, 10, 'Finished');
                $this->task($line, $this->laser, 20);
            }
            $tasks = Task::all();

            DB::flushQueryLog();
            DB::enableQueryLog();
            app(TaskRelease::class)->states($tasks);
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            return $queries;
        };

        $this->assertSame($count(1), $count(4));
    }
}
