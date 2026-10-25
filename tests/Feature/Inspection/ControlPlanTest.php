<?php

namespace Tests\Feature\Inspection;

use App\Models\Companies\Companies;
use App\Models\File;
use App\Models\Inspection\InspectionPlan;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Files\FileRole;
use App\Services\Files\FileStorageService;
use App\Services\Inspection\ControlPlanNeeds;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Plans de contrôle (lot 1) : qui a besoin d'un plan, droits d'écriture et de
 * validation, figement d'un plan validé, cotes et miniatures.
 */
class ControlPlanTest extends TestCase
{
    private User $user;
    private Companies $customer;

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

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // ProductsFactory pioche une unité et une famille existantes.
        \App\Models\Methods\MethodsUnits::factory()->create();
        \App\Models\Methods\MethodsFamilies::factory()->create();

        $this->customer = Companies::factory()->create(['statu_customer' => 2, 'requires_control_plan' => true]);
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function product(array $attributes = []): Products
    {
        return Products::factory()->create($attributes + ['ind' => 'A', 'control_plan_mode' => null]);
    }

    private function orderLine(Products $product, array $line = [], array $order = [], ?Companies $company = null): OrderLines
    {
        $order = Orders::factory()->create($order + [
            'companies_id' => ($company ?? $this->customer)->id,
            'type'         => 1,
            'statu'        => 1,
        ]);

        return OrderLines::factory()->create($line + [
            'orders_id'       => $order->id,
            'product_id'      => $product->id,
            'qty'             => 5,
            'delivered_qty'   => 0,
            'delivery_status' => 1,
            'invoice_status'  => 1,
            'delivery_date'   => '2026-12-01',
            'line_type'       => 'article',
        ]);
    }

    private function planFile(Products $product): File
    {
        $file = app(FileStorageService::class)->storeContents('%PDF-1.4 fake', 'plan.pdf', 'application/pdf');
        app(FileStorageService::class)->attach($file, $product, FileRole::PLAN, true);

        return $file;
    }

    private function plan(Products $product, array $attributes = []): InspectionPlan
    {
        return InspectionPlan::create($attributes + [
            'products_id' => $product->id,
            'revision'    => $product->ind,
            'file_id'     => $this->planFile($product)->id,
            'status'      => InspectionPlan::STATUS_DRAFT,
            'created_by'  => $this->user->id,
        ]);
    }

    private function needKeys(): array
    {
        return app(ControlPlanNeeds::class)->all()
            ->map(fn ($n) => $n['product_code'] . '@' . ($n['revision'] ?? ''))
            ->all();
    }

    // ------------------------------------------------------------------
    // Onglet « À bullé »
    // ------------------------------------------------------------------

    public function test_an_item_ordered_by_a_customer_requiring_a_plan_needs_one(): void
    {
        $product = $this->product(['code' => 'BRKT-1']);
        $this->orderLine($product);

        $this->assertSame(['BRKT-1@A'], $this->needKeys());
    }

    public function test_a_customer_without_the_requirement_does_not_create_a_need(): void
    {
        $other = Companies::factory()->create(['statu_customer' => 2, 'requires_control_plan' => false]);
        $this->orderLine($this->product(), company: $other);

        $this->assertSame([], $this->needKeys());
    }

    public function test_the_item_setting_overrides_the_customer_in_both_directions(): void
    {
        $other = Companies::factory()->create(['statu_customer' => 2, 'requires_control_plan' => false]);
        $this->orderLine($this->product(['code' => 'ALWAYS', 'control_plan_mode' => 'always']), company: $other);
        $this->orderLine($this->product(['code' => 'NEVER', 'control_plan_mode' => 'never']));

        $this->assertSame(['ALWAYS@A'], $this->needKeys());
    }

    public function test_the_revision_is_the_one_ordered_and_lines_are_grouped_by_revision(): void
    {
        $product = $this->product(['code' => 'REV']);
        $this->orderLine($product, ['ind' => 'B', 'delivery_date' => '2026-11-10']);
        $this->orderLine($product, ['ind' => 'B', 'delivery_date' => '2026-12-20']);
        $this->orderLine($product, ['ind' => null, 'delivery_date' => '2026-11-20']);

        $needs = app(ControlPlanNeeds::class)->all();

        $this->assertSame(['REV@B', 'REV@A'], $needs->map(fn ($n) => $n['product_code'] . '@' . $n['revision'])->all());
        $this->assertSame('2026-11-10', $needs[0]['needed_at']);
        $this->assertSame(2, $needs[0]['orders_count']);
    }

    public function test_delivered_lines_stopped_orders_and_presentation_lines_are_ignored(): void
    {
        $this->orderLine($this->product(['code' => 'DELIVERED']), ['delivery_status' => 3]);
        $this->orderLine($this->product(['code' => 'CANCELED']), [], ['statu' => 6]);
        $this->orderLine($this->product(['code' => 'INTERNAL']), [], ['type' => 2]);

        $this->assertSame([], $this->needKeys());
    }

