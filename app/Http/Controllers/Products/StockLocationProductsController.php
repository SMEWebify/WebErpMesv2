<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Products\Stocks;
use App\Events\OrderLineUpdated;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use App\Models\Workflow\OrderLines;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Models\Purchases\PurchaseReceiptLines;
use App\Http\Requests\Products\StoreStockMoveRequest;
use App\Http\Requests\Products\StoreStockLocationProductsRequest;
use App\Http\Requests\Products\UpdateStockLocationProductsRequest;

class StockLocationProductsController extends Controller
{
    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        
        $StockMoves = StockMove::where('stock_location_products_id', $id)->orderby('created_at', 'desc')->get();
        $StockLocationProduct = StockLocationProducts::findOrFail($id);
        $StockLocation = StockLocation::findOrFail($StockLocationProduct->stock_locations_id);
        $Stock = Stocks::findOrFail($StockLocation->stocks_id);
        $TaskList = Task::where('component_id', $id)
                        ->whereNotNull('order_lines_id')
                        ->orderby('created_at', 'desc')->get();
        $OrderLineList = OrderLines::where('product_id', $id)
                        ->orderby('created_at', 'desc')->get();
        
        return view('products/StockLocationProduct-show', [
            'Stock' => $Stock,
            'StockLocation' => $StockLocation,
            'StockLocationProduct' => $StockLocationProduct,
            'StockMoves' => $StockMoves,
            'TaskList' => $TaskList,
            'OrderLineList' => $OrderLineList,
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

        $stockMove = StockMove::create(['user_id' => $request->user_id, 
                                        'qty' => $request->mini_qty,
                                        'stock_location_products_id' =>  $StockLocationProduct->id, 
                                        'order_line_id' =>$request->order_line_id,
                                        'typ_move' => 12,
                                        'component_price' => $request->component_price,
                                        'x_size' => $product->x_size,
                                        'y_size' => $product->y_size,
                                        'z_size' => $product->z_size,
                                    ]);

        // update order line info
        //same function from deliverys request livewire controler
        $OrderLine = OrderLines::find($request->order_line_id);
        $OrderLine->delivered_qty =  $request->mini_qty;
        $OrderLine->delivered_remaining_qty = $OrderLine->delivered_remaining_qty - $request->mini_qty;
        //if we are delivered all part
        if($OrderLine->delivered_remaining_qty == 0){
            $OrderLine->delivery_status = 3;
            event(new OrderLineUpdated($OrderLine->id));
        }
        else{
            $OrderLine->delivery_status = 2;
            // update order statu info
            event(new OrderLineUpdated($OrderLine->id));
        }
        $OrderLine->save();

        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function storeFromPurchaseOrder(StoreStockLocationProductsRequest $request)
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

        $stockMove = StockMove::create(['user_id' => $request->user_id, 
                                        'qty' => $request->mini_qty,
                                        'stock_location_products_id' =>   $StockLocationProduct->id,  
                                        'task_id' =>$request->task_id,
                                        'purchase_receipt_line_id' =>$request->purchase_receipt_line_id,
                                        'typ_move' =>3,
                                        'component_price' => $request->component_price,
                                        'x_size' => $product->x_size,
                                        'y_size' => $product->y_size,
                                        'z_size' => $product->z_size,
                                    ]);
    
            /* // update stock if line of purchase order line*/
            PurchaseReceiptLines::where('id',$request->purchase_receipt_line_id,)->update(['stock_location_products_id'=>$StockLocationProduct->id]);
    

        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
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
    public function entryFromInternalOrder(StoreStockMoveRequest $request)
    {
        $stockMove = StockMove::create(['user_id' => $request->user_id, 
                                        'qty' => $request->qty,
                                        'stock_location_products_id' =>  $request->stock_location_products_id, 
                                        'order_line_id' =>$request->order_line_id,
                                        'typ_move' => $request->typ_move,
                                        'component_price' => $request->component_price,
                                    ]);

        // update order line info
        //same function from deliverys request livewire controler
        $OrderLine = OrderLines::find($request->order_line_id);
        $OrderLine->delivered_qty =  $request->qty;
        $OrderLine->delivered_remaining_qty = $OrderLine->delivered_remaining_qty - $request->qty;
        //if we are delivered all part
        if($OrderLine->delivered_remaining_qty == 0){
            $OrderLine->delivery_status = 3;
            event(new OrderLineUpdated($OrderLine->id));
        }
        else{
            $OrderLine->delivery_status = 2;
            // update order statu info
            event(new OrderLineUpdated($OrderLine->id));
        }
        $OrderLine->save();

        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
    }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entryFromPurchaseOrder(StoreStockMoveRequest $request)
    {
        $stockMove = StockMove::create(['user_id' => $request->user_id, 
                                        'qty' => $request->qty,
                                        'stock_location_products_id' =>  $request->stock_location_products_id, 
                                        'task_id' =>$request->task_id,
                                        'purchase_receipt_line_id' =>$request->purchase_receipt_line_id,
                                        'typ_move' => $request->typ_move,
                                        'component_price' => $request->component_price,
                                    ]);

        /* // update stock if line of purchase order line*/
        PurchaseReceiptLines::where('id',$request->purchase_receipt_line_id,)->update(['stock_location_products_id'=>$request->stock_location_products_id]);

        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
   }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function entry(StoreStockMoveRequest $request)
    {
        $stockMove = StockMove::create($request->only('user_id', 
                                                        'qty',
                                                        'stock_location_products_id', 
                                                        'typ_move',
                                                        'x_size',
                                                        'y_size',
                                                        'z_size',
                                                        'surface_perc',
                                                        'tracability',
                                                    ));
        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
   }

    /**
    * @param \Illuminate\Http\Request $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function sorting(StoreStockMoveRequest $request)
    {
        $stockMove = StockMove::create($request->only('user_id', 
                                                        'qty',
                                                        'stock_location_products_id', 
                                                        'typ_move',
                                                        'order_line_id',
                                                        'task_id',
                                                        'tracability',
                                                    ));
        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
    }
}
