@extends('adminlte::page')

@section('title', 'Products')

@section('content_header')
<div class="row mb-2">
  <div class="col-sm-6">
    <h1>Products list</h1>
  </div>
  <div class="col-sm-6">
    <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalProduct">
      New product
    </button>
  </div>
</div>

@stop

@section('right-sidebar')

@section('content')
<div class="card">
  @livewire('products-index')
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop