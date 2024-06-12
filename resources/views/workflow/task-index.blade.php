@extends('adminlte::page')

@section('title', __('general_content.tasks_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.tasks_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @livewire('task-lines')
  <x-InfocalloutComponent note="{{ __('general_content.tasks_info_1_trans_key') }}"  />
  <x-adminlte-card title="{{ __('general_content.add_generic_task_trans_key') }}" theme="primary" body-class="bg-white" theme-mode="full" footer-class="bg-white border-top rounded border-primary" collapsible removable maximizable>
    <div class="row">
      <div class="col-12">
        @livewire('task-manage', ['idType' => 'generic', 'idPage' => null, 'idLine' => null, 'statu' => 1])
      </div> 
    <!-- /.row -->
    </div>
  </x-adminlte-card>
@stop

@section('css')
<style>
  /* Custom CSS to make the tooltip background transparent */
  .tooltip-inner {
      background-color: rgba(0, 0, 0, 0.1); 
      color: #fff;
  }
</style>
@stop

@section('js')
<script>
  $(document).ready(function(){
      $('[data-toggle="tooltip"]').tooltip();   
  });
</script>
@stop