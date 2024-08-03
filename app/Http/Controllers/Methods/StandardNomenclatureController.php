<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Models\Methods\MethodsStandardNomenclature;
use App\Http\Requests\Methods\StoreStandardNomenclatureRequest;
use App\Http\Requests\Methods\UpdateStandardNomenclatureRequest;

class StandardNomenclatureController extends Controller
{
    
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }
    
    /**
     * Display a listing of the standard nomenclature.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        
        $MethodsStandardNomenclatures = MethodsStandardNomenclature::orderBy('id')->get();
        return view('methods/methods-standard-bom', [
            'MethodsStandardNomenclatures' => $MethodsStandardNomenclatures,
        ]);
    }

    /**
     * Store a newly created standard nomenclature in storage.
     *
     * @param \App\Http\Requests\Methods\StoreStandardNomenclatureRequest $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreStandardNomenclatureRequest $request)
    {
        $StandardNomenclature = MethodsStandardNomenclature::create($request->only('code', 'label','comment'));
        return redirect()->route('methods.standard.nomenclature')->with('success', 'Successfully created standard nomenclature.');
    }

    /**
     * Update the specified standard nomenclature in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateStandardNomenclatureRequest $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateStandardNomenclatureRequest $request)
    {
        $standardNomenclature = MethodsStandardNomenclature::findOrFail($request->id);
        $standardNomenclature->update($request->only(['label', 'comment']));
        return redirect()->route('methods.standard.nomenclature')->with('success', 'Successfully updated standard nomenclature.');
    }
}
