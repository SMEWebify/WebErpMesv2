<?php

namespace Tests\Feature\Crm;

use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Quotes;
use App\Services\Crm\InboundMailFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Demandes entrantes : un e-mail (API appelée par n8n, ou .eml / .msg déposé)
 * devient une piste rapprochée du bon client, sans doublon, convertible en devis.
 */
class InboundLeadTest extends TestCase
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
        Storage::fake(config('files.disk'));
        $this->user = User::factory()->create(['email' => 'sales@example.test']);
    }

    private function callApi(array $payload, array $abilities = ['leads:inbound'])
    {
        Sanctum::actingAs($this->user, $abilities);

        return $this->post(route('api.leads.inbound'), $payload + [
            'from_email'  => 'Jane.Buyer@acme-metal.test',
            'from_name'   => 'Jane Buyer',
            'subject'     => 'Demande de prix poinçons',
            'received_at' => '2026-11-18 08:42:00',
            'body'        => "Bonjour,\nmerci de chiffrer <3 poinçons>.",
            'message_id'  => '<abc-123@acme-metal.test>',
        ], ['Accept' => 'application/json']);
    }

    // ------------------------------------------------------------------
    // API (n8n)
    // ------------------------------------------------------------------

    public function test_unknown_sender_creates_a_prospect_and_a_lead_with_its_attachments(): void
    {
        $response = $this->callApi([
            'attachments' => [UploadedFile::fake()->create('plan.pdf', 50, 'application/pdf')],
            'original'    => UploadedFile::fake()->createWithContent('demande.eml', "From: x@y.test\r\n\r\nbody"),
        ])->assertCreated()->assertJson(['created' => true]);

        $lead = Leads::findOrFail($response->json('id'));
        $this->assertSame('Demande de prix poinçons', $lead->subject);
        $this->assertSame('jane.buyer@acme-metal.test', $lead->sender_email);
        $this->assertSame('abc-123@acme-metal.test', $lead->message_id);
        $this->assertSame('2026-11-18 08:42', $lead->received_at->format('Y-m-d H:i'));
        $this->assertSame(1, $lead->statu);
        $this->assertNull($lead->user_id);
        // Le texte du mail est échappé avant d'entrer dans le commentaire (éditeur riche).
        $this->assertStringContainsString('&lt;3 poinçons&gt;', $lead->comment);

        $company = Companies::findOrFail($lead->companies_id);
        $this->assertSame('Jane Buyer', $company->label);
        $this->assertSame(1, (int) $company->statu_customer);
        $contact = CompaniesContacts::findOrFail($lead->companies_contacts_id);
        $this->assertSame(['Jane', 'Buyer', 'jane.buyer@acme-metal.test'], [$contact->first_name, $contact->name, $contact->mail]);
        $this->assertSame($company->id, CompaniesAddresses::findOrFail($lead->companies_addresses_id)->companies_id);

        $files = $lead->files()->get();
        $this->assertCount(2, $files);
        $this->assertTrue((bool) $files->firstWhere('original_file_name', 'demande.eml')->pivot->is_primary);
    }

    public function test_a_replayed_call_returns_the_existing_lead(): void
    {
        $first = $this->callApi([])->assertCreated()->json('id');
        $this->callApi([])->assertOk()->assertJson(['id' => $first, 'created' => false]);

        $this->assertSame(1, Leads::count());
        $this->assertSame(1, Companies::count());
    }

    public function test_known_contact_is_reused(): void
    {
        $company = Companies::factory()->create();
        $address = CompaniesAddresses::factory()->create(['companies_id' => $company->id]);
        $contact = CompaniesContacts::factory()->create(['companies_id' => $company->id, 'mail' => 'jane.buyer@acme-metal.test']);

        $id = $this->callApi([])->assertCreated()->json('id');

        $lead = Leads::findOrFail($id);
        $this->assertSame([$company->id, $contact->id, $address->id], [$lead->companies_id, $lead->companies_contacts_id, $lead->companies_addresses_id]);
        $this->assertSame(1, Companies::count());
    }

    public function test_sender_is_attached_to_the_company_of_its_domain(): void
    {
        $company = Companies::factory()->create();
        CompaniesContacts::factory()->create(['companies_id' => $company->id, 'mail' => 'boss@acme-metal.test']);

        $id = $this->callApi([])->assertCreated()->json('id');

        $lead = Leads::findOrFail($id);
        $this->assertSame($company->id, $lead->companies_id);
        $this->assertSame('jane.buyer@acme-metal.test', CompaniesContacts::findOrFail($lead->companies_contacts_id)->mail);
        $this->assertSame(1, Companies::count());
    }

    public function test_public_mail_domain_never_matches_a_company(): void
    {
        $company = Companies::factory()->create();
        CompaniesContacts::factory()->create(['companies_id' => $company->id, 'mail' => 'someone@gmail.com']);

        $id = $this->callApi(['from_email' => 'other@gmail.com'])->assertCreated()->json('id');

        $this->assertNotSame($company->id, Leads::findOrFail($id)->companies_id);
        $this->assertSame(2, Companies::count());
    }

    public function test_owner_is_taken_from_the_call(): void
    {
        $id = $this->callApi(['owner_email' => 'sales@example.test'])->assertCreated()->json('id');

        $this->assertSame($this->user->id, Leads::findOrFail($id)->user_id);
        $this->assertSame(1, $this->user->notifications()->count());
    }

    public function test_token_without_the_ability_is_refused(): void
    {
        $this->callApi([], ['quotes:write'])->assertForbidden();
        $this->assertSame(0, Leads::count());
    }

    // ------------------------------------------------------------------
    // Dépôt d'un e-mail dans l'ERP
    // ------------------------------------------------------------------

    private function eml(): string
    {
        return implode("\r\n", [
            'From: "Paul Martin" <paul.martin@tolerie-durand.test>',
            'To: ventes@example.test',
            'Subject: =?UTF-8?Q?Matrice_de_pliage_88=C2=B0?=',
            'Date: Tue, 17 Nov 2026 14:05:00 +0100',
            'Message-ID: <',
            ' msg-42@tolerie-durand.test>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="b1"',
            '',
            '--b1',
            'Content-Type: text/plain; charset=UTF-8',
            '',
            'Pouvez-vous nous faire une offre ?',
            '--b1',
            'Content-Type: image/png',
            'Content-ID: <logo@sig>',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode('png'),
            '--b1',
            'Content-Type: application/pdf; name="plan.pdf"',
            'Content-Disposition: attachment; filename="plan.pdf"',
            'Content-Transfer-Encoding: base64',
            '',
            base64_encode('%PDF-1.4 test'),
            '--b1--',
            '',
        ]);
    }

    public function test_dropped_eml_becomes_a_lead_assigned_to_the_uploader(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('leads.json.import-mail'), [
            'mail' => UploadedFile::fake()->createWithContent('demande.eml', $this->eml()),
        ], ['Accept' => 'application/json'])->assertCreated()->assertJson(['created' => true]);

        $lead = Leads::latest('id')->firstOrFail();
        $this->assertStringEndsWith('/leads/' . $lead->id, $response->json('redirect'));
        $this->assertSame('Matrice de pliage 88°', $lead->subject);
        $this->assertSame('msg-42@tolerie-durand.test', $lead->message_id);
        $this->assertSame($this->user->id, $lead->user_id);
        $this->assertStringContainsString('Pouvez-vous nous faire une offre', $lead->comment);

        // L'e-mail d'origine et le plan ; le logo de signature (Content-ID) est laissé de côté.
        $names = $lead->files()->pluck('original_file_name')->sort()->values()->all();
        $this->assertSame(['demande.eml', 'plan.pdf'], $names);

        // Déposé une seconde fois : la même piste.
        $this->post(route('leads.json.import-mail'), [
            'mail' => UploadedFile::fake()->createWithContent('demande.eml', $this->eml()),
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['created' => false]);
        $this->assertSame(1, Leads::count());
    }

    public function test_mail_without_sender_is_refused(): void
    {
        $this->actingAs($this->user);

        $this->post(route('leads.json.import-mail'), [
            'mail' => UploadedFile::fake()->createWithContent('vide.eml', "Subject: test\r\n\r\nrien"),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertSame(0, Leads::count());
    }

    public function test_mailbox_header_is_split_into_name_and_address(): void
    {
        $this->assertSame(['Jane Buyer', 'jane@x.test'], InboundMailFile::mailbox('"Jane Buyer" <Jane@X.test>'));
        $this->assertSame([null, 'jane@x.test'], InboundMailFile::mailbox('jane@x.test'));
        $this->assertSame([null, null], InboundMailFile::mailbox('Jane Buyer'));
    }

    // ------------------------------------------------------------------
    // Conversion en devis
    // ------------------------------------------------------------------

    public function test_lead_becomes_a_quote_through_an_opportunity(): void
    {
        AccountingPaymentConditions::factory()->create(['default' => 1]);
        AccountingPaymentMethod::factory()->create(['default' => 1]);
        AccountingDelivery::factory()->create(['default' => 1]);
        $this->actingAs($this->user);

        $leadId = $this->callApi([])->json('id');
        $this->actingAs($this->user);

        $this->post(route('leads.store.quote', ['id' => $leadId]))->assertRedirect();

        $lead = Leads::findOrFail($leadId);
        $this->assertSame(4, (int) $lead->statu);
        $opportunity = Opportunities::where('leads_id', $leadId)->firstOrFail();
        $this->assertSame('Demande de prix poinçons', $opportunity->label);
        $quote = Quotes::where('opportunities_id', $opportunity->id)->firstOrFail();
        $this->assertSame($lead->companies_id, $quote->companies_id);

        // Une seconde conversion reprend l'opportunité existante.
        $this->post(route('leads.store.quote', ['id' => $leadId]))->assertRedirect();
        $this->assertSame(1, Opportunities::where('leads_id', $leadId)->count());
        $this->assertSame(2, Quotes::where('opportunities_id', $opportunity->id)->count());
    }

    public function test_quote_conversion_without_accounting_defaults_creates_nothing(): void
    {
        $leadId = $this->callApi([])->json('id');
        $this->actingAs($this->user);

        $this->post(route('leads.store.quote', ['id' => $leadId]))
            ->assertRedirect(route('leads.show', ['id' => $leadId]))
            ->assertSessionHas('error');

        $this->assertSame(0, Opportunities::count());
        $this->assertSame(0, Quotes::count());
    }
}
