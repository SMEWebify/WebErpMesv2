<?php

namespace App\Services\Purchases;

use App\Models\Planning\Task;
use App\Models\Purchases\PurchaseQuotationLines;
use Illuminate\Support\Facades\DB;

/**
 * Réponse fournisseur retenue pour le chiffrage d'un devis.
 *
 * Une ligne de demande de prix posée sur une tâche de devis (pas encore
 * commandé) peut être « retenue » : son prix unitaire devient le coût
 * unitaire de la tâche, donc la marge du devis (QuoteMargin). Une seule ligne
 * retenue par tâche ; en retenir une autre remplace la précédente.
 *
 * Refusé une fois le devis converti ou clos : le coût d'une commande se suit
 * par sa commande d'achat, pas par une demande de prix.
 */
class PurchaseQuotationRetention
{
    /** Statuts de devis où le chiffrage bouge encore : ouvert, envoyé. */
    public const QUOTE_EDITABLE = [1, 2];

    /** Raison du refus (clé de purchase_affair.retain_refused.*), null si possible. */
    public function blockingReason(PurchaseQuotationLines $line): ?string
    {
        $task = $line->tasks_id ? $line->tasks : null;

        if (! $task || ! $task->quote_lines_id || $task->order_lines_id) {
            return 'not_quote_task';
        }

        $quoteLine = $task->QuoteLines;
        if (! $quoteLine || $quoteLine->orderLine()->exists()) {
            return 'converted';
        }

        if (! in_array((int) $quoteLine->quote?->statu, self::QUOTE_EDITABLE, true)) {
            return 'quote_closed';
        }

        return null;
    }

    /**
     * Retient la ligne : coût unitaire de la tâche = prix de la ligne.
     *
     * @throws \DomainException refus (message traduit)
     */
    public function retain(PurchaseQuotationLines $line): Task
    {
        if ($reason = $this->blockingReason($line)) {
            throw new \DomainException(__('purchase_affair.retain_refused.' . $reason));
        }

        return DB::transaction(function () use ($line) {
            PurchaseQuotationLines::query()
                ->where('tasks_id', $line->tasks_id)
                ->whereKeyNot($line->id)
                ->whereNotNull('retained_at')
                ->update(['retained_at' => null]);

            $line->forceFill(['retained_at' => now()])->save();

            $task = $line->tasks;
            $task->unit_cost = $line->unit_price;
            $task->save();

            return $task;
        });
    }
}
