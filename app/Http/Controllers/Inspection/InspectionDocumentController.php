<?php

namespace App\Http\Controllers\Inspection;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Inspection\InspectionDocument;
use App\Models\Inspection\InspectionProject;
use App\Models\User;
use App\Notifications\InspectionDocumentApprovalNotification;

class InspectionDocumentController extends Controller
{
    private const STORAGE_DIRECTORY = 'inspection-documents';

    public function store(Request $request, $projectId)
    {
        $project = InspectionProject::findOrFail($projectId);

        $validated = $request->validate([
            'file'          => [
                'required',
                'file',
                'mimes:pdf,png,jpg,jpeg',
                'mimetypes:application/pdf,image/png,image/jpeg',
                'max:10240',
            ],
            'type'          => ['nullable', 'in:plan,spec,photo,other'],
            'version_label' => ['nullable', 'string', 'max:50'],
        ]);

        $file = $validated['file'];
        $originalName = $file->getClientOriginalName();
        $mime = $file->getMimeType();
        $extension = $file->guessExtension() ?: 'bin';
        $fileName = Auth::id() . '_' . Str::uuid()->toString() . '.' . $extension;

        Storage::disk('local')->putFileAs(self::STORAGE_DIRECTORY, $file, $fileName);

        // Obsolète les documents approuvés du même projet/type avant d'en créer un nouveau
        InspectionDocument::where('inspection_project_id', $project->id)
            ->where('type', $validated['type'] ?? 'plan')
            ->where('status', InspectionDocument::STATUS_APPROVED)
            ->each(fn ($doc) => $doc->makeObsolete());

        $document = InspectionDocument::create([
            'inspection_project_id' => $project->id,
            'type'                  => $validated['type'] ?? 'plan',
            'file_path'             => self::STORAGE_DIRECTORY . '/' . $fileName,
            'file_name'             => $originalName,
            'mime'                  => $mime,
            'version_label'         => $validated['version_label'] ?? null,
            'status'                => InspectionDocument::STATUS_DRAFT,
        ]);

        return response()->json($document, 201);
    }

    public function download($documentId)
    {
        $document = InspectionDocument::findOrFail($documentId);
        $path = self::STORAGE_DIRECTORY . '/' . basename($document->file_path);

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, $document->file_name);
    }

    public function submit($documentId)
    {
        $document = InspectionDocument::findOrFail($documentId);

        if ($document->status !== InspectionDocument::STATUS_DRAFT) {
            return response()->json(['message' => 'Seul un document en brouillon peut être soumis.'], 422);
        }

        $document->submit();

        $approvers = User::role(['admin', 'manager'])->get();
        Notification::send($approvers, new InspectionDocumentApprovalNotification($document, Auth::id()));

        return response()->json($document->fresh('Approver'));
    }

    public function approve($documentId)
    {
        $document = InspectionDocument::findOrFail($documentId);

        if ($document->status !== InspectionDocument::STATUS_PENDING_APPROVAL) {
            return response()->json(['message' => 'Seul un document en attente d\'approbation peut être approuvé.'], 422);
        }

        $document->approve();

        return response()->json($document->fresh('Approver'));
    }

    public function obsolete($documentId)
    {
        $document = InspectionDocument::findOrFail($documentId);

        if ($document->status !== InspectionDocument::STATUS_APPROVED) {
            return response()->json(['message' => 'Seul un document approuvé peut être rendu obsolète.'], 422);
        }

        $document->makeObsolete();

        return response()->json($document->fresh());
    }
}
