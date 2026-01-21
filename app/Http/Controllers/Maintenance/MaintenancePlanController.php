<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Maintenance\MaintenancePlan;
use App\Models\Maintenance\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenancePlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.factory', 'permission:asset_manager']);
    }

    public function index()
    {
        $plans = MaintenancePlan::with('asset')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('gmao.maintenance-plans-index', [
            'plans' => $plans,
        ]);
    }

    public function create(Request $request)
    {
        return view('gmao.maintenance-plans-create', [
            'assets' => Asset::orderBy('name')->get(),
            'triggerTypes' => $this->triggerTypeOptions(),
            'selectedAssetId' => $request->get('asset_id'),
        ]);
    }

    public function store(Request $request)
    {
        $plan = MaintenancePlan::create($this->validatedData($request));

        return redirect()->route('gmao.maintenance-plans.show', $plan->id);
    }

    public function show($id)
    {
        $plan = MaintenancePlan::with('asset')->findOrFail($id);

        return view('gmao.maintenance-plans-show', [
            'plan' => $plan,
        ]);
    }

    public function edit($id)
    {
        $plan = MaintenancePlan::findOrFail($id);

        return view('gmao.maintenance-plans-edit', [
            'plan' => $plan,
            'assets' => Asset::orderBy('name')->get(),
            'triggerTypes' => $this->triggerTypeOptions(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = MaintenancePlan::findOrFail($id);
        $plan->update($this->validatedData($request));

        return redirect()->route('gmao.maintenance-plans.show', $plan->id);
    }

    public function destroy($id)
    {
        $plan = MaintenancePlan::findOrFail($id);
        $plan->delete();

        return redirect()->route('gmao.maintenance-plans.index');
    }

    public function generateWorkOrder($id)
    {
        $plan = MaintenancePlan::with('asset')->findOrFail($id);

        $workOrder = WorkOrder::create([
            'asset_id' => $plan->asset_id,
            'title' => $plan->title,
            'description' => $plan->description,
            'actions_performed' => $plan->actions,
            'parts_consumed' => $plan->required_parts,
            'priority' => 'medium',
            'work_type' => 'preventive',
            'status' => 'planned',
            'requested_at' => now()->toDateString(),
            'scheduled_at' => $plan->fixed_date?->toDateString(),
            'estimated_duration_minutes' => $plan->estimated_duration_minutes,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('gmao.work-orders.show', $workOrder->id);
    }

    private function triggerTypeOptions(): array
    {
        return [
            'time' => 'Time-based',
            'machine_hours' => 'Machine hours',
            'cycles' => 'Cycles / pieces',
            'fixed_date' => 'Fixed date',
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:time,machine_hours,cycles,fixed_date',
            'trigger_value' => 'nullable|string|max:255',
            'fixed_date' => 'nullable|date',
            'estimated_duration_minutes' => 'nullable|integer|min:0',
            'required_skill' => 'nullable|string|max:255',
            'actions' => 'nullable|string',
            'required_parts' => 'nullable|string',
        ]);
    }
}
