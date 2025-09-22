<div>
    @include('include.alert-result')
    @php
        $priorityStyles = [
            \App\Models\Planning\Task::PRIORITY_HIGH => 'danger',
            \App\Models\Planning\Task::PRIORITY_MEDIUM => 'warning',
            \App\Models\Planning\Task::PRIORITY_LOW => 'success',
        ];
    @endphp
    <div class="row">
        @foreach ($columns as $columnKey => $columnData)
            <div class="col-xl-4 col-lg-6 mb-4 d-flex" wire:key="gtd-column-{{ $columnKey }}">
                <div class="card w-100 shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span>{{ $columnData['title'] }}</span>
                        <span class="badge badge-light">{{ $columnData['tasks']->count() }}</span>
                    </div>
                    <div class="card-body">
                        @forelse ($columnData['tasks'] as $task)
                            <div class="card border mb-3 shadow-sm" wire:key="gtd-task-{{ $task->id }}">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="card-title mb-1">#{{ $task->id }} - {{ $task->label }}</h5>
                                            <span class="badge badge-pill badge-{{ $priorityStyles[$task->priority] ?? 'secondary' }}">
                                                {{ __('Priority') }} : {{ $task->priority_label }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            @if($task->due_date)
                                                <span class="badge badge-info">{{ $task->due_date->format('d/m/Y') }}</span>
                                                @if($task->due_date->isPast())
                                                    <div><span class="badge badge-danger">{{ __('Overdue') }}</span></div>
                                                @elseif($task->due_date->isToday())
                                                    <div><span class="badge badge-warning">{{ __('Due today') }}</span></div>
                                                @endif
                                            @else
                                                <small class="text-muted">{{ __('No due date') }}</small>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="text-muted mb-2">
                                        {{ __('Status') }} : {{ $task->status->title ?? __('No status') }}
                                    </p>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">{{ __('Primary assignee') }}</label>
                                        <select class="form-control form-control-sm" wire:model="primaryAssignments.{{ $task->id }}" wire:change="saveAssignment({{ $task->id }})">
                                            <option value="">{{ __('Unassigned') }}</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">{{ __('Secondary assignee') }}</label>
                                        <select class="form-control form-control-sm" wire:model="secondaryAssignments.{{ $task->id }}" wire:change="saveAssignment({{ $task->id }})">
                                            <option value="">{{ __('Unassigned') }}</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="mb-1">{{ __('Priority level') }}</label>
                                        <select class="form-control form-control-sm" wire:model="priorityLevels.{{ $task->id }}" wire:change="updatePriority({{ $task->id }})">
                                            @foreach (\App\Models\Planning\Task::priorityLabels() as $priorityKey => $priorityLabel)
                                                <option value="{{ $priorityKey }}">{{ $priorityLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label class="mb-1 d-block">{{ __('Recent comments') }}</label>
                                        @if($task->taskActivities->isNotEmpty())
                                            <ul class="list-unstyled small mb-2">
                                                @foreach ($task->taskActivities as $activity)
                                                    <li class="mb-1">
                                                        <strong>{{ $activity->user->name ?? __('System') }}</strong>
                                                        <span class="text-muted">{{ $activity->created_at->format('d/m/Y H:i') }}</span>
                                                        <div>{{ $activity->comment }}</div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="text-muted small mb-2">{{ __('No comments yet.') }}</p>
                                        @endif
                                        <div class="form-group mb-0">
                                            <label class="mb-1">{{ __('Add a comment') }}</label>
                                            <textarea class="form-control form-control-sm" rows="2" wire:model.defer="commentBodies.{{ $task->id }}"></textarea>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="addComment({{ $task->id }})">
                                                    {{ __('Add comment') }}
                                                </button>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    @foreach ($columns as $targetKey => $targetColumn)
                                                        @if($targetColumn['default_status'] && $targetKey !== $columnKey)
                                                            <button type="button" class="btn btn-outline-secondary" wire:click="moveTask({{ $task->id }}, {{ $targetColumn['default_status'] }})">
                                                                {{ $targetColumn['title'] }}
                                                            </button>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted mb-0">{{ __('No tasks in this column.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
