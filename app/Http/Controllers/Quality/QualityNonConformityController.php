<?php

namespace App\Http\Controllers\Quality;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Companies\Companies;
use App\Models\Workflow\DeliveryLines;
use App\Services\SelectDataService;
use App\Services\DocumentCodeGenerator;
use App\Models\Quality\QualityNonConformity;
use App\Services\QualityNonConformityService;
use App\Http\Requests\Quality\StoreQualityNonConformityRequest;
use App\Http\Requests\Quality\UpdateQualityNonConformityRequest;

class QualityNonConformityController extends Controller
{
    protected $SelectDataService;
    public $qualityNonConformityService;
    protected $documentCodeGenerator;

    public function __construct(SelectDataService $SelectDataService,
                                QualityNonConformityService $qualityNonConformityService,
                                DocumentCodeGenerator $documentCodeGenerator)
    {
        $this->SelectDataService = $SelectDataService;
        $this->qualityNonConformityService = $qualityNonConformityService;
        $this->documentCodeGenerator = $documentCodeGenerator;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $userSelect      = $this->SelectDataService->getUsers();
        $ServicesSelect  = $this->SelectDataService->getServices();
        $CompanieSelect  = Companies::select('id', 'code', 'client_type', 'civility', 'label', 'last_name')->orderBy('label')->get();
        $CausesSelect    = $this->SelectDataService->getQualityCause();
        $FailuresSelect  = $this->SelectDataService->getQualityFailure();
        $CorrectionsSelect = $this->SelectDataService->getQualityCorrection();

        $LastNonConformity  = QualityNonConformity::orderBy('id', 'desc')->first();
        $lastId             = $LastNonConformity ? $LastNonConformity->id : 0;
        $codeNonConformity  = $this->documentCodeGenerator->generateDocumentCode('non-conformities', $lastId);

        $endpoints = [
            'list'         => route('quality.nonConformitie.api.list'),
            'store'        => route('quality.nonConformitie.api.store'),
            'apiBase'      => url('/quality/nonConformitie/api'),
            'pdfBase'      => preg_replace('/\/[^\/]+$/', '', route('pdf.nc', ['Document' => 1])),
            'taskBase'     => preg_replace('/\/[^\/]+$/', '', route('production.task.statu.id', ['id' => 1])),
            'deliveryBase' => preg_replace('/\/[^\/]+$/', '', route('deliverys.show', ['id' => 1])),
            'companieBase' => preg_replace('/\/[^\/]+$/', '', route('companies.show', ['id' => 1])),
            'orderBase'    => preg_replace('/\/[^\/]+$/', '', route('orders.show', ['id' => 1])),
        ];

        return view('quality/quality-non-conformities', [
            'codeNonConformity' => $codeNonConformity,
            'endpoints'         => $endpoints,
            'userSelect'        => $userSelect,
            'ServicesSelect'    => $ServicesSelect,
            'CompanieSelect'    => $CompanieSelect,
            'CausesSelect'      => $CausesSelect,
            'CorrectionsSelect' => $CorrectionsSelect,
            'FailuresSelect'    => $FailuresSelect,
        ]);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreQualityNonConformityRequest $request)
    {
        $this->qualityNonConformityService->createNonConformity($request->validated());
        return redirect()->route('quality.nonConformitie')->with('success', __('general_content.non_conformity_created_success_trans_key'));
    }

