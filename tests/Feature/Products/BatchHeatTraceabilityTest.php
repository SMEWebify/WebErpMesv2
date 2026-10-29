<?php

namespace Tests\Feature\Products;

use App\Models\Companies\Companies;
use App\Models\File;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\Task;
use App\Models\Products\Batch;
use App\Models\Products\Products;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Products\StockMove;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Coulée et certificat matière des lots : création du lot à la mise en stock d'une
 * ligne de réception, recherche par coulée, certificat dans la GED du lot, coulée
 * reportée sur la tâche qui consomme le lot et dans l'historique de l'article.
 */
class BatchHeatTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        Storage::fake(config('files.disk'));

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        foreach (['stock-lot-serial-management', 'products-menu'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $this->user->givePermissionTo($permission);
        }

        $service = MethodsServices::factory()->create(['type' => 3]);
        MethodsFamilies::factory()->create(['methods_services_id' => $service->id]);
        MethodsUnits::factory()->create();

        $this->supplier = Companies::factory()->create(['statu_supplier' => 2, 'label' => 'Aciérie du Nord']);
    }

    private function product(int $tracability = 1): Products
    {
        return Products::factory()->create(['tracability_type' => $tracability]);
    }

    private function receiptLine(Products $product, ?string $receivedCertificate = null): PurchaseReceiptLines
    {
        $purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id]);
        $purchaseLine = PurchaseLines::create([
            'purchases_id' => $purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '',
            'product_id' => $product->id, 'label' => 'Tôle S235', 'qty' => 10, 'receipt_qty' => 10,
            'selling_price' => 50, 'discount' => 0, 'unit_price_after_discount' => 50, 'total_selling_price' => 500,
        ]);
        $receipt = PurchaseReceipt::create([
            'code' => 'REC-' . uniqid(), 'label' => 'Réception', 'companies_id' => $this->supplier->id,
            'user_id' => $this->user->id,
        ]);

        return PurchaseReceiptLines::create([
            'purchase_receipt_id' => $receipt->id, 'purchase_line_id' => $purchaseLine->id,
            'ordre' => 10, 'receipt_qty' => 10, 'received_certificate_type' => $receivedCertificate,
        ]);
    }

    private function storeNewStock(PurchaseReceiptLines $line, Products $product, array $lot = [])
    {
        return $this->postJson(route('products.stockline.store.from.purchase.order'), $lot + [
            'code'                     => 'STOCK|' . $line->id . '|' . uniqid(),
            'products_id'              => $product->id,
            'stock_locations_id'       => StockLocation::factory()->create()->id,
            'mini_qty'                 => 10,
            'component_price'          => 50,
            'purchase_receipt_line_id' => $line->id,
            'user_id'                  => $this->user->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Réception
    // ------------------------------------------------------------------

    public function test_stocking_a_receipt_line_with_a_heat_number_creates_the_batch(): void
    {
        $product = $this->product();
        $line = $this->receiptLine($product, '3.1');

        $response = $this->storeNewStock($line, $product, ['heat_number' => 'C-48213', 'supplier_batch_code' => 'L-77'])
            ->assertOk()
            ->assertJsonPath('batch.heat_number', 'C-48213');

        $batch = Batch::sole();
        $this->assertSame('C-48213', $batch->heat_number);
        $this->assertSame('L-77', $batch->supplier_batch_code);
        $this->assertSame('3.1', $batch->certificate_type, 'Le certificat reçu de la ligne est repris.');
        $this->assertSame($this->supplier->id, (int) $batch->companies_id);
        $this->assertSame($line->id, (int) $batch->purchase_receipt_line_id);
        $this->assertSame($product->id, (int) $batch->product_id);

        $move = StockMove::where('purchase_receipt_line_id', $line->id)->sole();
        $this->assertSame($batch->id, (int) $move->batch_id);
        $this->assertSame($batch->code, $move->tracability);
        $this->assertSame($batch->id, $response->json('batch.id'));
    }

    public function test_a_batch_tracked_product_gets_a_batch_even_without_heat_number(): void
    {
        $product = $this->product(2);
        $line = $this->receiptLine($product);

        $this->storeNewStock($line, $product)->assertOk();

        $batch = Batch::sole();
        $this->assertNull($batch->heat_number);
        $this->assertStringStartsWith('BATCH-', $batch->code, 'Le n° de traçabilité généré devient le code du lot.');
        $this->assertSame($batch->id, (int) StockMove::sole()->batch_id);
    }

    public function test_an_untracked_product_without_heat_number_is_stocked_as_before(): void
    {
        $product = $this->product();
        $line = $this->receiptLine($product);

        $this->storeNewStock($line, $product)->assertOk()->assertJsonPath('batch', null);

        $this->assertSame(0, Batch::count());
        $move = StockMove::sole();
        $this->assertNull($move->batch_id);
        $this->assertNull($move->tracability);
    }

    public function test_entry_into_an_existing_stock_line_also_records_the_heat(): void
    {
        $product = $this->product();
        $line = $this->receiptLine($product);
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id]);

        $this->postJson(route('products.stockline.entry.from.purchase.order'), [
            'user_id' => $this->user->id, 'qty' => 10, 'typ_move' => 3, 'component_price' => 50,
            'purchase_receipt_line_id' => $line->id, 'stock_location_products_id' => $slp->id,
            'heat_number' => 'C-90001', 'certificate_type' => '2.2',
        ])->assertOk()->assertJsonPath('batch.heat_number', 'C-90001');

        $batch = Batch::sole();
        $this->assertSame('2.2', $batch->certificate_type);
        $this->assertSame($batch->id, (int) StockMove::where('stock_location_products_id', $slp->id)->sole()->batch_id);
    }

    public function test_an_unknown_certificate_type_is_refused_and_nothing_is_stocked(): void
    {
        $product = $this->product();
        $line = $this->receiptLine($product);

        $this->storeNewStock($line, $product, ['heat_number' => 'C-1', 'certificate_type' => '9.9'])
            ->assertUnprocessable()->assertJsonValidationErrors('certificate_type');

        $this->assertSame(0, Batch::count());
        $this->assertSame(0, StockMove::count());
        $this->assertNull($line->fresh()->stock_location_products_id);
    }

    public function test_the_receipt_lines_list_the_batches_created(): void
    {
        $product = $this->product();
        $line = $this->receiptLine($product);
        $this->storeNewStock($line, $product, ['heat_number' => 'C-555'])->assertOk();

        $this->getJson(route('purchases.receipt.lines.json', ['id' => $line->purchase_receipt_id]))
            ->assertOk()
            ->assertJsonPath('lines.0.batches.0.heat_number', 'C-555');
    }

    // ------------------------------------------------------------------
    // Recherche
    // ------------------------------------------------------------------

    public function test_the_batches_screen_searches_by_heat_number(): void
    {
        $product = $this->product();
        Batch::create(['code' => 'LOT-A', 'product_id' => $product->id, 'heat_number' => 'C-48213']);
        Batch::create(['code' => 'LOT-B', 'product_id' => $product->id, 'heat_number' => 'Z-99999']);
        Batch::create(['code' => 'LOT-C', 'product_id' => $product->id, 'supplier_batch_code' => 'FOURN-4821']);

        $this->get(route('products.batches', ['search' => '4821']))
            ->assertOk()
            ->assertSee('C-48213')
            ->assertSee('LOT-C')
            ->assertDontSee('Z-99999');

        // Un % saisi est cherché tel quel, pas comme joker
        $this->get(route('products.batches', ['search' => '%']))
            ->assertOk()
            ->assertDontSee('C-48213');
    }

    public function test_the_batches_screen_requires_the_lot_permission(): void
    {
        $other = User::factory()->create();
        $batch = Batch::create(['code' => 'LOT-A', 'heat_number' => 'C-1']);

        $this->actingAs($other)->get(route('products.batches'))->assertForbidden();
        $this->actingAs($other)->get(route('products.batches.show', ['batch' => $batch->id]))->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Certificat dans la GED du lot
    // ------------------------------------------------------------------

    public function test_the_certificate_is_stored_in_the_batch_documents_and_readable(): void
    {
        $batch = Batch::create(['code' => 'LOT-A', 'product_id' => $this->product()->id, 'heat_number' => 'C-48213']);

        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'batch',
            'fileable_id'   => $batch->id,
            'files'         => [UploadedFile::fake()->create('certificat-3.1.pdf', 20, 'application/pdf')],
            'role'          => FileRole::CERTIFICATE,
        ])->assertStatus(201);

        $file = File::sole();
        $this->assertSame(1, $batch->certificates()->count());
        Storage::disk(config('files.disk'))->assertExists($file->path);

        $this->getJson(route('files.json.list', ['fileable_type' => 'batch', 'fileable_id' => $batch->id]))
            ->assertOk()
            ->assertJsonPath('files.0.id', $file->id);

        $this->get(route('files.raw', ['file' => $file->id]))->assertOk();

        $this->get(route('products.batches.show', ['batch' => $batch->id]))
            ->assertOk()
            ->assertSee('C-48213')
            ->assertSee('data-fileable-type="batch"', false);

        $this->get(route('products.batches'))->assertOk()->assertSee(__('batches.certificate_attached'));
    }

    public function test_batch_documents_are_closed_to_users_without_the_lot_permission(): void
    {
        $batch = Batch::create(['code' => 'LOT-A']);
        $this->actingAs(User::factory()->create());

        $this->getJson(route('files.json.list', ['fileable_type' => 'batch', 'fileable_id' => $batch->id]))->assertForbidden();
        $this->postJson(route('files.json.store'), [
            'fileable_type' => 'batch',
            'fileable_id'   => $batch->id,
            'files'         => [UploadedFile::fake()->create('certificat.pdf', 20, 'application/pdf')],
        ])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Consommation sur tâche, historique
    // ------------------------------------------------------------------

    public function test_consumption_on_a_task_keeps_the_heat_oldest_batch_first(): void
    {
        $product = $this->product(2);
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id]);
        $first = Batch::create(['code' => 'LOT-1', 'product_id' => $product->id, 'heat_number' => 'C-1']);
        $second = Batch::create(['code' => 'LOT-2', 'product_id' => $product->id, 'heat_number' => 'C-2']);
        StockMove::create(['user_id' => $this->user->id, 'qty' => 3, 'stock_location_products_id' => $slp->id, 'typ_move' => 3, 'batch_id' => $first->id, 'tracability' => 'LOT-1']);
        StockMove::create(['user_id' => $this->user->id, 'qty' => 5, 'stock_location_products_id' => $slp->id, 'typ_move' => 3, 'batch_id' => $second->id, 'tracability' => 'LOT-2']);

        $task = Task::factory()->create([
            'quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'component_id' => $product->id,
        ]);

        $this->postJson(route('production.task.statu.api.good_qty_stock', ['id' => $task->id]), [
            'qty' => 4, 'component_id' => $product->id,
        ])->assertOk()->assertJsonPath('negative_stock', false);

        $out = StockMove::where('task_id', $task->id)->where('typ_move', 2)->orderBy('id')->get();
        $this->assertSame([[$first->id, 3.0], [$second->id, 1.0]],
            $out->map(fn ($m) => [(int) $m->batch_id, (float) $m->qty])->all());
        $this->assertSame(['LOT-1', 'LOT-2'], $out->pluck('tracability')->all());

        $consumed = collect($this->getJson(route('production.task.statu.api.show', ['id' => $task->id]))
            ->assertOk()->json('consumed_batches'));
        $this->assertSame(['C-1', 'C-2'], $consumed->pluck('heat_number')->all());
        $this->assertEquals(1.0, $consumed->firstWhere('id', $second->id)['qty']);
    }

    public function test_consumption_without_batches_is_a_single_move_as_before(): void
    {
        $product = $this->product();
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id]);
        StockMove::create(['user_id' => $this->user->id, 'qty' => 8, 'stock_location_products_id' => $slp->id, 'typ_move' => 3]);
        $task = Task::factory()->create([
            'quote_lines_id' => null, 'order_lines_id' => null, 'products_id' => null, 'sub_assembly_id' => null,
            'component_id' => $product->id,
        ]);

        $this->postJson(route('production.task.statu.api.good_qty_stock', ['id' => $task->id]), [
            'qty' => 4, 'component_id' => $product->id,
        ])->assertOk();

        $out = StockMove::where('task_id', $task->id)->sole();
        $this->assertNull($out->batch_id);
        $this->assertEquals(4.0, (float) $out->qty);
        $this->assertSame([], $this->getJson(route('production.task.statu.api.show', ['id' => $task->id]))->json('consumed_batches'));
    }

    public function test_a_manual_exit_with_the_batch_code_is_linked_to_the_batch(): void
    {
        $product = $this->product(2);
        $slp = StockLocationProducts::factory()->create(['products_id' => $product->id]);
        $batch = Batch::create(['code' => 'LOT-9', 'product_id' => $product->id, 'heat_number' => 'C-9']);

        $move = app(\App\Services\StockService::class)->createStockMove([
            'user_id' => $this->user->id, 'qty' => 1, 'stock_location_products_id' => $slp->id,
            'typ_move' => 6, 'tracability' => 'LOT-9',
        ]);

        $this->assertSame($batch->id, (int) $move->batch_id);
    }

    public function test_the_product_history_shows_the_received_heats(): void
    {
        $product = $this->product();
        Batch::create(['code' => 'LOT-A', 'product_id' => $product->id, 'heat_number' => 'C-48213', 'companies_id' => $this->supplier->id]);

        $items = collect($this->getJson(route('products.json.history', ['id' => $product->id]))->assertOk()->json());
        $batch = $items->firstWhere('type', 'batch');

        $this->assertNotNull($batch);
        $this->assertSame('LOT-A', $batch['code']);
        $this->assertStringContainsString('C-48213', $batch['label']);
        $this->assertStringContainsString('Aciérie du Nord', $batch['label']);
    }
}
