<?php

namespace App\Http\Controllers\Products;

use App\Models\Admin\Factory;
use App\Models\Planning\Status;
use App\Models\Products\Products;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsServices;

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
        $status_id = Status::select('id')->orderBy('order')->first();
        $ProductSelect = Products::select('id', 'code','label', 'methods_services_id')->get();
        $TechServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 1)->orWhere('type', '=', 7)->orderBy('ORDRE')->get();
        $BOMServicesSelect = MethodsServices::select('id', 'code','label', 'type')->where('type', '=', 2)
                                                                            ->orWhere('type', '=', 3)
                                                                            ->orWhere('type', '=', 4)
                                                                            ->orWhere('type', '=', 5)
                                                                            ->orWhere('type', '=', 6)
                                                                            ->orWhere('type', '=', 8)
                                                                            ->orderBy('ORDRE')->get();
        $Factory = Factory::first();
        if(!$Factory){
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }
        
        return view('products/products-show', [
            'Product' => $Product,
            'status_id' => $status_id,
            'ProductSelect' => $ProductSelect,
            'TechServicesSelect' =>  $TechServicesSelect,
            'BOMServicesSelect' =>  $BOMServicesSelect,
            'Factory' => $Factory,
        ]);
    }

   /* public function store(StoreProductsRequest $request)
    {
        $Product = Products::create($request->only());
        if($request->hasFile('picture')){
            $path = $request->picture->store('images/products/','public');
            $Product->update(['picture' => $path]);
        }
        
    }*/
}
