<?php

namespace Tests\Feature\Crm;

use App\Models\Companies\Companies;
use App\Models\EmailLog;
use App\Models\User;
use App\Models\Workflow\Leads;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Journal des échanges : les e-mails envoyés depuis l'ERP sur les documents d'une
 * société apparaissent dans son historique, seulement pour les modules ouverts.
 */
class CompanyEmailJournalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        foreach (['quotes-menu', 'orders-menu', 'leads-menu'] as $permission) {
            Permission::findOrCreate($permission);
        }
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->company = Companies::factory()->create();
    }

    private function mail($document, string $status = EmailLog::STATUS_SENT): EmailLog
    {
        $log = new EmailLog(['to' => 'buyer@client.test', 'subject' => 'Votre devis', 'message' => 'Bonjour', 'status' => $status, 'sent_at' => now()]);
        $log->emailable()->associate($document);
        $log->save();

        return $log;
    }

    private function timeline(): array
    {
        return $this->getJson(route('companies.json.timeline', ['id' => $this->company->id]))->assertOk()->json();
    }

    public function test_sent_e_mails_appear_in_the_company_history(): void
    {
        $this->user->givePermissionTo('quotes-menu');
        $quote = Quotes::factory()->create(['companies_id' => $this->company->id]);
        $other = Quotes::factory()->create(['companies_id' => Companies::factory()->create()->id]);
        $this->mail($quote);
        $this->mail($other);

        $emails = collect($this->timeline())->where('type', 'email')->values();

        $this->assertCount(1, $emails);
        $this->assertSame($quote->code, $emails[0]['code']);
        $this->assertSame('sent', $emails[0]['statu']);
        $this->assertStringContainsString('buyer@client.test', $emails[0]['label']);
        $this->assertSame(route('quotes.show', $quote->id), $emails[0]['url']);
    }

    public function test_e_mails_of_a_closed_module_stay_hidden(): void
    {
        $this->user->givePermissionTo('quotes-menu');
        $this->mail(Orders::factory()->create(['companies_id' => $this->company->id]));

        $this->assertSame([], collect($this->timeline())->where('type', 'email')->values()->all());
    }

    public function test_lead_is_named_by_its_subject(): void
    {
        $this->user->givePermissionTo('leads-menu');
        $contact = \App\Models\Companies\CompaniesContacts::factory()->create(['companies_id' => $this->company->id]);
        $address = \App\Models\Companies\CompaniesAddresses::factory()->create(['companies_id' => $this->company->id]);
        Leads::create([
            'companies_id' => $this->company->id, 'companies_contacts_id' => $contact->id, 'companies_addresses_id' => $address->id,
            'source' => 'E-mail', 'subject' => 'Matrices LVD',
        ]);

        $this->assertSame('Matrices LVD', collect($this->timeline())->firstWhere('type', 'lead')['label']);
    }
}
