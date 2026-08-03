<?php

namespace App\Services;

use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use App\Models\Products\StockReservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Réservations de stock sur les composants achetés (products.purchased = 1).
 *
 * Le calcul est piloté par recomputeForProduct() : pour un produit donné, on
 * répartit le stock physique disponible entre toutes les tâches actives qui
 * consomment ce composant, triées par end_date ASC (les tâches à échéance la
 * plus proche sont servies en premier). Une tâche sans stock suffisant reste
 * en manque (qty_missing).
 */
class StockReservationService
{
    // Types de mouvements comptés en entrée / sortie pour le stock physique.
    private const ENTRY_TYPES   = [1, 3, 5, 12, 14];
    private const SORTING_TYPES = [2, 4, 6, 9];

    /**
     * Recalcule intégralement les réservations pour ce composant.
     *
     * Idempotent : peut être rappelé autant de fois que nécessaire. Verrou
     * pessimiste sur la ligne products pour sérialiser les recomputes
     * concurrents sur le même composant.
     */
    public function recomputeForProduct(int $productId): void
    {
        DB::transaction(function () use ($productId) {
            $product = Products::whereKey($productId)->lockForUpdate()->first();

            // Composant supprimé ou non-acheté : on nettoie toute réservation résiduelle.
            if (!$product || (int) $product->purchased !== 1) {
                StockReservation::where('products_id', $productId)->delete();
                return;
            }

            $physicalStock = $this->physicalStockOf($productId);
            $finishedId    = $this->finishedStatusId();

            $tasks = Task::where('component_id', $productId)
                ->when($finishedId, fn ($q) => $q->where('status_id', '!=', $finishedId))
                ->orderByRaw('CASE WHEN end_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('end_date', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id', 'qty', 'end_date']);

            $remaining      = $physicalStock;
            $keptTaskIds    = [];

            foreach ($tasks as $task) {
                $alreadyConsumed = (float) StockMove::where('task_id', $task->id)
                    ->whereIn('typ_move', self::SORTING_TYPES)
                    ->sum('qty');

                $qtyNeeded = max(0.0, (float) $task->qty - $alreadyConsumed);

                if ($qtyNeeded <= 0) {
                    // Rien à réserver pour cette tâche — on nettoie une éventuelle ligne existante.
                    StockReservation::where('task_id', $task->id)
                        ->where('products_id', $productId)
                        ->delete();
                    continue;
                }

                $qtyReserved = min($qtyNeeded, max(0.0, $remaining));
                $qtyMissing  = $qtyNeeded - $qtyReserved;
                $remaining  -= $qtyReserved;

                StockReservation::updateOrCreate(
                    ['task_id' => $task->id, 'products_id' => $productId],
                    [
                        'qty_requested' => $qtyNeeded,
                        'qty_reserved'  => $qtyReserved,
                        'qty_missing'   => $qtyMissing,
                        'status'        => StockReservation::STATUS_ACTIVE,
                    ]
                );

                $keptTaskIds[] = $task->id;
            }

            // Supprime toute réservation orpheline (tâche finie, supprimée,
            // changement de component_id, etc.).
            StockReservation::where('products_id', $productId)
                ->when(!empty($keptTaskIds), fn ($q) => $q->whereNotIn('task_id', $keptTaskIds))
                ->delete();
        });
    }

    /**
     * Somme du stock physique du produit à travers tous ses emplacements.
     */
    private function physicalStockOf(int $productId): float
    {
        $entryList   = implode(',', self::ENTRY_TYPES);
        $sortingList = implode(',', self::SORTING_TYPES);

        return (float) StockMove::query()
            ->join('stock_location_products', 'stock_moves.stock_location_products_id', '=', 'stock_location_products.id')
            ->where('stock_location_products.products_id', $productId)
            ->selectRaw("
                COALESCE(SUM(CASE
                    WHEN stock_moves.typ_move IN ($entryList)   THEN stock_moves.qty
                    WHEN stock_moves.typ_move IN ($sortingList) THEN -stock_moves.qty
                    ELSE 0
                END), 0) AS available
            ")
            ->value('available');
    }

    /**
     * ID du statut "Finished", mémoïsé pour la durée de la requête.
     * Renvoie null si le statut n'existe pas (env de test par ex.).
     */
    private function finishedStatusId(): ?int
    {
        return Cache::store('array')->rememberForever('stock_reservation.finished_status_id', function () {
            return Status::where('title', 'Finished')->value('id');
        });
    }
}
