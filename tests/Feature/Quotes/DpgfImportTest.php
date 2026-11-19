<?php

namespace Tests\Feature\Quotes;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\File;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Quotes\DpgfImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Import DPGF : le classeur devient un aperçu (rien n'est écrit), l'utilisateur
 * corrige, puis les lignes relues sont créées en fin de devis et le classeur
 * rangé dans la GED du devis.
 */
class DpgfImportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MethodsUnits $unit;
    private MethodsUnits $squareMetre;
    private MethodsUnits $set;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Storage::fake('local');
        Storage::fake(config('files.disk'));
        config(['dpgf.enabled' => true]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        app()->forgetInstance('Factory');
        app()->instance('Factory', Factory::create(['name' => 'Métallerie', 'curency' => 'EUR', 'pdf_header_font_color' => '#ddd']));

        MethodsUnits::query()->update(['default' => 0]);
        AccountingVat::query()->update(['default' => 0]);
        $this->unit = MethodsUnits::factory()->create(['code' => 'UNIT', 'label' => 'Unité', 'default' => 1]);
        $this->squareMetre = MethodsUnits::factory()->create(['code' => 'M2', 'label' => 'Mètre carré', 'default' => 0]);
        $this->set = MethodsUnits::factory()->create(['code' => 'ENS', 'label' => 'Ensemble', 'default' => 0]);
        AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);
    }

    private function quote(int $statu = 1): Quotes
    {
        return Quotes::factory()->create(['code' => 'QT-0001', 'statu' => $statu, 'user_id' => $this->user->id]);
    }

    /** Classeur d'appel d'offres : titre, en-tête en ligne 4, deux lots, totaux. */
    private function workbook(array $secondSheet = []): UploadedFile
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet()->setTitle('Lot 08 Serrurerie');
        $sheet->fromArray([
            ['Résidence Les Tilleuls — DPGF'],
            ['Lot 08 : Métallerie / Serrurerie'],
            [],
            ['N°', 'Désignation des ouvrages', 'U', 'Qté', 'P.U. HT', 'Montant HT'],
            ['8.1', 'GARDE-CORPS'],
            ['8.1.1', 'Garde-corps acier galvanisé thermolaqué, main courante Ø42', 'ml', '24,5', null, null],
            ['8.1.2', 'Habillage tôle perforée des balcons', 'm²', '12', '85,50', '1026'],
            ['8.1.3', 'Installation de chantier', 'Ens', null, null, null],
            ['8.1.4', 'Échafaudage', 'PM', null, null, null],
            [null, 'Fixations par chevilles chimiques, compris toutes sujétions.'],
            ['8.1.5', str_repeat('Portail coulissant motorisé ', 12), 'U', '1', null, '4500'],
            [null, 'Total lot 8.1', null, null, null, '5526'],
            ['8.2', 'Escaliers'],
            ['8.2.1', 'Escalier hélicoïdal', 'U', null, null, null],
            [null, 'TOTAL GÉNÉRAL HT', null, null, null, '5526'],
            [null, 'TVA 20 %', null, null, null, '1105,20'],
            [null, 'TOTAL TTC', null, null, null, '6631,20'],
        ]);

        if ($secondSheet !== []) {
            $book->createSheet()->setTitle('Variantes')->fromArray($secondSheet);
        }

        $path = tempnam(sys_get_temp_dir(), 'dpgf') . '.xlsx';
        (new Xlsx($book))->save($path);

        return new UploadedFile($path, 'DPGF lot 08.xlsx', null, null, true);
    }

    private function lineAt(array $lines, int $row): array
    {
        foreach ($lines as $line) {
            if ($line['row'] === $row) {
                return $line;
            }
        }
        $this->fail("Ligne $row absente de l'aperçu");
    }

    /** @test */
    public function the_workbook_is_read_into_a_preview_without_saving_anything()
    {
        $quote = $this->quote();

        $response = $this->post(route('quotes.lines.json.dpgf.preview', ['quoteId' => $quote->id]), [
            'file' => $this->workbook(),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(4, $response->json('header_row'), 'en-tête trouvé sous le titre du document');
        $this->assertSame(['code' => 0, 'label' => 1, 'unit' => 2, 'qty' => 3, 'price' => 4, 'amount' => 5], $response->json('columns'));
        $this->assertSame(['Lot 08 Serrurerie'], $response->json('sheets'));

        $lines = $response->json('lines');

        $this->assertSame('section', $this->lineAt($lines, 5)['type']);
        $this->assertSame('8.1 GARDE-CORPS', $this->lineAt($lines, 5)['label'], 'numéro du titre gardé dans le libellé');

        $guardrail = $this->lineAt($lines, 6);
        $this->assertSame('article', $guardrail['type']);
        $this->assertSame('8.1.1', $guardrail['code']);
        $this->assertEquals(24.5, $guardrail['qty'], 'virgule décimale');
        $this->assertNull($guardrail['methods_units_id'], 'ml sans unité équivalente');
        $this->assertSame('ml', $guardrail['unit_text']);
        $this->assertNull($guardrail['selling_price'], 'DPGF vierge : aucun prix');

        $cladding = $this->lineAt($lines, 7);
        $this->assertSame($this->squareMetre->id, $cladding['methods_units_id'], 'm² reconnu comme M2');
        $this->assertEquals(85.5, $cladding['selling_price']);

        $site = $this->lineAt($lines, 8);
        $this->assertSame($this->set->id, $site['methods_units_id']);
        $this->assertEquals(1, $site['qty'], 'ensemble sans quantité = 1');
        $this->assertFalse($site['qty_missing']);

        $this->assertSame('text', $this->lineAt($lines, 9)['type'], 'poste pour mémoire');
        $this->assertSame('8.1.4 Échafaudage — PM', $this->lineAt($lines, 9)['label']);
        $this->assertSame('text', $this->lineAt($lines, 10)['type'], 'ligne descriptive sans numéro');

        $gate = $this->lineAt($lines, 11);
        $this->assertSame($this->unit->id, $gate['methods_units_id'], 'U reconnu comme UNIT');
        $this->assertEquals(4500, $gate['selling_price'], 'prix déduit du montant');
        $this->assertSame(DpgfImporter::LABEL_MAX, mb_strlen($gate['label']));
        $this->assertStringStartsWith('Portail coulissant', $gate['description']);

        $this->assertSame('subtotal', $this->lineAt($lines, 12)['type']);
        $this->assertSame('section', $this->lineAt($lines, 13)['type']);
        $this->assertTrue($this->lineAt($lines, 14)['qty_missing'], 'quantité à la charge de l\'entreprise');
        $this->assertSame('ignore', $this->lineAt($lines, 15)['type'], 'total général');
        $this->assertSame('ignore', $this->lineAt($lines, 16)['type'], 'TVA');
        $this->assertSame('ignore', $this->lineAt($lines, 17)['type'], 'TTC');
        $this->assertCount(13, $lines, 'lignes de titre du document et vides écartées');

        $this->assertSame(0, QuoteLines::count());
        $this->assertSame(0, File::count());
        $this->assertCount(1, Storage::disk('local')->files('imports/' . $this->user->id));
    }

    /** @test */
    public function another_sheet_and_the_users_columns_are_read_on_demand()
    {
        $quote = $this->quote();
        $token = $this->post(route('quotes.lines.json.dpgf.preview', ['quoteId' => $quote->id]), [
            'file' => $this->workbook([
                ['Article', 'Libellé', 'Unité', 'Quantité', 'Prix unitaire', 'Prix total HT'],
                ['V1', 'Option inox 316L', 'u', '2', '120', '240'],
            ]),
        ], ['Accept' => 'application/json'])->assertOk()->json('token');

        $second = $this->postJson(route('quotes.lines.json.dpgf.analyse', ['quoteId' => $quote->id]), [
            'token' => $token, 'sheet' => 1,
        ])->assertOk();
        $this->assertSame(1, $second->json('sheet'));
        $this->assertSame(['code' => 0, 'label' => 1, 'unit' => 2, 'qty' => 3, 'price' => 4, 'amount' => 5], $second->json('columns'), '« Prix total HT » est un montant');
        $this->assertSame('Option inox 316L', $second->json('lines.0.label'));
        $this->assertEquals(120, $second->json('lines.0.selling_price'));

        // Colonnes choisies à la main (désignation seule) : rien n'est plus chiffrable.
        $manual = $this->postJson(route('quotes.lines.json.dpgf.analyse', ['quoteId' => $quote->id]), [
            'token' => $token, 'sheet' => 0, 'header_row' => 4,
            'columns' => ['code' => null, 'label' => 1, 'unit' => null, 'qty' => null, 'price' => null, 'amount' => null],
        ])->assertOk();
        $this->assertSame(4, $manual->json('header_row'));
        $this->assertSame('GARDE-CORPS', $this->lineAt($manual->json('lines'), 5)['label']);
        $this->assertSame('section', $this->lineAt($manual->json('lines'), 5)['type'], 'titre en capitales');
        $this->assertSame('text', $this->lineAt($manual->json('lines'), 6)['type']);

        $this->postJson(route('quotes.lines.json.dpgf.analyse', ['quoteId' => $quote->id]), [
            'token' => '00000000-0000-4000-8000-000000000000',
        ])->assertStatus(410);
    }

    /** @test */
    public function an_unrecognised_header_asks_for_the_columns()
    {
        $quote = $this->quote();
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([['A', 'B'], ['1', 'Garde-corps']]);
        $path = tempnam(sys_get_temp_dir(), 'dpgf') . '.xlsx';
        (new Xlsx($book))->save($path);

        $response = $this->post(route('quotes.lines.json.dpgf.preview', ['quoteId' => $quote->id]), [
            'file' => new UploadedFile($path, 'devis.xlsx', null, null, true),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(0, $response->json('header_row'));
        $this->assertNull($response->json('columns.label'));
        $this->assertSame([], $response->json('lines'));
        $this->assertCount(2, $response->json('sample'), 'début de la feuille montré pour choisir');
    }

    /** @test */
    public function reviewed_lines_are_created_at_the_end_and_the_workbook_is_filed()
    {
        $quote = $this->quote();
        QuoteLines::create([
            'quotes_id' => $quote->id, 'ordre' => 3, 'code' => 'L-1', 'label' => 'Ligne existante',
            'qty' => 1, 'selling_price' => 10, 'discount' => 0, 'statu' => 1,
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => AccountingVat::getDefault()->id,
        ]);
        $token = $this->post(route('quotes.lines.json.dpgf.preview', ['quoteId' => $quote->id]), [
            'file' => $this->workbook(),
        ], ['Accept' => 'application/json'])->json('token');

        $response = $this->postJson(route('quotes.lines.json.dpgf.store', ['quoteId' => $quote->id]), [
            'token' => $token,
            'filename' => 'DPGF lot 08.xlsx',
            'lines' => [
                ['type' => 'section', 'label' => '8.1 GARDE-CORPS'],
                ['type' => 'article', 'code' => '8.1.1', 'label' => 'Garde-corps acier', 'qty' => 24.5, 'methods_units_id' => null, 'selling_price' => 180, 'description' => 'Texte complet du poste'],
                ['type' => 'text', 'label' => '8.1.4 Échafaudage — PM'],
                ['type' => 'subtotal', 'label' => null],
            ],
        ])->assertCreated();

        $this->assertCount(4, $response->json('lines'));
        $this->assertSame(['section', 'article', 'text', 'subtotal'], array_column($response->json('lines'), 'line_type'));

        $article = QuoteLines::where('code', '8.1.1')->firstOrFail();
        $this->assertSame(5, (int) $article->ordre);
        $this->assertSame($this->unit->id, (int) $article->methods_units_id, 'unité par défaut');
        $this->assertEquals(24.5, $article->qty);
        $this->assertEquals(180, $article->getRawOriginal('selling_price'));
        $this->assertSame('Texte complet du poste', QuoteLineDetails::where('quote_lines_id', $article->id)->value('external_comment'));

        $section = QuoteLines::where('label', '8.1 GARDE-CORPS')->firstOrFail();
        $this->assertSame(4, (int) $section->ordre);
        $this->assertEquals(0, $section->getRawOriginal('selling_price'));
        $this->assertSame('', (string) $section->code);

        $file = $quote->files()->firstOrFail();
        $this->assertSame('DPGF lot 08.xlsx', $file->original_file_name);
        $this->assertSame([], Storage::disk('local')->files('imports/' . $this->user->id), 'fichier temporaire supprimé');
    }

    /** @test */
    public function lines_are_validated_before_creation()
    {
        $quote = $this->quote();

        $this->postJson(route('quotes.lines.json.dpgf.store', ['quoteId' => $quote->id]), [
            'lines' => [
                ['type' => 'ignore', 'label' => 'TOTAL TTC'],
                ['type' => 'article', 'label' => '', 'qty' => -1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['lines.0.type', 'lines.1.label', 'lines.1.qty']);

        $this->assertSame(0, QuoteLines::count());
    }

    /** @test */
    public function a_quote_that_is_no_longer_open_refuses_the_import()
    {
        $quote = $this->quote(2);

        $this->post(route('quotes.lines.json.dpgf.preview', ['quoteId' => $quote->id]), [
            'file' => $this->workbook(),
        ], ['Accept' => 'application/json'])->assertForbidden();
        $this->postJson(route('quotes.lines.json.dpgf.store', ['quoteId' => $quote->id]), [
            'lines' => [['type' => 'text', 'label' => 'Texte']],
        ])->assertForbidden();

        $this->assertSame(0, QuoteLines::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @test */
    public function without_the_option_the_import_does_not_exist()
    {
        config(['dpgf.enabled' => false]);
        $quote = $this->quote();

        $this->post(route('quotes.lines.json.dpgf.preview', ['quoteId' => $quote->id]), [
            'file' => $this->workbook(),
        ], ['Accept' => 'application/json'])->assertNotFound();
        $this->postJson(route('quotes.lines.json.dpgf.analyse', ['quoteId' => $quote->id]), [
            'token' => '00000000-0000-4000-8000-000000000000',
        ])->assertNotFound();
        $this->postJson(route('quotes.lines.json.dpgf.store', ['quoteId' => $quote->id]), [
            'lines' => [['type' => 'text', 'label' => 'Texte']],
        ])->assertNotFound();

        $this->assertSame(0, QuoteLines::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    /** @test */
    public function amounts_are_read_whatever_their_format()
    {
        $this->assertSame(1234.5, DpgfImporter::number('1 234,50 €'));
        $this->assertSame(1234.5, DpgfImporter::number('1.234,5'));
        $this->assertSame(1234.5, DpgfImporter::number('1,234.50'));
        $this->assertSame(12.0, DpgfImporter::number('12'));
        $this->assertNull(DpgfImporter::number('PM'));
        $this->assertNull(DpgfImporter::number('-3'));
        $this->assertNull(DpgfImporter::number(''));
        $this->assertSame('m2', DpgfImporter::unitKey('m²'));
        $this->assertSame('m l', DpgfImporter::unitKey('M.L.'));
    }
}
