<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\companiesContacts;
use App\Models\Companies\companiesAddresses;

class PurchasesController extends Controller
{
    //
    public function index()
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
                                                            
        return view('workflow/purchases-index')->with('data',$data);
    }

    public function request()
    {    
        return view('workflow/purchases-request');
    }

    public function quotation()
    {    
        $CurentYear = Carbon::now()->format('Y');
        //Purchases data for chart
        $data['purchasesQuotationDataRate'] = DB::table('purchases_quotations')
                                    ->select('statu', DB::raw('count(*) as PurchaseQuotationCountRate'))
                                    ->groupBy('statu')
                                    ->get();

                                                            
        return view('workflow/purchases-quotation')->with('data',$data);
    }

    public function reciept()
    {    
        return view('workflow/purchases-reciept');
    }

    public function invoice()
    {    
        return view('workflow/purchases-invoice');
    }

    public function showPurchase()
    {    
        return view('workflow/purchases-invoice');
    }

    public function showQuotation()
    {    
        return view('workflow/purchases-invoice');
    }
}
