<?php

namespace App\Services\Quality;

use App\Models\Quality\QualityDerogation;
use Illuminate\Support\Collection;

/**
 * Une pièce dont la dérogation externe attend la décision du client ne part pas.
 *
 * Une dérogation concerne une ligne de commande directement (`order_lines_id`) ou
 * par sa non-conformité (`quality_non_conformities.order_lines_id`). Tant qu'elle
 * est externe et ouverte (en cours, attente client), la ligne ne peut pas être
 * mise sur un BL. Validée ou annulée, elle ne bloque plus rien. Une dérogation
 * interne ne bloque jamais.
 *
 * Appelé à la création d'un BL depuis la commande et depuis l'écran « Demande de
 * BL » ; la facturation directe (BL automatique) n'est pas contrôlée, comme pour
 * le plafond d'encours.
 */
class DerogationDeliveryGuard
{
    /**
     * Dérogations qui empêchent de livrer ces lignes de commande.
     *
     * @param  array<int, int|string>  $orderLineIds
     * @return Collection<int, QualityDerogation>
     */
    public function blocking(array $orderLineIds): Collection
    {
        $orderLineIds = array_values(array_unique(array_map('intval', $orderLineIds)));

        if ($orderLineIds === []) {
            return collect();
        }

        return QualityDerogation::query()
            ->where('type', QualityDerogation::TYPE_EXTERNAL)
            ->whereIn('statu', QualityDerogation::OPEN)
            ->where(function ($query) use ($orderLineIds) {
                $query->whereIn('order_lines_id', $orderLineIds)
                    ->orWhereHas('QualityNonConformity', fn ($nc) => $nc->whereIn('order_lines_id', $orderLineIds));
            })
            ->orderBy('code')
            ->get(['id', 'code', 'label']);
    }

    /**
     * Message de refus, ou null si rien ne bloque.
     *
     * @param  array<int, int|string>  $orderLineIds
     */
    public function refusal(array $orderLineIds): ?string
    {
        $blocking = $this->blocking($orderLineIds);

        if ($blocking->isEmpty()) {
            return null;
        }

        return __('derogations.delivery_blocked', [
            'codes' => $blocking->pluck('code')->implode(', '),
        ]);
    }
}
