@extends('adminlte::page')

@section('title', 'Companies')

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Companies list</h1>
      </div>
      <div class="col-sm-6">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalCompanie">
          New companie
        </button>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')
  <div class="card">
      @livewire('companies-lines')
  </div>
  <!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop