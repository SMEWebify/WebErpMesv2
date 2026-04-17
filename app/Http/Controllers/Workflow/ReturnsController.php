<?php

namespace App\Http\Controllers\Workflow;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Workflow\Returns as ReturnModel;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\DeliveryLines;
use App\Models\Quality\QualityNonConformity;
use App\Services\ReturnService;
use App\Services\DocumentCodeGenerator;

class ReturnsController extends Controller
{
    public function __construct(
        protected ReturnService $returnService,
        protected DocumentCodeGenerator $documentCodeGenerator,
    ) {}

    public function index()
    {
        $lastReturn = ReturnModel::latest('id')->first();

        // Build template URLs without using the {return} route parameter (reserved keyword issue)
        $base = rtrim(route('returns'), '/');
        $reactEndpoints = [
            'list'     => route('returns.json.list'),
            'store'    => route('returns.json.store'),
            'diagnose' => $base . '/json/__ID__/diagnose',
            'reopen'   => $base . '/json/__ID__/reopen',
            'close'    => $base . '/json/__ID__/close',
            'show'     => $base . '/__ID__',
        ];

        $reactProps = [
            'nextCode'         => $this->documentCodeGenerator->generateDocumentCode('return', $lastReturn?->id),
            'deliveries'       => Deliverys::select('id', 'code')->orderBy('code')->get()->toArray(),
            'nonConformities'  => QualityNonConformity::select('id', 'code')->orderBy('code')->get()->toArray(),
        ];

        $reactTrans = [
            'list'             => __('returns.fields.list'),
            'add_return'       => __('returns.fields.add_return'),
            'code'             => __('returns.fields.code'),
            'label'            => __('returns.fields.label'),
            'delivery'         => __('returns.fields.delivery'),
            'non_conformity'   => __('returns.fields.non_conformity'),
            'customer_report'  => __('returns.fields.customer_report'),
            'lines'            => __('returns.fields.lines'),
            'delivery_line'    => __('returns.fields.delivery_line'),
            'task'             => __('returns.fields.task'),
            'issue'            => __('returns.fields.issue'),
            'action'           => __('returns.fields.action'),
            'diagnosis'        => __('returns.fields.diagnosis'),
            'closure_comment'  => __('returns.fields.closure_comment'),
            'status'           => __('returns.fields.status'),
            'created_at'       => __('returns.fields.created_at'),
            'actions'          => __('returns.fields.actions'),
            'view'             => __('returns.fields.view'),
            'add_line'         => __('returns.fields.add_line'),
            'save'             => __('returns.fields.save'),
            'choose'           => __('returns.fields.choose'),
            'reopen_tasks'     => __('returns.fields.reopen_tasks'),
            'close_return'     => __('returns.fields.close_return'),
            'status_received'  => __('returns.status.received'),
            'status_diagnosed' => __('returns.status.diagnosed'),
            'status_in_rework' => __('returns.status.in_rework'),
            'status_closed'    => __('returns.status.closed'),
            'search'           => __('general_content.search_trans_key'),
            'all'              => __('general_content.all_trans_key'),
            'qty'              => __('general_content.qty_trans_key'),
            'no_data'          => __('general_content.no_data_trans_key'),
            'loading'          => __('general_content.notif_loading_trans_key'),
        ];

        return view('workflow.returns-index', compact('reactEndpoints', 'reactProps', 'reactTrans'));
    }

    public function show(int $id)
    {
        $return = ReturnModel::findOrFail($id);
        return view('workflow.returns-show', [
            'return' => $return,
        ]);
    }

    // -------------------------------------------------------------------------
    // JSON endpoints
    // -------------------------------------------------------------------------

    public function listJson(Request $request)
    {
        $search      = $request->get('search', '');
        $statusFilter = $request->filled('status') ? $request->get('status') : null;
        $sortField   = in_array($request->get('sort', 'created_at'), ['code', 'label', 'created_at', 'statu'])
            ? $request->get('sort', 'created_at')
            : 'created_at';
        $sortAsc     = $request->boolean('asc', false);

        $query = ReturnModel::with(['delivery:id,code', 'qualityNonConformity:id,code'])
            ->when($search, fn ($q) => $q->where(fn ($q2) =>
                $q2->where('code', 'like', '%'.$search.'%')
                   ->orWhere('label', 'like', '%'.$search.'%')
            ))
            ->when($statusFilter !== null, fn ($q) => $q->where('statu', (int) $statusFilter))
            ->orderBy($sortField, $sortAsc ? 'asc' : 'desc');

        $returns = $query->paginate(15);

        return response()->json([
            'data' => $returns->map(fn ($r) => [
                'id'               => $r->id,
                'code'             => $r->code,
                'label'            => $r->label,
                'statu'            => $r->statu,
                'status_label'     => $r->status_label,
                'delivery'         => $r->delivery ? ['id' => $r->delivery->id, 'code' => $r->delivery->code] : null,
                'non_conformity'   => $r->qualityNonConformity ? ['id' => $r->qualityNonConformity->id, 'code' => $r->qualityNonConformity->code] : null,
                'diagnosis'        => $r->diagnosis,
                'customer_report'  => $r->customer_report,
                'resolution_notes' => $r->resolution_notes,
                'created_at'       => $r->created_at?->format('d/m/Y'),
                'url'              => route('returns.show', ['id' => $r->id]),
            ]),
            'meta' => [
                'total'        => $returns->total(),
                'per_page'     => $returns->perPage(),
                'current_page' => $returns->currentPage(),
                'last_page'    => $returns->lastPage(),
            ],
        ]);
    }

    public function storeJson(Request $request)
    {
        $data = $request->validate([
            'code'                      => 'nullable|string|max:255',
            'label'                     => 'required|string|max:255',
            'deliverys_id'              => 'nullable|exists:deliverys,id',
            'quality_non_conformity_id' => 'nullable|exists:quality_non_conformities,id',
            'customer_report'           => 'nullable|string',
            'lines'                     => 'array|min:1',
            'lines.*.delivery_line_id'  => 'nullable|exists:delivery_lines,id',
            'lines.*.original_task_id'  => 'nullable|exists:tasks,id',
            'lines.*.qty'               => 'nullable|integer|min:1',
            'lines.*.issue_description' => 'nullable|string',
            'lines.*.rework_instructions' => 'nullable|string',
        ]);

        $payload = array_merge($data, ['created_by' => Auth::id()]);
        $return  = $this->returnService->registerReturn($payload, $data['lines'] ?? []);

        return response()->json(['message' => __('returns.messages.created'), 'id' => $return->id], 201);
    }

    public function diagnoseJson(Request $request, int $id)
    {
        $return = ReturnModel::findOrFail($id);
        $data = $request->validate([
            'diagnosis'       => 'required|string',
            'customer_report' => 'nullable|string',
        ]);

        $this->returnService->diagnose($return, array_merge($data, ['diagnosed_by' => Auth::id()]));

        return response()->json(['message' => __('returns.messages.diagnosed')]);
    }

    public function reopenJson(int $id)
    {
        $return = ReturnModel::with('lines')->findOrFail($id);
        $tasks  = $this->returnService->createReworkTasks($return);

        $message = empty($tasks)
            ? __('returns.messages.no_task')
            : __('returns.messages.tasks_reopened');

        return response()->json(['message' => $message]);
    }

    public function closeJson(Request $request, int $id)
    {
        $return = ReturnModel::findOrFail($id);
        $data = $request->validate([
            'resolution_notes' => 'nullable|string',
        ]);

        $this->returnService->close($return, $data);

        return response()->json(['message' => __('returns.messages.closed')]);
    }
}
