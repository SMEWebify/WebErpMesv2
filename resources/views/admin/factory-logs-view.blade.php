@extends('adminlte::page')

@section('title', 'LogsView')

@section('content_header')
    <h1>LogsView</h1>
@stop

@section('content')
    <x-adminlte-card theme="lime" theme-mode="outline">
        @livewire('logs-viewer')
    </x-adminlte-card>
@stop

@section('css')
@stop

@section('js')
@stop