<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Joignabilité de l'entreprise et recherche d'adresse client.
 *
 * Deux faces du même annuaire : savoir si NOUS pouvons recevoir, et savoir où
 * envoyer à nos clients. L'ouverture de notre propre ligne, elle, se fait sur
 * l'interface de la plateforme — WEM se contente de constater son absence.
 */
class PdpDirectoryReachabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->actingAs(User::factory()->create());

        config()->set('services.pdp.default', 'superpdp');
        config()->set('services.superpdp.client_id', 'client-id');
        config()->set('services.superpdp.client_secret', 'client-secret');
        config()->set('services.superpdp.base_url', 'https://api.superpdp.tech');

        Cache::flush();
    }

    public function test_an_open_line_makes_the_company_reachable(): void
    {
        $this->fakeDirectory([
            ['id' => 1, 'identifier' => '0225:853322915', 'directory' => 'ppf'],
        ]);

        $this->getJson(route('purchases.incoming.json.list'))
            ->assertOk()
            ->assertJsonPath('directory.reachable', true)
            ->assertJsonPath('directory.count', 1);
    }

    public function test_a_technical_return_line_alone_does_not_count(): void
    {
        // La ligne `_replyto` reçoit les messages de cycle de vie, jamais de
        // factures. La compter laisserait croire à tort qu'on est joignable —
        // et la plateforme la crée d'office, donc le cas est la règle.
        $this->fakeDirectory([
            ['id' => 2, 'identifier' => '0225:853322915_replyto', 'directory' => 'ppf', 'is_replyto' => true],
        ]);

        $this->getJson(route('purchases.incoming.json.list'))
            ->assertOk()
            ->assertJsonPath('directory.reachable', false)
            ->assertJsonPath('directory.count', 0);
    }

    public function test_an_unreachable_platform_does_not_break_the_screen(): void
    {
        // Un incident de la plateforme ne doit pas empêcher de consulter les
        // factures déjà reçues.
        Http::fake([
            'https://api.superpdp.tech/oauth2/token' => Http::response(['access_token' => 't', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/directory_entries' => Http::response([], 503),
        ]);

        $this->getJson(route('purchases.incoming.json.list'))
            ->assertOk()
            ->assertJsonPath('directory', null);
    }

    public function test_a_client_address_is_looked_up_from_its_siren(): void
    {
        Http::fake([
            'https://api.superpdp.tech/oauth2/token' => Http::response(['access_token' => 't', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/french_directory/entries*' => Http::response([
                'data' => [[
                    'identifier' => '0225:853322915',
                    'is_active'  => true,
                    'company'    => ['formal_name' => 'SUPER G', 'city' => 'PARIS'],
                ]],
            ]),
        ]);

        $company = Companies::factory()->create(['siren' => '853322915']);

        $this->getJson(route('companies.pdp.lookup', $company->id) . '?siren=853322915')
            ->assertOk()
            ->assertJsonPath('entries.0.identifier', '0225:853322915')
            ->assertJsonPath('entries.0.is_active', true);
    }

    public function test_a_lookup_without_siren_is_refused(): void
    {
        $company = Companies::factory()->create();

        $this->getJson(route('companies.pdp.lookup', $company->id))->assertStatus(422);
    }

    private function fakeDirectory(array $entries): void
    {
        Http::fake([
            'https://api.superpdp.tech/oauth2/token' => Http::response(['access_token' => 't', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/directory_entries' => Http::response(['data' => $entries]),
        ]);
    }
}
