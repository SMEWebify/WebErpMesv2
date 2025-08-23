@extends('adminlte::page')

@section('title', __('general_content.assets_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.assets_trans_key') }}</h1>
@stop

@section('content')
    @include('assets.partials.assets-list', ['assets' => $assets])
@stop
