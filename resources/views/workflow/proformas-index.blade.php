@extends('adminlte::page')

@section('title', 'Factures proforma')

@section('content_header')
  <h1>Factures proforma</h1>
@stop

@section('content')
<div
  id="proformas-index-app"
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
