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
use Illuminate\Http\Request;
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
        $currentYear = Carbon::now()->format('Y');

        $invoiceMonthlyRecap         = $this->invoiceKPIService->getInvoiceMonthlyRecap($currentYear);
        $invoiceMonthlyRecapPrevYear = $this->invoiceKPIService->getInvoiceMonthlyRecapPreviousYear($currentYear);
        $invoicesDataRate            = $this->invoiceKPIService->getInvoicesDataRate();

        $totalCount            = $this->invoiceKPIService->getTotalInvoicesCount();
        $totalAmount           = $this->invoiceKPIService->getTotalInvoiceAmount();
        $paidCount             = $this->invoiceKPIService->getPaidInvoicesCount();
        $unpaidCount           = $this->invoiceKPIService->getUnpaidInvoicesCount();
        $averagePaymentDelay   = $this->invoiceKPIService->getAveragePaymentDelay();
        $latePaymentRate       = $this->invoiceKPIService->getLatePaymentRate($totalCount);
        $topClients            = $this->invoiceKPIService->getTopClients()->load('companie');

        $reactKpi = [
            'totalCount'          => $totalCount,
            'totalAmount'         => round((float) $totalAmount, 2),
            'totalAmountFormatted'=> Number::currency($totalAmount, $currency, config('app.locale')),
            'paidCount'           => $paidCount,
            'unpaidCount'         => $unpaidCount,
            'paymentRate'         => $totalCount > 0 ? round($paidCount / $totalCount * 100, 1) : 0,
            'averagePaymentDelay' => round((float) $averagePaymentDelay, 1),
            'latePaymentRate'     => round((float) $latePaymentRate, 1),
        ];

        $reactChart = [
            'invoicesDataRate'              => $invoicesDataRate,
            'invoiceMonthlyRecap'           => $invoiceMonthlyRecap,
            'invoiceMonthlyRecapPreviousYear' => $invoiceMonthlyRecapPrevYear,
        ];

        $reactTopClients = $topClients->map(fn($c) => [
            'total_amount' => round((float) $c->total_amount, 2),
            'companie'     => $c->companie ? ['label' => $c->companie->label] : null,
        ])->values()->all();

        $reactEndpoints = [
            'list' => route('invoices.json.list'),
        ];

        return view('workflow/invoices-index', compact(
            'reactKpi',
            'reactChart',
            'reactTopClients',
            'reactEndpoints',
        ));
    }

    /**
     * JSON endpoint — paginated invoice list for the React InvoicesIndex component.
     */
    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);
        $companyId = $request->get('company_id');

        $allowed = ['code', 'label', 'created_at', 'due_date', 'statu', 'companie', 'contact', 'invoice_lines_count', 'total_amount'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir      = $sortAsc ? 'asc' : 'desc';
        $totalSub = 'COALESCE((SELECT SUM((order_lines.selling_price * invoice_lines.qty) * (1 - COALESCE(order_lines.discount,0)/100)) FROM invoice_lines JOIN order_lines ON invoice_lines.order_line_id = order_lines.id WHERE invoice_lines.invoices_id = invoices.id AND invoice_lines.deleted_at IS NULL), 0)';

        $query = Invoices::withCount('invoiceLines')
            ->selectRaw("invoices.*, {$totalSub} as total_amount")
            ->with(['companie:id,label,code', 'contact:id,first_name,name'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses))
            ->when($companyId, fn ($q) => $q->where('companies_id', $companyId));

        match ($sortField) {
            'companie'            => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = invoices.companies_id) {$dir}"),
            'contact'             => $query->orderByRaw("(SELECT name FROM companies_contacts WHERE companies_contacts.id = invoices.companies_contacts_id) {$dir}"),
            'invoice_lines_count' => $query->orderBy('invoice_lines_count', $dir),
            'total_amount'        => $query->orderByRaw("{$totalSub} {$dir}"),
            default               => $query->orderBy($sortField, $dir),
        };

        $invoices = $query->paginate(15);

        return response()->json([
            'data' => $invoices->map(fn ($inv) => [
                'id'                  => $inv->id,
                'code'                => $inv->code,
                'label'               => $inv->label,
                'statu'               => $inv->statu,
                'due_date'            => $inv->due_date,
                'created_at'          => $inv->created_at?->format('d/m/Y'),
                'companie'            => $inv->companie ? ['id' => $inv->companie->id, 'label' => $inv->companie->label] : null,
                'contact'             => $inv->contact  ? ['id' => $inv->contact->id,  'name'  => trim($inv->contact->first_name.' '.$inv->contact->name)] : null,
                'invoice_lines_count' => $inv->invoice_lines_count,
                'total_amount'        => round((float) $inv->total_amount, 2),
                'url'                 => route('invoices.show', ['id' => $inv->id]),
                'url_pdf'             => route('pdf.invoice', ['Document' => $inv->id]),
                'url_facturex'        => route('pdf.facturex', ['Document' => $inv->id]),
            ]),
            'meta' => [
                'total'        => $invoices->total(),
                'per_page'     => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
            ],
        ]);
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
        event(new DeliveryLineUpdated($DeliveryLine));
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
