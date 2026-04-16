@extends('adminlte::page')

@section('title', __('general_content.estimated_budget_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.estimated_budget_trans_key') }}</h1>
@stop

@section('content')
<div
    id="estimated-budgets-app"
    data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
    data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
