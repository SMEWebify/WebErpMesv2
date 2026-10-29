<?php

namespace App\Observers;

use App\Models\Planning\Task;
use App\Models\Workflow\OrderLineDelayHistory;
use App\Models\Workflow\OrderLines;
use App\Services\Production\ManufacturingDossierGate;
use App\Services\StockReservationService;
use App\Support\OrderLineDelayContext;
use Carbon\Carbon;

/**
 * - Recalcule les réservations quand une OrderLines.qty change : la formule
 *   de besoin est task.qty × orderline.qty, donc éditer la quantité commandée
 *   doit propager sur toutes les tâches attachées à cette ligne.
 * - Fige la date promise au client (promised_delivery_date) et historise
 *   chaque report de delivery_date / internal_delay.
 *
 * Une écriture de masse (`OrderLines::where(...)->update()`, `DB::table`)
 * ne passe pas par ici : les reports doivent être écrits ligne par ligne.
 */
class OrderLinesObserver
{
    public function __construct(private readonly StockReservationService $reservations) {}

    public function creating(OrderLines $orderLine): void
    {
        if ($orderLine->promised_delivery_date === null) {
            $orderLine->promised_delivery_date = self::toDate($orderLine->delivery_date);
        }

        // Une ligne neuve (copie comprise) n'a jamais de bon pour fabrication.
        if ($orderLine->released_for_production_at !== null || $orderLine->released_for_production_by !== null) {
            $orderLine->released_for_production_at = null;
            $orderLine->released_for_production_by = null;
        }

        // Dossier de fin de fabrication : jamais validé à la création (copie comprise) ;
        // exigence reprise de la catégorie d'affaire, sauf si la ligne la porte déjà (copie).
        $orderLine->dossier_validated_at = null;
        $orderLine->dossier_validated_by = null;
        $orderLine->dossier_source       = null;

        if (! ManufacturingDossierGate::isArticle($orderLine)) {
            $orderLine->dossier_required = false;
        } elseif (! array_key_exists('dossier_required', $orderLine->getAttributes())) {
            $orderLine->dossier_required = ManufacturingDossierGate::requiredByDefault($orderLine->orders_id);
        }
    }

    /**
     * Nouvel indice = nouveau plan : le bon pour fabrication donné sur
     * l'ancien ne vaut plus. activitylog garde l'ancien BPF et l'indice.
     */
    private function revokeReleaseOnNewRevision(OrderLines $orderLine): void
    {
        if (! $orderLine->isDirty('ind') || $orderLine->isDirty('released_for_production_at')) {
            return;
        }
        if ($orderLine->getOriginal('released_for_production_at') === null) {
            return;
        }

        $old = trim((string) $orderLine->getOriginal('ind'));
        $new = trim((string) $orderLine->ind);

        if ($old === $new) {
            return;
        }

        $orderLine->released_for_production_at = null;
        $orderLine->released_for_production_by = null;
    }

    /**
     * La date promise ne se modifie jamais. Une ligne qui n'en a pas encore
     * (créée sans date, ou antérieure à la colonne et pas encore reprise)
     * reçoit la date en vigueur avant cette modification — même règle que la
     * reprise `wem:orders:backfill-promised-dates`.
     */
    public function updating(OrderLines $orderLine): void
    {
        $this->revokeReleaseOnNewRevision($orderLine);

        // Ligne chargée par un select partiel : la date promise réelle est
        // inconnue ici, l'écrire écraserait celle de la base.
        if (!array_key_exists('promised_delivery_date', $orderLine->getRawOriginal())) {
            $orderLine->offsetUnset('promised_delivery_date');

            return;
        }

        $original = self::toDate($orderLine->getOriginal('promised_delivery_date'));

        if ($original !== null) {
            if ($orderLine->isDirty('promised_delivery_date')) {
                $orderLine->promised_delivery_date = $orderLine->getOriginal('promised_delivery_date');
            }

            return;
        }

        $orderLine->promised_delivery_date = self::toDate($orderLine->getOriginal('delivery_date'))
            ?? self::toDate($orderLine->delivery_date);
    }

    public function updated(OrderLines $orderLine): void
    {
        $this->recordDelays($orderLine);

        if (!$orderLine->isDirty('qty')) {
            return;
        }

        $componentIds = Task::where('order_lines_id', $orderLine->id)
            ->whereNotNull('component_id')
            ->pluck('component_id')
            ->unique()
            ->all();

        foreach ($componentIds as $componentId) {
            $this->reservations->recomputeForProduct((int) $componentId);
        }
    }

    /**
     * Écrit après la mise à jour (et non dans `updating`) pour qu'une
     * écriture qui échoue ne laisse pas un report fantôme. isDirty() et
     * getOriginal() portent encore l'état d'avant à ce stade.
     *
     * Les lignes de présentation (section, sous-total, texte) ne se livrent
     * pas : leurs dates ne sont pas historisées.
     */
    private function recordDelays(OrderLines $orderLine): void
    {
        if (array_key_exists('line_type', $orderLine->getAttributes()) && !$orderLine->isArticle()) {
            return;
        }

        foreach (OrderLineDelayHistory::FIELDS as $field) {
            if (!$orderLine->isDirty($field)) {
                continue;
            }

            $old = self::toDate($orderLine->getOriginal($field));
            $new = self::toDate($orderLine->getAttribute($field));
            if ($old === $new) {
                continue; // même jour, écrit autrement (« 2026-12-15 00:00:00 »)
            }

            // L'historique ne doit jamais faire échouer l'enregistrement de la ligne.
            try {
                OrderLineDelayHistory::create([
                    'order_lines_id' => $orderLine->id,
                    'field'          => $field,
                    'old_value'      => $old,
                    'new_value'      => $new,
                    'reason'         => OrderLineDelayContext::reason(),
                    'origin'         => OrderLineDelayContext::origin(),
                    'user_id'        => auth()->id(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** Date ramenée à Y-m-d ; une valeur illisible vaut null. */
    private static function toDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
