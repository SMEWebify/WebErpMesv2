<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsSection;
use App\Services\SelectDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class SectionsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $selectDataServiceMock;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::create([
            'name' => 'Test Factory',
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Mocking the SelectDataService for the dependency injection
        $this->selectDataServiceMock = Mockery::mock(SelectDataService::class);
        $this->app->instance(SelectDataService::class, $this->selectDataServiceMock);
    }

    /** 
     * Test index method to display the list of sections.
     */
    public function test_index_displays_sections()
    {
        // Créer des sections factices
        $sections = MethodsSection::factory()->count(3)->create();

        // Mock the SelectDataService to return some user data
        $this->selectDataServiceMock->shouldReceive('getUsers')->andReturn([
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2']
        ]);

        // Appel de la méthode index
        $response = $this->get(route('methods.section'));

        // Assurer que la réponse a un statut 200
        $response->assertStatus(200);

        // Vérifier que la vue contient les sections et le userSelect
        $response->assertViewHas('MethodsSections', function ($viewSections) use ($sections) {
            return $viewSections->count() === $sections->count();
        });
        $response->assertViewHas('userSelect', [
            ['id' => 1, 'name' => 'User 1'],
            ['id' => 2, 'name' => 'User 2']
        ]);
    }

    /**
     * Test store method to create a new section.
     */
    public function test_store_creates_section()
    {
        // Créer un utilisateur factice pour l'association
        $user = User::factory()->create();

        // Simuler une requête POST avec des données valides
        $response = $this->post(route('methods.section.create'), [
            'ordre' => 1,
            'code' => 'SEC001',
            'label' => 'Section 1',
            'user_id' => $user->id,
            'color' => '#FFFFFF',
        ]);

        // Vérifier que la section a été créée dans la base de données
        $this->assertDatabaseHas('methods_sections', [
            'code' => 'SEC001',
            'label' => 'Section 1',
            'user_id' => $user->id,
            'color' => '#FFFFFF',
        ]);

        // Assurer la redirection avec un message de succès
        $response->assertRedirect(route('methods.section'));
        $response->assertSessionHas('success', 'Successfully created section.');
    }

    /**
     * Test update method to update a section.
     */
    public function test_update_section()
    {
        // Créer une section factice
        $section = MethodsSection::factory()->create([
            'ordre' => 1,
            'code' => 'SEC002',
            'label' => 'Old Section',
            'color' => '#000000',
        ]);

        // Créer un utilisateur factice
        $user = User::factory()->create();

        // Simuler une requête POST avec des données mises à jour
        $response = $this->post(route('methods.section.update', ['id' => $section->id]), [
            'id' => $section->id,
            'ordre' => 2,
            'label' => 'Updated Section',
            'user_id' => $user->id,
            'color' => '#FF0000',
        ]);

        // Vérifier que la section a été mise à jour dans la base de données
        $this->assertDatabaseHas('methods_sections', [
            'id' => $section->id,
            'ordre' => 2,
            'label' => 'Updated Section',
            'user_id' => $user->id,
            'color' => '#FF0000',
        ]);

        // Assurer la redirection avec un message de succès
        $response->assertRedirect(route('methods.section'));
        $response->assertSessionHas('success', 'Successfully updated section.');
    }
}
