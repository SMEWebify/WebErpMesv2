<?php

namespace App\Services\Purchases;

use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\PurchasesQuotation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Offres fournisseurs indicées d'une demande de prix.
 *
 * Une famille est à plat : la demande de prix référente
 * (parent_purchase_quotation_id null) et tous ses indices, qui pointent vers
 * elle. Chaque nouvelle offre recopie la demande et ses lignes ; la précédente
 * est « remplacée » (superseded_at) et ne se transforme plus en commande.
 *
 * L'indice imprimé est celui du fournisseur (supplier_offer_revision, texte
 * libre) ; le code de la copie suit le rang dans la famille : DP-1, DP-1-2, DP-1-3.
 */
class PurchaseOfferRevisions
{
    /** Une commande est déjà sortie de la demande de prix (en partie ou en totalité). */
    public const STATU_ORDERED = [5, 6];

    /**
     * Raison du refus d'un nouvel indice (clé de purchase_offers.refused.*), null si possible.
     */
    public function revisionRefusal(PurchasesQuotation $quotation): ?string
    {
        if ($quotation->isSuperseded()) {
            return 'superseded';
        }

        // Les quantités déjà commandées ne suivraient pas la copie : la nouvelle offre
        // permettrait de commander une seconde fois.
        if (in_array((int) $quotation->statu, self::STATU_ORDERED, true)) {
            return 'ordered';
        }

        return null;
    }

    /**
     * Nouvelle offre (indice suivant) : copie de la demande et de ses lignes,
     * rattachée au référent ; la demande d'origine passe « remplacée ».
     *
     * @throws \DomainException refus (message traduit)
     */
    public function newRevision(PurchasesQuotation $source, ?string $supplierRevision = null): PurchasesQuotation
    {
        if ($reason = $this->revisionRefusal($source)) {
            throw new \DomainException(__('purchase_offers.refused.' . $reason, ['code' => $this->latest($source)->code]));
        }

        return DB::transaction(function () use ($source, $supplierRevision) {
            // Verrou du référent : deux clics simultanés ne créent pas deux indices.
            PurchasesQuotation::whereKey($this->rootId($source))->lockForUpdate()->first();
            $source->refresh();
            if ($reason = $this->revisionRefusal($source)) {
                throw new \DomainException(__('purchase_offers.refused.' . $reason, ['code' => $this->latest($source)->code]));
            }

            $root = $this->root($source);
            $rank = $this->family($source)->count();
            do {
                $code = $root->code . '-' . (++$rank);
            } while (PurchasesQuotation::withTrashed()->where('code', $code)->exists());

            $supplierRevision = trim((string) $supplierRevision);
            if ($supplierRevision === '' && filled($source->supplier_offer_revision)) {
                $supplierRevision = str_increment(trim($source->supplier_offer_revision));
            }

            $revision = $source->replicate([
                'code', 'statu', 'user_id', 'parent_purchase_quotation_id', 'superseded_at',
                'supplier_offer_revision', 'supplier_offer_date', 'supplier_offer_validity',
            ]);
            $revision->code = $code;
            // Nouvelle offre à saisir : lignes modifiables
            $revision->statu = 1;
            $revision->user_id = Auth::id() ?? $source->user_id;
            $revision->parent_purchase_quotation_id = $root->id;
            $revision->supplier_offer_revision = $supplierRevision !== '' ? mb_substr($supplierRevision, 0, 20) : null;
            $revision->save();

            foreach ($source->PurchaseQuotationLines()->get() as $line) {
                /** @var PurchaseQuotationLines $copy */
                $copy = $line->replicate(['qty_accepted', 'canceled_qty', 'retained_at']);
                $copy->purchases_quotation_id = $revision->id;
                $copy->save();
            }

            $source->forceFill(['superseded_at' => now()])->save();

            return $revision;
        });
    }

    /**
     * Référent puis indices, dans l'ordre de création.
     */
    public function family(PurchasesQuotation $quotation): Collection
    {
        $rootId = $this->rootId($quotation);

        return PurchasesQuotation::query()
            ->where(fn ($q) => $q->whereKey($rootId)->orWhere('parent_purchase_quotation_id', $rootId))
            ->withSum('PurchaseQuotationLines as lines_total', 'total_price')
            ->orderBy('id')
            ->get();
    }

    /** Dernier indice de la famille : le seul qui se commande. */
    public function latest(PurchasesQuotation $quotation): PurchasesQuotation
    {
        return $this->family($quotation)->whereNull('superseded_at')->last()
            ?? $this->family($quotation)->last()
            ?? $quotation;
    }

    private function rootId(PurchasesQuotation $quotation): int
    {
        return (int) ($quotation->parent_purchase_quotation_id ?: $quotation->id);
    }

    private function root(PurchasesQuotation $quotation): PurchasesQuotation
    {
        return $quotation->parent_purchase_quotation_id
            ? PurchasesQuotation::withTrashed()->findOrFail($quotation->parent_purchase_quotation_id)
            : $quotation;
    }
}
