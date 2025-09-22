@extends('adminlte::page')

@section('title', __('GTD Board'))

@section('content_header')
    <h1>{{ __('GTD Board') }}</h1>
@stop

@section('content')
    @livewire('gtd-board')
@stop

@section('css')
<style>
    .card .card {
        border-color: #dee2e6;
    }
</style>
@stop
