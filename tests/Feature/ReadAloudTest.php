<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Files\FileTranslations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * « Lire à voix haute » : synthèse vocale du navigateur, boutons rendus seulement
 * pour l'utilisateur qui a choisi la lecture facilitée (rien ne change pour les autres).
 */
class ReadAloudTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsReader(bool $readable): void
    {
        $this->actingAs(User::factory()->create()->forceFill(['ui_readable' => $readable]));
        config(['ai.providers.claude.api_key' => null]);
    }

    public function test_no_button_without_easier_reading(): void
    {
        $this->actingAsReader(false);

        $this->assertStringNotContainsString('data-read-aloud', View::make('include.read-aloud-button', ['target' => 'message'])->render());
        $this->assertSame('', trim(View::make('include.ai-rewrite-rich', ['target' => 'message'])->render()), 'ni IA ni lecture : rien sous l\'éditeur');
        $this->assertStringNotContainsString('data-read-aloud-button', Blade::render('<x-FormTextareaComment comment="" />'));
    }

    public function test_buttons_are_rendered_with_easier_reading(): void
    {
        $this->actingAsReader(true);

        $button = View::make('include.read-aloud-button', ['target' => 'message'])->render();
        $this->assertStringContainsString('data-target="message"', $button);
        $this->assertStringContainsString(__('read_aloud.read'), $button);
        $this->assertStringContainsString('class="d-none"', $button, 'masqué tant que le navigateur n\'a pas confirmé savoir lire');

        $this->assertStringContainsString('data-read-aloud-button', View::make('include.ai-rewrite-rich', ['target' => 'comment', 'personal' => true])->render());
        $this->assertStringNotContainsString('data-ai-rewrite-rich-button', View::make('include.ai-rewrite-rich', ['target' => 'comment'])->render(), 'sans IA : lecture seule');

        $field = Blade::render('<x-FormTextareaComment comment="Bonjour" />');
        $this->assertStringContainsString('data-read-aloud-scope', $field);
        $this->assertStringContainsString('data-read-aloud-button', $field);
    }

    public function test_the_mail_viewer_gets_its_texts(): void
    {
        $this->actingAsReader(true);

        $texts = FileTranslations::all();
        $this->assertSame(__('read_aloud.read'), $texts['read_aloud'] ?? null);
        $this->assertSame(__('read_aloud.stop'), $texts['read_aloud_stop'] ?? null);
    }
}
