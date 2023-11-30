@extends('adminlte::page')

@section('title', __('general_content.tasks_trans_key') .''. $Document->code .' - #'. $LineInfo->id .' '. $LineInfo->label  )

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>{{ __('general_content.tasks_trans_key') }} {{ $Document->code }} - #{{  $LineInfo->id }} {{  $LineInfo->label }}</h1>
      </div>
      <div class="col-sm-6">
        <a class="btn btn-primary btn-sm" href="{{ url()->previous() }}#Lines">
          <button type="button" class="btn btn-primary float-sm-right">
            {{ __('general_content.back_trans_key') }} 
          </button>
        </a>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @livewire('task-manage', ['idType' => $id_type, 'idPage' => $id_page, 'idLine' => $id_line,'statu' => $Document->statu ])
</div>
<!-- /.card -->
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