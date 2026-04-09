@extends('adminlte::page')

@section('title', __('general_content.error_404_title_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.error_404_title_trans_key') }}</h1>
@stop

@section('content')<div class="error-page">
    <h2 class="headline text-warning"> 404</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-warning"></i> {{ __('general_content.error_404_heading_trans_key') }}</h3>
        <p>{!! __('general_content.error_404_message_trans_key', ['url' => route('dashboard')]) !!}</p>
    </div>
@stop

@section('css')
@stop

@section('js')
@stop
