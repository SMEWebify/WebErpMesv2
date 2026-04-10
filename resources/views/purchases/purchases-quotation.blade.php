@extends('adminlte::page')

@section('title', __('general_content.requests_for_quotation_list_trans_key'))

@section('content_header')
  <div class="row mb-2">
      <h1>{{ __('general_content.requests_for_quotation_list_trans_key') }}</h1>
  </div>
@stop

@section('right-sidebar')

@section('content')
<div
    id="purchases-quotation-index-app"
    data-endpoints="{{ json_encode($reactEndpoints) }}"
    data-trans="{{ json_encode($reactTrans) }}"
    data-kpi="{{ json_encode($reactProps['kpi']) }}"
></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
