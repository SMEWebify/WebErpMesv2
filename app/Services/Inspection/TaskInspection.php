<?php

namespace App\Services\Inspection;

use App\Models\Inspection\InspectionControlPoint;
use App\Models\Inspection\InspectionMeasure;
use App\Models\Inspection\InspectionMeasureSession;
use App\Models\Inspection\InspectionPlan;
use App\Models\Inspection\InspectionProject;
use App\Models\Planning\Task;
use App\Models\Products\Products;
use App\Models\Quality\QualityControlDevice;
use App\Models\Quality\QualityNonConformity;
use App\Services\NotificationService;
use App\Notifications\NonConformityNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dossiers de contrôle tenus sur un plan de contrôle validé.
 *
 * Deux entrées :
 * - l'écran tâche : une ligne de commande dont l'article a un plan validé à
 *   l'indice commandé reçoit un dossier (un seul, partagé par ses tâches) ;
 * - le contrôle libre : un article choisi à la main (réception, audit, pièce
 *   prélevée en stock), sans commande ni tâche.
 *
 * Le premier dossier d'un article à un indice donné est un dossier premier
 * article (FAI) ; un contrôle libre l'est seulement si on le demande. Chaque
 * tâche mesure dans sa propre session (le contrôle libre dans une session sans
 * tâche) pour savoir qui a mesuré quoi, à quelle opération.
 *
 * Rien ici ne bloque la production : le service dit ce qui reste à mesurer,
 * l'écran avertit, l'opérateur décide.
 */
class TaskInspection
{
    /** Au-delà, une colonne par pièce n'est plus lisible : on plafonne la grille. */
    public const MAX_PARTS = 50;

    // ------------------------------------------------------------------
    // Plan et dossier d'une tâche
    // ------------------------------------------------------------------

    public function planFor(Task $task): ?InspectionPlan
    {
        $line = $task->OrderLines;
        if (! $line || ! $line->product_id) {
            return null;
        }

        $product = $line->Product;
        $revision = filled($line->ind) ? $line->ind : (filled($product?->ind) ? $product->ind : null);

        return $this->approvedPlan((int) $line->product_id, $revision);
    }

    public function approvedPlan(int $productId, ?string $revision): ?InspectionPlan
    {
        return InspectionPlan::where('products_id', $productId)
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
            // La quantité de la ligne a pu changer depuis l'ouverture du dossier.
            if ($existing && $existing->closed_at === null && (int) $existing->quantity_planned !== $this->partsOfLine($line)) {
                $existing->update(['quantity_planned' => $this->partsOfLine($line)]);
            }

            return $existing;
        }

