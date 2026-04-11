<?php

namespace App\Http\Controllers\Products;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Products\SerialNumbers;

class SerialNumbersController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $totalCount   = SerialNumbers::count();
        $soldCount    = SerialNumbers::where('status', 2)->count();
        $shippedCount = SerialNumbers::where('status', 3)->count();
        $inStockCount = SerialNumbers::where('status', 5)->count();

        $byStatus = SerialNumbers::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $reactKpi = [
            'totalCount'   => $totalCount,
            'soldCount'    => $soldCount,
            'shippedCount' => $shippedCount,
            'inStockCount' => $inStockCount,
            'byStatus'     => $byStatus,
        ];

        $reactEndpoints = [
            'list' => route('products.serialNumbers.json.list'),
        ];

        return view('products/serial-numbers-index', compact('reactKpi', 'reactEndpoints'));
    }

    /**
     * JSON endpoint — paginated serial numbers list for the React component.
     */
    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);
        $statuses  = $request->get('statuses', []);

        $allowed = ['serial_number', 'status', 'created_at'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $productId = $request->get('product_id');

        $query = SerialNumbers::with([
                'Product:id,label',
                'OrderLine:id,label,order_id',
                'OrderLine.order:id,code',
                'Task:id',
                'purchaseReceiptLines:id,purchase_receipt_id',
                'purchaseReceiptLines.purchaseReceipt:id,code',
            ])
            ->when($productId, fn ($q) => $q->where('products_id', $productId))
            ->when($search, fn ($q) => $q->where('serial_number', 'like', '%'.$search.'%'))
            ->when(!empty($statuses), fn ($q) => $q->whereIn('status', $statuses))
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc');

        $paginated = $query->paginate(15);

        return response()->json([
            'data' => $paginated->map(fn ($sn) => [
                'id'            => $sn->id,
                'serial_number' => $sn->serial_number,
                'status'        => $sn->status,
                'created_at'    => $sn->GetPrettyCreatedAttribute(),
                'trace_url'     => route('production.trace', ['serial' => $sn->serial_number]),
                'product'       => $sn->Product ? [
                    'label' => $sn->Product->label,
                    'url'   => route('products.show', ['id' => $sn->products_id]),
                ] : null,
                'order'         => $sn->OrderLine?->order ? [
                    'code' => $sn->OrderLine->order->code,
                    'url'  => route('orders.show', ['id' => $sn->OrderLine->order->id]),
                ] : null,
                'task'          => $sn->Task ? [
                    'url' => route('production.task.statu.id', ['id' => $sn->task_id]),
                ] : null,
                'receipt'       => $sn->purchaseReceiptLines?->purchaseReceipt ? [
                    'code' => $sn->purchaseReceiptLines->purchaseReceipt->code,
                    'url'  => route('purchase.receipts.show', ['id' => $sn->purchaseReceiptLines->purchase_receipt_id]),
                ] : null,
            ]),
            'meta' => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }
}
