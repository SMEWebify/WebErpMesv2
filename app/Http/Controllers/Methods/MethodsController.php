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
    //
    public function index()
    {

        $MethodsServices = MethodsServices::orderBy('ORDRE')->paginate(10);
        $ServicesSelect = MethodsServices::select('id', 'LABEL')->orderBy('ORDRE')->get();
        $MethodsRessources = MethodsRessources::orderBy('ORDRE')->paginate(10);
        $RessourcesSelect = MethodsRessources::select('id', 'LABEL')->orderBy('LABEL')->get();
        $MethodsSections = MethodsSection::orderBy('ORDRE')->paginate(10);
        $SectionsSelect = MethodsSection::select('id', 'LABEL')->orderBy('LABEL')->get();
        $MethodsLocations = MethodsLocation::orderBy('id')->paginate(10);
        $MethodsUnits = MethodsUnits::orderBy('id')->paginate(10);
        $MethodsFamilies = MethodsFamilies::orderBy('id')->paginate(10);
        $MethodsTools = MethodsTools::orderBy('CODE')->paginate(10);

        $userSelect = User::select('id', 'name')->get();
        $CompaniesSelect = Companies::select('id', 'LABEL')->orderBy('LABEL')->where('STATU_FOUR', 2)->get();

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
            'CompaniesSelect' => $CompaniesSelect,
            'MethodsTools' => $MethodsTools
            
        ]);

    }
}
