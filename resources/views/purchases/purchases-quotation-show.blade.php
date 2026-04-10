@extends('adminlte::page')

@section('title', __('general_content.requests_for_quotation_list_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <x-Content-header-previous-button
    h1="{{ __('general_content.requests_for_quotation_list_trans_key')}} : {{ $PurchaseQuotation->code }}"
    previous="{{ $previousUrl }}"
    list="{{ route('purchases.quotation') }}"
    next="{{ $nextUrl }}"
  />
@stop

@section('right-sidebar')

@section('content')
<div
    id="purchases-quotation-show-app"
    data-quotation="{{ json_encode($reactProps['quotation']) }}"
    data-suppliers="{{ json_encode($reactProps['suppliers']) }}"
    data-addresses="{{ json_encode($reactProps['addresses']) }}"
    data-contacts="{{ json_encode($reactProps['contacts']) }}"
    data-currency="{{ $reactProps['currency'] }}"
    data-endpoints="{{ json_encode($reactEndpoints) }}"
    data-trans="{{ json_encode($reactTrans) }}"
></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
