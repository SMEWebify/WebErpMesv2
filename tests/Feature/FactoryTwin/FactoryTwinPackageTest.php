<?php

namespace Tests\Feature\FactoryTwin;

use App\Models\Planning\Status;
use App\Models\User;
use App\Providers\FactoryTwinServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Chargement du package commercial factory-twin depuis FACTORY_TWIN_PATH.
 * Ignoré quand le package n'est pas à côté de l'ERP (installation open source, CI).
 */
class FactoryTwinPackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_the_package_the_page_presents_the_commercial_version(): void
    {
        if (FactoryTwinServiceProvider::available()) {
            $this->markTestSkipped('Package chargé par le .env de ce poste.');
        }
        $this->withoutMiddleware([\App\Http\Middleware\CheckUserRole::class, \App\Http\Middleware\CheckFactory::class]);
        Status::create(['title' => 'Open', 'order' => 1]);

        $this->actingAs(User::factory()->create())
            ->get(route('production.digital-twin'))
            ->assertOk()
            ->assertSee('nest2prod.com');
    }

    public function test_the_package_draws_every_equipment_type_of_the_erp(): void
    {
        $models = base_path('../factory-twin/resources/js/models.js');
        if (! is_file($models)) {
            $this->markTestSkipped('Package factory-twin absent.');
        }

        preg_match('/KIND_BUILDERS = \{(.*?)\};/s', file_get_contents($models), $block);
        preg_match_all('/^\s*(\w+):/m', $block[1] ?? '', $keys);

        $this->assertEqualsCanonicalizing(
            array_map(fn (\App\Enums\EquipmentKind $kind) => $kind->value, \App\Enums\EquipmentKind::cases()),
            $keys[1],
            'App\Enums\EquipmentKind et KIND_BUILDERS (factory-twin) doivent lister les mêmes types.'
        );
    }

    public function test_the_package_is_loaded_from_its_folder_and_served_with_erp_data(): void
    {
        $path = base_path('../factory-twin');
        if (! is_file($path . '/src/FactoryTwinServiceProvider.php')) {
            $this->markTestSkipped('Package factory-twin absent.');
        }

        config(['services.factory_twin.path' => '../factory-twin', 'services.factory_twin.cache_seconds' => 0]);
        $this->app->register(FactoryTwinServiceProvider::class, true);
        $this->assertTrue(FactoryTwinServiceProvider::available());
        // La base de test a été migrée avant le chargement du package.
        $this->artisan('migrate', ['--path' => realpath($path . '/database/migrations'), '--realpath' => true])->assertSuccessful();

        Route::middleware('web')->group(fn () => Route::factoryTwin('twin-test', 'twin-test'));
        Route::getRoutes()->refreshNameLookups();

        $this->get(route('twin-test'))->assertOk()->assertSee('Jumeau numérique');
        $this->getJson(route('twin-test.snapshot'))->assertOk()
            ->assertJsonPath('contract', 'factory-twin.snapshot/1')
            ->assertJsonStructure(['generated_at', 'operations', 'resources', 'queues' => ['operations', 'resources'], 'flows']);
        $this->getJson(route('twin-test.order', ['ref' => 'INCONNUE']))->assertOk()->assertJsonPath('found', false);
        // Aucune scène enregistrée et aucun plan proposé par l'ERP : scène vide, postes rangés par le front.
        $this->getJson(route('twin-test.scene'))->assertOk()->assertJsonPath('shapes', []);
    }
}
