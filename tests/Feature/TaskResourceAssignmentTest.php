<?php

namespace Tests\Feature;

use App\Models\Planning\TaskActivities;
use App\Services\TaskService;
use Carbon\Carbon;
use App\Jobs\CalculateTaskResources;
use App\Models\Workflow\OrderLines;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Planning\TaskResources;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Couvre l'articulation tâche → service → ressource :
 * une ressource peut servir plusieurs services, et une tâche ne peut être
 * affectée qu'à une ressource qui sait réaliser son service.
 */
class TaskResourceAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        // Le middleware CheckTaskStatus exige un Kanban configuré.
        Status::create(["title" => "Open", "order" => 1]);
    }

    private function service(int $type = MethodsServices::TYPE_PRODUCTIVE): MethodsServices
    {
        return MethodsServices::factory()->create(['type' => $type]);
    }

    /** Tâche de commande planifiée aujourd'hui, 2 h de charge. */
    private function productiveTask(MethodsServices $service): Task
    {
        return Task::factory()->create([
            'methods_services_id' => $service->id,
            'order_lines_id' => OrderLines::factory()->create()->id,
            'start_date' => now()->toDateString(),
            'seting_time' => 2,
            'unit_time' => 0,
            'qty' => 1,
        ]);
    }

    public function test_a_resource_can_serve_several_services()
    {
        $cutting = $this->service();
        $drilling = $this->service();

        $machine = MethodsRessources::factory()->create(['methods_services_id' => $cutting->id]);
        $machine->services()->syncWithoutDetaching([$drilling->id => ['preference' => 1]]);

        $this->assertEqualsCanonicalizing(
            [$cutting->id, $drilling->id],
            $machine->fresh()->services->pluck('id')->all()
        );
        // La machine remonte bien dans les deux services, sans duplication de ressource.
        $this->assertTrue($cutting->fresh()->Ressources->contains('id', $machine->id));
        $this->assertTrue($drilling->fresh()->Ressources->contains('id', $machine->id));
    }

    public function test_principal_service_is_mirrored_into_the_pivot()
    {
        $service = $this->service();
        $machine = MethodsRessources::factory()->create(['methods_services_id' => $service->id]);

        $this->assertDatabaseHas('methods_ressource_service', [
            'methods_ressources_id' => $machine->id,
            'methods_services_id'   => $service->id,
            'preference'            => 0,
        ]);
    }

    public function test_forcing_a_resource_that_does_not_serve_the_task_service_is_rejected()
    {
        $taskService = $this->service();
        $otherService = $this->service();

        $task = Task::factory()->create(['methods_services_id' => $taskService->id]);
        $foreignMachine = MethodsRessources::factory()->create(['methods_services_id' => $otherService->id]);

        $response = $this->putJson(
            route('production.task.statu.api.resource', ['id' => $task->id]),
            ['resource_id' => $foreignMachine->id]
        );

        $response->assertStatus(422);
        $this->assertDatabaseCount('task_resources', 0);
    }

    public function test_forcing_a_resource_of_the_task_service_is_accepted()
    {
        $taskService = $this->service();
        $task = Task::factory()->create(['methods_services_id' => $taskService->id]);
        $machine = MethodsRessources::factory()->create(['methods_services_id' => $taskService->id]);

        $response = $this->putJson(
            route('production.task.statu.api.resource', ['id' => $task->id]),
            ['resource_id' => $machine->id]
        );

        $response->assertOk();
        $this->assertDatabaseHas('task_resources', [
            'task_id'               => $task->id,
            'methods_ressources_id' => $machine->id,
            'role'                  => TaskResources::ROLE_MACHINE,
            'source'                => TaskResources::SOURCE_FORCED,
        ]);
    }

    public function test_productive_scope_excludes_material_and_subcontracting_tasks()
    {
        $productive = Task::factory()->create([
            'methods_services_id' => $this->service(MethodsServices::TYPE_PRODUCTIVE)->id,
        ]);
        Task::factory()->create([
            'methods_services_id' => $this->service(MethodsServices::TYPE_RAW_SHEET)->id,
        ]);
        Task::factory()->create([
            'methods_services_id' => $this->service(MethodsServices::TYPE_SUB_CONTRACTING)->id,
        ]);

        $this->assertSame([$productive->id], Task::productive()->pluck('id')->all());
    }

    public function test_automatic_assignment_only_targets_productive_tasks()
    {
        $productiveService = $this->service();
        $materialService = $this->service(MethodsServices::TYPE_RAW_SHEET);

        $machine = MethodsRessources::factory()->create([
            'methods_services_id' => $productiveService->id,
            'capacity' => 100,
        ]);

        $orderLine = OrderLines::factory()->create();

        $productiveTask = Task::factory()->create([
            'methods_services_id' => $productiveService->id,
            'order_lines_id' => $orderLine->id,
            'start_date' => now()->toDateString(),
            'seting_time' => 1,
            'unit_time' => 0,
            'qty' => 1,
        ]);
        $materialTask = Task::factory()->create([
            'methods_services_id' => $materialService->id,
            'order_lines_id' => $orderLine->id,
            'start_date' => now()->toDateString(),
            'seting_time' => 1,
            'unit_time' => 0,
            'qty' => 1,
        ]);

        (new CalculateTaskResources())->handle();

        $this->assertDatabaseHas('task_resources', [
            'task_id'               => $productiveTask->id,
            'methods_ressources_id' => $machine->id,
            'role'                  => TaskResources::ROLE_MACHINE,
            'source'                => TaskResources::SOURCE_AUTO,
        ]);
        $this->assertDatabaseMissing('task_resources', ['task_id' => $materialTask->id]);
    }

    /**
     * Une plieuse conduite par un opérateur consomme deux capacités : la machine
     * pour la totalité des heures, l'humain au prorata du labor_ratio.
     */
    public function test_labor_capacity_is_booked_alongside_the_machine()
    {
        $service = $this->service();

        $machine = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 100,
            'is_labor' => false,
            'labor_ratio' => 0.5,
        ]);
        $team = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 100,
            'is_labor' => true,
        ]);

        $task = $this->productiveTask($service);

        (new CalculateTaskResources())->handle();

        $this->assertDatabaseHas('task_resources', [
            'task_id'               => $task->id,
            'methods_ressources_id' => $machine->id,
            'role'                  => TaskResources::ROLE_MACHINE,
            'load_factor'           => 1,
        ]);
        $this->assertDatabaseHas('task_resources', [
            'task_id'               => $task->id,
            'methods_ressources_id' => $team->id,
            'role'                  => TaskResources::ROLE_LABOR,
            'load_factor'           => 0.5,
        ]);
    }

    /**
     * Poste purement manuel : aucune machine dans le service, la main-d'œuvre
     * porte la totalité des heures — c'est ce qui était impossible à planifier.
     */
    public function test_a_manual_station_is_planned_on_labor_capacity_alone()
    {
        $service = $this->service();
        $deburring = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 100,
            'is_labor' => true,
        ]);

        $task = $this->productiveTask($service);

        (new CalculateTaskResources())->handle();

        $this->assertDatabaseHas('task_resources', [
            'task_id'               => $task->id,
            'methods_ressources_id' => $deburring->id,
            'role'                  => TaskResources::ROLE_LABOR,
            'load_factor'           => 1,
        ]);
        $this->assertSame(1, $task->resources()->count());
    }

    /** Aucun opérateur réservé s'il n'y a pas de machine libre à conduire. */
    public function test_no_labor_is_booked_when_every_machine_is_saturated()
    {
        $service = $this->service();
        MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 0,
            'is_labor' => false,
            'labor_ratio' => 1,
        ]);
        MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 100,
            'is_labor' => true,
        ]);

        $task = $this->productiveTask($service);

        (new CalculateTaskResources())->handle();

        $this->assertDatabaseCount('task_resources', 0);
        $this->assertNotNull($task->id);
    }

    /** Forcer la main-d'œuvre ne doit pas détacher la machine, et inversement. */
    public function test_forcing_labor_keeps_the_machine_assignment()
    {
        $service = $this->service();
        $machine = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'is_labor' => false,
            'labor_ratio' => 1,
        ]);
        $team = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'is_labor' => true,
        ]);

        $task = Task::factory()->create(['methods_services_id' => $service->id]);

        $this->putJson(route('production.task.statu.api.resource', ['id' => $task->id]), ['resource_id' => $machine->id])
             ->assertOk();
        $this->putJson(route('production.task.statu.api.resource', ['id' => $task->id]), ['resource_id' => $team->id])
             ->assertOk();

        $this->assertSame($machine->id, $task->machineResource()->first()?->id);
        $this->assertSame($team->id, $task->laborResource()->first()?->id);
        $this->assertSame(2, $task->resources()->count());
    }

    /**
     * Le temps déclaré est imputé à la ressource au moment de la déclaration —
     * et n'y bouge plus si la tâche est réaffectée ensuite.
     */
    public function test_declared_time_is_frozen_on_the_resource_of_the_moment()
    {
        $service = $this->service();
        $first = MethodsRessources::factory()->create(['methods_services_id' => $service->id]);
        $second = MethodsRessources::factory()->create(['methods_services_id' => $service->id]);

        $task = Task::factory()->create(['methods_services_id' => $service->id]);
        $task->machineResource()->sync([$first->id => ['source' => TaskResources::SOURCE_MANUAL]]);

        app(TaskService::class)->recordTaskActivity($task->id, TaskActivities::TYPE_START);

        // Réaffectation a posteriori : l'historique ne doit pas suivre.
        $task->machineResource()->sync([$second->id => ['source' => TaskResources::SOURCE_FORCED]]);

        $this->assertDatabaseHas('task_activities', [
            'task_id'               => $task->id,
            'methods_ressources_id' => $first->id,
            'type'                  => TaskActivities::TYPE_START,
        ]);
        $this->assertSame(0, $second->taskActivities()->count());
    }

    /** Poste manuel : le réalisé est imputé à la capacité humaine. */
    public function test_declared_time_falls_back_on_the_labor_resource()
    {
        $service = $this->service();
        $team = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'is_labor' => true,
        ]);

        $task = Task::factory()->create(['methods_services_id' => $service->id]);
        $task->laborResource()->sync([$team->id => ['source' => TaskResources::SOURCE_MANUAL]]);

        app(TaskService::class)->recordTaskActivity($task->id, TaskActivities::TYPE_START);

        $this->assertSame(1, $team->taskActivities()->count());
    }

    /** Charge réelle : appariement départ / fin, par tâche, sur la fenêtre demandée. */
    public function test_declared_hours_pairs_start_and_end_events()
    {
        $service = $this->service();
        $machine = MethodsRessources::factory()->create(['methods_services_id' => $service->id]);

        $taskA = Task::factory()->create(['methods_services_id' => $service->id]);
        $taskB = Task::factory()->create(['methods_services_id' => $service->id]);

        $this->declare($machine, $taskA, TaskActivities::TYPE_START, '2026-08-10 08:00:00');
        $this->declare($machine, $taskA, TaskActivities::TYPE_END, '2026-08-10 10:30:00');
        // Deuxième tâche en parallèle, clôturée par un top "terminé".
        $this->declare($machine, $taskB, TaskActivities::TYPE_START, '2026-08-10 09:00:00');
        $this->declare($machine, $taskB, TaskActivities::TYPE_FINISH, '2026-08-10 10:00:00');

        $hours = $machine->declaredHours(
            Carbon::parse('2026-08-10 00:00:00'),
            Carbon::parse('2026-08-10 23:59:59')
        );

        $this->assertSame(3.5, $hours);
    }

    /** Un top départ resté ouvert court jusqu'à la fin de la fenêtre. */
    public function test_declared_hours_counts_a_still_running_task()
    {
        $service = $this->service();
        $machine = MethodsRessources::factory()->create(['methods_services_id' => $service->id]);
        $task = Task::factory()->create(['methods_services_id' => $service->id]);

        $this->declare($machine, $task, TaskActivities::TYPE_START, '2026-08-10 08:00:00');

        $hours = $machine->declaredHours(
            Carbon::parse('2026-08-10 00:00:00'),
            Carbon::parse('2026-08-10 12:00:00')
        );

        $this->assertSame(4.0, $hours);
    }

    private function declare(MethodsRessources $resource, Task $task, int $type, string $timestamp): void
    {
        TaskActivities::create([
            'task_id' => $task->id,
            'methods_ressources_id' => $resource->id,
            'user_id' => $this->user->id,
            'type' => $type,
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Rééquilibrage : l'ordonnancement reprend ses propres affectations, mais
     * ne touche ni aux choix humains ni aux tâches déjà commencées.
     */
    public function test_rebalance_releases_only_automatic_untouched_assignments()
    {
        $service = $this->service();
        $slow = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 100,
            'ordre' => 1,
        ]);

        $auto = $this->productiveTask($service);
        $forced = $this->productiveTask($service);
        $started = $this->productiveTask($service);

        $auto->resources()->attach($slow->id, ['role' => TaskResources::ROLE_MACHINE, 'source' => TaskResources::SOURCE_AUTO]);
        $forced->resources()->attach($slow->id, ['role' => TaskResources::ROLE_MACHINE, 'source' => TaskResources::SOURCE_FORCED]);
        $started->resources()->attach($slow->id, ['role' => TaskResources::ROLE_MACHINE, 'source' => TaskResources::SOURCE_AUTO]);

        // Du temps a déjà été déclaré sur la troisième : on n'y touche pas.
        app(TaskService::class)->recordTaskActivity($started->id, TaskActivities::TYPE_START);

        // Une machine plus prioritaire arrive après coup.
        $fast = MethodsRessources::factory()->create([
            'methods_services_id' => $service->id,
            'capacity' => 100,
            'ordre' => 0,
        ]);
        $fast->services()->syncWithoutDetaching([$service->id => ['preference' => 0]]);
        $slow->services()->syncWithoutDetaching([$service->id => ['preference' => 1]]);

        (new CalculateTaskResources(rebalance: true))->handle();

        // Réaffectée sur la machine préférée.
        $this->assertSame($fast->id, $auto->fresh()->machineResource()->first()?->id);
        // Choix humain et tâche démarrée conservés.
        $this->assertSame($slow->id, $forced->fresh()->machineResource()->first()?->id);
        $this->assertSame($slow->id, $started->fresh()->machineResource()->first()?->id);
    }
}
