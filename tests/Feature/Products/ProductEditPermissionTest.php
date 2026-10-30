<?php

namespace Tests\Feature\Products;

use App\Models\File;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\User;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\Quotes;
use App\Services\Files\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La base article est lue par les commerciaux (products-menu) et écrite par le
 * service technique seulement (products-edit). Le refus est posé côté serveur :
 * masquer les boutons ne suffit pas.
 */
class ProductEditPermissionTest extends TestCase
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

        Permission::findOrCreate('products-menu');
        Permission::findOrCreate('products-edit');
        Permission::findOrCreate('products-merge');

        // ProductsFactory pioche une unité et une famille existantes.
        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();
        // Statut initial des opérations créées par l'écran de gamme.
        \App\Models\Planning\Status::create(['title' => 'Open', 'order' => 1]);
    }

    private function salesUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products-menu');

        return $user;
    }

    private function technicalUser(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['products-menu', 'products-edit']);

        return $user;
    }

    private function product(array $attributes = []): Products
    {
        return Products::factory()->create(array_merge([
            'label' => 'Original',
            'picture' => null,
            'drawing_file' => null,
            'stl_file' => null,
            'svg_file' => null,
            // Un article acheté fait calculer un délai par DATEDIFF, absent de SQLite.
            'purchased' => 2,
        ], $attributes));
    }

    private function operation(array $attributes): Task
    {
        $service = MethodsServices::factory()->create(['type' => 1]);

        return Task::factory()->create(array_merge([
            'quote_lines_id' => null,
            'order_lines_id' => null,
            'products_id' => null,
            'sub_assembly_id' => null,
            'methods_services_id' => $service->id,
            'type' => 1,
            'label' => 'Découpe',
        ], $attributes));
    }

    private function taskPayload(): array
    {
        return [
            'label' => 'Pliage',
            'ordre' => 20,
            'methods_services_id' => MethodsServices::factory()->create(['type' => 1])->id,
            'type' => 1,
            'unit_cost' => 10,
            'unit_price' => 15,
        ];
    }

    // ── Lecture ─────────────────────────────────────────────────────────────

    public function test_a_sales_user_reads_the_product_page_without_any_write_action(): void
    {
        $product = $this->product();

        $response = $this->actingAs($this->salesUser())
            ->get(route('products.show', ['id' => $product->id]));

        $response->assertOk();
        $response->assertSee(__('products.read_only'));
        // Gamme en lecture, GED sans dépôt, pas de duplication.
        $response->assertSee('data-statu="0"', false);
        $response->assertSee('data-can-edit="0"', false);
        $response->assertDontSee(route('products.duplicate', $product->id), false);
    }

    public function test_a_technical_user_keeps_every_write_action(): void
    {
        $product = $this->product();

        $response = $this->actingAs($this->technicalUser())
            ->get(route('products.show', ['id' => $product->id]));

        $response->assertOk();
        $response->assertDontSee(__('products.read_only'));
        $response->assertSee('data-statu="1"', false);
        $response->assertSee('data-can-edit="1"', false);
        $response->assertSee(route('products.duplicate', $product->id), false);
    }

    public function test_a_sales_user_lists_products_and_reads_the_routing(): void
    {
        $product = $this->product();
        $this->operation(['products_id' => $product->id]);
        $user = $this->salesUser();

        $this->actingAs($user)->getJson(route('products.json.list'))->assertOk();
        $this->actingAs($user)
            ->getJson(route('task.manage.json.initial', ['products_id', $product->id, $product->id]))
            ->assertOk()
            ->assertJsonCount(1, 'tasks.technical_cut');
    }

    public function test_the_index_hides_creation_and_merge_without_products_edit(): void
    {
        $user = $this->salesUser();
        $user->givePermissionTo('products-merge');

        $response = $this->actingAs($user)->get(route('products'));

        $response->assertOk();
        $response->assertDontSee(route('products.json.store'), false);
        $response->assertSee("data-can-merge='false'", false);
    }

    // ── Écriture refusée ────────────────────────────────────────────────────

    public function test_a_sales_user_cannot_edit_a_product(): void
    {
        $product = $this->product();

        $this->actingAs($this->salesUser())
            ->post(route('products.update', ['id' => $product->id]), ['label' => 'Modifié'])
            ->assertForbidden();

        $this->assertSame('Original', $product->fresh()->label);
    }

    public function test_a_sales_user_cannot_create_duplicate_merge_or_import_products(): void
    {
        $user = $this->salesUser();
        $user->givePermissionTo('products-merge');
        $master = $this->product();
        $duplicate = $this->product();

        $this->actingAs($user)->postJson(route('products.json.store'), ['code' => 'NEW', 'label' => 'Nouveau'])->assertForbidden();
        $this->actingAs($user)->get(route('products.duplicate', ['id' => $master->id]))->assertForbidden();
        $this->actingAs($user)->postJson(route('products.json.merge', ['master' => $master->id, 'duplicate' => $duplicate->id]))->assertForbidden();
        $this->actingAs($user)->post(route('products.import'))->assertForbidden();

        $this->assertSame(2, Products::count());
    }

    public function test_a_sales_user_cannot_change_custom_fields_of_a_product(): void
    {
        $product = $this->product();

        $this->actingAs($this->salesUser())
            ->post(route('admin.factory.custom.field.value.store.update', ['id' => $product->id, 'type' => 'product']), ['custom_fields' => []])
            ->assertForbidden();
    }

    public function test_a_sales_user_cannot_attach_a_file_to_a_product(): void
    {
        $product = $this->product();

        $this->actingAs($this->salesUser())
            ->postJson(route('files.json.store'), [
                'fileable_type' => 'product',
                'fileable_id' => $product->id,
                'files' => [UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf')],
            ])
            ->assertForbidden();

        $this->assertSame(0, $product->files()->count());
    }

    public function test_a_sales_user_cannot_detach_a_product_file_even_one_they_uploaded(): void
    {
        $product = $this->product();
        $user = $this->salesUser();
        $storage = app(FileStorageService::class);

        // Déposé avant la perte du droit : le propriétaire ne suffit plus.
        $this->actingAs($user);
        $file = $storage->store(UploadedFile::fake()->create('plan.pdf', 12, 'application/pdf'));
        $storage->attach($file, $product, 'plan');
        $this->assertSame($user->id, (int) $file->fresh()->user_id);

        $this->deleteJson(route('files.json.destroy', ['file' => $file->id]), [
            'fileable_type' => 'product',
            'fileable_id' => $product->id,
        ])->assertForbidden();

        $this->assertSame(1, $product->files()->count());
        $this->assertFalse($user->can('delete', File::find($file->id)));
    }

    public function test_a_sales_user_still_reads_the_product_documents(): void
    {
        $product = $this->product();

        $this->actingAs($this->salesUser())
            ->getJson(route('files.json.list', ['fileable_type' => 'product', 'fileable_id' => $product->id]))
            ->assertOk();
    }

    public function test_a_sales_user_cannot_change_the_product_routing(): void
    {
        $product = $this->product();
        $task = $this->operation(['products_id' => $product->id]);
        $user = $this->salesUser();
        $page = ['products_id', $product->id];

        $this->actingAs($user)->postJson(route('task.manage.json.task.store', [...$page, $product->id]), $this->taskPayload())->assertForbidden();
        $this->actingAs($user)->putJson(route('task.manage.json.task.update', [...$page, $task->id]), $this->taskPayload())->assertForbidden();
        $this->actingAs($user)->postJson(route('task.manage.json.task.duplicate', [...$page, $task->id]))->assertForbidden();
        $this->actingAs($user)->deleteJson(route('task.manage.json.task.destroy', [...$page, $task->id]))->assertForbidden();
        $this->actingAs($user)->postJson(route('task.manage.json.subassembly.store', [...$page, $product->id]), [
            'ordre' => 1, 'child_id' => $product->id, 'qty' => 1, 'unit_price' => 1,
        ])->assertForbidden();

        $this->assertSame(1, Task::where('products_id', $product->id)->count());
        $this->assertSame('Découpe', $task->fresh()->label);
        $this->assertSame(0, SubAssembly::count());
    }

    public function test_the_routing_guard_follows_the_task_not_the_type_announced_in_the_url(): void
    {
        $product = $this->product();
        $task = $this->operation(['products_id' => $product->id]);
        $quote = Quotes::factory()->create(['statu' => 1]);

        // Une URL de devis ne déverrouille pas une opération d'article.
        $this->actingAs($this->salesUser())
            ->deleteJson(route('task.manage.json.task.destroy', ['quote_lines_id', $quote->id, $task->id]))
            ->assertForbidden();

        $this->assertNotNull($task->fresh());
    }

    public function test_the_routing_guard_reaches_nested_sub_assemblies_of_a_product(): void
    {
        $product = $this->product();
        $root = SubAssembly::create(['ordre' => 1, 'products_id' => $product->id, 'child_id' => $product->id, 'qty' => 1, 'unit_price' => 1]);
        $child = SubAssembly::create(['ordre' => 1, 'sub_assembly_id' => $root->id, 'child_id' => $product->id, 'qty' => 1, 'unit_price' => 1]);
        $task = $this->operation(['sub_assembly_id' => $child->id]);
        $user = $this->salesUser();

        $this->actingAs($user)->deleteJson(route('task.manage.json.subassembly.destroy', ['sub_assembly_id', $root->id, $child->id]))->assertForbidden();
        $this->actingAs($user)->deleteJson(route('task.manage.json.task.destroy', ['sub_assembly_id', $child->id, $task->id]))->assertForbidden();

        $this->assertNotNull($child->fresh());
        $this->assertNotNull($task->fresh());
    }

    public function test_a_technical_user_changes_the_product_routing(): void
    {
        $product = $this->product();

        $this->actingAs($this->technicalUser())
            ->postJson(route('task.manage.json.task.store', ['products_id', $product->id, $product->id]), $this->taskPayload())
            ->assertCreated();

        $this->assertSame(1, Task::where('products_id', $product->id)->count());
    }

    // ── Devis : reste ouvert ────────────────────────────────────────────────

    public function test_a_sales_user_still_imports_a_routed_product_into_a_quote_line(): void
    {
        $product = $this->product();
        $this->operation(['products_id' => $product->id]);

        $quote = Quotes::factory()->create(['statu' => 1]);
        $line = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'product_id' => $product->id]);
        $user = $this->salesUser();

        $this->actingAs($user)->getJson(route('products.json.search'))->assertOk();
        $this->actingAs($user)
            ->postJson(route('quotes.lines.json.breakdown', ['quoteId' => $quote->id, 'id' => $line->id]))
            ->assertOk();

        $this->assertSame(1, Task::where('quote_lines_id', $line->id)->count());
        // La gamme de la ligne de devis reste modifiable par le commercial.
        $this->actingAs($user)
            ->postJson(route('task.manage.json.task.store', ['quote_lines_id', $quote->id, $line->id]), $this->taskPayload())
            ->assertCreated();
        // L'article, lui, n'a pas bougé.
        $this->assertSame(1, Task::where('products_id', $product->id)->count());
    }

    public function test_creating_a_product_from_a_quote_line_requires_products_edit(): void
    {
        $quote = Quotes::factory()->create(['statu' => 1]);
        $line = QuoteLines::factory()->create(['quotes_id' => $quote->id, 'code' => 'NOUVEAU', 'label' => 'Nouvel article']);
        $before = Products::count();

        $this->actingAs($this->salesUser())
            ->postJson(route('quotes.lines.json.create-product', ['quoteId' => $quote->id, 'id' => $line->id]))
            ->assertForbidden();

        $this->assertSame($before, Products::count());
    }

    // ── Migration ───────────────────────────────────────────────────────────

    public function test_the_migration_gives_products_edit_to_every_existing_holder_of_products_menu(): void
    {
        Permission::where('name', 'products-edit')->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $sales = Role::findOrCreate('Commercial');
        $sales->givePermissionTo('products-menu');
        $other = Role::findOrCreate('Comptable');
        $admin = Role::findOrCreate('Admin');
        $direct = User::factory()->create();
        $direct->givePermissionTo('products-menu');

        $migration = require database_path('migrations/2026_10_30_000060_add_products_edit_permission.php');
        $migration->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($sales->fresh()->hasPermissionTo('products-edit'));
        $this->assertTrue($admin->fresh()->hasPermissionTo('products-edit'));
        $this->assertFalse($other->fresh()->hasPermissionTo('products-edit'));
        $this->assertTrue($direct->fresh()->hasDirectPermission('products-edit'));
    }
}
