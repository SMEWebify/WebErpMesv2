<?php

namespace Tests\Feature;

use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * GHSA-rqxq-q992-xj2h: products-menu only hid the menu entry, so a role
 * without it (asset_manager) could still read and edit the catalogue by URL.
 */
class ProductsAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Storage::fake(config('files.disk'));

        Permission::findOrCreate('products-menu');
        Permission::findOrCreate('asset_manager');
        Role::findOrCreate('asset_manager')->givePermissionTo('asset_manager');

        // ProductsFactory picks an existing unit and family at random.
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();
    }

    private function assetManager(): User
    {
        $user = User::factory()->create();
        $user->assignRole('asset_manager');

        return $user;
    }

    public function test_a_role_without_the_catalogue_permission_cannot_list_products(): void
    {
        $this->actingAs($this->assetManager())
            ->getJson(route('products.json.list'))
            ->assertForbidden();
    }

    public function test_a_role_without_the_catalogue_permission_cannot_edit_a_product(): void
    {
        $product = Products::factory()->create(['label' => 'Original']);

        $this->actingAs($this->assetManager())
            ->post(route('products.update', ['id' => $product->id]), ['label' => 'Tampered'])
            ->assertForbidden();

        $this->assertSame('Original', $product->fresh()->label);
    }

    public function test_a_role_without_the_catalogue_permission_cannot_open_a_product(): void
    {
        $product = Products::factory()->create();

        $this->actingAs($this->assetManager())
            ->get(route('products.show', ['id' => $product->id]))
            ->assertForbidden();
    }

    public function test_a_role_without_the_catalogue_permission_cannot_attach_a_file_to_a_product(): void
    {
        $product = Products::factory()->create();

        $this->actingAs($this->assetManager())
            ->postJson(route('files.json.store'), [
                'fileable_type' => 'product',
                'fileable_id' => $product->id,
                'files' => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
            ])
            ->assertForbidden();

        $this->assertSame(0, $product->files()->count());
    }

    public function test_the_product_lookup_stays_available_to_every_role(): void
    {
        // Quote, order and purchase lines pick products through this endpoint.
        $this->actingAs($this->assetManager())
            ->getJson(route('products.json.search'))
            ->assertOk();
    }

    public function test_the_catalogue_permission_grants_access(): void
    {
        $user = $this->assetManager();
        $user->givePermissionTo('products-menu');

        $this->actingAs($user)
            ->getJson(route('products.json.list'))
            ->assertOk();
    }
}
