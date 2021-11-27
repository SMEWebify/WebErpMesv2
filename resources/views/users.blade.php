@extends('adminlte::page')

@section('title', 'Users')

@section('content_header')
    <h1>User list</h1>
@stop



@section('content')
    <div class="card">
        <!-- /.card-header -->
        <div class="card-body">
          <div class="row">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>E-mail</th>
                  <th>Created</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($Users as $User)
                <tr>
                  <td>{{ $User->name }}</td>
                  <td>{{ $User->email }}</td>
                  <td>{{ $User->GetPrettyCreatedAttribute() }}</td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <a href="#" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                    <th>Name</th>
                    <th>E-mail</th>
                    <th>Created</th>
                    <th>Action</th>
                  </tr>
              </tfoot>
            </table>
          </div>
          <!-- /.row -->
          <div class="row">
            <div class="col-5">
              {{ $Users->links() }}
            </div>
          </div>
          <!-- /.row -->
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop