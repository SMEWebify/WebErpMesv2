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
  @include('include.alert-result')
    <div class="card card-primary">
      <div class="card-body">
        <div class="row">
          <div class="col-md-8 card-primary">
            <div class="row">
              <div class="col-12 col-sm-4">
                <x-adminlte-info-box title="Entries" text="{{ $StockLocationProduct->getTotalEntryStockMove() }} item(s)" icon="fa fa-arrow-up" theme="warning"/>
              </div>
              <div class="col-12 col-sm-4">
                <x-adminlte-info-box title="Sortings" text="{{ $StockLocationProduct->getTotalSortingStockMove() }} item(s)" icon="fa fa-arrow-down" theme="danger "/>
              </div>
              <div class="col-12 col-sm-4">
                <x-adminlte-info-box title="Current Qty" text="{{ $StockLocationProduct->getCurrentStockMove() }} item(s)" icon="fa fa-database" theme="success"/>
              </div>
            </div>
            <div class="card-header">
                <h3 class="card-title">Stocks location product list</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Date time</th>
                    <th>Qty</th>
                    <th>Order</th>
                    <th>Task</th>
                    <th>Purchase</th>
                    <th>Type</th>
                    <th>Price</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($StockMoves as $StockMove)
                  <tr>
                    <td>{{ $StockMove->UserManagement['name'] }}</td>
                    <td>{{ $StockMove->GetPrettyCreatedAttribute() }}</td>
                    <td>{{ $StockMove->qty }}</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>
                      @if(1 == $StockMove->typ_move )Inventories @endif
                      @if(2 == $StockMove->typ_move )Task allocation @endif
                      @if(3 == $StockMove->typ_move )Purchase order reception @endif
                      @if(4 == $StockMove->typ_move )Inter-stock mvts @endif
                      @if(5 == $StockMove->typ_move )Manual Stock reception @endif
                      @if(6 == $StockMove->typ_move )Manual Stock dispatching @endif
                      @if(7 == $StockMove->typ_move )Reservation @endif
                      @if(8 == $StockMove->typ_move )Reservation cancellation @endif
                      @if(9 == $StockMove->typ_move )Part delivery @endif
                      @if(10 == $StockMove->typ_move )In production @endif
                      @if(11 == $StockMove->typ_move )Reservation of a component in production @endif
                      @if(12 == $StockMove->typ_move )Manufactured component entry @endif
                      @if(13 == $StockMove->typ_move )Direct inventory @endif
                    </td>
                    <td></td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="9" text="No data available in table"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>User</th>
                    <th>Date time</th>
                    <th>Qty</th>
                    <th>Order</th>
                    <th>Task</th>
                    <th>Purchase</th>
                    <th>Type</th>
                    <th>Price</th>
                  </tr>
                </tfoot>
              </table>
            <!-- /.card-body-->
            </div>
          <!-- /.col-md-8 card-secondary-->
          </div>
          <div class="col-md-4">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">New entry stock line</h3>
              </div>
              <div class="card-body">
                <form  method="POST" action="{{ route('products.stockline.entry') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="typ_move">Move Type</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                      </div>
                      <select class="form-control" name="typ_move" id="typ_move">
                        <option value="5" >Manual Stock reception</option>
                        <option value="1" >Inventories</option>
                        <option value="3" >Purchase order reception</option>
                        <option value="12" >Manufactured component entry</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="qty">Qty :</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-times"></i></span>
                      </div>
                      <input type="number" class="form-control" name="qty" id="qty" placeholder="Ex: 10" step=".001">
                      <input type="hidden" name="stock_location_products_id" id="stock_location_products_id" value="{{ $StockLocationProduct->id }}">
                      <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}">
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </form>
              <!-- /.card body -->
              </div>
            <!-- /.card secondary -->
            </div>

            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title">New sorting stock line</h3>
              </div>

              <div class="card-body">
                <form  method="POST" action="{{ route('products.stockline.sorting') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="typ_move">Move Type</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                      </div>
                      <select class="form-control" name="typ_move" id="typ_move">
                        <option value="6" >Manual Stock dispatching</option>
                        <option value="9" >Part delivery</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="qty">Qty :</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-times"></i></span>
                      </div>
                      <input type="number" class="form-control" name="qty" id="qty" placeholder="Ex: 10" max="0" step=".001">
                      <input type="hidden" name="stock_location_products_id" id="stock_location_products_id" value="{{ $StockLocationProduct->id }}">
                      <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}">
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </form>
              <!-- /.card body -->
              </div>
            <!-- /.card secondary -->
            </div>
          <!-- /.col-md-4 -->
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