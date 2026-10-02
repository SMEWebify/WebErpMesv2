@extends('adminlte::page')

@section('title', __('adminlte::menu.methods_punch_designer_trans_key'))

@section('content_header')
    <div class="d-flex align-items-center">
        <h1 class="mb-0"><i class="fas fa-drafting-compass me-2"></i>{{ __('adminlte::menu.methods_punch_designer_trans_key') }}</h1>
        <a href="{{ route('methods.tool.catalog') }}" class="btn btn-sm btn-outline-primary ms-auto me-2">
            <i class="fas fa-book-open me-1"></i>{{ __('adminlte::menu.methods_tool_catalog_trans_key') }}
        </a>
        <a href="{{ route('methods.tool') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>{{ __('adminlte::menu.methods_tools_trans_key') }}
        </a>
    </div>
@stop

@section('content')
    <div id="punch-designer-app" data-brand="{{ $Factory->name ?: config('app.name') }}"
         data-library-url="{{ route('methods.tool.library', ['name' => 'press-brake-punches']) }}"></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
