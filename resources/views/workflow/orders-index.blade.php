@extends('adminlte::page')

@section('title', __('general_content.orders_list_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <h1>{{ __('general_content.orders_list_trans_key') }}</h1>
@stop

@php
  $reactKpi = [
    'deliveredOrdersPercentage' => $deliveredOrdersPercentage,
    'invoicedOrdersPercentage'  => $invoicedOrdersPercentage,
    'serviceRate'               => $serviceRate,
    'pendingDeliveries'         => $pendingDeliveries,
    'lateOrdersCount'           => $lateOrdersCount,
    'remainingDeliveryOrder'    => $remainingDeliveryOrder,
    'remainingInvoiceOrder'     => $remainingInvoiceOrder,
    'averageProcessingTime'     => $averageProcessingTime,
  ];

  $reactChart = [
    'ordersDataRate'                => $data['ordersDataRate'],
    'orderMonthlyRecap'             => $data['orderMonthlyRecap'],
    'orderMonthlyRecapPreviousYear' => $data['orderMonthlyRecapPreviousYear'],
  ];

  $reactTopCustomers = $topCustomers->map(fn($c) => [
    'order_count' => $c->order_count,
    'companie'    => $c->companie ? ['label' => $c->companie->label] : null,
  ])->values()->all();

  $reactEndpoints = [
    'list'         => route('orders.json.list'),
    'store'        => route('orders.json.store'),
    'selectData'   => route('orders.json.select-data'),
    'addresses'    => route('orders.json.addresses',      ['companyId' => '__ID__']),
    'contacts'     => route('orders.json.contacts',       ['companyId' => '__ID__']),
    'storeAddress' => route('orders.json.address.store'),
    'storeContact' => route('orders.json.contact.store'),
  ];

  $reactTrans = [
    'dashboard'                      => __('general_content.dashboard_trans_key'),
    'orders_list'                    => __('general_content.orders_list_trans_key'),
    'open'                           => __('general_content.open_trans_key'),
    'in_progress'                    => __('general_content.in_progress_trans_key'),
    'delivered'                      => __('general_content.delivered_trans_key'),
    'partly_delivered'               => __('general_content.partly_delivered_trans_key'),
    'stopped'                        => __('general_content.stopped_trans_key'),
    'canceled'                       => __('general_content.canceled_trans_key'),
    'order_delivered'                => __('general_content.order_delivered_trans_key'),
    'order_invoiced'                 => __('general_content.order_invoiced_trans_key'),
    'service_rate'                   => __('general_content.service_rate_trans_key'),
    'remaining_month'                => __('general_content.remaining_month_trans_key'),
    'remaining_invoice_month'        => __('general_content.remaining_invoice_month_trans_key'),
    'late_orders'                    => __('general_content.late_orders_trans_key'),
    'order_waiting'                  => __('general_content.order_waiting_trans_key'),
    'average_order_processing_time'  => __('general_content.average_order_processing_time_trans_key'),
    'day'                            => __('general_content.day_trans_key'),
    'monthly_recap'                  => __('general_content.monthly_recap_report_trans_key'),
    'order_forecast'                 => __('general_content.chart_order_forecast_trans_key'),
    'order_last_year'                => 'Order from last year',
    'orders_by_status'               => __('general_content.orders_trans_key'),
    'jan' => 'January',  'feb' => 'February', 'mar' => 'March',
    'apr' => 'April',    'may' => 'May',       'jun' => 'June',
    'jul' => 'July',     'aug' => 'August',    'sep' => 'September',
    'oct' => 'October',  'nov' => 'November',  'dec' => 'December',
    'new_order'              => __('general_content.new_order_trans_key'),
    'search'                 => __('general_content.search_trans_key'),
    'code'                   => 'Code',
    'label'                  => __('general_content.label_trans_key'),
    'company'                => __('general_content.name_company_trans_key'),
    'contact'                => __('general_content.contact_trans_key'),
    'address'                => __('general_content.new_address_trans_key'),
    'validity_date'          => __('general_content.validity_date_trans_key'),
    'status'                 => __('general_content.status_trans_key'),
    'lines'                  => __('general_content.order_line_trans_key'),
    'created_at'             => __('general_content.created_at_trans_key'),
    'customer_reference'     => __('general_content.customer_reference_trans_key'),
    'total_amount'           => __('general_content.total_trans_key'),
    'payment_condition'      => __('general_content.payment_conditions_trans_key'),
    'payment_method'         => __('general_content.payment_method_trans_key'),
    'delivery'               => __('general_content.delivery_constraint_trans_key'),
    'assignee'               => __('general_content.user_management_trans_key'),
    'comment'                => __('general_content.comment_trans_key'),
    'order_type'             => __('general_content.order_type_trans_key'),
    'customer_type'          => __('general_content.customer_type_order_trans_key'),
    'internal_type'          => __('general_content.internal_type_order_trans_key'),
    'orders'                 => __('general_content.orders_trans_key'),
    'page'                   => 'Page',
    'ordre'                  => __('general_content.ordre_trans_key'),
    'adress_label'           => __('general_content.adress_name_trans_key'),
    'adress'                 => __('general_content.adress_trans_key'),
    'postal_code'            => __('general_content.postal_code_trans_key'),
    'city'                   => __('general_content.city_trans_key'),
    'country'                => __('general_content.country_trans_key'),
    'phone'                  => __('general_content.phone_trans_key'),
    'email'                  => __('general_content.email_trans_key'),
    'civility'               => __('general_content.civility_trans_key'),
    'first_name'             => __('general_content.first_name_trans_key'),
    'name'                   => __('general_content.name_trans_key'),
    'function'               => __('general_content.function_trans_key'),
    'mobile'                 => __('general_content.mobile_phone_trans_key'),
    'new_address'            => __('general_content.new_address_trans_key'),
    'new_contact'            => __('general_content.new_companie_trans_key'),
    'save'                   => __('general_content.save_trans_key'),
    'saving'                 => __('general_content.saving_trans_key'),
    'cancel'                 => __('general_content.cancel_trans_key'),
    'no_results'             => __('general_content.no_results_trans_key'),
    'total'                  => __('general_content.total_trans_key'),
    'currency'               => app('Factory')->curency ?? 'EUR',
    'locale'                 => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="orders-index-app"
  data-kpi='@json($reactKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-chart='@json($reactChart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-top-customers='@json($reactTopCustomers, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
