<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Workflow\Invoices;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Http\Controllers\Controller;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;

class InvoicesController extends Controller
{
    //
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

    public function request()
    {    
        return view('workflow/invoices-request');
    }

    public function show(Invoices $id)
    {
        $CompanieSelect = Companies::select('id', 'CODE','LABEL')->get();
        $AddressSelect = CompaniesAddresses::select('id', 'LABEL','ADRESS')->get();
        $ContactSelect = CompaniesContacts::select('id', 'FIRST_NAME','NAME')->get();
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
        ]);
    }

    public function print(Invoices $id)
    {
        $Factory = Factory::first();
        return view('workflow/orders-print', [
            'Delivery' => $id,
            'Factory' => $Factory,
        ]);
    }
    
    public function update(UpdateInvoiceRequest $request)
    {
        $Invoice = Invoices::find($request->id);
        $Invoice->LABEL=$request->LABEL;
        $Invoice->statu=$request->statu;
        $Invoice->companies_id=$request->companies_id;
        $Invoice->companies_contacts_id=$request->companies_contacts_id;
        $Invoice->companies_addresses_id=$request->companies_addresses_id;
        $Invoice->comment=$request->comment;
        $Invoice->save();

        return redirect()->route('Invoice.show', ['id' =>  $Invoice->id])->with('success', 'Successfully updated Invoice');
    }
}
