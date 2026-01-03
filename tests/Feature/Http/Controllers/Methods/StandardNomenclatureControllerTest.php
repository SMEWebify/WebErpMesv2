<?php

namespace Tests\Feature\Http\Controllers\Methods;

use Tests\TestCase;
use App\Models\User;
use App\Models\Admin\Factory;
use App\Models\Methods\MethodsStandardNomenclature;
use App\Services\SelectDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Methods\StoreStandardNomenclatureRequest;
use App\Http\Requests\Methods\UpdateStandardNomenclatureRequest;

class StandardNomenclatureControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $selectDataService;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        Factory::create([
            'name' => 'Test Factory',
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->selectDataService = $this->createMock(SelectDataService::class);
        $this->app->instance(SelectDataService::class, $this->selectDataService);
    }

    /** @test */
    public function it_displays_the_list_of_standard_nomenclatures()
    {
        // Arrange
        $nomenclatures = MethodsStandardNomenclature::factory()->count(3)->create();

        // Act
        $response = $this->get(route('methods.standard.nomenclature'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('MethodsStandardNomenclatures', function ($viewNomenclatures) use ($nomenclatures) {
            return $viewNomenclatures->count() === 3;
        });
    }

    /** @test */
    public function it_stores_a_new_standard_nomenclature()
    {
        // Arrange
        $data = [
            'code' => 'STD001',
            'label' => 'Standard Nomenclature 1',
            'comment' => 'This is a comment',
        ];

        // Act
        $response = $this->post(route('methods.standard.nomenclature.create'), $data);

        // Assert
        $response->assertRedirect(route('methods.standard.nomenclature'));
        $response->assertSessionHas('success', 'Successfully created standard nomenclature.');
        $this->assertDatabaseHas('methods_standard_nomenclatures', $data);
    }

    /** @test */
    public function it_fails_to_store_nomenclature_with_invalid_data()
    {
        // Arrange: Send invalid data
        $data = [
            'code' => '', // Empty code
            'label' => 'Nomenclature',
            'comment' => 'Comment',
        ];

        // Act
        $response = $this->post(route('methods.standard.nomenclature.create'), $data);

        // Assert
        $response->assertSessionHasErrors(['code']);
        $this->assertDatabaseMissing('methods_standard_nomenclatures', ['label' => 'Nomenclature']);
    }

    /** @test */
    public function it_updates_an_existing_standard_nomenclature()
    {
        // Arrange
        $nomenclature = MethodsStandardNomenclature::factory()->create([
            'code' => 'STD001',
            'label' => 'Old Label',
        ]);

        $updateData = [
            'id' => $nomenclature->id,
            'label' => 'Updated Label',
            'comment' => 'Updated comment',
        ];

        // Act
        $response = $this->post(route('methods.standard.nomenclature.update', ['id' => $nomenclature->id]), $updateData);

        // Assert
        $response->assertRedirect(route('methods.standard.nomenclature'));
        $response->assertSessionHas('success', 'Successfully updated standard nomenclature.');
        $this->assertDatabaseHas('methods_standard_nomenclatures', [
            'id' => $nomenclature->id,
            'label' => 'Updated Label',
            'comment' => 'Updated comment',
        ]);
    }

    /** @test */
    public function it_fails_to_update_with_invalid_data()
    {
        // Arrange
        $nomenclature = MethodsStandardNomenclature::factory()->create();

        // Act: Sending invalid update (empty label)
        $response = $this->post(route('methods.standard.nomenclature.update', ['id' => $nomenclature->id]), [
            'id' => $nomenclature->id,
            'label' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['label']);
        $this->assertDatabaseMissing('methods_standard_nomenclatures', ['label' => '']);
    }
}
