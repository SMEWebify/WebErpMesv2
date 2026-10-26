<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\InvoicePayment;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsUnits;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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
    }

    public function test_index_page_loads(): void
    {
        $response = $this->get(route('invoices'));
        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_show_page_loads(): void
    {
        $invoice = Invoices::factory()->create();
        $response = $this->get(route('invoices.show', ['id' => $invoice->id]));
        $response->assertStatus(200);
    }

    public function test_json_list_returns_paginated_data(): void
    {
        Invoices::factory()->count(3)->create();

        $response = $this->getJson(route('invoices.json.list'));

        $response->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->assertGreaterThanOrEqual(3, $response->json('meta.total'));
    }

    public function test_can_create_invoice_from_delivery_lines(): void
    {
        $vat      = AccountingVat::factory()->create();
        $order    = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id'                => $order->id,
            'qty'                      => 5,
            'delivered_qty'            => 5,
            'delivered_remaining_qty'  => 0,
            'invoiced_qty'             => 0,
            'invoiced_remaining_qty'   => 5,
            'accounting_vats_id'       => $vat->id,
        ]);
        $delivery = Deliverys::factory()->create([
            'companies_id'           => $order->companies_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'order_id'               => $order->id,
        ]);
        $deliveryLine = DeliveryLines::create([
            'deliverys_id'   => $delivery->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 5,
            'invoice_status' => 1,
        ]);

        $response = $this->postJson(route('invoices-request.store'), [
            'code'                    => 'FA-TEST-001',
            'label'                   => 'Test Invoice',
            'companies_id'            => $order->companies_id,
            'companies_addresses_id'  => $order->companies_addresses_id,
            'companies_contacts_id'   => $order->companies_contacts_id,
            'user_id'                 => $this->user->id,
            'lines'                   => [$deliveryLine->id],
        ]);

        $response->assertOk()->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('invoices', ['code' => 'FA-TEST-001']);
        $this->assertDatabaseHas('invoice_lines', [
            'order_line_id'   => $orderLine->id,
            'delivery_line_id' => $deliveryLine->id,
            'qty'             => 5,
        ]);
    }

    public function test_an_invoice_from_delivery_lines_books_its_sale_entry(): void
    {
        // Régression du 20/05/2026 : la facture créée sans invoice_type sautait l'écriture VENT.
        $vat = AccountingVat::factory()->create(['rate' => 20]);
        \App\Models\Accounting\AccountingAllocation::create([
            'account' => '701000', 'label' => 'Ventes', 'accounting_vats_id' => $vat->id,
            'vat_account' => '445710', 'code_account' => '701000', 'type_imputation' => 1,
        ]);
        $order     = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id' => $order->id, 'qty' => 2, 'selling_price' => 50, 'discount' => 0,
            'delivered_qty' => 2, 'delivered_remaining_qty' => 0,
            'invoiced_qty' => 0, 'invoiced_remaining_qty' => 2, 'accounting_vats_id' => $vat->id,
        ]);
        $delivery = Deliverys::factory()->create([
            'companies_id' => $order->companies_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'order_id' => $order->id,
        ]);
        $deliveryLine = DeliveryLines::create([
            'deliverys_id' => $delivery->id, 'order_line_id' => $orderLine->id,
            'ordre' => 10, 'qty' => 2, 'invoice_status' => 1,
        ]);

        $this->postJson(route('invoices-request.store'), [
            'code' => 'FA-TEST-VENT', 'label' => 'Test', 'companies_id' => $order->companies_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'companies_contacts_id' => $order->companies_contacts_id,
            'user_id' => $this->user->id, 'lines' => [$deliveryLine->id],
        ])->assertOk();

        $line = InvoiceLines::where('delivery_line_id', $deliveryLine->id)->firstOrFail();
        $this->assertEquals(1, (int) $line->invoice->invoice_type);
        // 2 × 50 € HT + 20 % = 120 € TTC au débit du 411.
        $this->assertEquals(120.0, (float) \App\Models\Accounting\AccountingEntry::where('invoice_line_id', $line->id)->sum('debit_amount'));
    }

    public function test_a_converted_proforma_books_its_sale_entry(): void
    {
        $vat = AccountingVat::factory()->create(['rate' => 20]);
        $allocation = \App\Models\Accounting\AccountingAllocation::create([
            'account' => '701000', 'label' => 'Ventes', 'accounting_vats_id' => $vat->id,
            'vat_account' => '445710', 'code_account' => '701000', 'type_imputation' => 1,
        ]);
        $order     = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id' => $order->id, 'qty' => 1, 'selling_price' => 100, 'discount' => 0,
            'invoiced_qty' => 0, 'invoiced_remaining_qty' => 1, 'accounting_vats_id' => $vat->id,
        ]);
        $proforma = Invoices::factory()->create(['statu' => 2, 'invoice_type' => 3]);
        $line = InvoiceLines::create([
            'invoices_id' => $proforma->id, 'order_line_id' => $orderLine->id, 'ordre' => 10, 'qty' => 1,
            'unit_price' => 100, 'discount' => 0, 'vat_rate' => 20, 'accounting_vats_id' => $vat->id,
            'accounting_allocation_id' => $allocation->id, 'invoice_status' => 1,
        ]);
        $entries = fn () => \App\Models\Accounting\AccountingEntry::where('invoice_line_id', $line->id);
        $this->assertEquals(0, $entries()->count());

        $this->postJson(route('proformas.convert', $proforma->id))->assertOk();

        $this->assertEquals(120.0, (float) $entries()->sum('debit_amount'));
    }

    public function test_can_create_invoice_from_delivery(): void
    {
        $vat      = AccountingVat::factory()->create();
        $order    = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id'          => $order->id,
            'accounting_vats_id' => $vat->id,
        ]);
        $delivery = Deliverys::factory()->create([
            'companies_id'           => $order->companies_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'order_id'               => $order->id,
        ]);
        DeliveryLines::create([
            'deliverys_id'   => $delivery->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 3,
            'invoice_status' => 1,
        ]);

        $response = $this->get(route('invoices.store.from.delivery', $delivery->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['companies_id' => $order->companies_id]);
        $this->assertDatabaseHas('invoice_lines', ['order_line_id' => $orderLine->id]);
    }

    public function test_can_update_invoice(): void
    {
        $invoice = Invoices::factory()->create();

        $response = $this->post(route('invoices.update', ['id' => $invoice->id]), [
            'id'                      => $invoice->id,
            'label'                   => 'Updated Invoice',
            'companies_id'            => $invoice->companies_id,
            'companies_contacts_id'   => $invoice->companies_contacts_id,
            'companies_addresses_id'  => $invoice->companies_addresses_id,
            'customer_reference'      => 'REF-001',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id'                 => $invoice->id,
            'label'              => 'Updated Invoice',
            'customer_reference' => 'REF-001',
        ]);
    }

    public function test_can_change_invoice_status(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 1]);

        $response = $this->postJson(route('invoices.json.statu', ['id' => $invoice->id]), ['statu' => 2]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'statu' => 2]);
    }

    public function test_an_issued_invoice_never_goes_back_to_draft(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);

        $this->postJson(route('invoices.json.statu', ['id' => $invoice->id]), ['statu' => 1])
            ->assertStatus(422)
            ->assertJsonStructure(['error']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'statu' => 2]);
    }

    public function test_a_draft_invoice_cannot_be_paid(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 1]);

        $this->postJson(route('invoices.payments.store', $invoice->id), [
            'amount'       => 100.00,
            'payment_date' => now()->toDateString(),
        ])->assertStatus(422)->assertJsonStructure(['message']);

        $this->assertDatabaseMissing('invoice_payments', ['invoice_id' => $invoice->id]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'statu' => 1]);
    }

    public function test_can_add_payment_to_invoice(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);

        $response = $this->postJson(route('invoices.payments.store', $invoice->id), [
            'amount'       => 100.00,
            'payment_date' => now()->toDateString(),
        ]);

        $response->assertOk()->assertJsonStructure(['ok', 'remaining']);
        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
            'amount'     => 100.00,
        ]);
    }

    public function test_can_delete_payment(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 5]);
        $payment = InvoicePayment::create([
            'invoice_id'   => $invoice->id,
            'amount'       => 50.00,
            'payment_date' => now()->toDateString(),
            'user_id'      => $this->user->id,
        ]);

        $response = $this->deleteJson(route('invoices.payments.destroy', [$invoice->id, $payment->id]));

        $response->assertOk();
        $this->assertDatabaseMissing('invoice_payments', ['id' => $payment->id]);
    }

    public function test_payment_date_follows_the_settling_payment(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);
        InvoiceLines::create([
            'invoices_id' => $invoice->id,
            'label'       => 'Prestation',
            'ordre'       => 10,
            'qty'         => 1,
            'unit_price'  => 100,
            'discount'    => 0,
            'vat_rate'    => 20,
        ]);

        $this->postJson(route('invoices.payments.store', $invoice->id), [
            'amount' => 50, 'payment_date' => '2026-10-01',
        ])->assertOk();
        $this->assertNull($invoice->fresh()->payment_date);

        $this->postJson(route('invoices.payments.store', $invoice->id), [
            'amount' => 70, 'payment_date' => '2026-10-10',
        ])->assertOk();
        $this->assertSame('2026-10-10', \Illuminate\Support\Carbon::parse($invoice->fresh()->payment_date)->toDateString());

        $last = InvoicePayment::where('invoice_id', $invoice->id)->latest('id')->first();
        $this->deleteJson(route('invoices.payments.destroy', [$invoice->id, $last->id]))->assertOk();
        $this->assertNull($invoice->fresh()->payment_date);
    }

    public function test_deleting_a_payment_removes_its_unexported_entry(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);
        $this->postJson(route('invoices.payments.store', $invoice->id), [
            'amount' => 120, 'payment_date' => '2026-10-01', 'reference' => 'VIR 1',
        ])->assertOk();
        $payment = InvoicePayment::where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(2, \App\Models\Accounting\AccountingEntry::where('invoice_payment_id', $payment->id)->count());

        $this->deleteJson(route('invoices.payments.destroy', [$invoice->id, $payment->id]))->assertOk();

        $this->assertSame(0, \App\Models\Accounting\AccountingEntry::where('journal_code', 'ENCAIS')
            ->where('document_reference', $invoice->code)->count());
    }

    public function test_deleting_a_payment_already_exported_reverses_its_entry(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);
        $this->postJson(route('invoices.payments.store', $invoice->id), [
            'amount' => 120, 'payment_date' => '2026-10-01',
        ])->assertOk();
        $payment = InvoicePayment::where('invoice_id', $invoice->id)->firstOrFail();
        \App\Models\Accounting\AccountingEntry::where('invoice_payment_id', $payment->id)->update(['exported' => true]);

        $this->deleteJson(route('invoices.payments.destroy', [$invoice->id, $payment->id]))->assertOk();

        $entries = \App\Models\Accounting\AccountingEntry::where('journal_code', 'ENCAIS')
            ->where('document_reference', $invoice->code)->get();
        // Les deux lignes exportées restent, deux lignes inverses les annulent.
        $this->assertCount(4, $entries);
        $this->assertEquals($entries->sum('debit_amount'), $entries->sum('credit_amount'));
        $this->assertEquals(240.0, (float) $entries->sum('debit_amount'));
    }

    public function test_editing_a_draft_line_updates_its_sale_entry(): void
    {
        $vat = AccountingVat::factory()->create(['rate' => 20]);
        \App\Models\Accounting\AccountingAllocation::create([
            'account' => '701000', 'label' => 'Ventes', 'accounting_vats_id' => $vat->id,
            'vat_account' => '445710', 'code_account' => '701000', 'type_imputation' => 1,
        ]);
        MethodsUnits::factory()->create();
        $invoice = Invoices::factory()->create(['statu' => 1, 'invoice_type' => 1]);

        $lineId = $this->postJson(route('invoices.lines.store', $invoice->id), [
            'label' => 'Frais de port', 'qty' => 1, 'unit_price' => 80, 'accounting_vats_id' => $vat->id,
        ])->assertCreated()->json('line.id');
        $debit = fn () => (float) \App\Models\Accounting\AccountingEntry::where('invoice_line_id', $lineId)->sum('debit_amount');
        $this->assertEquals(96.0, $debit());

        $this->patchJson(route('invoices.lines.update', [$invoice->id, $lineId]), ['qty' => 2])->assertOk();

        // Une seule écriture, au nouveau montant (2 × 80 € HT + 20 %).
        $this->assertEquals(192.0, $debit());
    }

    public function test_the_payments_tab_deducts_credit_notes_from_the_balance(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2, 'invoice_type' => 1]);
        $invoiceLine = InvoiceLines::create([
            'invoices_id' => $invoice->id, 'ordre' => 10, 'label' => 'Platine', 'qty' => 1,
            'unit_price' => 100, 'discount' => 0, 'vat_rate' => 20, 'invoice_status' => 2,
        ]);
        $creditNote = \App\Models\Workflow\CreditNotes::factory()->create(['invoices_id' => $invoice->id, 'statu' => 2]);
        \App\Models\Workflow\CreditNoteLines::create([
            'credit_note_id' => $creditNote->id, 'invoice_line_id' => $invoiceLine->id, 'label' => 'Remise', 'qty' => 1,
            'unit_price' => 50, 'discount' => 0, 'vat_rate' => 20,
        ]);

        // 120 € TTC facturés, 60 € TTC d'avoir : il reste 60 €, pas 120.
        $this->getJson(route('invoices.payments.index', $invoice->id))
            ->assertOk()
            ->assertJsonPath('credited', 60)
            ->assertJsonPath('remaining', 60);
    }

    public function test_can_add_free_line_to_draft_invoice(): void
    {
        $vat     = AccountingVat::factory()->create(['rate' => 20]);
        $unit    = MethodsUnits::factory()->create();
        $invoice = Invoices::factory()->create(['statu' => 1]);

        $response = $this->postJson(route('invoices.lines.store', $invoice->id), [
            'label'              => 'Frais de port',
            'code'               => 'PORT',
            'qty'                => 1,
            'unit_price'         => 80,
            'discount'           => 0,
            'methods_units_id'   => $unit->id,
            'accounting_vats_id' => $vat->id,
        ]);

        $response->assertStatus(201)->assertJsonPath('line.is_free_line', true);

        $this->assertDatabaseHas('invoice_lines', [
            'invoices_id'   => $invoice->id,
            'order_line_id' => null,
            'label'         => 'Frais de port',
            'unit_price'    => 80,
            'vat_rate'      => 20,
        ]);

        // La ligne libre entre bien dans les totaux de la facture.
        $calculator = new \App\Services\InvoiceCalculatorService($invoice->fresh());
        $this->assertEquals(80.0, round($calculator->getSubTotal(), 2));
        $this->assertEquals(96.0, round($calculator->getTotalPrice(), 2));
    }

    public function test_cannot_add_free_line_to_emitted_invoice(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);

        $response = $this->postJson(route('invoices.lines.store', $invoice->id), [
            'label'      => 'Frais de port',
            'qty'        => 1,
            'unit_price' => 80,
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('invoice_lines', ['invoices_id' => $invoice->id]);
    }

    public function test_add_free_line_requires_label_and_price(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 1]);

        $this->postJson(route('invoices.lines.store', $invoice->id), ['qty' => 1])
             ->assertStatus(422);
    }

    public function test_can_delete_free_line_from_draft_invoice(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 1]);
        $line    = InvoiceLines::create([
            'invoices_id' => $invoice->id,
            'label'       => 'Frais de port',
            'ordre'       => 10,
            'qty'         => 1,
            'unit_price'  => 80,
            'discount'    => 0,
            'vat_rate'    => 20,
        ]);

        $this->deleteJson(route('invoices.lines.destroy', [$invoice->id, $line->id]))
             ->assertOk();

        $this->assertSoftDeleted('invoice_lines', ['id' => $line->id]);
    }

    public function test_deleting_order_line_restores_invoiced_qty(): void
    {
        $vat       = AccountingVat::factory()->create();
        $order     = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id'               => $order->id,
            'qty'                     => 5,
            'invoiced_qty'            => 5,
            'invoiced_remaining_qty'  => 0,
            'invoice_status'          => 3,
            'accounting_vats_id'      => $vat->id,
        ]);
        $invoice = Invoices::factory()->create(['statu' => 1]);
        $line    = InvoiceLines::create([
            'invoices_id'   => $invoice->id,
            'order_line_id' => $orderLine->id,
            'ordre'         => 10,
            'qty'           => 5,
            'unit_price'    => 10,
            'discount'      => 0,
            'vat_rate'      => 20,
        ]);

        $this->deleteJson(route('invoices.lines.destroy', [$invoice->id, $line->id]))
             ->assertOk();

        $orderLine->refresh();
        $this->assertEquals(0, $orderLine->invoiced_qty);
        $this->assertEquals(5, $orderLine->invoiced_remaining_qty);
        $this->assertEquals(1, $orderLine->invoice_status);
    }

    public function test_deleting_line_reopens_the_delivery_line(): void
    {
        $vat       = AccountingVat::factory()->create();
        $order     = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id'               => $order->id,
            'qty'                     => 4,
            'invoiced_qty'            => 4,
            'invoiced_remaining_qty'  => 0,
            'invoice_status'          => 3,
            'accounting_vats_id'      => $vat->id,
        ]);
        $delivery = Deliverys::factory()->create([
            'companies_id'           => $order->companies_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'order_id'               => $order->id,
        ]);
        $deliveryLine = DeliveryLines::create([
            'deliverys_id'   => $delivery->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 4,
            'invoice_status' => 4, // facturée
        ]);
        $invoice = Invoices::factory()->create(['statu' => 1]);
        $line    = InvoiceLines::create([
            'invoices_id'      => $invoice->id,
            'order_line_id'    => $orderLine->id,
            'delivery_line_id' => $deliveryLine->id,
            'ordre'            => 10,
            'qty'              => 4,
            'unit_price'       => 10,
            'discount'         => 0,
            'vat_rate'         => 20,
        ]);

        $this->deleteJson(route('invoices.lines.destroy', [$invoice->id, $line->id]))
             ->assertOk();

        $deliveryLine->refresh();
        $orderLine->refresh();

        $this->assertEquals(1, $deliveryLine->invoice_status);  // redevient facturable
        $this->assertEquals(0, $orderLine->invoiced_qty);
        $this->assertEquals(4, $orderLine->invoiced_remaining_qty);
        $this->assertEquals(1, $orderLine->invoice_status);
    }

    public function test_deleting_one_of_two_lines_keeps_the_delivery_line_invoiced(): void
    {
        $order     = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id'              => $order->id,
            'qty'                    => 10,
            'invoiced_qty'           => 10,
            'invoiced_remaining_qty' => 0,
            'invoice_status'         => 3,
        ]);
        $delivery = Deliverys::factory()->create([
            'companies_id'           => $order->companies_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'order_id'               => $order->id,
        ]);
        $deliveryLine = DeliveryLines::create([
            'deliverys_id'   => $delivery->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 10,
            'invoice_status' => 4,
        ]);

        $invoice = Invoices::factory()->create(['statu' => 1]);
        $common  = [
            'invoices_id'      => $invoice->id,
            'order_line_id'    => $orderLine->id,
            'delivery_line_id' => $deliveryLine->id,
            'unit_price'       => 10,
            'discount'         => 0,
            'vat_rate'         => 20,
        ];
        $lineA = InvoiceLines::create($common + ['ordre' => 10, 'qty' => 5]);
        InvoiceLines::create($common + ['ordre' => 20, 'qty' => 5]);

        $this->deleteJson(route('invoices.lines.destroy', [$invoice->id, $lineA->id]))
             ->assertOk();

        $deliveryLine->refresh();
        $orderLine->refresh();

        // Une seconde ligne facture toujours ce BL : il reste facturé.
        $this->assertEquals(4, $deliveryLine->invoice_status);
        // Seule la quantité de la ligne supprimée est rendue.
        $this->assertEquals(5, $orderLine->invoiced_qty);
        $this->assertEquals(5, $orderLine->invoiced_remaining_qty);
        $this->assertEquals(2, $orderLine->invoice_status); // partiellement facturée
    }

    public function test_invoice_pdf_renders_with_a_free_line(): void
    {
        $vat     = AccountingVat::factory()->create(['rate' => 20]);
        $unit    = MethodsUnits::factory()->create();
        $invoice = Invoices::factory()->create(['statu' => 2]);

        InvoiceLines::create([
            'invoices_id'        => $invoice->id,
            'label'              => 'Frais de port',
            'code'               => 'PORT',
            'methods_units_id'   => $unit->id,
            'ordre'              => 10,
            'qty'                => 1,
            'unit_price'         => 80,
            'discount'           => 0,
            'vat_rate'           => $vat->rate,
            'accounting_vats_id' => $vat->id,
        ]);

        $response = $this->get(route('pdf.invoice', ['Document' => $invoice->id]));

        $response->assertOk();
    }

    public function test_cannot_delete_line_from_emitted_invoice(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);
        $line    = InvoiceLines::create([
            'invoices_id' => $invoice->id,
            'label'       => 'Frais de port',
            'ordre'       => 10,
            'qty'         => 1,
            'unit_price'  => 80,
        ]);

        $this->deleteJson(route('invoices.lines.destroy', [$invoice->id, $line->id]))
             ->assertStatus(403);

        $this->assertDatabaseHas('invoice_lines', ['id' => $line->id, 'deleted_at' => null]);
    }

    public function test_create_invoice_requires_at_least_one_line(): void
    {
        $order = Orders::factory()->create();

        $response = $this->postJson(route('invoices-request.store'), [
            'code'                    => 'FA-FAIL-001',
            'label'                   => 'No Lines',
            'companies_id'            => $order->companies_id,
            'companies_addresses_id'  => $order->companies_addresses_id,
            'companies_contacts_id'   => $order->companies_contacts_id,
            'user_id'                 => $this->user->id,
            'lines'                   => [],
        ]);

        $response->assertStatus(422);
    }
}
