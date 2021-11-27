@extends('adminlte::page')

@section('title', 'Quotes lines list')

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>Quotes lines list</h1>
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @livewire('quotes-lines-index')
<!-- /.card -->
</div>

@stop

@section('css')
@stop

@section('js')
@stop