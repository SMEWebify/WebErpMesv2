@extends('adminlte::page')

@section('title', __('general_content.error_500_title_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.error_500_title_trans_key') }}</h1>
@stop

@section('content')<div class="error-page">
    <h2 class="headline text-danger"> 500</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-danger"></i> {{ __('general_content.error_500_heading_trans_key') }}</h3>
        <p>{!! __('general_content.error_500_message_trans_key', ['url' => route('dashboard')]) !!}</p>
    </div>
@stop

@section('css')
@stop

@section('js')
@stop
