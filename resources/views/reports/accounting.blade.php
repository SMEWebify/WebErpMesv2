@extends('adminlte::page')

@section('title', __('general_content.accounting_reports_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.accounting_reports_trans_key') }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-4">
        <x-adminlte-card title="{{ __('general_content.total_revenue_trans_key') }}" theme="success" icon="fas fa-coins" >
            {{ \Illuminate\Support\Number::currency($revenue, app('Factory')->curency, config('app.locale')) }}
        </x-adminlte-card>
    </div>
    <div class="col-md-4">
        <x-adminlte-card title="{{ __('general_content.total_expense_trans_key') }}" theme="danger" icon="fas fa-file-invoice-dollar" >
            {{ \Illuminate\Support\Number::currency($expenses, app('Factory')->curency, config('app.locale')) }}
        </x-adminlte-card>
    </div>
    <div class="col-md-4">
        <x-adminlte-card title="{{ __('general_content.total_profit_trans_key') }}" theme="purple" theme-mode="outline" icon="fas fa-chart-line" >
            {{ \Illuminate\Support\Number::currency($profit, app('Factory')->curency, config('app.locale')) }}
        </x-adminlte-card>
    </div>
</div>
<div class="mt-4">
    <div
        id="fec-export-lines-app"
        data-endpoints="{{ json_encode([
            'list'   => route('admin.fec.export.json.list'),
            'export' => route('admin.fec.export', ['ext' => '__EXT__']),
        ]) }}"
        data-trans="{{ json_encode(['no_data' => __('general_content.no_data_trans_key')]) }}"
        data-start-date="{{ now()->startOfYear()->format('Y-m-d') }}"
        data-end-date="{{ now()->format('Y-m-d') }}"
    ></div>
</div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop