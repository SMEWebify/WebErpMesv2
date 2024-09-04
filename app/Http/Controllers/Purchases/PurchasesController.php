<?php

namespace App\Http\Controllers\Purchases;

use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Traits\NextPreviousTrait;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use Illuminate\Support\Facades\Auth;
use App\Models\Products\StockLocation;
use App\Models\Purchases\PurchaseLines;
use App\Models\Accounting\AccountingVat;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Companies\CompaniesContacts;
use App\Services\PurchaseCalculatorService;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Products\StockLocationProducts;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Http\Requests\Purchases\StorePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseInvoiceRequest;
use App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest;
use App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest;

class PurchasesController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;

    public function __construct(
            SelectDataService $SelectDataService, 
            PurchaseKPIService $purchaseKPIService,
            CustomFieldService $customFieldService
        ){
        $this->SelectDataService = $SelectDataService;
        $this->purchaseKPIService = $purchaseKPIService;
        $this->customFieldService = $customFieldService;
    }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function request()
    {   
        return view('purchases/purchases-request');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function quotation()
    {    
        $data['purchasesQuotationDataRate'] = $this->purchaseKPIService->getPurchaseQuotationDataRate();
                                                            
        return view('purchases/purchases-quotation')->with('data',$data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function purchase()
    {   
        $data['purchasesDataRate'] = $this->purchaseKPIService->getPurchasesDataRate();
        $data['purchaseMonthlyRecap'] = $this->purchaseKPIService->getPurchaseMonthlyRecap();

        $topRatedSuppliers = $this->purchaseKPIService->getTopRatedSuppliers();
        $sortedByAvgReceptionDelay = $this->purchaseKPIService->getAverageReceptionDelayBySupplier();
        $top5FastestSuppliers = $sortedByAvgReceptionDelay->take(5);
        $top5SlowestSuppliers = $sortedByAvgReceptionDelay->reverse()->take(5);

        $topProducts = $this->purchaseKPIService->getTopProducts();
        $averageAmount = $this->purchaseKPIService->getAverageAmount();
        $totalPurchaseLineCount = $this->purchaseKPIService->getTotalPurchaseCount();
        $totalPurchasesAmount = $this->purchaseKPIService->getTotalPurchaseAmount();

        $userSelect = $this->SelectDataService->getUsers();
        $CompanieSelect = $this->SelectDataService->getSupplier();

        $LastPurchase =  Purchases::latest()->first();
        //if we have no id, define 0 
        if($LastPurchase == Null){
            $code =  "PU-0";
            $label = "PU-0";
        }
        // else we use is from db
        else{
            $code = "PU-".  $LastPurchase->id;
            $label = "PU-".  $LastPurchase->id;
        }

        return view('purchases/purchases-index', [
                                                    'topRatedSuppliers' => $topRatedSuppliers,
                                                    'top5FastestSuppliers' => $top5FastestSuppliers,
                                                    'top5SlowestSuppliers' => $top5SlowestSuppliers,
                                                    'topProducts' => $topProducts,
                                                    'averageAmount' => $averageAmount,
                                                    'totalPurchaseLineCount' => $totalPurchaseLineCount,
                                                    'totalPurchasesAmount' => $totalPurchasesAmount,
                                                    'userSelect' => $userSelect,
                                                    'CompanieSelect' => $CompanieSelect,
                                                    'code' => $code,
                                                    'label' => $label,
                                                ])->with('data',$data);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePurchase(StorePurchaseRequest  $request)
    {
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $request->companies_id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $request->companies_id]);
        $purchaseData = $request->only('code', 'label', 'companies_id', 'user_id');
        
        $defaultAddress = ($defaultAddress->id  ?? 0);
        $defaultContact = ($defaultContact->id  ?? 0);

        if($defaultAddress == 0 || $defaultContact == 0 ){
            return redirect()->back()->with('error', 'No default settings');
        }

        $purchaseData['companies_addresses_id'] = $defaultAddress;
        $purchaseData['companies_contacts_id'] = $defaultContact;

        $purchaseOrderCreated = Purchases::create($purchaseData);

        return redirect()->route('purchases.show', ['id' => $purchaseOrderCreated->id])->with('success', 'Successfully created new purchase order');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showQuotation(PurchasesQuotation $id)
    {   
        $CompanieSelect = $this->SelectDataService->getSupplier();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchasesQuotation(), $id->id);
                                    
        return view('purchases/purchases-quotation-show', [
            'PurchaseQuotation' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showPurchase(Purchases $id)
    {   
        $CompanieSelect = $this->SelectDataService->getSupplier();
        $AddressSelect = $this->SelectDataService->getAddress($id->companies_id);
        $ContactSelect = $this->SelectDataService->getContact($id->companies_id);
        $PurchaseCalculatorService = new PurchaseCalculatorService($id);
        $totalPrice = $PurchaseCalculatorService->getTotalPrice();
        $subPrice = $PurchaseCalculatorService->getSubTotal();
        $vatPrice = $PurchaseCalculatorService->getVatTotal();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Purchases(), $id->id);
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('purchase', $id->id);

        return view('purchases/purchases-show', [
            'Purchase' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice,
            'vatPrice' => $vatPrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request, $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePurchaseOrder(Request $request, $id)
    { 
        if($request->PurchaseQuotationLine){
            //get data to dulicate for new purchase order
            $purchaseData = PurchasesQuotation::find($id);

            //get last order id for create new Code id
            $LastPurchase =  Purchases::orderBy('id', 'desc')->first();
            if($LastPurchase == Null){
                $purchaseCode = "PU-0";
            }
            else{
                $purchaseCode = "PU-". $LastPurchase->id;
            }

            $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $purchaseData->companies_id]);
            $defaultContact = CompaniesContacts::getDefault(['companies_id' => $purchaseData->companies_id]);
            $AccountingVat = AccountingVat::getDefault(); 
            $defaultAddress = ($defaultAddress->id  ?? 0);
            $defaultContact = ($defaultContact->id  ?? 0);
            $AccountingVat = ($AccountingVat->id  ?? 0);
    
            if($defaultAddress == 0 || $defaultContact == 0 || $AccountingVat == 0){
                return redirect()->back()->with('error', 'No default settings');
            }

            // Create order
            $PurchaseOrderCreated = Purchases::create([
                'code'=> $purchaseCode,  
                'label'=> $purchaseCode, 
                'companies_id'=>$purchaseData->companies_id, 
                'companies_contacts_id' =>$defaultContact,
                'companies_addresses_id' =>$defaultAddress,
                //'statu' => defaut 1
                'user_id'=>Auth::id(),
                //'Comment' => defaut emtpy
            ]);

            $StatusUpdate = Status::select('id')->where('title', 'Supplied')->first();
            if(is_null($StatusUpdate)){
                $StatusUpdate = Status::select('id')->where('title', 'In progress')->first();
            }
            
            if(is_null($StatusUpdate)){
                return redirect()->back()->with('error', 'No status in kanban for define progress');
            }
            
            if($PurchaseOrderCreated){
                // Create lines
                $ordre = 10;
                foreach ($request->PurchaseQuotationLine as $key => $item) {
                    //if not best to find request value, but we cant send hidden data with livewire
                    //How pass all information from task information ?
                    $Task = Task::find($request->PurchaseQuotationLineTaskid[$key]);

                    // Create delivery line
                    $PurchaseLines = PurchaseLines::create([
                            'purchases_id' => $PurchaseOrderCreated->id,
                            'tasks_id' => $request->PurchaseQuotationLineTaskid[$key], 
                            'ordre' => $ordre, 
                            //'code' => , can be null
                            'product_id' =>$Task->products_id,
                            'label' => $Task->label,
                            //'supplier_ref' => , can be null
                            'qty' => $Task->qty,
                            'selling_price' => $Task->unit_cost,
                            'discount' => 0,
                            'unit_price_after_discount' => $Task->unit_cost,
                            'total_selling_price' => $Task->unit_cost * $Task->qty,
                            //'receipt_qty' =>, defaut to 0
                            //'invoiced_qty' =>, defaut to 0
                            'methods_units_id' => $Task->methods_units_id,
                            'accounting_vats_id' => $AccountingVat,
                            //'stock_locations_id' => , can be null
                            'statu' => 1
                        ]); 

                    /* // up order line for next record*/
                    $ordre= $ordre+10;
                    /* // update task statu Supplied on Kanban*/
                    if($StatusUpdate->id){
                        $taskUpdated = Task::where('id',$request->PurchaseQuotationLineTaskid[$key])->update(['status_id'=>$StatusUpdate->id]);
                    }
                    /* update quotation line qty accepted*/
                    $PurchasesQuotationLine = PurchaseQuotationLines::where('id', $item)->update(['qty_accepted'=> $Task->qty]);
                }

                return redirect()->route('purchases.show', ['id' => $PurchaseOrderCreated->id])->with('success', 'Successfully created new purchase order');
                
            }
            else{
                return redirect()->back()->withErrors(['msg' => 'Something went wrong']);
            }
        }
        else{
            return redirect()->back()->withErrors(['msg' => 'no lines selected']);
        }
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showReceipt(PurchaseReceipt $id)
    {   
        
        $StockLocationList = StockLocation::all();
        $StockLocationProductList = StockLocationProducts::all();
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
            'averageReceptionDelay' => $averageReceptionDelay->avg_reception_delay
        ]);
    }

    /**
     * @param $id
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
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseQuotation(UpdatePurchaseQuotationRequest $request)
    {
        $PurchasesQuotation = PurchasesQuotation::find($request->id);
        $PurchasesQuotation->label=$request->label;
        $PurchasesQuotation->statu=$request->statu;
        $PurchasesQuotation->companies_id=$request->companies_id;
        $PurchasesQuotation->companies_contacts_id=$request->companies_contacts_id;
        $PurchasesQuotation->companies_addresses_id=$request->companies_addresses_id;
        $PurchasesQuotation->delay=$request->delay;
        $PurchasesQuotation->comment=$request->comment;
        $PurchasesQuotation->save();
        
        return redirect()->route('purchases.quotations.show', ['id' =>  $PurchasesQuotation->id])->with('success', 'Successfully updated purchase quotation');
    }

    /**
     * @param \Illuminate\Http\Request $request
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateReceptionControl(Request $request, $id)
    {
        $purchaseReceipt = PurchaseReceipt::findOrFail($id);

        $purchaseReceipt->reception_controlled = 1;
        $purchaseReceipt->reception_control_date = now(); 
        $purchaseReceipt->reception_control_user_id = auth()->user()->id; 

        $purchaseReceipt->save();

        return redirect()->back()->with('success', 'Contrôle de réception mis à jour avec succès.');
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePurchaseInvoice(UpdatePurchaseInvoiceRequest $request)
    {
        $PurchaseInvoice = PurchaseInvoice::find($request->id);
        $PurchaseInvoice->label=$request->label;
        $PurchaseInvoice->statu=$request->statu;
        $PurchaseInvoice->comment=$request->comment;
        $PurchaseInvoice->save();
        
        return redirect()->route('purchase.invoices.show', ['id' =>  $PurchaseInvoice->id])->with('success', 'Successfully updated reciept');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function waintingReceipt()
    {    
        return view('purchases/purchases-wainting-receipt');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function receipt()
    {    
        $data['PurchaseReciepCountDataRate'] = $this->purchaseKPIService->getPurchaseReciepCountDataRate();
        return view('purchases/purchases-receipt')->with('data',$data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function waintingInvoice()
    {    
        return view('purchases/purchases-wainting-invoice');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function invoice()
    {   
        $data['purchasesDataRate'] = $this->purchaseKPIService->getPurchaseInvoiceDataRate();
        $data['purchaseInvoiceMonthlyRecap'] = $this->purchaseKPIService->getPurchaseInvoiceMonthlyRecap();
                                                            
        return view('purchases/purchases-invoice')->with('data',$data);
    }
}
