@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Companies list</h1>
@stop

@section('right-sidebar')

@section('content')

                <div class="card">
                    <!-- /.card-header -->
                    <div class="card-body">
                      <div class="row">
                        <table id="example1" class="table table-bordered table-striped">
                          <thead>
                          <tr>
                            <th>Code</th>
                            <th>Label</th>
                            <th>Statu client</th>
                            <th>Statu supplier</th>
                          </tr>
                          </thead>
                          <tbody>
                            @foreach ($Companieslist as $Company)
                            <tr>
                              <td><a href="{{ route('companies.show', ['id' => $Company->id])}}">{{ $Company->code }} </a></td>
                              <td>{{ $Company->LABEL }}</td>
                              <td>{{ $Company->STATU_CLIENT }}</td>
                              <td>{{ $Company->STATU_FOUR }}</td>
                            </tr>
                            @endforeach
                          </tbody>
                          <tfoot>
                          <tr>
                            <th>Code</th>
                            <th>Label</th>
                            <th>Statu client</th>
                            <th>Statu supplier</th>
                          </tr>
                          </tfoot>
                        </table>
                      </div>
                      <!-- /.row -->
                      <div class="row">
                        {{ $Companieslist->links()}}
                      </div>
                      <!-- /.row -->
                    </div>
                    <!-- /.card-body -->
                  </div>
                  <!-- /.card -->

@stop
                  
 @section('css')
   <link rel="stylesheet" href="/css/admin_custom.css">
 @stop
                  
@section('js')
  <script> console.log('Hi!'); </script>
@stop