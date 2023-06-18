@extends('adminlte::page')

@section('title', 'Leads')

@section('content_header')
<div class="row mb-2">
  <div class="col-sm-6">
      <h1>Leads list</h1>
  </div>
  <div class="col-sm-6">
      <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalLead">
          New Lead
      </button>
  </div>
</div>
@stop

@section('right-sidebar')

@section('content')

<div class="card-body">

  @livewire('leads-index')
</div>
@stop

@section('css')
@stop

@section('js')
@stop