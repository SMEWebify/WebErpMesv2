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
                    <div  id="companies_wrapper" class="dataTables_wrapper dt-bootstrap4">
                        
                        <div class="col-sm-12">
                          <table id="companies" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
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
                      </div>
                      <!-- /.row -->
                    </div>
                  </div>
                  <!-- /.card-body -->
                </div>
                <!-- /.card -->

@stop
                  
 @section('css')
   <link rel="stylesheet" href="/css/admin_custom.css">
   <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.0/css/jquery.dataTables.min.css">
   <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.0/css/buttons.dataTables.min.css">
 @stop
                  
@section('js')

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.11.0/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.html5.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.print.min.js"></script>
  <script> 

  $(document).ready( function () {
    $("#companies").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#companies_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop