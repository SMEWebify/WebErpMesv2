<?php

namespace Tests\Feature\Quotes;

use App\Models\Accounting\AccountingVat;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsUnits;
use App\Models\User;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Quotes\QuoteLineCopier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Dictée d'un devis : le texte devient des lignes en brouillon (rien n'est
 * écrit), l'utilisateur les corrige, puis les lignes validées sont créées et
 * marquées « proposé par IA ».
 */
class QuoteDictationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MethodsUnits $unit;
    private MethodsUnits $day;
    private AccountingVat $vat;

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

        // Une seule unité et une seule TVA par défaut : celles du test.
        MethodsUnits::query()->update(['default' => 0]);
        AccountingVat::query()->update(['default' => 0]);
        $this->unit = MethodsUnits::factory()->create(['code' => 'U', 'label' => 'Unité', 'default' => 1]);
        $this->day = MethodsUnits::factory()->create(['code' => 'J', 'label' => 'Jour', 'default' => 0]);
        $this->vat = AccountingVat::factory()->create(['rate' => 20, 'default' => 1]);

        config([
            'ai.providers.claude.api_key' => 'test-key',
            'ai.providers.claude.default_model' => 'claude-haiku-4-5-20251001',
        ]);
    }

    private function quote(int $statu = 1): Quotes
    {
        return Quotes::factory()->create(['code' => 'QT-0001', 'statu' => $statu, 'user_id' => $this->user->id]);
    }

    private function fakeClaude(string $answer): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $answer]],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
            ]),
        ]);
    }

    /** @test */
    public function it_proposes_draft_lines_without_saving_anything()
    {
        $quote = $this->quote();
        $this->fakeClaude("```json\n" . json_encode(['lines' => [
            ['label' => 'Garde-corps acier 4 m, main courante ronde Ø42', 'qty' => 1, 'unit' => 'u', 'unit_price' => null, 'material' => 'Acier S235', 'note' => 'Main courante Ø42'],
            ['label' => 'Fabrication en atelier', 'qty' => '2', 'unit' => 'J', 'unit_price' => '450,5', 'material' => null, 'note' => null],
            ['label' => 'Pose', 'qty' => 0.5, 'unit' => 'demi-journée', 'unit_price' => -3],
            ['label' => '   ', 'qty' => 3],
        ]]) . "\n```");

        $response = $this->postJson(route('quotes.lines.json.dictation.propose', ['quoteId' => $quote->id]), [
            'text' => 'garde-corps acier 4 m, main courante ronde Ø42, 2 jours d\'atelier à 450,50, pose une demi-journée',
        ])->assertOk();

        $lines = $response->json('lines');
        $this->assertCount(3, $lines, 'une ligne sans désignation est ignorée');
        $this->assertSame($this->unit->id, $lines[0]['methods_units_id'], 'code d\'unité reconnu sans tenir compte de la casse');
        $this->assertNull($lines[0]['selling_price'], 'aucun prix inventé');
        $this->assertSame('Acier S235', $lines[0]['material']);
        $this->assertSame($this->day->id, $lines[1]['methods_units_id']);
        $this->assertEquals(2, $lines[1]['qty']);
        $this->assertEquals(450.5, $lines[1]['selling_price']);
        $this->assertNull($lines[2]['methods_units_id']);
        $this->assertSame('demi-journée', $lines[2]['unit_text'], 'unité inconnue montrée à l\'utilisateur');
        $this->assertNull($lines[2]['selling_price'], 'prix négatif écarté');

        $this->assertSame(0, QuoteLines::count());

        // Seuls la consigne, les unités et le texte dicté partent : pas le client.
        Http::assertSent(function (HttpRequest $request) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

            return str_contains($body, '<dictee>') && str_contains($body, 'J = Jour') && ! str_contains($body, 'QT-0001');
        });
    }

    /** @test */
    public function an_unreadable_answer_is_reported_and_nothing_is_saved()
    {
        $quote = $this->quote();
        $this->fakeClaude('Je ne peux pas répondre.');

        $this->postJson(route('quotes.lines.json.dictation.propose', ['quoteId' => $quote->id]), ['text' => 'garde-corps'])
            ->assertStatus(502)
            ->assertJsonPath('message', __('quote_dictation.failed'));

        $this->assertSame(0, QuoteLines::count());
    }

    /** @test */
    public function the_ai_being_unreachable_is_reported()
    {
        $quote = $this->quote();
        Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'overloaded'], 529)]);

        $this->postJson(route('quotes.lines.json.dictation.propose', ['quoteId' => $quote->id]), ['text' => 'garde-corps'])
            ->assertStatus(502)
            ->assertJsonPath('message', __('ai_text.failed'));
    }

    /** @test */
    public function without_ai_provider_the_dictation_is_refused()
    {
        $quote = $this->quote();
        config(['ai.providers.claude.api_key' => null]);
        Http::fake();

        $this->postJson(route('quotes.lines.json.dictation.propose', ['quoteId' => $quote->id]), ['text' => 'garde-corps'])
            ->assertStatus(502)
            ->assertJsonPath('message', __('ai_text.unavailable'));

        Http::assertNothingSent();
    }

    /** @test */
    public function validated_lines_are_created_at_the_end_and_marked_as_proposed_by_ai()
    {
        $quote = $this->quote();
        QuoteLines::create([
            'quotes_id' => $quote->id, 'ordre' => 4, 'code' => 'L-1', 'label' => 'Ligne existante',
            'qty' => 1, 'selling_price' => 10, 'discount' => 0, 'statu' => 1,
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ]);

        $response = $this->postJson(route('quotes.lines.json.dictation.store', ['quoteId' => $quote->id]), [
            'lines' => [
                ['label' => 'Garde-corps acier', 'qty' => 1, 'methods_units_id' => null, 'selling_price' => null, 'material' => 'Acier S235', 'internal_comment' => 'Main courante Ø42'],
                ['label' => 'Fabrication en atelier', 'qty' => 2, 'methods_units_id' => $this->day->id, 'selling_price' => 450],
            ],
        ])->assertCreated();

        $this->assertCount(2, $response->json('lines'));
        $this->assertTrue($response->json('lines.0.ai_proposed'));
        $this->assertSame(1, (int) $response->json('lines.0.statu'), 'statut Ouvert affiché sans recharger');

        $first = QuoteLines::where('label', 'Garde-corps acier')->firstOrFail();
        $this->assertSame(5, (int) $first->ordre);
        $this->assertSame($this->unit->id, (int) $first->methods_units_id, 'unité par défaut');
        $this->assertSame($this->vat->id, (int) $first->accounting_vats_id);
        $this->assertEquals(0, $first->getRawOriginal('selling_price'), 'prix non dicté = 0, à saisir');
        $this->assertNotNull($first->ai_proposed_at);

        $details = QuoteLineDetails::where('quote_lines_id', $first->id)->firstOrFail();
        $this->assertSame('Acier S235', $details->material);
        $this->assertSame('Main courante Ø42', $details->internal_comment);

        $second = QuoteLines::where('label', 'Fabrication en atelier')->firstOrFail();
        $this->assertSame(6, (int) $second->ordre);
        $this->assertEquals(450, $second->getRawOriginal('selling_price'));

        $this->assertNull(QuoteLines::where('label', 'Ligne existante')->value('ai_proposed_at'));
    }

    /** @test */
    public function lines_are_validated_before_creation()
    {
        $quote = $this->quote();

        $this->postJson(route('quotes.lines.json.dictation.store', ['quoteId' => $quote->id]), [
            'lines' => [['label' => str_repeat('x', 256), 'qty' => -1]],
        ])->assertStatus(422)->assertJsonValidationErrors(['lines.0.label', 'lines.0.qty']);

        $this->postJson(route('quotes.lines.json.dictation.store', ['quoteId' => $quote->id]), ['lines' => []])
            ->assertStatus(422);

        $this->assertSame(0, QuoteLines::count());
    }

    /** @test */
    public function a_quote_that_is_no_longer_open_refuses_the_dictation()
    {
        $quote = $this->quote(2);
        Http::fake();

        $this->postJson(route('quotes.lines.json.dictation.propose', ['quoteId' => $quote->id]), ['text' => 'garde-corps'])
            ->assertForbidden();
        $this->postJson(route('quotes.lines.json.dictation.store', ['quoteId' => $quote->id]), [
            'lines' => [['label' => 'Garde-corps', 'qty' => 1]],
        ])->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, QuoteLines::count());
    }

    /** @test */
    public function a_copied_line_does_not_keep_the_ai_mark()
    {
        $quote = $this->quote();
        $line = QuoteLines::create([
            'quotes_id' => $quote->id, 'ordre' => 1, 'code' => '', 'label' => 'Garde-corps',
            'qty' => 1, 'selling_price' => 0, 'discount' => 0, 'statu' => 1,
            'methods_units_id' => $this->unit->id, 'accounting_vats_id' => $this->vat->id,
        ]);
        $line->ai_proposed_at = now();
        $line->save();

        $copy = app(QuoteLineCopier::class)->copy($line->fresh(), $quote->id, 2);

        $this->assertNull($copy->fresh()->ai_proposed_at);
    }

    /** @test */
    public function the_recording_is_transcribed_by_ovh_and_nothing_is_saved()
    {
        $quote = $this->quote();
        config(['ai.providers.ovh.api_key' => 'ovh-key', 'ai.providers.ovh.base_url' => 'https://ovh.test/v1']);
        Http::fake(['ovh.test/*' => Http::response(['text' => ' garde-corps acier 4 mètres '])]);

        $this->post(route('quotes.lines.json.dictation.transcribe', ['quoteId' => $quote->id]), [
            'audio' => UploadedFile::fake()->create('blob', 20, 'audio/webm'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('text', 'garde-corps acier 4 mètres');

        $this->assertSame(0, QuoteLines::count());
    }
}
