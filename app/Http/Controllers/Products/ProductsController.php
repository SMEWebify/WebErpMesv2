<?php

namespace App\Http\Controllers\Products;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsUnits;
use App\Models\Planning\SubAssembly;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Http\Requests\Products\UpdateProductsRequest;

class ProductsController extends Controller
{
    /**
     * @return view
     */
    public function index()
    {
        return view('products/products-index');
    }

    /**
     * @param $id
     * @return View
     */
    public function show($id)
    {
        $Product = Products::findOrFail($id);
        $status_id = Status::select('id')->orderBy('order')->first();
        $ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
        $TechServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ordre')->get();
        $BOMServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 2)
                                                                            ->orWhere('type', '=', 3)
                                                                            ->orWhere('type', '=', 4)
                                                                            ->orWhere('type', '=', 5)
                                                                            ->orWhere('type', '=', 6)
                                                                            ->orWhere('type', '=', 8)
                                                                            ->orderBy('ordre')->get();

        $ServicesSelect = MethodsServices::select('id', 'label')->orderBy('ordre')->get();
        $UnitsSelect = MethodsUnits::select('id', 'label', 'type')->orderBy('label')->get();
        $FamiliesSelect = MethodsFamilies::select('id', 'label')->orderBy('label')->get();

        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }
        
        $previousUrl = route('products.show', ['id' => $Product->id-1]);
        $nextUrl = route('products.show', ['id' => $Product->id+1]);

        return view('products/products-show', [
            'Product' => $Product,
            'status_id' => $status_id,
            'ProductSelect' => $ProductSelect,
            'TechServicesSelect' =>  $TechServicesSelect,
            'BOMServicesSelect' =>  $BOMServicesSelect,
            'ServicesSelect' => $ServicesSelect,
            'UnitsSelect' => $UnitsSelect,
            'FamiliesSelect' => $FamiliesSelect,
            'Factory' => $Factory,
            'previousUrl' =>  $previousUrl,
            'nextUrl' =>  $nextUrl,
        ]);
    }

    /**
     * @param Request $request
     * @return View
     */
    public function StoreImage(Request $request)
    {
        
        $request->validate([
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $Product = Products::findOrFail($request->id);
            $file =  $request->file('picture');
            $oringalFileName = $request->file->getClientOriginalName();
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
     * @param Request $request
     * @return View
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
     * @param Request $request
     * @return View
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
        $Product->save();
        return redirect()->route('products.show', ['id' =>  $Product->id])->with('success', 'Successfully updated product');
    }

    /**
     * @param $id
     * @return View
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
