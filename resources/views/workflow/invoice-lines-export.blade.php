@extends('adminlte::page')

@section('title', __('general_content.invoices_export_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.invoices_export_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

@livewire('invoice-export-lines')

@stop

@section('css')
@stop

@section('js')
@stop