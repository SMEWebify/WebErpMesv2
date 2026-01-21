<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Maintenance\WorkOrder;
use App\Models\Times\TimesMachineEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.factory', 'permission:asset_manager']);
    }

    public function index()
    {
        $workOrders = WorkOrder::with(['asset', 'machineEvent', 'creator', 'technician'])
            ->orderByDesc('requested_at')
            ->paginate(15);

        return view('gmao.work-orders-index', [
            'workOrders' => $workOrders,
        ]);
    }

    public function create(Request $request)
    {
        return view('gmao.work-orders-create', [
            'assets' => Asset::orderBy('name')->get(),
            'machineEvents' => TimesMachineEvent::orderBy('ordre')->get(),
            'priorities' => $this->priorityOptions(),
            'statuses' => $this->statusOptions(),
            'workTypes' => $this->workTypeOptions(),
            'technicians' => User::orderBy('name')->get(),
            'selectedAssetId' => $request->get('asset_id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['created_by'] = Auth::id();

        $workOrder = WorkOrder::create($data);

        return redirect()->route('gmao.work-orders.show', $workOrder->id);
    }

    public function show($id)
    {
        $workOrder = WorkOrder::with(['asset', 'machineEvent', 'creator', 'technician'])->findOrFail($id);

        return view('gmao.work-orders-show', [
            'workOrder' => $workOrder,
        ]);
    }

    public function edit($id)
    {
        $workOrder = WorkOrder::findOrFail($id);

        return view('gmao.work-orders-edit', [
            'workOrder' => $workOrder,
            'assets' => Asset::orderBy('name')->get(),
            'machineEvents' => TimesMachineEvent::orderBy('ordre')->get(),
            'priorities' => $this->priorityOptions(),
            'statuses' => $this->statusOptions(),
            'workTypes' => $this->workTypeOptions(),
            'technicians' => User::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $workOrder = WorkOrder::findOrFail($id);
        $data = $this->validatedData($request);

        $workOrder->update($data);

        return redirect()->route('gmao.work-orders.show', $workOrder->id);
    }

    public function destroy($id)
    {
        $workOrder = WorkOrder::findOrFail($id);
        $workOrder->delete();

        return redirect()->route('gmao.work-orders.index');
    }

    private function priorityOptions(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }

    private function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'planned' => 'Planned',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'closed' => 'Closed',
        ];
    }

    private function workTypeOptions(): array
    {
        return [
            'preventive' => 'Preventive',
            'corrective' => 'Corrective',
            'improvement' => 'Improvement',
            'safety' => 'Safety',
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'times_machine_event_id' => 'nullable|exists:times_machine_events,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'actions_performed' => 'nullable|string',
            'parts_consumed' => 'nullable|string',
            'comments' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'work_type' => 'required|in:preventive,corrective,improvement,safety',
            'status' => 'required|in:draft,planned,in_progress,completed,closed',
            'requested_at' => 'required|date',
            'scheduled_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
            'estimated_duration_minutes' => 'nullable|integer|min:0',
            'actual_duration_minutes' => 'nullable|integer|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'failure_type' => 'nullable|string|max:255',
            'severity' => 'nullable|string|max:255',
            'machine_stopped' => 'nullable|boolean',
            'failure_started_at' => 'nullable|date',
        ]);
    }
}
