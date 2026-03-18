@extends('adminlte::page')

@section('title', __('general_content.orders_lines_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.orders_lines_list_trans_key') }}</h1>
@stop

@php
  $reactEndpoints = [
    'list' => route('order-lines.json.list'),
  ];

  $reactTrans = [
    'order'                    => __('general_content.order_trans_key'),
    'ordre'                    => __('general_content.sort_trans_key'),
    'code'                     => __('general_content.id_trans_key'),
    'product'                  => __('general_content.product_trans_key'),
    'label'                    => __('general_content.label_trans_key'),
    'qty'                      => __('general_content.qty_trans_key'),
    'unit'                     => __('general_content.unit_trans_key'),
    'price'                    => __('general_content.price_trans_key'),
    'discount'                 => __('general_content.discount_trans_key'),
    'vat'                      => __('general_content.vat_trans_key'),
    'delivery_date'            => __('general_content.delivery_date_trans_key'),
    'tasks_status'             => __('general_content.tasks_status_trans_key'),
    'delivery_status'          => __('general_content.delivery_status_trans_key'),
    'invoice_status'           => __('general_content.invoice_status_trans_key'),
    'task'                     => __('general_content.task_trans_key'),
    'tasks'                    => __('general_content.tasks_trans_key'),
    'action'                   => __('general_content.action_trans_key'),
    'search'                   => __('general_content.search_trans_key'),
    'no_results'               => __('general_content.no_data_trans_key'),
    'total'                    => __('general_content.total_trans_key'),
    'view_order'               => __('general_content.order_trans_key'),
    'calculated_price'         => 'Prix calculé',
    // tasks_status
    'no_task'                  => __('general_content.no_task_trans_key'),
    'created'                  => __('general_content.created_trans_key'),
    'in_progress'              => __('general_content.in_progress_trans_key'),
    'finished_task'            => __('general_content.finished_task_trans_key'),
    // delivery_status
    'not_delivered'            => __('general_content.not_delivered_trans_key'),
    'partly_delivered'         => __('general_content.partly_delivered_trans_key'),
    'delivered'                => __('general_content.delivered_trans_key'),
    'delivered_without_dn'     => __('general_content.delivered_without_dn_trans_key'),
    'partly_stored'            => __('general_content.partly_stored_trans_key'),
    'stock'                    => __('general_content.stock_trans_key'),
    // invoice_status
    'not_invoiced'             => __('general_content.not_invoiced_trans_key'),
    'partly_invoiced'          => __('general_content.partly_invoiced_trans_key'),
    'invoiced'                 => __('general_content.invoiced_trans_key'),
    'currency'                 => app('Factory')->curency ?? 'EUR',
    'locale'                   => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="order-lines-index-app"
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
