@extends('adminlte::page')

@section('title', 'Nesting')

@section('content_header')
    <h1><i class="fas fa-th mr-2"></i>Nesting — Plan de débit</h1>
@stop

@section('content')
    <div
        id="nesting-app"
        data-doc-type="{{ request('type', '') }}"
        data-doc-id="{{ request('id', '') }}"
    ></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
