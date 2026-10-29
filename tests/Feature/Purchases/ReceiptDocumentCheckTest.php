<?php

namespace Tests\Feature\Purchases;

use App\Models\Companies\Companies;
use App\Models\Products\StockMove;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\Purchases;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Services\Purchases\ReceiptDocumentCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Visa documentaire des lignes de réception : permission, écart entre certificat exigé
 * et reçu, décision finale (physique ET documentaire), NC fournisseur, liste d'attente.
 */
class ReceiptDocumentCheckTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Companies $supplier;
    private Purchases $purchase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckUserRole::class,
            \App\Http\Middleware\CheckFactory::class,
            \App\Http\Middleware\CheckTaskStatus::class,
        ]);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Permission::firstOrCreate(['name' => 'reception-document-check', 'guard_name' => 'web']);

        $this->supplier = Companies::factory()->create(['statu_supplier' => 2]);
        $this->purchase = Purchases::factory()->create(['companies_id' => $this->supplier->id]);
    }

    private function receiptLine(?string $required, ?string $received = null, array $attributes = []): PurchaseReceiptLines
    {
        $purchaseLine = PurchaseLines::create([
            'purchases_id' => $this->purchase->id, 'tasks_id' => 0, 'ordre' => 1, 'code' => '',
            'label' => 'Bride RCC-M', 'qty' => 4, 'receipt_qty' => 4, 'selling_price' => 100, 'discount' => 0,
            'unit_price_after_discount' => 100, 'total_selling_price' => 400,
            'certificate_type' => $required,
        ]);

        $receipt = PurchaseReceipt::create([
            'code' => 'REC-' . uniqid(), 'label' => 'Réception', 'companies_id' => $this->supplier->id,
            'user_id' => $this->user->id,
        ]);

        return PurchaseReceiptLines::create($attributes + [
            'purchase_receipt_id' => $receipt->id, 'purchase_line_id' => $purchaseLine->id,
            'ordre' => 10, 'receipt_qty' => 4, 'received_certificate_type' => $received,
        ]);
    }

    private function check(PurchaseReceiptLines $line, array $payload)
    {
        return $this->postJson(route('purchase.receipts.lines.document-check', ['purchaseReceiptLine' => $line->id]), $payload);
    }

    private function allow(): void
    {
        $this->user->givePermissionTo('reception-document-check');
    }

    /** @test */
    public function the_visa_is_refused_without_the_permission()
    {
        $line = $this->receiptLine('3.1', '3.1');

        $this->check($line, ['document_check_result' => 'compliant'])->assertForbidden();

        $line->refresh();
        $this->assertNull($line->document_check_result);
        $this->assertNull($line->document_check_by);
    }

    /** @test */
    public function the_visa_is_recorded_with_its_author_and_date()
    {
        $this->allow();
        $line = $this->receiptLine('3.1', '3.1');

        $this->check($line, ['document_check_result' => 'compliant', 'document_check_comment' => 'Certificat lu'])
            ->assertOk()
            ->assertJsonPath('line.document_check_status', 'compliant')
            ->assertJsonPath('line.certificate_gap', null);

        $line->refresh();
        $this->assertSame('compliant', $line->document_check_result);
        $this->assertSame($this->user->id, (int) $line->document_check_by);
        $this->assertNotNull($line->document_check_at);
        $this->assertSame('Certificat lu', $line->document_check_comment);
    }

    /** @test */
    public function a_certificate_type_gap_is_reported()
    {
        $insufficient = $this->receiptLine('3.1', '2.2');
        $missing      = $this->receiptLine('3.1', null);
        $higher       = $this->receiptLine('2.2', '3.1');

        $lines = collect($this->getJson(route('purchases.receipt.lines.json', ['id' => $insufficient->purchase_receipt_id]))
            ->assertOk()->json('lines'));

        $this->assertSame(
            ['code' => 'insufficient', 'required' => '3.1', 'received' => '2.2'],
            $lines->firstWhere('id', $insufficient->id)['certificate_gap']
        );
        $this->assertSame('missing', ReceiptDocumentCheck::gapFor($missing)['code']);
        $this->assertNull(ReceiptDocumentCheck::gapFor($higher), 'Un certificat de rang supérieur couvre l\'exigence.');
        $this->assertSame('different', ReceiptDocumentCheck::certificateGap('3.1', 'X')['code']);
    }

    /** @test */
    public function a_compliant_visa_despite_a_gap_requires_a_comment()
    {
        $this->allow();
        $line = $this->receiptLine('3.1', '2.2');

        $this->check($line, ['document_check_result' => 'compliant'])
            ->assertUnprocessable()->assertJsonValidationErrors('document_check_comment');
        $this->assertNull($line->fresh()->document_check_result);

        $this->check($line, ['document_check_result' => 'compliant', 'document_check_comment' => 'Dérogation client D-12'])
            ->assertOk()
            ->assertJsonPath('line.certificate_gap.code', 'insufficient');
    }

    /** @test */
    public function the_received_certificate_can_be_corrected_during_the_visa()
    {
        $this->allow();
        $line = $this->receiptLine('3.1', null);

        $this->check($line, ['document_check_result' => 'compliant', 'received_certificate_type' => '3.1'])
            ->assertOk()->assertJsonPath('line.certificate_gap', null);

        $this->assertSame('3.1', $line->fresh()->received_certificate_type);

        $this->check($line, ['document_check_result' => 'compliant', 'received_certificate_type' => '9.9'])
            ->assertUnprocessable()->assertJsonValidationErrors('received_certificate_type');
    }

    /** @test */
    public function the_final_decision_needs_both_physical_and_document_compliance()
    {
        $line = $this->receiptLine('3.1', '3.1', ['accepted_qty' => 4, 'rejected_qty' => 0]);

        $this->assertSame(ReceiptDocumentCheck::DECISION_PENDING, ReceiptDocumentCheck::decision($line));

        $line->forceFill(['document_check_result' => 'compliant'])->save();
        $this->assertSame(ReceiptDocumentCheck::DECISION_ACCEPTED, ReceiptDocumentCheck::decision($line->fresh()));

        $line->forceFill(['document_check_result' => 'non_compliant'])->save();
        $this->assertSame(ReceiptDocumentCheck::DECISION_REJECTED, ReceiptDocumentCheck::decision($line->fresh()));

        // Sans certificat exigé : seul le contrôle physique décide
        $plain = $this->receiptLine(null, null, ['accepted_qty' => 4]);
        $this->assertSame(ReceiptDocumentCheck::DECISION_ACCEPTED, ReceiptDocumentCheck::decision($plain));

        $rejected = $this->receiptLine('3.1', '3.1', ['accepted_qty' => 2, 'rejected_qty' => 2]);
        $rejected->forceFill(['document_check_result' => 'compliant'])->save();
        $this->assertSame(ReceiptDocumentCheck::DECISION_REJECTED, ReceiptDocumentCheck::decision($rejected->fresh()));
    }

    /** @test */
    public function a_non_compliant_visa_can_create_a_supplier_nc()
    {
        $this->allow();
        $line = $this->receiptLine('3.1', '2.2');

        $this->check($line, [
            'document_check_result' => 'non_compliant', 'document_check_comment' => 'Certificat 2.2 seulement',
            'create_non_conformity' => true,
        ])->assertOk();

        $nc = QualityNonConformity::sole();
        $this->assertSame($nc->id, (int) $line->fresh()->quality_non_conformity_id);
        $this->assertSame(2, (int) $nc->type, 'NC externe (fournisseur).');
        $this->assertSame($this->supplier->id, (int) $nc->companie_id);
        $this->assertStringContainsString('Certificat 2.2 seulement', $nc->failure_comment);

        // Un second visa ne crée pas de seconde NC
        $this->check($line, ['document_check_result' => 'non_compliant', 'create_non_conformity' => true])->assertOk();
        $this->assertSame(1, QualityNonConformity::count());
    }

    /** @test */
    public function the_visa_does_not_touch_stock()
    {
        $this->allow();
        $line = $this->receiptLine('3.1', '2.2');

        $this->check($line, ['document_check_result' => 'non_compliant'])->assertOk();

        $this->assertSame(0, StockMove::count());
        $this->assertNull($line->fresh()->stock_location_products_id);
    }

    /** @test */
    public function changing_the_received_certificate_on_inspection_clears_the_visa()
    {
        $this->allow();
        $line = $this->receiptLine('3.1', '3.1');
        $this->check($line, ['document_check_result' => 'compliant'])->assertOk();

        // Même certificat : le visa reste
        $this->postJson(route('purchase.receipts.lines.update', ['purchaseReceiptLine' => $line->id]), [
            'accepted_qty' => 4, 'rejected_qty' => 0, 'received_certificate_type' => '3.1',
        ])->assertOk();
        $this->assertSame('compliant', $line->fresh()->document_check_result);

        $this->postJson(route('purchase.receipts.lines.update', ['purchaseReceiptLine' => $line->id]), [
            'accepted_qty' => 4, 'rejected_qty' => 0, 'received_certificate_type' => '2.2',
        ])->assertOk()->assertJsonPath('line.document_check_status', 'pending');

        $line->refresh();
        $this->assertSame('2.2', $line->received_certificate_type);
        $this->assertNull($line->document_check_result);
        $this->assertNull($line->document_check_by);
    }

    /** @test */
    public function the_awaiting_list_shows_only_lines_needing_a_visa()
    {
        $this->allow();
        $awaiting = $this->receiptLine('3.1', null);
        $received = $this->receiptLine(null, '3.1');
        $legacy   = $this->receiptLine(null, null);
        $done     = $this->receiptLine('3.1', '3.1');
        $this->check($done, ['document_check_result' => 'compliant'])->assertOk();

        $ids = collect($this->getJson(route('purchases.receipt.document-check.json'))->assertOk()->json('data'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$awaiting->id, $received->id], $ids);
        $this->assertNotContains($legacy->id, $ids);

        $compliant = collect($this->getJson(route('purchases.receipt.document-check.json', ['filter' => 'compliant']))->json('data'))->pluck('id')->all();
        $this->assertSame([$done->id], $compliant);

        $this->get(route('purchases.receipt.document-check'))->assertOk();
    }
}
