@extends('adminlte::page')

@section('title', __('general_content.order_calendar_trans_key'))

@section('content_header')
  <h1>{{__('general_content.order_calendar_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <p>
    Currently a problem on the calendar since the update, see the progress on
    <a  target="_blank" href="https://github.com/SMEWebify/WebErpMesv2/issues/215">
      <span class="text-bold markdown-title js-issue-title">[PLANNING] Calendar not working after laravel and livewire update</span>
      <span class="color-fg-muted">#215</span>
    </a>
  </p>
  <div class="card-body">
    @livewire('calendar')
  </div>
</div>
@stop

@section('css')
@stop




