<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\SupplierQualificationOverride;
use App\Models\Companies\SupplierRating;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\User;
use App\Services\Purchases\SupplierQualification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Fournisseur qualifié obligatoire (option purchases.require_qualified_supplier) :
 * chaque chemin de création ou de validation d'une commande d'achat est refusé pour un
 * fournisseur non qualifié, le passe-droit est tracé, et l'option inactive ne change rien.
 */
class SupplierQualificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
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
        Permission::findOrCreate(SupplierQualification::OVERRIDE_PERMISSION);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        AccountingVat::factory()->create(['default' => 1]);
        foreach (['Open', 'Supplied', 'RFQ in progress'] as $order => $title) {
            Status::create(['title' => $title, 'order' => $order]);
        }

        [$this->supplier, $this->contact, $this->address] = $this->makeSupplier();

        config(['purchases.require_qualified_supplier' => true]);
    }

    /** @return array{Companies, CompaniesContacts, CompaniesAddresses} */
    private function makeSupplier(): array
    {
        $supplier = Companies::factory()->create(['statu_supplier' => 2]);
        $contact = CompaniesContacts::factory()->create(['companies_id' => $supplier->id, 'default' => 1]);
        $address = CompaniesAddresses::factory()->create(['companies_id' => $supplier->id, 'default' => 1]);

        return [$supplier, $contact, $address];
    }

    private function evaluate(Companies $supplier, string $status, ?string $nextReview = null, ?string $createdAt = null): SupplierRating
    {
        $purchase = Purchases::factory()->create([
            'companies_id' => $supplier->id, 'statu' => 4,
            'companies_contacts_id' => $this->contact->id, 'companies_addresses_id' => $this->address->id,
        ]);
        $rating = SupplierRating::create([
            'purchases_id' => $purchase->id, 'companies_id' => $supplier->id, 'rating' => 4,
            'evaluation_status' => $status,
            'approved_at' => $status === 'approved' ? now() : null,
            'next_review_at' => $nextReview,
        ]);
        if ($createdAt) {
            DB::table('supplier_ratings')->where('id', $rating->id)->update(['created_at' => $createdAt]);
        }

        return $rating;
    }

    private function qualify(Companies $supplier): void
    {
        $this->evaluate($supplier, 'approved', now()->addYear()->toDateString());
    }

    private function storePayload(array $extra = []): array
    {
        return $extra + [
            'code'                   => 'PU-' . uniqid(),
            'label'                  => 'Commande',
            'companies_id'           => $this->supplier->id,
            'companies_contacts_id'  => $this->contact->id,
            'companies_addresses_id' => $this->address->id,
        ];
    }

    private function draft(array $attributes = []): Purchases
    {
        return Purchases::factory()->create($attributes + [
            'companies_id' => $this->supplier->id, 'statu' => 1,
            'companies_contacts_id' => $this->contact->id, 'companies_addresses_id' => $this->address->id,
        ]);
    }

    private function quotation(): PurchasesQuotation
    {
        $quotation = PurchasesQuotation::create([
            'code' => 'DP-' . uniqid(), 'label' => 'DP', 'companies_id' => $this->supplier->id,
            'companies_contacts_id' => $this->contact->id, 'companies_addresses_id' => $this->address->id,
            'user_id' => $this->user->id,
        ]);
        PurchaseQuotationLines::create([
            'purchases_quotation_id' => $quotation->id, 'tasks_id' => 0, 'ordre' => 10,
            'label' => 'Tôle', 'qty_to_order' => 2, 'unit_price' => 10, 'total_price' => 20,
        ]);

        return $quotation;
    }

    // -------------------------------------------------------------------------
    // Règle
    // -------------------------------------------------------------------------

    public function test_the_rule_reads_the_latest_formal_evaluation(): void
    {
        $service = app(SupplierQualification::class);

        $this->assertSame(SupplierQualification::UNQUALIFIED, $service->stateOf($this->supplier->id)['status']);

        $this->evaluate($this->supplier, 'approved', null, now()->subMonths(2)->toDateTimeString());
        $this->assertSame(SupplierQualification::QUALIFIED, $service->stateOf($this->supplier->id)['status'], 'revue nulle = qualifié');

        $this->evaluate($this->supplier, 'under_review', null, now()->subMonth()->toDateTimeString());
        $this->assertSame(SupplierQualification::UNQUALIFIED, $service->stateOf($this->supplier->id)['status'], 'la dernière évaluation décide');

        $this->evaluate($this->supplier, 'approved', now()->subDay()->toDateString());
        $this->assertSame(SupplierQualification::REVIEW_OVERDUE, $service->stateOf($this->supplier->id)['status']);

        $this->evaluate($this->supplier, 'approved', now()->toDateString(), now()->addMinute()->toDateTimeString());
        $this->assertSame(SupplierQualification::QUALIFIED, $service->stateOf($this->supplier->id)['status'], 'revue le jour même : encore valide');
    }

    public function test_a_receipt_rating_is_not_an_evaluation(): void
    {
        $this->evaluate($this->supplier, 'approved', now()->addYear()->toDateString(), now()->subDay()->toDateTimeString());
        $receipt = PurchaseReceipt::create([
            'code' => 'REC-1', 'label' => 'Réception', 'companies_id' => $this->supplier->id, 'user_id' => $this->user->id,
        ]);
        $rating = $this->evaluate($this->supplier, 'pending');
        $rating->update(['purchase_receipt_id' => $receipt->id]);

        $this->assertSame(SupplierQualification::QUALIFIED, app(SupplierQualification::class)->stateOf($this->supplier->id)['status']);
    }

    public function test_states_of_many_suppliers_take_one_query(): void
    {
        $others = collect(range(1, 5))->map(fn () => $this->makeSupplier()[0]);
        $others->each(fn ($s) => $this->qualify($s));

        DB::enableQueryLog();
        $states = app(SupplierQualification::class)->states($others->pluck('id')->push($this->supplier->id));
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(SupplierQualification::UNQUALIFIED, $states[$this->supplier->id]['status']);
        $this->assertSame(SupplierQualification::QUALIFIED, $states[$others[0]->id]['status']);
    }

    // -------------------------------------------------------------------------
    // Option inactive
    // -------------------------------------------------------------------------

    public function test_inactive_option_keeps_the_current_behaviour(): void
    {
        config(['purchases.require_qualified_supplier' => false]);

        $this->postJson(route('purchases.json.store'), $this->storePayload())->assertCreated();

        $purchase = Purchases::latest('id')->first();
        $this->postJson(route('purchases.json.statu', $purchase->id), ['statu' => 2])->assertOk();
        $this->assertSame(2, (int) $purchase->fresh()->statu);

        $this->getJson(route('purchases.json.select-data'))
            ->assertOk()
            ->assertJsonPath('supplier_qualification.enabled', false)
            ->assertJsonPath('suppliers.0.qualification', null);

        $this->assertSame(0, SupplierQualificationOverride::count());
    }

    // -------------------------------------------------------------------------
    // Chemins de création
    // -------------------------------------------------------------------------

    public function test_react_creation_is_refused_for_an_unqualified_supplier(): void
    {
        $payload = $this->storePayload();

        $this->postJson(route('purchases.json.store'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('supplier_qualification.suppliers.0.id', $this->supplier->id)
            ->assertJsonPath('supplier_qualification.suppliers.0.status', SupplierQualification::UNQUALIFIED)
            ->assertJsonPath('supplier_qualification.can_override', false);

        $this->assertDatabaseMissing('purchases', ['code' => $payload['code']]);
    }

    public function test_react_creation_is_refused_when_the_review_is_overdue(): void
    {
        $this->evaluate($this->supplier, 'approved', now()->subWeek()->toDateString());

        $this->postJson(route('purchases.json.store'), $this->storePayload())
            ->assertStatus(422)
            ->assertJsonPath('supplier_qualification.suppliers.0.status', SupplierQualification::REVIEW_OVERDUE);
    }

    public function test_a_qualified_supplier_passes(): void
    {
        $this->qualify($this->supplier);

        $this->postJson(route('purchases.json.store'), $this->storePayload())->assertCreated();
        $this->getJson(route('purchases.json.select-data'))
            ->assertJsonPath('supplier_qualification.enabled', true)
            ->assertJsonFragment(['id' => $this->supplier->id, 'qualification' => SupplierQualification::QUALIFIED]);
    }

    public function test_bank_purchase_form_is_refused(): void
    {
        $this->post(route('purchases.store'), ['code' => 'PU-BANK', 'label' => 'Banque', 'companies_id' => $this->supplier->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('purchases', ['code' => 'PU-BANK']);
    }

    public function test_purchase_request_order_is_refused_but_a_quotation_request_is_allowed(): void
    {
        $this->postJson(route('purchases.request.store'), [
            'document_type' => 'PU', 'code' => 'PU-REQ', 'label' => 'Sous-traitance', 'companies_id' => $this->supplier->id,
        ])->assertStatus(422)->assertJsonStructure(['supplier_qualification' => ['suppliers']]);
        $this->assertDatabaseMissing('purchases', ['code' => 'PU-REQ']);

        $this->postJson(route('purchases.request.store'), [
            'document_type' => 'PQ', 'code' => 'PQ-REQ', 'label' => 'Consultation', 'selected_companies' => [$this->supplier->id],
        ])->assertOk();
        $this->assertDatabaseHas('purchases_quotations', ['companies_id' => $this->supplier->id]);
    }

    public function test_quotation_conversion_is_refused(): void
    {
        $quotation = $this->quotation();

        $this->post(route('purchases.orders.store', ['id' => $quotation->id]), [
            'PurchaseQuotationLine' => $quotation->PurchaseQuotationLines->pluck('id')->all(),
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, Purchases::where('companies_id', $this->supplier->id)->where('statu', 1)->count());
    }

    public function test_quotation_conversion_with_override_is_traced(): void
    {
        $this->user->givePermissionTo(SupplierQualification::OVERRIDE_PERMISSION);
        $quotation = $this->quotation();

        $this->post(route('purchases.orders.store', ['id' => $quotation->id]), [
            'PurchaseQuotationLine'    => $quotation->PurchaseQuotationLines->pluck('id')->all(),
            'supplier_override'        => 1,
            'supplier_override_reason' => 'Seul fournisseur de cette nuance, audit planifié',
        ])->assertRedirect()->assertSessionHas('success');

        $purchase = Purchases::where('companies_id', $this->supplier->id)->where('statu', 1)->firstOrFail();
        $this->assertDatabaseHas('supplier_qualification_overrides', [
            'companies_id' => $this->supplier->id, 'document_type' => $purchase->getMorphClass(),
            'document_id' => $purchase->id, 'context' => 'create', 'qualification_status' => 'unqualified',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_reorder_checks_every_supplier_before_creating_anything(): void
    {
        [$second] = $this->makeSupplier();
        $this->qualify($second);
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();
        $product = Products::factory()->create(['purchased' => 1]);
        $lines = [
            ['product_id' => $product->id, 'companies_id' => $second->id, 'qty' => 2, 'price' => 5],
            ['product_id' => $product->id, 'companies_id' => $this->supplier->id, 'qty' => 3, 'price' => 5],
        ];

        $this->postJson(route('purchases.reorder.store'), ['lines' => $lines])
            ->assertStatus(422)
            ->assertJsonCount(1, 'supplier_qualification.suppliers')
            ->assertJsonPath('supplier_qualification.suppliers.0.id', $this->supplier->id);
        $this->assertSame(0, Purchases::whereIn('companies_id', [$second->id, $this->supplier->id])->where('statu', 1)->count());

        $this->user->givePermissionTo(SupplierQualification::OVERRIDE_PERMISSION);
        $this->postJson(route('purchases.reorder.store'), [
            'lines' => $lines, 'supplier_override' => 1, 'supplier_override_reason' => 'Rupture, dépannage',
        ])->assertCreated()->assertJsonCount(2, 'purchases');

        // Seule la commande du fournisseur non qualifié porte un passe-droit
        $this->assertSame(1, SupplierQualificationOverride::count());
        $this->assertSame($this->supplier->id, (int) SupplierQualificationOverride::first()->companies_id);
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    public function test_validation_of_a_draft_is_refused(): void
    {
        $purchase = $this->draft();

        $this->postJson(route('purchases.json.statu', $purchase->id), ['statu' => 2])
            ->assertStatus(422)
            ->assertJsonPath('supplier_qualification.texts.message', __('supplier_qualification.blocked_validate'));
        $this->assertSame(1, (int) $purchase->fresh()->statu);

        // Annuler reste possible
        $this->postJson(route('purchases.json.statu', $purchase->id), ['statu' => 5])->assertOk();
    }

    public function test_validation_override_is_traced(): void
    {
        $this->user->givePermissionTo(SupplierQualification::OVERRIDE_PERMISSION);
        $purchase = $this->draft();

        $this->postJson(route('purchases.json.statu', $purchase->id), [
            'statu' => 2, 'supplier_override' => 1, 'supplier_override_reason' => 'Commande urgente validée par la direction',
        ])->assertOk();

        $this->assertSame(2, (int) $purchase->fresh()->statu);
        $this->assertDatabaseHas('supplier_qualification_overrides', [
            'document_id' => $purchase->id, 'context' => 'validate', 'reason' => 'Commande urgente validée par la direction',
        ]);
    }

    public function test_an_override_given_at_creation_also_covers_validation(): void
    {
        $this->user->givePermissionTo(SupplierQualification::OVERRIDE_PERMISSION);
        $this->postJson(route('purchases.json.store'), $this->storePayload([
            'supplier_override' => 1, 'supplier_override_reason' => 'Fournisseur imposé par le client',
        ]))->assertCreated();

        $purchase = Purchases::latest('id')->first();
        $this->postJson(route('purchases.json.statu', $purchase->id), ['statu' => 2])->assertOk();
        $this->assertSame(1, SupplierQualificationOverride::count());
    }

    public function test_supplier_change_on_a_validated_order_is_refused(): void
    {
        [$qualified, $qContact, $qAddress] = $this->makeSupplier();
        $this->qualify($qualified);
        $purchase = $this->draft(['companies_id' => $qualified->id, 'statu' => 2,
            'companies_contacts_id' => $qContact->id, 'companies_addresses_id' => $qAddress->id]);

        $this->post(route('purchase.update', ['id' => $purchase->id]), [
            'id' => $purchase->id, 'label' => 'Changement', 'companies_id' => $this->supplier->id,
            'companies_contacts_id' => $this->contact->id, 'companies_addresses_id' => $this->address->id,
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame($qualified->id, (int) $purchase->fresh()->companies_id);
    }

    // -------------------------------------------------------------------------
    // Passe-droit
    // -------------------------------------------------------------------------

    public function test_override_requires_the_permission(): void
    {
        $this->postJson(route('purchases.json.store'), $this->storePayload([
            'supplier_override' => 1, 'supplier_override_reason' => 'Je force',
        ]))->assertForbidden();

        $this->assertSame(0, SupplierQualificationOverride::count());
    }

    public function test_override_requires_a_reason(): void
    {
        $this->user->givePermissionTo(SupplierQualification::OVERRIDE_PERMISSION);

        $this->postJson(route('purchases.json.store'), $this->storePayload(['supplier_override' => 1, 'supplier_override_reason' => ' ']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('supplier_override_reason');
    }

    public function test_override_creates_the_order_and_is_traced(): void
    {
        $this->user->givePermissionTo(SupplierQualification::OVERRIDE_PERMISSION);
        $this->evaluate($this->supplier, 'approved', now()->subMonth()->toDateString());
        $payload = $this->storePayload([
            'supplier_override' => 1, 'supplier_override_reason' => 'Requalification en cours, audit le 15',
        ]);

        $this->postJson(route('purchases.json.store'), $payload)->assertCreated();

        $purchase = Purchases::where('code', $payload['code'])->firstOrFail();
        $override = SupplierQualificationOverride::firstOrFail();
        $this->assertSame($purchase->id, (int) $override->document_id);
        $this->assertSame('create', $override->context);
        $this->assertSame('review_overdue', $override->qualification_status);
        $this->assertSame('approved', $override->evaluation_status);
        $this->assertSame(now()->subMonth()->toDateString(), $override->next_review_at->toDateString());
        $this->assertSame('Requalification en cours, audit le 15', $override->reason);
    }

    // -------------------------------------------------------------------------
    // Affichage et export
    // -------------------------------------------------------------------------

    public function test_company_and_order_sheets_show_the_qualification(): void
    {
        $purchase = $this->draft();
        $this->user->givePermissionTo(SupplierQualification::OVERRIDE_PERMISSION);
        $this->postJson(route('purchases.json.statu', $purchase->id), [
            'statu' => 2, 'supplier_override' => 1, 'supplier_override_reason' => 'Dépannage du 29',
        ])->assertOk();

        $level = ob_get_level();
        $order = $this->get(route('purchases.show', ['id' => $purchase->id]));
        // purchases-show ouvre @section('right-sidebar') sans le refermer : tampon refermé ici
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $order->assertOk()->assertSee(__('supplier_qualification.status.unqualified'))->assertSee('Dépannage du 29');

        // Fiche société : companies.show ne se rend pas sous SQLite (FLOOR d'un indicateur
        // existant) ; la carte qu'elle inclut est rendue avec les mêmes données.
        $card = view('include.supplier-qualification-card', [
            'qualification' => [
                'status'    => SupplierQualification::UNQUALIFIED,
                'detail'    => __('supplier_qualification.detail.no_evaluation'),
                'overrides' => SupplierQualificationOverride::with(['user:id,name', 'document'])->where('companies_id', $this->supplier->id)->get(),
            ],
            'showDocument' => true,
        ])->render();
        $this->assertStringContainsString('Dépannage du 29', $card);
        $this->assertStringContainsString($purchase->code, $card);
        $this->assertStringContainsString(__('supplier_qualification.context.validate'), $card);
    }

    public function test_sheets_show_nothing_when_the_option_is_inactive(): void
    {
        config(['purchases.require_qualified_supplier' => false]);
        $purchase = $this->draft();

        $level = ob_get_level();
        $order = $this->get(route('purchases.show', ['id' => $purchase->id]));
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $order->assertOk()->assertDontSee(__('supplier_qualification.status.unqualified'));
    }

    public function test_export_lists_qualified_suppliers_only(): void
    {
        [$qualified] = $this->makeSupplier();
        $this->qualify($qualified);

        $csv = $this->get(route('purchases.suppliers.qualified.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString($qualified->label, $csv);
        $this->assertStringNotContainsString($this->supplier->label, $csv);

        $all = $this->get(route('purchases.suppliers.qualified.export', ['scope' => 'all']))->streamedContent();
        $this->assertStringContainsString($this->supplier->label, $all);
        $this->assertStringContainsString(__('supplier_qualification.status.unqualified'), $all);
    }
}
