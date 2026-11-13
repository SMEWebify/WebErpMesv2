<?php

namespace Tests\Feature\Nest2Bend;

use App\Models\File;
use App\Models\Workflow\BendingSimulation;
use App\Models\Workflow\QuoteLines;
use App\Services\Integrations\Nest2Bend\Data\BendingResult;
use App\Services\Integrations\Nest2Bend\Data\SetupSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fiche de réglage PDF (`fiche` du résultat) : téléchargée côté serveur avec la clé, rangée
 * dans la GED de la ligne (nouvelle version s'il y en a déjà une), et jamais bloquante pour
 * l'enregistrement du temps.
 */
class BendingSetupSheetTest extends TestCase
{
    use RefreshDatabase;
    use Nest2BendTestHelpers;

    private const SESSION = '7f1c2a54-9a0e-4c5e-9f1d-2b3c4d5e6f70';
    private const RESULT = '0193f2a1-7c4e-7b2d-9a1f-3c5d7e9f1a2b';
    private const SHEET = self::N2B . '/api/v1/fiches/' . self::RESULT . '.pdf';
    private const PDF = "%PDF-1.7\n1 0 obj << /Type /Catalog >> endobj\ntrailer\n%%EOF\n";
    private const SHEET_NAME = 'fiche-reglage-DV-2026-0142-L3-2026-11-12.pdf';

    /** @var list<string> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpNest2Bend();

        Event::listen(MessageLogged::class, function (MessageLogged $event) {
            $this->logged[] = $event->message . ' ' . json_encode($event->context);
        });
    }

    private function route(string $name, QuoteLines $line): string
    {
        return route('quotes.lines.nest2bend.' . $name, ['quoteId' => $line->quotes_id, 'id' => $line->id]);
    }

    private function pdf(): \GuzzleHttp\Promise\PromiseInterface
    {
        return Http::response(self::PDF, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . self::SHEET_NAME . '"',
        ]);
    }

    /**
     * @param  mixed  $sheet  réponse (ou closure) pour l'URL de la fiche
     */
    private function fake(mixed $sheet, ?string $fiche = self::SHEET): void
    {
        Http::fake([
            self::SHEET => $sheet,
            self::N2B . '/api/v1/embed-sessions' => Http::response([
                'session_id' => self::SESSION,
                'url'        => self::N2B . '/integre/abc',
                'expires_in' => 60,
            ], 201),
            self::N2B . '/api/v1/results/*' => Http::response([
                'result_id'         => self::RESULT,
                'session_id'        => self::SESSION,
                'reference_externe' => 'DV-2026-0142/L3',
                'fiche'             => $fiche,
                'resultat'          => [
                    'temps' => [
                        'politique' => 'calcule',
                        'compteurs' => ['plis' => 3],
                        'retenu'    => ['preparation' => 270, 'unitaire' => 12],
                    ],
                    'simulation' => [
                        'presse'   => ['id' => 4, 'nom' => 'Trumpf TruBend 5130'],
                        'montages' => [[
                            'poincon'          => ['id' => 1, 'reference' => 'P-88', 'constructeur' => 'Wila', 'angle' => 88, 'rayon' => 0.8],
                            'matrice'          => ['id' => 2, 'reference' => 'M-V16', 'constructeur' => 'Wila', 'angle' => 88, 'rayon' => 1, 'ouverture_v' => 16],
                            'poincon_retourne' => false,
                        ]],
                        'sequence' => [2, 0, 1],
                        'plis'     => [['montage' => 0], ['montage' => 0], ['montage' => 0]],
                    ],
                ],
            ], 200),
        ]);
    }

    private function simulate(QuoteLines $line)
    {
        $this->postJson($this->route('session', $line))->assertCreated();

        return $this->postJson($this->route('result', $line), ['result_id' => self::RESULT])->assertOk();
    }

    private function line(): QuoteLines
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine(['internal_comment' => 'avant']);
        $this->attachStep($line);

        return $line;
    }

    private function sheets(QuoteLines $line)
    {
        return $line->files()->where('extension', 'pdf')->orderBy('files.id')->get();
    }

    private function assertKeyNeverLogged(): void
    {
        foreach ($this->logged as $entry) {
            $this->assertStringNotContainsString(self::KEY, $entry);
            $this->assertStringNotContainsString('%PDF', $entry);
        }
    }

    public function test_the_setup_sheet_is_downloaded_server_side_and_filed_in_the_line_documents(): void
    {
        $line = $this->line();
        $this->fake($this->pdf());

        $response = $this->simulate($line);

        Http::assertSent(fn (ClientRequest $r) => $r->url() === self::SHEET
            && $r->method() === 'GET'
            && $r->hasHeader('Authorization', 'Bearer ' . self::KEY));

        $sheets = $this->sheets($line);
        $this->assertCount(1, $sheets);
        $file = $sheets->first();
        $this->assertSame(self::SHEET_NAME, $file->original_file_name);
        $this->assertSame('application/pdf', $file->type);
        $this->assertSame(self::PDF, Storage::disk($file->disk)->get($file->path));

        $simulation = BendingSimulation::sole();
        $this->assertSame(BendingSimulation::SHEET_STORED, $simulation->fiche_status);
        $this->assertSame($file->id, $simulation->fiche_file_id);

        $response->assertJsonPath('last.sheet', 'stored')
            ->assertJsonPath('last.sheet_name', self::SHEET_NAME)
            ->assertJsonPath('last.presse', 'Trumpf TruBend 5130')
            ->assertJsonPath('last.sequence', '3 → 1 → 2');
        $this->assertStringContainsString('P-88 (Wila)', $response->json('last.outils.0'));
        $this->assertStringContainsString('M-V16 V16 (Wila)', $response->json('last.outils.0'));

        // La clé n'est jamais renvoyée au navigateur.
        $this->assertStringNotContainsString(self::KEY, $response->getContent());
        $this->assertKeyNeverLogged();
    }

    /**
     * @return array<string, array{0: mixed, 1: int}>
     */
    public static function failures(): array
    {
        return [
            'pas de fiche (404)'     => [fn () => Http::response(['message' => 'Not Found'], 404), 404],
            'moteur arrêté (503)'    => [fn () => Http::response(['message' => 'Service Unavailable'], 503), 503],
            'délai dépassé'          => [fn () => fn () => throw new ConnectionException('cURL error 28: Operation timed out'), 0],
            'réponse qui n\'est pas un PDF' => [fn () => Http::response('<html>proxy</html>', 200, ['Content-Type' => 'text/html']), 200],
        ];
    }

    /**
     * @dataProvider failures
     */
    public function test_a_failed_download_never_prevents_recording_the_time(callable $sheet, int $status): void
    {
        $line = $this->line();
        $this->fake($sheet());

        $response = $this->simulate($line);

        $this->assertStringContainsString('4 min 30 s', (string) $line->QuoteLineDetails()->first()->internal_comment);
        $simulation = BendingSimulation::sole();
        $this->assertSame(BendingSimulation::STATUS_DONE, $simulation->status);
        $this->assertSame(BendingSimulation::SHEET_FAILED, $simulation->fiche_status);
        $this->assertSame($status, $simulation->fiche_error);
        $this->assertCount(0, $this->sheets($line));

        $response->assertJsonPath('last.sheet', 'failed');
        $this->assertNotEmpty($response->json('last.sheet_error'));

        // Seul le statut est journalisé.
        $this->assertTrue(collect($this->logged)->contains(fn ($entry) => str_contains($entry, 'fiche de réglage') && str_contains($entry, '"status":' . $status)));
        $this->assertKeyNeverLogged();
    }

    public function test_fetch_the_sheet_retries_later(): void
    {
        $line = $this->line();
        $this->fake(Http::sequence()
            ->push(['message' => 'Service Unavailable'], 503)
            ->push(self::PDF, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="' . self::SHEET_NAME . '"']));

        $this->simulate($line)->assertJsonPath('last.sheet', 'failed');

        $this->postJson($this->route('sheet', $line))
            ->assertOk()
            ->assertJsonPath('last.sheet', 'stored')
            ->assertJson(['message' => __('nest2bend.sheet.stored', ['name' => self::SHEET_NAME])]);

        $this->assertCount(1, $this->sheets($line));
        // Le temps n'est pas ajouté une seconde fois.
        $this->assertSame(1, substr_count((string) $line->QuoteLineDetails()->first()->internal_comment, '[NEST2BEND'));
    }

    public function test_a_retry_that_fails_again_says_why(): void
    {
        $line = $this->line();
        $this->fake(Http::response(['message' => 'Not Found'], 404));
        $this->simulate($line);

        $this->postJson($this->route('sheet', $line))
            ->assertStatus(502)
            ->assertJson(['message' => __('nest2bend.sheet.error_404')]);
    }

    public function test_a_new_simulation_adds_a_version_to_the_existing_sheet(): void
    {
        $line = $this->line();
        $this->fake($this->pdf());
        $this->simulate($line);
        $first = $this->sheets($line)->sole();

        // Seconde simulation de la même ligne, dont la fiche n'a pas encore été rangée.
        BendingSimulation::create([
            'quote_lines_id' => $line->id, 'user_id' => $this->user->id, 'reference_externe' => 'DV-2026-0142/L3',
            'session_id' => 'autre-session', 'result_id' => '0193f2a1-7c4e-7b2d-9a1f-3c5d7e9f1a2c',
            'status' => BendingSimulation::STATUS_DONE, 'completed_at' => now()->addMinute(),
            'fiche_url' => self::SHEET, 'fiche_status' => BendingSimulation::SHEET_PENDING,
        ]);

        $this->postJson($this->route('sheet', $line))->assertOk();

        $current = $line->files()->currentVersion()->where('extension', 'pdf')->get();
        $this->assertCount(1, $current);
        $second = $current->sole();
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($first->id, (int) $second->version_group_id);
        $this->assertSame(2, $second->versionNumber());
        $this->assertTrue($first->fresh()->isSuperseded());
    }

    public function test_a_sheet_removed_from_the_documents_is_filed_again_as_a_new_file(): void
    {
        $line = $this->line();
        $this->fake($this->pdf());
        $this->simulate($line);
        $first = $this->sheets($line)->sole();
        $line->files()->detach($first->id);

        BendingSimulation::create([
            'quote_lines_id' => $line->id, 'user_id' => $this->user->id, 'reference_externe' => 'DV-2026-0142/L3',
            'session_id' => 'autre-session', 'status' => BendingSimulation::STATUS_DONE, 'completed_at' => now()->addMinute(),
            'fiche_url' => self::SHEET, 'fiche_status' => BendingSimulation::SHEET_FAILED, 'fiche_error' => 503,
        ]);
        $this->postJson($this->route('sheet', $line))->assertOk();

        $second = $this->sheets($line)->sole();
        $this->assertNull($second->version_group_id);
        $this->assertFalse($first->fresh()->isSuperseded());
    }

    public function test_no_sheet_is_requested_when_the_result_has_none(): void
    {
        $line = $this->line();
        $this->fake($this->pdf(), fiche: null);

        $this->simulate($line)->assertJsonPath('last.sheet', null);

        Http::assertNotSent(fn (ClientRequest $r) => $r->url() === self::SHEET);
        $this->assertNull(BendingSimulation::sole()->fiche_status);

        $this->postJson($this->route('sheet', $line))->assertNotFound();
    }

    public function test_the_key_is_never_sent_to_a_sheet_url_of_another_origin(): void
    {
        $line = $this->line();
        $this->fake($this->pdf(), fiche: 'https://ailleurs.test/fiche.pdf');

        $this->simulate($line)->assertJsonPath('last.sheet', 'failed');

        Http::assertNotSent(fn (ClientRequest $r) => str_starts_with($r->url(), 'https://ailleurs.test'));
        $this->assertCount(0, $this->sheets($line));
    }

    public function test_the_line_page_shows_the_latest_simulation(): void
    {
        $line = $this->line();
        $this->fake($this->pdf());
        $this->simulate($line);

        $this->get(route('quotes.lines.detail.edit', ['idQuote' => $line->quotes_id, 'id' => $line->id]))
            ->assertOk()
            ->assertSee('Trumpf TruBend 5130')
            ->assertSee(self::SHEET_NAME)
            ->assertSee(route('quotes.lines.nest2bend.sheet', ['quoteId' => $line->quotes_id, 'id' => $line->id]), false)
            ->assertDontSee(self::KEY)
            ->assertDontSee(self::SHEET, false);
    }

    public function test_the_simulation_block_is_read_defensively(): void
    {
        $result = BendingResult::fromArray([
            'result_id' => self::RESULT, 'session_id' => self::SESSION, 'fiche' => '',
            'resultat'  => ['simulation' => [
                'presse'   => null,
                'montages' => [['poincon' => null, 'matrice' => ['reference' => 'M1'], 'poincon_retourne' => true], 'x'],
                'sequence' => [1, 'a', 0],
                'plis'     => [['montage' => 0], null],
            ]],
        ]);

        $this->assertNull($result->fiche);
        $this->assertNull($result->simulation['presse']);
        $this->assertSame('M1', $result->simulation['montages'][0]['matrice']);
        $this->assertSame([1, 0], $result->simulation['sequence']);
        $this->assertSame([0, null], $result->simulation['plis']);
    }

    public function test_the_file_name_comes_from_content_disposition(): void
    {
        $this->assertSame(self::SHEET_NAME, SetupSheet::fileNameFrom('attachment; filename="' . self::SHEET_NAME . '"', 'x.pdf'));
        $this->assertSame('fiche réglage.pdf', SetupSheet::fileNameFrom("attachment; filename*=UTF-8''fiche%20r%C3%A9glage.pdf", 'x.pdf'));
        $this->assertSame('passwd.pdf', SetupSheet::fileNameFrom('attachment; filename="../../etc/passwd"', 'x.pdf'));
        $this->assertSame('defaut.pdf', SetupSheet::fileNameFrom(null, 'defaut.pdf'));
    }
}
