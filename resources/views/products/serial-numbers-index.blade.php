@extends('adminlte::page')

@section('title', __('general_content.serial_numbers_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.serial_numbers_list_trans_key') }}</h1>
@stop

@php
  $reactTrans = [
    'dashboard'            => __('general_content.dashboard_trans_key'),
    'serial_numbers_list'  => __('general_content.serial_numbers_list_trans_key'),
    'total_serial_numbers' => __('general_content.serial_numbers_trans_key'),
    'serial_number'        => __('general_content.serial_numbers_trans_key'),
    'product'              => __('general_content.product_trans_key'),
    'order'                => __('general_content.order_trans_key'),
    'task'                 => __('general_content.task_trans_key'),
    'po_receipt'           => __('general_content.po_receipt_trans_key'),
    'status'               => __('general_content.statu_trans_key'),
    'created_at'           => __('general_content.created_at_trans_key'),
    'trace'                => __('Trace'),
    'view'                 => __('general_content.view_trans_key'),
    'search'               => __('general_content.search_trans_key'),
    'no_results'           => __('general_content.no_data_trans_key'),
    // statuses
    'undefined'            => __('general_content.undefined_trans_key'),
    'sold'                 => __('general_content.sold_trans_key'),
    'shipped'              => __('general_content.shipped_trans_key'),
    'returned'             => __('general_content.returned_trans_key'),
    'in_stock'             => __('general_content.in_stock_trans_key'),
    'locale'               => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="serial-numbers-index-app"
  data-kpi='@json($reactKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
