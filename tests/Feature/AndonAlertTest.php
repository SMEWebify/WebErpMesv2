<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'alerte Andon de l'écran opérateur perdait sa description (champ « message » lu comme
 * « description ») et partait sur la ressource n° 1 quand l'opération n'en avait aucune.
 */
class AndonAlertTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_description_is_kept_and_no_resource_is_invented()
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        $this->actingAs(User::factory()->create());

        $this->from('/workshop')->post(route('workshop.andon.store'), [
            'task_id' => null,
            'resource_id' => '',
            'type' => 'Panne',
            'description' => 'Plieuse arrêtée, vérin hydraulique',
        ])->assertRedirect();

        $this->assertDatabaseHas('andon_alerts', [
            'type' => 'Panne',
            'description' => 'Plieuse arrêtée, vérin hydraulique',
            'methods_ressources_id' => null,
        ]);
    }

    /** @test */
    public function the_former_message_field_is_still_accepted()
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        $this->actingAs(User::factory()->create());

        $this->from('/workshop')->post(route('workshop.andon.store'), [
            'type' => 'Matière', 'message' => 'Tôle 3 mm manquante',
        ])->assertRedirect();

        $this->assertDatabaseHas('andon_alerts', ['type' => 'Matière', 'description' => 'Tôle 3 mm manquante']);
    }
}
