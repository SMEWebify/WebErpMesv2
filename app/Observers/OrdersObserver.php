<?php

namespace App\Observers;

use App\Jobs\PushOrderToN2P;
use App\Models\Workflow\Orders;
use App\Services\Settings\SettingsService;

class OrdersObserver
{
    public function __construct(private SettingsService $settings)
    {
    }

    public function updated(Orders $order): void
    {
        if (!$order->isDirty('statu')) {
            return;
        }

        $config = $this->settings->getMany([
            'n2p_enabled',
            'n2p_send_on_order_status_from',
            'n2p_send_on_order_status_to',
        ]);

        if (empty($config['n2p_enabled'])) {
            return;
        }

        $fromSetting = $this->normalizeStatus($config['n2p_send_on_order_status_from'] ?? 'OPEN');
        $toSetting = $this->normalizeStatus($config['n2p_send_on_order_status_to'] ?? 'IN_PROGRESS');

        $oldStatus = $this->normalizeStatus($order->getOriginal('statu'));
        $newStatus = $this->normalizeStatus($order->statu);

        if ($oldStatus === $fromSetting && $newStatus === $toSetting) {
            PushOrderToN2P::dispatch($order->getKey());
        }
    }

    private function normalizeStatus($status): ?int
    {
        if (is_numeric($status)) {
            return (int) $status;
        }

        $map = [
            'OPEN' => 1,
            'IN_PROGRESS' => 2,
            'DELIVERED' => 3,
            'PARTLY_DELIVERED' => 4,
            'STOPPED' => 5,
            'CANCELED' => 6,
        ];

        $upper = strtoupper((string) $status);

        return $map[$upper] ?? null;
    }
}
