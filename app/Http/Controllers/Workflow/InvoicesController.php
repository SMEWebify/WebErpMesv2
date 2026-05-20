<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Support\Number;
use App\Services\InvoiceService;
use App\Services\InvoiceDataService;
use App\Services\SelectDataService;
use App\Services\DocumentCodeGenerator;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\InvoicePayment;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Services\AccountingEntryService;
use App\Traits\NextPreviousTrait;
use App\Models\Workflow\Deliverys;
use App\Events\DeliveryLineUpdated;
use App\Models\Workflow\OrderLines;
use App\Services\InvoiceKPIService;
use App\Http\Controllers\Controller;
use App\Models\Integrations\QontoInvoiceMapping;
use App\Services\CustomFieldService;
use App\Services\Integrations\QontoConnectionService;
use App\Services\InvoiceLineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\DeliveryLines;
use App\Models\Companies\Companies;
use App\Services\InvoiceCalculatorService;
use App\Services\AccountingPeriodService;
use App\Http\Requests\Workflow\UpdateInvoiceRequest;

class InvoicesController extends Controller
{
    use NextPreviousTrait;

    protected $invoiceKPIService;
    protected $customFieldService;
    protected $invoiceService;
    protected $invoiceLineService;
    protected $invoiceDataService;
    protected $selectDataService;
    protected $documentCodeGenerator;

