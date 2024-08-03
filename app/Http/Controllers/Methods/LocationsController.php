<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsLocation;
use App\Http\Requests\Methods\StoreLocationRequest;
use App\Http\Requests\Methods\UpdateLocationRequest;

class LocationsController extends Controller
{   
    protected $SelectDataService;
    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * Display a listing of the location.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $RessourcesSelect = $this->SelectDataService->getRessources();
        $MethodsLocations = MethodsLocation::orderBy('id')->get();
        $userSelect = $this->SelectDataService->getUsers();
        return view('methods/methods-locations', [
            'RessourcesSelect' => $RessourcesSelect,
            'MethodsLocations' => $MethodsLocations,
            'userSelect' => $userSelect,
        ]);
    }

    /**
     * Store a newly created location in storage.
     *
     * @param \App\Http\Requests\Methods\StoreLocationRequest $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreLocationRequest $request)
    {
        $Location = MethodsLocation::create($request->only('code', 'label','ressource_id','color'));
        return redirect()->route('methods.location')->with('success', 'Successfully created location.');
    }

    /**
     * Update the specified location in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateLocationRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateLocationRequest $request)
    {
        $location = MethodsLocation::findOrFail($request->id);
        $location->update($request->only(['label', 'color', 'ressource_id']));
        return redirect()->route('methods.location')->with('success', 'Successfully updated Location.');
    }
}
