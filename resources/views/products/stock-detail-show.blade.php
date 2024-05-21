@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key'))

@section('content_header')
<div class="row mb-2">
  <h1>{{ __('general_content.stock_trans_key') }}</h1>
</div>
@stop

@section('right-sidebar')

@section('content')
<div class="card-body">
  @livewire('stock-detail', ['id' =>$StockDetailId])
</div>

@stop

@section('css')
@stop

@section('js')
@stop