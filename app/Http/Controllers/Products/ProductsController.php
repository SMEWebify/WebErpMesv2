<?php

namespace App\Http\Controllers\Products;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Traits\NextPreviousTrait;
use App\Services\Files\FileRole;
use App\Services\SelectDataService;
use App\Services\CustomFieldService;
use App\Http\Controllers\Controller;
use App\Models\Planning\SubAssembly;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Purchases\PurchaseLines;
use App\Models\Workflow\QuoteLines;
use App\Models\Workflow\OrderLines;
use App\Services\StockCalculationService;
use App\Services\StockReservationService;
use App\Services\ABC_MFR_CalculatorService;
use App\Models\Products\ProductsQuantityPrice;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\UpdateProductsRequest;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\ProductPriceHistory;
use App\Services\ProductMergeService;

class ProductsController extends Controller
{    
    use NextPreviousTrait;
    
    protected $SelectDataService;
    protected $abcFMRService;
    protected $stockCalculationService;
    protected $customFieldService;

    public function __construct(SelectDataService $SelectDataService,
                                ABC_MFR_CalculatorService $abcFMRService,
                                StockCalculationService $stockCalculationService,
                                CustomFieldService $customFieldService)
    {
        $this->SelectDataService = $SelectDataService;
        $this->abcFMRService = $abcFMRService;
        $this->stockCalculationService = $stockCalculationService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $totalCount     = Products::count();
        $soldCount      = Products::where('sold', 1)->count();
        $purchasedCount = Products::where('purchased', 1)->count();

        $byService = Products::join('methods_services', 'products.methods_services_id', '=', 'methods_services.id')
            ->selectRaw('methods_services.label as service_label, COUNT(*) as count')
            ->groupBy('methods_services.label')
            ->orderByDesc('count')
            ->get();

        $byFamily = Products::join('methods_families', 'products.methods_families_id', '=', 'methods_families.id')
            ->selectRaw('methods_families.label as family_label, COUNT(*) as count')
            ->groupBy('methods_families.label')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $reactKpi = [
            'totalCount'     => $totalCount,
            'soldCount'      => $soldCount,
            'purchasedCount' => $purchasedCount,
            'soldRate'       => $totalCount > 0 ? round($soldCount / $totalCount * 100, 1) : 0,
            'purchasedRate'  => $totalCount > 0 ? round($purchasedCount / $totalCount * 100, 1) : 0,
        ];

        $reactChart = [
            'byService' => $byService,
            'byFamily'  => $byFamily,
        ];

        $reactEndpoints = [
            'list'         => route('products.json.list'),
            'store'        => route('products.json.store'),
            'selectData'   => route('products.json.select-data'),
        ];

        $canMerge = Auth::user()->can('products-merge');
        if ($canMerge) {
            $reactEndpoints['duplicates']   = route('products.json.duplicates');
            $reactEndpoints['mergeBaseUrl'] = url('/products/json/merge');
        }

        return view('products/products-index', compact('reactKpi', 'reactChart', 'reactEndpoints', 'canMerge'));
    }

