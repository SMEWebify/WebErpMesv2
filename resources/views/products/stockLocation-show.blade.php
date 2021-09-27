@extends('adminlte::page')

@section('title', 'Stock')

@section('content_header')
    <h1> Stock location Product for {{ $StockLocation->LABEL }}</h1>
@stop

@section('content')
<div class="card">
  @if($errors->count())
  <div class="alert alert-danger">
    <ul>
    @foreach ( $errors->all() as $message)
     <li> {{ $message }}</li>
    @endforeach
    </ul>
  </div>
  @endif
    <div class="card card-primary">
      <div class="card-body">
        <div class="row">
          <div class="col-md-8 card-secondary">
            <div class="card-header">
                <h3 class="card-title">Stocks location product list</h3>
            </div>
            <div class="card-body p-0">
              <div  id="stocks_wrapper" class="dataTables_wrapper dt-bootstrap4">     
                <div class="col-sm-12">
                  <table id="stocks" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                    <thead>
                      <tr>
                        <th>Code</th>
                        <th>User management</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Qty reserve</th>
                        <th>Qty mini</th>
                        <th>End date</th>
                        <th>Addressing</th>
                        <th>Action</th>
                      </tr>
                    </thead>
                    <tbody>
                      @forelse ($StockLocationsProducts as $StockLocationsProduct)
                      <tr>
                        <td>{{ $StockLocationsProduct->CODE }}</td>
                        <td>{{ $StockLocationsProduct->UserManagement['name'] }}</td>
                        <td>{{ $StockLocationsProduct->Product['LABEL'] }}</td>
                        <td>{{ $StockLocationsProduct->stock_qty }}</td>
                        <td>{{ $StockLocationsProduct->reserve_qty }}</td>
                        <td>{{ $StockLocationsProduct->mini_qty }}</td>
                        <td>{{ $StockLocationsProduct->end_date }}</td>
                        <td>{{ $StockLocationsProduct->addressing }}</td>
                        <td class="text-right py-0 align-middle">
                          <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info"><i class="fas fa-edit"></i></a>
                          </div>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td>No Data</td>
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td> 
                        <td></td>
                        <td></td>  
                      </tr>
                      @endforelse
                    </tbody>
                    <tfoot>
                      <tr>
                        <th>Code</th>
                        <th>User management</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Qty reserve</th>
                        <th>Qty mini</th>
                        <th>End date</th>
                        <th>Addressing</th>
                        <th>Action</th>
                      </tr>
                    </tfoot>
                  </table>
                <!-- /.col-sm-12 -->
                </div>
              <!-- /.stock_wrapper-->
              </div>
            <!-- /.card-body p-0-->
            </div>
          <!-- /.col-md-8 card-secondary-->
          </div>

          <div class="col-md-4 card-secondary">
              <div class="card-header">
                <h3 class="card-title">New stock product line</h3>
              </div>
              <form  method="POST" action="{{ route('products.stockline.store') }}" class="form-horizontal">
                @csrf
                <div class="form-group">
                  <label for="CODE">External ID</label>
                  <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID" value="STOCK-PRODUCT-{{ $LastStockLocationProduct->id ?? '0' }}">
                  <input type="hidden" name="stock_locations_id" id="stock_locations_id" value="{{ $StockLocation->id }}">
                </div>
                <div class="form-group">
                  <label for="user_id">User management</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-user"></i></span>
                    </div>
                    <select class="form-control" name="user_id" id="user_id">
                      @foreach ($userSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="products_id">Product</label>
                  <select class="form-control" name="products_id" id="products_id">
                    @foreach ($ProductSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->CODE }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label for="mini_qty">Mini qty :</label>
                  <input type="number" class="form-control" name="mini_qty" id="mini_qty" placeholder="Mini qty ex: 1.50" step=".001">
                </div>
                <div class="form-group">
                  <label for="end_date">End date</label>
                  <input type="date" class="form-control" name="end_date"  id="end_date" >
                </div>
                <div class="form-group">
                  <label for="addressing">Addressing</label>
                  <input type="text" class="form-control" name="addressing" id="addressing" placeholder="Addressing">
                </div>
                <div class="card-footer">
                  <div class="offset-sm-2 col-sm-10">
                    <button type="submit" class="btn btn-danger">Submit New</button>
                  </div>
                </div>
              </form>
            <!-- /.card secondary -->
            </div>
          <!-- /.row -->
          </div>
        <!-- /.card body -->
        </div>
      <!-- /.card primary -->
      </div>
     <!-- /.card --> 
    </div>

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
$("#stocks").DataTable({
dom: 'Bfrtip',
buttons: [
'copy', 'csv', 'excel', 'pdf', 'print'
]
}).buttons().container().appendTo('#stocks_wrapper .col-md-6:eq(0)');
} );


</script>
@stop