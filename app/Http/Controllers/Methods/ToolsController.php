<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Products\Products;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Models\Products\StockLocation;
use App\Services\Stock\ToolStockService;
use App\Http\Requests\Methods\StoreToolRequest;
use App\Http\Requests\Methods\UpdateToolRequest;

class ToolsController extends Controller
{
    
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * Display a listing of the tools.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(ToolStockService $toolStockService)
    {
        $MethodsTools = MethodsTools::with('stockProduct.Stock_location_product')->orderBy('code')->get();
        return view('methods/methods-tools', [
            'MethodsTools' => $MethodsTools,
            'ToolStock'    => $toolStockService->stockFor($MethodsTools),
        ] + $this->stockProductSelectData());
    }

    /**
     * Listes nécessaires pour créer l'article de stock d'un outil.
     */
    private function stockProductSelectData(): array
    {
        return [
            'StockServices'  => MethodsServices::select('id', 'label')->orderBy('ordre')->get(),
            'StockFamilies'  => MethodsFamilies::select('id', 'label')->orderBy('label')->get(),
            'StockUnits'     => MethodsUnits::select('id', 'label')->orderBy('label')->get(),
            'StockLocations' => StockLocation::select('id', 'code', 'label')->orderBy('code')->get(),
        ];
    }

    /**
     * Règles de création de l'article de stock (préfixe pour les champs du configurateur).
     */
    private function stockProductRules(string $prefix = ''): array
    {
        return [
            $prefix . 'methods_services_id' => 'required|exists:methods_services,id',
            $prefix . 'methods_families_id' => 'required|exists:methods_families,id',
            $prefix . 'methods_units_id'    => 'required|exists:methods_units,id',
            $prefix . 'stock_locations_id'  => 'nullable|exists:stock_locations,id',
            $prefix . 'mini_qty'            => 'nullable|numeric|min:0',
            $prefix . 'qty_eco_min'         => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Article de stock d'un outil : le créer, lier un article existant (par code) ou délier.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeStockProduct(Request $request, $id, ToolStockService $toolStockService)
    {
        $tool = MethodsTools::findOrFail($id);
        $mode = $request->validate(['mode' => 'required|in:create,link,unlink'])['mode'];

        if ($mode === 'unlink') {
            $tool->update(['products_id' => null]);
            return redirect()->route('methods.tool')->with('success', "Outil {$tool->code} délié de son article de stock.");
        }

        if ($mode === 'link') {
            $code = $request->validate(['product_code' => 'required|string|exists:products,code'], [
                'product_code.exists' => 'Aucun article ne porte ce code.',
            ])['product_code'];
            $tool->update(['products_id' => Products::where('code', $code)->value('id')]);
            return redirect()->route('methods.tool')->with('success', "Outil {$tool->code} lié à l'article {$code}.");
        }

        if (Products::withTrashed()->where('code', $tool->code)->exists()) {
            return back()->withErrors(['msg' => "Un article porte déjà le code {$tool->code} : utilisez « Lier à un article existant »."]);
        }
        $data = $request->validate($this->stockProductRules());
        $toolStockService->createProductForTool($tool, $data);

        return redirect()->route('methods.tool')->with('success', "Article de stock {$tool->code} créé.");
    }
    
    /**
     * Outillage de presse plieuse (configurateur de poinçon, catalogue constructeurs) :
     * fonctionnalités réservées à la version commerciale. Les anciennes URLs sont
     * conservées et présentent l'offre Nest2Prod.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function punchDesigner()
    {
        return view('methods/methods-punch-designer');
    }

    /**
     * Configurateur d'outil de tournage : assemble le code ISO (porte-outil, barre
     * d'alésage, plaquette) à partir des planches normalisées.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function configurator()
    {
        return view('methods/methods-tool-configurator', $this->stockProductSelectData());
    }

    /**
     * Crée l'outil assemblé par le configurateur (appel AJAX multipart). L'image,
     * optionnelle, est l'illustration PNG générée côté navigateur à partir du code.
     *
     * @param \App\Http\Requests\Methods\StoreToolRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeConfigured(StoreToolRequest $request, ToolStockService $toolStockService)
    {
        $request->validate([
            'cost'     => 'nullable|numeric|min:0',
            'qty'      => 'integer|min:0',
            'end_date' => 'nullable|date',
            'comment'  => 'nullable|string|max:5000',
        ]);
        $stock = null;
        if ($request->boolean('create_product')) {
            $request->validate(['code' => [Rule::unique('products', 'code')]], [
                'code.unique' => 'Un article porte déjà ce code : créez l\'outil sans article puis liez-le depuis la liste des outils.',
            ]);
            $stock = $request->validate($this->stockProductRules('stock.'))['stock'];
        }

        $attributes = $request->only('code', 'label', 'cost', 'end_date', 'comment', 'qty') + ['ETAT' => 1];
        if ($request->hasFile('picture')) {
            // même emplacement que les images déposées à la main (storage/images/tools)
            $attributes['picture'] = basename($request->file('picture')->store('images/tools', 'public'));
        }

        $tool = MethodsTools::create($attributes);
        if ($stock !== null) {
            $toolStockService->createProductForTool($tool, $stock);
        }

        session()->flash('success', __('general_content.tool_created_success_trans_key'));

        return response()->json([
            'id'       => $tool->id,
            'redirect' => route('methods.tool'),
        ], 201);
    }

    /**
     * Store a newly created tool in storage.
     *
     * @param \App\Http\Requests\Methods\StoreToolRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreToolRequest $request)
    {
        $Tool =  MethodsTools::create($request->only('code','label', 'cost', 'end_date','comment', 'qty'));

        if($request->ETAT) $Tool->ETAT=1;
        else $Tool->ETAT = 2;
        $Tool->save();

        if($request->hasFile('picture')){
            $Tool = MethodsTools::findOrFail($Tool->id);
            $path = $request->file('picture')->store('images/tools', 'public');
            $Tool->update(['picture' => basename($path)]);
            $Tool->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }

        return redirect()->route('methods.tool')->with('success', __('general_content.tool_created_success_trans_key'));
    }

    /**
     * Update the specified tool in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateToolRequest $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateToolRequest $request)
    {
        $tool = MethodsTools::findOrFail($request->id);

        $tool->update([
            'label' => $request->label,
            'ETAT' => $request->etat_update ? 1 : 2,
            'cost' => $request->cost,
            'end_date' => $request->end_date,
            'qty' => $request->qty,
        ]);

        return redirect()->route('methods.tool')->with('success', __('general_content.tool_updated_success_trans_key'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage(Request $request)
    {
        
        $request->validate([
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $Service = MethodsTools::findOrFail($request->id);
            $path = $request->file('picture')->store('images/tools', 'public');
            $Service->update(['picture' => basename($path)]);
            $Service->save();
            return redirect()->route('methods.tool')->with('success', __('general_content.tool_updated_success_trans_key'));
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
