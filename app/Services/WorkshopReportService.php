<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Planning\Task;
use App\Models\Planning\AndonAlerts;
use App\Models\Planning\TaskActivities;
use App\Models\Methods\MethodsRessources;
use Illuminate\Support\Facades\DB;

/**
 * Rapport atelier : reconstruit le réalisé (heures pointées, pièces bonnes,
 * rebuts) à partir des déclarations brutes de task_activities.
 *
 * Les heures ne sont pas lues via Task::getTotalLogTime() : cet accesseur
 * raisonne sur toute la vie de la tâche, alors qu'un rapport atelier doit
 * borner le réalisé à une période. Les sessions sont donc appariées ici
 * (type 1 = départ, types 2/3 = arrêt) et la durée est imputée à la ressource
 * et à l'opérateur figés sur le départ, pas à l'affectation courante.
 */
class WorkshopReportService
{
    public const PERIODS = [
        'today' => "Aujourd'hui",
        '7d'    => '7 derniers jours',
        '30d'   => '30 derniers jours',
    ];

    public function build(string $period = 'today'): array
    {
        $period = array_key_exists($period, self::PERIODS) ? $period : 'today';
        [$from, $to] = $this->range($period);

        $activities = TaskActivities::query()
            ->whereBetween('timestamp', [$from, $to])
            ->orderBy('task_id')
            ->orderBy('timestamp')
            ->orderBy('id')
            ->get(['id', 'task_id', 'methods_ressources_id', 'user_id', 'type', 'timestamp', 'good_qt', 'bad_qt']);

        $sessions = $this->pairSessions($activities);
        $labels   = $this->labels($activities);
        $buckets  = $this->aggregate($activities, $sessions, $labels, $from, $to);

        return [
            'period' => [
                'key'   => $period,
                'label' => self::PERIODS[$period],
                'from'  => $from->format('d/m/Y H:i'),
                'to'    => $to->format('d/m/Y H:i'),
            ],
            'periods'      => collect(self::PERIODS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'kpi'          => $this->kpi($activities, $sessions),
            'per_day'      => $buckets['per_day'],
            'per_resource' => $buckets['per_resource'],
            'per_user'     => $buckets['per_user'],
            'per_service'  => $buckets['per_service'],
            'andon'        => $this->andon($from, $to),
            'in_progress'  => $this->inProgress(),
            'generated_at' => now()->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(string $period): array
    {
        return match ($period) {
            '7d'    => [Carbon::today()->subDays(6), Carbon::now()],
            '30d'   => [Carbon::today()->subDays(29), Carbon::now()],
            default => [Carbon::today(), Carbon::now()],
        };
    }

    /**
     * Appariement départ -> arrêt, tâche par tâche.
     *
     * Un arrêt sans départ dans la période (session ouverte avant le début du
     * rapport) est ignoré plutôt que compté depuis le début de la fenêtre :
     * mieux vaut sous-estimer que d'inventer des heures.
     *
     * @return array<int, array{task_id: int, seconds: int, day: string, resource_id: ?int, user_id: ?int}>
     */
    private function pairSessions($activities): array
    {
        $sessions = [];
        $pending  = [];

        foreach ($activities as $row) {
            $type = (int) $row->type;

            if ($type === TaskActivities::TYPE_START) {
                $pending[$row->task_id][] = $row;
                continue;
            }

            if (! in_array($type, [TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH], true)) {
                continue;
            }

            $start = empty($pending[$row->task_id]) ? null : array_pop($pending[$row->task_id]);
            if ($start === null) {
                continue;
            }

            $startAt = Carbon::parse($start->timestamp);
            $endAt   = Carbon::parse($row->timestamp);

            $sessions[] = [
                'task_id'     => (int) $row->task_id,
                'seconds'     => max(0, $startAt->diffInSeconds($endAt, false)),
                'day'         => $startAt->format('Y-m-d'),
                'resource_id' => $start->methods_ressources_id ?? $row->methods_ressources_id,
                'user_id'     => $start->user_id ?? $row->user_id,
            ];
        }

        return $sessions;
    }

    /**
     * Libellés des ressources, opérateurs et tâches touchés par la période.
     */
    private function labels($activities): array
    {
        $tasks = Task::with('service:id,label,color')
            ->whereIn('id', $activities->pluck('task_id')->unique()->all())
            ->get(['id', 'label', 'methods_services_id'])
            ->keyBy('id');

        return [
            'resources' => MethodsRessources::pluck('label', 'id')->all(),
            'users'     => User::pluck('name', 'id')->all(),
            'tasks'     => $tasks,
        ];
    }

    private function aggregate($activities, array $sessions, array $labels, Carbon $from, Carbon $to): array
    {
        $perDay = [];
        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $perDay[$day->format('Y-m-d')] = [
                'date'  => $day->format('Y-m-d'),
                'label' => $day->format('d/m'),
                'hours' => 0.0,
                'good'  => 0,
                'bad'   => 0,
            ];
        }

        $perResource = [];
        $perUser     = [];
        $perService  = [];

        $touchResource = function ($id) use (&$perResource, $labels) {
            $key = $id ? (int) $id : 0;
            if (! isset($perResource[$key])) {
                $perResource[$key] = [
                    'label' => $key ? ($labels['resources'][$key] ?? "Ressource #{$key}") : 'Non affecté',
                    'hours' => 0.0, 'good' => 0, 'bad' => 0, 'sessions' => 0,
                ];
            }
            return $key;
        };

        $touchUser = function ($id) use (&$perUser, $labels) {
            $key = $id ? (int) $id : 0;
            if (! isset($perUser[$key])) {
                $perUser[$key] = [
                    'label' => $key ? ($labels['users'][$key] ?? "Utilisateur #{$key}") : 'Inconnu',
                    'hours' => 0.0, 'good' => 0, 'bad' => 0, 'sessions' => 0,
                ];
            }
            return $key;
        };

        $touchService = function ($taskId) use (&$perService, $labels) {
            $service = $labels['tasks'][$taskId]->service ?? null;
            $key     = $service->id ?? 0;
            if (! isset($perService[$key])) {
                $perService[$key] = [
                    'label' => $service->label ?? 'Sans service',
                    'color' => $service->color ?? '#6c757d',
                    'hours' => 0.0, 'good' => 0, 'bad' => 0,
                ];
            }
            return $key;
        };

        foreach ($sessions as $session) {
            $hours = $session['seconds'] / 3600;

            if (isset($perDay[$session['day']])) {
                $perDay[$session['day']]['hours'] += $hours;
            }

            $rKey = $touchResource($session['resource_id']);
            $perResource[$rKey]['hours']    += $hours;
            $perResource[$rKey]['sessions'] += 1;

            $uKey = $touchUser($session['user_id']);
            $perUser[$uKey]['hours']    += $hours;
            $perUser[$uKey]['sessions'] += 1;

            $sKey = $touchService($session['task_id']);
            $perService[$sKey]['hours'] += $hours;
        }

        foreach ($activities as $row) {
            $type = (int) $row->type;
            $good = $type === TaskActivities::TYPE_DECLARE_GOOD ? (int) $row->good_qt : 0;
            $bad  = $type === TaskActivities::TYPE_DECLARE_BAD  ? (int) $row->bad_qt  : 0;
            if ($good === 0 && $bad === 0) {
                continue;
            }

            $day = Carbon::parse($row->timestamp)->format('Y-m-d');
            if (isset($perDay[$day])) {
                $perDay[$day]['good'] += $good;
                $perDay[$day]['bad']  += $bad;
            }

            $rKey = $touchResource($row->methods_ressources_id);
            $perResource[$rKey]['good'] += $good;
            $perResource[$rKey]['bad']  += $bad;

            $uKey = $touchUser($row->user_id);
            $perUser[$uKey]['good'] += $good;
            $perUser[$uKey]['bad']  += $bad;

            $sKey = $touchService($row->task_id);
            $perService[$sKey]['good'] += $good;
            $perService[$sKey]['bad']  += $bad;
        }

        return [
            'per_day'      => array_values($perDay),
            'per_resource' => $this->finalize($perResource),
            'per_user'     => $this->finalize($perUser),
            'per_service'  => $this->finalize($perService),
        ];
    }

    /**
     * Arrondit, calcule le taux de rebut de la ligne et trie par charge décroissante.
     */
    private function finalize(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                $produced          = $row['good'] + $row['bad'];
                $row['hours']      = round($row['hours'], 2);
                $row['scrap_rate'] = $produced > 0 ? round($row['bad'] / $produced * 100, 1) : 0.0;
                return $row;
            })
            ->sortByDesc(fn ($row) => $row['hours'] * 1000 + $row['good'])
            ->values()
            ->all();
    }

