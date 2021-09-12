<?php

namespace App\Http\Controllers\Products;

use App\Models\User;
use Illuminate\Http\Request;
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

        $Products = Products::All();
        $userSelect = User::select('id', 'name')->get();
        $ServicesSelect = MethodsServices::select('id', 'LABEL')->orderBy('ORDRE')->get();
        $UnitsSelect = MethodsUnits::select('id', 'LABEL', 'TYPE')->orderBy('LABEL')->get();
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
        $ProductSelect = Products::select('id', 'CODE','LABEL', 'methods_services_id')->get();
        $TechProductList = Task::Where('products_id', '=', $id)->Where(function ($query) {
                                                                                        $query->orwhere('TYPE', '=', 1)
                                                                                                ->orWhere('TYPE', '=', 7);
                                                                                        })->orderBy('ORDER')->get();;
        $BOMProductList = Task::Where('products_id', '=', $id)->Where(function ($query) {
                                                                                        $query->orWhere('TYPE', '=', 3)
                                                                                            ->orWhere('TYPE', '=', 4)
                                                                                            ->orWhere('TYPE', '=', 5)
                                                                                            ->orWhere('TYPE', '=', 6)
                                                                                            ->orWhere('TYPE', '=', 8);
                                                                                        })->orderBy('ORDER')->get();

        $TechServicesSelect = MethodsServices::select('id', 'CODE','LABEL', 'TYPE')->where('TYPE', '=', 1)->orWhere('TYPE', '=', 7)->orderBy('ORDRE')->get();
        $BOMServicesSelect = MethodsServices::select('id', 'CODE','LABEL', 'TYPE')->where('TYPE', '=', 2)
                                                                            ->orWhere('TYPE', '=', 3)
                                                                            ->orWhere('TYPE', '=', 4)
                                                                            ->orWhere('TYPE', '=', 5)
                                                                            ->orWhere('TYPE', '=', 6)
                                                                            ->orWhere('TYPE', '=', 8)
                                                                            ->orderBy('ORDRE')->get();
        return view('products/products-show', [
            'Product' => $Product,
            'TechProductList' => $TechProductList,
            'BOMProductList' => $BOMProductList,
            'ProductSelect' => $ProductSelect,
            'TechServicesSelect' =>  $TechServicesSelect,
            'BOMServicesSelect' =>  $BOMServicesSelect,
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
