<?php 
namespace App\Http\Controllers\OSH;

use Illuminate\Http\Request;
use App\Models\OSH\OSHIncident;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Http\Requests\OSH\StoreIncidentRequest;
use App\Http\Requests\OSH\UpdateIncidentRequest;

class IncidentsController extends Controller
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
        // Récupération de tous les incidents
        $incidents = OSHIncident::all();
        $userSelect = $this->SelectDataService->getUsers();
        
        // Retourner la vue avec les incidents
        return view('osh/osh-incidents', compact('incidents', 'userSelect'));
    }

    public function store(StoreIncidentRequest $request)
    {
        OSHIncident::create($request->validated());
        return redirect()->route('osh.incidents')->with('success', 'Incident created successfully.');
    }

    public function update(UpdateIncidentRequest $request, $id)
    {

        $incident = OSHIncident::findOrFail($id);
        $incident->update($request->validated());

        return redirect()->route('osh.incidents')->with('success', 'Incident updated successfully.');
    }

}
