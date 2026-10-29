<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Products\Batch;
use App\Models\Products\Products;
use App\Models\Purchases\PurchaseReceiptLines;

class BatchService
{
    /**
     * Champs du lot saisis à la mise en stock d'une ligne de réception.
     */
    public const RECEIPT_FIELDS = ['heat_number', 'supplier_batch_code', 'certificate_type'];

    public function createBatch($productId, $productionDate = null, $expirationDate = null)
    {
        return Batch::create([
            'code' => Str::uuid(),
            'product_id' => $productId,
            'production_date' => $productionDate,
            'expiration_date' => $expirationDate,
        ]);
    }

    public function closeBatch(Batch $batch)
    {
        $batch->update(['closed_at' => now()]);
        return $batch;
    }

    /**
     * Règles de validation des champs de lot à la réception (tous facultatifs).
     *
     * @return array<string, mixed>
     */
    public static function receiptRules(): array
    {
        return [
            'heat_number'         => 'nullable|string|max:255',
            'supplier_batch_code' => 'nullable|string|max:255',
            'certificate_type'    => 'nullable|string|in:' . implode(',', config('purchase_quality.certificate_types', [])),
        ];
    }

    /**
     * Un lot est créé à la réception si l'article est suivi par lot
     * (tracability_type = 2) ou si une coulée / un lot fournisseur est saisi.
     * Sinon rien ne change : mouvement de stock sans lot, comme avant.
     */
    public function receiptNeedsBatch(?Products $product, array $data): bool
    {
        if ($product && (int) $product->tracability_type === 2) {
            return true;
        }

        return trim((string) ($data['heat_number'] ?? '')) !== ''
            || trim((string) ($data['supplier_batch_code'] ?? '')) !== '';
    }

    /**
     * Crée le lot d'une ligne de réception : fournisseur repris de la réception,
     * certificat repris de la ligne (`received_certificate_type`) s'il n'est pas saisi.
     * Le code du lot sert aussi de `tracability` sur le mouvement de stock.
     */
    public function createFromReceiptLine(PurchaseReceiptLines $line, int $productId, array $data, ?string $code = null): Batch
    {
        $clean = static fn ($value) => ($value = trim((string) $value)) === '' ? null : $value;

        return Batch::create([
            'code'                     => $code ?? app(StockService::class)->generateBatchNumber(),
            'product_id'               => $productId,
            'heat_number'              => $clean($data['heat_number'] ?? null),
            'supplier_batch_code'      => $clean($data['supplier_batch_code'] ?? null),
            'certificate_type'         => $clean($data['certificate_type'] ?? null) ?? $line->received_certificate_type,
            'companies_id'             => $line->purchaseReceipt?->companies_id,
            'purchase_receipt_line_id' => $line->id,
        ]);
    }
}
