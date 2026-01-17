<?php

namespace App\Http\Controllers\Purchases;

use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use App\Services\PurchaseQuotationService;
use App\Services\PurchaseOrderService;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Models\Purchases\PurchaseRfqGroup;
use App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\DB;

class PurchasesRFQController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;
    protected $purchaseQuotationService;
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
            PurchaseQuotationService $purchaseQuotationService,
            PurchaseOrderService $purchaseOrderService,
        ){
        $this->SelectDataService = $SelectDataService;
        $this->purchaseKPIService = $purchaseKPIService;
        $this->customFieldService = $customFieldService;
        $this->purchaseQuotationService = $purchaseQuotationService;
        $this->purchaseOrderService = $purchaseOrderService;
    }
    
    /**
     * Display the purchase request view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function request()
    {   
        return view('purchases/purchases-request');
    }

    /**
     * Display the purchase quotation view.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function quotation()
    {    
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $data['purchasesQuotationDataRate'] = $this->purchaseKPIService->getPurchaseQuotationDataRate();
        $totalPurchaseQuotationCount = PurchasesQuotation::count();
        $totalPurchaseQuotationLineCount = PurchaseQuotationLines::count();
        $totalPurchaseQuotationAmount = Number::currency(
            PurchaseQuotationLines::sum('total_price'),
            $currency,
            config('app.locale')
        );
                                                            
        return view('purchases/purchases-quotation', [
            'totalPurchaseQuotationCount' => $totalPurchaseQuotationCount,
            'totalPurchaseQuotationLineCount' => $totalPurchaseQuotationLineCount,
            'totalPurchaseQuotationAmount' => $totalPurchaseQuotationAmount,
        ])->with('data', $data);
    }

    /**
     * Display a specific purchase quotation.
     *
     * @param PurchasesQuotation $id
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
     * Display a comparison table for a RFQ group.
     *
     * @param PurchaseRfqGroup $group
     * @return \Illuminate\Contracts\View\View
     */
    public function compareQuotationGroup(PurchaseRfqGroup $group)
    {
        $group->load(['purchaseQuotations.companie', 'purchaseQuotations.PurchaseQuotationLines']);
        $quotations = $group->purchaseQuotations;

        $lineGroups = collect();
        foreach ($quotations as $quotation) {
            foreach ($quotation->PurchaseQuotationLines as $line) {
                $key = $line->product_id ? 'product-' . $line->product_id : 'line-' . $line->id;
                if (!$lineGroups->has($key)) {
                    $lineGroups->put($key, [
                        'label' => $line->label ?? $line->code ?? __('general_content.line_trans_key'),
                        'qty' => $line->qty_to_order,
                        'lines' => [],
                    ]);
                }

                $lineGroup = $lineGroups->get($key);
                $lineGroup['lines'][$quotation->id] = $line;
                $lineGroups->put($key, $lineGroup);
            }
        }

        $supplierTotals = $quotations->mapWithKeys(function ($quotation) {
            return [$quotation->id => $quotation->PurchaseQuotationLines->sum('total_price')];
        });

        return view('purchases/purchases-quotation-compare', [
            'rfqGroup' => $group,
            'quotations' => $quotations,
            'lineGroups' => $lineGroups->values(),
            'supplierTotals' => $supplierTotals,
        ]);
    }

    /**
     * Duplicate a purchase quotation with its lines.
     *
     * @param PurchasesQuotation $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicateQuotation(PurchasesQuotation $id)
    {
        $id->load('PurchaseQuotationLines');
        $newCode = $this->purchaseQuotationService->generatePurchasesQuotationCode();
        $newLabel = $id->label . ' #duplicate' . $id->id;

        $newQuotation = DB::transaction(function () use ($id, $newCode, $newLabel) {
            $newQuotation = $this->purchaseQuotationService->createPurchasesQuotation(
                $id->companies_id,
                $newCode,
                $newLabel,
                $id->companies_contacts_id,
                $id->companies_addresses_id,
                $id->rfq_group_id
            );
            $newQuotation->delay = $id->delay;
            $newQuotation->statu = $id->statu;
            $newQuotation->comment = $id->comment;
            $newQuotation->save();

            foreach ($id->PurchaseQuotationLines as $line) {
                $newLine = $line->replicate();
                $newLine->purchases_quotation_id = $newQuotation->id;
                $newLine->save();
            }

            return $newQuotation;
        });

        return redirect()->route('purchases.quotations.show', ['id' =>  $newQuotation->id])
            ->with('success', 'Successfully duplicated purchase quotation');
    }

    /**
     * Update a purchase quotation.
     *
     * @param \App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest $request
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
}
