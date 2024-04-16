<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Admin\CustomField;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Deliverys;
use Illuminate\Support\Facades\DB;
use App\Events\DeliveryLineUpdated;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Services\InvoiceCalculator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\DeliveryLines;
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
                                                                SUM((order_lines.selling_price * invoice_lines.qty)-(order_lines.selling_price * invoice_lines.qty)*(order_lines.discount/100)) AS orderSum
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

    
    public function storeFromDelevery($id)
    {
        $LastInvoice =  Invoices::latest()->first();
        if($LastInvoice == Null){
            $code = "IN-0";
            $label = "IN-0";
        }
        else{
            $code = "IN-". $LastInvoice->id;
            $label = "IN-". $LastInvoice->id;
        }

        $DeliveryData = Deliverys::find($id);

        // Create invoice
        $InvoiceCreated = Invoices::create([
            'code'=>$code,  
            'label'=>$label, 
            'companies_id'=>$DeliveryData->companies_id,   
            'companies_addresses_id'=>$DeliveryData->companies_addresses_id,  
            'companies_contacts_id'=>$DeliveryData->companies_contacts_id,  
            'user_id'=>Auth::id(),
        ]);

        $DeliveryLines = DeliveryLines::where('deliverys_id', $id)->get();
        foreach ($DeliveryLines as $DeliveryLine) {
            // Create  invoice line
            $InvoiceLines = InvoiceLines::create([
                'invoices_id' => $InvoiceCreated->id,
                'order_line_id' => $DeliveryLine->order_line_id, 
                'delivery_line_id' => $DeliveryLine->id, 
                'ordre' => $DeliveryLine->ordre, 
                'qty' => $DeliveryLine->qty,
                'statu' => 1
            ]); 

            $DeliveryLine->invoice_status = 4; 
            $DeliveryLine->save();
            
            event(new DeliveryLineUpdated($DeliveryLine->id));

            // update order line info
            $OrderLine = OrderLines::find($DeliveryLine->order_line_id);
            $OrderLine->invoiced_qty =  $OrderLine->invoiced_qty + $DeliveryLine->qty;
            $OrderLine->invoiced_remaining_qty = $OrderLine->invoiced_remaining_qty - $DeliveryLine->qty;
            //if we are invoiced all part
            if($OrderLine->invoiced_remaining_qty == 0){
                $OrderLine->invoice_status = 3;
            }
            else{
                $OrderLine->invoice_status = 2;
            }
            $OrderLine->save();
        }

        // return view on new document
        return redirect()->route('invoices.show', ['id' => $InvoiceCreated->id])->with('success', 'Successfully created new invoice');

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
        $CustomFields = CustomField::where('custom_fields.related_type', '=', 'invoice')
                                    ->leftJoin('custom_field_values  as cfv', function($join) use ($id) {
                                        $join->on('custom_fields.id', '=', 'cfv.custom_field_id')
                                                ->where('cfv.entity_type', '=', 'invoice')
                                                ->where('cfv.entity_id', '=', $id->id);
                                    })
                                    ->select('custom_fields.*', 'cfv.value as field_value')
                                    ->get();

        return view('workflow/invoices-show', [
            'Invoice' => $id,
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

        // For each invoice line associated with this invoice
        foreach ($Invoice->InvoiceLines as $line) {
            // Update the invoice line status with the new invoice status
            $line->invoice_status = $Invoice->statu;

            // Save each updated invoice line
            $line->save();
        }

        return redirect()->route('invoices.show', ['id' =>  $Invoice->id])->with('success', 'Successfully updated Invoice');
    }

    /**
     * @return View
     */
    public function export()
    {   
        return view('workflow/invoice-lines-export');
    }
}
