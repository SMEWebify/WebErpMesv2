<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Products\Stocks;
use App\Models\Products\Products;
use App\Models\Products\StockMove;
use Illuminate\Support\Facades\DB;
use App\Models\Workflow\OrderLines;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Models\Products\StockLocation;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\StoreStockRequest;
use App\Http\Requests\Products\UpdateStockRequest;

class StockController extends Controller
{
    
    protected $SelectDataService;
    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    //
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

    public function store(StoreStockRequest $request)
    {
        $Stock = Stocks::create($request->only('code','label', 'user_id'));
        return redirect()->route('products.stock.show', ['id' => $Stock->id])->with('success', 'Successfully created new stock');
    }

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

    public function detail(Request $request)
    {
        return view('products/stock-detail-show', ['StockDetailId' => $request->id]);
    }
}
