<?php 

namespace App\Http\Controllers\OSH;

use Illuminate\Http\Request;
use App\Models\OSH\OSHConformite;
use App\Services\SelectDataService;
use App\Http\Controllers\Controller;
use App\Http\Requests\OSH\StoreConformityRequest;
use App\Http\Requests\OSH\UpdateConformityRequest;

class ConformitiesController extends Controller
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
        // Récupération de toutes les conformités
        $conformities = OSHConformite::all();
        $userSelect = $this->SelectDataService->getUsers();
        $sectionsSelect =  $this->SelectDataService->getSection();
        // Retourner la vue avec les conformités
        return view('osh/osh-conformities', compact('conformities', 'userSelect', 'sectionsSelect'));
    }

    public function store(StoreConformityRequest $request)
    {
        OSHConformite::create($request->validated());
        return redirect()->route('osh.conformities')->with('success', 'Conformity created successfully.');
    }

    public function update(UpdateConformityRequest $request, $id)
    {
        $conformity = OSHConformite::findOrFail($id);
        $conformity->update($request->validated());

        return redirect()->route('osh.conformities')->with('success', 'Conformity updated successfully.');
    }
}
