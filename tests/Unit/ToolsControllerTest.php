<?php

namespace Tests\Feature\Controllers\Methods;

use Tests\TestCase;
use App\Models\Methods\MethodsTools;
use App\Services\SelectDataService;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class ToolsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $selectDataService;

    public function setUp(): void
    {
        parent::setUp();

        // Mock du service SelectDataService
        $this->selectDataService = $this->createMock(SelectDataService::class);
        $this->app->instance(SelectDataService::class, $this->selectDataService);
    }

    /**
     * Test de la méthode index
     *
     * @return void
     */
    public function test_it_displays_a_listing_of_tools()
    {
        $tools = MethodsTools::factory()->count(5)->create();

        $response = $this->get(route('methods.tool'));

        $response->assertStatus(200)
                 ->assertViewIs('methods.methods-tools')
                 ->assertViewHas('MethodsTools', function ($viewTools) use ($tools) {
                     return $viewTools->count() === 5;
                 });
    }

    /**
     * Test de la méthode store
     *
     * @return void
     */
    public function test_it_stores_a_new_tool()
    {
        $toolData = MethodsTools::factory()->make()->toArray();

        $response = $this->post(route('methods.tool.create'), array_merge($toolData, [
            'picture' => UploadedFile::fake()->image('tool.jpg'),
            'ETAT' => 1
        ]));

        $this->assertDatabaseHas('methods_tools', [
            'code' => $toolData['code'],
            'label' => $toolData['label'],
        ]);

        $response->assertRedirect(route('methods.tool'))
                 ->assertSessionHas('success', 'Successfully created tool.');
    }

    /**
     * Test d'erreur lors de la création de tool sans image
     *
     * @return void
     */
    public function test_it_fails_to_store_without_image()
    {
        $toolData = MethodsTools::factory()->make()->toArray();

        $response = $this->post(route('methods.tool.create'), array_merge($toolData, ['ETAT' => 1]));

        $response->assertSessionHasErrors('msg', 'Error, no image selected');
    }

    /**
     * Test de la méthode update
     *
     * @return void
     */
    public function test_it_updates_an_existing_tool()
    {
        $tool = MethodsTools::factory()->create();

        $updateData = [
            'id' => $tool->id,
            'label' => 'Updated Tool',
            'cost' => 150,
            'end_date' => now()->addMonth(),
            'qty' => 10,
            'etat_update' => 1,
        ];

        $response = $this->post(route('methods.tool.update', ['id' => $tool->id]), $updateData);

        $this->assertDatabaseHas('methods_tools', [
            'id' => $tool->id,
            'label' => 'Updated Tool',
            'cost' => 150,
            'qty' => 10,
        ]);

        $response->assertRedirect(route('methods.tool'))
                 ->assertSessionHas('success', 'Successfully updated tool.');
    }

    /**
     * Test de la méthode StoreImage
     *
     * @return void
     */
    public function test_it_stores_an_image_for_existing_tool()
    {
        Storage::fake('public');

        $tool = MethodsTools::factory()->create();

        $response = $this->post(route('methods.tool.update.picture', ['id' => $tool->id]), [
            'id' => $tool->id,
            'picture' => UploadedFile::fake()->image('tool_image.jpg')
        ]);

        $tool = $tool->fresh(); // Refresh to get updated data

        Storage::disk('public')->assertExists('images/methods/' . $tool->picture);

        $response->assertRedirect(route('methods.tool'))
                 ->assertSessionHas('success', 'Successfully updated tool.');
    }

    /**
     * Test d'erreur lors du chargement d'image dans StoreImage sans fichier
     *
     * @return void
     */
    public function test_it_fails_to_store_image_without_file()
    {
        $tool = MethodsTools::factory()->create();

        $response = $this->post(route('methods.tool.update.picture', ['id' => $tool->id]), [
            'id' => $tool->id,
        ]);

        $response->assertSessionHasErrors('msg', 'Error, no image selected');
    }
}
