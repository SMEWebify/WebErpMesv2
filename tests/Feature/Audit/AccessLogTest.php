<?php

namespace Tests\Feature\Audit;

use App\Models\File;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use App\Services\Audit\AccessLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Journal des consultations (log_name `access`) : une ligne par consultation réussie,
 * dédoublonnée par utilisateur et sujet, rien du tout quand l'option est inactive.
 */
class AccessLogTest extends TestCase
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

        Storage::fake(config('files.disk'));
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();
        Permission::findOrCreate('products-menu');

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('products-menu');
        $this->actingAs($this->user);

        config(['audit.log_reads' => true, 'audit.dedup_minutes' => 10]);
    }

    private function gedFile(): File
    {
        $product = Products::factory()->create([
            'picture' => null, 'drawing_file' => null, 'stl_file' => null, 'svg_file' => null, 'purchased' => 2,
        ]);

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'product',
            'fileable_id'   => $product->id,
            'files'         => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
        ])->assertStatus(201);

        return File::firstOrFail();
    }

    private function accesses()
    {
        return Activity::query()->where('log_name', 'access');
    }

    public function test_viewing_a_ged_file_is_logged_with_user_subject_route_and_ip(): void
    {
        $file = $this->gedFile();

        $this->get(route('files.raw', ['file' => $file->id]))->assertOk();

        $this->assertSame(1, $this->accesses()->count());
        $entry = $this->accesses()->first();
        $this->assertSame($this->user->id, (int) $entry->causer_id);
        $this->assertSame((new File())->getMorphClass(), $entry->subject_type);
        $this->assertSame($file->id, (int) $entry->subject_id);
        $this->assertSame('read', $entry->event);
        $this->assertSame('files.raw', $entry->getExtraProperty('route'));
        $this->assertSame('127.0.0.1', $entry->getExtraProperty('ip'));
    }

    public function test_repeated_views_within_the_window_are_logged_once(): void
    {
        $file = $this->gedFile();

        $this->get(route('files.raw', ['file' => $file->id]))->assertOk();
        $this->get(route('files.download', ['file' => $file->id]))->assertOk();
        $this->get(route('files.raw', ['file' => $file->id]))->assertOk();

        $this->assertSame(1, $this->accesses()->count());

        // Un autre utilisateur sur le même sujet est tracé séparément.
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('files.raw', ['file' => $file->id]))->assertOk();
        $this->assertSame(2, $this->accesses()->count());

        // Fenêtre écoulée : la consultation suivante est de nouveau tracée.
        $this->travel(11)->minutes();
        $this->actingAs($this->user)->get(route('files.raw', ['file' => $file->id]))->assertOk();
        $this->assertSame(3, $this->accesses()->count());
    }

    public function test_nothing_is_written_when_the_option_is_off(): void
    {
        config(['audit.log_reads' => false]);
        $file = $this->gedFile();

        $this->get(route('files.raw', ['file' => $file->id]))->assertOk();
        $this->get(route('files.download', ['file' => $file->id]))->assertOk();

        $this->assertSame(0, $this->accesses()->count());
    }

    public function test_a_refused_or_missing_subject_is_not_logged(): void
    {
        $this->get(route('files.raw', ['file' => 999999]))->assertNotFound();

        $this->assertSame(0, $this->accesses()->count());
    }

    public function test_the_logger_records_a_subject_from_its_id_without_loading_it(): void
    {
        $logger = app(AccessLogger::class);

        $this->assertTrue($logger->record($this->user, 'quote', 42, 'quotes.show', '10.0.0.1'));
        $this->assertFalse($logger->record($this->user, 'quote', 42, 'quotes.show', '10.0.0.1'));
        $this->assertTrue($logger->record($this->user, 'order', 42, 'orders.show', '10.0.0.1'));
        $this->assertFalse($logger->record($this->user, 'unknown', 42, null, null));

        $this->assertSame(2, $this->accesses()->count());
    }

    public function test_every_sensitive_screen_carries_the_middleware(): void
    {
        $expected = [
            'opportunities.show'       => 'audit.access:opportunity,id',
            'quotes.show'              => 'audit.access:quote,id',
            'orders.show'              => 'audit.access:order,id',
            'orders.lines.detail.edit' => 'audit.access:order-line,id',
            'files.raw'                => 'audit.access:file,file',
            'files.download'           => 'audit.access:file,file',
        ];

        foreach ($expected as $name => $middleware) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertContains($middleware, $route->gatherMiddleware(), $name);
        }
    }

    public function test_the_screen_and_export_are_reserved_to_the_permission(): void
    {
        // Créée et donnée au rôle Admin par la migration.
        $this->assertTrue(Permission::where('name', 'audit-access-log')->exists());

        // L'accès au menu Administration ne suffit pas.
        Permission::findOrCreate('your-company-menu');
        $this->user->givePermissionTo('your-company-menu');

        $this->get(route('admin.access-log.index'))->assertForbidden();
        $this->getJson(route('admin.access-log.json.list'))->assertForbidden();
        $this->get(route('admin.access-log.export'))->assertForbidden();
    }

    public function test_the_admin_filters_and_exports_the_log(): void
    {
        $file = $this->gedFile();
        $this->get(route('files.raw', ['file' => $file->id]))->assertOk();
        $other = User::factory()->create(['name' => 'Autre salarié']);
        app(AccessLogger::class)->record($other, 'quote', 7, 'quotes.show', '10.0.0.2');

        Permission::findOrCreate('your-company-menu');
        Permission::findOrCreate('audit-access-log');
        $admin = User::factory()->create();
        $admin->givePermissionTo(['your-company-menu', 'audit-access-log']);
        $this->actingAs($admin);

        $this->get(route('admin.access-log.index'))->assertOk()->assertSee('access-log-app', false);

        $all = $this->getJson(route('admin.access-log.json.list'))->assertOk();
        $this->assertSame(2, $all->json('meta.total'));

        $byUser = $this->getJson(route('admin.access-log.json.list', ['user_id' => $this->user->id]))->assertOk();
        $this->assertSame(1, $byUser->json('meta.total'));
        $this->assertSame('file', $byUser->json('data.0.type'));
        $this->assertSame('plan.pdf', $byUser->json('data.0.subject_label'));

        $byType = $this->getJson(route('admin.access-log.json.list', ['type' => 'quote', 'subject_id' => 7]))->assertOk();
        $this->assertSame(1, $byType->json('meta.total'));

        $future = $this->getJson(route('admin.access-log.json.list', ['from' => now()->addDay()->toDateString()]))->assertOk();
        $this->assertSame(0, $future->json('meta.total'));

        $csv = $this->get(route('admin.access-log.export', ['type' => 'quote']));
        $csv->assertOk();
        $content = $csv->streamedContent();
        $this->assertStringContainsString('Autre salarié', $content);
        $this->assertStringContainsString('10.0.0.2', $content);
        $this->assertStringNotContainsString('plan.pdf', $content);
    }

    public function test_the_shared_history_viewer_does_not_expose_accesses(): void
    {
        app(AccessLogger::class)->record($this->user, 'quote', 7, 'quotes.show', '10.0.0.2');

        $response = $this->getJson(route('admin.logs-viewer.json.list', [
            'startDate' => now()->subDay()->toDateString(),
            'endDate'   => now()->addDay()->toDateString(),
        ]))->assertOk();

        $this->assertSame([], collect($response->json('logs'))->where('description', 'read')->values()->all());
    }
}
