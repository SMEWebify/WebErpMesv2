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
     * @return View
     */
    public function index()
    {
        
        $ServicesSelect = $this->SelectDataService->getServices();
        $RessourcesSelect = $this->SelectDataService->getRessources();
        $SectionsSelect = $this->SelectDataService->getSection();
        $userSelect = $this->SelectDataService->getUsers();
        $SupplierSelect = $this->SelectDataService->getSupplier();

        $MethodsServices = MethodsServices::orderBy('ordre')->paginate(10);
        $MethodsRessources = MethodsRessources::orderBy('ordre')->paginate(10);
        $MethodsSections = MethodsSection::orderBy('ordre')->paginate(10);
        $MethodsLocations = MethodsLocation::orderBy('id')->paginate(10);
        $MethodsUnits = MethodsUnits::orderBy('id')->paginate(10);
        $MethodsFamilies = MethodsFamilies::orderBy('id')->paginate(10);
        $MethodsTools = MethodsTools::orderBy('code')->paginate(10);
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
