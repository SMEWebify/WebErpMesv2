<?php

namespace Tests\Feature\Inspection;

use App\Models\Companies\Companies;
use App\Models\Inspection\InspectionMeasure;
use App\Models\Inspection\InspectionPlan;
use App\Models\Inspection\InspectionProject;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Files\FileRole;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Fin du lot 2 et lot 3 : NC pré-remplie depuis une mesure, contrôle libre d'un
 * article sans tâche, rapport premier article (signatures, PDF dans la GED),
 * et l'ancienne adresse de l'écran d'inspection.
 */
class DossierReportTest extends TestCase
{
    private User $user;
    private Companies $customer;
    private MethodsServices $laser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Storage::fake(config('files.disk'));
        Permission::findOrCreate('inspection-plans-edit');
        Permission::findOrCreate('inspection-plans-approve');

        $this->user = User::factory()->create(['name' => 'Josiah Bondy']);
        $this->actingAs($this->user);

        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();
        foreach (['Open', 'In progress', 'Finished'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }

        $this->customer = Companies::factory()->create(['statu_customer' => 2, 'label' => 'MarsX']);
        $this->laser = MethodsServices::factory()->create(['code' => 'LASER', 'label' => 'Laser']);
    }

    private function product(): Products
    {
        return Products::factory()->create(['ind' => 'A', 'code' => 'BRKT-' . uniqid(), 'material' => 'Inox 304L', 'thickness' => 3]);
    }

    private function plan(Products $product): InspectionPlan
    {
        $plan = InspectionPlan::create([
            'products_id' => $product->id, 'revision' => 'A',
            'status' => InspectionPlan::STATUS_APPROVED, 'approved_at' => now(),
        ]);
        $plan->points()->create([
            'number' => 1, 'label' => '1', 'characteristic_type' => 'diameter',
            'nominal_value' => 12, 'tol_min' => -0.1, 'tol_max' => 0.1, 'frequency_type' => 'all', 'is_fai' => true,
            'grid_zone' => 'G8',
        ]);

        return $plan;
    }

    private function task(Products $product): Task
    {
        $order = Orders::factory()->create([
            'companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1, 'customer_reference' => 'PO4632',
        ]);
        $line = OrderLines::factory()->create([
            'orders_id' => $order->id, 'product_id' => $product->id, 'qty' => 2,
            'delivered_qty' => 0, 'delivery_status' => 1, 'invoice_status' => 1, 'line_type' => 'article',
        ]);

        return Task::factory()->create([
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null,
            'methods_services_id' => $this->laser->id, 'ordre' => 1,
        ]);
    }

    private function measureTask(Task $task, InspectionPlan $plan, int $part, float $value)
    {
        return $this->postJson(route('production.task.statu.api.inspection.measure', $task->id), [
            'point_id' => $plan->points()->first()->id, 'part' => $part, 'value' => $value,
        ]);
    }

    // ------------------------------------------------------------------
    // NC depuis une mesure
    // ------------------------------------------------------------------

    public function test_a_non_conforming_measure_opens_a_prefilled_quality_nc_once(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);
        $measureId = $this->measureTask($task, $plan, 2, 12.3)->assertJsonPath('measure.result', 'nok')->json('measure.id');

        $response = $this->postJson(route('production.task.statu.api.inspection.nc', [$task->id, $measureId]))
            ->assertOk()
            ->assertJsonPath('nc.code', 'NC-CTRL-' . $measureId);

        $nc = QualityNonConformity::findOrFail($response->json('nc.id'));
        $this->assertStringContainsString('⌀12 ±0.1', $nc->label);
        $this->assertStringContainsString('12.3', $nc->label);
        $this->assertStringContainsString('2', $nc->label);
        $this->assertSame($task->id, (int) $nc->task_id);
        $this->assertSame($task->order_lines_id, (int) $nc->order_lines_id);
        $this->assertStringContainsString('Josiah Bondy', $nc->failure_comment);
        $this->assertSame($nc->id, InspectionMeasure::find($measureId)->quality_non_conformity_id);

