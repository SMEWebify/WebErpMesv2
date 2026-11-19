<?php

namespace Tests\Feature\Companies;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\User;
use App\Services\Companies\CompanyRegistryLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Préremplissage d'une société depuis le répertoire Sirene (API Recherche
 * d'entreprises simulée) et création avec son adresse.
 */
class CompanyRegistryLookupTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://recherche-entreprises.api.gouv.fr/search*';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->actingAs(User::factory()->create());

        config()->set('services.company_registry.enabled', true);
        config()->set('services.company_registry.url', 'https://recherche-entreprises.api.gouv.fr');
        Cache::flush();
    }

    private function unit(array $overrides = []): array
    {
        return array_replace_recursive([
            'siren'              => '552100554',
            'nom_complet'        => 'ACME TOLERIE (ACME)',
            'nom_raison_sociale' => 'ACME TOLERIE',
            'activite_principale'=> '25.62B',
            'etat_administratif' => 'A',
            'siege' => [
                'siret'              => '55210055400013',
                'adresse'            => '12 RUE DES FORGES 35000 RENNES',
                'code_postal'        => '35000',
                'libelle_commune'    => 'RENNES',
                'etat_administratif' => 'A',
                'est_siege'          => true,
            ],
            'matching_etablissements' => [[
                'siret'              => '55210055400021',
                'adresse'            => 'ZI DU MOULIN 44000 NANTES',
                'code_postal'        => '44000',
                'libelle_commune'    => 'NANTES',
                'etat_administratif' => 'F',
                'est_siege'          => false,
            ]],
        ], $overrides);
    }

    public function test_the_vat_key_is_computed_from_the_siren(): void
    {
        // Exemple officiel : SIREN 404 833 048 → FR83404833048.
        $this->assertSame('FR83404833048', CompanyRegistryLookup::vatNumber('404833048'));
        $this->assertNull(CompanyRegistryLookup::vatNumber('12345'));
    }

    public function test_a_siren_search_returns_the_head_office(): void
    {
        Http::fake([self::API => Http::response(['results' => [$this->unit()]])]);

        $this->getJson(route('companies.json.registry-search', ['q' => '552 100 554']))
            ->assertOk()
            ->assertJsonPath('results.0.label', 'ACME TOLERIE')
            ->assertJsonPath('results.0.siret', '55210055400013')
            ->assertJsonPath('results.0.naf_code', '25.62B')
            ->assertJsonPath('results.0.intra_community_vat', CompanyRegistryLookup::vatNumber('552100554'))
            ->assertJsonPath('results.0.active', true)
            ->assertJsonPath('results.0.address.adress', '12 RUE DES FORGES')
            ->assertJsonPath('results.0.address.zipcode', '35000')
            ->assertJsonPath('results.0.address.city', 'RENNES')
            ->assertJsonPath('results.0.address.country', 'France');

        // Les espaces du SIREN sont retirés avant l'envoi.
        Http::assertSent(fn ($request) => $request['q'] === '552100554');
    }

    public function test_a_siret_search_returns_the_matching_establishment(): void
    {
        Http::fake([self::API => Http::response(['results' => [$this->unit()]])]);

        $this->getJson(route('companies.json.registry-search', ['q' => '55210055400021']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.siret', '55210055400021')
            ->assertJsonPath('results.0.is_head_office', false)
            ->assertJsonPath('results.0.active', false)
            ->assertJsonPath('results.0.address.city', 'NANTES');
    }

    public function test_a_short_query_is_refused_without_calling_the_api(): void
    {
        Http::fake();

        $this->getJson(route('companies.json.registry-search', ['q' => 'ab']))->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_an_unreachable_registry_answers_503(): void
    {
        Http::fake([self::API => Http::response('', 500)]);

        $this->getJson(route('companies.json.registry-search', ['q' => 'acme']))
            ->assertStatus(503)
            ->assertJsonStructure(['message']);
    }

    public function test_a_disabled_registry_answers_404(): void
    {
        config()->set('services.company_registry.enabled', false);
        Http::fake();

        $this->getJson(route('companies.json.registry-search', ['q' => 'acme']))->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_creation_stores_the_registry_fields_and_the_default_address(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('companies.json.store'), [
            'code'                => 'CLI-REG-1',
            'label'               => 'ACME TOLERIE',
            'client_type'         => 1,
            'user_id'             => $user->id,
            'siren'               => '552100554',
            'naf_code'            => '25.62B',
            'intra_community_vat' => 'FR02552100554',
            'address'             => ['adress' => '12 RUE DES FORGES', 'zipcode' => '35000', 'city' => 'RENNES', 'country' => 'France'],
        ])->assertCreated();

        $company = Companies::where('code', 'CLI-REG-1')->firstOrFail();
        $this->assertSame('552100554', $company->siren);
        $this->assertSame('25.62B', $company->naf_code);

        $address = CompaniesAddresses::where('companies_id', $company->id)->sole();
        $this->assertSame('RENNES', $address->city);
        $this->assertEquals(1, $address->default);
    }

    public function test_creation_without_registry_data_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('companies.json.store'), [
            'code'        => 'CLI-REG-2',
            'label'       => 'Sans répertoire',
            'client_type' => 1,
            'user_id'     => $user->id,
        ])->assertCreated();

        $company = Companies::where('code', 'CLI-REG-2')->firstOrFail();
        $this->assertNull($company->siren);
        $this->assertSame(0, CompaniesAddresses::where('companies_id', $company->id)->count());
    }
}
