@extends('adminlte::page')

@section('title', 'Stock')

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1> Stock location for {{ $Stock->label }} stock</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{ route('products.stock') }}">Stock list</a></li>
        </ol>
    </div>
  </div>
@stop

@section('content')
  @include('include.alert-result')
    <div class="card card-primary">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 card-primary">
            <div class="card-header">
                <h3 class="card-title">Stocks location list</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Lines count</th>
                    <th>End date</th>
                    <th>User management</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($StockLocations as $StockLocation)
                  <tr>
                    <td>{{ $StockLocation->code }}</td>
                    <td>{{ $StockLocation->label }}</td>
                    <td>{{ $StockLocation->stock_location_products_count }}</td>
                    <td>{{ $StockLocation->end_date }}</td>
                    <td>{{ $StockLocation->UserManagement['name'] }}</td>
                    <td class=" py-0 align-middle">
                      <div class="btn-group btn-group-sm">
                        <a href="{{ route('products.stocklocation.show', ['id' => $StockLocation->id])}}" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                      </div>
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#StockLocation{{ $StockLocation->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $StockLocation->id }} -->
                      <x-adminlte-modal id="StockLocation{{ $StockLocation->id }}" title="Update {{ $StockLocation->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('products.stocklocation.update', ['id' => $StockLocation->id]) }}" >
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">Label</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $StockLocation->label }}">
                                <input type="hidden" name="stocks_id"  id="stocks_id"  value="{{ $StockLocation->stocks_id }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="service_id">User management</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                  <select class="form-control" name="user_id" id="user_id">
                                    @foreach ($userSelect as $item)
                                    <option value="{{ $item->id }}" @if($StockLocation->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                    @endforeach
                                  </select>
                              </div>
                            </div>
                            <div class="form-group">
                              <label>Comment</label>
                              <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ...">{{ $StockLocation->comment }}</textarea>
                            </div>
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                      </x-adminlte-modal>
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="5" text="No data available in table"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>Code</th>
                    <th>Label</th>
                    <th>Lines count</th>
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
              <div class="card-body">
                <form  method="POST" action="{{ route('products.stocklocation.store') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="code">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="code" id="code" placeholder="External ID" value="STOCK-LOCATION-{{ $LastStockLocation->id ?? '0' }}">
                      <input type="hidden" name="stocks_id" id="stocks_id" value="{{ $Stock->id }}">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="label">Description</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label" id="label" placeholder="Description">
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
                  <div class="form-group">
                    <label for="end_date">End date</label>
                    <input type="date" class="form-control" name="end_date"  id="end_date" >
                  </div>
                  <div class="form-group">
                    <label>Comment</label>
                    <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..."></textarea>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
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

@stop

@section('css')
@stop

@section('js')
@stop