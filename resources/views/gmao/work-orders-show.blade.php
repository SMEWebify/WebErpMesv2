@extends('adminlte::page')

@section('title', 'Work Order #' . $workOrder->id)

@section('content_header')
    <h1>Work Order #{{ $workOrder->id }}</h1>
@stop

@section('content')
    @php
        $statusLabel = ucfirst(str_replace('_', ' ', $workOrder->status));
        $priorityLabel = ucfirst(str_replace('_', ' ', $workOrder->priority));
        $typeLabel = ucfirst(str_replace('_', ' ', $workOrder->work_type));
        $statusClass = match ($workOrder->status) {
            'scheduled' => 'badge-primary',
            'in_progress' => 'badge-info',
            'completed' => 'badge-success',
            'cancelled' => 'badge-secondary',
            default => 'badge-light',
        };
        $priorityClass = match ($workOrder->priority) {
            'low' => 'badge-success',
            'medium' => 'badge-warning',
            'high' => 'badge-danger',
            default => 'badge-light',
        };
        $typeClass = match ($workOrder->work_type) {
            'preventive' => 'badge-info',
            'corrective' => 'badge-danger',
            default => 'badge-light',
        };
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <x-adminlte-card theme="light" title="General Information">
                <x-slot name="toolsSlot">
                    <span class="badge {{ $statusClass }} mr-2">{{ $statusLabel }}</span>
                    <span class="badge {{ $priorityClass }}">{{ $priorityLabel }}</span>
                </x-slot>
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">Title</p>
                        <p class="font-weight-bold mb-3">{{ $workOrder->title }}</p>

                        <p class="text-muted text-uppercase mb-1">Description</p>
                        <p class="mb-3">{{ $workOrder->description ?? 'Not specified' }}</p>

                        <p class="text-muted text-uppercase mb-1">Machine event</p>
                        <p class="mb-0">{{ $workOrder->machineEvent?->label ?? 'Not specified' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">Type</p>
                        <span class="badge {{ $typeClass }} mb-3">{{ $typeLabel }}</span>

                        <p class="text-muted text-uppercase mb-1">Asset</p>
                        <p class="mb-3">{{ $workOrder->asset?->name ?? 'Not specified' }}</p>

                        <p class="text-muted text-uppercase mb-1">Created by</p>
                        <p class="mb-0">
                            <i class="far fa-user mr-1 text-muted"></i>{{ $workOrder->creator?->name ?? 'N/A' }}
                        </p>
                    </div>
                </div>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="Schedule & Timeline">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-calendar text-primary mr-1"></i> Requested at
                        </p>
                        <p class="mb-0">{{ optional($workOrder->requested_at)->format('Y-m-d') ?? 'Not specified' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-calendar-check text-success mr-1"></i> Scheduled at
                        </p>
                        <p class="mb-0">{{ optional($workOrder->scheduled_at)->format('Y-m-d') ?? 'Not specified' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-clock text-warning mr-1"></i> Started at
                        </p>
                        <p class="mb-0">{{ $workOrder->started_at ? $workOrder->started_at->format('Y-m-d H:i') : 'Not started' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-flag text-purple mr-1"></i> Finished at
                        </p>
                        <p class="mb-0">{{ $workOrder->finished_at ? $workOrder->finished_at->format('Y-m-d H:i') : 'Not finished' }}</p>
                    </div>
                </div>
                <hr class="mt-0">
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">Estimated duration</p>
                        <p class="mb-0">
                            {{ $workOrder->estimated_duration_minutes ? $workOrder->estimated_duration_minutes . ' min' : 'Not specified' }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">Actual duration</p>
                        <p class="mb-0">
                            {{ $workOrder->actual_duration_minutes ? $workOrder->actual_duration_minutes . ' min' : 'Not available' }}
                        </p>
                    </div>
                </div>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="Execution Details">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">Assigned technician</p>
                        <p class="mb-0 text-warning">{{ $workOrder->technician?->name ?? 'Unassigned' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">Machine stopped</p>
                        <span class="badge {{ $workOrder->machine_stopped ? 'badge-danger' : 'badge-success' }}">
                            {{ $workOrder->machine_stopped ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">Actions performed</p>
                        <p class="mb-0 font-italic">{{ $workOrder->actions_performed ?? 'None recorded' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">Parts consumed</p>
                        <p class="mb-0 font-italic">{{ $workOrder->parts_consumed ?? 'None used' }}</p>
                    </div>
                </div>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="Comments & Photos">
                <p class="mb-0 font-italic">{{ $workOrder->comments ?? 'No comments or photos added' }}</p>
            </x-adminlte-card>
        </div>

        <div class="col-lg-4">
            <x-adminlte-card theme="light" title="Actions">
                <a href="{{ route('gmao.work-orders.edit', $workOrder->id) }}" class="btn btn-success btn-block mb-2">
                    <i class="far fa-edit mr-1"></i> Edit Work Order
                </a>
                <a href="{{ route('gmao.work-orders.index') }}" class="btn btn-outline-secondary btn-block mb-2">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
                <form method="POST" action="{{ route('gmao.work-orders.destroy', $workOrder->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="far fa-trash-alt mr-1"></i> Delete
                    </button>
                </form>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="Quick Info">
                <p class="text-muted text-uppercase mb-1">Status</p>
                <p class="mb-2 text-primary font-weight-bold">{{ $statusLabel }}</p>

                <p class="text-muted text-uppercase mb-1">Priority</p>
                <p class="mb-2 text-warning font-weight-bold">{{ $priorityLabel }}</p>

                <p class="text-muted text-uppercase mb-1">Type</p>
                <p class="mb-0 text-info font-weight-bold">{{ $typeLabel }}</p>
            </x-adminlte-card>
        </div>
    </div>
@stop
