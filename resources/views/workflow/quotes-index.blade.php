@extends('adminlte::page')

@section('title', 'Quote list')

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>Quotes list</h1>
    </div>
    <div class="col-sm-6">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalQuote">
            New quote
        </button>
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @livewire('quotes-index')
<!-- /.card -->
</div>

@stop

@section('css')
@stop

@section('js')
@stop