<?php

namespace Tests\Feature\Purchases;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsUnits;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Services\Documents\DocumentPdfService;
use App\Services\PdfThemeResolver;
use App\Services\PurchaseCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Number;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Non-régression du PDF de commande d'achat.
 *
 * Les références de tests/Fixtures/pdf-purchases ont été rendues AVANT
 * l'introduction des clauses qualité : une commande sans clause doit produire
 * exactement le même HTML. Ne jamais régénérer une référence existante pour
 * faire passer ce test (une référence absente est écrite, test incomplet).
 */
class PurchasePdfLegacyRenderingTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURES = __DIR__ . '/../../Fixtures/pdf-purchases';

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
            'vue standard'      => ['print/pdf-purchases', 'standard'],
            'vue personnalisée' => ['print/custom/pdf-purchases', 'custom'],
        ];
    }

    #[DataProvider('views')]
    public function test_a_purchase_order_without_clause_renders_as_before(string $view, string $suffix): void
    {
        $purchase = $this->makePurchase();

        $this->assertMatchesReference("purchase-{$suffix}.html", $this->render($view, $purchase->fresh()));
    }

    /**
     * Chemin du bouton PDF et des e-mails : sans clause, le service remet à dompdf
     * le HTML de la vue résolue, sans rien y ajouter.
     */
    public function test_the_print_service_adds_nothing_to_an_order_without_clause(): void
    {
        $purchase = $this->makePurchase();
        $view     = app(PdfThemeResolver::class)->resolveForDocument($purchase, 'print/pdf-purchases', app('Factory'));

        $this->assertSame(
            $this->render($view, $purchase->fresh()),
            $this->stabilize(app(DocumentPdfService::class)->html($purchase->fresh()))
        );
    }

    private function render(string $view, Purchases $Document): string
    {
        if (!View::exists($view)) {
            $this->markTestSkipped("Vue {$view} absente (les vues personnalisées ne sont pas versionnées).");
        }

        $calculator          = new PurchaseCalculatorService($Document);
        $typeDocumentName    = __('general_content.purchase_order_trans_key');
        $Factory             = app('Factory');
        $formattedTotalPrice = Number::currency($calculator->getTotalPrice(), 'EUR', config('app.locale'));
        $formattedSubPrice   = Number::currency($calculator->getSubTotal(), 'EUR', config('app.locale'));
        $vatPrice            = $calculator->getVatTotal();
        $normalizeCurrency   = fn ($value) => str_replace(["\u{00A0}", "\u{202F}"], ' ', (string) $value);
        $image               = null;
        $customCss           = null;
        $printRows           = null;

        $Document->Lines = $Document->PurchaseLines;
        unset($Document->PurchaseLines);

        return $this->stabilize(view($view, compact(
            'typeDocumentName', 'Document', 'Factory', 'formattedTotalPrice',
            'formattedSubPrice', 'vatPrice', 'image', 'customCss', 'normalizeCurrency', 'printRows'
        ))->render());
    }

    // La vue imprime la date du jour et l'année du copyright : seules parties variables.
    private function stabilize(string $html): string
    {
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

        $normalize = fn (string $s) => str_replace("\r\n", "\n", $s);

        $this->assertSame($normalize(file_get_contents($path)), $normalize($html), "Le rendu de {$name} a changé.");
    }

    private function makePurchase(): Purchases
    {
        $company = Companies::factory()->create(['label' => 'Aciers de l\'Ouest']);
        $contact = CompaniesContacts::factory()->create([
            'companies_id' => $company->id, 'civility' => 'Mme', 'first_name' => 'Anne',
            'name' => 'Durand', 'number' => '06 11 11 11 11', 'mail' => 'anne@aciers.test',
        ]);
        $address = CompaniesAddresses::factory()->create([
            'companies_id' => $company->id, 'adress' => '5 quai des Aciéries',
            'zipcode' => '56100', 'city' => 'Lorient', 'province' => 'Morbihan', 'country' => 'France',
        ]);

        $purchase = Purchases::factory()->create([
            'code' => 'PU-0001', 'label' => 'Tôles', 'statu' => 1,
            'companies_id' => $company->id, 'companies_contacts_id' => $contact->id,
            'companies_addresses_id' => $address->id, 'comment' => 'Livraison quai 2',
        ]);

        $vat  = AccountingVat::factory()->create(['label' => 'TVA 20', 'rate' => 20]);
        $unit = MethodsUnits::factory()->create(['code' => 'KG', 'label' => 'Kilogramme']);

        foreach ([
            ['ordre' => 1, 'code' => 'T-DC01', 'label' => 'Tôle DC01 2 mm', 'supplier_ref' => 'AO-221', 'qty' => 1500, 'selling_price' => 1.1, 'discount' => 0, 'ind' => null],
            ['ordre' => 2, 'code' => 'T-304',  'label' => 'Tôle inox 304 3 mm', 'supplier_ref' => null, 'qty' => 200, 'selling_price' => 4.25, 'discount' => 5, 'ind' => 'B'],
        ] as $row) {
            PurchaseLines::create($row + PurchaseLines::netPrices($row['selling_price'], $row['qty'], $row['discount']) + [
                'purchases_id' => $purchase->id, 'tasks_id' => 0,
                'methods_units_id' => $unit->id, 'accounting_vats_id' => $vat->id,
            ]);
        }

        return $purchase;
    }
}
