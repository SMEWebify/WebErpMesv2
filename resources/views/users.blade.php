@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>User list</h1>
@stop



@section('content')
    @foreach ($Users as $User)
        <x-ContactComponent :name="$User->name" />
    @endforeach
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop