<?php

namespace App\Observers;

use App\Jobs\PushOrderToN2P;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Workflow\Orders;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrdersObserver
{
    private const MENU_ORDERS_NOT_FINISH_CACHE_KEY = 'menu_orders_not_finish';

    public function created(Orders $order): void
    {
        Cache::forget(self::MENU_ORDERS_NOT_FINISH_CACHE_KEY);
    }

    public function updated(Orders $order): void
    {
        if (!$order->isDirty('statu')) {
            return;
        }

        Cache::forget(self::MENU_ORDERS_NOT_FINISH_CACHE_KEY);

        // Source de vérité unique : l'endpoint n2p/outbound. is_active =
        // master switch, metadata porte la règle de transition métier.
        $endpoint = IntegrationEndpoint::query()
            ->forSystem('n2p')
            ->outbound()
            ->active()
            ->first();

        if (! $endpoint) {
            return;
        }

        $fromSetting = $this->normalizeStatus($endpoint->meta(IntegrationEndpoint::META_STATUS_TRANSITION_FROM));
        $toSetting   = $this->normalizeStatus($endpoint->meta(IntegrationEndpoint::META_STATUS_TRANSITION_TO));

        $oldStatus = $this->normalizeStatus($order->getOriginal('statu'));
        $newStatus = $this->normalizeStatus($order->statu);

        if ($oldStatus === $fromSetting && $newStatus === $toSetting) {
            // En QUEUE_CONNECTION=sync, une exception du job (endpoint inactif,
            // N2P down, HMAC KO) remonterait au contrôleur et 500erait la mise
            // à jour de la commande — opération métier critique qu'on ne veut
            // JAMAIS bloquer sur un push d'intégration. On isole. En Redis async
            // le try/catch est un no-op (dispatch ne throw pas).
            try {
                PushOrderToN2P::dispatch($order->getKey());
            } catch (Throwable $e) {
                Log::channel('n2p')->error('N2P push dispatch failed (defensive catch)', [
                    'order_id' => $order->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
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
