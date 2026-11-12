<?php

namespace App\Services\Affairs;

use App\Models\Planning\Task;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Database\Eloquent\Builder;

/**
 * Périmètre documentaire d'une affaire : tout ce qui descend de l'opportunité.
 *
 *   opportunité → devis (quotes.opportunities_id)
 *               → commandes (orders.quotes_id)
 *               → lignes de commande → tâches (tasks.order_lines_id)
 *               → lignes d'achat (purchase_lines.tasks_id, ou commande d'achat
 *                 rattachée par son en-tête à l'opportunité, à un devis ou à une
 *                 commande de l'affaire : achats sans tâche)
 *               → factures (invoices.order_id, ou une ligne de facture sur une
 *                 ligne de commande de l'affaire : une facture peut regrouper
 *                 plusieurs commandes et n'a alors pas d'order_id)
 *
 * Un devis créé sans opportunité n'appartient à aucune affaire : il reste un
 * devis courant et n'est jamais rattaché implicitement.
 *
 * Chaque liste d'identifiants est résolue en une requête et mémorisée, pour
 * que la synthèse et la timeline partagent la même définition du périmètre.
 */
class AffairScope
{
    private ?array $quoteIds = null;
    private ?array $orderIds = null;
    private ?array $orderLineIds = null;
    private ?array $taskIds = null;
    private ?array $purchaseLineIds = null;
    private ?array $invoiceIds = null;

    public function __construct(public readonly Opportunities $opportunity)
    {
    }

    /** @return int[] */
    public function quoteIds(): array
    {
        return $this->quoteIds ??= Quotes::query()
            ->where('opportunities_id', $this->opportunity->id)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Commandes client (type 1) issues des devis de l'affaire. @return int[] */
    public function orderIds(): array
    {
        return $this->orderIds ??= $this->quoteIds() === [] ? [] : Orders::query()
            ->whereIn('quotes_id', $this->quoteIds())
            ->where('type', 1)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Lignes article des commandes de l'affaire. @return int[] */
    public function orderLineIds(): array
    {
        return $this->orderLineIds ??= $this->orderIds() === [] ? [] : OrderLines::query()
            ->whereIn('orders_id', $this->orderIds())
            ->articles()
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return int[] */
    public function taskIds(): array
    {
        return $this->taskIds ??= $this->orderLineIds() === [] ? [] : Task::query()
            ->whereIn('order_lines_id', $this->orderLineIds())
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return int[] */
    public function purchaseLineIds(): array
    {
        return $this->purchaseLineIds ??= self::purchaseLinesQuery(
            $this->opportunity->id, $this->quoteIds(), $this->orderIds(), $this->taskIds()
        )->pluck('purchase_lines.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Lignes d'achat de l'affaire : sur une de ses tâches, ou d'une commande
     * d'achat rattachée par son en-tête (PurchaseAffairLink).
     *
     * @param int[] $quoteIds
     * @param int[] $orderIds
     * @param int[] $taskIds
     */
    public static function purchaseLinesQuery(int $opportunityId, array $quoteIds, array $orderIds, array $taskIds): Builder
    {
        $linkedPurchases = Purchases::query()->select('id')->where(function ($q) use ($opportunityId, $quoteIds, $orderIds) {
            $q->where('opportunities_id', $opportunityId);
            if ($quoteIds !== []) {
                $q->orWhereIn('quotes_id', $quoteIds);
            }
            if ($orderIds !== []) {
                $q->orWhereIn('orders_id', $orderIds);
            }
        });

        return PurchaseLines::query()->where(function ($q) use ($taskIds, $linkedPurchases) {
            $q->whereIn('purchase_lines.purchases_id', $linkedPurchases);
            if ($taskIds !== []) {
                $q->orWhereIn('purchase_lines.tasks_id', $taskIds);
            }
        });
    }

    /** @return int[] */
    public function invoiceIds(): array
    {
        if ($this->invoiceIds !== null) {
            return $this->invoiceIds;
        }
        if ($this->orderIds() === []) {
            return $this->invoiceIds = [];
        }

        return $this->invoiceIds = self::invoicesQuery($this->orderIds(), $this->orderLineIds())
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Factures de l'affaire : rattachées à une de ses commandes, ou portant
     * une ligne sur une de ses lignes de commande (facture multi-commandes).
     *
     * @param int[] $orderIds
     * @param int[] $orderLineIds
     */
    public static function invoicesQuery(array $orderIds, array $orderLineIds): Builder
    {
        return Invoices::query()->where(function ($q) use ($orderIds, $orderLineIds) {
            $q->whereIn('invoices.order_id', $orderIds);
            if ($orderLineIds !== []) {
                $q->orWhereIn('invoices.id', InvoiceLines::query()
                    ->select('invoices_id')
                    ->whereIn('order_line_id', $orderLineIds));
            }
        });
    }
}
