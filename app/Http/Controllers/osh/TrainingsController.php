<?php

namespace App\Http\Controllers\OSH;

use Illuminate\Http\Request;
use App\Models\OSH\OSHFormation;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Http\Requests\OSH\StoreTrainingRequest;
use App\Http\Requests\OSH\UpdateTrainingRequest;

class TrainingsController extends Controller
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
        // Récupération de toutes les formations
        $trainings = OSHFormation::all();
        $userSelect = $this->SelectDataService->getUsers();
        
        // Retourner la vue avec les formations
        return view('osh/osh-trainings', compact('trainings', 'userSelect'));
    }

    public function store(StoreTrainingRequest $request)
    {
        OSHFormation::create($request->validated());
        return redirect()->route('osh.training')->with('success', 'Training created successfully.');
    }

    public function update(UpdateTrainingRequest $request, $id)
    {
        $training = OSHFormation::findOrFail($id);
        $training->update($request->validated());
        $training->certification_obtained = $request->certification_obtained;
        $training->save();
        return redirect()->route('osh.training')->with('success', 'Training updated successfully.');
    }
}
