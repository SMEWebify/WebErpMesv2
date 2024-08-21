<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Workflow\Invoices;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use App\Events\DeliveryLineUpdated;
use App\Models\Workflow\OrderLines;
use App\Services\InvoiceKPIService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\InvoiceLineService;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\DeliveryLines;
use App\Services\InvoiceCalculatorService;
use App\Http\Requests\Workflow\UpdateInvoiceRequest;

class InvoicesController extends Controller
{
    use NextPreviousTrait;
    /**
     * @return \Illuminate\Contracts\View\View
     */

    protected $invoiceKPIService;
    protected $customFieldService;
    protected $invoiceLineService;

    public function __construct(
                    InvoiceKPIService $invoiceKPIService,
                    CustomFieldService $customFieldService,
                    InvoiceLineService $invoiceLineService,
            ){
                $this->invoiceKPIService = $invoiceKPIService;
                $this->customFieldService = $customFieldService;
                $this->invoiceLineService = $invoiceLineService;
            }
    
    public function index()
    {    
        $CurentYear = Carbon::now()->format('Y');

        $data['invoicesDataRate'] = $this->invoiceKPIService->getInvoicesDataRate();
        $data['invoiceMonthlyRecap'] = $this->invoiceKPIService->getInvoiceMonthlyRecap($CurentYear);

        $totalInvoices = $this->invoiceKPIService->getTotalInvoicesCount();
        $totalInvoiceAmount = $this->invoiceKPIService->getTotalInvoiceAmount();
        $totalPaymentsReceived = $this->invoiceKPIService->getTotalPaymentsReceived();
        $paidInvoices = $this->invoiceKPIService->getPaidInvoicesCount();
        $unpaidInvoices = $this->invoiceKPIService->getUnpaidInvoicesCount();
        $averagePaymentDelay = $this->invoiceKPIService->getAveragePaymentDelay();
        $latePaymentRate = $this->invoiceKPIService->getLatePaymentRate($totalInvoices);
        $topClients = $this->invoiceKPIService->getTopClients();
        $topProducts = $this->invoiceKPIService->getTopProducts();

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
                // Create invoice line
                $this->invoiceLineService->createInvoiceLine($InvoiceCreated, $DeliveryLine->order_line_id, $DeliveryLine->id, $DeliveryLine->ordre, $DeliveryLine->qty);

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
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('invoice', $id->id);

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
