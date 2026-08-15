<?php

namespace App\Http\Controllers\Purchases;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Services\CustomFieldService;
use App\Services\PurchaseKPIService;
use App\Services\PurchaseOrderService;
use App\Services\DocumentCodeGenerator;
use App\Services\AccountingEntryService;
use App\Services\Integrations\Pdp\PdpIncomingInvoiceService;
use App\Services\PurchaseInvoiceService;
use App\Models\Integrations\PdpIncomingInvoice;
use App\Models\Purchases\PurchaseInvoice;
use App\Models\Purchases\PurchaseInvoiceLines;
use App\Models\Purchases\PurchaseLines;
use App\Models\Purchases\PurchaseReceiptLines;
use Illuminate\Validation\Rule;
use App\Services\AccountingPeriodService;
use App\Http\Requests\Purchases\UpdatePurchaseInvoiceRequest;

class PurchasesInvoiceController extends Controller
{
    use NextPreviousTrait;

    protected $SelectDataService;
    protected $purchaseKPIService;
    protected $customFieldService;
    protected $purchaseOrderService;
    protected $purchaseInvoiceService;
    protected $documentCodeGenerator;
    protected $accountingEntryService;

    public function __construct(
            SelectDataService $SelectDataService,
            PurchaseKPIService $purchaseKPIService,
            CustomFieldService $customFieldService,
            PurchaseOrderService $purchaseOrderService,
            PurchaseInvoiceService $purchaseInvoiceService,
            DocumentCodeGenerator $documentCodeGenerator,
            AccountingEntryService $accountingEntryService,
        ){
        $this->SelectDataService      = $SelectDataService;
        $this->purchaseKPIService     = $purchaseKPIService;
        $this->customFieldService     = $customFieldService;
        $this->purchaseOrderService   = $purchaseOrderService;
        $this->purchaseInvoiceService = $purchaseInvoiceService;
        $this->documentCodeGenerator  = $documentCodeGenerator;
        $this->accountingEntryService = $accountingEntryService;
    }

    /**
     * Display the waiting invoice view (React).
     */
    public function waintingInvoice()
    {
        $initialCode  = $this->documentCodeGenerator->peekNextCode('purchase-invoice');

        $reactEndpoints = [
            'init'  => route('purchases.waiting.invoice.json.init'),
            'store' => route('purchases.waiting.invoice.json.store'),
        ];

        $reactTrans = [
            'sort_company'      => __('general_content.sort_companie_trans_key'),
            'select_company'    => __('general_content.select_company_trans_key'),
            'no_select_company' => __('general_content.no_select_company_trans_key'),
            'external_id'       => __('general_content.external_id_trans_key'),
            'user'              => __('general_content.user_management_trans_key'),
            'select_user'       => __('general_content.select_user_management_trans_key'),
            'new_invoice'       => __('general_content.new_invoice_document_trans_key'),
            'order'             => __('general_content.order_trans_key'),
            'purchase_order'    => __('general_content.purchase_order_trans_key'),
            'purchase_receipt'  => __('general_content.purchase_receipt_trans_key'),
            'supplier'          => __('general_content.supplier_trans_key'),
            'description'       => __('general_content.description_trans_key'),
            'qty'               => __('general_content.qty_reciept_trans_key'),
            'action'            => __('general_content.action_trans_key'),
            'add_to_document'   => __('general_content.add_to_document_trans_key'),
            'no_data'           => __('general_content.no_data_trans_key'),
            'generic'           => __('general_content.generic_trans_key'),
            'view'              => __('general_content.view_trans_key'),
            'loading'           => __('general_content.notif_loading_trans_key'),
            'error'             => __('general_content.error_trans_key') ?? 'Erreur',
        ];

        return view('purchases/purchases-wainting-invoice', compact('reactEndpoints', 'reactTrans', 'initialCode'));
    }

