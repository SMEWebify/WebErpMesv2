<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeliveriesTest extends TestCase
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
        $response = $this->get(route('deliverys'));
        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_show_page_loads(): void
    {
        $delivery = Deliverys::factory()->create();
        $response = $this->get(route('deliverys.show', ['id' => $delivery->id]));
        $response->assertStatus(200);
    }

    public function test_json_list_returns_paginated_data(): void
    {
        Deliverys::factory()->count(3)->create();

        $response = $this->getJson(route('deliverys.json.list'));

        $response->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->assertGreaterThanOrEqual(3, $response->json('meta.total'));
    }

    public function test_can_create_delivery_note(): void
    {
        $order = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id'               => $order->id,
            'qty'                     => 10,
            'delivered_qty'           => 0,
            'delivered_remaining_qty' => 10,
        ]);

        $response = $this->postJson(route('deliverys-request.store'), [
            'code'                    => 'BL-TEST-001',
            'label'                   => 'Test Delivery',
            'companies_id'            => $order->companies_id,
            'companies_addresses_id'  => $order->companies_addresses_id,
            'companies_contacts_id'   => $order->companies_contacts_id,
            'user_id'                 => $this->user->id,
            'lines'                   => [
                ['order_line_id' => $orderLine->id, 'qty' => 5],
            ],
        ]);

        $response->assertOk()->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('deliverys', ['code' => 'BL-TEST-001']);
        $this->assertDatabaseHas('delivery_lines', [
            'order_line_id' => $orderLine->id,
            'qty'           => 5,
        ]);
    }

    public function test_create_delivery_requires_at_least_one_line(): void
    {
        $order = Orders::factory()->create();

        $response = $this->postJson(route('deliverys-request.store'), [
            'code'                    => 'BL-FAIL-001',
            'label'                   => 'No Lines',
            'companies_id'            => $order->companies_id,
            'companies_addresses_id'  => $order->companies_addresses_id,
            'companies_contacts_id'   => $order->companies_contacts_id,
            'user_id'                 => $this->user->id,
            'lines'                   => [],
        ]);

        $response->assertStatus(422);
    }

    public function test_creating_delivery_updates_order_line_delivered_qty(): void
    {
        $order = Orders::factory()->create(['statu' => 1]);
        $orderLine = OrderLines::factory()->create([
            'orders_id'               => $order->id,
            'qty'                     => 10,
            'delivered_qty'           => 0,
            'delivered_remaining_qty' => 10,
        ]);

        $this->postJson(route('deliverys-request.store'), [
            'code'                    => 'BL-QTY-001',
            'label'                   => 'Qty Update Test',
            'companies_id'            => $order->companies_id,
            'companies_addresses_id'  => $order->companies_addresses_id,
            'companies_contacts_id'   => $order->companies_contacts_id,
            'user_id'                 => $this->user->id,
            'lines'                   => [
                ['order_line_id' => $orderLine->id, 'qty' => 6],
            ],
        ]);

        $this->assertDatabaseHas('order_lines', [
            'id'                      => $orderLine->id,
            'delivered_qty'           => 6,
            'delivered_remaining_qty' => 4,
        ]);
    }

    public function test_can_update_delivery(): void
    {
        $delivery = Deliverys::factory()->create();

        $response = $this->post(route('deliverys.update', ['id' => $delivery->id]), [
            'id'                      => $delivery->id,
            'label'                   => 'Updated Delivery',
            'tracking_number'         => 'TRACK-123',
            'comment'                 => 'Updated comment',
            'companies_id'            => $delivery->companies_id,
            'companies_contacts_id'   => $delivery->companies_contacts_id,
            'companies_addresses_id'  => $delivery->companies_addresses_id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deliverys', [
            'id'              => $delivery->id,
            'label'           => 'Updated Delivery',
            'tracking_number' => 'TRACK-123',
        ]);
    }

    public function test_can_change_delivery_status(): void
    {
        $delivery = Deliverys::factory()->create(['statu' => 1]);

        $response = $this->postJson(route('deliverys.json.statu', ['id' => $delivery->id]), ['statu' => 2]);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('deliverys', ['id' => $delivery->id, 'statu' => 2]);
    }

    public function test_can_mark_delivery_line_not_chargeable(): void
    {
        $delivery     = Deliverys::factory()->create();
        $order        = Orders::factory()->create();
        $orderLine    = OrderLines::factory()->create(['orders_id' => $order->id]);
        $deliveryLine = DeliveryLines::create([
            'deliverys_id'   => $delivery->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 2,
            'invoice_status' => 1,
        ]);

        $response = $this->postJson(
            route('deliverys.line.not-chargeable', ['id' => $delivery->id, 'lineId' => $deliveryLine->id])
        );

        $response->assertOk();
        $this->assertDatabaseHas('delivery_lines', ['id' => $deliveryLine->id, 'invoice_status' => 2]);
    }
}
