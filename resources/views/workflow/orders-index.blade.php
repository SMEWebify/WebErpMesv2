@extends('adminlte::page')

@section('title', 'Orders list')

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Orders list</h1>
      </div>
      <div class="col-sm-6">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalOrder">
          New Order
        </button>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')
<div class="card">
  @livewire('orders-index')
<!-- /.card -->
</div>

@stop

@section('css')
@stop

@section('js')
@stop