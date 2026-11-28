<?php

namespace Tests\Feature\Production;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Production\ProductionTimesVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Écran tâche, option factory.hide_production_times : ni temps prévus, ni temps
 * pointé, ni TRS, ni avancement pour l'opérateur ; le pointage reste possible.
 * production-times-view garde l'affichage complet. Coût, prix et marge ne sont
 * jamais envoyés, option ou non.
 */
class ProductionTimesVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const TIME_FIELDS = ['seting_time', 'unit_time', 'total_time', 'total_log_time', 'total_log_seconds', 'trs', 'progress'];

    private User $user;
    private MethodsServices $bending;

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
        $this->bending = MethodsServices::factory()->create(['code' => 'PLI', 'label' => 'Pliage', 'type' => MethodsServices::TYPE_PRODUCTIVE]);
    }

    private function hideTimes(bool $hide): void
    {
        app()->instance('Factory', new Factory(['hide_production_times' => $hide]));
    }

    private function task(): Task
    {
        $order = Orders::factory()->create(['companies_id' => Companies::factory()->create()->id, 'type' => 1, 'statu' => 1]);
        $line = OrderLines::factory()->create([
            'orders_id' => $order->id, 'product_id' => Products::factory()->create()->id, 'qty' => 10,
            'delivered_qty' => 0, 'invoiced_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1,
            'line_type' => 'article', 'ind' => 'A',
        ]);

        return Task::factory()->create([
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'methods_services_id' => $this->bending->id, 'type' => 1, 'ordre' => 20,
            'seting_time' => 0.5, 'unit_time' => 0.1, 'unit_cost' => 12, 'unit_price' => 20,
            'status_id' => Status::where('title', 'Open')->value('id'),
        ]);
    }

    public function test_times_are_sent_when_the_option_is_off(): void
    {
        $this->hideTimes(false);

        $json = $this->getJson(route('production.task.statu.api.show', $this->task()->id))
            ->assertOk()
            ->assertJsonPath('times_hidden', false)
            ->json();

        $this->assertNotNull($json['seting_time']);
        $this->assertNotNull($json['total_time']);
        $this->assertArrayHasKey('total_log_seconds', $json);
    }

    public function test_times_are_removed_for_an_operator_when_the_option_is_on(): void
    {
        $this->hideTimes(true);

        $json = $this->getJson(route('production.task.statu.api.show', $this->task()->id))
            ->assertOk()
            ->assertJsonPath('times_hidden', true)
            ->json();

        foreach (self::TIME_FIELDS as $field) {
            $this->assertNull($json[$field], $field);
        }
        // Les quantités restent : l'opérateur sait combien il lui reste à faire.
        $this->assertArrayHasKey('order_qty', $json);
        $this->assertArrayHasKey('total_net_good_qt', $json);
    }

    public function test_the_permission_keeps_the_full_display(): void
    {
        $this->hideTimes(true);
        $this->user->givePermissionTo(ProductionTimesVisibility::PERMISSION);

        $json = $this->getJson(route('production.task.statu.api.show', $this->task()->id))
            ->assertOk()
            ->assertJsonPath('times_hidden', false)
            ->json();

        $this->assertNotNull($json['total_time']);
    }

    public function test_cost_price_and_margin_are_never_sent(): void
    {
        foreach ([false, true] as $hide) {
            $this->hideTimes($hide);
            $json = $this->getJson(route('production.task.statu.api.show', $this->task()->id))->assertOk()->json();

            foreach (['formatted_unit_cost', 'formatted_unit_price', 'margin'] as $field) {
                $this->assertArrayNotHasKey($field, $json);
            }
        }
    }

    public function test_clocking_still_works_with_times_hidden(): void
    {
        $this->hideTimes(true);
        $task = $this->task();

        $this->postJson(route('production.task.statu.api.start', $task->id))->assertSuccessful();
        $this->postJson(route('production.task.statu.api.pause', $task->id))->assertSuccessful();

        $this->assertSame(
            [TaskActivities::TYPE_START, TaskActivities::TYPE_END],
            TaskActivities::where('task_id', $task->id)->orderBy('id')->pluck('type')->map(fn ($t) => (int) $t)->all(),
        );
    }

    public function test_landing_kpis_drop_hours_and_oee(): void
    {
        $this->hideTimes(true);

        $this->get(route('production.task.statu'))
            ->assertOk()
            ->assertViewHas('kpi', fn ($kpi) => $kpi['timesHidden'] === true
                && $kpi['totalProducedHours'] === null
                && $kpi['averageTRS'] === null);
    }
}
