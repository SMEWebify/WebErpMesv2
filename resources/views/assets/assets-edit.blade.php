@extends('adminlte::page')

@section('title', __('general_content.edit_trans_key') . ' ' . __('general_content.asset_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.edit_trans_key') }} {{ __('general_content.asset_trans_key') }}</h1>
@stop

@section('content')
    <form method="POST" action="{{ route('assets.update', $asset->id) }}">
        @csrf
        <x-adminlte-card title="{{ __('general_content.edit_trans_key') }} {{ $asset->name }}" theme="secondary" maximizable>
            <div class="form-group">
                <label for="name">{{ __('general_content.name_trans_key') }}</label>
                <input type="text" class="form-control" name="name" id="name" value="{{ old('name', $asset->name) }}">
            </div>
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" class="form-control" name="category" id="category" value="{{ old('category', $asset->category) }}">
            </div>
            <div class="form-group">
                <label for="methods_ressource_id">{{ __('general_content.ressource_trans_key') }}</label>
                <select class="form-control" name="methods_ressource_id" id="methods_ressource_id">
                    <option value="">{{ __('general_content.select_ressource_trans_key') }}</option>
                    @foreach($ressourcesSelect as $ressource)
                        <option value="{{ $ressource->id }}" {{ old('methods_ressource_id', $asset->methods_ressource_id) == $ressource->id ? 'selected' : '' }}>
                            {{ $ressource->label }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="acquisition_value">Acquisition value</label>
                <input type="number" step="0.01" class="form-control" name="acquisition_value" id="acquisition_value" value="{{ old('acquisition_value', $asset->acquisition_value) }}">
            </div>
            <div class="form-group">
                <label for="acquisition_date">Acquisition date</label>
                <input type="date" class="form-control" name="acquisition_date" id="acquisition_date" value="{{ old('acquisition_date', $asset->acquisition_date->format('Y-m-d')) }}">
            </div>
            <div class="form-group">
                <label for="depreciation_duration">Depreciation duration (months)</label>
                <input type="number" class="form-control" name="depreciation_duration" id="depreciation_duration" value="{{ old('depreciation_duration', $asset->depreciation_duration) }}">
            </div>
            <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save" />
                <a href="{{ route('assets.show', $asset->id) }}" class="btn btn-secondary float-right">{{ __('general_content.back_trans_key') }}</a>
            </x-slot>
        </x-adminlte-card>
    </form>
@stop