        // Redemander rend la même NC.
        $this->postJson(route('production.task.statu.api.inspection.nc', [$task->id, $measureId]))
            ->assertJsonPath('nc.id', $nc->id);
        $this->assertSame(1, QualityNonConformity::count());
    }

    public function test_a_conforming_measure_does_not_open_an_nc(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);
        $measureId = $this->measureTask($task, $plan, 1, 12.0)->json('measure.id');

        $this->postJson(route('production.task.statu.api.inspection.nc', [$task->id, $measureId]))->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Contrôle libre
    // ------------------------------------------------------------------

    public function test_a_free_inspection_needs_an_approved_plan(): void
    {
        $this->postJson(route('inspection.dossiers.store'), ['products_id' => $this->product()->id, 'qty' => 3])
            ->assertStatus(422);
    }

    public function test_a_free_inspection_is_measured_without_any_task(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);

        $url = $this->postJson(route('inspection.dossiers.store'), [
            'products_id' => $product->id, 'qty' => 3, 'title' => 'Réception lot 42', 'is_fai' => true,
        ])->assertCreated()->json('url');

        $dossier = InspectionProject::latest('id')->first();
        $this->assertNull($dossier->order_lines_id);
        $this->assertTrue($dossier->is_fai);
        $this->assertSame('CTRL-LIBRE-' . $dossier->id, $dossier->code);
        $this->get($url)->assertOk()->assertSee('inspection-dossier-app', false);

        $this->getJson(route('inspection.dossiers.show', $dossier))
            ->assertOk()
            ->assertJsonPath('dossier.is_free', true)
            ->assertJsonPath('dossier.required', 3)
            ->assertJsonPath('points.0.this_operation', true);

        $this->postJson(route('inspection.dossiers.measure', $dossier), [
            'point_id' => $plan->points()->first()->id, 'part' => 1, 'value' => 12.05,
        ])->assertOk()->assertJsonPath('summary.done', 1);

        $this->getJson(route('inspection.plans.index', ['tab' => 'in_progress']))
            ->assertJsonPath('rows.0.is_free', true)
            ->assertJsonPath('rows.0.open_url', route('quality.inspections.show', $dossier));
    }

    // ------------------------------------------------------------------
    // Rapport premier article
    // ------------------------------------------------------------------

    public function test_the_report_is_filled_from_the_erp(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);
        $this->measureTask($task, $plan, 1, 12.02);
        $dossier = InspectionProject::firstOrFail();

        $this->getJson(route('inspection.reports.show', $dossier))
            ->assertOk()
            ->assertJsonPath('form1.part_number', $product->code)
            ->assertJsonPath('form1.fair_identifier', $dossier->code)
            ->assertJsonPath('form1.po_number', 'PO4632')
            ->assertJsonPath('form1.customer', 'MarsX')
            ->assertJsonPath('form1.has_nc', false)
            ->assertJsonPath('form2.0.kind', 'material')
            ->assertJsonPath('form3.0.requirement', '⌀12 ±0.1')
            ->assertJsonPath('form3.0.location', 'G8')
            ->assertJsonPath('form3.0.results.0.value', 12.02)
            ->assertJsonPath('form3.0.result', 'ok');
    }

    public function test_only_known_fields_are_saved(): void
    {
        $product = $this->product();
        $this->plan($product);
        $task = $this->task($product);
        $this->getJson(route('production.task.statu.api.inspection', $task->id));
        $dossier = InspectionProject::firstOrFail();

        $this->patchJson(route('inspection.reports.update', $dossier), [
            'fields' => ['serial_number' => 'SN-001', 'organization' => 'Pirate'],
            'fai_type' => 'partial',
        ])->assertOk()
            ->assertJsonPath('form1.serial_number', 'SN-001')
            ->assertJsonPath('form1.fai_type', 'partial');

        $this->assertArrayNotHasKey('organization', $dossier->fresh()->report_fields);
    }

    public function test_signatures_follow_permissions_and_approval_freezes_the_report(): void
    {
        $product = $this->product();
        $this->plan($product);
        $task = $this->task($product);
        $this->getJson(route('production.task.statu.api.inspection', $task->id));
        $dossier = InspectionProject::firstOrFail();
        $sign = fn (string $role, bool $value = true) => $this->postJson(route('inspection.reports.sign', $dossier), ['role' => $role, 'sign' => $value]);

        $sign('verified')->assertForbidden();
        $this->user->givePermissionTo('inspection-plans-edit');
        $sign('verified')->assertOk()->assertJsonPath('form1.verified_by', 'Josiah Bondy');

        $sign('approved')->assertForbidden();
        $this->user->givePermissionTo('inspection-plans-approve');
        $sign('approved')->assertOk()->assertJsonPath('form1.approved_by', 'Josiah Bondy');

        $this->patchJson(route('inspection.reports.update', $dossier), ['report_comment' => 'x'])->assertStatus(409);

        $sign('approved', false)->assertOk();
        $this->patchJson(route('inspection.reports.update', $dossier), ['report_comment' => 'RAS'])->assertOk();
    }

    public function test_approval_requires_verification_first(): void
    {
        $this->user->givePermissionTo(['inspection-plans-edit', 'inspection-plans-approve']);
        $product = $this->product();
        $this->plan($product);
        $task = $this->task($product);
        $this->getJson(route('production.task.statu.api.inspection', $task->id));
        $dossier = InspectionProject::firstOrFail();

        $this->postJson(route('inspection.reports.sign', $dossier), ['role' => 'approved', 'sign' => true])->assertStatus(422);
    }

    public function test_the_pdf_is_stored_in_the_order_documents(): void
    {
        $product = $this->product();
        $plan = $this->plan($product);
        $task = $this->task($product);
        $this->measureTask($task, $plan, 1, 12.02);
        $dossier = InspectionProject::firstOrFail();

        $fileId = $this->postJson(route('inspection.reports.generate', $dossier))
            ->assertOk()
            ->assertJsonStructure(['file' => ['id', 'view_url'], 'form1'])
            ->json('file.id');

        $this->assertSame($fileId, $dossier->fresh()->report_file_id);
        $order = Orders::findOrFail($dossier->orders_id);
        $this->assertTrue($order->files()->whereKey($fileId)->wherePivot('role', FileRole::CONTROL)->exists());
        $this->get(route('quality.inspections.report', $dossier))->assertOk()->assertSee('inspection-report-app', false);
    }

    // ------------------------------------------------------------------
    // Ancien écran
    // ------------------------------------------------------------------

    public function test_the_old_inspection_url_leads_to_the_quality_plans(): void
    {
        $this->get(route('quality.inspection.projects'))->assertRedirect();
    }
}
