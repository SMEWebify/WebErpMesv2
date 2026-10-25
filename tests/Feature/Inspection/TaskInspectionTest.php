<?php

namespace Tests\Feature\Inspection;

use App\Models\Companies\Companies;
use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionMeasure;
use App\Models\Inspection\InspectionPlan;
use App\Models\Inspection\InspectionProject;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Inspection\TaskInspection;
use Tests\TestCase;

/**
 * Lot 2 : le plan de contrôle validé suit la fabrication. Dossier créé par
 * ligne de commande, premier article, pièces à mesurer, jugement des mesures,
 * avertissement (jamais de blocage) et clôture.
 */
class TaskInspectionTest extends TestCase
{
    private User $user;
    private Companies $customer;
    private MethodsServices $laser;
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
        foreach (['Open', 'In progress', 'Finished'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }

        $this->customer = Companies::factory()->create(['statu_customer' => 2]);
        $this->laser = MethodsServices::factory()->create(['code' => 'LASER', 'label' => 'Laser']);
        $this->bending = MethodsServices::factory()->create(['code' => 'PLI', 'label' => 'Pliage']);
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function product(array $attributes = []): Products
    {
        return Products::factory()->create($attributes + ['ind' => 'A']);
    }

    /** Plan validé avec deux cotes : ⌀12 ±0,1 sur toutes les pièces, planéité 0,05 en premier article. */
    private function plan(Products $product, ?string $revision = 'A'): InspectionPlan
    {
        $plan = InspectionPlan::create([
            'products_id' => $product->id, 'revision' => $revision,
            'status' => InspectionPlan::STATUS_APPROVED, 'approved_at' => now(),
        ]);
        $plan->points()->create([
            'number' => 1, 'label' => '1', 'characteristic_type' => 'diameter',
            'nominal_value' => 12, 'tol_min' => -0.1, 'tol_max' => 0.1,
            'frequency_type' => 'all', 'is_fai' => true, 'methods_services_id' => $this->laser->id,
        ]);
        $plan->points()->create([
            'number' => 2, 'label' => '2', 'characteristic_type' => 'gdt', 'gdt_symbol' => 'flatness',
            'nominal_value' => 0, 'tol_min' => 0, 'tol_max' => 0.05,
            'frequency_type' => 'sample', 'frequency_value' => 2, 'is_fai' => true, 'is_critical' => true,
            'methods_services_id' => $this->bending->id,
        ]);

        return $plan;
    }

    private function task(Products $product, array $line = [], ?MethodsServices $service = null): Task
    {
        $order = Orders::factory()->create(['companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1]);
        $orderLine = OrderLines::factory()->create($line + [
            'orders_id' => $order->id, 'product_id' => $product->id, 'qty' => 4,
            'delivered_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1, 'line_type' => 'article',
        ]);

        return Task::factory()->create([
            'order_lines_id' => $orderLine->id, 'quote_lines_id' => null, 'products_id' => null,
            'methods_services_id' => ($service ?? $this->laser)->id, 'ordre' => 1,
        ]);
    }

    private function point(InspectionPlan $plan, int $number): InspectionControlPoint
    {
        return $plan->points()->where('number', $number)->firstOrFail();
    }

    private function measure(Task $task, InspectionControlPoint $point, int $part, array $payload)
    {
        return $this->postJson(route('production.task.statu.api.inspection.measure', $task->id), $payload + [
            'point_id' => $point->id, 'part' => $part,
        ]);
    }

    // ------------------------------------------------------------------
    // Plan et dossier
    // ------------------------------------------------------------------

    public function test_a_task_without_approved_plan_at_the_ordered_revision_has_no_inspection(): void
    {
        $product = $this->product();
        $this->plan($product, 'A');
        $task = $this->task($product, ['ind' => 'B']);

        $this->getJson(route('production.task.statu.api.show', $task->id))
            ->assertOk()
            ->assertJsonPath('inspection', null);
        $this->getJson(route('production.task.statu.api.inspection', $task->id))->assertNotFound();
    }

    public function test_the_task_screen_reports_the_inspection_without_creating_the_dossier(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);

        $this->getJson(route('production.task.statu.api.show', $task->id))
            ->assertOk()
            ->assertJsonPath('inspection.plan_id', $plan->id)
            ->assertJsonPath('inspection.dossier_id', null)
            ->assertJsonPath('inspection.is_fai', true);

        $this->assertSame(0, InspectionProject::count());
    }

    public function test_starting_the_task_creates_one_first_article_dossier_per_order_line(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $first = $this->task($product);

        $this->postJson(route('production.task.statu.api.start', $first->id))->assertOk();
        $this->postJson(route('production.task.statu.api.start', $first->id))->assertOk();

        $this->assertSame(1, InspectionProject::count());
        $dossier = InspectionProject::first();
        $this->assertTrue($dossier->is_fai);
        $this->assertSame($plan->id, $dossier->inspection_plan_id);

        // Seconde commande du même article au même indice : contrôle courant.
        $second = $this->task($product);
        $this->getJson(route('production.task.statu.api.inspection', $second->id))
            ->assertOk()
            ->assertJsonPath('dossier.is_fai', false);
    }

    public function test_the_sheet_lists_the_parts_to_measure_and_the_current_operation(): void
    {
        $product = $this->product();
        $this->plan($product);
        $task = $this->task($product);

        $points = $this->getJson(route('production.task.statu.api.inspection', $task->id))
            ->assertOk()
            ->assertJsonPath('dossier.parts', 4)
            ->assertJsonPath('dossier.required', 6)
            ->json('points');

        $this->assertSame([1, 2, 3, 4], $points[0]['parts']);
        $this->assertTrue($points[0]['this_operation']);
        $this->assertSame([1, 2], $points[1]['parts']);
        $this->assertFalse($points[1]['this_operation']);
    }

    public function test_sampling_rules_and_the_first_article_part(): void
    {
        $service = app(TaskInspection::class);
        $point = new InspectionControlPoint(['frequency_type' => 'every_n', 'frequency_value' => 3, 'is_fai' => false]);
        $this->assertSame([1, 4, 7, 10], $service->partsFor($point, 10, false));

        $point = new InspectionControlPoint(['frequency_type' => 'first', 'is_fai' => true]);
        $this->assertSame([1], $service->partsFor($point, 10, true));

        $point = new InspectionControlPoint(['frequency_type' => 'all']);
        $this->assertCount(TaskInspection::MAX_PARTS, $service->partsFor($point, 500, false));
    }

    // ------------------------------------------------------------------
    // Mesures
    // ------------------------------------------------------------------

    public function test_a_measure_is_judged_against_its_tolerances(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);

        $this->measure($task, $this->point($plan, 1), 1, ['value' => 12.1])->assertOk()->assertJsonPath('measure.result', 'ok');
        $this->measure($task, $this->point($plan, 1), 2, ['value' => 12.11])->assertOk()->assertJsonPath('measure.result', 'nok');
        $this->measure($task, $this->point($plan, 2), 1, ['value' => 0.06])->assertOk()->assertJsonPath('measure.result', 'nok');
    }

    public function test_without_tolerance_the_operator_judges(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $note = $plan->points()->create(['number' => 3, 'label' => 'Ébavurer', 'characteristic_type' => 'note', 'frequency_type' => 'first']);
        $task = $this->task($product);

        $this->measure($task, $note, 1, ['result' => 'nok'])->assertOk()->assertJsonPath('measure.result', 'nok');
        $this->measure($task, $note, 2, [])->assertStatus(422);
    }

    public function test_measuring_again_corrects_the_same_part(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);

        $this->measure($task, $this->point($plan, 1), 1, ['value' => 12.3]);
        $this->measure($task, $this->point($plan, 1), 1, ['value' => 12.02])->assertJsonPath('measure.result', 'ok');

        $this->assertSame(1, InspectionMeasure::count());
    }

