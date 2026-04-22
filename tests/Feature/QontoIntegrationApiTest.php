<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Customer\Customer;
use App\Models\Integrations\QontoClientMapping;
use App\Models\Integrations\QontoConnection;
use App\Models\Integrations\QontoSyncReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
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
        ]);

        QontoConnection::create([
            'tenant_id' => $user->id,
            'access_token' => Crypt::encryptString('token'),
            'refresh_token' => Crypt::encryptString('refresh'),
            'access_token_expires_at' => now()->addHour(),
        ]);

        Http::fake([
            'https://thirdparty.qonto.com/v2/clients' => Http::response([
                'clients' => [
                    ['id' => 'q1', 'name' => 'Acme Test', 'postal_code' => '75001', 'city' => 'Paris'],
                    ['id' => 'q2', 'name' => 'Acme Test', 'postal_code' => '75001', 'city' => 'Paris'],
                ],
            ], 200),
            'https://thirdparty.qonto.com/v2/clients*' => Http::response(['client' => ['id' => 'created-1']], 201),
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
}
