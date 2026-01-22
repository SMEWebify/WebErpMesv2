@extends('adminlte::page')

@section('title', __('general_content.gmao_work_order_number_trans_key', ['id' => $workOrder->id]))

@section('content_header')
    <h1>{{ __('general_content.gmao_work_order_number_trans_key', ['id' => $workOrder->id]) }}</h1>
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
            <x-adminlte-card theme="light" title="{{ __('general_content.gmao_general_information_trans_key') }}">
                <x-slot name="toolsSlot">
                    <span class="badge {{ $statusClass }} mr-2">{{ $statusLabel }}</span>
                    <span class="badge {{ $priorityClass }}">{{ $priorityLabel }}</span>
                </x-slot>
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.title_trans_key') }}</p>
                        <p class="font-weight-bold mb-3">{{ $workOrder->title }}</p>

                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.description_trans_key') }}</p>
                        <p class="mb-3">{{ $workOrder->description ?? __('general_content.gmao_not_specified_trans_key') }}</p>

                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.machine_event_trans_key') }}</p>
                        <p class="mb-0">{{ $workOrder->machineEvent?->label ?? __('general_content.gmao_not_specified_trans_key') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.type_trans_key') }}</p>
                        <span class="badge {{ $typeClass }} mb-3">{{ $typeLabel }}</span>

                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.asset_trans_key') }}</p>
                        <p class="mb-3">{{ $workOrder->asset?->name ?? __('general_content.gmao_not_specified_trans_key') }}</p>

                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.gmao_created_by_trans_key') }}</p>
                        <p class="mb-0">
                            <i class="far fa-user mr-1 text-muted"></i>{{ $workOrder->creator?->name ?? __('general_content.not_available_trans_key') }}
                        </p>
                    </div>
                </div>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="{{ __('general_content.gmao_schedule_timeline_trans_key') }}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-calendar text-primary mr-1"></i> {{ __('general_content.gmao_requested_at_trans_key') }}
                        </p>
                        <p class="mb-0">{{ optional($workOrder->requested_at)->format('Y-m-d') ?? __('general_content.gmao_not_specified_trans_key') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-calendar-check text-success mr-1"></i> {{ __('general_content.gmao_scheduled_at_trans_key') }}
                        </p>
                        <p class="mb-0">{{ optional($workOrder->scheduled_at)->format('Y-m-d') ?? __('general_content.gmao_not_specified_trans_key') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-clock text-warning mr-1"></i> {{ __('general_content.gmao_started_at_trans_key') }}
                        </p>
                        <p class="mb-0">{{ $workOrder->started_at ? $workOrder->started_at->format('Y-m-d H:i') : __('general_content.gmao_not_started_trans_key') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">
                            <i class="far fa-flag text-purple mr-1"></i> {{ __('general_content.gmao_finished_at_trans_key') }}
                        </p>
                        <p class="mb-0">{{ $workOrder->finished_at ? $workOrder->finished_at->format('Y-m-d H:i') : __('general_content.gmao_not_finished_trans_key') }}</p>
                    </div>
                </div>
                <hr class="mt-0">
                <div class="row">
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.gmao_estimated_duration_trans_key') }}</p>
                        <p class="mb-0">
                            {{ $workOrder->estimated_duration_minutes ? $workOrder->estimated_duration_minutes . ' ' . __('general_content.gmao_minutes_suffix_trans_key') : __('general_content.gmao_not_specified_trans_key') }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.gmao_actual_duration_trans_key') }}</p>
                        <p class="mb-0">
                            {{ $workOrder->actual_duration_minutes ? $workOrder->actual_duration_minutes . ' ' . __('general_content.gmao_minutes_suffix_trans_key') : __('general_content.gmao_not_available_trans_key') }}
                        </p>
                    </div>
                </div>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="{{ __('general_content.gmao_execution_details_trans_key') }}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.gmao_assigned_technician_trans_key') }}</p>
                        <p class="mb-0 text-warning">{{ $workOrder->technician?->name ?? __('general_content.gmao_unassigned_trans_key') }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.gmao_machine_stopped_trans_key') }}</p>
                        <span class="badge {{ $workOrder->machine_stopped ? 'badge-danger' : 'badge-success' }}">
                            {{ $workOrder->machine_stopped ? __('general_content.yes_trans_key') : __('general_content.no_trans_key') }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.gmao_actions_performed_trans_key') }}</p>
                        <p class="mb-0 font-italic">{{ $workOrder->actions_performed ?? __('general_content.gmao_none_recorded_trans_key') }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="text-muted text-uppercase mb-1">{{ __('general_content.gmao_parts_consumed_trans_key') }}</p>
                        <p class="mb-0 font-italic">{{ $workOrder->parts_consumed ?? __('general_content.gmao_none_used_trans_key') }}</p>
                    </div>
                </div>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="{{ __('general_content.gmao_comments_photos_trans_key') }}">
                <p class="mb-0 font-italic">{{ $workOrder->comments ?? __('general_content.gmao_no_comments_photos_trans_key') }}</p>
            </x-adminlte-card>
        </div>

        <div class="col-lg-4">
            <x-adminlte-card theme="light" title="{{ __('general_content.action_trans_key') }}">
                <a href="{{ route('gmao.work-orders.edit', $workOrder->id) }}" class="btn btn-success btn-block mb-2">
                    <i class="far fa-edit mr-1"></i> {{ __('general_content.gmao_edit_work_order_trans_key') }}
                </a>
                <a href="{{ route('gmao.work-orders.index') }}" class="btn btn-outline-secondary btn-block mb-2">
                    <i class="fas fa-arrow-left mr-1"></i> {{ __('general_content.gmao_back_to_list_trans_key') }}
                </a>
                <form method="POST" action="{{ route('gmao.work-orders.destroy', $workOrder->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="far fa-trash-alt mr-1"></i> {{ __('general_content.delete_trans_key') }}
                    </button>
                </form>
            </x-adminlte-card>

            <x-adminlte-card theme="light" title="{{ __('general_content.gmao_quick_info_trans_key') }}">
                <p class="text-muted text-uppercase mb-1">{{ __('general_content.status_trans_key') }}</p>
                <p class="mb-2 text-primary font-weight-bold">{{ $statusLabel }}</p>

                <p class="text-muted text-uppercase mb-1">{{ __('general_content.priority_trans_key') }}</p>
                <p class="mb-2 text-warning font-weight-bold">{{ $priorityLabel }}</p>

                <p class="text-muted text-uppercase mb-1">{{ __('general_content.type_trans_key') }}</p>
                <p class="mb-0 text-info font-weight-bold">{{ $typeLabel }}</p>
            </x-adminlte-card>
        </div>
    </div>
@stop