    public function test_the_first_article_warning_clears_once_part_one_is_measured(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);

        $this->measure($task, $this->point($plan, 1), 1, ['value' => 12])
            ->assertJsonPath('summary.fai_pending', true);
        $this->measure($task, $this->point($plan, 2), 1, ['value' => 0.01])
            ->assertJsonPath('summary.fai_pending', false)
            ->assertJsonPath('summary.done', 2);
    }

    public function test_good_parts_can_be_declared_while_the_first_article_is_pending(): void
    {
        $product = $this->product();
        $this->plan($product);
        $task = $this->task($product);
        $this->postJson(route('production.task.statu.api.start', $task->id));

        // L'écran avertit, le serveur ne bloque jamais la production.
        $this->postJson(route('production.task.statu.api.good_qty', $task->id), ['qty' => 2])->assertOk();
    }

    public function test_a_point_of_another_plan_is_refused(): void
    {
        $product = $this->product();
        $this->plan($product);
        $task = $this->task($product);
        $foreign = $this->plan($this->product(['code' => 'OTHER']));

        $this->measure($task, $this->point($foreign, 1), 1, ['value' => 12])->assertNotFound();
    }

    public function test_a_closed_inspection_refuses_new_measures_and_moves_to_the_completed_tab(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);
        $this->measure($task, $this->point($plan, 1), 1, ['value' => 12]);

        $this->getJson(route('inspection.plans.index', ['tab' => 'in_progress']))
            ->assertJsonPath('counts.in_progress', 1)
            ->assertJsonPath('rows.0.measures', 1);

        $this->postJson(route('production.task.statu.api.inspection.close', $task->id))
            ->assertOk()
            ->assertJsonPath('summary.closed', true);
        $this->measure($task, $this->point($plan, 1), 2, ['value' => 12])->assertStatus(409);

        $this->getJson(route('inspection.plans.index', ['tab' => 'closed']))
            ->assertJsonPath('counts.closed', 1)
            ->assertJsonPath('counts.in_progress', 0)
            ->assertJsonPath('rows.0.product_code', $product->code);
    }
}
