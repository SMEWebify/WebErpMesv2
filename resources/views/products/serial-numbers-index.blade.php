@extends('adminlte::page')

@section('title', __('general_content.serial_numbers_trans_key'))

@section('content_header')
  <h1>{{__('general_content.serial_numbers_list_trans_key')}}</h1>
@stop

@section('right-sidebar')

@section('content')
@livewire('serial-numbers-index')
@stop

@section('css')
@stop

@section('js')
@stop