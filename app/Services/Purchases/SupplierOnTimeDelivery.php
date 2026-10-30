<?php

namespace App\Services\Purchases;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Taux de livraison à l'heure des fournisseurs (pendant achats du taux OTD client).
 *
 * - Une ligne d'achat est mesurée quand elle est entièrement réceptionnée (somme des
 *   quantités reçues ≥ quantité commandée), à la date de sa DERNIÈRE réception : une
 *   livraison partielle à l'heure suivie d'un solde en retard est en retard.
 * - Date de référence : date confirmée par le fournisseur (`confirmed_delivery_date`),
 *   sinon date demandée (`delivery_date`). Une ligne sans aucune des deux n'entre pas
 *   dans le taux ; elle est comptée à part (`undated`).
 * - À l'heure : dernière réception au plus tard le jour de référence + tolérance
 *   (`purchases.on_time_tolerance_days`, 0 par défaut).
 * - Fenêtre glissante : celle de la note fournisseur (`supplier_rating.window_months`),
 *   appliquée à la date de dernière réception, pour que les deux indicateurs affichés
 *   côte à côte couvrent la même période.
 * - Réceptions, lignes de réception, lignes et commandes d'achat supprimées ignorées.
 *
 * Une seule requête, quel que soit le nombre de fournisseurs.
 */
class SupplierOnTimeDelivery
{
    /**
     * @param  int[]|null  $companyIds  null = tous les fournisseurs
     * @return array{suppliers: Collection<int, array>, global: array}
     *         `suppliers` indexée par companies_id, fournisseurs sans ligne réceptionnée absents
     */
    public function summary(?array $companyIds = null): array
    {
        $since = $this->since();
        $tolerance = $this->toleranceDays();

        $rows = DB::table('purchase_lines')
            ->join('purchases', 'purchases.id', '=', 'purchase_lines.purchases_id')
            ->join('purchase_receipt_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
            ->join('purchase_receipts', 'purchase_receipts.id', '=', 'purchase_receipt_lines.purchase_receipt_id')
            ->whereNull('purchase_lines.deleted_at')
            ->whereNull('purchases.deleted_at')
            ->whereNull('purchase_receipt_lines.deleted_at')
            ->whereNull('purchase_receipts.deleted_at')
            ->where('purchase_lines.qty', '>', 0)
            ->when($companyIds !== null, fn ($q) => $q->whereIn('purchases.companies_id', $companyIds))
            ->groupBy(
                'purchase_lines.id',
                'purchases.companies_id',
                'purchase_lines.qty',
                'purchase_lines.delivery_date',
                'purchase_lines.confirmed_delivery_date'
            )
            ->havingRaw('SUM(purchase_receipt_lines.receipt_qty) >= purchase_lines.qty')
            ->havingRaw('MAX(purchase_receipts.created_at) >= ?', [$since->toDateTimeString()])
            ->select([
                'purchases.companies_id',
                'purchase_lines.delivery_date',
                'purchase_lines.confirmed_delivery_date',
                DB::raw('MAX(purchase_receipts.created_at) AS last_receipt_at'),
            ])
            ->get();

        $counts = [];
        $global = $this->emptyCounts();

        foreach ($rows as $row) {
            $companyId = (int) $row->companies_id;
            $counts[$companyId] ??= $this->emptyCounts();

            $reference = $row->confirmed_delivery_date ?: $row->delivery_date;
            $key = $reference === null
                ? 'undated'
                : (self::isOnTime($row->last_receipt_at, $reference, $tolerance) ? 'on_time' : 'late');

            $counts[$companyId][$key]++;
            $global[$key]++;
        }

        return [
            'suppliers' => collect($counts)->map(fn (array $c) => $this->stats($c)),
            'global'    => $this->stats($global),
        ];
    }

    /** Taux d'un fournisseur (voir summary()). */
    public function forSupplier(int $companyId): array
    {
        return $this->summary([$companyId])['suppliers']->get($companyId) ?? $this->stats($this->emptyCounts());
    }

    /** Dernière réception au plus tard le jour de référence + tolérance (comparaison à la journée). */
    public static function isOnTime(string $receivedAt, string $reference, int $toleranceDays = 0): bool
    {
        $received = Carbon::parse($receivedAt)->startOfDay();
        $deadline = Carbon::parse($reference)->startOfDay()->addDays(max(0, $toleranceDays));

        return $received->lessThanOrEqualTo($deadline);
    }

    /** Badge d'un taux, mêmes seuils que resources/js/lib/supplierOnTime.js. */
    public static function badgeClass(?float $rate): string
    {
        return match (true) {
            $rate === null => 'text-bg-secondary',
            $rate >= 95    => 'text-bg-success',
            $rate >= 85    => 'text-bg-warning',
            default        => 'text-bg-danger',
        };
    }

    private function stats(array $counts): array
    {
        $measured = $counts['on_time'] + $counts['late'];

        return $counts + [
            'measured'       => $measured,
            'rate'           => $measured > 0 ? round($counts['on_time'] / $measured * 100, 1) : null,
            'since'          => $this->since()->toDateString(),
            'window_months'  => $this->windowMonths(),
            'tolerance_days' => $this->toleranceDays(),
        ];
    }

    private function emptyCounts(): array
    {
        return ['on_time' => 0, 'late' => 0, 'undated' => 0];
    }

    private function since(): Carbon
    {
        return now()->subMonths($this->windowMonths())->startOfDay();
    }

    private function windowMonths(): int
    {
        return (int) config('supplier_rating.window_months', 12);
    }

    private function toleranceDays(): int
    {
        return max(0, (int) config('purchases.on_time_tolerance_days', 0));
    }
}
