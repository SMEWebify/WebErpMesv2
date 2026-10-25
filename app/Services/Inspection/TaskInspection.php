<?php

namespace App\Services\Inspection;

use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionMeasure;
use App\Models\Inspection\InspectionMeasureSession;
use App\Models\Inspection\InspectionPlan;
use App\Models\Inspection\InspectionProject;
use App\Models\Planning\Task;
use App\Models\Quality\QualityControlDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Contrôle d'une fabrication depuis l'écran tâche.
 *
 * Une ligne de commande dont l'article a un plan de contrôle validé à l'indice
 * commandé reçoit un dossier d'inspection (un seul, partagé par toutes ses
 * tâches). Le premier dossier d'un article à un indice donné est un dossier
 * premier article (FAI). Chaque tâche mesure dans sa propre session, pour savoir
 * qui a mesuré quoi et à quelle opération.
 *
 * Rien ici ne bloque la production : le service dit ce qui reste à mesurer,
 * l'écran avertit, l'opérateur décide.
 */
class TaskInspection
{
    /** Au-delà, une colonne par pièce n'est plus lisible : on plafonne la grille. */
    public const MAX_PARTS = 50;

    public function planFor(Task $task): ?InspectionPlan
    {
        $line = $task->OrderLines;
        if (! $line || ! $line->product_id) {
            return null;
        }

        $product = $line->Product;
        $revision = filled($line->ind) ? $line->ind : (filled($product?->ind) ? $product->ind : null);

        return InspectionPlan::where('products_id', $line->product_id)
            ->where('status', InspectionPlan::STATUS_APPROVED)
            ->where(fn ($q) => $revision === null ? $q->whereNull('revision') : $q->where('revision', $revision))
            ->latest('approved_at')
            ->first();
    }

    /**
     * Dossier de la ligne de commande pour ce plan ; créé à la demande quand
     * `$create` (démarrage de la tâche, ouverture de l'onglet Contrôle).
     */
    public function dossierFor(Task $task, bool $create = false): ?InspectionProject
    {
        $plan = $this->planFor($task);
        if (! $plan) {
            return null;
        }

        $line = $task->OrderLines;
        $existing = InspectionProject::where('order_lines_id', $line->id)
            ->where('inspection_plan_id', $plan->id)
            ->first();

        if ($existing || ! $create) {
            return $existing;
        }

        return DB::transaction(function () use ($line, $plan) {
            $order = $line->order;

            // Premier article : aucun dossier antérieur pour cet article à cet indice.
            $isFai = ! InspectionProject::where('products_id', $plan->products_id)
                ->where(fn ($q) => $plan->revision === null ? $q->whereNull('revision') : $q->where('revision', $plan->revision))
                ->lockForUpdate()
                ->exists();

            return InspectionProject::create([
                'code'               => 'CTRL-' . ($order?->code ?? $line->orders_id) . '-' . $line->id . '-' . $plan->id,
                'title'              => trim(($plan->product?->code ?? '') . ' — ' . ($order?->code ?? '')),
                'companies_id'       => $order?->companies_id ?? 0,
                'orders_id'          => $line->orders_id,
                'order_lines_id'     => $line->id,
                'inspection_plan_id' => $plan->id,
                'products_id'        => $plan->products_id,
                'revision'           => $plan->revision,
                'is_fai'             => $isFai,
                'status'             => 'active',
                'quantity_planned'   => $this->partsOf($line),
                'serial_tracking'    => false,
                'created_by'         => Auth::id() ?? 0,
            ]);
        });
    }

    /**
     * Pièces à mesurer pour une cote (n° 1 à N), selon son échantillonnage. Sur un
     * dossier premier article, une cote marquée FAI exige au moins la pièce 1.
     *
     * @return array<int, int>
     */
    public function partsFor(InspectionControlPoint $point, int $qty, bool $isFai): array
    {
        $qty = max(1, min($qty, self::MAX_PARTS));
        $n = max(1, (int) ($point->frequency_value ?? 1));

        $parts = match ($point->frequency_type) {
            'first'   => [1],
            'every_n' => range(1, $qty, $n),
            'sample'  => range(1, min($n, $qty)),
            default   => range(1, $qty),
        };

        if ($isFai && $point->is_fai && ! in_array(1, $parts, true)) {
            array_unshift($parts, 1);
        }

        return $parts;
    }

