<?php

namespace App\Http\Controllers\Api\Collaboration;

use App\Http\Controllers\Controller;
use App\Models\Collaboration\Whiteboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhiteboardFileController extends Controller
{
    public function index(Whiteboard $whiteboard): JsonResponse
    {
        $files = $whiteboard->files()
            ->latest()
            ->get();

        return response()->json($files);
    }

    public function store(Request $request, Whiteboard $whiteboard): JsonResponse
    {
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'max:10240'],
        ]);

        $userId = optional($request->user())->id;
        $savedFiles = [];

        foreach ($request->file('files', []) as $uploadedFile) {
            $path = $uploadedFile->store('whiteboards', ['disk' => 'public']);

            $file = $whiteboard->files()->create([
                'original_name' => $uploadedFile->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
                'uploaded_by' => $userId,
            ]);

            $savedFiles[] = $file;
        }

        return response()->json($savedFiles, 201);
    }
}
