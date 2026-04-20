@extends('adminlte::page')

@section('title', $return->code)

@section('content_header')
    <h1>{{ $return->code }}</h1>
@stop

@section('content')
    <div
        id="return-show-app"
        data-initial="{{ json_encode($props['initial']) }}"
        data-endpoints="{{ json_encode($props['endpoints']) }}"
        data-trans="{{ json_encode($props['trans']) }}"
    ></div>
@stop

@section('css')
@viteReactRefresh
@vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
