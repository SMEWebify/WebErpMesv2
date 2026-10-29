<?php

namespace App\Services\Purchases;

/**
 * Résultat d'un contrôle de qualification fournisseur (SupplierQualification) :
 * fournisseurs non qualifiés concernés, et motif du passe-droit une fois accepté.
 */
class SupplierCheck
{
    /** Motif du passe-droit accepté par SupplierQualification::decide(). */
    public ?string $overrideReason = null;

    /**
     * @param array<int, array> $blocked état des fournisseurs non qualifiés, par id
     * @param string $context create | validate | supplier_change
     */
    public function __construct(public readonly array $blocked, public readonly string $context)
    {
    }

    public function blocks(): bool
    {
        return $this->blocked !== [];
    }
}
