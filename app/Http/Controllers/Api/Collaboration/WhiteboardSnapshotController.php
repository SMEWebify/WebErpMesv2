<?php

namespace App\Http\Controllers\Api\Collaboration;

use App\Http\Controllers\Controller;
use App\Models\Collaboration\Whiteboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhiteboardSnapshotController extends Controller
{
    public function index(Whiteboard $whiteboard): JsonResponse
    {
        $snapshots = $whiteboard->snapshots()
            ->latest()
            ->get();

        return response()->json($snapshots);
    }

    public function store(Request $request, Whiteboard $whiteboard): JsonResponse
    {
        $data = $request->validate([
            'state' => ['required'],
        ]);

        $snapshot = $whiteboard->snapshots()->create([
            'state' => $data['state'],
            'created_by' => optional($request->user())->id,
        ]);

        return response()->json($snapshot, 201);
    }
}
