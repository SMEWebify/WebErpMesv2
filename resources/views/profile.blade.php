
@extends('adminlte::page')

@section('title', 'Profile')

@section('content_header')
    <h1>Profile</h1>
@stop



@section('content')
<div class="card">
    <div class="card-header p-2">
      <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link active" href="#Settings" data-toggle="tab">Profile settings</a></li>
        <li class="nav-item"><a class="nav-link" href="#Kanban" data-toggle="tab">Kanban user settings</a></li>
      </ul>
    </div>
    <!-- /.card-header -->
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane active" id="Settings">
                <div class="col-md-8">
                    @livewire('user-profile')
                </div>
            </div>
            <div class="tab-pane " id="Kanban">

            </div>
        </div>
    </div>
</div>
<!-- /.card -->
@stop

@section('css')
     
@stop

@section('js')
    <script> </script>
@stop