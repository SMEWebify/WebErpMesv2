<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Workflow\Quotes;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuoteApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the show method of QuoteController.
     *
     * @return void
     */
    public function test_can_show_quote()
    {
        // Crée un devis (Quote)
        $quote = Quotes::factory()->create();

        // Fais une requête GET pour afficher ce devis spécifique
        $this->authenticateApiUser();
        $response = $this->getJson("/api/quote/{$quote->id}");

        // Vérifie que la réponse a le statut 200 (succès)
        $response->assertStatus(200);

        // Vérifie que la réponse contient les données du devis
        $response->assertJson([
            'data' => [
                'id' => $quote->id,
                'code' => $quote->code,
                'label' => $quote->label,
                // Ajoute d'autres attributs selon le modèle
            ],
        ]);
    }

    public function test_store_saves_sanitized_svg_picture_from_base64(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::factory()->create());
        $company = \App\Models\Companies\Companies::factory()->create();
        $contact = \App\Models\Companies\CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $address = \App\Models\Companies\CompaniesAddresses::factory()->create(['companies_id' => $company->id]);

        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script><path d="M0 0 L10 10"/></svg>';

        $response = $this->postJson('/api/quote', [
            'label'        => 'Devis RadQuote',
            'companies_id' => $company->id,
            'companies_contacts_id'  => $contact->id,
            'companies_addresses_id' => $address->id,
            'accounting_payment_conditions_id' => \App\Models\Accounting\AccountingPaymentConditions::value('id'),
            'accounting_payment_methods_id'    => \App\Models\Accounting\AccountingPaymentMethod::value('id'),
            'accounting_deliveries_id'         => \App\Models\Accounting\AccountingDelivery::value('id'),
            'lines'    => [[
                'ordre'  => 1,
                'label'  => 'Part1',
                'qty'    => 2,
                'selling_price' => 12.5,
                'accounting_vats_id' => \App\Models\Accounting\AccountingVat::value('id'),
                'methods_units_id'   => \App\Models\Methods\MethodsUnits::value('id'),
                'detail' => [
                    'cam_file'       => 'Part1.sym',
                    'picture_base64' => base64_encode($svg),
                ],
            ]],
        ]);

        $response->assertCreated();

        $detail = \App\Models\Workflow\Quotes::findOrFail($response->json('data.id'))
            ->QuoteLines()->first()->QuoteLineDetails;

        $this->assertStringEndsWith('.svg', $detail->picture);
        $path = public_path('images/quote-lines/' . $detail->picture);
        $this->assertFileExists($path);

        try {
            $stored = file_get_contents($path);
            $this->assertStringContainsString('<path d="M0 0 L10 10"', $stored);
            $this->assertStringNotContainsString('alert', $stored);
        } finally {
            @unlink($path);
        }
    }

    /**
     * Un contenu que ni SvgSanitizer ni GD ne reconnaissent ne laisse ni vignette
     * ni fichier : avant, il était écrit en .bin et affiché comme image cassée.
     */
    public function test_store_ignores_unrecognized_picture_instead_of_writing_bin_file(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::factory()->create());
        $company = \App\Models\Companies\Companies::factory()->create();

        $dir    = public_path('images/quote-lines');
        $before = is_dir($dir) ? scandir($dir) : [];

        $response = $this->postJson('/api/quote', [
            'label'        => 'Devis RadQuote',
            'companies_id' => $company->id,
            'companies_contacts_id'  => \App\Models\Companies\CompaniesContacts::factory()->create(['companies_id' => $company->id])->id,
            'companies_addresses_id' => \App\Models\Companies\CompaniesAddresses::factory()->create(['companies_id' => $company->id])->id,
            'accounting_payment_conditions_id' => \App\Models\Accounting\AccountingPaymentConditions::value('id'),
            'accounting_payment_methods_id'    => \App\Models\Accounting\AccountingPaymentMethod::value('id'),
            'accounting_deliveries_id'         => \App\Models\Accounting\AccountingDelivery::value('id'),
            'lines' => [[
                'ordre' => 1,
                'label' => 'Part1',
                'qty'   => 1,
                'selling_price' => 10,
                'accounting_vats_id' => \App\Models\Accounting\AccountingVat::value('id'),
                'methods_units_id'   => \App\Models\Methods\MethodsUnits::value('id'),
                'detail' => [
                    'cam_file'       => 'Part1.sym',
                    'picture_base64' => base64_encode('<html><body>pas une image</body></html>'),
                ],
            ]],
        ]);

        $response->assertCreated();

        $detail = \App\Models\Workflow\Quotes::findOrFail($response->json('data.id'))
            ->QuoteLines()->first()->QuoteLineDetails;

        $this->assertNull($detail->picture);
        $this->assertSame('Part1.sym', $detail->cam_file);
        $this->assertSame($before, is_dir($dir) ? scandir($dir) : []);
    }

    /**
     * Flux RadQuote (CustomGUIFunction.vb) : devis via l'API, puis SVG + .sym
     * rattachés à la ligne dans la GED, lignes retrouvées par leur ordre.
     */
    public function test_radquote_files_are_attached_to_quote_line_in_ged(): void
    {
        \Illuminate\Support\Facades\Storage::fake(config('files.disk'));
        \Laravel\Sanctum\Sanctum::actingAs(\App\Models\User::factory()->create());
        $company = \App\Models\Companies\Companies::factory()->create();

        $response = $this->postJson('/api/quote', [
            'label'        => 'Devis RadQuote GED',
            'companies_id' => $company->id,
            'companies_contacts_id'  => \App\Models\Companies\CompaniesContacts::factory()->create(['companies_id' => $company->id])->id,
            'companies_addresses_id' => \App\Models\Companies\CompaniesAddresses::factory()->create(['companies_id' => $company->id])->id,
            'accounting_payment_conditions_id' => \App\Models\Accounting\AccountingPaymentConditions::value('id'),
            'accounting_payment_methods_id'    => \App\Models\Accounting\AccountingPaymentMethod::value('id'),
            'accounting_deliveries_id'         => \App\Models\Accounting\AccountingDelivery::value('id'),
            'lines' => [[
                'ordre' => 1,
                'label' => 'Part1',
                'qty'   => 1,
                'selling_price' => 10,
                'accounting_vats_id' => \App\Models\Accounting\AccountingVat::value('id'),
                'methods_units_id'   => \App\Models\Methods\MethodsUnits::value('id'),
            ]],
        ])->assertCreated();

        $lineId = collect($response->json('data.quote_lines'))->firstWhere('ordre', 1)['id'];

        $this->post('/api/files/json/store', [
            'fileable_type' => 'quote-line',
            'fileable_id'   => $lineId,
            'role'          => 'vectoriel',
            'is_primary'    => 'true',
            'files'         => [\Illuminate\Http\UploadedFile::fake()->createWithContent('Part1.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>')],
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->post('/api/files/json/store', [
            'fileable_type' => 'quote-line',
            'fileable_id'   => $lineId,
            'role'          => 'cam',
            'files'         => [\Illuminate\Http\UploadedFile::fake()->createWithContent('Part1.sym', 'RADAN')],
        ], ['Accept' => 'application/json'])->assertCreated();

        $files = \App\Models\Workflow\QuoteLines::findOrFail($lineId)->files()->get();

        $this->assertSame(['Part1.svg' => 'vectoriel', 'Part1.sym' => 'cam'], $files->sortBy('original_file_name')->mapWithKeys(fn ($f) => [$f->original_file_name => $f->pivot->role])->all());
        $this->assertTrue((bool) $files->firstWhere('original_file_name', 'Part1.svg')->pivot->is_primary);
    }
}
