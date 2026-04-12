@extends('adminlte::page')

@section('title', __('general_content.tasks_trans_key') .''. $Document->code .' - #'. $LineInfo->id .' '. $LineInfo->label  )

@section('content_header')

<div class="row mb-2">
  <div class="col-sm-6">
    <h1>{{ __('general_content.tasks_trans_key') }} {{ $Document->code }} - #{{  $LineInfo->id }} {{  $LineInfo->label }}</h1>
  </div>
  <div class="col-sm-6">
      <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="{{ url()->previous() }}#Lines">{{ __('general_content.back_trans_key') }}</a></li>
      </ol>
  </div>
</div>

@stop

@section('right-sidebar')

@section('content')

@php
$endpoints = [
    'initial_data'          => route('task.manage.json.initial',      [$id_type, $id_page, $id_line]),
    'select_data'           => route('task.manage.json.select-data',  [$id_type, $id_page, $id_line]),
    'task_store'            => route('task.manage.json.task.store',   [$id_type, $id_page, $id_line]),
    'task_update'           => route('task.manage.json.task.update',  [$id_type, $id_page, '__ID__']),
    'task_destroy'          => route('task.manage.json.task.destroy', [$id_type, $id_page, '__ID__']),
    'task_duplicate'        => route('task.manage.json.task.duplicate', [$id_type, $id_page, '__ID__']),
    'subassembly_store'     => route('task.manage.json.subassembly.store',     [$id_type, $id_page, $id_line]),
    'subassembly_update'    => route('task.manage.json.subassembly.update',    [$id_type, $id_page, '__ID__']),
    'subassembly_destroy'   => route('task.manage.json.subassembly.destroy',   [$id_type, $id_page, '__ID__']),
    'subassembly_duplicate' => route('task.manage.json.subassembly.duplicate', [$id_type, $id_page, '__ID__']),
    'import_csv'            => route('task.manage.json.import-csv',            [$id_type, $id_page, $id_line]),
    'apply_nomenclature'    => route('task.manage.json.apply-nomenclature',    [$id_type, $id_page, $id_line, '__TPL_ID__']),
];
@endphp

<div
  id="task-manage-app"
  data-id-type="{{ $id_type }}"
  data-id-page="{{ $id_page }}"
  data-id-line="{{ $id_line }}"
  data-statu="{{ $Document->statu }}"
  data-currency="{{ $Factory->curency ?? '€' }}"
  data-endpoints="{{ json_encode($endpoints) }}"
></div>

@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
<style>
  .btn-xs { padding: 0.1rem 0.35rem; font-size: 0.72rem; line-height: 1.3; }
</style>
@stop

@section('js')
@vite(['resources/js/app.js'])
@stop