    public function createNCFromDelivery($uuid, $id)
    {
        $deliveryLine = DeliveryLines::with('delivery')->findOrFail($id);
        abort_unless($deliveryLine->delivery && $deliveryLine->delivery->uuid === $uuid, 403);
        $this->qualityNonConformityService->createNCFromDelivery($id);
        return redirect()->back()->with('success', __('general_content.non_conformity_created_success_trans_key'));
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateQualityNonConformityRequest $request)
    {
        $QualityNonConformity = QualityNonConformity::find($request->id);
        $QualityNonConformity->label  = $request->label;
        $QualityNonConformity->statu  = $request->statu;
        if ($request->type_update) $QualityNonConformity->type = 1;
        else $QualityNonConformity->type = 2;
        $QualityNonConformity->user_id           = $request->user_id;
        $QualityNonConformity->service_id        = $request->service_id;
        $QualityNonConformity->failure_id        = $request->failure_id;
        $QualityNonConformity->failure_comment   = $request->failure_comment;
        $QualityNonConformity->causes_id         = $request->causes_id;
        $QualityNonConformity->causes_comment    = $request->causes_comment;
        $QualityNonConformity->correction_id     = $request->correction_id;
        $QualityNonConformity->correction_comment = $request->correction_comment;
        $QualityNonConformity->companie_id       = $request->companie_id;
        $QualityNonConformity->qty               = $request->qty;
        $QualityNonConformity->save();
        return redirect()->route('quality.nonConformitie')->with('success', __('general_content.non_conformity_updated_success_trans_key'));
    }

    public function closeResolutionDate($id)
    {
        $nonConformity = QualityNonConformity::findOrFail($id);
        if ($nonConformity) {
            $nonConformity->resolution_date = now();
            $nonConformity->statu = 3;
            $nonConformity->save();
            return redirect()->back()->with('success', 'The resolution date has been updated.');
        }
    }

    public function reopenResolutionDate($id)
    {
        $nonConformity = QualityNonConformity::findOrFail($id);
        if ($nonConformity) {
            $nonConformity->resolution_date = null;
            $nonConformity->statu = 1;
            $nonConformity->save();
            return redirect()->back()->with('success', 'The NC date has been updated.');
        }
    }

    // -------------------------------------------------------------------------
    // React API methods
    // -------------------------------------------------------------------------

