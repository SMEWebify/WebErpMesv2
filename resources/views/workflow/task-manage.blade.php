@extends('adminlte::page')

@section('title', 'Task for ' . $Document->code .' - Line #'. $LineInfo->id .' '. $LineInfo->label  )

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Task for {{ $Document->code }} - Line #{{  $LineInfo->id }} {{  $LineInfo->label }}</h1>
      </div>
      <div class="col-sm-6">
        <a class="btn btn-primary btn-sm" href="{{ url()->previous() }}#Lines">
          <button type="button" class="btn btn-primary float-sm-right">
            Back
          </button>
        </a>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @livewire('task-manage', ['idType' => $id_type, 'idPage' => $id_page, 'idLine' => $id_line])
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop