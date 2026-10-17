<?php

namespace Tests\Feature\Planning;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Task;
use App\Models\Planning\TaskDependency;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Liens et quantité par opération vus de l'extérieur : API devis (chemin d'un
 * outil de devis), export des commandes (chemin d'un outil de planification)
 * et données de l'éditeur de gamme.
 */
class RoutingApiTest extends TestCase
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
        \App\Models\Methods\MethodsFamilies::factory()->create();
    }

    private function task(array $over): array
    {
        return array_merge(['type' => 1, 'seting_time' => 1, 'unit_time' => 0.1, 'unit_cost' => 1, 'unit_price' => 1], $over);
    }

    private function payload(array $tasks): array
    {
        // Contact et adresse : facultatifs pour l'API, obligatoires en base.
        $company = Companies::factory()->create();

        return [
            'label'                            => 'Deux tours en chevauchement',
            'companies_id'                     => $company->id,
            'companies_contacts_id'            => CompaniesContacts::factory()->create(['companies_id' => $company->id])->id,
            'companies_addresses_id'           => CompaniesAddresses::factory()->create(['companies_id' => $company->id])->id,
            'accounting_payment_conditions_id' => AccountingPaymentConditions::value('id'),
            'accounting_payment_methods_id'    => AccountingPaymentMethod::value('id'),
            'accounting_deliveries_id'         => AccountingDelivery::value('id'),
            'lines' => [[
                'ordre'              => 1,
                'label'              => 'Pièce tournée deux côtés',
                'qty'                => 100,
                'selling_price'      => 50,
                'methods_units_id'   => MethodsUnits::value('id'),
                'accounting_vats_id' => AccountingVat::value('id'),
                'tasks'              => $tasks,
            ]],
        ];
    }

    private function workshopTasks(): array
    {
        return [
            $this->task(['label' => 'Débit', 'ordre' => 10]),
            $this->task(['label' => 'Tournage côté 1', 'ordre' => 20]),
            $this->task(['label' => 'Tournage côté 2', 'ordre' => 30, 'planned_qty' => 40, 'predecessors' => [
                ['ordre' => 20, 'type' => 'SS', 'lag_value' => 1, 'lag_unit' => 'piece'],
                ['ordre' => 20, 'type' => 'FF', 'lag_value' => 1, 'lag_unit' => 'piece'],
            ]]),
            $this->task(['label' => 'Fraisage', 'ordre' => 40, 'predecessors' => [['ordre' => 20], ['ordre' => 30]]]),
        ];
    }

    // ------------------------------------------------------------------
    // API devis
    // ------------------------------------------------------------------

    public function test_the_quote_api_records_links_by_operation_number(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson(route('api.quote.store'), $this->payload($this->workshopTasks()))->assertCreated();

        $tasks = collect($response->json('data.quote_lines.0.tasks') ?? $response->json('quote_lines.0.tasks'))->keyBy('ordre');
        $this->assertCount(2, $tasks[30]['predecessors']);
        $this->assertSame(20, $tasks[30]['predecessors'][0]['ordre']);
        $this->assertSame('SS', $tasks[30]['predecessors'][0]['type']);
        $this->assertSame('piece', $tasks[30]['predecessors'][0]['lag_unit']);
        $this->assertEquals(40, $tasks[30]['planned_qty']);
        $this->assertSame([20, 30], array_column($tasks[40]['predecessors'], 'ordre'));
        $this->assertSame([], $tasks[20]['predecessors']);
        $this->assertSame(4, TaskDependency::count());
    }

    public function test_links_are_kept_when_absent_and_cleared_when_empty(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $response = $this->postJson(route('api.quote.store'), $this->payload($this->workshopTasks()))->assertCreated();
        $quote = Quotes::latest('id')->firstOrFail();
        $ids = Task::where('quote_lines_id', $quote->QuoteLines()->value('id'))->pluck('id', 'ordre');

        // Renvoi sans `predecessors` : les liens restent.
        $keep = array_map(fn (array $t) => array_diff_key($t + ['id' => $ids[$t['ordre']]], ['predecessors' => true]), $this->workshopTasks());
        $payload = $this->payload($keep);
        $payload['lines'][0]['id'] = $quote->QuoteLines()->value('id');
        $this->putJson(route('api.quote.update', $quote), $payload)->assertOk();
        $this->assertSame(4, TaskDependency::count());

        // `predecessors: []` sur le fraisage : il suit de nouveau la précédente.
        $payload['lines'][0]['tasks'][3]['predecessors'] = [];
        $this->putJson(route('api.quote.update', $quote), $payload)->assertOk();
        $this->assertSame(2, TaskDependency::count());
        $this->assertSame(0, TaskDependency::where('task_id', $ids[40])->count());
    }

    public function test_an_unknown_operation_or_a_loop_cancels_the_whole_quote(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $before = Quotes::count();

        $tasks = $this->workshopTasks();
        $tasks[3]['predecessors'] = [['ordre' => 99]];
        $this->postJson(route('api.quote.store'), $this->payload($tasks))->assertStatus(422);

        // 10 après 20, alors que 20 suit encore 10.
        $tasks = $this->workshopTasks();
        $tasks[0]['predecessors'] = [['ordre' => 20]];
        $this->postJson(route('api.quote.store'), $this->payload($tasks))->assertStatus(422);

        $tasks = $this->workshopTasks();
        $tasks[3]['predecessors'] = [['ordre' => 20, 'type' => 'FS', 'lag_value' => 5, 'lag_unit' => 'piece']];
        $this->postJson(route('api.quote.store'), $this->payload($tasks))->assertStatus(422);

        $this->assertSame($before, Quotes::count(), 'rien n\'est enregistré');
        $this->assertSame(0, TaskDependency::count());
    }

    // ------------------------------------------------------------------
    // Export et éditeur de gamme
    // ------------------------------------------------------------------

    /** @return array{0: OrderLines, 1: array<int, Task>} */
    private function orderRouting(): array
    {
        $order = Orders::factory()->create(['statu' => 1]);
        $line  = OrderLines::factory()->create(['orders_id' => $order->id, 'qty' => 100, 'line_type' => 'article', 'tasks_status' => 1]);
        $blank = ['quote_lines_id' => null, 'order_lines_id' => $line->id, 'products_id' => null, 'sub_assembly_id' => null, 'type' => 1, 'not_recalculate' => 0];
        $tasks = [];
        foreach ([10, 20, 30, 40] as $ordre) {
            $tasks[$ordre] = Task::factory()->create($blank + ['ordre' => $ordre, 'seting_time' => 1, 'unit_time' => 0.1]);
        }

        return [$line, $tasks];
    }

    public function test_the_order_export_gives_a_planning_tool_everything_it_needs(): void
    {
        [$line, $tasks] = $this->orderRouting();
        $tasks[30]->update(['planned_qty' => 40]);
        TaskDependency::create(['task_id' => $tasks[30]->id, 'predecessor_task_id' => $tasks[20]->id, 'type' => 'SS', 'lag_value' => 1, 'lag_unit' => 'piece']);
        TaskDependency::create(['task_id' => $tasks[30]->id, 'predecessor_task_id' => $tasks[20]->id, 'type' => 'FF', 'lag_value' => 1, 'lag_unit' => 'piece']);

        $this->actingAs(User::factory()->create(), 'api');
        $response = $this->getJson('/api/exports/sales-orders?include_lines=1')->assertOk();

        $exported = collect($response->json('data.0.order_lines.0.tasks'))->keyBy('ordre');
        $this->assertSame('20SS+1pc;20FF+1pc', $exported[30]['predecessors']);
        $this->assertSame(40.0, (float) $exported[30]['planned_qty']);
        $this->assertSame(5.0, (float) $exported[30]['planned_hours'], '1 h + 40 × 0,1 h');
        $this->assertSame('', $exported[20]['predecessors'], 'sans lien : suit la précédente');
        $this->assertNull($exported[20]['planned_qty']);
        $this->assertSame(11.0, (float) $exported[20]['planned_hours'], '1 h + 100 × 0,1 h');
    }

    public function test_the_routing_editor_shows_links_and_saves_the_operation_quantity(): void
    {
        $this->actingAs(User::factory()->create());
        [$line, $tasks] = $this->orderRouting();
        TaskDependency::create(['task_id' => $tasks[40]->id, 'predecessor_task_id' => $tasks[20]->id, 'type' => 'FS']);
        TaskDependency::create(['task_id' => $tasks[40]->id, 'predecessor_task_id' => $tasks[30]->id, 'type' => 'FS']);
        $route = fn (string $name, $id) => route($name, ['id_type' => 'order_lines_id', 'id_page' => $line->orders_id, 'id' => $id]);

        $data = $this->getJson(route('task.manage.json.initial', ['id_type' => 'order_lines_id', 'id_page' => $line->orders_id, 'id_line' => $line->id]))
            ->assertOk()
            ->json('dependencies');
        $this->assertTrue($data[$tasks[20]->id]['implicit']);
        $this->assertSame('after 10', $data[$tasks[20]->id]['display']);
        $this->assertSame('20FS;30FS', $data[$tasks[40]->id]['display']);

        $this->patchJson($route('task.manage.json.task.planned-qty', $tasks[30]->id), ['planned_qty' => 60])
            ->assertOk()->assertJsonPath('task.planned_qty', fn ($qty) => (float) $qty === 60.0);
        $this->patchJson($route('task.manage.json.task.planned-qty', $tasks[30]->id), ['planned_qty' => null])
            ->assertOk()->assertJsonPath('task.planned_qty', null);

        $bom = Task::factory()->create(['quote_lines_id' => null, 'order_lines_id' => $line->id, 'products_id' => null, 'sub_assembly_id' => null, 'type' => 2, 'ordre' => 5]);
        $this->patchJson($route('task.manage.json.task.planned-qty', $bom->id), ['planned_qty' => 3])->assertStatus(422);

        Orders::whereKey($line->orders_id)->update(['statu' => 2]);
        $this->patchJson($route('task.manage.json.task.planned-qty', $tasks[30]->id), ['planned_qty' => 60])->assertForbidden();
    }
}