    private function kpi($activities, array $sessions): array
    {
        $good     = (int) $activities->where('type', TaskActivities::TYPE_DECLARE_GOOD)->sum('good_qt');
        $bad      = (int) $activities->where('type', TaskActivities::TYPE_DECLARE_BAD)->sum('bad_qt');
        $produced = $good + $bad;
        $seconds  = array_sum(array_column($sessions, 'seconds'));

        return [
            'declared_hours' => round($seconds / 3600, 2),
            'sessions'       => count($sessions),
            'good_qty'       => $good,
            'bad_qty'        => $bad,
            'scrap_rate'     => $produced > 0 ? round($bad / $produced * 100, 1) : 0.0,
            'finished_tasks' => $activities->where('type', TaskActivities::TYPE_FINISH)->pluck('task_id')->unique()->count(),
            'active_users'   => $activities->pluck('user_id')->filter()->unique()->count(),
            'late_tasks'     => Task::whereNotNull('end_date')
                ->whereDate('end_date', '<', Carbon::today())
                ->whereHas('status', fn ($q) => $q->where('title', '!=', 'Finished'))
                ->count(),
        ];
    }

    private function andon(Carbon $from, Carbon $to): array
    {
        $alerts = AndonAlerts::with('resource:id,label')
            ->whereBetween('triggered_at', [$from, $to])
            ->get(['id', 'type', 'status', 'methods_ressources_id', 'triggered_at', 'resolved_at']);

        $avgMinutes = function ($rows) {
            $resolved = $rows->filter(fn ($a) => $a->resolved_at !== null);
            if ($resolved->isEmpty()) {
                return null;
            }
            return round($resolved->avg(fn ($a) => Carbon::parse($a->triggered_at)->diffInMinutes(Carbon::parse($a->resolved_at))), 1);
        };

        return [
            'total'       => $alerts->count(),
            'open'        => AndonAlerts::where('status', '!=', 3)->count(),
            'avg_minutes' => $avgMinutes($alerts),
            'by_type'     => $alerts->groupBy('type')->map(fn ($rows, $type) => [
                'label'       => $type !== '' && $type !== null ? $type : 'Non typé',
                'count'       => $rows->count(),
                'open'        => $rows->where('status', '!=', 3)->count(),
                'avg_minutes' => $avgMinutes($rows),
            ])->sortByDesc('count')->values()->all(),
            'by_resource' => $alerts->groupBy('methods_ressources_id')->map(fn ($rows) => [
                'label' => $rows->first()->resource->label ?? 'Non affecté',
                'count' => $rows->count(),
            ])->sortByDesc('count')->take(6)->values()->all(),
        ];
    }

