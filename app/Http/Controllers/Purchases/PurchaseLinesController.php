<?php

namespace App\Http\Controllers\Purchases;

use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Admin\Factory;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Products\Products;
use App\Models\Methods\MethodsUnits;
use App\Models\Accounting\AccountingVat;
use App\Services\DocumentCodeGenerator;
use App\Services\PurchaseReceiptService;

class PurchaseLinesController extends Controller
{
    public function __construct(
        protected DocumentCodeGenerator $documentCodeGenerator,
        protected PurchaseReceiptService $purchaseReceiptService,
    ) {}

    // -------------------------------------------------------------------------
    // GET /{purchaseId}/lines/json
    // -------------------------------------------------------------------------

    public function linesForPurchaseJson(int $purchaseId)
    {
        abort_unless(auth()->check(), 403);
        $purchase = Purchases::findOrFail($purchaseId);

        $lines = PurchaseLines::with([
                'unit:id,label,code',
                'VAT:id,label,rate',
                'product:id,code,label',
                'purchaseReceiptLines:id,purchase_line_id,receipt_qty,purchase_receipt_id',
                'purchaseReceiptLines.purchaseReceipt:id,code',
            ])
            ->where('purchases_id', $purchaseId)
            ->orderBy('ordre', 'asc')
            ->get();

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale', 'fr');

        return response()->json([
            'lines'          => $lines->map(fn ($l) => $this->formatLineJson($l, $currency, $locale)),
            'purchase_statu' => $purchase->statu,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /{purchaseId}/lines/json/select-data
    // -------------------------------------------------------------------------

    public function selectDataForPurchaseJson(int $purchaseId)
    {
        abort_unless(auth()->check(), 403);
        $purchase = Purchases::findOrFail($purchaseId);
        $factory  = Factory::first();

        // Filter products by preferred suppliers for this purchase company
        $productsQuery = Products::select('id', 'label', 'code', 'methods_units_id', 'selling_price');
        if ($purchase->companies_id) {
            $productsQuery->whereHas('preferredSuppliers', function ($q) use ($purchase) {
                $q->where('companies_id', $purchase->companies_id);
            });
        }

        return response()->json([
            'products' => $productsQuery->orderBy('code')->get(),
            'units'    => MethodsUnits::select('id', 'label', 'code', 'default')->orderBy('label')->get(),
            'vats'     => AccountingVat::select('id', 'label', 'rate', 'default')->orderBy('rate')->get(),
            'currency' => $factory->curency ?? 'EUR',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /{purchaseId}/lines/json/store
    // -------------------------------------------------------------------------

    public function storeLineJson(int $purchaseId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $purchase = Purchases::findOrFail($purchaseId);
        abort_if($purchase->statu != 1, 403);

        $validated = $request->validate([
            'ordre'              => 'required|numeric|min:1',
            'label'              => 'required|string|max:255',
            'qty'                => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'discount'           => 'required|numeric|min:0|max:100',
            'product_id'         => 'nullable|exists:products,id',
            'code'               => 'nullable|string|max:255',
            'supplier_ref'       => 'nullable|string|max:255',
            'methods_units_id'   => 'nullable|exists:methods_units,id',
            'accounting_vats_id' => 'nullable|exists:accounting_vats,id',
            'delivery_date'      => 'nullable|date',
        ]);

        $defaultVat  = AccountingVat::getDefault();
        $defaultUnit = MethodsUnits::getDefault();

        if (! $defaultVat || ! $defaultUnit) {
            return response()->json(['error' => 'Aucune TVA ou unité par défaut configurée'], 422);
        }

        $line = PurchaseLines::create([
            'purchases_id'       => $purchaseId,
            'ordre'              => $validated['ordre'],
            'code'               => $validated['code'] ?? '',
            'product_id'         => $validated['product_id'] ?? null,
            'label'              => $validated['label'],
            'qty'                => $validated['qty'],
            'supplier_ref'       => $validated['supplier_ref'] ?? null,
            'methods_units_id'   => $validated['methods_units_id'] ?? $defaultUnit->id,
            'selling_price'      => $validated['selling_price'],
            'discount'           => $validated['discount'],
            'accounting_vats_id' => $validated['accounting_vats_id'] ?? $defaultVat->id,
            'delivery_date'      => $validated['delivery_date'] ?? null,
        ]);

        $line->load([
            'unit:id,label,code',
            'VAT:id,label,rate',
            'product:id,code,label',
            'purchaseReceiptLines:id,purchase_line_id,receipt_qty,purchase_receipt_id',
            'purchaseReceiptLines.purchaseReceipt:id,code',
        ]);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))], 201);
    }

    // -------------------------------------------------------------------------
    // PUT /{purchaseId}/lines/json/{id}
    // -------------------------------------------------------------------------

    public function updateLineJson(int $purchaseId, int $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $line = PurchaseLines::where('id', $id)->where('purchases_id', $purchaseId)->firstOrFail();

        $validated = $request->validate([
            'ordre'              => 'required|numeric|min:0',
            'label'              => 'required|string|max:255',
            'qty'                => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'discount'           => 'required|numeric|min:0|max:100',
            'product_id'         => 'nullable|exists:products,id',
            'code'               => 'nullable|string|max:255',
            'supplier_ref'       => 'nullable|string|max:255',
            'methods_units_id'   => 'nullable|exists:methods_units,id',
            'accounting_vats_id' => 'nullable|exists:accounting_vats,id',
            'delivery_date'      => 'nullable|date',
        ]);

        $line->update($validated);
        $line->load([
            'unit:id,label,code',
            'VAT:id,label,rate',
            'product:id,code,label',
            'purchaseReceiptLines:id,purchase_line_id,receipt_qty,purchase_receipt_id',
            'purchaseReceiptLines.purchaseReceipt:id,code',
        ]);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))]);
    }

    // -------------------------------------------------------------------------
    // DELETE /{purchaseId}/lines/json/{id}
    // -------------------------------------------------------------------------

    public function destroyLineJson(int $purchaseId, int $id)
    {
        abort_unless(auth()->check(), 403);
        $line = PurchaseLines::where('id', $id)->where('purchases_id', $purchaseId)->firstOrFail();
        $line->delete();

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /{purchaseId}/lines/json/{id}/duplicate
    // -------------------------------------------------------------------------

    public function duplicateLineJson(int $purchaseId, int $id)
    {
        abort_unless(auth()->check(), 403);
        $line = PurchaseLines::where('id', $id)->where('purchases_id', $purchaseId)->firstOrFail();

        $newLine             = $line->replicate();
        $newLine->ordre      = $line->ordre + 1;
        $newLine->code       = $line->code ? $line->code . '#dup' . $line->id : '';
        $newLine->label      = $line->label . '#dup' . $line->id;
        $newLine->receipt_qty  = 0;
        $newLine->invoiced_qty = 0;
        $newLine->save();

        $newLine->load([
            'unit:id,label,code',
            'VAT:id,label,rate',
            'product:id,code,label',
            'purchaseReceiptLines:id,purchase_line_id,receipt_qty,purchase_receipt_id',
            'purchaseReceiptLines.purchaseReceipt:id,code',
        ]);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($newLine, $currency, config('app.locale'))], 201);
    }

    // -------------------------------------------------------------------------
    // POST /{purchaseId}/lines/json/reorder
    // -------------------------------------------------------------------------

    public function reorderJson(int $purchaseId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $purchase = Purchases::findOrFail($purchaseId);
        abort_if($purchase->statu != 1, 403);

        $request->validate([
            'order'         => 'required|array',
            'order.*.id'    => 'required|integer',
            'order.*.ordre' => 'required|integer|min:1',
        ]);

        foreach ($request->order as $item) {
            PurchaseLines::where('id', $item['id'])
                ->where('purchases_id', $purchaseId)
                ->update(['ordre' => $item['ordre']]);
        }

        return response()->json(['success' => true]);
    }

    // -------------------------------------------------------------------------
    // POST /{purchaseId}/lines/json/store-receipt
    // -------------------------------------------------------------------------

    public function storeReceiptJson(int $purchaseId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $purchase = Purchases::findOrFail($purchaseId);
        abort_if($purchase->statu != 1, 403);

        $request->validate([
            'line_ids'   => 'required|array|min:1',
            'line_ids.*' => 'integer|exists:purchase_lines,id',
        ]);

        $lastReceipt    = PurchaseReceipt::latest()->first();
        $purchaseReceiptId = $lastReceipt ? $lastReceipt->id : 0;
        $code           = $this->documentCodeGenerator->generateDocumentCode('purchase-receipt', $purchaseReceiptId);

        $receiptData = [
            'code'        => $code,
            'label'       => $code,
            'companies_id' => $purchase->companies_id,
            'user_id'     => Auth::id(),
        ];

        // Build data array in the format expected by PurchaseReceiptService
        $data = [];
        foreach ($request->line_ids as $lineId) {
            $data[$lineId] = ['purchase_line_id' => $lineId];
        }

        try {
            $receipt = $this->purchaseReceiptService->createPurchaseReceipt($data, $receiptData);
            return response()->json([
                'redirect' => route('purchase.receipts.show', ['id' => $receipt->id]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // -------------------------------------------------------------------------
    // Private helper
    // -------------------------------------------------------------------------

    private function formatLineJson(PurchaseLines $l, string $currency, string $locale): array
    {
        $rawPrice = (float) ($l->getRawOriginal('selling_price') ?? 0);
        $total    = $rawPrice * (float) $l->qty * (1 - ((float) ($l->discount ?? 0) / 100));

        return [
            'id'                 => $l->id,
            'purchases_id'       => $l->purchases_id,
            'ordre'              => $l->ordre,
            'code'               => $l->code,
            'supplier_ref'       => $l->supplier_ref,
            'product_id'         => $l->product_id,
            'product_code'       => $l->product?->code,
            'product_url'        => $l->product_id ? route('products.show', ['id' => $l->product_id]) : null,
            'label'              => $l->label,
            'qty'                => (float) $l->qty,
            'receipt_qty'        => (float) ($l->receipt_qty ?? 0),
            'invoiced_qty'       => (float) ($l->invoiced_qty ?? 0),
            'methods_units_id'   => $l->methods_units_id,
            'unit_label'         => $l->unit?->label,
            'unit_code'          => $l->unit?->code,
            'selling_price'      => $rawPrice,
            'formatted_price'    => Number::currency($rawPrice, $currency, $locale),
            'formatted_total'    => Number::currency($total, $currency, $locale),
            'discount'           => $l->discount,
            'accounting_vats_id' => $l->accounting_vats_id,
            'vat_label'          => $l->VAT?->label,
            'vat_rate'           => $l->VAT?->rate,
            'delivery_date'      => $l->delivery_date,
            'receipt_lines'      => ($l->relationLoaded('purchaseReceiptLines') ? $l->purchaseReceiptLines : collect())->map(fn ($rl) => [
                'id'          => $rl->id,
                'qty'         => $rl->receipt_qty,
                'receipt_code' => $rl->purchaseReceipt?->code,
                'receipt_url'  => $rl->purchase_receipt_id ? route('purchase.receipts.show', ['id' => $rl->purchase_receipt_id]) : null,
            ]),
        ];
    }
}
