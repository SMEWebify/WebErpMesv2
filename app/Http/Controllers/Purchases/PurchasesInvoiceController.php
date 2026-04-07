<?php

namespace App\Http\Controllers\Purchases;

use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use App\Services\PurchaseOrderService;
use App\Models\Purchases\PurchaseInvoice;
use App\Http\Requests\Purchases\UpdatePurchaseInvoiceRequest;

class PurchasesInvoiceController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;
    protected $purchaseOrderService;

    /**
     * Constructor to initialize services.
     *
     * @param SelectDataService $SelectDataService
     * @param PurchaseKPIService $purchaseKPIService
     * @param CustomFieldService $customFieldService
     * @param PurchaseOrderService $purchaseOrderService
     */
    public function __construct(
            SelectDataService $SelectDataService, 
            PurchaseKPIService $purchaseKPIService,
            CustomFieldService $customFieldService,
            PurchaseOrderService $purchaseOrderService,
        ){
        $this->SelectDataService = $SelectDataService;
        $this->purchaseKPIService = $purchaseKPIService;
        $this->customFieldService = $customFieldService;
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * Display the waiting invoice view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function waintingInvoice()
    {    
        return view('purchases/purchases-wainting-invoice');
    }

    /**
     * Display a specific purchase invoice.
     *
     * @param PurchaseInvoice $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showInvoice(PurchaseInvoice $id)
    {   
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchaseInvoice(), $id->id);

        return view('purchases/purchases-invoice-show', [
            'PurchaseInvoice' => $id,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }
    
    /**
     * Update a purchase invoice.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseInvoiceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseInvoice(UpdatePurchaseInvoiceRequest $request)
    {
        $PurchaseInvoice = PurchaseInvoice::find($request->id);
        $PurchaseInvoice->label=$request->label;
        $PurchaseInvoice->statu=$request->statu;
        $PurchaseInvoice->comment=$request->comment;
        $PurchaseInvoice->save();
        
        return redirect()->route('purchase.invoices.show', ['id' =>  $PurchaseInvoice->id])->with('success', __('general_content.purchase_receipt_updated_success_trans_key'));
    }

    /**
     * Display the invoice view with data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function invoice()
    {   
        $data['purchasesDataRate'] = $this->purchaseKPIService->getPurchaseInvoiceDataRate();
        $data['purchaseInvoiceMonthlyRecap'] = $this->purchaseKPIService->getPurchaseInvoiceMonthlyRecap();
                                                            
        return view('purchases/purchases-invoice')->with('data',$data);
    }
}
