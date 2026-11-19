<?php

namespace App\Services\Delivery;

use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\PackagingLine;
use App\Models\Workflow\ReturnLines;
use App\Services\DeliveryService;
use App\Services\StockService;
use App\Services\Workflow\OrderLineCancellationException;
use Illuminate\Support\Facades\DB;

/**
 * Annulation d'une ligne de BL (pièce finalement non partie, ligne mise par erreur).
 *
 * - Refusée si la ligne est facturée, ou citée par un retour client, un colis ou une NC.
 * - La quantité est rendue au reste à livrer de la ligne de commande. Si cette
 *   ligne de commande est annulée, la quantité rejoint la part annulée.
 * - Une sortie de stock faite par le BL (`typ_move` 9 de la ligne de commande) est
 *   compensée par une entrée (`typ_move` 5) au CUMP du moment : le CUMP ne bouge pas.
 * - La ligne de BL est supprimée ; le geste est tracé sur le BL (`delivery_line_cancelled`).
 *
 * Une ligne de BL dont la ligne de commande a disparu s'annule aussi (rien à rendre).
 * Les tâches fermées par la livraison ne sont pas rouvertes.
 */
class DeliveryLineCancellation
{
    public const MIN_REASON_LENGTH = 3;

    /** Statuts de commande. */
    private const ORDER_IN_PROGRESS = 2;
    private const ORDER_DELIVERED = 3;
    private const ORDER_PARTLY_DELIVERED = 4;

    public function __construct(
        private readonly DeliveryService $deliveries,
        private readonly StockService $stock,
    ) {}

    /**
     * Raison de refuser l'annulation, null si elle est permise.
     */
    public function refusal(DeliveryLines $line): ?string
    {
        if ((int) $line->invoice_status === 4 || InvoiceLines::where('delivery_line_id', $line->id)->exists()) {
            return __('order_line_cancel.errors.delivery_invoiced');
        }
        if (ReturnLines::where('delivery_line_id', $line->id)->exists()) {
            return __('order_line_cancel.errors.delivery_returned');
        }
        if (PackagingLine::where('delivery_line_id', $line->id)->exists()) {
            return __('order_line_cancel.errors.delivery_packaged');
        }
        if (QualityNonConformity::where('delivery_line_id', $line->id)->exists()) {
            return __('order_line_cancel.errors.delivery_nc');
        }

        return null;
    }

    /**
     * @return array{order_line_id: ?int, qty: float, stock_restored: float}
     *
     * @throws OrderLineCancellationException
     */
    public function cancel(DeliveryLines $line, ?User $user, ?string $reason): array
    {
        $reason = is_string($reason) ? trim($reason) : '';
        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            throw new OrderLineCancellationException(__('order_line_cancel.errors.reason_required'));
        }

