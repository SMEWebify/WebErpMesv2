<?php

namespace App\Http\Controllers\Methods;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Companies\Companies;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsTools;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsSection;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsLocation;
use App\Models\Methods\MethodsServices;
use App\Models\Methods\MethodsRessources;
use App\Models\Methods\MethodsStandardNomenclature;

class MethodsController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        
        $ServicesSelect = $this->SelectDataService->getServices();
        $RessourcesSelect = $this->SelectDataService->getRessources();
        $SectionsSelect = $this->SelectDataService->getSection();
        $userSelect = $this->SelectDataService->getUsers();
        $SupplierSelect = $this->SelectDataService->getSupplier();

        $MethodsServices = MethodsServices::orderBy('ordre')->get();
        $MethodsRessources = MethodsRessources::orderBy('ordre')->get();
        $MethodsSections = MethodsSection::orderBy('ordre')->get();
        $MethodsLocations = MethodsLocation::orderBy('id')->get();
        $MethodsUnits = MethodsUnits::orderBy('id')->get();
        $MethodsFamilies = MethodsFamilies::orderBy('id')->get();
        $MethodsTools = MethodsTools::orderBy('code')->get();
        $MethodsStandardNomenclatures = MethodsStandardNomenclature::orderBy('id')->get();

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
            'MethodsTools' => $MethodsTools,
            'MethodsStandardNomenclatures' => $MethodsStandardNomenclatures
            
        ]);
    }
}
