<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Task;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Workflow\OrderLineCalculatedPrice;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Prix figé à la commande : le prix calculé (relu sur la gamme) ne se réactive
 * plus hors préparation sans permission ni motif, jamais sur une ligne livrée ou
 * facturée, et sa désactivation enregistre le prix du moment.
 */
class OrderLineCalculatedPriceTest extends TestCase
{
    private User $user;
    private AccountingVat $vat;
    private MethodsServices $service;

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

        Permission::firstOrCreate(['name' => OrderLineCalculatedPrice::PERMISSION, 'guard_name' => 'web']);
        $this->vat = AccountingVat::factory()->create(['rate' => 20]);
        $this->service = MethodsServices::factory()->create(['type' => 1]);
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function order(array $attributes = []): Orders
    {
        return Orders::factory()->create($attributes + ['type' => 1, 'statu' => 1, 'review_decision' => null]);
    }

    /** Ligne vendue 100 € dont la gamme vaut 120 € (une opération à 120 €). */
    private function line(?Orders $order = null, array $attributes = []): OrderLines
    {
        $order ??= $this->order();

        $line = OrderLines::factory()->create($attributes + [
            'orders_id' => $order->id, 'code' => 'PIECE-' . fake()->unique()->numberBetween(1, 99999), 'qty' => 1,
            'delivered_qty' => 0, 'delivered_remaining_qty' => 1, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 1,
            'delivery_status' => 1, 'invoice_status' => 1, 'line_type' => 'article', 'selling_price' => 100,
            'discount' => 0, 'accounting_vats_id' => $this->vat->id, 'use_calculated_price' => false,
        ]);

        Task::factory()->create([
            'order_lines_id' => $line->id, 'quote_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'methods_services_id' => $this->service->id, 'type' => 1, 'ordre' => 1, 'unit_price' => 120,
            'planned_qty' => null,
        ]);

        return $line;
    }

    private function toggle(OrderLines $line, bool $enable, ?string $reason = null)
    {
        return $this->patchJson(route('orders.lines.json.calculated-price', ['orderId' => $line->orders_id, 'id' => $line->id]), array_filter([
            'enable' => $enable, 'reason' => $reason,
        ], fn ($v) => $v !== null));
    }

    private function grant(): void
    {
        $this->user->givePermissionTo(OrderLineCalculatedPrice::PERMISSION);
    }

    // ------------------------------------------------------------------
    // Activation
    // ------------------------------------------------------------------

    public function test_activation_is_free_while_the_order_is_in_preparation(): void
    {
        $line = $this->line();

        $this->toggle($line, true)->assertOk()
            ->assertJsonPath('line.use_calculated_price', true)
            ->assertJsonPath('line.effective_price', 120);

        $this->assertTrue((bool) $line->fresh()->use_calculated_price);
    }

    public function test_activation_is_refused_once_the_contract_review_is_approved(): void
    {
        $line = $this->line($this->order(['review_decision' => 'approved']));

        $this->toggle($line, true)->assertForbidden()
            ->assertJsonPath('message', __('calculated_price.errors.order_locked'));

        $this->assertFalse((bool) $line->fresh()->use_calculated_price);
    }

    public function test_activation_is_refused_once_the_order_left_the_open_status(): void
    {
        $line = $this->line($this->order(['statu' => 2]));

        $this->toggle($line, true)->assertForbidden();

        $this->assertFalse((bool) $line->fresh()->use_calculated_price);
    }

    public function test_the_permission_allows_activation_on_a_validated_order_with_a_reason(): void
    {
        $this->grant();
        $line = $this->line($this->order(['statu' => 2, 'review_decision' => 'approved']));

        // Sans motif : refus, l'écran demande le motif.
        $this->toggle($line, true)->assertStatus(422)
            ->assertJsonPath('reason_required', true);
        $this->assertFalse((bool) $line->fresh()->use_calculated_price);

        $this->toggle($line, true, 'Avenant client n°2')->assertOk()
            ->assertJsonPath('line.use_calculated_price', true);

        $this->assertTrue((bool) $line->fresh()->use_calculated_price);

        $log = Activity::where('subject_type', $line->getMorphClass())->where('subject_id', $line->id)
            ->where('description', 'calculated_price_enabled')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('Avenant client n°2', $log->properties['reason']);
        $this->assertTrue($log->properties['override']);
        $this->assertSame($this->user->id, $log->causer_id);
    }

