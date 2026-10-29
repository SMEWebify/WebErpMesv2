@extends('adminlte::page')

@section('title', __('receipt_document_check.title'))

@section('content_header')
  <h1>{{ __('receipt_document_check.title') }}</h1>
@stop

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <p class="text-body-secondary small mb-3">{{ __('receipt_document_check.info') }}</p>
    <div
      id="receipt-document-check-app"
      data-endpoints="{{ json_encode($endpoints) }}"
      data-document-check="{{ json_encode($documentCheck) }}"
    ></div>
  </div>
</div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