    public function test_an_existing_plan_at_that_revision_removes_the_need_but_not_for_another_revision(): void
    {
        $product = $this->product(['code' => 'PLANNED']);
        $this->orderLine($product, ['ind' => 'A']);
        $this->orderLine($product, ['ind' => 'B']);
        $this->plan($product, ['revision' => 'A', 'status' => InspectionPlan::STATUS_APPROVED]);

        $this->assertSame(['PLANNED@B'], $this->needKeys());
    }

    public function test_the_need_list_costs_two_queries_whatever_its_size(): void
    {
        foreach (range(1, 6) as $i) {
            $product = $this->product(['code' => "Q{$i}"]);
            $this->planFile($product);
            $this->orderLine($product);
        }

        DB::enableQueryLog();
        $needs = app(ControlPlanNeeds::class)->all();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertCount(6, $needs);
        $this->assertNotNull($needs[0]['file']);
        $this->assertSame(2, $count);
    }

    public function test_the_index_returns_rows_and_tab_counts(): void
    {
        $product = $this->product();
        $this->orderLine($product);
        $this->plan($this->product(['code' => 'DRAFTED']));

        $this->getJson(route('inspection.plans.index', ['tab' => 'needs']))
            ->assertOk()
            ->assertJsonPath('counts.needs', 1)
            ->assertJsonPath('counts.draft', 1)
            ->assertJsonPath('rows.0.product_id', $product->id);

        $this->getJson(route('inspection.plans.index', ['tab' => 'draft']))
            ->assertOk()
            ->assertJsonPath('rows.0.product_code', 'DRAFTED');
    }

    // ------------------------------------------------------------------
    // Droits et cycle de vie
    // ------------------------------------------------------------------

    public function test_creating_a_plan_requires_the_edit_permission(): void
    {
        $product = $this->product();

        $this->postJson(route('inspection.plans.store'), ['products_id' => $product->id])->assertForbidden();

        $this->user->givePermissionTo('inspection-plans-edit');
        $this->postJson(route('inspection.plans.store'), ['products_id' => $product->id])
            ->assertCreated()
            ->assertJsonPath('plan.revision', 'A');
    }

    public function test_creating_twice_reopens_the_same_draft_with_the_primary_drawing(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $product = $this->product();
        $file = $this->planFile($product);

        $first = $this->postJson(route('inspection.plans.store'), ['products_id' => $product->id])->json('plan');
        $second = $this->postJson(route('inspection.plans.store'), ['products_id' => $product->id])->assertOk()->json('plan');

        $this->assertSame($first['id'], $second['id']);
        $this->assertSame($file->id, $first['file']['id']);
    }

    public function test_a_file_from_another_entity_is_refused(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $foreign = $this->planFile($this->product(['code' => 'OTHER']));

        $this->postJson(route('inspection.plans.store'), ['products_id' => $this->product()->id, 'file_id' => $foreign->id])
            ->assertStatus(422);
    }

    public function test_approving_requires_the_approve_permission_and_at_least_one_point(): void
    {
        $plan = $this->plan($this->product());

        $this->postJson(route('inspection.plans.approve', $plan))->assertForbidden();

        $this->user->givePermissionTo('inspection-plans-approve');
        $this->postJson(route('inspection.plans.approve', $plan))->assertStatus(422);

        $plan->points()->create(['number' => 1, 'label' => '1', 'box_x' => 0.1, 'box_y' => 0.1, 'box_w' => 0.1, 'box_h' => 0.1]);
        $this->postJson(route('inspection.plans.approve', $plan))
            ->assertOk()
            ->assertJsonPath('plan.status', 'approved');
    }

    public function test_an_approved_plan_is_frozen(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product(), ['status' => InspectionPlan::STATUS_APPROVED]);
        $point = $plan->points()->create(['number' => 1, 'label' => '1']);

