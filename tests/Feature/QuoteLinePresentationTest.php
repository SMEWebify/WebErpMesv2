<?php

namespace Tests\Feature;

use App\Events\OrderLineUpdated;
use App\Listeners\CheckOrderDeliveredStatus;
use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Documents\SalesPrintLayout;
use App\Services\N2P\N2PPayloadBuilder;
use App\Services\OrderConfirmationService;
use App\Services\QuoteCalculatorService;
use App\Services\Quotes\QuoteDuplicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Number;
use Tests\TestCase;

/**
 * Lignes de présentation d'un devis : sections, sous-totaux, textes, articles
 * masqués et ouvrages au forfait — des totaux jusqu'à la facture.
 */
class QuoteLinePresentationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    private AccountingVat $vat20;
    private AccountingVat $vat10;
    private MethodsUnits $unit;
    private MethodsUnits $forfait;

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

        $this->vat20   = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
        $this->vat10   = AccountingVat::factory()->create(['rate' => 10, 'default' => 0]);
        $this->unit    = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
        $this->forfait = MethodsUnits::factory()->create(['code' => 'FORF', 'label' => 'Forfait', 'default' => 0]);
    }

    // ---------------------------------------------------------------------
    // Fixtures
    // ---------------------------------------------------------------------

    private function quote(array $attributes = []): Quotes
    {
        return Quotes::factory()->create(['statu' => 1, 'user_id' => $this->user->id] + $attributes);
    }

    private function article(Quotes $quote, int $ordre, float $qty, float $price, array $extra = []): QuoteLines
    {
        return QuoteLines::create(array_merge([
            'quotes_id' => $quote->id, 'ordre' => $ordre, 'code' => 'A' . $ordre, 'label' => 'Article ' . $ordre,
            'qty' => $qty, 'selling_price' => $price, 'discount' => 0,
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat20->id,
        ], $extra));
    }

    private function presentation(Quotes $quote, int $ordre, string $type, string $label = '', array $extra = []): QuoteLines
    {
        return QuoteLines::create(array_merge([
            'quotes_id' => $quote->id, 'ordre' => $ordre, 'line_type' => $type, 'code' => '', 'label' => $label,
            'qty' => 0, 'selling_price' => 0, 'discount' => 0,
            // TVA différente des articles : elle ne doit jamais apparaître au pied du devis.
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat10->id,
        ], $extra));
    }

    /**
     * Section « Garde-corps » : 12 × 85 + pose masquée 960, sous-total.
     * Section « Portail » au forfait « 1 × unité » : 3000 + 1200.
     * Texte final.
     */
    private function metalworkQuote(): Quotes
    {
        $quote = $this->quote();
        $this->presentation($quote, 1, 'section', 'Garde-corps');
        $this->article($quote, 2, 12, 85);
        $this->article($quote, 3, 1, 960, ['hide_on_pdf' => true, 'label' => 'Pose']);
        $this->presentation($quote, 4, 'subtotal');
        $this->presentation($quote, 5, 'section', 'Portail', ['pdf_package' => QuoteLines::PACKAGE_UNIT, 'methods_units_id' => $this->forfait->id]);
        $this->article($quote, 6, 1, 3000, ['label' => 'Portail coulissant']);
        $this->article($quote, 7, 1, 1200, ['label' => 'Motorisation']);
        $this->presentation($quote, 8, 'text', 'Validité 3 mois');

        return $quote->fresh();
    }

    // ---------------------------------------------------------------------
    // Totaux
    // ---------------------------------------------------------------------

    public function test_hidden_lines_count_in_totals_and_presentation_lines_do_not(): void
    {
        $quote = $this->metalworkQuote();
        $calc  = new QuoteCalculatorService($quote);

        // 1020 + 960 (masquée) + 3000 + 1200
        $this->assertEqualsWithDelta(6180, $calc->getSubTotal(), 0.001);
        $this->assertEqualsWithDelta(6180 * 1.2, $calc->getTotalPrice(), 0.001);

        // Une seule ventilation : la TVA à 10 % des sections et textes n'apparaît pas.
        $vat = $calc->getVatTotal();
        $this->assertCount(1, $vat);
        $this->assertEquals(20, reset($vat)[0]);
    }

    public function test_a_presentation_line_cannot_carry_an_amount(): void
    {
        $quote   = $this->quote();
        $section = $this->presentation($quote, 1, 'section', 'Titre', ['qty' => 5, 'selling_price' => 100, 'hide_on_pdf' => true]);

        $section->refresh();
        $this->assertEquals(0, $section->qty);
        $this->assertEquals(0, $section->selling_price);
        $this->assertFalse($section->hide_on_pdf);
    }

    public function test_an_article_cannot_be_printed_as_a_package(): void
    {
        $line = $this->article($this->quote(), 1, 1, 10, ['pdf_package' => QuoteLines::PACKAGE_UNIT]);

        $this->assertSame(0, $line->fresh()->pdf_package);
    }

    // ---------------------------------------------------------------------
    // PDF
    // ---------------------------------------------------------------------

    public function test_print_layout_shows_sections_subtotals_and_packages_and_omits_hidden_lines(): void
    {
        $quote = $this->metalworkQuote();
        $html  = $this->renderSalesPdf($quote);

        $this->assertStringContainsString('Garde-corps', $html);
        $this->assertStringContainsString('Article 2', $html);
        $this->assertStringNotContainsString('Pose', $html);
        // Sous-total de la section, ligne masquée comprise : 1020 + 960
        $this->assertStringContainsString($this->money(1980), $html);
        // Ouvrage au forfait : un seul montant, aucun détail
        $this->assertStringContainsString($this->money(4200), $html);
        $this->assertStringContainsString('Forfait', $html);
        $this->assertStringNotContainsString('Portail coulissant', $html);
        $this->assertStringNotContainsString('Motorisation', $html);
        $this->assertStringContainsString('Validité 3 mois', $html);
        // Totaux inchangés
        $this->assertStringContainsString($this->money(6180), $html);
    }

    public function test_the_custom_view_shows_sections_subtotals_and_packages_too(): void
    {
        if (!\Illuminate\Support\Facades\View::exists('print/custom/pdf-sales')) {
            $this->markTestSkipped('Vue print/custom/pdf-sales absente (non versionnée).');
        }

        $html = $this->renderSalesPdf($this->metalworkQuote(), 'print/custom/pdf-sales');

        $this->assertStringContainsString('line-section', $html);
        $this->assertStringContainsString('Garde-corps', $html);
        $this->assertStringNotContainsString('Pose', $html);
        $this->assertStringContainsString($this->money(1980), $html);
        $this->assertStringContainsString($this->money(4200), $html);
        $this->assertStringContainsString('Forfait', $html);
        $this->assertStringNotContainsString('Motorisation', $html);
        $this->assertStringContainsString('Validité 3 mois', $html);
    }

    public function test_each_subtotal_starts_from_the_previous_one(): void
    {
        $quote = $this->quote();
        $this->presentation($quote, 1, 'section', 'Escalier');
        $this->article($quote, 2, 1, 100);
        $this->presentation($quote, 3, 'subtotal', 'Limon');
        $this->article($quote, 4, 1, 40);
        $this->article($quote, 5, 1, 2);
        $this->presentation($quote, 6, 'subtotal', 'Marches');

        $rows = collect(app(SalesPrintLayout::class)->build($quote->fresh()->QuoteLines)['rows'])
            ->where('type', 'subtotal')->pluck('amount', 'label')->all();

        $this->assertEquals(['Limon' => 100, 'Marches' => 42], $rows);
    }

    public function test_a_package_section_with_no_line_below_prints_as_a_plain_title(): void
    {
        $quote = $this->quote();
        $this->article($quote, 1, 1, 500, ['label' => 'Limon']);
        $this->presentation($quote, 2, 'section', 'ESCALIER', ['pdf_package' => QuoteLines::PACKAGE_UNIT]);

        $layout = app(SalesPrintLayout::class)->build($quote->fresh()->QuoteLines);

        $this->assertSame(['article', 'section'], array_column($layout['rows'], 'type'));
        $this->assertSame(['Limon'], $layout['lines']->pluck('label')->all(), 'Aucun forfait à 0 € ne doit être imprimé.');
    }

    public function test_a_view_unaware_of_presentation_lines_still_prints_a_correct_document(): void
    {
        $quote = $this->metalworkQuote();
        $quote->Lines = $quote->QuoteLines;

        app(SalesPrintLayout::class)->apply($quote);

        // Ce qu'une vue print/custom non mise à jour verra dans $Document->Lines.
        $this->assertSame(['Article 2', 'Portail'], $quote->Lines->pluck('label')->all());
        $package = $quote->Lines->last();
        $this->assertEquals(1, $package->qty);
        $this->assertEquals(4200, $package->selling_price);
        $this->assertSame('Forfait', $package->Unit->label);
        $this->assertFalse($package->exists);
    }

    public function test_the_quote_pdf_route_still_renders(): void
    {
        $quote = $this->metalworkQuote();

        $this->get(route('pdf.quote', ['Document' => $quote->id]))->assertOk();
    }

    public function test_the_public_quote_page_shows_the_layout_without_hidden_details(): void
    {
        $quote = $this->metalworkQuote();
        $calc  = new QuoteCalculatorService($quote);

        // Rendu direct : la route journalise la visite dans guest_visits, dont
        // ip_address n'est jamais renseignée — MySQL non strict l'accepte, pas SQLite.
        $html = view('guest/guest-quote-info', [
            'Quote'                   => $quote,
            'totalPrices'             => $calc->getTotalPrice(),
            'subPrice'                => $calc->getSubTotal(),
            'vatPrice'                => $calc->getVatTotal(),
            'TotalServiceProductTime' => [],
            'TotalServiceSettingTime' => [],
            'TotalServiceCost'        => [],
            'TotalServicePrice'       => [],
            'printRows'               => app(SalesPrintLayout::class)->build($quote->QuoteLines)['rows'],
        ])->render();

        $this->assertStringContainsString('Garde-corps', $html);
        $this->assertStringContainsString('Article 2', $html);
        $this->assertStringNotContainsString('fw-medium">Pose<', $html);
        $this->assertStringNotContainsString('Motorisation', $html);
        $this->assertStringContainsString('4200.00', $html);
    }

    public function test_the_public_order_page_shows_the_layout_without_hidden_details(): void
    {
        $order = $this->convert($this->metalworkQuote(), ['Article 2', 'Pose', 'Portail coulissant', 'Motorisation']);

        $this->get(route('guest.order.show', ['uuid' => $order->uuid]))
            ->assertOk()
            ->assertSee('Garde-corps')
            ->assertDontSee('fw-medium">Pose<', false)
            ->assertDontSee('Motorisation')
            ->assertSee('4200.00');
    }

    // ---------------------------------------------------------------------
    // Saisie
    // ---------------------------------------------------------------------

    public function test_a_section_is_inserted_at_the_requested_position(): void
    {
        $quote  = $this->quote();
        $first  = $this->article($quote, 1, 1, 10);
        $second = $this->article($quote, 2, 1, 10);

        $this->postJson(route('quotes.lines.json.store', ['quoteId' => $quote->id]), [
            'line_type' => 'section', 'ordre' => 2, 'label' => 'Pose',
        ])->assertCreated()->assertJsonPath('line.line_type', 'section')->assertJsonPath('line.ordre', 2);

        $this->assertSame(1, $first->fresh()->ordre);
        $this->assertSame(3, $second->fresh()->ordre);
    }

    public function test_a_section_needs_a_title_but_a_subtotal_does_not(): void
    {
        $quote = $this->quote();
        $url   = route('quotes.lines.json.store', ['quoteId' => $quote->id]);

        $this->postJson($url, ['line_type' => 'section', 'ordre' => 1, 'label' => ''])->assertUnprocessable();
        $this->postJson($url, ['line_type' => 'subtotal', 'ordre' => 1])->assertCreated();
        $this->postJson($url, ['line_type' => 'bogus', 'ordre' => 1, 'label' => 'x'])->assertUnprocessable();
    }

    public function test_presentation_options_follow_the_line_type(): void
    {
        $quote    = $this->quote();
        $article  = $this->article($quote, 1, 1, 10);
        $section  = $this->presentation($quote, 2, 'section', 'S');
        $subtotal = $this->presentation($quote, 3, 'subtotal');
        $url      = fn ($line) => route('quotes.lines.json.presentation', ['quoteId' => $quote->id, 'id' => $line->id]);

        $this->patchJson($url($article), ['hide_on_pdf' => true])->assertOk()->assertJsonPath('line.hide_on_pdf', true);
        $this->patchJson($url($article), ['pdf_package' => 1])->assertUnprocessable();
        $this->patchJson($url($section), ['pdf_package' => 2, 'methods_units_id' => $this->forfait->id])
            ->assertOk()->assertJsonPath('line.pdf_package', 2);
        $this->patchJson($url($section), ['hide_on_pdf' => true])->assertUnprocessable();
        $this->patchJson($url($subtotal), ['hide_on_pdf' => true])->assertUnprocessable();

        $this->assertSame($this->forfait->id, $section->fresh()->methods_units_id);
    }

    public function test_price_increase_leaves_presentation_lines_at_zero(): void
    {
        $quote   = $this->quote();
        $article = $this->article($quote, 1, 1, 10);
        $section = $this->presentation($quote, 2, 'section', 'S');

        $this->postJson(route('quotes.lines.json.price-increase', ['quoteId' => $quote->id]), ['amount' => 5])
            ->assertOk()->assertJsonPath('updated', 1);

        $this->assertEquals(15, $article->fresh()->selling_price);
        $this->assertEquals(0, $section->fresh()->selling_price);
    }

    // ---------------------------------------------------------------------
    // Listes et recherche
    // ---------------------------------------------------------------------

    public function test_presentation_lines_stay_out_of_the_global_list_and_the_import_search(): void
    {
        $source = $this->metalworkQuote();
        $target = $this->quote();

        $codes = collect($this->getJson(route('quote-lines.json.list', ['search' => '']))->json('data'))->pluck('label');
        $this->assertNotContains('Garde-corps', $codes);
        $this->assertContains('Article 2', $codes);

        $sources = $this->getJson(route('quotes.lines.json.import-sources', ['quoteId' => $target->id, 'search' => 'Garde-corps']))->json('data');
        $this->assertSame([], $sources, 'Le titre d\'une section ne doit pas faire remonter le devis.');

        $sources = collect($this->getJson(route('quotes.lines.json.import-sources', ['quoteId' => $target->id]))->json('data'));
        $lines   = collect($sources->firstWhere('id', $source->id)['lines'])->pluck('label');
        $this->assertNotContains('Garde-corps', $lines);
        $this->assertCount(4, $lines);

        $sectionId = $source->QuoteLines->firstWhere('line_type', 'section')->id;
        $this->postJson(route('quotes.lines.json.import-from', ['quoteId' => $target->id]), ['line_ids' => [$sectionId]])
            ->assertCreated()->assertJsonCount(0, 'lines');
    }

    // ---------------------------------------------------------------------
    // Copies
    // ---------------------------------------------------------------------

    public function test_duplicating_a_quote_keeps_the_layout(): void
    {
        $source = $this->metalworkQuote();
        $copy   = app(QuoteDuplicator::class)->duplicate($source);

        $this->assertSame(
            $source->QuoteLines->map(fn ($l) => [$l->ordre, $l->line_type, (bool) $l->hide_on_pdf, (int) $l->pdf_package, $l->label])->all(),
            $copy->QuoteLines()->get()->map(fn ($l) => [$l->ordre, $l->line_type, (bool) $l->hide_on_pdf, (int) $l->pdf_package, $l->label])->all(),
        );
    }

    public function test_saving_and_using_a_template_keeps_the_layout(): void
    {
        $source   = $this->metalworkQuote();
        $template = app(QuoteDuplicator::class)->saveAsTemplate($source, 'Trame métallerie');
        $quote    = app(QuoteDuplicator::class)->duplicate($template);

        $this->assertSame(
            ['section', 'article', 'article', 'subtotal', 'section', 'article', 'article', 'text'],
            $quote->QuoteLines()->get()->pluck('line_type')->all(),
        );
        $this->assertSame(QuoteLines::PACKAGE_UNIT, $quote->QuoteLines()->where('line_type', 'section')->get()->last()->pdf_package);
    }

    // ---------------------------------------------------------------------
    // Conversion en commande
    // ---------------------------------------------------------------------

    private function convert(Quotes $quote, array $labels, ?string $presentation = null): Orders
    {
        $ids = $quote->QuoteLines->whereIn('label', $labels)->pluck('id')->all();
        $payload = ['line_ids' => $ids] + ($presentation ? ['presentation' => $presentation] : []);

        $this->postJson(route('quotes.lines.json.store-order', ['quoteId' => $quote->id]), $payload)->assertOk();

        return Orders::where('quotes_id', $quote->id)->latest('id')->firstOrFail();
    }

    public function test_conversion_carries_the_sections_of_the_selected_lines_by_default(): void
    {
        $quote = $this->metalworkQuote();
        $order = $this->convert($quote, ['Article 2', 'Pose']);

        // Le texte final suit la section Portail : aucune de ses lignes n'étant
        // commandée, il reste sur le devis avec elle.
        $lines = $order->OrderLines()->get();
        $this->assertSame(['section', 'article', 'article', 'subtotal'], $lines->pluck('line_type')->all());
        $this->assertSame(['Garde-corps', 'Article 2', 'Pose', ''], $lines->pluck('label')->all());
        $this->assertTrue((bool) $lines->firstWhere('label', 'Pose')->hide_on_pdf);
        $this->assertSame(3, QuoteLines::where('label', 'Garde-corps')->value('statu'));
    }

    public function test_conversion_can_drop_the_layout(): void
    {
        $quote = $this->metalworkQuote();
        $order = $this->convert($quote, ['Article 2', 'Portail coulissant'], 'drop');

        $this->assertSame(['article', 'article'], $order->OrderLines()->pluck('line_type')->all());
    }

    public function test_an_order_package_prints_like_the_quote(): void
    {
        $quote = $this->metalworkQuote();
        $order = $this->convert($quote, ['Portail coulissant', 'Motorisation']);

        $html = $this->renderSalesPdf($order);
        $this->assertStringContainsString($this->money(4200), $html);
        $this->assertStringNotContainsString('Motorisation', $html);
    }

    public function test_the_order_confirmation_freezes_the_layout(): void
    {
        $quote        = $this->metalworkQuote();
        $order        = $this->convert($quote, ['Portail coulissant', 'Motorisation']);
        $confirmation = app(OrderConfirmationService::class)->createFromOrder($order);

        $section = $confirmation->OrderConfirmationLines->firstWhere('line_type', 'section');
        $this->assertSame(QuoteLines::PACKAGE_UNIT, $section->pdf_package);
        $this->assertSame($this->forfait->id, $section->methods_units_id);
    }

    // ---------------------------------------------------------------------
    // Livraison, facturation, fabrication
    // ---------------------------------------------------------------------

    public function test_presentation_order_lines_have_nothing_to_deliver_or_invoice(): void
    {
        $order   = $this->convert($this->metalworkQuote(), ['Article 2']);
        $section = $order->OrderLines()->where('line_type', 'section')->firstOrFail();

        $this->assertEquals(0, $section->delivered_remaining_qty);
        $this->assertEquals(0, $section->invoiced_remaining_qty);
        $this->assertSame(3, (int) $section->delivery_status);
        $this->assertSame(3, (int) $section->invoice_status);
        $this->assertEquals(0, $section->average_percent_progress_delevery);
    }

    public function test_an_order_is_delivered_once_its_articles_are(): void
    {
        $order   = $this->convert($this->metalworkQuote(), ['Article 2']);
        $article = $order->OrderLines()->articles()->firstOrFail();
        $article->update(['delivery_status' => 3]);

        (new CheckOrderDeliveredStatus())->handle(new OrderLineUpdated($article));

        $this->assertSame(3, (int) $order->fresh()->statu);
    }

    public function test_a_presentation_line_can_never_be_delivered(): void
    {
        $order    = $this->convert($this->metalworkQuote(), ['Article 2']);
        $section  = $order->OrderLines()->where('line_type', 'section')->firstOrFail();
        $delivery = Deliverys::factory()->create();

        $this->expectException(\DomainException::class);
        DeliveryLines::create(['deliverys_id' => $delivery->id, 'order_line_id' => $section->id, 'ordre' => 1, 'qty' => 1, 'statu' => 1]);
    }

    public function test_a_presentation_line_can_never_be_invoiced(): void
    {
        $order   = $this->convert($this->metalworkQuote(), ['Article 2']);
        $section = $order->OrderLines()->where('line_type', 'section')->firstOrFail();
        $invoice = Invoices::factory()->create(['statu' => 1, 'invoice_type' => 1]);

        try {
            app(\App\Services\InvoiceLineService::class)->createInvoiceLine($invoice, $section->id, null, 10, 1, $this->vat20->id);
            $this->fail('Une ligne de présentation a été facturée.');
        } catch (\DomainException) {
            $this->assertSame(0, InvoiceLines::where('order_line_id', $section->id)->count());
        }
    }

    public function test_presentation_lines_are_not_offered_for_delivery_nor_sent_to_production(): void
    {
        $quote = $this->metalworkQuote();
        $order = $this->convert($quote, ['Article 2', 'Pose']);
        $order->update(['statu' => 1]);

        $requestable = OrderLines::whereIn('delivery_status', [1, 2])->where('orders_id', $order->id)->pluck('line_type')->unique()->all();
        $this->assertSame(['article'], $requestable);

        $payload = (new N2PPayloadBuilder())->build($order->fresh(), ['n2p_send_tasks' => false]);
        $this->assertCount(2, $payload['jobs']);
    }

    public function test_a_proforma_from_a_quote_only_bills_articles(): void
    {
        $quote   = $this->metalworkQuote();
        $company = Companies::findOrFail($quote->companies_id);
        $ids     = $quote->QuoteLines->whereIn('label', ['Portail coulissant', 'Motorisation'])->pluck('id')->all();

        $this->postJson(route('proformas.request.store.quote'), [
            'code' => 'PF-1', 'label' => 'Acompte', 'quote_id' => $quote->id, 'companies_id' => $company->id,
            'companies_addresses_id' => $quote->companies_addresses_id, 'companies_contacts_id' => $quote->companies_contacts_id,
            'user_id' => $this->user->id, 'lines' => $ids,
        ])->assertOk();

        $proforma = Invoices::where('code', 'PF-1')->firstOrFail();
        $this->assertSame(2, InvoiceLines::where('invoices_id', $proforma->id)->count());
        $this->assertContains('section', OrderLines::where('orders_id', $proforma->order_id)->pluck('line_type')->all());
    }

    // ---------------------------------------------------------------------
    // API
    // ---------------------------------------------------------------------

    public function test_an_api_upsert_that_ignores_sections_does_not_delete_them(): void
    {
        $quote   = $this->quote();
        $section = $this->presentation($quote, 1, 'section', 'Garde-corps');
        $article = $this->article($quote, 2, 1, 10);

        $reflection = new \ReflectionMethod(\App\Http\Controllers\Api\QuoteController::class, 'syncLines');
        $reflection->invoke(app(\App\Http\Controllers\Api\QuoteController::class), $quote, [
            ['id' => $article->id, 'ordre' => 2, 'label' => 'Article modifié', 'qty' => 2, 'selling_price' => 10],
        ]);

        $this->assertNotNull($section->fresh(), 'La section a été supprimée par l\'upsert API.');
        $this->assertSame('Article modifié', $article->fresh()->label);
    }

    // ---------------------------------------------------------------------

    private function renderSalesPdf($document, string $view = 'print/pdf-sales'): string
    {
        $key        = $document instanceof Quotes ? 'QuoteLines' : 'OrderLines';
        $calculator = $document instanceof Quotes
            ? new QuoteCalculatorService($document)
            : new \App\Services\OrderCalculatorService($document);

        $Document = $document->fresh();
        $Document->Lines = $Document->$key;
        unset($Document->$key);
        $printRows = app(SalesPrintLayout::class)->apply($Document);

        return view($view, [
            'typeDocumentName'    => 'Devis',
            'Document'            => $Document,
            'Factory'             => app('Factory'),
            'formattedTotalPrice' => $this->money($calculator->getTotalPrice()),
            'formattedSubPrice'   => $this->money($calculator->getSubTotal()),
            'vatPrice'            => $calculator->getVatTotal(),
            'image'               => null,
            'customCss'           => null,
            'normalizeCurrency'   => fn ($v) => str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $v),
            'printRows'           => $printRows,
        ])->render();
    }

    private function money(float $value): string
    {
        return str_replace(["\u{00A0}", "\u{202F}"], ' ', Number::currency($value, 'EUR', config('app.locale')));
    }
}
