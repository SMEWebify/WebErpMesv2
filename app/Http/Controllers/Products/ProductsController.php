<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
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

        $Products = Products::All();
        $userSelect = User::select('id', 'name')->get();
        $ServicesSelect = MethodsServices::select('id', 'LABEL')->orderBy('LABEL')->get();
        $UnitsSelect = MethodsUnits::select('id', 'LABEL')->orderBy('LABEL')->get();
        $FamiliesSelect = MethodsFamilies::select('id', 'LABEL')->orderBy('LABEL')->get();

        return view('products/products-index', [
            'Products' => $Products,
            'userSelect' => $userSelect,
            'ServicesSelect' => $ServicesSelect,
            'ServicesSelect' => $ServicesSelect,
            'UnitsSelect' => $UnitsSelect,
            'FamiliesSelect' => $FamiliesSelect
        ]);
    }

    public function show($id)
    {

        $Product = Products::findOrFail($id);
        $ServicesSelect = MethodsServices::select('id', 'LABEL')->orderBy('LABEL')->get();

        return view('products/products-show', [
            'Product' => $Product,
            'ServicesSelect' =>  $ServicesSelect
        ]);
    }

    public function store(StoreProductsRequest $request)
    {
       
        $Product = Products::create($request->only('CODE',
                                                    'LABEL', 
                                                    'IND',
                                                    'methods_services_id', 
                                                    'methods_families_id', 
                                                    'purchased', 
                                                    'purchased_price', 
                                                    'sold', 
                                                    'selling_price', 
                                                    'methods_units_id', 
                                                    'material', 
                                                    'thickness', 
                                                    'weight', 
                                                    'x_size', 
                                                    'Y_size', 
                                                    'z_size', 
                                                    'x_oversize',
                                                    'y_oversize',
                                                    'z_oversize',
                                                    'comment',
                                                    'tracability_type',
                                                    'qty_eco_min',
                                                    'qty_eco_max',
                                                    'diameter',
                                                    'diameter_oversize',
                                                    'section_size'));

        if($request->hasFile('PICTURE')){
            $path = $request->PICTURE->store('images/products/','public');
            $Product->update(['PICTURE' => $path]);
        }

        return redirect()->route('products.show', ['id' => $Product->id])->with('success', 'Successfully created new product');

    }
}
