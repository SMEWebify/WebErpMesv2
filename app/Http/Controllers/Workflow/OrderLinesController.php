<?php

namespace App\Http\Controllers\Workflow;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use Illuminate\Support\Facades\Auth;
use App\Events\OrderLineUpdated;
use App\Services\ImportCsvService;
use App\Services\DeliveryService;
use App\Services\DeliveryLineService;
use App\Services\InvoiceService;
use App\Services\InvoiceLineService;
use App\Services\SerialNumberService;
use App\Services\DocumentCodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\Admin\Factory;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
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
use App\Http\Requests\Workflow\UpdateOrderLineDetailsRequest;

class OrderLinesController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('workflow/orders-lines-index');
    }

    public function listJson(Request $request)
    {
        $search           = $request->get('search', '');
        $sortField        = $request->get('sort', 'label');
        $sortAsc          = $request->boolean('asc', true);
        $productId        = $request->get('product_id');
        $deliveryStatuses = array_filter(array_map('intval', (array) $request->get('delivery_statuses', [])));

        $allowed = ['label', 'code', 'orders_id', 'qty', 'selling_price', 'delivery_date', 'tasks_status', 'delivery_status', 'invoice_status', 'created_at', 'ordre'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'label';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = OrderLines::with([
                'order:id,code,type',
                'Unit:id,label',
                'VAT:id,label',
                'DeliveryLines:id,order_line_id,qty,deliverys_id',
                'DeliveryLines.delivery:id,code',
                'InvoiceLines:id,order_line_id,qty,invoices_id',
                'InvoiceLines.invoice:id,code',
            ])
            ->withCount(['Task', 'SubAssembly'])
            ->when($search, fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->when(is_numeric($productId), fn ($q) => $q->where('product_id', $productId))
            ->when(!empty($deliveryStatuses), fn ($q) => $q->whereIn('delivery_status', $deliveryStatuses))
            ->orderBy($sortField, $dir);

        $lines = $query->paginate(15);

        return response()->json([
            'data' => $lines->map(fn ($l) => [
                'id'                   => $l->id,
                'orders_id'            => $l->orders_id,
                'order_code'           => $l->order?->code,
                'order_type'           => $l->order?->type,
                'order_url'            => route('orders.show', ['id' => $l->orders_id]),
                'ordre'                => $l->ordre,
                'code'                 => $l->code,
                'product_id'           => $l->product_id,
                'product_url'          => $l->product_id ? route('products.show', ['id' => $l->product_id]) : null,
                'label'                => $l->label,
                'qty'                  => $l->qty,
                'delivered_qty'        => $l->delivered_qty,
                'invoiced_qty'         => $l->invoiced_qty,
                'unit_label'           => $l->Unit?->label,
                'selling_price'        => (float) ($l->getRawOriginal('selling_price') ?? 0),
                'use_calculated_price' => (bool) $l->use_calculated_price,
                'discount'             => $l->discount,
                'vat_label'            => $l->VAT?->label,
                'delivery_date'        => $l->delivery_date,
                'tasks_status'         => $l->tasks_status,
                'delivery_status'      => $l->delivery_status,
                'invoice_status'       => $l->invoice_status,
                'delivery_progress'    => $l->qty > 0 ? round($l->delivered_qty / $l->qty * 100, 1) : 0,
                'invoice_progress'     => $l->qty > 0 ? round($l->invoiced_qty / $l->qty * 100, 1) : 0,
                'task_count'           => $l->task_count,
                'sub_assembly_count'   => $l->sub_assembly_count,
                'task_url'             => route('task.manage', ['id_type' => 'order_lines_id', 'id_page' => $l->orders_id, 'id_line' => $l->id]),
                'delivery_lines'       => $l->DeliveryLines->map(fn ($dl) => [
                    'id'           => $dl->id,
                    'qty'          => $dl->qty,
                    'delivery_code' => $dl->delivery?->code,
                    'delivery_url'  => $dl->deliverys_id ? route('deliverys.show', ['id' => $dl->deliverys_id]) : null,
                ]),
                'invoice_lines'        => $l->InvoiceLines->map(fn ($il) => [
                    'id'           => $il->id,
                    'qty'          => $il->qty,
                    'invoice_code' => $il->invoice?->code,
                    'invoice_url'  => $il->invoices_id ? route('invoices.show', ['id' => $il->invoices_id]) : null,
                ]),
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
     * @param \App\Http\Requests\Workflow\UpdateOrderLineDetailsRequest $request
     * @param int $idOrder
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update($idOrder, UpdateOrderLineDetailsRequest $request)
    {
        $OrderLineDetails = OrderLineDetails::findOrFail($request->id);
        $validated = $request->validated();
        $validated['custom_requirements'] = $this->sanitizeCustomRequirements($request->input('custom_requirements', []));

        $OrderLineDetails->update($validated);

        return redirect()->route('orders.show', ['id' => $idOrder])->with('success', 'Successfully updated order detail line');
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

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage($idOrder,Request $request)
    {
        
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $OrderLineDetails = OrderLineDetails::findOrFail($request->id);
            $file =  $request->file('picture');
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . uniqid() . '.' . $extension;
            $file->move(public_path('images/order-lines'), $filename);
            $OrderLineDetails->update(['picture' => $filename]);
            $OrderLineDetails->save();
            return redirect()->route('orders.show', ['id' =>  $idOrder])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    /**
     * Imports order lines from a CSV file.
     *
     * @param int $idOrder The ID of the order to import lines into.
     * @param \Illuminate\Http\Request $request The HTTP request instance containing the CSV file.
     * @param \App\Services\ImportCsvService $importCsvService The service responsible for importing CSV data.
     * @return \Illuminate\Http\RedirectResponse Redirects back to the previous page after import.
     */
    public function import($idOrder, Request $request, ImportCsvService $importCsvService)
    {
        $importCsvService->importOrderLines($idOrder, $request);
        return redirect()->back();
    }

    // -------------------------------------------------------------------------
    // JSON API — React OrderLinesPage
    // -------------------------------------------------------------------------

    public function linesForOrderJson($orderId)
    {
        $order = Orders::findOrFail($orderId);
        abort_unless(auth()->check(), 403);

        $lines = OrderLines::with([
                'Unit:id,label,code',
                'VAT:id,label,rate',
                'Product:id,code,label,drawing_file',
                'OrderLineDetails:id,order_lines_id,picture',
                'DeliveryLines:id,order_line_id,qty,deliverys_id',
                'DeliveryLines.delivery:id,code',
                'InvoiceLines:id,order_line_id,qty,invoices_id',
                'InvoiceLines.invoice:id,code',
            ])
            ->withCount(['Task', 'SubAssembly'])
            ->where('orders_id', $orderId)
            ->orderBy('ordre', 'asc')
            ->get();

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale', 'fr');

        return response()->json([
            'lines'        => $lines->map(fn ($l) => $this->formatLineJson($l, $currency, $locale)),
            'order_statu'  => $order->statu,
            'order_type'   => $order->type,
        ]);
    }

    public function selectDataForOrderJson($orderId)
    {
        abort_unless(auth()->check(), 403);
        $order   = Orders::with('companie')->findOrFail($orderId);
        $factory = Factory::first();

        return response()->json([
            'products'          => Products::select('id', 'label', 'code', 'methods_units_id', 'selling_price')->orderBy('code')->get(),
            'units'             => MethodsUnits::select('id', 'label', 'code', 'default')->orderBy('label')->get(),
            'vats'              => AccountingVat::select('id', 'label', 'rate', 'default')->orderBy('rate')->get(),
            'currency'          => $factory->curency ?? 'EUR',
            'customer_discount' => (float) ($order->companie->discount ?? 0),
            'customer_id'       => $order->companie?->id,
            'customer_type'     => $order->companie?->client_type !== null ? (int) $order->companie->client_type : null,
            'default_delivery'  => $order->validity_date,
        ]);
    }

    public function priceListForProductJson($orderId, $productId)
    {
        abort_unless(auth()->check(), 403);
        $order        = Orders::with('companie')->findOrFail($orderId);
        $factory      = Factory::first();
        $currency     = $factory->curency ?? 'EUR';
        $locale       = config('app.locale', 'fr');
        $customerId   = $order->companie?->id;
        $customerType = $order->companie?->client_type !== null ? (int) $order->companie->client_type : null;

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

    public function storeLineJson($orderId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $order = Orders::findOrFail($orderId);
        abort_if($order->statu == 6 || $order->type == 2, 403);

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

        $line = OrderLines::create([
            'orders_id'                 => $orderId,
            'ordre'                     => $validated['ordre'],
            'code'                      => $validated['code'] ?? '',
            'product_id'                => $validated['product_id'] ?? null,
            'label'                     => $validated['label'],
            'qty'                       => $validated['qty'],
            'delivered_remaining_qty'   => $validated['qty'],
            'invoiced_remaining_qty'    => $validated['qty'],
            'methods_units_id'          => $validated['methods_units_id'] ?? $defaultUnit->id,
            'selling_price'             => $validated['selling_price'],
            'discount'                  => $validated['discount'],
            'accounting_vats_id'        => $validated['accounting_vats_id'] ?? $defaultVat->id,
            'delivery_date'             => $validated['delivery_date'] ?? null,
        ]);

        $detailData = ['order_lines_id' => $line->id];
        if ($line->product_id) {
            $product = Products::find($line->product_id);
            if ($product) {
                $detailData = array_merge($detailData, $this->buildDetailDataFromProduct($product));
            }
        }
        OrderLineDetails::create($detailData);

        $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'OrderLineDetails:id,order_lines_id,picture']);
        $line->loadCount(['Task', 'SubAssembly']);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))], 201);
    }

    public function updateLineJson($orderId, $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $line = OrderLines::where('id', $id)->where('orders_id', $orderId)->firstOrFail();

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
        ]);

        $line->update($validated);
        $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'OrderLineDetails:id,order_lines_id,picture']);
        $line->loadCount(['Task', 'SubAssembly']);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))]);
    }

    public function destroyLineJson($orderId, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = OrderLines::where('id', $id)->where('orders_id', $orderId)->firstOrFail();
        $line->delete();
        Task::where('order_lines_id', $id)->delete();

        return response()->json(['success' => true]);
    }

    public function breakDownLineJson($orderId, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = OrderLines::where('id', $id)->where('orders_id', $orderId)->firstOrFail();

        abort_if(!$line->product_id, 422);

        $firstStatus = \App\Models\Planning\Status::select('id')->orderBy('order')->first();
        $statusId    = $firstStatus?->id;

        Task::where('products_id', $line->product_id)->get()->each(function ($task) use ($id, $statusId) {
            $new                 = $task->replicate();
            $new->order_lines_id = $id;
            $new->products_id    = null;
            $new->status_id      = $statusId;
            $new->origin         = '3';
            $new->save();
        });

        SubAssembly::where('products_id', $line->product_id)->get()->each(function ($sub) use ($id) {
            $new                 = $sub->replicate();
            $new->order_lines_id = $id;
            $new->products_id    = null;
            $new->save();
        });

        $line->tasks_status = 2;
        $line->save();

        $line->loadCount(['Task', 'SubAssembly']);
        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))]);
    }

    public function duplicateLineJson($orderId, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = OrderLines::where('id', $id)->where('orders_id', $orderId)->firstOrFail();

        $newLine        = $line->replicate();
        $newLine->ordre = $line->ordre + 1;
        $newLine->code  = $line->code . '#dup' . $line->id;
        $newLine->label = $line->label . '#dup' . $line->id;
        // Reset delivery/invoice tracking fields
        $newLine->delivered_qty           = 0;
        $newLine->delivered_remaining_qty = $line->qty;
        $newLine->invoiced_qty            = 0;
        $newLine->invoiced_remaining_qty  = $line->qty;
        $newLine->delivery_status         = 1;
        $newLine->invoice_status          = 1;
        $newLine->tasks_status            = 1;
        $newLine->save();

        $details = OrderLineDetails::where('order_lines_id', $id)->first();
        if ($details) {
            $newDetails                 = $details->replicate();
            $newDetails->order_lines_id = $newLine->id;
            $newDetails->save();
        } else {
            OrderLineDetails::create(['order_lines_id' => $newLine->id]);
        }

        Task::where('order_lines_id', $id)->get()->each(function ($t) use ($newLine) {
            $nt                   = $t->replicate();
            $nt->order_lines_id  = $newLine->id;
            $nt->origin           = '5';
            $nt->save();
        });

        SubAssembly::where('order_lines_id', $id)->get()->each(function ($s) use ($newLine) {
            $ns                  = $s->replicate();
            $ns->order_lines_id = $newLine->id;
            $ns->save();
        });

        $newLine->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'OrderLineDetails:id,order_lines_id,picture']);
        $newLine->loadCount(['Task', 'SubAssembly']);

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($newLine, $currency, config('app.locale'))], 201);
    }

    public function moveLineJson($orderId, $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $line      = OrderLines::where('id', $id)->where('orders_id', $orderId)->firstOrFail();
        $direction = $request->input('direction');

        if ($direction === 'up') {
            $line->increment('ordre', 1);
        } else {
            $line->decrement('ordre', 1);
        }

        return response()->json(['ordre' => $line->fresh()->ordre]);
    }

    public function reorderJson($orderId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $order = Orders::findOrFail($orderId);
        abort_if($order->statu == 6 || $order->type == 2, 403);

        $request->validate([
            'order'          => 'required|array',
            'order.*.id'     => 'required|integer',
            'order.*.ordre'  => 'required|integer|min:1',
        ]);

        foreach ($request->order as $item) {
            OrderLines::where('id', $item['id'])
                ->where('orders_id', $orderId)
                ->update(['ordre' => $item['ordre']]);
        }

        return response()->json(['success' => true]);
    }

    public function priceIncreaseJson($orderId, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $order = Orders::findOrFail($orderId);
        abort_if($order->statu == 6 || $order->type == 2, 403);

        $request->validate(['amount' => 'required|numeric|gt:0']);
        $count = OrderLines::where('orders_id', $orderId)->increment('selling_price', (float) $request->amount);

        return response()->json(['updated' => $count]);
    }

    public function detailEdit($idOrder, $id)
    {
        abort_unless(auth()->check(), 403);
        $line = OrderLines::with(['OrderLineDetails', 'Product'])
            ->where('id', $id)
            ->where('orders_id', $idOrder)
            ->firstOrFail();

        $factory = Factory::first();

        return view('workflow.order-line-detail-edit', compact('line', 'idOrder', 'factory'));
    }

    public function tasksForLineJson(int $orderId, int $id)
    {
        abort_unless(auth()->check(), 403);
        $line = OrderLines::where('id', $id)->where('orders_id', $orderId)->firstOrFail();

        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';
        $locale   = config('app.locale');

        $tasks = Task::with('service:id,label,color,picture,hourly_rate')
            ->where('order_lines_id', $id)
            ->orderBy('ordre')
            ->get()
            ->map(fn ($t) => [
                'id'         => $t->id,
                'ordre'      => $t->ordre,
                'label'      => $t->label,
                'service'    => $t->service ? [
                    'label'   => $t->service->label,
                    'color'   => $t->service->color,
                    'picture' => $t->service->picture,
                ] : null,
                'total_time' => $t->TotalTime(),
                'qty'        => $t->qty,
                'unit_price' => Number::currency((float) $t->unit_price, $currency, $locale),
                'unit_cost'  => Number::currency((float) $t->unit_cost,  $currency, $locale),
                'margin'     => $t->unit_cost > 0 ? $t->Margin() : null,
            ]);

        return response()->json([
            'tasks'                => $tasks,
            'use_calculated_price' => (bool) $line->use_calculated_price,
            'task_url'             => route('task.manage', ['id_type' => 'order_lines_id', 'id_page' => $orderId, 'id_line' => $id]),
        ]);
    }

    public function toggleCalculatedPriceJson(int $orderId, int $id, Request $request)
    {
        abort_unless(auth()->check(), 403);
        $line = OrderLines::where('id', $id)->where('orders_id', $orderId)->firstOrFail();

        $enable = (bool) $request->input('enable');
        $line->update(['use_calculated_price' => $enable ? 1 : 0]);

        $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label', 'OrderLineDetails:id,order_lines_id,picture']);
        $line->loadCount(['Task', 'SubAssembly']);
        $factory  = Factory::first();
        $currency = $factory->curency ?? 'EUR';

        return response()->json(['line' => $this->formatLineJson($line, $currency, config('app.locale'))]);
    }

    private function formatLineJson(OrderLines $l, string $currency, string $locale): array
    {
        $rawPrice       = (float) ($l->getRawOriginal('selling_price') ?? 0);
        $effectivePrice = (float) $l->selling_price;

        return [
            'id'                   => $l->id,
            'orders_id'            => $l->orders_id,
            'ordre'                => $l->ordre,
            'code'                 => $l->code,
            'product_id'           => $l->product_id,
            'product_code'         => $l->Product?->code,
            'product_url'          => $l->product_id ? route('products.show', ['id' => $l->product_id]) : null,
            'product_drawing_file' => $l->Product?->drawing_file,
            'label'                => $l->label,
            'qty'                  => $l->qty,
            'delivered_qty'        => $l->delivered_qty,
            'invoiced_qty'         => $l->invoiced_qty,
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
            'tasks_status'         => $l->tasks_status,
            'delivery_status'      => $l->delivery_status,
            'invoice_status'       => $l->invoice_status,
            'task_count'           => $l->task_count,
            'sub_assembly_count'   => $l->sub_assembly_count,
            'detail_id'            => $l->OrderLineDetails?->id,
            'picture'              => $l->OrderLineDetails?->picture,
            'task_url'             => route('task.manage', ['id_type' => 'order_lines_id', 'id_page' => $l->orders_id, 'id_line' => $l->id]),
            'detail_url'           => route('orders.lines.detail.edit', ['idOrder' => $l->orders_id, 'id' => $l->id]),
            'delivery_lines'       => ($l->relationLoaded('DeliveryLines') ? $l->DeliveryLines : collect())->map(fn ($dl) => [
                'id'           => $dl->id,
                'qty'          => $dl->qty,
                'delivery_code'=> $dl->delivery?->code,
                'delivery_url' => $dl->deliverys_id ? route('deliverys.show', ['id' => $dl->deliverys_id]) : null,
            ]),
            'invoice_lines'        => ($l->relationLoaded('InvoiceLines') ? $l->InvoiceLines : collect())->map(fn ($il) => [
                'id'          => $il->id,
                'qty'         => $il->qty,
                'invoice_code'=> $il->invoice?->code,
                'invoice_url' => $il->invoices_id ? route('invoices.show', ['id' => $il->invoices_id]) : null,
            ]),
        ];
    }

    private function buildDetailDataFromProduct(Products $product): array
    {
        return array_filter([
            'material'           => $product->material,
            'thickness'          => $product->thickness,
            'finishing'          => $product->finishing,
            'weight'             => $product->weight,
            'bend_count'         => $product->bend_count,
            'x_size'             => $product->x_size,
            'y_size'             => $product->y_size,
            'z_size'             => $product->z_size,
            'x_oversize'         => $product->x_oversize,
            'y_oversize'         => $product->y_oversize,
            'z_oversize'         => $product->z_oversize,
            'diameter'           => $product->diameter,
            'diameter_oversize'  => $product->diameter_oversize,
            'cad_file_path'      => $product->cad_file_path,
            'cam_file_path'      => $product->cam_file_path,
        ], fn ($v) => $v !== null && $v !== '');
    }

    // -------------------------------------------------------------------------
    // Bulk actions
    // -------------------------------------------------------------------------

    public function storeDeliveryJson(int $orderId, Request $request)
    {
        abort_unless(auth()->check(), 403);

        $request->validate([
            'line_ids'            => 'required|array|min:1',
            'line_ids.*'          => 'integer',
            'remove_from_stock'   => 'boolean',
            'create_serial_number'=> 'boolean',
        ]);

        $order = Orders::findOrFail($orderId);
        abort_if($order->statu == 6, 422, __('general_content.order_canceled_no_document_trans_key'));

        $lineIds           = $request->input('line_ids');
        $removeFromStock   = (bool) $request->input('remove_from_stock', false);
        $createSerialNumber= (bool) $request->input('create_serial_number', false);

        $lastDelivery = Deliverys::orderBy('id', 'desc')->first();
        $deliveryId   = $lastDelivery ? $lastDelivery->id : 0;
        $generator    = app(DocumentCodeGenerator::class);
        $deliveryCode = $generator->generateDocumentCode('delivery', $deliveryId);

        $deliveryService     = app(DeliveryService::class);
        $deliveryLineService = app(DeliveryLineService::class);
        $serialService       = app(SerialNumberService::class);

        $delivery = $deliveryService->createDelivery(
            $deliveryCode,
            $order->label,
            $order->companies_id,
            $order->companies_addresses_id,
            $order->companies_contacts_id,
            Auth::id(),
            $order->customer_reference
        );

        if (! $delivery) {
            return response()->json(['error' => 'Erreur lors de la création du BL'], 500);
        }

        $ordre = 10;
        foreach ($lineIds as $lineId) {
            $line = OrderLines::find($lineId);
            if (! $line || $line->orders_id !== $orderId) continue;

            $deliveryLineService->createDeliveryLine($delivery, $lineId, $ordre, $line->delivered_remaining_qty);

            if ($createSerialNumber) {
                for ($i = 0; $i < $line->delivered_remaining_qty; $i++) {
                    $serialService->createSerialNumber($line->product_id, $line->id, 2);
                }
            }

            $line->delivered_qty             += $line->delivered_remaining_qty;
            $line->delivered_remaining_qty    = 0;
            $line->delivery_status            = $line->delivered_remaining_qty == 0 ? 3 : 2;
            $line->save();
            event(new OrderLineUpdated($line));

            $ordre += 10;
        }

        return response()->json([
            'redirect' => route('deliverys.show', ['id' => $delivery->id]),
        ]);
    }

    public function storeInvoiceJson(int $orderId, Request $request)
    {
        abort_unless(auth()->check(), 403);

        $request->validate([
            'line_ids'            => 'required|array|min:1',
            'line_ids.*'          => 'integer',
            'remove_from_stock'   => 'boolean',
            'create_serial_number'=> 'boolean',
        ]);

        $order = Orders::findOrFail($orderId);
        abort_if($order->statu == 6, 422, __('general_content.order_canceled_no_document_trans_key'));

        $lineIds           = $request->input('line_ids');
        $removeFromStock   = (bool) $request->input('remove_from_stock', false);
        $createSerialNumber= (bool) $request->input('create_serial_number', false);

        $lastInvoice = Invoices::orderBy('id', 'desc')->first();
        $invoiceId   = $lastInvoice ? $lastInvoice->id : 0;
        $generator   = app(DocumentCodeGenerator::class);
        $invoiceCode = $generator->generateDocumentCode('invoice', $invoiceId);

        $invoiceService     = app(InvoiceService::class);
        $invoiceLineService = app(InvoiceLineService::class);
        $serialService      = app(SerialNumberService::class);

        $invoice = $invoiceService->createInvoice(
            $invoiceCode,
            $invoiceCode,
            $order->companies_id,
            $order->companies_addresses_id,
            $order->companies_contacts_id,
            Auth::id()
        );

        if (! $invoice) {
            return response()->json(['error' => 'Erreur lors de la création de la facture'], 500);
        }

        $ordre = 10;
        foreach ($lineIds as $lineId) {
            $line = OrderLines::find($lineId);
            if (! $line || $line->orders_id !== $orderId) continue;

            $invoiceLineService->createInvoiceLine($invoice, $lineId, null, $ordre, $line->invoiced_remaining_qty, $line->accounting_vats_id);

            if ($createSerialNumber) {
                for ($i = 0; $i < $line->invoiced_remaining_qty; $i++) {
                    $serialService->createSerialNumber($line->product_id, $line->id, 2);
                }
            }

            $line->delivered_qty             += $line->delivered_remaining_qty;
            $line->delivered_remaining_qty    = 0;
            $line->delivery_status            = $line->delivered_remaining_qty == 0 ? 3 : 2;

            $line->invoiced_qty              += $line->invoiced_remaining_qty;
            $line->invoiced_remaining_qty     = 0;
            $line->invoice_status             = $line->invoiced_remaining_qty == 0 ? 3 : 2;
            $line->save();
            event(new OrderLineUpdated($line));

            $ordre += 10;
        }

        return response()->json([
            'redirect' => route('invoices.show', ['id' => $invoice->id]),
        ]);
    }

    public function createProductsFromLinesJson(int $orderId, Request $request)
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
            $line = OrderLines::with(['OrderLineDetails', 'Task', 'SubAssembly'])
                ->where('id', $lineId)
                ->where('orders_id', $orderId)
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

            $detail = $line->OrderLineDetails;
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
                $newTask->order_lines_id  = null;
                $newTask->origin          = '5';
                $newTask->save();
            }

            foreach ($line->SubAssembly as $sub) {
                $newSub                 = $sub->replicate();
                $newSub->products_id    = $product->id;
                $newSub->order_lines_id = null;
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

    // -------------------------------------------------------------------------
    // RADAN .sym import
    // -------------------------------------------------------------------------

    public function importSymJson(Request $request, int $orderId)
    {
        abort_unless(auth()->check(), 403);

        if (! env('RADAN_SYM_IMPORT', false)) {
            return response()->json(['error' => 'Import RADAN désactivé'], 403);
        }

        $order = Orders::findOrFail($orderId);
        abort_if($order->statu == 6 || $order->type == 2, 403);
        abort_unless($order->user_id === Auth::id() || Auth::user()->hasRole(['admin', 'manager']), 403);

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

        $nextOrdre    = (OrderLines::where('orders_id', $orderId)->max('ordre') ?? 0) + 1;
        $createdLines = [];
        $errors       = [];

        foreach ($request->file('files') as $file) {
            try {
                $data = $this->parseSymFile($file);

                $internalDelay = $order->validity_date
                    ? Carbon::parse($order->validity_date)
                        ->subDays((int) ($factory->add_delivery_delay_order ?? 0))
                        ->format('Y-m-d')
                    : null;

                $line = OrderLines::create([
                    'orders_id'                 => $orderId,
                    'ordre'                     => $nextOrdre++,
                    'code'                      => $data['code'],
                    'label'                     => $data['label'],
                    'qty'                       => 1,
                    'delivered_remaining_qty'   => 1,
                    'invoiced_remaining_qty'    => 1,
                    'methods_units_id'          => $defaultUnit->id,
                    'selling_price'             => 0,
                    'discount'                  => 0,
                    'accounting_vats_id'        => $defaultVat->id,
                    'delivery_date'             => $order->validity_date,
                    'internal_delay'            => $internalDelay,
                ]);

                OrderLineDetails::create([
                    'order_lines_id'      => $line->id,
                    'material'            => $data['material'],
                    'thickness'           => $data['thickness'],
                    'x_size'              => $data['x_size'],
                    'y_size'              => $data['y_size'],
                    'weight'              => $data['weight'],
                    'cad_file'            => $data['code'],
                    'picture'             => $data['picture'],
                    'custom_requirements' => ! empty($data['extra']) ? $data['extra'] : null,
                ]);

                $line->load(['Unit:id,label,code', 'VAT:id,label,rate', 'Product:id,code,label,drawing_file', 'OrderLineDetails:id,order_lines_id,picture']);
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
        $content = preg_replace('/\sxmlns="[^"]+"/', '', $content);

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new \RuntimeException('Fichier XML invalide');
        }

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
