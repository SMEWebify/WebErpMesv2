<?php

namespace App\Jobs;

use App\Models\Planning\Task;
use App\Models\Methods\MethodsRessources;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class CalculateTaskResources implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const CACHE_KEY = 'task_calculation_resources_progress';

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->initializeProgress();

        $totalTasks = Task::whereNotNull('order_lines_id')
            ->whereDoesntHave('resources')
            ->count();

        if ($totalTasks === 0) {
            $this->markFinished();
            return;
        }

        // Build in-memory capacity map once — eliminates N+1 in remainingCapacity()
        // Structure: [resource_id => ['_capacity' => float, 'Y-m-d' => usedHours, ...]]
        $capacityMap = $this->buildCapacityMap();

        $processed  = 0;
        $batchMsgs  = [];

        Task::with('service.Ressources')
            ->whereNotNull('order_lines_id')
            ->whereDoesntHave('resources')
            ->orderBy('id')
            ->chunkById(50, function ($tasks) use ($totalTasks, &$processed, &$capacityMap, &$batchMsgs) {
                foreach ($tasks as $task) {
                    $processed++;
                    $batchMsgs[] = $this->assignResource($task, $capacityMap);

                    // Flush cache every 10 tasks instead of every task
                    if ($processed % 10 === 0 || $processed === $totalTasks) {
                        $this->flushProgress($processed, $totalTasks, $batchMsgs);
                        $batchMsgs = [];
                    }
                }
            });

        $this->markFinished();
    }

    /**
     * Load all resources with their existing task assignments into an in-memory map.
     * [resource_id => ['_capacity' => float, 'Y-m-d' => usedHours]]
     */
    private function buildCapacityMap(): array
    {
        $map = [];

        MethodsRessources::with(['tasks' => function ($q) {
            $q->whereNotNull('start_date')
              ->select('tasks.id', 'tasks.start_date', 'tasks.seting_time', 'tasks.unit_time', 'tasks.qty');
        }])->get()->each(function (MethodsRessources $resource) use (&$map) {
            // capacity is stored weekly — convert to daily for per-day assignment checks
            $map[$resource->id] = ['_capacity' => $resource->dailyCapacity()];

            foreach ($resource->tasks as $task) {
                $date = Carbon::parse($task->start_date)->toDateString();
                $map[$resource->id][$date] = ($map[$resource->id][$date] ?? 0) + $task->TotalTime();
            }
        });

        return $map;
    }

    /**
     * Find and attach a resource using the preloaded capacity map.
     * Updates the map after assignment to keep subsequent checks correct.
     */
    private function assignResource(Task $task, array &$capacityMap): string
    {
        $service    = $task->service;
        $taskDate   = $task->start_date
            ? Carbon::parse($task->start_date)->toDateString()
            : Carbon::today()->toDateString();
        $taskHours  = $task->TotalTime();

        $resource = $service?->Ressources->first(function (MethodsRessources $res) use ($taskDate, $taskHours, &$capacityMap) {
            $capacity = $capacityMap[$res->id]['_capacity'] ?? (float) $res->capacity;
            $used     = $capacityMap[$res->id][$taskDate] ?? 0.0;

            return ($capacity - $used) >= $taskHours;
        });

        if ($resource) {
            $task->resources()->attach($resource->id, [
                'autoselected_ressource' => 0,
                'userforced_ressource'   => 0,
            ]);

            // Update in-memory map so subsequent tasks in the same chunk see correct capacity
            $capacityMap[$resource->id][$taskDate] = ($capacityMap[$resource->id][$taskDate] ?? 0.0) + $taskHours;

            return $resource->label . ' affecté(e) à la tâche #' . $task->id . ' (' . ($service?->label ?? 'N/A') . ')';
        }

        return 'Aucune ressource disponible pour la tâche #' . $task->id . ' (' . ($service?->label ?? 'N/A') . ')';
    }

    private function initializeProgress(): void
    {
        Cache::put(self::CACHE_KEY, [
            'status'   => 'running',
            'progress' => 0,
            'count'    => 0,
            'messages' => [],
        ], now()->addHour());
    }

    private function flushProgress(int $processed, int $total, array $newMessages): void
    {
        $state    = Cache::get(self::CACHE_KEY, []);
        $messages = array_slice(array_merge($state['messages'] ?? [], $newMessages), -20);

        Cache::put(self::CACHE_KEY, [
            'status'   => 'running',
            'progress' => round(($processed / $total) * 100, 2),
            'count'    => $processed,
            'messages' => $messages,
        ], now()->addHour());
    }

    private function markFinished(): void
    {
        $state = Cache::get(self::CACHE_KEY, []);

        Cache::put(self::CACHE_KEY, [
            'status'   => 'finished',
            'progress' => $state['progress'] ?? 100,
            'count'    => $state['count'] ?? 0,
            'messages' => $state['messages'] ?? [],
        ], now()->addHour());
    }
}
