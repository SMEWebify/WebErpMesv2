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
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">{{ __('general_content.purchase_receipt_lines_trans_key') }}  ({{ count($PurchaseReceipt->PurchaseReceiptLines) }})</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            @if($PurchaseReceipt->companie->recept_controle == 1 && $PurchaseReceipt->reception_controlled == 0)
            <x-adminlte-alert theme="warning" title="Warning">
              {{ __('general_content.po_receipt_note_trans_key') }} 
              <form action="{{ route('purchase.receipts.reception_control', $PurchaseReceipt->id) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">{{ __('general_content.validate_control_trans_key') }}</button>
              </form>
            </x-adminlte-alert>
            @endif
            <form method="POST" action="{{ route('receipt.update', ['id' => $PurchaseReceipt->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf 
                    <div class="row">
                      <div class="form-group col-md-6">
                        <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $PurchaseReceipt->code }}</p>
                        <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $PurchaseReceipt->GetshortCreatedAttribute() }}</p>
                      </div>
                      <div class="form-group col-md-6">
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
                    </div>
                    <div class="row">
                      <div class="form-group col-md-6">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_purchase_reciept_trans_key'), 'Value' =>  $PurchaseReceipt->label])
                      </div>

                      <div class="form-group col-md-6">
                        <x-adminlte-input name="delivery_note_number" label="{{ __('general_content.delivery_note_number_trans_key') }}" placeholder="{{ __('general_content.delivery_note_number_trans_key') }}" value="{{  $PurchaseReceipt->delivery_note_number }}" label-class="text-success">
                          <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-tags"></i>
                              </div>
                          </x-slot>
                        </x-adminlte-input>
                      </div>
                    </div>
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $PurchaseReceipt->comment }}" />
                    </div>
                  <div class="modal-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" maximizable>
              <div class="card-body">
                {{ __('general_content.created_at_trans_key') }} :  {{ $PurchaseReceipt->GetPrettyCreatedAttribute() }}
              </div>
              <div class="card-body">
                {{ __('general_content.companie_name_trans_key') }} :  <x-CompanieButton id="{{ $PurchaseReceipt->companie['id'] }}" label="{{ $PurchaseReceipt->companie['label'] }}"  />
              </div>
              <div class="card-body">
                {{ __('general_content.delevery_time_trans_key') }} :  {{ $averageReceptionDelay }}
              </div>
              @if($PurchaseReceipt->companie->recept_controle == 1 && $PurchaseReceipt->reception_controlled == 1)
              <div class="card-body">
                {{ __('general_content.reception_control_trans_key') }} :  {{ $PurchaseReceipt->UserReceptionControl['name'] }} - {{ $PurchaseReceipt->GetPrettyControlDateAttribute() }}
              </div>
              @endif
            </x-adminlte-card>

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" collapsible="collapsed" maximizable>
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
        <div class="card mb-3">
          <div class="card-body">
            <form method="POST" action="{{ route('purchase.receipts.lines.manual', $PurchaseReceipt->id) }}">
              @csrf
              <div class="form-row align-items-end">
                <div class="form-group col-md-6">
                  <label for="manual_product_id">{{ __('general_content.product_trans_key') }}</label>
                  <select class="form-control" name="product_id" id="manual_product_id">
                    <option value="">{{ __('general_content.select_option_trans_key') }}</option>
                    @foreach ($productSelect as $product)
                      <option value="{{ $product->id }}">{{ $product->code }} - {{ $product->label }}</option>
                    @endforeach
                  </select>
                  @error('product_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-3">
                  <label for="manual_qty">{{ __('general_content.qty_trans_key') }}</label>
                  <input type="number" min="1" class="form-control" name="qty" id="manual_qty" value="{{ old('qty', 1) }}">
                  @error('qty') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="form-group col-md-3">
                  <button type="submit" class="btn btn-outline-primary btn-block">{{ __('general_content.add_manual_receipt_line_trans_key') }}</button>
                </div>
              </div>
            </form>
          </div>
        </div>
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
                  <th>{{ __('general_content.qty_accepted_trans_key') }}</th>
                  <th>{{ __('general_content.quantity_rejected_trans_key') }}</th>
                  <th>{{ __('general_content.inspection_result_trans_key') }}</th>
                  <th>{{ __('general_content.inspection_date_trans_key') }}</th>
                  <th>{{ __('general_content.inspected_by_trans_key') }}</th>
                  <th>{{ __('general_content.non_conformitie_trans_key') }}</th>
                  <th>{{__('general_content.action_trans_key') }}</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($PurchaseReceipt->PurchaseReceiptLines as $PurchaseReceiptLine)
                  @php
                    $task = $PurchaseReceiptLine->purchaseLines->tasks;
                  @endphp
                  <tr>
                    <td>
                      @if(optional($task)->OrderLines)
                        <x-OrderButton id="{{ $task->OrderLines->orders_id }}" code="{{ $task->OrderLines->order->code }}"  />
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
                      @if(optional($task)->OrderLines)
                        {{ $task->OrderLines->qty }} x {{ $task->qty }}
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>
                      @if(optional($task)->OrderLines)
                        {{ $task->OrderLines->label }}
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks_id ?? null)
                        <a href="{{ route('production.task.statu.id', ['id' => $task->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                        #{{ $task->id }} - {{ $task->label }}
                        @if($task?->component_id)
                            - {{ $task->Component['label'] }}
                        @endif
                      @else
                          {{ $PurchaseReceiptLine->purchaseLines->label }}
                      @endif
                    </td>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks_id ?? null)
                          @if($task?->component_id) 
                          <x-ButtonTextView route="{{ route('products.show', ['id' => $task->component_id])}}" />
                          @endif
                      @else
                          @if($PurchaseReceiptLine->purchaseLines->product_id ) 
                              <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseReceiptLine->purchaseLines->product_id])}}" />
                          @endif
                      @endif
                    </td>
                    <td>
                      @if($PurchaseReceiptLine->purchaseLines->tasks_id ?? null)
                        {{ number_format($task->getQualityRequiredAttribute(), 0, '', ' ')  }} 
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>{{ number_format($PurchaseReceiptLine->purchaseLines->qty, 0, '', ' ') }}</td>
                    <td>{{ number_format($PurchaseReceiptLine->receipt_qty, 0, '', ' ') }}</td>
                    <td>{{ number_format($PurchaseReceiptLine->accepted_qty ?? 0, 0, '', ' ') }}</td>
                    <td>{{ number_format($PurchaseReceiptLine->rejected_qty ?? 0, 0, '', ' ') }}</td>
                    <td>{{ $PurchaseReceiptLine->inspection_result ?? __('general_content.no_data_trans_key') }}</td>
                    <td>
                      @if($PurchaseReceiptLine->inspection_date)
                        {{ $PurchaseReceiptLine->inspection_date->format('d/m/Y') }}
                      @else
                        {{ __('general_content.no_data_trans_key') }}
                      @endif
                    </td>
                    <td>{{ optional($PurchaseReceiptLine->inspector)->name ?? __('general_content.no_data_trans_key') }}</td>
                    <td>
                      @if($PurchaseReceiptLine->qualityNonConformity)
                        {{ $PurchaseReceiptLine->qualityNonConformity->code }}
                      @else
                        {{ __('general_content.no_data_trans_key') }}
                      @endif
                    </td>

                    <td>
                      <div class="mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#purchaseReceiptLineInspection{{ $PurchaseReceiptLine->id }}">
                          <i class="fas fa-clipboard-check"></i> {{ __('general_content.inspection_details_trans_key') }}
                        </button>
                      </div>
                      <x-adminlte-modal id="purchaseReceiptLineInspection{{ $PurchaseReceiptLine->id }}" title="{{ __('general_content.inspection_details_trans_key') }}" theme="teal" icon="fas fa-clipboard-check" size='lg' disable-animations>
                        <form method="POST" action="{{ route('purchase.receipts.lines.update', $PurchaseReceiptLine->id) }}">
                          @csrf
                          <div class="form-row">
                            <div class="form-group col-md-6">
                              <label for="inspected_by_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.inspected_by_trans_key') }}</label>
                              <select class="form-control" name="inspected_by" id="inspected_by_{{ $PurchaseReceiptLine->id }}">
                                <option value="">{{ __('general_content.select_option_trans_key') }}</option>
                                @foreach ($userSelect as $user)
                                  <option value="{{ $user->id }}" @if($PurchaseReceiptLine->inspected_by == $user->id) selected @endif>{{ $user->name }}</option>
                                @endforeach
                              </select>
                            </div>
                            <div class="form-group col-md-6">
                              <label for="inspection_date_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.inspection_date_trans_key') }}</label>
                              <input type="date" class="form-control" name="inspection_date" id="inspection_date_{{ $PurchaseReceiptLine->id }}" value="{{ optional($PurchaseReceiptLine->inspection_date)->format('Y-m-d') }}">
                            </div>
                          </div>
                          <div class="form-row">
                            <div class="form-group col-md-4">
                              <label for="accepted_qty_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.qty_accepted_trans_key') }}</label>
                              <input type="number" min="0" class="form-control" name="accepted_qty" id="accepted_qty_{{ $PurchaseReceiptLine->id }}" value="{{ $PurchaseReceiptLine->accepted_qty }}">
                              @error('accepted_qty') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-group col-md-4">
                              <label for="rejected_qty_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.quantity_rejected_trans_key') }}</label>
                              <input type="number" min="0" class="form-control" name="rejected_qty" id="rejected_qty_{{ $PurchaseReceiptLine->id }}" value="{{ $PurchaseReceiptLine->rejected_qty }}">
                              @error('rejected_qty') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-group col-md-4">
                              <label for="quality_non_conformity_id_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.non_conformitie_trans_key') }}</label>
                              <select class="form-control" name="quality_non_conformity_id" id="quality_non_conformity_id_{{ $PurchaseReceiptLine->id }}">
                                <option value="">{{ __('general_content.select_option_trans_key') }}</option>
                                @foreach ($nonConformities as $nonConformity)
                                  <option value="{{ $nonConformity->id }}" @if($PurchaseReceiptLine->quality_non_conformity_id == $nonConformity->id) selected @endif>{{ $nonConformity->code }}</option>
                                @endforeach
                              </select>
                            </div>
                          </div>
                          <div class="form-row">
                            <div class="form-group col-md-12">
                              <label for="inspection_result_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.inspection_result_trans_key') }}</label>
                              <input type="text" class="form-control" name="inspection_result" id="inspection_result_{{ $PurchaseReceiptLine->id }}" value="{{ $PurchaseReceiptLine->inspection_result }}">
                            </div>
                          </div>
                          <div class="form-row">
                            <div class="col-md-12">
                              <div class="border rounded p-3">
                                <div class="custom-control custom-switch mb-3">
                                  <input type="checkbox" class="custom-control-input" id="create_nc_{{ $PurchaseReceiptLine->id }}" name="create_non_conformity" value="1">
                                  <label class="custom-control-label" for="create_nc_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.quick_nc_creation_trans_key') }}</label>
                                </div>
                                <div class="form-row">
                                  <div class="form-group col-md-6">
                                    <label for="new_nc_label_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.label_trans_key') }}</label>
                                    <input type="text" class="form-control" name="new_nc_label" id="new_nc_label_{{ $PurchaseReceiptLine->id }}" placeholder="{{ __('general_content.label_trans_key') }}">
                                  </div>
                                  <div class="form-group col-md-6">
                                    <label for="new_nc_comment_{{ $PurchaseReceiptLine->id }}">{{ __('general_content.comment_trans_key') }}</label>
                                    <textarea class="form-control" rows="2" name="new_nc_comment" id="new_nc_comment_{{ $PurchaseReceiptLine->id }}" placeholder="{{ __('general_content.comment_trans_key') }}"></textarea>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="submit" class="btn btn-success">{{ __('general_content.update_trans_key') }}</button>
                          </div>
                        </form>
                      </x-adminlte-modal>
                        @if($task?->component_id || $PurchaseReceiptLine->purchaseLines->product_id ?? null)
                          @if(empty($PurchaseReceiptLine->stock_location_products_id))
                            @php
                              if($task?->component_id){
                                $productId = $task->component_id;
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
                              <input type="hidden" name="user_id" id="user_id" value="{{ Auth::id() }}" >
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
                            @php
                              $filteredStockLocationProductList = $StockLocationProductList->filter(function($StockLocationProduct) use ($productId) {
                                  return $StockLocationProduct->products_id == $productId;
                              });
                            @endphp
                            @if($filteredStockLocationProductList->isNotEmpty())
                              <form  method="POST" action="{{ route('products.stockline.entry.from.purchase.order') }}" class="form-horizontal">
                                @csrf
                                <input type="hidden" name="user_id" id="user_id" value="{{ Auth::id() }}" >
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
                                      @forelse ($filteredStockLocationProductList as $StockLocationProduct)
                                        <option value="{{ $StockLocationProduct->id }}">{{ __('general_content.location_trans_key') }} : {{ $StockLocationProduct->StockLocation->code }} | {{ __('general_content.stock_trans_key') }} : {{ $StockLocationProduct->code }} </option>
                                      @empty
                                        <option value="">{{ __('general_content.no_stock_location_trans_key') }}</option>
                                      @endforelse
                                    </select>
                                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.new_entry_stock_trans_key') }}" theme="success" icon="fas fa-lg fa-save"/>
                                  </div>
                                </div>
                              </form>
                            @endif
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
                    <th>{{ __('general_content.qty_accepted_trans_key') }}</th>
                    <th>{{ __('general_content.quantity_rejected_trans_key') }}</th>
                    <th>{{ __('general_content.inspection_result_trans_key') }}</th>
                    <th>{{ __('general_content.inspection_date_trans_key') }}</th>
                    <th>{{ __('general_content.inspected_by_trans_key') }}</th>
                    <th>{{ __('general_content.non_conformitie_trans_key') }}</th>
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
