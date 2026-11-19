<?php

namespace Tests\Feature\Products;

use App\Models\Companies\Companies;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Référence de l'article chez chaque fournisseur (products_preferred_suppliers) :
 * saisie sur la fiche article, reprise sur les lignes de commande d'achat,
 * trouvée par la recherche d'articles.
 */
class ProductSupplierReferenceTest extends TestCase
{
    use RefreshDatabase;

    private Companies $supplier;
    private Companies $otherSupplier;
    private Products $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);

        Permission::findOrCreate('products-menu');
        Permission::findOrCreate('products-edit');
        $user = User::factory()->create();
        $user->givePermissionTo(['products-menu', 'products-edit']);
        $this->actingAs($user);

        MethodsUnits::factory()->create();
        MethodsFamilies::factory()->create();

        $this->supplier      = Companies::factory()->create(['statu_supplier' => 2]);
        $this->otherSupplier = Companies::factory()->create(['statu_supplier' => 2]);
        $this->product       = Products::factory()->create(['code' => 'TOLE-DC01-2', 'label' => 'Tôle DC01 2 mm']);
    }

    private function line(Purchases $purchase, array $attributes = []): PurchaseLines
    {
        return PurchaseLines::create($attributes + [
            'purchases_id' => $purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => 'TOLE-DC01-2',
            'product_id' => $this->product->id, 'label' => 'Tôle DC01 2 mm', 'qty' => 4,
            'selling_price' => 100, 'discount' => 0, 'receipt_qty' => 0,
            'unit_price_after_discount' => 100, 'total_selling_price' => 400,
        ]);
    }

    public function test_supplier_is_added_with_its_reference(): void
    {
        $this->post(route('products.supplier.create'), [
            'product_id' => $this->product->id, 'companies_id' => $this->supplier->id, 'supplier_reference' => ' AO-221 ',
        ])->assertRedirect();

        $this->assertSame('AO-221', $this->product->preferredSuppliers()->first()->pivot->supplier_reference);
    }

    public function test_posting_an_existing_supplier_updates_its_reference_without_duplicate(): void
    {
        $this->product->preferredSuppliers()->attach($this->supplier->id, ['supplier_reference' => 'OLD']);

        $this->post(route('products.supplier.create'), [
            'product_id' => $this->product->id, 'companies_id' => $this->supplier->id, 'supplier_reference' => 'NEW-1',
        ])->assertRedirect();

        $this->assertSame(1, DB::table('products_preferred_suppliers')->where('product_id', $this->product->id)->count());
        $this->assertSame('NEW-1', $this->product->preferredSuppliers()->first()->pivot->supplier_reference);
    }

    public function test_reference_requires_products_edit(): void
    {
        $reader = User::factory()->create();
        $reader->givePermissionTo('products-menu');

        $this->actingAs($reader)->post(route('products.supplier.create'), [
            'product_id' => $this->product->id, 'companies_id' => $this->supplier->id, 'supplier_reference' => 'X',
        ])->assertForbidden();

        $this->assertSame(0, DB::table('products_preferred_suppliers')->count());
    }

    public function test_purchase_line_takes_the_reference_of_the_order_supplier(): void
    {
        $this->product->preferredSuppliers()->attach([
            $this->supplier->id      => ['supplier_reference' => 'AO-221'],
            $this->otherSupplier->id => ['supplier_reference' => 'ZZ-9'],
        ]);
        $purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id, 'statu' => 1]);

        $this->assertSame('AO-221', $this->line($purchase)->supplier_ref);
    }

    public function test_a_typed_reference_is_never_replaced(): void
    {
        $this->product->preferredSuppliers()->attach($this->supplier->id, ['supplier_reference' => 'AO-221']);
        $purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id, 'statu' => 1]);

        $this->assertSame('SAISIE-1', $this->line($purchase, ['supplier_ref' => 'SAISIE-1'])->supplier_ref);
    }

    public function test_line_stays_empty_without_reference_for_this_supplier(): void
    {
        $this->product->preferredSuppliers()->attach($this->otherSupplier->id, ['supplier_reference' => 'ZZ-9']);
        $purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id, 'statu' => 1]);

        $this->assertNull($this->line($purchase)->supplier_ref);
        $this->assertNull($this->line($purchase, ['product_id' => null])->supplier_ref);
    }

    public function test_search_finds_a_product_by_supplier_reference_and_returns_it(): void
    {
        $this->product->preferredSuppliers()->attach([
            $this->supplier->id      => ['supplier_reference' => 'AO-221'],
            $this->otherSupplier->id => ['supplier_reference' => 'ZZ-9'],
        ]);
        Products::factory()->create(['code' => 'VIS-M6', 'label' => 'Vis M6']);

        // Sans fournisseur : référence de n'importe quel fournisseur
        $this->assertSame(['TOLE-DC01-2'], array_column(
            $this->getJson(route('products.json.search', ['q' => 'zz-9']))->assertOk()->json('products'), 'code'));

        // Fournisseur filtré : sa référence seulement, renvoyée pour préremplir la ligne
        $this->assertSame([], $this->getJson(route('products.json.search', ['q' => 'ZZ-9', 'supplier_id' => $this->supplier->id]))->json('products'));

        $found = $this->getJson(route('products.json.search', ['q' => 'AO-2', 'supplier_id' => $this->supplier->id]))->assertOk()->json('products');
        $this->assertSame('TOLE-DC01-2', $found[0]['code']);
        $this->assertSame('AO-221', $found[0]['supplier_reference']);
    }
}
