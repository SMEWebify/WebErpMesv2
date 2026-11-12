<?php

namespace Tests\Feature\Nest2Bend;

use App\Models\Integrations\Nest2BendSetting;
use App\Models\User;
use App\Models\Workflow\BendingSimulation;
use App\Models\Workflow\QuoteLines;
use App\Services\Integrations\Nest2Bend\BendingSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Bouton « Simuler » d'une ligne de devis : présence selon le STEP, création de session
 * (201 / 403 / 422 / 503) et lecture du résultat, contre un faux NEST2BEND.
 */
class BendingSimulationTest extends TestCase
{
    use RefreshDatabase;
    use Nest2BendTestHelpers;

    private const SESSION = '7f1c2a54-9a0e-4c5e-9f1d-2b3c4d5e6f70';
    // UUID v7, comme ceux de NEST2BEND.
    private const RESULT = '0193f2a1-7c4e-7b2d-9a1f-3c5d7e9f1a2b';
    private const CODE = 'Zq8yR2xK4pVt7mN1cB5wE3hJ6gF0dS9aL2kQ8uY4iO7rT1eW';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpNest2Bend();
    }

    private function sessionRoute(QuoteLines $line): string
    {
        return route('quotes.lines.nest2bend.session', ['quoteId' => $line->quotes_id, 'id' => $line->id]);
    }

    private function resultRoute(QuoteLines $line): string
    {
        return route('quotes.lines.nest2bend.result', ['quoteId' => $line->quotes_id, 'id' => $line->id]);
    }

    private function detailPage(QuoteLines $line)
    {
        return $this->get(route('quotes.lines.detail.edit', ['idQuote' => $line->quotes_id, 'id' => $line->id]))->assertOk();
    }

    private function fakeEmbed(int $status = 201, ?array $body = null, array $result = []): void
    {
        Http::fake([
            self::N2B . '/api/v1/embed-sessions' => Http::response($body ?? [
                'session_id' => self::SESSION,
                'url'        => self::N2B . '/integre/' . self::CODE,
                'expires_in' => 60,
                'analyse'    => ['epaisseur' => 2, 'sections' => 1, 'remarques' => ['Unités converties en millimètres (facteur 25.4).']],
            ], $status),
            self::N2B . '/api/v1/compte' => Http::response($this->account(['etat' => 'lecture_seule', 'peut_ecrire' => false]), 200),
            self::N2B . '/api/v1/results/*' => Http::response(array_replace_recursive([
                'result_id'         => self::RESULT,
                'session_id'        => self::SESSION,
                'reference_externe' => 'DV-2026-0142/L3',
                'termine_at'        => '2026-11-12T10:00:00+00:00',
                'resultat'          => [
                    'temps' => [
                        'politique' => 'calcule',
                        'compteurs' => ['plis' => 6, 'retournements' => 2],
                        'calcule'   => ['preparation' => 270, 'unitaire' => 12],
                        'impose'    => null,
                        'retenu'    => ['preparation' => 270, 'unitaire' => 12],
                    ],
                    'section' => ['epaisseur' => 2, 'cotes' => [30, 60], 'plis' => [['angle' => 90, 'sens' => 1]]],
                ],
            ], $result), 200),
        ]);
    }

    // ---------------------------------------------------------------------
    // Bouton
    // ---------------------------------------------------------------------

    public function test_no_button_while_nest2bend_is_not_linked(): void
    {
        $line = $this->quoteLine();
        $this->attachStep($line);

        $this->detailPage($line)->assertDontSee('data-react="bending-simulation"', false);
    }

    public function test_the_button_is_greyed_without_a_step_file(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();

        $this->detailPage($line)
            ->assertSee('data-react="bending-simulation"', false)
            ->assertSee('data-has-step="0"', false);

        $state = app(BendingSimulationService::class)->buttonState($line);
        $this->assertFalse($state['enabled']);
        $this->assertSame(__('nest2bend.button.no_step'), $state['reason']);
    }

    public function test_the_button_is_active_with_a_step_file(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);

        $this->detailPage($line)
            ->assertSee('data-has-step="1"', false)
            ->assertSee('data-active="1"', false)
            ->assertSee('support-gauche.step', false)
            ->assertDontSee(self::KEY, false);
    }

    public function test_the_step_of_the_product_is_used_when_the_line_has_none(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        // ProductsFactory pioche une famille existante.
        \App\Models\Methods\MethodsFamilies::factory()->create();
        $product = \App\Models\Products\Products::factory()->create();
        $line->update(['product_id' => $product->id]);

        $storage = app(\App\Services\Files\FileStorageService::class);
        $storage->attach($storage->storeContents('ISO-10303-21;', 'article.stp', 'application/step'), $product, 'modele_3d');

        $this->assertSame('article.stp', app(BendingSimulationService::class)->buttonState($line->fresh())['step']);
    }

    public function test_the_button_is_disabled_when_the_subscription_expired(): void
    {
        $this->linkNest2Bend(Nest2BendSetting::STATUS_EXPIRED);
        $line = $this->quoteLine();
        $this->attachStep($line);

        $this->detailPage($line)->assertSee('data-expired="1"', false)->assertSee('data-active="0"', false);
        $this->assertFalse(app(BendingSimulationService::class)->buttonState($line)['enabled']);
    }

    // ---------------------------------------------------------------------
    // Création de session
    // ---------------------------------------------------------------------

    public function test_a_session_is_opened_server_side_with_the_raw_step_file(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $contents = $this->attachStep($line);
        $this->fakeEmbed();

        $response = $this->postJson($this->sessionRoute($line))
            ->assertCreated()
            ->assertJson([
                'url'       => self::N2B . '/integre/' . self::CODE,
                'origin'    => self::N2B,
                'remarques' => ['Unités converties en millimètres (facteur 25.4).'],
            ]);
        $response->assertJsonMissingPath('session_id');
        $this->assertStringNotContainsString(self::KEY, $response->getContent());

        Http::assertSent(function (ClientRequest $r) use ($contents) {
            return $r->url() === self::N2B . '/api/v1/embed-sessions'
                && $r->method() === 'POST'
                && $r->hasHeader('Authorization', 'Bearer ' . self::KEY)
                && $r['origine'] === 'http://localhost'
                && $r['reference_externe'] === 'DV-2026-0142/L3'
                && in_array($r['langue'], ['fr', 'en'], true)
                && $r['step']['nom'] === 'support-gauche.step'
                // Octets bruts, Latin-1 compris : aucune conversion d'encodage.
                && base64_decode($r['step']['contenu_base64']) === $contents
                && ! isset($r['piece'])
                && $r['outils'] === []
                && $r['temps'] === ['politique' => 'calcule', 'quantite' => 50];
        });

        $simulation = BendingSimulation::sole();
        $this->assertSame(BendingSimulation::STATUS_PENDING, $simulation->status);
        $this->assertSame(self::SESSION, $simulation->session_id);
        $this->assertSame($this->user->id, (int) $simulation->user_id);

        // Le code d'URL (usage unique, 60 s) n'est stocké nulle part.
        foreach (DB::table('bending_simulations')->get() as $row) {
            $this->assertStringNotContainsString(self::CODE, json_encode($row));
        }
    }

    public function test_a_403_means_expired_and_refreshes_the_card(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed(403, ['message' => 'Abonnement inactif : session intégrée impossible.']);

        $this->postJson($this->sessionRoute($line))
            ->assertForbidden()
            ->assertJson(['message' => __('nest2bend.errors.inactive'), 'reason' => 'inactive']);

        Http::assertSent(fn (ClientRequest $r) => $r->url() === self::N2B . '/api/v1/compte');
        $this->assertSame(Nest2BendSetting::STATUS_EXPIRED, Nest2BendSetting::current()->status);
        $this->assertSame(0, BendingSimulation::count());
    }

    public function test_a_403_carrying_the_rights_updates_the_card_without_calling_compte(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed(403, [
            'message' => 'Abonnement inactif : session intégrée impossible.',
            'droits'  => ['etat' => 'lecture_seule', 'peut_ecrire' => false, 'echeance' => '2026-11-01T00:00:00+00:00', 'modules' => ['socle'], 'presses_max' => 1],
        ]);

        $this->postJson($this->sessionRoute($line))->assertForbidden();

        Http::assertNotSent(fn (ClientRequest $r) => str_ends_with($r->url(), '/api/v1/compte'));
        $setting = Nest2BendSetting::current();
        $this->assertSame(Nest2BendSetting::STATUS_EXPIRED, $setting->status);
        $this->assertSame('lecture_seule', $setting->etat);
        $this->assertSame('2026-11-01', $setting->echeance->toDateString());
        // Le 403 ne porte pas la société : celle déjà connue est gardée.
        $this->assertSame('Tôlerie Martin', $setting->societe);
    }

    public function test_a_429_asks_to_retry_in_a_minute(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed(429, ['message' => 'Too Many Attempts.']);

        $this->postJson($this->sessionRoute($line))
            ->assertStatus(429)
            ->assertJson(['message' => __('nest2bend.errors.rate_limited')]);

        $this->assertSame(Nest2BendSetting::STATUS_ACTIVE, Nest2BendSetting::current()->status);
    }

    public function test_a_422_shows_the_message_of_nest2bend(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed(422, [
            'message' => 'Ce fichier contient 3 pièces : exportez une seule pièce pliée par fichier.',
            'errors'  => ['step' => ['Ce fichier contient 3 pièces : exportez une seule pièce pliée par fichier.']],
        ]);

        $this->postJson($this->sessionRoute($line))
            ->assertStatus(422)
            ->assertJson(['message' => 'Ce fichier contient 3 pièces : exportez une seule pièce pliée par fichier.']);

        $this->assertSame(0, BendingSimulation::count());
        $this->assertSame(Nest2BendSetting::STATUS_ACTIVE, Nest2BendSetting::current()->status);
    }

    public function test_a_503_asks_to_retry(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed(503, ['message' => 'Calcul momentanément indisponible.']);

        $this->postJson($this->sessionRoute($line))
            ->assertStatus(503)
            ->assertJson(['message' => __('nest2bend.errors.unavailable')]);
    }

    public function test_no_call_without_a_step_file(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        Http::fake();

        $this->postJson($this->sessionRoute($line))
            ->assertStatus(422)
            ->assertJson(['message' => __('nest2bend.button.no_step')]);

        Http::assertNothingSent();
    }

    public function test_no_call_when_the_subscription_is_known_to_be_expired(): void
    {
        $this->linkNest2Bend(Nest2BendSetting::STATUS_EXPIRED);
        $line = $this->quoteLine();
        $this->attachStep($line);
        Http::fake();

        $this->postJson($this->sessionRoute($line))->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_an_iframe_url_of_another_origin_is_never_opened(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed(201, ['session_id' => self::SESSION, 'url' => 'https://evil.test/x', 'expires_in' => 60]);

        $this->postJson($this->sessionRoute($line))
            ->assertStatus(502)
            ->assertJsonMissing(['url' => 'https://evil.test/x'])
            ->assertJson(['message' => __('nest2bend.errors.origin_mismatch', ['received' => 'https://evil.test', 'configured' => self::N2B])]);
        $this->assertSame(0, BendingSimulation::count());
    }

    public function test_a_line_of_another_quote_is_not_found(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        Http::fake();

        $this->postJson(route('quotes.lines.nest2bend.session', ['quoteId' => $line->quotes_id + 999, 'id' => $line->id]))
            ->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // Lecture du résultat
    // ---------------------------------------------------------------------

    private function openSession(QuoteLines $line): void
    {
        $this->postJson($this->sessionRoute($line))->assertCreated();
    }

    public function test_the_result_is_read_server_side_and_appended_to_the_internal_comment(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine(['internal_comment' => 'Voir le client pour la tolérance.']);
        $this->attachStep($line);
        $this->fakeEmbed();
        $this->openSession($line);

        $response = $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])->assertOk();

        Http::assertSent(fn (ClientRequest $r) => $r->url() === self::N2B . '/api/v1/results/' . self::RESULT
            && $r->hasHeader('Authorization', 'Bearer ' . self::KEY));

        $comment = $line->QuoteLineDetails()->first()->internal_comment;
        $this->assertStringStartsWith("Voir le client pour la tolérance.\n[NEST2BEND ", $comment);
        $this->assertStringContainsString('4 min 30 s', $comment);
        $this->assertStringContainsString('12 s', $comment);
        $this->assertStringContainsString('50', $comment);
        $this->assertStringContainsString('plis 6', $comment);
        $this->assertSame($comment, $response->json('internal_comment'));

        $simulation = BendingSimulation::sole();
        $this->assertSame(BendingSimulation::STATUS_DONE, $simulation->status);
        $this->assertSame(self::RESULT, $simulation->result_id);
        $this->assertSame(270.0, $simulation->preparation_s);
        $this->assertSame(12.0, $simulation->unitaire_s);
        $this->assertSame(['plis' => 6, 'retournements' => 2], $simulation->compteurs);
    }

    public function test_a_result_without_time_is_recorded_as_no_time(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        // Politique « masque » : ni temps retenu ni compteurs. Pas une erreur.
        $this->fakeEmbed(result: ['resultat' => ['temps' => ['politique' => 'masque', 'retenu' => null, 'compteurs' => null], 'section' => null]]);
        $this->openSession($line);

        $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])->assertOk();

        $comment = (string) $line->QuoteLineDetails()->first()->internal_comment;
        $this->assertStringContainsString(__('nest2bend.comment.no_time'), $comment);
        $this->assertStringNotContainsString('—', $comment);

        $simulation = BendingSimulation::sole();
        $this->assertSame(BendingSimulation::STATUS_DONE, $simulation->status);
        $this->assertNull($simulation->preparation_s);
        $this->assertSame([], $simulation->compteurs);
    }

    public function test_a_single_missing_time_is_left_out(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed(result: ['resultat' => ['temps' => ['retenu' => ['preparation' => null, 'unitaire' => 12]]]]);
        $this->openSession($line);

        $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])->assertOk();

        $comment = (string) $line->QuoteLineDetails()->first()->internal_comment;
        $this->assertStringNotContainsString(__('nest2bend.comment.preparation', ['time' => '']), $comment);
        $this->assertStringContainsString('12 s', $comment);
    }

    public function test_the_same_result_is_recorded_only_once(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed();
        $this->openSession($line);

        $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])->assertOk();
        $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])->assertOk();

        $this->assertSame(1, substr_count((string) $line->QuoteLineDetails()->first()->internal_comment, '[NEST2BEND'));
    }

    public function test_a_result_of_a_session_not_opened_here_is_refused(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine(['internal_comment' => 'avant']);
        $this->attachStep($line);
        // Aucune session ouverte par l'ERP : un resultId valide d'ailleurs n'est pas cru.
        $this->fakeEmbed();

        $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])->assertNotFound();

        $this->assertSame('avant', $line->QuoteLineDetails()->first()->internal_comment);
    }

    public function test_a_result_opened_on_another_line_is_refused(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed();
        $this->openSession($line);

        $other = QuoteLines::create($line->only(['quotes_id', 'code', 'label', 'qty', 'selling_price', 'discount', 'methods_units_id', 'accounting_vats_id']) + ['ordre' => 4]);
        \App\Models\Workflow\QuoteLineDetails::create(['quote_lines_id' => $other->id]);

        $this->postJson($this->resultRoute($other), ['result_id' => self::RESULT])->assertNotFound();
        $this->assertSame(BendingSimulation::STATUS_PENDING, BendingSimulation::sole()->status);
    }

    public function test_a_result_opened_by_another_user_is_refused(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        $this->fakeEmbed();
        $this->openSession($line);

        $this->actingAs(User::factory()->create());
        $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])->assertNotFound();
    }

    public function test_a_result_purged_by_nest2bend_is_not_found(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        $this->attachStep($line);
        Http::fake([
            self::N2B . '/api/v1/embed-sessions' => Http::response(['session_id' => self::SESSION, 'url' => self::N2B . '/integre/' . self::CODE, 'expires_in' => 60], 201),
            self::N2B . '/api/v1/results/*' => Http::response(['message' => 'Not Found'], 404),
        ]);
        $this->openSession($line);

        $this->postJson($this->resultRoute($line), ['result_id' => self::RESULT])
            ->assertNotFound()
            ->assertJson(['message' => __('nest2bend.errors.not_found')]);
    }

    public function test_the_result_id_must_be_a_uuid(): void
    {
        $this->linkNest2Bend();
        $line = $this->quoteLine();
        Http::fake();

        $this->postJson($this->resultRoute($line), ['result_id' => '../compte'])->assertStatus(422);

        Http::assertNothingSent();
    }

    // ---------------------------------------------------------------------
    // Mise en forme
    // ---------------------------------------------------------------------

    public function test_durations_are_readable(): void
    {
        $this->assertSame('12 s', BendingSimulationService::duration(12));
        $this->assertSame('12,4 s', BendingSimulationService::duration(12.4));
        $this->assertSame('4 min 30 s', BendingSimulationService::duration(270));
        $this->assertSame('2 min', BendingSimulationService::duration(120));
        $this->assertSame('1 h 05 min', BendingSimulationService::duration(3900));
        $this->assertSame('—', BendingSimulationService::duration(null));
    }
}
