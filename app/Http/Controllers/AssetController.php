<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assets\Asset;
use App\Services\SelectDataService;
use Illuminate\Support\Facades\Redirect;

class AssetController extends Controller
{
    protected SelectDataService $SelectDataService;

    public function __construct(SelectDataService $SelectDataService)
    {
        $this->middleware(['auth', 'check.factory', 'permission:asset_manager']);
        $this->SelectDataService = $SelectDataService;
    }

    public function index()
    {
        $assets = Asset::with('methodsRessource')->orderBy('id')->paginate(10);
        return view('assets.assets-index', compact('assets'));
    }

    public function create()
    {
        $ressourcesSelect = $this->SelectDataService->getRessources();
        return view('assets.assets-create', compact('ressourcesSelect'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category' => 'nullable|string',
            'methods_ressource_id' => 'nullable|exists:methods_ressources,id',
            'acquisition_value' => 'required|numeric',
            'acquisition_date' => 'required|date',
            'depreciation_duration' => 'required|integer',
        ]);
        $asset = Asset::create($data);
        return Redirect::route('assets.show', $asset->id);
    }

    public function show($id)
    {
        $asset = Asset::with(['accountingEntries', 'workOrders', 'maintenancePlans'])->findOrFail($id);
        return view('assets.assets-show', compact('asset'));
    }

    public function edit($id)
    {
        $asset = Asset::findOrFail($id);
        $ressourcesSelect = $this->SelectDataService->getRessources();
        return view('assets.assets-edit', compact('asset', 'ressourcesSelect'));
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string',
            'category' => 'nullable|string',
            'methods_ressource_id' => 'nullable|exists:methods_ressources,id',
            'acquisition_value' => 'required|numeric',
            'acquisition_date' => 'required|date',
            'depreciation_duration' => 'required|integer',
        ]);
        $asset->update($data);
        return Redirect::route('assets.show', $asset->id);
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();
        return Redirect::route('assets');
    }
}
