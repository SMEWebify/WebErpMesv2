<?php

namespace Tests\Feature\AI;

use App\Models\Integrations\AISetting;
use App\Models\User;
use App\Services\AI\AISettingsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * « Réécrire sans fautes » sous un éditeur riche : seuls les morceaux de texte
 * partent chez le provider et sont remplacés un à un ; balises, liens, images et
 * variables {…} restent tels quels. Rien n'est enregistré.
 */
class RichTextRewriteTest extends TestCase
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

    private function fakeSegments(array $segments): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => json_encode(['segments' => $segments], JSON_UNESCAPED_UNICODE)]],
        ])]);
    }

    public function test_text_is_corrected_piece_by_piece_and_the_formatting_is_kept(): void
    {
        $this->fakeSegments(['Bonjour {contact},', 'ci-joint', 'notre devis', '.', 'Cordialement']);

        $response = $this->postJson(route('ai.rewrite'), [
            'html' => '<p>Bonjour {contact},</p><p>ci-joins <strong>notre devie</strong>.</p><p><a href="https://exemple.test">Cordialemant</a></p>',
        ])->assertOk();

        $this->assertTrue($response->json('changed'));
        $html = $response->json('html');
        $this->assertStringContainsString('<p>ci-joint <strong>notre devis</strong>.</p>', $html, 'espaces et balises gardés');
        $this->assertStringContainsString('<a href="https://exemple.test">Cordialement</a>', $html);
        $this->assertStringContainsString('{contact}', $html);

        // Seuls les morceaux de texte partent : ni balise ni adresse du lien.
        Http::assertSent(function (HttpRequest $request) {
            $content = $request['messages'][0]['content'];

            return str_contains($content, '"segments"') && ! str_contains($content, '<strong>') && ! str_contains($content, 'exemple.test');
        });
    }

    public function test_a_piece_whose_variables_change_is_kept_as_it_was(): void
    {
        $this->fakeSegments(['Bonjour Paul,', 'Merci.']);

        $html = $this->postJson(route('ai.rewrite'), ['html' => '<p>Bonjour {contact},</p><p>Merçi.</p>'])
            ->assertOk()->json('html');

        $this->assertStringContainsString('<p>Bonjour {contact},</p>', $html);
        $this->assertStringContainsString('<p>Merci.</p>', $html);
    }

    public function test_an_answer_with_another_number_of_pieces_is_refused(): void
    {
        $this->fakeSegments(['Bonjour']);

        $this->postJson(route('ai.rewrite'), ['html' => '<p>Bonjour</p><p>Merçi</p>'])
            ->assertStatus(502)
            ->assertJsonPath('message', __('ai_text.failed'));
    }

    public function test_nothing_changed_is_reported(): void
    {
        $this->fakeSegments(['Merci.']);

        $this->postJson(route('ai.rewrite'), ['html' => '<p>Merci.</p>'])
            ->assertOk()
            ->assertJsonPath('changed', false);
    }

    public function test_an_empty_editor_is_refused_without_calling_the_ai(): void
    {
        Http::fake();

        $this->postJson(route('ai.rewrite'), ['html' => '<p><br></p>'])->assertStatus(502)
            ->assertJsonPath('message', __('ai_text.empty'));

        Http::assertNothingSent();
    }

    public function test_a_script_in_the_answer_never_comes_back(): void
    {
        $this->fakeSegments(['<script>alert(1)</script>Merci.']);

        $html = $this->postJson(route('ai.rewrite'), ['html' => '<p>Merçi.</p>'])->assertOk()->json('html');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_the_button_citing_third_parties_needs_the_personal_data_permission(): void
    {
        $render = fn (bool $personal) => View::make('include.ai-rewrite-rich', ['target' => 'message', 'personal' => $personal])->render();

        $this->assertStringNotContainsString('data-ai-rewrite-rich', $render(true), 'e-mail : pas sans autorisation');
        $this->assertStringContainsString('data-ai-rewrite-rich', $render(false), 'article : toujours proposé');

        $setting = AISetting::create(['provider' => 'claude', 'api_key' => 'k', 'max_tokens' => 2048, 'timeout_seconds' => 30, 'is_active' => true]);
        $setting->allow_personal_data = true;
        $setting->save();
        app(AISettingsResolver::class)->forget();

        $this->assertStringContainsString('data-ai-rewrite-rich', $render(true));
        $this->assertStringContainsString('data-target="message"', $render(true));
    }

    public function test_without_ai_provider_no_button_is_shown(): void
    {
        config(['ai.providers.claude.api_key' => null]);

        $this->assertStringNotContainsString('data-ai-rewrite-rich',
            View::make('include.ai-rewrite-rich', ['target' => 'comment', 'personal' => false])->render());
    }
}
