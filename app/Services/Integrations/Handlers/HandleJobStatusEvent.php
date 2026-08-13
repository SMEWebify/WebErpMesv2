<?php

namespace App\Services\Integrations\Handlers;

use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\Integrations\IntegrationEventHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class HandleJobStatusEvent implements IntegrationEventHandler
{
    public const EVENT_TYPE = 'job.status_changed';

    /**
     * Statuts ERP Order (voir OrdersController::updateStatu et enum métier).
     */
    private const ORDER_OPEN = 1;
    private const ORDER_IN_PROGRESS = 2;
    private const ORDER_DELIVERED = 3;
    private const ORDER_STOPPED = 5;
    private const ORDER_CANCELED = 6;

    /**
     * Mapping conservateur N2P Job.status → ERP Orders.statu.
     *
     * On ne fait QUE des transitions safe :
     *  - N2P in_progress → ERP IN_PROGRESS (2) uniquement si Order est OPEN (1)
     *  - N2P on_hold     → ERP STOPPED (5) uniquement si Order est IN_PROGRESS (2)
     *  - N2P completed   : PAS de mapping auto (marquer DELIVERED nécessite de vérifier
     *                      que TOUTES les lignes de la commande sont terminées — chantier
     *                      métier V2, dépend des règles de livraison/facturation)
     *  - N2P canceled    : PAS de mapping auto (une seule ligne annulée != commande annulée)
     */
    private const ALLOWED_TRANSITIONS = [
        'in_progress' => [self::ORDER_OPEN => self::ORDER_IN_PROGRESS],
        'on_hold'     => [self::ORDER_IN_PROGRESS => self::ORDER_STOPPED],
    ];

    public function handle(IntegrationEndpoint $endpoint, array $data, array $meta): void
    {
        if ((string) ($meta['event_type'] ?? '') !== self::EVENT_TYPE) {
            throw new InvalidArgumentException('HandleJobStatusEvent only handles job.status_changed');
        }

        $ofCode = (string) ($data['of_code'] ?? '');
        $newStatus = (string) ($data['new_status'] ?? '');

        if ($ofCode === '' || $newStatus === '') {
            throw new InvalidArgumentException('job.status_changed requires of_code and new_status');
        }

        $order = $this->resolveOrder($ofCode, (string) ($data['line_ref'] ?? ''));
        if (! $order) {
            // Order absente côté ERP — peut arriver après restauration d'un
            // vieux backup, purge RGPD, ou décalage de tenants. On loge et on
            // sort en silence : throw ferait retenter N2P en boucle pour rien
            // (la ligne n'existera jamais). Delivery marquée processed → 200.
            Log::channel('n2p')->warning('Skipping job.status_changed for unknown order', [
                'of_code' => $ofCode,
                'line_ref' => (string) ($data['line_ref'] ?? ''),
                'new_status' => $newStatus,
            ]);
            return;
        }

        $occurredAt = $meta['occurred_at'] instanceof Carbon
            ? $meta['occurred_at']
            : Carbon::parse((string) $meta['occurred_at']);

        if ($order->n2p_last_status_event_at && $order->n2p_last_status_event_at->gte($occurredAt)) {
            Log::channel('n2p')->info('Skipping stale job.status_changed event', [
                'order_id' => $order->id,
                'event_occurred_at' => $occurredAt->toIso8601String(),
                'last_applied_at' => $order->n2p_last_status_event_at->toIso8601String(),
            ]);
            return;
        }

        $targetStatu = $this->resolveTargetStatus($newStatus, (int) $order->statu);
        $updates = ['n2p_last_status_event_at' => $occurredAt];

        if ($targetStatu !== null) {
            $updates['statu'] = $targetStatu;
            Log::channel('n2p')->info('Order status updated from N2P event', [
                'order_id' => $order->id,
                'from' => $order->statu,
                'to' => $targetStatu,
                'n2p_status' => $newStatus,
            ]);
        }

        $order->fill($updates)->save();
    }

    private function resolveOrder(string $ofCode, string $lineRef): ?Orders
    {
        $orderLineId = null;
        if (str_starts_with($ofCode, 'OF')) {
            $orderLineId = (int) substr($ofCode, 2);
        } elseif (ctype_digit($lineRef)) {
            $orderLineId = (int) $lineRef;
        }

        if ($orderLineId === null || $orderLineId <= 0) {
            return null;
        }

        return OrderLines::query()->whereKey($orderLineId)->first()?->order;
    }

    private function resolveTargetStatus(string $newStatus, int $currentStatu): ?int
    {
        $transitions = self::ALLOWED_TRANSITIONS[$newStatus] ?? null;
        if ($transitions === null) {
            return null;
        }
        return $transitions[$currentStatu] ?? null;
    }
}
