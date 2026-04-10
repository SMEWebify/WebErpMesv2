@extends('adminlte::page')

@section('title', __('general_content.gmao_dashboard_page_title_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.gmao_dashboard_page_title_trans_key') }}</h1>
@stop

@section('content')
    <div
        id="gmao-dashboard-app"
        data-kpis="{{ json_encode($kpis) }}"
        data-work-orders-count="{{ json_encode($workOrdersCount) }}"
        data-recent-work-orders="{{ json_encode($recentWorkOrders) }}"
        data-maintenance-plans-count="{{ $maintenancePlansCount }}"
        data-endpoints="{{ json_encode($endpoints) }}"
    ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
