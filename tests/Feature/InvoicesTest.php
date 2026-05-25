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
