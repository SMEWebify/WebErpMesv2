<?php

namespace App\Services\Purchases;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\SupplierRating;
use App\Models\Purchases\PurchaseReceipt;

/**
 * Notation rapide du fournisseur à la réception, et note glissante qui en découle.
 *
 * - La fenêtre de notation est facultative : une réception sans note reste valide.
 * - Trois critères de 1 à 5 : conformité, délai, documents. Ils sont rangés dans les
 *   colonnes d'évaluation existantes (échelle 0-100, d'où × 20) : qualité, logistique,
 *   service. `rating` reçoit leur moyenne arrondie, pour les écrans qui ne lisent qu'elle.
 * - Note glissante (fenêtre `supplier_rating.window_months`) : toutes les évaluations du
 *   fournisseur, celles des réceptions comme celles saisies sur la fiche. Une réception
 *   dont une ligne porte une NC (non annulée) compte la note qualité
 *   `supplier_rating.nc_quality_score`, notée ou non : la NC est souvent créée après la
 *   notation, elle est donc appliquée à la lecture et jamais écrite dans la note.
 */
class SupplierReceiptRating
{
    public const CRITERIA = ['conformity', 'delay', 'documents'];

    public function ratingFor(PurchaseReceipt $receipt): ?SupplierRating
    {
        return SupplierRating::where('purchase_receipt_id', $receipt->id)->first();
    }

    /**
     * Enregistre (ou corrige) la note d'une réception.
     *
     * @param  array{conformity:int, delay:int, documents:int}  $scores  de 1 à 5
     *
     * @throws \DomainException  réception sans ligne : aucune commande d'achat à rattacher
     */
    public function record(PurchaseReceipt $receipt, array $scores, ?string $comment = null): SupplierRating
    {
        // supplier_ratings.purchases_id est obligatoire : commande de la première ligne.
        $purchaseId = DB::table('purchase_receipt_lines')
            ->join('purchase_lines', 'purchase_lines.id', '=', 'purchase_receipt_lines.purchase_line_id')
            ->where('purchase_receipt_lines.purchase_receipt_id', $receipt->id)
            ->orderBy('purchase_receipt_lines.ordre')
            ->orderBy('purchase_receipt_lines.id')
            ->value('purchase_lines.purchases_id');

        if (! $purchaseId) {
            throw new \DomainException(__('supplier_rating.no_lines'));
        }

        $values = array_map('intval', [
            'conformity' => $scores['conformity'],
            'delay'      => $scores['delay'],
            'documents'  => $scores['documents'],
        ]);

        return SupplierRating::updateOrCreate(
            ['purchase_receipt_id' => $receipt->id],
            [
                'purchases_id'               => $purchaseId,
                'companies_id'               => $receipt->companies_id,
                'rating'                     => (int) round(array_sum($values) / 3),
                'comment'                    => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
                'evaluation_score_quality'   => $values['conformity'] * 20,
                'evaluation_score_logistics' => $values['delay'] * 20,
                'evaluation_score_service'   => $values['documents'] * 20,
            ]
        );
    }

    /**
     * Les trois critères d'une note de réception, sur 5.
     *
     * @return array{conformity:?int, delay:?int, documents:?int}
     */
    public function scoresOf(SupplierRating $rating): array
    {
        $toFive = fn ($v) => $v === null ? null : (int) round($v / 20);

        return [
            'conformity' => $toFive($rating->evaluation_score_quality),
            'delay'      => $toFive($rating->evaluation_score_logistics),
            'documents'  => $toFive($rating->evaluation_score_service),
        ];
    }

