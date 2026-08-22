@extends('adminlte::page')

@section('title', __('general_content.inventories_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.inventories_trans_key') }}</h1>
@stop

@php
  $reactEndpoints = [
    'indexJson' => route('products.inventories.index.json'),
    'store'     => route('products.inventories.store'),
    'show'      => url('/' . app()->getLocale() . '/products/inventories'),
    'cancel'    => url('/' . app()->getLocale() . '/products/inventories'),
  ];

  $reactTrans = [
    'title'              => __('general_content.inventories_trans_key'),
    'new_inventory'      => __('general_content.new_inventory_trans_key'),
    'code'               => 'Code',
    'label'              => __('general_content.label_trans_key'),
    'scope'              => __('general_content.scope_trans_key'),
    'scope_all'          => __('general_content.scope_all_trans_key'),
    'scope_location'     => __('general_content.scope_location_trans_key'),
    'scope_category'     => __('general_content.scope_category_trans_key'),
    'status'             => __('general_content.status_trans_key'),
    'status_draft'       => __('general_content.status_draft_trans_key'),
    'status_exported'    => __('general_content.status_exported_trans_key'),
    'status_validated'   => __('general_content.status_validated_trans_key'),
    'status_cancelled'   => __('general_content.status_cancelled_trans_key'),
    'created_at'         => __('general_content.created_at_trans_key'),
    'created_by'         => __('general_content.created_by_trans_key'),
    'validated_at'       => __('general_content.validated_at_trans_key'),
    'actions'            => __('general_content.actions_trans_key'),
    'view'               => __('general_content.view_trans_key'),
    'no_results'         => __('general_content.no_data_trans_key'),
    'create'             => __('general_content.create_trans_key'),
    'cancel'             => __('general_content.cancel_trans_key'),
    'confirm_cancel'     => __('general_content.confirm_cancel_trans_key'),
    'pause_moves_warning'=> __('general_content.inventory_pause_moves_warning_trans_key'),
    'select_locations'   => __('general_content.select_locations_trans_key'),
    'select_categories'  => __('general_content.select_categories_trans_key'),
    'locale'             => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="inventories-index-app"
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-stock-locations='@json($stockLocations, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-categories='@json($categories, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
