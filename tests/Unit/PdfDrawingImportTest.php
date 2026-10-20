<?php

namespace Tests\Unit;

use App\Services\AI\AISettingsResolver;
use App\Services\Cad\CadImportService;
use App\Services\Cad\Parsers\PdfParser;
use App\Services\Files\FileRole;
use FPDF;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class PdfDrawingImportTest extends TestCase
{
    /** @var array<int, string> */
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_claude_receives_the_pdf_and_fills_the_line(): void
    {
        $this->useProvider('claude', ['api_key' => 'sk-test', 'model' => 'claude-haiku-4-5-20251001']);

        Http::fake(['*' => Http::response(['content' => [['type' => 'text', 'text' => "```json\n" . json_encode([
            'is_drawing' => true,
            'code' => 'PL-1042',
            'label' => 'Équerre de fixation',
            'material' => 'S235',
            'thickness' => '3,0',
            'x_size' => 250,
            'y_size' => 120.5,
            'weight' => null,
            'qty' => 12,
            'revision' => 'B',
            'finishing' => 'Galvanisation',
            'notes' => null,
        ]) . "\n```"]]])]);

        $parts = app(CadImportService::class)->parse($this->drawing('equerre.pdf'));

        $this->assertCount(1, $parts);
        $line = $parts[0];

        $this->assertSame('PL-1042', $line['code']);
        $this->assertSame('Équerre de fixation', $line['label']);
        $this->assertSame('S235', $line['material']);
        $this->assertSame(3.0, $line['thickness']);
        $this->assertSame(250.0, $line['x_size']);
        $this->assertSame(120.5, $line['y_size']);
        $this->assertNull($line['weight']);
        $this->assertSame(12.0, $line['qty']);
        $this->assertSame(FileRole::PLAN, $line['ged_role']);
        $this->assertContains(['label' => 'Indice', 'value' => 'B'], $line['extra']);
        $this->assertContains(['label' => 'Source', 'value' => 'PDF lu par IA — à vérifier'], $line['extra']);

        Http::assertSent(function (Request $request) {
            $content = $request['messages'][0]['content'];

            return $request->hasHeader('x-api-key', 'sk-test')
                && $content[0]['type'] === 'document'
                && $content[0]['source']['media_type'] === 'application/pdf'
                && str_starts_with(base64_decode($content[0]['source']['data']), '%PDF-');
        });
    }

    public function test_missing_answers_fall_back_on_the_file_name_and_one_piece(): void
    {
        $this->useProvider('claude', ['api_key' => 'sk-test']);

        Http::fake(['*' => Http::response(['content' => [['type' => 'text', 'text' => '{"is_drawing": true, "code": null, "label": "", "thickness": "n/a", "qty": 0}']]])]);

        $line = app(CadImportService::class)->parse($this->drawing('flasque.pdf'))[0];

        $this->assertSame('flasque', $line['code']);
        $this->assertSame('flasque', $line['label']);
        $this->assertNull($line['thickness']);
        $this->assertSame(1, $line['qty']);
    }

    public function test_openai_compatible_provider_gets_the_text_layer(): void
    {
        $this->useProvider('ovh', ['base_url' => 'https://ovh.test/v1', 'model' => 'Mistral-Small']);

        Http::fake(['ovh.test/*' => Http::response(['choices' => [['message' => ['content' => '{"is_drawing": true, "code": "PL-7", "material": "Inox 304L"}']]]])]);

        $line = app(PdfParser::class)->parse($this->drawing('pl7.pdf', 'REPERE PL-7 MATIERE INOX 304L EPAISSEUR 2'));

        $this->assertSame('PL-7', $line['code']);
        $this->assertSame('Inox 304L', $line['material']);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://ovh.test/v1/chat/completions'
            && str_contains($request['messages'][1]['content'], 'MATIERE INOX 304L'));
    }

    public function test_openai_compatible_provider_cannot_read_a_scan(): void
    {
        $this->useProvider('ovh', ['base_url' => 'https://ovh.test/v1']);
        Http::fake();

        $this->expectExceptionMessage('Aucun texte lisible');

        try {
            app(PdfParser::class)->parse($this->drawing('scan.pdf', null));
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_a_document_that_is_not_a_drawing_is_refused(): void
    {
        $this->useProvider('claude', ['api_key' => 'sk-test']);
        Http::fake(['*' => Http::response(['content' => [['type' => 'text', 'text' => '{"is_drawing": false}']]])]);

        $this->expectExceptionMessage('ne ressemble pas à un plan');

        app(PdfParser::class)->parse($this->drawing('facture.pdf'));
    }

    public function test_claude_without_a_key_is_refused_before_any_call(): void
    {
        $this->useProvider('claude', ['api_key' => null]);
        Http::fake();

        $this->expectExceptionMessage('IA non configurée');

        try {
            app(PdfParser::class)->parse($this->drawing('plan.pdf'));
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_a_file_that_is_not_a_pdf_is_refused(): void
    {
        $this->expectExceptionMessage('PDF non reconnu');

        app(PdfParser::class)->parse($this->upload('plan.pdf', 'not a pdf'));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function useProvider(string $provider, array $config): void
    {
        $settings = Mockery::mock(AISettingsResolver::class)->makePartial();
        $settings->shouldReceive('activeProvider')->andReturn($provider);

        $base = ['provider' => $provider, 'api_key' => null, 'model' => 'model', 'max_tokens' => 1024,
            'timeout' => 30, 'base_url' => null, 'source' => 'db'];

        if ($provider === 'claude') {
            $settings->shouldReceive('claude')->andReturn($config + $base);
        } else {
            $settings->shouldReceive('openAiCompatible')->with($provider)->andReturn($config + $base);
        }

        $this->app->instance(AISettingsResolver::class, $settings);
    }

    /**
     * A one-page PDF; without text it stands for a scanned drawing.
     */
    private function drawing(string $name, ?string $text = 'Plan de pièce'): UploadedFile
    {
        $pdf = new FPDF();
        $pdf->AddPage();

        if ($text !== null) {
            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Text(20, 20, $text);
        } else {
            $pdf->Rect(20, 20, 100, 50);
        }

        return $this->upload($name, $pdf->Output('S'));
    }

    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, $content);

        $this->temporaryFiles[] = $path;

        return new UploadedFile($path, $name, null, null, true);
    }
}
