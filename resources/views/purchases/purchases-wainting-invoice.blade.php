@extends('adminlte::page')

@section('title', 'Purchases Wainting Invoice')

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>Purchases Wainting Invoice</h1>
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @livewire('purchases-wainting-invoice')
<!-- /.card -->
</div>

@stop

@section('css')
@stop

@section('js')
@stop