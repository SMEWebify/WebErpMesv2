<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Support\Number;
use App\Services\InvoiceService;
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
use App\Models\Workflow\DeliveryLines;
use App\Services\InvoiceCalculatorService;
use App\Http\Requests\Workflow\UpdateInvoiceRequest;

class InvoicesController extends Controller
{
    use NextPreviousTrait;

    protected $invoiceKPIService;
    protected $customFieldService;
    protected $invoiceService;
    protected $invoiceLineService;

    public function __construct(
                    InvoiceKPIService $invoiceKPIService,
                    CustomFieldService $customFieldService,
                    InvoiceService $invoiceService,
                    InvoiceLineService $invoiceLineService,
            ){
                $this->invoiceKPIService = $invoiceKPIService;
                $this->customFieldService = $customFieldService;
                $this->invoiceService = $invoiceService;
                $this->invoiceLineService = $invoiceLineService;
            }
    
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {    
        $factory = app('Factory');  
        $currency = $factory->curency ?? 'EUR';
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

        $totalInvoices = Number::currency($totalInvoices, $currency, config('app.locale'));
        $totalInvoiceAmount = Number::currency($totalInvoiceAmount, $currency, config('app.locale'));
        $totalPaymentsReceived = Number::currency($totalPaymentsReceived, $currency, config('app.locale'));

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

    /**
     * Update the delivery line.
     *
     * @param $DeliveryLine
     * @return void
     */
    private function updateDeliveryLine($DeliveryLine)
    {
        $DeliveryLine->invoice_status = 4;
        $DeliveryLine->save();
        event(new DeliveryLineUpdated($DeliveryLine->id));
    }

    /**
     * Update the order line.
     *
     * @param $OrderLine
     * @param $DeliveryLine
     * @return void
     */
    private function updateOrderLine($OrderLine, $DeliveryLine)
    {
        $OrderLine->invoiced_qty += $DeliveryLine->qty;
        $OrderLine->invoiced_remaining_qty -= $DeliveryLine->qty;

        $OrderLine->invoice_status = $OrderLine->invoiced_remaining_qty == 0 ? 3 : 2;
        $OrderLine->save();
    }

    /**
     * Store a new invoice from delivery.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromDelevery($id)
    {
        $LastInvoice = Invoices::latest()->first();
        $nextInvoiceId = $LastInvoice ? $LastInvoice->id + 1 : 0;
        $code = "IN-" . $nextInvoiceId;
        $label = "IN-" . $nextInvoiceId;

        $DeliveryData = Deliverys::find($id);

        $user = Auth::user();
        $InvoiceCreated = $this->invoiceService->createInvoice($code, $label, $DeliveryData->companies_id, $DeliveryData->companies_addresses_id, $DeliveryData->companies_contacts_id, $user->id);

        $DeliveryLines = DeliveryLines::where('deliverys_id', $id)->get();
        foreach ($DeliveryLines as $DeliveryLine) {
            if ($DeliveryLine->invoice_status != 4) {
                // Create invoice line
                $this->invoiceLineService->createInvoiceLine($InvoiceCreated, $DeliveryLine->order_line_id, $DeliveryLine->id, $DeliveryLine->ordre, $DeliveryLine->qty, $DeliveryLine->OrderLine->accounting_vats_id);

                // Update delivery line
                $this->updateDeliveryLine($DeliveryLine);

                // Update order line info
                $OrderLine = OrderLines::find($DeliveryLine->order_line_id);
                $this->updateOrderLine($OrderLine, $DeliveryLine);
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
        $factory = app('Factory'); 
        $currency = $factory->curency ?? 'EUR';
        
        $InvoiceCalculatorService = new InvoiceCalculatorService($id);
        $totalPrice = $InvoiceCalculatorService->getTotalPrice();
        $subPrice = $InvoiceCalculatorService->getSubTotal();
        
        $totalPrice = Number::currency($totalPrice, $currency, config('app.locale'));
        $subPrice = Number::currency($subPrice, $currency, config('app.locale'));

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
}