    /**
     * Résumé pour l'écran tâche, sans rien créer : y a-t-il un contrôle, où en
     * est-il, reste-t-il des cotes premier article à mesurer ?
     */
    public function summary(Task $task): ?array
    {
        $plan = $this->planFor($task);
        if (! $plan) {
            return null;
        }

        $dossier = $this->dossierFor($task);
        $progress = $dossier ? $this->progress($dossier, $plan, $this->partsOf($task->OrderLines)) : null;

        return [
            'plan_id'     => $plan->id,
            'dossier_id'  => $dossier?->id,
            'is_fai'      => $dossier ? $dossier->is_fai : ! InspectionProject::where('products_id', $plan->products_id)
                ->where(fn ($q) => $plan->revision === null ? $q->whereNull('revision') : $q->where('revision', $plan->revision))
                ->exists(),
            'closed'      => $dossier?->closed_at !== null,
            'required'    => $progress['required'] ?? null,
            'done'        => $progress['done'] ?? 0,
            'nok'         => $progress['nok'] ?? 0,
            'fai_pending' => $progress['fai_pending'] ?? true,
            // Textes de l'écran tâche (ses traductions viennent d'ailleurs).
            'labels'      => [
                'tab'         => __('inspection.control_tab'),
                'fai_warning' => __('inspection.fai_pending_warning'),
            ],
        ];
    }

    /**
     * Tout ce que l'onglet Contrôle affiche : plan bullé, cotes, pièces à mesurer,
     * dernières mesures, appareils. Crée le dossier s'il n'existe pas encore.
     */
    public function sheet(Task $task): ?array
    {
        $dossier = $this->dossierFor($task, create: true);
        if (! $dossier) {
            return null;
        }

        $plan = $dossier->Plan()->with(['points', 'file', 'product'])->first();
        $qty = $this->partsOf($task->OrderLines);
        $latest = $this->latestMeasures($dossier);

        $points = $plan->points->map(function (InspectionControlPoint $point) use ($qty, $dossier, $latest, $task) {
            $data = InspectionPlanPresenter::point($point);
            $data['parts'] = $this->partsFor($point, $qty, $dossier->is_fai);
            $data['has_tolerance'] = $point->hasTolerance();
            $data['this_operation'] = $point->methods_services_id === null
                || (int) $point->methods_services_id === (int) $task->methods_services_id;
            $data['measures'] = $latest->get($point->id, collect())
                ->mapWithKeys(fn ($m) => [$m->part_index => [
                    'id'     => $m->id,
                    'value'  => $m->measured_value,
                    'result' => $m->result,
                    'by'     => $m->measured_by_name,
                    'at'     => $m->measured_at?->toIso8601String(),
                ]]);

            return $data;
        })->values();

        $progress = $this->progress($dossier, $plan, $qty, $latest);

        return [
            'dossier' => [
                'id'        => $dossier->id,
                'code'      => $dossier->code,
                'is_fai'    => $dossier->is_fai,
                'closed'    => $dossier->closed_at !== null,
                'closed_at' => $dossier->closed_at?->toIso8601String(),
                'parts'     => $qty,
                'capped'    => $this->rawQtyOf($task->OrderLines) > self::MAX_PARTS,
            ] + $progress,
            'plan'    => InspectionPlanPresenter::plan($plan),
            'points'  => $points,
            'devices' => $this->devices(),
        ];
    }

