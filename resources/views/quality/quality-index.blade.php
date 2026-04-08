@extends('adminlte::page')

@section('title', __('general_content.quality_trans_key'))

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ __('general_content.quality_trans_key') }}</h1>
    </div>
@stop

@section('content')
<div
    id="quality-index-app"
    data-kpi='@json($reactKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-rates='@json($reactRates, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-status-counts='@json($reactStatusCounts, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-top-generators='@json($reactTopGenerators, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-calibration-alerts='@json($reactCalibrationAlerts, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-initial-failures='@json($reactInitialFailures, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-initial-causes='@json($reactInitialCauses, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-initial-corrections='@json($reactInitialCorrections, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
