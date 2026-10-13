@extends('adminlte::page')

@section('title', __('general_content.error_403_title_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.error_403_title_trans_key') }}</h1>
@stop

@section('content')
<div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-4 py-5 text-center text-md-start">
    <h2 class="display-1 fw-light text-warning mb-0">403</h2>
    <div>
        <h3><i class="fas fa-exclamation-triangle text-warning"></i> {{ __('general_content.error_403_heading_trans_key') }}</h3>
        <p>{!! __('general_content.error_403_message_trans_key', ['url' => route('dashboard')]) !!}</p>
    </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop
