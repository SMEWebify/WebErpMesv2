@extends('adminlte::page')

@section('title', __('general_content.invoice_supplier_trans_key'))

@section('content_header')
  <x-Content-header-previous-button  h1="{{ __('general_content.invoice_supplier_trans_key') }}: {{  $PurchaseInvoice->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases.invoice') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-toggle="tab">{{ __('general_content.purchase_invoice_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">{{ __('general_content.purchase_invoice_lines_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('invoice.update', ['id' => $PurchaseInvoice->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $PurchaseInvoice->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="{{ __('general_content.status_trans_key') }}" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $PurchaseInvoice->statu ) Selected @endif >{{ __('general_content.in_progress_trans_key') }}</option>
                          <option value="2" @if(2 == $PurchaseInvoice->statu ) Selected @endif >{{ __('general_content.to_be_posted_trans_key') }}</option>
                          <option value="3" @if(2 == $PurchaseInvoice->statu ) Selected @endif >{{ __('general_content.closed_trans_key') }}</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_purchase_invoice_trans_key'), 'Value' =>  $PurchaseInvoice->label])
                      </div>

                      <div class="col-3">
                        <x-adminlte-input name="delivery_note_number" label="{{ __('general_content.delivery_note_number_trans_key') }}" placeholder="{{ __('general_content.delivery_note_number_trans_key') }}" value="{{  $PurchaseInvoice->delivery_note_number }}" label-class="text-success">
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
                      <div class="col-5">
                        <label for="companies_id">{{ __('general_content.companie_trans_key') }}</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <a class="btn btn-primary btn-sm" href="{{ route('companies.show', ['id' => $PurchaseInvoice->companie->id])}}">
                              <i class="fas fa-buildin"></i>
                              {{  $PurchaseInvoice->companie->code }} - {{  $PurchaseInvoice->companie->label }}
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $PurchaseInvoice->comment }}" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </x-adminlte-card>
            </form>
          </div>
          <!--<div class="col-md-3">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <div class="card-body table-responsive p-0">
                  <table class="table table-hover">
                    <tr>
                    </tr>
                  </table>
              </div>
            </div>
            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.options_trans_key') }}</h3>
              </div>
              <div class="card-body table-responsive p-0">
                  <table class="table table-hover">
                  <tr>
                      <td style="width:50%"> 
                      </td>
                      <td>
                      </td>
                  </tr>
                </table>
              </div>
            </div>
          </div>-->
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
                  <th>{{ __('general_content.purchase_receipt_trans_key') }}</th>
                  <th>{{ __('general_content.description_trans_key') }}</th>
                  <th>{{ __('general_content.supplier_ref_trans_key') }}</th>
                  <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                  <th>{{ __('general_content.price_trans_key') }}</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($PurchaseInvoice->PurchaseInvoiceLines as $PurchaseInvoiceLine)
                  <tr>
                    <td>
                      @if($PurchaseInvoiceLine->purchaseLines->tasks->OrderLines ?? null)
                        <x-OrderButton id="{{ $PurchaseInvoiceLine->purchaseLines->tasks->OrderLines->orders_id }}" code="{{ $PurchaseInvoiceLine->purchaseLines->tasks->OrderLines->order->code }}"  />
                      @else
                        {{__('general_content.generic_trans_key') }} 
                      @endif
                    </td>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('purchases.show', ['id' => $PurchaseInvoiceLine->purchaseLines->purchases_id ])}}">
                          <i class="fas fa-folder"></i>
                          {{ $PurchaseInvoiceLine->purchaseLines->purchase->code }}
                      </a>
                    </td>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('purchase.receipts.show', ['id' => $PurchaseInvoiceLine->purchaseReceiptLines->purchaseReceipt->id ])}}">
                          <i class="fas fa-folder"></i>
                          {{ $PurchaseInvoiceLine->purchaseReceiptLines->purchaseReceipt->code }}
                      </a>
                    </td>
                    <td>
                      @if($PurchaseInvoiceLine->purchaseLines->tasks_id ?? null)
                        <a href="{{ route('production.task.statu.id', ['id' => $PurchaseInvoiceLine->purchaseLines->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                        #{{ $PurchaseInvoiceLine->purchaseLines->tasks->id }} - {{ $PurchaseInvoiceLine->purchaseLines->tasks->label }}
                        @if($PurchaseInvoiceLine->purchaseLines->tasks->component_id )
                            - {{ $PurchaseInvoiceLine->purchaseLines->tasks->Component['label'] }}
                        @endif
                      @else
                          {{ $PurchaseInvoiceLine->purchaseLines->label }}
                      @endif
                    </td>
                    <td>{{ $PurchaseInvoiceLine->purchaseLines->supplier_ref }}</td>
                    <td>{{ $PurchaseInvoiceLine->purchaseReceiptLines->receipt_qty }}</td>
                    <td>{{ $PurchaseInvoiceLine->purchaseLines->selling_price  }} {{ $Factory->curency }}</td>
                  </tr>
                @empty
                  <x-EmptyDataLine col="7" text="{{ __('general_content.no_data_trans_key') }}."  />
              @endforelse
                <tfoot>
                  <tr>
                    <th>{{ __('general_content.purchase_receipt_trans_key') }}</th>
                    <th>{{ __('general_content.purchase_order_trans_key') }}</th>
                    <th>{{ __('general_content.order_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.supplier_ref_trans_key') }}</th>
                    <th>{{ __('general_content.qty_trans_key') }}</th>
                    <th>{{ __('general_content.price_trans_key') }}</th>
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