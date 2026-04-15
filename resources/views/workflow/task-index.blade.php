@extends('adminlte::page')

@section('title', __('general_content.tasks_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.tasks_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  <div id="tasks-index-app"
       data-tasks='@json($tasksData, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-services='@json($services, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-statuses='@json($statuses, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-resources='@json($resources, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-default-status-ids='@json($defaultStatusIds, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-trans='@json($trans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
  </div>

  <x-InfocalloutComponent note="{{ __('general_content.tasks_info_1_trans_key') }}"  />
  <x-adminlte-card title="{{ __('general_content.add_generic_task_trans_key') }}" theme="primary" body-class="bg-white" theme-mode="full" footer-class="bg-white border-top rounded border-primary" collapsible removable maximizable>
    <div class="row">
      <div class="col-12">
        @livewire('task-manage', ['idType' => 'generic', 'idPage' => null, 'idLine' => null, 'statu' => 1])
      </div>
    </div>
  </x-adminlte-card>
@stop

@section('css')
  @viteReactRefresh
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
  <style>
    .tooltip-inner {
        background-color: rgba(0, 0, 0, 0.1);
        color: #fff;
    }
  </style>
@stop

