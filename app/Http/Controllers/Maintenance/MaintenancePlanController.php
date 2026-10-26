<?php

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Models\Assets\Asset;
use App\Models\Maintenance\MaintenancePlan;
use App\Services\Maintenance\PreventiveMaintenanceScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

    public function show($id, PreventiveMaintenanceScheduler $scheduler)
    {
        $plan = MaintenancePlan::with('asset')->findOrFail($id);

        return view('gmao.maintenance-plans-show', [
            'plan' => $plan,
            'nextDueDate' => $scheduler->nextDueDate($plan),
            'workOrders' => $plan->workOrders()->orderByDesc('id')->limit(10)->get(),
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

    public function generateWorkOrder($id, PreventiveMaintenanceScheduler $scheduler)
    {
        $plan = MaintenancePlan::with('asset')->findOrFail($id);

        $workOrder = $scheduler->createWorkOrder(
            $plan,
            $scheduler->nextDueDate($plan) ?? $plan->fixed_date,
            Auth::id()
        );

        return redirect()->route('gmao.work-orders.show', $workOrder->id);
    }

    private function triggerTypeOptions(): array
    {
        return MaintenancePlan::triggerTypeOptions();
    }

    private function validatedData(Request $request): array
    {
        // Intervalle et date ne sont exigés que pour la génération automatique : un plan
        // existant reste modifiable sans les renseigner.
        $auto = $request->boolean('auto_generate');

        $data = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'trigger_type' => 'required|in:time,machine_hours,cycles,fixed_date',
            'trigger_value' => 'nullable|string|max:255',
            'fixed_date' => ['nullable', 'date', Rule::requiredIf($auto && $request->input('trigger_type') === 'fixed_date')],
            'interval_days' => ['nullable', 'integer', 'min:1', 'max:3650', Rule::requiredIf($auto && $request->input('trigger_type') === 'time')],
            'lead_days' => 'nullable|integer|min:0|max:365',
            'estimated_duration_minutes' => 'nullable|integer|min:0',
            'required_skill' => 'nullable|string|max:255',
            'actions' => 'nullable|string',
            'required_parts' => 'nullable|string',
        ]);

        $data['auto_generate'] = $auto;
        $data['lead_days'] = $data['lead_days'] ?? 7;

        return $data;
    }
}
