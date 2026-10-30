<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\User;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Documents\DocumentPdfService;
use App\Services\Quotes\QuoteMargin;
use App\Services\Quotes\QuoteMarginRefused;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Marge du devis (information interne) : coût de revient des lignes sur toute
 * la profondeur des sous-ensembles, marge globale refusée sur un devis converti,
 * et aucun effet sur le PDF client. Le PDF d'un devis existant reste couvert par
 * SalesPdfLegacyRenderingTest (références jamais régénérées).
 */
class QuoteMarginTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private int $unitId;
    private int $vatId;
    private int $serviceId;

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

        $this->unitId = MethodsUnits::factory()->create()->id;
        $this->vatId  = AccountingVat::factory()->create(['rate' => 20])->id;
        // Le calcul des temps par poste de la fiche suppose un poste sur chaque opération.
        $this->serviceId = MethodsServices::factory()->create()->id;
    }

    private function line(Quotes $quote, int $ordre, array $attributes = []): QuoteLines
    {
        return QuoteLines::create($attributes + [
            'quotes_id' => $quote->id, 'ordre' => $ordre, 'code' => 'L-' . $ordre, 'label' => 'Ligne ' . $ordre,
            'qty' => 1, 'selling_price' => 100, 'discount' => 0, 'statu' => 1,
            'methods_units_id' => $this->unitId, 'accounting_vats_id' => $this->vatId,
        ]);
    }

    private function task(array $attributes): Task
    {
        return Task::create($attributes + ['label' => 'Opération', 'ordre' => 10, 'status_id' => 1, 'unit_price' => 0, 'methods_services_id' => $this->serviceId]);
    }

    /**
     * Ligne de 10 pièces à 50 € :
     *  - opération 2 €/pièce ; sous-traitance 3 € sur 5 pièces sur 10 (part 0,5) → 1,50 € ;
     *  - nomenclature 4 € × 2 → 8 € ;
     *  - sous-ensemble × 2 : opération 1 € + composant 0,50 € × 4 + sous-ensemble enfant × 3
     *    (opération 2 €) = 1 + 2 + 6 = 9 € → 18 €.
     * Coût unitaire 29,50 €, coût 295 €, vente 500 €.
     */
    private function costedQuote(): array
    {
        $quote = Quotes::factory()->create(['code' => 'QT-0001', 'statu' => 1, 'user_id' => $this->user->id]);

        $line = $this->line($quote, 1, ['qty' => 10, 'selling_price' => 50]);
        $this->task(['quote_lines_id' => $line->id, 'type' => 1, 'unit_cost' => 2]);
        $this->task(['quote_lines_id' => $line->id, 'type' => 7, 'unit_cost' => 3, 'planned_qty' => 5]);
        $this->task(['quote_lines_id' => $line->id, 'type' => 2, 'unit_cost' => 4, 'qty' => 2]);

        $root  = SubAssembly::create(['ordre' => 1, 'quote_lines_id' => $line->id, 'child_id' => 1, 'qty' => 2, 'unit_price' => 0]);
        $this->task(['sub_assembly_id' => $root->id, 'type' => 1, 'unit_cost' => 1]);
        $this->task(['sub_assembly_id' => $root->id, 'type' => 3, 'unit_cost' => 0.5, 'qty' => 4]);
        $child = SubAssembly::create(['ordre' => 1, 'sub_assembly_id' => $root->id, 'child_id' => 1, 'qty' => 3, 'unit_price' => 0]);
        // Une opération de sous-ensemble qui porte aussi la clé de la ligne n'est comptée qu'une fois.
        $this->task(['sub_assembly_id' => $child->id, 'quote_lines_id' => $line->id, 'type' => 1, 'unit_cost' => 2]);

        // Lignes de présentation : ignorées. Article sans gamme : vendu, sans coût.
        $this->line($quote, 2, ['line_type' => 'section', 'label' => 'Section']);
        $bare = $this->line($quote, 3, ['qty' => 1, 'selling_price' => 100]);
        $this->line($quote, 4, ['line_type' => 'subtotal', 'label' => '']);
        $this->line($quote, 5, ['line_type' => 'text', 'label' => 'Texte']);

        return [$quote, $line, $bare];
    }

    public function test_the_cost_price_covers_operations_bill_of_materials_and_nested_sub_assemblies(): void
    {
        [$quote, $line, $bare] = $this->costedQuote();

        $margin = app(QuoteMargin::class)->forQuote($quote->fresh());

        $this->assertEqualsWithDelta(29.5, $margin['lines'][$line->id]['unit_cost'], 0.0001);
        $this->assertSame(295.0, $margin['lines'][$line->id]['cost_total']);
        $this->assertSame(500.0, $margin['lines'][$line->id]['sale_total']);
        $this->assertSame(205.0, $margin['lines'][$line->id]['margin']);
        $this->assertSame(69.49, $margin['lines'][$line->id]['margin_rate']);

        $this->assertFalse($margin['lines'][$bare->id]['has_cost']);
        $this->assertNull($margin['lines'][$bare->id]['margin_rate']);
        $this->assertCount(2, $margin['lines'], 'Les lignes de section, sous-total et texte sont ignorées.');

        $this->assertSame(295.0, $margin['totals']['cost_total']);
        $this->assertSame(600.0, $margin['totals']['sale_total']);
        $this->assertSame(305.0, $margin['totals']['margin']);
        $this->assertSame(103.39, $margin['totals']['margin_rate']);
        $this->assertSame(1, $margin['totals']['lines_without_cost']);
    }

    public function test_the_cost_is_read_with_a_constant_number_of_queries(): void
    {
        [$quote] = $this->costedQuote();
        $lines   = QuoteLines::where('quotes_id', $quote->id)->get();

        DB::enableQueryLog();
        app(QuoteMargin::class)->unitCosts($lines);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Opérations de ligne, racines, deux niveaux d'enfants (le dernier vide), opérations de sous-ensembles.
        $this->assertSame(5, $count);
    }

    public function test_a_line_under_the_threshold_is_flagged(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id]);
        $loss  = $this->line($quote, 1, ['qty' => 1, 'selling_price' => 80]);
        $this->task(['quote_lines_id' => $loss->id, 'type' => 1, 'unit_cost' => 100]);
        $thin  = $this->line($quote, 2, ['qty' => 1, 'selling_price' => 110]);
        $this->task(['quote_lines_id' => $thin->id, 'type' => 1, 'unit_cost' => 100]);

        $margin = app(QuoteMargin::class)->forQuote($quote->fresh());
        $this->assertTrue($margin['lines'][$loss->id]['below_threshold'], 'Seuil par défaut 0 % : marge négative signalée.');
        $this->assertFalse($margin['lines'][$thin->id]['below_threshold']);

        config(['quotes.margin_alert_threshold' => 15]);
        $margin = app(QuoteMargin::class)->forQuote($quote->fresh());
        $this->assertTrue($margin['lines'][$thin->id]['below_threshold']);
    }

    public function test_the_lines_json_carries_the_margin_and_the_threshold(): void
    {
        [$quote, $line] = $this->costedQuote();

        $response = $this->getJson(route('quotes.lines.json.for-quote', ['quoteId' => $quote->id]))->assertOk();

        $this->assertSame(0, (int) $response->json('margin_threshold'));
        $lines = collect($response->json('lines'))->keyBy('id');
        $this->assertEquals(69.49, $lines[$line->id]['margin']['margin_rate']);
        $this->assertNull($lines->firstWhere('line_type', 'section')['margin']);
    }

    public function test_the_quote_page_shows_the_internal_margin_card(): void
    {
        [$quote] = $this->costedQuote();

        $this->get(route('quotes.show', ['id' => $quote->id]))
            ->assertOk()
            ->assertSee(__('quote_margin.title'))
            ->assertSee(__('quote_margin.apply_title'));
    }

    // ---------------------------------------------------------------------
    // Marge globale
    // ---------------------------------------------------------------------

    public function test_an_overall_margin_recalculates_prices_from_the_cost(): void
    {
        [$quote, $line, $bare] = $this->costedQuote();
        $line->update(['discount' => 10, 'use_calculated_price' => true]);

        $this->post(route('quotes.margin.apply', ['id' => $quote->id]), ['margin_rate' => 20])
            ->assertRedirect(route('quotes.show', ['id' => $quote->id]))
            ->assertSessionHas('success');

        $line->refresh();
        // 29,50 × 1,20 / 0,90 : la remise de la ligne est compensée.
        $this->assertEquals(39.33, (float) $line->getRawOriginal('selling_price'));
        $this->assertFalse((bool) $line->use_calculated_price);
        $this->assertEqualsWithDelta(20.0, app(QuoteMargin::class)->forQuote($quote->fresh())['lines'][$line->id]['margin_rate'], 0.05);

        // Ligne sans coût : prix inchangé.
        $this->assertEquals(100.0, (float) $bare->fresh()->getRawOriginal('selling_price'));
    }

    public function test_an_overall_margin_is_refused_on_a_converted_quote_and_never_touches_the_order(): void
    {
        [$quote, $line] = $this->costedQuote();
        // Converti puis rouvert à la main : le statut ne suffit pas, la conversion fait foi.
        $order = Orders::factory()->create(['statu' => 1, 'type' => 1]);
        $orderLine = OrderLines::create([
            'orders_id' => $order->id, 'quote_lines_id' => $line->id, 'ordre' => 1, 'code' => 'L-1', 'label' => 'Ligne 1',
            'qty' => 10, 'delivered_qty' => 0, 'delivered_remaining_qty' => 10, 'invoiced_qty' => 0, 'invoiced_remaining_qty' => 10,
            'selling_price' => 50, 'discount' => 0, 'methods_units_id' => $this->unitId, 'accounting_vats_id' => $this->vatId,
        ]);

        $this->post(route('quotes.margin.apply', ['id' => $quote->id]), ['margin_rate' => 50])
            ->assertRedirect(route('quotes.show', ['id' => $quote->id]))
            ->assertSessionHas('error', __('quote_margin.refused_converted'));

        $this->assertEquals(50.0, (float) $line->fresh()->getRawOriginal('selling_price'));
        $this->assertEquals(50.0, (float) $orderLine->fresh()->getRawOriginal('selling_price'));

        $this->expectException(QuoteMarginRefused::class);
        app(QuoteMargin::class)->apply($quote->fresh(), 50);
    }

    public function test_an_overall_margin_is_refused_on_a_quote_that_is_no_longer_open(): void
    {
        [$quote, $line] = $this->costedQuote();
        $quote->update(['statu' => 2]);

        $this->post(route('quotes.margin.apply', ['id' => $quote->id]), ['margin_rate' => 50])
            ->assertSessionHas('error', __('quote_margin.refused_status'));

        $this->assertEquals(50.0, (float) $line->fresh()->getRawOriginal('selling_price'));
    }

    // ---------------------------------------------------------------------
    // PDF
    // ---------------------------------------------------------------------

    public function test_the_margin_is_never_printed_and_leaves_the_pdf_unchanged(): void
    {
        [$quote] = $this->costedQuote();
        $pdf = app(DocumentPdfService::class);

        $before = $pdf->html($quote->fresh());
        $this->get(route('quotes.show', ['id' => $quote->id]))->assertOk();
        $this->getJson(route('quotes.lines.json.for-quote', ['quoteId' => $quote->id]))->assertOk();
        $after = $pdf->html($quote->fresh());

        $this->assertSame($before, $after);
        foreach (['title', 'cost_total', 'margin_rate', 'markup_rate', 'internal'] as $key) {
            $this->assertStringNotContainsString(__('quote_margin.' . $key), $after);
        }
        $this->assertStringNotContainsString('295,00', $after, 'Le coût de revient n\'apparaît pas sur le PDF.');
    }
}
