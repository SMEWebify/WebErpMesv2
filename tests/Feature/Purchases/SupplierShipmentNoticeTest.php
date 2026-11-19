<?php

namespace Tests\Feature\Purchases;

use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Purchases;
use App\Models\User;
use App\Services\Files\FileRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Avis de livraison du fournisseur : transporteur, n° de suivi, date d'expédition et BL
 * du fournisseur rangé dans la GED de la réception, en un seul envoi depuis l'écran
 * « En attente de réception ».
 */
class SupplierShipmentNoticeTest extends TestCase
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
        $this->supplier = Companies::factory()->create(['statu_supplier' => 2]);
        // La réception passe les tâches liées à « Finished »
        \App\Models\Planning\Status::create(['title' => 'Finished', 'order' => 1]);
    }

    private function line(): PurchaseLines
    {
        $purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id, 'statu' => 2]);

        return PurchaseLines::create([
            'purchases_id' => $purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '',
            'label' => 'Poinçon rond', 'qty' => 2, 'receipt_qty' => 0, 'selling_price' => 50, 'discount' => 0,
            'unit_price_after_discount' => 50, 'total_selling_price' => 100,
        ]);
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'code'                 => 'RC-TEST-1',
            'label'                => 'RC-TEST-1',
            'companies_id'         => $this->supplier->id,
            'user_id'              => $this->user->id,
            'delivery_note_number' => 'DN-82530425',
        ];
    }

    public function test_receipt_keeps_carrier_tracking_and_files_the_supplier_delivery_note(): void
    {
        $line = $this->line();

        $this->post(route('purchases.waiting.receipt.json.store'), $this->payload([
            'line_ids'           => [$line->id],
            'carrier'            => 'DHL',
            'tracking_number'    => '1156018813',
            'shipped_at'         => '2026-11-18',
            'delivery_note_file' => UploadedFile::fake()->create('delivery-note.pdf', 120, 'application/pdf'),
        ]), ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['redirect']);

        $receipt = PurchaseReceipt::where('code', 'RC-TEST-1')->firstOrFail();
        $this->assertSame('DHL', $receipt->carrier);
        $this->assertSame('1156018813', $receipt->tracking_number);
        $this->assertSame('2026-11-18', $receipt->shipped_at->format('Y-m-d'));
        $this->assertSame('DN-82530425', $receipt->delivery_note_number);

        $file = $receipt->files()->first();
        $this->assertNotNull($file);
        $this->assertSame(FileRole::SUPPLIER_DELIVERY_NOTE, $file->pivot->role);
        $this->assertTrue((bool) $file->pivot->is_primary);
        Storage::disk(config('files.disk'))->assertExists($file->path);
    }

    public function test_receipt_without_notice_is_created_as_before(): void
    {
        $line = $this->line();

        $this->postJson(route('purchases.waiting.receipt.json.store'), $this->payload([
            'line_ids' => [$line->id],
        ]))->assertOk();

        $receipt = PurchaseReceipt::where('code', 'RC-TEST-1')->firstOrFail();
        $this->assertNull($receipt->carrier);
        $this->assertNull($receipt->tracking_number);
        $this->assertNull($receipt->shipped_at);
        $this->assertSame(0, $receipt->files()->count());
    }

    public function test_empty_receipt_accepts_the_notice(): void
    {
        $this->post(route('purchases.waiting.receipt.json.store.empty'), $this->payload([
            'carrier'            => 'UPS',
            'delivery_note_file' => UploadedFile::fake()->image('bl.jpg'),
        ]), ['Accept' => 'application/json'])->assertOk();

        $receipt = PurchaseReceipt::where('code', 'RC-TEST-1')->firstOrFail();
        $this->assertSame('UPS', $receipt->carrier);
        $this->assertSame(1, $receipt->files()->count());
    }

    public function test_unsupported_delivery_note_is_refused_before_anything_is_created(): void
    {
        $line = $this->line();

        $this->post(route('purchases.waiting.receipt.json.store'), $this->payload([
            'line_ids'           => [$line->id],
            'delivery_note_file' => UploadedFile::fake()->create('macro.exe', 10),
        ]), ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('delivery_note_file');

        $this->assertSame(0, PurchaseReceipt::count());
    }

    public function test_receipt_form_updates_the_notice(): void
    {
        $receipt = PurchaseReceipt::create([
            'code' => 'RC-2', 'label' => 'RC-2', 'companies_id' => $this->supplier->id, 'user_id' => $this->user->id,
        ]);

        $this->post(route('receipt.update', ['id' => $receipt->id]), [
            'id'              => $receipt->id,
            'label'           => 'RC-2',
            'statu'           => 1,
            'carrier'         => 'Geodis',
            'tracking_number' => 'GX-42',
            'shipped_at'      => '2026-11-17',
        ])->assertRedirect();

        $receipt->refresh();
        $this->assertSame('Geodis', $receipt->carrier);
        $this->assertSame('GX-42', $receipt->tracking_number);
        $this->assertSame('2026-11-17', $receipt->shipped_at->format('Y-m-d'));
    }
}
