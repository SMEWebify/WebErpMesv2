@extends('adminlte::page')

@section('title', __('inspection.title'))

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1>{{ __('inspection.title') }}</h1>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('quality.inspection.projects') }}">
            <i class="fas fa-clock-rotate-left me-1"></i>{{ __('inspection.old_screen') }}
        </a>
    </div>
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
