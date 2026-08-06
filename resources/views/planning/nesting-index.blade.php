@extends('adminlte::page')

@section('title', 'Nesting')

@section('content_header')
    <h1><i class="fas fa-th mr-2"></i>Nesting - Besoin matière</h1>
@stop

@section('content')
    <div id="nesting-app"></div>
@stop

@section('css')
    @viteReactRefresh
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop
