@extends('adminlte::page')

@section('title', 'Nouvelle facture proforma')

@section('content_header')
  <h1>Nouvelle facture proforma</h1>
@stop

@section('content')
<div
  id="proformas-request-app"
  data-code="{{ $reactProps['code'] }}"
  data-user-id="{{ $reactProps['userId'] }}"
  data-users='@json($reactProps['users'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-companies='@json($reactProps['companies'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
