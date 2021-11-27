<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsServices;
use App\Http\Requests\Products\StoreProductsRequest;

class ProductsController extends Controller
{
    //
    public function index()
    {
        return view('products/products-index');
    }
    
    public function show($id)
    {
        $Product = Products::findOrFail($id);
        $ProductSelect = Products::select('id', 'CODE','LABEL', 'methods_services_id')->get();
        $TechServicesSelect = MethodsServices::select('id', 'CODE','LABEL', 'TYPE')->where('TYPE', '=', 1)->orWhere('TYPE', '=', 7)->orderBy('ORDRE')->get();
        $BOMServicesSelect = MethodsServices::select('id', 'CODE','LABEL', 'TYPE')->where('TYPE', '=', 2)
                                                                            ->orWhere('TYPE', '=', 3)
                                                                            ->orWhere('TYPE', '=', 4)
                                                                            ->orWhere('TYPE', '=', 5)
                                                                            ->orWhere('TYPE', '=', 6)
                                                                            ->orWhere('TYPE', '=', 8)
                                                                            ->orderBy('ORDRE')->get();
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('danger', 'Please check factory information');
        }
        
        return view('products/products-show', [
            'Product' => $Product,
            'ProductSelect' => $ProductSelect,
            'TechServicesSelect' =>  $TechServicesSelect,
            'BOMServicesSelect' =>  $BOMServicesSelect,
            'Factory' => $Factory,
        ]);
    }

   /* public function store(StoreProductsRequest $request)
    {
        $Product = Products::create($request->only());
        if($request->hasFile('PICTURE')){
            $path = $request->PICTURE->store('images/products/','public');
            $Product->update(['PICTURE' => $path]);
        }
        
    }*/
}
