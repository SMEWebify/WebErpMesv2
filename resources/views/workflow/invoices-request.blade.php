@extends('adminlte::page')

@section('title', 'Invoices request list')

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>Invoices request</h1>
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')

@livewire('invoices-request')

@stop

@section('css')
@stop

@section('js')
@stop