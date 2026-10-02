@extends('adminlte::page')

@section('title', 'Statut du stock')

@section('content_header')
    <div class="d-flex align-items-center">
        <h1 class="mb-0">Statut du stock</h1>
        <a href="{{ route('purchases.reorder') }}" class="btn btn-sm btn-warning ms-auto">
            <i class="fas fa-cart-plus me-1"></i>Réapprovisionner
        </a>
    </div>
@stop

@section('content')
  @include('include.alert-result')

  <div id="stock-shortages-app"
       data-endpoints="{{ json_encode([
           'shortages' => route('products.stock.shortages.json'),
           'product'   => url('/'.app()->getLocale().'/products'),
           'task'      => url('/'.app()->getLocale().'/task'),
       ]) }}"
  ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
