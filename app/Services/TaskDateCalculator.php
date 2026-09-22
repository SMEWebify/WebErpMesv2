<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Planning\Task;
use App\Models\Methods\MethodsRessources;
use App\Models\Times\TimesBanckHoliday;
use App\Support\WorkingTime;
use Illuminate\Support\Collection;

class TaskDateCalculator
{
    /** Sens de calcul possibles pour le planificateur. */
    public const DIRECTION_ALAP = 'alap';
    public const DIRECTION_ASAP = 'asap';

    /**
     * Adjust date to previous working day if it falls on weekend or bank holiday.
     */
    public function adjustForWeekendsAndHolidays(Carbon $date): Carbon
    {
        do {
            if ($date->isSaturday()) {
                $date->subDay();
            } elseif ($date->isSunday()) {
                $date->subDays(2);
            }
            if (TimesBanckHoliday::isBankHoliday($date)) {
                $date->subDay();
            }
        } while ($date->isWeekend() || TimesBanckHoliday::isBankHoliday($date));

        return $date;
    }

    /**
     * Miroir avant de adjustForWeekendsAndHolidays : cale une date sur le
     * prochain jour ouvré au lieu du précédent. Utilisé par l'ASAP.
     */
    public function adjustForWeekendsAndHolidaysForward(Carbon $date): Carbon
    {
        do {
            if ($date->isSaturday()) {
                $date->addDays(2);
            } elseif ($date->isSunday()) {
                $date->addDay();
            }
            if (TimesBanckHoliday::isBankHoliday($date)) {
                $date->addDay();
            }
        } while ($date->isWeekend() || TimesBanckHoliday::isBankHoliday($date));

        return $date;
    }

    /**
     * Adjust a date by subtracting the given number of seconds while
     * respecting working hours, weekends and bank holidays.
     */
    public function adjustForWorkingHours(Carbon $date, int $secondsToSubtract): Carbon
    {
        $hours = $secondsToSubtract / 3600;
        return WorkingTime::subtractWorkingHours($date, $hours);
    }

    /**
     * Calculate start and end dates for the given task.
     *
     * @return array{0: Carbon,1: Carbon}
     */
    public function calculateTaskDates(Task $task, Carbon $end): array
    {
        $end = $this->adjustForWeekendsAndHolidays($end);
        $duration = ($task->seting_time ?? 0) + (($task->unit_time ?? 0) * ($task->qty ?? 0));
        $start = (clone $end)->subHours($duration);
        $start = $this->adjustForWeekendsAndHolidays($start);
        return [$start, $end];
    }

    /**
     * Select a resource that still has capacity.
     */
    public function selectResourceForTask(Task $task, Collection|array $resources): ?MethodsRessources
    {
        foreach ($resources as $resource) {
            if ($resource->capacity >= 1) {
                return $resource;
            }
        }
        return null;
    }

    /**
     * Enchaîne rétroactivement les tâches d'une même ligne : la dernière tâche
     * finit à l'ancre (`internal_delay`), la précédente finit là où la suivante
     * commence — avec, en option, un délai inter-opérations glissé entre les
     * deux.
     *
     * Les $tasks doivent être fournies en ordre décroissant de `ordre`
     * (downstream d'abord, upstream ensuite) : c'est la clé de lecture du
     * backscheduling en aval.
     *
     * Le closure $delayHoursBetween reçoit `(fromServiceId, toServiceId)` — où
     * `from` est la tâche courante (upstream, sur le point d'être placée) et
     * `to` la tâche déjà placée juste en aval — et renvoie un nombre d'heures
     * atelier à intercaler entre la fin de la première et le début de la
     * seconde. Passer null (le défaut) revient au comportement historique
     * « transition instantanée ».
     *
     * Correction au passage : l'ancienne boucle déplaçait `$taskEndDate` vers
     * `$startDate` ET accumulait `$elapsedTimeInSeconds`, ce qui appliquait
     * deux fois la durée à partir de la 2e tâche. Ici on garde uniquement le
     * curseur mobile et on repart de 0 pour chaque tâche.
     *
     * @param  iterable<Task>  $tasks
     * @return array<int, array{task: Task, start: Carbon, end: Carbon}>
     */
    public function chainTasks(iterable $tasks, Carbon $anchor, ?callable $delayHoursBetween = null): array
    {
        $cursor = $this->adjustForWeekendsAndHolidays($anchor->copy());
        $result = [];
        $previousServiceId = null;

        foreach ($tasks as $task) {
            if ($previousServiceId !== null && $delayHoursBetween !== null) {
                $delayHours = (float) $delayHoursBetween(
                    $task->methods_services_id ?? null,
                    $previousServiceId
                );

                if ($delayHours > 0) {
                    $cursor = WorkingTime::subtractWorkingHours($cursor, $delayHours);
                }
            }

            $end = $cursor->copy();
            $duration = (float) $task->TotalTime();
            $start = WorkingTime::subtractWorkingHours($cursor, $duration);

            $result[] = ['task' => $task, 'start' => $start, 'end' => $end];

            $cursor = $start;
            $previousServiceId = $task->methods_services_id ?? null;
        }

        return $result;
    }

    /**
     * ASAP — miroir avant de chainTasks. Les tâches doivent être fournies en
     * ordre croissant de `ordre` (upstream d'abord) : la première tâche
     * démarre à l'ancre, chaque suivante démarre là où la précédente termine
     * — décalée du délai inter-opérations si fourni.
     *
     * Le closure $delayHoursBetween est appelé avec `(fromServiceId, toServiceId)`
     * où `from` est la tâche déjà placée juste en amont et `to` la tâche
     * courante — même convention que chainTasks pour que le resolver de délais
     * reste symétrique.
     *
     * @param  iterable<Task>  $tasks
     * @return array<int, array{task: Task, start: Carbon, end: Carbon}>
     */
    public function chainTasksForward(iterable $tasks, Carbon $anchor, ?callable $delayHoursBetween = null): array
    {
        $cursor = $this->adjustForWeekendsAndHolidaysForward($anchor->copy());
        $result = [];
        $previousServiceId = null;

        foreach ($tasks as $task) {
            if ($previousServiceId !== null && $delayHoursBetween !== null) {
                $delayHours = (float) $delayHoursBetween(
                    $previousServiceId,
                    $task->methods_services_id ?? null
                );

                if ($delayHours > 0) {
                    $cursor = WorkingTime::addWorkingHours($cursor, $delayHours);
                }
            }

            $start = $cursor->copy();
            $duration = (float) $task->TotalTime();
            $end = WorkingTime::addWorkingHours($cursor, $duration);

            $result[] = ['task' => $task, 'start' => $start, 'end' => $end];

            $cursor = $end;
            $previousServiceId = $task->methods_services_id ?? null;
        }

        return $result;
    }
}
