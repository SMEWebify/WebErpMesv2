@extends('adminlte::page')

@section('title', __('general_content.po_receipt_trans_key')) 

@section('content_header')
  <x-Content-header-previous-button  h1="{{ __('general_content.po_receipt_trans_key') }}: {{  $PurchaseReceipt->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases.receipt') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-toggle="tab">{{ __('general_content.purchase_receipt_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">{{ __('general_content.purchase_receipt_lines_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('receipt.update', ['id' => $PurchaseReceipt->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="form-group col-md-3">
                        <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $PurchaseReceipt->code }}
                      </div>
                      <div class="form-group col-md-3">
                        <x-adminlte-select name="statu" label="{{ __('general_content.status_trans_key') }}" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $PurchaseReceipt->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                          <option value="2" @if(2 == $PurchaseReceipt->statu ) Selected @endif >{{ __('general_content.stock_trans_key') }}</option>
                        </x-adminlte-select>
                      </div>
                      <div class="form-group col-md-3">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_purchase_reciept_trans_key'), 'Value' =>  $PurchaseReceipt->label])
                      </div>

                      <div class="form-group col-md-3">
                        <x-adminlte-input name="delivery_note_number" label="{{ __('general_content.delivery_note_number_trans_key') }}" placeholder="{{ __('general_content.delivery_note_number_trans_key') }}" value="{{  $PurchaseReceipt->delivery_note_number }}" label-class="text-success">
                          <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-tags"></i>
                              </div>
                          </x-slot>
                        </x-adminlte-input>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">{{ __('general_content.supplier_info_trans_key') }}</label>
                    </div>
                    <div class="row">
                      <div class="form-group col-md-5">
                        <label for="companies_id">{{ __('general_content.companie_trans_key') }}</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <a class="btn btn-primary btn-sm" href="{{ route('companies.show', ['id' => $PurchaseReceipt->companie->id])}}">
                              <i class="fas fa-buildin"></i>
                              {{  $PurchaseReceipt->companie->code }} - {{  $PurchaseReceipt->companie->label }}
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $PurchaseReceipt->comment }}" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                    <td style="width:50%"> 
                    {{ __('general_content.delevery_time_trans_key') }}
                    </td>
                    <td>
                      <td>{{ $averageReceptionDelay }}
                    </td>
                  </tr>
                </table>
              </div>
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                      <td style="width:50%"> 
                        {{ __('general_content.po_receipt_trans_key') }}
                      </td>
                      <td>
                        <x-ButtonTextPDF route="{{ route('pdf.receipt', ['Document' => $PurchaseReceipt->id])}}" />
                      </td>
                  </tr>
                </table>
              </div>
            </x-adminlte-card>

            @include('include.file-store', ['inputName' => "purchase_receipts_id",'inputValue' => $PurchaseReceipt->id,'filesList' => $PurchaseReceipt->files,])
          </div>
        </div>
      </div>    
      <div class="tab-pane " id="PurchaseLines">
        <!-- Table row -->
        <div class="row">
          <div class="col-12 table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>{{ __('general_content.order_trans_key') }}</th>
                  <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                  <th>{{ __('general_content.qty_trans_key') }}</th>
                  <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                  <th>{{__('general_content.label_trans_key') }}</th>
                  <th>{{ __('general_content.product_trans_key') }}</th>
                  <th>{{ __('general_content.qty_trans_key') }}</th>
                  <th>{{ __('general_content.qty_purchase_trans_key') }}</th>
                  <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                  <th>{{__('general_content.action_trans_key') }}</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($PurchaseReceipt->PurchaseReceiptLines as $PurchaseReceiptLine)
                  <tr>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks->OrderLines ?? null)
                        <x-OrderButton id="{{ $PurchaseReceiptLine->purchaseLines->tasks->OrderLines->orders_id }}" code="{{ $PurchaseReceiptLine->purchaseLines->tasks->OrderLines->order->code }}"  />
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('purchases.show', ['id' => $PurchaseReceiptLine->purchaseLines->purchase->id])}}">
                        <i class="fas fa-folder"></i>
                        {{ $PurchaseReceiptLine->purchaseLines->purchase->code }}
                    </a>
                    </td>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks->OrderLines ?? null)
                        {{ $PurchaseReceiptLine->purchaseLines->tasks->OrderLines->qty }} x 
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks->OrderLines ?? null)
                        {{ $PurchaseReceiptLine->purchaseLines->tasks->OrderLines->label }}
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks_id ?? null)
                        <a href="{{ route('production.task.statu.id', ['id' => $PurchaseReceiptLine->purchaseLines->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                        #{{ $PurchaseReceiptLine->purchaseLines->tasks->id }} - {{ $PurchaseReceiptLine->purchaseLines->tasks->label }}
                        @if($PurchaseReceiptLine->purchaseLines->tasks->component_id )
                            - {{ $PurchaseReceiptLine->purchaseLines->tasks->Component['label'] }}
                        @endif
                      @else
                          {{ $PurchaseReceiptLine->purchaseLines->label }}
                      @endif
                    </td>
                    <td>
                      @if($PurchaseReceiptLinepurchaseLines->tasks_id ?? null)
                          @if($PurchaseReceiptLine->purchaseLines->tasks->component_id ) 
                          <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseReceiptLine->purchaseLines->tasks->component_id])}}" />
                          @endif
                      @else
                          @if($PurchaseReceiptLine->purchaseLines->product_id ) 
                              <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseReceiptLine->purchaseLines->product_id])}}" />
                          @endif
                      @endif
                    </td>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks_id ?? null)
                        {{ $PurchaseReceiptLine->purchaseLines->tasks->qty  }} 
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>{{ $PurchaseReceiptLine->purchaseLines->qty  }}</td>
                    <td>{{ $PurchaseReceiptLine->receipt_qty }}</td>
                    
                    <td>
                        @if($PurchaseReceiptLine->purchaseLines->tasks->component_id ?? null || $PurchaseReceiptLine->purchaseLines->product_id ?? null)
                          @if(empty($PurchaseReceiptLine->stock_location_products_id))

                          @php
                            if($PurchaseReceiptLine->purchaseLines->tasks->component_id ?? null){
                              $productId = $PurchaseReceiptLine->purchaseLines->tasks->component_id;
                              $taskId = $PurchaseReceiptLine->purchaseLines->tasks_id;
                            }
                            elseif($PurchaseReceiptLine->purchaseLines->product_id ?? null){
                              $productId = $PurchaseReceiptLine->purchaseLines->product_id;
                              $taskId = null;
                            }
                          @endphp

                          <form  method="POST" action="{{ route('products.stockline.store.from.purchase.order') }}" class="form-horizontal">
                            @csrf
                            <input type="hidden" name="products_id" id="products_id" value="{{ $productId }}">
                            <input type="hidden" name="code" id="code" value="STOCK|{{ $PurchaseReceiptLine->purchaseReceipt->code }}|{{ $PurchaseReceiptLine->id }}|{{ now()->format('Y-m-d') }}">
                            <input type="hidden" name="stock_qty" id="stock_qty" value="{{ $PurchaseReceiptLine->receipt_qty }}" >
                            <input type="hidden" name="mini_qty" id="mini_qty" value="{{ $PurchaseReceiptLine->receipt_qty }}" >
                            <input type="hidden" name="component_price" id="component_price" value="{{ $PurchaseReceiptLine->purchaseLines->selling_price }}" >
                            <input type="hidden" name="task_id" id="task_id" value="{{ $taskId }}" >
                            <input type="hidden" name="purchase_receipt_line_id" id="purchase_receipt_line_id" value="{{ $PurchaseReceiptLine->id }}" >
                            <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}" >
                            <div class="form-group">
                              <label for="stock_locations_id">{{ __('general_content.stock_location_list_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control" name="stock_locations_id" id="stock_locations_id">
                                  @forelse ($StockLocationList as $StockLocation)
                                  <option value="{{ $StockLocation->id }}">{{ __('general_content.stock_trans_key') }} : {{ $StockLocation->Stocks->code }}| {{ __('general_content.location_trans_key') }} : {{ $StockLocation->code }} </option>
                                  @empty
                                  <option value="">{{ __('general_content.no_stock_location_trans_key') }}</option>
                                  @endforelse
                                </select>
                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.new_stock_trans_key') }}" theme="success" icon="fas fa-lg fa-save"/>
                              </div>
                            </div>
                          </form>
                          <form  method="POST" action="{{ route('products.stockline.entry.from.purchase.order') }}" class="form-horizontal">
                            @csrf
                            <input type="hidden" name="user_id" id="user_id" value="{{ auth()->user()->id }}" >
                            <input type="hidden" name="qty" id="qty" value="{{ $PurchaseReceiptLine->receipt_qty }}" >
                            <input type="hidden" name="task_id" id="task_id" value="{{ $taskId }}" >
                            <input type="hidden" name="purchase_receipt_line_id" id="purchase_receipt_line_id" value="{{ $PurchaseReceiptLine->id }}" >
                            <input type="hidden" name="component_price" id="component_price" value="{{ $PurchaseReceiptLine->purchaseLines->selling_price }}" >
                            <input type="hidden" name="typ_move" id="typ_move" value="3" >
                            <div class="form-group">
                              <label for="stock_location_products_id">{{ __('general_content.stock_location_product_list_trans_key') }}</label>
                              <div class="input-group">
                                <div class="input-group-prepend">
                                  <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control" name="stock_location_products_id" id="stock_location_products_id">
                                  @forelse ($StockLocationProductList as $StockLocationProduct)
                                      @if($StockLocationProduct->products_id == $productId)
                                        <option value="{{ $StockLocationProduct->id }}">{{ __('general_content.stock_trans_key') }} : {{ $StockLocationProduct->code }}| {{ __('general_content.location_trans_key') }} : {{ $StockLocation->code }} </option>
                                      @endif
                                    @empty
                                  <option value="">{{ __('general_content.no_stock_location_trans_key') }}</option>
                                  @endforelse
                                </select>
                                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.new_entry_stock_trans_key') }}" theme="success" icon="fas fa-lg fa-save"/>
                              </div>
                            </div>
                          </form>
                          @else
                          <a href="{{ route('products.stockline.show', ['id' => $PurchaseReceiptLine->stock_location_products_id])}}" class="btn btn-sm btn-success">{{ $PurchaseReceiptLine->StockLocationProducts->code}} </a>
                          @endif
                        @else
                        {{ __('general_content.no_product_in_line_stock_trans_key') }}
                        @endif
                    </td>
                  </tr>
                @empty
                  <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}."  />
                @endforelse
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }} {{__('general_content.label_trans_key') }}</th>
                    <th>{{__('general_content.label_trans_key') }}</th>
                    <th>{{ __('general_content.product_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.qty_purchase_trans_key') }}</th>
                    <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                    <th>{{__('general_content.action_trans_key') }}</th>
                  </tr>
                </tfoot>
              </tbody>
            </table>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
@stop