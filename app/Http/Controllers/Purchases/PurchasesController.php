<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use App\Events\PurchaseCreated;
use App\Traits\NextPreviousTrait;
use App\Models\Purchases\Purchases;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use Illuminate\Support\Facades\Auth;
use App\Services\PurchaseOrderService;
use App\Models\Companies\CompaniesContacts;
use App\Services\PurchaseCalculatorService;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchasesQuotation;
use App\Http\Requests\Purchases\StorePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;

class PurchasesController extends Controller
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
     * Display the purchase view with various data.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function purchase()
    {   
        $currentYear = Carbon::now()->format('Y');
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $data['purchasesDataRate'] = $this->purchaseKPIService->getPurchasesDataRate();
        $data['purchaseMonthlyRecap'] = $this->purchaseKPIService->getPurchaseMonthlyRecap($currentYear);

        $topRatedSuppliers = $this->purchaseKPIService->getTopRatedSuppliers();
        $sortedByAvgReceptionDelay = $this->purchaseKPIService->getAverageReceptionDelayBySupplier();
        $top5FastestSuppliers = $sortedByAvgReceptionDelay->take(5);
        $top5SlowestSuppliers = $sortedByAvgReceptionDelay->reverse()->take(5);

        $compositeIndicators = $this->purchaseKPIService->getSupplierCompositeIndicators();
        $suppliersToRequalify = $this->purchaseKPIService->getSuppliersToRequalify(30);

        $topProducts = $this->purchaseKPIService->getTopProducts();
        $averageAmount = Number::currency($this->purchaseKPIService->getAverageAmount(), $currency, config('app.locale'));
        $totalPurchaseLineCount = $this->purchaseKPIService->getTotalPurchaseCount();
        $totalPurchasesAmount = Number::currency($this->purchaseKPIService->getTotalPurchaseAmount(), $currency, config('app.locale'));

        $userSelect = $this->SelectDataService->getUsers();
        $CompanieSelect = $this->SelectDataService->getSupplier();

        $LastPurchase =   $this->purchaseOrderService->generatePurchaseCode();

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
                                                    'code' => $LastPurchase,
                                                    'label' => $LastPurchase,
                                                    'compositeIndicators' => $compositeIndicators,
                                                    'suppliersToRequalify' => $suppliersToRequalify,
                                                ])->with('data',$data);
    }

    /**
     * Display a specific purchase.
     *
     * @param Purchases $id
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
     * Prepare purchase data for storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|bool The prepared purchase data or false if defaults are missing.
     */
    protected function preparePurchaseData($request)
    {
        $defaultAddress = CompaniesAddresses::getDefault(['companies_id' => $request->companies_id]);
        $defaultContact = CompaniesContacts::getDefault(['companies_id' => $request->companies_id]);

        if (!$defaultAddress || !$defaultContact) {
            return false;
        }

        $purchaseData = $request->only('code', 'label', 'companies_id', 'user_id');
        $purchaseData['companies_addresses_id'] = $defaultAddress->id;
        $purchaseData['companies_contacts_id'] = $defaultContact->id;
        $purchaseData['user_id'] = Auth::id();

        return $purchaseData;
    }

    /**
     * Store a new bank purchase.
     *
     * @param \App\Http\Requests\Purchases\StorePurchaseRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeBankPurchase(StorePurchaseRequest  $request)
    {
        $purchaseData = $this->preparePurchaseData($request);

        if ($purchaseData === false) {
            return redirect()->back()->with('error', 'No default settings fount for address, contact or accounting vat');
        }
    
        $purchaseOrderCreated = Purchases::create($purchaseData);
    
        return redirect()->route('purchases.show', ['id' => $purchaseOrderCreated->id])
                            ->with('success', 'Successfully created new purchase order');
    }

    /**
     * Store a new purchase order from a request for quotation (RFQ).
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id The ID of the purchase quotation.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storePurchaseOrderFromRFQ(Request $request, $id)
    { 
        if (!$request->PurchaseQuotationLine) {
            return redirect()->back()->withErrors(['msg' => 'no lines selected']);
        }
    
        $PurchasesQuotationData = PurchasesQuotation::findOrFail($id);
    
        $purchaseOrder = $this->purchaseOrderService->createPurchaseOrderFromQuotation($PurchasesQuotationData);
    
        if (!$purchaseOrder) {
            return redirect()->back()->withErrors(['msg' => 'Something went wrong (like no default settings for address, contact or accounting vat)']);
        }
    
        $statusUpdate = $this->purchaseOrderService->getStatusUpdate();
    
        if (!$statusUpdate) {
            return redirect()->back()->with('error', 'No status "Supplied" or "In progress" in kanban for defining progress');
        }
    
        $this->purchaseOrderService->processPurchaseQuotationLines(
            $request->PurchaseQuotationLine,
            $request->PurchaseQuotationLineTaskid,
            $purchaseOrder,
            $statusUpdate->id,
            $request->PurchaseQuotationLinePrice,
        );

        // Déclencher l'événement PurchaseCreated
        event(new PurchaseCreated($PurchasesQuotationData));
    
        return redirect()->route('purchases.show', ['id' => $purchaseOrder->id])
                            ->with('success', 'Successfully created new purchase order');
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
}
