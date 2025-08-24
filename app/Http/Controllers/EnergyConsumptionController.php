<?php

namespace App\Http\Controllers;

use App\Models\EnergyConsumption;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EnergyConsumptionController extends Controller
{
    public function index()
    {
        $energyConsumptions = EnergyConsumption::all();
        $machines = Machine::all();
        return view('energy-consumptions.energy-consumptions-index', compact('energyConsumptions', 'machines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'kwh' => 'required|numeric',
            'cost_per_kwh' => 'required|numeric',
        ]);

        $data['total_cost'] = $data['kwh'] * $data['cost_per_kwh'];

        EnergyConsumption::create($data);
        return Redirect::route('energy-consumptions.index');
    }

    public function show($id)
    {
        $energyConsumption = EnergyConsumption::findOrFail($id);
        return view('energy-consumptions.energy-consumptions-show', compact('energyConsumption'));
    }
}
