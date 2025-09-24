<?php

namespace App\Http\Controllers\Collaboration;

use App\Http\Controllers\Controller;
use App\Models\Collaboration\Whiteboard;
use Illuminate\Contracts\View\View;

class WhiteboardController extends Controller
{
    public function show(?Whiteboard $whiteboard = null): View
    {
        $board = $whiteboard
            ? $whiteboard->load(['snapshots' => fn ($query) => $query->latest()->limit(20), 'files' => fn ($query) => $query->latest()])
            : Whiteboard::with(['snapshots' => fn ($query) => $query->latest()->limit(20), 'files' => fn ($query) => $query->latest()])
                ->latest()
                ->first();

        return view('collaboration.whiteboard-index', [
            'whiteboard' => $board,
            'snapshots' => $board?->snapshots ?? [],
            'files' => $board?->files ?? [],
            'endpoints' => [],
        ]);
    }
}