    /**
     * Retard de la réception, en jours : pire ligne, date de réception contre la date promise
     * de la ligne d'achat. Null si aucune ligne n'a de date promise. Une avance compte 0.
     */
    public function daysLate(PurchaseReceipt $receipt): ?int
    {
        $promised = DB::table('purchase_receipt_lines')
            ->join('purchase_lines', 'purchase_lines.id', '=', 'purchase_receipt_lines.purchase_line_id')
            ->where('purchase_receipt_lines.purchase_receipt_id', $receipt->id)
            ->whereNotNull('purchase_lines.delivery_date')
            ->min('purchase_lines.delivery_date');

        if (! $promised) {
            return null;
        }

        $received = Carbon::parse($receipt->created_at ?? now())->startOfDay();

        return max(0, (int) Carbon::parse($promised)->startOfDay()->diffInDays($received, false));
    }

    /** Note de délai proposée (1 à 5), ou null sans date promise. */
    public function suggestedDelayScore(PurchaseReceipt $receipt): ?int
    {
        $days = $this->daysLate($receipt);

        return $days === null ? null : self::delayScoreFor($days);
    }

    public static function delayScoreFor(int $daysLate): int
    {
        $thresholds = config('supplier_rating.delay_thresholds', []);
        ksort($thresholds);

        foreach ($thresholds as $maxDays => $score) {
            if ($daysLate <= $maxDays) {
                return (int) $score;
            }
        }

        return 1;
    }

    /** La réception a-t-elle une ligne liée à une NC non annulée ? */
    public function hasNonConformity(PurchaseReceipt $receipt): bool
    {
        return $this->nonConformingReceipts(null, null)
            ->where('purchase_receipts.id', $receipt->id)
            ->exists();
    }

    /**
     * Note glissante d'un fournisseur (voir rolling()).
     *
     * @return array{rating:?float, quality:?float, logistics:?float, service:?float, composite_score:?float, ratings_count:int, nc_receipts:int, since:string, window_months:int}
     */
    public function rollingFor(int $companyId): array
    {
        return $this->rolling([$companyId])->get($companyId) ?? $this->emptyStats();
    }

