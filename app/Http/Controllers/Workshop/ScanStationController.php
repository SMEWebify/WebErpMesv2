<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Services\Production\ScanStation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Écran de scan atelier (docs/pointage-scan.md) : page plein écran et action
 * d'un scan. Même accès que l'écran tâche de l'atelier (groupe workshop).
 */
class ScanStationController extends Controller
{
    public function index()
    {
        return view('workshop/workshop-scan', [
            'mode' => ScanStation::mode(),
            'trans' => __('scan_station.screen'),
            'modeLabel' => __('scan_station.modes.' . ScanStation::mode()),
        ]);
    }

    public function scan(Request $request, ScanStation $station): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:200',
            'good_qty' => 'nullable|integer|min:0|max:1000000',
            'bad_qty' => 'nullable|integer|min:0|max:1000000',
        ]);

        $quantities = $request->has('good_qty') || $request->has('bad_qty')
            ? ['good' => (int) ($validated['good_qty'] ?? 0), 'bad' => (int) ($validated['bad_qty'] ?? 0)]
            : null;

        $outcome = $station->scan($validated['code'], Auth::id(), $quantities);
        $task = $outcome['task'];

        return response()->json([
            'result' => $outcome['result'],
            'message' => $outcome['reason'] ?? __('scan_station.results.' . $outcome['result']),
            'code' => trim($validated['code']),
            'task' => $task ? ScanStation::summary($task) : null,
            'url' => $task ? route('workshop.task.statu.id', ['id' => $task->getKey()]) : null,
        ], $outcome['result'] === ScanStation::NOT_FOUND ? 404 : 200);
    }
}
