@extends('adminlte::page')

@section('title', __('general_content.leads_trans_key'))

@section('content_header')
  <div class="row mb-2">
      <h1>{{ __('general_content.leads_trans_key') }}</h1>
  </div>
@stop

@section('content')

@php
$trans = [
    // statuses
    'new'         => __('general_content.new_trans_key'),
    'assigned'    => __('general_content.assigned_trans_key'),
    'in_progress' => __('general_content.in_progress_trans_key'),
    'converted'   => __('general_content.converted_trans_key'),
    'lost'        => __('general_content.lost_trans_key'),
    // priorities
    'burning'  => __('general_content.burning_trans_key'),
    'hot'      => __('general_content.hot_trans_key'),
    'lukewarm' => __('general_content.lukewarm_trans_key'),
    'cold'     => __('general_content.cold_trans_key'),
    // dashboard
    'statistics'  => __('general_content.statistiques_trans_key'),
    'by_company'  => __('general_content.opportunities_by_company_trans_key'),
    'by_priority' => __('general_content.priority_trans_key'),
    'by_user'     => __('general_content.user_trans_key'),
    'lead_count'  => __('general_content.lead_count_trans_key'),
    // table
    'company'    => __('general_content.companie_trans_key'),
    'contact'    => __('general_content.contact_name_trans_key'),
    'user'       => __('general_content.user_trans_key'),
    'source'     => __('general_content.source_trans_key'),
    'priority'   => __('general_content.priority_trans_key'),
    'campaign'   => __('general_content.campaign_trans_key'),
    'status'     => __('general_content.status_trans_key'),
    'created_at' => __('general_content.created_at_trans_key'),
    'action'     => __('general_content.action_trans_key'),
    // form
    'address_select' => __('general_content.select_address_trans_key'),
    'contact_select' => __('general_content.select_contact_trans_key'),
    'comment'        => __('general_content.comment_trans_key'),
    'close'          => __('general_content.close_trans_key'),
    'submit'         => __('general_content.submit_trans_key'),
    'new_lead'       => __('general_content.new_leads_trans_key'),
    // misc
    'dashboard' => __('general_content.dashboard_trans_key'),
    'list'      => __('general_content.list_leads_trans_key'),
    'no_data'   => __('general_content.no_data_trans_key'),
    'search'    => __('general_content.search_trans_key'),
    'page'      => 'Page',
    'locale'    => str_replace('_', '-', config('app.locale')),
];

$endpoints = [
    'list'       => route('leads.json.list'),
    'kanban'     => route('leads.json.kanban'),
    'kanbanMove' => route('leads.json.kanban-move', ['id' => '__ID__']),
    'store'      => route('leads.json.store'),
    'selectData' => route('leads.json.select-data'),
    'addresses'  => route('leads.json.addresses', ['companyId' => '__ID__']),
    'contacts'   => route('leads.json.contacts',  ['companyId' => '__ID__']),
];
@endphp

<div
  id="leads-index-app"
  data-kpi='@json($kpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-chart='@json($chart, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-by-company='@json($byCompany, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-by-priority='@json($byPriority, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-by-user='@json($byUser, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
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
