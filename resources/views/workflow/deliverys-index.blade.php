@extends('adminlte::page')

@section('title', __('general_content.deliverys_notes_list_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.deliverys_notes_list_trans_key') }}</h1>
@stop

@php
    $reactChart = [
        'deliverysDataRate'    => $data['deliverysDataRate'],
        'deliveryMonthlyRecap' => $data['deliveryMonthlyRecap'],
        'fiscalYearStartMonth' => $data['fiscalYearStartMonth'] ?? 1,
    ];

    $reactEndpoints = [
        'list'    => route('deliverys.json.list'),
        'request' => route('deliverys-request'),
    ];

    $reactTrans = [
        // statuts livraison
        'in_progress'     => __('general_content.in_progress_trans_key'),
        'send'            => __('general_content.send_trans_key'),
        // statuts facturation
        'chargeable'      => __('general_content.chargeable_trans_key'),
        'not_chargeable'  => __('general_content.not_chargeable_trans_key'),
        'partly_invoiced' => __('general_content.partly_invoiced_trans_key'),
        'invoiced'        => __('general_content.invoiced_trans_key'),
        // KPI labels
        'total'           => __('general_content.total_trans_key'),
        // colonnes
        'code'            => __('general_content.id_trans_key'),
        'label'           => __('general_content.label_trans_key'),
        'company'         => __('general_content.customer_trans_key'),
        'lines'           => __('general_content.lines_count_trans_key'),
        'status'          => __('general_content.status_trans_key'),
        'invoice_status'  => __('general_content.invoice_status_trans_key'),
        'user'            => __('general_content.user_trans_key'),
        'created_at'      => __('general_content.created_at_trans_key'),
        'action'          => __('general_content.action_trans_key'),
        // ui
        'search'          => __('general_content.search_trans_key'),
        'new_delivery'    => __('general_content.new_trans_key'),
        'no_results'      => __('general_content.no_data_trans_key'),
        'statistiques'    => __('general_content.statistiques_trans_key'),
        'monthly_recap'   => __('general_content.monthly_recap_report_trans_key'),
        'view'            => __('general_content.view_trans_key'),
        'columns'         => __('general_content.columns_trans_key'),
        // i18n
        'currency'        => app('Factory')->curency ?? 'EUR',
        'locale'          => str_replace('_', '-', config('app.locale')),
    ];
@endphp

@section('content')
<div
    id="deliverys-index-app"
    data-chart='@json($reactChart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
