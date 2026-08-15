<?php

namespace Tests\Feature;

use App\Services\Integrations\Pdp\Drivers\SuperPdpGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Annuaire de la facturation électronique.
 *
 * L'enjeu de ces tests est l'ouverture de la ligne de réception : sans elle,
 * aucune facture fournisseur ne parvient à l'entreprise. Et l'annuaire visé
 * comme le format de l'identifiant changent entre bac à sable et production —
 * une erreur ici se solde par un refus incompréhensible.
 */
class SuperPdpDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.superpdp.client_id', 'client-id');
        config()->set('services.superpdp.client_secret', 'client-secret');
        config()->set('services.superpdp.base_url', 'https://api.superpdp.tech');

        Cache::flush();
    }

    public function test_our_directory_lines_are_listed(): void
    {
        $this->fakeHttp('sandbox', [
            'https://api.superpdp.tech/v1.beta/directory_entries' => Http::response([
                'data' => [
                    ['id' => 1, 'identifier' => '0225:853322915', 'directory' => 'peppol', 'effective_date' => '2026-09-01T00:00:00Z'],
                    ['id' => 2, 'identifier' => '0225:853322915_replyto', 'directory' => 'peppol', 'is_replyto' => true],
                ],
            ]),
        ]);

        $entries = $this->gateway()->listEntries();

        $this->assertCount(2, $entries);
        $this->assertSame('0225:853322915', $entries[0]['identifier']);
        $this->assertSame('2026-09-01', $entries[0]['effective_date']);
        $this->assertFalse($entries[0]['is_replyto']);
        $this->assertTrue($entries[1]['is_replyto']);
    }

    public function test_in_sandbox_the_line_is_opened_in_the_peppol_directory_with_a_prefixed_identifier(): void
    {
        // En bac à sable, seul l'annuaire `peppol` est ouvrable, et l'adresse
        // doit y porter son scheme ID.
        $this->fakeHttp('sandbox', [
            'https://api.superpdp.tech/v1.beta/directory_entries' => Http::response(['id' => 10, 'identifier' => '0225:853322915', 'directory' => 'peppol']),
        ]);

        $this->gateway()->openEntry('853322915');

        Http::assertSent(fn (ClientRequest $r) => $this->isEntryCreation($r)
            && $r->data()['directory'] === 'peppol'
            && $r->data()['identifier'] === '0225:853322915');
    }

    public function test_in_production_a_french_identifier_goes_to_the_ppf_directory_unprefixed(): void
    {
        // En production, les identifiants français passent par `ppf` : la
        // plateforme crée elle-même l'entrée Peppol correspondante.
        $this->fakeHttp('production', [
            'https://api.superpdp.tech/v1.beta/directory_entries' => Http::response(['id' => 11, 'identifier' => '853322915', 'directory' => 'ppf']),
        ]);

        $this->gateway()->openEntry('0225:853322915', '2026-09-01');

        Http::assertSent(fn (ClientRequest $r) => $this->isEntryCreation($r)
            && $r->data()['directory'] === 'ppf'
            && $r->data()['identifier'] === '853322915'
            && $r->data()['effective_date'] === '2026-09-01');
    }

    public function test_the_effective_date_is_dropped_outside_the_french_directory(): void
    {
        // La date de prise d'effet n'a de sens que pour l'annuaire français ;
        // l'envoyer ailleurs ferait refuser la requête.
        $this->fakeHttp('sandbox', [
            'https://api.superpdp.tech/v1.beta/directory_entries' => Http::response(['id' => 12]),
        ]);

        $this->gateway()->openEntry('853322915', '2026-09-01');

        Http::assertSent(fn (ClientRequest $r) => $this->isEntryCreation($r)
            && ! isset($r->data()['effective_date']));
    }

    public function test_a_refused_opening_is_reported_readably(): void
    {
        $this->fakeHttp('sandbox', [
            'https://api.superpdp.tech/v1.beta/directory_entries' => Http::response(['message' => 'identifier already registered'], 422),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/identifier already registered/');

        $this->gateway()->openEntry('853322915');
    }

    public function test_a_client_billing_address_is_looked_up_by_siren(): void
    {
        $this->fakeHttp('production', [
            'https://api.superpdp.tech/v1.beta/french_directory/entries*' => Http::response([
                'data' => [[
                    'identifier' => '0225:853322915',
                    'is_active'  => true,
                    'company'    => ['formal_name' => 'SUPER G', 'city' => 'PARIS 20E ARRONDISSEMENT'],
                ]],
            ]),
        ]);

        $entries = $this->gateway()->lookupEntries('853 322 915');

        $this->assertSame('0225:853322915', $entries[0]['identifier']);
        $this->assertSame('SUPER G', $entries[0]['name']);
        $this->assertTrue($entries[0]['is_active']);

        // Le SIREN est normalisé : les utilisateurs le saisissent espacé.
        Http::assertSent(fn (ClientRequest $r) => str_contains($r->url(), 'number=853322915'));
    }

    public function test_companies_can_be_searched_by_name(): void
    {
        $this->fakeHttp('production', [
            'https://api.superpdp.tech/v1.beta/french_directory/companies*' => Http::response([
                'data' => [['number' => '853322915', 'formal_name' => 'SUPER G', 'postcode' => '75020', 'city' => 'PARIS']],
            ]),
        ]);

        $companies = $this->gateway()->searchCompanies(['name' => 'SUPER']);

        $this->assertSame('853322915', $companies[0]['number']);
        Http::assertSent(fn (ClientRequest $r) => str_contains(urldecode($r->url()), 'formal_name_starts_with=SUPER'));
    }

    /* ------------------------------------------------------------- Utilitaires */

    /** L'assertion est jouée sur toutes les requêtes, jeton OAuth compris. */
    private function isEntryCreation(ClientRequest $request): bool
    {
        return $request->method() === 'POST'
            && str_ends_with($request->url(), '/v1.beta/directory_entries');
    }

    private function gateway(): SuperPdpGateway
    {
        return $this->app->make(SuperPdpGateway::class);
    }

    private function fakeHttp(string $env, array $stubs): void
    {
        Http::fake($stubs + [
            'https://api.superpdp.tech/oauth2/token'          => Http::response(['access_token' => 'access-token', 'expires_in' => 1800]),
            'https://api.superpdp.tech/v1.beta/companies/me'  => Http::response(['id' => 1, 'env' => $env]),
        ]);
    }
}
