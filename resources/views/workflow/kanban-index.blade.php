@extends('adminlte::page')

@section('title', __('general_content.workflow_trans_key'))

@section('content_header')
<div class="row mb-2">
    <h1>{{ __('general_content.workflow_trans_key') }}</h1>
</div>
@stop

@section('right-sidebar')

@section('content')
    <x-InfocalloutComponent note="{{ __('general_content.workflow_info_1_trans_key') }}"  />
    <div id="card" class="card">
        <kanban-board :initial-data="{{ $tasks }}"></kanban-board>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/app.css">
@stop

@section('js')
    <script src="/js/app.js"></script>
@stop