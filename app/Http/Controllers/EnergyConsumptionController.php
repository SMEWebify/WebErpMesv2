<?php

namespace App\Http\Controllers;

use App\Models\EnergyConsumption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EnergyConsumptionController extends Controller
{
    public function index()
    {
        $energyConsumptions = EnergyConsumption::all();
        return view('energy-consumptions.energy-consumptions-index', compact('energyConsumptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'recorded_at' => 'required|date',
            'amount' => 'required|numeric',
        ]);
        EnergyConsumption::create($data);
        return Redirect::route('energy-consumptions.index');
    }

    public function show($id)
    {
        $energyConsumption = EnergyConsumption::findOrFail($id);
        return view('energy-consumptions.energy-consumptions-show', compact('energyConsumption'));
    }
}