    /**
     * Enregistre (ou corrige) la mesure d'une pièce, dans la session de la tâche.
     */
    public function record(Task $task, InspectionControlPoint $point, int $part, ?float $value, ?string $result, ?int $deviceId, ?string $comment): InspectionMeasure
    {
        $dossier = $this->dossierFor($task, create: true);
        abort_if(! $dossier || $point->inspection_plan_id !== $dossier->inspection_plan_id, 404);
        abort_if($dossier->closed_at !== null, 409, __('inspection.dossier_closed'));

        return DB::transaction(function () use ($task, $dossier, $point, $part, $value, $result, $deviceId, $comment) {
            $session = InspectionMeasureSession::firstOrCreate(
                ['inspection_project_id' => $dossier->id, 'tasks_id' => $task->id, 'status' => 'open'],
                [
                    'session_code' => 'T' . $task->id . '-' . now()->format('YmdHis'),
                    'type'         => 'task',
                    'started_at'   => now(),
                    'created_by'   => Auth::id() ?? 0,
                ],
            );

            ['result' => $judged, 'deviation' => $deviation] = $point->evaluate($value, $result);

            return InspectionMeasure::updateOrCreate(
                [
                    'inspection_measure_session_id' => $session->id,
                    'inspection_control_point_id'   => $point->id,
                    'part_index'                    => $part,
                ],
                [
                    'measured_value'            => $value,
                    'result'                    => $judged,
                    'deviation'                 => $deviation,
                    'comment'                   => $comment,
                    'measured_by'               => Auth::id() ?? 0,
                    'measured_at'               => now(),
                    'quality_control_device_id' => $deviceId,
                ],
            );
        });
    }

    public function close(Task $task): InspectionProject
    {
        $dossier = $this->dossierFor($task);
        abort_if(! $dossier, 404);

        $dossier->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => Auth::id()]);
        InspectionMeasureSession::where('inspection_project_id', $dossier->id)
            ->where('status', 'open')
            ->update(['status' => 'closed', 'ended_at' => now()]);

        return $dossier;
    }

    /**
     * Dernière mesure de chaque (cote, pièce), toutes tâches confondues.
     *
     * @return Collection<int, Collection<int, InspectionMeasure>> par id de cote
     */
    private function latestMeasures(InspectionProject $dossier): Collection
    {
        return InspectionMeasure::query()
            ->join('inspection_measure_sessions as s', 's.id', '=', 'inspection_measures.inspection_measure_session_id')
            ->leftJoin('users as u', 'u.id', '=', 'inspection_measures.measured_by')
            ->where('s.inspection_project_id', $dossier->id)
            ->whereNotNull('inspection_measures.part_index')
            ->orderBy('inspection_measures.measured_at')
            ->orderBy('inspection_measures.id')
            ->get(['inspection_measures.*', 'u.name as measured_by_name'])
            ->groupBy('inspection_control_point_id')
            ->map(fn (Collection $rows) => $rows->keyBy('part_index'));
    }

    /**
     * @return array{required: int, done: int, nok: int, fai_pending: bool}
     */
    private function progress(InspectionProject $dossier, InspectionPlan $plan, int $qty, ?Collection $latest = null): array
    {
        $latest ??= $this->latestMeasures($dossier);
        $required = 0;
        $done = 0;
        $nok = 0;
        $faiPending = false;

        foreach ($plan->points as $point) {
            $measured = $latest->get($point->id, collect());
            foreach ($this->partsFor($point, $qty, $dossier->is_fai) as $part) {
                $required++;
                $measure = $measured->get($part);
                if ($measure) {
                    $done++;
                    $nok += $measure->result === 'nok' ? 1 : 0;
                } elseif ($dossier->is_fai && $point->is_fai && $part === 1) {
                    $faiPending = true;
                }
            }
        }

        return ['required' => $required, 'done' => $done, 'nok' => $nok, 'fai_pending' => $faiPending];
    }

    private function rawQtyOf($line): int
    {
        return max(1, (int) ceil((float) ($line?->qty ?? 1)));
    }

    private function partsOf($line): int
    {
        return min(self::MAX_PARTS, $this->rawQtyOf($line));
    }

    private function devices(): array
    {
        return QualityControlDevice::orderBy('code')->get()->map(fn ($d) => [
            'id'      => $d->id,
            'label'   => trim($d->code . ' — ' . $d->label),
            'overdue' => $d->calibration_due_at !== null && $d->calibration_due_at->isPast(),
        ])->values()->all();
    }
}
