<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UiTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiAppearanceTest extends TestCase
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
        config(['theme.palette' => 'nest2prod']);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_user_can_save_a_preset_and_a_primary_colour(): void
    {
        $this->put(route('profile.appearance.update'), ['ui_preset' => 'navy', 'ui_primary' => 'orange'])
            ->assertRedirect();

        $this->user->refresh();
        $this->assertSame('navy', $this->user->ui_preset);
        $this->assertSame('orange', $this->user->ui_primary);
    }

    public function test_empty_values_reset_to_the_default(): void
    {
        $this->user->forceFill(['ui_preset' => 'navy', 'ui_primary' => 'orange'])->save();

        $this->put(route('profile.appearance.update'), ['ui_preset' => '', 'ui_primary' => ''])->assertRedirect();

        $this->user->refresh();
        $this->assertNull($this->user->ui_preset);
        $this->assertNull($this->user->ui_primary);
        $this->assertSame(UiTheme::NEST2PROD, UiTheme::preset($this->user));
    }

    public function test_unknown_values_are_rejected(): void
    {
        $this->put(route('profile.appearance.update'), ['ui_preset' => 'purple-rain', 'ui_primary' => 'lightblue'])
            ->assertSessionHasErrors(['ui_preset', 'ui_primary']);

        $this->assertNull($this->user->refresh()->ui_preset);
    }

    public function test_default_appearance_keeps_the_brand_theme(): void
    {
        $html = $this->get(route('user.profile', ['id' => $this->user->id]))->assertOk()->getContent();

        $this->assertStringContainsString('css/theme-nest2prod.css', $html);
        $this->assertStringNotContainsString('data-lte-primary=', $html);
        $this->assertStringContainsString('wem-sidebar-neutral', $html);
    }

    public function test_chosen_appearance_is_applied_to_the_layout(): void
    {
        $this->user->forceFill(['ui_preset' => 'navy', 'ui_primary' => 'orange'])->save();

        $html = $this->get(route('user.profile', ['id' => $this->user->id]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<aside class="app-sidebar[^"]*text-bg-navy/', $html);
        $this->assertStringContainsString('data-lte-primary="orange"', $html);
        // Le thème de la marque redéfinit les couleurs : il ne doit plus être chargé.
        $this->assertStringNotContainsString('css/theme-nest2prod.css', $html);
        $this->assertStringNotContainsString('wem-sidebar-neutral', $html);
    }

    public function test_coloured_header_gets_dark_navbar_links(): void
    {
        $this->user->forceFill(['ui_preset' => 'navy-sky'])->save();

        $html = $this->get(route('user.profile', ['id' => $this->user->id]))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<nav class="app-header[^"]*text-bg-sky navbar-dark/', $html);
        // Pas de couleur choisie : celle de l'habillage s'applique.
        $this->assertStringContainsString('data-lte-primary="sky"', $html);
    }

    public function test_profile_shows_the_appearance_tab_with_the_current_choice(): void
    {
        $this->user->forceFill(['ui_preset' => 'steel'])->save();

        $this->get(route('user.profile', ['id' => $this->user->id]))
            ->assertSee('id="Appearance"', false)
            ->assertSee('value="steel"', false)
            ->assertSee(__('appearance.presets.gradient-teal'));
    }
}
