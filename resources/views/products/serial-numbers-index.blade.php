@extends('adminlte::page')

@section('title', __('general_content.serial_numbers_trans_key'))

@section('content_header')
  <h1>{{__('general_content.serial_numbers_list_trans_key')}}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#SerialNumbers" data-toggle="tab">{{ __('general_content.serial_numbers_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Logs" data-toggle="tab">{{ __('general_content.logs_activity_trans_key') }}</a></li>
    </ul>
  </div>
  <div class="card-body">
    <div class="tab-content">
      <div class="active tab-pane" id="SerialNumbers">
        @livewire('serial-numbers-index')
      </div>
      <div class="tab-pane" id="Logs">
        @livewire('logs-viewer', ['subjectType' => 'App\\Models\\Products\\SerialNumbers'])
      </div>
    </div>
  </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop
