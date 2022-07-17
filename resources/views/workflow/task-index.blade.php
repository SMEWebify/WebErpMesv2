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
  @livewire('task-lines')
  <x-InfocalloutComponent note="You can add a generic task that is not linked to an order. These are not displayed in the workflow."  />
  <div class="card card-primary collapsed-card">
    <div class="card-header">
      <h3 class="card-title">Add generic task</h3>
      <div class="card-tools">
        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
          <i class="fas fa-plus"></i>
        </button>
        <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <div class="card-body" style="display: none;">
      <div class="row">
        @livewire('task-manage', ['idType' => 'generic', 'idPage' => null, 'idLine' => null]) 
      <!-- /.row -->
      </div>
    <!-- /.card body -->
    </div>
  <!-- /.card -->
  </div>
<!-- /.card -->
</div>
@stop

@section('css')
@stop

@section('js')
@stop