@extends('adminlte::page')

@section('title', 'Quote Lines list')

@section('content_header')
  
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Quote Lines list</h1>
      </div>
    </div>

@stop

@section('right-sidebar')

@section('content')

                <div class="card">
                  <div class="card-body">
                    <div  id="quotes_wrapper" class="dataTables_wrapper dt-bootstrap4">
                        <div class="col-sm-12">
                          <table id="quotes" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                            <thead>
                            <tr>
                              <th>Quote ID</th>
                              <th>Sort</th>
                              <th>External ID</th>
                              <th>Product</th>
                              <th>Description</th>
                              <th>Qty</th>
                              <th>Unit</th>
                              <th>Selling price</th>
                              <th>Discount</th>
                              <th>VAT type</th>
                              <th>Delivery date</th>
                              <th>Statu</th>
                              <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                              @foreach ($QuoteLineslist as $QuoteLine)
                              <tr>
                                <td></td>
                                <td>{{ $QuoteLine->ORDRE }}</td>
                                <td>{{ $QuoteLine->CODE }}</td>
                                <td>@if(1 == $QuoteLine->product_id ) {{ $QuoteLine->Product['LABEL'] }}@endif</td>
                                <td>{{ $QuoteLine->LABEL }}</td>
                                <td>{{ $QuoteLine->qty }}</td>
                                <td>{{ $QuoteLine->Unit['LABEL'] }}</td>
                                <td>{{ $QuoteLine->selling_price }}</td>
                                <td>{{ $QuoteLine->discount }}</td>
                                <td>{{ $QuoteLine->VAT['LABEL'] }}</td>
                                <td>{{ $QuoteLine->delivery_date }}</td>
                                <td>
                                  @if(1 == $QuoteLine->statu )   <span class="badge badge-info"> Open</span>@endif
                                  @if(2 == $QuoteLine->statu )  <span class="badge badge-warning">Send</span>@endif
                                  @if(3 == $QuoteLine->statu )  <span class="badge badge-success">Win</span>@endif
                                  @if(4 == $QuoteLine->statu )  <span class="badge badge-danger">Lost</span>@endif
                                  @if(5 == $QuoteLine->statu )  <span class="badge badge-secondary">Closed</span>@endif
                                  @if(6 == $QuoteLine->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                                </td>
                                <td>
                                  <a class="btn btn-primary btn-sm" href="{{ route('quote.show', ['id' => $QuoteLine->quotes_id])}}">
                                    <i class="fas fa-folder"></i>
                                    View
                                  </a>
                                </td>
                              </tr>
                              @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                              <th>Quote ID</th>
                              <th>Sort</th>
                              <th>External ID</th>
                              <th>Product</th>
                              <th>Description</th>
                              <th>Qty</th>
                              <th>Unit</th>
                              <th>Selling price</th>
                              <th>Discount</th>
                              <th>VAT type</th>
                              <th>Delivery date</th>
                              <th>Statu</th>
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
    $("#quotes").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#quotes_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop