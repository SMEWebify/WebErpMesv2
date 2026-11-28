<?php

namespace Tests\Feature\Crm;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\Leads;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * API prospect : un logiciel tiers (chiffrage, formulaire) crée ou retrouve la
 * société et son contact, ne fait que compléter une fiche connue (sauf passage en
 * client), et ouvre une piste sans doublon.
 */
class InboundProspectTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        $this->user = User::factory()->create(['email' => 'sales@example.test']);
    }

    private function callApi(array $payload, array $abilities = ['prospects:write'])
    {
        Sanctum::actingAs($this->user, $abilities);

        return $this->postJson(route('api.prospects.store'), $payload);
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'company' => ['label' => 'Acme Métal', 'siren' => '123 456 789', 'website' => 'www.acme-metal.test'],
            'contact' => ['first_name' => 'Jane', 'name' => 'Buyer', 'email' => 'Jane.Buyer@acme-metal.test', 'phone' => '0102030405', 'function' => 'Acheteuse'],
            'address' => ['adress' => '1 rue des Forges', 'zipcode' => '35000', 'city' => 'Rennes'],
            'lead'    => ['subject' => 'Demande de prix', 'message' => "Bonjour,\n<b>20 pièces</b>", 'source' => 'Site web', 'external_id' => 'form-42'],
            'owner_email' => 'sales@example.test',
        ], $override);
    }

    public function test_unknown_prospect_creates_company_contact_address_and_lead(): void
    {
        $response = $this->callApi($this->payload())->assertCreated();

        $company = Companies::findOrFail($response->json('company.id'));
        $this->assertSame('Acme Métal', $company->label);
        $this->assertSame('123456789', $company->siren);
        $this->assertSame(3, (int) $company->statu_customer); // prospect
        $this->assertTrue($response->json('company.created'));
        $this->assertNull($response->json('company.matched_by'));

        $contact = CompaniesContacts::findOrFail($response->json('contact.id'));
        $this->assertSame('jane.buyer@acme-metal.test', $contact->mail);
        $this->assertSame('Buyer', $contact->name);
        $this->assertSame('0102030405', $contact->number);

        $this->assertSame('Rennes', CompaniesAddresses::where('companies_id', $company->id)->value('city'));

        $lead = Leads::findOrFail($response->json('lead.id'));
        $this->assertSame('Site web', $lead->source);
        $this->assertSame('api:form-42', $lead->message_id);
        $this->assertSame($this->user->id, $lead->user_id);
        $this->assertStringContainsString('&lt;b&gt;20 pièces&lt;/b&gt;', $lead->comment);
        $this->assertSame(1, $this->user->notifications()->count());
    }

    public function test_a_replayed_call_returns_the_existing_records(): void
    {
        $first = $this->callApi($this->payload())->assertCreated();
        $second = $this->callApi($this->payload())->assertOk();

        $this->assertSame($first->json('lead.id'), $second->json('lead.id'));
        $this->assertFalse($second->json('lead.created'));
        $this->assertSame(1, Companies::count());
        $this->assertSame(1, Leads::count());
        $this->assertSame(1, $this->user->notifications()->count());
    }

    public function test_known_siren_is_completed_but_never_overwritten(): void
    {
        $known = Companies::factory()->create(['label' => 'Nom relu', 'siren' => '123456789', 'website' => null, 'naf_code' => '25.62B']);
        CompaniesAddresses::create(['companies_id' => $known->id, 'ordre' => 1, 'label' => 'Siège', 'adress' => '9 quai Saisi', 'zipcode' => '44000', 'city' => 'Nantes', 'default' => 1]);

        $response = $this->callApi($this->payload(['company' => ['naf_code' => '99.99Z'], 'lead' => null]))->assertCreated();

        $this->assertSame($known->id, $response->json('company.id'));
        $this->assertSame('siren', $response->json('company.matched_by'));
        $this->assertSame(['website'], $response->json('company.updated'));
        $this->assertTrue($response->json('contact.created'));
        $this->assertNull($response->json('lead'));
        $known->refresh();
        $this->assertSame('Nom relu', $known->label);
        $this->assertSame('25.62B', $known->naf_code);
        $this->assertSame('www.acme-metal.test', $known->website);
        $this->assertSame(['Nantes'], CompaniesAddresses::where('companies_id', $known->id)->pluck('city')->all());
    }

    public function test_estimating_software_finds_its_client_again_and_makes_it_a_customer(): void
    {
        $first = $this->callApi([
            'system'  => 'alma',
            'company' => ['label' => 'Acme Métal', 'external_id' => 'C-0042'],
            'contact' => ['name' => 'Buyer', 'email' => 'jane.buyer@acme-metal.test'],
        ])->assertCreated();
        $this->assertSame(3, (int) Companies::findOrFail($first->json('company.id'))->statu_customer);

        // Second envoi du logiciel : retrouvé par sa référence, la fiche est complétée, rien n'est écrasé.
        $second = $this->callApi([
            'system'  => 'alma',
            'company' => ['label' => 'Autre nom', 'external_id' => 'C-0042', 'status' => 'customer', 'siren' => '123456789'],
            'contact' => ['name' => 'Buyer', 'first_name' => 'Jane', 'email' => 'jane.buyer@acme-metal.test', 'phone' => '0102030405'],
            'address' => ['adress' => '1 rue des Forges', 'zipcode' => '35000', 'city' => 'Rennes'],
        ])->assertOk();

        $this->assertSame($first->json('company.id'), $second->json('company.id'));
        $this->assertSame('external_id', $second->json('company.matched_by'));
        $this->assertEqualsCanonicalizing(['siren', 'status', 'address'], $second->json('company.updated'));
        $this->assertEqualsCanonicalizing(['first_name', 'phone'], $second->json('contact.updated'));

        $company = Companies::findOrFail($first->json('company.id'));
        $this->assertSame('Acme Métal', $company->label);
        $this->assertSame(2, (int) $company->statu_customer);
        // L'adresse « à compléter » posée à la création est remplie, pas doublée.
        $this->assertSame(['Rennes'], CompaniesAddresses::where('companies_id', $company->id)->pluck('city')->all());
        $this->assertSame(1, Companies::count());
    }

    public function test_a_customer_is_never_downgraded_to_prospect(): void
    {
        $known = Companies::factory()->create(['siren' => '123456789', 'statu_customer' => 2]);

        $this->callApi($this->payload(['company' => ['status' => 'prospect'], 'lead' => null]));

        $this->assertSame(2, (int) $known->fresh()->statu_customer);
    }

    public function test_external_id_requires_the_system(): void
    {
        $this->callApi($this->payload(['company' => ['external_id' => 'C-1']]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['system']);
    }

    public function test_known_contact_is_reused_without_creating_anything(): void
    {
        $known = Companies::factory()->create(['siren' => null]);
        $contact = CompaniesContacts::create(['companies_id' => $known->id, 'ordre' => 1, 'name' => 'Buyer', 'mail' => 'jane.buyer@acme-metal.test', 'default' => 1]);

        $response = $this->callApi($this->payload(['company' => ['siren' => null], 'lead' => null]))->assertOk();

        $this->assertSame($known->id, $response->json('company.id'));
        $this->assertSame('contact', $response->json('company.matched_by'));
        $this->assertSame($contact->id, $response->json('contact.id'));
        $this->assertFalse($response->json('contact.created'));
        $this->assertSame(1, Companies::count());
    }

    public function test_public_mail_domain_creates_a_company_named_after_the_person(): void
    {
        $response = $this->callApi([
            'contact' => ['first_name' => 'Paul', 'name' => 'Martin', 'email' => 'paul.martin@gmail.com'],
        ])->assertCreated();

        $this->assertSame('Paul Martin', $response->json('company.label'));
        $this->assertNull($response->json('lead'));
    }

    public function test_unreachable_contact_and_bad_siren_are_refused(): void
    {
        $this->callApi(['contact' => ['name' => 'Martin'], 'company' => ['siren' => '12345']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contact.phone', 'company.siren']);

        $this->callApi(['contact' => ['phone' => '0102030405']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contact.email']);

        $this->assertSame(0, Companies::count());
    }

    public function test_token_without_the_ability_is_refused(): void
    {
        $this->callApi($this->payload(), ['leads:inbound'])->assertForbidden();
        $this->assertSame(0, Companies::count());
    }
}
