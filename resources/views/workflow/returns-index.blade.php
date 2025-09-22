@extends('adminlte::page')

@section('title', __('returns.fields.list'))

@section('content_header')
    <h1>{{ __('returns.fields.list') }}</h1>
@stop

@section('content')
    @livewire('returns-index')
@stop
