@extends('adminlte::page')

@section('title', 'Orders list')

@section('content_header')
  <h1>Orders lines list</h1>
@stop

@section('right-sidebar')

@section('content')

@livewire('orders-lines-index')

@stop

@section('css')
@stop

@section('js')
  <script>
    $(function () {
      $('[data-toggle="tooltip"]').tooltip({
            html:true
        })
    })
  </script>
@stop