@extends('adminlte::page')

@section('title', __('inspection.bubbler_title'))

@section('content')
    <div class="pt-2"
        id="plan-bubbler-app"
        data-plan-id="{{ $planId }}"
        data-endpoints='@json($endpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
        data-trans='@json($trans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
        data-can-edit='@json($canEdit)'
        data-can-approve='@json($canApprove)'
        data-ai-reading='@json($aiReading)'>
    </div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
