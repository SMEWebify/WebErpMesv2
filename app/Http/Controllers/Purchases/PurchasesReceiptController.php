<?php

namespace App\Http\Controllers\Purchases;

use Illuminate\Http\Request;
use App\Traits\NextPreviousTrait;
use App\Models\Purchases\Purchases;
use App\Models\Products\Products;
use App\Models\Accounting\AccountingVat;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\DocumentCodeGenerator;
use App\Services\PurchaseKPIService;
use App\Services\QualityNonConformityService;
use Illuminate\Support\Facades\Auth;
use App\Models\Products\StockLocation;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Quality\QualityNonConformity;
use App\Models\Products\StockLocationProducts;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest;

class PurchasesReceiptController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;
    protected $purchaseOrderService;
    protected $qualityNonConformityService;
    protected $documentCodeGenerator;

    /**
     * Constructor to initialize services.
     *
     * @param SelectDataService $SelectDataService
     * @param PurchaseKPIService $purchaseKPIService
     * @param CustomFieldService $customFieldService
     */
    public function __construct(
            SelectDataService $SelectDataService,
            PurchaseKPIService $purchaseKPIService,
            CustomFieldService $customFieldService,
            QualityNonConformityService $qualityNonConformityService,
            DocumentCodeGenerator $documentCodeGenerator,
        ){
        $this->SelectDataService = $SelectDataService;
        $this->purchaseKPIService = $purchaseKPIService;
        $this->customFieldService = $customFieldService;
        $this->qualityNonConformityService = $qualityNonConformityService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }
    
    
    /**
     * Display the waiting receipt view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function waintingReceipt()
    {    
        return view('purchases/purchases-wainting-receipt');
    }

    /**
     * Display a specific purchase receipt.
     *
     * @param PurchaseReceipt $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showReceipt(PurchaseReceipt $id)
    {   
        
        $StockLocationList = StockLocation::all();
        $StockLocationProductList = StockLocationProducts::all();
        $userSelect = $this->SelectDataService->getUsers();
        $nonConformities = $this->SelectDataService->getQualityNonConformity();
        $productSelect = $this->SelectDataService->getProductsSelect();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchaseReceipt(), $id->id);

        $averageReceptionDelay = PurchaseReceiptLines::join('purchase_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
                                                    ->where('purchase_receipt_lines.purchase_receipt_id', $id->id) // Filtrer par bon de réception spécifique
                                                    ->selectRaw('AVG(DATEDIFF(purchase_receipt_lines.created_at, purchase_lines.created_at)) AS avg_reception_delay')
                                                    ->first();

        return view('purchases/purchases-receipt-show', [
            'PurchaseReceipt' => $id,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'StockLocationList' =>  $StockLocationList,
            'StockLocationProductList' =>  $StockLocationProductList,
            'averageReceptionDelay' => $averageReceptionDelay->avg_reception_delay,
            'userSelect' => $userSelect,
            'nonConformities' => $nonConformities,
            'productSelect' => $productSelect,
        ]);
    }

    /**
     * Update a purchase order.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchase(UpdatePurchaseRequest $request)
    {
        $Purchases = Purchases::find($request->id);
        $Purchases->label=$request->label;
        $Purchases->companies_id=$request->companies_id;
        $Purchases->companies_contacts_id=$request->companies_contacts_id;
        $Purchases->companies_addresses_id=$request->companies_addresses_id;
        $Purchases->comment=$request->comment;
        $Purchases->save();
        
        return redirect()->route('purchases.show', ['id' =>  $Purchases->id])->with('success', 'Successfully updated purchase order');
    }

    /**
     * Update a purchase receipt.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseReceipt(UpdatePurchaseReceiptRequest $request)
    {
        $PurchaseReceipt = PurchaseReceipt::find($request->id);
        $PurchaseReceipt->label=$request->label;
        $PurchaseReceipt->statu=$request->statu;
        $PurchaseReceipt->delivery_note_number=$request->delivery_note_number;
        $PurchaseReceipt->comment=$request->comment;
        $PurchaseReceipt->save();
        
        return redirect()->route('purchase.receipts.show', ['id' =>  $PurchaseReceipt->id])->with('success', 'Successfully updated reciept');
    }

    /**
     * Update the reception control of a purchase receipt.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id The ID of the purchase receipt.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateReceiptControl(Request $request, $id)
    {
        $purchaseReceipt = PurchaseReceipt::findOrFail($id);

        $purchaseReceipt->reception_controlled = 1;
        $purchaseReceipt->reception_control_date = now(); 
        $purchaseReceipt->reception_control_user_id = Auth::id(); 
        $purchaseReceipt->save();

        return redirect()->back()->with('success', 'Contrôle de réception mis à jour avec succès.');
    }

    /**
     * Display the receipt view with data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function receipt()
    {
        $data['PurchaseReciepCountDataRate'] = $this->purchaseKPIService->getPurchaseReciepCountDataRate();
        $data['purchaseReceiptMonthlyRecap'] = $this->purchaseKPIService->getPurchaseReceiptMonthlyRecap();
        return view('purchases/purchases-receipt')->with('data',$data);
    }

    /**
     * Update inspection related data for a purchase receipt line.
     */
    public function updateLineInspection(Request $request, PurchaseReceiptLines $purchaseReceiptLine)
    {
        $validated = $request->validate([
            'inspected_by' => 'nullable|exists:users,id',
            'inspection_date' => 'nullable|date',
            'accepted_qty' => 'nullable|integer|min:0',
            'rejected_qty' => 'nullable|integer|min:0',
            'inspection_result' => 'nullable|string|max:255',
            'quality_non_conformity_id' => 'nullable|exists:quality_non_conformities,id',
            'create_non_conformity' => 'nullable|boolean',
            'new_nc_label' => 'nullable|string|max:255',
            'new_nc_comment' => 'nullable|string|max:1000',
        ]);

        $purchaseReceiptLine->loadMissing('purchaseLines.purchase', 'purchaseLines.tasks.OrderLines');

        $acceptedQty = array_key_exists('accepted_qty', $validated)
            ? (int) ($validated['accepted_qty'] ?? 0)
            : ($purchaseReceiptLine->accepted_qty ?? 0);

        $rejectedQty = array_key_exists('rejected_qty', $validated)
            ? (int) ($validated['rejected_qty'] ?? 0)
            : ($purchaseReceiptLine->rejected_qty ?? 0);

        $totalInspected = $acceptedQty + $rejectedQty;

        if ($totalInspected > $purchaseReceiptLine->receipt_qty) {
            return redirect()->back()->withErrors([
                'accepted_qty' => __('general_content.inspection_qty_error_trans_key', [
                    'receipt' => $purchaseReceiptLine->receipt_qty,
                ]),
            ])->withInput();
        }

        if ($request->boolean('create_non_conformity') && empty($validated['quality_non_conformity_id'])) {
            $qualityNonConformity = $this->createQuickNonConformity(
                $purchaseReceiptLine,
                $validated['new_nc_label'] ?? null,
                $validated['new_nc_comment'] ?? null,
                $rejectedQty
            );

            $validated['quality_non_conformity_id'] = $qualityNonConformity->id;
        }

        $purchaseReceiptLine->update([
            'inspected_by' => $validated['inspected_by'] ?? null,
            'inspection_date' => $validated['inspection_date'] ?? null,
            'accepted_qty' => $acceptedQty,
            'rejected_qty' => $rejectedQty,
            'inspection_result' => $validated['inspection_result'] ?? null,
            'quality_non_conformity_id' => $validated['quality_non_conformity_id'] ?? null,
        ]);

        return redirect()->back()->with('success', __('general_content.inspection_update_success_trans_key'));
    }

    public function storeManualReceiptLine(Request $request, PurchaseReceipt $id)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $product = Products::findOrFail($validated['product_id']);
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $id->companies_id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $id->companies_id]);
        $accountingVat = AccountingVat::getDefault();

        if (!$defaultAddress || !$defaultContact) {
            return redirect()->back()->with('error', 'No default settings fount for address or contact');
        }

        if (!$accountingVat) {
            return redirect()->back()->with('error', 'No default accounting VAT found');
        }

        $manualPurchaseCode = 'MANUAL-' . $id->code;
        $purchase = Purchases::firstOrCreate(
            ['code' => $manualPurchaseCode],
            [
                'label' => $manualPurchaseCode,
                'companies_id' => $id->companies_id,
                'companies_contacts_id' => $defaultContact->id,
                'companies_addresses_id' => $defaultAddress->id,
                'user_id' => Auth::id(),
            ]
        );

        $nextPurchaseOrdre = (int) PurchaseLines::where('purchases_id', $purchase->id)->max('ordre');
        $nextReceiptOrdre = (int) PurchaseReceiptLines::where('purchase_receipt_id', $id->id)->max('ordre');
        $qty = (int) $validated['qty'];
        $price = $product->purchased_price ?? 0;

        $purchaseLine = PurchaseLines::create([
            'purchases_id' => $purchase->id,
            'tasks_id' => 0,
            'ordre' => $nextPurchaseOrdre + 10,
            'code' => $product->code,
            'product_id' => $product->id,
            'label' => $product->label,
            'qty' => $qty,
            'selling_price' => $price,
            'discount' => 0,
            'unit_price_after_discount' => $price,
            'total_selling_price' => $price * $qty,
            'receipt_qty' => $qty,
            'methods_units_id' => $product->methods_units_id,
            'accounting_vats_id' => $accountingVat->id,
        ]);

        PurchaseReceiptLines::create([
            'purchase_receipt_id' => $id->id,
            'purchase_line_id' => $purchaseLine->id,
            'ordre' => $nextReceiptOrdre + 10,
            'receipt_qty' => $qty,
        ]);

        $purchaseLines = PurchaseLines::where('purchases_id', $purchase->id)->get();
        $allReceived = $purchaseLines->every(function ($purchaseLineItem) {
            return $purchaseLineItem->receipt_qty >= $purchaseLineItem->qty;
        });

        $purchase->statu = $allReceived ? 4 : 3;
        $purchase->save();

        return redirect()->back()->with('success', 'Successfully added manual receipt line');
    }

    protected function createQuickNonConformity(
        PurchaseReceiptLines $purchaseReceiptLine,
        ?string $label,
        ?string $comment,
        ?int $rejectedQty
    ): QualityNonConformity {
        $lastNonConformity = QualityNonConformity::latest('id')->first();
        $code = $this->documentCodeGenerator->generateDocumentCode('non-conformities', $lastNonConformity?->id ?? 0);
        $label = $label ?: $code;

        $data = [
            'code' => $code,
            'label' => $label,
            'statu' => 1,
            'user_id' => Auth::id(),
            'companie_id' => optional($purchaseReceiptLine->purchaseLines->purchase)->companies_id,
            'qty' => $rejectedQty ?? $purchaseReceiptLine->receipt_qty,
        ];

        if ($comment) {
            $data['failure_comment'] = $comment;
        }

        $orderLine = optional($purchaseReceiptLine->purchaseLines->tasks)->OrderLines;
        if ($orderLine) {
            $data['order_lines_id'] = $orderLine->id;
        }

        return $this->qualityNonConformityService->createNonConformity($data);
    }
}
