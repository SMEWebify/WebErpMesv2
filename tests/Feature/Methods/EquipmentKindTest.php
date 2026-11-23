<?php

namespace Tests\Feature\Methods;

use App\Enums\EquipmentKind;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use App\Models\User;
use App\Services\Integrations\FactoryTwin\FactoryTwinSnapshot;
use App\Services\SelectDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Type d'équipement des postes de charge et des ressources (App\Enums\EquipmentKind).
 */
class EquipmentKindTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Factory::create(['name' => 'Test Factory']);
        $this->actingAs(User::factory()->create());
        $select = $this->createMock(SelectDataService::class);
        $select->method('getSupplier')->willReturn(collect());
        $this->app->instance(SelectDataService::class, $select);
    }

    private function serviceUpdate(MethodsServices $service, array $extra = []): array
    {
        return $extra + [
            'id' => $service->id, 'ordre' => 2, 'label' => $service->label, 'type' => 1,
            'hourly_rate' => 80, 'margin' => 10, 'color' => '#000000',
        ];
    }

    private function resourceUpdate(MethodsRessources $resource, array $extra = []): array
    {
        return $extra + [
            'id' => $resource->id, 'ordre' => 1, 'label' => $resource->label, 'capacity' => 35,
            'section_id' => $resource->section_id, 'color' => '#FF0000', 'methods_services_id' => $resource->methods_services_id,
        ];
    }

    public function test_the_service_type_is_saved_and_kept_when_the_form_does_not_send_it(): void
    {
        $service = MethodsServices::factory()->create(['type' => 1]);

        $this->post(route('methods.service.update', ['id' => $service->id]), $this->serviceUpdate($service, ['equipment_kind' => 'laser_tube']))
            ->assertRedirect(route('methods.service'));
        $this->assertSame('laser_tube', $service->fresh()->equipment_kind);

        // Ancien formulaire sans le champ : inchangé.
        $this->post(route('methods.service.update', ['id' => $service->id]), $this->serviceUpdate($service));
        $this->assertSame('laser_tube', $service->fresh()->equipment_kind);

        // « Non précisé » remet à null.
        $this->post(route('methods.service.update', ['id' => $service->id]), $this->serviceUpdate($service, ['equipment_kind' => '']));
        $this->assertNull($service->fresh()->equipment_kind);
    }

    public function test_an_unknown_type_is_refused(): void
    {
        $service = MethodsServices::factory()->create(['type' => 1]);

        $this->post(route('methods.service.update', ['id' => $service->id]), $this->serviceUpdate($service, ['equipment_kind' => 'teleporteur']))
            ->assertSessionHasErrors('equipment_kind');
        $this->assertNull($service->fresh()->equipment_kind);
    }

    public function test_the_resource_type_is_saved(): void
    {
        $resource = MethodsRessources::factory()->create();

        $this->post(route('methods.ressource.update', ['id' => $resource->id]), $this->resourceUpdate($resource, ['equipment_kind' => 'lathe']))
            ->assertRedirect(route('methods.ressource'));
        $this->assertSame('lathe', $resource->fresh()->equipment_kind);

        $this->post(route('methods.ressource.update', ['id' => $resource->id]), $this->resourceUpdate($resource));
        $this->assertSame('lathe', $resource->fresh()->equipment_kind);
    }

    public function test_the_screens_list_every_type(): void
    {
        $this->get(route('methods.service'))->assertOk()
            ->assertSee(EquipmentKind::Paint->label())
            ->assertSee(__('equipment.kind_help_service'));
    }

    public function test_every_type_is_translated(): void
    {
        foreach (['fr', 'en'] as $locale) {
            app()->setLocale($locale);
            foreach (EquipmentKind::cases() as $kind) {
                $this->assertNotSame('equipment.kinds.' . $kind->value, $kind->label(), "{$locale} : {$kind->value}");
            }
        }
    }

    public function test_the_twin_takes_the_resource_type_then_the_service_type(): void
    {
        $machining = MethodsServices::factory()->create(['code' => 'USI', 'type' => MethodsServices::TYPE_PRODUCTIVE, 'equipment_kind' => 'milling']);
        $cad = MethodsServices::factory()->create(['code' => 'CAO', 'type' => MethodsServices::TYPE_PRODUCTIVE, 'equipment_kind' => 'office']);
        MethodsServices::factory()->create(['code' => 'DIV', 'type' => MethodsServices::TYPE_PRODUCTIVE]);
        MethodsRessources::factory()->create(['code' => 'TOUR-1', 'methods_services_id' => $machining->id, 'is_labor' => false, 'equipment_kind' => 'lathe']);
        MethodsRessources::factory()->create(['code' => 'CU-1', 'methods_services_id' => $machining->id, 'is_labor' => false]);

        $data = app(FactoryTwinSnapshot::class)->snapshot();
        $resources = collect($data['resources'])->keyBy('code');
        $operations = collect($data['operations'])->keyBy('code');

        $this->assertSame('lathe', $resources['TOUR-1']['kind']);
        $this->assertSame('milling', $resources['CU-1']['kind']);
        $this->assertSame('office', $operations['CAO']['kind']);
        $this->assertNull($operations['DIV']['kind']); // deviné par le nom côté jumeau
        $this->assertNotNull($cad);
    }
}
