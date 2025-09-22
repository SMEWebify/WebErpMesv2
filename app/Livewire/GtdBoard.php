<?php

namespace App\Livewire;

use App\Events\TaskChangeStatu;
use App\Models\Planning\Status;
use App\Models\Planning\Task;
use App\Models\Planning\TaskActivities;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class GtdBoard extends Component
{
    public array $columns = [];
    public Collection $users;
    public array $commentBodies = [];
    public array $primaryAssignments = [];
    public array $secondaryAssignments = [];
    public array $priorityLevels = [];

    protected TaskService $taskService;

    protected array $statusMap = [];

    protected $listeners = [
        'refreshGtdBoard' => 'loadBoard',
    ];

    public function mount(TaskService $taskService): void
    {
        $this->taskService = $taskService;
        $this->users = User::select('id', 'name')->orderBy('name')->get();

        $this->resolveStatuses();
        $this->loadBoard();
    }

    public function render()
    {
        return view('livewire.gtd-board');
    }

    public function saveAssignment(int $taskId): void
    {
        $task = Task::find($taskId);

        if (! $task) {
            return;
        }

        $task->user_id = $this->normalizeUserId($this->primaryAssignments[$taskId] ?? null);
        $task->secondary_user_id = $this->normalizeUserId($this->secondaryAssignments[$taskId] ?? null);
        $task->save();

        $this->loadBoard();

        session()->flash('success', __('Assignment updated successfully.'));
    }

    public function updatePriority(int $taskId): void
    {
        $task = Task::find($taskId);

        if (! $task) {
            return;
        }

        $priority = (int) ($this->priorityLevels[$taskId] ?? Task::PRIORITY_MEDIUM);
        $validPriorities = array_keys(Task::priorityLabels());

        if (! in_array($priority, $validPriorities, true)) {
            $priority = Task::PRIORITY_MEDIUM;
        }

        $task->priority = $priority;
        $task->save();

        $this->loadBoard();

        session()->flash('success', __('Priority updated successfully.'));
    }

    public function moveTask(int $taskId, int $statusId): void
    {
        $task = Task::find($taskId);

        if (! $task || $task->status_id === $statusId) {
            return;
        }

        $task->status_id = $statusId;
        $task->save();

        if (in_array($statusId, $this->statusMap['in_progress'] ?? [], true)) {
            $this->taskService->recordTaskActivity($taskId, TaskActivities::TYPE_START);
        } elseif (in_array($statusId, $this->statusMap['done'] ?? [], true)) {
            $this->taskService->recordTaskActivity($taskId, TaskActivities::TYPE_FINISH);
        }

        event(new TaskChangeStatu($taskId));

        $this->loadBoard();

        session()->flash('success', __('Task status updated.'));
    }

    public function addComment(int $taskId): void
    {
        $comment = trim($this->commentBodies[$taskId] ?? '');

        if ($comment === '') {
            return;
        }

        $this->taskService->recordTaskActivity($taskId, TaskActivities::TYPE_COMMENT, 0, 0, $comment);
        $this->commentBodies[$taskId] = '';

        $this->loadBoard();

        session()->flash('success', __('Comment added successfully.'));
    }

    public function loadBoard(): void
    {
        $this->columns = [
            'backlog' => [
                'title' => __('Backlog'),
                'default_status' => $this->statusMap['backlog'][0] ?? null,
                'tasks' => $this->getTasksForStatus('backlog'),
            ],
            'in_progress' => [
                'title' => __('In progress'),
                'default_status' => $this->statusMap['in_progress'][0] ?? null,
                'tasks' => $this->getTasksForStatus('in_progress'),
            ],
            'done' => [
                'title' => __('Done'),
                'default_status' => $this->statusMap['done'][0] ?? null,
                'tasks' => $this->getTasksForStatus('done'),
            ],
        ];

        $allTasks = collect();
        foreach ($this->columns as $column) {
            $allTasks = $allTasks->merge($column['tasks']);
        }

        $this->hydrateAssignments($allTasks);
    }

    protected function getTasksForStatus(string $column): Collection
    {
        $statusIds = $this->statusMap[$column] ?? [];
        $query = $this->genericTaskQuery();

        if (empty($statusIds)) {
            if ($column === 'backlog') {
                return $query->whereNull('status_id')->get();
            }

            return collect();
        }

        if ($column === 'backlog') {
            $query->where(function ($innerQuery) use ($statusIds) {
                $innerQuery->whereIn('status_id', $statusIds)
                    ->orWhereNull('status_id');
            });
        } else {
            $query->whereIn('status_id', $statusIds);
        }

        return $query->get();
    }

    protected function genericTaskQuery(): Builder
    {
        return Task::query()
            ->with([
                'user:id,name',
                'secondaryAssignee:id,name',
                'status:id,title',
                'taskActivities' => function ($query) {
                    $query->where('type', TaskActivities::TYPE_COMMENT)
                        ->with('user:id,name')
                        ->latest()
                        ->take(5);
                },
            ])
            ->whereNull('quote_lines_id')
            ->whereNull('order_lines_id')
            ->whereNull('products_id')
            ->whereNull('sub_assembly_id')
            ->orderBy('priority')
            ->orderBy('due_date')
            ->orderBy('label');
    }

    protected function hydrateAssignments(Collection $tasks): void
    {
        foreach ($tasks as $task) {
            $this->primaryAssignments[$task->id] = $task->user_id ? (string) $task->user_id : '';
            $this->secondaryAssignments[$task->id] = $task->secondary_user_id ? (string) $task->secondary_user_id : '';
            $this->priorityLevels[$task->id] = (string) ($task->priority ?? Task::PRIORITY_MEDIUM);
            $this->commentBodies[$task->id] = $this->commentBodies[$task->id] ?? '';
        }
    }

    protected function resolveStatuses(): void
    {
        $statuses = Status::orderBy('order')->get();

        $this->statusMap = [
            'backlog' => $this->matchStatuses($statuses, ['open', 'pending', 'to do']),
            'in_progress' => $this->matchStatuses($statuses, ['in progress', 'started', 'ongoing']),
            'done' => $this->matchStatuses($statuses, ['finished', 'done', 'completed']),
        ];

        if (empty($this->statusMap['backlog']) && $statuses->isNotEmpty()) {
            $this->statusMap['backlog'] = [$statuses->first()->id];
        }

        if (empty($this->statusMap['in_progress']) && $statuses->count() > 1) {
            $this->statusMap['in_progress'] = [$statuses->get(1)->id ?? $statuses->first()->id];
        }

        if (empty($this->statusMap['done']) && $statuses->isNotEmpty()) {
            $this->statusMap['done'] = [$statuses->last()->id];
        }
    }

    protected function matchStatuses(Collection $statuses, array $labels): array
    {
        $expected = collect($labels)->map(fn ($label) => mb_strtolower($label));

        return $statuses
            ->filter(fn ($status) => $expected->contains(mb_strtolower($status->title)))
            ->pluck('id')
            ->all();
    }

    protected function normalizeUserId($value): ?int
    {
        $value = is_numeric($value) ? (int) $value : null;

        return $value ?: null;
    }
}
