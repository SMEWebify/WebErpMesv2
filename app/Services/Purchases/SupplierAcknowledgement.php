<?php

namespace App\Services\Purchases;

use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Accusé de réception (AR) des commandes d'achat par le fournisseur.
 *
 * - Saisie ligne par ligne (`record()`) : date de l'AR, référence, date de livraison
 *   confirmée (à défaut, la date demandée). Une nouvelle saisie remplace la précédente
 *   (le fournisseur renvoie un AR modifié) ; activitylog garde l'historique.
 * - Seulement sur une commande passée : en cours (1) ou annulée (5) → 422.
 * - Date de référence des retards (`referenceDate()`, `REFERENCE_SQL`) : date confirmée,
 *   sinon date demandée. Utilisée par la synthèse d'affaire, l'écran « En attente de
 *   réception », le retard proposé à la notation et le taux de livraison à l'heure.
 * - « Sans AR depuis N jours » (`overdueQuery()`) : commande commandée ou partiellement
 *   reçue (2, 3), passée depuis au moins `purchases.ack_overdue_days` jours (`ordered_at`,
 *   sinon `created_at` pour l'existant), dont une ligne à quantité non nulle n'a pas d'AR.
 *   Informatif : rien n'est bloqué.
 */
class SupplierAcknowledgement
{
    /** Date de référence d'une ligne d'achat en SQL (préfixe de table `purchase_lines`). */
    public const REFERENCE_SQL = 'COALESCE(purchase_lines.confirmed_delivery_date, purchase_lines.delivery_date)';

    /** Statuts d'une commande sur laquelle un AR est attendu (commandée, partiellement reçue). */
    public const AWAITING_STATUSES = [2, 3];

    private const DRAFT = 1;
    private const CANCELED = 5;

    /**
     * Enregistre l'AR sur des lignes de la commande.
     *
     * @param  array<int, array{id:int, confirmed_delivery_date?:?string}>  $lines
     * @return Collection<int, PurchaseLines>  lignes mises à jour
     *
     * @throws ValidationException commande en cours ou annulée, ligne d'une autre commande
     */
    public function record(Purchases $purchase, array $lines, string $ackAt, ?string $reference = null): Collection
    {
        if (in_array((int) $purchase->statu, [self::DRAFT, self::CANCELED], true)) {
            throw ValidationException::withMessages([
                'purchase' => __('supplier_ack.not_ordered'),
            ]);
        }

        $ids = collect($lines)->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $models = PurchaseLines::query()
            ->where('purchases_id', $purchase->id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($models->count() !== $ids->count()) {
            throw ValidationException::withMessages([
                'lines' => __('supplier_ack.foreign_line'),
            ]);
        }

        $reference = $reference !== null && trim($reference) !== '' ? trim($reference) : null;

        return DB::transaction(function () use ($lines, $models, $ackAt, $reference) {
            $updated = collect();

            foreach ($lines as $entry) {
                $line = $models->get((int) $entry['id']);
                $confirmed = $entry['confirmed_delivery_date'] ?? null;

                // Champs hors $fillable : seul ce service les écrit. Passage par le modèle
                // pour qu'activitylog trace la date de référence qui change.
                $line->forceFill([
                    'supplier_ack_at'         => $ackAt,
                    'supplier_ack_reference'  => $reference,
                    'confirmed_delivery_date' => $confirmed ?: $line->delivery_date,
                ])->save();

                $updated->push($line);
            }

            return $updated;
        });
    }

    /** Date de référence des retards : confirmée par le fournisseur, sinon demandée. */
    public static function referenceDate(object $line): ?string
    {
        $date = $line->confirmed_delivery_date ?: $line->delivery_date;

        return $date ? Carbon::parse($date)->toDateString() : null;
    }

    /**
     * Écart en jours entre la date confirmée et la date demandée (positif = plus tard
     * que demandé). Null s'il manque l'une des deux.
     */
    public static function gapDays(object $line): ?int
    {
        if (! $line->confirmed_delivery_date || ! $line->delivery_date) {
            return null;
        }

        return (int) Carbon::parse($line->delivery_date)->startOfDay()
            ->diffInDays(Carbon::parse($line->confirmed_delivery_date)->startOfDay(), false);
    }

    public function overdueDays(): int
    {
        return max(0, (int) config('purchases.ack_overdue_days', 3));
    }

    /** Commandes passées depuis au moins N jours dont une ligne attend son AR. */
    public function overdueQuery(): Builder
    {
        $limit = now()->subDays($this->overdueDays());

        return Purchases::query()
            ->whereIn('purchases.statu', self::AWAITING_STATUSES)
            ->where(fn ($q) => $q
                ->where('purchases.ordered_at', '<=', $limit)
                ->orWhere(fn ($q) => $q->whereNull('purchases.ordered_at')->where('purchases.created_at', '<=', $limit)))
            ->whereHas('PurchaseLines', fn ($q) => $this->awaitingLines($q));
    }

    /** Nombre de commandes de la liste (badge du menu). */
    public function overdueCount(): int
    {
        return $this->overdueQuery()->count();
    }

    /**
     * Liste « sans AR » : commandes avec fournisseur, lignes sans AR et ancienneté.
     *
     * @return Collection<int, array>
     */
    public function overdueList(): Collection
    {
        return $this->overdueQuery()
            ->with('companie:id,code,label')
            ->withCount(['PurchaseLines as lines_count' => fn ($q) => $q->where('qty', '>', 0)])
            ->withCount(['PurchaseLines as awaiting_lines_count' => fn ($q) => $this->awaitingLines($q)])
            ->withMin(['PurchaseLines as next_delivery_date' => fn ($q) => $this->awaitingLines($q)], 'delivery_date')
            ->get()
            ->map(function (Purchases $purchase) {
                $since = $purchase->ordered_at ?? $purchase->created_at;

                return [
                    'id'                   => $purchase->id,
                    'code'                 => $purchase->code,
                    'label'                => $purchase->label,
                    'supplier'             => trim(($purchase->companie?->code ?? '') . ' - ' . ($purchase->companie?->label ?? ''), ' -'),
                    'statu'                => (int) $purchase->statu,
                    'ordered_at'           => $since,
                    'days'                 => (int) Carbon::parse($since)->startOfDay()->diffInDays(now()->startOfDay()),
                    'lines_count'          => (int) $purchase->lines_count,
                    'awaiting_lines_count' => (int) $purchase->awaiting_lines_count,
                    'next_delivery_date'   => $purchase->next_delivery_date,
                    'url'                  => route('purchases.show', $purchase->id),
                ];
            })
            ->sortByDesc('days')
            ->values();
    }

    /** Lignes qui attendent un AR : quantité non nulle, pas d'AR. */
    private function awaitingLines($query)
    {
        return $query->where('qty', '>', 0)->whereNull('supplier_ack_at');
    }
}