    /**
     * Note glissante par fournisseur, en deux requêtes quel que soit le nombre de fournisseurs.
     *
     * Chaque évaluation est une observation (qualité, logistique, service sur 5) ; une
     * évaluation sans critère mais avec une note globale vaut cette note sur les trois.
     * Une réception non conforme sans note ajoute une observation de qualité seule.
     * `rating` = moyenne des trois moyennes de critère ; `composite_score` = la même sur 100,
     * échelle de l'indicateur fournisseurs.
     *
     * @param  int[]|null  $companyIds  null = tous les fournisseurs
     * @return Collection<int, array>  indexée par companies_id, fournisseurs sans observation absents
     */
    public function rolling(?array $companyIds = null): Collection
    {
        $since = $this->since();
        $ncScore = (float) config('supplier_rating.nc_quality_score', 1);

        $ratings = DB::table('supplier_ratings')
            ->leftJoin('purchase_receipts', 'purchase_receipts.id', '=', 'supplier_ratings.purchase_receipt_id')
            ->where('supplier_ratings.created_at', '>=', $since)
            ->where(fn ($q) => $q->whereNull('supplier_ratings.purchase_receipt_id')
                ->orWhereNull('purchase_receipts.deleted_at'))
            ->when($companyIds !== null, fn ($q) => $q->whereIn('supplier_ratings.companies_id', $companyIds))
            ->get([
                'supplier_ratings.companies_id',
                'supplier_ratings.purchase_receipt_id',
                'supplier_ratings.rating',
                'supplier_ratings.evaluation_score_quality',
                'supplier_ratings.evaluation_score_logistics',
                'supplier_ratings.evaluation_score_service',
            ]);

        $ncReceipts = $this->nonConformingReceipts($since, $companyIds)
            ->get(['purchase_receipts.id', 'purchase_receipts.companies_id']);

        $ncReceiptIds = $ncReceipts->pluck('id')->map(fn ($id) => (int) $id)->flip();
        $ratedReceiptIds = [];
        $observations = [];

        foreach ($ratings as $row) {
            $axes = [
                'quality'   => $row->evaluation_score_quality === null ? null : $row->evaluation_score_quality / 20,
                'logistics' => $row->evaluation_score_logistics === null ? null : $row->evaluation_score_logistics / 20,
                'service'   => $row->evaluation_score_service === null ? null : $row->evaluation_score_service / 20,
            ];

            if (count(array_filter($axes, fn ($v) => $v !== null)) === 0) {
                if (! $row->rating) {
                    continue;
                }
                $axes = array_fill_keys(array_keys($axes), (float) $row->rating);
            }

            if ($row->purchase_receipt_id !== null) {
                $ratedReceiptIds[(int) $row->purchase_receipt_id] = true;

                if (isset($ncReceiptIds[(int) $row->purchase_receipt_id])) {
                    $axes['quality'] = min($axes['quality'] ?? $ncScore, $ncScore);
                }
            }

            $observations[(int) $row->companies_id][] = $axes;
        }

        $ncCount = [];
        foreach ($ncReceipts as $receipt) {
            $companyId = (int) $receipt->companies_id;
            $ncCount[$companyId] = ($ncCount[$companyId] ?? 0) + 1;

            if (! isset($ratedReceiptIds[(int) $receipt->id])) {
                $observations[$companyId][] = ['quality' => $ncScore, 'logistics' => null, 'service' => null];
            }
        }

        return collect($observations)->map(function (array $rows, int $companyId) use ($ncCount, $since) {
            $avg = function (string $axis) use ($rows) {
                $values = array_filter(array_column($rows, $axis), fn ($v) => $v !== null);

                return $values ? array_sum($values) / count($values) : null;
            };

            $axes = ['quality' => $avg('quality'), 'logistics' => $avg('logistics'), 'service' => $avg('service')];
            $present = array_filter($axes, fn ($v) => $v !== null);
            $overall = $present ? array_sum($present) / count($present) : null;

            return [
                'rating'          => $overall === null ? null : round($overall, 2),
                'quality'         => $axes['quality'] === null ? null : round($axes['quality'], 2),
                'logistics'       => $axes['logistics'] === null ? null : round($axes['logistics'], 2),
                'service'         => $axes['service'] === null ? null : round($axes['service'], 2),
                'composite_score' => $overall === null ? null : round($overall * 20, 1),
                'ratings_count'   => count($rows),
                'nc_receipts'     => $ncCount[$companyId] ?? 0,
                'since'           => $since->toDateString(),
                'window_months'   => (int) config('supplier_rating.window_months', 12),
            ];
        });
    }

    private function since(): Carbon
    {
        return now()->subMonths((int) config('supplier_rating.window_months', 12))->startOfDay();
    }

    private function emptyStats(): array
    {
        return [
            'rating' => null, 'quality' => null, 'logistics' => null, 'service' => null,
            'composite_score' => null, 'ratings_count' => 0, 'nc_receipts' => 0,
            'since' => $this->since()->toDateString(),
            'window_months' => (int) config('supplier_rating.window_months', 12),
        ];
    }

    /** Réceptions (non supprimées) dont une ligne est liée à une NC non annulée. */
    private function nonConformingReceipts(?Carbon $since, ?array $companyIds)
    {
        return DB::table('purchase_receipts')
            ->whereNull('purchase_receipts.deleted_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('purchase_receipt_lines')
                    ->join('quality_non_conformities', 'quality_non_conformities.id', '=', 'purchase_receipt_lines.quality_non_conformity_id')
                    ->whereColumn('purchase_receipt_lines.purchase_receipt_id', 'purchase_receipts.id')
                    ->where('quality_non_conformities.statu', '!=', 4);
            })
            ->when($since !== null, fn ($q) => $q->where('purchase_receipts.created_at', '>=', $since))
            ->when($companyIds !== null, fn ($q) => $q->whereIn('purchase_receipts.companies_id', $companyIds));
    }
}
