<?php

namespace Tests\Feature\Nest2Bend;

use App\Models\Integrations\Nest2BendSetting;
use App\Services\Integrations\Nest2Bend\Nest2BendAccess;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Carte NEST2BEND : stockage de la clé, test de connexion, et les états tirés de
 * GET /api/v1/compte (pas de clé, 401, abonnement expiré, actif, essai / grâce).
 */
class Nest2BendSettingsTest extends TestCase
{
    use RefreshDatabase;
    use Nest2BendTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpNest2Bend();
    }

    private function punchDesigner()
    {
        return $this->get(route('methods.tool.punch-designer'))->assertOk();
    }

    // ---------------------------------------------------------------------
    // Clé API
    // ---------------------------------------------------------------------

    public function test_the_key_is_encrypted_and_never_sent_back_to_the_browser_nor_logged(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $e) use (&$logged) {
            $logged[] = $e->message . json_encode($e->context);
        });

        Http::fake([self::N2B . '/api/v1/compte' => Http::response($this->account(), 200)]);

        $this->put(route('admin.integrations.nest2bend.update'), [
            'base_url' => self::N2B . '/',
            'api_key'  => self::KEY,
        ])->assertRedirect(route('admin.integrations.nest2bend.index'));

        $raw = DB::table('nest2bend_settings')->value('api_key');
        $this->assertNotEmpty($raw);
        $this->assertStringNotContainsString(self::KEY, $raw);
        $this->assertSame(self::KEY, Nest2BendSetting::current()->api_key);
        $this->assertSame(self::N2B, Nest2BendSetting::current()->base_url);

        Http::assertSent(fn (ClientRequest $r) => $r->url() === self::N2B . '/api/v1/compte'
            && $r->hasHeader('Authorization', 'Bearer ' . self::KEY));

        $page = $this->get(route('admin.integrations.nest2bend.index'))->assertOk();
        $page->assertDontSee(self::KEY, false);
        $page->assertDontSee('0123456789abcdef', false);
        // Seuls le préfixe et les 4 derniers caractères sont montrés.
        $page->assertSee('n2b_••••••••CRET', false);

        $this->get(route('admin.integrations.index'))->assertOk()->assertDontSee(self::KEY, false);
        $this->assertSame([], array_filter($logged, fn ($line) => str_contains($line, self::KEY)));
        $this->assertArrayNotHasKey('api_key', Nest2BendSetting::current()->toArray());
    }

    public function test_an_empty_key_field_keeps_the_saved_key(): void
    {
        $this->linkNest2Bend();
        Http::fake([self::N2B . '/api/v1/compte' => Http::response($this->account(), 200)]);

        $this->put(route('admin.integrations.nest2bend.update'), ['base_url' => self::N2B, 'api_key' => ''])
            ->assertRedirect();

        $this->assertSame(self::KEY, Nest2BendSetting::current()->api_key);
    }

    public function test_a_key_without_the_n2b_prefix_is_refused(): void
    {
        $this->put(route('admin.integrations.nest2bend.update'), ['base_url' => self::N2B, 'api_key' => 'abc'])
            ->assertSessionHasErrors('api_key');
    }

    // ---------------------------------------------------------------------
    // Les quatre états (+ essai / grâce)
    // ---------------------------------------------------------------------

    public function test_without_a_key_the_commercial_screen_stays(): void
    {
        Http::fake();

        $this->assertTrue(app(Nest2BendAccess::class)->state()['show_commercial']);
        $this->punchDesigner()
            ->assertSee('class="commercial-feature"', false)
            ->assertSee('images/commercial/nest2bend-simulation.png', false)
            ->assertDontSee(__('nest2bend.panel.title'));
        $this->get(route('admin.integrations.index'))->assertOk()->assertSee(__('nest2bend.badge.none'));

        Http::assertNothingSent();
    }

    public function test_a_401_shows_an_invalid_key_and_keeps_the_commercial_screen(): void
    {
        $this->linkNest2Bend(Nest2BendSetting::STATUS_UNKNOWN);
        Http::fake([self::N2B . '/api/v1/compte' => Http::response(['message' => 'Clé API absente, invalide ou révoquée.'], 401)]);

        $this->postJson(route('admin.integrations.nest2bend.test'))
            ->assertOk()
            ->assertJson(['ok' => false, 'status' => 'invalid', 'message' => __('nest2bend.state.invalid')]);

        $state = app(Nest2BendAccess::class)->state();
        $this->assertSame('invalid', $state['status']);
        $this->assertTrue($state['show_commercial']);
        $this->assertFalse($state['can_simulate']);

        $this->get(route('admin.integrations.index'))->assertOk()->assertSee(__('nest2bend.state.invalid'));
        $this->punchDesigner()->assertSee('class="commercial-feature"', false);
    }

    public function test_a_subscription_that_cannot_write_is_shown_as_expired_with_a_link(): void
    {
        $this->linkNest2Bend(Nest2BendSetting::STATUS_UNKNOWN);
        Http::fake([self::N2B . '/api/v1/compte' => Http::response($this->account(['etat' => 'lecture_seule', 'peut_ecrire' => false]), 200)]);

        $this->postJson(route('admin.integrations.nest2bend.test'))
            ->assertJson(['ok' => false, 'status' => 'expired', 'message' => __('nest2bend.state.expired')]);

        $state = app(Nest2BendAccess::class)->state();
        $this->assertTrue($state['expired']);
        $this->assertFalse($state['can_simulate']);
        $this->assertFalse($state['show_commercial']);

        $this->get(route('admin.integrations.index'))->assertOk()
            ->assertSee(__('nest2bend.state.expired'))
            ->assertSee(self::N2B, false);
        $this->punchDesigner()->assertDontSee('class="commercial-feature"', false)->assertSee(__('nest2bend.panel.expired'));
    }

    public function test_an_active_subscription_removes_the_commercial_screen(): void
    {
        $this->linkNest2Bend(Nest2BendSetting::STATUS_UNKNOWN);
        Http::fake([self::N2B . '/api/v1/compte' => Http::response($this->account(), 200)]);

        $this->postJson(route('admin.integrations.nest2bend.test'))
            ->assertJson(['ok' => true, 'status' => 'active', 'societe' => 'Tôlerie Martin', 'warning' => null]);

        $setting = Nest2BendSetting::current();
        $this->assertSame(['socle', 'collision'], $setting->modules);
        $this->assertSame(3, $setting->presses_max);
        $this->assertSame('2027-01-31', $setting->echeance->toDateString());

        $state = app(Nest2BendAccess::class)->state();
        $this->assertTrue($state['can_simulate']);
        $this->assertFalse($state['warning']);
        $this->punchDesigner()
            ->assertDontSee('class="commercial-feature"', false)
            ->assertDontSee('nest2bend-simulation.png', false)
            ->assertSee(__('nest2bend.panel.title'));
    }

    /**
     * @dataProvider warningStates
     */
    public function test_a_trial_or_grace_period_is_active_with_a_warning_and_the_due_date(string $etat): void
    {
        $this->linkNest2Bend(Nest2BendSetting::STATUS_UNKNOWN);
        Http::fake([self::N2B . '/api/v1/compte' => Http::response($this->account(['etat' => $etat]), 200)]);

        $warning = __('nest2bend.state.warning', ['etat' => __('nest2bend.etats.' . $etat), 'echeance' => '31/01/2027']);

        $this->postJson(route('admin.integrations.nest2bend.test'))
            ->assertJson(['ok' => true, 'status' => 'active', 'warning' => $warning]);

        $state = app(Nest2BendAccess::class)->state();
        $this->assertTrue($state['can_simulate']);
        $this->assertTrue($state['warning']);
        $this->get(route('admin.integrations.index'))->assertOk()->assertSee($warning);
    }

    public static function warningStates(): array
    {
        return ['essai' => ['essai'], 'grace' => ['grace']];
    }

    // ---------------------------------------------------------------------
    // Cache de l'état
    // ---------------------------------------------------------------------

    public function test_a_network_failure_keeps_the_last_known_state(): void
    {
        $this->linkNest2Bend();
        Http::fake([self::N2B . '/api/v1/compte' => Http::response('oops', 503)]);

        $this->postJson(route('admin.integrations.nest2bend.test'))
            ->assertJson(['ok' => false, 'status' => 'active', 'message' => __('nest2bend.errors.unavailable')]);

        $this->assertTrue(app(Nest2BendAccess::class)->canSimulate());
        $this->assertSame(__('nest2bend.errors.unavailable'), Nest2BendSetting::current()->last_error);
    }

    public function test_pages_never_call_nest2bend_to_render(): void
    {
        $this->linkNest2Bend();
        Http::fake();

        $this->get(route('admin.integrations.index'))->assertOk();
        $this->get(route('admin.integrations.nest2bend.index'))->assertOk();
        $this->punchDesigner();

        Http::assertNothingSent();
    }

    public function test_the_daily_command_refreshes_the_state(): void
    {
        $this->linkNest2Bend();
        Http::fake([self::N2B . '/api/v1/compte' => Http::response($this->account(['peut_ecrire' => false, 'etat' => 'lecture_seule']), 200)]);

        $this->artisan('wem:nest2bend:refresh')->assertSuccessful();

        $this->assertSame(Nest2BendSetting::STATUS_EXPIRED, Nest2BendSetting::current()->status);
    }

    public function test_the_daily_command_does_nothing_without_a_key(): void
    {
        Http::fake();

        $this->artisan('wem:nest2bend:refresh')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_clearing_the_key_goes_back_to_the_commercial_screen(): void
    {
        $this->linkNest2Bend();
        Http::fake();

        $this->put(route('admin.integrations.nest2bend.update'), ['base_url' => self::N2B, 'clear_key' => '1'])
            ->assertRedirect();

        $this->assertNull(DB::table('nest2bend_settings')->value('api_key'));
        $this->assertSame('none', app(Nest2BendAccess::class)->state()['status']);
        Http::assertNothingSent();
    }
}
