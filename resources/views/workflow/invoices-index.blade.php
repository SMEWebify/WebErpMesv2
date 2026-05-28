@extends('adminlte::page')

@section('title', __('general_content.invoices_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.invoices_list_trans_key') }}</h1>
@stop

@php
  $reactTrans = [
    'dashboard'          => __('general_content.dashboard_trans_key'),
    'invoices_list'      => __('general_content.invoices_list_trans_key'),
    'draft'              => 'Brouillon',
    'in_progress'        => __('general_content.in_progress_trans_key'),
    'send'               => __('general_content.send_trans_key'),
    'pending'            => __('general_content.pending_trans_key'),
    'unpaid'             => __('general_content.unpaid_trans_key'),
    'paid'               => __('general_content.paid_trans_key'),
    'statistiques'       => __('general_content.statistiques_trans_key'),
    'monthly_recap'      => __('general_content.monthly_recap_report_trans_key'),
    'invoices_label'     => __('general_content.invoices_list_trans_key'),
    'total_invoiced'     => __('general_content.amount_of_invoice_trans_key'),
    'payment_rate'       => __('general_content.bills_paid_trans_key'),
    'unpaid_invoices'    => __('general_content.bills_unpaid_trans_key'),
    'late'               => __('general_content.late_payment_time_trans_key'),
    'invoice_forecast'   => __('general_content.invoices_list_trans_key'),
    'invoice_last_year'  => 'N-1',
    'jan' => 'January',  'feb' => 'February', 'mar' => 'March',
    'apr' => 'April',    'may' => 'May',       'jun' => 'June',
    'jul' => 'July',     'aug' => 'August',    'sep' => 'September',
    'oct' => 'October',  'nov' => 'November',  'dec' => 'December',
    'search'     => __('general_content.search_trans_key'),
    'code'       => 'Code',
    'client'     => __('general_content.customer_trans_key'),
    'label'      => __('general_content.label_trans_key'),
    'contact'    => __('general_content.contact_trans_key'),
    'due_date'   => __('general_content.due_date_trans_key'),
    'status'     => __('general_content.status_trans_key'),
    'lines'      => __('general_content.lines_count_trans_key'),
    'created_at' => __('general_content.created_at_trans_key'),
    'total'      => __('general_content.total_price_trans_key'),
    'no_results' => __('general_content.no_data_trans_key'),
    'view'       => __('general_content.view_trans_key'),
    'currency'   => app('Factory')->curency ?? 'EUR',
    'locale'     => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="invoices-index-app"
  data-kpi='@json($reactKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-chart='@json($reactChart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-top-clients='@json($reactTopClients, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
