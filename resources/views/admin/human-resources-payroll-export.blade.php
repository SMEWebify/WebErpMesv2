@extends('adminlte::page')

@section('title', __('general_content.payroll_export_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.payroll_export_trans_key') }} — {{ $PeriodStart->format('m/Y') }}</h1>
@stop

@section('content')
@include('include.alert-result')

<x-adminlte-card title="{{ __('general_content.search_trans_key') }}" theme="secondary" collapsible>
    <form method="GET" action="{{ route('human.resources.payroll.export') }}" class="form-inline">
        <label class="mr-2" for="month">{{ __('general_content.payroll_month_trans_key') }}</label>
        <input type="month" class="form-control mr-3" name="month" id="month" value="{{ $filters['month'] }}">

        <label class="mr-2" for="user_id">{{ __('general_content.user_trans_key') }}</label>
        <select class="form-control mr-3" name="user_id" id="user_id">
            <option value="">{{ __('general_content.all_trans_key') }}</option>
            @foreach($userSelect as $item)
                <option value="{{ $item->id }}" @if($filters['user_id'] == $item->id) selected @endif>{{ $item->name }}</option>
            @endforeach
        </select>

        <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.search_trans_key') }}" theme="primary" icon="fas fa-lg fa-search"/>
    </form>
</x-adminlte-card>

@if(count($Warnings) > 0)
    <div class="callout callout-warning">
        <h5><i class="fas fa-exclamation-triangle"></i> {{ __('general_content.payroll_warnings_trans_key') }}</h5>
        <ul class="mb-0">
            @foreach($Warnings as $Warning)
                <li>
                    {{ $Warning['user']->name ?? '#' }} —
                    @if($Warning['type'] === 'attendance_anomaly')
                        {{ __('general_content.payroll_warning_attendance_trans_key', ['count' => $Warning['count']]) }}
                    @else
                        {{ __('general_content.payroll_warning_matricule_trans_key') }}
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif

<x-adminlte-card title="{{ __('general_content.payroll_variables_trans_key') }}" theme="primary" maximizable>
    <div class="table-responsive p-0">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th>{{ __('general_content.payroll_matricule_trans_key') }}</th>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{ __('general_content.code_trans_key') }}</th>
                    <th>{{ __('general_content.label_trans_key') }}</th>
                    <th class="text-right">{{ __('general_content.payroll_quantity_trans_key') }}</th>
                    <th>{{ __('general_content.unit_trans_key') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($Rows as $Row)
                <tr>
                    <td>{{ $Row['matricule'] }}</td>
                    <td>{{ $Row['name'] }}</td>
                    <td><span class="badge badge-secondary">{{ $Row['code'] }}</span></td>
                    <td>{{ $Row['label'] }}</td>
                    <td class="text-right">{{ number_format((float) $Row['quantity'], 2, ',', ' ') }}</td>
                    <td>{{ $Row['unit'] }}</td>
                </tr>
            @empty
                <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}" />
            @endforelse
            </tbody>
        </table>
    </div>

    <x-slot name="footerSlot">
        <form method="POST" action="{{ route('human.resources.payroll.export.download', ['ext' => 'csv']) }}" class="d-inline">
            @csrf
            <input type="hidden" name="month" value="{{ $filters['month'] }}">
            <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
            <x-adminlte-button class="btn-flat" type="submit" label="CSV" theme="success" icon="fas fa-lg fa-file-csv"/>
        </form>
        <form method="POST" action="{{ route('human.resources.payroll.export.download', ['ext' => 'xlsx']) }}" class="d-inline">
            @csrf
            <input type="hidden" name="month" value="{{ $filters['month'] }}">
            <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
            <x-adminlte-button class="btn-flat" type="submit" label="Excel" theme="success" icon="fas fa-lg fa-file-excel"/>
        </form>
        <small class="text-muted ml-2">{{ __('general_content.payroll_export_hint_trans_key') }}</small>
    </x-slot>
</x-adminlte-card>
@stop

@section('css')
@stop

@section('js')
@stop