        return DB::transaction(function () use ($line, $plan) {
            $order = $line->order;

            return InspectionProject::create([
                'code'               => 'CTRL-' . ($order?->code ?? $line->orders_id) . '-' . $line->id . '-' . $plan->id,
                'title'              => trim(($plan->product?->code ?? '') . ' — ' . ($order?->code ?? '')),
                'companies_id'       => $order?->companies_id,
                'orders_id'          => $line->orders_id,
                'order_lines_id'     => $line->id,
                'inspection_plan_id' => $plan->id,
                'products_id'        => $plan->products_id,
                'revision'           => $plan->revision,
                'is_fai'             => $this->isFirstDossier($plan),
                'status'             => 'active',
                'quantity_planned'   => $this->partsOfLine($line),
                'serial_tracking'    => false,
                'created_by'         => Auth::id() ?? 0,
            ]);
        });
    }

    /**
     * Contrôle libre : un article choisi à la main, sans commande ni tâche.
     * L'article doit avoir un plan validé à l'indice demandé (le sien par défaut).
     */
    public function createFree(Products $product, int $qty, ?string $revision, ?string $title, ?int $companyId, bool $isFai): InspectionProject
    {
        $revision ??= filled($product->ind) ? $product->ind : null;
        $plan = $this->approvedPlan($product->id, $revision);
        abort_if(! $plan, 422, __('inspection.no_plan_for_task'));

        return DB::transaction(function () use ($product, $plan, $qty, $title, $companyId, $isFai) {
            $dossier = InspectionProject::create([
                'code'               => 'CTRL-LIBRE-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -4)),
                'title'              => $title ?: trim($product->code . ' — ' . __('inspection.free_control')),
                'companies_id'       => $companyId,
                'inspection_plan_id' => $plan->id,
                'products_id'        => $product->id,
                'revision'           => $plan->revision,
                'is_fai'             => $isFai,
                'status'             => 'active',
                'quantity_planned'   => max(1, min($qty, self::MAX_PARTS)),
                'serial_tracking'    => false,
                'created_by'         => Auth::id() ?? 0,
            ]);
            // Code définitif lisible, une fois l'id connu.
            $dossier->update(['code' => 'CTRL-LIBRE-' . $dossier->id]);

            return $dossier;
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

    // ------------------------------------------------------------------
    // Lecture
    // ------------------------------------------------------------------

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

        return ($dossier ? $this->summaryOf($dossier) : [
            'dossier_id'  => null,
            'is_fai'      => $this->isFirstDossier($plan),
            'closed'      => false,
            'required'    => null,
            'done'        => 0,
            'nok'         => 0,
            'fai_pending' => true,
        ]) + [
            'plan_id' => $plan->id,
            // Textes de l'écran tâche (ses traductions viennent d'ailleurs).
            'labels'  => [
                'tab'         => __('inspection.control_tab'),
                'fai_warning' => __('inspection.fai_pending_warning'),
            ],
        ];
    }

    public function summaryOf(InspectionProject $dossier): array
    {
        $plan = $dossier->Plan()->with('points')->first();

        return [
            'dossier_id' => $dossier->id,
            'is_fai'     => $dossier->is_fai,
            'closed'     => $dossier->closed_at !== null,
        ] + $this->progress($dossier, $plan, $this->partsOf($dossier));
    }

    /**
     * Tout ce que l'onglet Contrôle affiche : plan bullé, cotes, pièces à mesurer,
     * dernières mesures, appareils. `$task` : cotes de son opération mises en avant.
     */
    public function sheetOf(InspectionProject $dossier, ?Task $task = null): array
    {
        $plan = $dossier->Plan()->with(['points', 'file', 'product'])->first();
        $qty = $this->partsOf($dossier);
        $latest = $this->latestMeasures($dossier);

        $points = $plan->points->map(function (InspectionControlPoint $point) use ($qty, $dossier, $latest, $task) {
            $data = InspectionPlanPresenter::point($point);
            $data['parts'] = $this->partsFor($point, $qty, $dossier->is_fai);
            $data['has_tolerance'] = $point->hasTolerance();
            $data['this_operation'] = $task === null
                || $point->methods_services_id === null
                || (int) $point->methods_services_id === (int) $task->methods_services_id;
            $data['measures'] = $latest->get($point->id, collect())
                ->mapWithKeys(fn ($m) => [$m->part_index => $this->measureRow($m, $m->measured_by_name)]);

            return $data;
        })->values();

        return [
            'dossier' => [
                'id'         => $dossier->id,
                'code'       => $dossier->code,
                'title'      => $dossier->title,
                'is_fai'     => $dossier->is_fai,
                'is_free'    => $dossier->order_lines_id === null,
                'closed'     => $dossier->closed_at !== null,
                'closed_at'  => $dossier->closed_at?->toIso8601String(),
                'parts'      => $qty,
                'capped'     => $this->rawQtyOf($dossier) > self::MAX_PARTS,
                'report_url' => route('quality.inspections.report', ['dossier' => $dossier->id]),
            ] + $this->progress($dossier, $plan, $qty, $latest),
            'plan'    => InspectionPlanPresenter::plan($plan),
            'points'  => $points,
            'devices' => $this->devices(),
        ];
    }

    /**
     * Dernière mesure de chaque (cote, pièce), toutes sessions confondues.
     *
     * @return Collection<int, Collection<int, InspectionMeasure>> par id de cote
     */
    public function latestMeasures(InspectionProject $dossier): Collection
    {
        return InspectionMeasure::query()
            ->join('inspection_measure_sessions as s', 's.id', '=', 'inspection_measures.inspection_measure_session_id')
            ->leftJoin('users as u', 'u.id', '=', 'inspection_measures.measured_by')
            ->leftJoin('quality_control_devices as d', 'd.id', '=', 'inspection_measures.quality_control_device_id')
            ->leftJoin('quality_non_conformities as nc', 'nc.id', '=', 'inspection_measures.quality_non_conformity_id')
            ->where('s.inspection_project_id', $dossier->id)
            ->whereNotNull('inspection_measures.part_index')
            ->orderBy('inspection_measures.measured_at')
            ->orderBy('inspection_measures.id')
            ->get(['inspection_measures.*', 'u.name as measured_by_name', 'd.code as device_code', 'd.label as device_label', 'nc.code as nc_code'])
            ->groupBy('inspection_control_point_id')
            ->map(fn (Collection $rows) => $rows->keyBy('part_index'));
    }

    // ------------------------------------------------------------------
    // Écriture
    // ------------------------------------------------------------------

    /**
     * Enregistre (ou corrige) la mesure d'une pièce, dans la session de la tâche
     * (ou la session libre du dossier).
     */
    public function record(InspectionProject $dossier, ?Task $task, InspectionControlPoint $point, int $part, ?float $value, ?string $result, ?int $deviceId, ?string $comment): InspectionMeasure
    {
        abort_if($point->inspection_plan_id !== $dossier->inspection_plan_id, 404);
        abort_if($dossier->closed_at !== null, 409, __('inspection.dossier_closed'));

        return DB::transaction(function () use ($task, $dossier, $point, $part, $value, $result, $deviceId, $comment) {
            $session = InspectionMeasureSession::firstOrCreate(
                ['inspection_project_id' => $dossier->id, 'tasks_id' => $task?->id, 'status' => 'open'],
                [
                    'session_code' => ($task ? 'T' . $task->id : 'L' . $dossier->id) . '-' . now()->format('YmdHis'),
                    'type'         => $task ? 'task' : 'free',
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

    public function close(InspectionProject $dossier): InspectionProject
    {
        $dossier->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => Auth::id()]);
        InspectionMeasureSession::where('inspection_project_id', $dossier->id)
            ->where('status', 'open')
            ->update(['status' => 'closed', 'ended_at' => now()]);

        return $dossier;
    }

    /**
     * NC du module qualité pré-remplie depuis une mesure hors tolérance : cote,
     * valeur, pièce, appareil, opérateur, commande et tâche. Une mesure porte au
     * plus une NC : la redemander rend celle qui existe.
     */
    public function declareNonConformity(InspectionMeasure $measure): QualityNonConformity
    {
        if ($measure->quality_non_conformity_id) {
            return QualityNonConformity::findOrFail($measure->quality_non_conformity_id);
        }

        $measure->loadMissing(['ControlPoint', 'Session.Project', 'Device', 'MeasuredBy']);
        $point = $measure->ControlPoint;
        $dossier = $measure->Session->Project;
        $task = $measure->Session->tasks_id ? Task::find($measure->Session->tasks_id) : null;

        $requirement = $point->requirementText();
        $label = mb_substr(__('inspection.nc_label', [
            'number' => $point->number,
            'requirement' => $requirement,
            'value' => $measure->measured_value ?? __('inspection.nc_judged_nok'),
            'part' => $measure->part_index,
        ]), 0, 255);

        $details = implode("\n", array_filter([
            __('inspection.nc_detail_dossier', ['code' => $dossier->code]),
            __('inspection.nc_detail_requirement', ['requirement' => $requirement]),
            $measure->measured_value !== null ? __('inspection.nc_detail_value', ['value' => $measure->measured_value, 'deviation' => $measure->deviation]) : null,
            __('inspection.nc_detail_part', ['part' => $measure->part_index]),
            $measure->Device ? __('inspection.nc_detail_device', ['device' => trim($measure->Device->code . ' ' . $measure->Device->label)]) : null,
            __('inspection.nc_detail_operator', ['name' => $measure->MeasuredBy?->name ?? '—', 'at' => $measure->measured_at?->format('d/m/Y H:i')]),
            $measure->comment,
        ]));

        return DB::transaction(function () use ($measure, $dossier, $task, $label, $details) {
            $nc = QualityNonConformity::create([
                'code'            => 'NC-CTRL-' . $measure->id,
                'label'           => $label,
                'statu'           => 1,
                'type'            => 1,
                'user_id'         => Auth::id(),
                'service_id'      => $task?->methods_services_id,
                'failure_comment' => $details,
                'companie_id'     => $dossier->companies_id,
                'order_lines_id'  => $dossier->order_lines_id,
                'task_id'         => $task?->id,
                'ind'             => $dossier->revision,
                'qty'             => 1,
            ]);

            $measure->update(['quality_non_conformity_id' => $nc->id]);

            app(NotificationService::class)->sendNotification(NonConformityNotification::class, $nc, 'non_conformity_notification');

            return $nc;
        });
    }

    public function measureRow(InspectionMeasure $m, ?string $byName = null): array
    {
        return [
            'id'     => $m->id,
            'value'  => $m->measured_value,
            'result' => $m->result,
            'by'     => $byName,
            'at'     => $m->measured_at?->toIso8601String(),
            'nc'     => $m->quality_non_conformity_id ? ($m->nc_code ?? true) : null,
            'nc_url' => $m->quality_non_conformity_id && $m->nc_code
                ? route('quality.nonConformitie', ['search' => $m->nc_code])
                : null,
        ];
    }

    // ------------------------------------------------------------------
    // Interne
    // ------------------------------------------------------------------

    private function isFirstDossier(InspectionPlan $plan): bool
    {
        return ! InspectionProject::where('products_id', $plan->products_id)
            ->where(fn ($q) => $plan->revision === null ? $q->whereNull('revision') : $q->where('revision', $plan->revision))
            ->exists();
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

    /** Quantité réelle avant plafond : celle de la ligne de commande, sinon celle saisie. */
    private function rawQtyOf(InspectionProject $dossier): int
    {
        $qty = $dossier->order_lines_id
            ? \App\Models\Workflow\OrderLines::whereKey($dossier->order_lines_id)->value('qty')
            : null;

        return max(1, (int) ceil((float) ($qty ?? $dossier->quantity_planned ?? 1)));
    }

    private function partsOf(InspectionProject $dossier): int
    {
        return min(self::MAX_PARTS, max(1, (int) ($dossier->quantity_planned ?: 1)));
    }

    private function partsOfLine($line): int
    {
        return min(self::MAX_PARTS, max(1, (int) ceil((float) ($line?->qty ?? 1))));
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