        return DB::transaction(function () use ($line, $user, $reason) {
            $line = DeliveryLines::whereKey($line->getKey())->lockForUpdate()->firstOrFail();

            if ($refusal = $this->refusal($line)) {
                throw new OrderLineCancellationException($refusal);
            }

            $qty = (float) $line->qty;
            $orderLine = OrderLines::whereKey($line->order_line_id)->lockForUpdate()->first();
            $restored = 0.0;

            if ($orderLine) {
                $this->giveBack($orderLine, $qty);
                $restored = $this->restoreStock($orderLine->id, $qty, $user);
                $this->refreshOrderStatus((int) $orderLine->orders_id);
            }

            $deliveryId = (int) $line->deliverys_id;
            $line->delete();
            $this->deliveries->recomputeInvoiceStatus($deliveryId);

            activity()
                ->performedOn(Deliverys::find($deliveryId) ?? $line)
                ->causedBy($user)
                ->withProperties([
                    'reason' => $reason,
                    'delivery_line_id' => $line->id,
                    'order_line_id' => $line->order_line_id,
                    'code' => $orderLine?->code,
                    'label' => $orderLine?->label,
                    'qty' => $qty,
                    'stock_restored' => $restored,
                ])
                ->log('delivery_line_cancelled');

            return ['order_line_id' => $orderLine?->id, 'qty' => $qty, 'stock_restored' => $restored];
        });
    }

    /**
     * Rend la quantité à la ligne de commande.
     */
    private function giveBack(OrderLines $orderLine, float $qty): void
    {
        $delivered = max(0.0, (float) $orderLine->delivered_qty - $qty);

        if ($orderLine->isCancelled()) {
            // Ligne annulée : ce qui ne part plus rejoint la part annulée.
            $newQty = max(0.0, (float) $orderLine->qty - $qty);
            $orderLine->forceFill([
                'qty' => $newQty,
                'cancelled_qty' => (float) $orderLine->cancelled_qty + $qty,
                'delivered_qty' => $delivered,
                'invoiced_remaining_qty' => max(0.0, $newQty - (float) $orderLine->invoiced_qty),
            ]);
        } else {
            $remaining = (float) $orderLine->delivered_remaining_qty + $qty;
            $orderLine->forceFill([
                'delivered_qty' => $delivered,
                'delivered_remaining_qty' => $remaining,
                'delivery_status' => $remaining <= 0 ? 3 : ($delivered > 0 ? 2 : 1),
            ]);
        }

        $orderLine->save();
    }

    /**
     * Rentre en stock ce que les BL de la ligne de commande avaient sorti, dans la
     * limite de la quantité annulée : sorties `typ_move` 9 moins les entrées de
     * compensation déjà faites, emplacement par emplacement, le plus récent d'abord.
     */
    private function restoreStock(int $orderLineId, float $qty, ?User $user): float
    {
        $net = StockMove::where('order_line_id', $orderLineId)
            ->whereIn('typ_move', [9, 5])
            ->whereNull('remnant_source_move_id')
            ->groupBy('stock_location_products_id')
            ->selectRaw('stock_location_products_id,
                SUM(CASE WHEN typ_move = 9 THEN qty ELSE -qty END) AS net,
                MAX(id) AS last_id')
            ->orderByDesc('last_id')
            ->get();

        $toRestore = $qty;
        $restored = 0.0;

        foreach ($net as $row) {
            if ($toRestore <= 0) {
                break;
            }
            $back = min((float) $row->net, $toRestore);
            if ($back <= 0) {
                continue;
            }

            $slp = StockLocationProducts::find($row->stock_location_products_id);
            if (! $slp) {
                continue;
            }

            $this->stock->createStockMove([
                'user_id' => $user?->id,
                'qty' => $back,
                'stock_location_products_id' => $slp->id,
                'order_line_id' => $orderLineId,
                'typ_move' => 5,
                'component_price' => (float) $slp->unit_cost, // au CUMP : CUMP inchangé
            ]);

            $toRestore -= $back;
            $restored += $back;
        }

        return $restored;
    }

    /**
     * Commande « livrée » qui ne l'est plus : partiellement livrée s'il reste une
     * pièce partie, sinon en cours. Les autres statuts ne bougent pas.
     */
    private function refreshOrderStatus(int $orderId): void
    {
        $order = Orders::find($orderId);
        if (! $order || ! in_array((int) $order->statu, [self::ORDER_DELIVERED, self::ORDER_PARTLY_DELIVERED], true)) {
            return;
        }

        $lines = OrderLines::where('orders_id', $orderId);
        $allDelivered = (clone $lines)->where('delivery_status', '<>', 3)->doesntExist();
        $anyShipped = (clone $lines)->where('delivered_qty', '>', 0)->exists();

        $status = match (true) {
            $allDelivered && $anyShipped => self::ORDER_DELIVERED,
            $anyShipped => self::ORDER_PARTLY_DELIVERED,
            default => self::ORDER_IN_PROGRESS,
        };

        if ($status !== (int) $order->statu) {
            $order->update(['statu' => $status]);
        }
    }
}
