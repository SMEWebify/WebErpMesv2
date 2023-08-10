@extends('adminlte::page')

@section('title', 'Products')

@section('content_header')
<div class="row mb-2">
  <h1>Products list</h1>
</div>
@stop

@section('right-sidebar')

@section('content')
  @livewire('products-index')
@stop

@section('css')
@stop

@section('js')
@stop