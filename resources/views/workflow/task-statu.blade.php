@extends('adminlte::page')

@section('title', __('general_content.tasks_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.tasks_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="card-body">
  @livewire('task-statu', ['id' =>$TaskId])

  @livewire('chatlive', ['idItem' => $TaskId, 'Class' => 'Task'])
</div>
@stop

@section('css')
@stop

@section('js')
@stop