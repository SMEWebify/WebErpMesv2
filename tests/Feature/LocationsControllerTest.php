<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Methods\MethodsLocation;
use App\Services\SelectDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocationsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $selectDataService;

    public function setUp(): void
    {
        parent::setUp();
        // Mock du service SelectDataService
        $this->selectDataService = $this->createMock(SelectDataService::class);
        $this->selectDataService->method('getRessources')->willReturn(['Resource1', 'Resource2']);
        $this->selectDataService->method('getUsers')->willReturn(['User1', 'User2']);
    }

    /** @test */
    public function it_displays_the_locations_index()
    {
        // Créer un utilisateur pour l'authentification
        $user = User::factory()->create();

        // Créer des locations factices
        MethodsLocation::factory()->count(3)->create();

        // Simuler la route 'methods.location' avec authentification
        $response = $this->actingAs($user)->get(route('methods.location'));

        // Vérifier que la vue correcte est retournée
        $response->assertStatus(200);
        $response->assertViewIs('methods/methods-locations');

        // Vérifier que les locations sont passées à la vue
        $response->assertViewHas('MethodsLocations');
        $response->assertViewHas('RessourcesSelect', ['Resource1', 'Resource2']);
        $response->assertViewHas('userSelect', ['User1', 'User2']);
    }

    /** @test */
    public function it_stores_a_new_location()
    {
        // Créer un utilisateur pour l'authentification
        $user = User::factory()->create();

        // Simuler une requête POST avec des données valides
        $data = [
            'code' => 'LOC001',
            'label' => 'New Location',
            'ressource_id' => 1,
            'color' => '#FFFFFF',
        ];

        $response = $this->actingAs($user)->post(route('methods.location.create'), $data);

        // Vérifier que la redirection fonctionne
        $response->assertRedirect(route('methods.location'));
        $response->assertSessionHas('success', 'Successfully created location.');

        // Vérifier que la location a été créée en base de données
        $this->assertDatabaseHas('methods_locations', [
            'code' => 'LOC001',
            'label' => 'New Location',
            'ressource_id' => 1,
            'color' => '#FFFFFF',
        ]);
    }

    /** @test */
    public function it_updates_an_existing_location()
    {
        // Créer un utilisateur pour l'authentification
        $user = User::factory()->create();

        // Créer une location factice à mettre à jour
        $location = MethodsLocation::factory()->create([
            'label' => 'Old Location',
            'ressource_id' => 1,
            'color' => '#000000',
        ]);

        // Simuler une requête POST avec des données mises à jour
        $data = [
            'id' => $location->id,
            'label' => 'Updated Location',
            'ressource_id' => 2,
            'color' => '#FFFFFF',
        ];

        $response = $this->actingAs($user)->post(route('methods.location.update', ['id' => $location->id]), $data);

        // Vérifier que la redirection fonctionne
        $response->assertRedirect(route('methods.location'));
        $response->assertSessionHas('success', 'Successfully updated Location.');

        // Vérifier que la location a été mise à jour en base de données
        $this->assertDatabaseHas('methods_locations', [
            'id' => $location->id,
            'label' => 'Updated Location',
            'ressource_id' => 2,
            'color' => '#FFFFFF',
        ]);
    }
}
