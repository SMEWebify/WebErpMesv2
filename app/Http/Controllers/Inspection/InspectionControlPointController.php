<?php

namespace App\Http\Controllers\Inspection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionProject;

class InspectionControlPointController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $projectId)
    {
        $project = InspectionProject::findOrFail($projectId);

        $validated = $request->validate([
            'points' => ['nullable', 'array'],
            'points.*.number' => ['required_with:points', 'integer'],
            'points.*.label' => ['required_with:points', 'string', 'max:255'],
            'points.*.category' => ['nullable', 'in:dimension,visual,attribute'],
            'points.*.nominal_value' => ['nullable', 'numeric'],
            'points.*.tol_min' => ['nullable', 'numeric'],
            'points.*.tol_max' => ['nullable', 'numeric'],
            'points.*.unit' => ['nullable', 'string', 'max:20'],
            'points.*.frequency_type' => ['nullable', 'in:all,one_of_n,first_last,custom'],
            'points.*.frequency_value' => ['nullable', 'integer'],
            'points.*.plan_page' => ['nullable', 'integer'],
            'points.*.plan_ref' => ['nullable', 'string', 'max:100'],
            'points.*.phase' => ['nullable', 'string', 'max:100'],
            'points.*.instrument_type' => ['nullable', 'string', 'max:100'],
            'points.*.is_critical' => ['nullable', 'boolean'],
            'points.*.order' => ['nullable', 'integer'],
            'number' => ['required_without:points', 'integer'],
            'label' => ['required_without:points', 'string', 'max:255'],
            'category' => ['nullable', 'in:dimension,visual,attribute'],
            'nominal_value' => ['nullable', 'numeric'],
            'tol_min' => ['nullable', 'numeric'],
            'tol_max' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:20'],
            'frequency_type' => ['nullable', 'in:all,one_of_n,first_last,custom'],
            'frequency_value' => ['nullable', 'integer'],
            'plan_page' => ['nullable', 'integer'],
            'plan_ref' => ['nullable', 'string', 'max:100'],
            'phase' => ['nullable', 'string', 'max:100'],
            'instrument_type' => ['nullable', 'string', 'max:100'],
            'is_critical' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer'],
        ]);

        if (!empty($validated['points'])) {
            $created = collect($validated['points'])->map(function ($point) use ($project) {
                return InspectionControlPoint::create(array_merge($point, [
                    'inspection_project_id' => $project->id,
                ]));
            });

            return response()->json($created, 201);
        }

        $point = InspectionControlPoint::create(array_merge($validated, [
            'inspection_project_id' => $project->id,
        ]));

        return response()->json($point, 201);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $point = InspectionControlPoint::findOrFail($id);

        $validated = $request->validate([
            'number' => ['sometimes', 'integer'],
            'label' => ['sometimes', 'string', 'max:255'],
            'category' => ['nullable', 'in:dimension,visual,attribute'],
            'nominal_value' => ['nullable', 'numeric'],
            'tol_min' => ['nullable', 'numeric'],
            'tol_max' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:20'],
            'frequency_type' => ['nullable', 'in:all,one_of_n,first_last,custom'],
            'frequency_value' => ['nullable', 'integer'],
            'plan_page' => ['nullable', 'integer'],
            'plan_ref' => ['nullable', 'string', 'max:100'],
            'phase' => ['nullable', 'string', 'max:100'],
            'instrument_type' => ['nullable', 'string', 'max:100'],
            'is_critical' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer'],
        ]);

        $point->update($validated);

        return response()->json($point);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $point = InspectionControlPoint::findOrFail($id);
        $point->delete();

        return response()->json(['status' => 'deleted']);
    }
}
