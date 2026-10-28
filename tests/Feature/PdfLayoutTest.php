<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\File as FileModel;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteProjectEstimate;
use App\Models\Workflow\Quotes;
use App\Services\Documents\DocumentPdfService;
use App\Services\Documents\PdfLayoutRegistry;
use App\Services\OrderConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Tests\TestCase;

/**
 * Modèles d'impression des documents commerciaux (devis, commande, AR) : court
 * (rendu historique, couvert par SalesPdfLegacyRenderingTest), détaillé,
 * technique sans prix, et modèles déposés en dossier.
 */
class PdfLayoutTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_ROOT        = 'print/__test_layouts';
    private const TEST_CUSTOM_ROOT = 'print/__test_custom_layouts';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create([
            'name' => 'Métallerie Dupont', 'curency' => 'EUR', 'pdf_header_font_color' => '#dddddd',
        ]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(resource_path('views/' . self::TEST_ROOT));
        File::deleteDirectory(resource_path('views/' . self::TEST_CUSTOM_ROOT));

        parent::tearDown();
    }

    // --- Technique : aucun montant ------------------------------------------

    public function test_the_technical_layout_of_a_quote_prints_no_amount(): void
    {
        $quote = $this->makeQuote();
        $html  = app(DocumentPdfService::class)->html($quote->fresh(), 'technical');

        $this->assertStringContainsString('Garde-corps acier', $html);
        $this->assertStringContainsString('S235', $html);
        $this->assertStringContainsString('Tolérance', $html);
        $this->assertStringContainsString('Texte long du garde-corps', $html);
        $this->assertNoAmount($html);
        $this->assertStringNotContainsString('guest/quote', $html, 'La page publique montre les prix.');
    }

    public function test_the_technical_layout_of_an_order_confirmation_prints_no_amount(): void
    {
        $confirmation = app(OrderConfirmationService::class)->createFromOrder($this->makeOrder());
        $html         = app(DocumentPdfService::class)->html($confirmation->fresh(), 'technical');

        $this->assertStringContainsString('Garde-corps acier', $html);
        $this->assertStringContainsString('S235', $html, 'Les caractéristiques viennent de la ligne de commande.');
        $this->assertNoAmount($html);
    }

    public function test_the_technical_layout_receives_no_price_variable(): void
    {
        $quote = $this->makeQuote();
        $seen  = null;
        view()->composer('print.layouts.technical.pdf-sales', function ($view) use (&$seen) {
            $seen = $view->getData();
        });

        app(DocumentPdfService::class)->html($quote->fresh(), 'technical');

        foreach (['formattedTotalPrice', 'formattedSubPrice', 'vatPrice', 'printRows', 'normalizeCurrency'] as $variable) {
            $this->assertArrayNotHasKey($variable, $seen);
        }
        $this->assertCount(0, $seen['Document']->Lines, 'Aucune ligne Eloquent, donc aucun prix à lire.');
        foreach ($seen['layoutRows'] as $row) {
            $this->assertArrayNotHasKey('unit_price', $row);
            $this->assertArrayNotHasKey('amount', $row);
        }
    }

    // --- Détaillé ------------------------------------------------------------

    public function test_the_long_layout_adds_details_and_the_estimate_blocks_ticked_for_the_pdf(): void
    {
        $quote = $this->makeQuote();
        $html  = app(DocumentPdfService::class)->html($quote->fresh(), 'long');

        $this->assertStringContainsString('Texte long du garde-corps', $html);
        $this->assertStringContainsString('Tolérance', $html);
        $this->assertStringContainsString('Livraison par semi-remorque', $html);
        $this->assertStringContainsString($this->money(150), $html);
        $this->assertStringContainsString(__('pdf_layouts.estimate_cost_not_included'), $html);
        $this->assertStringNotContainsString('Benne à déchets', $html, 'Bloc non coché pour le PDF.');
        $this->assertStringContainsString($this->money(1020), $html, 'Montant HT de la ligne.');
    }

    public function test_the_long_layout_prints_the_main_plan_of_the_line_as_a_thumbnail(): void
    {
        Storage::fake(config('files.disk'));
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk(config('files.disk'))->put('private/files/plan.png', $png);

        $quote = $this->makeQuote();
        $plan  = FileModel::create([
            'name' => 'plan.png', 'original_file_name' => 'plan.png', 'type' => 'image/png', 'kind' => 'image', 'extension' => 'png',
            'disk' => config('files.disk'), 'path' => 'private/files/plan.png', 'size' => strlen($png),
        ]);
        $other = FileModel::create([
            'name' => 'photo.png', 'original_file_name' => 'photo.png', 'type' => 'image/png', 'kind' => 'image', 'extension' => 'png',
            'disk' => config('files.disk'), 'path' => 'private/files/plan.png', 'size' => strlen($png),
        ]);
        $line = $quote->QuoteLines()->first();
        $line->files()->attach($other->id, ['role' => 'photo', 'is_primary' => true]);
        $line->files()->attach($plan->id, ['role' => 'plan', 'is_primary' => true]);

        $service = app(DocumentPdfService::class);

        $this->assertStringContainsString('data:image/png;base64,' . base64_encode($png), $service->html($quote->fresh(), 'long'));
        $this->assertStringNotContainsString(base64_encode($png), $service->html($quote->fresh(), 'technical'));
    }

    // --- Langue ----------------------------------------------------------------

    public function test_the_document_language_does_not_follow_the_user_and_is_restored(): void
    {
        $quote   = $this->makeQuote();
        $service = app(DocumentPdfService::class);

        $this->assertSame('en', app()->getLocale());
        $this->assertStringContainsString('<h2>Devis</h2>', $service->html($quote->fresh(), 'long', 'fr'));
        $this->assertStringContainsString('Désignation', $service->html($quote->fresh(), 'technical', 'fr'));
        $this->assertStringContainsString('<h2>Quote</h2>', $service->html($quote->fresh(), 'long', 'en'));
        $this->assertSame('en', app()->getLocale(), "La langue de l'utilisateur est rétablie.");
    }

    // --- Route d'impression ----------------------------------------------------

    public function test_the_print_route_accepts_known_layouts_and_languages_only(): void
    {
        $quote = $this->makeQuote();
        $url   = fn (array $query) => route('pdf.quote', ['Document' => $quote->id] + $query);

        $this->get($url([]))->assertOk();
        $this->get($url(['layout' => 'long', 'lang' => 'en']))->assertOk();
        $this->get($url(['layout' => 'technical', 'lang' => 'fr']))->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=QT-0001.pdf');
        $this->get($url(['layout' => 'unknown']))->assertNotFound();
        $this->get($url(['layout' => '../pdf-sales']))->assertNotFound();
        $this->get($url(['layout' => 'custom/pdf-sales']))->assertNotFound();
        $this->get($url(['lang' => 'de']))->assertNotFound();
    }

    public function test_an_order_confirmation_in_progress_still_cannot_be_printed(): void
    {
        $confirmation = app(OrderConfirmationService::class)->createFromOrder($this->makeOrder());
        $confirmation->forceFill(['statu' => \App\Models\Workflow\OrderConfirmations::STATUS_IN_PROGRESS])->save();

        $this->get(route('pdf.orders.confirm', ['Document' => $confirmation->id, 'layout' => 'technical']))->assertForbidden();
    }

    // --- Modèles déposés en dossier --------------------------------------------

    public function test_a_dropped_folder_becomes_a_layout_of_the_documents_whose_view_it_contains(): void
    {
        config(['pdf.layouts_path' => self::TEST_ROOT, 'pdf.custom_layouts_path' => self::TEST_CUSTOM_ROOT]);

        $this->dropView(self::TEST_ROOT, 'atelier', 'pdf-sales', 'ATELIER {{ $Document->code }}', ['label' => ['fr' => 'Atelier', 'en' => 'Workshop']]);
        $this->dropView(self::TEST_ROOT, 'factures', 'pdf-invoice', 'FACTURE');
        $this->dropView(self::TEST_ROOT, 'Bad Name', 'pdf-sales', 'NON');
        $this->dropView(self::TEST_ROOT, 'sansprix', 'pdf-invoice', 'NON', ['prices' => false]);

        $registry = new PdfLayoutRegistry();
        $sales    = $registry->available('print/pdf-sales');

        $this->assertSame(['short', 'atelier'], array_keys($sales));
        $this->assertSame('Workshop', $sales['atelier']->label());
        $this->assertSame(['short', 'factures'], array_keys($registry->available('print/pdf-invoice')),
            "Un modèle sans prix n'est pas proposé là où les lignes ne savent pas s'imprimer sans prix.");

        $this->app->instance(PdfLayoutRegistry::class, $registry);
        $html = app(DocumentPdfService::class)->html($this->makeQuote()->fresh(), 'atelier');
        $this->assertStringContainsString('ATELIER QT-0001', $html);
    }

    public function test_a_custom_folder_overrides_a_standard_layout_file_and_its_manifest(): void
    {
        config(['pdf.layouts_path' => self::TEST_ROOT, 'pdf.custom_layouts_path' => self::TEST_CUSTOM_ROOT]);

        $this->dropView(self::TEST_ROOT, 'detail', 'pdf-sales', 'STANDARD', ['label' => 'Standard', 'prices' => false]);
        $this->dropView(self::TEST_CUSTOM_ROOT, 'detail', 'pdf-sales', 'CLIENT');

        $layout = (new PdfLayoutRegistry())->find('print/pdf-sales', 'detail');

        $this->assertSame(self::TEST_CUSTOM_ROOT . '/detail/pdf-sales', $layout->view);
        $this->assertFalse($layout->prices, 'Sans manifeste client, celui du modèle standard s\'applique : jamais de prix par surprise.');
        $this->assertSame('Standard', $layout->label());
    }

    public function test_the_print_menu_lists_the_layouts_and_languages(): void
    {
        $this->blade('<x-pdf-print-menu route="/print/quote/1" />')
            ->assertSee(__('pdf_layouts.short'))
            ->assertSee(__('pdf_layouts.long'))
            ->assertSee(__('pdf_layouts.technical'))
            ->assertSee('Français')
            ->assertSee('value="en" selected', false);
    }

    // ---------------------------------------------------------------------------

    private function assertNoAmount(string $html): void
    {
        foreach (['€', 'EUR', $this->money(85), $this->money(1020), $this->money(150), '85.00', '1,020', '1 020', '150.00'] as $amount) {
            $this->assertStringNotContainsString($amount, $html, "Montant imprimé : {$amount}");
        }
    }

    private function money(float $value): string
    {
        return str_replace(["\u{00A0}", "\u{202F}"], ' ', Number::currency($value, 'EUR', app()->getLocale()));
    }

    private function dropView(string $root, string $layout, string $file, string $content, ?array $manifest = null): void
    {
        $dir = resource_path("views/{$root}/{$layout}");
        File::ensureDirectoryExists($dir);
        File::put("{$dir}/{$file}.blade.php", $content);
        if ($manifest !== null) {
            File::put("{$dir}/layout.json", json_encode($manifest));
        }
    }

    private function lineRow(): array
    {
        $vat  = AccountingVat::factory()->create(['label' => 'TVA 20', 'rate' => 20]);
        $unit = MethodsUnits::factory()->create(['code' => 'ML', 'label' => 'Mètre linéaire']);

        return [
            'ordre' => 1, 'code' => 'GC-01', 'label' => 'Garde-corps acier', 'qty' => 12,
            'methods_units_id' => $unit->id, 'selling_price' => 85, 'discount' => 0,
            'accounting_vats_id' => $vat->id, 'delivery_date' => '2026-11-30',
        ];
    }

    private function details(): array
    {
        return [
            'material' => 'S235', 'thickness' => 3, 'external_comment' => 'Texte long du garde-corps',
            'custom_requirements' => [['label' => 'Tolérance', 'value' => '±0,5']],
        ];
    }

    private function makeQuote(): Quotes
    {
        $quote = Quotes::factory()->create([
            'companies_id' => Companies::factory()->create()->id,
            'code' => 'QT-0001', 'label' => 'Escalier', 'statu' => 1,
        ]);
        $line = QuoteLines::create($this->lineRow() + ['quotes_id' => $quote->id]);
        QuoteLineDetails::create(['quote_lines_id' => $line->id] + $this->details());

        QuoteProjectEstimate::create([
            'quotes_id' => $quote->id,
            'logistics' => 'Livraison par semi-remorque', 'logistics_cost' => 150, 'show_logistics_on_pdf' => 1,
            'waste_management' => 'Benne à déchets', 'waste_management_cost' => 80, 'show_waste_on_pdf' => 2,
        ]);

        return $quote;
    }

    private function makeOrder(): Orders
    {
        $order = Orders::factory()->create([
            'companies_id' => Companies::factory()->create()->id,
            'code' => 'OR-0001', 'label' => 'Escalier', 'statu' => 1, 'type' => 1,
        ]);
        $row  = $this->lineRow();
        $line = OrderLines::create($row + [
            'orders_id' => $order->id, 'delivered_qty' => 0, 'delivered_remaining_qty' => $row['qty'],
            'invoiced_qty' => 0, 'invoiced_remaining_qty' => $row['qty'], 'internal_delay' => '2026-11-20',
        ]);
        OrderLineDetails::create(['order_lines_id' => $line->id] + $this->details());

        return $order;
    }
}
