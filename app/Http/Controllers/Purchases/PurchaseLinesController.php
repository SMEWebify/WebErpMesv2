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
use App\Models\Methods\MethodsUnits;
use App\Models\Accounting\AccountingVat;
use App\Services\DocumentCodeGenerator;
use App\Services\PurchaseReceiptService;
use App\Services\SelectDataService;
use App\Services\Purchases\PurchaseCriticality;

class PurchaseLinesController extends Controller
{
    public function __construct(
        protected DocumentCodeGenerator $documentCodeGenerator,
        protected PurchaseReceiptService $purchaseReceiptService,
        protected SelectDataService $selectDataService,
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

        $factory  = app('Factory');
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
        Purchases::findOrFail($purchaseId);
        $factory  = app('Factory');

        // Products are searched on demand (products.json.search, filtered on the purchase
        // supplier) rather than sent in full: the catalogue can exceed PHP memory.
        return response()->json([
            'units'    => $this->selectDataService->getUnitsSelect(),
            'vats'     => $this->selectDataService->getVATSelect(),
            'currency' => $factory->curency ?? 'EUR',
            'criticality' => [
                'options' => PurchaseCriticality::options(),
                'labels'  => [
                    'title'             => __('purchase_quality.criticality'),
                    'certificate_type'  => __('purchase_quality.certificate_type'),
                    'construction_code' => __('purchase_quality.construction_code'),
                    'nuclear_level'     => __('purchase_quality.nuclear_level'),
                    'none'              => __('purchase_quality.none'),
                ],
            ],
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
            'ind'                => 'nullable|string|max:50',
            'supplier_ref'       => 'nullable|string|max:255',
            'methods_units_id'   => 'nullable|exists:methods_units,id',
            'accounting_vats_id' => 'nullable|exists:accounting_vats,id',
            'delivery_date'      => 'nullable|date',
        ] + PurchaseCriticality::validationRules());

        $defaultVat  = AccountingVat::getDefault();
        $defaultUnit = MethodsUnits::getDefault();

        if (! $defaultVat || ! $defaultUnit) {
            return response()->json(['error' => 'Aucune TVA ou unité par défaut configurée'], 422);
        }

        $line = PurchaseLines::create([
            'purchases_id'       => $purchaseId,
            'tasks_id'           => 0, // ligne sans tâche (MySQL non strict mettait déjà 0)
            'ordre'              => $validated['ordre'],
            'code'               => $validated['code'] ?? '',
            'ind'                => $validated['ind'] ?? null,
            'product_id'         => $validated['product_id'] ?? null,
            'label'              => $validated['label'],
            'qty'                => $validated['qty'],
            'supplier_ref'       => $validated['supplier_ref'] ?? null,
            'methods_units_id'   => $validated['methods_units_id'] ?? $defaultUnit->id,
            'selling_price'      => $validated['selling_price'],
            'discount'           => $validated['discount'],
            'accounting_vats_id' => $validated['accounting_vats_id'] ?? $defaultVat->id,
            'delivery_date'      => $validated['delivery_date'] ?? null,
        ] + $this->criticalityFrom($request, $validated)
          + PurchaseLines::netPrices($validated['selling_price'], $validated['qty'], $validated['discount']));

        $line->load([
            'unit:id,label,code',
            'VAT:id,label,rate',
            'product:id,code,label',
            'purchaseReceiptLines:id,purchase_line_id,receipt_qty,purchase_receipt_id',
            'purchaseReceiptLines.purchaseReceipt:id,code',
        ]);

        $factory  = app('Factory');
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
            'ind'                => 'nullable|string|max:50',
            'supplier_ref'       => 'nullable|string|max:255',
            'methods_units_id'   => 'nullable|exists:methods_units,id',
            'accounting_vats_id' => 'nullable|exists:accounting_vats,id',
            'delivery_date'      => 'nullable|date',
        ] + PurchaseCriticality::validationRules(PurchaseCriticality::of($line)));

        // Criticité absente de la requête : laissée telle quelle (appelants d'avant la fonctionnalité)
        $validated = array_diff_key($validated, array_flip(PurchaseCriticality::FIELDS)) + $this->criticalityFrom($request, $validated);

        $line->update($validated + PurchaseLines::netPrices($validated['selling_price'], $validated['qty'], $validated['discount']));
        $line->load([
            'unit:id,label,code',
            'VAT:id,label,rate',
            'product:id,code,label',
            'purchaseReceiptLines:id,purchase_line_id,receipt_qty,purchase_receipt_id',
            'purchaseReceiptLines.purchaseReceipt:id,code',
        ]);

        $factory  = app('Factory');
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

        $factory  = app('Factory');
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
            // rate=1 : la fiche propose la notation rapide du fournisseur
            return response()->json([
                'redirect' => route('purchase.receipts.show', ['id' => $receipt->id, 'rate' => 1]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // -------------------------------------------------------------------------
    // Private helper
    // -------------------------------------------------------------------------

    /**
     * Champs de criticité envoyés par l'écran, vides compris (l'utilisateur a retiré
     * l'exigence). Un champ absent n'est pas repris : à la création, la ligne le
     * reçoit alors de l'article (PurchaseLines::booted).
     */
    private function criticalityFrom(Request $request, array $validated): array
    {
        $values = [];
        foreach (PurchaseCriticality::FIELDS as $field) {
            if ($request->exists($field)) {
                $values[$field] = $validated[$field] ?? null;
            }
        }

        return $values;
    }

    private function formatLineJson(PurchaseLines $l, string $currency, string $locale): array
    {
        $rawPrice = (float) ($l->getRawOriginal('selling_price') ?? 0);
        $total    = $rawPrice * (float) $l->qty * (1 - ((float) ($l->discount ?? 0) / 100));

        return [
            'id'                 => $l->id,
            'purchases_id'       => $l->purchases_id,
            'ordre'              => $l->ordre,
            'code'               => $l->code,
            'ind'                => $l->ind,
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
            'certificate_type'   => $l->certificate_type,
            'construction_code'  => $l->construction_code,
            'nuclear_level'      => $l->nuclear_level,
            'receipt_lines'      => ($l->relationLoaded('purchaseReceiptLines') ? $l->purchaseReceiptLines : collect())->map(fn ($rl) => [
                'id'          => $rl->id,
                'qty'         => $rl->receipt_qty,
                'receipt_code' => $rl->purchaseReceipt?->code,
                'receipt_url'  => $rl->purchase_receipt_id ? route('purchase.receipts.show', ['id' => $rl->purchase_receipt_id]) : null,
            ]),
        ];
    }
}
