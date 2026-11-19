<?php

namespace App\Services\Workflow;

use App\Enums\SalesLineType;
use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Services\TaskService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Annulation d'une ligne de commande : le client renonce au reste non livré.
 *
 * - La quantité commandée tombe à la quantité livrée ; l'écart est gardé dans
 *   `cancelled_qty`. Le total de la commande baisse donc, le livré reste dû.
 * - Reste à livrer 0, reste à facturer recalculé (le livré non facturé reste à facturer).
 * - Tâches non terminées de la ligne fermées (même effet qu'une livraison complète).
 * - Motif obligatoire, tracé par activitylog (`order_line_cancelled`).
 *
 * La ligne n'est jamais supprimée : un BL ou une facture qui la cite reste lisible.
 * C'est aussi pourquoi la suppression d'une ligne livrée ou facturée est refusée
 * (`deletionRefusal()`).
 */
class OrderLineCancellation
{
    public const MIN_REASON_LENGTH = 3;

    public function __construct(private readonly TaskService $tasks) {}

    /** Statut de commande « Annulée ». */
    private const ORDER_CANCELLED = 6;

    /**
     * Raison de refuser l'annulation, null si elle est permise.
     */
    public function refusal(OrderLines $line): ?string
    {
        if ($line->line_type !== null && $line->line_type !== SalesLineType::Article->value) {
            return __('order_line_cancel.errors.not_article');
        }
        if ($line->isCancelled()) {
            return __('order_line_cancel.errors.already_cancelled');
        }
        if ((float) $line->delivered_remaining_qty <= 0) {
            return __('order_line_cancel.errors.fully_delivered');
        }
        if ((float) $line->invoiced_qty > (float) $line->delivered_qty) {
            return __('order_line_cancel.errors.invoiced_beyond_delivered');
        }

        return null;
    }

    /**
     * @throws OrderLineCancellationException
     */
    public function cancel(OrderLines $line, ?User $user, ?string $reason): OrderLines
    {
        $reason = is_string($reason) ? trim($reason) : '';
        if (mb_strlen($reason) < self::MIN_REASON_LENGTH) {
            throw new OrderLineCancellationException(__('order_line_cancel.errors.reason_required'));
        }

        return DB::transaction(function () use ($line, $user, $reason) {
            $line = OrderLines::whereKey($line->getKey())->lockForUpdate()->firstOrFail();

            if ($refusal = $this->refusal($line)) {
                throw new OrderLineCancellationException($refusal);
            }

            $before = (float) $line->qty;
            $cancelled = (float) $line->delivered_remaining_qty;
            $newQty = max(0.0, $before - $cancelled);
            $invoicedRemaining = max(0.0, $newQty - (float) $line->invoiced_qty);

            $line->forceFill([
                'qty' => $newQty,
                'delivered_remaining_qty' => 0,
                'delivery_status' => 3,
                'invoiced_remaining_qty' => $invoicedRemaining,
                'invoice_status' => $invoicedRemaining <= 0 ? 3 : ((float) $line->invoiced_qty > 0 ? 2 : 1),
                'cancelled_at' => now(),
                'cancelled_by' => $user?->id,
                'cancel_reason' => mb_substr($reason, 0, 255),
                'cancelled_qty' => $cancelled,
            ])->save();

            $closedTasks = $this->closeOpenTasks($line->id);
            $this->refreshOrderStatus((int) $line->orders_id);

            activity()
                ->performedOn($line)
                ->causedBy($user)
                ->withProperties([
                    'reason' => $reason,
                    'qty_before' => $before,
                    'cancelled_qty' => $cancelled,
                    'qty_after' => $newQty,
                    'closed_tasks' => $closedTasks,
                ])
                ->log('order_line_cancelled');

            return $line;
        });
    }

    /**
     * Raison de refuser la suppression d'une ligne, null si elle est permise :
     * une ligne livrée ou facturée, même en partie, ou citée par un BL ou une
     * facture, ne se supprime pas (le document la perdrait).
     */
    public function deletionRefusal(OrderLines $line): ?string
    {
        $used = (float) $line->delivered_qty > 0
            || (float) $line->invoiced_qty > 0
            || DeliveryLines::where('order_line_id', $line->id)->exists()
            || InvoiceLines::where('order_line_id', $line->id)->exists();

        return $used ? __('order_line_cancel.errors.delete_used') : null;
    }

    /**
     * Ferme les tâches non terminées de la ligne. Renvoie leurs ids.
     */
    private function closeOpenTasks(int $orderLineId): array
    {
        $finished = Status::where('title', 'Finished')->value('id');
        if (! $finished) {
            return [];
        }

        $tasks = Task::where('order_lines_id', $orderLineId)
            ->where(fn ($q) => $q->whereNull('status_id')->orWhere('status_id', '<>', $finished))
            ->get();

        foreach ($tasks as $task) {
            $task->update(['status_id' => $finished]);
            $this->tasks->recordTaskActivity($task->id, TaskActivities::TYPE_FINISH, 0, 0);
            Event::dispatch(new TaskChangeStatu($task->id));
        }

        return $tasks->pluck('id')->all();
    }

    /**
     * Commande livrée si toutes ses lignes le sont et qu'au moins une pièce est partie.
     * Sinon le statut n'est pas touché (une commande entièrement annulée sans
     * livraison s'annule par son propre statut).
     */
    private function refreshOrderStatus(int $orderId): void
    {
        $order = Orders::find($orderId);
        if (! $order || (int) $order->statu === self::ORDER_CANCELLED) {
            return;
        }

        $lines = OrderLines::where('orders_id', $orderId);
        $allDelivered = (clone $lines)->where('delivery_status', '<>', 3)->doesntExist();
        $anyShipped = (clone $lines)->where('delivered_qty', '>', 0)->exists();

        if ($allDelivered && $anyShipped && (int) $order->statu !== 3) {
            $order->update(['statu' => 3]); // Eloquent : OrdersObserver (cache)
        }
    }
}
