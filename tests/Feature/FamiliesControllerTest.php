<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsLocation;
use App\Services\SelectDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FamiliesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $selectDataService;

    public function setUp(): void
    {
        parent::setUp();
        // Mock du service SelectDataService
        $this->selectDataService = $this->createMock(SelectDataService::class);
        $this->selectDataService->method('getRessources')->willReturn(['Resource1', 'Resource2']);
        $this->selectDataService->method('getServices')->willReturn(['Service1', 'Service2']);
    }

    /** @test */
    public function it_displays_the_families_index()
    {
        // Créer un utilisateur pour l'authentification
        $user = User::factory()->create();

        // Créer des familles et des locations factices
        MethodsFamilies::factory()->count(3)->create();
        MethodsLocation::factory()->count(2)->create();

        // Simuler la route 'methods.family' avec authentification
        $response = $this->actingAs($user)->get(route('methods.family'));

        // Vérifier que la vue correcte est retournée
        $response->assertStatus(200);
        $response->assertViewIs('methods/methods-families');

        // Vérifier que les familles et les locations sont passées à la vue
        $response->assertViewHas('MethodsFamilies');
        $response->assertViewHas('MethodsLocations');
        $response->assertViewHas('RessourcesSelect', ['Resource1', 'Resource2']);
        $response->assertViewHas('ServicesSelect', ['Service1', 'Service2']);
    }

    /** @test */
    public function it_stores_a_new_family()
    {
        // Créer un utilisateur pour l'authentification
        $user = User::factory()->create();

        // Simuler une requête POST avec des données valides
        $data = [
            'code' => 'F001',
            'label' => 'New Family',
            'methods_services_id' => 1,
        ];

        $response = $this->actingAs($user)->post(route('methods.family.create'), $data);

        // Vérifier que la redirection fonctionne
        $response->assertRedirect(route('methods.family'));
        $response->assertSessionHas('success', 'Successfully created family.');

        // Vérifier que la famille a été créée en base de données
        $this->assertDatabaseHas('methods_families', [
            'code' => 'F001',
            'label' => 'New Family',
            'methods_services_id' => 1,
        ]);
    }

    /** @test */
    public function it_updates_an_existing_family()
    {
        // Créer un utilisateur pour l'authentification
        $user = User::factory()->create();

        // Créer une famille factice à mettre à jour
        $family = MethodsFamilies::factory()->create([
            'label' => 'Old Family',
            'methods_services_id' => 1,
        ]);

        // Simuler une requête POST avec des données mises à jour
        $data = [
            'id' => $family->id,
            'label' => 'Updated Family',
            'methods_services_id' => 2,
        ];

        $response = $this->actingAs($user)->post(route('methods.family.update', ['id' => $family->id]), $data);

        // Vérifier que la redirection fonctionne
        $response->assertRedirect(route('methods.family'));
        $response->assertSessionHas('success', 'Successfully updated Family.');

        // Vérifier que la famille a été mise à jour en base de données
        $this->assertDatabaseHas('methods_families', [
            'id' => $family->id,
            'label' => 'Updated Family',
            'methods_services_id' => 2,
        ]);
    }
}
