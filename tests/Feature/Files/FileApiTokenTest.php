<?php

namespace Tests\Feature\Files;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * GED par jeton d'API : sous la garde « sanctum », les droits (déclarés pour
 * « web ») doivent s'appliquer comme dans le navigateur — ni tout refuser, ni
 * tout ouvrir.
 */
class FileApiTokenTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Products $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
        ]);
        Storage::fake(config('files.disk'));

        foreach (['products-menu', 'products-edit'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $this->user = User::factory()->create();
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create(['methods_services_id' => MethodsServices::factory()->create()->id]);
        $this->product = Products::factory()->create();
    }

    private function upload()
    {
        Sanctum::actingAs($this->user, ['*']);

        return $this->postJson(route('api.files.store'), [
            'fileable_type' => 'product',
            'fileable_id'   => $this->product->id,
            'files'         => [UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf')],
        ]);
    }

    public function test_a_token_of_a_user_with_the_rights_can_attach_a_file_to_an_item(): void
    {
        $this->user->givePermissionTo(['products-menu', 'products-edit']);

        $this->upload()->assertCreated();
        $this->assertSame(1, $this->product->files()->count());
    }

    public function test_a_token_of_a_user_without_the_write_right_is_refused(): void
    {
        $this->user->givePermissionTo('products-menu');

        $this->upload()->assertForbidden();
        $this->assertSame(0, $this->product->files()->count());
    }

    public function test_a_token_reads_the_list_with_the_read_right(): void
    {
        $this->user->givePermissionTo('products-menu');
        Sanctum::actingAs($this->user, ['*']);

        $this->getJson(route('api.files.list', ['fileable_type' => 'product', 'fileable_id' => $this->product->id]))
            ->assertOk();
    }
}
