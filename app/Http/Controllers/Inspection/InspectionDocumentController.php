<?php

namespace App\Http\Controllers\Inspection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Inspection\InspectionDocument;
use App\Models\Inspection\InspectionProject;

class InspectionDocumentController extends Controller
{
    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $projectId)
    {
        $project = InspectionProject::findOrFail($projectId);

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg'],
            'type' => ['nullable', 'in:plan,spec,photo,other'],
            'version_label' => ['nullable', 'string', 'max:50'],
        ]);

        $file = $validated['file'];
        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType();
        $extension = $file->getClientOriginalExtension();
        $fileName = Auth::id() . '_' . Str::uuid()->toString() . '.' . $extension;

        $directory = public_path('inspection-documents');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file->move($directory, $fileName);

        $document = InspectionDocument::create([
            'inspection_project_id' => $project->id,
            'type' => $validated['type'] ?? 'plan',
            'file_path' => 'inspection-documents/' . $fileName,
            'file_name' => $originalName,
            'mime' => $mime,
            'version_label' => $validated['version_label'] ?? null,
        ]);

        return response()->json($document, 201);
    }
}
