@extends('adminlte::page')

@section('title', __('general_content.quotes_lines_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.quotes_lines_list_trans_key') }}</h1>
@stop

@php
  $reactEndpoints = [
    'list' => route('quote-lines.json.list'),
  ];

  $reactTrans = [
    'quote'                => __('general_content.quote_trans_key'),
    'ordre'                => __('general_content.sort_trans_key'),
    'code'                 => __('general_content.id_trans_key'),
    'product'              => __('general_content.product_trans_key'),
    'label'                => __('general_content.label_trans_key'),
    'qty'                  => __('general_content.qty_trans_key'),
    'unit'                 => __('general_content.unit_trans_key'),
    'price'                => __('general_content.price_trans_key'),
    'discount'             => __('general_content.discount_trans_key'),
    'vat'                  => __('general_content.vat_trans_key'),
    'delivery_date'        => __('general_content.delivery_date_trans_key'),
    'status'               => __('general_content.status_trans_key'),
    'action'               => __('general_content.action_trans_key'),
    'task'                 => __('general_content.task_trans_key'),
    'tasks'                => __('general_content.tasks_trans_key'),
    'search'               => __('general_content.search_trans_key'),
    'no_results'           => __('general_content.no_data_trans_key'),
    'total'                => __('general_content.total_trans_key'),
    'view_quote'           => __('general_content.quote_trans_key'),
    'calculated_price'     => 'Prix calculé',
    // quote line statuses
    'open'                 => __('general_content.open_trans_key'),
    'send'                 => __('general_content.send_trans_key'),
    'win'                  => __('general_content.win_trans_key'),
    'lost'                 => __('general_content.lost_trans_key'),
    'closed'               => __('general_content.closed_trans_key'),
    'obsolete'             => __('general_content.obsolete_trans_key'),
    'currency'             => app('Factory')->curency ?? 'EUR',
    'locale'               => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="quote-lines-index-app"
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
