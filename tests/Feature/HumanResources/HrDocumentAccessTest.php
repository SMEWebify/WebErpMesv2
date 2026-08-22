<?php

namespace Tests\Feature\HumanResources;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Employee folder documents hold personal data: unlike the rest of the GED,
 * they must not be readable by the whole factory.
 */
class HrDocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        Permission::findOrCreate('human-resources-menu');
    }

    /**
     * Attach a document to an employee folder and return its id.
     */
    private function attachDocumentTo(User $employee, User $uploader): int
    {
        $response = $this->actingAs($uploader)->postJson(route('files.json.store'), [
            'fileable_type' => 'user',
            'fileable_id' => $employee->id,
            'files' => [UploadedFile::fake()->create('contrat.pdf', 12, 'application/pdf')],
            'role' => \App\Services\Files\FileRole::CONTRACT,
        ]);

        $response->assertCreated();

        return $response->json('files.0.id');
    }

    /** @test */
    public function an_employee_can_read_their_own_folder()
    {
        $employee = User::factory()->create();
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');

        $fileId = $this->attachDocumentTo($employee, $hr);

        $this->actingAs($employee)
            ->getJson(route('files.json.list', ['fileable_type' => 'user', 'fileable_id' => $employee->id]))
            ->assertOk()
            ->assertJsonPath('files.0.id', $fileId);

        $this->actingAs($employee)
            ->get(route('files.raw', ['file' => $fileId]))
            ->assertOk();
    }

    /** @test */
    public function a_colleague_cannot_list_another_employee_folder()
    {
        $employee = User::factory()->create();
        $colleague = User::factory()->create();
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');

        $this->attachDocumentTo($employee, $hr);

        $this->actingAs($colleague)
            ->getJson(route('files.json.list', ['fileable_type' => 'user', 'fileable_id' => $employee->id]))
            ->assertForbidden();
    }

    /** @test */
    public function a_colleague_cannot_download_a_document_of_another_employee()
    {
        $employee = User::factory()->create();
        $colleague = User::factory()->create();
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');

        $fileId = $this->attachDocumentTo($employee, $hr);

        $this->actingAs($colleague)
            ->get(route('files.raw', ['file' => $fileId]))
            ->assertForbidden();

        $this->actingAs($colleague)
            ->get(route('files.download', ['file' => $fileId]))
            ->assertForbidden();
    }

    /** @test */
    public function a_colleague_cannot_attach_a_document_to_another_employee_folder()
    {
        $employee = User::factory()->create();
        $colleague = User::factory()->create();

        $this->actingAs($colleague)
            ->postJson(route('files.json.store'), [
                'fileable_type' => 'user',
                'fileable_id' => $employee->id,
                'files' => [UploadedFile::fake()->create('contrat.pdf', 12, 'application/pdf')],
            ])
            ->assertForbidden();
    }

    /** @test */
    public function human_resources_can_read_any_employee_folder()
    {
        $employee = User::factory()->create();
        $hr = User::factory()->create();
        $hr->givePermissionTo('human-resources-menu');

        $fileId = $this->attachDocumentTo($employee, $hr);

        $this->actingAs($hr)
            ->getJson(route('files.json.list', ['fileable_type' => 'user', 'fileable_id' => $employee->id]))
            ->assertOk();

        $this->actingAs($hr)
            ->get(route('files.raw', ['file' => $fileId]))
            ->assertOk();
    }
}
