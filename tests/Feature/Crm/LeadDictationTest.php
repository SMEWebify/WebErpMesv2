<?php

namespace Tests\Feature\Crm;

use App\Models\Admin\Factory;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\Integrations\AISetting;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Services\AI\AISettingsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Dictée d'une piste : n'existe que si l'entreprise a autorisé l'envoi de données
 * personnelles au provider (carte IA). Le brouillon n'écrit rien ; société et
 * contact sont rapprochés de la base par l'ERP, jamais envoyés au provider.
 */
class LeadDictationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

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
    }

    private function aiSetting(bool $allow, string $provider = 'claude'): AISetting
    {
        $setting = AISetting::create([
            'provider' => $provider, 'api_key' => 'test-key', 'model' => 'claude-haiku-4-5-20251001',
            'max_tokens' => 2048, 'timeout_seconds' => 30, 'is_active' => true,
        ]);
        $setting->allow_personal_data = $allow;
        $setting->save();
        app(AISettingsResolver::class)->forget();

        return $setting;
    }

    private function fakeClaude(array $answer): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode($answer)]],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 10],
            ]),
        ]);
    }

    private function propose(string $text = 'appel de M. Martin de Dupont Métal')
    {
        return $this->postJson(route('leads.json.dictation.propose'), ['text' => $text]);
    }

    public function test_without_the_personal_data_permission_the_dictation_is_refused(): void
    {
        $this->aiSetting(false);
        Http::fake();

        $this->propose()->assertForbidden()->assertJsonPath('message', __('lead_dictation.unavailable'));
        $this->postJson(route('leads.json.dictation.store'), [
            'company_label' => 'Dupont Métal', 'contact_name' => 'Martin', 'source' => 'Téléphone', 'priority' => 3,
        ])->assertForbidden();

        Http::assertNothingSent();
        $this->assertSame(0, Leads::count());
    }

    public function test_a_configuration_from_the_env_file_alone_has_not_allowed_anything(): void
    {
        config(['ai.providers.claude.api_key' => 'env-key']);
        Http::fake();

        $this->propose()->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_the_index_shows_the_button_only_when_allowed(): void
    {
        $this->aiSetting(false);
        $this->get(route('leads'))->assertOk()->assertDontSee('leads\/json\/dictation\/propose', false);

        AISetting::query()->update(['allow_personal_data' => true]);
        app(AISettingsResolver::class)->forget();
        $this->get(route('leads'))->assertOk()->assertSee('leads\/json\/dictation\/propose', false);
    }

    public function test_it_proposes_a_draft_matched_with_the_database_without_saving_anything(): void
    {
        $this->aiSetting(true);
        $company = Companies::factory()->create(['label' => 'Dupont Métal']);
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id, 'first_name' => 'Paul', 'name' => 'Martin', 'mail' => 'p.martin@dupont.test']);
        Companies::factory()->create(['label' => 'Dupont Métal Services']);

        $this->fakeClaude([
            'company' => 'Dupont Métal', 'contact_first_name' => 'Paul', 'contact_last_name' => 'Martin',
            'contact_function' => 'Acheteur', 'email' => null, 'phone' => '02 99 00 00 00',
            'subject' => '200 supports pliés inox', 'need' => '200 supports pliés en inox 304 pour fin janvier.', 'priority' => 1,
        ]);

        $draft = $this->propose('appel de Paul Martin, Dupont Métal, 200 supports inox, urgent')->assertOk()->json('draft');

        $this->assertSame($company->id, $draft['companies_id'], 'nom identique : société choisie');
        $this->assertSame($contact->id, $draft['companies_contacts_id'], 'contact rapproché par son nom');
        $this->assertCount(2, $draft['candidates'], 'société approchante proposée aussi');
        $this->assertSame(1, $draft['priority']);
        $this->assertSame('200 supports pliés en inox 304 pour fin janvier.', $draft['comment']);
        $this->assertSame(__('lead_dictation.default_source'), $draft['source']);

        $this->assertSame(0, Leads::count());

        // Seuls la consigne et la dictée partent : rien de la base (code, autres sociétés).
        Http::assertSent(function (HttpRequest $request) use ($company) {
            $body = json_encode($request->data(), JSON_UNESCAPED_UNICODE);

            return str_contains($body, '<dictee>') && ! str_contains($body, 'Dupont Métal Services') && ! str_contains($body, (string) $company->code);
        });
    }

    public function test_a_contact_recognised_by_email_designates_its_company(): void
    {
        $this->aiSetting(true);
        $company = Companies::factory()->create(['label' => 'Société Alpha']);
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id, 'mail' => 'achat@alpha.test']);

        $this->fakeClaude(['company' => 'Alfa', 'contact_last_name' => 'Durand', 'email' => 'ACHAT@alpha.test', 'priority' => 9]);

        $draft = $this->propose()->assertOk()->json('draft');

        $this->assertSame($company->id, $draft['companies_id']);
        $this->assertSame($contact->id, $draft['companies_contacts_id']);
        $this->assertSame(3, $draft['priority'], 'priorité hors 1-4 ramenée à tiède');
    }

    public function test_an_unknown_company_and_contact_are_created_as_a_prospect(): void
    {
        $this->aiSetting(true);

        $response = $this->postJson(route('leads.json.dictation.store'), [
            'company_label' => 'Dupont Métal', 'contact_first_name' => 'Paul', 'contact_name' => 'Martin',
            'contact_mail' => 'p.martin@dupont.test', 'contact_number' => '02 99 00 00 00',
            'subject' => '200 supports', 'comment' => "200 supports <inox>\nfin janvier", 'source' => 'Téléphone', 'priority' => 2,
        ])->assertCreated();

        $lead = Leads::firstOrFail();
        $this->assertStringEndsWith('/' . $lead->id, $response->json('redirect'));
        $this->assertSame((int) $this->user->id, (int) $lead->user_id);
        $this->assertSame(1, (int) $lead->statu);
        $this->assertSame(2, (int) $lead->priority);
        $this->assertSame('Téléphone', $lead->source);
        $this->assertSame('200 supports', $lead->subject);
        $this->assertSame('200 supports &lt;inox&gt;<br>' . "\n" . 'fin janvier', $lead->comment, 'texte échappé');

        $company = Companies::findOrFail($lead->companies_id);
        $this->assertSame('Dupont Métal', $company->label);
        $contact = CompaniesContacts::findOrFail($lead->companies_contacts_id);
        $this->assertSame(['Paul', 'Martin', 'p.martin@dupont.test', '02 99 00 00 00'], [$contact->first_name, $contact->name, $contact->mail, $contact->number]);
        $this->assertSame($company->id, CompaniesAddresses::findOrFail($lead->companies_addresses_id)->companies_id, 'adresse à compléter');
    }

    public function test_an_existing_company_and_contact_are_reused(): void
    {
        $this->aiSetting(true);
        $company = Companies::factory()->create();
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $address = CompaniesAddresses::factory()->create(['companies_id' => $company->id]);
        $companies = Companies::count();
        $contacts = CompaniesContacts::count();

        $this->postJson(route('leads.json.dictation.store'), [
            'companies_id' => $company->id, 'companies_contacts_id' => $contact->id, 'source' => 'Salon', 'priority' => 3,
        ])->assertCreated();

        $lead = Leads::firstOrFail();
        $this->assertSame([$company->id, $contact->id, $address->id], [(int) $lead->companies_id, (int) $lead->companies_contacts_id, (int) $lead->companies_addresses_id]);
        $this->assertSame($companies, Companies::count());
        $this->assertSame($contacts, CompaniesContacts::count());
    }

    public function test_a_contact_of_another_company_is_refused(): void
    {
        $this->aiSetting(true);
        $company = Companies::factory()->create();
        $other = CompaniesContacts::factory()->create(['companies_id' => Companies::factory()->create()->id]);

        $this->postJson(route('leads.json.dictation.store'), [
            'companies_id' => $company->id, 'companies_contacts_id' => $other->id, 'source' => 'Salon', 'priority' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors(['companies_contacts_id']);

        $this->assertSame(0, Leads::count());
    }

    public function test_changing_provider_withdraws_the_permission(): void
    {
        $this->withoutMiddleware();
        $this->aiSetting(true);

        $this->put(route('admin.integrations.ai.update'), [
            'provider' => 'ovh', 'max_tokens' => 2048, 'timeout_seconds' => 30, 'is_active' => 1, 'allow_personal_data' => 1,
        ])->assertRedirect();

        $this->assertFalse((bool) AISetting::current()->allow_personal_data);
        $this->assertFalse(app(AISettingsResolver::class)->allowsPersonalData());
    }

    public function test_the_permission_is_recorded_with_its_author_and_date(): void
    {
        $this->withoutMiddleware();
        $this->aiSetting(false);

        $this->put(route('admin.integrations.ai.update'), [
            'provider' => 'claude', 'max_tokens' => 2048, 'timeout_seconds' => 30, 'is_active' => 1, 'allow_personal_data' => 1,
        ])->assertRedirect();

        $setting = AISetting::current();
        $this->assertTrue((bool) $setting->allow_personal_data);
        $this->assertSame((int) $this->user->id, (int) $setting->personal_data_allowed_by);
        $this->assertNotNull($setting->personal_data_allowed_at);
        $this->assertTrue(app(AISettingsResolver::class)->allowsPersonalData());

        $this->put(route('admin.integrations.ai.update'), [
            'provider' => 'claude', 'max_tokens' => 2048, 'timeout_seconds' => 30, 'is_active' => 1, 'allow_personal_data' => 0,
        ])->assertRedirect();

        $setting->refresh();
        $this->assertFalse((bool) $setting->allow_personal_data);
        $this->assertNull($setting->personal_data_allowed_by);
    }
}
