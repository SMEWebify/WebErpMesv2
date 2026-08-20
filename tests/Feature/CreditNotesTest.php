<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\CreditNoteLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreditNotesTest extends TestCase
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
        $response = $this->get(route('credit-notes'));
        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_show_page_loads(): void
    {
        $creditNote = CreditNotes::factory()->create();
        $response = $this->get(route('credit.notes.show', ['id' => $creditNote->id]));
        $response->assertStatus(200);
    }

    public function test_json_list_returns_paginated_data(): void
    {
        CreditNotes::factory()->count(3)->create();

        $response = $this->getJson(route('credit-notes.json.list'));

        $response->assertOk()->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_create_credit_note_from_invoice_lines(): void
    {
        $order = Orders::factory()->create();
        $orderLine = OrderLines::factory()->create([
            'orders_id'              => $order->id,
            'qty'                    => 5,
            'invoiced_qty'           => 5,
            'invoiced_remaining_qty' => 0,
            'invoice_status'         => 4,
        ]);
        $invoice = Invoices::factory()->create([
            'companies_id'           => $order->companies_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id,
            'order_id'               => $order->id,
        ]);
        $invoiceLine = InvoiceLines::create([
            'invoices_id'    => $invoice->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 5,
            'invoice_status' => 4,
        ]);

        $response = $this->post(route('credit-notes.store.from.invoice'), [
            'selected_invoice_lines' => [$invoiceLine->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_notes', [
            'invoices_id' => $invoice->id,
            'companies_id' => $invoice->companies_id,
        ]);
        $this->assertDatabaseHas('credit_note_lines', [
            'order_line_id'   => $orderLine->id,
            'invoice_line_id' => $invoiceLine->id,
            'qty'             => 5,
        ]);
    }

    public function test_can_create_credit_note_from_a_free_invoice_line(): void
    {
        $invoice = Invoices::factory()->create(['statu' => 2]);
        // Ligne libre : aucune ligne de commande à rembobiner derrière.
        $invoiceLine = InvoiceLines::create([
            'invoices_id'    => $invoice->id,
            'label'          => 'Frais de port',
            'ordre'          => 10,
            'qty'            => 1,
            'unit_price'     => 80,
            'discount'       => 0,
            'vat_rate'       => 20,
            'invoice_status' => 4,
        ]);

        $response = $this->post(route('credit-notes.store.from.invoice'), [
            'selected_invoice_lines' => [$invoiceLine->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_note_lines', [
            'order_line_id'   => null,
            'invoice_line_id' => $invoiceLine->id,
            'label'           => 'Frais de port',
            'unit_price'      => 80,
            'vat_rate'        => 20,
        ]);

        $creditNote = CreditNotes::where('invoices_id', $invoice->id)->firstOrFail();
        $calculator = new \App\Services\CreditNoteCalculatorService($creditNote);
        $this->assertEquals(80.0, round($calculator->getSubTotal(), 2));
        $this->assertEquals(96.0, round($calculator->getTotalPrice(), 2));
    }

    public function test_credit_note_reverses_order_line_invoiced_qty(): void
    {
        $order = Orders::factory()->create();
        $orderLine = OrderLines::factory()->create([
            'orders_id'              => $order->id,
            'qty'                    => 10,
            'invoiced_qty'           => 10,
            'invoiced_remaining_qty' => 0,
            'invoice_status'         => 4,
        ]);
        $invoice = Invoices::factory()->create([
            'companies_id'           => $order->companies_id,
            'companies_contacts_id'  => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id,
        ]);
        $invoiceLine = InvoiceLines::create([
            'invoices_id'    => $invoice->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 10,
            'invoice_status' => 4,
        ]);

        $this->post(route('credit-notes.store.from.invoice'), [
            'selected_invoice_lines' => [$invoiceLine->id],
        ]);

        $this->assertDatabaseHas('order_lines', [
            'id'                     => $orderLine->id,
            'invoiced_qty'           => 0,
            'invoiced_remaining_qty' => 10,
            'invoice_status'         => 1,
        ]);
    }

    public function test_credit_note_requires_invoice_lines(): void
    {
        $response = $this->post(route('credit-notes.store.from.invoice'), [
            'selected_invoice_lines' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('credit_notes', 0);
    }

    public function test_can_update_credit_note(): void
    {
        $creditNote = CreditNotes::factory()->create(['statu' => 1]);

        $response = $this->post(route('credit.notes.update', ['id' => $creditNote->id]), [
            'id'     => $creditNote->id,
            'label'  => 'Updated Credit Note',
            'statu'  => 2,
            'reason' => 'Produit défectueux',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_notes', [
            'id'     => $creditNote->id,
            'label'  => 'Updated Credit Note',
            'statu'  => 2,
            'reason' => 'Produit défectueux',
        ]);
    }
}
