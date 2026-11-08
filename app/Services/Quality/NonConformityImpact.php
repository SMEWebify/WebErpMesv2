<?php

namespace App\Services\Quality;

use App\Listeners\CheckOrderLineTaskStatus;
use App\Models\Methods\MethodsServices;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\Quality\QualityNonConformity;
use App\Models\User;
use App\Models\Workflow\OrderLineDelayHistory;
use App\Models\Workflow\OrderLines;
use App\Support\OrderLineDelayContext;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Impact d'une non-conformité : coût (interne, externe, rebut), délai, ordre de
 * retouche. Point d'entrée unique, appelé par l'écran NC et par le futur
 * connecteur Siteflow (flux 4, source `siteflow`, sans utilisateur).
 *
 * - **Coût interne réel** = temps pointé sur les tâches de retouche × taux horaire
 *   du poste (même règle que Task::getTotalRealizedCost()), affiché à côté du coût
 *   saisi. Le total retient le coût interne **saisi**, à défaut le réel.
 * - **Retouche** = tâches de la ligne de commande de la NC, `origin` = TASK_ORIGIN,
 *   `quality_non_conformity_id` posé. Prix et coût unitaires à 0 : le prix calculé,
 *   les quantités et les montants de la ligne ne bougent pas. Ce sont des tâches
 *   ordinaires : BPF et visa de phase s'y appliquent sans exception.
 * - **Délai** : un report de la date de livraison est proposé (date + delay_days),
 *   jamais appliqué d'office ; appliqué, il est historisé (origine non_conformity,
 *   motif « NC {code} »).
 * - Une NC annulée (statu 4) ne compte dans aucun indicateur de coût.
 */
class NonConformityImpact
{
    public const PERMISSION = 'quality-rework-order';

    /** tasks.origin d'une tâche de retouche de NC. */
    public const TASK_ORIGIN = '9';

    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_SITEFLOW = 'siteflow';
    public const SOURCE_API = 'api';
    public const SOURCES = [self::SOURCE_MANUAL, self::SOURCE_SITEFLOW, self::SOURCE_API];

    /** Postes proposés pour une retouche : production interne ou sous-traitance. */
    public const REWORK_SERVICE_TYPES = [MethodsServices::TYPE_PRODUCTIVE, MethodsServices::TYPE_SUB_CONTRACTING];

    public const STATU_CANCELED = 4;

    private const FINISHED = 'Finished';

    // ------------------------------------------------------------------
    // Lecture
    // ------------------------------------------------------------------

    /**
     * Coûts, délai, tâches de retouche et report proposé d'une NC.
     */
    public function impact(QualityNonConformity $nc): array
    {
        $tasks  = $this->reworkTasks([$nc->id]);
        $actual = round((float) $tasks->sum('actual_cost'), 2);
        $line   = $nc->order_lines_id ? OrderLines::with('order:id,code')->find($nc->order_lines_id) : null;

        return [
            'costs' => [
                'internal'        => $nc->cost_internal,
                'internal_actual' => $actual,
                'external'        => $nc->cost_external,
                'scrap'           => $nc->cost_scrap,
                'total'           => self::total($nc->cost_internal, $nc->cost_external, $nc->cost_scrap, $actual),
            ],
            'delay_days'     => $nc->delay_days,
            'impact_comment' => $nc->impact_comment,
            'rework'         => $tasks->map(fn ($t) => [
                'id'            => (int) $t->id,
                'label'         => $t->label,
                'service'       => $t->service_label,
                'status'        => $t->status_title,
                'finished'      => $t->finished,
                'planned_hours' => round((float) $t->seting_time, 2),
                'actual_hours'  => $t->actual_hours,
                'hourly_rate'   => (float) $t->hourly_rate,
                'actual_cost'   => $t->actual_cost,
                'released'      => $t->released_at !== null,
            ])->values()->all(),
            'rework_in_progress' => $tasks->contains(fn ($t) => ! $t->finished),
            'order_line' => $line ? [
                'id'                     => $line->id,
                'label'                  => $line->label,
                'order_id'               => $line->orders_id,
                'order_code'             => $line->order?->code,
                'delivery_date'          => self::dateString($line->delivery_date),
                'proposed_delivery_date' => $this->proposedDeliveryDate($nc, $line),
            ] : null,
            'rework_refusal' => $this->reworkRefusal($nc, $line),
        ];
    }

