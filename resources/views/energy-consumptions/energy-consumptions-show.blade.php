@extends('adminlte::page')

@section('title', __('general_content.energy_consumption_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.energy_consumption_trans_key') }}</h1>
@stop

@section('content')
    <x-adminlte-card theme="primary" icon="fas fa-bolt">
        <dl class="row">
            <dt class="col-sm-3">{{ __('general_content.machine_trans_key') }}</dt>
            <dd class="col-sm-9">{{ optional($energyConsumption->methodsRessource)->label ?? '-' }}</dd>
            <dt class="col-sm-3">{{ __('general_content.kwh_trans_key') }}</dt>
            <dd class="col-sm-9">{{ $energyConsumption->kwh }}</dd>
            <dt class="col-sm-3">{{ __('general_content.cost_per_kwh_trans_key') }}</dt>
            <dd class="col-sm-9">{{ $energyConsumption->cost_per_kwh }}</dd>
            <dt class="col-sm-3">{{ __('general_content.total_cost_trans_key') }}</dt>
            <dd class="col-sm-9">{{ $energyConsumption->total_cost }}</dd>
        </dl>
        <x-adminlte-button class="btn-flat" theme="secondary" icon="fas fa-arrow-left" label="{{ __('general_content.back_to_list_trans_key') }}" onclick="window.location='{{ route('energy-consumptions.index') }}'"/>
    </x-adminlte-card>
@stop
