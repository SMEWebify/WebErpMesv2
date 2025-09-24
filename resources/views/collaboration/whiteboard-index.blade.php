@extends('adminlte::page')

@section('title', 'Tableau blanc collaboratif')

@section('content_header')
    <h1>Tableau blanc collaboratif</h1>
@stop

@section('content')
    <div
        id="whiteboard-app"
        data-initial-whiteboard-id="{{ $whiteboard->id ?? '' }}"
        data-initial-whiteboard='@json($whiteboard ?? null)'
        data-initial-snapshots='@json($snapshots ?? [])'
        data-initial-files='@json($files ?? [])'
        data-endpoints='@json($endpoints ?? [])'
    ></div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/app.css">
@stop

@section('js')
    <script src="/js/app.js"></script>
@stop
