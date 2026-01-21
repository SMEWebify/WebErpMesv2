@extends('adminlte::page')

@section('title', 'Maintenance Plan #' . $plan->id)

@section('content_header')
    <h1>Maintenance Plan #{{ $plan->id }}</h1>
@stop

@section('content')
    <x-adminlte-card title="Maintenance Plan Details" theme="primary" maximizable>
        <div class="row">
            <div class="col-md-6">
                <dl>
                    <dt>Asset</dt>
                    <dd>{{ $plan->asset?->name }}</dd>
                    <dt>Title</dt>
                    <dd>{{ $plan->title }}</dd>
                    <dt>Description</dt>
                    <dd>{{ $plan->description ?? 'N/A' }}</dd>
                    <dt>Trigger type</dt>
                    <dd>{{ ucfirst(str_replace('_', ' ', $plan->trigger_type)) }}</dd>
                    <dt>Trigger value</dt>
                    <dd>{{ $plan->trigger_value ?? 'N/A' }}</dd>
                    <dt>Fixed date</dt>
                    <dd>{{ optional($plan->fixed_date)->format('Y-m-d') }}</dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl>
                    <dt>Estimated duration</dt>
                    <dd>{{ $plan->estimated_duration_minutes ? $plan->estimated_duration_minutes . ' min' : 'N/A' }}</dd>
                    <dt>Required skill</dt>
                    <dd>{{ $plan->required_skill ?? 'N/A' }}</dd>
                    <dt>Actions list</dt>
                    <dd>{{ $plan->actions ?? 'N/A' }}</dd>
                    <dt>Required parts</dt>
                    <dd>{{ $plan->required_parts ?? 'N/A' }}</dd>
                </dl>
            </div>
        </div>
        <form method="POST" action="{{ route('gmao.maintenance-plans.generate-work-order', $plan->id) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success">Generate work order</button>
        </form>
        <a href="{{ route('gmao.maintenance-plans.edit', $plan->id) }}" class="btn btn-info">Edit</a>
        <a href="{{ route('gmao.maintenance-plans.index') }}" class="btn btn-secondary">Back</a>
        <form method="POST" action="{{ route('gmao.maintenance-plans.destroy', $plan->id) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </x-adminlte-card>
@stop
