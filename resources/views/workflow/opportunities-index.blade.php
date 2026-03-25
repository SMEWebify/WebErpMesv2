@extends('adminlte::page')

@section('title', __('general_content.opportunities_trans_key'))

@section('content_header')
  <div class="row mb-2">
      <h1>{{ __('general_content.opportunities_trans_key') }}</h1>
  </div>
@stop

@section('content')

@php
$trans = [
    // statuses
    'new'           => __('general_content.new_trans_key'),
    'quote_made'    => __('general_content.quote_made_trans_key'),
    'negotiation'   => __('general_content.negotiation_trans_key'),
    'closed_won'    => __('general_content.closed_won_trans_key'),
    'closed_lost'   => __('general_content.closed_lost_trans_key'),
    'informational' => __('general_content.informational_trans_key'),
    // dashboard
    'statistics'        => __('general_content.statistiques_trans_key'),
    'recent_activities' => __('general_content.logs_activity_trans_key'),
    'by_company'        => __('general_content.opportunities_by_company_trans_key'),
    'by_amount'         => __('general_content.opportunities_by_probability_trans_key'),
    'total_won'         => __('general_content.total_amount_won_trans_key'),
    'total_lost'        => __('general_content.total_amount_lost_trans_key'),
    'count'             => __('general_content.opportunities_count_trans_key'),
    // table
    'label'               => __('general_content.label_trans_key'),
    'company'             => __('general_content.companie_trans_key'),
    'company_name'        => __('general_content.companie_name_trans_key'),
    'status'              => __('general_content.status_trans_key'),
    'user'                => __('general_content.user_trans_key'),
    'created_at'          => __('general_content.created_at_trans_key'),
    'probability'         => __('general_content.probality_trans_key'),
    'budget'              => __('general_content.budget_trans_key'),
    'amount'              => __('general_content.amount_trans_key'),
    'action'              => __('general_content.action_trans_key'),
    'opportunities_count' => __('general_content.opportunities_count_trans_key'),
    // form
    'opportunity_name' => __('general_content.name_opportunity_trans_key'),
    'address_select'   => __('general_content.select_address_trans_key'),
    'contact_select'   => __('general_content.select_contact_trans_key'),
    'comment'          => __('general_content.comment_trans_key'),
    'close'            => __('general_content.close_trans_key'),
    'submit'           => __('general_content.submit_trans_key'),
    'new_opportunity'  => __('general_content.new_opportunities_trans_key'),
    // misc
    'dashboard' => __('general_content.dashboard_trans_key'),
    'list'      => __('general_content.opportunities_list_trans_key'),
    'no_data'   => __('general_content.no_data_trans_key'),
    'search'    => __('general_content.search_trans_key'),
    'page'      => 'Page',
    'currency'  => app('Factory')->curency ?? 'EUR',
    'locale'    => str_replace('_', '-', config('app.locale')),
];

$endpoints = [
    'list'       => route('opportunities.json.list'),
    'kanban'     => route('opportunities.json.kanban'),
    'kanbanMove' => route('opportunities.json.kanban-move', ['id' => '__ID__']),
    'store'      => route('opportunities.json.store'),
    'selectData' => route('opportunities.json.select-data'),
    'addresses'  => route('opportunities.json.addresses', ['companyId' => '__ID__']),
    'contacts'   => route('opportunities.json.contacts',  ['companyId' => '__ID__']),
];
@endphp

<div
  id="opportunities-index-app"
  data-kpi='@json($kpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-chart='@json($chart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-activities='@json($activities, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-by-company='@json($byCompany, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-by-amount='@json($byAmount, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-endpoints='@json($endpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($trans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
></div>

@stop

@section('css')
@stop

@section('js')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
