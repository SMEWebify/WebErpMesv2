@extends('adminlte::page')

@section('title', 'Work Order #' . $workOrder->id)

@section('content_header')
    <h1>Work Order #{{ $workOrder->id }}</h1>
@stop

@section('content')
    <x-adminlte-card title="Work Order Details" theme="primary" maximizable>
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt>Asset</dt>
                    <dd>{{ $workOrder->asset?->name }}</dd>
                    <dt>Title</dt>
                    <dd>{{ $workOrder->title }}</dd>
                    <dt>Description</dt>
                    <dd>{{ $workOrder->description }}</dd>
                    <dt>Machine event</dt>
                    <dd>{{ $workOrder->machineEvent?->label ?? 'Not specified' }}</dd>
                    <dt>Priority</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $workOrder->priority)) }}</dd>
                    <dt>Status</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $workOrder->status)) }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl>
                    <dt>Requested at</dt>
                    <dd>{{ optional($workOrder->requested_at)->format('Y-m-d') }}</dd>
                    <dt>Scheduled at</dt>
                    <dd>{{ optional($workOrder->scheduled_at)->format('Y-m-d') }}</dd>
                    <dt>Completed at</dt>
                    <dd>{{ optional($workOrder->completed_at)->format('Y-m-d') }}</dd>
                    <dt>Created by</dt>
                    <dd>{{ $workOrder->creator?->name ?? 'N/A' }}</dd>
                </dl>
            </div>
        </div>
        <a href="{{ route('gmao.work-orders.edit', $workOrder->id) }}" class="btn btn-info">Edit</a>
        <a href="{{ route('gmao.work-orders.index') }}" class="btn btn-secondary">Back</a>
        <form method="POST" action="{{ route('gmao.work-orders.destroy', $workOrder->id) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </x-adminlte-card>
@stop
