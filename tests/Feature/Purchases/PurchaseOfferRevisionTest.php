<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Planning\Status;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\PurchaseRfqGroup;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\User;
use App\Services\Files\FileableRegistry;
use App\Services\Files\FileRole;
use App\Services\Purchases\PurchaseOfferRevisions;
use App\Services\PurchaseCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\TestCase;

/**
 * Offres fournisseurs indicées (PurchaseOfferRevisions) : chaîne d'indices,
 * seul le dernier se commande, la commande garde référence et indice.
 */
class PurchaseOfferRevisionTest extends TestCase
{
    use RefreshDatabase;

    private Companies $supplier;
    private CompaniesContacts $contact;
    private CompaniesAddresses $address;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Métallerie Dupont', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));

        AccountingVat::factory()->create(['default' => 1]);
        foreach (['Open', 'Supplied', 'RFQ in progress'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }

        $this->supplier = Companies::factory()->create();
        $this->contact = CompaniesContacts::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);
        $this->address = CompaniesAddresses::factory()->create(['companies_id' => $this->supplier->id, 'default' => 1]);
    }

    private function quotation(array $attributes = []): PurchasesQuotation
    {
        $quotation = PurchasesQuotation::create($attributes + [
            'code' => 'DP-1',
            'label' => 'Tôles',
            'companies_id' => $this->supplier->id,
            'companies_contacts_id' => $this->contact->id,
            'companies_addresses_id' => $this->address->id,
            'user_id' => auth()->id(),
            'supplier_offer_reference' => 'OF-12',
            'supplier_offer_revision' => 'A',
            'supplier_offer_date' => '2026-11-02',
        ]);

        PurchaseQuotationLines::create([
            'purchases_quotation_id' => $quotation->id, 'tasks_id' => 0, 'ordre' => 10,
            'label' => 'Tôle DC01', 'qty_to_order' => 4, 'unit_price' => 20, 'total_price' => 80,
        ]);

        return $quotation;
    }

    public function test_revisions_form_a_flat_chain_and_supersede_the_previous_one(): void
    {
        $root = $this->quotation();

        $response = $this->postJson(route('purchases.quotation.api.revision', ['quotation' => $root->id]))
            ->assertCreated();

        $second = PurchasesQuotation::where('code', 'DP-1-2')->firstOrFail();
        $response->assertJson(['redirect' => route('purchases.quotations.show', ['id' => $second->id])]);
        $this->assertSame($root->id, (int) $second->parent_purchase_quotation_id);
        $this->assertSame('B', $second->supplier_offer_revision);
        $this->assertSame('OF-12', $second->supplier_offer_reference);
        $this->assertNull($second->supplier_offer_date, 'la date est celle de la nouvelle offre, à saisir');
        $this->assertSame(1, (int) $second->statu);
        $this->assertCount(1, $second->PurchaseQuotationLines);
        $this->assertEquals(0, $second->PurchaseQuotationLines->first()->qty_accepted);
        $this->assertNotNull($root->fresh()->superseded_at);
        $this->assertNull($second->superseded_at);

        // Indice saisi par l'utilisateur, toujours rattaché au référent (famille à plat)
        $third = app(PurchaseOfferRevisions::class)->newRevision($second, 'C1');
        $this->assertSame('DP-1-3', $third->code);
        $this->assertSame($root->id, (int) $third->parent_purchase_quotation_id);
        $this->assertSame('C1', $third->supplier_offer_revision);
        $this->assertNotNull($second->fresh()->superseded_at);

        $family = app(PurchaseOfferRevisions::class)->family($root);
        $this->assertSame(['DP-1', 'DP-1-2', 'DP-1-3'], $family->pluck('code')->all());
        $this->assertEquals(80, $family->last()->lines_total);
        $this->assertSame($third->id, app(PurchaseOfferRevisions::class)->latest($root)->id);
    }

    public function test_a_superseded_or_ordered_quotation_cannot_be_revised(): void
    {
        $root = $this->quotation();
        app(PurchaseOfferRevisions::class)->newRevision($root);

        $this->postJson(route('purchases.quotation.api.revision', ['quotation' => $root->id]))
            ->assertStatus(409)
            ->assertJsonFragment(['message' => __('purchase_offers.refused.superseded', ['code' => 'DP-1-2'])]);

        $ordered = $this->quotation(['code' => 'DP-9', 'statu' => 6]);
        $this->postJson(route('purchases.quotation.api.revision', ['quotation' => $ordered->id]))
            ->assertStatus(409);

        $this->assertSame(3, PurchasesQuotation::count());
    }

    public function test_only_the_latest_revision_turns_into_a_purchase_order(): void
    {
        $root = $this->quotation();
        $latest = app(PurchaseOfferRevisions::class)->newRevision($root);

        $this->post(route('purchases.orders.store', ['id' => $root->id]), [
            'PurchaseQuotationLine' => [$root->PurchaseQuotationLines->first()->id],
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertSame(0, Purchases::count());

        $this->post(route('purchases.orders.store', ['id' => $latest->id]), [
            'PurchaseQuotationLine' => [$latest->PurchaseQuotationLines->first()->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $purchase = Purchases::sole();
        $this->assertSame('OF-12', $purchase->supplier_offer_reference);
        $this->assertSame('B', $purchase->supplier_offer_revision);
    }

    public function test_a_superseded_revision_can_no_longer_be_edited(): void
    {
        $root = $this->quotation();
        app(PurchaseOfferRevisions::class)->newRevision($root);
        $line = $root->PurchaseQuotationLines->first();

        $this->patchJson(route('purchases.quotation.api.lines.update', ['quotation' => $root->id, 'line' => $line->id]), [
            'ordre' => 10, 'label' => 'Modifiée', 'qty_to_order' => 1, 'unit_price' => 1,
        ])->assertStatus(409);
        $this->postJson(route('purchases.quotation.api.lines.store', ['quotation' => $root->id]), [
            'ordre' => 20, 'label' => 'Nouvelle', 'qty_to_order' => 1, 'unit_price' => 1,
        ])->assertStatus(409);
        $this->patchJson(route('purchases.quotation.api.update', ['quotation' => $root->id]), [
            'label' => 'Renommée',
        ])->assertStatus(409);

        $this->assertSame('Tôle DC01', $line->fresh()->label);
        $this->assertSame('Tôles', $root->fresh()->label);
    }

    public function test_the_offer_fields_are_saved_from_the_screen(): void
    {
        $quotation = $this->quotation(['supplier_offer_reference' => null, 'supplier_offer_revision' => null]);

        $this->patchJson(route('purchases.quotation.api.update', ['quotation' => $quotation->id]), [
            'label' => 'Tôles',
            'supplier_offer_reference' => 'DEV-2026-118',
            'supplier_offer_revision' => '2',
            'supplier_offer_date' => '2026-11-10',
            'supplier_offer_validity' => '2026-12-10',
        ])->assertOk();

        $quotation->refresh();
        $this->assertSame('DEV-2026-118', $quotation->supplier_offer_reference);
        $this->assertSame('2', $quotation->supplier_offer_revision);
        $this->assertSame('2026-12-10', $quotation->supplier_offer_validity->format('Y-m-d'));
    }

    public function test_the_rfq_comparison_only_shows_latest_revisions(): void
    {
        $group = PurchaseRfqGroup::create(['code' => 'RFQ-1', 'label' => 'Tôles', 'user_id' => auth()->id()]);
        $root = $this->quotation(['rfq_group_id' => $group->id]);
        $latest = app(PurchaseOfferRevisions::class)->newRevision($root);

        $this->get(route('purchases.quotations.compare', ['group' => $group->id]))
            ->assertOk()
            ->assertViewHas('quotations', fn ($quotations) => $quotations->pluck('id')->all() === [$latest->id]);
    }

    public function test_the_purchase_order_prints_the_offer_only_when_filled(): void
    {
        $purchase = Purchases::factory()->create([
            'code' => 'PU-0001', 'label' => 'Tôles', 'statu' => 1,
            'companies_id' => $this->supplier->id, 'companies_contacts_id' => $this->contact->id,
            'companies_addresses_id' => $this->address->id,
        ]);

        $this->assertStringNotContainsString(__('purchase_offers.print_caption'), $this->renderStandardPdf($purchase->fresh()));
        $this->assertSame('', $purchase->printSupplierOfferCaption());

        $purchase->fill(['supplier_offer_reference' => 'OF-12', 'supplier_offer_revision' => 'B'])->save();

        $this->assertStringContainsString(
            __('purchase_offers.print_caption') . ' : OF-12 · ' . __('purchase_offers.print_revision', ['revision' => 'B']),
            $this->renderStandardPdf($purchase->fresh())
        );
    }

    // Vue de base (une vue print/custom de l'instance locale ne reprend pas la légende)
    private function renderStandardPdf(Purchases $Document): string
    {
        $calculator          = new PurchaseCalculatorService($Document);
        $typeDocumentName    = __('general_content.purchase_order_trans_key');
        $Factory             = app('Factory');
        $formattedTotalPrice = Number::currency($calculator->getTotalPrice(), 'EUR', config('app.locale'));
        $formattedSubPrice   = Number::currency($calculator->getSubTotal(), 'EUR', config('app.locale'));
        $vatPrice            = $calculator->getVatTotal();
        $normalizeCurrency   = fn ($value) => (string) $value;
        $image               = null;
        $customCss           = null;
        $printRows           = null;
        $Document->Lines     = $Document->PurchaseLines;

        return view('print/pdf-purchases', compact(
            'typeDocumentName', 'Document', 'Factory', 'formattedTotalPrice',
            'formattedSubPrice', 'vatPrice', 'image', 'customCss', 'normalizeCurrency', 'printRows'
        ))->render();
    }

    public function test_the_supplier_offer_can_be_filed_in_the_quotation_documents(): void
    {
        $this->assertSame(PurchasesQuotation::class, FileableRegistry::classFor('purchase-quotation'));
        $this->assertContains(FileRole::SUPPLIER_OFFER, FileRole::all());
        $this->assertContains(FileRole::SUPPLIER_OFFER, FileRole::forPurchaseQuotations());
        $this->assertNotSame(
            FileRole::transKey(FileRole::SUPPLIER_OFFER),
            __(FileRole::transKey(FileRole::SUPPLIER_OFFER))
        );
    }
}
