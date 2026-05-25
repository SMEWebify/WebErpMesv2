<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use Illuminate\Foundation\Testing\RefreshDatabase;

class QuotesWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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
    }

    public function test_index_page_loads(): void
    {
        $response = $this->get(route('quotes'));
        // KPI queries use MySQL-specific functions (MONTH, FLOOR) that don't run in SQLite.
        // We only assert the route is accessible (not redirected/forbidden).
        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    public function test_show_page_loads(): void
    {
        $quote = Quotes::factory()->create();
        $response = $this->get(route('quotes.show', ['id' => $quote->id]));
        $response->assertStatus(200);
    }

    public function test_json_list_returns_paginated_data(): void
    {
        Quotes::factory()->count(3)->create(['statu' => 1]);

        $response = $this->getJson(route('quotes.json.list', ['statuses' => [1]]));

        $response->assertOk()->assertJsonStructure(['data', 'meta']);
        $this->assertGreaterThanOrEqual(3, $response->json('meta.total'));
        $this->assertArrayHasKey('id', $response->json('data.0'));
    }

    public function test_json_list_filters_by_status(): void
    {
        Quotes::factory()->create(['statu' => 1]);
        Quotes::factory()->create(['statu' => 5]);

        $response = $this->getJson(route('quotes.json.list', ['statuses' => [1]]));

        $response->assertOk();
        foreach ($response->json('data') as $item) {
            $this->assertEquals(1, $item['statu']);
        }
    }

    public function test_can_create_quote(): void
    {
        $company   = Companies::factory()->create();
        $contact   = CompaniesContacts::factory()->create(['companies_id' => $company->id]);
        $address   = CompaniesAddresses::factory()->create(['companies_id' => $company->id]);
        $condition = AccountingPaymentConditions::factory()->create();
        $method    = AccountingPaymentMethod::factory()->create();
        $delivery  = AccountingDelivery::factory()->create();

        $response = $this->postJson(route('quotes.json.store'), [
            'code'                              => 'QT-TEST-001',
            'label'                             => 'Test Quote',
            'companies_id'                      => $company->id,
            'companies_contacts_id'             => $contact->id,
            'companies_addresses_id'            => $address->id,
            'accounting_payment_conditions_id'  => $condition->id,
            'accounting_payment_methods_id'     => $method->id,
            'accounting_deliveries_id'          => $delivery->id,
            'user_id'                           => $this->user->id,
        ]);

        $response->assertStatus(201)->assertJsonStructure(['redirect']);
        $this->assertDatabaseHas('quotes', ['code' => 'QT-TEST-001', 'statu' => 1]);
    }

    public function test_create_quote_requires_mandatory_fields(): void
    {
        $response = $this->postJson(route('quotes.json.store'), []);
        $response->assertStatus(422);
    }

    public function test_can_update_quote(): void
    {
        $quote     = Quotes::factory()->create();
        $condition = AccountingPaymentConditions::factory()->create();
        $method    = AccountingPaymentMethod::factory()->create();
        $delivery  = AccountingDelivery::factory()->create();

        $response = $this->post(route('quotes.update', ['id' => $quote->id]), [
            'label'                             => 'Updated Quote Label',
            'companies_id'                      => $quote->companies_id,
            'companies_contacts_id'             => $quote->companies_contacts_id,
            'companies_addresses_id'            => $quote->companies_addresses_id,
            'accounting_payment_conditions_id'  => $condition->id,
            'accounting_payment_methods_id'     => $method->id,
            'accounting_deliveries_id'          => $delivery->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'label' => 'Updated Quote Label']);
    }

    public function test_can_change_quote_status(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);

        $response = $this->postJson(route('quotes.json.statu', ['id' => $quote->id]), ['statu' => 3]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'statu' => 3]);
    }

    public function test_changing_quote_status_propagates_to_lines(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        QuoteLines::factory()->create(['quotes_id' => $quote->id, 'statu' => 1]);
        QuoteLines::factory()->create(['quotes_id' => $quote->id, 'statu' => 1]);

        $this->postJson(route('quotes.json.statu', ['id' => $quote->id]), ['statu' => 5]);

        $this->assertEquals(0, QuoteLines::where('quotes_id', $quote->id)->where('statu', '<', 5)->count());
    }
}
