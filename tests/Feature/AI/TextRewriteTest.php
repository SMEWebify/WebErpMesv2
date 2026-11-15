<?php

namespace Tests\Feature\AI;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * « Réécrire sans fautes » : renvoie une proposition, n'enregistre rien.
 */
class TextRewriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        config([
            'ai.providers.claude.api_key' => 'test-key',
            'ai.providers.claude.default_model' => 'claude-haiku-4-5-20251001',
        ]);
    }

    /** @test */
    public function it_returns_the_corrected_text()
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => "« Garde-corps en acier S235, main courante ronde Ø42. »"]],
        ])]);

        $this->postJson(route('ai.rewrite'), ['text' => 'gardecorp en acié S235, main courante ronde Ø42'])
            ->assertOk()
            ->assertJsonPath('text', 'Garde-corps en acier S235, main courante ronde Ø42.');

        // Le texte est encadré pour ne jamais être pris pour une consigne.
        Http::assertSent(fn (HttpRequest $request) => str_contains($request['messages'][0]['content'], '<texte>')
            && str_contains($request['system'], 'Ne modifie jamais les nombres'));
    }

    /** @test */
    public function it_reads_an_openai_compatible_provider()
    {
        \App\Models\Integrations\AISetting::create([
            'provider' => 'ovh', 'base_url' => 'https://ovh.test/v1', 'model' => 'Mistral', 'is_active' => true,
        ]);
        app(\App\Services\AI\AISettingsResolver::class)->forget();
        Http::fake(['ovh.test/*' => Http::response(['choices' => [['message' => ['content' => '<texte>Pose comprise.</texte>']]]])]);

        $this->postJson(route('ai.rewrite'), ['text' => 'pose comprize'])
            ->assertOk()
            ->assertJsonPath('text', 'Pose comprise.');
    }

    /** @test */
    public function an_empty_text_is_refused_without_calling_the_ai()
    {
        Http::fake();

        $this->postJson(route('ai.rewrite'), ['text' => ''])->assertStatus(422);

        Http::assertNothingSent();
    }

    /** @test */
    public function a_failure_is_reported()
    {
        Http::fake(['api.anthropic.com/*' => Http::response([], 500)]);

        $this->postJson(route('ai.rewrite'), ['text' => 'pose comprize'])
            ->assertStatus(502)
            ->assertJsonPath('message', __('ai_text.failed'));
    }

    /** @test */
    public function the_comment_field_offers_the_rewrite_only_when_an_ai_is_configured()
    {
        $html = (string) $this->blade('<x-FormTextareaComment comment="Bonjour" :rewrite="true" :max-length="255" />');
        $this->assertStringContainsString('data-ai-rewrite-button', $html);
        $this->assertStringContainsString('data-max="255"', $html);

        $plain = (string) $this->blade('<x-FormTextareaComment comment="Bonjour" />');
        $this->assertStringNotContainsString('data-ai-rewrite', $plain);

        config(['ai.providers.claude.api_key' => null]);
        $without = (string) $this->blade('<x-FormTextareaComment comment="Bonjour" :rewrite="true" />');
        $this->assertStringNotContainsString('data-ai-rewrite', $without);
    }
}
