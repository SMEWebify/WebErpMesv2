<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Planning\Status;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Commande d'achat et demande de prix créées depuis l'écran « Demande d'achat » :
 * elles doivent porter le contact et l'adresse par défaut du fournisseur, chacun
 * dans sa colonne.
 */
class PurchaseRequestDefaultContactTest extends TestCase
{
    use RefreshDatabase;

    private Companies $supplier;
    private CompaniesAddresses $address;
    private CompaniesContacts $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        AccountingVat::factory()->create(['default' => 1]);
        Status::create(['title' => 'Supplied', 'order' => 1]);
        Status::create(['title' => 'RFQ in progress', 'order' => 2]);

        // Les adresses d'autres tiers décalent les identifiants : l'adresse du
        // fournisseur n'a pas le même numéro que son contact, comme en exploitation.
        CompaniesAddresses::factory()->count(3)->create();

        $this->supplier = Companies::factory()->create();
        $this->address  = CompaniesAddresses::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);
        $this->contact  = CompaniesContacts::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);

        $this->assertNotSame($this->address->id, $this->contact->id);
    }

    public function test_purchase_order_keeps_default_contact_and_address(): void
    {
        $this->postJson(route('purchases.request.store'), [
            'document_type' => 'PU',
            'code'          => 'PU-TEST',
            'label'         => 'Commande matière',
            'companies_id'  => $this->supplier->id,
            'task_ids'      => [],
        ])->assertOk();

        $purchase = Purchases::where('code', 'PU-TEST')->firstOrFail();
        $this->assertSame($this->contact->id, (int) $purchase->companies_contacts_id);
        $this->assertSame($this->address->id, (int) $purchase->companies_addresses_id);
    }

    public function test_quotation_keeps_default_contact_and_address(): void
    {
        $this->postJson(route('purchases.request.store'), [
            'document_type'      => 'PQ',
            'code'               => 'PQ-TEST',
            'label'              => 'Consultation matière',
            'selected_companies' => [$this->supplier->id],
            'task_ids'           => [],
        ])->assertOk();

        $quotation = PurchasesQuotation::where('companies_id', $this->supplier->id)->firstOrFail();
        $this->assertSame($this->contact->id, (int) $quotation->companies_contacts_id);
        $this->assertSame($this->address->id, (int) $quotation->companies_addresses_id);
    }
}
