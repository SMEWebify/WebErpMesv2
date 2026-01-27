<?php

namespace App\Http\Controllers\Products;

use App\Models\Planning\Task;
use App\Services\StockService;
use App\Models\Products\Stocks;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use App\Models\Workflow\OrderLines;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Services\StockCalculationService;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\StoreStockMoveRequest;
use App\Http\Requests\Products\StoreStockLocationProductsRequest;
use App\Http\Requests\Products\UpdateStockLocationProductsRequest;

class StockLocationProductsController extends Controller
{

    protected $stockCalculationService;    
    protected $stockService;

    public function __construct(StockCalculationService $stockCalculationService,
                                StockService $stockService)
    {
        $this->stockCalculationService = $stockCalculationService;
        $this->stockService = $stockService;
    }
    
    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        
        $StockMoves = StockMove::where('stock_location_products_id', $id)->orderby('created_at', 'desc')->get();
        $StockLocationProduct = StockLocationProducts::findOrFail($id);
        $Product = Products::findOrFail($StockLocationProduct->products_id);
        $StockLocation = StockLocation::findOrFail($StockLocationProduct->stock_locations_id);
        $Stock = Stocks::findOrFail($StockLocation->stocks_id);
        $TaskList = Task::where('component_id', $id)
                        ->whereNotNull('order_lines_id')
                        ->orderby('created_at', 'desc')->get();
        $OrderLineList = OrderLines::where('product_id', $id)
                        ->orderby('created_at', 'desc')->get();

        $averageCost = $this->stockCalculationService->calculateWeightedAverageCost($id);

