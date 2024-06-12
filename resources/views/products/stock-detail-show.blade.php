@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.stock_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @livewire('stock-detail', ['id' =>$StockDetailId])
@stop

@section('css')
@stop

@section('js')
@stop