    public function test_activation_is_always_refused_on_an_invoiced_line_even_with_the_permission(): void
    {
        $this->grant();
        $line = $this->line(null, ['invoiced_qty' => 0.5, 'invoiced_remaining_qty' => 0.5, 'invoice_status' => 2]);

        $this->toggle($line, true, 'Motif valable')->assertStatus(422)
            ->assertJsonPath('message', __('calculated_price.errors.shipped'));

        $this->assertFalse((bool) $line->fresh()->use_calculated_price);
    }

    public function test_activation_is_refused_on_a_partly_delivered_line(): void
    {
        $line = $this->line(null, ['delivered_qty' => 0.5, 'delivered_remaining_qty' => 0.5]);

        $this->toggle($line, true)->assertStatus(422)
            ->assertJsonPath('message', __('calculated_price.errors.shipped'));
    }

    // ------------------------------------------------------------------
    // Désactivation
    // ------------------------------------------------------------------

    public function test_deactivation_freezes_the_current_calculated_price(): void
    {
        $line = $this->line($this->order(['statu' => 2, 'review_decision' => 'approved']));
        $line->forceFill(['use_calculated_price' => true])->save(); // ligne existante déjà en prix calculé

        $this->toggle($line, false)->assertOk()
            ->assertJsonPath('line.use_calculated_price', false)
            ->assertJsonPath('line.effective_price', 120);

        $line->refresh();
        $this->assertFalse((bool) $line->use_calculated_price);
        $this->assertEqualsWithDelta(120, (float) $line->getRawOriginal('selling_price'), 0.0001);

        // Une modification ultérieure de la gamme ne change plus le prix.
        Task::where('order_lines_id', $line->id)->update(['unit_price' => 200]);
        $this->assertEqualsWithDelta(120, (float) $line->fresh()->selling_price, 0.0001);
    }

    public function test_deactivation_stays_possible_on_a_shipped_line(): void
    {
        $line = $this->line(null, ['invoiced_qty' => 1, 'invoiced_remaining_qty' => 0, 'invoice_status' => 3]);
        $line->forceFill(['use_calculated_price' => true])->save();

        $this->toggle($line, false)->assertOk();

        $this->assertEqualsWithDelta(120, (float) $line->fresh()->getRawOriginal('selling_price'), 0.0001);
    }

    // ------------------------------------------------------------------
    // Écran
    // ------------------------------------------------------------------

    public function test_the_lines_screen_explains_why_the_switch_is_greyed_out(): void
    {
        $order = $this->order(['review_decision' => 'approved']);
        $this->line($order);

        $this->getJson(route('orders.lines.json.for-order', ['orderId' => $order->id]))->assertOk()
            ->assertJsonPath('lines.0.calculated_price.can_enable', false)
            ->assertJsonPath('lines.0.calculated_price.blocked_reason', __('calculated_price.errors.order_locked'));

        $this->grant();
        $this->getJson(route('orders.lines.json.for-order', ['orderId' => $order->id]))->assertOk()
            ->assertJsonPath('lines.0.calculated_price.can_enable', true)
            ->assertJsonPath('lines.0.calculated_price.needs_reason', true);
    }

    public function test_the_listing_command_reports_existing_lines_without_changing_them(): void
    {
        $line = $this->line($this->order(['statu' => 2]));
        $line->forceFill(['use_calculated_price' => true])->save();

        $this->artisan('wem:orders:calculated-price-lines')->assertSuccessful();

        $line->refresh();
        $this->assertTrue((bool) $line->use_calculated_price);
        $this->assertEqualsWithDelta(100, (float) $line->getRawOriginal('selling_price'), 0.0001);
    }
}
