<?php

namespace App\Services\Accounting;

use App\Models\Companies\Companies;
use App\Models\Products\Products;
use App\Models\Accounting\AccountingVatSalesRule;
use App\Models\Accounting\AccountingVatPurchaseRule;

/**
 * Porte unique de résolution de la TVA par la matrice (régime du tiers × nature
 * de la ligne). Appelée là où une ligne prend son code de TVA et là où une
 * écriture prend ses comptes.
 *
 * Contrat de repli (rétrocompatibilité) :
 *   - régime du tiers OU nature de la ligne non renseigné → retourne null :
 *     l'appelant retombe sur le code de TVA par défaut (comportement actuel) ;
 *   - régime ET nature renseignés mais AUCUNE règle → VatRuleNotFoundException :
 *     matrice incomplète, on refuse plutôt que de poser une TVA fausse ;
 *   - sinon → la règle trouvée.
 */
class VatResolver
{
    public function resolveSale(?Companies $client, ?Products $product = null, ?int $natureId = null): ?AccountingVatSalesRule
    {
        [$regimeId, $natureId] = $this->keys($client, $product, $natureId);
        if ($regimeId === null || $natureId === null) {
            return null;
        }

        $rule = AccountingVatSalesRule::where('vat_regime_id', $regimeId)
            ->where('vat_nature_id', $natureId)
            ->first();

        if (! $rule) {
            throw new VatRuleNotFoundException(
                __('vat.rule_not_found_sale', ['regime' => $regimeId, 'nature' => $natureId])
            );
        }

        return $rule;
    }

    public function resolvePurchase(?Companies $supplier, ?Products $product = null, ?int $natureId = null): ?AccountingVatPurchaseRule
    {
        [$regimeId, $natureId] = $this->keys($supplier, $product, $natureId);
        if ($regimeId === null || $natureId === null) {
            return null;
        }

        $rule = AccountingVatPurchaseRule::where('vat_regime_id', $regimeId)
            ->where('vat_nature_id', $natureId)
            ->first();

        if (! $rule) {
            throw new VatRuleNotFoundException(
                __('vat.rule_not_found_purchase', ['regime' => $regimeId, 'nature' => $natureId])
            );
        }

        return $rule;
    }

    /**
     * Code de TVA à poser par défaut sur une ligne de vente (ou null → l'appelant
     * garde son défaut habituel).
     */
    public function defaultSaleVatId(?Companies $client, ?Products $product = null, ?int $natureId = null): ?int
    {
        return $this->resolveSale($client, $product, $natureId)?->accounting_vats_id;
    }

    public function defaultPurchaseVatId(?Companies $supplier, ?Products $product = null, ?int $natureId = null): ?int
    {
        return $this->resolvePurchase($supplier, $product, $natureId)?->accounting_vats_id;
    }

    /**
     * @return array{0: ?int, 1: ?int} [regimeId, natureId]
     */
    private function keys(?Companies $party, ?Products $product, ?int $natureId): array
    {
        return [
            $party?->vat_regime_id,
            $natureId ?? $product?->vat_nature_id,
        ];
    }
}