    /**
     * JSON endpoint — paginated product list for the React ProductsIndex component.
     */
    public function listJson(Request $request)
    {
        $search    = $request->get('search', '');
        $sortField = $request->get('sort', 'created_at');
        $sortAsc   = $request->boolean('asc', false);
        $sold      = $request->get('sold');
        $purchased = $request->get('purchased');

        $allowed = ['code', 'label', 'created_at', 'sold', 'purchased', 'selling_price', 'purchased_price'];
        if (!in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }

        $dir = $sortAsc ? 'asc' : 'desc';

        $query = Products::with(['service:id,label', 'family:id,label'])
            ->withCount('Task as task_count')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('label', 'like', '%'.$search.'%')
                  ->orWhere('code', 'like', '%'.$search.'%');
            }))
            ->when($sold !== null && $sold !== '', fn ($q) => $q->where('sold', (int) $sold))
            ->when($purchased !== null && $purchased !== '', fn ($q) => $q->where('purchased', (int) $purchased))
            ->orderBy($sortField, $dir);

        $products = $query->paginate(15);

        return response()->json([
            'data' => $products->map(fn ($p) => [
                'id'         => $p->id,
                'code'       => $p->code,
                'label'      => $p->label,
                'created_at' => $p->created_at?->format('d/m/Y'),
                'sold'       => $p->sold,
                'purchased'  => $p->purchased,
                'service'    => $p->service?->label,
                'family'     => $p->family?->label,
                'task_count' => $p->task_count,
                'selling_price'   => $p->selling_price,
                'purchased_price' => $p->purchased_price,
                'url'        => route('products.show', ['id' => $p->id]),
            ]),
            'meta' => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    /**
     * JSON endpoint — create a new product.
     */
    public function storeJson(Request $request)
    {
        $validated = $request->validate([
            'code'                => 'required|unique:products',
            'label'               => 'required|string|max:255',
            'methods_services_id' => 'required|exists:methods_services,id',
            'methods_families_id' => 'required|exists:methods_families,id',
            'methods_units_id'    => 'required|exists:methods_units,id',
            'sold'                => 'required|in:1,2',
            'purchased'           => 'required|in:1,2',
            'tracability_type'    => 'required|in:1,2,3',
            'ind'                 => 'nullable|string|max:50',
            'material'            => 'nullable|string|max:255',
            'finishing'           => 'nullable|string|max:255',
            'thickness'           => 'nullable|numeric|min:0',
            'weight'              => 'nullable|numeric|min:0',
            'x_size'              => 'nullable|numeric|min:0',
            'y_size'              => 'nullable|numeric|min:0',
            'z_size'              => 'nullable|numeric|min:0',
            'x_oversize'          => 'nullable|numeric|min:0',
            'y_oversize'          => 'nullable|numeric|min:0',
            'z_oversize'          => 'nullable|numeric|min:0',
            'diameter'            => 'nullable|numeric|min:0',
            'diameter_oversize'   => 'nullable|numeric|min:0',
            'section_size'        => 'nullable|numeric|min:0',
            'qty_eco_min'         => 'nullable|numeric|min:0',
            'qty_eco_max'         => 'nullable|numeric|min:0',
            'purchased_price'     => 'nullable|numeric|min:0',
            'selling_price'       => 'nullable|numeric|min:0',
            'comment'             => 'nullable|string',
        ]);

        $product = Products::create($validated);

        return response()->json([
            'id'  => $product->id,
            'url' => route('products.show', ['id' => $product->id]),
        ], 201);
    }

    /**
     * JSON endpoint — select data for the create product modal.
     */
    public function selectDataJson()
    {
        return response()->json([
            'services' => MethodsServices::select('id', 'label')->orderBy('ordre')->get(),
            'families' => MethodsFamilies::select('id', 'label')->orderBy('label')->get(),
            'units'    => MethodsUnits::select('id', 'label', 'type')->orderBy('label')->get(),
        ]);
    }

    /**
     * Fetch select data for the view.
     *
     * @return array
     */
    private function fetchSelectData()
    {
        return [
            'userSelect' => $this->SelectDataService->getUsers(),
            'ProductSelect' => $this->SelectDataService->getProductsSelect(),
            'ServicesSelect' => $this->SelectDataService->getServices(),
            'UnitsSelect' => $this->SelectDataService->getUnitsSelect(),
            'FamiliesSelect' => $this->SelectDataService->getFamilies(),
            'CompanieSelect' => $this->SelectDataService->getSupplier(),
            'CustomerSelect' => $this->SelectDataService->getCompanies(),
            'TechServicesSelect' => $this->SelectDataService->getTechServices(),
            'BOMServicesSelect' => $this->SelectDataService->getBOMServices(),
        ];
    }

    /**
     * Calculate the last purchase price for a product.
     *
     * @param int $productId
     * @return float
     */
    private function calculateLastPurchasePrice($productId)
    {
        return PurchaseLines::where('product_id', $productId)
                            ->orderBy('created_at', 'desc')
                            ->first()
                            ->selling_price ?? 0;
    }

    /**
     * Calculate the average cost for a product.
     *
     * @param int $productId
     * @return float
     */
    private function calculateAverageCost($productId)
    {
        $StockLocationsProducts = StockLocationProducts::where('products_id', $productId)->get();
        $totalWeightedCost = 0;
        $totalQuantity = 0;

        foreach ($StockLocationsProducts as $stockLocationProduct) {
            $currentQuantity = $stockLocationProduct->getCurrentStockMove();
            $weightedAverageCost = $this->stockCalculationService->calculateWeightedAverageCost($stockLocationProduct->id);

            $totalWeightedCost += $currentQuantity * $weightedAverageCost;
            $totalQuantity += $currentQuantity;
        }

        return $totalQuantity > 0 ? $totalWeightedCost / $totalQuantity : 0;
    }

    /**
     * Calculate the average supply delay for a product.
     *
     * @param int $productId
     * @return float|null
     */
    private function calculateAverageSupplyDelay($productId)
    {
        return DB::table('purchase_receipt_lines')
            ->join('purchase_lines', 'purchase_receipt_lines.purchase_line_id', '=', 'purchase_lines.id')
            ->where('purchase_lines.product_id', $productId)
            ->avg(DB::raw('DATEDIFF(purchase_receipt_lines.created_at, purchase_lines.created_at)'));
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        // files is eager loaded with its pivot so the sidebar can pick the photo
        // flagged as primary without an extra query.
        $Product = Products::with('files')->findOrFail($id);
        $productPicture = $Product->files->first(
            fn ($file) => $file->pivot->role === FileRole::PHOTO && $file->pivot->is_primary
        );
        $selectData = $this->fetchSelectData();
        $status_id = Status::select('id')->orderBy('order')->first();
        $StockLocationsProducts = StockLocationProducts::where('products_id', $id)->get();
        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Products(), $Product->id);
        $finalAnalysis = $this->abcFMRService->calculateABC_FMR($Product->id);
        $lastPurchasePrice = $this->calculateLastPurchasePrice($id);
        $averageCost = $this->calculateAverageCost($id);
        $averageSupplyDelay = null;
        $reservationSummary = null;
        if ($Product->purchased == 1) {
            $averageSupplyDelay = $this->calculateAverageSupplyDelay($id);
            $reservationSummary = app(StockReservationService::class)->summaryForProduct($id);
        }
        $CustomFields = $this->customFieldService->getCustomFieldsWithValues('product', $Product->id);

        $customerPriceLists = $Product->customerPriceLists()
            ->with('company')
            ->orderByRaw('companies_id IS NOT NULL DESC')
            ->orderByRaw('customer_type IS NOT NULL DESC')
            ->orderBy('min_qty')
            ->orderBy('max_qty')
            ->get();

        return view('products/products-show', array_merge($selectData, [
            'Product' => $Product,
            'status_id' => $status_id,
            'previousUrl' => $previousUrl,
            'nextUrl' => $nextUrl,
            'StockLocationsProducts' => $StockLocationsProducts,
            'finalAnalysis' => $finalAnalysis,
            'lastPurchasePrice' => $lastPurchasePrice,
            'averageCost' => $averageCost,
            'averageSupplyDelay' => $averageSupplyDelay,
            'reservationSummary' => $reservationSummary,
            'CustomFields' => $CustomFields,
            'CustomerPriceLists' => $customerPriceLists,
            'productPicture' => $productPicture,
        ]));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreSupplier(Request $request)
    {
        $product = Products::find($request->product_id);
        if (!$product) {
            return redirect()->back()->withErrors(['message' => 'Product not found.']);
        }
        else{
            $product->preferredSuppliers()->attach($request->companies_id);
            return redirect()->route('products.show', ['id' =>  $request->product_id])->with('success', __('general_content.supplier_added_success_trans_key'));
        }
    }

    public function StoreSupplierPriceQty(Request $request){

        // Validate the request data
        $validatedData = $request->validate([
            'min_qty' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        // Create a new quantity price entry
        $quantityPrice = ProductsQuantityPrice::create([
            'products_id' => $request->id,
            'companies_id' => $request->companies_id,
            'min_qty' => $request->min_qty,
            'max_qty' => $request->max_qty,
            'price' => $request->price,
        ]);

        // Redirect back with success message

        return redirect()->route('products.show', ['id' =>  $request->id])->with('success', __('general_content.quantity_price_added_success_trans_key'));
    }

    /*
     * The picture / drawing / STL / SVG upload actions have been removed: every
     * document now goes through the unified drop area of the Documents tab
     * (FileApiController), which stores outside the web root and accepts STEP,
     * IGES and DXF as well. The drawing_file / stl_file / svg_file / picture
     * columns are kept in sync by FileStorageService for the consumers that
     * still read them (quote lines, order lines, API resource, TaskStatu).
     */

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProductsRequest $request)
    {
        $Product = Products::findOrFail($request->id);
         // Update the Product record with the other fields
        $Product->update($request->validated());
         // Handle specific cases outside mass assignment
        $Product->purchased = $request->has('purchased') ? 1 : 2;
        $Product->sold = $request->has(key: 'sold') ? 1 : 2;
        $Product->save();
        return redirect()->route('products.show', ['id' =>  $Product->id])->with('success', __('general_content.product_updated_success_trans_key'));
    }

/**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate($id)
    {
        $Product = Products::findOrFail($id);
        $newProduct = $Product->replicate();
        $newProduct->code = Str::uuid();
        $newProduct->label = $Product->label . "#duplicate";
        $newProduct->save();

        $this->duplicateTasks($id, $newProduct->id);
        $this->duplicateSubAssemblies($id, $newProduct->id);

        return redirect()->route('products.show', ['id' => $newProduct->id])->with('success', 'Successfully duplicated product');
    }

    /**
     * Duplicate tasks for the new product.
     *
     * @param int $oldProductId
     * @param int $newProductId
     * @return void
     */
    private function duplicateTasks($oldProductId, $newProductId)
    {
        $Tasks = Task::where('products_id', $oldProductId)->get();
        foreach ($Tasks as $Task) {
            $newTask = $Task->replicate();
            $newTask->products_id = $newProductId;
            $newTask->save();
        }
    }

    /**
     * Duplicate sub-assemblies for the new product.
     *
     * @param int $oldProductId
     * @param int $newProductId
     * @return void
     */
    private function duplicateSubAssemblies($oldProductId, $newProductId)
    {
        $SubAssemblyLine = SubAssembly::where('products_id', $oldProductId)->get();
        foreach ($SubAssemblyLine as $SubAssembly) {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->products_id = $newProductId;
            $newSubAssembly->save();
        }
    }

    /**
     * JSON endpoint — products that share the same code (exact duplicates).
     */
    public function duplicatesJson()
    {
        $duplicateCodes = Products::select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('code');

        if ($duplicateCodes->isEmpty()) {
            return response()->json([]);
        }

        $products = Products::with(['service:id,label', 'family:id,label'])
            ->whereIn('code', $duplicateCodes)
            ->orderBy('code')
            ->get();

        return response()->json(
            $products->groupBy('code')->map(fn ($group, $code) => [
                'code'     => $code,
                'products' => $group->values()->map(fn ($p) => [
                    'id'      => $p->id,
                    'code'    => $p->code,
                    'label'   => $p->label,
                    'service' => $p->service?->label,
                    'family'  => $p->family?->label,
                    'url'     => route('products.show', ['id' => $p->id]),
                ]),
            ])->values()
        );
    }

    /**
     * JSON endpoint — preview the impact of merging duplicate into master.
     */
    public function mergePreviewJson(int $master, int $duplicate)
    {
        abort_if($master === $duplicate, 422, 'Master and duplicate must differ.');
        return response()->json(
            app(ProductMergeService::class)->preview($master, $duplicate)
        );
    }

    /**
     * JSON endpoint — price history (purchase + sale) for a product.
     */
    public function priceHistoryJson(int $id)
    {
        Products::findOrFail($id);

        $rows = ProductPriceHistory::where('products_id', $id)
            ->with('user:id,name')
            ->orderBy('type')
            ->orderByDesc('started_at')
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'type'       => $r->type,
                'price'      => (float) $r->price,
                'started_at' => $r->started_at?->format('Y-m-d'),
                'ended_at'   => $r->ended_at?->format('Y-m-d'),
                'user'       => $r->user?->name,
            ]);

        return response()->json([
            'purchase' => $rows->where('type', 'purchase')->values(),
            'sale'     => $rows->where('type', 'sale')->values(),
        ]);
    }

    /**
     * JSON endpoint — execute the merge of duplicate into master.
     */
    public function mergeJson(int $master, int $duplicate)
    {
        abort_if($master === $duplicate, 422, 'Master and duplicate must differ.');
        app(ProductMergeService::class)->merge($master, $duplicate);
        return response()->json(['ok' => true]);
    }

    /**
     * JSON endpoint — timeline items (quote lines, order lines, purchase lines) for a product.
     * Returns a flat array sorted by date descending, same format as CompanyTimeline.
     */
    public function historyJson(int $id)
    {
        Products::findOrFail($id);

        $items = collect();

        QuoteLines::where('product_id', $id)
            ->with(['quote:id,code,statu,created_at', 'Unit:id,label'])
            ->get()
            ->each(function ($l) use (&$items) {
                $items->push([
                    'type'  => 'quote',
                    'id'    => $l->id,
                    'code'  => $l->quote?->code,
                    'label' => $l->label . ($l->qty ? ' — ' . $l->qty . ($l->Unit ? ' ' . $l->Unit->label : '') : ''),
                    'statu' => $l->quote?->statu,
                    'date'  => $l->quote?->created_at?->format('Y-m-d'),
                    'url'   => route('quotes.show', ['id' => $l->quotes_id]),
                ]);
            });

        OrderLines::where('product_id', $id)
            ->with(['order:id,code,statu,created_at', 'Unit:id,label'])
            ->get()
            ->each(function ($l) use (&$items) {
                $items->push([
                    'type'  => 'order',
                    'id'    => $l->id,
                    'code'  => $l->order?->code,
                    'label' => $l->label . ($l->qty ? ' — ' . $l->qty . ($l->Unit ? ' ' . $l->Unit->label : '') : ''),
                    'statu' => $l->order?->statu,
                    'date'  => $l->order?->created_at?->format('Y-m-d'),
                    'url'   => route('orders.show', ['id' => $l->orders_id]),
                ]);
            });

        PurchaseLines::where('product_id', $id)
            ->with(['purchase:id,code,statu,created_at', 'Unit:id,label'])
            ->get()
            ->each(function ($l) use (&$items) {
                $items->push([
                    'type'  => 'purchase',
                    'id'    => $l->id,
                    'code'  => $l->purchase?->code,
                    'label' => $l->label . ($l->qty ? ' — ' . $l->qty . ($l->Unit ? ' ' . $l->Unit->label : '') : ''),
                    'statu' => $l->purchase?->statu,
                    'date'  => $l->purchase?->created_at?->format('Y-m-d'),
                    'url'   => route('purchases.show', ['id' => $l->purchases_id]),
                ]);
            });

        return response()->json(
            $items->sortByDesc('date')->values()
        );
    }

}
