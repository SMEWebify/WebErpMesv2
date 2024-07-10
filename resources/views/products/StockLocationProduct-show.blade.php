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
        <x-adminlte-card title="{{ __('general_content.stock_location_product_list_trans_key') }}" theme="primary" maximizable>
          <div class="table-responsive p-0">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>{{ __('general_content.user_trans_key') }}</th>
                  <th>{{__('general_content.date_time_trans_key') }}</th>
                  <th>{{ __('general_content.qty_trans_key') }}</th>
                  <th>{{ __('general_content.order_trans_key') }}</th>
                  <th>{{ __('general_content.task_trans_key') }}</th>
                  <th>{{__('general_content.po_receipt_trans_key') }}</th>
                  <th>{{ __('general_content.type_trans_key') }}</th>
                  <th>{{ __('general_content.price_trans_key') }}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($StockMoves as $StockMove)
                <tr>
                  <td>{{ $StockMove->UserManagement['name'] }}</td>
                  <td>{{ $StockMove->GetPrettyCreatedAttribute() }}</td>
                  <td>{{ $StockMove->qty }}</td>
                  <td>
                  @if($StockMove->order_line_id)
                    <x-OrderButton id="{{ $StockMove->OrderLine->order['id'] }}" code="{{ $StockMove->OrderLine->order['code'] }}"  />
                  @endif
                  </td>
                  <td>
                  @if($StockMove->task_id)
                    <a href="{{ route('production.task.statu.id', ['id' => $StockMove->task_id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                  @endif
                  </td>
                  <td>
                  @if($StockMove->purchase_receipt_line_id)
                    <a class="btn btn-primary btn-sm" href="{{ route('purchase.receipts.show', ['id' => $StockMove->purchaseReceiptLines->purchase_receipt_id])}}">
                      <i class="fas fa-folder"></i>
                      {{ $StockMove->purchaseReceiptLines->purchaseReceipt->code }}
                    </a>
                  @endif
                  </td>
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
                  <td>
                    <div class="btn-group btn-group-sm">
                      <a href="{{ route('products.stock.detail.show', ['id' => $StockMove->id])}}" class="btn btn-info"><i class="fa fa-lg fa-fw fa-eye"></i></a>
                    </div>
                  </td>
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
                  <th>{{__('general_content.po_receipt_trans_key') }}</th>
                  <th>{{ __('general_content.type_trans_key') }}</th>
                  <th>{{ __('general_content.price_trans_key') }}</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </x-adminlte-card>
    </div>
    <!-- /.col-md-8 card-secondary-->
    <div class="col-md-4">
      <form  method="POST" action="{{ route('products.stockline.manual.entry') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('general_content.new_entry_stock_trans_key') }}" theme="secondary" maximizable>
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
              <input type="number" class="form-control" name="qty" id="qty" placeholder="Ex: 10" min="1" step=".001">
              <input type="hidden" name="stock_location_products_id" id="stock_location_products_id" value="{{ $StockLocationProduct->id }}">
              <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}">
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span><span class="input-group-text">X</span>
                </div>
                <input type="number" class="form-control"  name="x_size" id="x_size"  placeholder="{{ __('general_content.x_size_trans_key') }}" step=".001">
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span><span class="input-group-text">Y</span>
                </div>
                <input type="number" class="form-control"   name="y_size" id="y_size"  placeholder="{{ __('general_content.y_size_trans_key') }}" step=".001">
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span><span class="input-group-text">Z</span>
                </div>
                <input type="number" class="form-control"  name="z_size" id="z_size"  placeholder="{{ __('general_content.z_size_trans_key') }}" step=".001">
            </div>
          </div>
          <div class="form-group">
            <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span><span class="input-group-text">M²</span>
                    </div>
                    <input type="number" class="form-control"  name="surface_perc" id="surface_perc"  placeholder="M²" step=".001">
                </div>
          </div>
          <div class="form-group">
            <label for="tracability">{{ __('general_content.tracability_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                </div>
                <input type="text" class="form-control"  name="tracability" id="tracability"  placeholder="{{ __('general_content.tracability_trans_key') }}" step=".001">
            </div>
          </div>
          <x-slot name="footerSlot">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        </x-adminlte-card>
      </form>

      <form  method="POST" action="{{ route('products.stockline.sorting') }}" class="form-horizontal">
        <x-adminlte-card title="{{ __('general_content.new_sorting_stock_trans_key') }}" theme="warning" maximizable>
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
              <input type="number" class="form-control" name="qty" id="qty" placeholder="Ex: 10" min="1" step=".001">
              <input type="hidden" name="stock_location_products_id" id="stock_location_products_id" value="{{ $StockLocationProduct->id }}">
              <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}">
            </div>
          </div>
          <div class="form-group">
              <x-adminlte-select2 name="order_line_id" id="order_line_id" label="{{ __('general_content.select_order_line_trans_key') }}" label-class="text-secondary"
                igroup-size="lg" data-placeholder="{{ __('general_content.select_order_line_trans_key') }}">
                <x-slot name="prependSlot">
                    <div class="input-group-text bg-gradient-secondary">
                        <i class="fas fa-list"></i>
                    </div>
                </x-slot>
                <option value="null">{{ __('general_content.select_order_line_trans_key') }}</option>
                @foreach ($OrderLineList as $item)
                <option value="{{ $item->id }}">#{{ $item->id }} - {{ $item->Order->code }}</option>
                @endforeach
              </x-adminlte-select2>
          </div>
          <div class="form-group">
              <x-adminlte-select2 name="task_id" id="task_id" label="{{ __('general_content.task_trans_key') }}" label-class="text-lightblue"
                igroup-size="lg" data-placeholder="{{ __('general_content.select_task_trans_key') }}">
                <x-slot name="prependSlot">
                    <div class="input-group-text bg-gradient-info">
                        <i class="fas fa-list"></i>
                    </div>
                </x-slot>
                <option value="null">{{ __('general_content.select_task_trans_key') }}</option>
                @foreach ($TaskList as $item)
                <option value="{{ $item->id }}">#{{ $item->id }}</option>
                @endforeach
              </x-adminlte-select2>
          </div>
          <div class="form-group">
            <label for="tracability">{{ __('general_content.tracability_trans_key') }}</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                </div>
                <input type="text" class="form-control"  name="tracability" id="tracability"  placeholder="{{ __('general_content.tracability_trans_key') }}" step=".001">
            </div>
          </div>
          <x-slot name="footerSlot">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </x-slot>
        </x-adminlte-card>
      </form>
    <!-- /.card secondary -->
    </div>
    <!-- /.col-md-4 -->
  </div>
  <!-- /.row -->
@stop

@section('css')
@stop

@section('js')
@stop