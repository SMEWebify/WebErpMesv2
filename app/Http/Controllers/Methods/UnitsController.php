<?php

namespace App\Http\Controllers\Methods;

use Illuminate\Http\Request;
use App\Services\SelectDataService;
use App\Models\Methods\MethodsUnits;
use App\Models\Methods\MethodsLocation;
use App\Http\Requests\Methods\StoreUnitRequest;
use App\Http\Requests\Methods\UpdateUnitRequest;

class UnitsController extends Controller
{
    protected $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->SelectDataService = $SelectDataService;
    }

    /**
     * Display a listing of the units.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $MethodsUnits = MethodsUnits::orderBy('id')->get();
        return view('methods/methods-untis', [
            'MethodsUnits' => $MethodsUnits,
        ]);
    }
    
    /**
     * Store a newly created unit in storage.
     *
     * @param \App\Http\Requests\Methods\StoreUnitRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreUnitRequest $request)
    {
        $Unit = MethodsUnits::create($request->only('code', 'label','type'));
        return redirect()->route('methods.unit')->with('success', 'Successfully created unit.');
    }

    /**
     * Update the specified unit in storage.
     *
     * @param \App\Http\Requests\Methods\UpdateUnitRequest $request
    * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateUnitRequest $request)
    {
        if ($request->default == 1) {
            MethodsUnits::query()->update(['default' => 0]);
        }
        $unit = MethodsUnits::findOrFail($request->id);
        $unit->update([
            'label' => $request->label,
            'type' => $request->type,
            'default' => $request->default,
        ]);
        return redirect()->route('methods.unit')->with('success', 'Successfully updated Unit.');
    }
}
