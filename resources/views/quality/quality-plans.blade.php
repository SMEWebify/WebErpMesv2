@extends('adminlte::page')

@section('title', __('inspection.title'))

@section('content_header')
    <h1>{{ __('inspection.title') }}</h1>
@stop

@section('content')
    <div
        id="quality-plans-app"
        data-endpoints='@json($endpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
        data-trans='@json($trans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
        data-can-edit='@json($canEdit)'>
    </div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
