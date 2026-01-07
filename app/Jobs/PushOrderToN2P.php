<?php

namespace App\Jobs;

use App\Models\Workflow\Orders;
use App\Services\N2P\N2PClient;
use App\Services\N2P\N2PPayloadBuilder;
use App\Services\Settings\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushOrderToN2P implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 900, 1800, 3600];

    public function __construct(public readonly int $orderId)
    {
    }

    public function handle(SettingsService $settings, N2PPayloadBuilder $payloadBuilder): void
    {
        $order = Orders::with(['OrderLines.OrderLineDetails', 'OrderLines.Task', 'companie'])
            ->findOrFail($this->orderId);

        $config = $settings->getMany([
            'n2p_base_url',
            'n2p_api_token',
            'n2p_job_status_on_send',
            'n2p_priority_default',
            'n2p_send_tasks',
            'n2p_verify_ssl',
        ], [
            'n2p_verify_ssl' => true,
        ]);

        $client = new N2PClient(
            $config['n2p_base_url'],
            $config['n2p_api_token'] ?? null,
            $config['n2p_verify_ssl'] ?? true
        );
        $payload = $payloadBuilder->build($order, $config);

        $response = $client->pushJobs($payload);

        $order->update([
            'n2p_last_push_at' => now(),
            'n2p_last_push_status' => 'OK',
            'n2p_last_push_error' => null,
        ]);

        Log::channel('n2p')->info('N2P push success', [
            'order_id' => $order->getKey(),
            'response' => $response,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Orders::query()
            ->whereKey($this->orderId)
            ->update([
                'n2p_last_push_at' => now(),
                'n2p_last_push_status' => 'ERROR',
                'n2p_last_push_error' => $exception->getMessage(),
            ]);

        Log::channel('n2p')->error('N2P push failed', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
