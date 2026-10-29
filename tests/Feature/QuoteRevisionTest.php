<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Documents\DocumentPdfService;
use App\Services\PdfThemeResolver;
use App\Services\Quotes\QuoteDuplicator;
use App\Services\Quotes\QuoteRevisionRefused;
use App\Services\Quotes\QuoteRevisions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Indices (A, B, C…) et variantes d'un devis : chaîne d'indices, variantes
 * ouvertes en parallèle, clôture des autres quand l'une est gagnée, et PDF.
 * Le PDF d'un devis sans indice reste couvert par SalesPdfLegacyRenderingTest.
 */
class QuoteRevisionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private QuoteRevisions $revisions;

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
        app()->instance('Factory', Factory::create(['name' => 'Métallerie', 'curency' => 'EUR', 'pdf_header_font_color' => '#ddd']));

        $this->revisions = app(QuoteRevisions::class);
    }

    private function quote(array $attributes = []): Quotes
    {
        $quote = Quotes::factory()->create($attributes + ['code' => 'QT-0001', 'statu' => 1, 'user_id' => $this->user->id]);

        QuoteLines::create([
            'quotes_id' => $quote->id, 'ordre' => 1, 'code' => 'GC-01', 'label' => 'Garde-corps',
            'qty' => 2, 'selling_price' => 100, 'discount' => 0, 'statu' => 1,
            'methods_units_id' => MethodsUnits::factory()->create()->id,
            'accounting_vats_id' => AccountingVat::factory()->create(['rate' => 20])->id,
        ]);

        return $quote;
    }

    // ---------------------------------------------------------------------
    // Indices
    // ---------------------------------------------------------------------

    public function test_a_chain_of_revisions_keeps_the_base_code_and_the_reference_quote(): void
    {
        $a = $this->quote();

        $b = $this->revisions->newRevision($a);
        $c = $this->revisions->newRevision($b);

        $this->assertSame(['QT-0001-B', 'B', $a->id], [$b->code, $b->revision, $b->parent_quote_id]);
        $this->assertSame(['QT-0001-C', 'C', $a->id], [$c->code, $c->revision, $c->parent_quote_id]);
        $this->assertNull($c->variant_label);

        // L'indice précédent est remplacé, lignes comprises ; le dernier reste ouvert.
        $this->assertSame(6, (int) $a->fresh()->statu);
        $this->assertSame(6, (int) $b->fresh()->statu);
        $this->assertSame(1, (int) $c->fresh()->statu);
        $this->assertSame(6, (int) QuoteLines::where('quotes_id', $b->id)->value('statu'));

        // Contenu recopié par QuoteDuplicator.
        $this->assertSame(['Garde-corps'], $c->QuoteLines()->pluck('label')->all());
        $this->assertSame(1, (int) QuoteLines::where('quotes_id', $c->id)->value('statu'));

        $this->assertSame(['QT-0001', 'QT-0001-B', 'QT-0001-C'], $this->revisions->family($b)->pluck('code')->all());
    }

    public function test_only_the_latest_revision_can_be_revised(): void
    {
        $a = $this->quote();
        $b = $this->revisions->newRevision($a);

        $this->assertSame($b->id, $this->revisions->newerRevision($a->fresh())?->id);
        $this->assertNull($this->revisions->newerRevision($b));

        $this->expectException(QuoteRevisionRefused::class);
        $this->revisions->newRevision($a->fresh());
    }

    public function test_a_won_or_lost_quote_keeps_its_status_when_revised(): void
    {
        $a = $this->quote(['statu' => 4]);

        $this->revisions->newRevision($a);

        $this->assertSame(4, (int) $a->fresh()->statu);
    }

    public function test_revision_route_redirects_to_the_new_revision(): void
    {
        $a = $this->quote(['statu' => 2]);

        $response = $this->post(route('quotes.revision', ['id' => $a->id]));

        $b = Quotes::where('code', 'QT-0001-B')->firstOrFail();
        $response->assertRedirect(route('quotes.show', ['id' => $b->id]));
        $this->assertSame(6, (int) $a->fresh()->statu);

        // Une seconde demande sur l'indice remplacé est refusée sans rien créer.
        $this->post(route('quotes.revision', ['id' => $a->id]))
            ->assertRedirect(route('quotes.show', ['id' => $a->id]))
            ->assertSessionHas('error');
        $this->assertSame(2, Quotes::count());
    }

    // ---------------------------------------------------------------------
    // Variantes
    // ---------------------------------------------------------------------

    public function test_variants_share_the_reference_quote_and_stay_open(): void
    {
        $a  = $this->quote();
        $v1 = $this->revisions->newVariant($a, 'Acier galvanisé');
        $v2 = $this->revisions->newVariant($a, '  Inox 316  ');

        $this->assertSame(['QT-0001-V1', $a->id, 'Acier galvanisé', null], [$v1->code, $v1->parent_quote_id, $v1->variant_label, $v1->revision]);
        $this->assertSame(['QT-0001-V2', $a->id, 'Inox 316'], [$v2->code, $v2->parent_quote_id, $v2->variant_label]);

        $this->assertSame(1, (int) $a->fresh()->statu);
        $this->assertSame(1, (int) $v1->fresh()->statu);
        $this->assertSame(1, (int) $v2->fresh()->statu);

        // Une variante d'une variante ou d'un indice reste rattachée au référent.
        $b  = $this->revisions->newRevision($a);
        $v3 = $this->revisions->newVariant($b, 'Thermolaqué');
        $this->assertSame(['QT-0001-V3', $a->id], [$v3->code, $v3->parent_quote_id]);
    }

    public function test_a_variant_has_its_own_chain_of_revisions(): void
    {
        $a  = $this->quote();
        $v1 = $this->revisions->newVariant($a, 'Galva');

        $v1b = $this->revisions->newRevision($v1);

        $this->assertSame(['QT-0001-V1-B', 'B', 'Galva', $a->id], [$v1b->code, $v1b->revision, $v1b->variant_label, $v1b->parent_quote_id]);
        $this->assertSame(6, (int) $v1->fresh()->statu);
        // La ligne du référent n'est pas touchée et peut toujours être révisée.
        $this->assertSame(1, (int) $a->fresh()->statu);
        $this->assertSame('QT-0001-B', $this->revisions->newRevision($a->fresh())->code);
    }

    public function test_variant_route_requires_a_label(): void
    {
        $a = $this->quote();

        $this->post(route('quotes.variant', ['id' => $a->id]), ['variant_label' => ''])
            ->assertSessionHasErrors('variant_label');

        $this->post(route('quotes.variant', ['id' => $a->id]), ['variant_label' => 'Option B'])
            ->assertRedirect();
        $this->assertSame('Option B', Quotes::where('code', 'QT-0001-V1')->value('variant_label'));
    }

    public function test_winning_a_variant_proposes_and_closes_the_other_open_quotes(): void
    {
        $a  = $this->quote(['statu' => 2]);
        $v1 = $this->revisions->newVariant($a, 'Galva');
        $v2 = $this->revisions->newVariant($a, 'Inox');
        $v2->update(['statu' => 4]);

        // Refusé tant que le devis n'est pas gagné.
        $this->post(route('quotes.family.close', ['id' => $v1->id]))->assertSessionHas('error');
        $this->assertSame(2, (int) $a->fresh()->statu);

        $v1->update(['statu' => 3]);
        $this->assertSame([$a->id], $this->revisions->openSiblings($v1->fresh())->pluck('id')->all());

        $this->post(route('quotes.family.close', ['id' => $v1->id]))->assertSessionHas('success');

        $this->assertSame(5, (int) $a->fresh()->statu);   // ouvert → fermé
        $this->assertSame(3, (int) $v1->fresh()->statu);  // gagné inchangé
        $this->assertSame(4, (int) $v2->fresh()->statu);  // perdu inchangé
        $this->assertSame(5, (int) QuoteLines::where('quotes_id', $a->id)->value('statu'));
    }

    public function test_the_quote_page_shows_the_history(): void
    {
        $a  = $this->quote();
        $b  = $this->revisions->newRevision($a);
        $v1 = $this->revisions->newVariant($b, 'Galva');

        $this->get(route('quotes.show', ['id' => $b->id]))
            ->assertOk()
            ->assertSee(__('quote_revisions.history'))
            ->assertSee('QT-0001-V1')
            ->assertSee(__('quote_revisions.variant_short', ['label' => 'Galva']));
    }

    // ---------------------------------------------------------------------
    // Copies ordinaires
    // ---------------------------------------------------------------------

    public function test_a_plain_duplicate_or_template_does_not_join_the_family(): void
    {
        $a  = $this->quote();
        $b  = $this->revisions->newRevision($a);
        $v1 = $this->revisions->newVariant($b, 'Galva');

        $copy     = app(QuoteDuplicator::class)->duplicate($v1);
        $template = app(QuoteDuplicator::class)->saveAsTemplate($v1, 'Trame');

        foreach ([$copy, $template] as $quote) {
            $this->assertNull($quote->parent_quote_id);
            $this->assertNull($quote->revision);
            $this->assertNull($quote->variant_label);
        }

        $this->expectException(QuoteRevisionRefused::class);
        $this->revisions->newRevision($template);
    }

    // ---------------------------------------------------------------------
    // PDF
    // ---------------------------------------------------------------------

    public function test_revision_and_variant_are_printed_only_when_set(): void
    {
        $a  = $this->quote();
        $b  = $this->revisions->newRevision($a);
        $v1 = $this->revisions->newVariant($a, 'Galva');

        // Vue standard : une vue print/custom déployée sur la machine (non versionnée)
        // ne connaît pas la légende.
        $this->app->instance(PdfThemeResolver::class, new class(config()) extends PdfThemeResolver {
            public function resolve(string $baseViewKey, ?Factory $factory = null): string
            {
                return $baseViewKey;
            }
        });
        $pdf = app(DocumentPdfService::class);

        $this->assertStringNotContainsString(__('quote_revisions.pdf_revision', ['revision' => 'A']), $pdf->html($a->fresh()));
        $this->assertNull($a->fresh()->printRevisionCaption());

        $this->assertStringContainsString('<div>' . e(__('quote_revisions.pdf_revision', ['revision' => 'B'])) . '</div>', $pdf->html($b->fresh()));
        $this->assertStringContainsString('<div>' . e(__('quote_revisions.pdf_variant', ['label' => 'Galva'])) . '</div>', $pdf->html($v1->fresh()));
        $this->assertStringContainsString(e(__('quote_revisions.pdf_revision', ['revision' => 'B'])), $pdf->html($b->fresh(), 'long'));
    }
}
