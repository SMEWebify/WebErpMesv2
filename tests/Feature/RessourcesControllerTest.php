<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RessourcesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    /** Services réels : le formulaire valide désormais leur existence. */
    protected MethodsServices $service;
    protected MethodsServices $secondService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->service = MethodsServices::factory()->create();
        $this->secondService = MethodsServices::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test index method to display the list of ressources.
     */
    public function test_index_displays_ressources()
    {
        // Créer des ressources factices
        $ressources = MethodsRessources::factory()->count(3)->create();

        // Simuler la requête vers l'index
        $response = $this->get(route('methods.ressource'));

        // Assurer que la réponse a un statut 200 (OK)
        $response->assertStatus(200);

        // Vérifier que les ressources sont présentes dans la vue
        $response->assertViewHas('MethodsRessources', function ($viewRessources) use ($ressources) {
            return $viewRessources->count() === $ressources->count();
        });
    }

    /**
     * Test store method to create a new ressource.
     */
    public function test_store_creates_ressource()
    {
        Storage::fake('public');

        // Simuler une requête POST avec des données valides
        $response = $this->post(route('methods.ressource.create'), [
            'ordre' => 1,
            'code' => 'R001',
            'label' => 'Ressource 1',
            'capacity' => 10,
            'section_id' => 1,
            'color' => '#FFFFFF',
            'methods_services_id' => $this->service->id,
            'mask_time' => true,
            'picture' => UploadedFile::fake()->image('ressource.jpg'),
        ]);

        // Vérifier que la ressource a été créée dans la base de données
        $this->assertDatabaseHas('methods_ressources', [
            'code' => 'R001',
            'label' => 'Ressource 1',
            'capacity' => 10,
            'mask_time' => 1,  // Le champ mask_time devrait être mis à jour à 1
        ]);

        // Assurer la redirection avec un message de succès
        $response->assertRedirect(route('methods.ressource'));
        $response->assertSessionHas('success', 'Successfully created resource.');
    }

    /**
     * Test store method fails without an image.
     */
    public function test_store_fails_without_image()
    {
        // Simuler une requête POST sans image
        $response = $this->post(route('methods.ressource.create'), [
            'ordre' => 1,
            'code' => 'R002',
            'label' => 'Ressource 2',
            'capacity' => 10,
            'section_id' => 1,
            'color' => '#000000',
            'methods_services_id' => $this->service->id,
            'mask_time' => true,
        ]);

        // Vérifier que la ressource n'est pas créée et qu'une erreur est renvoyée
        $response->assertSessionHasErrors(['msg']);
    }

    /**
     * Test update method to update a ressource.
     */
    public function test_update_ressource()
    {
        // Créer une ressource factice
        $ressource = MethodsRessources::factory()->create([
            'ordre' => 1,
            'code' => 'R003',
            'label' => 'Old Label',
        ]);

        // Simuler une requête POST avec des données mises à jour
        $response = $this->post(route('methods.ressource.update', ['id' => $ressource->id]), [
            'id' => $ressource->id,
            'ordre' => 2,
            'label' => 'Updated Label',
            'capacity' => 20,
            'mask_time_update' => true,
            'section_id' => 2,
            'color' => '#FF0000',
            'methods_services_id' => $this->secondService->id,
        ]);

        // Vérifier que la ressource a été mise à jour dans la base de données
        $this->assertDatabaseHas('methods_ressources', [
            'id' => $ressource->id,
            'label' => 'Updated Label',
            'capacity' => 20,
            'mask_time' => 1,
        ]);

        // Assurer la redirection avec un message de succès
        $response->assertRedirect(route('methods.ressource'));
        $response->assertSessionHas('success', 'Successfully updated resource.');
    }

    /**
     * Test store image method to upload an image for a ressource.
     */
    public function test_store_image()
    {
        // Simuler le système de fichiers temporairement
        Storage::fake('public');

        // Créer une ressource factice
        $ressource = MethodsRessources::factory()->create();

        // Simuler une requête POST avec une image valide
        $response = $this->post(route('methods.ressource.update.picture', ['id' => $ressource->id]), [
            'id' => $ressource->id,
            'picture' => UploadedFile::fake()->image('ressource.jpg'),
        ]);

        $ressource = $ressource->fresh();

        // Vérifier que l'image a été stockée
        Storage::disk('public')->assertExists('images/ressources/' . $ressource->picture);

        // Vérifier que la ressource a bien l'image associée dans la base de données
        $this->assertDatabaseHas('methods_ressources', [
            'id' => $ressource->id,
            'picture' => $ressource->picture,
        ]);

        // Assurer la redirection avec un message de succès
        $response->assertRedirect(route('methods.ressource'));
        $response->assertSessionHas('success', 'Successfully updated resource.');
    }

    /**
     * Une ressource peut réaliser plusieurs services : le service principal
     * ouvre la liste (preference 0), les services complémentaires suivent.
     */
    public function test_store_attaches_principal_and_additional_services()
    {
        Storage::fake('public');

        $this->post(route('methods.ressource.create'), [
            'ordre' => 1,
            'code' => 'R010',
            'label' => 'Centre usinage',
            'capacity' => 35,
            'section_id' => 1,
            'color' => '#FFFFFF',
            'methods_services_id' => $this->service->id,
            'additional_services' => [$this->secondService->id],
            'mask_time' => true,
            'picture' => UploadedFile::fake()->image('ressource.jpg'),
        ]);

        $ressource = MethodsRessources::where('code', 'R010')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$this->service->id, $this->secondService->id],
            $ressource->services->pluck('id')->all()
        );
        $this->assertSame(0, (int) $ressource->services->firstWhere('id', $this->service->id)->pivot->preference);
        $this->assertSame(1, (int) $ressource->services->firstWhere('id', $this->secondService->id)->pivot->preference);
    }

    /** Une ressource peut être une capacité humaine, avec ses personnes habilitées. */
    public function test_store_creates_a_labor_resource_with_qualified_users()
    {
        Storage::fake('public');

        $operator = User::factory()->create();

        $this->post(route('methods.ressource.create'), [
            'ordre' => 2,
            'code' => 'EBAV',
            'label' => 'Ebavurage manuel',
            'capacity' => 35,
            'section_id' => 1,
            'color' => '#FFFFFF',
            'methods_services_id' => $this->service->id,
            'is_labor' => 1,
            'labor_ratio' => 1,
            'qualified_users' => [$operator->id],
            'mask_time' => true,
            'picture' => UploadedFile::fake()->image('ressource.jpg'),
        ]);

        $ressource = MethodsRessources::where('code', 'EBAV')->firstOrFail();

        $this->assertTrue($ressource->is_labor);
        $this->assertSame([$operator->id], $ressource->users->pluck('id')->all());
        $this->assertSame(
            MethodsRessources::LEVEL_AUTONOMOUS,
            (int) $ressource->users->first()->pivot->level
        );
    }

    /**
     * Le switch AdminLTE poste la chaîne "true", pas 1 : la validation doit
     * l'accepter, sinon le formulaire renvoie « le champ is labor doit être
     * vrai ou faux ».
     */
    public function test_the_adminlte_switch_value_is_accepted()
    {
        Storage::fake('public');

        $response = $this->post(route('methods.ressource.create'), [
            'ordre' => 3,
            'code' => 'R020',
            'label' => 'Poste manuel',
            'capacity' => 35,
            'section_id' => 1,
            'color' => '#FFFFFF',
            'methods_services_id' => $this->service->id,
            'is_labor' => 'true',
            'mask_time' => 'true',
            'picture' => UploadedFile::fake()->image('ressource.jpg'),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(MethodsRessources::where('code', 'R020')->firstOrFail()->is_labor);
    }

    /** Switch désactivé : le champ n'est pas posté du tout. */
    public function test_an_unchecked_switch_is_stored_as_false()
    {
        Storage::fake('public');

        $this->post(route('methods.ressource.create'), [
            'ordre' => 4,
            'code' => 'R021',
            'label' => 'Machine',
            'capacity' => 35,
            'section_id' => 1,
            'color' => '#FFFFFF',
            'methods_services_id' => $this->service->id,
            'picture' => UploadedFile::fake()->image('ressource.jpg'),
        ]);

        $this->assertFalse(MethodsRessources::where('code', 'R021')->firstOrFail()->is_labor);
    }
}
