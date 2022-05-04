
@extends('adminlte::page')

@section('title', 'Profile')

@section('content_header')
    <h1>Profile</h1>
@stop



@section('content')

@include('include.alert-result')

<div class="card">
    @livewire('user-profile')
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop