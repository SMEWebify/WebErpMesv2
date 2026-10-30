<?php

namespace Tests\Feature;

use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\DeliveryLines;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Documents\DocumentPdfService;
use App\Services\Documents\SalesPrintLayout;
use App\Services\PdfThemeResolver;
use App\Services\OrderCalculatorService;
use App\Services\OrderConfirmationCalculatorService;
use App\Services\OrderConfirmationService;
use App\Services\QuoteCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Number;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Non-régression du PDF commercial partagé (devis, commande, ARC).
 *
 * Les fichiers de référence de tests/Fixtures/pdf-sales ont été rendus AVANT
 * l'introduction des lignes de présentation (sections, sous-totaux, textes,
 * lignes masquées). Un document qui n'utilise aucune de ces fonctions doit
 * produire exactement le même HTML : c'est la garantie donnée au client en
 * production. On compare le HTML et non le PDF, que dompdf horodate.
 *
 * Si une référence manque, elle est écrite et le test est marqué incomplet :
 * ne jamais régénérer une référence existante pour faire passer ce test.
 */
class SalesPdfLegacyRenderingTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURES = __DIR__ . '/../Fixtures/pdf-sales';

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());

        app()->forgetInstance('Factory');
        $factory = Factory::create([
            'name'                  => 'Métallerie Dupont',
            'address'               => '12 rue des Forges',
            'zipcode'               => '35000',
            'city'                  => 'Rennes',
            'phone_number'          => '02 99 00 00 00',
            'mail'                  => 'contact@dupont.test',
            'curency'               => 'EUR',
            'pdf_header_font_color' => '#dddddd',
        ]);
        app()->instance('Factory', $factory);
    }

    public static function views(): array
    {
        return [
            'vue standard'      => ['print/pdf-sales', 'standard'],
            'vue personnalisée' => ['print/custom/pdf-sales', 'custom'],
        ];
    }

    #[DataProvider('views')]
    public function test_a_quote_without_presentation_lines_renders_as_before(string $view, string $suffix): void
    {
        $quote = $this->makeQuote();

        $this->assertMatchesReference(
            "quote-{$suffix}.html",
            $this->render($view, $quote->fresh(), new QuoteCalculatorService($quote->fresh()), 'Devis', 'QuoteLines')
        );
    }

    #[DataProvider('views')]
    public function test_an_order_without_presentation_lines_renders_as_before(string $view, string $suffix): void
    {
        $order = $this->makeOrder();

        $this->assertMatchesReference(
            "order-{$suffix}.html",
            $this->render($view, $order->fresh(), new OrderCalculatorService($order->fresh()), 'Commande', 'OrderLines')
        );
    }

    #[DataProvider('views')]
    public function test_an_order_confirmation_without_presentation_lines_renders_as_before(string $view, string $suffix): void
    {
        $order        = $this->makeOrder();
        $confirmation = app(OrderConfirmationService::class)->createFromOrder($order);
        // Date figée comme celles du devis et de la commande : la vue imprime la date du
        // document, et la référence a été capturée le 07/10/2026.
        $confirmation->forceFill([
            'code'       => 'ARC-0001',
            'uuid'       => '00000000-0000-0000-0000-0000000000a1',
            'created_at' => '2026-10-07 10:00:00',
        ])->save();

        $this->assertMatchesReference(
            "order-confirmation-{$suffix}.html",
            $this->render($view, $confirmation->fresh(), new OrderConfirmationCalculatorService($confirmation->fresh()), 'ARC 1', 'OrderConfirmationLines')
        );
    }

    /**
     * Modèle « court » rendu par DocumentPdfService — chemin du bouton PDF et des
     * e-mails — sans paramètre ou avec layout=short : même HTML que la référence.
     * Les références portent un titre écrit en dur (« Devis », « ARC 1 ») là où le
     * service met le titre traduit : seul ce titre est ramené à celui de la référence.
     */
    public static function shortLayouts(): array
    {
        return [
            'sans paramètre, vue résolue'  => [null, false],
            'layout=short, vue résolue'    => ['short', false],
            'sans paramètre, vue standard' => [null, true],
            'layout=short, vue standard'   => ['short', true],
        ];
    }

    #[DataProvider('shortLayouts')]
    public function test_the_short_layout_of_the_print_service_renders_the_quote_reference(?string $layout, bool $standard): void
    {
        $quote = $this->makeQuote()->fresh();

        $this->assertMatchesReference(
            "quote-{$this->resolvedSuffix($standard)}.html",
            $this->serviceHtml($quote, $layout, __('general_content.quote_trans_key'), 'Devis')
        );
    }

    #[DataProvider('shortLayouts')]
    public function test_the_short_layout_of_the_print_service_renders_the_order_reference(?string $layout, bool $standard): void
    {
        $order = $this->makeOrder()->fresh();

        $this->assertMatchesReference(
            "order-{$this->resolvedSuffix($standard)}.html",
            $this->serviceHtml($order, $layout, __('general_content.order_trans_key'), 'Commande')
        );
    }

    #[DataProvider('shortLayouts')]
    public function test_the_short_layout_of_the_print_service_renders_the_order_confirmation_reference(?string $layout, bool $standard): void
    {
        $confirmation = app(OrderConfirmationService::class)->createFromOrder($this->makeOrder());
        $confirmation->forceFill([
            'code'       => 'ARC-0001',
            'uuid'       => '00000000-0000-0000-0000-0000000000a1',
            'created_at' => '2026-10-07 10:00:00',
        ])->save();
        $confirmation = $confirmation->fresh();

        $this->assertMatchesReference(
            "order-confirmation-{$this->resolvedSuffix($standard)}.html",
            $this->serviceHtml($confirmation, $layout, __('general_content.order_confirm_trans_key') . ' ' . $confirmation->revision, 'ARC 1')
        );
    }

    /**
     * Bon de livraison : la référence delivery-standard.html a été rendue AVANT la
     * liste de colisage. Le BL par défaut — vue de base, puis service d'impression
     * sans paramètre — reste identique, même quand le BL porte des colis.
     */
    public function test_a_delivery_note_renders_as_before(): void
    {
        $delivery = $this->makeDelivery();

        $this->assertMatchesReference('delivery-standard.html', $this->renderDelivery($delivery->fresh()));
    }

    public function test_the_default_layout_of_the_print_service_renders_the_delivery_note_reference(): void
    {
        $delivery = $this->makeDelivery();
        $this->resolvedSuffix(true);

        if (class_exists(\App\Models\Workflow\PackagingLine::class)) {
            $line = $delivery->DeliveryLines()->first();
            $box  = \App\Models\Workflow\Packaging::create([
                'deliverys_id' => $delivery->id, 'code' => 'C1', 'type' => 'Caisse', 'user_id' => auth()->id(),
                'gross_weight' => 50, 'net_weight' => 40, 'length' => 1200, 'width' => 800, 'height' => 600,
            ]);
            \App\Models\Workflow\PackagingLine::create(['packaging_id' => $box->id, 'delivery_line_id' => $line->id, 'qty' => 2]);
        }

        $this->assertMatchesReference(
            'delivery-standard.html',
            $this->serviceHtml($delivery->fresh(), null, __('general_content.delivery_notes_trans_key'), 'Bon de livraison')
        );
    }

    // ---------------------------------------------------------------------

    /**
     * Prépare les variables du BL comme PrintController::generatePdf avant la
     * liste de colisage, sur la vue standard.
     */
    private function renderDelivery($Document): string
    {
        $Factory             = app('Factory');
        $formattedTotalPrice = Number::currency(0, 'EUR', config('app.locale'));
        $formattedSubPrice   = Number::currency(0, 'EUR', config('app.locale'));
        $vatPrice            = 0;
        $normalizeCurrency   = fn ($value) => str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $value);
        $image               = null;
        $customCss           = null;
        $printRows           = null;
        $typeDocumentName    = 'Bon de livraison';

        $Document->Lines = $Document->DeliveryLines;
        unset($Document->DeliveryLines);

        $html = view('print/pdf-delivery', compact(
            'typeDocumentName', 'Document', 'Factory', 'formattedTotalPrice',
            'formattedSubPrice', 'vatPrice', 'image', 'customCss', 'normalizeCurrency', 'printRows'
        ))->render();

        return str_replace([date('Y-m-d'), '&copy; ' . date('Y')], ['{{today}}', '&copy; {{year}}'], $html);
    }

    private function makeDelivery(): Deliverys
    {
        $order    = $this->makeOrder();
        $delivery = Deliverys::create([
            'uuid' => '00000000-0000-0000-0000-000000000003', 'code' => 'DN-0001', 'label' => 'Escalier',
            'companies_id' => $order->companies_id, 'companies_contacts_id' => $order->companies_contacts_id,
            'companies_addresses_id' => $order->companies_addresses_id, 'statu' => 1,
            'user_id' => auth()->id(), 'comment' => 'Commentaire du BL', 'customer_reference' => 'REF-CLIENT',
        ]);

        foreach ($order->OrderLines()->orderBy('ordre')->get() as $i => $orderLine) {
            DeliveryLines::create([
                'deliverys_id' => $delivery->id, 'order_line_id' => $orderLine->id,
                'ordre' => $i + 1, 'qty' => $i === 0 ? 5 : $orderLine->qty, 'statu' => 1,
            ]);
        }
        $order->OrderLines()->orderBy('ordre')->first()->update(['equipment_tag' => ['1RCP/001', '1RCP/002']]);

        return $delivery;
    }

    /**
     * Le service imprime la vue personnalisée quand elle existe (PdfThemeResolver).
     * $standard : la surcharge est neutralisée, pour vérifier aussi la vue standard
     * sur une machine où une vue print/custom est déployée.
     */
    private function resolvedSuffix(bool $standard): string
    {
        if ($standard) {
            $this->app->instance(PdfThemeResolver::class, new class(config()) extends PdfThemeResolver {
                public function resolve(string $baseViewKey, ?Factory $factory = null): string
                {
                    return $baseViewKey;
                }
            });

            return 'standard';
        }

        return View::exists('print/custom/pdf-sales') ? 'custom' : 'standard';
    }

    /**
     * Titre traduit du service ramené au titre écrit en dur de la référence,
     * tel quel ou en majuscules (une vue personnalisée peut le capitaliser).
     */
    private function serviceHtml($document, ?string $layout, string $serviceTitle, string $referenceTitle): string
    {
        $html = app(DocumentPdfService::class)->html($document, $layout);

        $html = str_replace(
            ["<title>{$serviceTitle} - #", "<h2>{$serviceTitle}</h2>", '>' . mb_strtoupper($serviceTitle) . '<'],
            ["<title>{$referenceTitle} - #", "<h2>{$referenceTitle}</h2>", '>' . mb_strtoupper($referenceTitle) . '<'],
            $html
        );

        return str_replace([date('Y-m-d'), '&copy; ' . date('Y')], ['{{today}}', '&copy; {{year}}'], $html);
    }

    /**
     * Prépare les variables comme PrintController::generatePdf (mise en page
     * SalesPrintLayout comprise) et rend la vue
     * sans passer par dompdf.
     */
    private function render(string $view, $Document, $calculator, string $typeDocumentName, string $linesKey): string
    {
        if (!View::exists($view)) {
            $this->markTestSkipped("Vue {$view} absente (les vues personnalisées ne sont pas versionnées).");
        }

        $Factory             = app('Factory');
        $formattedTotalPrice = Number::currency($calculator->getTotalPrice(), 'EUR', config('app.locale'));
        $formattedSubPrice   = Number::currency($calculator->getSubTotal(), 'EUR', config('app.locale'));
        $vatPrice            = $calculator->getVatTotal();
        $normalizeCurrency   = fn ($value) => str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $value);
        $image               = null;
        $customCss           = null;

        $Document->Lines = $Document->$linesKey;
        unset($Document->$linesKey);
        $printRows = app(SalesPrintLayout::class)->apply($Document);
        $this->assertNull($printRows, 'Un document sans ligne de présentation ne doit pas être remis en page.');

        $html = view($view, compact(
            'typeDocumentName', 'Document', 'Factory', 'formattedTotalPrice',
            'formattedSubPrice', 'vatPrice', 'image', 'customCss', 'normalizeCurrency', 'printRows'
        ))->render();

        // La vue imprime la date du jour et l'année du copyright : seules parties
        // variables d'un rendu à l'autre.
        return str_replace([date('Y-m-d'), '&copy; ' . date('Y')], ['{{today}}', '&copy; {{year}}'], $html);
    }

    private function assertMatchesReference(string $name, string $html): void
    {
        $path = self::FIXTURES . '/' . $name;

        if (!is_file($path)) {
            if (!is_dir(self::FIXTURES)) {
                mkdir(self::FIXTURES, 0777, true);
            }
            file_put_contents($path, $html);
            $this->markTestIncomplete("Référence {$name} créée : relancer le test.");
        }

        // Fins de ligne normalisées : elles dépendent du checkout (autocrlf), pas du rendu.
        $normalize = fn (string $s) => str_replace("\r\n", "\n", $s);

        $this->assertSame($normalize(file_get_contents($path)), $normalize($html), "Le rendu de {$name} a changé.");
    }

    private function parties(): array
    {
        $company = Companies::factory()->create(['label' => 'Client Test SARL']);
        $contact = CompaniesContacts::factory()->create([
            'companies_id' => $company->id, 'civility' => 'M.', 'first_name' => 'Jean',
            'name' => 'Martin', 'number' => '06 00 00 00 00', 'mail' => 'jean@client.test',
        ]);
        $address = CompaniesAddresses::factory()->create([
            'companies_id' => $company->id, 'adress' => '3 allée des Chênes',
            'zipcode' => '44000', 'city' => 'Nantes', 'province' => 'Loire-Atlantique', 'country' => 'France',
        ]);

        return [
            'companies_id'                     => $company->id,
            'companies_contacts_id'            => $contact->id,
            'companies_addresses_id'           => $address->id,
            'accounting_payment_conditions_id' => AccountingPaymentConditions::factory()->create(['code' => '30FDM', 'label' => '30 jours fin de mois'])->id,
            'accounting_payment_methods_id'    => AccountingPaymentMethod::factory()->create(['code' => 'VIR', 'label' => 'Virement'])->id,
        ];
    }

    private function units(): array
    {
        $vat20 = AccountingVat::factory()->create(['label' => 'TVA 20', 'rate' => 20]);
        $vat10 = AccountingVat::factory()->create(['label' => 'TVA 10', 'rate' => 10]);
        $unit  = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité']);
        $ml    = MethodsUnits::factory()->create(['code' => 'ML', 'label' => 'Mètre linéaire']);

        return [$vat20, $vat10, $unit, $ml];
    }

    private function lineRows(): array
    {
        [$vat20, $vat10, $unit, $ml] = $this->units();

        return [
            ['ordre' => 1, 'code' => 'GC-01', 'label' => 'Garde-corps acier', 'qty' => 12, 'methods_units_id' => $ml->id,   'selling_price' => 85,  'discount' => 0,  'accounting_vats_id' => $vat20->id, 'delivery_date' => '2026-11-30', 'detail' => ['material' => 'S235', 'thickness' => 3]],
            ['ordre' => 2, 'code' => 'MC-02', 'label' => 'Main courante inox', 'qty' => 12, 'methods_units_id' => $ml->id,   'selling_price' => 42,  'discount' => 5,  'accounting_vats_id' => $vat20->id, 'delivery_date' => null,         'detail' => ['material' => '304', 'thickness' => 2]],
            ['ordre' => 3, 'code' => 'TL-03', 'label' => 'Thermolaquage',      'qty' => 1,  'methods_units_id' => $unit->id, 'selling_price' => 180, 'discount' => 0,  'accounting_vats_id' => $vat10->id, 'delivery_date' => '2026-12-15', 'detail' => null],
        ];
    }

    private function makeQuote(): Quotes
    {
        $quote = Quotes::factory()->create($this->parties() + [
            'uuid' => '00000000-0000-0000-0000-000000000001', 'code' => 'QT-0001', 'label' => 'Escalier',
            'customer_reference' => 'REF-CLIENT', 'validity_date' => '2026-12-31', 'statu' => 1,
            'comment' => 'Commentaire du devis', 'created_at' => '2026-10-01 10:00:00',
        ]);

        foreach ($this->lineRows() as $row) {
            $detail = $row['detail'];
            unset($row['detail']);
            $line = QuoteLines::create($row + ['quotes_id' => $quote->id]);
            QuoteLineDetails::create(['quote_lines_id' => $line->id] + ($detail ?? []));
        }

        return $quote;
    }

    private function makeOrder(): Orders
    {
        $order = Orders::factory()->create($this->parties() + [
            'uuid' => '00000000-0000-0000-0000-000000000002', 'code' => 'OR-0001', 'label' => 'Escalier',
            'customer_reference' => 'REF-CLIENT', 'validity_date' => '2026-12-31', 'statu' => 1, 'type' => 1,
            'comment' => 'Commentaire de la commande', 'created_at' => '2026-10-01 10:00:00',
        ]);

        foreach ($this->lineRows() as $row) {
            $detail = $row['detail'];
            unset($row['detail']);
            $line = OrderLines::create($row + [
                'orders_id' => $order->id, 'delivered_qty' => 0, 'delivered_remaining_qty' => $row['qty'],
                'invoiced_qty' => 0, 'invoiced_remaining_qty' => $row['qty'], 'internal_delay' => '2026-11-20',
            ]);
            if ($detail) {
                OrderLineDetails::create(['order_lines_id' => $line->id] + $detail);
            }
        }

        return $order;
    }
}
