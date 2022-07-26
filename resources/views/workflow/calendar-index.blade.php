@extends('adminlte::page')

@section('title', 'Calendar')

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Calendar</h1>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card-body">
  @livewire('calendar')
</div>
@stop

@section('css')
@stop




