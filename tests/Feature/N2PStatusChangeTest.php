<?php

namespace Tests\Feature;

use App\Jobs\PushOrderToN2P;
use App\Models\Integrations\IntegrationEndpoint;
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

        // The trigger now lives on the n2p/outbound endpoint (is_active is the
        // master switch, metadata the business transition), no longer in settings.
        IntegrationEndpoint::query()->where('system_code', 'n2p')->delete();
        IntegrationEndpoint::create([
            'name'        => 'N2P outbound test',
            'system_code' => 'n2p',
            'direction'   => IntegrationEndpoint::DIRECTION_OUTBOUND,
            'url'         => 'https://n2p.test',
            'auth_method' => IntegrationEndpoint::AUTH_NONE,
            'verify_ssl'  => false,
            'is_active'   => true,
            'metadata'    => [
                IntegrationEndpoint::META_STATUS_TRANSITION_FROM => 'OPEN',
                IntegrationEndpoint::META_STATUS_TRANSITION_TO   => 'IN_PROGRESS',
            ],
        ]);

        $order = (new Orders())->newFromBuilder(['id' => 99, 'statu' => 1]);
        $order->statu = 2;

        $observer = new OrdersObserver(new SettingsService());
        $observer->updated($order);

        Queue::assertPushed(PushOrderToN2P::class, function (PushOrderToN2P $job) {
            return $job->orderId === 99;
        });
    }
}
