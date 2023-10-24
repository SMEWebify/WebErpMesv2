@extends('adminlte::page')

@section('title', __('general_content.tasks_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.tasks_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="card-body">
  @livewire('task-lines')
  <x-InfocalloutComponent note="{{ __('general_content.tasks_info_1_trans_key') }}"  />
  <div class="card card-primary collapsed-card">
    <div class="card-header">
      <h3 class="card-title">{{ __('general_content.add_generic_task_trans_key') }}</h3>
      <div class="card-tools">
        <button type="button" class="btn btn-tool" data-card-widget="collapse" title="{{ __('general_content.collapse_trans_key') }}">
          <i class="fas fa-plus"></i>
        </button>
        <button type="button" class="btn btn-tool" data-card-widget="remove" title="{{ __('general_content.remove_trans_key') }}">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    <div class="card-body" style="display: none;">
      <div class="row">
        <div class="col-12">
          @livewire('task-manage', ['idType' => 'generic', 'idPage' => null, 'idLine' => null, 'statu' => 1])
        </div> 
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