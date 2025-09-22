@extends('adminlte::page')

@section('title', $return->code)

@section('content_header')
    <h1>{{ $return->code }}</h1>
@stop

@section('content')
    @livewire('return-show', ['returnId' => $return->id])
@stop
