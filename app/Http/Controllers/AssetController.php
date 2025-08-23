<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assets\Asset;
use Illuminate\Support\Facades\Redirect;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.factory', 'permission:asset_manager']);
    }

    public function index()
    {
        $assets = Asset::all();
        return view('assets.assets-index', compact('assets'));
    }

    public function create()
    {
        return view('assets.assets-create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'category' => 'nullable|string',
            'acquisition_value' => 'required|numeric',
            'acquisition_date' => 'required|date',
            'depreciation_duration' => 'required|integer',
        ]);
        $asset = Asset::create($data);
        return Redirect::route('assets.show', $asset->id);
    }

    public function show($id)
    {
        $asset = Asset::with('accountingEntries')->findOrFail($id);
        return view('assets.assets-show', compact('asset'));
    }

    public function edit($id)
    {
        $asset = Asset::findOrFail($id);
        return view('assets.assets-edit', compact('asset'));
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string',
            'category' => 'nullable|string',
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
