@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>User list</h1>
@stop



@section('content')
     <div class="card">
        <!-- /.card-header -->
        <div class="card-body">
          <div class="row">
            <table id="example1" class="table table-bordered table-striped">
              <thead>
              <tr>
                <th>Name</th>
                <th>E-mail</th>
                <th>Created</th>
              </tr>
              </thead>
              <tbody>
                @foreach ($Users as $User)
                <tr>
                  <td>{{ $User->name }}</td>
                  <td>{{ $User->email }}</td>
                  <td>{{ $User->GetPrettyCreatedAttribute() }}</td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                    <th>Name</th>
                    <th>E-mail</th>
                    <th>Created</th>
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
    <script> </script>
@stop