<?php

namespace App\Http\Controllers\Inspection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Inspection\InspectionNonconformity;
use App\Models\Inspection\InspectionProject;

class InspectionNonconformityController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inspection_project_id' => ['required', 'integer'],
            'inspection_measure_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:open,in_progress,closed'],
        ]);

        InspectionProject::findOrFail($validated['inspection_project_id']);

        $nonconformity = InspectionNonconformity::create([
            'inspection_project_id' => $validated['inspection_project_id'],
            'inspection_measure_id' => $validated['inspection_measure_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'open',
            'created_by' => Auth::id(),
        ]);

        return response()->json($nonconformity, 201);
    }
}
