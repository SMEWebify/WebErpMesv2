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
        return view('products/stock-detail-show', ['StockDetailId' => $request->id]);
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
}
