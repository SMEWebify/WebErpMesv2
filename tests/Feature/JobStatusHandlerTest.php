<?php

namespace Tests\Feature;

use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class JobStatusHandlerTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'job-status-secret';

    protected function setUp(): void
    {
        parent::setUp();

        IntegrationEndpoint::query()->where('system_code', 'n2p')->delete();
        IntegrationEndpoint::create([
            'name'                  => 'N2P inbound test',
            'system_code'           => 'n2p',
            'direction'             => IntegrationEndpoint::DIRECTION_INBOUND,
            'auth_method'           => IntegrationEndpoint::AUTH_HMAC,
            'hmac_secret'           => self::SECRET,
            'hmac_header'           => 'X-N2P-Signature-256',
            'timestamp_header'      => 'X-N2P-Timestamp',
            'timestamp_tolerance_s' => 300,
            'is_active'             => true,
            'events'                => [],
            'verify_ssl'            => true,
        ]);
    }

    public function test_in_progress_event_promotes_open_order_to_in_progress(): void
    {
        $order = Orders::factory()->create(['statu' => 1]);
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);

        $this->postJobStatus('OF' . $line->id, 'in_progress')->assertOk();

        $order->refresh();
        $this->assertSame(2, (int) $order->statu);
        $this->assertNotNull($order->n2p_last_status_event_at);
    }

    public function test_completed_event_does_not_auto_deliver_order(): void
    {
        $order = Orders::factory()->create(['statu' => 2]);
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);

        $this->postJobStatus('OF' . $line->id, 'completed')->assertOk();

        $order->refresh();
        // Business decision: single job completion doesn't imply full order delivery.
        $this->assertSame(2, (int) $order->statu);
        $this->assertNotNull($order->n2p_last_status_event_at);
    }

    public function test_on_hold_from_in_progress_sets_stopped(): void
    {
        $order = Orders::factory()->create(['statu' => 2]);
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);

        $this->postJobStatus('OF' . $line->id, 'on_hold')->assertOk();

        $order->refresh();
        $this->assertSame(5, (int) $order->statu);
    }

    public function test_stale_event_is_ignored(): void
    {
        $order = Orders::factory()->create([
            'statu' => 1,
            'n2p_last_status_event_at' => now(),
        ]);
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);

        // Event with older occurred_at than what's already applied
        $body = $this->buildBody('job.status_changed', [
            'of_code' => 'OF' . $line->id,
            'new_status' => 'in_progress',
        ], Carbon::now()->subHour()->toIso8601String());

        $this->signedPost('n2p', $body)->assertOk();

        $order->refresh();
        // Still OPEN — stale event skipped
        $this->assertSame(1, (int) $order->statu);
    }

    public function test_already_delivered_order_is_not_demoted(): void
    {
        $order = Orders::factory()->create(['statu' => 3]);
        $line = OrderLines::factory()->create(['orders_id' => $order->id]);

        $this->postJobStatus('OF' . $line->id, 'in_progress')->assertOk();

        $order->refresh();
        // Anti-regression semantic: only OPEN → IN_PROGRESS is allowed
        $this->assertSame(3, (int) $order->statu);
    }

    private function postJobStatus(string $ofCode, string $newStatus): TestResponse
    {
        return $this->signedPost('n2p', $this->buildBody('job.status_changed', [
            'of_code'    => $ofCode,
            'new_status' => $newStatus,
        ]));
    }

    private function buildBody(string $eventType, array $data = [], ?string $occurredAt = null): string
    {
        return json_encode([
            'event_id'    => (string) Str::uuid(),
            'event_type'  => $eventType,
            'occurred_at' => $occurredAt ?? Carbon::now()->toIso8601String(),
            'source'      => 'n2p',
            'data'        => $data,
        ]);
    }

    private function signedPost(string $systemCode, string $body): TestResponse
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, self::SECRET);

        return $this->call(
            'POST',
            '/api/integrations/' . $systemCode . '/inbound',
            [], [], [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_N2P_TIMESTAMP' => (string) $timestamp,
                'HTTP_X_N2P_SIGNATURE_256' => $signature,
            ],
            $body
        );
    }
}