    /**
     * Total d'une NC : coût interne saisi (à défaut, le réel des retouches) + externe + rebut.
     */
    public static function total(?float $internal, ?float $external, ?float $scrap, float $actualInternal = 0.0): float
    {
        return round(($internal ?? $actualInternal) + (float) $external + (float) $scrap, 2);
    }

    /**
     * Date de livraison proposée : date actuelle de la ligne + délai de la NC.
     * Null sans délai positif, sans ligne ou sans date.
     */
    public function proposedDeliveryDate(QualityNonConformity $nc, ?OrderLines $line = null): ?string
    {
        $line ??= $nc->order_lines_id ? OrderLines::find($nc->order_lines_id) : null;
        $date = $line ? self::dateString($line->delivery_date) : null;

        if (! $date || (int) $nc->delay_days <= 0) {
            return null;
        }

        return Carbon::parse($date)->addDays((int) $nc->delay_days)->toDateString();
    }

    /**
     * Tâches de retouche de plusieurs NC, avec heures et coût réels, en deux requêtes.
     *
     * @param int[] $ncIds
     */
    public function reworkTasks(array $ncIds): Collection
    {
        if ($ncIds === []) {
            return collect();
        }

        $tasks = Task::query()
            ->leftJoin('statuses', 'statuses.id', '=', 'tasks.status_id')
            ->leftJoin('methods_services', 'methods_services.id', '=', 'tasks.methods_services_id')
            ->whereIn('tasks.quality_non_conformity_id', $ncIds)
            ->orderBy('tasks.ordre')
            ->orderBy('tasks.id')
            ->toBase()
            ->get(['tasks.id', 'tasks.label', 'tasks.quality_non_conformity_id', 'tasks.seting_time', 'tasks.released_at',
                'statuses.title as status_title', 'methods_services.label as service_label', 'methods_services.hourly_rate']);

        $activities = $this->activitiesByTask($tasks->pluck('id')->all());

        return $tasks->map(fn ($task) => self::withActuals($task, $activities->get($task->id, collect())));
    }

    /**
     * Heures et coût réels d'une tâche déjà chargée (hourly_rate, status_title),
     * à partir de ses pointages triés. Partagé avec la synthèse d'affaire.
     */
    public static function withActuals(object $task, iterable $activities): object
    {
        $seconds = Task::workedSecondsFrom($activities);

        $task->actual_hours = round($seconds / 3600, 2);
        $task->actual_cost  = round($seconds / 3600 * (float) ($task->hourly_rate ?? 0), 2);
        $task->finished     = strcasecmp((string) ($task->status_title ?? ''), self::FINISHED) === 0;

        return $task;
    }

