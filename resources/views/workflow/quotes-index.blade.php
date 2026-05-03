@extends('adminlte::page')

@section('title', __('general_content.quotes_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.quotes_list_trans_key') }}</h1>
@stop

@php
  $reactKpi = [
    'averageAmount'  => $averageAmount,
    'conversionRate' => $conversionRate,
    'responseRate'   => $responseRate,
  ];

  $reactChart = [
    'quotesDataRate'                => $data['quotesDataRate'],
    'quoteMonthlyRecap'             => $data['quoteMonthlyRecap'],
    'quoteMonthlyRecapPreviousYear' => $data['quoteMonthlyRecapPreviousYear'],
  ];

  $reactTopCustomers = $topCustomers->map(fn($c) => [
    'quote_count' => $c->quote_count,
    'companie'    => $c->companie ? ['label' => $c->companie->label] : null,
  ])->values()->all();

  $reactEndpoints = [
    'list'       => route('quotes.json.list'),
    'store'      => route('quotes.json.store'),
    'selectData' => route('quotes.json.select-data'),
    'addresses'      => route('quotes.json.addresses',    ['companyId' => '__ID__']),
    'contacts'       => route('quotes.json.contacts',     ['companyId' => '__ID__']),
    'storeAddress'   => route('quotes.json.address.store'),
    'storeContact'   => route('quotes.json.contact.store'),
  ];

  $reactTrans = [
    'dashboard'             => __('general_content.dashboard_trans_key'),
    'quotes_list'           => __('general_content.quotes_list_trans_key'),
    'open'                  => __('general_content.open_trans_key'),
    'send'                  => __('general_content.send_trans_key'),
    'win'                   => __('general_content.win_trans_key'),
    'lost'                  => __('general_content.lost_trans_key'),
    'closed'                => __('general_content.closed_trans_key'),
    'obsolete'              => __('general_content.obsolete_trans_key'),
    'statistiques'          => __('general_content.statistiques_trans_key'),
    'monthly_recap'         => __('general_content.monthly_recap_report_trans_key'),
    'average_quote_amount'  => __('general_content.average_quote_amount'),
    'quote_conversion_rate' => __('general_content.quote_conversion_rate'),
    'quote_response_rate'   => __('general_content.quote_response_rate'),
    'quote_trans'           => __('general_content.quote_trans_key'),
    'quote_forecast'        => 'Quote forecast',
    'quote_last_year'       => 'Quote from last year',
    'jan' => 'January',  'feb' => 'February', 'mar' => 'March',
    'apr' => 'April',    'may' => 'May',       'jun' => 'June',
    'jul' => 'July',     'aug' => 'August',    'sep' => 'September',
    'oct' => 'October',  'nov' => 'November',  'dec' => 'December',
    'new_quote'             => __('general_content.new_quote_trans_key'),
    'search'                => __('general_content.search_trans_key'),
    'code'                  => 'Code',
    'client'                => __('general_content.client_trans_key'),
    'external_id'           => __('general_content.external_id_trans_key'),
    'label'                 => __('general_content.label_trans_key'),
    'company'               => __('general_content.name_company_trans_key'),
    'contact'               => __('general_content.contact_trans_key'),
    'address'               => __('general_content.new_address_trans_key'),
    'validity_date'         => __('general_content.validity_date_trans_key'),
    'status'                => __('general_content.status_trans_key'),
    'lines'                 => __('general_content.quote_line_trans_key'),
    'created_at'            => __('general_content.created_at_trans_key'),
    'customer_reference'    => __('general_content.customer_reference_trans_key'),
    'payment_condition'     => __('general_content.payment_conditions_trans_key'),
    'payment_method'        => __('general_content.payment_method_trans_key'),
    'delivery'              => __('general_content.delivery_constraint_trans_key'),
    'assignee'              => __('general_content.user_management_trans_key'),
    'comment'               => __('general_content.comment_trans_key'),
    'ordre'            => __('general_content.ordre_trans_key'),
    'adress_label'     => __('general_content.adress_name_trans_key'),
    'adress'           => __('general_content.adress_trans_key'),
    'postal_code'      => __('general_content.postal_code_trans_key'),
    'city'             => __('general_content.city_trans_key'),
    'country'          => __('general_content.country_trans_key'),
    'phone'            => __('general_content.phone_trans_key'),
    'email'            => __('general_content.email_trans_key'),
    'civility'         => __('general_content.civility_trans_key'),
    'first_name'       => __('general_content.first_name_trans_key'),
    'name'             => __('general_content.name_trans_key'),
    'function'         => __('general_content.function_trans_key'),
    'mobile'           => __('general_content.mobile_phone_trans_key'),
    'new_address'      => __('general_content.new_address_trans_key'),
    'new_contact'      => __('general_content.new_companie_trans_key'),
    'save'             => __('general_content.save_trans_key'),
    'saving'     => __('general_content.saving_trans_key'),
    'cancel'     => __('general_content.cancel_trans_key'),
    'no_results' => __('general_content.no_results_trans_key'),
    'total'      => __('general_content.total_trans_key'),
    'currency'   => app('Factory')->curency ?? 'EUR',
    'locale'     => str_replace('_', '-', config('app.locale')),
  ];
@endphp

@section('content')
<div
  id="quotes-index-app"
  data-kpi='@json($reactKpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-chart='@json($reactChart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-top-customers='@json($reactTopCustomers, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-quotes-by-user='@json($quotesCountByUser, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
<style>
  .quote-kpi-item { background-color: #f8f9fa; }
  .quote-kpi-item .badge { font-size: 0.8rem; white-space: normal; text-align: left; }
</style>
@stop
