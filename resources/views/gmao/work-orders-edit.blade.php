@extends('adminlte::page')

@section('title', __('general_content.gmao_edit_work_order_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.gmao_edit_work_order_trans_key') }}</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('gmao.work-orders.update', $workOrder->id) }}">
        @csrf
        @method('PUT')
        <x-adminlte-card title="{{ __('general_content.gmao_work_order_trans_key') }}" theme="secondary" maximizable>
            <div class="form-group">
                <label for="asset_id">{{ __('general_content.asset_trans_key') }}</label>
                <select class="form-control" name="asset_id" id="asset_id">
                    <option value="">{{ __('general_content.gmao_select_asset_trans_key') }}</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id', $workOrder->asset_id) == $asset->id)>{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="times_machine_event_id">{{ __('general_content.machine_event_trans_key') }}</label>
                <select class="form-control" name="times_machine_event_id" id="times_machine_event_id">
                    <option value="">{{ __('general_content.gmao_not_specified_trans_key') }}</option>
                    @foreach($machineEvents as $machineEvent)
                        <option value="{{ $machineEvent->id }}" @selected(old('times_machine_event_id', $workOrder->times_machine_event_id) == $machineEvent->id)>{{ $machineEvent->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="title">{{ __('general_content.title_trans_key') }}</label>
                <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $workOrder->title) }}">
            </div>
            <div class="form-group">
                <label for="description">{{ __('general_content.description_trans_key') }}</label>
                <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $workOrder->description) }}</textarea>
            </div>
            <div class="form-group">
                <label for="priority">{{ __('general_content.priority_trans_key') }}</label>
                <select class="form-control" name="priority" id="priority">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $workOrder->priority) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="work_type">{{ __('general_content.type_trans_key') }}</label>
                <select class="form-control" name="work_type" id="work_type">
                    @foreach($workTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('work_type', $workOrder->work_type) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">{{ __('general_content.status_trans_key') }}</label>
                <select class="form-control" name="status" id="status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $workOrder->status) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="assigned_to">{{ __('general_content.gmao_assigned_technician_trans_key') }}</label>
                <select class="form-control" name="assigned_to" id="assigned_to">
                    <option value="">{{ __('general_content.gmao_not_assigned_trans_key') }}</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected(old('assigned_to', $workOrder->assigned_to) == $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="requested_at">{{ __('general_content.gmao_requested_at_trans_key') }}</label>
                <input type="date" class="form-control" name="requested_at" id="requested_at" value="{{ old('requested_at', optional($workOrder->requested_at)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="scheduled_at">{{ __('general_content.gmao_scheduled_at_trans_key') }}</label>
                <input type="date" class="form-control" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at', optional($workOrder->scheduled_at)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="started_at">{{ __('general_content.gmao_started_at_trans_key') }}</label>
                <input type="datetime-local" class="form-control" name="started_at" id="started_at" value="{{ old('started_at', optional($workOrder->started_at)->format('Y-m-d\\TH:i')) }}">
            </div>
            <div class="form-group">
                <label for="finished_at">{{ __('general_content.gmao_finished_at_trans_key') }}</label>
                <input type="datetime-local" class="form-control" name="finished_at" id="finished_at" value="{{ old('finished_at', optional($workOrder->finished_at)->format('Y-m-d\\TH:i')) }}">
            </div>
            <div class="form-group">
                <label for="completed_at">{{ __('general_content.gmao_completed_at_trans_key') }}</label>
                <input type="date" class="form-control" name="completed_at" id="completed_at" value="{{ old('completed_at', optional($workOrder->completed_at)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="estimated_duration_minutes">{{ __('general_content.gmao_estimated_duration_minutes_trans_key') }}</label>
                <input type="number" min="0" class="form-control" name="estimated_duration_minutes" id="estimated_duration_minutes" value="{{ old('estimated_duration_minutes', $workOrder->estimated_duration_minutes) }}">
            </div>
            <div class="form-group">
                <label for="actual_duration_minutes">{{ __('general_content.gmao_actual_duration_minutes_trans_key') }}</label>
                <input type="number" min="0" class="form-control" name="actual_duration_minutes" id="actual_duration_minutes" value="{{ old('actual_duration_minutes', $workOrder->actual_duration_minutes) }}">
            </div>
            <div class="form-group">
                <label for="actions_performed">{{ __('general_content.gmao_actions_performed_trans_key') }}</label>
                <textarea class="form-control" name="actions_performed" id="actions_performed" rows="4">{{ old('actions_performed', $workOrder->actions_performed) }}</textarea>
            </div>
            <div class="form-group">
                <label for="parts_consumed">{{ __('general_content.gmao_parts_consumed_trans_key') }}</label>
                <textarea class="form-control" name="parts_consumed" id="parts_consumed" rows="3">{{ old('parts_consumed', $workOrder->parts_consumed) }}</textarea>
            </div>
            <div class="form-group">
                <label for="comments">{{ __('general_content.gmao_comments_photos_trans_key') }}</label>
                <textarea class="form-control" name="comments" id="comments" rows="3">{{ old('comments', $workOrder->comments) }}</textarea>
            </div>
            <div class="form-group">
                <label for="failure_type">{{ __('general_content.failure_type_trans_key') }}</label>
                <input type="text" class="form-control" name="failure_type" id="failure_type" value="{{ old('failure_type', $workOrder->failure_type) }}">
            </div>
            <div class="form-group">
                <label for="severity">{{ __('general_content.gmao_severity_trans_key') }}</label>
                <input type="text" class="form-control" name="severity" id="severity" value="{{ old('severity', $workOrder->severity) }}">
            </div>
            <div class="form-group">
                <label for="machine_stopped">{{ __('general_content.gmao_machine_stopped_trans_key') }}</label>
                <select class="form-control" name="machine_stopped" id="machine_stopped">
                    <option value="0" @selected(old('machine_stopped', $workOrder->machine_stopped ? '1' : '0') === '0')>{{ __('general_content.no_trans_key') }}</option>
                    <option value="1" @selected(old('machine_stopped', $workOrder->machine_stopped ? '1' : '0') === '1')>{{ __('general_content.yes_trans_key') }}</option>
                </select>
            </div>
            <div class="form-group">
                <label for="failure_started_at">{{ __('general_content.gmao_failure_started_at_trans_key') }}</label>
                <input type="datetime-local" class="form-control" name="failure_started_at" id="failure_started_at" value="{{ old('failure_started_at', optional($workOrder->failure_started_at)->format('Y-m-d\\TH:i')) }}">
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.save_trans_key') }}" theme="success" icon="fas fa-lg fa-save" />
                <a href="{{ route('gmao.work-orders.show', $workOrder->id) }}" class="btn btn-secondary float-right">{{ __('general_content.back_trans_key') }}</a>
            </x-slot>
        </x-adminlte-card>
    </form>
@stop
