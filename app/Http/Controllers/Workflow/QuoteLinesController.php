<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Events\OrderCreated;
use App\Services\ImportCsvService;
use App\Services\CustomFieldService;
use App\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Models\Admin\Factory;
use App\Models\Admin\CustomField;
use App\Models\Admin\CustomFieldValue;
use App\Models\Workflow\Quotes;
use App\Models\Workflow\Orders;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\QuoteLineDetails;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\OrderLineDetails;
use App\Models\Products\Products;
use App\Models\Products\CustomerPriceList;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Accounting\AccountingVat;
use App\Models\Planning\Task;
use App\Models\Planning\SubAssembly;
use App\Http\Requests\Workflow\UpdateQuoteLineDetailsRequest;

class QuoteLinesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('workflow/quotes-lines-index');
    }

    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $sortField = $request->get('sort', 'label');
        $sortAsc   = $request->boolean('asc', true);
        $productId = $request->get('product_id');
        $statuses  = array_filter(array_map('intval', (array) $request->get('statuses', [])));

        $allowed = ['label', 'code', 'quotes_id', 'qty', 'selling_price', 'delivery_date', 'statu', 'created_at', 'ordre'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'label';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = QuoteLines::with(['quote:id,code', 'Unit:id,label', 'VAT:id,label'])
            ->withCount(['Task', 'SubAssembly'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when(is_numeric($productId), fn ($q) => $q->where('product_id', $productId))
            ->when(!empty($statuses), fn ($q) => $q->whereIn('statu', $statuses))
            ->orderBy($sortField, $dir);

        $lines = $query->paginate(15);

        return response()->json([
            'data' => $lines->map(fn ($l) => [
                'id'                   => $l->id,
                'quotes_id'            => $l->quotes_id,
                'quote_code'           => $l->quote?->code,
                'quote_url'            => route('quotes.show', ['id' => $l->quotes_id]),
                'ordre'                => $l->ordre,
                'code'                 => $l->code,
                'product_id'           => $l->product_id,
                'product_url'          => $l->product_id ? route('products.show', ['id' => $l->product_id]) : null,
                'label'                => $l->label,
                'qty'                  => $l->qty,
                'unit_label'           => $l->Unit?->label,
                'selling_price'        => (float) ($l->getRawOriginal('selling_price') ?? 0),
                'use_calculated_price' => (bool) $l->use_calculated_price,
                'discount'             => $l->discount,
                'vat_label'            => $l->VAT?->label,
                'delivery_date'        => $l->delivery_date,
                'statu'                => $l->statu,
                'task_count'           => $l->task_count,
                'sub_assembly_count'   => $l->sub_assembly_count,
                'task_url'             => route('task.manage', ['id_type' => 'quote_lines_id', 'id_page' => $l->quotes_id, 'id_line' => $l->id]),
            ]),
            'meta' => [
                'total'        => $lines->total(),
                'per_page'     => $lines->perPage(),
                'current_page' => $lines->currentPage(),
                'last_page'    => $lines->lastPage(),
            ],
        ]);
    }

    /**
     * @param \App\Http\Requests\Workflow\UpdateQuoteLineDetailsRequest $request
     * @param int $idQuote
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($idQuote, UpdateQuoteLineDetailsRequest $request)
    {
        $QuoteLineDetails = QuoteLineDetails::findOrFail($request->id);
        $validated = $request->validated();
        $validated['custom_requirements'] = $this->sanitizeCustomRequirements($request->input('custom_requirements', []));
        unset($validated['product_custom_fields']);

        $QuoteLineDetails->update($validated);
        $this->syncProductCustomFields(
            $QuoteLineDetails->quote_lines_id,
            $request->input('product_custom_fields', [])
        );

        return redirect()->route('quotes.show', ['id' => $idQuote])->with('success', 'Successfully updated quote detail line');
    }

    private function sanitizeCustomRequirements(array $requirements): array
    {
        return collect($requirements)
            ->map(function ($requirement) {
                return [
                    'label' => isset($requirement['label']) ? trim($requirement['label']) : '',
                    'value' => isset($requirement['value']) ? trim($requirement['value']) : '',
                ];
            })
            ->filter(function ($requirement) {
                return $requirement['label'] !== '' || $requirement['value'] !== '';
            })
            ->values()
            ->all();
    }

    private function syncProductCustomFields(int $quoteLineId, array $fields): void
    {
        if (empty($fields)) {
            return;
        }

        $validIds = CustomField::where('related_type', 'product')->pluck('id')->all();

        foreach ($fields as $fieldId => $fieldValue) {
            if (!in_array((int) $fieldId, $validIds, true)) {
                continue;
            }

            $existingValue = CustomFieldValue::where('custom_field_id', $fieldId)
                ->where('entity_id', $quoteLineId)
                ->where('entity_type', 'quote_line')
                ->first();

            if ($fieldValue === null || $fieldValue === '') {
                if ($existingValue) {
                    $existingValue->delete();
                }
                continue;
            }

            if ($existingValue) {
                $existingValue->update(['value' => $fieldValue]);
            } else {
                CustomFieldValue::create([
                    'custom_field_id' => $fieldId,
                    'entity_id' => $quoteLineId,
                    'entity_type' => 'quote_line',
                    'value' => $fieldValue,
                ]);
            }
        }
    }
    
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage($idQuote,Request $request)
    {
        
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $QuoteLineDetails = QuoteLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('images/quote-lines'), $filename);
            $QuoteLineDetails->update(['picture' => $filename]);
            $QuoteLineDetails->save();
            return redirect()->route('quotes.show', ['id' =>  $idQuote])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    /**
     * Imports quote lines from a CSV file.
     *
     * @param int $idQuote The ID of the quote to import lines into.
     * @param \Illuminate\Http\Request $request The HTTP request object containing the CSV file.
     * @param \App\Services\ImportCsvService $importCsvService The service used to import quote lines from the CSV file.
     * @return \Illuminate\Http\RedirectResponse A redirect response back to the previous page.
     */
    public function import($idQuote, Request $request, ImportCsvService $importCsvService)
    {
        $importCsvService->importQuoteLines($idQuote, $request);
        return redirect()->back();
    }

    // -------------------------------------------------------------------------
    // JSON API — React QuoteLinesPage
    // -------------------------------------------------------------------------

    public function linesForQuoteJson($quoteId)
    {
        $quote = Quotes::findOrFail($quoteId);
        abort_unless(auth()->check(), 403);

        $lines = QuoteLines::with([
                'Unit:id,label,code',
                'VAT:id,label,rate',
                'Product:id,code,label,drawing_file',
                'QuoteLineDetails:id,quote_lines_id,picture',
                'orderLine:id,quote_lines_id,orders_id',
                'orderLine.order:id,code',
            ])
            ->withCount(['Task', 'SubAssembly'])
            ->where('quotes_id', $quoteId)
            ->orderBy('ordre', 'asc')
            ->get();

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale', 'fr');

        return response()->json([
            'lines'       => $lines->map(fn ($l) => $this->formatLineJson($l, $currency, $locale)),
            'quote_statu' => $quote->statu,
        ]);
    }

    public function selectDataForQuoteJson($quoteId)
    {
        abort_unless(auth()->check(), 403);
        $quote   = Quotes::with('companie')->findOrFail($quoteId);
        $factory = Factory::first();

        return response()->json([
            'products'         => Products::select('id', 'label', 'code', 'methods_units_id', 'selling_price')->orderBy('code')->get(),
            'units'            => MethodsUnits::select('id', 'label', 'code', 'default')->orderBy('label')->get(),
            'vats'             => AccountingVat::select('id', 'label', 'rate', 'default')->orderBy('rate')->get(),
            'currency'         => $factory->curency ?? 'EUR',
            'customer_discount'=> (float) ($quote->companie->discount ?? 0),
            'customer_id'      => $quote->companie?->id,
            'customer_type'    => $quote->companie?->client_type !== null ? (int) $quote->companie->client_type : null,
            'default_delivery' => $quote->validity_date,
        ]);
    }

    public function priceListForProductJson($quoteId, $productId)
    {
        abort_unless(auth()->check(), 403);
        $quote       = Quotes::with('companie')->findOrFail($quoteId);
        $factory     = Factory::first();
        $currency    = $factory->curency ?? 'EUR';
        $locale      = config('app.locale', 'fr');
        $customerId  = $quote->companie?->id;
        $customerType = $quote->companie?->client_type !== null ? (int) $quote->companie->client_type : null;

        $priceList = CustomerPriceList::with('company')
            ->where('products_id', $productId)
            ->get()
            ->filter(function ($price) use ($customerId, $customerType) {
                if ($price->companies_id && $customerId && (int) $price->companies_id === (int) $customerId) {
                    return true;
                }
                if ($price->companies_id) {
                    return false;
                }
                if ($price->customer_type !== null && $customerType !== null) {
                    return (int) $price->customer_type === (int) $customerType;
                }
                return $price->companies_id === null && $price->customer_type === null;
            })
            ->values()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'min_qty'         => (int) $p->min_qty,
                'max_qty'         => $p->max_qty !== null ? (int) $p->max_qty : null,
                'price'           => (float) $p->price,
                'formatted_price' => Number::currency($p->price, $currency, $locale),
                'scope'           => $p->companies_id ? 'company' : ($p->customer_type !== null ? 'segment' : 'general'),
                'scope_label'     => $p->companies_id
                    ? ('Client - ' . ($p->company?->label ?? '#' . $p->companies_id))
                    : ($p->customer_type !== null ? 'Segment ' . $p->customer_type : 'Général'),
            ]);

        return response()->json(['price_list' => $priceList]);
    }

    public function storeLineJson($quoteId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $quote = Quotes::findOrFail($quoteId);
        abort_if($quote->statu != 1, 403);

        $validated = $request->validate([
            'ordre'              => 'required|numeric|min:1',
            'label'              => 'required|string|max:255',
            'qty'                => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'discount'           => 'required|numeric|min:0|max:100',
            'product_id'         => 'nullable|exists:products,id',
            'code'               => 'nullable|string|max:255',
            'methods_units_id'   => 'nullable|exists:methods_units,id',
            'accounting_vats_id' => 'nullable|exists:accounting_vats,id',
            'delivery_date'      => 'nullable|date',
        ]);

        $defaultVat  = AccountingVat::getDefault();
        $defaultUnit = MethodsUnits::getDefault();

        if (! $defaultVat || ! $defaultUnit) {
            return response()->json(['error' => 'No default VAT or Unit configured'], 422);
        }

        $line = QuoteLines::create([
            'quotes_id'          => $quoteId,
            'ordre'              => $validated['ordre'],
            'code'               => $validated['code'] ?? '',
            'product_id'         => $validated['product_id'] ?? null,
            'label'              => $validated['label'],
            'qty'                => $validated['qty'],
            'methods_units_id'   => $validated['methods_units_id'] ?? $defaultUnit->id,
            'selling_price'      => $validated['selling_price'],
            'discount'           => $validated['discount'],
            'accounting_vats_id' => $validated['accounting_vats_id'] ?? $defaultVat->id,
            'delivery_date'      => $validated['delivery_date'] ?? null,
        ]);

        $detailData = ['quote_lines_id' => $line->id];
        if ($line->product_id) {
            $product = Products::find($line->product_id);
            if ($product) {
                $detailData = array_merge($detailData, $this->buildDetailDataFromProduct($product));
            }
        }
        QuoteLineDetails::create($detailData);

        $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'QuoteLineDetails:id,quote_lines_id,picture']);
        $line->loadCount(['Task', 'SubAssembly']);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))], 201);
    }

    public function updateLineJson($quoteId, $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $line = QuoteLines::where('id', $id)->where('quotes_id', $quoteId)->firstOrFail();

        $validated = $request->validate([
            'ordre'              => 'required|numeric|min:0',
            'label'              => 'required|string|max:255',
            'qty'                => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'discount'           => 'required|numeric|min:0|max:100',
            'product_id'         => 'nullable|exists:products,id',
            'code'               => 'nullable|string|max:255',
            'methods_units_id'   => 'nullable|exists:methods_units,id',
            'accounting_vats_id' => 'nullable|exists:accounting_vats,id',
            'delivery_date'      => 'nullable|date',
            'statu'              => 'nullable|integer|min:1|max:6',
        ]);

        $line->update($validated);
        $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'QuoteLineDetails:id,quote_lines_id,picture']);
        $line->loadCount(['Task', 'SubAssembly']);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))]);
    }

    public function destroyLineJson($quoteId, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = QuoteLines::where('id', $id)->where('quotes_id', $quoteId)->firstOrFail();
        $line->delete();
        Task::where('quote_lines_id', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function breakDownLineJson($quoteId, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = QuoteLines::where('id', $id)->where('quotes_id', $quoteId)->firstOrFail();

        abort_if(!$line->product_id, 422);

        $firstStatus = \App\Models\Planning\Status::select('id')->orderBy('order')->first();
        $statusId    = $firstStatus?->id;

        Task::where('products_id', $line->product_id)->get()->each(function ($task) use ($id, $statusId) {
            $new                  = $task->replicate();
            $new->quote_lines_id  = $id;
            $new->products_id     = null;
            $new->status_id       = $statusId;
            $new->origin          = '3';
            $new->save();
        });

        SubAssembly::where('products_id', $line->product_id)->get()->each(function ($sub) use ($id) {
            $new                  = $sub->replicate();
            $new->quote_lines_id  = $id;
            $new->products_id     = null;
            $new->save();
        });

        $line->loadCount(['Task', 'SubAssembly']);
        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))]);
    }

    public function duplicateLineJson($quoteId, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = QuoteLines::where('id', $id)->where('quotes_id', $quoteId)->firstOrFail();

        $newLine        = $line->replicate();
        $newLine->ordre = $line->ordre + 1;
        $newLine->code  = $line->code . '#dup' . $line->id;
        $newLine->label = $line->label . '#dup' . $line->id;
        $newLine->save();

        $details = QuoteLineDetails::where('quote_lines_id', $id)->first();
        if ($details) {
            $newDetails                  = $details->replicate();
            $newDetails->quote_lines_id = $newLine->id;
            $newDetails->save();
        } else {
            QuoteLineDetails::create(['quote_lines_id' => $newLine->id]);
        }

        Task::where('quote_lines_id', $id)->get()->each(function ($t) use ($newLine) {
            $nt                  = $t->replicate();
            $nt->quote_lines_id = $newLine->id;
            $nt->origin          = '5';
            $nt->save();
        });

        SubAssembly::where('quote_lines_id', $id)->get()->each(function ($s) use ($newLine) {
            $ns                  = $s->replicate();
            $ns->quote_lines_id = $newLine->id;
            $ns->save();
        });

        $newLine->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'QuoteLineDetails:id,quote_lines_id,picture']);
        $newLine->loadCount(['Task', 'SubAssembly']);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($newLine, $currency, config('app.locale'))], 201);
    }

    public function moveLineJson($quoteId, $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $line      = QuoteLines::where('id', $id)->where('quotes_id', $quoteId)->firstOrFail();
        $direction = $request->input('direction');

        if ($direction === 'up') {
            $line->increment('ordre', 1);
        } else {
            $line->decrement('ordre', 1);
        }

        return response()->json(['ordre' => $line->fresh()->ordre]);
    }

    public function reorderJson($quoteId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $quote = Quotes::findOrFail($quoteId);
        abort_if($quote->statu != 1, 403);

        $request->validate([
            'order'          => 'required|array',
            'order.*.id'     => 'required|integer',
            'order.*.ordre'  => 'required|integer|min:1',
        ]);

        foreach ($request->order as $item) {
            QuoteLines::where('id', $item['id'])
                ->where('quotes_id', $quoteId)
                ->update(['ordre' => $item['ordre']]);
        }

        return response()->json(['success' => true]);
    }

    public function priceIncreaseJson($quoteId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $quote = Quotes::findOrFail($quoteId);
        abort_if($quote->statu != 1, 403);

        $request->validate(['amount' => 'required|numeric|gt:0']);
        $count = QuoteLines::where('quotes_id', $quoteId)->increment('selling_price', (float) $request->amount);

        return response()->json(['updated' => $count]);
    }

    public function detailEdit($idQuote, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = QuoteLines::with(['QuoteLineDetails', 'Product'])
            ->where('id', $id)
            ->where('quotes_id', $idQuote)
            ->firstOrFail();

        $factory              = Factory::first();
        $customFieldService   = app(CustomFieldService::class);
        $productCustomFields  = $customFieldService->getProductCustomFieldsForQuoteLine(
            $line->product_id ? (int) $line->product_id : null,
            $line->id
        );

        return view('workflow.quote-line-detail-edit', compact('line', 'idQuote', 'factory', 'productCustomFields'));
    }

    private function formatLineJson(QuoteLines $l, string $currency, string $locale): array
    {
        $rawPrice       = (float) ($l->getRawOriginal('selling_price') ?? 0);
        $effectivePrice = (float) $l->selling_price;

        return [
            'id'                   => $l->id,
            'quotes_id'            => $l->quotes_id,
            'ordre'                => $l->ordre,
            'code'                 => $l->code,
            'product_id'           => $l->product_id,
            'product_code'         => $l->Product?->code,
            'product_url'          => $l->product_id ? route('products.show', ['id' => $l->product_id]) : null,
            'product_drawing_file' => $l->Product?->drawing_file,
            'label'                => $l->label,
            'qty'                  => $l->qty,
            'methods_units_id'     => $l->methods_units_id,
            'unit_label'           => $l->Unit?->label,
            'unit_code'            => $l->Unit?->code,
            'selling_price'        => $rawPrice,
            'effective_price'      => $effectivePrice,
            'use_calculated_price' => (bool) $l->use_calculated_price,
            'formatted_price'      => Number::currency($effectivePrice, $currency, $locale),
            'discount'             => $l->discount,
            'accounting_vats_id'   => $l->accounting_vats_id,
            'vat_label'            => $l->VAT?->label,
            'delivery_date'        => $l->delivery_date,
            'statu'                => $l->statu,
            'task_count'           => $l->task_count,
            'sub_assembly_count'   => $l->sub_assembly_count,
            'detail_id'            => $l->QuoteLineDetails?->id,
            'picture'              => $l->QuoteLineDetails?->picture,
            'task_url'             => route('task.manage', ['id_type' => 'quote_lines_id', 'id_page' => $l->quotes_id, 'id_line' => $l->id]),
            'detail_url'           => route('quotes.lines.detail.edit', ['idQuote' => $l->quotes_id, 'id' => $l->id]),
            'order_code'           => $l->orderLine?->order?->code,
            'order_url'            => $l->orderLine?->order ? route('orders.show', ['id' => $l->orderLine->orders_id]) : null,
        ];
    }

    public function storeOrderJson(Request $request, int $quoteId)
    {
        $lineIds = $request->input('line_ids', []);
        if (empty($lineIds)) {
            return response()->json(['error' => 'Aucune ligne sélectionnée.'], 422);
        }

        $quote = Quotes::findOrFail($quoteId);
        abort_unless($quote->user_id === Auth::id() || Auth::user()->hasRole(['admin','manager']), 403);

        if (!in_array($quote->statu, [1, 2])) {
            return response()->json(['error' => 'Ce devis ne peut plus être converti en commande (statut invalide).'], 422);
        }

        $factory = Factory::first();

        $order = DB::transaction(function () use ($quote, $lineIds, $factory) {
            $lastOrder = Orders::latest('id')->first();
            $orderCode = $lastOrder ? 'OR-' . ($lastOrder->id + 1) : 'OR-1';

            $orderService = app(OrderService::class);
            $newOrder = $orderService->createOrder(
                $orderCode,
                $quote->label,
                $quote->customer_reference,
                $quote->companies_id,
                $quote->companies_contacts_id,
                $quote->companies_addresses_id,
                $quote->validity_date,
                1,
                Auth::id(),
                $quote->accounting_payment_conditions_id,
                $quote->accounting_payment_methods_id,
                $quote->accounting_deliveries_id,
                $quote->comment,
                1,
                $quote->id,
                null
            );

            $quoteLineMap = QuoteLines::with(['QuoteLineDetails', 'Task', 'SubAssembly'])
                ->whereIn('id', $lineIds)
                ->where('quotes_id', $quote->id)
                ->get()
                ->keyBy('id');

            foreach ($lineIds as $lineId) {
                $quoteLine = $quoteLineMap->get($lineId);
                if (!$quoteLine) continue;

                $deliveryDate = $quoteLine->delivery_date ?? now()->addDays(7)->format('Y-m-d');
                $date = \Carbon\Carbon::parse($deliveryDate);
                $internalDelay = $date->subDays((int) ($factory->add_delivery_delay_order ?? 0))->format('Y-m-d');

                $newOrderLine = OrderLines::create([
                    'orders_id'                 => $newOrder->id,
                    'quote_lines_id'            => $quoteLine->id,
                    'ordre'                     => $quoteLine->ordre,
                    'code'                      => $quoteLine->code,
                    'product_id'                => $quoteLine->product_id,
                    'label'                     => $quoteLine->label,
                    'qty'                       => $quoteLine->qty,
                    'delivered_remaining_qty'   => $quoteLine->qty,
                    'invoiced_remaining_qty'    => $quoteLine->qty,
                    'methods_units_id'          => $quoteLine->methods_units_id,
                    'selling_price'             => $quoteLine->selling_price,
                    'discount'                  => $quoteLine->discount,
                    'accounting_vats_id'        => $quoteLine->accounting_vats_id,
                    'internal_delay'            => $internalDelay,
                    'delivery_date'             => $quoteLine->delivery_date,
                ]);

                $detail = $quoteLine->QuoteLineDetails;
                if ($detail) {
                    OrderLineDetails::create([
                        'order_lines_id'     => $newOrderLine->id,
                        'x_size'             => $detail->x_size,
                        'y_size'             => $detail->y_size,
                        'z_size'             => $detail->z_size,
                        'x_oversize'         => $detail->x_oversize,
                        'y_oversize'         => $detail->y_oversize,
                        'z_oversize'         => $detail->z_oversize,
                        'diameter'           => $detail->diameter,
                        'diameter_oversize'  => $detail->diameter_oversize,
                        'material'           => $detail->material,
                        'thickness'          => $detail->thickness,
                        'finishing'          => $detail->finishing,
                        'weight'             => $detail->weight,
                        'bend_count'         => $detail->bend_count,
                        'material_loss_rate' => $detail->material_loss_rate,
                        'cad_file'           => $detail->cad_file,
                        'cam_file'           => $detail->cam_file,
                        'cad_file_path'      => $detail->cad_file_path,
                        'cam_file_path'      => $detail->cam_file_path,
                        'picture'             => $detail->picture,
                        'internal_comment'    => $detail->internal_comment,
                        'external_comment'    => $detail->external_comment,
                        'custom_requirements' => $detail->custom_requirements,
                    ]);
                }

                foreach ($quoteLine->Task as $task) {
                    $newTask = $task->replicate();
                    $newTask->order_lines_id = $newOrderLine->id;
                    $newTask->quote_lines_id = null;
                    $newTask->origin = '6';
                    $newTask->save();
                }

                if ($quoteLine->Task->isNotEmpty()) {
                    $newOrderLine->tasks_status = 2;
                    $newOrderLine->save();
                }

                foreach ($quoteLine->SubAssembly as $sub) {
                    $newSub = $sub->replicate();
                    $newSub->order_lines_id = $newOrderLine->id;
                    $newSub->quote_lines_id = null;
                    $newSub->save();
                }

                QuoteLines::where('id', $lineId)->update(['statu' => 3]);
            }

            Quotes::where('id', $quote->id)->update(['statu' => 3]);

            return $newOrder;
        });

        event(new OrderCreated($order));

        return response()->json([
            'redirect' => route('orders.show', ['id' => $order->id]),
        ]);
    }

    public function tasksForLineJson(int $quoteId, int $id)
    {
        abort_unless(auth()->check(), 403);
        $line = QuoteLines::where('id', $id)->where('quotes_id', $quoteId)->firstOrFail();

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale');

        $tasks = \App\Models\Planning\Task::with('service:id,label,color,picture,hourly_rate')
            ->where('quote_lines_id', $id)
            ->orderBy('ordre')
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'ordre'       => $t->ordre,
                'label'       => $t->label,
                'service'     => $t->service ? [
                    'label'   => $t->service->label,
                    'color'   => $t->service->color,
                    'picture' => $t->service->picture,
                ] : null,
                'total_time'  => $t->TotalTime(),
                'qty'         => $t->qty,
                'unit_price'  => \Illuminate\Support\Number::currency((float) $t->unit_price,  $currency, $locale),
                'unit_cost'   => \Illuminate\Support\Number::currency((float) $t->unit_cost,   $currency, $locale),
                'margin'      => $t->unit_cost > 0 ? $t->Margin() : null,
            ]);

        return response()->json([
            'tasks'                => $tasks,
            'use_calculated_price' => (bool) $line->use_calculated_price,
            'task_url'             => route('task.manage', ['id_type' => 'quote_lines_id', 'id_page' => $quoteId, 'id_line' => $id]),
        ]);
    }

    public function toggleCalculatedPriceJson(int $quoteId, int $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $line = QuoteLines::where('id', $id)->where('quotes_id', $quoteId)->firstOrFail();

        $enable = (bool) $request->input('enable');
        $line->update(['use_calculated_price' => $enable ? 1 : 0]);

        $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label', 'QuoteLineDetails:id,quote_lines_id,picture']);
        $line->loadCount(['Task', 'SubAssembly']);
        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))]);
    }

    public function createProductJson(int $quoteId, int $id)
    {
        $line = QuoteLines::with(['QuoteLineDetails', 'Task', 'SubAssembly'])
            ->where('id', $id)
            ->where('quotes_id', $quoteId)
            ->firstOrFail();

        abort_unless($line->code && $line->label, 422);

        $service = MethodsServices::where('type', 8)->first();
        $family  = $service ? MethodsFamilies::where('methods_services_id', $service->id)->first() : null;

        if (!$service || !$family) {
            return response()->json(['error' => 'Service composant (type 8) ou famille introuvable.'], 422);
        }

        $product = Products::create([
            'code'                => $line->code,
            'label'               => $line->label,
            'methods_services_id' => $service->id,
            'methods_families_id' => $family->id,
            'purchased'           => 2,
            'purchased_price'     => 1,
            'sold'                => 1,
            'selling_price'       => $line->selling_price,
            'methods_units_id'    => $line->methods_units_id,
            'tracability_type'    => 1,
        ]);

        // Copy details from QuoteLineDetails to product
        $detail = $line->QuoteLineDetails;
        if ($detail) {
            $product->material          = $detail->material;
            $product->thickness         = $detail->thickness;
            $product->finishing         = $detail->finishing;
            $product->weight            = $detail->weight;
            $product->bend_count        = $detail->bend_count;
            $product->x_size            = $detail->x_size;
            $product->y_size            = $detail->y_size;
            $product->z_size            = $detail->z_size;
            $product->x_oversize        = $detail->x_oversize;
            $product->y_oversize        = $detail->y_oversize;
            $product->z_oversize        = $detail->z_oversize;
            $product->diameter          = $detail->diameter;
            $product->diameter_oversize = $detail->diameter_oversize;
            $product->cad_file_path     = $detail->cad_file_path;
            $product->cam_file_path     = $detail->cam_file_path;
            $product->save();
        }

        // Duplicate tasks
        foreach ($line->Task as $task) {
            $newTask = $task->replicate();
            $newTask->products_id    = $product->id;
            $newTask->quote_lines_id = null;
            $newTask->origin         = '5';
            $newTask->save();
        }

        // Duplicate sub-assemblies
        foreach ($line->SubAssembly as $sub) {
            $newSub = $sub->replicate();
            $newSub->products_id    = $product->id;
            $newSub->quote_lines_id = null;
            $newSub->save();
        }

        // Link product back to the quote line
        $line->product_id = $product->id;
        $line->save();

        return response()->json([
            'product_id'   => $product->id,
            'product_code' => $product->code,
            'product_url'  => route('products.show', ['id' => $product->id]),
        ]);
    }

    public function createProductsFromLinesJson(int $quoteId, Request $request)
    {
        abort_unless(auth()->check(), 403);

        $request->validate([
            'line_ids'   => 'required|array|min:1',
            'line_ids.*' => 'integer',
        ]);

        $lineIds = $request->input('line_ids');

        $service = MethodsServices::where('type', 8)->first();
        $family  = $service ? MethodsFamilies::where('methods_services_id', $service->id)->first() : null;

        if (! $service || ! $family) {
            return response()->json(['error' => 'Service composant (type 8) ou famille introuvable.'], 422);
        }

        $created = [];
        $skipped = [];
        foreach ($lineIds as $lineId) {
            $line = QuoteLines::with(['QuoteLineDetails', 'Task', 'SubAssembly'])
                ->where('id', $lineId)
                ->where('quotes_id', $quoteId)
                ->first();

            if (! $line || ! $line->code || ! $line->label) continue;

            if (Products::where('code', $line->code)->exists()) {
                $skipped[] = ['line_id' => $line->id, 'code' => $line->code, 'label' => $line->label];
                continue;
            }

            $product = Products::create([
                'code'                => $line->code,
                'label'               => $line->label,
                'methods_services_id' => $service->id,
                'methods_families_id' => $family->id,
                'purchased'           => 2,
                'purchased_price'     => 1,
                'sold'                => 1,
                'selling_price'       => $line->selling_price,
                'methods_units_id'    => $line->methods_units_id,
                'tracability_type'    => 1,
            ]);

            $detail = $line->QuoteLineDetails;
            if ($detail) {
                $product->material          = $detail->material;
                $product->thickness         = $detail->thickness;
                $product->finishing         = $detail->finishing;
                $product->weight            = $detail->weight;
                $product->bend_count        = $detail->bend_count;
                $product->x_size            = $detail->x_size;
                $product->y_size            = $detail->y_size;
                $product->z_size            = $detail->z_size;
                $product->x_oversize        = $detail->x_oversize;
                $product->y_oversize        = $detail->y_oversize;
                $product->z_oversize        = $detail->z_oversize;
                $product->diameter          = $detail->diameter;
                $product->diameter_oversize = $detail->diameter_oversize;
                $product->cad_file_path     = $detail->cad_file_path;
                $product->cam_file_path     = $detail->cam_file_path;
                $product->save();
            }

            foreach ($line->Task as $task) {
                $newTask                  = $task->replicate();
                $newTask->products_id     = $product->id;
                $newTask->quote_lines_id  = null;
                $newTask->origin          = '5';
                $newTask->save();
            }

            foreach ($line->SubAssembly as $sub) {
                $newSub                 = $sub->replicate();
                $newSub->products_id    = $product->id;
                $newSub->quote_lines_id = null;
                $newSub->save();
            }

            $line->product_id = $product->id;
            $line->save();

            $created[] = [
                'line_id'     => $line->id,
                'product_id'  => $product->id,
                'product_url' => route('products.show', ['id' => $product->id]),
            ];
        }

        return response()->json(['created' => $created, 'skipped' => $skipped]);
    }

    private function buildDetailDataFromProduct(Products $product): array
    {
        return [
            'x_size'            => $product->x_size,
            'y_size'            => $product->y_size,
            'z_size'            => $product->z_size,
            'x_oversize'        => $product->x_oversize,
            'y_oversize'        => $product->y_oversize,
            'z_oversize'        => $product->z_oversize,
            'diameter'          => $product->diameter,
            'diameter_oversize' => $product->diameter_oversize,
            'material'          => $product->material,
            'thickness'         => $product->thickness,
            'finishing'         => $product->finishing,
            'weight'            => $product->weight,
            'bend_count'        => $product->bend_count,
            'cad_file_path'     => $product->cad_file_path,
            'cam_file_path'     => $product->cam_file_path,
        ];
    }

    // -------------------------------------------------------------------------
    // RADAN .sym import
    // -------------------------------------------------------------------------

    public function importSymJson(Request $request, int $quoteId)
    {
        abort_unless(auth()->check(), 403);

        if (! env('RADAN_SYM_IMPORT', false)) {
            return response()->json(['error' => 'Import RADAN désactivé'], 403);
        }

        $quote = Quotes::findOrFail($quoteId);
        abort_if($quote->statu != 1, 403);
        abort_unless($quote->user_id === Auth::id() || Auth::user()->hasRole(['admin', 'manager']), 403);

        $request->validate([
            'files'   => 'required|array|min:1',
            'files.*' => 'required|file|max:10240',
        ]);

        $defaultVat  = AccountingVat::getDefault();
        $defaultUnit = MethodsUnits::getDefault();

        if (! $defaultVat || ! $defaultUnit) {
            return response()->json(['error' => 'Aucune TVA ou unité par défaut configurée'], 422);
        }

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale');

        $nextOrdre    = (QuoteLines::where('quotes_id', $quoteId)->max('ordre') ?? 0) + 1;
        $createdLines = [];
        $errors       = [];

        foreach ($request->file('files') as $file) {
            try {
                $data = $this->parseSymFile($file);

                $line = QuoteLines::create([
                    'quotes_id'          => $quoteId,
                    'ordre'              => $nextOrdre++,
                    'code'               => $data['code'],
                    'label'              => $data['label'],
                    'qty'                => 1,
                    'methods_units_id'   => $defaultUnit->id,
                    'selling_price'      => 0,
                    'discount'           => 0,
                    'accounting_vats_id' => $defaultVat->id,
                ]);

                QuoteLineDetails::create([
                    'quote_lines_id'      => $line->id,
                    'material'            => $data['material'],
                    'thickness'           => $data['thickness'],
                    'x_size'              => $data['x_size'],
                    'y_size'              => $data['y_size'],
                    'weight'              => $data['weight'],
                    'cad_file'            => $data['code'],
                    'picture'             => $data['picture'],
                    'custom_requirements' => ! empty($data['extra']) ? $data['extra'] : null,
                ]);

                $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'QuoteLineDetails:id,quote_lines_id,picture']);
                $line->loadCount(['Task', 'SubAssembly']);

                $createdLines[] = $this->formatLineJson($line, $currency, $locale);
            } catch (\Exception $e) {
                $errors[] = $file->getClientOriginalName() . ' : ' . $e->getMessage();
            }
        }

        return response()->json(['lines' => $createdLines, 'errors' => $errors], 201);
    }

    private function parseSymFile(\Illuminate\Http\UploadedFile $file): array
    {
        $content = $file->get();

        // Strip namespace so SimpleXML can find elements without prefix
        $content = preg_replace('/\sxmlns="[^"]+"/', '', $content);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new \RuntimeException('Fichier XML invalide');
        }

        // Index all Attr elements by their num attribute
        $attrs = [];
        foreach ($xml->RadanAttributes->Group ?? [] as $group) {
            foreach ($group->Attr ?? [] as $attr) {
                $num = (int) $attr['num'];
                $attrs[$num] = isset($attr['value']) ? (string) $attr['value'] : null;
            }
        }

        $get = fn (int $num) => $attrs[$num] ?? null;

        $filename  = $get(110) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $material  = $get(119);
        $thickness = $get(120) !== null ? (float) $get(120) : null;
        $thickUnit = $get(121) ?? 'mm';
        $xSize     = $get(165) !== null ? (float) $get(165) : null;
        $ySize     = $get(166) !== null ? (float) $get(166) : null;
        $weight    = $get(164) !== null ? round((float) $get(164), 3) : null;
        $perimExt  = $get(167) !== null ? (float) $get(167) : null;
        $perimTot  = $get(168) !== null ? (float) $get(168) : null;
        $surface   = $get(162) !== null ? (float) $get(162) : null;
        $surfExt   = $get(163) !== null ? (float) $get(163) : null;
        $geoUnit   = $get(169) ?? 'mm';
        $laserCut  = $get(510) !== null ? (float) $get(510) : null;
        $laserPiercing = $get(512) !== null ? (int) $get(512) : null;
        $bendCount = $get(500) !== null ? (int) $get(500) : null;

        // Build label: "CODE - MATERIAU Xmm (XxYmm)"
        $labelParts = [$filename];
        if ($material) {
            $thickStr     = $thickness !== null ? " {$thickness}{$thickUnit}" : '';
            $labelParts[] = $material . $thickStr;
        }
        if ($xSize !== null && $ySize !== null) {
            $labelParts[] = "{$xSize}x{$ySize}{$geoUnit}";
        }

        // Extract thumbnail
        $picture = null;
        if (isset($xml->Thumbnail)) {
            $b64 = trim((string) $xml->Thumbnail);
            if ($b64 !== '') {
                $picture = $this->saveThumbnailFromSym($b64);
            }
        }

        // Extra RADAN metadata stored in custom_requirements JSON
        $extra = array_filter([
            'perimetre_ext'    => $perimExt,
            'perimetre_total'  => $perimTot,
            'surface'          => $surface,
            'surface_ext'      => $surfExt,
            'geo_unit'         => $geoUnit,
            'laser_cut_length' => $laserCut,
            'laser_piercings'  => $laserPiercing,
            'bend_count'       => $bendCount,
            'radan_source'     => true,
        ], fn ($v) => $v !== null && $v !== false);

        return [
            'code'      => $filename,
            'label'     => implode(' - ', $labelParts),
            'material'  => $material,
            'thickness' => $thickness,
            'x_size'    => $xSize,
            'y_size'    => $ySize,
            'weight'    => $weight,
            'picture'   => $picture,
            'extra'     => $extra ?: null,
        ];
    }

    private function saveThumbnailFromSym(string $base64): ?string
    {
        $binary = base64_decode(preg_replace('/\s+/', '', $base64));
        if ($binary === false || strlen($binary) < 10) {
            return null;
        }

        $dir = public_path('images/quote-lines');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Convert BMP → PNG via GD (PHP 8.x GD supports BMP)
        if (function_exists('imagecreatefromstring')) {
            $image = @imagecreatefromstring($binary);
            if ($image !== false) {
                $filename = time() . '_' . uniqid() . '.png';
                imagepng($image, $dir . '/' . $filename);
                imagedestroy($image);
                return $filename;
            }
        }

        // Fallback: save raw BMP
        $filename = time() . '_' . uniqid() . '.bmp';
        file_put_contents($dir . '/' . $filename, $binary);
        return $filename;
    }
}
