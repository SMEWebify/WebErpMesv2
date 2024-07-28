<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Admin\CustomField;
use App\Models\Workflow\Invoices;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use Illuminate\Support\Facades\DB;
use App\Events\DeliveryLineUpdated;
use App\Models\Companies\Companies;
use App\Models\Workflow\OrderLines;
use App\Services\InvoiceCalculatorService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\DeliveryLines;
use App\Models\Companies\CompaniesContacts;
use App\Models\Companies\CompaniesAddresses;
use App\Http\Requests\Workflow\UpdateInvoiceRequest;

class InvoicesController extends Controller
{
    use NextPreviousTrait;
    /**
     * @return \Illuminate\Contracts\View\View
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

        // Total number of invoices
        $totalInvoices = Invoices::count();

        // Total amount of invoices
        $totalInvoiceAmount = Invoices::all()->reduce(function ($carry, $invoice) {
            return $carry + $invoice->getTotalPriceAttribute();
        }, 0);

        // Total amount of payments received
        // Make sure the getTotalPriceAttribute method returns the total amount paid
        $totalPaymentsReceived = Invoices::where('statu', 5)->get()->reduce(function ($carry, $invoice) {
            return $carry + $invoice->getTotalPriceAttribute();
        }, 0);

        // Paid vs. unpaid invoices
        $paidInvoices = Invoices::where('statu', 5)->count();
        $unpaidInvoices = Invoices::where('statu','!=', 4)->count();

    // Average payment deadline (Make sure you have payment_date and due_date fields in the invoices table)
        $averagePaymentDelay = Invoices::whereNotNull('payment_date')
                                        ->select(DB::raw('AVG(DATEDIFF(payment_date, due_date)) as avg_delay'))
                                        ->value('avg_delay');

        // Late payment rate
        $latePaymentRate = Invoices::where('statu', 4)
                                    ->where('due_date', '<', now())
                                    ->count() / ($totalInvoices ?: 1) * 100;

        // Top customers by invoiced amount
        $topClients = Invoices::select('companies_id', DB::raw('SUM((invoice_lines.qty * order_lines.selling_price) - (invoice_lines.qty * order_lines.selling_price)*(order_lines.discount/100)) as total_amount'))
                                ->join('invoice_lines', 'invoices.id', '=', 'invoice_lines.invoices_id')
                                ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                                ->groupBy('companies_id')
                                ->orderByDesc('total_amount')
                                ->take(5)
                                ->get();

    // Top products/services billed
        $topProducts = InvoiceLines::select('order_lines.product_id', DB::raw('SUM(invoice_lines.qty) as total_quantity'))
                                    ->join('order_lines', 'invoice_lines.order_line_id', '=', 'order_lines.id')
                                    ->groupBy('order_lines.product_id')
                                    ->orderByDesc('total_quantity')
                                    ->take(5)
                                    ->get();



        return view('workflow/invoices-index', compact(
                                                        'totalInvoices',
                                                        'totalInvoiceAmount',
                                                        'totalPaymentsReceived',
                                                        'paidInvoices',
                                                        'unpaidInvoices',
                                                        'averagePaymentDelay',
                                                        'latePaymentRate',
                                                        'topClients',
                                                        'topProducts',
                                                    ))->with('data',$data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
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
            'uuid'=> Str::uuid(),
            'code'=>$code,  
            'label'=>$label, 
            'companies_id'=>$DeliveryData->companies_id,   
            'companies_addresses_id'=>$DeliveryData->companies_addresses_id,  
            'companies_contacts_id'=>$DeliveryData->companies_contacts_id,  
            'user_id'=>Auth::id(),
            'due_date' => Carbon::now()->addDays(30),
        ]);

        $DeliveryLines = DeliveryLines::where('deliverys_id', $id)->get();
        foreach ($DeliveryLines as $DeliveryLine) {
            if($DeliveryLine->invoice_status != 4){
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
        }

        // return view on new document
        return redirect()->route('invoices.show', ['id' => $InvoiceCreated->id])->with('success', 'Successfully created new invoice');

    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show(Invoices $id)
    {
        $InvoiceCalculatorService = new InvoiceCalculatorService($id);
        $totalPrice = $InvoiceCalculatorService->getTotalPrice();
        $subPrice = $InvoiceCalculatorService->getSubTotal();
        $vatPrice = $InvoiceCalculatorService->getVatTotal();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Invoices(), $id->id);
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
            'totalPrices' => $totalPrice,
            'subPrice' => $subPrice, 
            'vatPrice' => $vatPrice,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'CustomFields' => $CustomFields,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateInvoiceRequest $request)
    {
        $Invoice = Invoices::find($request->id);
        $Invoice->label=$request->label;
        $Invoice->statu=$request->statu;
        $Invoice->due_date=$request->due_date;
        $Invoice->incoterm=$request->incoterm;
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
     * @return \Illuminate\Contracts\View\View
     */
    public function export()
    {   
        return view('workflow/invoice-lines-export');
    }
}
