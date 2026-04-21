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
        @php
        $genericEndpoints = [
            'initial_data'          => route('task.manage.json.initial',      ['generic', 0, 0]),
            'select_data'           => route('task.manage.json.select-data',  ['generic', 0, 0]),
            'task_store'            => route('task.manage.json.task.store',   ['generic', 0, 0]),
            'task_update'           => route('task.manage.json.task.update',  ['generic', 0, '__ID__']),
            'task_destroy'          => route('task.manage.json.task.destroy', ['generic', 0, '__ID__']),
            'task_duplicate'        => route('task.manage.json.task.duplicate', ['generic', 0, '__ID__']),
            'subassembly_store'     => route('task.manage.json.subassembly.store',     ['generic', 0, 0]),
            'subassembly_update'    => route('task.manage.json.subassembly.update',    ['generic', 0, '__ID__']),
            'subassembly_destroy'   => route('task.manage.json.subassembly.destroy',   ['generic', 0, '__ID__']),
            'subassembly_duplicate' => route('task.manage.json.subassembly.duplicate', ['generic', 0, '__ID__']),
            'import_csv'            => route('task.manage.json.import-csv',            ['generic', 0, 0]),
            'apply_nomenclature'    => route('task.manage.json.apply-nomenclature',    ['generic', 0, 0, '__TPL_ID__']),
        ];
        @endphp
        <div
          id="task-manage-app"
          data-id-type="generic"
          data-id-page="0"
          data-id-line="0"
          data-statu="1"
          data-currency="{{ app('Factory')->curency ?? '€' }}"
          data-endpoints="{{ json_encode($genericEndpoints) }}"
        ></div>
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

