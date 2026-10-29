<?php

namespace Tests\Feature\Production;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\AffairCategory;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Production\ManufacturingDossierException;
use App\Services\Production\ManufacturingDossierGate;
use Tests\TestCase;

/**
 * Dossier de fin de fabrication : une ligne qui l'exige ne passe ni sur un BL ni
 * sur une facture tant qu'il n'est pas validé, quelle que soit la porte ; une
 * ligne sans exigence n'est jamais concernée.
 */
class ManufacturingDossierGateTest extends TestCase
{
    private User $user;
    private Companies $customer;
    private AccountingVat $vat;

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

        $this->customer = Companies::factory()->create(['statu_customer' => 2]);
        CompaniesContacts::factory()->create(['companies_id' => $this->customer->id]);
        CompaniesAddresses::factory()->create(['companies_id' => $this->customer->id]);
        $this->vat = AccountingVat::factory()->create(['rate' => 20]);
    }

    // ------------------------------------------------------------------
    // Fabriques
    // ------------------------------------------------------------------

    private function order(array $attributes = []): Orders
    {
        return Orders::factory()->create($attributes + ['companies_id' => $this->customer->id, 'type' => 1, 'statu' => 1]);
    }

    /** Ligne à livrer et à facturer en entier. */
    private function line(?Orders $order = null, array $attributes = []): OrderLines
    {
        $order ??= $this->order();

        return OrderLines::factory()->create($attributes + [
            'orders_id' => $order->id, 'code' => 'PIECE-' . fake()->unique()->numberBetween(1, 99999), 'qty' => 4,
            'delivered_qty' => 0, 'delivered_remaining_qty' => 4, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 4,
            'delivery_status' => 1, 'invoice_status' => 1, 'line_type' => 'article', 'selling_price' => 100,
            'discount' => 0, 'accounting_vats_id' => $this->vat->id,
        ]);
    }

    private function requiring(OrderLines $line): OrderLines
    {
        $line->forceFill(['dossier_required' => true])->save();

        return $line;
    }

    private function validated(OrderLines $line): OrderLines
    {
        app(ManufacturingDossierGate::class)->validate($line, $this->user);

        return $line;
    }

    /** BL déjà émis sur la ligne (avant que le dossier ne soit exigé, par exemple). */
    private function deliveryLine(OrderLines $line, float $qty = 4): DeliveryLines
    {
        $order = $line->order;
        $delivery = Deliverys::factory()->create([
            'companies_id' => $order->companies_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'order_id' => $order->id,
        ]);

        return DeliveryLines::create([
            'deliverys_id' => $delivery->id, 'order_line_id' => $line->id, 'ordre' => 10, 'qty' => $qty, 'invoice_status' => 1,
        ]);
    }

    private function deliveryRequestPayload(OrderLines $line, string $code = 'BL-DOSSIER-1'): array
    {
        $order = $line->order;

        return [
            'code' => $code, 'label' => 'BL', 'companies_id' => $order->companies_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'user_id' => $this->user->id, 'lines' => [['order_line_id' => $line->id, 'qty' => 2]],
        ];
    }

    private function invoiceRequestPayload(DeliveryLines $deliveryLine, string $code = 'FA-DOSSIER-1'): array
    {
        $order = $deliveryLine->OrderLine->order;

        return [
            'code' => $code, 'label' => 'Facture', 'companies_id' => $order->companies_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'user_id' => $this->user->id, 'lines' => [$deliveryLine->id],
        ];
    }

    // ------------------------------------------------------------------
    // BL
    // ------------------------------------------------------------------

    public function test_delivery_request_screen_refuses_a_line_without_validated_dossier(): void
    {
        $line = $this->requiring($this->line());

        $this->postJson(route('deliverys-request.store'), $this->deliveryRequestPayload($line))
            ->assertStatus(422)
            ->assertJsonPath('dossier_lines.0.id', $line->id)
            ->assertJsonPath('errors.lines.0', fn ($message) => str_contains($message, $line->code));

        $this->assertDatabaseMissing('deliverys', ['code' => 'BL-DOSSIER-1']);
        $this->assertSame(0, DeliveryLines::where('order_line_id', $line->id)->count());
    }

    public function test_delivery_from_the_order_refuses_a_line_without_validated_dossier(): void
    {
        $order   = $this->order();
        $free    = $this->line($order);
        $blocked = $this->requiring($this->line($order));

        $this->postJson(route('orders.lines.json.store-delivery', $order->id), ['line_ids' => [$free->id, $blocked->id]])
            ->assertStatus(422)
            ->assertJsonPath('error', fn ($message) => str_contains($message, $blocked->code) && ! str_contains($message, $free->code))
            ->assertJsonCount(1, 'dossier_lines');

        // Rien n'est parti, même la ligne libre : le BL est refusé en entier.
        $this->assertSame(0, DeliveryLines::whereIn('order_line_id', [$free->id, $blocked->id])->count());
    }

    public function test_a_validated_dossier_lets_the_delivery_go(): void
    {
        $order = $this->order();
        $line  = $this->validated($this->requiring($this->line($order)));

        $this->postJson(route('orders.lines.json.store-delivery', $order->id), ['line_ids' => [$line->id]])
            ->assertOk()->assertJsonStructure(['redirect']);

        $this->assertSame(1, DeliveryLines::where('order_line_id', $line->id)->count());
    }

    public function test_a_line_without_requirement_is_not_concerned(): void
    {
        $line = $this->line();

        $this->postJson(route('deliverys-request.store'), $this->deliveryRequestPayload($line))
            ->assertOk()->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('deliverys', ['code' => 'BL-DOSSIER-1']);
    }

    public function test_the_delivery_request_screen_flags_the_lines_awaiting_their_dossier(): void
    {
        $pending   = $this->requiring($this->line());
        $validated = $this->validated($this->requiring($this->line()));
        $free      = $this->line();

        $rows = collect($this->getJson(route('deliverys-request.company-data', ['company_id' => $this->customer->id]))
            ->assertOk()->json('lines'))->keyBy('id');

        $this->assertTrue($rows[$pending->id]['dossier_pending']);
        $this->assertFalse($rows[$validated->id]['dossier_pending']);
        $this->assertFalse($rows[$free->id]['dossier_pending']);
    }

    // ------------------------------------------------------------------
    // Factures
    // ------------------------------------------------------------------

    public function test_the_invoice_request_screen_flags_the_lines_awaiting_their_dossier(): void
    {
        $pending = $this->line();
        $pendingDelivery = $this->deliveryLine($pending);
        $this->requiring($pending);
        $freeDelivery = $this->deliveryLine($this->line());

        $rows = collect($this->getJson(route('invoices-request.lines', ['company_id' => $this->customer->id]))
            ->assertOk()->json('lines'))->keyBy('id');

        $this->assertTrue($rows[$pendingDelivery->id]['dossier_pending']);
        $this->assertFalse($rows[$freeDelivery->id]['dossier_pending']);
    }

    public function test_direct_invoicing_refuses_and_creates_neither_invoice_nor_delivery(): void
    {
        $order = $this->order();
        $line  = $this->requiring($this->line($order));
        $invoices   = Invoices::count();
        $deliveries = Deliverys::count();

        $this->postJson(route('orders.lines.json.store-invoice', $order->id), ['line_ids' => [$line->id]])
            ->assertStatus(422)
            ->assertJsonPath('dossier_lines.0.id', $line->id);

        $this->assertSame($invoices, Invoices::count());
        $this->assertSame($deliveries, Deliverys::count());
        $this->assertEquals(4, (float) $line->fresh()->invoiced_remaining_qty);
    }

    public function test_direct_invoicing_passes_once_the_dossier_is_validated(): void
    {
        $order = $this->order();
        $line  = $this->validated($this->requiring($this->line($order)));

        $this->postJson(route('orders.lines.json.store-invoice', $order->id), ['line_ids' => [$line->id]])
            ->assertOk()->assertJsonStructure(['redirect']);

        $this->assertSame(1, InvoiceLines::where('order_line_id', $line->id)->count());
    }

    public function test_invoice_from_delivery_lines_is_refused(): void
    {
        // BL émis avant que le dossier ne soit exigé : la facture reste bloquée.
        $line         = $this->line();
        $deliveryLine = $this->deliveryLine($line);
        $this->requiring($line);

        $this->postJson(route('invoices-request.store'), $this->invoiceRequestPayload($deliveryLine))
            ->assertStatus(422)
            ->assertJsonPath('errors.lines.0', fn ($message) => str_contains($message, $line->code));

        $this->assertDatabaseMissing('invoices', ['code' => 'FA-DOSSIER-1']);
        $this->assertSame(1, (int) $deliveryLine->fresh()->invoice_status);
    }

    public function test_invoice_from_delivery_lines_without_requirement_is_created(): void
    {
        $deliveryLine = $this->deliveryLine($this->line());

        $this->postJson(route('invoices-request.store'), $this->invoiceRequestPayload($deliveryLine))->assertOk();

        $this->assertDatabaseHas('invoices', ['code' => 'FA-DOSSIER-1']);
    }

    public function test_generating_all_invoices_of_a_company_is_refused(): void
    {
        $free         = $this->deliveryLine($this->line());
        $blockedLine  = $this->line();
        $this->deliveryLine($blockedLine);
        $this->requiring($blockedLine);
        $invoices = Invoices::count();

        $this->postJson(route('invoices-request.generate-all'), [
            'companies_id' => $this->customer->id,
            'companies_addresses_id' => $free->OrderLine->order->companies_addresses_id,
            'companies_contacts_id' => $free->OrderLine->order->companies_contacts_id,
            'user_id' => $this->user->id,
        ])->assertStatus(422)->assertJsonPath('dossier_lines.0.id', $blockedLine->id);

        $this->assertSame($invoices, Invoices::count());
    }

    public function test_invoice_of_a_delivery_note_redirects_back_with_the_refusal(): void
    {
        $line         = $this->line();
        $deliveryLine = $this->deliveryLine($line);
        $this->requiring($line);
        $invoices = Invoices::count();

        $this->from(route('deliverys.show', $deliveryLine->deliverys_id))
            ->get(route('invoices.store.from.delivery', $deliveryLine->deliverys_id))
            ->assertRedirect(route('deliverys.show', $deliveryLine->deliverys_id))
            ->assertSessionHas('error', fn ($message) => str_contains($message, $line->code));

        $this->assertSame($invoices, Invoices::count());
    }

    public function test_converting_a_proforma_into_an_invoice_is_refused(): void
    {
        $line = $this->requiring($this->line());
        $proforma = Invoices::factory()->create(['statu' => 2, 'invoice_type' => 3, 'companies_id' => $this->customer->id]);
        InvoiceLines::create([
            'invoices_id' => $proforma->id, 'order_line_id' => $line->id, 'ordre' => 10, 'qty' => 4,
            'unit_price' => 100, 'discount' => 0, 'vat_rate' => 20, 'accounting_vats_id' => $this->vat->id, 'invoice_status' => 1,
        ]);

        $this->postJson(route('proformas.convert', $proforma->id))
            ->assertStatus(422)
            ->assertJsonPath('dossier_lines.0.id', $line->id);

        $this->assertSame(3, (int) $proforma->fresh()->invoice_type);
        $this->assertEquals(0, (float) $line->fresh()->invoiced_qty);
    }

    // ------------------------------------------------------------------
    // Exigence
    // ------------------------------------------------------------------

    public function test_a_new_line_inherits_the_requirement_of_the_affair_category(): void
    {
        $category = AffairCategory::create(['code' => 'DEBIT', 'label' => 'Débitmétrie', 'requires_manufacturing_dossier' => true]);
        $plain    = AffairCategory::create(['code' => 'MECA', 'label' => 'Mécanique']);

        $this->assertTrue($this->line($this->order(['affair_category_id' => $category->id]))->fresh()->dossier_required);
        $this->assertFalse($this->line($this->order(['affair_category_id' => $plain->id]))->fresh()->dossier_required);
        $this->assertFalse($this->line($this->order())->fresh()->dossier_required);
        $this->assertFalse($this->line($this->order(['affair_category_id' => $category->id]), ['line_type' => 'section'])->fresh()->dossier_required);
    }

    public function test_a_copied_line_keeps_the_requirement_but_never_the_validation(): void
    {
        $line = $this->validated($this->requiring($this->line()));

        $copy = $line->fresh()->replicate();
        $copy->save();

        $this->assertTrue($copy->fresh()->dossier_required);
        $this->assertNull($copy->fresh()->dossier_validated_at);
        $this->assertNull($copy->fresh()->dossier_validated_by);
    }

    public function test_siteflow_validation_is_recorded_with_its_source(): void
    {
        $line = app(ManufacturingDossierGate::class)->validate($this->requiring($this->line()), null, ManufacturingDossierGate::SOURCE_SITEFLOW);

        $this->assertSame('siteflow', $line->fresh()->dossier_source);
        $this->assertNull($line->fresh()->dossier_validated_by);
        $this->assertFalse(app(ManufacturingDossierGate::class)->isBlocking($line->fresh()));
    }

    public function test_the_validation_cannot_be_cancelled_once_the_line_has_shipped(): void
    {
        $line = $this->validated($this->requiring($this->line(null, ['delivered_qty' => 1, 'delivered_remaining_qty' => 3])));

        $this->expectException(ManufacturingDossierException::class);
        app(ManufacturingDossierGate::class)->revoke($line);
    }

    // ------------------------------------------------------------------
    // Action groupée de la fiche commande
    // ------------------------------------------------------------------

    public function test_the_bulk_action_requires_the_permission(): void
    {
        $order = $this->order();
        $line  = $this->requiring($this->line($order));

        $this->postJson(route('orders.lines.json.dossier', $order->id), ['line_ids' => [$line->id], 'action' => 'validate'])
            ->assertForbidden();

        $this->assertNull($line->fresh()->dossier_validated_at);
    }

    public function test_the_bulk_action_validates_requires_and_waives(): void
    {
        $this->user->givePermissionTo(ManufacturingDossierGate::PERMISSION);
        $order   = $this->order();
        $line    = $this->requiring($this->line($order));
        $other   = $this->line($order);
        $foreign = $this->requiring($this->line()); // autre commande : ignorée

        $this->postJson(route('orders.lines.json.dossier', $order->id), ['line_ids' => [$line->id, $foreign->id], 'action' => 'validate'])
            ->assertOk()
            ->assertJsonPath('done', [$line->id])
            ->assertJsonPath("lines.{$line->id}.validated_by", $this->user->name);

        $fresh = $line->fresh();
        $this->assertNotNull($fresh->dossier_validated_at);
        $this->assertSame($this->user->id, (int) $fresh->dossier_validated_by);
        $this->assertSame('manual', $fresh->dossier_source);
        $this->assertNull($foreign->fresh()->dossier_validated_at);

        $this->postJson(route('orders.lines.json.dossier', $order->id), ['line_ids' => [$other->id], 'action' => 'require'])->assertOk();
        $this->assertTrue($other->fresh()->dossier_required);

        $this->postJson(route('orders.lines.json.dossier', $order->id), ['line_ids' => [$other->id], 'action' => 'waive'])->assertOk();
        $this->assertFalse($other->fresh()->dossier_required);
    }

    public function test_the_bulk_action_reports_refusals_line_by_line(): void
    {
        $this->user->givePermissionTo(ManufacturingDossierGate::PERMISSION);
        $order   = $this->order();
        $shipped = $this->validated($this->requiring($this->line($order, ['delivered_qty' => 4, 'delivered_remaining_qty' => 0])));
        $open    = $this->validated($this->requiring($this->line($order)));

        $this->postJson(route('orders.lines.json.dossier', $order->id), ['line_ids' => [$shipped->id, $open->id], 'action' => 'revoke'])
            ->assertOk()
            ->assertJsonPath('done', [$open->id])
            ->assertJsonPath('refused.0.id', $shipped->id)
            ->assertJsonPath('refused.0.message', __('manufacturing_dossier.already_shipped'));

        $this->assertNotNull($shipped->fresh()->dossier_validated_at);
        $this->assertNull($open->fresh()->dossier_validated_at);
    }

    public function test_the_order_page_lists_the_dossier_state_of_each_line(): void
    {
        $order = $this->order();
        $line  = $this->validated($this->requiring($this->line($order)));

        $row = collect($this->getJson(route('orders.lines.json.for-order', $order->id))->assertOk()->json('lines') ?? [])
            ->firstWhere('id', $line->id);

        $this->assertNotNull($row, 'ligne absente de la liste');
        $this->assertTrue($row['dossier']['required']);
        $this->assertSame($this->user->name, $row['dossier']['validated_by']);
    }
}
