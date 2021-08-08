@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Accounting settings</h1>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab">Payment condition</a></li>
      <li class="nav-item"><a class="nav-link" href="#timeline" data-toggle="tab">Payment choice</a></li>
      <li class="nav-item"><a class="nav-link" href="#settings" data-toggle="tab">VAT</a></li>
      <li class="nav-item"><a class="nav-link" href="#settings" data-toggle="tab"> Accouting allocations</a></li>
      <li class="nav-item"><a class="nav-link" href="#settings" data-toggle="tab"> Delevery mode</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">

      <div class="tab-pane" id="activity">
        <form class="form-horizontal">
          <div class="form-group row">
            <label for="inputName" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputName" placeholder="Name">
            </div>
          </div>
          <div class="form-group row">
            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputEmail" placeholder="Email">
            </div>
          </div>
          <div class="form-group row">
            <div class="offset-sm-2 col-sm-10">
              <button type="submit" class="btn btn-danger">Submit</button>
            </div>
          </div>
        </form>
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane" id="timeline">
        <form class="form-horizontal">
          <div class="form-group row">
            <label for="inputName" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputName" placeholder="Name">
            </div>
          </div>
          <div class="form-group row">
            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputEmail" placeholder="Email">
            </div>
          </div>
          <div class="form-group row">
            <div class="offset-sm-2 col-sm-10">
              <button type="submit" class="btn btn-danger">Submit</button>
            </div>
          </div>
        </form>
      </div>
      <!-- /.tab-pane -->

      <div class="tab-pane" id="settings">
        <form class="form-horizontal">
          <div class="form-group row">
            <label for="inputName" class="col-sm-2 col-form-label">Name</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputName" placeholder="Name">
            </div>
          </div>
          <div class="form-group row">
            <label for="inputEmail" class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10">
              <input type="email" class="form-control" id="inputEmail" placeholder="Email">
            </div>
          </div>
          <div class="form-group row">
            <div class="offset-sm-2 col-sm-10">
              <button type="submit" class="btn btn-danger">Submit</button>
            </div>
          </div>
        </form>
      </div>
      <!-- /.tab-pane -->
    </div>
    <!-- /.tab-content -->
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
</div>

@stop
                  
 @section('css')
   <link rel="stylesheet" href="/css/admin_custom.css">
 @stop
                  
@section('js')
  <script> console.log('Hi!'); </script>
@stop