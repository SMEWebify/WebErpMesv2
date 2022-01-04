@extends('adminlte::page')

@section('title', 'Deliverys list')

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>Deliverys list</h1>
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @livewire('deliverys-index')
<!-- /.card -->
</div>

@stop

@section('css')
@stop

@section('js')
@stop