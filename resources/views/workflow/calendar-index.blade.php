@extends('adminlte::page')

@section('title', __('general_content.order_calendar_trans_key'))

@section('content_header')
  <h1>{{__('general_content.order_calendar_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  @livewire('calendar')
</div>
@stop

@section('css')
@stop