    public function __construct(
        InvoiceKPIService     $invoiceKPIService,
        CustomFieldService    $customFieldService,
        InvoiceService        $invoiceService,
        InvoiceLineService    $invoiceLineService,
        InvoiceDataService    $invoiceDataService,
        SelectDataService     $selectDataService,
        DocumentCodeGenerator $documentCodeGenerator,
    ){
        $this->invoiceKPIService    = $invoiceKPIService;
        $this->customFieldService   = $customFieldService;
        $this->invoiceService       = $invoiceService;
        $this->invoiceLineService   = $invoiceLineService;
        $this->invoiceDataService   = $invoiceDataService;
        $this->selectDataService    = $selectDataService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $factory = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $currentYear     = Carbon::now()->format('Y');
        $fiscal          = $factory->getCurrentFiscalYear();
        $fiscalStart     = $fiscal['start'];
        $fiscalEnd       = $fiscal['end'];
        $prevFiscalStart = $fiscalStart->copy()->subYear();
        $prevFiscalEnd   = $fiscalEnd->copy()->subYear();

        $invoiceMonthlyRecap         = $this->invoiceKPIService->getInvoiceMonthlyRecap($currentYear, $fiscalStart, $fiscalEnd);
        $invoiceMonthlyRecapPrevYear = $this->invoiceKPIService->getInvoiceMonthlyRecap((int)$currentYear - 1, $prevFiscalStart, $prevFiscalEnd);
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
            'invoicesDataRate'               => $invoicesDataRate,
            'invoiceMonthlyRecap'            => $invoiceMonthlyRecap,
            'invoiceMonthlyRecapPreviousYear'=> $invoiceMonthlyRecapPrevYear,
            'fiscalYearStartMonth'           => (int) ($factory->fiscal_year_start_month ?? 1),
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
            ->where('invoice_type', 1)
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

        $qontoEnabled = QontoConnectionService::isEnabled();

        // Charger les mappings Qonto en une seule requête si intégration active
        $qontoMappings = [];
        if ($qontoEnabled) {
            $ids = $invoices->pluck('id');
            $qontoMappings = QontoInvoiceMapping::whereIn('invoice_id', $ids)
                ->pluck('lifecycle_status', 'invoice_id')
                ->all();
        }

        return response()->json([
            'qonto_enabled' => $qontoEnabled,
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
                'qonto_status'        => $qontoEnabled ? ($qontoMappings[$inv->id] ?? null) : null,
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
        $companyIds = $this->invoiceDataService->getUniqueCompanyIdsWithOpenInvoiceLines();

        $reactProps = [
            'code'      => $this->documentCodeGenerator->peekNextCode('invoice'),
            'userId'    => Auth::id(),
            'users'     => $this->selectDataService->getUsers(),
            'companies' => $this->selectDataService->getCompanies($companyIds),
        ];

        $reactEndpoints = [
            'lines'            => route('invoices-request.lines'),
            'store'            => route('invoices-request.store'),
            'generateAll'      => route('invoices-request.generate-all'),
        ];

        $reactTrans = [
            'company'         => __('general_content.sort_companie_trans_key'),
            'date_from'       => __('general_content.delivery_note_date_from_trans_key'),
            'date_to'         => __('general_content.delivery_note_date_to_trans_key'),
            'external_id'     => __('general_content.external_id_trans_key'),
            'label'           => __('general_content.label_trans_key'),
            'user'            => __('general_content.user_management_trans_key'),
            'address'         => __('general_content.adress_name_trans_key'),
            'contact'         => __('general_content.contact_name_trans_key'),
            'new_invoice'     => __('general_content.new_invoice_trans_key'),
            'generate_all'    => __('general_content.generate_pending_invoices_trans_key'),
            'order'           => __('general_content.order_trans_key'),
            'delivery_note'   => __('general_content.delivery_notes_trans_key'),
            'customer'        => __('general_content.customer_trans_key'),
            'qty'             => __('general_content.qty_trans_key'),
            'unit'            => __('general_content.unit_trans_key'),
            'price'           => __('general_content.price_trans_key'),
            'discount'        => __('general_content.discount_trans_key'),
            'vat'             => __('general_content.vat_trans_key'),
            'action'          => __('general_content.action_trans_key'),
            'add_to_document' => __('general_content.add_to_document_trans_key'),
            'internal_order'  => __('general_content.internal_order_trans_key'),
            'no_data'         => __('general_content.no_data_trans_key'),
            'select_company'  => __('general_content.select_company_trans_key'),
            'select_user'     => __('general_content.select_user_management_trans_key'),
            'select_address'  => __('general_content.select_address_trans_key'),
            'select_contact'  => __('general_content.select_contact_trans_key'),
            'no_company'      => __('general_content.no_select_company_trans_key'),
            'no_address'      => __('general_content.no_address_trans_key'),
            'no_contact'      => __('general_content.no_contact_trans_key'),
            'no_lines'        => 'No lines selected',
            'invoices_created' => 'Invoices created',
        ];

        return view('workflow/invoices-request', [
            'reactProps'     => $reactProps,
            'reactEndpoints' => $reactEndpoints,
            'reactTrans'     => $reactTrans,
        ]);
    }

    /**
     * Returns delivery lines for a given company + optional date range.
     */
    public function requestLines(Request $request)
    {
        $companyId  = $request->get('company_id') ? (int) $request->get('company_id') : null;
        $dateStart  = $request->get('date_start') ?: null;
        $dateEnd    = $request->get('date_end')   ?: null;

        $addresses = $this->selectDataService->getAddress($companyId);
        $contacts  = $this->selectDataService->getContact($companyId);

        $lines = $this->invoiceDataService->getInvoiceRequestsLines($companyId, $dateStart, $dateEnd, 'id', true);
        $lines->load(['delivery:id,code,companies_id', 'OrderLine.order.companie:id,label', 'OrderLine.Unit:id,label', 'OrderLine.VAT:id,label']);

        return response()->json([
            'addresses' => $addresses->map(fn($a) => [
                'id'     => $a->id,
                'label'  => $a->label,
                'adress' => $a->adress,
            ]),
            'contacts' => $contacts->map(fn($c) => [
                'id'         => $c->id,
                'first_name' => $c->first_name,
                'name'       => $c->name,
            ]),
            'lines' => $lines->map(fn($l) => [
                'id'              => $l->id,
                'qty'             => $l->qty,
                'delivery_code'   => $l->delivery?->code,
                'delivery_url'    => $l->delivery ? route('deliverys.show', ['id' => $l->deliverys_id]) : null,
                'order_code'      => $l->OrderLine?->order?->code,
                'order_type'      => $l->OrderLine?->order?->type,
                'order_url'       => $l->OrderLine?->order ? route('orders.show', ['id' => $l->OrderLine->order->id]) : null,
                'companie_label'  => $l->OrderLine?->order?->companie?->label,
                'companie_url'    => $l->OrderLine?->order?->companies_id ? route('companies.show', ['id' => $l->OrderLine->order->companies_id]) : null,
                'line_code'       => $l->OrderLine?->code,
                'line_label'      => $l->OrderLine?->label,
                'unit_label'      => $l->OrderLine?->Unit?->label,
                'selling_price'   => $l->OrderLine?->selling_price,
                'discount'        => $l->OrderLine?->discount,
                'vat_label'       => $l->OrderLine?->VAT?->label,
                'vat_id'          => $l->OrderLine?->accounting_vats_id,
                'order_line_id'   => $l->order_line_id,
                'orders_id'       => $l->OrderLine?->orders_id,
            ]),
        ]);
    }

    /**
     * Creates an invoice from selected delivery lines.
     */
    public function storeInvoiceApi(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'code'                   => 'required|unique:invoices',
            'label'                  => 'required',
            'companies_id'           => 'required|integer|min:1',
            'companies_addresses_id' => 'required|integer|min:1',
            'companies_contacts_id'  => 'required|integer|min:1',
            'user_id'                => 'required|integer|min:1',
            'lines'                  => 'required|array|min:1',
            'lines.*'                => 'integer',
        ]);

        $invoice = $this->invoiceService->createInvoice(
            $validated['code'],
            $validated['label'],
            $validated['companies_id'],
            $validated['companies_addresses_id'],
            $validated['companies_contacts_id'],
            $validated['user_id'],
        );

        $ordre = 10;
        foreach ($validated['lines'] as $deliveryLineId) {
            $deliveryLine = DeliveryLines::find($deliveryLineId);
            if (!$deliveryLine) continue;

            $this->invoiceLineService->createInvoiceLine(
                $invoice,
                $deliveryLine->order_line_id,
                $deliveryLine->id,
                $ordre,
                $deliveryLine->qty,
                $deliveryLine->OrderLine->accounting_vats_id,
            );
            $this->updateDeliveryLine($deliveryLine);
            $orderLine = OrderLines::find($deliveryLine->order_line_id);
            $this->updateOrderLine($orderLine, $deliveryLine);

            $ordre += 10;
        }

        return response()->json([
            'redirect' => route('invoices.show', ['id' => $invoice->id]),
        ]);
    }

