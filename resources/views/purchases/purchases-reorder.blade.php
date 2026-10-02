@extends('adminlte::page')

@section('title', 'Réapprovisionnement')

@section('content_header')
    <div class="d-flex align-items-center">
        <h1 class="mb-0"><i class="fas fa-cart-plus me-2"></i>Réapprovisionnement{{ $scope === 'tools' ? ' — outils' : '' }}</h1>
        @if($scope === 'tools' || $products)
            <a href="{{ route('methods.tool') }}" class="btn btn-sm btn-outline-secondary ms-auto">
                <i class="fas fa-arrow-left me-1"></i>{{ __('adminlte::menu.methods_tools_trans_key') }}
            </a>
        @else
            <a href="{{ route('products.stock.shortages') }}" class="btn btn-sm btn-outline-secondary ms-auto">
                <i class="fas fa-arrow-left me-1"></i>Statut du stock
            </a>
        @endif
    </div>
@stop

@section('content')
    <div id="reorder-app"
         data-endpoints="{{ json_encode([
             'json'  => route('purchases.reorder.json'),
             'store' => route('purchases.reorder.store'),
         ]) }}"
         data-scope="{{ $scope }}"
         data-products="{{ json_encode($products) }}"
         data-currency="{{ $Factory->curency }}"></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
