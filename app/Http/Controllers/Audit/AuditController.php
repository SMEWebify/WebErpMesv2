<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Audit\AuditPlan;
use App\Models\Audit\AuditProcess;
use App\Models\Audit\AuditSchedule;
use App\Models\Audit\AuditChecklist;
use App\Models\Audit\AuditChecklistItem;
use App\Models\Audit\AuditExecution;
use App\Models\Audit\AuditFinding;
use App\Models\Quality\QualityAction;

class AuditController extends Controller
{
    // ─── VIEW ────────────────────────────────────────────────────────────────

    public function index()
    {
        $users = User::select('id', 'name')->orderBy('name')->get();

        $processes = AuditProcess::withTrashed(false)
            ->with('responsibleUser:id,name')
            ->orderBy('name')
            ->get();

        $checklists = AuditChecklist::with(['items' => fn($q) => $q->orderBy('order_index')])
            ->orderBy('iso_clause')
            ->orderBy('title')
            ->get();

        $kpi = $this->buildKpi();

        $endpoints = [
            // Plans
            'plansIndex'      => route('audit.api.plans.index'),
            'plansStore'      => route('audit.api.plans.store'),
            'planBase'        => url('/audit/api/plans'),
            // Processes
            'processesIndex'  => route('audit.api.processes.index'),
            'processesStore'  => route('audit.api.processes.store'),
            'processBase'     => url('/audit/api/processes'),
            // Schedules
            'schedulesIndex'  => route('audit.api.schedules.index'),
            'schedulesStore'  => route('audit.api.schedules.store'),
            'scheduleBase'    => url('/audit/api/schedules'),
            // Checklists
            'checklistsIndex' => route('audit.api.checklists.index'),
            // Executions
            'executionBase'   => url('/audit/api/executions'),
            // Findings
            'findingBase'     => url('/audit/api/findings'),
            // Dashboard
            'dashboardKpi'    => route('audit.api.dashboard'),
        ];

        return view('audit.audit-planner', [
            'endpoints'  => $endpoints,
            'users'      => $users,
            'processes'  => $processes,
            'checklists' => $checklists,
            'kpi'        => $kpi,
            'canAdmin'   => true, // route already protected by permission:audit-menu
        ]);
    }

    // ─── DASHBOARD KPI ───────────────────────────────────────────────────────

    public function apiDashboard(): JsonResponse
    {
        return response()->json($this->buildKpi());
    }

    private function buildKpi(): array
    {
        $year = now()->year;

        $totalSchedules    = AuditSchedule::whereHas('plan', fn($q) => $q->where('year', $year))->count();
        $completedSchedules = AuditSchedule::whereHas('plan', fn($q) => $q->where('year', $year))
            ->where('status', 'completed')->count();
        $cancelledSchedules = AuditSchedule::whereHas('plan', fn($q) => $q->where('year', $year))
            ->where('status', 'cancelled')->count();

        $totalFindings  = AuditFinding::whereHas('execution.schedule.plan', fn($q) => $q->where('year', $year))->count();
        $majorNc        = AuditFinding::whereHas('execution.schedule.plan', fn($q) => $q->where('year', $year))
            ->where('type', 'major_nc')->count();
        $minorNc        = AuditFinding::whereHas('execution.schedule.plan', fn($q) => $q->where('year', $year))
            ->where('type', 'minor_nc')->count();
        $observations   = AuditFinding::whereHas('execution.schedule.plan', fn($q) => $q->where('year', $year))
            ->where('type', 'observation')->count();

        $findingsByClause = AuditFinding::whereHas('execution.schedule.plan', fn($q) => $q->where('year', $year))
            ->whereNotNull('iso_clause')
            ->select('iso_clause', DB::raw('count(*) as count'))
            ->groupBy('iso_clause')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $monthly = AuditSchedule::whereHas('plan', fn($q) => $q->where('year', $year))
            ->select(DB::raw('MONTH(planned_date) as month'), 'status', DB::raw('count(*) as count'))
            ->groupBy('month', 'status')
            ->get();

        return [
            'year'               => $year,
            'totalSchedules'     => $totalSchedules,
            'completedSchedules' => $completedSchedules,
            'cancelledSchedules' => $cancelledSchedules,
            'completionRate'     => $totalSchedules > 0 ? round($completedSchedules / $totalSchedules * 100, 1) : 0,
            'totalFindings'      => $totalFindings,
            'majorNc'            => $majorNc,
            'minorNc'            => $minorNc,
            'observations'       => $observations,
            'findingsByClause'   => $findingsByClause,
            'monthly'            => $monthly,
        ];
    }

