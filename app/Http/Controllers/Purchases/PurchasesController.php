<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Http\Controllers\Controller;
use App\Services\PurchaseCalculator;
use Illuminate\Support\Facades\Auth;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Purchases\PurchaseQuotationLines;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest;
use App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest;

class PurchasesController extends Controller
{
    /**
     * @return View
     */
    public function request()
    {    
        return view('purchases/purchases-request');
    }

    /**
     * @return View
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
     * @return View
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
                                                            
        return view('purchases/purchases-index')->with('data',$data);
    }

    /**
     * @param $id
     * @return View
     */
    public function showQuotation(PurchasesQuotation $id)
    {   
        $CompanieSelect = Companies::select('id', 'code','label')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();
        $Factory = Factory::first();
        $previousUrl = route('purchase.quotation.show', ['id' => $id->id-1]);
        $nextUrl = route('purchase.quotation.show', ['id' => $id->id+1]);

        return view('purchases/purchases-quotation-show', [
            'PurchaseQuotation' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    /**
     * @param $id
     * @return View
     */
    public function showPurchase(Purchases $id)
    {   
        $CompanieSelect = Companies::select('id', 'code','label')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();
        $PurchaseCalculator = new PurchaseCalculator($id);
        $totalPrice = $PurchaseCalculator->getTotalPrice();
        $previousUrl = route('purchase.show', ['id' => $id->id-1]);
        $nextUrl = route('purchase.show', ['id' => $id->id+1]);

        $Factory = Factory::first();

        return view('purchases/purchases-show', [
            'Purchase' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    /**
     * @param Request $request, $id
     * @return View
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
                            //'stock_location_id' => , can be null
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

                return redirect()->route('purchase.show', ['id' => $PurchaseOrderCreated->id])->with('success', 'Successfully created new purchase order');
                
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
     * @return View
     */
    public function showReceipt(PurchaseReceipt $id)
    {   
        $Factory = Factory::first();
        $previousUrl = route('purchase.receipt.show', ['id' => $id->id-1]);
        $nextUrl = route('purchase.receipt.show', ['id' => $id->id+1]);

        return view('purchases/purchases-receipt-show', [
            'PurchaseReceipt' => $id,
            'Factory' => $Factory,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    /**
     * @param Request $request
     * @return View
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
        
        return redirect()->route('purchase.show', ['id' =>  $Purchases->id])->with('success', 'Successfully updated purchase order');
    }

    /**
     * @param Request $request
     * @return View
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
        
        return redirect()->route('purchase.quotation.show', ['id' =>  $PurchasesQuotation->id])->with('success', 'Successfully updated purchase quotation');
    }

    /**
     * @param Request $request
     * @return View
     */
    public function updatePurchaseReceipt(UpdatePurchaseReceiptRequest $request)
    {
        $PurchaseReceipt = PurchaseReceipt::find($request->id);
        $PurchaseReceipt->label=$request->label;
        $PurchaseReceipt->statu=$request->statu;
        $PurchaseReceipt->delivery_note_number=$request->delivery_note_number;
        $PurchaseReceipt->comment=$request->comment;
        $PurchaseReceipt->save();
        
        return redirect()->route('purchase.receipt.show', ['id' =>  $PurchaseReceipt->id])->with('success', 'Successfully updated reciept');
    }
    
    /**
     * @return View
     */
    public function waintingReceipt()
    {    
        return view('purchases/purchases-wainting-receipt');
    }

    /**
     * @return View
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
     * @return View
     */
    public function invoice()
    {    
        return view('purchases/purchases-invoice');
    }
}
