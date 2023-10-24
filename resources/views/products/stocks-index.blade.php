@extends('adminlte::page')

@section('title', __('general_content.stock_trans_key')) 

@section('content_header')
    <h1>{{ __('general_content.stock_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
    <div class="card card-primary">
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ __('general_content.stock_list_trans_key') }}</h3>
            </div>
            <div class="card-body table-responsive p-0">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.lines_count_trans_key') }}</th>
                    <th>{{__('general_content.created_at_trans_key') }}</th>
                    <th>{{__('general_content.action_trans_key') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($stocks as $stock)
                  <tr>
                    <td>{{ $stock->code }}</td>
                    <td>{{ $stock->label }}</td>
                    <td>{{ $stock->stock_location_count }}</td>
                    
                    <td>{{ $stock->GetPrettyCreatedAttribute() }}</td>
                    <td class="py-0 align-middle">
                      <div class="btn-group btn-group-sm">
                        <a href="{{ route('products.stock.show', ['id' => $stock->id])}}" class="btn bg-primary"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                      </div>
                      <!-- Button Modal -->
                      <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#StockModal{{ $stock->id }}">
                        <i class="fa fa-lg fa-fw  fa-edit"></i>
                      </button>
                      <!-- Modal {{ $stock->id }} -->
                      <x-adminlte-modal id="StockModal{{ $stock->id }}" title="Update {{ $stock->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                        <form method="POST" action="{{ route('products.stock.update', ['id' => $stock->id]) }}" >
                          @csrf
                          <div class="card-body">
                            <div class="form-group">
                              <label for="label">{{__('general_content.label_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $stock->label }}">
                              </div>
                            </div>
                            <div class="form-group">
                              <label for="service_id">{{ __('general_content.user_management_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                  <select class="form-control" name="user_id" id="user_id">
                                    @foreach ($userSelect as $item)
                                    <option value="{{ $item->id }}" @if($stock->user_id == $item->id  ) Selected @endif>{{ $item->name }}</option>
                                    @endforeach
                                  </select>
                              </div>
                            </div>
                          </div>
                          <div class="card-footer">
                            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                          </div>
                        </form>
                      </x-adminlte-modal>
                    </td>
                  </tr>
                  @empty
                  <x-EmptyDataLine col="4" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
                </tbody>
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.external_id_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{__('general_content.lines_count_trans_key') }}</th>
                    <th>{{__('general_content.created_at_trans_key') }}</th>
                    <th>{{__('general_content.action_trans_key') }}</th>
                  </tr>
                </tfoot>
              </table>
            </div>
          <!-- /.card secondary -->
          </div>

          <div class="col-md-6 card-secondary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.new_stock_trans_key') }}</h3>
              </div>
              <div class="card-body">
                <form  method="POST" action="{{ route('products.stock.store') }}" class="form-horizontal">
                  @csrf
                  <div class="form-group">
                    <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="STOCK-{{ $LastStock->id ?? '0' }}">
                    </div>
                  </div>
                  <div class="form-group">
                    <label for="label">{{ __('general_content.description_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label" id="label" placeholder="Description">
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

      <div class="col-md-12 card-info">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.new_stock_trans_key') }}</h3>
        </div>
        <div class="card-body table-responsive p-0">
          <table class="table table-hover">
              <thead>
                  <tr>
                      <th>{{ __('general_content.order_trans_key') }}</th>
                      <th>{{__('general_content.customer_trans_key') }}</th>
                      <th>{{ __('general_content.external_id_trans_key') }}</th>
                      <th>{{__('general_content.label_trans_key') }}</th>
                      <th>{{ __('general_content.qty_trans_key') }}</th>
                      <th>{{ __('general_content.unit_trans_key') }}</th>
                      <th>{{ __('general_content.price_trans_key') }}</th>
                      <th>{{ __('general_content.discount_trans_key') }}</th>
                      <th>{{ __('general_content.vat_trans_key') }}</th>
                      <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                      <th>{{__('general_content.action_trans_key') }}</th>
                  </tr>
              </thead>
              <tbody>
                  @forelse ($InternalOrderRequestsLineslist as $InternalOrderRequestsLines)
                  <tr>
                      <td><x-OrderButton id="{{ $InternalOrderRequestsLines->order['id'] }}" code="{{ $InternalOrderRequestsLines->order['code'] }}"  /></td>
                      <td>{{ __('general_content.internal_order_trans_key') }}</td>
                      <td>{{ $InternalOrderRequestsLines->code }}</td>
                      <td>{{ $InternalOrderRequestsLines->label }}</td>
                      <td>
                          {{ $InternalOrderRequestsLines->delivered_remaining_qty }}
                      </td>
                      <td>{{ $InternalOrderRequestsLines->Unit['label'] }}</td>
                      <td>{{ $InternalOrderRequestsLines->selling_price }}</td>
                      <td>{{ $InternalOrderRequestsLines->discount }}</td>
                      <td>{{ $InternalOrderRequestsLines->VAT['label'] }}</td>
                      <td>{{ $InternalOrderRequestsLines->delivery_date }}</td>
                      <td>
                        <form  method="POST" action="{{ route('products.stockline.store.from.internal.order') }}" class="form-horizontal">
                          @csrf
                          <input type="hidden" name="products_id" id="products_id" value="{{ $InternalOrderRequestsLines->product_id }}">
                          <input type="hidden" name="code" id="code" value="STOCK-{{ $InternalOrderRequestsLines->label }}">
                          <input type="hidden" name="stock_qty" id="stock_qty" value="{{ $InternalOrderRequestsLines->delivered_remaining_qty }}" >
                          <input type="hidden" name="mini_qty" id="mini_qty" value="{{ $InternalOrderRequestsLines->delivered_remaining_qty }}" >
                          <input type="hidden" name="component_price" id="component_price" value="{{ $InternalOrderRequestsLines->selling_price }}" >
                          <input type="hidden" name="order_line_id" id="order_line_id" value="{{ $InternalOrderRequestsLines->id }}" >
                          <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}" >
                          <div class="form-group">
                            <label for="stock_locations_id">Chose stock location</label>
                            <div class="input-group">
                              <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                              </div>
                              <select class="form-control" name="stock_locations_id" id="stock_locations_id">
                                @forelse ($StockLocationList as $StockLocation)
                                <option value="{{ $StockLocation->id }}">Stock : {{ $StockLocation->Stocks->code }}| Location : {{ $StockLocation->code }} </option>
                                @endforeach
                              </select>
                              <x-adminlte-button class="btn-flat" type="submit" label="Add" theme="success" icon="fas fa-lg fa-save"/>
                            </div>
                          </div>
                          
                        </form>
                      </td>
                  </tr>
                  @empty
                      <x-EmptyDataLine col="14" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
              </tbody>
              <tfoot>
                  <tr>
                      <th>{{ __('general_content.order_trans_key') }}</th>
                      <th>{{__('general_content.customer_trans_key') }}</th>
                      <th>{{ __('general_content.external_id_trans_key') }}</th>
                      <th>{{__('general_content.label_trans_key') }}</th>
                      <th>{{ __('general_content.description_trans_key') }}</th>
                      <th>{{ __('general_content.qty_trans_key') }}</th>
                      <th>{{ __('general_content.unit_trans_key') }}</th>
                      <th>{{ __('general_content.price_trans_key') }}</th>
                      <th>{{ __('general_content.discount_trans_key') }}</th>
                      <th>{{ __('general_content.vat_trans_key') }}</th>
                      <th>{{ __('general_content.delivery_date_trans_key') }}</th>
                      <th>{{__('general_content.action_trans_key') }}</th>
                  </tr>
              </tfoot>
          </table>
      </div>
    </div>
@stop

@section('css')
@stop

@section('js')

@stop