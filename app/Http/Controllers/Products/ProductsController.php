<?php

namespace App\Http\Controllers\Products;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Traits\NextPreviousTrait;
use App\Services\SelectDataService;
use App\Services\ABC_MFR_CalculatorService;
use App\Http\Controllers\Controller;
use App\Models\Planning\SubAssembly;
use App\Models\Methods\MethodsServices;
use App\Models\Products\ProductsQuantityPrice;
use App\Models\Products\StockLocationProducts;
use App\Http\Requests\Products\UpdateProductsRequest;

class ProductsController extends Controller
{    
    use NextPreviousTrait;
    
    protected $SelectDataService;
    protected $abcFMRService;

    public function __construct(SelectDataService $SelectDataService, ABC_MFR_CalculatorService $abcFMRService)
    {
        $this->SelectDataService = $SelectDataService;
        $this->abcFMRService = $abcFMRService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('products/products-index');
    }

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $Product = Products::findOrFail($id);
        
        $userSelect = $this->SelectDataService->getUsers();
        $ProductSelect = $this->SelectDataService->getProductsSelect();
        $ServicesSelect = $this->SelectDataService->getServices();
        $UnitsSelect = $this->SelectDataService->getUnitsSelect();
        $FamiliesSelect = $this->SelectDataService->getFamilies();
        $SupplierSelect = $this->SelectDataService->getSupplier();

        $status_id = Status::select('id')->orderBy('order')->first();
        $StockLocationsProducts = StockLocationProducts::where('products_id', $id)->get(); 
        $TechServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ordre')->get();
        $BOMServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 2)
                                                                            ->orWhere('type', '=', 3)
                                                                            ->orWhere('type', '=', 4)
                                                                            ->orWhere('type', '=', 5)
                                                                            ->orWhere('type', '=', 6)
                                                                            ->orWhere('type', '=', 8)
                                                                            ->orderBy('ordre')->get();


        list($previousUrl, $nextUrl) = $this->getNextPrevious(new Products(), $Product->id);
        $finalAnalysis = $this->abcFMRService->calculateABC_FMR($Product->id);

        return view('products/products-show', [
            'userSelect' => $userSelect,
            'Product' => $Product,
            'status_id' => $status_id,
            'ProductSelect' => $ProductSelect,
            'TechServicesSelect' =>  $TechServicesSelect,
            'BOMServicesSelect' =>  $BOMServicesSelect,
            'ServicesSelect' => $ServicesSelect,
            'UnitsSelect' => $UnitsSelect,
            'FamiliesSelect' => $FamiliesSelect,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
            'SupplierSelect' =>  $SupplierSelect,
            'StockLocationsProducts' =>  $StockLocationsProducts,
            'finalAnalysis' =>  $finalAnalysis,
        ]);
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
            $product->preferredSuppliers()->attach($request->compannie_id);
            return redirect()->route('products.show', ['id' =>  $request->product_id])->with('success', 'Successfully add supplier.');
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

        return redirect()->route('products.show', ['id' =>  $request->id])->with('success', 'Successfully add quantity price.');
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage(Request $request)
    {
        
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $Product = Products::findOrFail($request->id);
            $file =  $request->file('picture');
            $oringalFileName = $file->getClientOriginalName();
            $fileName = time() . '_' .  $oringalFileName;
            $request->picture->move(public_path('images/products'), $fileName);
            $Product->update(['picture' => $fileName]);
            $Product->save();

            return redirect()->route('products.show', ['id' =>  $Product->id])->with('success', 'Successfully updated image');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreDrawing(Request $request)
    {
        if($request->hasFile('drawing')){
            //$type = $request->file->getClientMimeType();
            //$size = $request->file->getSize();
            $Product = Products::findOrFail($request->id);
            $file =  $request->file('drawing');
            $fileName = auth()->id() . '' . time() . '.pdf';
            $request->drawing->move(public_path('drawing'), $fileName);
            $Product->update(['drawing_file' => $fileName]);
            $Product->save();

            return redirect()->route('products.show', ['id' =>  $Product->id])->with('success', 'Successfully updated drawing');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no drawing selected']);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreStl(Request $request)
    {
        if($request->hasFile('stl')){
            //$type = $request->file->getClientMimeType();
            //$size = $request->file->getSize();
            $Product = Products::findOrFail($request->id);
            $file =  $request->file('stl');
            $fileName = auth()->id() . '' . time() . '.stl';
            $request->stl->move(public_path('stl'), $fileName);
            $Product->update(['stl_file' => $fileName]);
            $Product->save();

            return redirect()->route('products.show', ['id' =>  $Product->id])->with('success', 'Successfully updated stl');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no stl selected']);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreSvg(Request $request)
    {
        if($request->hasFile('svg')){
            //$type = $request->file->getClientMimeType();
            //$size = $request->file->getSize();
            $Product = Products::findOrFail($request->id);
            $file =  $request->file('svg');
            $fileName = auth()->id() . '' . time() . '.svg';
            $request->svg->move(public_path('svg'), $fileName);
            $Product->update(['svg_file' => $fileName]);
            $Product->save();

            return redirect()->route('products.show', ['id' =>  $Product->id])->with('success', 'Successfully updated svg');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no svg selected']);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProductsRequest $request)
    {
        $Product = Products::findOrFail($request->id);
        $Product->label = $request->label; 
        $Product->ind=$request->ind;
        $Product->methods_services_id = $request->methods_services_id; 
        $Product->methods_families_id = $request->methods_families_id; 
        $Product->purchased = $request->purchased; 
        $Product->purchased_price = $request->purchased_price; 
        $Product->sold = $request->sold; 
        $Product->selling_price = $request->selling_price; 
        $Product->methods_units_id = $request->methods_units_id; 
        $Product->material = $request->material; 
        $Product->thickness = $request->thickness; 
        $Product->weight = $request->weight; 
        $Product->x_size = $request->x_size; 
        $Product->y_size = $request->y_size; 
        $Product->z_size = $request->z_size; 
        $Product->x_oversize = $request->x_oversize;
        $Product->y_oversize = $request->y_oversize;
        $Product->z_oversize = $request->z_oversize;
        $Product->comment = $request->comment;
        $Product->tracability_type = $request->tracability_type;
        $Product->qty_eco_min = $request->qty_eco_min;
        $Product->qty_eco_max = $request->qty_eco_max;
        $Product->diameter = $request->diameter;
        $Product->diameter_oversize = $request->diameter_oversize;
        $Product->section_size = $request->section_size;
        $Product->finishing = $request->finishing;
        $Product->save();
        return redirect()->route('products.show', ['id' =>  $Product->id])->with('success', 'Successfully updated product');
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
        $newProduct->label = $Product->label ."#duplicate";
        $newProduct->save();

        $Tasks = Task::where('products_id', $id)->get();
        foreach ($Tasks as $Task) 
        {
            $newTask = $Task->replicate();
            $newTask->products_id = $newProduct->id;
            $newTask->save();
        }

        $SubAssemblyLine = SubAssembly::where('products_id', $id)->get();
        foreach ($SubAssemblyLine as $SubAssembly) 
        {
            $newSubAssembly = $SubAssembly->replicate();
            $newSubAssembly->products_id = $newProduct->id;
            $newSubAssembly->save();
        }
        
        return redirect()->route('products.show', ['id' =>  $newProduct->id])->with('success', 'Successfully duplicate product');
    }

}
