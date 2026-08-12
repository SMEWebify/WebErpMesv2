<?php

namespace Tests\Feature;

use App\Events\InvoiceStatusChanged;
use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Customer\Customer;
use App\Models\Integrations\PdpInvoiceSubmission;
use App\Models\Integrations\QontoClientMapping;
use App\Models\Integrations\QontoConnection;
use App\Models\Integrations\QontoSyncReview;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\OrderLines;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QontoIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_reports_feature_disabled_when_missing_credentials(): void
    {
        config()->set('services.qonto.client_id', null);
        config()->set('services.qonto.client_secret', null);

        $this->authenticateApiUser();

        $response = $this->getJson('/api/integrations/qonto/status');

        $response->assertOk()->assertJson([
            'feature_enabled' => false,
            'connected' => false,
            'import_bidirectionnel' => false,
        ]);
        $this->assertNull($response->json('last_sync_at'));
    }

    public function test_status_reports_connection_metadata_when_connected(): void
    {
        config()->set('services.qonto.client_id', 'qonto-client-id');
        config()->set('services.qonto.client_secret', 'qonto-client-secret');

        $user = $this->authenticateApiUser();
        $lastSyncAt = now()->subHour()->startOfSecond();

        QontoConnection::create([
            'tenant_id' => $user->id,
            'access_token' => Crypt::encryptString('token'),
            'refresh_token' => Crypt::encryptString('refresh'),
            'access_token_expires_at' => now()->addHour(),
            'import_bidirectionnel' => true,
            'last_sync_at' => $lastSyncAt,
            'scope' => 'offline_access client.read client.write',
        ]);

        $response = $this->getJson('/api/integrations/qonto/status');

        $response->assertOk()->assertJson([
            'feature_enabled' => true,
            'connected' => true,
            'import_bidirectionnel' => true,
            'scope' => 'offline_access client.read client.write',
        ]);
        $this->assertSame($lastSyncAt->toISOString(), $response->json('last_sync_at'));
    }

    public function test_connect_returns_authorization_url_and_state(): void
    {
        config()->set('services.qonto.client_id', 'qonto-client-id');

        $user = $this->authenticateApiUser();

        $response = $this->getJson('/api/integrations/qonto/connect');

        $response->assertOk()->assertJsonStructure(['authorization_url', 'state']);
        $state = $response->json('state');
        $this->assertSame($user->id, Cache::get("qonto.oauth.state.{$state}"));
    }

    public function test_sync_creates_review_when_matching_is_ambiguous(): void
    {
        config()->set('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2');

        $user = $this->authenticateApiUser();

        $company = Companies::create([
            'label' => 'Acme Test',
            'user_id' => $user->id,
            'client_type' => '1',
            'statu_customer' => 1,
            'active' => 1,
        ]);

        CompaniesAddresses::create([
            'companies_id' => $company->id,
            'label' => 'HQ',
            'adress' => '1 Rue de test',
            'zipcode' => '75001',
            'city' => 'Paris',
            'default' => 1,
        ]);

        $contact = Customer::create([
            'companies_id' => $company->id,
            'name' => 'Acme Contact',
            'mail' => 'contact@acme.test',
            'is_customer_portal_user' => true,
        ]);

        QontoConnection::create([
            'tenant_id' => $user->id,
            'access_token' => Crypt::encryptString('token'),
            'refresh_token' => Crypt::encryptString('refresh'),
            'access_token_expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'https://thirdparty.qonto.com/v2/clients*' => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'clients' => [
                            ['id' => 'q1', 'name' => 'Acme Test', 'postal_code' => '75001', 'city' => 'Paris'],
                            ['id' => 'q2', 'name' => 'Acme Test', 'postal_code' => '75001', 'city' => 'Paris'],
                        ],
                    ], 200);
                }
                return Http::response(['client' => ['id' => 'created-1']], 201);
            },
        ]);

        $response = $this->postJson('/api/integrations/qonto/clients/sync');

        $response->assertOk();
        $this->assertDatabaseHas('qonto_client_mappings', [
            'tenant_id' => $user->id,
            'wem_client_id' => $company->id,
            'sync_status' => 'review_required',
        ]);
        $this->assertDatabaseCount('qonto_sync_reviews', 1);
        $this->assertDatabaseMissing('qonto_client_mappings', [
            'tenant_id' => $user->id,
            'wem_client_id' => $company->id,
            'sync_status' => 'created_in_qonto',
        ]);
    }


    public function test_settings_persist_bidirectional_import_flag(): void
    {
        $user = $this->authenticateApiUser();

        QontoConnection::create([
            'tenant_id' => $user->id,
            'access_token' => Crypt::encryptString('token'),
            'refresh_token' => Crypt::encryptString('refresh'),
            'access_token_expires_at' => now()->addHour(),
            'import_bidirectionnel' => false,
        ]);

        $response = $this->postJson('/api/integrations/qonto/settings', [
            'import_bidirectionnel' => true,
        ]);

        $response->assertOk()->assertJson(['import_bidirectionnel' => true]);
        $this->assertDatabaseHas('qonto_connections', [
            'tenant_id' => $user->id,
            'import_bidirectionnel' => 1,
        ]);
    }

    public function test_resolve_links_review_to_mapping(): void
    {
        $user = $this->authenticateApiUser();

        $review = QontoSyncReview::create([
            'tenant_id' => $user->id,
            'wem_client_id' => 100,
            'qonto_client_id' => 'qonto-123',
            'matching_score' => 65,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/integrations/qonto/clients/'.$review->id.'/resolve', [
            'action' => 'link',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('qonto_client_mappings', [
            'tenant_id' => $user->id,
            'wem_client_id' => 100,
            'qonto_client_id' => 'qonto-123',
            'sync_status' => 'linked',
        ]);
    }

    public function test_submit_invoice_posts_structured_payload_to_client_invoices(): void
    {
        config()->set('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2');

        $user    = $this->authenticateApiUser();
        $invoice = $this->makeInvoiceWithSingleLine($user->id);

        $this->connectQonto($user->id);
        QontoClientMapping::create([
            'tenant_id'       => $user->id,
            'wem_client_id'   => $invoice->companies_id,
            'qonto_client_id' => 'qonto-client-1',
            'sync_status'     => 'linked',
            'matching_score'  => 100,
        ]);

        Http::fake([
            'https://thirdparty.qonto.com/v2/client_invoices' => Http::response([
                'client_invoice' => ['id' => 'qi-1', 'status' => 'unpaid'],
            ], 201),
        ]);

        $this->postJson("/api/integrations/qonto/invoices/{$invoice->id}/submit")->assertOk();

        Http::assertSent(function (ClientRequest $request) {
            $body = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://thirdparty.qonto.com/v2/client_invoices'
                && $body['client_id'] === 'qonto-client-1'
                && $body['number'] === 'FA-2026-001'
                && $body['due_date'] === '2026-09-30'
                && $body['status'] === 'unpaid'
                && $body['payment_methods']['iban'] === 'FR7616798000010000123456789'
                // Prix bruts + remise séparée, TVA en ratio : format attendu par Qonto.
                && $body['items'][0]['quantity'] === '4.0000'
                && $body['items'][0]['unit_price'] === ['value' => '125.50', 'currency' => 'EUR']
                && $body['items'][0]['vat_rate'] === '0.2000'
                && $body['items'][0]['discount'] === ['type' => 'percentage', 'value' => '10.00'];
        });

        $this->assertDatabaseHas('pdp_invoice_submissions', [
            'invoice_id'       => $invoice->id,
            'provider'         => 'qonto',
            'external_id'      => 'qi-1',
            'lifecycle_status' => 'submitted',
        ]);
    }

    public function test_submit_invoice_fails_cleanly_when_client_is_not_mapped(): void
    {
        config()->set('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2');

        $user    = $this->authenticateApiUser();
        $invoice = $this->makeInvoiceWithSingleLine($user->id);

        $this->connectQonto($user->id);
        Http::fake();

        $this->postJson("/api/integrations/qonto/invoices/{$invoice->id}/submit")
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'Aucun client Qonto'));

        Http::assertNothingSent();
        $this->assertDatabaseCount('pdp_invoice_submissions', 0);
    }

    public function test_poll_maps_paid_status_to_wem_paid_status(): void
    {
        config()->set('services.qonto.api_base_url', 'https://thirdparty.qonto.com/v2');
        Event::fake([InvoiceStatusChanged::class]);

        $user    = $this->authenticateApiUser();
        $invoice = $this->makeInvoiceWithSingleLine($user->id);

        $this->connectQonto($user->id);
        PdpInvoiceSubmission::create([
            'tenant_id'        => $user->id,
            'invoice_id'       => $invoice->id,
            'provider'         => 'qonto',
            'external_id'      => 'qi-1',
            'lifecycle_status' => 'submitted',
            'submitted_at'     => now(),
        ]);

        Http::fake([
            'https://thirdparty.qonto.com/v2/client_invoices/qi-1' => Http::response([
                'client_invoice' => ['id' => 'qi-1', 'status' => 'paid'],
            ], 200),
        ]);

        $this->postJson("/api/integrations/qonto/invoices/{$invoice->id}/poll")->assertOk();

        $this->assertDatabaseHas('pdp_invoice_submissions', [
            'invoice_id'       => $invoice->id,
            'lifecycle_status' => 'paid',
        ]);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'statu' => 5]);
    }

    private function connectQonto(int $tenantId): QontoConnection
    {
        return QontoConnection::create([
            'tenant_id'               => $tenantId,
            'access_token'            => Crypt::encryptString('token'),
            'refresh_token'           => Crypt::encryptString('refresh'),
            'access_token_expires_at' => now()->addHour(),
            // Renseigné ici pour éviter l'appel de découverte GET /organization.
            'iban'                    => 'FR7616798000010000123456789',
        ]);
    }

    private function makeInvoiceWithSingleLine(int $tenantId): Invoices
    {
        $invoice = Invoices::factory()->create([
            'user_id'      => $tenantId,
            'code'         => 'FA-2026-001',
            'invoice_type' => 1,
            'statu'        => 1,
            'due_date'     => '2026-09-30',
        ]);

        $orderLine = OrderLines::factory()->create([
            'code'  => 'PART-1',
            'label' => 'Tôle pliée 3mm',
        ]);

        InvoiceLines::create([
            'invoices_id'    => $invoice->id,
            'order_line_id'  => $orderLine->id,
            'ordre'          => 10,
            'qty'            => 4,
            'unit_price'     => 125.50,
            'discount'       => 10,
            'vat_rate'       => 20,
            'invoice_status' => 1,
        ]);

        return $invoice->fresh();
    }
}