        return view('products/StockLocationProduct-show', [
            'Stock' => $Stock,
            'StockLocation' => $StockLocation,
            'StockLocationProduct' => $StockLocationProduct,
            'Product' => $Product,
            'StockMoves' => $StockMoves,
            'TaskList' => $TaskList,
            'OrderLineList' => $OrderLineList,
            'averageCost' => $averageCost,
        ]);
    }

    public function store(StoreStockLocationProductsRequest $request)
    {
        $StockLocationProduct = StockLocationProducts::create($request->only('code',
                                                                'user_id', 
                                                                'stock_locations_id',
                                                                'products_id', 
                                                                'mini_qty',
                                                                'end_date',
                                                                'addressing',
                                            ));
        return redirect()->route('products.stocklocation.show', ['id' => $StockLocationProduct->stock_locations_id])->with('success', 'Successfully created new stock line');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateStockLocationProductsRequest $request)
    {
        $StockLocationProduct = StockLocationProducts::find($request->id);
        $StockLocationProduct->mini_qty=$request->mini_qty;
        $StockLocationProduct->user_id=$request->user_id;
        $StockLocationProduct->end_date=$request->end_date;
        $StockLocationProduct->addressing=$request->addressing;
        $StockLocationProduct->save();
        return redirect()->route('products.stocklocation.show', ['id' => $request->stock_locations_id])->with('success', 'Successfully updated stock line'.  $StockLocationProduct->label);
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromInternalOrder(StoreStockLocationProductsRequest $request)
    {
        $StockLocationProduct = StockLocationProducts::create($request->only('code',
                                                                'user_id', 
                                                                'stock_locations_id',
                                                                'products_id', 
                                                                'mini_qty',
                                                                'end_date',
                                                                'addressing',
                                            ));

        $product = Products::find($StockLocationProduct->products_id);
        if ($product->tracability_type == 2) {
            $tracability = $this->stockService->generateBatchNumber();
        } else {
            $tracability = null;
        }

        $data = [
            'user_id' => $request->user_id,
            'qty' => $request->mini_qty,
            'stock_location_products_id' => $StockLocationProduct->id,
            'order_line_id' => $request->order_line_id,
            'typ_move' => 12,
            'component_price' => $request->component_price,
            'x_size' => $product->x_size,
            'y_size' => $product->y_size,
            'z_size' => $product->z_size,
            'tracability' => $tracability,
        ];

        $this->stockService->createStockMove($data);

        // Mise à jour de la ligne de commande
        $this->stockService->updateOrderLine($request->order_line_id, $request->mini_qty);

        return redirect()->route('products.stockline.show', ['id' => $StockLocationProduct->id])->with('success', 'Successfully created new move stock.');
    }
    
    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entryFromInternalOrder(StoreStockMoveRequest $request)
    {
        $data = $request->only('user_id', 'qty', 'stock_location_products_id', 'order_line_id', 'typ_move', 'component_price');
        $this->stockService->createStockMove($data);

        // Mise à jour de la ligne de commande
        $this->stockService->updateOrderLine($request->order_line_id, $request->qty);

        return redirect()->route('products.stockline.show', ['id' => $request->stock_location_products_id])->with('success', 'Successfully created new move stock.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromPurchaseOrder(StoreStockLocationProductsRequest $request)
    {
        $purchaseReceiptLine = PurchaseReceiptLines::findOrFail($request->purchase_receipt_line_id);
        $receivedQty = $this->resolveAcceptedQty($purchaseReceiptLine);

        $StockLocationProduct = StockLocationProducts::create($request->only('code',
                                                                'user_id', 
                                                                'stock_locations_id',
                                                                'products_id', 
                                                                'mini_qty',
                                                                'end_date',
                                                                'addressing',
                                            ));

        $product = Products::find($StockLocationProduct->products_id);
        if ($product->tracability_type == 2) {
            $tracability = $this->stockService->generateBatchNumber();
        } else {
            $tracability = null;
        }

        $data = [
            'user_id' => $request->user_id,
            'qty' => $receivedQty,
            'stock_location_products_id' => $StockLocationProduct->id,
            'task_id' => $request->task_id,
            'purchase_receipt_line_id' => $request->purchase_receipt_line_id,
            'typ_move' => 3,
            'component_price' => $request->component_price,
            'x_size' => $product->x_size,
            'y_size' => $product->y_size,
            'z_size' => $product->z_size,
            'tracability' => $tracability,
        ];

        $this->stockService->createStockMove($data);
    
        // Mise à jour de la ligne de réception de l'achat
        $this->stockService->updatePurchaseReceiptLine($request->purchase_receipt_line_id, $StockLocationProduct->id);

        return redirect()->route('products.stockline.show', ['id' => $StockLocationProduct->id])->with('success', 'Successfully created new move stock.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entryFromPurchaseOrder(StoreStockMoveRequest $request)
    {
        $purchaseReceiptLine = PurchaseReceiptLines::findOrFail($request->purchase_receipt_line_id);
        $receivedQty = $this->resolveAcceptedQty($purchaseReceiptLine);

        $data = $request->only('user_id', 'stock_location_products_id', 'task_id', 'purchase_receipt_line_id', 'typ_move', 'component_price');
        $data['qty'] = $receivedQty;
        $this->stockService->createStockMove($data);

        // Mise à jour de la ligne de réception de l'achat
        $this->stockService->updatePurchaseReceiptLine($request->purchase_receipt_line_id, $request->stock_location_products_id);

        return redirect()->route('products.stockline.show', ['id' => $request->stock_location_products_id])->with('success', 'Successfully created new move stock.');
   }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entry(StoreStockMoveRequest $request)
    {
        $data = [
            'user_id' => $request->user_id,
            'qty' => $request->qty,
            'stock_location_products_id' => $request->stock_location_products_id,
            'typ_move' => $request->typ_move,
            'x_size' => $request->x_size,
            'y_size' => $request->y_size,
            'z_size' => $request->z_size,
            'surface_perc' => $request->surface_perc,
            'tracability' => $request->tracability,
        ];

        $stockMove = $this->stockService->createStockMove($data);
        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function sorting(StoreStockMoveRequest $request)
    {
        $stockLocationProduct = StockLocationProducts::find($request->stock_location_products_id);

        // Vérifie si la quantité demandée est disponible avec la traçabilité
        if ($this->stockCalculationService->canDispatch($request->tracability, $request->qty, $stockLocationProduct->id)) {
            $data = [
                'user_id' => $request->user_id,
                'qty' => $request->qty,
                'stock_location_products_id' => $request->stock_location_products_id,
                'typ_move' => $request->typ_move,
                'order_line_id' => $request->order_line_id,
                'task_id' => $request->task_id,
                'tracability' => $request->tracability,
            ];

            $stockMove = $this->stockService->createStockMove($data);

            return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
        }
        else{
            return redirect()->route('products.stockline.show', ['id' => $request->stock_location_products_id])->with('error', 'Not enough stock available for this tracability');
        }
    }

    private function resolveAcceptedQty(PurchaseReceiptLines $purchaseReceiptLine): int
    {
        if ($purchaseReceiptLine->accepted_qty === null) {
            return (int) $purchaseReceiptLine->receipt_qty;
        }

        if ($purchaseReceiptLine->accepted_qty === 0 && ! $this->hasInspection($purchaseReceiptLine)) {
            return (int) $purchaseReceiptLine->receipt_qty;
        }

        return (int) $purchaseReceiptLine->accepted_qty;
    }

    private function hasInspection(PurchaseReceiptLines $purchaseReceiptLine): bool
    {
        return $purchaseReceiptLine->inspected_by !== null
            || $purchaseReceiptLine->inspection_date !== null
            || $purchaseReceiptLine->inspection_result !== null
            || (int) $purchaseReceiptLine->rejected_qty > 0
            || $purchaseReceiptLine->quality_non_conformity_id !== null;
    }
}