    // ─── PLANS ───────────────────────────────────────────────────────────────

    public function plansIndex(): JsonResponse
    {
        $plans = AuditPlan::with(['creator:id,name', 'schedules'])
            ->orderByDesc('year')
            ->orderBy('title')
            ->get()
            ->map(fn($p) => array_merge($p->toArray(), [
                'schedules_count'   => $p->schedules->count(),
                'completed_count'   => $p->schedules->where('status', 'completed')->count(),
                'completion_rate'   => $p->completion_rate,
            ]));

        return response()->json($plans);
    }

    public function plansStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'  => 'required|string|max:255',
            'year'   => 'required|integer|min:2020|max:2099',
            'scope'  => 'nullable|string',
            'status' => 'in:draft,active,closed',
        ]);

        $data['created_by'] = auth()->id();
        $plan = AuditPlan::create($data);
        $plan->load('creator:id,name');

        return response()->json($plan, 201);
    }

    public function plansUpdate(Request $request, int $id): JsonResponse
    {
        $plan = AuditPlan::findOrFail($id);
        $data = $request->validate([
            'title'  => 'required|string|max:255',
            'year'   => 'required|integer|min:2020|max:2099',
            'scope'  => 'nullable|string',
            'status' => 'in:draft,active,closed',
        ]);
        $plan->update($data);

        return response()->json($plan->fresh('creator:id,name'));
    }

    public function plansDestroy(int $id): JsonResponse
    {
        AuditPlan::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ─── PROCESSES ───────────────────────────────────────────────────────────

    public function processesIndex(): JsonResponse
    {
        $processes = AuditProcess::with('responsibleUser:id,name')
            ->orderBy('name')
            ->get();

        return response()->json($processes);
    }

    public function processesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
        ]);

        $process = AuditProcess::create($data);
        $process->load('responsibleUser:id,name');

        return response()->json($process, 201);
    }

    public function processesUpdate(Request $request, int $id): JsonResponse
    {
        $process = AuditProcess::findOrFail($id);
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'description'         => 'nullable|string',
            'responsible_user_id' => 'nullable|exists:users,id',
        ]);
        $process->update($data);

        return response()->json($process->fresh('responsibleUser:id,name'));
    }

    public function processesDestroy(int $id): JsonResponse
    {
        AuditProcess::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ─── SCHEDULES ───────────────────────────────────────────────────────────

    public function schedulesIndex(Request $request): JsonResponse
    {
        $query = AuditSchedule::with([
            'plan:id,title,year,status',
            'process:id,name',
            'checklist:id,title,iso_clause',
            'leadAuditor:id,name',
            'latestExecution',
        ]);

        if ($request->filled('plan_id')) {
            $query->where('audit_plan_id', $request->plan_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('month')) {
            $query->whereMonth('planned_date', $request->month);
        }

        $schedules = $query->orderBy('planned_date')->get();

        return response()->json($schedules);
    }

    public function schedulesStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audit_plan_id'          => 'required|exists:audit_plans,id',
            'audit_process_id'       => 'required|exists:audit_processes,id',
            'audit_checklist_id'     => 'nullable|exists:audit_checklists,id',
            'planned_date'           => 'required|date',
            'planned_duration_hours' => 'integer|min:1|max:40',
            'lead_auditor_id'        => 'nullable|exists:users,id',
            'notes'                  => 'nullable|string',
        ]);

        $schedule = AuditSchedule::create($data);
        $schedule->load(['plan:id,title,year', 'process:id,name', 'leadAuditor:id,name', 'checklist:id,title']);

        return response()->json($schedule, 201);
    }

    public function schedulesUpdate(Request $request, int $id): JsonResponse
    {
        $schedule = AuditSchedule::findOrFail($id);
        $data = $request->validate([
            'audit_checklist_id'     => 'nullable|exists:audit_checklists,id',
            'planned_date'           => 'required|date',
            'planned_duration_hours' => 'integer|min:1|max:40',
            'lead_auditor_id'        => 'nullable|exists:users,id',
            'status'                 => 'in:planned,in_progress,completed,cancelled',
            'notes'                  => 'nullable|string',
        ]);
        $schedule->update($data);

        return response()->json($schedule->fresh(['plan:id,title,year', 'process:id,name', 'leadAuditor:id,name']));
    }

    public function schedulesDestroy(int $id): JsonResponse
    {
        AuditSchedule::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    // ─── CHECKLISTS ──────────────────────────────────────────────────────────

    public function checklistsIndex(): JsonResponse
    {
        $checklists = AuditChecklist::with(['items' => fn($q) => $q->orderBy('order_index')])
            ->orderBy('iso_clause')
            ->orderBy('title')
            ->get();

        return response()->json($checklists);
    }

    // ─── EXECUTIONS ──────────────────────────────────────────────────────────

    public function executionsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audit_schedule_id'      => 'required|exists:audit_schedules,id',
            'actual_date'            => 'required|date',
            'actual_duration_hours'  => 'nullable|integer|min:1|max:40',
            'summary'                => 'nullable|string',
            'conclusion'             => 'nullable|string',
        ]);

        $data['created_by'] = auth()->id();

        $execution = DB::transaction(function () use ($data) {
            $execution = AuditExecution::create($data);
            // Mark the schedule as in_progress
            AuditSchedule::where('id', $data['audit_schedule_id'])
                ->where('status', 'planned')
                ->update(['status' => 'in_progress']);
            return $execution;
        });

        $execution->load(['schedule.process:id,name', 'schedule.plan:id,title,year', 'findings']);

        return response()->json($execution, 201);
    }

    public function executionsShow(int $id): JsonResponse
    {
        $execution = AuditExecution::with([
            'schedule.process:id,name',
            'schedule.plan:id,title,year',
            'schedule.checklist.items',
            'findings.checklistItem',
            'findings.creator:id,name',
            'creator:id,name',
        ])->findOrFail($id);

        return response()->json($execution);
    }

    public function executionsUpdate(Request $request, int $id): JsonResponse
    {
        $execution = AuditExecution::findOrFail($id);
        $data = $request->validate([
            'actual_date'           => 'date',
            'actual_duration_hours' => 'nullable|integer|min:1|max:40',
            'summary'               => 'nullable|string',
            'conclusion'            => 'nullable|string',
        ]);
        $execution->update($data);

        return response()->json($execution->fresh());
    }

    public function executionsClose(int $id): JsonResponse
    {
        $execution = AuditExecution::findOrFail($id);

        DB::transaction(function () use ($execution) {
            $execution->update(['status' => 'closed']);
            AuditSchedule::where('id', $execution->audit_schedule_id)
                ->update(['status' => 'completed']);
        });

        return response()->json($execution->fresh());
    }

    // ─── FINDINGS ────────────────────────────────────────────────────────────

    public function findingsStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audit_execution_id'      => 'required|exists:audit_executions,id',
            'audit_checklist_item_id' => 'nullable|exists:audit_checklist_items,id',
            'type'                    => 'required|in:major_nc,minor_nc,observation,positive_point',
            'description'             => 'required|string',
            'evidence'                => 'nullable|string',
            'iso_clause'              => 'nullable|string|max:50',
        ]);

        $data['created_by'] = auth()->id();
        $finding = AuditFinding::create($data);
        $finding->load(['checklistItem', 'creator:id,name']);

        return response()->json($finding, 201);
    }

    public function findingsUpdate(Request $request, int $id): JsonResponse
    {
        $finding = AuditFinding::findOrFail($id);
        $data = $request->validate([
            'type'               => 'in:major_nc,minor_nc,observation,positive_point',
            'description'        => 'string',
            'evidence'           => 'nullable|string',
            'iso_clause'         => 'nullable|string|max:50',
            'quality_action_id'  => 'nullable|exists:quality_actions,id',
        ]);
        $finding->update($data);

        return response()->json($finding->fresh(['checklistItem', 'creator:id,name', 'qualityAction']));
    }

    public function findingsDestroy(int $id): JsonResponse
    {
        AuditFinding::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}
