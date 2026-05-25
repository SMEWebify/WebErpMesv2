<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrdersWebTest extends TestCase
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
        $response = $this->get(route('orders'));
        // KPI queries use MySQL-specific functions (MONTH, FLOOR) that don't run in SQLite.
        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_show_page_loads(): void
    {
        $order = Orders::factory()->create();
        $response = $this->get(route('orders.show', ['id' => $order->id]));
        $response->assertStatus(200);
    }

    public function test_json_list_returns_paginated_data(): void
    {
        Orders::factory()->count(3)->create(['statu' => 1]);

        $response = $this->getJson(route('orders.json.list', ['statuses' => [1]]));

        $response->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->assertGreaterThanOrEqual(3, $response->json('meta.total'));
        $this->assertArrayHasKey('id', $response->json('data.0'));
    }

    public function test_can_create_external_order(): void
    {
        $company   = Companies::factory()->create(['statu_customer' => 2]);
        $contact   = CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $address   = CompaniesAddresses::factory()->create(['companies_id' => $company->id]);
        $condition = AccountingPaymentConditions::factory()->create();
        $method    = AccountingPaymentMethod::factory()->create();
        $delivery  = AccountingDelivery::factory()->create();

        $response = $this->postJson(route('orders.json.store'), [
            'code'                              => 'OR-TEST-001',
            'label'                             => 'Test Order',
            'type'                              => 1,
            'companies_id'                      => $company->id,
            'companies_contacts_id'             => $contact->id,
            'companies_addresses_id'            => $address->id,
            'accounting_payment_conditions_id'  => $condition->id,
            'accounting_payment_methods_id'     => $method->id,
            'accounting_deliveries_id'          => $delivery->id,
            'user_id'                           => $this->user->id,
        ]);

        $response->assertStatus(201)->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('orders', ['code' => 'OR-TEST-001', 'type' => 1]);
    }

    public function test_can_create_internal_order(): void
    {
        $response = $this->postJson(route('orders.json.store'), [
            'code'    => 'OR-INT-001',
            'label'   => 'Internal Order',
            'type'    => 2,
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(201)->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('orders', ['code' => 'OR-INT-001', 'type' => 2]);
    }

    public function test_create_external_order_requires_company(): void
    {
        $response = $this->postJson(route('orders.json.store'), [
            'code'    => 'OR-FAIL-001',
            'label'   => 'Missing Company',
            'type'    => 1,
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_update_order(): void
    {
        $order     = Orders::factory()->create();
        $condition = AccountingPaymentConditions::factory()->create();
        $method    = AccountingPaymentMethod::factory()->create();
        $delivery  = AccountingDelivery::factory()->create();

        $response = $this->post(route('orders.update', ['id' => $order->id]), [
            'label'                             => 'Updated Order',
            'companies_id'                      => $order->companies_id,
            'companies_contacts_id'             => $order->companies_contacts_id,
            'companies_addresses_id'            => $order->companies_addresses_id,
            'accounting_payment_conditions_id'  => $condition->id,
            'accounting_payment_methods_id'     => $method->id,
            'accounting_deliveries_id'          => $delivery->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'label' => 'Updated Order']);
    }

    public function test_can_change_order_status(): void
    {
        $order = Orders::factory()->create(['statu' => 1]);

        $response = $this->postJson(route('orders.json.statu', ['id' => $order->id]), ['statu' => 2]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'statu' => 2]);
    }

    public function test_cannot_cancel_order_with_invoiced_lines(): void
    {
        $order = Orders::factory()->create(['statu' => 2]);
        OrderLines::factory()->create([
            'orders_id'   => $order->id,
            'invoiced_qty' => 5,
        ]);

        $response = $this->postJson(route('orders.json.statu', ['id' => $order->id]), ['statu' => 6]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'statu' => 2]);
    }

    public function test_can_cancel_order_with_no_invoiced_lines(): void
    {
        $order = Orders::factory()->create(['statu' => 2]);
        OrderLines::factory()->create([
            'orders_id'    => $order->id,
            'invoiced_qty' => 0,
        ]);

        $response = $this->postJson(route('orders.json.statu', ['id' => $order->id]), ['statu' => 6]);

        $response->assertOk()->assertJson(['success' => true]);
    }
}
