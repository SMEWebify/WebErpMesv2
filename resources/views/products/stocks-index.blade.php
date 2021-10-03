@extends('adminlte::page')

@section('title', 'Stock')

@section('content_header')
    <h1>Stock</h1>
@stop

@section('right-sidebar')

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
                                <h3 class="card-title">Stocks list</h3>
                            </div>
                            <div class="card-body p-0">
                              <table class="table">
                                <thead>
                                  <tr>
                                    <th>External ID</th>
                                    <th>Desciption</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  @forelse ($stocks as $stock)
                                  <tr>
                                    <td>{{ $stock->CODE }}</td>
                                    <td>{{ $stock->LABEL }}</td>
                                    <td>{{ $stock->GetPrettyCreatedAttribute() }}</td>
                                    <td class="text-right py-0 align-middle">
                                      <div class="btn-group btn-group-sm">
                                        <a href="{{ route('products.stocks.show', ['id' => $stock->id])}}" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
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
                                  </tr>
                                  @endforelse
                                </tbody>
                                <tfoot>
                                  <tr>
                                    <th>External ID</th>
                                    <th>Desciption</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                  </tr>
                                </tfoot>
                              </table>
                            </div>
                          <!-- /.card secondary -->
                          </div>
            
                          <div class="col-md-6 card-secondary">
                              <div class="card-header">
                                <h3 class="card-title">New Stock</h3>
                              </div>
                              <form  method="POST" action="{{ route('products.stock.store') }}" class="form-horizontal">
                                @csrf
                                <div class="form-group">
                                  <label for="CODE">External ID</label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID" value="STOCK-{{ $LastStock->id ?? '0' }}">
                                  </div>
                                </div>
                                <div class="form-group">
                                  <label for="LABEL">Description</label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                   <input type="text" class="form-control" name="LABEL" id="LABEL" placeholder="Description">
                                  </div>
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

@stop