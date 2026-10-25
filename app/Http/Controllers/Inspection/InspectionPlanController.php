<?php

namespace App\Http\Controllers\Inspection;

use App\Http\Controllers\Controller;
use App\Models\Inspection\InspectionPlan;
use App\Models\Inspection\InspectionProject;
use App\Models\Methods\MethodsServices;
use App\Models\Products\Products;
use App\Services\Files\FileRole;
use App\Services\Files\ThumbnailStore;
use App\Services\Inspection\ControlPlanNeeds;
use App\Services\Inspection\InspectionPlanPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Plans de contrôle : liste « Plans qualité », bulleur, validation.
 *
 * Lecture : tout utilisateur connecté (comme la GED de l'article). Écriture :
 * inspection-plans-edit ; validation : inspection-plans-approve (middlewares
 * posés sur les routes). Un plan validé n'est plus modifiable : on en crée une
 * nouvelle version, qui rend l'ancienne obsolète à sa validation.
 */
class InspectionPlanController extends Controller
{
    public const EDIT_PERMISSION = 'inspection-plans-edit';
    public const APPROVE_PERMISSION = 'inspection-plans-approve';

    private const TABS = ['needs', InspectionPlan::STATUS_DRAFT, InspectionPlan::STATUS_APPROVED, 'in_progress', 'closed'];

    /**
     * Textes des écrans React. Passés aux vues dans une variable : la directive
     * @json découpe son argument aux virgules et casserait une expression.
     */
    public static function trans(): array
    {
        return __('inspection') + ['locale' => str_replace('_', '-', app()->getLocale())];
    }

    public function indexView()
    {
        return view('quality/quality-plans', [
            'endpoints' => $this->endpoints(),
            'trans'     => self::trans(),
            'canEdit'   => auth()->user()->can(self::EDIT_PERMISSION),
        ]);
    }

    public function bubbler(InspectionPlan $plan)
    {
        $user = auth()->user();

        return view('quality/quality-plan-bubbler', [
            'planId'     => $plan->id,
            'trans'      => self::trans(),
            'endpoints'  => $this->endpoints(),
            'canEdit'    => $user->can(self::EDIT_PERMISSION),
            'canApprove' => $user->can(self::APPROVE_PERMISSION),
            'aiReading'  => app(\App\Services\Inspection\CharacteristicReader::class)->available(),
        ]);
    }

    public function index(Request $request, ControlPlanNeeds $needs): JsonResponse
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'needs';
        $search = trim((string) $request->query('search', '')) ?: null;
        $perPage = 25;
        $page = max(1, (int) $request->query('page', 1));

        if ($tab === 'needs') {
            $all = $needs->all($search);
            $rows = $all->forPage($page, $perPage)->values();
            $total = $all->count();
        } elseif (in_array($tab, ['in_progress', 'closed'], true)) {
            $paginator = $this->dossiers($search, $tab === 'closed')
                ->orderByDesc($tab === 'closed' ? 'closed_at' : 'updated_at')
                ->paginate($perPage, ['*'], 'page', $page);

            $rows = collect($paginator->items())->map(fn (InspectionProject $d) => $this->dossierRow($d));
            $total = $paginator->total();
        } else {
            $paginator = $this->plans($search, $request->query('product_id'))
                ->where('status', $tab)
                ->orderByDesc($tab === InspectionPlan::STATUS_APPROVED ? 'approved_at' : 'updated_at')
                ->paginate($perPage, ['*'], 'page', $page);

            $rows = collect($paginator->items())->map(fn ($plan) => InspectionPlanPresenter::plan($plan));
            $total = $paginator->total();
        }

        return response()->json([
            'tab'    => $tab,
            'rows'   => $rows,
            'meta'   => ['current_page' => $page, 'last_page' => max(1, (int) ceil($total / $perPage)), 'total' => $total],
            'counts' => $this->counts($needs),
        ]);
    }

    /**
     * Plans d'un article, toutes révisions (onglet de la fiche article).
     */
    public function forProduct(Products $product): JsonResponse
    {
        $plans = $this->plans(null, $product->id)
            ->orderByRaw("CASE status WHEN 'draft' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'product_revision' => $product->ind,
            'plans'            => $plans->map(fn ($plan) => InspectionPlanPresenter::plan($plan))->values(),
        ]);
    }

    public function show(InspectionPlan $plan): JsonResponse
    {
        $plan->load(['product', 'file', 'creator', 'approver', 'points']);

        return response()->json([
            'plan'     => InspectionPlanPresenter::plan($plan, withPoints: true),
            'services' => MethodsServices::orderBy('ordre')->get(['id', 'code', 'label']),
            'files'    => $this->productPlanFiles($plan->product),
        ]);
    }

    /**
     * Crée le brouillon d'un article à une révision, ou rouvre celui qui existe.
     * Avec from_plan_id : nouvelle version d'un plan validé, cotes recopiées.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'products_id'  => ['required', 'integer', 'exists:products,id'],
            'revision'     => ['nullable', 'string', 'max:50'],
            'file_id'      => ['nullable', 'integer', 'exists:files,id'],
            'from_plan_id' => ['nullable', 'integer', 'exists:inspection_plans,id'],
        ]);

        $product = Products::findOrFail($data['products_id']);
        $revision = array_key_exists('revision', $data) && $data['revision'] !== null
            ? $data['revision']
            : (filled($product->ind) ? $product->ind : null);

        $existing = InspectionPlan::where('products_id', $product->id)
            ->where('status', InspectionPlan::STATUS_DRAFT)
            ->where(fn ($q) => $revision === null ? $q->whereNull('revision') : $q->where('revision', $revision))
            ->first();

        if ($existing) {
            return response()->json(['plan' => InspectionPlanPresenter::plan($existing)]);
        }

        $fileId = $data['file_id'] ?? null;
        if ($fileId !== null) {
            abort_unless($product->files()->whereKey($fileId)->exists(), 422, __('inspection.file_not_attached'));
        } else {
            $fileId = $this->productPlanFiles($product)->first()['id'] ?? null;
        }

        $source = isset($data['from_plan_id']) ? InspectionPlan::with('points')->find($data['from_plan_id']) : null;
        abort_if($source && $source->products_id !== $product->id, 422);

        $plan = DB::transaction(function () use ($product, $revision, $fileId, $source) {
            $plan = InspectionPlan::create([
                'products_id' => $product->id,
                'revision'    => $revision,
                'file_id'     => $source && $source->file_id === $fileId ? $source->file_id : $fileId,
                'page_count'  => $source && $source->file_id === $fileId ? $source->page_count : null,
                'rotation'    => $source && $source->file_id === $fileId ? $source->rotation : 0,
                'status'      => InspectionPlan::STATUS_DRAFT,
                'created_by'  => auth()->id(),
            ]);

            foreach ($source?->points ?? [] as $point) {
                $copy = $point->replicate(['inspection_project_id']);
                $copy->inspection_plan_id = $plan->id;
                $copy->save();
            }

            return $plan;
        });

        return response()->json(['plan' => InspectionPlanPresenter::plan($plan->fresh(['product', 'file']))], 201);
    }

    public function update(Request $request, InspectionPlan $plan): JsonResponse
    {
        $this->ensureEditable($plan);

        $data = $request->validate([
            'file_id'    => ['sometimes', 'nullable', 'integer', 'exists:files,id'],
            'page_count' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:500'],
            'rotation'   => ['sometimes', 'integer', 'in:0,90,180,270'],
        ]);

        if (! empty($data['file_id'])) {
            abort_unless($plan->product->files()->whereKey($data['file_id'])->exists(), 422, __('inspection.file_not_attached'));
        }

        $plan->update($data);

        return response()->json(['plan' => InspectionPlanPresenter::plan($plan->fresh(['product', 'file']))]);
    }

    public function destroy(InspectionPlan $plan, ThumbnailStore $thumbnails): JsonResponse
    {
        $this->ensureEditable($plan);

        $thumbnails->delete($plan->thumbnail_path);
        $plan->delete();

        return response()->json(['deleted' => true]);
    }

    /**
     * Valide un brouillon. Le plan validé précédent de la même révision devient
     * obsolète ; ceux des autres révisions restent en vigueur (des commandes à
     * l'ancien indice peuvent encore être en cours).
     */
    public function approve(InspectionPlan $plan): JsonResponse
    {
        $this->ensureEditable($plan);
        abort_if($plan->points()->doesntExist(), 422, __('inspection.approve_needs_points'));
        abort_if($plan->file_id === null, 422, __('inspection.approve_needs_file'));

        DB::transaction(function () use ($plan) {
            InspectionPlan::where('products_id', $plan->products_id)
                ->where('status', InspectionPlan::STATUS_APPROVED)
                ->where(fn ($q) => $plan->revision === null ? $q->whereNull('revision') : $q->where('revision', $plan->revision))
                ->update(['status' => InspectionPlan::STATUS_OBSOLETE]);

            $plan->update([
                'status'      => InspectionPlan::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return response()->json(['plan' => InspectionPlanPresenter::plan($plan->fresh(['product', 'file', 'approver']))]);
    }

    public function thumbnail(InspectionPlan $plan, ThumbnailStore $thumbnails): StreamedResponse
    {
        return $thumbnails->response($plan->thumbnail_path);
    }

    /**
     * Miniature du plan avec ses bulles, rendue par le bulleur.
     */
    public function storeThumbnail(Request $request, InspectionPlan $plan, ThumbnailStore $thumbnails): JsonResponse
    {
        $request->validate(['thumbnail' => ['required', 'file']]);

        $plan->forceFill([
            'thumbnail_path' => $thumbnails->store($request->file('thumbnail'), 'inspection-plans', $plan->id, $plan->thumbnail_path),
        ])->save();

        return response()->json(['thumbnail_url' => InspectionPlanPresenter::plan($plan)['thumbnail_url']]);
    }

    public static function ensureEditable(InspectionPlan $plan): void
    {
        abort_unless($plan->isEditable(), 409, __('inspection.plan_locked'));
    }

    private function plans(?string $search, $productId)
    {
        return InspectionPlan::with(['product', 'file', 'creator', 'approver'])
            ->withCount(['points', 'points as critical_count' => fn ($q) => $q->where('is_critical', true)])
            ->when($productId, fn ($q) => $q->where('products_id', $productId))
            ->when($search, fn ($q) => $q->whereHas('product', fn ($p) => $p
                ->where('code', 'like', "%{$search}%")
                ->orWhere('label', 'like', "%{$search}%")));
    }

    private function counts(ControlPlanNeeds $needs): array
    {
        $byStatus = InspectionPlan::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $dossiers = InspectionProject::whereNotNull('inspection_plan_id')
            ->selectRaw('closed_at IS NULL as open, COUNT(*) as total')
            ->groupByRaw('closed_at IS NULL')
            ->pluck('total', 'open');

        return [
            'needs'       => $needs->count(),
            'draft'       => (int) ($byStatus[InspectionPlan::STATUS_DRAFT] ?? 0),
            'approved'    => (int) ($byStatus[InspectionPlan::STATUS_APPROVED] ?? 0),
            'in_progress' => (int) ($dossiers[1] ?? 0),
            'closed'      => (int) ($dossiers[0] ?? 0),
        ];
    }

    /**
     * Dossiers de contrôle tenus depuis l'écran tâche (lot 2) : ceux de l'ancien
     * écran d'inspection, sans plan, n'y figurent pas.
     */
    private function dossiers(?string $search, bool $closed)
    {
        return InspectionProject::with(['Plan.file', 'Plan.product', 'Company', 'Order'])
            ->whereNotNull('inspection_plan_id')
            ->when($closed, fn ($q) => $q->whereNotNull('closed_at'), fn ($q) => $q->whereNull('closed_at'))
            ->withCount([
                'Measures as measures_count',
                'Measures as nok_count' => fn ($q) => $q->where('inspection_measures.result', 'nok'),
            ])
            ->addSelect(['first_task_id' => \App\Models\Planning\Task::selectRaw('MIN(id)')
                ->whereColumn('tasks.order_lines_id', 'inspection_projects.order_lines_id')])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('inspection_projects.code', 'like', "%{$search}%")
                ->orWhereHas('Plan.product', fn ($p) => $p->where('code', 'like', "%{$search}%")->orWhere('label', 'like', "%{$search}%"))
                ->orWhereHas('Company', fn ($c) => $c->where('label', 'like', "%{$search}%"))));
    }

    private function dossierRow(InspectionProject $dossier): array
    {
        $plan = $dossier->Plan;

        return [
            'id'            => $dossier->id,
            'code'          => $dossier->code,
            'is_fai'        => $dossier->is_fai,
            'product_id'    => $plan?->products_id,
            'product_code'  => $plan?->product?->code,
            'product_label' => $plan?->product?->label,
            'revision'      => $dossier->revision,
            'company'       => $dossier->Company?->label,
            'order_code'    => $dossier->Order?->code,
            'measures'      => (int) $dossier->measures_count,
            'nok'           => (int) $dossier->nok_count,
            'file'          => $plan?->file ? InspectionPlanPresenter::fileSummary($plan->file) : null,
            'thumbnail_url' => $plan ? InspectionPlanPresenter::plan($plan)['thumbnail_url'] : null,
            'created_at'    => $dossier->created_at?->toIso8601String(),
            'closed_at'     => $dossier->closed_at?->toIso8601String(),
            'is_free'       => $dossier->order_lines_id === null,
            // Contrôle d'une tâche : son écran ; contrôle libre : sa page.
            'open_url'      => $dossier->first_task_id
                ? route('production.task.statu.id', ['id' => $dossier->first_task_id])
                : route('quality.inspections.show', ['dossier' => $dossier->id]),
            'report_url'    => route('quality.inspections.report', ['dossier' => $dossier->id]),
        ];
    }

    /**
     * Fichiers de l'article qu'on peut bullé : PDF et images, plan principal en tête.
     */
    private function productPlanFiles(?Products $product)
    {
        if ($product === null) {
            return collect();
        }

        return $product->files()
            ->whereIn('kind', ['doc', 'image'])
            ->orderByRaw("CASE WHEN fileables.role = ? THEN 0 ELSE 1 END", [FileRole::PLAN])
            ->orderByDesc('fileables.is_primary')
            ->orderByDesc('files.id')
            ->get()
            ->map(fn ($file) => InspectionPlanPresenter::fileSummary($file) + ['role' => $file->pivot->role])
            ->values();
    }

    public static function endpoints(): array
    {
        return [
            'productPlans'   => route('inspection.plans.product', ['product' => '__ID__']),
            'productSearch'  => route('products.json.search'),
            'dossierStore'   => route('inspection.dossiers.store'),
            'list'           => route('inspection.plans.index'),
            'store'          => route('inspection.plans.store'),
            'show'           => route('inspection.plans.show', ['plan' => '__ID__']),
            'update'         => route('inspection.plans.update', ['plan' => '__ID__']),
            'destroy'        => route('inspection.plans.destroy', ['plan' => '__ID__']),
            'approve'        => route('inspection.plans.approve', ['plan' => '__ID__']),
            'thumbnail'      => route('inspection.plans.thumbnail.store', ['plan' => '__ID__']),
            'pointStore'     => route('inspection.plans.points.store', ['plan' => '__ID__']),
            'pointUpdate'    => route('inspection.plans.points.update', ['point' => '__ID__']),
            'pointDestroy'   => route('inspection.plans.points.destroy', ['point' => '__ID__']),
            'renumber'       => route('inspection.plans.points.renumber', ['plan' => '__ID__']),
            'readZone'       => route('inspection.plans.read-zone', ['plan' => '__ID__']),
            'bubbler'        => route('inspection.plans.bubbler', ['plan' => '__ID__']),
            'index'          => route('quality.plans'),
            'product'        => route('products.show', ['id' => '__ID__']),
            'productDocuments' => route('products.show', ['id' => '__ID__']) . '#Documents',
        ];
    }
}
