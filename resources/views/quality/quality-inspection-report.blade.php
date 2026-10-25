@extends('adminlte::page')

@section('title', __('inspection.report_title') . ' — ' . $dossier->code)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">{{ __('inspection.report_title') }} <small class="text-secondary">{{ $dossier->code }}</small></h1>
            <div class="text-secondary">{{ $dossier->title }}</div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('quality.plans') }}">
            <i class="fas fa-arrow-left me-1"></i>{{ __('inspection.back') }}
        </a>
    </div>
@stop

@section('content')
    <div
        id="inspection-report-app"
        data-dossier-id="{{ $dossier->id }}"
        data-endpoints='@json($endpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
        data-trans='@json($trans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
    </div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
