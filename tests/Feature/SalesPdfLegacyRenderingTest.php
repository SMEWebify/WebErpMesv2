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
use App\Models\Workflow\OrderLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Documents\SalesPrintLayout;
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
        $confirmation->forceFill(['code' => 'ARC-0001', 'uuid' => '00000000-0000-0000-0000-0000000000a1'])->save();

        $this->assertMatchesReference(
            "order-confirmation-{$suffix}.html",
            $this->render($view, $confirmation->fresh(), new OrderConfirmationCalculatorService($confirmation->fresh()), 'ARC 1', 'OrderConfirmationLines')
        );
    }

    // ---------------------------------------------------------------------

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
