@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key')) 

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>{{ $StockLocation->label }} {{__('general_content.stock_location_trans_key') }}</h1> 
      </div>
      <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ route('products.stock') }}">{{ __('general_content.stock_list_trans_key') }}</a></li>
              <li class="breadcrumb-item"><a href="{{ route('products.stock.show', ['id' => $StockLocation->stocks_id]) }}">{{__('general_content.stock_trans_key') }} {{ $Stock->label }}</a></li>
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
            <div class="card-header">
                <h3 class="card-title">{{ __('general_content.stock_location_product_list_trans_key') }}</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{__('general_content.id_trans_key') }}</th>
                    <th>{{ __('general_content.user_management_trans_key') }}</th>
                    <th>{{ __('general_content.product_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <!--<th>Qty reserve</th>-->
                    <th>{{ __('general_content.qty_mini_trans_key') }}</th>
                    <th>{{ __('general_content.end_date_trans_key') }}</th>
                    <th>{{ __('general_content.addressing_trans_key') }}</th>
                    <th>{{__('general_content.action_trans_key') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($StockLocationsProducts as $StockLocationsProduct)
                
                  <tr>
                    <td>{{ $StockLocationsProduct->code }}</td>
                    <td>{{ $StockLocationsProduct->UserManagement['name'] }}</td>
                    <td>{{ $StockLocationsProduct->Product['label'] }}</td>
                    <td>{{ $StockLocationsProduct->getCurrentStockMove() }}</td>
                    <!--<td>{{ $StockLocationsProduct->reserve_qty }}</td>-->
                    <td>{{ $StockLocationsProduct->mini_qty }}</td>
                    <td>{{ $StockLocationsProduct->end_date }}</td>
                    <td>{{ $StockLocationsProduct->addressing }}</td>
                    <td class=" py-0 align-middle">
                      <div class="btn-group btn-group-sm">
                        <a href="{{ route('products.stockline.show', ['id' => $StockLocationsProduct->id])}}" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                      </div>
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#StockLocationsProduct{{ $StockLocationsProduct->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $StockLocationsProduct->id }} -->
                      <x-adminlte-modal id="StockLocationsProduct{{ $StockLocationsProduct->id }}" title="Update {{ $StockLocationsProduct->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('products.stockline.update', ['id' => $StockLocationsProduct->id]) }}" >
                          @csrf
                            <div class="form-group">
                              <label for="service_id">{{ __('general_content.user_management_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                <select class="form-control" name="user_id" id="user_id">
                                  @foreach ($userSelect as $item)
                                  <option value="{{ $item->id }}" @if($StockLocationsProduct->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                  @endforeach
                                </select>
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="mini_qty">{{ __('general_content.qty_mini_trans_key') }} :</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-times"></i></span>
                                </div>
                                <input type="number" class="form-control" name="mini_qty" id="mini_qty" placeholder="{{ __('general_content.qty_mini_trans_key') }} ex: 1.50" step=".001" value="{{ $StockLocationsProduct->mini_qty }}">
                                <input type="hidden" name="stock_locations_id"  id="stock_locations_id"  value="{{ $StockLocationsProduct->stock_locations_id }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                              <input type="date" class="form-control" name="end_date"  id="end_date" value="{{ $StockLocationsProduct->end_date }}">
                            </div>
                            <div class="form-group">
                              <label for="addressing">{{ __('general_content.addressing_trans_key') }}</label>
                              <input type="text" class="form-control" name="addressing" id="addressing" placeholder="{{ __('general_content.addressing_trans_key') }}" value="{{ $StockLocationsProduct->addressing }}">
                            </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                      </x-adminlte-modal>
                    </td>
                  </tr>
                  @empty
                  <x-EmptyDataLine col="9" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{__('general_content.id_trans_key') }}</th>
                    <th>{{ __('general_content.user_management_trans_key') }}</th>
                    <th>{{ __('general_content.product_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <!--<th>Qty reserve</th>-->
                    <th>{{ __('general_content.qty_mini_trans_key') }}</th>
                    <th>{{ __('general_content.end_date_trans_key') }}</th>
                    <th>{{ __('general_content.addressing_trans_key') }}</th>
                    <th>{{__('general_content.action_trans_key') }}</th>
                  </tr>
                </tfoot>
              </table>
            <!-- /.card-body-->
            </div>
          <!-- /.col-md-8 card-secondary-->
          </div>
          <div class="col-md-4 card-secondary">
            <div class="card-header">
              <h3 class="card-title">{{ __('general_content.new_stock_location_product_trans_key') }}</h3>
            </div>

            <div class="card-body">
              <form  method="POST" action="{{ route('products.stockline.store') }}" class="form-horizontal">
                @csrf
                <div class="form-group">
                  <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                    </div>
                    <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="STOCK-PRODUCT-{{ $LastStockLocationProduct->id ?? '0' }}">
                    <input type="hidden" name="stock_locations_id" id="stock_locations_id" value="{{ $StockLocation->id }}">
                  </div>
                </div>
                <div class="form-group">
                  <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
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
                  <label for="products_id">{{ __('general_content.product_trans_key') }}</label> 
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                    </div>
                    <select class="form-control" name="products_id" id="products_id">
                      @foreach ($ProductSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->code }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label for="mini_qty">{{ __('general_content.qty_mini_trans_key') }} :</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-times"></i></span>
                    </div>
                    <input type="number" class="form-control" name="mini_qty" id="mini_qty" placeholder="{{ __('general_content.qty_mini_trans_key') }} ex: 1.50" step=".001">
                  </div>
                </div>
                <div class="form-group">
                  <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
                  <input type="date" class="form-control" name="end_date"  id="end_date" >
                </div>
                <div class="form-group">
                  <label for="addressing">{{ __('general_content.addressing_trans_key') }}</label>
                  <input type="text" class="form-control" name="addressing" id="addressing" placeholder="{{ __('general_content.addressing_trans_key') }}">
                </div>
                <div class="card-footer">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
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