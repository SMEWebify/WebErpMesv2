@extends('adminlte::page')

@section('title', __('general_content.waiting_to_receipt_trans_key'))

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>{{ __('general_content.waiting_to_receipt_trans_key') }}</h1>
    </div>
  </div>
@stop

@section('content')
<div
  id="purchases-waiting-receipt-app"
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-initial-code='@json($initialCode, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