    /**
     * Generates one invoice per order for all open delivery lines of a company.
     */
    public function generateInvoicesForCompanyApi(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'companies_id'           => 'required|integer|min:1',
            'companies_addresses_id' => 'required|integer|min:1',
            'companies_contacts_id'  => 'required|integer|min:1',
            'user_id'                => 'required|integer|min:1',
            'date_start'             => 'nullable|date',
            'date_end'               => 'nullable|date',
        ]);

        $deliveryLines = $this->invoiceDataService->getInvoiceRequestsLines(
            $validated['companies_id'],
            $validated['date_start'] ?? null,
            $validated['date_end']   ?? null,
        );

        if ($deliveryLines->isEmpty()) {
            return response()->json(['message' => 'No lines to invoice'], 422);
        }

        $deliveryLines->load('OrderLine');

        $invoiceCount = 0;
        $deliveryLines
            ->groupBy(fn($line) => $line->OrderLine?->orders_id)
            ->each(function ($lines) use ($validated, &$invoiceCount) {
                $lastInvoice = Invoices::latest()->first();
                $invoiceId   = $lastInvoice ? $lastInvoice->id : 0;
                $code        = $this->documentCodeGenerator->generateDocumentCode('invoice', $invoiceId);

                $invoice = $this->invoiceService->createInvoice(
                    $code, $code,
                    $validated['companies_id'],
                    $validated['companies_addresses_id'],
                    $validated['companies_contacts_id'],
                    $validated['user_id'],
                );

                $ordre = 10;
                foreach ($lines as $deliveryLine) {
                    $this->invoiceLineService->createInvoiceLine(
                        $invoice,
                        $deliveryLine->order_line_id,
                        $deliveryLine->id,
                        $ordre,
                        $deliveryLine->qty,
                        $deliveryLine->OrderLine->accounting_vats_id,
                    );
                    $this->updateDeliveryLine($deliveryLine);
                    $orderLine = OrderLines::find($deliveryLine->order_line_id);
                    $this->updateOrderLine($orderLine, $deliveryLine);
                    $ordre += 10;
                }
                $invoiceCount++;
            });

        return response()->json([
            'message' => $invoiceCount . ' invoice(s) created',
            'count'   => $invoiceCount,
        ]);
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
        $invoiceId = $LastInvoice ? $LastInvoice->id : 0;
        $code = $this->documentCodeGenerator->generateDocumentCode('invoice', $invoiceId);
        $DeliveryData = Deliverys::find($id);

        $user = Auth::user();
        $InvoiceCreated = $this->invoiceService->createInvoice(
            $code,
            $DeliveryData->label,
            $DeliveryData->companies_id,
            $DeliveryData->companies_addresses_id,
            $DeliveryData->companies_contacts_id,
            $user->id,
            $DeliveryData->customer_reference
        );

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

        $qontoEnabled = QontoConnectionService::isEnabled();
        $qontoMapping = $qontoEnabled
            ? QontoInvoiceMapping::where('invoice_id', $id->id)->first()
            : null;

        $companies = Companies::where('active', 1)->orderBy('code')->get(['id', 'code', 'label']);
        $addresses = $this->selectDataService->getAddress($id->companies_id);
        $contacts  = $this->selectDataService->getContact($id->companies_id);

        return view('workflow/invoices-show', [
            'Invoice'      => $id,
            'totalPrices'  => $totalPrice,
            'subPrice'     => $subPrice,
            'vatPrice'     => $vatPrice,
            'previousUrl'  => $previousUrl,
            'nextUrl'      => $nextUrl,
            'CustomFields' => $CustomFields,
            'qontoEnabled'     => $qontoEnabled,
            'qontoMapping'     => $qontoMapping,
            'companies'        => $companies,
            'addresses'        => $addresses,
            'contacts'         => $contacts,
            'companyAcUrl'     => route('invoices.company-ac'),
            'paymentMethods'   => AccountingPaymentMethod::orderBy('label')->get(['id', 'label']),
            'paymentEndpoints' => [
                'index'   => route('invoices.payments.index', $id->id),
                'store'   => route('invoices.payments.store', $id->id),
                'destroy' => route('invoices.payments.destroy', [$id->id, '__payment__']),
            ],
        ]);
    }

    public function companyAddressContact(Request $request): \Illuminate\Http\JsonResponse
    {
        $companyId = (int) $request->get('company_id');
        $addresses = $this->selectDataService->getAddress($companyId);
        $contacts  = $this->selectDataService->getContact($companyId);

        return response()->json([
            'addresses' => $addresses->map(fn($a) => [
                'id'    => $a->id,
                'label' => trim($a->label . ($a->adress ? ' — ' . $a->adress : '')),
            ])->values(),
            'contacts' => $contacts->map(fn($c) => [
                'id'    => $c->id,
                'label' => trim($c->first_name . ' ' . $c->name),
            ])->values(),
        ]);
    }

    public function changeStatusJson(Request $request, $id)
    {
        $invoice = Invoices::findOrFail($id);

        // Période verrouillée : on bloque uniquement si la facture est déjà postée en compta
        if ($invoice->accounting_status === 3 && app(AccountingPeriodService::class)->isLocked($invoice->created_at)) {
            return response()->json(['error' => "Période {$invoice->created_at->format('m/Y')} verrouillée — statut comptable non modifiable."], 422);
        }

        $statu   = (int) $request->input('statu');

        $invoice->statu = $statu;
        $invoice->save();

        foreach ($invoice->InvoiceLines as $line) {
            $line->invoice_status = $statu;
            $line->save();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateInvoiceRequest $request)
    {
        $Invoice = Invoices::find($request->id);

        if (app(AccountingPeriodService::class)->isLocked($Invoice->created_at)) {
            return redirect()->back()->withErrors(['period' => "Période {$Invoice->created_at->format('m/Y')} verrouillée — cette facture ne peut plus être modifiée."]);
        }
        $Invoice->label                  = $request->label;
        $Invoice->due_date               = $request->due_date;
        $Invoice->incoterm               = $request->incoterm;
        $Invoice->comment                = $request->comment;
        $Invoice->companies_id           = $request->companies_id;
        $Invoice->companies_addresses_id = $request->companies_addresses_id;
        $Invoice->companies_contacts_id  = $request->companies_contacts_id;
        $Invoice->customer_reference     = $request->customer_reference;
        $Invoice->save();

        return redirect()->route('invoices.show', ['id' =>  $Invoice->id])->with('success', 'Successfully updated Invoice');
    }

    public function paymentsIndex(Invoices $invoice): \Illuminate\Http\JsonResponse
    {
        $payments = $invoice->payments()->with('paymentMethod:id,label', 'user:id,name')->get()
            ->map(fn ($p) => [
                'id'             => $p->id,
                'amount'         => (float) $p->amount,
                'payment_date'   => $p->payment_date->format('Y-m-d'),
                'payment_method' => $p->paymentMethod?->label,
                'reference'      => $p->reference,
                'note'           => $p->note,
                'user'           => $p->user?->name,
                'created_at'     => $p->created_at->format('d/m/Y'),
            ]);

        $total        = $invoice->getTotalPriceAttribute();
        $paid         = $payments->sum('amount');
        $remaining    = round($total - $paid, 2);

        return response()->json([
            'payments'  => $payments,
            'total'     => $total,
            'paid'      => $paid,
            'remaining' => $remaining,
        ]);
    }

    public function paymentsStore(Request $request, Invoices $invoice): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'amount'            => 'required|numeric|min:0.01',
            'payment_date'      => 'required|date',
            'payment_method_id' => 'nullable|exists:accounting_payment_methods,id',
            'reference'         => 'nullable|string|max:100',
            'note'              => 'nullable|string|max:500',
        ]);

        $payment = InvoicePayment::create([
            ...$validated,
            'invoice_id' => $invoice->id,
            'user_id'    => Auth::id(),
        ]);

        $payment->load('invoice.companie', 'paymentMethod');
        app(AccountingEntryService::class)->createPaymentEntry($payment);

        // Mise à jour automatique du statut facture
        $total     = $invoice->getTotalPriceAttribute();
        $paid      = (float) InvoicePayment::where('invoice_id', $invoice->id)->sum('amount');
        $remaining = round($total - $paid, 2);

        if ($remaining <= 0) {
            $invoice->statu = 5; // Payée
            $invoice->InvoiceLines->each(fn ($l) => $l->update(['invoice_status' => 5]));
        } elseif ($paid > 0) {
            $invoice->statu = 3; // Partiellement réglée (en attente)
        }
        $invoice->save();

        return response()->json(['ok' => true, 'remaining' => max(0, $remaining)]);
    }

    public function paymentsDestroy(Invoices $invoice, InvoicePayment $payment): \Illuminate\Http\JsonResponse
    {
        abort_if($payment->invoice_id !== $invoice->id, 404);

        $payment->delete();

        // Recalcul du statut
        $total     = $invoice->getTotalPriceAttribute();
        $paid      = (float) InvoicePayment::where('invoice_id', $invoice->id)->sum('amount');
        $remaining = round($total - $paid, 2);

        if ($remaining <= 0) {
            $invoice->statu = 5;
        } elseif ($paid > 0) {
            $invoice->statu = 3;
        } else {
            $invoice->statu = 2; // Envoyée — plus de règlement
        }
        $invoice->save();

        return response()->json(['ok' => true, 'remaining' => max(0, $remaining)]);
    }
}
