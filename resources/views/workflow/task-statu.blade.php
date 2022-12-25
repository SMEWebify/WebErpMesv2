@extends('adminlte::page')

@section('title', 'Task list')

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Task list</h1>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card-body">

  @livewire('task-statu')

</div>
@stop

@section('css')
@stop

@section('js')
@stop