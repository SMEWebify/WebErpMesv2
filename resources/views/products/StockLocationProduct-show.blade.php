@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key')) 

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>{{ $StockLocationProduct->code }}  {{ __('general_content.stock_location_trans_key') }}</h1>
      </div>
      <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('products.stock') }}">{{ __('general_content.stock_list_trans_key') }}</a></li>
              <li class="breadcrumb-item"><a href="{{ route('products.stock.show', ['id' => $StockLocation->stocks_id]) }}">{{ __('general_content.stock_trans_key') }} {{ $Stock->label }}</a></li>
              <li class="breadcrumb-item"><a href="{{ route('products.stocklocation.show', ['id' => $StockLocationProduct->stock_locations_id]) }}">{{ __('general_content.stock_location_trans_key') }} {{ $StockLocation->label }}</a></li>
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
                <x-adminlte-info-box title="Current {{__('general_content.qty_trans_key') }}" text="{{ $StockLocationProduct->getCurrentStockMove() }} item(s)" icon="fa fa-database" theme="success"/>
              </div>
            </div>
            <div class="card-header">
                <h3 class="card-title">{{ __('general_content.stock_location_product_list_trans_key') }}</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{__('general_content.date_time_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.task_trans_key') }}</th>
                    <th>{{__('general_content.purchase_trans_key') }}</th>
                    <th>{{ __('general_content.type_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
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
                      @if(1 == $StockMove->typ_move ){{__('general_content.inventories_trans_key') }} @endif
                      @if(2 == $StockMove->typ_move ){{__('general_content.task_allocation_trans_key') }} @endif
                      @if(3 == $StockMove->typ_move ){{__('general_content.purchase_order_reception_trans_key') }} @endif
                      @if(4 == $StockMove->typ_move ){{__('general_content.inter_stock_mvts_trans_key') }} @endif
                      @if(5 == $StockMove->typ_move ){{__('general_content.manual_stock_recep_trans_key') }} @endif
                      @if(6 == $StockMove->typ_move ){{__('general_content.manual_stock_dispatching_trans_key') }} @endif
                      @if(7 == $StockMove->typ_move ){{__('general_content.reservation_trans_key') }} @endif
                      @if(8 == $StockMove->typ_move ){{__('general_content.reservation_cancellation_trans_key') }} @endif
                      @if(9 == $StockMove->typ_move ){{__('general_content.part_delivery_trans_key') }} @endif
                      @if(10 == $StockMove->typ_move ){{__('general_content.in_production_trans_key') }} @endif
                      @if(11 == $StockMove->typ_move ){{__('general_content.reservation_component_production_trans_key') }} @endif
                      @if(12 == $StockMove->typ_move ){{__('general_content.manufactured_component_entry_trans_key') }} @endif
                      @if(13 == $StockMove->typ_move ){{__('general_content.direct_inventory_trans_key') }} @endif
                    </td>
                    <td></td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{__('general_content.date_time_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.task_trans_key') }}</th>
                    <th>{{__('general_content.purchase_trans_key') }}</th>
                    <th>{{ __('general_content.type_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
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
                <h3 class="card-title">{{__('general_content.new_entry_stock_trans_key') }}</h3>
              </div>
              <div class="card-body">
                <form  method="POST" action="{{ route('products.stockline.entry') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="typ_move">{{__('general_content.move_Type_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                      </div>
                      <select class="form-control" name="typ_move" id="typ_move">
                        <option value="5" >{{__('general_content.manual_stock_recep_trans_key') }}</option>
                        <option value="1" >{{__('general_content.inventories_trans_key') }}</option>
                        <option value="3" >{{__('general_content.purchase_order_reception_trans_key') }}</option>
                        <option value="12" >{{__('general_content.manufactured_component_entry_trans_key') }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="qty">{{__('general_content.qty_trans_key') }} :</label>
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
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                  </div>
                </form>
              <!-- /.card body -->
              </div>
            <!-- /.card secondary -->
            </div>

            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title">{{__('general_content.new_sorting_stock_trans_key') }}</h3>
              </div>

              <div class="card-body">
                <form  method="POST" action="{{ route('products.stockline.sorting') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="typ_move">{{__('general_content.move_Type_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-list"></i></span>
                      </div>
                      <select class="form-control" name="typ_move" id="typ_move">
                        <option value="6" >{{__('general_content.manual_stock_dispatching_trans_key') }}</option>
                        <option value="9" >{{__('general_content.part_delivery_trans_key') }}</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="qty">{{__('general_content.qty_trans_key') }} :</label>
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
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
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