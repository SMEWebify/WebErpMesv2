<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Accounting\AccountingVat;

/**
 * Catégorie EN 16931 d'un code de TVA : valeur explicite si renseignée, sinon
 * repli rétrocompatible sur l'ancienne règle (S si taux > 0, Z sinon).
 */
class VatCategoryTest extends TestCase
{
    public function test_categorie_explicite_prime(): void
    {
        $vat = new AccountingVat(['rate' => 0, 'en16931_category' => 'K']);
        $this->assertSame('K', $vat->resolvedEn16931Category());
    }

    public function test_repli_S_si_taux_positif(): void
    {
        $vat = new AccountingVat(['rate' => 20]);
        $this->assertSame('S', $vat->resolvedEn16931Category());
    }

    public function test_repli_Z_si_taux_nul(): void
    {
        $vat = new AccountingVat(['rate' => 0]);
        $this->assertSame('Z', $vat->resolvedEn16931Category());
    }
}