    /**
     * JSON init — companies, users, initial code, waiting invoice lines.
     */
    public function waitingInvoiceInit(Request $request)
    {
        $companiesId = $request->get('companies_id', '');

        $companyIds = $this->purchaseInvoiceService->getUniqueCompanyIdsWithOpenPurchaseReceiptLines();
        $companies  = $this->SelectDataService->getSupplier($companyIds);
        $users      = $this->SelectDataService->getUsers();

        $initialCode = $this->documentCodeGenerator->peekNextCode('purchase-invoice');

        $lines = $this->purchaseInvoiceService->getPurchasesWaintingInvoiceLines($companiesId);

        $mappedLines = $lines->map(function ($line) {
            $purchaseLine = $line->purchaseLines;
            $task         = $purchaseLine?->tasks;
            $orderLine    = $task?->OrderLines;
            $order        = $orderLine?->order;

            // Montants : le prix vient de la ligne de commande d'achat, la
            // quantité de la réception. C'est ce qui permet de confronter le
            // total réellement dû au total facturé par le fournisseur.
            $unitPrice = (float) ($purchaseLine?->unit_price_after_discount ?? 0);

            return [
                'id'              => $line->id,
                'receipt_qty'     => $line->receipt_qty,
                'unit_price'      => $unitPrice,
                'line_total'      => round($unitPrice * (float) $line->receipt_qty, 2),
                // order
                'order_id'        => $order?->id,
                'order_code'      => $order?->code,
                'order_url'       => $orderLine?->orders_id ? route('orders.show', ['id' => $orderLine->orders_id]) : null,
                // purchase
                'purchase_id'     => $purchaseLine?->purchase?->id,
                'purchase_code'   => $purchaseLine?->purchase?->code,
                'purchase_url'    => $purchaseLine?->purchase?->id ? route('purchases.show', ['id' => $purchaseLine->purchase->id]) : null,
                // receipt
                'receipt_id'      => $line->purchase_receipt_id,
                'receipt_code'    => $line->purchaseReceipt?->code,
                'receipt_url'     => $line->purchase_receipt_id ? route('purchase.receipts.show', ['id' => $line->purchase_receipt_id]) : null,
                // supplier
                'supplier_code'   => $line->purchaseReceipt?->companie?->code,
                'supplier_label'  => $line->purchaseReceipt?->companie?->label,
                // task / description
                'tasks_id'        => $purchaseLine?->tasks_id,
                'task_id'         => $task?->id,
                'task_url'        => $task ? route('production.task.statu.id', ['id' => $task->id]) : null,
                'line_code'       => $purchaseLine?->code,
                'line_label'      => $purchaseLine?->label,
                'component_label' => $task?->component_id ? optional($task->Component)->label : null,
            ];
        });

        return response()->json([
            'companies'    => $companies->map(fn($c) => ['id' => $c->id, 'code' => $c->code, 'label' => $c->label]),
            'users'        => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
            'initial_code' => $initialCode,
            'lines'        => $mappedLines,
            'incoming'     => $this->incomingDocument($request->get('incoming_id')),
        ]);
    }

    /**
     * Facture électronique reçue servant de base au rapprochement.
     *
     * Ses lignes ne deviennent jamais des lignes de facture d'achat : celles-ci
     * référencent une commande et une réception, seule garantie qu'on ne paie
     * que ce qui a été commandé et reçu. Le document du fournisseur est donc
     * affiché **en regard** de la sélection, comme pièce à confronter.
     */
    private function incomingDocument($incomingId): ?array
    {
        if (! $incomingId) {
            return null;
        }

        $incoming = PdpIncomingInvoice::find($incomingId);

        if (! $incoming) {
            return null;
        }

        return [
            'id'             => $incoming->id,
            'seller_name'    => $incoming->seller_name,
            'invoice_number' => $incoming->invoice_number,
            'issue_date'     => optional($incoming->issue_date)->format('d/m/Y'),
            'due_date'       => optional($incoming->due_date)->format('d/m/Y'),
            'currency'       => $incoming->currency,
            'total_ht'       => (float) $incoming->total_ht,
            'total_vat'      => (float) $incoming->total_vat,
            'total_ttc'      => (float) $incoming->total_ttc,
            'companies_id'   => $incoming->supplier_company_id,
            'lines'          => array_map(fn (array $l) => [
                'name'       => $l['name'] ?? '',
                'quantity'   => (float) ($l['quantity'] ?? 0),
                'unit_code'  => $l['unit_code'] ?? null,
                'line_total' => (float) ($l['line_total'] ?? 0),
            ], $incoming->payload['lines'] ?? []),
        ];
    }

