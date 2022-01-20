@extends('adminlte::page')

@section('title', 'Kanban')

@section('content_header')
    
    <div class="row mb-2">
        <div class="col-sm-6">
        <h1>Kanban board</h1>
        </div>
    </div>

@stop

@section('right-sidebar')

@section('content')

    <x-InfocalloutComponent note="The views are configured in the 'Your company' page."  />
    <div id="card" class="card">
        <kanban-board :initial-data="{{ $tasks }}"></kanban-board>
    </div>

@stop

@section('css')
    <link rel="stylesheet" href="/css/app.css">
@stop

@section('js')
    <script src="/js/app.js"></script>
@stop