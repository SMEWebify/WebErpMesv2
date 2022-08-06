<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Products\Stocks;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use App\Http\Requests\Products\StoreStockRequest;
use App\Http\Requests\Products\UpdateStockRequest;

class StockController extends Controller
{
    //
    public function index()
    {
        $stocks = Stocks::All();
        $userSelect = User::select('id', 'name')->get();
        $LastStock =  DB::table('stocks')->orderBy('id', 'desc')->first();
        return view('products/stocks-index', [
            'stocks' => $stocks,
            'userSelect' => $userSelect,
            'LastStock' => $LastStock
        ]);
    }

    public function store(StoreStockRequest $request)
    {
        $Stock = Stocks::create($request->only('code','label', 'user_id'));
        return redirect()->route('products.stock.show', ['id' => $Stock->id])->with('success', 'Successfully created new stock');
    }

    public function show($id)
    {
        $Stock = Stocks::findOrFail($id);
        $StockLocations = StockLocation::where('stocks_id', '=', $id)->get();
        $userSelect = User::select('id', 'name')->get();
        $LastStockLocation =  DB::table('stock_locations')->orderBy('id', 'desc')->first();
        
        return view('products/stock-show', [
            'Stock' => $Stock,
            'StockLocations' => $StockLocations,
            'userSelect' => $userSelect,
            'LastStockLocation' => $LastStockLocation
        ]);
    }

    /**
     * @param $request
     * @return View
     */
    public function update(UpdateStockRequest $request)
    {
        $Stock = Stocks::find($request->id);
        $Stock->label=$request->label;
        $Stock->user_id=$request->user_id;
        $Stock->save();
        return redirect()->route('products.stock')->with('success', 'Successfully updated stock '.  $Stock->label);
    }
}
