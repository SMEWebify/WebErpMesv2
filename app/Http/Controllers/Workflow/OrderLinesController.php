<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use App\Services\ImportCsvService;
use App\Http\Controllers\Controller;
use App\Models\Workflow\OrderLines;
use App\Models\Workflow\OrderLineDetails;
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
}
