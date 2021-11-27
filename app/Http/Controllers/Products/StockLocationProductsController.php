<?php

namespace App\Http\Controllers\Products;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreStockLocationProductsRequest;
use App\Models\Products\StockLocationProducts;

class StockLocationProductsController extends Controller
{
    //
    public function store(StoreStockLocationProductsRequest $request)
    {
        $StockLocationProduct = StockLocationProducts::create($request->only('CODE',
                                                                'user_id', 
                                                                'stock_locations_id',
                                                                'products_id', 
                                                                'mini_qty',
                                                                'end_date',
                                                                'addressing',
                                            ));
        return redirect()->route('products.stocklocation.show', ['id' => $StockLocationProduct->stock_locations_id])->with('success', 'Successfully created new stock line');
    }

}
