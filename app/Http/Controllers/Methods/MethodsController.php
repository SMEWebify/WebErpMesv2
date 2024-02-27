<?php

namespace App\Http\Controllers\Methods;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsRessources;

class MethodsController extends Controller
{
    
    /**
     * @return View
     */
    public function index()
    {
        $MethodsServices = MethodsServices::orderBy('ordre')->paginate(10);
        $ServicesSelect = MethodsServices::select('id', 'label')->orderBy('ordre')->get();
        $MethodsRessources = MethodsRessources::orderBy('ordre')->paginate(10);
        $RessourcesSelect = MethodsRessources::select('id', 'label')->orderBy('label')->get();
        $MethodsSections = MethodsSection::orderBy('ordre')->paginate(10);
        $SectionsSelect = MethodsSection::select('id', 'label')->orderBy('label')->get();
        $MethodsLocations = MethodsLocation::orderBy('id')->paginate(10);
        $MethodsUnits = MethodsUnits::orderBy('id')->paginate(10);
        $MethodsFamilies = MethodsFamilies::orderBy('id')->paginate(10);
        $MethodsTools = MethodsTools::orderBy('code')->paginate(10);
        $userSelect = User::select('id', 'name')->get();
        $SupplierSelect = Companies::select('id', 'label')->orderBy('label')->where('statu_supplier', 2)->get();

        return view('methods/methods-index', [
            'MethodsServices' => $MethodsServices,
            'ServicesSelect' => $ServicesSelect,
            'MethodsRessources' => $MethodsRessources,
            'RessourcesSelect' => $RessourcesSelect,
            'MethodsUnits' =>  $MethodsUnits,
            'MethodsFamilies' => $MethodsFamilies,
            'MethodsSections' => $MethodsSections,
            'SectionsSelect' =>  $SectionsSelect,
            'MethodsLocations' =>  $MethodsLocations,
            'userSelect' => $userSelect,
            'SupplierSelect' => $SupplierSelect,
            'MethodsTools' => $MethodsTools
            
        ]);
    }
}
