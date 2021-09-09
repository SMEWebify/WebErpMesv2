@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1> Stock location for {{ $Stock->LABEL }}</h1>
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
          <div class="col-md-6 card-secondary">
            <div class="card-header">
                <h3 class="card-title">Stocks location list</h3>
            </div>
            <div class="card-body p-0">
              <table class="table">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>End date</th>
                    <th>User management</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($StockLocations as $StockLocation)
                  <tr>
                    <td>{{ $StockLocation->CODE }}</td>
                    <td>{{ $StockLocation->LABEL }}</td>
                    <td>{{ $StockLocation->END_DATE }}</td>
                    <td>{{ $StockLocation->UserManagement['name'] }}</td>
                    <td class="text-right py-0 align-middle">
                      <div class="btn-group btn-group-sm">
                        <a href="{{ route('products.stocklocation.show', ['id' => $StockLocation->id])}}" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                      </div>
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
                  </tr>
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>End date</th>
                    <th>User management</th>
                    <th>Action</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          <!-- /.card secondary -->
          </div>

          <div class="col-md-6 card-secondary">
              <div class="card-header">
                <h3 class="card-title">New Stock location</h3>
              </div>
              <form  method="POST" action="{{ route('products.stocklocation.store') }}" class="form-horizontal">
                @csrf
                <div class="form-group">
                  <label for="CODE">External ID</label>
                  <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID" value="STOCK-LOCATION-{{ $LastStockLocation->id ?? '0' }}">
                  <input type="hidden" name="stocks_id" id="stocks_id" value="{{ $Stock->id }}">
                </div>
                <div class="form-group">
                  <label for="LABEL">Description</label>
                  <input type="text" class="form-control" name="LABEL" id="LABEL" placeholder="Description">
                </div>
                <div class="form-group">
                  <label for="user_id">User management</label>
                  <select class="form-control" name="user_id" id="user_id">
                    @foreach ($userSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="form-group">
                  <label for="END_DATE">End date</label>
                  <input type="date" class="form-control" name="END_DATE"  id="END_DATE" >
                </div>
                <div class="form-group">
                  <label>Comment</label>
                  <textarea class="form-control" rows="3" name="COMMENT"  placeholder="Enter ..."></textarea>
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
<link rel="stylesheet" href="/css/admin_custom.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.0/css/jquery.dataTables.min.css">
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.0/css/buttons.dataTables.min.css">
@stop
   
@section('js')

@stop