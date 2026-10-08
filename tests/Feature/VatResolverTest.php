<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Companies\Companies;
use App\Models\Products\Products;
use App\Models\Accounting\VatRegime;
use App\Models\Accounting\VatNature;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingVatSalesRule;
use App\Services\Accounting\VatResolver;
use App\Services\Accounting\VatRuleNotFoundException;

class VatResolverTest extends TestCase
{
    use RefreshDatabase;

    private function regime(string $code): VatRegime
    {
        return VatRegime::create(['code' => $code, 'label' => $code]);
    }

    private function nature(string $code): VatNature
    {
        return VatNature::create(['code' => $code, 'label' => $code]);
    }

    public function test_resolveSale_renvoie_la_regle_du_couple(): void
    {
        $regime = $this->regime('UE');
        $nature = $this->nature('fabrication');
        $vat    = AccountingVat::factory()->create(['rate' => 0, 'en16931_category' => 'K']);

        $rule = AccountingVatSalesRule::create([
            'vat_regime_id'      => $regime->id,
            'vat_nature_id'      => $nature->id,
            'accounting_vats_id' => $vat->id,
            'sales_account'      => '701000',
            'vat_account'        => null,
        ]);

        $client  = new Companies(['vat_regime_id' => $regime->id]);
        $product = new Products(['vat_nature_id' => $nature->id]);

        $resolved = app(VatResolver::class)->resolveSale($client, $product);

        $this->assertNotNull($resolved);
        $this->assertSame($rule->id, $resolved->id);
        $this->assertSame($vat->id, $resolved->accounting_vats_id);
    }

    public function test_resolveSale_bloque_si_couple_sans_regle(): void
    {
        $regime = $this->regime('EXPORT');
        $nature = $this->nature('prestation');

        $client  = new Companies(['vat_regime_id' => $regime->id]);
        $product = new Products(['vat_nature_id' => $nature->id]);

        $this->expectException(VatRuleNotFoundException::class);
        app(VatResolver::class)->resolveSale($client, $product);
    }

    public function test_resolveSale_retombe_sur_null_sans_regime(): void
    {
        $nature  = $this->nature('fabrication');
        $client  = new Companies(); // pas de régime
        $product = new Products(['vat_nature_id' => $nature->id]);

        $this->assertNull(app(VatResolver::class)->resolveSale($client, $product));
    }

    public function test_override_de_nature_au_niveau_ligne(): void
    {
        $regime = $this->regime('FR');
        $nature = $this->nature('outillage');
        $vat    = AccountingVat::factory()->create(['rate' => 20, 'en16931_category' => 'S']);

        AccountingVatSalesRule::create([
            'vat_regime_id'      => $regime->id,
            'vat_nature_id'      => $nature->id,
            'accounting_vats_id' => $vat->id,
            'sales_account'      => '707000',
            'vat_account'        => '445710',
        ]);

        $client = new Companies(['vat_regime_id' => $regime->id]);

        // Aucune nature sur l'article : l'override de ligne fournit la nature.
        $resolved = app(VatResolver::class)->resolveSale($client, null, $nature->id);

        $this->assertNotNull($resolved);
        $this->assertSame('707000', $resolved->sales_account);
    }
}
