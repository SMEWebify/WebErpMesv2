@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Products list</h1>
@stop

@section('right-sidebar')

@section('content')

                <div class="card">
                   <!-- /.card-header -->
                  <div class="card-body">
                    <div  id="products_wrapper" class="dataTables_wrapper dt-bootstrap4">
                        
                        <div class="col-sm-12">
                          <table id="products" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                            <thead>
                            <tr>
                              <th>Code</th>
                              <th>Label</th>
                              <th>Created At</th>
                              <th>Sold</th>
                              <th>Purchase</th>
                              <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                              @foreach ($Products as $Product)
                              <tr>
                                <td>{{ $Product->CODE }}</td>
                                <td>{{ $Product->LABEL }}</td>
                                <td>{{ $Product->GetPrettyCreatedAttribute() }}</td>
                                <td>
                                  @if($Product->sold == 1 )
                                  <i class="fas fa-check-double"></i>
                                  @else
                                  <i class="fas fa-times"></i>
                                  @endif
                                </td>
                                <td>
                                  @if($Product->purchased == 1 )
                                  <i class="fas fa-check"></i>
                                  @else
                                  <i class="fas fa-times"></i>
                                  @endif
                                </td>
                                <td>
                                  <button onclick="window.location='{{ route('products.show', ['id' => $Product->id])}}'" class="btn btn-xs btn-default text-teal mx-1 shadow"  type="button" title="Show">
                                    <i class="fa fa-lg fa-fw fa-eye"></i> View
                                  </button>
                                  <!--<button  class="btn btn-xs btn-default text-primary mx-1 shadow" title="Edit">
                                    <i class="fa fa-lg fa-fw fa-pen"></i>Edit
                                  </button>-->
                              </td>
                              </tr>
                              @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                              <th>Code</th>
                              <th>Label</th>
                              <th>Created At</th>
                              <th>Sold</th>
                              <th>Purchase</th>
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
    $("#products").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#products_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop