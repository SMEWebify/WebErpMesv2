@extends('adminlte::page')

@section('title', __('general_content.gmao_edit_maintenance_plan_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.gmao_edit_maintenance_plan_trans_key') }}</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('gmao.maintenance-plans.update', $plan->id) }}">
        @csrf
        @method('PUT')
        <x-adminlte-card title="{{ __('general_content.gmao_maintenance_plan_trans_key') }}" theme="secondary" maximizable>
            <div class="form-group">
                <label for="asset_id">{{ __('general_content.asset_trans_key') }}</label>
                <select class="form-control" name="asset_id" id="asset_id">
                    <option value="">{{ __('general_content.gmao_select_asset_trans_key') }}</option>
                    @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" @selected(old('asset_id', $plan->asset_id) == $asset->id)>{{ $asset->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="title">{{ __('general_content.title_trans_key') }}</label>
                <input type="text" class="form-control" name="title" id="title" value="{{ old('title', $plan->title) }}">
            </div>
            <div class="form-group">
                <label for="description">{{ __('general_content.description_trans_key') }}</label>
                <textarea class="form-control" name="description" id="description" rows="3">{{ old('description', $plan->description) }}</textarea>
            </div>
            <div class="form-group">
                <label for="trigger_type">{{ __('general_content.gmao_trigger_type_trans_key') }}</label>
                <select class="form-control" name="trigger_type" id="trigger_type">
                    @foreach($triggerTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('trigger_type', $plan->trigger_type) == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="trigger_value">{{ __('general_content.gmao_trigger_value_hint_trans_key') }}</label>
                <input type="text" class="form-control" name="trigger_value" id="trigger_value" value="{{ old('trigger_value', $plan->trigger_value) }}">
            </div>
            <div class="form-group">
                <label for="fixed_date">{{ __('general_content.gmao_fixed_date_trans_key') }}</label>
                <input type="date" class="form-control" name="fixed_date" id="fixed_date" value="{{ old('fixed_date', optional($plan->fixed_date)->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="estimated_duration_minutes">{{ __('general_content.gmao_estimated_duration_minutes_trans_key') }}</label>
                <input type="number" min="0" class="form-control" name="estimated_duration_minutes" id="estimated_duration_minutes" value="{{ old('estimated_duration_minutes', $plan->estimated_duration_minutes) }}">
            </div>
            <div class="form-group">
                <label for="required_skill">{{ __('general_content.gmao_required_skill_trans_key') }}</label>
                <input type="text" class="form-control" name="required_skill" id="required_skill" value="{{ old('required_skill', $plan->required_skill) }}">
            </div>
            <div class="form-group">
                <label for="actions">{{ __('general_content.gmao_actions_list_trans_key') }}</label>
                <textarea class="form-control" name="actions" id="actions" rows="3">{{ old('actions', $plan->actions) }}</textarea>
            </div>
            <div class="form-group">
                <label for="required_parts">{{ __('general_content.gmao_required_parts_trans_key') }}</label>
                <textarea class="form-control" name="required_parts" id="required_parts" rows="3">{{ old('required_parts', $plan->required_parts) }}</textarea>
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.save_trans_key') }}" theme="success" icon="fas fa-lg fa-save" />
                <a href="{{ route('gmao.maintenance-plans.show', $plan->id) }}" class="btn btn-secondary float-right">{{ __('general_content.back_trans_key') }}</a>
            </x-slot>
        </x-adminlte-card>
    </form>
@stop
