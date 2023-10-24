@extends('adminlte::page')

@section('title', __('general_content.quotes_lines_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.quotes_lines_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

@livewire('quotes-lines-index')

@stop

@section('css')
@stop

@section('js')
@stop