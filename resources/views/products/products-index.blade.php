@extends('adminlte::page')

@section('title', __('general_content.products_trans_key'))

@section('content_header')
  <h1>{{__('general_content.product_list_trans_key')}}</h1>
@stop

@section('right-sidebar')

@section('content')
  @livewire('products-index')
@stop

@section('css')
@stop

@section('js')
@stop