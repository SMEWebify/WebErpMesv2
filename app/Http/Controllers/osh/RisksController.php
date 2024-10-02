<?php 
namespace App\Http\Controllers\OSH;

use Illuminate\Http\Request;
use App\Models\OSH\OSHRisque;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Http\Requests\OSH\StoreRiskRequest;
use App\Http\Requests\OSH\UpdateRiskRequest;

class RisksController extends Controller
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
        // Récupération de tous les risques
        $risks = OSHRisque::all();
        $userSelect = $this->SelectDataService->getUsers();
        $sectionsSelect =  $this->SelectDataService->getSection();
        
        // Retourner la vue avec les risques
        return view('osh/osh-risks', compact('risks', 'userSelect', 'sectionsSelect'));
    }

    public function store(StoreRiskRequest $request)
    {
        OSHRisque::create($request->validated());
        return redirect()->route('osh.risks')->with('success', 'Risk created successfully.');
    }

    public function update(UpdateRiskRequest $request, $id)
    {
        $risk = OSHRisque::findOrFail($id);
        $risk->update($request->validated());

        return redirect()->route('osh.risks')->with('success', 'Risk updated successfully.');
    }
}
