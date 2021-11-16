@extends('adminlte::page')

@section('title', 'Task list')

@section('content_header')
    

    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Task list</h1>
      </div>
    </div>

@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
      @livewire('task-lines')
</div>
<!-- /.card -->

@stop

@section('css')


@stop

@section('js')
<script> 

</script>
@stop