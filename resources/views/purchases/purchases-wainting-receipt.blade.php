@extends('adminlte::page')

@section('title', __('general_content.waiting_to_receipt_trans_key'))

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
        <h1>{{ __('general_content.waiting_to_receipt_trans_key') }}</h1> 
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')

@livewire('purchases-wainting-receipt')

@stop

@section('css')
@stop

@section('js')
@stop