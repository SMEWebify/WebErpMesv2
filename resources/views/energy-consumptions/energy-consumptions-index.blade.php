@extends('adminlte::page')

@section('title', __('general_content.energy_consumption_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.energy_consumption_trans_key') }}</h1>
@stop

@section('content')
    <x-adminlte-card theme="primary" icon="fas fa-bolt">
        <form method="POST" action="{{ route('energy-consumptions.store') }}" class="mb-4">
            @csrf
            <div class="row">
                <div class="col-md-4">
                    <x-adminlte-select name="methods_ressource_id" label="{{ __('general_content.machine_trans_key') }}" required>
                        @foreach ($methodsRessources as $resource)
                            <option value="{{ $resource->id }}">{{ $resource->id }} - {{ $resource->label }}</option>
                        @endforeach
                    </x-adminlte-select>
                </div>
                <div class="col-md-3">
                    <x-adminlte-input name="kwh" type="number" step="0.01" label="{{ __('general_content.kwh_trans_key') }}" required />
                </div>
                <div class="col-md-3">
                    <x-adminlte-input name="cost_per_kwh" type="number" step="0.01" label="{{ __('general_content.cost_per_kwh_trans_key') }}" required />
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.add_trans_key') }}" theme="success" icon="fas fa-plus"/>
                </div>
            </div>
        </form>

        <ul class="list-group">
            @foreach ($energyConsumptions as $energyConsumption)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="{{ route('energy-consumptions.show', $energyConsumption->id) }}">
                        {{ optional($energyConsumption->methodsRessource)->label ?? '-' }} - {{ $energyConsumption->kwh }} kWh @ {{ $energyConsumption->cost_per_kwh }} = {{ $energyConsumption->total_cost }}
                    </a>
                </li>
            @endforeach
        </ul>
    </x-adminlte-card>
@stop
