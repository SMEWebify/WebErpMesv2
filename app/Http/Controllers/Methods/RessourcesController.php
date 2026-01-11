<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsRessources;
use App\Http\Requests\Methods\StoreRessourceRequest;
use App\Http\Requests\Methods\UpdateRessourceRequest;

class RessourcesController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * Display a listing of the ressources.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $MethodsRessources = MethodsRessources::orderBy('ordre')->get();
        $SectionsSelect = $this->SelectDataService->getSection();
        $ServicesSelect = $this->SelectDataService->getServices();
        return view('methods/methods-ressources', [
            'MethodsRessources' => $MethodsRessources,
            'SectionsSelect' => $SectionsSelect,
            'ServicesSelect' => $ServicesSelect,
        ]);
    }

    /**
     * Store a newly created ressource in storage.
     *
     * @param \App\Http\Requests\Methods\StoreRessourceRequest $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreRessourceRequest $request)
    {
        
        $Ressource =  MethodsRessources::create($request->only('ordre','code', 'label', 'capacity','section_id', 'color', 'methods_services_id'));
        
        if($request->mask_time) $Ressource->mask_time=1;
        else $Ressource->mask_time = 2;
        $Ressource->save();

        if($request->hasFile('picture')){
            $Ressource = MethodsRessources::findOrFail($Ressource->id);
            $path = $request->file('picture')->store('images/ressources', 'public');
            $Ressource->update(['picture' => basename($path)]);
            $Ressource->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
        return redirect()->route('methods.ressource')->with('success', 'Successfully created ressource.');
    }

    /**
     * Update the specified ressource in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateRessourceRequest $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateRessourceRequest $request)
    {
        $ressource = MethodsRessources::findOrFail($request->id);

        $ressource->update([
            'ordre' => $request->ordre,
            'label' => $request->label,
            'mask_time' => $request->mask_time_update ? 1 : 2,
            'capacity' => $request->capacity,
            'section_id' => $request->section_id,
            'color' => $request->color,
            'methods_services_id' => $request->methods_services_id,
        ]);

        return redirect()->route('methods.ressource')->with('success', 'Successfully updated ressource.');
    }
    
    /**
     * @param \Illuminate\Http\Request $request
      * @return \Illuminate\Http\RedirectResponse
     */
    public function StoreImage(Request $request)
    {
        
        $request->validate([
            'picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
        ]);
        
        if($request->hasFile('picture')){
            $Service = MethodsRessources::findOrFail($request->id);
            $path = $request->file('picture')->store('images/ressources', 'public');
            $Service->update(['picture' => basename($path)]);
            $Service->save();
            return redirect()->route('methods.ressource')->with('success', 'Successfully updated ressource.');
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
