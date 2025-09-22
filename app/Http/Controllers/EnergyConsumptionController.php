<?php

namespace App\Http\Controllers;

use App\Models\EnergyConsumption;
use App\Models\Methods\MethodsRessources;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class EnergyConsumptionController extends Controller
{
    public function index()
    {
        $energyConsumptions = EnergyConsumption::with('methodsRessource')->get();
        $methodsRessources = MethodsRessources::orderBy('label')->get();

        return view('energy-consumptions.energy-consumptions-index', compact('energyConsumptions', 'methodsRessources'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'methods_ressource_id' => 'required|exists:methods_ressources,id',
            'kwh' => 'required|numeric',
            'cost_per_kwh' => 'required|numeric',
        ]);

        $data['total_cost'] = $data['kwh'] * $data['cost_per_kwh'];

        EnergyConsumption::create($data);
        return Redirect::route('energy-consumptions.index');
    }

    public function show($id)
    {
        $energyConsumption = EnergyConsumption::with('methodsRessource')->findOrFail($id);
        return view('energy-consumptions.energy-consumptions-show', compact('energyConsumption'));
    }
}
