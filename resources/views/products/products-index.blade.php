@extends('adminlte::page')

@section('title', __('general_content.products_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <h1>{{ __('general_content.product_list_trans_key') }}</h1>
@stop

@php
  $reactTrans = [
    'dashboard'          => __('general_content.dashboard_trans_key'),
    'products_list'      => __('general_content.product_list_trans_key'),
    'total_products'     => __('general_content.products_trans_key'),
    'sold_products'      => __('general_content.sold_trans_key'),
    'purchased_products' => __('general_content.purchased_trans_key'),
    'products_label'     => __('general_content.products_trans_key'),
    'by_service'         => __('general_content.service_trans_key'),
    'by_family'          => __('general_content.select_family_trans_key'),
    'search'             => __('general_content.search_trans_key'),
    'code'               => 'Code',
    'label'              => __('general_content.label_trans_key'),
    'service'            => __('general_content.service_trans_key'),
    'family'             => __('general_content.select_family_trans_key'),
    'sold'               => __('general_content.sold_trans_key'),
    'not_sold'           => __('general_content.no_trans_key'),
    'purchased'          => __('general_content.purchased_trans_key'),
    'not_purchased'      => __('general_content.no_trans_key'),
    'tasks'              => __('general_content.tasks_trans_key'),
    'created_at'         => __('general_content.created_at_trans_key'),
    'no_results'         => __('general_content.no_data_trans_key'),
    'view'               => __('general_content.view_trans_key'),
    'new_product'        => __('general_content.new_product_trans_key'),
    'ind'                => __('general_content.index_trans_key'),
    'unit'               => __('general_content.unit_trans_key'),
    'tracability'        => __('general_content.tracability_trans_key'),
    'no_traceability'    => __('general_content.no_traceability_trans_key'),
    'batch_number'       => __('general_content.with_batch_number_trans_key'),
    'serial_number'      => __('general_content.with_serial_number_trans_key'),
    'purchased_price'    => __('general_content.purchased_price_trans_key'),
    'selling_price'      => __('general_content.price_trans_key'),
    'material'           => __('general_content.material_trans_key'),
    'thickness'          => __('general_content.thickness_trans_key'),
    'weight'             => __('general_content.weight_trans_key'),
    'finishing'          => __('general_content.finishing_trans_key'),
    'diameter'           => __('general_content.diameter_trans_key'),
    'diameter_oversize'  => __('general_content.diameter_oversize_trans_key'),
    'section_size'       => __('general_content.section_size_trans_key'),
    'qty_eco_min'        => __('general_content.quantite_eco_min_trans_key'),
    'qty_eco_max'        => __('general_content.quantite_eco_max_trans_key'),
    'comment'            => __('general_content.comment_trans_key'),
    'close'              => __('general_content.close_trans_key'),
    'submit'             => __('general_content.submit_trans_key'),
    'yes'                => __('general_content.yes_trans_key'),
    'no'                 => __('general_content.no_trans_key'),
    'locale'             => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="products-index-app"
  data-kpi='@json($reactKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-chart='@json($reactChart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