    /**
     * Sessions encore ouvertes : un départ non refermé, tous historiques confondus.
     * C'est l'information la plus utile sur un écran d'atelier — qui tourne, sur quoi.
     */
    private function inProgress(): array
    {
        $openTaskIds = DB::table('task_activities')
            ->whereIn('type', [TaskActivities::TYPE_START, TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH])
            ->groupBy('task_id')
            ->havingRaw('SUM(CASE WHEN type = ? THEN 1 ELSE -1 END) > 0', [TaskActivities::TYPE_START])
            ->pluck('task_id');

        if ($openTaskIds->isEmpty()) {
            return [];
        }

        $starts = TaskActivities::with(['user:id,name', 'resource:id,label'])
            ->whereIn('task_id', $openTaskIds)
            ->where('type', TaskActivities::TYPE_START)
            ->orderByDesc('timestamp')
            ->get(['id', 'task_id', 'user_id', 'methods_ressources_id', 'timestamp'])
            ->unique('task_id');

        $tasks = Task::with('service:id,label,color')
            ->whereIn('id', $starts->pluck('task_id'))
            ->get(['id', 'label', 'methods_services_id'])
            ->keyBy('id');

        return $starts->map(function ($start) use ($tasks) {
            $task    = $tasks[$start->task_id] ?? null;
            $startAt = Carbon::parse($start->timestamp);

            return [
                'task_id'  => (int) $start->task_id,
                'label'    => $task->label ?? "Tâche #{$start->task_id}",
                'service'  => $task->service->label ?? null,
                'color'    => $task->service->color ?? '#6c757d',
                'user'     => $start->user->name ?? null,
                'resource' => $start->resource->label ?? null,
                'since'    => $startAt->format('d/m H:i'),
                'minutes'  => $startAt->diffInMinutes(now()),
            ];
        })->sortByDesc('minutes')->values()->all();
    }
}
