<?php

namespace App\Services\HumanResources;

use App\Models\Attendance;
use App\Models\Planning\TaskActivities;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Pairs raw events into worked time per employee.
 *
 * Two independent sources feed the same shape: badge punches (attendances,
 * in/out) and production activity (task_activities, start/end per task). The
 * pairing was written inline in the attendance screen; it lives here so the
 * payroll export cannot drift from what the screen displays.
 */
class AttendanceAggregator
{
    /**
     * Worked time from badge punches.
     *
     * @return array<int, array{user: mixed, total_seconds: int, anomalies: int, days: int}>
     */
    public function fromPunches(?int $userId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $punches = Attendance::with('user')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($startDate, fn ($query) => $query->whereDate('punched_at', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('punched_at', '<=', $endDate))
            ->orderBy('user_id')
            ->orderBy('punched_at')
            ->get();

        return $this->pair(
            $punches,
            fn ($punch) => $punch->direction === 'in',
            fn ($punch) => $punch->direction === 'out',
            fn ($punch) => Carbon::parse($punch->punched_at),
            // A badge has a single open session per employee, whatever the task.
            fn ($punch) => 0
        );
    }

    /**
     * Worked time from production activity.
     *
     * @return array<int, array{user: mixed, total_seconds: int, anomalies: int, days: int}>
     */
    public function fromTaskActivities(?int $userId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $activities = TaskActivities::with('user')
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when($startDate, fn ($query) => $query->whereDate('timestamp', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('timestamp', '<=', $endDate))
            ->orderBy('user_id')
            ->orderBy('timestamp')
            ->get();

        return $this->pair(
            $activities,
            fn ($activity) => $activity->type === TaskActivities::TYPE_START,
            fn ($activity) => in_array($activity->type, [TaskActivities::TYPE_END, TaskActivities::TYPE_FINISH], true),
            fn ($activity) => Carbon::parse($activity->timestamp),
            // An operator can hold several tasks open at once.
            fn ($activity) => $activity->task_id
        );
    }

    /**
     * Fold a chronological event stream into worked seconds per employee.
     *
     * An opening event that finds a session already open, a closing event with
     * no opening, or a session still open at the end of the period are all
     * counted as anomalies rather than silently dropped: a payroll export that
     * hides a forgotten punch-out is worse than one that flags it.
     *
     * @param  Collection<int, mixed>  $events
     * @return array<int, array{user: mixed, total_seconds: int, anomalies: int, days: int}>
     */
    private function pair(Collection $events, callable $opens, callable $closes, callable $at, callable $slot): array
    {
        $report = [];
        $dayBuckets = [];
        $open = [];

        foreach ($events as $event) {
            $userId = (int) $event->user_id;

            if (!isset($report[$userId])) {
                $report[$userId] = [
                    'user' => $event->user,
                    'total_seconds' => 0,
                    'anomalies' => 0,
                ];
            }

            $timestamp = $at($event);
            $key = $slot($event);
            $dayBuckets[$userId][$timestamp->toDateString()] = true;

            if ($opens($event)) {
                if (isset($open[$userId][$key])) {
                    $report[$userId]['anomalies']++;
                }

                $open[$userId][$key] = $timestamp;
                continue;
            }

            if (!$closes($event)) {
                continue;
            }

            if (!isset($open[$userId][$key])) {
                $report[$userId]['anomalies']++;
                continue;
            }

            $start = $open[$userId][$key];

            if ($timestamp->greaterThan($start)) {
                // Carbon 3 returns a signed difference: the opening instant has
                // to be the receiver, otherwise the duration comes out negative.
                $report[$userId]['total_seconds'] += (int) $start->diffInSeconds($timestamp);
            } else {
                $report[$userId]['anomalies']++;
            }

            unset($open[$userId][$key]);
        }

        foreach ($open as $userId => $sessions) {
            if (!isset($report[$userId])) {
                continue;
            }

            $report[$userId]['anomalies'] += count($sessions);
        }

        foreach ($report as $userId => $data) {
            $report[$userId]['days'] = isset($dayBuckets[$userId]) ? count($dayBuckets[$userId]) : 0;
        }

        return $report;
    }
}
