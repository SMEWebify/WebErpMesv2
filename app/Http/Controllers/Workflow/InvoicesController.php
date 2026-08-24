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
use App\Events\InvoiceStatusChanged;
use App\Models\Workflow\InvoiceLines;
use App\Models\Workflow\OrderLines;
use App\Services\InvoiceKPIService;
use App\Http\Controllers\Controller;
use App\Models\Integrations\PdpInvoiceSubmission;
use App\Services\CustomFieldService;
use App\Services\Integrations\Pdp\PdpManager;
use App\Services\InvoiceLineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\DeliveryLines;
use App\Models\Companies\Companies;
use App\Models\Companies\CompanyDocumentDefault;
use App\Models\Accounting\AccountingEntry;
use App\Models\Accounting\AccountingVat;
use App\Models\Methods\MethodsUnits;
use App\Services\InvoiceCalculatorService;
use App\Services\AccountingPeriodService;
use App\Http\Requests\Workflow\UpdateInvoiceRequest;
use Illuminate\Support\Facades\DB;

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
        // LEFT JOIN : une ligne libre n'a pas de ligne de commande et serait
        // sinon absente du total. Le snapshot porté par la ligne de facture
        // prime sur le prix courant de la ligne de commande.
        $totalSub = 'COALESCE((SELECT SUM((COALESCE(invoice_lines.unit_price, order_lines.selling_price, 0) * invoice_lines.qty) * (1 - COALESCE(invoice_lines.discount, order_lines.discount, 0)/100)) FROM invoice_lines LEFT JOIN order_lines ON invoice_lines.order_line_id = order_lines.id WHERE invoice_lines.invoices_id = invoices.id AND invoice_lines.deleted_at IS NULL), 0)';

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

        $pdpEnabled = app(PdpManager::class)->isEnabled();

        // Charger les statuts PDP en une seule requête si intégration active
        $pdpStatuses = [];
        if ($pdpEnabled) {
            $ids = $invoices->pluck('id');
            $pdpStatuses = PdpInvoiceSubmission::whereIn('invoice_id', $ids)
                ->pluck('lifecycle_status', 'invoice_id')
                ->all();
        }

        return response()->json([
            'pdp_enabled' => $pdpEnabled,
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
                'pdp_status'          => $pdpEnabled ? ($pdpStatuses[$inv->id] ?? null) : null,
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

        $defaults = $companyId ? CompanyDocumentDefault::forCompany($companyId)['invoice'] : ['contact_id' => null, 'address_id' => null];

        $defaultAddressId = $defaults['address_id'] && $addresses->contains('id', $defaults['address_id'])
            ? $defaults['address_id']
            : null;

        $defaultContactId = $defaults['contact_id'] && $contacts->contains('id', $defaults['contact_id'])
            ? $defaults['contact_id']
            : null;

        return response()->json([
            'default_address_id' => $defaultAddressId,
            'default_contact_id' => $defaultContactId,
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
            // 1 = Facturable, 3 = Partiellement : seules ces lignes sont facturées.
            // 2 = Non facturable, 4 = Facturé : ignorées.
            if (in_array((int) $DeliveryLine->invoice_status, [1, 3], true)) {
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

        $pdpEnabled = app(PdpManager::class)->isEnabled();
        $pdpSubmission = $pdpEnabled
            ? PdpInvoiceSubmission::where('invoice_id', $id->id)->first()
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
            'pdpEnabled'       => $pdpEnabled,
            'pdpSubmission'    => $pdpSubmission,
            'companies'        => $companies,
            'addresses'        => $addresses,
            'contacts'         => $contacts,
            'companyAcUrl'     => route('invoices.company-ac'),
            // Référentiels du formulaire d'ajout de ligne libre (brouillon).
            'vats'             => AccountingVat::orderBy('label')->get(['id', 'label', 'rate', 'default']),
            'units'            => MethodsUnits::orderBy('label')->get(['id', 'label', 'code', 'default']),
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
        event(new InvoiceStatusChanged($invoice, $statu));

        foreach ($invoice->InvoiceLines as $line) {
            $line->invoice_status = $statu;
            $line->save();
        }

        return response()->json(['ok' => true]);
    }

    public function updateLine(Request $request, int $id, int $lineId): \Illuminate\Http\JsonResponse
    {
        $invoice = Invoices::findOrFail($id);
        abort_unless(auth()->check(), 403);
        abort_if($invoice->statu !== 1, 403, 'La facture n\'est plus en brouillon.');

        $data = $request->validate([
            'qty'        => 'sometimes|numeric|min:0',
            'unit_price' => 'sometimes|numeric|min:0',
            'discount'   => 'sometimes|numeric|min:0|max:100',
        ]);

        $line = InvoiceLines::where('id', $lineId)->where('invoices_id', $id)->firstOrFail();
        $line->update($data);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        return response()->json([
            'ok'         => true,
            'unit_price' => $line->unit_price,
            'discount'   => $line->discount,
            'qty'        => $line->qty,
            'line_total' => round($line->qty * $line->unit_price * (1 - $line->discount / 100), 2),
            'formatted'  => Number::currency($line->unit_price, $currency, config('app.locale')),
        ]);
    }

    /**
     * Ajoute une ligne libre à une facture en brouillon.
     *
     * Couvre le frais oublié au moment de la commande — port, frais de dossier,
     * prestation ponctuelle — que l'on veut porter sur la facture existante
     * plutôt que sur un second document.
     *
     * Volontairement limité au brouillon : une facture émise est intangible
     * (art. L441-9 du code de commerce, art. 242 nonies A du CGI). Passée
     * l'émission, la correction relève de la facture complémentaire ou de l'avoir.
     */
    public function storeLine(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->check(), 403);
        $invoice = Invoices::findOrFail($id);
        abort_if($invoice->statu !== 1, 403, 'La facture n\'est plus en brouillon.');

        if (app(AccountingPeriodService::class)->isLocked($invoice->created_at)) {
            return response()->json([
                'message' => "Période {$invoice->created_at->format('m/Y')} verrouillée — cette facture ne peut plus être modifiée.",
            ], 422);
        }

        $data = $request->validate([
            'label'              => 'required|string|max:255',
            'code'               => 'nullable|string|max:255',
            'qty'                => 'required|numeric|min:0',
            'unit_price'         => 'required|numeric|min:0',
            'discount'           => 'nullable|numeric|min:0|max:100',
            'product_id'         => 'nullable|exists:products,id',
            'methods_units_id'   => 'nullable|exists:methods_units,id',
            'accounting_vats_id' => 'nullable|exists:accounting_vats,id',
        ]);

        $line = $this->invoiceLineService->createFreeLine($invoice, $data);

        return response()->json([
            'ok'   => true,
            'line' => $this->invoiceDataService->formatDraftLine($line),
        ], 201);
    }

    /**
     * Supprime une ligne d'une facture en brouillon.
     *
     * Une ligne issue d'une commande rend ses quantités à la ligne d'origine et
     * rouvre la ligne de BL : sans cela la commande resterait marquée facturée
     * sans facture en face. Aucun document n'étant encore sorti, il s'agit d'une
     * correction de saisie et non d'un avoir.
     */
    public function destroyLine(Request $request, int $id, int $lineId): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->check(), 403);
        $invoice = Invoices::findOrFail($id);
        abort_if($invoice->statu !== 1, 403, 'La facture n\'est plus en brouillon.');

        if (app(AccountingPeriodService::class)->isLocked($invoice->created_at)) {
            return response()->json([
                'message' => "Période {$invoice->created_at->format('m/Y')} verrouillée — cette facture ne peut plus être modifiée.",
            ], 422);
        }

        $line = InvoiceLines::where('id', $lineId)->where('invoices_id', $id)->firstOrFail();

        DB::transaction(function () use ($line) {
            // Écritures de vente générées à la création de la ligne.
            AccountingEntry::where('invoice_line_id', $line->id)->delete();

            if ($line->order_line_id) {
                $orderLine = OrderLines::lockForUpdate()->find($line->order_line_id);
                if ($orderLine) {
                    $orderLine->invoiced_qty            = max(0, $orderLine->invoiced_qty - $line->qty);
                    $orderLine->invoiced_remaining_qty += $line->qty;
                    $orderLine->invoice_status          = $orderLine->invoiced_qty <= 0 ? 1 : 2;
                    $orderLine->save();
                }
            }

            // La ligne de BL redevient facturable, sauf si une autre ligne de
            // facture la référence encore. Statut dérivé plutôt que forcé : on
            // ne repasse jamais « facturable » une ligne encore facturée ailleurs.
            if ($line->delivery_line_id) {
                $deliveryLine = DeliveryLines::find($line->delivery_line_id);
                if ($deliveryLine) {
                    $stillInvoiced = InvoiceLines::where('delivery_line_id', $deliveryLine->id)
                        ->where('id', '<>', $line->id)
                        ->exists();

                    $deliveryLine->invoice_status = $stillInvoiced ? 4 : 1;
                    $deliveryLine->save();
                    // Recalcule l'en-tête du BL (deliverys.invoice_status).
                    event(new DeliveryLineUpdated($deliveryLine));
                }
            }

            $line->delete();
        });

        return response()->json(['ok' => true]);
    }

    public function emit(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->check(), 403);
        $invoice = Invoices::findOrFail($id);
        abort_if($invoice->statu !== 1, 422, 'La facture n\'est pas en brouillon.');

        $invoice->statu = 2;
        $invoice->save();
        event(new InvoiceStatusChanged($invoice, 2));

        foreach ($invoice->InvoiceLines as $line) {
            $line->invoice_status = 2;
            $line->save();
        }

        return response()->json(['ok' => true, 'statu' => 2]);
    }

    /**
     * Dépose la facture sur la plateforme de dématérialisation active.
     *
     * Route web (session + CSRF) et non API : la carte est affichée dans une
     * page Blade authentifiée par session, elle n'a pas de jeton porteur.
     */
    public function pdpSubmit(int $id, \App\Services\Integrations\Pdp\PdpInvoiceService $pdpInvoiceService): \Illuminate\Http\JsonResponse
    {
        $invoice = Invoices::findOrFail($id);

        abort_if($invoice->invoice_type !== 1, 422, 'Seules les factures peuvent être déposées sur la plateforme.');
        abort_if($invoice->statu === 1, 422, "Cette facture est encore en brouillon : émettez-la avant de la déposer.");

        try {
            $submission = $pdpInvoiceService->submit($invoice);
        } catch (\RuntimeException $e) {
            // Données manquantes, document non conforme ou refus de la
            // plateforme : le message est rédigé pour l'utilisateur.
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'submission' => $submission]);
    }

    /** Interroge la plateforme et met à jour le statut de la facture. */
    public function pdpPoll(int $id, \App\Services\Integrations\Pdp\PdpInvoiceService $pdpInvoiceService): \Illuminate\Http\JsonResponse
    {
        $submission = PdpInvoiceSubmission::where('invoice_id', $id)->firstOrFail();

        return response()->json([
            'ok'         => true,
            'submission' => $pdpInvoiceService->poll($submission),
        ]);
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
        event(new InvoiceStatusChanged($invoice, $invoice->statu));

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
        event(new InvoiceStatusChanged($invoice, $invoice->statu));

        return response()->json(['ok' => true, 'remaining' => max(0, $remaining)]);
    }
}
