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
        
        $Ressource = MethodsRessources::create(array_merge(
            $request->only('ordre', 'code', 'label', 'capacity', 'section_id', 'color', 'methods_services_id'),
            ['mask_time' => $request->mask_time ? 1 : 2]
        ));

        if($request->hasFile('picture')){
            $Ressource = MethodsRessources::findOrFail($Ressource->id);
            $path = $request->file('picture')->store('images/ressources', 'public');
            $Ressource->update(['picture' => basename($path)]);
            $Ressource->save();
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
        return redirect()->route('methods.ressource')->with('success', __('general_content.resource_created_success_trans_key'));
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

        return redirect()->route('methods.ressource')->with('success', __('general_content.resource_updated_success_trans_key'));
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
            return redirect()->route('methods.ressource')->with('success', __('general_content.resource_updated_success_trans_key'));
        }
        else{
            return back()->withInput()->withErrors(['msg' => 'Error, no image selected']);
        }
    }
}
