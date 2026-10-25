@extends('adminlte::page')

@section('title', $dossier->code)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">{{ __('inspection.free_control') }} <small class="text-secondary">{{ $dossier->code }}</small></h1>
            <div class="text-secondary">{{ $dossier->title }}</div>
        </div>
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('quality.plans') }}">
            <i class="fas fa-arrow-left me-1"></i>{{ __('inspection.back') }}
        </a>
    </div>
@stop

@section('content')
    <div class="card mb-3">
        <div id="inspection-dossier-app" data-base-url="{{ $baseUrl }}"></div>
    </div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
