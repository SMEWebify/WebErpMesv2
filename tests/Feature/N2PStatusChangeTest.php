<?php

namespace Tests\Feature;

use App\Jobs\PushOrderToN2P;
use App\Models\Setting;
use App\Models\Workflow\Orders;
use App\Observers\OrdersObserver;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class N2PStatusChangeTest extends TestCase
{
    public function test_dispatches_job_on_configured_status_change(): void
    {
        Queue::fake();

        Setting::create(['key' => 'n2p_enabled', 'value' => 'true']);
        Setting::create(['key' => 'n2p_send_on_order_status_from', 'value' => 'OPEN']);
        Setting::create(['key' => 'n2p_send_on_order_status_to', 'value' => 'IN_PROGRESS']);

        $order = new Orders([
            'id' => 99,
            'statu' => 2,
        ]);
        $order->setOriginal('statu', 1);

        $observer = new OrdersObserver(new SettingsService());
        $observer->updated($order);

        Queue::assertPushed(PushOrderToN2P::class, function (PushOrderToN2P $job) {
            return $job->orderId === 99;
        });
    }
}
