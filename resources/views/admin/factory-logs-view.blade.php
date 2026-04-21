@extends('adminlte::page')

@section('title', 'LogsView')

@section('content_header')
    <h1>LogsView</h1>
@stop

@section('content')
    <x-adminlte-card theme="lime" theme-mode="outline">
        @include('include.logs-viewer-mount')
    </x-adminlte-card>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop