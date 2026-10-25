<?php

namespace Tests\Feature;

use App\Models\Methods\MethodsServices;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La fenêtre « Modifier » d'un service pré-remplissait la marge avec le taux horaire :
 * enregistrer sans corriger écrasait la marge (ex. 210 €/h → 210 % de marge).
 */
class MethodsServiceEditFormTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function the_edit_form_shows_the_margin_of_the_service()
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        $this->actingAs(User::factory()->create());

        MethodsServices::factory()->create(['hourly_rate' => 210, 'margin' => 15]);

        $this->get(route('methods.service'))
            ->assertOk()
            ->assertSee('name="margin" id="margin" placeholder="10%" step=".001" value="15', false)
            ->assertDontSee('name="margin" id="margin" placeholder="10%" step=".001" value="210', false);
    }
}
