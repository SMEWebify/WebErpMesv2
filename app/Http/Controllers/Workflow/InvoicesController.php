<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Workflow\Invoices;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Services\InvoiceCalculator;
use App\Http\Controllers\Controller;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Workflow\UpdateInvoiceRequest;

class InvoicesController extends Controller
{
    /**
     * @return View
     */
    public function index()
    {    
        $CurentYear = Carbon::now()->format('Y');
        //Invoices data for chart
        $data['invoicesDataRate'] = DB::table('invoices')
                                    ->select('statu', DB::raw('count(*) as InvoiceCountRate'))
                                    ->groupBy('statu')
                                    ->get();
        //Invoices data for chart
        $data['invoiceMonthlyRecap'] = DB::table('invoice_lines')
                                                            ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                                                            ->selectRaw('
                                                                MONTH(invoice_lines.created_at) AS month,
                                                                SUM((order_lines.selling_price * order_lines.qty)-(order_lines.selling_price * order_lines.qty)*(order_lines.discount/100)) AS orderSum
                                                            ')
                                                            ->whereYear('invoice_lines.created_at', $CurentYear)
                                                            ->groupByRaw('MONTH(invoice_lines.created_at) ')
                                                            ->get();
        return view('workflow/invoices-index')->with('data',$data);
    }

    /**
     * @return View
     */
    public function request()
    {    
        return view('workflow/invoices-request');
    }

    /**
     * @param $id
     * @return View
     */
    public function show(Invoices $id)
    {
        $CompanieSelect = Companies::select('id', 'code','label')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'label','adress')->get();
        $ContactSelect = CompaniesContacts::select('id', 'first_name','name')->get();
        $OrderCalculator = new InvoiceCalculator($id);
        $totalPrice = $OrderCalculator->getTotalPrice();
        $subPrice = $OrderCalculator->getSubTotal();
        $vatPrice = $OrderCalculator->getVatTotal();
        $previousUrl = route('invoices.show', ['id' => $id->id-1]);
        $nextUrl = route('invoices.show', ['id' => $id->id+1]);

        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }

        return view('workflow/invoices-show', [
            'Invoice' => $id,
            'CompanieSelect' => $CompanieSelect,
            'AddressSelect' => $AddressSelect,
            'ContactSelect' => $ContactSelect,
            'Factory' => $Factory,
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    /**
     * @param Request $request
     * @return View
     */
    public function update(UpdateInvoiceRequest $request)
    {
        $Invoice = Invoices::find($request->id);
        $Invoice->label=$request->label;
        $Invoice->statu=$request->statu;
        $Invoice->comment=$request->comment;
        $Invoice->save();

        return redirect()->route('invoices.show', ['id' =>  $Invoice->id])->with('success', 'Successfully updated Invoice');
    }
}
