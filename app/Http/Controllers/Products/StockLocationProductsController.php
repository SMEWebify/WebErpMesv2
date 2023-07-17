<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Products\Stocks;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\StoreStockMoveRequest;
use App\Http\Requests\Products\StoreStockLocationProductsRequest;
use App\Http\Requests\Products\UpdateStockLocationProductsRequest;

class StockLocationProductsController extends Controller
{
    public function show($id)
    {
        
        $StockMoves = StockMove::where('stock_location_products_id', $id)->orderby('created_at', 'desc')->get();
        $StockLocationProduct = StockLocationProducts::findOrFail($id);
        $StockLocation = StockLocation::findOrFail($StockLocationProduct->stock_locations_id);
        $Stock = Stocks::findOrFail($StockLocation->stocks_id);
        
        return view('products/StockLocationProduct-show', [
            'Stock' => $Stock,
            'StockLocation' => $StockLocation,
            'StockLocationProduct' => $StockLocationProduct,
            'StockMoves' => $StockMoves,
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
     * @param $request
     * @return View
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
     * @param $request
     * @return View
     */
    public function entry(StoreStockMoveRequest $request)
    {
        $stockMove = StockMove::create($request->only('user_id', 
                                                        'qty',
                                                        'stock_location_products_id', 
                                                        'typ_move',
                                                    ));
        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
   }

    /**
     * @param $request
     * @return View
     */
    public function sorting(StoreStockMoveRequest $request)
    {
        $stockMove = StockMove::create($request->only('user_id', 
                                                        'qty',
                                                        'stock_location_products_id', 
                                                        'typ_move',
                                                    ));
        return redirect()->route('products.stockline.show', ['id' => $stockMove->stock_location_products_id])->with('success', 'Successfully created new move stock.');
    }
}
