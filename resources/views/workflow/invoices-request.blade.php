@extends('adminlte::page')

@section('title', __('general_content.invoices_request_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.invoices_request_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

@livewire('invoices-request')

@stop

@section('css')
@stop

@section('js')
@stop