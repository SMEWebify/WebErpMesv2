<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Purchases\Purchases;
use App\Http\Controllers\Controller;
use App\Services\PurchaseCalculator;
use App\Models\Purchases\PurchaseReceipt;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Models\Purchases\PurchasesQuotation;
use App\Http\Requests\Purchases\UpdatePurchaseRequest;
use App\Http\Requests\Purchases\UpdatePurchaseReceiptRequest;
use App\Http\Requests\Purchases\UpdatePurchaseQuotationRequest;

class PurchasesController extends Controller
{
    public function request()
    {    
        return view('purchases/purchases-request');
    }

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

    public function showQuotation(PurchasesQuotation $id)
    {   
        $CompanieSelect = Companies::select('id', 'code','label')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();

        $Factory = Factory::first();

        //return view('purchases/purchases-quotation-show', compact('PurchaseQuotation', 'CompanieSelect','AddressSelect', 'ContactSelect','Factory' ));.
        return view('purchases/purchases-quotation-show', [
            'PurchaseQuotation' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
        ]);
    }

    public function showPurchase(Purchases $id)
    {   
        $CompanieSelect = Companies::select('id', 'code','label')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();

        $PurchaseCalculator = new PurchaseCalculator($id);
        $totalPrice = $PurchaseCalculator->getTotalPrice();

        $Factory = Factory::first();

        return view('purchases/purchases-show', [
            'Purchase' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
        ]);
    }

    public function showReceipt(PurchaseReceipt $id)
    {   
        $Factory = Factory::first();
        
        return view('purchases/purchases-receipt-show', [
            'PurchaseReceipt' => $id,
            'Factory' => $Factory,
        ]);
    }

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

    public function updatePurchaseReceipt(UpdatePurchaseReceiptRequest $request)
    {
        $PurchaseReceipt = PurchaseReceipt::find($request->id);
        $PurchaseReceipt->label=$request->label;
        $PurchaseReceipt->statu=$request->statu;
        $PurchaseReceipt->delivery_note_number=$request->delivery_note_number;
        $PurchaseReceipt->save();
        
        return redirect()->route('purchase.receipt.show', ['id' =>  $PurchaseReceipt->id])->with('success', 'Successfully updated reciept');
    }
    

    
    public function waintingReceipt()
    {    
        return view('purchases/purchases-wainting-receipt');
    }

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

    public function invoice()
    {    
        return view('purchases/purchases-invoice');
    }
}
