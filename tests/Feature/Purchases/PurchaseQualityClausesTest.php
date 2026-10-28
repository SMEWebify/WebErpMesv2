<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseOrderQualityClause;
use App\Models\Purchases\PurchaseQualityClause;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Services\Documents\DocumentPdfService;
use App\Services\Purchases\PurchaseQualityClauses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Clauses qualité des commandes d'achat : règles de correspondance, criticité
 * reprise de l'article, copie figée à la sortie de « en cours », impression.
 */
class PurchaseQualityClausesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private PurchaseQualityClauses $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create(['name' => 'Usine', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd'])->fresh());

        AccountingVat::create(['code' => 'TVA20', 'label' => 'TVA 20 %', 'rate' => 20, 'default' => 1]);
        MethodsUnits::create(['code' => 'U', 'label' => 'Unité', 'type' => 1, 'default' => 1]);

        $this->service = app(PurchaseQualityClauses::class);
    }

    public function test_a_rule_applies_when_all_its_criteria_match_a_line(): void
    {
        $this->clause('CERT-31', ['certificate_type' => '3.1']);
        $this->clause('RCCM-N1', ['construction_code' => 'RCC-M', 'nuclear_level' => 'N1']);
        $this->clause('CERT-32', ['certificate_type' => '3.2']);

        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1', 'construction_code' => 'RCC-M', 'nuclear_level' => 'N2']);

        $this->assertSame(['CERT-31'], $this->service->forOrder($purchase)->pluck('code')->all());

        $this->line($purchase, ['nuclear_level' => 'N1', 'construction_code' => 'RCC-M']);

        $this->assertSame(['CERT-31', 'RCCM-N1'], $this->service->forOrder($purchase)->pluck('code')->all());
    }

    public function test_an_inactive_clause_and_a_rule_without_criteria_apply_to_nothing(): void
    {
        $this->clause('OFF', ['certificate_type' => '3.1'], ['active' => false]);
        $this->clause('EMPTY', []);
        PurchaseQualityClause::where('code', 'EMPTY')->first()->rules()->create([]);

        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1']);
        $this->line($purchase, []);

        $this->assertTrue($this->service->forOrder($purchase)->isEmpty());
    }

    public function test_a_new_line_takes_the_criticality_of_its_product(): void
    {
        $product = $this->product([
            'certificate_type' => '3.2', 'construction_code' => 'RCC-MX', 'nuclear_level' => 'N1',
        ]);
        $purchase = $this->purchase();

        $line = $this->line($purchase, ['product_id' => $product->id]);

        $this->assertSame(['3.2', 'RCC-MX', 'N1'], [$line->certificate_type, $line->construction_code, $line->nuclear_level]);
    }

    public function test_the_screen_can_override_or_clear_the_product_criticality(): void
    {
        $product  = $this->product(['certificate_type' => '3.2', 'nuclear_level' => 'N1']);
        $purchase = $this->purchase();

        $this->postJson(route('purchases.lines.json.store', ['purchaseId' => $purchase->id]), [
            'ordre' => 1, 'label' => 'Bride', 'qty' => 2, 'selling_price' => 10, 'discount' => 0,
            'product_id' => $product->id,
            'certificate_type' => '3.1', 'construction_code' => '', 'nuclear_level' => '',
        ])->assertCreated()->assertJsonPath('line.certificate_type', '3.1');

        $line = PurchaseLines::firstOrFail();
        $this->assertSame('3.1', $line->certificate_type);
        $this->assertNull($line->nuclear_level);

        $this->putJson(route('purchases.lines.json.update', ['purchaseId' => $purchase->id, 'id' => $line->id]), [
            'ordre' => 1, 'label' => 'Bride', 'qty' => 2, 'selling_price' => 10, 'discount' => 0,
            'nuclear_level' => 'N2',
        ])->assertOk();

        $line->refresh();
        $this->assertSame('3.1', $line->certificate_type, 'Un champ absent de la requête n\'est pas modifié.');
        $this->assertSame('N2', $line->nuclear_level);
    }

    public function test_an_unknown_criticality_value_is_refused(): void
    {
        $purchase = $this->purchase();

        $this->postJson(route('purchases.lines.json.store', ['purchaseId' => $purchase->id]), [
            'ordre' => 1, 'label' => 'Bride', 'qty' => 2, 'selling_price' => 10, 'discount' => 0,
            'certificate_type' => '9.9',
        ])->assertUnprocessable()->assertJsonValidationErrors('certificate_type');
    }

    public function test_clauses_are_frozen_when_the_order_leaves_in_progress(): void
    {
        $clause   = $this->clause('CERT-31', ['certificate_type' => '3.1'], ['text_fr' => 'Certificat 3.1 exigé.']);
        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1']);

        // En cours : rien n'est écrit, le texte suit le référentiel
        $this->assertSame(0, PurchaseOrderQualityClause::count());

        $this->postJson(route('purchases.json.statu', $purchase->id), ['statu' => 2])->assertOk();

        $purchase->refresh();
        $this->assertNotNull($purchase->quality_clauses_frozen_at);
        $this->assertSame(1, PurchaseOrderQualityClause::count());

        // Le référentiel change après l'émission : la commande garde sa copie
        $clause->update(['text_fr' => 'Nouveau texte.', 'active' => false]);
        $this->clause('CERT-31-BIS', ['certificate_type' => '3.1']);

        $clauses = $this->service->forOrder($purchase->fresh());
        $this->assertSame(['CERT-31'], $clauses->pluck('code')->all());
        $this->assertSame('Certificat 3.1 exigé.', $clauses->first()->text_fr);

        $html = app(DocumentPdfService::class)->html($purchase->fresh());
        $this->assertStringContainsString('Certificat 3.1 exigé.', $html);
        $this->assertStringNotContainsString('Nouveau texte.', $html);
    }

    public function test_a_later_status_change_does_not_refreeze(): void
    {
        $clause   = $this->clause('CERT-31', ['certificate_type' => '3.1'], ['text_fr' => 'Texte émis.']);
        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1']);

        $purchase->update(['statu' => 2]);
        $clause->update(['text_fr' => 'Texte modifié.']);
        $purchase->fresh()->update(['statu' => 3]);

        $this->assertSame('Texte émis.', PurchaseOrderQualityClause::sole()->text_fr);
    }

    public function test_reopening_the_order_recomputes_its_clauses(): void
    {
        $clause   = $this->clause('CERT-31', ['certificate_type' => '3.1'], ['text_fr' => 'Texte émis.']);
        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1']);

        $purchase->update(['statu' => 2]);
        $clause->update(['text_fr' => 'Texte révisé.']);

        $purchase->fresh()->update(['statu' => 1]);

        $purchase->refresh();
        $this->assertNull($purchase->quality_clauses_frozen_at);
        $this->assertSame(0, PurchaseOrderQualityClause::count());
        $this->assertSame('Texte révisé.', $this->service->forOrder($purchase)->first()->text_fr);
    }

    public function test_an_order_issued_before_the_feature_stays_without_clause(): void
    {
        $this->clause('CERT-31', ['certificate_type' => '3.1']);
        $purchase = $this->purchase(['statu' => 2]);
        $this->line($purchase, ['certificate_type' => '3.1']);

        // État posé par la migration sur les commandes déjà émises
        DB::table('purchases')->where('id', $purchase->id)->update(['quality_clauses_frozen_at' => now()]);
        DB::table('purchase_order_quality_clauses')->delete();

        $this->assertTrue($this->service->forOrder($purchase->fresh())->isEmpty());
    }

    public function test_an_order_moved_out_of_in_progress_without_the_model_is_frozen_on_read(): void
    {
        $this->clause('CERT-31', ['certificate_type' => '3.1']);
        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1']);
        DB::table('purchases')->where('id', $purchase->id)->update(['statu' => 4]);

        $this->assertSame(['CERT-31'], $this->service->forOrder($purchase->fresh())->pluck('code')->all());
        $this->assertNotNull($purchase->fresh()->quality_clauses_frozen_at);
        $this->assertSame(1, PurchaseOrderQualityClause::count());
    }

    public function test_the_pdf_prints_the_clauses_in_the_document_language_at_the_end(): void
    {
        $this->clause('CERT-31', ['certificate_type' => '3.1'], ['text_fr' => 'Certificat 3.1 exigé.', 'text_en' => '3.1 certificate required.']);
        $purchase = $this->purchase();
        $this->line($purchase, ['label' => 'Bride RCC-M', 'certificate_type' => '3.1']);

        $fr = app(DocumentPdfService::class)->html($purchase->fresh(), null, 'fr');
        $en = app(DocumentPdfService::class)->html($purchase->fresh(), null, 'en');

        $this->assertStringContainsString('Certificat 3.1 exigé.', $fr);
        $this->assertStringContainsString('3.1 certificate required.', $en);
        $this->assertStringNotContainsString('Certificat 3.1 exigé.', $en);
        $this->assertLessThan(strripos($fr, '</main>'), strpos($fr, 'Certificat 3.1 exigé.'));
        $this->assertStringContainsString('Bride RCC-M', substr($fr, strpos($fr, __('purchase_quality.pdf_title', [], 'fr'))));
    }

    public function test_the_download_route_renders_a_pdf_with_clauses(): void
    {
        $this->clause('CERT-31', ['certificate_type' => '3.1']);
        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1']);

        $this->get(route('pdf.purchase', ['Document' => $purchase->id]))->assertOk();
    }

    public function test_the_order_page_shows_its_clauses(): void
    {
        $this->clause('CERT-31', ['certificate_type' => '3.1'], ['text_fr' => 'Certificat 3.1 exigé.']);
        $purchase = $this->purchase();
        $this->line($purchase, ['certificate_type' => '3.1']);

        $level = ob_get_level();

        $response = $this->get(route('purchases.show', ['id' => $purchase->id]));

        // purchases-show ouvre @section('right-sidebar') sans le refermer (antérieur aux
        // clauses) : Blade laisse un tampon de sortie ouvert, refermé ici.
        while (ob_get_level() > $level) {
            ob_end_clean();
        }

        $response->assertOk()->assertSee('Certificat 3.1 exigé.');
    }

    public function test_the_product_form_saves_its_criticality(): void
    {
        $product = $this->product(['nuclear_level' => 'N2']);
        Permission::findOrCreate('products-menu');
        $this->user->givePermissionTo('products-menu');

        // La fiche article elle-même n'est pas rendue sous SQLite (DATEDIFF) : on vérifie
        // son enregistrement.
        $form = ['id' => $product->id, 'label' => $product->label,
            'methods_services_id' => $product->methods_services_id, 'methods_families_id' => $product->methods_families_id, 'methods_units_id' => $product->methods_units_id];

        $this->post(route('products.update', ['id' => $product->id]), $form + ['certificate_type' => '3.1', 'nuclear_level' => ''])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(['3.1', null], [$product->fresh()->certificate_type, $product->fresh()->nuclear_level]);

        $this->post(route('products.update', ['id' => $product->id]), $form + ['certificate_type' => '4.0'])
            ->assertSessionHasErrors('certificate_type');
    }

    public function test_the_reference_screen_requires_its_permission(): void
    {
        $this->get(route('purchases.quality-clauses'))->assertForbidden();

        Permission::firstOrCreate(['name' => 'purchase-quality-clauses-manage', 'guard_name' => 'web']);
        $this->user->givePermissionTo('purchase-quality-clauses-manage');

        $this->get(route('purchases.quality-clauses'))->assertOk();

        $this->post(route('purchases.quality-clauses.create'), [
            'code' => 'CERT-31', 'label' => 'Certificat 3.1', 'text_fr' => 'Certificat 3.1 exigé.',
        ])->assertRedirect(route('purchases.quality-clauses'));

        $clause = PurchaseQualityClause::where('code', 'CERT-31')->sole();

        $this->post(route('purchases.quality-clauses.rules.store', ['id' => $clause->id]), [])
            ->assertSessionHas('error');
        $this->assertSame(0, $clause->rules()->count());

        $this->post(route('purchases.quality-clauses.rules.store', ['id' => $clause->id]), ['certificate_type' => '3.1'])
            ->assertSessionHas('success');
        $this->assertSame(1, $clause->rules()->count());
    }

    private function clause(string $code, array $rule, array $attributes = []): PurchaseQualityClause
    {
        $clause = PurchaseQualityClause::create($attributes + [
            'code' => $code, 'label' => $code, 'text_fr' => "Texte {$code}", 'active' => true,
        ]);

        if ($rule !== []) {
            $clause->rules()->create($rule);
        }

        return $clause;
    }

    private function product(array $attributes): Products
    {
        MethodsFamilies::firstOrCreate(['code' => 'FAM'], ['label' => 'Famille', 'methods_services_id' => MethodsUnits::first()->id]);

        return Products::factory()->create($attributes);
    }

    private function purchase(array $attributes = []): Purchases
    {
        return Purchases::factory()->create($attributes + ['statu' => 1]);
    }

    private function line(Purchases $purchase, array $attributes): PurchaseLines
    {
        $vat  = AccountingVat::first();
        $unit = MethodsUnits::first();

        return PurchaseLines::create($attributes + [
            'purchases_id' => $purchase->id, 'tasks_id' => 0, 'ordre' => 1,
            'label' => 'Ligne', 'qty' => 1, 'selling_price' => 10, 'discount' => 0,
            'unit_price_after_discount' => 10, 'total_selling_price' => 10,
            'methods_units_id' => $unit->id, 'accounting_vats_id' => $vat->id,
        ]);
    }
}
