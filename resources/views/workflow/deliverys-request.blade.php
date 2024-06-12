@extends('adminlte::page')

@section('title', __('general_content.deliverys_notes_request_trans_key'))

@section('content_header')
  <h1>{{__('general_content.deliverys_notes_request_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

@livewire('deliverys-request')

@stop

@section('css')
@stop

@section('js')
@stop