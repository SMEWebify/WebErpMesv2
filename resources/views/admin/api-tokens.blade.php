@extends('adminlte::page')

@section('title', 'Tokens API')

@section('content_header')
    <h1><i class="fas fa-key mr-2"></i>Tokens API</h1>
@stop

@section('content')
    <div
        id="api-tokens-app"
        data-tokens="{{ json_encode($tokens) }}"
        data-endpoints="{{ json_encode([
            'store'   => route('admin.api-tokens.store'),
            'destroy' => route('admin.api-tokens.destroy', ':id'),
        ]) }}"
    ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
