@extends('adminlte::page')

@section('title', 'Edit Maintenance Plan')

@section('content_header')
    <h1>Edit Maintenance Plan</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('gmao.maintenance-plans.update', $plan->id) }}">
        @csrf
        @method('PUT')
        <x-adminlte-card title="Maintenance Plan" theme="secondary" maximizable>
            <div class="form-group">
                <label for="asset_id">Asset</label>
                <select class="form-control" name="asset_id" id="asset_id">
                    <option value="">Select an asset</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id', $plan->asset_id) == $asset->id)>{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $plan->title) }}">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" name="description" id="description" rows="3">{{ old('description', $plan->description) }}</textarea>
            </div>
            <div class="form-group">
                <label for="trigger_type">Trigger type</label>
                <select class="form-control" name="trigger_type" id="trigger_type">
                    @foreach($triggerTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('trigger_type', $plan->trigger_type) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="trigger_value">Trigger value (weekly, monthly, 500 hours, 200 cycles...)</label>
                <input type="text" class="form-control" name="trigger_value" id="trigger_value" value="{{ old('trigger_value', $plan->trigger_value) }}">
            </div>
            <div class="form-group">
                <label for="fixed_date">Fixed date</label>
                <input type="date" class="form-control" name="fixed_date" id="fixed_date" value="{{ old('fixed_date', optional($plan->fixed_date)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="estimated_duration_minutes">Estimated duration (minutes)</label>
                <input type="number" min="0" class="form-control" name="estimated_duration_minutes" id="estimated_duration_minutes" value="{{ old('estimated_duration_minutes', $plan->estimated_duration_minutes) }}">
            </div>
            <div class="form-group">
                <label for="required_skill">Required skill</label>
                <input type="text" class="form-control" name="required_skill" id="required_skill" value="{{ old('required_skill', $plan->required_skill) }}">
            </div>
            <div class="form-group">
                <label for="actions">Actions list</label>
                <textarea class="form-control" name="actions" id="actions" rows="3">{{ old('actions', $plan->actions) }}</textarea>
            </div>
            <div class="form-group">
                <label for="required_parts">Required parts</label>
                <textarea class="form-control" name="required_parts" id="required_parts" rows="3">{{ old('required_parts', $plan->required_parts) }}</textarea>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="Save" theme="success" icon="fas fa-lg fa-save" />
                <a href="{{ route('gmao.maintenance-plans.show', $plan->id) }}" class="btn btn-secondary float-right">Back</a>
            </x-slot>
        </x-adminlte-card>
    </form>
@stop
