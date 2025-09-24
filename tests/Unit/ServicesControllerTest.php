<?php

namespace Tests\Feature\Http\Controllers\Methods;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Methods\MethodsServices;
use App\Services\SelectDataService;

class ServicesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $mockSelectDataService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::create([
            'name' => 'Test Factory',
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Mock le service SelectDataService
        $this->mockSelectDataService = $this->createMock(SelectDataService::class);
        $this->app->instance(SelectDataService::class, $this->mockSelectDataService);
    }

    /** @test */
    public function it_displays_the_services_list()
    {
        // Crée des services factices
        MethodsServices::factory()->count(5)->create();

        // Mock la méthode getSupplier du service SelectDataService
        $this->mockSelectDataService
            ->expects($this->once())
            ->method('getSupplier')
            ->willReturn(['Supplier 1', 'Supplier 2']);

        // Exécute la requête GET
        $response = $this->get(route('methods.service'));

        // Vérifie le code de statut et les données dans la vue
        $response->assertStatus(200);
        $response->assertViewHas('MethodsServices');
        $response->assertViewHas('CompanieSelect', ['Supplier 1', 'Supplier 2']);
    }

    /** @test */
    public function it_stores_a_new_service_with_image()
    {
        // Simule le stockage des fichiers
        Storage::fake('public');

        // Crée des données valides avec une image
        $data = [
            'code' => 'SRV001',
            'ordre' => 1,
            'label' => 'Test Service',
            'type' => 'consulting',
            'hourly_rate' => 100,
            'margin' => 20,
            'color' => '#FFFFFF',
            'companies_id' => 1,
            'picture' => UploadedFile::fake()->image('service.jpg')
        ];

        // Exécute la requête POST
        $response = $this->post(route('methods.service.create'), $data);

        // Vérifie que le service a bien été créé
        $this->assertDatabaseHas('methods_services', ['code' => 'SRV001', 'label' => 'Test Service']);

        // Vérifie que l'image a bien été stockée
        Storage::disk('public')->assertExists('images/methods/' . $data['picture']->hashName());

        // Vérifie la redirection
        $response->assertRedirect(route('methods.service'));
        $response->assertSessionHas('success', 'Successfully created service.');
    }

    /** @test */
    public function it_returns_error_if_no_image_is_selected()
    {
        // Crée des données sans image
        $data = [
            'code' => 'SRV001',
            'ordre' => 1,
            'label' => 'Test Service',
            'type' => 'consulting',
            'hourly_rate' => 100,
            'margin' => 20,
            'color' => '#FFFFFF',
            'companies_id' => 1
        ];

        // Exécute la requête POST
        $response = $this->post(route('methods.service.create'), $data);

        // Vérifie que l'erreur est retournée
        $response->assertSessionHasErrors(['msg' => 'Error, no image selected']);
    }

    /** @test */
    public function it_updates_an_existing_service()
    {
        // Crée un service existant
        $service = MethodsServices::factory()->create();

        // Crée des données de mise à jour
        $data = [
            'id' => $service->id,
            'ordre' => 2,
            'label' => 'Updated Service',
            'type' => 'maintenance',
            'hourly_rate' => 120,
            'margin' => 25,
            'color' => '#000000',
            'companies_id' => 2
        ];

        // Exécute la requête POST
        $response = $this->post(route('methods.service.update', ['id' => $service->id]), $data);

        // Vérifie que le service a été mis à jour
        $this->assertDatabaseHas('methods_services', ['id' => $service->id, 'label' => 'Updated Service']);

        // Vérifie la redirection
        $response->assertRedirect(route('methods.service'));
        $response->assertSessionHas('success', 'Successfully updated service.');
    }

    /** @test */
    public function it_updates_the_service_image()
    {
        // Simule le stockage des fichiers
        Storage::fake('public');

        // Crée un service existant
        $service = MethodsServices::factory()->create();

        // Crée des données avec une nouvelle image
        $data = [
            'id' => $service->id,
            'picture' => UploadedFile::fake()->image('new_service.jpg')
        ];

        // Exécute la requête POST pour la mise à jour de l'image
        $response = $this->post(route('methods.service.update.picture', ['id' => $service->id]), $data);

        // Vérifie que l'image a bien été mise à jour
        $this->assertDatabaseHas('methods_services', ['id' => $service->id, 'picture' => $data['picture']->hashName()]);

        // Vérifie que l'image a bien été stockée
        Storage::disk('public')->assertExists('images/methods/' . $data['picture']->hashName());

        // Vérifie la redirection
        $response->assertRedirect(route('methods.service'));
        $response->assertSessionHas('success', 'Successfully updated service.');
    }

    /** @test */
    public function it_returns_error_if_no_image_is_selected_for_update()
    {
        // Crée un service existant
        $service = MethodsServices::factory()->create();

        // Crée des données sans image
        $data = [
            'id' => $service->id
        ];

        // Exécute la requête POST pour la mise à jour de l'image sans image
        $response = $this->post(route('methods.service.update.picture', ['id' => $service->id]), $data);

        // Vérifie que l'erreur est retournée
        $response->assertSessionHasErrors(['msg' => 'Error, no image selected']);
    }
}