    public function apiList(Request $request): JsonResponse
    {
        $search   = $request->input('search', '');
        $statuses = $request->input('statuses', []);
        $types    = $request->input('types', []);
        $allowed  = ['id', 'code', 'label', 'statu', 'type', 'created_at'];
        $sort     = in_array($request->input('sort'), $allowed) ? $request->input('sort') : 'id';
        $asc      = $request->boolean('asc', false);

        $query = QualityNonConformity::with([
            'UserManagement',
            'Failure',
            'Cause',
            'Correction',
            'service',
            'companie',
            'orderLine.order',
            'delivery',
            'task',
        ])->orderBy($sort, $asc ? 'asc' : 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('label', 'like', "%{$search}%");
            });
        }
        if (!empty($statuses)) {
            $query->whereIn('statu', array_map('intval', (array) $statuses));
        }
        if (!empty($types)) {
            $query->whereIn('type', array_map('intval', (array) $types));
        }

        $items = $query->paginate(15);

        return response()->json([
            'data' => $items->map(fn ($nc) => $this->formatNc($nc)),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page'    => $items->lastPage(),
                'total'        => $items->total(),
                'per_page'     => $items->perPage(),
            ],
        ]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'               => 'required|string|unique:quality_non_conformities,code',
            'label'              => 'required|string|max:255',
            'statu'              => 'nullable|integer|in:1,2,3,4',
            'type'               => 'nullable|integer|in:1,2',
            'user_id'            => 'required|exists:users,id',
            'service_id'         => 'nullable|exists:methods_services,id',
            'failure_id'         => 'nullable|exists:quality_failures,id',
            'failure_comment'    => 'nullable|string',
            'causes_id'          => 'nullable|exists:quality_causes,id',
            'causes_comment'     => 'nullable|string',
            'correction_id'      => 'nullable|exists:quality_corrections,id',
            'correction_comment' => 'nullable|string',
            'companie_id'        => 'nullable|exists:companies,id',
            'qty'                => 'nullable|numeric|min:1',
        ]);

        // The service uses isset($data['type']) to distinguish internal/external.
        // We normalize: remove 'type' key for external (2), keep for internal (1).
        $typeValue = (int) ($data['type'] ?? 2);
        if ($typeValue === 2) unset($data['type']);

        $nc = $this->qualityNonConformityService->createNonConformity($data);
        $nc->load(['UserManagement', 'Failure', 'Cause', 'Correction', 'service', 'companie', 'orderLine.order', 'delivery', 'task']);

        return response()->json(['success' => true, 'data' => $this->formatNc($nc)], 201);
    }

    public function apiUpdate(Request $request, $id): JsonResponse
    {
        $nc = QualityNonConformity::findOrFail($id);

        $data = $request->validate([
            'label'              => 'required|string|max:255',
            'statu'              => 'nullable|integer|in:1,2,3,4',
            'type'               => 'nullable|integer|in:1,2',
            'user_id'            => 'nullable|exists:users,id',
            'service_id'         => 'nullable|exists:methods_services,id',
            'failure_id'         => 'nullable|exists:quality_failures,id',
            'failure_comment'    => 'nullable|string',
            'causes_id'          => 'nullable|exists:quality_causes,id',
            'causes_comment'     => 'nullable|string',
            'correction_id'      => 'nullable|exists:quality_corrections,id',
            'correction_comment' => 'nullable|string',
            'companie_id'        => 'nullable|exists:companies,id',
            'qty'                => 'nullable|numeric|min:1',
        ]);

        $nc->label              = $data['label'];
        $nc->statu              = $data['statu'] ?? $nc->statu;
        $nc->type               = $data['type'] ?? $nc->type;
        $nc->user_id            = $data['user_id'] ?? $nc->user_id;
        $nc->service_id         = $data['service_id'] ?? null;
        $nc->failure_id         = $data['failure_id'] ?? null;
        $nc->failure_comment    = $data['failure_comment'] ?? null;
        $nc->causes_id          = $data['causes_id'] ?? null;
        $nc->causes_comment     = $data['causes_comment'] ?? null;
        $nc->correction_id      = $data['correction_id'] ?? null;
        $nc->correction_comment = $data['correction_comment'] ?? null;
        $nc->companie_id        = array_key_exists('companie_id', $data) ? $data['companie_id'] : $nc->companie_id;
        $nc->qty                = $data['qty'] ?? null;
        $nc->save();

        $nc->load(['UserManagement', 'Failure', 'Cause', 'Correction', 'service', 'companie', 'orderLine.order', 'delivery', 'task']);

        return response()->json(['success' => true, 'data' => $this->formatNc($nc)]);
    }

    public function apiClose($id): JsonResponse
    {
        $nc = QualityNonConformity::findOrFail($id);
        $nc->resolution_date = now();
        $nc->statu = 3;
        $nc->save();

        return response()->json(['success' => true, 'statu' => 3]);
    }

    public function apiReopen($id): JsonResponse
    {
        $nc = QualityNonConformity::findOrFail($id);
        $nc->resolution_date = null;
        $nc->statu = 1;
        $nc->save();

        return response()->json(['success' => true, 'statu' => 1]);
    }

    private function formatNc(QualityNonConformity $nc): array
    {
        return [
            'id'                 => $nc->id,
            'code'               => $nc->code,
            'label'              => $nc->label,
            'statu'              => $nc->statu,
            'type'               => $nc->type,
            'user_id'            => $nc->user_id,
            'user_name'          => $nc->UserManagement?->name,
            'service_id'         => $nc->service_id,
            'service_label'      => $nc->service?->label,
            'failure_id'         => $nc->failure_id,
            'failure_label'      => $nc->Failure?->label,
            'failure_comment'    => $nc->failure_comment,
            'causes_id'          => $nc->causes_id,
            'causes_label'       => $nc->Cause?->label,
            'causes_comment'     => $nc->causes_comment,
            'correction_id'      => $nc->correction_id,
            'correction_label'   => $nc->Correction?->label,
            'correction_comment' => $nc->correction_comment,
            'companie_id'        => $nc->companie_id,
            'companie_label'     => $nc->companie?->label,
            'order_lines_id'     => $nc->order_lines_id,
            'order_id'           => $nc->orderLine?->orders_id,
            'order_code'         => $nc->orderLine?->order?->code,
            'task_id'            => $nc->task_id,
            'deliverys_id'       => $nc->deliverys_id,
            'delivery_code'      => $nc->delivery?->code,
            'qty'                => $nc->qty,
            'resolution_date'    => $nc->resolution_date,
            'created_pretty'     => $nc->GetPrettyCreatedAttribute(),
        ];
    }
}
