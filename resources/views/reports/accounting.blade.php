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
        <x-adminlte-card title="{{ __('general_content.total_profit_trans_key') }}" theme="primary" icon="fas fa-chart-line" >
            {{ \Illuminate\Support\Number::currency($profit, app('Factory')->curency, config('app.locale')) }}
        </x-adminlte-card>
    </div>
</div>
<div class="mt-4">
    @livewire('fec-export-lines')
</div>
@stop