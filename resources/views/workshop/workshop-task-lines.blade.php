@extends('adminlte::page')

@section('title', __('general_content.workshop_interface_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.workshop_interface_trans_key') }}</h1>
@stop

@section('content')
    @livewire('task-lines')
@stop

@section('css')

@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
        // Ajoute la classe sidebar-hidden à la balise body dès que la page est chargée
        $("body").addClass("sidebar-hidden");
        });
    </script>
@stop