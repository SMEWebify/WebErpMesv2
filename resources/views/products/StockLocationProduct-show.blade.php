@extends('adminlte::page')

@section('title', 'Stock')

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1> Stock line for {{ $StockLocationProduct->code }}  stock</h1>
      </div>
      <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('products.stock') }}">Stock list</a></li>
              <li class="breadcrumb-item"><a href="{{ route('products.stock.show', ['id' => $StockLocation->stocks_id]) }}">Stock {{ $Stock->label }}</a></li>
              <li class="breadcrumb-item"><a href="{{ route('products.stocklocation.show', ['id' => $StockLocationProduct->stock_locations_id]) }}">Stock location {{ $StockLocation->label }}</a></li>
          </ol>
      </div>
    </div>
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
          <div class="col-md-8 card-primary">
            <div class="card-header">
                <h3 class="card-title">Stocks location product list</h3>
            </div>
            <div class="card-body">
              <div  id="stocks_wrapper" class="dataTables_wrapper dt-bootstrap4">     
                <div class="col-sm-12">
                  <table class="table">
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
            <!-- /.card-body-->
            </div>
          <!-- /.col-md-8 card-secondary-->
          </div>
          <div class="col-md-4 card-secondary">
              <div class="card-header">
                <h3 class="card-title">New stock product line</h3>
              </div>

              <div class="card-body">
                <form  method="POST" action="{{ route('products.stockline.store') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="code">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="code" id="code" placeholder="External ID" value="STOCK-PRODUCT-{{ $LastStockLocationProduct->id ?? '0' }}">
                      <input type="hidden" name="stock_locations_id" id="stock_locations_id" value="{{ $StockLocation->id }}">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="mini_qty">Mini qty :</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-times"></i></span>
                      </div>
                      <input type="number" class="form-control" name="mini_qty" id="mini_qty" placeholder="Mini qty ex: 1.50" step=".001">
                    </div>
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
              <!-- /.card body -->
              </div>
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
@stop

@section('js')
@stop