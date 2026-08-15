<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Models\User;
use App\Services\Integrations\Pdp\PdpIncomingInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Statut fournisseur des sociétés qui nous facturent.
 *
 * Une même société peut être client et fournisseur : le client à qui l'on vend
 * des pièces peut nous facturer de la sous-traitance. Les deux statuts sont
 * indépendants dans WEM, et `statu_supplier = 2` conditionne la présence de la
 * société dans tout le module achats.
 */
class PdpIncomingSupplierStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_who_invoices_us_becomes_a_supplier(): void
    {
        // Sans cette activation, la facture d'achat créée porterait une société
        // que les listes de sélection et les KPI achats excluent : elle
        // existerait sans apparaître nulle part.
        $company = Companies::factory()->create([
            'label'          => 'Tricatel',
            'statu_customer' => 2,
            'statu_supplier' => 1,
        ]);

        $this->convert($company);

        $this->assertSame(2, (int) $company->fresh()->statu_supplier);
    }

    public function test_the_customer_status_is_left_untouched(): void
    {
        $company = Companies::factory()->create([
            'label'          => 'Tricatel',
            'statu_customer' => 2,
            'statu_supplier' => 1,
        ]);

        $this->convert($company);

        $this->assertSame(2, (int) $company->fresh()->statu_customer);
    }

    public function test_an_established_supplier_is_left_as_is(): void
    {
        $company = Companies::factory()->create(['statu_supplier' => 2]);

        $this->convert($company);

        $this->assertSame(2, (int) $company->fresh()->statu_supplier);
    }

    private function convert(Companies $company): void
    {
        $incoming = PdpIncomingInvoice::create([
            'provider'            => 'superpdp',
            'external_id'         => '4242',
            'supplier_company_id' => $company->id,
            'seller_name'         => $company->label,
            'invoice_number'      => 'F-2026-001',
            'status'              => PdpIncomingInvoice::STATUS_RECEIVED,
        ]);

        $this->app->make(PdpIncomingInvoiceService::class)
            ->convertToPurchaseInvoice($incoming, User::factory()->create()->id);
    }
}
