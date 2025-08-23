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
                <div class="col-md-5">
                    <x-adminlte-input name="recorded_at" type="date" label="{{ __('general_content.date_trans_key') }}" required />
                </div>
                <div class="col-md-5">
                    <x-adminlte-input name="amount" type="number" step="0.01" label="{{ __('general_content.amount_trans_key') }} (kWh)" required />
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
                        {{ $energyConsumption->recorded_at }} - {{ $energyConsumption->amount }} kWh
                    </a>
                </li>
            @endforeach
        </ul>
    </x-adminlte-card>
@stop
