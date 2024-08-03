<?php

namespace App\Http\Controllers\Methods;

use App\Services\SelectDataService;
use App\Models\Methods\MethodsFamilies;
use App\Models\Methods\MethodsLocation;
use App\Http\Requests\Methods\StoreFamilyRequest;
use App\Http\Requests\Methods\UpdateFamilyRequest;

class FamiliesController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $MethodsFamilies = MethodsFamilies::orderBy('id')->get();
        $MethodsLocations = MethodsLocation::orderBy('id')->get();
        $RessourcesSelect = $this->SelectDataService->getRessources();
        $ServicesSelect = $this->SelectDataService->getServices();

        return view('methods/methods-families', [
            'MethodsFamilies' => $MethodsFamilies,
            'MethodsLocations' => $MethodsLocations,
            'RessourcesSelect' => $RessourcesSelect,
            'ServicesSelect' => $ServicesSelect,
        ]);
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Methods\StoreFamilyRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreFamilyRequest $request)
    {
        $Family = MethodsFamilies::create($request->only('code', 'label','methods_services_id'));
        return redirect()->route('methods.family')->with('success', 'Successfully created family.');
    }

    /**
     * Update the specified resource in storage.
     *
    * @param \App\Http\Requests\Methods\UpdateFamilyRequest  $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateFamilyRequest $request)
    {
        $family = MethodsFamilies::findOrFail($request->id);
        $family->update($request->only(['label', 'methods_services_id']));
        return redirect()->route('methods.family')->with('success', 'Successfully updated Family.');
    }
}
