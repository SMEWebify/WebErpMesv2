<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnergyConsumption;
use Illuminate\Http\Request;

class EnergyConsumptionController extends Controller
{
    public function index()
    {
        return response()->json(EnergyConsumption::with('methodsRessource')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'methods_ressource_id' => 'required|exists:methods_ressources,id',
            'kwh' => 'required|numeric',
            'cost_per_kwh' => 'required|numeric',
        ]);

        $consumption = EnergyConsumption::create($data);

        return response()->json($consumption->load('methodsRessource'), 201);
    }
}

