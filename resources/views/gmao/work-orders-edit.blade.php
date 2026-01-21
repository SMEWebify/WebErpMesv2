@extends('adminlte::page')

@section('title', 'Edit Work Order')

@section('content_header')
    <h1>Edit Work Order</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('gmao.work-orders.update', $workOrder->id) }}">
        @csrf
        @method('PUT')
        <x-adminlte-card title="Work Order" theme="secondary" maximizable>
            <div class="form-group">
                <label for="asset_id">Asset</label>
                <select class="form-control" name="asset_id" id="asset_id">
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id', $workOrder->asset_id) == $asset->id)>{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="times_machine_event_id">Machine event</label>
                <select class="form-control" name="times_machine_event_id" id="times_machine_event_id">
                    <option value="">Not specified</option>
                    @foreach($machineEvents as $machineEvent)
                        <option value="{{ $machineEvent->id }}" @selected(old('times_machine_event_id', $workOrder->times_machine_event_id) == $machineEvent->id)>{{ $machineEvent->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $workOrder->title) }}">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $workOrder->description) }}</textarea>
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <select class="form-control" name="priority" id="priority">
                    @foreach($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $workOrder->priority) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" name="status" id="status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $workOrder->status) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="requested_at">Requested at</label>
                <input type="date" class="form-control" name="requested_at" id="requested_at" value="{{ old('requested_at', optional($workOrder->requested_at)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="scheduled_at">Scheduled at</label>
                <input type="date" class="form-control" name="scheduled_at" id="scheduled_at" value="{{ old('scheduled_at', optional($workOrder->scheduled_at)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="completed_at">Completed at</label>
                <input type="date" class="form-control" name="completed_at" id="completed_at" value="{{ old('completed_at', optional($workOrder->completed_at)->format('Y-m-d')) }}">
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="Save" theme="success" icon="fas fa-lg fa-save" />
                <a href="{{ route('gmao.work-orders.show', $workOrder->id) }}" class="btn btn-secondary float-right">Back</a>
            </x-slot>
        </x-adminlte-card>
    </form>
@stop
