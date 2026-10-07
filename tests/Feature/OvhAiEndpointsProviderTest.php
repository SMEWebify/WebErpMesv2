<?php

namespace Tests\Feature;

use App\Models\Integrations\AISetting;
use App\Services\AI\AISettingsResolver;
use App\Services\AI\Modules\ERPAssistantModule;
use App\Services\AI\Tools\ERPToolRegistry;
use App\Services\AI\Tools\QuoteQueryTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * OVHcloud AI Endpoints : l'assistant ERP bascule sur l'API compatible OpenAI
 * quand le provider est choisi dans l'admin, et la boucle d'outils traduit
 * correctement les formats Claude ↔ OpenAI.
 */
class OvhAiEndpointsProviderTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1/chat/completions';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function useOvh(?string $key = 'ovh-token'): void
    {
        AISetting::create([
            'provider'        => 'ovh',
            'api_key'         => $key,
            'model'           => 'Mistral-Small-3.2-24B-Instruct-2506',
            'max_tokens'      => 1024,
            'timeout_seconds' => 30,
            'is_active'       => true,
        ]);
        app(AISettingsResolver::class)->forget();
    }

    /** @test */
    public function the_assistant_stays_on_claude_without_a_stored_choice(): void
    {
        $this->assertSame('claude', app(AISettingsResolver::class)->activeProvider());
    }

    /** @test */
    public function the_assistant_runs_the_tool_loop_against_ovh(): void
    {
        $this->useOvh();

        $registry = Mockery::mock(ERPToolRegistry::class);
        $registry->shouldReceive('definitions')->andReturn([QuoteQueryTool::definition()]);
        $registry->shouldReceive('dispatch')
            ->once()
            ->with('search_quotes', ['client' => 'ACME'])
            ->andReturn(['quotes' => [['code' => 'DV-001']]]);
        $this->app->instance(ERPToolRegistry::class, $registry);
        $this->app->forgetInstance(\App\Services\AI\AIGateway::class);
        $this->app->forgetInstance(ERPAssistantModule::class);

        Http::fakeSequence(self::ENDPOINT)
            ->push([
                'model'   => 'Mistral-Small-3.2-24B-Instruct-2506',
                'choices' => [[
                    'finish_reason' => 'tool_calls',
                    'message'       => [
                        'role'       => 'assistant',
                        'content'    => null,
                        'tool_calls' => [[
                            'id'       => 'call_1',
                            'type'     => 'function',
                            'function' => ['name' => 'search_quotes', 'arguments' => '{"client":"ACME"}'],
                        ]],
                    ],
                ]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 10],
            ])
            ->push([
                'model'   => 'Mistral-Small-3.2-24B-Instruct-2506',
                'choices' => [['finish_reason' => 'stop', 'message' => ['role' => 'assistant', 'content' => 'Devis DV-001']]],
                'usage'   => ['prompt_tokens' => 150, 'completion_tokens' => 5],
            ]);

        $response = app(ERPAssistantModule::class)->chat('Devis ACME ?', [], 'fr');

        $this->assertTrue($response->success, (string) $response->error);
        $this->assertSame('Devis DV-001', $response->content);
        $this->assertSame('tool_ovh', $response->provider);
        $this->assertSame(250, $response->inputTokens);

        $sent = Http::recorded()->map(fn ($pair) => $pair[0]);
        $this->assertCount(2, $sent);

        /** @var ClientRequest $first */
        $first = $sent[0];
        $this->assertSame('Bearer ovh-token', $first->header('Authorization')[0]);
        $this->assertSame('system', $first['messages'][0]['role']);
        $this->assertSame('function', $first['tools'][0]['type']);
        $this->assertSame('search_quotes', $first['tools'][0]['function']['name']);
        $this->assertArrayHasKey('parameters', $first['tools'][0]['function']);

        // Le résultat de l'outil repart en message `tool` rattaché à l'appel.
        $last = collect($sent[1]['messages'])->last();
        $this->assertSame('tool', $last['role']);
        $this->assertSame('call_1', $last['tool_call_id']);
        $this->assertStringContainsString('DV-001', $last['content']);
    }

    /** @test */
    public function no_authorization_header_is_sent_in_anonymous_mode(): void
    {
        $this->useOvh(null);

        Http::fake([self::ENDPOINT => Http::response([
            'choices' => [['message' => ['role' => 'assistant', 'content' => 'OK']]],
        ])]);

        $response = app(ERPAssistantModule::class)->chat('Bonjour', [], 'fr');

        $this->assertTrue($response->success);
        Http::assertSent(fn (ClientRequest $r) => ! $r->hasHeader('Authorization'));
    }

    /** @test */
    public function the_settings_page_offers_ovh_and_shows_anonymous_access(): void
    {
        $this->withoutMiddleware();
        // Sans middleware, ShareErrorsFromSession ne partage plus $errors.
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $this->useOvh(null);

        $this->actingAs(\App\Models\User::factory()->create())
            ->get(route('admin.integrations.ai.index'))
            ->assertOk()
            ->assertSee('OVHcloud AI Endpoints')
            ->assertSee('Accès anonyme');
    }

    /** @test */
    public function switching_provider_without_a_new_key_drops_the_previous_one(): void
    {
        $this->withoutMiddleware();

        AISetting::create([
            'provider' => 'claude', 'api_key' => 'sk-ant-secret', 'max_tokens' => 2048,
            'timeout_seconds' => 30, 'is_active' => true,
        ]);

        $this->actingAs(\App\Models\User::factory()->create())
            ->put(route('admin.integrations.ai.update'), [
                'provider' => 'ovh', 'max_tokens' => 2048, 'timeout_seconds' => 30, 'is_active' => 1,
            ])
            ->assertRedirect();

        $setting = AISetting::current();
        $this->assertSame('ovh', $setting->provider);
        $this->assertNull($setting->api_key);
    }
}
