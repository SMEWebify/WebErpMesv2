@extends('adminlte::page')

@section('title', __('receivables.reminders.title'))

@section('content_header')
  <div class="d-flex justify-content-between align-items-center">
    <h1 class="mb-0">{{ __('receivables.reminders.title') }}</h1>
    @if($canSend)
      <a href="{{ route('invoices.reminders.levels') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-sliders-h me-1"></i> {{ __('receivables.reminders.levels_link') }}
      </a>
    @endif
  </div>
@stop

@section('content')
<div
  id="invoice-reminders-app"
  data-endpoints='@json($reactEndpoints, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-trans='@json($reactTrans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
  data-can-send="{{ $canSend ? '1' : '0' }}">
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
