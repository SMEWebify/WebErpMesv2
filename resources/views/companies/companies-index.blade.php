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
                            <th>Created At</th>
                            <th>Statu client</th>
                            <th>Statu supplier</th>
                            <th>Action</th>
                          </tr>
                          </thead>
                          <tbody>
                            @foreach ($Companieslist as $Companie)
                            <tr>
                              <td>{{ $Companie->CODE }}</td>
                              <td>{{ $Companie->LABEL }}</td>
                              <td>{{ $Companie->GetPrettyCreatedAttribute() }}</td>
                              <td>
                                @if($Companie->STATU_CLIENT == 2 )
                                <i class="fas fa-check-double"></i>
                                @elseif($Companie->STATU_CLIENT == 3 )
                                <i class="fas fa-check"></i>
                                @else
                                <i class="fas fa-times"></i>
                                @endif
                              </td>
                              <td>
                                @if($Companie->STATU_FOUR == 2 )
                                <i class="fas fa-check"></i>
                                 @else
                                 <i class="fas fa-times"></i>
                                @endif
                              </td>
                              <td>
                                <button onclick="window.location='{{ route('companies.show', ['id' => $Companie->id])}}'" class="btn btn-xs btn-default text-teal mx-1 shadow"  type="button" title="Show">
                                  <i class="fa fa-lg fa-fw fa-eye"></i> View
                                </button>
                                <!--<button  class="btn btn-xs btn-default text-primary mx-1 shadow" title="Edit">
                                  <i class="fa fa-lg fa-fw fa-pen"></i>Edit
                                </button>-->
                                <button onclick="window.location='{{ route('addresses.create', ['id' => $Companie->id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Add adress">
                                  <i class="fa fa-lg fa-fw fa-address-card"></i>Add adress
                                </button>
                                <button onclick="window.location='{{ route('contacts.create', ['id' => $Companie->id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Add contact">
                                  <i class="fa fa-lg fa-fw fa-address-book"></i>Add contact
                                </button>
                             </td>
                            </tr>
                            @endforeach
                          </tbody>
                          <tfoot>
                          <tr>
                            <th>Code</th>
                            <th>Label</th>
                            <th>Created At</th>
                            <th>Statu client</th>
                            <th>Statu supplier</th>
                            <th>Action</th>
                          </tr>
                          </tfoot>
                        </table>
                      </div>
                      <!-- /.row -->
                      <div class="row">
                        <div class="col-5">
                         {{ $Companieslist->links()}}
                        </div>
                        <div class="col-5">
                          <button type="button" onclick="window.location='{{ route('companies.create') }}'" class="btn btn-block bg-gradient-primary">New companie</button>
                         </div>
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