@extends('adminlte::page')

@section('title', 'New Work Order')

@section('content_header')
    <h1>New Work Order</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('gmao.work-orders.store') }}">
        @csrf
        <x-adminlte-card title="Work Order" theme="secondary" maximizable>
            <div class="form-group">
                <label for="asset_id">Asset</label>
                <select class="form-control" name="asset_id" id="asset_id">
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id', $selectedAssetId) == $asset->id)>{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="times_machine_event_id">Machine event</label>
                <select class="form-control" name="times_machine_event_id" id="times_machine_event_id">
                    <option value="">Not specified</option>
                    @foreach($machineEvents as $machineEvent)
                        <option value="{{ $machineEvent->id }}" @selected(old('times_machine_event_id') == $machineEvent->id)>{{ $machineEvent->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" class="form-control" name="title" id="title" value="{{ old('title') }}">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" name="description" id="description" rows="4">{{ old('description') }}</textarea>
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <select class="form-control" name="priority" id="priority">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', 'medium') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="work_type">Type</label>
                <select class="form-control" name="work_type" id="work_type">
                    @foreach($workTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('work_type', 'preventive') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" name="status" id="status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', 'draft') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="assigned_to">Assigned technician</label>
                <select class="form-control" name="assigned_to" id="assigned_to">
                    <option value="">Not assigned</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" @selected(old('assigned_to') == $technician->id)>{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="requested_at">Requested at</label>
                <input type="date" class="form-control" name="requested_at" id="requested_at" value="{{ old('requested_at', now()->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="scheduled_at">Scheduled at</label>
                <input type="date" class="form-control" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at') }}">
            </div>
            <div class="form-group">
                <label for="started_at">Started at</label>
                <input type="datetime-local" class="form-control" name="started_at" id="started_at" value="{{ old('started_at') }}">
            </div>
            <div class="form-group">
                <label for="finished_at">Finished at</label>
                <input type="datetime-local" class="form-control" name="finished_at" id="finished_at" value="{{ old('finished_at') }}">
            </div>
            <div class="form-group">
                <label for="completed_at">Completed at</label>
                <input type="date" class="form-control" name="completed_at" id="completed_at" value="{{ old('completed_at') }}">
            </div>
            <div class="form-group">
                <label for="estimated_duration_minutes">Estimated duration (minutes)</label>
                <input type="number" min="0" class="form-control" name="estimated_duration_minutes" id="estimated_duration_minutes" value="{{ old('estimated_duration_minutes') }}">
            </div>
            <div class="form-group">
                <label for="actual_duration_minutes">Actual duration (minutes)</label>
                <input type="number" min="0" class="form-control" name="actual_duration_minutes" id="actual_duration_minutes" value="{{ old('actual_duration_minutes') }}">
            </div>
            <div class="form-group">
                <label for="actions_performed">Actions performed</label>
                <textarea class="form-control" name="actions_performed" id="actions_performed" rows="4">{{ old('actions_performed') }}</textarea>
            </div>
            <div class="form-group">
                <label for="parts_consumed">Parts consumed</label>
                <textarea class="form-control" name="parts_consumed" id="parts_consumed" rows="3">{{ old('parts_consumed') }}</textarea>
            </div>
            <div class="form-group">
                <label for="comments">Comments / photos</label>
                <textarea class="form-control" name="comments" id="comments" rows="3">{{ old('comments') }}</textarea>
            </div>
            <div class="form-group">
                <label for="failure_type">Failure type</label>
                <input type="text" class="form-control" name="failure_type" id="failure_type" value="{{ old('failure_type') }}">
            </div>
            <div class="form-group">
                <label for="severity">Severity</label>
                <input type="text" class="form-control" name="severity" id="severity" value="{{ old('severity') }}">
            </div>
            <div class="form-group">
                <label for="machine_stopped">Machine stopped</label>
                <select class="form-control" name="machine_stopped" id="machine_stopped">
                    <option value="0" @selected(old('machine_stopped', '0') === '0')>No</option>
                    <option value="1" @selected(old('machine_stopped') === '1')>Yes</option>
                </select>
            </div>
            <div class="form-group">
                <label for="failure_started_at">Failure started at</label>
                <input type="datetime-local" class="form-control" name="failure_started_at" id="failure_started_at" value="{{ old('failure_started_at') }}">
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="Save" theme="success" icon="fas fa-lg fa-save" />
                <a href="{{ route('gmao.work-orders.index') }}" class="btn btn-secondary float-right">Back</a>
            </x-slot>
        </x-adminlte-card>
    </form>
@stop
