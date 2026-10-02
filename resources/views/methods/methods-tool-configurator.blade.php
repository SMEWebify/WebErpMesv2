@extends('adminlte::page')

@section('title', __('adminlte::menu.methods_tool_configurator_trans_key'))

@section('content_header')
    <div class="d-flex align-items-center">
        <h1 class="mb-0"><i class="fas fa-tools me-2"></i>{{ __('adminlte::menu.methods_tool_configurator_trans_key') }}</h1>
        <a href="{{ route('methods.tool') }}" class="btn btn-sm btn-outline-secondary ms-auto">
            <i class="fas fa-arrow-left me-1"></i>{{ __('adminlte::menu.methods_tools_trans_key') }}
        </a>
    </div>
@stop

@section('content')
    <div id="tool-configurator-app"
         data-store-url="{{ route('methods.tool.configurator.store') }}"
         data-index-url="{{ route('methods.tool') }}"
         data-currency="{{ $Factory->curency }}"
         data-stock-options="{{ json_encode([
             'services'  => $StockServices,
             'families'  => $StockFamilies,
             'units'     => $StockUnits,
             'locations' => $StockLocations->map(fn ($l) => ['id' => $l->id, 'label' => $l->code . ' — ' . $l->label]),
         ]) }}"></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
