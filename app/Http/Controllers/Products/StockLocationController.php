<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Products\Products;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\StoreStockLocationRequest;

class StockLocationController extends Controller
{
    //
    public function show($id)
    {
        $StockLocation = StockLocation::findOrFail($id);
        $StockLocationsProducts = StockLocationProducts::where('stock_locations_id', '=', $id)->get();
        $userSelect = User::select('id', 'name')->get();
        $ProductSelect = Products::select('id', 'CODE')->get();
        $LastStockLocationProduct =  DB::table('stock_location_products')->orderBy('id', 'desc')->first();

        return view('products/stockLocation-show', [
            'StockLocation' => $StockLocation,
            'StockLocationsProducts' => $StockLocationsProducts,
            'userSelect' => $userSelect,
            'ProductSelect' => $ProductSelect,
            'LastStockLocationProduct' => $LastStockLocationProduct
        ]);
    }

    public function store(StoreStockLocationRequest $request)
    {
        $StockLocation = StockLocation::create($request->only('CODE',
                                                'LABEL', 
                                                'stocks_id',
                                                'user_id',
                                                'END_DATE',
                                                'COMMENT',
                                            ));

        return redirect()->route('products.stocklocation.show', ['id' => $StockLocation->id])->with('success', 'Successfully created new location stock');
    }

}
