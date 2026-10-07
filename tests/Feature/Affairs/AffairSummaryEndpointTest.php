<?php

namespace Tests\Feature\Affairs;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Companies\CompaniesContacts;
use App\Models\User;
use App\Models\Workflow\Opportunities;
use App\Models\Workflow\Quotes;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Accès aux endpoints de synthèse et d'historique d'affaire : mêmes conditions
 * que la fiche opportunité, sections filtrées par les permissions de menu.
 */
class AffairSummaryEndpointTest extends TestCase
{
    private Opportunities $opportunity;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);

        $company = Companies::factory()->create();
        $this->opportunity = Opportunities::factory()->create([
            'companies_id'           => $company->id,
            'companies_contacts_id'  => CompaniesContacts::factory()->create(['companies_id' => $company->id])->id,
            'companies_addresses_id' => CompaniesAddresses::factory()->create(['companies_id' => $company->id])->id,
            'leads_id'               => null,
        ]);
        Quotes::factory()->create([
            'opportunities_id' => $this->opportunity->id,
            'companies_id'     => $company->id,
            'statu'            => 1,
        ]);
    }

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        return $user;
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson(route('opportunities.json.summary', $this->opportunity->id))->assertUnauthorized();
        $this->getJson(route('opportunities.json.timeline', $this->opportunity->id))->assertUnauthorized();
    }

    public function test_summary_returns_the_affair_state(): void
    {
        $this->actingAs($this->userWith(['quotes-menu', 'orders-menu', 'purchases-menu', 'invoices-menu', 'deliverys-menu']));

        $this->getJson(route('opportunities.json.summary', $this->opportunity->id))
            ->assertOk()
            ->assertJsonPath('stage', 'quote')
            ->assertJsonPath('next_action.code', 'send_quote')
            ->assertJsonCount(1, 'sections.quotes')
            ->assertJsonStructure(['stage', 'path', 'blockers', 'next_action', 'totals', 'counts', 'sections' => ['quotes', 'orders', 'purchases', 'invoices', 'visits']]);

        $this->getJson(route('opportunities.json.timeline', $this->opportunity->id))
            ->assertOk()
            ->assertJsonFragment(['type' => 'quote']);
    }

    public function test_sections_without_the_menu_permission_are_hidden(): void
    {
        $this->actingAs($this->userWith([]));

        $response = $this->getJson(route('opportunities.json.summary', $this->opportunity->id))->assertOk();
        $this->assertSame(['visits'], array_keys($response->json('sections')));
        $this->assertNull($response->json('totals.quoted'));
        $this->assertNull($response->json('next_action'));

        $types = array_column($this->getJson(route('opportunities.json.timeline', $this->opportunity->id))->json('data'), 'type');
        $this->assertNotContains('quote', $types);
        $this->assertContains('opportunity', $types);
    }

    public function test_opportunity_page_opens_on_the_summary_tab(): void
    {
        $this->withoutVite();
        $this->actingAs($this->userWith([]));

        $this->get(route('opportunities.show', $this->opportunity->id))
            ->assertOk()
            ->assertSee('data-react="affair-summary"', false)
            ->assertSee('data-react="affair-timeline"', false)
            ->assertSee('<div class="tab-pane active" id="Summary">', false);
    }

    public function test_unknown_opportunity_is_not_found(): void
    {
        $this->actingAs($this->userWith([]));

        $this->getJson(route('opportunities.json.summary', 999999))->assertNotFound();
    }
}
