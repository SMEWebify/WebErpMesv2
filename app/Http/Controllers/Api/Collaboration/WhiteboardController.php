<?php

namespace App\Http\Controllers\Api\Collaboration;

use App\Http\Controllers\Controller;
use App\Models\Collaboration\Whiteboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhiteboardController extends Controller
{
    public function index(): JsonResponse
    {
        $whiteboards = Whiteboard::query()
            ->latest()
            ->get();

        return response()->json($whiteboards);
    }

    public function show(Whiteboard $whiteboard): JsonResponse
    {
        $whiteboard->load(['snapshots' => fn ($query) => $query->latest(), 'files' => fn ($query) => $query->latest()]);

        return response()->json($whiteboard);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateWhiteboard($request);
        $userId = optional($request->user())->id;

        $whiteboard = new Whiteboard();
        $whiteboard->fill($data);
        $whiteboard->created_by = $userId;
        $whiteboard->updated_by = $userId;
        $whiteboard->save();

        $whiteboard = $whiteboard->fresh();
        $whiteboard->load(['snapshots' => fn ($query) => $query->latest(), 'files' => fn ($query) => $query->latest()]);

        return response()->json($whiteboard);
    }

    public function update(Request $request, Whiteboard $whiteboard): JsonResponse
    {
        $data = $this->validateWhiteboard($request);
        $userId = optional($request->user())->id;

        $whiteboard->fill($data);
        $whiteboard->updated_by = $userId;
        $whiteboard->save();

        $whiteboard = $whiteboard->fresh();
        $whiteboard->load(['snapshots' => fn ($query) => $query->latest(), 'files' => fn ($query) => $query->latest()]);

        return response()->json($whiteboard);
    }

    protected function validateWhiteboard(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable'],
        ]);

        if (!array_key_exists('title', $validated)) {
            $validated['title'] = $request->input('title');
        }

        if (!array_key_exists('state', $validated)) {
            $validated['state'] = $request->input('state');
        }

        if (!$validated['title']) {
            $validated['title'] = 'Nouveau tableau';
        }

        return $validated;
    }
}
