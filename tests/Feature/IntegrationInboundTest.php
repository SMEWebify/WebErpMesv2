<?php

namespace Tests\Feature;

use App\Models\Integrations\IntegrationDelivery;
use App\Models\Integrations\IntegrationEndpoint;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Services\Integrations\Handlers\HandleTaskEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class IntegrationInboundTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-secret-for-inbound';

    private IntegrationEndpoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        IntegrationEndpoint::query()->where('system_code', 'n2p')->delete();

        $this->endpoint = IntegrationEndpoint::create([
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

    public function test_missing_signature_returns_401(): void
    {
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, ['of_code' => 'OF1', 'line_ref' => '1', 'operation_code' => 'X']);

        $response = $this->postJson('/api/integrations/n2p/inbound', json_decode($body, true));

        $response->assertStatus(401);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, ['of_code' => 'OF1', 'line_ref' => '1', 'operation_code' => 'X']);
        $timestamp = time();

        $response = $this->call(
            'POST',
            '/api/integrations/n2p/inbound',
            [], [], [],
            $this->serverHeaders([
                'X-N2P-Timestamp' => (string) $timestamp,
                'X-N2P-Signature-256' => 'deadbeef',
            ]),
            $body
        );

        $response->assertStatus(401);
    }

    public function test_stale_timestamp_returns_401(): void
    {
        $stale = time() - 3600;
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, ['of_code' => 'OF1', 'line_ref' => '1', 'operation_code' => 'X']);
        $sig = hash_hmac('sha256', $stale . '.' . $body, self::SECRET);

        $response = $this->call(
            'POST', '/api/integrations/n2p/inbound', [], [], [],
            $this->serverHeaders([
                'X-N2P-Timestamp' => (string) $stale,
                'X-N2P-Signature-256' => $sig,
            ]),
            $body
        );

        $response->assertStatus(401);
    }

    public function test_unknown_system_returns_404(): void
    {
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, ['of_code' => 'OF1', 'line_ref' => '1', 'operation_code' => 'X']);

        $response = $this->signedPost('unknown', $body);

        $response->assertStatus(404);
    }

    public function test_disabled_endpoint_returns_403(): void
    {
        $this->endpoint->update(['is_active' => false]);
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, ['of_code' => 'OF1', 'line_ref' => '1', 'operation_code' => 'X']);

        $response = $this->signedPost('n2p', $body);

        $response->assertStatus(403);
    }

    public function test_task_started_creates_activity_row(): void
    {
        $task = Task::factory()->create([
            'code' => 'OP-CUT-01',
            'order_lines_id' => 42,
        ]);

        $occurredAt = '2026-08-10T09:30:00Z';
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, [
            'of_code' => 'OF42',
            'line_ref' => '42',
            'operation_code' => 'OP-CUT-01',
        ], $occurredAt);

        $response = $this->signedPost('n2p', $body);

        $response->assertStatus(200)->assertJson(['status' => 'processed']);

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => TaskActivities::TYPE_START,
        ]);

        $activity = TaskActivities::query()->where('task_id', $task->id)->first();
        $this->assertNotNull($activity);
        $this->assertEquals(
            Carbon::parse($occurredAt)->toDateTimeString(),
            Carbon::parse($activity->timestamp)->toDateTimeString(),
            'occurred_at from the event must be persisted as timestamp'
        );
    }

    public function test_task_finished_and_declared_good_map_to_expected_types(): void
    {
        $task = Task::factory()->create([
            'code' => 'OP-BEND-01',
            'order_lines_id' => 100,
        ]);

        $this->signedPost('n2p', $this->buildBody(HandleTaskEvent::EVENT_FINISHED, [
            'of_code' => 'OF100',
            'line_ref' => '100',
            'operation_code' => 'OP-BEND-01',
        ]))->assertStatus(200);

        $this->signedPost('n2p', $this->buildBody(HandleTaskEvent::EVENT_DECLARED_GOOD, [
            'of_code' => 'OF100',
            'line_ref' => '100',
            'operation_code' => 'OP-BEND-01',
            'good_qty' => 5,
        ]))->assertStatus(200);

        $types = TaskActivities::query()->where('task_id', $task->id)->pluck('type')->all();
        $this->assertContains(TaskActivities::TYPE_FINISH, $types);
        $this->assertContains(TaskActivities::TYPE_DECLARE_GOOD, $types);

        $goodRow = TaskActivities::query()
            ->where('task_id', $task->id)
            ->where('type', TaskActivities::TYPE_DECLARE_GOOD)
            ->first();
        $this->assertEquals(5, $goodRow->good_qt);
    }

    public function test_duplicate_event_id_is_idempotent(): void
    {
        $task = Task::factory()->create(['code' => 'OP-1', 'order_lines_id' => 7]);
        $eventId = (string) Str::uuid();
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, [
            'of_code' => 'OF7',
            'line_ref' => '7',
            'operation_code' => 'OP-1',
        ], eventId: $eventId);

        $this->signedPost('n2p', $body)->assertStatus(200);
        $this->signedPost('n2p', $body)->assertStatus(200);

        $this->assertEquals(1, TaskActivities::query()->where('task_id', $task->id)->count());
        $this->assertEquals(1, IntegrationDelivery::query()
            ->where('event_id', $eventId)
            ->where('direction', 'in')
            ->count());
    }

    public function test_task_resolver_falls_back_to_prefix(): void
    {
        $task = Task::factory()->create(['code' => null, 'order_lines_id' => 33]);

        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, [
            'of_code' => 'OF33',
            'line_ref' => '33',
            'operation_code' => 'op-' . $task->id,
        ]);

        $this->signedPost('n2p', $body)->assertStatus(200);

        $this->assertDatabaseHas('task_activities', [
            'task_id' => $task->id,
            'type' => TaskActivities::TYPE_START,
        ]);
    }

    public function test_unknown_event_type_marks_no_handler(): void
    {
        $body = $this->buildBody('task.does_not_exist', ['of_code' => 'OF1', 'line_ref' => '1', 'operation_code' => 'X']);

        $response = $this->signedPost('n2p', $body);

        $response->assertStatus(200)->assertJson(['status' => 'ignored']);
    }

    public function test_unresolvable_task_marks_delivery_failed(): void
    {
        $body = $this->buildBody(HandleTaskEvent::EVENT_STARTED, [
            'of_code' => 'OF99999',
            'line_ref' => '99999',
            'operation_code' => 'nope',
        ]);

        $response = $this->signedPost('n2p', $body);

        $response->assertStatus(500)->assertJson(['status' => 'failed']);

        $this->assertDatabaseHas('integration_deliveries', [
            'integration_endpoint_id' => $this->endpoint->id,
            'direction' => 'in',
            'event_type' => HandleTaskEvent::EVENT_STARTED,
        ]);

        $delivery = IntegrationDelivery::query()
            ->where('integration_endpoint_id', $this->endpoint->id)
            ->first();
        $this->assertNotNull($delivery->error);
        $this->assertNull($delivery->processed_at);
    }

    private function buildBody(string $eventType, array $data = [], ?string $occurredAt = null, ?string $eventId = null): string
    {
        return json_encode([
            'event_id'    => $eventId ?? (string) Str::uuid(),
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
            $this->serverHeaders([
                'X-N2P-Timestamp' => (string) $timestamp,
                'X-N2P-Signature-256' => $signature,
            ]),
            $body
        );
    }

    private function serverHeaders(array $headers): array
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }
        return $server;
    }
}
