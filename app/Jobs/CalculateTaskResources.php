<?php

namespace App\Jobs;

use App\Models\Planning\Task;
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

        $processed = 0;

        Task::with('service.Ressources')
            ->whereNotNull('order_lines_id')
            ->whereDoesntHave('resources')
            ->orderBy('id')
            ->chunkById(50, function ($tasks) use ($totalTasks, &$processed) {
                foreach ($tasks as $task) {
                    $processed++;
                    $this->assignResource($task);
                    $this->updateProgress($processed, $totalTasks);
                }
            });

        $this->markFinished();
    }

    private function assignResource(Task $task): void
    {
        $service = $task->service;
        $taskDate = $task->start_date ? Carbon::parse($task->start_date) : Carbon::today();

        $resource = $service?->Ressources
            ->first(fn ($res) => $res->remainingCapacity($taskDate) >= $task->TotalTime());

        if ($resource) {
            $task->resources()->attach($resource->id, [
                'autoselected_ressource' => 0,
                'userforced_ressource' => 0,
            ]);

            $this->pushMessage($resource->label . ' affected to task #' . $task->id . ' for ' . ($task->service['label'] ?? 'N/A') . ' service');
            return;
        }

        $this->pushMessage('No resource available for task #' . $task->id . ' for ' . ($task->service['label'] ?? 'N/A') . ' service');
    }

    private function initializeProgress(): void
    {
        Cache::put(self::CACHE_KEY, [
            'status' => 'running',
            'progress' => 0,
            'count' => 0,
            'messages' => [],
        ], now()->addHour());
    }

    private function updateProgress(int $processed, int $total): void
    {
        $state = Cache::get(self::CACHE_KEY, []);

        Cache::put(self::CACHE_KEY, [
            'status' => 'running',
            'progress' => round(($processed / $total) * 100, 2),
            'count' => $processed,
            'messages' => $state['messages'] ?? [],
        ], now()->addHour());
    }

    private function pushMessage(string $message): void
    {
        $state = Cache::get(self::CACHE_KEY, []);
        $messages = $state['messages'] ?? [];
        $messages[] = $message;

        Cache::put(self::CACHE_KEY, [
            'status' => 'running',
            'progress' => $state['progress'] ?? 0,
            'count' => $state['count'] ?? 0,
            'messages' => array_slice($messages, -20),
        ], now()->addHour());
    }

    private function markFinished(): void
    {
        $state = Cache::get(self::CACHE_KEY, []);

        Cache::put(self::CACHE_KEY, [
            'status' => 'finished',
            'progress' => $state['progress'] ?? 100,
            'count' => $state['count'] ?? 0,
            'messages' => $state['messages'] ?? [],
        ], now()->addHour());
    }
}
