<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Admin\CustomField;
use App\Traits\NextPreviousTrait;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\PurchaseCalculatorService;
use Illuminate\Support\Facades\Auth;
use App\Models\Products\StockLocation;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Products\StockLocationProducts;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Http\Requests\Purchases\StorePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest;
use App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest;

class PurchasesController extends Controller
{
    use NextPreviousTrait;
    protected $SelectDataService;
    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
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
        $CurentYear = Carbon::now()->format('Y');
        //Purchases data for chart
        $data['purchasesQuotationDataRate'] = DB::table('purchases_quotations')
                                    ->select('statu', DB::raw('count(*) as PurchaseQuotationCountRate'))
                                    ->groupBy('statu')
                                    ->get();

                                                            
        return view('purchases/purchases-quotation')->with('data',$data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function purchase()
    {   
        $CurentYear = Carbon::now()->format('Y');
        //Purchases data for chart
        $data['purchasesDataRate'] = DB::table('purchases')
                                    ->select('statu', DB::raw('count(*) as PurchaseCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Purchases data for chart
        $data['purchaseMonthlyRecap'] = DB::table('purchase_lines')
                                                            ->join('tasks', 'purchase_lines.tasks_id', '=', 'tasks.id')
                                                            ->join('order_lines', 'tasks.order_lines_id', '=', 'order_lines.id')
                                                            ->selectRaw('
                                                                MONTH(purchase_lines.created_at) AS month,
                                                                SUM((order_lines.selling_price * order_lines.qty)-(order_lines.selling_price * order_lines.qty)*(order_lines.discount/100)) AS purchaseSum
                                                            ')
                                                            ->whereYear('purchase_lines.created_at', $CurentYear)
                                                            ->groupByRaw('MONTH(purchase_lines.created_at) ')
                                                            ->get();

        $topRatedSuppliers = Companies::where('statu_supplier', 2 ) // Filter active suppliers
                                                            ->withCount('rating') // Count the number of evaluations
                                                            ->having('rating_count', '>', 0) // Exclude suppliers without reviews
                                                                ->orderByDesc(function ($company) {
                                                                    return $company->select(DB::raw('avg(rating)'))
                                                                        ->from('supplier_ratings')
                                                                        ->whereColumn('companies_id', 'companies.id');
                                                                })
                                                                ->take(5) // Limit results to the first 5 suppliers
                                                                ->get();

        $averageReceptionDelayBySupplier = PurchaseReceiptLines::join('purchase_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
                                                                ->join('purchases', 'purchase_lines.purchases_id', '=', 'purchases.id')
                                                                ->join('companies', 'purchases.companies_id', '=', 'companies.id') // Join the suppliers table
                                                                ->selectRaw('companies.label AS supplier_name, AVG(DATEDIFF(purchase_receipt_lines.created_at, purchase_lines.created_at)) AS avg_reception_delay')
                                                                ->groupBy('companies.label') // Group by supplier
                                                                ->get();

        // Sorting results by average reception time in ascending order
        $sortedByAvgReceptionDelay = $averageReceptionDelayBySupplier->sortBy('avg_reception_delay');
        // Collect the 5 suppliers with the shortest deadlines
        $top5FastestSuppliers = $sortedByAvgReceptionDelay->take(5);
        // Retrieve the 5 suppliers with the longest lead times
        $top5SlowestSuppliers = $sortedByAvgReceptionDelay->reverse()->take(5);

        $topProducts = PurchaseLines::select('products.label', 'purchase_lines.product_id', DB::raw('SUM(purchase_lines.qty) as total_quantity'))
                                    ->join('products', 'products.id', '=', 'purchase_lines.product_id')
                                    ->groupBy('purchase_lines.product_id', 'products.label')
                                    ->orderByDesc('total_quantity')
                                    ->take(5)
                                    ->get();

        $totalPurchaseAmount = PurchaseLines::sum('total_selling_price');
        $totalPurchaseCount = PurchaseLines::count();
        $averageAmount = $totalPurchaseCount > 0 ? $totalPurchaseAmount / $totalPurchaseCount : 0;
        
        $totalPurchaseLineCount = PurchaseLines::count();
        $totalPurchasesAmount = PurchaseLines::sum('total_selling_price');

        $userSelect = $this->SelectDataService->getUsers();
        $SupplierSelect = $this->SelectDataService->getSupplier();

        
        $LastPurchase = 0;
        $LastPurchase =  Purchases::latest()->first();
        //if we have no id, define 0 
        if($LastPurchase == Null){
            $code =  "-0";
            $label = "-0";
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
                                                    'SupplierSelect' => $SupplierSelect,
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
        $PurchaseOrderCreated = Purchases::create($request->only('code', 'label','companies_id','user_id'));

        return redirect()->route('purchases.show', ['id' => $PurchaseOrderCreated->id])->with('success', 'Successfully created new purchase order');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function showQuotation(PurchasesQuotation $id)
    {   
        $CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $id->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $id->companies_id)->get();
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
        $CompanieSelect = Companies::select('id', 'code','client_type','civility','label','last_name')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->where('companies_id', $id->companies_id)->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->where('companies_id', $id->companies_id)->get();
        $PurchaseCalculatorService = new PurchaseCalculatorService($id);
        $totalPrice = $PurchaseCalculatorService->getTotalPrice();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Purchases(), $id->id);
        $CustomFields = CustomField::where('custom_fields.related_type', '=', 'purchase')
                                    ->leftJoin('custom_field_values  as cfv', function($join) use ($id) {
                                        $join->on('custom_fields.id', '=', 'cfv.custom_field_id')
                                                ->where('cfv.entity_type', '=', 'purchase')
                                                ->where('cfv.entity_id', '=', $id->id);
                                    })
                                    ->select('custom_fields.*', 'cfv.value as field_value')
                                    ->get();

        return view('purchases/purchases-show', [
            'Purchase' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'totalPrices' => $totalPrice,
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
            // Create order
            $PurchaseOrderCreated = Purchases::create([
                'code'=> $purchaseCode,  
                'label'=> $purchaseCode, 
                'companies_id'=>$purchaseData->companies_id, 
                //'companies_contacts_id' 
                //'companies_addresses_id' 
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
                            //'accounting_allocation_id' => , can be null
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
        $Purchases->statu=$request->statu;
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
        $CurentYear = Carbon::now()->format('Y');
        //Purchases data for chart
        $data['PurchaseReciepCountDataRate'] = DB::table('purchase_receipts')
                                    ->select('statu', DB::raw('count(*) as PurchaseReciepCountRate'))
                                    ->groupBy('statu')
                                    ->get();

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
        $CurentYear = Carbon::now()->format('Y');
        //Purchases data for chart
        $data['purchasesDataRate'] = DB::table('purchase_invoices')
                                    ->select('statu', DB::raw('count(*) as PurchaseInvoiceCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Purchases data for chart
        $data['purchaseMonthlyRecap'] = DB::table('purchase_invoice_lines')
                                                            ->join('purchase_lines', 'purchase_invoice_lines.purchase_line_id', '=', 'purchase_lines.id')
                                                            ->join('tasks', 'purchase_lines.tasks_id', '=', 'tasks.id')
                                                            ->join('order_lines', 'tasks.order_lines_id', '=', 'order_lines.id')
                                                            ->selectRaw('
                                                                MONTH(purchase_lines.created_at) AS month,
                                                                SUM((order_lines.selling_price * order_lines.qty)-(order_lines.selling_price * order_lines.qty)*(order_lines.discount/100)) AS purchaseSum
                                                            ')
                                                            ->whereYear('purchase_invoice_lines.created_at', $CurentYear)
                                                            ->groupByRaw('MONTH(purchase_invoice_lines.created_at) ')
                                                            ->get();
                                                            
        return view('purchases/purchases-invoice')->with('data',$data);
    }
}
