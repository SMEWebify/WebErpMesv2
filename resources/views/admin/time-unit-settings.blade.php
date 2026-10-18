@extends('adminlte::page')

@section('title', __('general_content.time_unit_settings_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.time_unit_settings_trans_key') }}</h1>
@stop

@section('content')
    <x-InfocalloutComponent note="{{ __('general_content.time_unit_note_trans_key') }}" />

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.time.unit.settings.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3" style="max-width: 360px;">
                    <label for="time_unit" class="form-label">{{ __('general_content.time_unit_label_trans_key') }}</label>
                    <select name="time_unit" id="time_unit" class="form-select @error('time_unit') is-invalid @enderror">
                        <option value="hours" @selected(old('time_unit', $unit) === 'hours')>{{ __('general_content.time_unit_hours_trans_key') }}</option>
                        <option value="minutes" @selected(old('time_unit', $unit) === 'minutes')>{{ __('general_content.time_unit_minutes_trans_key') }}</option>
                    </select>
                    @error('time_unit')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ __('general_content.save_trans_key') }}</button>
            </form>
        </div>
    </div>
@stop
