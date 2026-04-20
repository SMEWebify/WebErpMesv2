@extends('adminlte::page')

@section('title', __('GTD Board'))

@section('content_header')
    <h1>{{ __('GTD Board') }}</h1>
@stop

@section('content')
    <div
        id="gtd-board-app"
        data-endpoints="{{ json_encode($props['endpoints']) }}"
    ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
<style>
    .card .card { border-color: #dee2e6; }
</style>
@stop

@section('js')
@stop
