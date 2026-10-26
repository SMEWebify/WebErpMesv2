<?php

namespace Tests\Feature;

use App\Models\Companies\Companies;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\Purchases;
use App\Services\PurchaseReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * « En attente de réception » relistait les lignes déjà reçues (receipt_qty <= qty) et son
 * filtre fournisseur en like '%1%' ramenait aussi les fournisseurs 10, 11…
 */
class PurchaseWaitingReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function line(Purchases $purchase, array $attributes): PurchaseLines
    {
        return PurchaseLines::create($attributes + [
            'purchases_id' => $purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '',
            'label' => 'Tôle', 'qty' => 4, 'receipt_qty' => 0, 'selling_price' => 100, 'discount' => 0,
            'unit_price_after_discount' => 0, 'total_selling_price' => 0,
        ]);
    }

    /** @test */
    public function a_fully_received_line_is_no_longer_waiting()
    {
        $purchase = Purchases::factory()->create();
        $waiting  = $this->line($purchase, ['label' => 'À recevoir']);
        $received = $this->line($purchase, ['label' => 'Reçue', 'receipt_qty' => 4]);

        $ids = app(PurchaseReceiptService::class)->getPurchasesWaintingReceiptLines()->pluck('id');

        $this->assertTrue($ids->contains($waiting->id));
        $this->assertFalse($ids->contains($received->id));
    }

    /** @test */
    public function the_supplier_filter_is_exact()
    {
        // Ids 1 et 11 : le like '%1%' les confondait.
        $first = Companies::factory()->create();
        Companies::factory()->count(9)->create();
        $eleventh = Companies::factory()->create();
        $this->assertStringContainsString((string) $first->id, (string) $eleventh->id);

        $mine   = $this->line(Purchases::factory()->create(['companies_id' => $first->id]), []);
        $others = $this->line(Purchases::factory()->create(['companies_id' => $eleventh->id]), []);

        $ids = app(PurchaseReceiptService::class)->getPurchasesWaintingReceiptLines($first->id)->pluck('id');

        $this->assertTrue($ids->contains($mine->id));
        $this->assertFalse($ids->contains($others->id));
    }
}
