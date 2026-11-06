<?php

namespace Tests\Feature\Imports;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Imports de l'écran Imports/Exports et des onglets « Import des lignes » :
 * CSV au ; ou à la , en UTF-8 ou Windows-1252, XLSX, accents intacts, en-tête
 * ni erreur ni ligne importée, rejets listés avec leur numéro de ligne.
 */
class ImportCsvServiceTest extends TestCase
{
    use ImportTestFiles;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
            \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
        $this->actingAs(User::factory()->create());

        foreach ([AccountingVat::class, AccountingPaymentConditions::class, AccountingPaymentMethod::class, AccountingDelivery::class, MethodsUnits::class] as $model) {
            $model::query()->update(['default' => 0]);
            $model::query()->limit(1)->update(['default' => 1]);
        }
        MethodsFamilies::factory()->create();
    }

    private function companyWithDefaults(string $code): Companies
    {
        $company = Companies::factory()->create(['code' => $code]);
        CompaniesAddresses::factory()->create(['companies_id' => $company->id, 'default' => 0]);
        CompaniesAddresses::factory()->create(['companies_id' => $company->id, 'default' => 1, 'label' => 'Adresse par défaut']);
        CompaniesContacts::factory()->create(['companies_id' => $company->id, 'default' => 0]);
        CompaniesContacts::factory()->create(['companies_id' => $company->id, 'default' => 1, 'first_name' => 'Contact par défaut']);

        return $company;
    }

    // ── Sociétés ─────────────────────────────────────────────────────────────

    #[DataProvider('formats')]
    public function test_companies_are_imported_with_their_accents(string $format): void
    {
        $file = $this->{$format}([
            ['Code', 'Raison sociale', 'Site'],
            ['CLI-01', 'Tôlerie Martin', 'www.tolerie.fr'],
            ['CLI-02', 'Équerres & Cie', null],
        ]);

        $this->post(route('companies.import'), ['import_file' => $file, 'header' => 'true', 'code' => 0, 'label' => 1, 'website' => 2])
            ->assertRedirect()
            ->assertSessionHas('success', __('imports.result', ['created' => 2, 'rejected' => 0]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Tôlerie Martin', Companies::where('code', 'CLI-01')->value('label'));
        $this->assertSame('Équerres & Cie', Companies::where('code', 'CLI-02')->value('label'));
        $this->assertSame('www.tolerie.fr', Companies::where('code', 'CLI-01')->value('website'));
        $this->assertNull(Companies::where('code', 'CLI-02')->value('website'));
    }

    public function test_an_optional_column_is_read_whatever_the_number_of_lines(): void
    {
        // Avant : array_key_exists(5, $lignes) → naf_code lu seulement si le fichier avait plus de 5 lignes.
        $file = $this->csvUtf8Bom([
            ['CLI-10', 'Pliage Ouest', '', '', '123456789', '2511Z'],
            ['CLI-11', 'Soudure Est', '', '', '987654321', '2562B'],
        ]);

        $this->post(route('companies.import'), ['import_file' => $file, 'code' => 0, 'label' => 1, 'siren' => 4, 'naf_code' => 5])
            ->assertSessionHas('success', __('imports.result', ['created' => 2, 'rejected' => 0]));

        $this->assertSame('2511Z', Companies::where('code', 'CLI-10')->value('naf_code'));
        $this->assertSame('987654321', Companies::where('code', 'CLI-11')->value('siren'));
    }

    public function test_rejected_lines_are_listed_without_hiding_the_created_ones(): void
    {
        $file = $this->csvUtf8Bom([
            ['code', 'label'],
            ['CLI-20', 'Laser Nord'],
            ['', 'Sans code'],
            [],
            ['CLI-21', 'Laser Sud'],
        ]);

        $response = $this->post(route('companies.import'), ['import_file' => $file, 'header' => 'true', 'code' => 0, 'label' => 1]);

        $response->assertSessionHas('success', __('imports.result', ['created' => 2, 'rejected' => 1]));
        $errors = session('errors')->all();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString(__('imports.reject.line', ['line' => 3, 'reason' => '']), $errors[0]);
        $this->assertSame(2, Companies::whereIn('code', ['CLI-20', 'CLI-21'])->count());
    }

    public function test_nothing_created_is_a_failure(): void
    {
        $file = $this->csvUtf8Bom([['code', 'label'], ['', 'Sans code']]);

        $this->post(route('companies.import'), ['import_file' => $file, 'header' => 'true', 'code' => 0, 'label' => 1])
            ->assertSessionMissing('success')
            ->assertSessionHas('error', __('imports.nothing_created', ['rejected' => 1]));
    }

    public function test_an_unsupported_file_is_refused(): void
    {
        $file = UploadedFile::fake()->create('clients.pdf', 10);

        $this->post(route('companies.import'), ['import_file' => $file, 'code' => 0, 'label' => 1])
            ->assertSessionHasErrors();
        $this->assertSame(0, Companies::where('csv_file_name', 'clients.pdf')->count());
    }

    // ── Articles ─────────────────────────────────────────────────────────────

    #[DataProvider('formats')]
    public function test_products_are_imported_with_their_accents_and_decimals(string $format): void
    {
        $service = MethodsServices::factory()->create();
        $file = $this->{$format}([
            ['Référence', 'Désignation', 'Matière', 'Épaisseur'],
            ['EQ-001', 'Équerre pliée 90°', 'Inox 304L', '1,5'],
            ['TL-002', 'Tôle perforée', 'Acier', 2],
        ]);

        $this->post(route('products.import'), [
            'import_file' => $file, 'header' => 'true',
            'methods_services_id' => $service->id, 'methods_families_id' => MethodsFamilies::value('id'), 'methods_units_id' => MethodsUnits::value('id'),
            'code' => 0, 'label' => 1, 'material' => 2, 'thickness' => 3,
        ])->assertSessionHas('success', __('imports.result', ['created' => 2, 'rejected' => 0]));

        $product = Products::where('code', 'EQ-001')->sole();
        $this->assertSame('Équerre pliée 90°', $product->label);
        $this->assertEquals(1.5, (float) $product->thickness);
        $this->assertEquals(2.0, (float) Products::where('code', 'TL-002')->value('thickness'));
    }

    // ── Devis et commandes ──────────────────────────────────────────────────

    #[DataProvider('formats')]
    public function test_quotes_take_the_default_contact_and_address_of_the_customer(string $format): void
    {
        $company = $this->companyWithDefaults('CLI-30');
        $file = $this->{$format}([
            ['code', 'libellé', 'client', 'validité'],
            ['DEV-30', 'Capot tôlé', 'CLI-30', '25/11/2026'],
            ['DEV-31', 'Client inconnu', 'XXX', null],
        ]);

        $this->post(route('quotes.import'), ['import_file' => $file, 'header' => 'true', 'code' => 0, 'label' => 1, 'companies_id' => 2, 'validity_date' => 3])
            ->assertSessionHas('success', __('imports.result', ['created' => 1, 'rejected' => 1]));

        $quote = Quotes::where('code', 'DEV-30')->sole();
        $this->assertSame('Capot tôlé', $quote->label);
        $this->assertSame(CompaniesContacts::where('companies_id', $company->id)->where('default', 1)->value('id'), $quote->companies_contacts_id);
        $this->assertSame(CompaniesAddresses::where('companies_id', $company->id)->where('default', 1)->value('id'), $quote->companies_addresses_id);
        $this->assertSame('2026-11-25', substr((string) $quote->validity_date, 0, 10));
        $this->assertStringContainsString(__('imports.reject.company_unknown', ['code' => 'XXX']), session('errors')->first());
    }

    public function test_orders_take_the_default_contact_and_address_of_the_customer(): void
    {
        $company = $this->companyWithDefaults('CLI-40');
        $file = $this->xlsx([['code', 'libellé', 'client'], ['CMD-40', 'Bâti soudé', 'CLI-40']]);

        $this->post(route('orders.import'), ['import_file' => $file, 'header' => 'true', 'code' => 0, 'label' => 1, 'companies_id' => 2])
            ->assertSessionHas('success', __('imports.result', ['created' => 1, 'rejected' => 0]));

        $order = Orders::where('code', 'CMD-40')->sole();
        $this->assertSame('Bâti soudé', $order->label);
        $this->assertSame(CompaniesContacts::where('companies_id', $company->id)->where('default', 1)->value('id'), $order->companies_contacts_id);
        $this->assertSame(CompaniesAddresses::where('companies_id', $company->id)->where('default', 1)->value('id'), $order->companies_addresses_id);
    }

    // ── Lignes de devis et de commande ──────────────────────────────────────

    #[DataProvider('formats')]
    public function test_quote_lines_are_imported(string $format): void
    {
        $quote = Quotes::factory()->create();
        $file = $this->{$format}([
            ['code', 'désignation', 'qté', 'prix', 'remise'],
            ['L-01', 'Équerre', 4, '12,5', null],
            ['L-02', 'Tôle pliée', 2, 30, 10],
        ]);

        $this->post(route('quotes.lines.import', ['idQuote' => $quote->id]), [
            'import_file' => $file, 'header' => 'true', 'code' => 0, 'label' => 1, 'qty' => 2, 'selling_price' => 3, 'discount' => 4,
        ])->assertSessionHas('success', __('imports.result', ['created' => 2, 'rejected' => 0]));

        $line = QuoteLines::where('quotes_id', $quote->id)->where('code', 'L-01')->sole();
        $this->assertSame('Équerre', $line->label);
        $this->assertEquals(12.5, (float) $line->getRawOriginal('selling_price'));
        $this->assertEquals(0, (float) $line->discount);
        $this->assertTrue(\App\Models\Workflow\QuoteLineDetails::where('quote_lines_id', $line->id)->exists());
    }

    public function test_order_lines_are_imported_after_the_existing_ones(): void
    {
        $order = Orders::factory()->create();
        $file = $this->xlsx([
            ['code', 'désignation', 'qté', 'prix', 'livraison'],
            ['OL-01', 'Châssis soudé', 3, 120.5, '15/12/2026'],
            ['OL-02', 'Capot', 1, 40, null],
        ]);

        $this->post(route('orders.lines.import', ['idOrder' => $order->id]), [
            'import_file' => $file, 'header' => 'true', 'code' => 0, 'label' => 1, 'qty' => 2, 'selling_price' => 3, 'delivery_date' => 4,
        ])->assertSessionHas('success', __('imports.result', ['created' => 2, 'rejected' => 0]));

        $lines = \App\Models\Workflow\OrderLines::where('orders_id', $order->id)->orderBy('ordre')->get();
        $this->assertSame(['OL-01', 'OL-02'], $lines->pluck('code')->all());
        $this->assertSame('Châssis soudé', $lines[0]->label);
        $this->assertEquals(3, (float) $lines[0]->delivered_remaining_qty);
        $this->assertSame('2026-12-15', substr((string) $lines[0]->delivery_date, 0, 10));
        $this->assertGreaterThan($lines[0]->ordre, $lines[1]->ordre);
    }

    // ── Jeu de démonstration (docs/imports-exemples) ────────────────────────

    public function test_demo_files_import_without_any_rejection(): void
    {
        $demo = fn (string $name) => new UploadedFile(base_path('docs/imports-exemples/' . $name), $name, null, null, true);

        $this->post(route('companies.import'), [
            'import_file' => $demo('clients.xlsx'), 'header' => 'true',
            'code' => 0, 'label' => 1, 'siren' => 2, 'naf_code' => 3, 'intra_community_vat' => 4, 'website' => 5, 'discount' => 6,
        ])->assertSessionHas('success', __('imports.result', ['created' => 5, 'rejected' => 0]))->assertSessionHasNoErrors();
        $this->assertSame('Ascenseurs & Équipements de l\'Ouest', Companies::where('code', 'C00045')->value('label'));

        $this->post(route('products.import'), [
            'import_file' => $demo('articles.xlsx'), 'header' => 'true',
            'methods_services_id' => MethodsServices::factory()->create()->id, 'methods_families_id' => MethodsFamilies::value('id'), 'methods_units_id' => MethodsUnits::value('id'),
            'code' => 0, 'label' => 1, 'ind' => 2, 'material' => 3, 'thickness' => 4, 'x_size' => 5, 'y_size' => 6, 'weight' => 7,
            'purchased_price' => 8, 'selling_price' => 9, 'finishing' => 10, 'comment' => 11,
        ])->assertSessionHas('success', __('imports.result', ['created' => 6, 'rejected' => 0]))->assertSessionHasNoErrors();
        $this->assertSame('Équerre de fixation galvanisée', Products::where('code', 'EQU-GALVA-50')->value('label'));
        $this->assertEquals(1.5, (float) Products::where('code', 'TOLE-304-15')->value('thickness'));

        \App\Models\Planning\Status::create(['title' => 'Open', 'order' => 1]);
        foreach (['LASER' => 1, 'PLIAGE' => 1, 'SOUDURE' => 1, 'MATIERE' => 3] as $code => $type) {
            MethodsServices::factory()->create(['code' => $code, 'type' => $type]);
        }
        $line = QuoteLines::factory()->create();
        $data = $this->post(route('task.manage.json.import-csv', ['id_type' => 'quote_lines_id', 'id_page' => $line->quotes_id, 'id_line' => $line->id]), ['csv_file' => $demo('gamme-support-plie.xlsx')], ['Accept' => 'application/json'])
            ->assertOk()->json();
        $this->assertSame(4, $data['success_count']);
        $this->assertSame([], $data['errors']);
        $this->assertSame(['Découpe laser fibre', 'Pliage 2 plis à 90°', 'Soudure MIG des renforts'], array_column($data['technical_cut'], 'label'));
    }

    // ── Compatibilité : fichiers d'exemple du dépôt (public/imports) ────────

    public function test_sample_files_of_the_repository_still_import(): void
    {
        $sample = fn (string $name) => new UploadedFile(public_path('imports/' . $name), $name, null, null, true);

        $this->post(route('companies.import'), ['import_file' => $sample('CompanieCsvTestImport.csv'), 'header' => 'true', 'code' => 0, 'label' => 1, 'website' => 2, 'discount' => 3])
            ->assertSessionHasNoErrors();
        $this->assertSame('Companie 1', Companies::where('code', 'COMP01')->value('label'));

        $lines = count(array_filter(file(public_path('imports/test_products_20.csv')), 'trim')) - 1;
        $this->post(route('products.import'), [
            'import_file' => $sample('test_products_20.csv'), 'header' => 'true',
            'methods_services_id' => MethodsServices::factory()->create()->id, 'methods_families_id' => MethodsFamilies::value('id'), 'methods_units_id' => MethodsUnits::value('id'),
            'code' => 0, 'label' => 1, 'ind' => 2, 'purchased_price' => 3, 'selling_price' => 4, 'thickness' => 6,
        ])->assertSessionHas('success', __('imports.result', ['created' => $lines, 'rejected' => 0]));
        $this->assertSame('Label_1', Products::where('code', 'CODE_1')->value('label'));

        // Fichier à la virgule, sans en-tête : lu en une seule colonne avant la détection du séparateur.
        $this->post(route('products.import'), [
            'import_file' => $sample('formats_toles_laser_2000x1000.csv'),
            'methods_services_id' => MethodsServices::value('id'), 'methods_families_id' => MethodsFamilies::value('id'), 'methods_units_id' => MethodsUnits::value('id'),
            'code' => 0, 'label' => 1, 'material' => 4, 'thickness' => 5, 'finishing' => 14,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Tôle laser', Products::where('code', 'TL-AC-2000x1000-EP1')->value('finishing'));

        // Fichier ISO-8859 (en-tête « date de validité »), dates au format français.
        $this->companyWithDefaults('XEGID310');
        $this->post(route('quotes.import'), ['import_file' => $sample('QuoteTestImport.csv'), 'header' => 'true', 'code' => 0, 'label' => 1, 'customer_reference' => 2, 'companies_id' => 3, 'validity_date' => 4, 'comment' => 5])
            ->assertSessionHas('success');
        $this->assertSame('Chantier 1', Quotes::where('code', 'DEVIS-1')->value('customer_reference'));
    }
}
