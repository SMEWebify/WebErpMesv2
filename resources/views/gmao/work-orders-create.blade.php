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
                <label for="status">Status</label>
                <select class="form-control" name="status" id="status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', 'requested') == $value)>{{ $label }}</option>
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
                <label for="completed_at">Completed at</label>
                <input type="date" class="form-control" name="completed_at" id="completed_at" value="{{ old('completed_at') }}">
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="Save" theme="success" icon="fas fa-lg fa-save" />
                <a href="{{ route('gmao.work-orders.index') }}" class="btn btn-secondary float-right">Back</a>
            </x-slot>
        </x-adminlte-card>
    </form>
@stop
