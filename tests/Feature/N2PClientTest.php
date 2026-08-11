<?php

namespace Tests\Feature;

use App\Models\Integrations\IntegrationDelivery;
use App\Models\Integrations\IntegrationEndpoint;
use App\Services\N2P\N2PClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class N2PClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_jobs_success_with_bearer(): void
    {
        Http::fake([
            'https://n2p.test/api/plugin/jobs' => Http::response(['ok' => true], 200),
        ]);

        $endpoint = $this->makeEndpoint([
            'auth_method' => IntegrationEndpoint::AUTH_BEARER,
            'bearer_token' => 'token',
        ]);

        $client = new N2PClient($endpoint);

        $response = $client->pushJobs(['jobs' => []]);

        $this->assertSame(['ok' => true], $response);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://n2p.test/api/plugin/jobs'
                && $request->hasHeader('Authorization', 'Bearer token');
        });

        $this->assertDatabaseHas('integration_deliveries', [
            'integration_endpoint_id' => $endpoint->id,
            'direction' => 'out',
            'event_type' => 'job.pushed',
            'http_status' => 200,
        ]);

        $endpoint->refresh();
        $this->assertNotNull($endpoint->last_success_at);
    }

    public function test_push_jobs_signs_with_hmac_when_required(): void
    {
        Http::fake([
            'https://n2p.test/api/plugin/jobs' => Http::response(['ok' => true], 200),
        ]);

        $endpoint = $this->makeEndpoint([
            'auth_method' => IntegrationEndpoint::AUTH_HMAC,
            'hmac_secret' => 'shared-secret',
            'hmac_header' => 'X-N2P-Signature-256',
            'timestamp_header' => 'X-N2P-Timestamp',
        ]);

        $client = new N2PClient($endpoint);
        $client->pushJobs(['jobs' => []]);

        Http::assertSent(function ($request) {
            $ts = $request->header('X-N2P-Timestamp')[0] ?? null;
            $sig = $request->header('X-N2P-Signature-256')[0] ?? null;
            if ($ts === null || $sig === null) {
                return false;
            }
            $expected = hash_hmac('sha256', $ts . '.' . $request->body(), 'shared-secret');
            return hash_equals($expected, $sig);
        });
    }

    public function test_push_jobs_throws_on_failure_and_marks_delivery(): void
    {
        $this->expectException(RequestException::class);

        Http::fake([
            'https://n2p.test/api/plugin/jobs' => Http::response(['error' => 'fail'], 500),
        ]);

        $endpoint = $this->makeEndpoint([
            'auth_method' => IntegrationEndpoint::AUTH_NONE,
        ]);

        try {
            (new N2PClient($endpoint))->pushJobs(['jobs' => []]);
        } finally {
            $this->assertDatabaseHas('integration_deliveries', [
                'integration_endpoint_id' => $endpoint->id,
                'direction' => 'out',
                'http_status' => 500,
            ]);

            $delivery = IntegrationDelivery::query()
                ->where('integration_endpoint_id', $endpoint->id)
                ->first();
            $this->assertNotNull($delivery->error);
            $this->assertNull($delivery->processed_at);

            $endpoint->refresh();
            $this->assertNotNull($endpoint->last_error_at);
        }
    }

    private function makeEndpoint(array $overrides = []): IntegrationEndpoint
    {
        // La migration seed 2026_08_10_120002 pré-insère un endpoint
        // (n2p, outbound). On updateOrCreate pour ne pas collisionner sur
        // l'index unique (system_code, direction).
        $attributes = array_merge([
            'name' => 'Nest2Prod test',
            'system_code' => 'n2p',
            'direction' => IntegrationEndpoint::DIRECTION_OUTBOUND,
            'url' => 'https://n2p.test',
            'auth_method' => IntegrationEndpoint::AUTH_NONE,
            'verify_ssl' => false,
            'is_active' => true,
        ], $overrides);

        return IntegrationEndpoint::updateOrCreate(
            [
                'system_code' => $attributes['system_code'],
                'direction'   => $attributes['direction'],
            ],
            $attributes,
        );
    }
}
