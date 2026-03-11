<?php

namespace Tests\Unit;

use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\OrderLinesService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderLinesServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_incoming_orders_excludes_canceled_orders(): void
    {
        Carbon::setTestNow('2026-03-10');

        $openOrder = Orders::factory()->create(['statu' => 1]);
        $canceledOrder = Orders::factory()->create(['statu' => 6]);

        OrderLines::factory()->create([
            'orders_id' => $openOrder->id,
            'delivery_date' => '2026-03-11',
            'delivery_status' => 1,
        ]);

        OrderLines::factory()->create([
            'orders_id' => $canceledOrder->id,
            'delivery_date' => '2026-03-11',
            'delivery_status' => 1,
        ]);

        $service = new OrderLinesService();
        $incomingOrders = $service->getIncomingOrders();

        $this->assertCount(1, $incomingOrders);
        $this->assertSame($openOrder->id, $incomingOrders->first()->orders_id);
    }
}