    /**
     * JSON store — create a purchase invoice from selected receipt lines.
     */
    public function storeInvoiceJson(Request $request)
    {
        $validated = $request->validate([
            'code'               => 'required|unique:purchase_invoices',
            'companies_id'       => 'required|exists:companies,id',
            'user_id'            => 'required|exists:users,id',
            'line_ids'           => 'required|array|min:1',
            'line_ids.*'         => 'integer|exists:purchase_receipt_lines,id',
            'incoming_id'        => 'nullable|integer|exists:pdp_incoming_invoices,id',
            'supplier_reference' => [
                'nullable', 'string', 'max:100',
                Rule::unique('purchase_invoices')->where(
                    fn ($q) => $q->where('companies_id', $request->companies_id)
                ),
            ],
        ]);

        $invoice = PurchaseInvoice::create([
            'code'               => $validated['code'],
            'label'              => $validated['code'],
            'companies_id'       => $validated['companies_id'],
            'user_id'            => $validated['user_id'],
            'supplier_reference' => $validated['supplier_reference'] ?? null,
        ]);

        foreach ($validated['line_ids'] as $receiptLineId) {
            $receiptLine    = PurchaseReceiptLines::find($receiptLineId);
            $accountingType = 2;

            if ($receiptLine->purchaseLines->tasks_id == 0) {
                $accountingType = 5;
            } elseif ($receiptLine->purchaseLines->tasks?->OrderLines?->order?->type == 2) {
                $accountingType = 5;
            }

            $allocationId = $this->accountingEntryService->getAllocationId(
                $accountingType,
                $receiptLine->purchaseLines->accounting_vats_id
            );

            $invoiceLine = PurchaseInvoiceLines::create([
                'purchase_invoice_id'        => $invoice->id,
                'purchase_receipt_line_id'   => $receiptLine->id,
                'purchase_line_id'           => $receiptLine->purchase_line_id,
                'accounting_allocation_id'   => $allocationId,
            ]);

            if ($allocationId !== null) {
                $this->accountingEntryService->createPurchaseEntry($invoiceLine);
            }

            PurchaseLines::where('id', $receiptLine->purchase_line_id)
                ->increment('invoiced_qty', $receiptLine->receipt_qty);
        }

        // Rattache la facture électronique reçue : elle passe en « convertie »
        // et cesse d'apparaître comme à traiter dans la boîte de réception.
        if (! empty($validated['incoming_id'])) {
            $incoming = PdpIncomingInvoice::find($validated['incoming_id']);

            if ($incoming) {
                app(PdpIncomingInvoiceService::class)->attachPurchaseInvoice($incoming, $invoice);
            }
        }

        return response()->json(['redirect' => route('purchase.invoices.show', ['id' => $invoice->id])]);
    }

    /**
     * Display a specific purchase invoice.
     */
    public function showInvoice(PurchaseInvoice $id)
    {
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new PurchaseInvoice(), $id->id);

