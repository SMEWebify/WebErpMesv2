<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsRessources;
use App\Models\Times\WorkShiftPattern;
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
        $MethodsRessources = MethodsRessources::with(['services', 'users'])->orderBy('ordre')->get();
        $SectionsSelect = $this->SelectDataService->getSection();
        $ServicesSelect = $this->SelectDataService->getServices();
        $UsersSelect = $this->SelectDataService->getUsers();
        $ShiftPatternsSelect = WorkShiftPattern::orderBy('code')->get(['id', 'label']);
        return view('methods/methods-ressources', [
            'MethodsRessources' => $MethodsRessources,
            'SectionsSelect' => $SectionsSelect,
            'ServicesSelect' => $ServicesSelect,
            'UsersSelect' => $UsersSelect,
            'ShiftPatternsSelect' => $ShiftPatternsSelect,
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
            [
                'mask_time'   => $request->mask_time ? 1 : 2,
                'is_labor'    => (bool) $request->boolean('is_labor'),
                'labor_ratio' => $request->input('labor_ratio', 0),
                'work_shift_pattern_id' => $request->input('work_shift_pattern_id') ?: null,
            ]
        ));

        $Ressource->services()->sync($this->serviceSyncPayload($request));
        $Ressource->users()->sync($this->userSyncPayload($request));

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
            'is_labor' => (bool) $request->boolean('is_labor'),
            'labor_ratio' => $request->input('labor_ratio', 0),
            'work_shift_pattern_id' => $request->input('work_shift_pattern_id') ?: null,
        ]);

        $ressource->services()->sync($this->serviceSyncPayload($request));
        $ressource->users()->sync($this->userSyncPayload($request));

        return redirect()->route('methods.ressource')->with('success', __('general_content.resource_updated_success_trans_key'));
    }
    
    /**
     * Construit le payload de synchronisation des services réalisables.
     *
     * Le service principal (methods_services_id, conservé sur la ressource pour
     * l'affichage et la compatibilité) ouvre la liste et devient donc le premier
     * choix de l'affectation automatique (preference = 0).
     *
     * @return array<int, array<string, int>>
     */
    private function serviceSyncPayload(Request $request): array
    {
        return collect([$request->input('methods_services_id')])
            ->merge($request->input('additional_services', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->mapWithKeys(fn ($id, $preference) => [$id => ['preference' => $preference]])
            ->all();
    }

    /**
     * Personnes habilitées sur la ressource. Niveau autonome par défaut : la
     * granularité fine (formation / référent, date de validité) se pilote depuis
     * le pivot, pas depuis ce formulaire.
     *
     * @return array<int, array<string, int>>
     */
    private function userSyncPayload(Request $request): array
    {
        return collect($request->input('qualified_users', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->mapWithKeys(fn ($id) => [$id => ['level' => MethodsRessources::LEVEL_AUTONOMOUS]])
            ->all();
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
