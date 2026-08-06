@extends('adminlte::page')

@section('title', 'Statut du stock')

@section('content_header')
    <h1>Statut du stock</h1>
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
