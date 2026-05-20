<?php

namespace App\Http\Controllers\Workflow;

use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Orders;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\QuoteLines;
use App\Models\Companies\Companies;
use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\InvoiceStatusChanged;
use App\Services\InvoiceService;
use App\Services\InvoiceLineService;
use App\Services\OrderService;
use App\Services\SelectDataService;
use App\Services\DocumentCodeGenerator;
use App\Services\InvoiceCalculatorService;

class ProformaController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private InvoiceLineService $invoiceLineService,
        private OrderService $orderService,
        private DocumentCodeGenerator $documentCodeGenerator,
        private SelectDataService $selectDataService,
    ) {}

    public function index()
    {
        $reactEndpoints = [
            'list'    => route('proformas.json.list'),
            'request' => route('proformas.request'),
        ];

        return view('workflow.proformas-index', [
            'reactEndpoints' => $reactEndpoints,
        ]);
    }

    public function listJson(Request $request)
    {
        $search   = $request->get('search', '');
        $statuses = array_filter(array_map('intval', (array) $request->get('statuses', [])));
        $sortField = $request->get('sort', 'created_at');
        $sortAsc  = $request->boolean('asc', false);

        $allowed = ['code', 'label', 'created_at', 'statu', 'companie'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir      = $sortAsc ? 'asc' : 'desc';
        $totalSub = 'COALESCE((SELECT SUM((order_lines.selling_price * invoice_lines.qty) * (1 - COALESCE(order_lines.discount,0)/100)) FROM invoice_lines JOIN order_lines ON invoice_lines.order_line_id = order_lines.id WHERE invoice_lines.invoices_id = invoices.id AND invoice_lines.deleted_at IS NULL), 0)';

        $query = Invoices::withCount('invoiceLines')
            ->selectRaw("invoices.*, {$totalSub} as total_amount")
            ->with(['companie:id,label,code', 'contact:id,first_name,name'])
            ->where('invoice_type', 3)
            ->when($search,   fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when($statuses, fn ($q) => $q->whereIn('statu', $statuses));

        match ($sortField) {
            'companie' => $query->orderByRaw("(SELECT label FROM companies WHERE companies.id = invoices.companies_id) {$dir}"),
            default    => $query->orderBy($sortField, $dir),
        };

        $proformas = $query->paginate(15);

        return response()->json([
            'data' => $proformas->map(fn ($p) => [
                'id'                  => $p->id,
                'code'                => $p->code,
                'label'               => $p->label,
                'statu'               => $p->statu,
                'created_at'          => $p->created_at?->format('d/m/Y'),
                'companie'            => $p->companie ? ['id' => $p->companie->id, 'label' => $p->companie->label] : null,
                'contact'             => $p->contact  ? ['id' => $p->contact->id,  'name'  => trim($p->contact->first_name.' '.$p->contact->name)] : null,
                'invoice_lines_count' => $p->invoice_lines_count,
                'total_amount'        => round((float) $p->total_amount, 2),
                'url'                 => route('proformas.show', ['id' => $p->id]),
                'url_pdf'             => route('pdf.proforma', ['Document' => $p->id]),
            ]),
            'meta' => [
                'total'        => $proformas->total(),
                'per_page'     => $proformas->perPage(),
                'current_page' => $proformas->currentPage(),
                'last_page'    => $proformas->lastPage(),
            ],
        ]);
    }

    public function request()
    {
        $reactProps = [
            'code'      => $this->documentCodeGenerator->peekNextCode('proforma'),
            'userId'    => Auth::id(),
            'users'     => $this->selectDataService->getUsers(),
            'companies' => $this->selectDataService->getCompanies(),
        ];

        $reactEndpoints = [
            'lines'          => route('proformas.request.lines'),
            'store'          => route('proformas.request.store'),
            'quotes'         => route('proformas.request.quotes'),
            'storeFromQuote' => route('proformas.request.store.quote'),
        ];

        return view('workflow.proformas-request', [
            'reactProps'     => $reactProps,
            'reactEndpoints' => $reactEndpoints,
        ]);
    }

    public function requestLines(Request $request)
    {
        $companyId = $request->get('company_id') ? (int) $request->get('company_id') : null;

        $addresses = $this->selectDataService->getAddress($companyId);
        $contacts  = $this->selectDataService->getContact($companyId);

        $lines = collect();
        if ($companyId) {
            $lines = OrderLines::with(['order:id,code,companies_id', 'Unit:id,label', 'VAT:id,rate,label'])
                ->whereHas('order', fn ($q) => $q->where('companies_id', $companyId)->whereIn('statu', [1, 2, 3, 4]))
                ->where('qty', '>', 0)
                ->get();
        }

        return response()->json([
            'addresses' => $addresses->map(fn ($a) => ['id' => $a->id, 'label' => $a->label, 'adress' => $a->adress]),
            'contacts'  => $contacts->map(fn ($c) => ['id' => $c->id, 'first_name' => $c->first_name, 'name' => $c->name]),
            'lines'     => $lines->map(fn ($l) => [
                'id'            => $l->id,
                'order_id'      => $l->orders_id,
                'order_code'    => $l->order?->code,
                'order_url'     => $l->order ? route('orders.show', ['id' => $l->orders_id]) : null,
                'code'          => $l->code,
                'label'         => $l->label,
                'qty'           => $l->qty,
                'unit_label'    => $l->Unit?->label,
                'selling_price' => $l->selling_price,
                'discount'      => $l->discount,
                'vat_label'     => $l->VAT?->label,
                'vat_id'        => $l->accounting_vats_id,
            ]),
        ]);
    }

    public function storeFromOrder(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'code'                   => 'required|unique:invoices',
            'label'                  => 'required|string|max:255',
            'companies_id'           => 'required|integer|min:1',
            'companies_addresses_id' => 'required|integer|min:1',
            'companies_contacts_id'  => 'required|integer|min:1',
            'user_id'                => 'required|integer|min:1',
            'lines'                  => 'required|array|min:1',
            'lines.*'                => 'integer',
        ]);

        $proforma = Invoices::create([
            'uuid'                   => Str::uuid(),
            'code'                   => $validated['code'],
            'label'                  => $validated['label'],
            'companies_id'           => $validated['companies_id'],
            'companies_addresses_id' => $validated['companies_addresses_id'],
            'companies_contacts_id'  => $validated['companies_contacts_id'],
            'user_id'                => $validated['user_id'],
            'invoice_type'           => 3,
            'statu'                  => 1,
        ]);

        $orderLines = OrderLines::whereIn('id', $validated['lines'])->get()->keyBy('id');
        $orderIds   = $orderLines->pluck('orders_id')->unique();
        if ($orderIds->count() === 1) {
            $proforma->order_id = $orderIds->first();
            $proforma->save();
        }

        $ordre = 10;
        foreach ($validated['lines'] as $orderLineId) {
            $orderLine = $orderLines->get($orderLineId);
            if (!$orderLine) continue;

            $this->invoiceLineService->createInvoiceLine(
                $proforma,
                $orderLine->id,
                null,
                $ordre,
                $orderLine->qty,
                $orderLine->accounting_vats_id,
            );
            $ordre += 10;
        }

        return response()->json([
            'redirect' => route('proformas.show', ['id' => $proforma->id]),
        ]);
    }

    // Returns quotes eligible for a proforma (not cancelled) for a given company.
    public function requestQuotes(Request $request)
    {
        $companyId = $request->get('company_id') ? (int) $request->get('company_id') : null;

        $quotes = Quotes::with('QuoteLines')
            ->when($companyId, fn ($q) => $q->where('companies_id', $companyId))
            ->whereNotIn('statu', [3, 4]) // exclure refusé/annulé
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'quotes' => $quotes->map(fn ($q) => [
                'id'    => $q->id,
                'code'  => $q->code,
                'label' => $q->label,
                'statu' => $q->statu,
                'lines' => $q->QuoteLines->map(fn ($l) => [
                    'id'            => $l->id,
                    'code'          => $l->code,
                    'label'         => $l->label,
                    'qty'           => $l->qty,
                    'selling_price' => $l->selling_price,
                    'discount'      => $l->discount,
                ]),
            ]),
        ]);
    }

    // Creates a draft order (statu=0) from selected quote lines, then builds the proforma on top.
    public function storeFromQuote(Request $request)
    {
        abort_unless(auth()->check(), 403);

        $validated = $request->validate([
            'code'                   => 'required|unique:invoices',
            'label'                  => 'required|string|max:255',
            'quote_id'               => 'required|integer|exists:quotes,id',
            'companies_id'           => 'required|integer|min:1',
            'companies_addresses_id' => 'required|integer|min:1',
            'companies_contacts_id'  => 'required|integer|min:1',
            'user_id'                => 'required|integer|min:1',
            'lines'                  => 'required|array|min:1',
            'lines.*'                => 'integer',
        ]);

        $quote = Quotes::with('QuoteLines')->findOrFail($validated['quote_id']);
        $factory = app('Factory');

        $order = DB::transaction(function () use ($quote, $validated, $factory) {
            $orderCode = $this->documentCodeGenerator->generateDocumentCode('order');

            $newOrder = $this->orderService->createOrder(
                $orderCode,
                $quote->label,
                $quote->customer_reference,
                $quote->companies_id,
                $quote->companies_contacts_id,
                $quote->companies_addresses_id,
                $quote->validity_date,
                0, // statu=0 : en attente de paiement
                Auth::id(),
                $quote->accounting_payment_conditions_id,
                $quote->accounting_payment_methods_id,
                $quote->accounting_deliveries_id,
                $quote->comment,
                1,
                $quote->id,
                null
            );

            $quoteLineMap = QuoteLines::whereIn('id', $validated['lines'])
                ->where('quotes_id', $quote->id)
                ->get()->keyBy('id');

            foreach ($validated['lines'] as $lineId) {
                $quoteLine = $quoteLineMap->get($lineId);
                if (!$quoteLine) continue;

                $deliveryDate  = $quoteLine->delivery_date ?? now()->addDays(30)->format('Y-m-d');
                $internalDelay = \Carbon\Carbon::parse($deliveryDate)
                    ->subDays((int) ($factory->add_delivery_delay_order ?? 0))
                    ->format('Y-m-d');

                OrderLines::create([
                    'orders_id'               => $newOrder->id,
                    'quote_lines_id'          => $quoteLine->id,
                    'ordre'                   => $quoteLine->ordre,
                    'code'                    => $quoteLine->code,
                    'product_id'              => $quoteLine->product_id,
                    'label'                   => $quoteLine->label,
                    'qty'                     => $quoteLine->qty,
                    'delivered_remaining_qty' => $quoteLine->qty,
                    'invoiced_remaining_qty'  => $quoteLine->qty,
                    'methods_units_id'        => $quoteLine->methods_units_id,
                    'selling_price'           => $quoteLine->selling_price,
                    'discount'                => $quoteLine->discount,
                    'accounting_vats_id'      => $quoteLine->accounting_vats_id,
                    'internal_delay'          => $internalDelay,
                    'delivery_date'           => $quoteLine->delivery_date,
                ]);
            }

            QuoteLines::whereIn('id', $quoteLineMap->keys()->all())->update(['statu' => 3]);
            Quotes::where('id', $quote->id)->update(['statu' => 3]);

            return $newOrder;
        });

        event(new OrderCreated($order));

        // Proforma sur les lignes de la commande brouillon
        $proforma = Invoices::create([
            'uuid'                   => Str::uuid(),
            'code'                   => $validated['code'],
            'label'                  => $validated['label'],
            'companies_id'           => $validated['companies_id'],
            'companies_addresses_id' => $validated['companies_addresses_id'],
            'companies_contacts_id'  => $validated['companies_contacts_id'],
            'user_id'                => $validated['user_id'],
            'invoice_type'           => 3,
            'statu'                  => 1,
            'order_id'               => $order->id,
        ]);

        $ordre = 10;
        foreach ($order->orderLines as $orderLine) {
            $this->invoiceLineService->createInvoiceLine(
                $proforma,
                $orderLine->id,
                null,
                $ordre,
                $orderLine->qty,
                $orderLine->accounting_vats_id,
            );
            $ordre += 10;
        }

        return response()->json([
            'redirect' => route('proformas.show', ['id' => $proforma->id]),
        ]);
    }

    // Activates the draft order linked to this proforma (statu 0 → 1) on payment confirmation.
    public function activateOrder($id)
    {
        $proforma = Invoices::where('invoice_type', 3)->findOrFail($id);

        abort_unless($proforma->order_id, 422);

        $order = Orders::findOrFail($proforma->order_id);
        abort_unless($order->statu === 0, 422);

        $order->statu = 1;
        $order->save();
        event(new OrderStatusChanged($order, 1));

        return response()->json(['ok' => true]);
    }

    public function show(Invoices $id)
    {
        abort_unless($id->invoice_type === 3, 404);

        $factory  = app('Factory');
        $currency = $factory->curency ?? 'EUR';
        $calc     = new InvoiceCalculatorService($id);

        $totalPrice = Number::currency($calc->getTotalPrice(), $currency, config('app.locale'));
        $subPrice   = Number::currency($calc->getSubTotal(),  $currency, config('app.locale'));
        $vatPrice   = $calc->getVatTotal();

        $companies = Companies::where('active', 1)->orderBy('code')->get(['id', 'code', 'label']);
        $addresses = $this->selectDataService->getAddress($id->companies_id);
        $contacts  = $this->selectDataService->getContact($id->companies_id);

        $linkedOrder = $id->order_id ? Orders::find($id->order_id) : null;

        return view('workflow.proformas-show', [
            'Proforma'     => $id,
            'totalPrices'  => $totalPrice,
            'subPrice'     => $subPrice,
            'vatPrice'     => $vatPrice,
            'companies'    => $companies,
            'addresses'    => $addresses,
            'contacts'     => $contacts,
            'companyAcUrl' => route('invoices.company-ac'),
            'linkedOrder'  => $linkedOrder,
        ]);
    }

    public function update(Request $request, $id)
    {
        $proforma = Invoices::where('invoice_type', 3)->findOrFail($id);

        $request->validate([
            'label'                  => 'required|string|max:255',
            'companies_id'           => 'required|integer',
            'companies_addresses_id' => 'required|integer',
            'companies_contacts_id'  => 'required|integer',
        ]);

        $proforma->update($request->only([
            'label', 'due_date', 'incoterm', 'comment',
            'companies_id', 'companies_addresses_id', 'companies_contacts_id', 'customer_reference',
        ]));

        return redirect()->route('proformas.show', ['id' => $proforma->id])->with('success', 'Proforma mise à jour.');
    }

    public function changeStatusJson(Request $request, $id)
    {
        $proforma = Invoices::where('invoice_type', 3)->findOrFail($id);
        $statu    = (int) $request->input('statu');

        $proforma->statu = $statu;
        $proforma->save();
        event(new InvoiceStatusChanged($proforma, $statu));

        foreach ($proforma->invoiceLines as $line) {
            $line->invoice_status = $statu;
            $line->save();
        }

        return response()->json(['ok' => true]);
    }

    // Converts the proforma into a real invoice (invoice_type 3 → 1).
    // The proforma document becomes the actual invoice — no new document created.
    public function convertToInvoice($id)
    {
        $proforma = Invoices::where('invoice_type', 3)->findOrFail($id);

        abort_unless(in_array($proforma->statu, [1, 2, 3]), 422);

        DB::transaction(function () use ($proforma) {
            $newCode = $this->documentCodeGenerator->generateDocumentCode('invoice');

            $proforma->invoice_type = 1;
            $proforma->code         = $newCode;
            $proforma->statu        = 1;
            $proforma->save();

            foreach ($proforma->invoiceLines as $invoiceLine) {
                $orderLine = OrderLines::find($invoiceLine->order_line_id);
                if (!$orderLine) continue;

                $orderLine->invoiced_qty             += $invoiceLine->qty;
                $orderLine->invoiced_remaining_qty   -= $invoiceLine->qty;
                $orderLine->invoiced_remaining_qty    = max(0, $orderLine->invoiced_remaining_qty);
                $orderLine->invoice_status            = $orderLine->invoiced_remaining_qty == 0 ? 3 : 2;
                $orderLine->save();
            }
        });

        return response()->json([
            'redirect' => route('invoices.show', ['id' => $proforma->id]),
        ]);
    }
}
