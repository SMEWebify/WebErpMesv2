<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workflow\CreditNotes;
use App\Support\MobileLauncher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileLauncherTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_menu_entries_by_header(): void
    {
        $groups = MobileLauncher::groups([
            ['text' => 'Tableau de bord', 'href' => '/dashboard', 'icon' => 'fas fa-tachometer-alt', 'icon_color' => 'warning'],
            ['text' => 'Devis', 'href' => '#', 'icon' => 'fas fa-calculator', 'icon_color' => 'teal', 'active' => true, 'submenu' => [
                ['text' => 'Liste', 'href' => '/quotes', 'active' => true],
                ['text' => 'Lignes', 'href' => '/quotes/lines', 'label' => 3, 'label_color' => 'warning'],
            ]],
            ['header' => 'Paramètres'],
            ['text' => 'Tableurs', 'href' => '/spreadsheet', 'icon' => 'nav-icon fas fa-table'],
            ['text' => 'Vide', 'href' => '#', 'submenu' => []],
            ['header' => 'Sans entrée'],
        ]);

        $this->assertCount(2, $groups);
        $this->assertNull($groups[0]['title']);
        $this->assertSame(['Tableau de bord', 'Devis'], array_column($groups[0]['apps'], 'text'));
        $this->assertSame('#f59e0b', $groups[0]['apps'][0]['color']);
        $this->assertCount(2, $groups[0]['apps'][1]['children']);
        $this->assertSame(3, $groups[0]['apps'][1]['children'][1]['label']);

        // Un parent sans enfant ni lien est écarté, nav-icon est propre à la sidebar
        $this->assertSame('Paramètres', $groups[1]['title']);
        $this->assertSame(['Tableurs'], array_column($groups[1]['apps'], 'text'));
        $this->assertSame('fas fa-table', $groups[1]['apps'][0]['icon']);
        $this->assertSame('#64748b', $groups[1]['apps'][0]['color']);

        $this->assertSame('Devis', MobileLauncher::current($groups)['text']);
    }

    public function test_active_entry_ignores_the_locale_prefix_and_prefers_the_most_specific(): void
    {
        $menu = [
            ['text' => 'Commandes', 'href' => 'http://localhost/orders', 'submenu' => [
                ['text' => 'Liste', 'href' => 'http://localhost/orders'],
                ['text' => 'Lignes', 'href' => 'http://localhost/orders/lines'],
            ]],
            ['text' => 'Qualité', 'href' => '#', 'submenu' => [
                ['text' => 'Tableau', 'href' => 'http://localhost/quality'],
                ['text' => 'Actions', 'href' => 'http://localhost/quality/action'],
            ]],
        ];

        $groups = MobileLauncher::groups($menu, 'fr/quality/action/12');
        $this->assertSame('Qualité', MobileLauncher::current($groups)['text']);
        $this->assertSame([false, true], array_column($groups[0]['apps'][1]['children'], 'active'));

        // À égalité avec le lien du parent, c'est le sous-menu qui est marqué
        $groups = MobileLauncher::groups($menu, 'en/orders');
        $this->assertSame('Commandes', MobileLauncher::current($groups)['text']);
        $this->assertSame([true, false], array_column($groups[0]['apps'][0]['children'], 'active'));

        $this->assertNull(MobileLauncher::current(MobileLauncher::groups($menu, 'fr/settings')));
    }

    public function test_launcher_is_rendered_on_admin_pages(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        $this->actingAs(User::factory()->create());

        $creditNote = CreditNotes::factory()->create();

        $response = $this->get(route('credit.notes.show', ['id' => $creditNote->id]));

        $response->assertOk();
        $response->assertSee('id="wem-launcher"', false);
        $response->assertSee('data-wem-launcher-open', false);
    }
}
