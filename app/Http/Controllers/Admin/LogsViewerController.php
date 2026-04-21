<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class LogsViewerController extends Controller
{
    public function meta()
    {
        return response()->json([
            'available_models' => Activity::select('subject_type')->distinct()->pluck('subject_type'),
            'available_users'  => User::select('id', 'name')->get(),
        ]);
    }

    public function list(Request $request)
    {
        $validated = $request->validate([
            'startDate'   => 'required|date',
            'endDate'     => 'required|date|after_or_equal:startDate',
            'userId'      => 'nullable|integer',
            'subjectType' => 'nullable|string',
            'subjectId'   => 'nullable|integer',
            'model'       => 'nullable|string',
        ]);

        $query = Activity::query();

        if (!empty($validated['subjectType']) && !empty($validated['subjectId'])) {
            $query->where('subject_type', $validated['subjectType'])
                  ->where('subject_id', $validated['subjectId']);
        } elseif (!empty($validated['model'])) {
            $query->where('subject_type', $validated['model']);
        }

        $query->whereDate('created_at', '>=', $validated['startDate'])
              ->whereDate('created_at', '<=', $validated['endDate']);

        if (!empty($validated['userId'])) {
            $query->where('causer_id', $validated['userId']);
        }

        $logs = $query->latest()->get()->map(function ($log) {
            $props = $log->properties;
            $propsArray = is_string($props) ? json_decode($props, true) : $props->toArray();

            return [
                'id'           => $log->id,
                'description'  => $log->description,
                'subject_type' => $log->subject_type,
                'causer_type'  => $log->causer_type,
                'properties'   => $propsArray,
                'created_at'   => $log->created_at?->format('d/m/Y H:i'),
            ];
        });

        return response()->json(['logs' => $logs]);
    }
}