        return view('purchases/purchases-invoice-show', [
            'PurchaseInvoice' => $id,
            'previousUrl'     => $previousUrl,
            'nextUrl'         => $nextUrl,
        ]);
    }

    /**
     * Update a purchase invoice.
     */
    public function updatePurchaseInvoice(UpdatePurchaseInvoiceRequest $request)
    {
        $PurchaseInvoice = PurchaseInvoice::find($request->id);

        if (app(AccountingPeriodService::class)->isLocked($PurchaseInvoice->created_at)) {
            return redirect()->back()->withErrors(['period' => "Période {$PurchaseInvoice->created_at->format('m/Y')} verrouillée — cette facture ne peut plus être modifiée."]);
        }

        $PurchaseInvoice->label              = $request->label;
        $PurchaseInvoice->statu              = $request->statu;
        $PurchaseInvoice->comment            = $request->comment;
        $PurchaseInvoice->supplier_reference = $request->supplier_reference ?: null;
        $PurchaseInvoice->save();

        return redirect()->route('purchase.invoices.show', ['id' => $PurchaseInvoice->id])
            ->with('success', __('general_content.purchase_receipt_updated_success_trans_key'));
    }

    /**
     * Display the invoice index with KPI/chart data.
     */
    public function invoice()
    {
        $purchasesDataRate           = $this->purchaseKPIService->getPurchaseInvoiceDataRate();
        $purchaseInvoiceMonthlyRecap = $this->purchaseKPIService->getPurchaseInvoiceMonthlyRecap();

        $totalCount      = PurchaseInvoice::count();
        $toBePostedCount = PurchaseInvoice::where('statu', 2)->count();
        $closedCount     = PurchaseInvoice::where('statu', 3)->count();

        $reactKpi = [
            'totalCount'      => $totalCount,
            'toBePostedCount' => $toBePostedCount,
            'closedCount'     => $closedCount,
        ];

        $reactChart = [
            'purchaseInvoicesDataRate'    => $purchasesDataRate,
            'purchaseInvoiceMonthlyRecap' => $purchaseInvoiceMonthlyRecap,
        ];

        $reactEndpoints = [
            'list' => route('purchases.invoice.json.list'),
        ];

        $reactTrans = [
            'total_invoices'      => __('general_content.total_trans_key'),
            'to_be_posted'        => __('general_content.to_be_posted_trans_key'),
            'closed'              => __('general_content.close_trans_key'),
            'in_progress'         => __('general_content.in_progress_trans_key'),
            'code'                => __('general_content.id_trans_key'),
            'label'               => __('general_content.label_trans_key'),
            'company'             => __('general_content.customer_trans_key'),
            'lines_count'         => __('general_content.lines_count_trans_key'),
            'status'              => __('general_content.status_trans_key'),
            'created_at'          => __('general_content.created_at_trans_key'),
            'action'              => __('general_content.action_trans_key'),
            'search'              => __('general_content.search_trans_key'),
            'no_data'             => __('general_content.no_data_trans_key'),
            'results'             => 'résultats',
            'view'                => __('general_content.view_trans_key'),
            'status_distribution' => __('general_content.statistiques_trans_key'),
            'monthly_recap'       => __('general_content.statistiques_trans_key'),
            'invoices'            => __('general_content.invoices_trans_key'),
            'jan' => 'Jan', 'feb' => 'Fév', 'mar' => 'Mar', 'apr' => 'Avr',
            'may' => 'Mai', 'jun' => 'Jun', 'jul' => 'Jul', 'aug' => 'Aoû',
            'sep' => 'Sep', 'oct' => 'Oct', 'nov' => 'Nov', 'dec' => 'Déc',
        ];

        return view('purchases/purchases-invoice', compact(
            'reactKpi',
            'reactChart',
            'reactEndpoints',
            'reactTrans',
        ));
    }

    /**
     * JSON endpoint — paginated list of purchase invoices for the React component.
     */
    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);

        $allowed = ['code', 'label', 'companie', 'statu', 'created_at', 'lines_count'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = PurchaseInvoice::withCount('PurchaseInvoiceLines as lines_count')
            ->with(['companie:id,label'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses));

        match ($sortField) {
            'companie'    => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = purchase_invoices.companies_id) {$dir}"),
            'lines_count' => $query->orderBy('lines_count', $dir),
            default       => $query->orderBy($sortField, $dir),
        };

        $invoices = $query->paginate(15);

        return response()->json([
            'data' => $invoices->map(fn ($inv) => [
                'id'          => $inv->id,
                'code'        => $inv->code,
                'label'       => $inv->label,
                'statu'       => $inv->statu,
                'created_at'  => $inv->created_at?->format('d/m/Y'),
                'companie'    => $inv->companie ? ['id' => $inv->companie->id, 'label' => $inv->companie->label] : null,
                'lines_count' => $inv->lines_count,
                'url'         => route('purchase.invoices.show', ['id' => $inv->id]),
            ]),
            'meta' => [
                'total'        => $invoices->total(),
                'per_page'     => $invoices->perPage(),
                'current_page' => $invoices->currentPage(),
                'last_page'    => $invoices->lastPage(),
            ],
        ]);
    }
}
