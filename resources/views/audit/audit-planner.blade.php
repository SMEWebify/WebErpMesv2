@extends('adminlte::page')

@section('title', 'Planificateur d\'audits internes ISO 9001')

@section('content_header')
    <h1><i class="fas fa-clipboard-check mr-2"></i>Planificateur d'audits internes ISO 9001</h1>
@stop

@section('content')
    <div
        id="audit-planner-app"
        data-endpoints="{{ json_encode($endpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"
        data-users="{{ json_encode($users, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"
        data-processes="{{ json_encode($processes, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"
        data-checklists="{{ json_encode($checklists, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"
        data-kpi="{{ json_encode($kpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"
        data-can-admin="{{ json_encode($canAdmin) }}"
    ></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <style>
        .audit-tab-content { min-height: 500px; }
        .finding-badge-major { background-color: #dc3545; }
        .finding-badge-minor { background-color: #fd7e14; }
        .finding-badge-obs   { background-color: #17a2b8; }
        .finding-badge-pos   { background-color: #28a745; }
        .checklist-item-row:hover { background-color: #f8f9fa; }
        .schedule-card { border-left: 4px solid #dee2e6; transition: border-color .2s; }
        .schedule-card.planned      { border-left-color: #17a2b8; }
        .schedule-card.in_progress  { border-left-color: #ffc107; }
        .schedule-card.completed    { border-left-color: #28a745; }
        .schedule-card.cancelled    { border-left-color: #6c757d; }
    </style>
@stop
