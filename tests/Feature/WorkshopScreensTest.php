<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Products\StockMove;
use App\Services\WorkshopReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkshopScreensTest extends TestCase
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
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** L'accueil tablette : 4 tuiles plein écran, chacune entièrement cliquable. */
    public function test_home_shows_four_full_screen_tiles(): void
    {
        $response = $this->get(route('workshop'))->assertOk();

        foreach ([
            route('workshop.task.lines'),
            route('workshop.task.statu'),
            route('workshop.stock.detail'),
            route('workshop.reports'),
        ] as $url) {
            $response->assertSee('class="ws-tile ws-tile--', false);
            $response->assertSee('href="' . $url . '"', false);
        }

        $this->assertSame(4, substr_count($response->getContent(), 'class="ws-tile ws-tile--'));
    }

    /** La carte "Stocks" de l'accueil atelier pointe ici, sans id : c'était un 404. */
    public function test_stock_detail_without_id_shows_the_scan_screen(): void
    {
        $this->get(route('workshop.stock.detail'))
            ->assertOk()
            ->assertSee('Scanner une étiquette de stock');
    }

    public function test_stock_scan_redirects_to_the_matching_move(): void
    {
        $move = StockMove::create([
            'user_id'                    => $this->user->id,
            'qty'                        => 5,
            'typ_move'                   => 5,
            // Emplacement fictif : la chaîne de factories produit/unité/famille
            // n'apporte rien à la résolution du scan, seule la colonne compte.
            'stock_location_products_id' => 1,
        ]);

        $this->get(route('workshop.stock.scan', ['code' => $move->id]))
            ->assertRedirect(route('workshop.stock.detail.id', ['id' => $move->id]));
    }

    public function test_stock_scan_resolves_a_tracability_serial(): void
    {
        $move = StockMove::create([
            'user_id'                    => $this->user->id,
            'qty'                        => 2,
            'typ_move'                   => 5,
            'tracability'                => 'LOT-2026-42',
            // Emplacement fictif : la chaîne de factories produit/unité/famille
            // n'apporte rien à la résolution du scan, seule la colonne compte.
            'stock_location_products_id' => 1,
        ]);

        $this->get(route('workshop.stock.scan', ['code' => 'LOT-2026-42']))
            ->assertRedirect(route('workshop.stock.detail.id', ['id' => $move->id]));
    }

    public function test_unknown_scan_code_returns_the_scan_screen_not_a_404(): void
    {
        $this->get(route('workshop.stock.scan', ['code' => 'INCONNU-999']))
            ->assertOk()
            ->assertSee('INCONNU-999', false);
    }

    public function test_scan_screen_lists_the_last_moves(): void
    {
        $move = StockMove::create([
            'user_id'                    => $this->user->id,
            'qty'                        => 7,
            'typ_move'                   => 5,
            'tracability'                => 'LOT-2026-77',
            'stock_location_products_id' => 1,
        ]);

        $this->get(route('workshop.stock.detail'))
            ->assertOk()
            ->assertSee('Derniers mouvements')
            ->assertSee('LOT-2026-77')
            ->assertSee(route('workshop.stock.detail.id', ['id' => $move->id]), false);
    }

    public function test_reports_page_loads(): void
    {
        $this->get(route('workshop.reports'))->assertOk();
    }

    public function test_reports_json_exposes_the_workshop_blocks(): void
    {
        $this->getJson(route('workshop.reports.json', ['period' => '7d']))
            ->assertOk()
            ->assertJsonStructure([
                'period'  => ['key', 'label', 'from', 'to'],
                'kpi'     => ['declared_hours', 'good_qty', 'bad_qty', 'scrap_rate', 'finished_tasks', 'late_tasks'],
                'per_day', 'per_resource', 'per_user', 'per_service', 'in_progress',
                'andon'   => ['total', 'open', 'by_type'],
            ])
            ->assertJsonPath('period.key', '7d');
    }

    /**
     * Le réalisé doit être borné à la période : une session appariée dans la
     * fenêtre compte, une session ouverte n'ajoute pas d'heures mais apparaît
     * comme "en cours".
     */
    public function test_report_pairs_declarations_within_the_period(): void
    {
        $closed = Task::factory()->create();
        $open   = Task::factory()->create();

        TaskActivities::create([
            'task_id'   => $closed->id,
            'user_id'   => $this->user->id,
            'type'      => TaskActivities::TYPE_START,
            'timestamp' => now()->subHours(3),
        ]);
        TaskActivities::create([
            'task_id'   => $closed->id,
            'user_id'   => $this->user->id,
            'type'      => TaskActivities::TYPE_END,
            'timestamp' => now()->subHours(1),
        ]);
        TaskActivities::create([
            'task_id'   => $closed->id,
            'user_id'   => $this->user->id,
            'type'      => TaskActivities::TYPE_DECLARE_GOOD,
            'timestamp' => now()->subHours(1),
            'good_qt'   => 18,
        ]);
        TaskActivities::create([
            'task_id'   => $closed->id,
            'user_id'   => $this->user->id,
            'type'      => TaskActivities::TYPE_DECLARE_BAD,
            'timestamp' => now()->subHours(1),
            'bad_qt'    => 2,
        ]);
        TaskActivities::create([
            'task_id'   => $open->id,
            'user_id'   => $this->user->id,
            'type'      => TaskActivities::TYPE_START,
            'timestamp' => now()->subMinutes(30),
        ]);

        $report = app(WorkshopReportService::class)->build('today');

        $this->assertSame(2.0, $report['kpi']['declared_hours']);
        $this->assertSame(1, $report['kpi']['sessions']);
        $this->assertSame(18, $report['kpi']['good_qty']);
        $this->assertSame(2, $report['kpi']['bad_qty']);
        $this->assertSame(10.0, $report['kpi']['scrap_rate']);
        $this->assertSame([$open->id], array_column($report['in_progress'], 'task_id'));
        $this->assertSame(2.0, $report['per_user'][0]['hours']);
        $this->assertSame(18, $report['per_user'][0]['good']);
    }
}
