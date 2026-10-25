<?php

namespace Tests\Feature;

use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsStandardNomenclature;
use App\Models\Methods\MethodsStandardTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La gamme d'une nomenclature standard (Méthodes → Nomenclature standard → « Voir ») passe par
 * l'écran gamme commun. Une tâche de modèle n'a pas de statut : charger la relation `status`
 * faisait échouer tout l'écran (« Chargement échoué »).
 */
class StandardNomenclatureRoutingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_routing_of_a_standard_nomenclature_loads()
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->actingAs(User::factory()->create());

        $service = MethodsServices::factory()->create(['type' => 1]);
        $model = MethodsStandardNomenclature::factory()->create();
        MethodsStandardTask::create([
            'label' => 'Découpe laser',
            'ordre' => 10,
            'methods_nomenclature_standard_id' => $model->id,
            'methods_services_id' => $service->id,
            'type' => 1,
            'seting_time' => 0.25,
            'unit_time' => 0.05,
            'unit_cost' => 12,
            'unit_price' => 18,
        ]);

        $this->getJson(route('task.manage.json.initial', [
            'id_type' => 'nomenclature_lines_id',
            'id_page' => $model->id,
            'id_line' => $model->id,
        ]))
            ->assertOk()
            ->assertJsonPath('tasks.technical_cut.0.label', 'Découpe laser');
    }
}
