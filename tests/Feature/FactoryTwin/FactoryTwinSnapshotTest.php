<?php

namespace Tests\Feature\FactoryTwin;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Planning\TaskResources;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Integrations\FactoryTwin\FactoryTwinSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Données du jumeau numérique (contrat factory-twin.snapshot/1), calculées sans le package.
 */
class FactoryTwinSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MethodsServices $laser;
    private MethodsServices $bending;
    private MethodsRessources $laserMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setTime(14, 0));

        $this->user = User::factory()->create();
        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        foreach (['Open', 'Started', 'In progress', 'Finished'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }

        $this->laser = MethodsServices::factory()->create(['code' => 'laser', 'label' => 'Découpe laser', 'type' => MethodsServices::TYPE_PRODUCTIVE, 'ordre' => 10]);
        $this->bending = MethodsServices::factory()->create(['code' => 'PLI', 'label' => 'Pliage', 'type' => MethodsServices::TYPE_PRODUCTIVE, 'ordre' => 20]);
        MethodsServices::factory()->create(['code' => 'TOLE', 'label' => 'Tôle', 'type' => MethodsServices::TYPE_RAW_SHEET]);

        // 40 h par semaine, sans régime horaire : 8 h par jour.
        $this->laserMachine = MethodsRessources::factory()->create(['code' => 'LAS-01', 'label' => 'Laser fibre', 'methods_services_id' => $this->laser->id, 'capacity' => 40, 'is_labor' => false]);
        MethodsRessources::factory()->create(['code' => 'OP-PLI', 'label' => 'Plieur', 'methods_services_id' => $this->bending->id, 'capacity' => 40, 'is_labor' => true]);
    }

    private function line(array $order = [], array $line = []): OrderLines
    {
        $order = Orders::factory()->create($order + [
            'code' => 'CMD-' . fake()->unique()->numberBetween(100, 999),
            'companies_id' => Companies::factory()->create(['label' => 'Client Test'])->id, 'type' => 1, 'statu' => 1,
        ]);

        return OrderLines::factory()->create($line + [
            'orders_id' => $order->id, 'product_id' => Products::factory()->create()->id, 'qty' => 10, 'label' => 'Équerre',
            'delivered_qty' => 0, 'delivered_remaining_qty' => 10, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ind' => 'A', 'delivery_date' => now()->addWeek()->toDateString(),
        ]);
    }

    private function task(OrderLines $line, MethodsServices $service, int $ordre, string $status, array $extra = []): Task
    {
        return Task::factory()->create($extra + [
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'methods_services_id' => $service->id, 'type' => 1, 'ordre' => $ordre, 'code' => $service->code,
            'seting_time' => 0.5, 'unit_time' => 0.1, 'planned_qty' => null, 'start_date' => null, 'end_date' => null,
            'status_id' => Status::where('title', $status)->value('id'),
        ]);
    }

    private function activity(Task $task, int $type, string $at, float $good = 0): void
    {
        TaskActivities::create(['task_id' => $task->id, 'user_id' => $this->user->id, 'type' => $type, 'timestamp' => $at, 'good_qt' => $good, 'bad_qt' => 0]);
    }

    private function onMachine(Task $task): void
    {
        TaskResources::create(['task_id' => $task->id, 'methods_ressources_id' => $this->laserMachine->id, 'role' => TaskResources::ROLE_MACHINE, 'source' => 'manual']);
    }

    private function op(array $data, string $code): array
    {
        return collect($data['operations'])->firstWhere('code', $code);
    }

    private function snapshot(): array
    {
        return app(FactoryTwinSnapshot::class)->snapshot();
    }

    public function test_operations_and_machines_follow_the_contract(): void
    {
        $data = $this->snapshot();

        $this->assertSame('factory-twin.snapshot/1', $data['contract']);
        // Postes productifs seulement, code en majuscules (clé du plan du jumeau).
        $this->assertSame(['LASER', 'PLI'], array_column($data['operations'], 'code'));
        $this->assertSame(480.0, (float) $this->op($data, 'LASER')['capacity_min']);
        // Main-d'œuvre : capacité du poste, mais pas de machine 3D.
        $this->assertSame(480.0, (float) $this->op($data, 'PLI')['capacity_min']);
        $this->assertSame(['LAS-01'], array_column($data['resources'], 'code'));
        $this->assertSame('LASER', $data['resources'][0]['operation_code']);
        $this->assertSame('Découpe laser', $data['resources'][0]['type']);
        $this->assertSame(['LASER', 'PLI'], array_keys($data['queues']['operations']));
        $this->assertSame(['LAS-01'], array_keys($data['queues']['resources']));
    }

    public function test_queues_charge_and_late_tasks(): void
    {
        $line = $this->line();
        $cutting = $this->task($line, $this->laser, 10, 'In progress', ['end_date' => now()->subHour()]);
        $this->onMachine($cutting);
        $this->activity($cutting, TaskActivities::TYPE_START, now()->subHour()->toDateTimeString());
        $this->task($line, $this->bending, 20, 'Open');

        $data = $this->snapshot();
        $laserQueue = $data['queues']['resources']['LAS-01'];

        $this->assertSame(1, $laserQueue['in_progress']);
        $this->assertSame(1, $laserQueue['late']);
        $task = $laserQueue['tasks'][0];
        $this->assertSame('in_progress', $task['status']);
        $this->assertSame('OF' . $line->id, $task['of_code']);
        $this->assertSame(Orders::find($line->orders_id)->code, $task['order_ref']);
        $this->assertSame('Client Test', $task['customer_name']);
        $this->assertSame(90.0, (float) $task['planned_time_min']); // 0,5 h + 0,1 h × 10
        $this->assertTrue($task['is_late']);

        $laser = $data['resources'][0];
        $this->assertSame(60.0, (float) $laser['done_min']);
        $this->assertSame(30.0, (float) $laser['remaining_min']);
        $this->assertSame(19, $laser['usage_percent']); // 90 / 480

        // La tâche de pliage sans date n'entre pas dans la charge du jour, mais reste en file.
        $this->assertSame(1, $data['queues']['operations']['PLI']['open']);
        $this->assertSame(0.0, (float) $this->op($data, 'PLI')['remaining_min']);
    }

    public function test_finished_tasks_and_closed_orders_leave_the_queues(): void
    {
        $line = $this->line();
        $this->task($line, $this->laser, 10, 'Finished');
        $closed = $this->line(['statu' => 4]);
        $this->task($closed, $this->laser, 10, 'Open');

        $data = $this->snapshot();

        $this->assertSame(0, $data['queues']['operations']['LASER']['open']);
    }

    public function test_time_worked_today_counts_from_midnight(): void
    {
        $line = $this->line(['statu' => 4]);
        $task = $this->task($line, $this->laser, 10, 'Finished');
        $this->onMachine($task);
        // Session commencée hier à 23 h, terminée aujourd'hui à 1 h : une heure aujourd'hui.
        $this->activity($task, TaskActivities::TYPE_START, today()->subHour()->toDateTimeString());
        $this->activity($task, TaskActivities::TYPE_FINISH, today()->addHour()->toDateTimeString());

        $data = $this->snapshot();

        $this->assertSame(60.0, (float) $data['resources'][0]['done_min']);
        $this->assertSame(0, $data['queues']['resources']['LAS-01']['open']);
    }

    public function test_flows_between_operations_and_to_shipping(): void
    {
        $waiting = $this->line();
        $cut = $this->task($waiting, $this->laser, 10, 'Finished');
        $this->onMachine($cut);
        $this->activity($cut, TaskActivities::TYPE_FINISH, now()->subHours(3)->toDateTimeString());
        $this->task($waiting, $this->bending, 20, 'Open');

        $partial = $this->line();
        $cutting = $this->task($partial, $this->laser, 10, 'In progress');
        $this->onMachine($cutting);
        $this->activity($cutting, TaskActivities::TYPE_DECLARE_GOOD, now()->subHour()->toDateTimeString(), 4);
        $this->task($partial, $this->bending, 20, 'Open');

        $done = $this->line([], ['delivered_remaining_qty' => 6, 'delivery_date' => today()->subDay()->toDateString()]);
        $this->task($done, $this->laser, 10, 'Finished');
        $bent = $this->task($done, $this->bending, 20, 'Finished');
        $this->activity($bent, TaskActivities::TYPE_FINISH, now()->subMinutes(30)->toDateTimeString());

        $flows = collect($this->snapshot()['flows'])->keyBy('key');

        $toBending = $flows['r:LAS-01>o:PLI'];
        $this->assertSame(['resource' => 'LAS-01', 'operation' => 'LASER'], $toBending['from']);
        $this->assertSame(['resource' => null, 'operation' => 'PLI'], $toBending['to']);
        $this->assertSame(14.0, (float) $toBending['qty']); // 10 finies + 4 bonnes déclarées
        $this->assertSame(180, $toBending['max_wait_min']);
        $this->assertEqualsCanonicalizing(['done', 'in_progress'], array_column($toBending['jobs'], 'upstream_status'));

        $shipping = $flows['o:PLI>a:shipping'];
        $this->assertSame('shipping', $shipping['to']['area']);
        $this->assertSame(6.0, (float) $shipping['qty']);
        $this->assertSame(1, $shipping['late']);
    }

    public function test_order_trace(): void
    {
        $line = $this->line(['code' => 'CMD-777']);
        $this->task($line, $this->laser, 10, 'Finished');
        $this->task($line, $this->bending, 20, 'Open');

        $trace = app(FactoryTwinSnapshot::class)->orderTrace('cmd-777');

        $this->assertTrue($trace['found']);
        $this->assertSame('CMD-777', $trace['ref']);
        $this->assertSame(['LASER', 'PLI'], $trace['route']);
        $this->assertSame('done', $trace['operations']['LASER']['status']);
        $this->assertSame('planned', $trace['operations']['PLI']['status']);
        $this->assertSame(50, $trace['overall_progress']);
        $this->assertSame(1, $trace['jobs_count']);
        $this->assertSame($line->delivery_date, $trace['due_date']);

        $this->assertFalse(app(FactoryTwinSnapshot::class)->orderTrace('INCONNUE')['found']);
    }

    public function test_query_count_does_not_grow_with_tasks(): void
    {
        $count = function () {
            // Instance neuve : ResourceCapacityService garde des caches entre deux appels.
            app()->forgetInstance(\App\Services\ResourceCapacityService::class);
            DB::flushQueryLog();
            DB::enableQueryLog();
            app()->make(FactoryTwinSnapshot::class)->snapshot();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        $line = $this->line();
        $this->task($line, $this->laser, 10, 'Open');
        $count(); // remplit les caches de l'application (régime horaire par défaut…)
        $few = $count();

        for ($i = 0; $i < 5; $i++) {
            $other = $this->line();
            $this->task($other, $this->laser, 10, 'In progress');
            $this->task($other, $this->bending, 20, 'Open');
        }

        $this->assertSame($few, $count());
    }
}
