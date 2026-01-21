<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Maintenance\WorkOrder;
use App\Models\Times\TimesMachineEvent;
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
        $workOrders = WorkOrder::with(['asset', 'machineEvent', 'creator'])
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
        $workOrder = WorkOrder::with(['asset', 'machineEvent', 'creator'])->findOrFail($id);

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
            'requested' => 'Requested',
            'scheduled' => 'Scheduled',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'canceled' => 'Canceled',
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'times_machine_event_id' => 'nullable|exists:times_machine_events,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:requested,scheduled,in_progress,completed,canceled',
            'requested_at' => 'required|date',
            'scheduled_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
        ]);
    }
}