        $this->postJson(route('inspection.plans.points.store', $plan), ['characteristic_type' => 'dimension'])->assertStatus(409);
        $this->patchJson(route('inspection.plans.points.update', $point), ['nominal_value' => 3])->assertStatus(409);
        $this->deleteJson(route('inspection.plans.points.destroy', $point))->assertStatus(409);
        $this->deleteJson(route('inspection.plans.destroy', $plan))->assertStatus(409);
    }

    public function test_a_new_version_copies_the_points_and_its_approval_retires_the_previous_one_of_the_same_revision(): void
    {
        $this->user->givePermissionTo(['inspection-plans-edit', 'inspection-plans-approve']);
        $product = $this->product();
        $old = $this->plan($product, ['status' => InspectionPlan::STATUS_APPROVED]);
        $old->points()->create(['number' => 1, 'label' => '1', 'nominal_value' => 12, 'tol_min' => -0.1, 'tol_max' => 0.1]);
        $otherRevision = $this->plan($product, ['revision' => 'Z', 'status' => InspectionPlan::STATUS_APPROVED]);

        $new = $this->postJson(route('inspection.plans.store'), [
            'products_id' => $product->id, 'revision' => 'A', 'from_plan_id' => $old->id, 'file_id' => $old->file_id,
        ])->assertCreated()->json('plan');

        $this->assertSame(1, InspectionPlan::find($new['id'])->points()->count());

        $this->postJson(route('inspection.plans.approve', $new['id']))->assertOk();

        $this->assertSame(InspectionPlan::STATUS_OBSOLETE, $old->fresh()->status);
        $this->assertSame(InspectionPlan::STATUS_APPROVED, $otherRevision->fresh()->status);
        $this->assertSame(1, $old->points()->count(), 'the retired plan keeps its points');
    }

    // ------------------------------------------------------------------
    // Cotes
    // ------------------------------------------------------------------

    public function test_points_are_numbered_and_validated(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product());

        $this->postJson(route('inspection.plans.points.store', $plan), ['box_x' => 1.4])->assertStatus(422);
        $this->postJson(route('inspection.plans.points.store', $plan), ['characteristic_type' => 'gdt', 'gdt_symbol' => 'banana'])->assertStatus(422);

        $first = $this->postJson(route('inspection.plans.points.store', $plan), [
            'characteristic_type' => 'gdt', 'gdt_symbol' => 'perpendicularity', 'tol_max' => 0.03, 'datums' => 'A, B,',
            'box_x' => 0.1, 'box_y' => 0.2, 'box_w' => 0.05, 'box_h' => 0.02,
        ])->assertCreated()->json('point');
        $second = $this->postJson(route('inspection.plans.points.store', $plan), ['characteristic_type' => 'dimension'])->json('point');

        $this->assertSame([1, 2], [$first['number'], $second['number']]);
        $this->assertSame('A,B', $first['datums']);
    }

    public function test_a_non_gdt_type_drops_the_gdt_fields(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product());
        $point = $plan->points()->create(['number' => 1, 'label' => '1', 'characteristic_type' => 'gdt', 'gdt_symbol' => 'flatness', 'datums' => 'A']);

        $this->patchJson(route('inspection.plans.points.update', $point), ['characteristic_type' => 'radius'])
            ->assertOk()
            ->assertJsonPath('point.gdt_symbol', null)
            ->assertJsonPath('point.datums', null);
    }

    public function test_renumbering_follows_the_reading_order(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product());
        $p1 = $plan->points()->create(['number' => 1, 'label' => '1', 'plan_page' => 2, 'box_x' => 0.1, 'box_y' => 0.1]);
        $p2 = $plan->points()->create(['number' => 2, 'label' => 'Note', 'plan_page' => 1, 'box_x' => 0.8, 'box_y' => 0.5]);
        $p3 = $plan->points()->create(['number' => 3, 'label' => '3', 'plan_page' => 1, 'box_x' => 0.6, 'box_y' => 0.1]);
        $p4 = $plan->points()->create(['number' => 4, 'label' => '4', 'plan_page' => 1, 'box_x' => 0.2, 'box_y' => 0.11]);

        $points = $this->postJson(route('inspection.plans.points.renumber', $plan))->assertOk()->json('points');

        // Même bande (y ≈ 0.10) lue de gauche à droite, puis plus bas, puis page 2.
        $this->assertSame([$p4->id, $p3->id, $p2->id, $p1->id], array_column($points, 'id'));
        $this->assertSame([1, 2, 3, 4], array_column($points, 'number'));
        // Un libellé numérique suit le numéro, un libellé saisi est gardé.
        $this->assertSame(['1', '2', 'Note', '4'], array_column($points, 'label'));
    }

    public function test_the_rotation_is_a_quarter_turn_saved_on_the_plan(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product());

        $this->patchJson(route('inspection.plans.update', $plan), ['rotation' => 45])->assertStatus(422);
        $this->patchJson(route('inspection.plans.update', $plan), ['rotation' => 270])
            ->assertOk()
            ->assertJsonPath('plan.rotation', 270);
    }

    public function test_renumbering_follows_the_reading_order_of_the_rotated_drawing(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product(), ['rotation' => 90]);
        // Repère de la page : A en haut à gauche, B en bas à gauche. Tourné de 90°
        // dans le sens horaire, B se retrouve en haut à gauche et A en haut à droite.
        $a = $plan->points()->create(['number' => 1, 'label' => '1', 'box_x' => 0.1, 'box_y' => 0.1, 'box_w' => 0.05, 'box_h' => 0.05]);
        $b = $plan->points()->create(['number' => 2, 'label' => '2', 'box_x' => 0.1, 'box_y' => 0.8, 'box_w' => 0.05, 'box_h' => 0.05]);

        $points = $this->postJson(route('inspection.plans.points.renumber', $plan))->assertOk()->json('points');

        $this->assertSame([$b->id, $a->id], array_column($points, 'id'));
    }

    // ------------------------------------------------------------------
    // Lecture d'une zone par l'IA
    // ------------------------------------------------------------------

    private function fakeClaude(string $answer): void
    {
        config(['ai.providers.claude.api_key' => 'test-key', 'ai.providers.claude.default_model' => 'claude-haiku-4-5-20251001']);
        Http::fake(['api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => $answer]]])]);
    }

    public function test_the_ai_reads_a_dimension_and_writes_nothing(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product());
        $this->fakeClaude('```json {"type":"diameter","text":"2X Ø12 ±0,1","count":2,"nominal":12,"tol_minus":-0.1,"tol_plus":0.1,"unit":"mm"} ```');

        $this->post(route('inspection.plans.read-zone', $plan), ['image' => UploadedFile::fake()->image('zone.png', 300, 120)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('characteristic.characteristic_type', 'diameter')
            ->assertJsonPath('characteristic.count', 2)
            ->assertJsonPath('characteristic.nominal_value', 12)
            ->assertJsonPath('characteristic.tol_min', -0.1)
            ->assertJsonPath('characteristic.label', '2X Ø12 ±0,1');

        $this->assertSame(0, $plan->points()->count());
        Http::assertSent(fn ($request) => $request['messages'][0]['content'][0]['source']['media_type'] === 'image/png');
    }

    public function test_the_ai_reads_a_feature_control_frame(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product());
        $this->fakeClaude('{"type":"gdt","text":"⌖ ⌀0.03 Ⓜ A B C","gdt_symbol":"position","tolerance_zone":0.03,"diameter_zone":true,"modifier":"M","datums":"A, B, C"}');

        $this->post(route('inspection.plans.read-zone', $plan), ['image' => UploadedFile::fake()->image('zone.png', 300, 120)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('characteristic.gdt_symbol', 'position')
            ->assertJsonPath('characteristic.tol_max', 0.03)
            ->assertJsonPath('characteristic.diameter_zone', true)
            ->assertJsonPath('characteristic.material_modifier', 'M')
            ->assertJsonPath('characteristic.datums', 'A,B,C');
    }

    public function test_reading_needs_a_configured_ai_and_a_png(): void
    {
        $this->user->givePermissionTo('inspection-plans-edit');
        $plan = $this->plan($this->product());
        config(['ai.providers.claude.api_key' => null]);

        $this->post(route('inspection.plans.read-zone', $plan), ['image' => UploadedFile::fake()->image('zone.png', 300, 120)], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('message', __('inspection.ai_missing'));

        $this->fakeClaude('{}');
        $this->post(route('inspection.plans.read-zone', $plan), ['image' => UploadedFile::fake()->image('zone.jpg', 300, 120)], ['Accept' => 'application/json'])
            ->assertStatus(422);
    }

    public function test_the_index_and_bubbler_pages_render(): void
    {
        $plan = $this->plan($this->product());

        // Les textes doivent arriver jusqu'au composant (régression : @json coupé aux virgules).
        $this->get(route('quality.plans'))->assertOk()
            ->assertSee('quality-plans-app', false)
            ->assertSee('"tab_needs":"' . __('inspection.tab_needs') . '"', false);
        $this->get(route('inspection.plans.bubbler', $plan))->assertOk()->assertSee('plan-bubbler-app', false);
        $this->getJson(route('inspection.plans.show', $plan))->assertOk()->assertJsonPath('plan.file.id', $plan->file_id);
    }

    // ------------------------------------------------------------------
    // Miniatures
    // ------------------------------------------------------------------

    public function test_a_thumbnail_must_be_a_small_image(): void
    {
        $file = $this->planFile($this->product());

        $this->post(route('files.json.thumbnail', $file), ['thumbnail' => UploadedFile::fake()->createWithContent('t.png', '<svg/>')], ['Accept' => 'application/json'])
            ->assertStatus(422);
        $this->post(route('files.json.thumbnail', $file), ['thumbnail' => UploadedFile::fake()->image('t.png', 900, 600)], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->post(route('files.json.thumbnail', $file), ['thumbnail' => UploadedFile::fake()->image('t.png', 400, 280)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['thumbnail_url']);

        $this->assertNotNull($file->fresh()->thumbnail_path);
        $this->get($file->fresh()->thumbnail_url)->assertOk()->assertHeader('Content-Type', 'image/png');
    }
}
