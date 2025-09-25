@extends('adminlte::page')

@section('title', __('general_content.reports_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.reports_trans_key') }}</h1>
@stop

@section('content')
<div class="row">
    <div class="col-lg-4">
        <x-adminlte-small-box title="{{ $deliveredOrdersPercentage }}%"
                              text="{{ __('general_content.order_delivered_trans_key') }}"
                              icon="fas fa-shipping-fast" theme="success" />
    </div>
    <div class="col-lg-4">
        <x-adminlte-small-box title="{{ $invoicedOrdersPercentage }}%"
                              text="{{ __('general_content.order_invoiced_trans_key') }}"
                              icon="fas fa-file-invoice-dollar" theme="info" />
    </div>
    <div class="col-lg-4">
        <x-adminlte-small-box title="{{ $serviceRate }}%"
                              text="{{ __('general_content.service_rate_trans_key') }}"
                              icon="fas fa-chart-line" theme="primary" />
    </div>
</div>

<div class="row mt-4">
    <div class="col-lg-4">
        <x-adminlte-small-box title="{{ number_format($averageQuoteAmount,2) }}"
                              text="{{ __('general_content.average_quote_amount') }}"
                              icon="fas fa-file-signature" theme="teal" />
    </div>
    <div class="col-lg-4">
        <x-adminlte-small-box title="{{ $conversionRate }}%"
                              text="{{ __('general_content.quote_conversion_rate') }}"
                              icon="fas fa-exchange-alt" theme="warning" />
    </div>
    <div class="col-lg-4">
        <x-adminlte-small-box title="{{ $responseRate }}%"
                              text="{{ __('general_content.quote_response_rate') }}"
                              icon="fas fa-chart-pie" theme="purple" />
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <x-adminlte-card title="{{ __('general_content.top_customers_trans_key') }}" theme="secondary" maximizable>
            <ul class="list-group list-group-flush">
                @foreach($topOrderCustomers as $customer)
                    <li class="list-group-item">
                        <strong>{{ $customer->companie->label ?? 'Internal' }}</strong> - {{ $customer->order_count }}
                    </li>
                @endforeach
            </ul>
        </x-adminlte-card>
    </div>
    <div class="col-md-6">
        <x-adminlte-card title="{{ __('general_content.top_customers_trans_key') }} ({{ __('general_content.quote_trans_key') }})" theme="secondary" maximizable>
            <ul class="list-group list-group-flush">
                @foreach($topQuoteCustomers as $customer)
                    <li class="list-group-item">
                        <strong>{{ $customer->companie->label ?? 'Internal' }}</strong> - {{ $customer->quote_count }}
                    </li>
                @endforeach
            </ul>
        </x-adminlte-card>
    </div>
</div>
@stop