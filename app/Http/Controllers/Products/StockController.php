<?php

namespace App\Http\Controllers\Products;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Products\Stocks;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\StoreStockRequest;
use App\Http\Requests\Products\UpdateStockRequest;
use App\Models\Products\Products;
use App\Models\Planning\Task;
use App\Models\Planning\SubAssembly;
use App\Models\Accounting\AccountingVat;
use App\Services\OrderService;
use App\Services\StockValuationService;
use Illuminate\Support\Facades\Auth;
use App\Models\Workflow\OrderLineDetails;

class StockController extends Controller
{
    
    protected $SelectDataService;
    protected $orderService;

    public function __construct(SelectDataService $SelectDataService, OrderService $orderService)
    {
        $this->SelectDataService = $SelectDataService;
        $this->orderService = $orderService;
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $stocks = Stocks::withCount('StockLocation')->get();
        $userSelect = $this->SelectDataService->getUsers();
        $LastStock =  DB::table('stocks')->orderBy('id', 'desc')->first();
        $StockLocationList = StockLocation::all();
        $StockLocationProductList = StockLocationProducts::all();

        //Select order line where not delivered or partialy delivered
        $InternalOrderRequestsLineslist = OrderLines::where(
                                                        function($query) {
                                                            return $query
                                                                ->where('delivery_status', '=', '1')
                                                                ->orWhere('delivery_status', '=', '2');
                                                        })
                                                    ->whereHas('order', function($q){
                                                        $q->where('type', '=', '2');
                                                    })->get();

        return view('products/stocks-index', [
            'stocks' => $stocks,
            'userSelect' => $userSelect,
            'LastStock' => $LastStock,
            'StockLocationList' => $StockLocationList,
            'StockLocationProductList' => $StockLocationProductList,
            'InternalOrderRequestsLineslist' => $InternalOrderRequestsLineslist
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreStockRequest $request)
    {
        $Stock = Stocks::create($request->only('code','label', 'user_id'));
        return redirect()->route('products.stock.show', ['id' => $Stock->id])->with('success', 'Successfully created new stock');
    }

        /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $Stock = Stocks::findOrFail($id);
        $StockLocations = StockLocation::withCount('StockLocationProducts')->where('stocks_id', '=', $id)->get();
        $userSelect = $this->SelectDataService->getUsers();
        $LastStockLocation =  DB::table('stock_locations')->orderBy('id', 'desc')->first();
        
        return view('products/stock-show', [
            'Stock' => $Stock,
            'StockLocations' => $StockLocations,
            'userSelect' => $userSelect,
            'LastStockLocation' => $LastStockLocation
        ]);
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
    */
    public function update(UpdateStockRequest $request)
    {
        $Stock = Stocks::find($request->id);
        $Stock->label=$request->label;
        $Stock->user_id=$request->user_id;
        $Stock->save();
        return redirect()->route('products.stock')->with('success', 'Successfully updated stock '.  $Stock->label);
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Contracts\View\View
     */
    public function detail(Request $request)
    {
        $stockMove = StockMove::with([
            'UserManagement',
            'StockLocationProducts.product',
            'OrderLine.order',
            'Task',
            'purchaseReceiptLines.purchaseReceipt',
            'photos',
        ])->findOrFail($request->id);

        $factory = app('Factory');
        $user = auth()->user();

        $initial = [
            'id'              => $stockMove->id,
            'user_name'       => $stockMove->UserManagement?->name,
            'date'            => $stockMove->GetPrettyCreatedAttribute(),
            'qty'             => $stockMove->qty,
            'typ_move'        => $stockMove->typ_move,
            'formatted_price' => $stockMove->formatted_component_price,
            'x_size'          => $stockMove->x_size,
            'y_size'          => $stockMove->y_size,
            'z_size'          => $stockMove->z_size,
            'nb_part'         => $stockMove->nb_part,
            'surface_perc'    => $stockMove->surface_perc,
            'tracability'     => $stockMove->tracability,
            'product_code'    => $stockMove->StockLocationProducts?->product?->code,
            'order'           => $stockMove->order_line_id ? [
                'id'   => $stockMove->OrderLine?->order?->id,
                'code' => $stockMove->OrderLine?->order?->code,
                'url'  => $user->can('orders-menu')
                    ? route('orders.show', ['id' => $stockMove->OrderLine?->order?->id])
                    : null,
            ] : null,
            'task'            => $stockMove->task_id ? [
                'id'  => $stockMove->task_id,
                'url' => $user->can('scheduling-menu')
                    ? route('production.task.statu.id', ['id' => $stockMove->task_id])
                    : null,
            ] : null,
            'purchase_receipt' => $stockMove->purchase_receipt_line_id ? [
                'id'   => $stockMove->purchaseReceiptLines?->purchase_receipt_id,
                'code' => $stockMove->purchaseReceiptLines?->purchaseReceipt?->code,
                'url'  => $user->can('purchases-menu')
                    ? route('purchase.receipts.show', ['id' => $stockMove->purchaseReceiptLines?->purchase_receipt_id])
                    : null,
            ] : null,
            'barcode_base64' => \DNS1D::getBarcodePNG(
                strval($stockMove->id),
                $factory->task_barre_code ?? 'C128',
                4, 60, [1, 1, 1], true
            ),
            'photos'    => $stockMove->photos->map(fn($p) => [
                'name'               => $p->name,
                'original_file_name' => $p->original_file_name,
            ])->values()->toArray(),
            'trace_url' => $stockMove->tracability
                ? route('production.trace', ['serial' => $stockMove->tracability])
                : null,
        ];

        $props = [
            'initial'   => $initial,
            'endpoints' => [
                'update'      => route('products.stock.detail.update.json', ['id' => $stockMove->id]),
                'photo_store' => route('photo.store'),
            ],
        ];

        return view('products/stock-detail-show', compact('props'));
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
    */
    public function detailUpdate(Request $request)
    {
        $StockDetail = StockMove::find($request->id);
        $StockDetail->x_size=$request->x_size;
        $StockDetail->y_size=$request->y_size;
        $StockDetail->z_size=$request->z_size;
        $StockDetail->nb_part=$request->nb_part;
        $StockDetail->surface_perc=$request->surface_perc;
        $StockDetail->tracability=$request->tracability;
        $StockDetail->save();
        return redirect()->route('products.stock.detail.show', ['id' => $StockDetail->id])->with('success', 'Successfully updated stock');
    }

    public function stockCurrentJson(): \Illuminate\Http\JsonResponse
    {
        $products = Products::with(['Stock_location_product', 'undeliveredOrderLines', 'unFinishedTaskLines.OrderLines'])->get();

        $data = $products->map(function ($product) {
            $totalStockMove = $product->getTotalStockMove();
            $undeliveredQty = $product->getTotalUndeliveredQtyWithoutTasksAttribute();
            $taskQty        = $product->getTotalUnFinishedTaskLinesQtyAttribute();
            $qtyNeed        = $undeliveredQty + $taskQty;

            if ($totalStockMove > $qtyNeed) {
                $statusColor = 'success';
            } elseif ($totalStockMove < $qtyNeed) {
                $statusColor = 'danger';
            } else {
                $statusColor = 'warning';
            }

            $locations = $product->Stock_location_product->map(function ($loc) {
                $current = $loc->getCurrentStockMove();
                if ($current > $loc->mini_qty) {
                    $color = 'success';
                } elseif ($current < $loc->mini_qty) {
                    $color = 'danger';
                } else {
                    $color = 'warning';
                }
                return [
                    'id'            => $loc->id,
                    'code'          => $loc->code,
                    'url'           => route('products.stockline.show', ['id' => $loc->id]),
                    'current_stock' => $current,
                    'mini_qty'      => $loc->mini_qty,
                    'color'         => $color,
                ];
            })->values();

            return [
                'id'               => $product->id,
                'label'            => $product->label,
                'product_url'      => route('products.show', ['id' => $product->id]),
                'total_stock_move' => $totalStockMove,
                'qty_need'         => $qtyNeed,
                'undelivered_qty'  => $undeliveredQty,
                'task_qty'         => $taskQty,
                'status_color'     => $statusColor,
                'location_count'   => $product->StockLocationProductCount(),
                'locations'        => $locations,
            ];
        });

        return response()->json($data);
    }

    public function storeOrderJson(Request $request): \Illuminate\Http\JsonResponse
    {
        $productId       = $request->input('product_id');
        $productQuantity = $request->input('qty');

        $factory = app('Factory');

        $dateFormatted = Carbon::now()->format('d-m-y');
        $validity_date = Carbon::now()->addDays(7);

        $accountingVatDefaultId = AccountingVat::select('id')->where('default', 1)->first()?->id ?? 0;

        if ($accountingVatDefaultId === 0) {
            return response()->json(['error' => 'No default VAT settings'], 422);
        }

        $product      = Products::findOrFail($productId);
        $sellingPrice = $product->selling_price ?? 0;

        $user          = Auth::user();
        $order         = $this->orderService->createOrder(
            'INT-STOCK-' . $dateFormatted,
            'INT-STOCK-' . $dateFormatted,
            '', null, null, null,
            $validity_date, 1, $user->id,
            null, null, null, '', 2, null, null
        );

        $internalDelay = Carbon::instance($validity_date)
            ->subDays($factory->add_delivery_delay_order ?? 0)
            ->format('Y-m-d');

        $orderLine = OrderLines::create([
            'orders_id'               => $order->id,
            'ordre'                   => 10,
            'code'                    => $product->code,
            'product_id'              => $product->id,
            'label'                   => $product->label,
            'qty'                     => $productQuantity,
            'delivered_remaining_qty' => $productQuantity,
            'invoiced_remaining_qty'  => $productQuantity,
            'methods_units_id'        => $product->methods_units_id,
            'selling_price'           => $sellingPrice,
            'discount'                => 0,
            'accounting_vats_id'      => $accountingVatDefaultId,
            'internal_delay'          => $internalDelay,
            'delivery_date'           => $validity_date->format('Y-m-d'),
        ]);

        OrderLineDetails::create([
            'order_lines_id'     => $orderLine->id,
            'x_size'             => $product->x_size,
            'y_size'             => $product->y_size,
            'z_size'             => $product->z_size,
            'x_oversize'         => $product->x_oversize,
            'y_oversize'         => $product->y_oversize,
            'z_oversize'         => $product->z_oversize,
            'diameter'           => $product->diameter,
            'diameter_oversize'  => $product->diameter_oversize,
            'material'           => $product->material,
            'thickness'          => $product->thickness,
            'finishing'          => $product->finishing,
            'weight'             => $product->weight,
            'bend_count'         => $product->bend_count,
            'material_loss_rate' => 0,
            'cad_file'           => $product->drawing_file,
            'cam_file'           => null,
            'cad_file_path'      => $product->cad_file_path,
            'cam_file_path'      => $product->cam_file_path,
        ]);

        foreach (Task::where('products_id', $productId)->get() as $task) {
            $newTask                 = $task->replicate();
            $newTask->order_lines_id = $orderLine->id;
            $newTask->products_id    = null;
            $newTask->save();
        }

        foreach (SubAssembly::where('products_id', $productId)->get() as $sub) {
            $newSub                 = $sub->replicate();
            $newSub->order_lines_id = $orderLine->id;
            $newSub->products_id    = null;
            $newSub->save();
        }

        return response()->json([
            'redirect_url' => route('orders.show', ['id' => $order->id]),
        ]);
    }

    /**
     * JSON — total inventory value + per-line breakdown (CUMP-based).
     */
    public function valuationJson(): \Illuminate\Http\JsonResponse
    {
        $service   = app(StockValuationService::class);
        $total     = $service->getTotalInventoryValue();
        $breakdown = $service->getValuationBreakdown();

        return response()->json([
            'total'     => $total,
            'breakdown' => $breakdown,
        ]);
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function detailUpdateJson(Request $request)
    {
        $StockDetail = StockMove::findOrFail($request->id);
        $StockDetail->x_size       = $request->x_size;
        $StockDetail->y_size       = $request->y_size;
        $StockDetail->z_size       = $request->z_size;
        $StockDetail->surface_perc = $request->surface_perc;
        $StockDetail->tracability  = $request->tracability;
        $StockDetail->save();
        return response()->json(['success' => true]);
    }
}