    /**
     * Pourquoi un ordre de retouche ne peut pas être lancé (null = possible).
     */
    public function reworkRefusal(QualityNonConformity $nc, ?OrderLines $line = null): ?string
    {
        if ((int) $nc->statu === self::STATU_CANCELED) {
            return __('nc_impact.errors.canceled');
        }

        $line ??= $nc->order_lines_id ? OrderLines::find($nc->order_lines_id) : null;
        if (! $line) {
            return __('nc_impact.errors.no_order_line');
        }
        if (($line->line_type ?? 'article') !== 'article') {
            return __('nc_impact.errors.not_article');
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Écriture
    // ------------------------------------------------------------------

    /**
     * Coûts, délai et commentaire d'impact. Seuls les champs présents sont
     * modifiés ; une valeur vide efface le champ. Journalisé (activitylog).
     */
    public function updateImpact(QualityNonConformity $nc, array $data): QualityNonConformity
    {
        foreach (['cost_internal', 'cost_external', 'cost_scrap'] as $field) {
            if (array_key_exists($field, $data)) {
                $nc->{$field} = self::nullableNumber($data[$field]);
            }
        }
        if (array_key_exists('delay_days', $data)) {
            $nc->delay_days = $data['delay_days'] === null || $data['delay_days'] === '' ? null : (int) $data['delay_days'];
        }
        if (array_key_exists('impact_comment', $data)) {
            $comment = trim((string) $data['impact_comment']);
            $nc->impact_comment = $comment === '' ? null : $comment;
        }

        $nc->save();

        return $nc;
    }

    /**
     * Ordre de retouche : une tâche par opération sur la ligne de commande de la NC.
     *
     * @param array<int, array{methods_services_id:int, hours?:float|string|null, label?:string|null}> $operations
     * @return Collection<int, Task>
     *
     * @throws NonConformityImpactException
     */
    public function createReworkOrder(QualityNonConformity $nc, array $operations, ?User $user, string $source = self::SOURCE_MANUAL): Collection
    {
        $this->authorize($user, $source);

        $line = $nc->order_lines_id ? OrderLines::find($nc->order_lines_id) : null;
        if ($refusal = $this->reworkRefusal($nc, $line)) {
            throw new NonConformityImpactException($refusal, 422);
        }
        if ($operations === []) {
            throw new NonConformityImpactException(__('nc_impact.errors.no_operation'), 422);
        }

        $services = MethodsServices::query()
            ->whereIn('id', collect($operations)->pluck('methods_services_id')->filter()->all())
            ->whereIn('type', self::REWORK_SERVICE_TYPES)
            ->get()
            ->keyBy('id');

        foreach ($operations as $operation) {
            $hours = $operation['hours'] ?? null;
            $hours = $hours === null || $hours === '' ? 0 : $hours;
            if (! $services->has((int) ($operation['methods_services_id'] ?? 0))) {
                throw new NonConformityImpactException(__('nc_impact.errors.invalid_service'), 422);
            }
            if (! is_numeric($hours) || (float) $hours < 0) {
                throw new NonConformityImpactException(__('nc_impact.errors.invalid_hours'), 422);
            }
        }

        $tasks = DB::transaction(function () use ($nc, $line, $operations, $services) {
            $statusId = Status::orderBy('order')->value('id');
            $ordre    = (int) Task::where('order_lines_id', $line->id)->max('ordre');
            // Pièces à retoucher = quantité de la NC (tasks.qty est entier) ; sans quantité,
            // l'opération porte sur la ligne (planned_qty nul, comme une opération de gamme).
            $qty      = $nc->qty !== null && (int) $nc->qty > 0 ? (int) $nc->qty : null;
            $created  = collect();

            foreach ($operations as $operation) {
                $service = $services->get((int) $operation['methods_services_id']);
                $label   = trim((string) ($operation['label'] ?? ''));
                $ordre  += 10;

                $task = new Task([
                    'code'                => $service->code,
                    'label'               => $label !== '' ? mb_substr($label, 0, 255)
                        : __('nc_impact.rework_label', ['code' => $nc->code, 'service' => $service->label]),
                    'ordre'               => $ordre,
                    'order_lines_id'      => $line->id,
                    'methods_services_id' => $service->id,
                    'type'                => $service->type,
                    // Temps prévu de la retouche porté en réglage : il ne dépend pas de la quantité de la ligne.
                    'seting_time'         => round((float) ($operation['hours'] ?? 0), 2),
                    'unit_time'           => 0,
                    'qty'                 => $qty,
                    'planned_qty'         => $qty,
                    'qty_init'            => $qty,
                    // Non facturable : ni prix ni coût de gamme, la ligne de commande reste à l'identique.
                    'unit_cost'           => 0,
                    'unit_price'          => 0,
                    'status_id'           => $statusId,
                    'to_schedule'         => 1,
                    'origin'              => self::TASK_ORIGIN,
                ]);
                $task->quality_non_conformity_id = $nc->id;
                $task->save();

                $created->push($task);
            }

            return $created;
        });

        CheckOrderLineTaskStatus::syncOrderLine((int) $line->id);

        activity()
            ->performedOn($nc)
            ->causedBy($user)
            ->withProperties(['source' => $source, 'tasks' => $tasks->pluck('id')->all()])
            ->log('nc_rework_ordered');

        return $tasks;
    }

    /**
     * Reporte la date de livraison de la ligne de la NC (par défaut à la date
     * proposée). Historisé par OrderLinesObserver, origine non_conformity.
     *
     * @throws NonConformityImpactException
     */
    public function applyDelay(QualityNonConformity $nc, ?string $date, ?User $user, string $source = self::SOURCE_MANUAL): OrderLines
    {
        $this->authorize($user, $source);

        $line = $nc->order_lines_id ? OrderLines::find($nc->order_lines_id) : null;
        if (! $line) {
            throw new NonConformityImpactException(__('nc_impact.errors.no_order_line'), 422);
        }
        if (($line->line_type ?? 'article') !== 'article') {
            throw new NonConformityImpactException(__('nc_impact.errors.not_article'), 422);
        }

        $date = $date ?: $this->proposedDeliveryDate($nc, $line);
        if (! $date) {
            throw new NonConformityImpactException(__('nc_impact.errors.no_date'), 422);
        }

        OrderLineDelayContext::run(
            OrderLineDelayHistory::ORIGIN_NON_CONFORMITY,
            fn () => $line->update(['delivery_date' => Carbon::parse($date)->toDateString()]),
            'NC ' . $nc->code,
        );

        return $line->fresh();
    }

    // ------------------------------------------------------------------
    // Indicateurs
    // ------------------------------------------------------------------

    /**
     * Coût de non-qualité des NC créées sur les `$months` derniers mois (mois en
     * cours compris), par mois et par fournisseur (société de la NC active comme
     * fournisseur). Trois requêtes.
     */
    public function nonQualityCosts(int $months = 12, ?CarbonInterface $now = null, int $suppliers = 10): array
    {
        $now  = $now ? Carbon::instance($now) : Carbon::now();
        $from = $now->copy()->startOfMonth()->subMonths(max(1, $months) - 1);

        $ncs = QualityNonConformity::query()
            ->leftJoin('companies', 'companies.id', '=', 'quality_non_conformities.companie_id')
            ->where('quality_non_conformities.created_at', '>=', $from)
            ->where('quality_non_conformities.statu', '!=', self::STATU_CANCELED)
            ->toBase()
            ->get(['quality_non_conformities.id', 'quality_non_conformities.created_at',
                'quality_non_conformities.cost_internal', 'quality_non_conformities.cost_external',
                'quality_non_conformities.cost_scrap', 'quality_non_conformities.companie_id',
                'companies.label as company_label', 'companies.statu_supplier']);

        $actual = $this->reworkTasks($ncs->pluck('id')->all())
            ->groupBy('quality_non_conformity_id')
            ->map(fn ($tasks) => (float) $tasks->sum('actual_cost'));

        $ncs->each(function ($nc) use ($actual) {
            $internalActual = round((float) $actual->get($nc->id, 0), 2);
            $nc->internal = $nc->cost_internal !== null ? (float) $nc->cost_internal : $internalActual;
            $nc->external = (float) $nc->cost_external;
            $nc->scrap    = (float) $nc->cost_scrap;
            $nc->total    = round($nc->internal + $nc->external + $nc->scrap, 2);
            $nc->month    = Carbon::parse($nc->created_at)->format('Y-m');
        });

        $byMonth = $ncs->groupBy('month');
        $monthRows = [];
        for ($cursor = $from->copy(); $cursor <= $now; $cursor->addMonth()) {
            $key   = $cursor->format('Y-m');
            $group = $byMonth->get($key, collect());
            $monthRows[] = [
                'month'    => $key,
                'count'    => $group->count(),
                'internal' => round((float) $group->sum('internal'), 2),
                'external' => round((float) $group->sum('external'), 2),
                'scrap'    => round((float) $group->sum('scrap'), 2),
                'total'    => round((float) $group->sum('total'), 2),
            ];
        }

        $supplierRows = $ncs
            ->filter(fn ($nc) => $nc->companie_id && (int) $nc->statu_supplier === 2)
            ->groupBy('companie_id')
            ->map(fn ($group) => [
                'company_id' => (int) $group->first()->companie_id,
                'label'      => $group->first()->company_label,
                'count'      => $group->count(),
                'total'      => round((float) $group->sum('total'), 2),
            ])
            ->sortByDesc('total')
            ->take($suppliers)
            ->values()
            ->all();

        return [
            'from'      => $from->toDateString(),
            'total'     => round((float) $ncs->sum('total'), 2),
            'months'    => $monthRows,
            'suppliers' => $supplierRows,
        ];
    }

    // ------------------------------------------------------------------
    // Outils
    // ------------------------------------------------------------------

    /** Pointages START / END / FINISH des tâches, triés, groupés par tâche. */
    private function activitiesByTask(array $taskIds): Collection
    {
        if ($taskIds === []) {
            return collect();
        }

        return TaskActivities::query()
            ->whereIn('task_id', $taskIds)
            ->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH])
            ->orderBy('timestamp')
            ->orderBy('id')
            ->toBase()
            ->get(['task_id', 'type', 'timestamp', 'user_id', 'methods_ressources_id'])
            ->groupBy('task_id');
    }

    /** Une saisie à l'écran exige la permission ; Siteflow et l'API passent sans utilisateur. */
    private function authorize(?User $user, string $source): void
    {
        if (! in_array($source, self::SOURCES, true)) {
            throw new NonConformityImpactException(__('nc_impact.errors.forbidden'), 403);
        }
        if ($source === self::SOURCE_MANUAL && ! $user?->can(self::PERMISSION)) {
            throw new NonConformityImpactException(__('nc_impact.errors.forbidden'), 403);
        }
    }

    private static function nullableNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) str_replace(',', '.', (string) $value), 2);
    }

    private static function dateString($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }
}
