@extends('adminlte::page')

@section('title', 'Purchase Invoice')

@section('content_header')
  <x-Content-header-previous-button  h1="Purchase Invoice: {{  $PurchaseInvoice->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases.invoice') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-toggle="tab">Purchase Invoice info</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">Purchase Invoice lines</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('invoice.update', ['id' => $PurchaseInvoice->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">External ID :</label>  {{  $PurchaseInvoice->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $PurchaseInvoice->statu ) Selected @endif >In progress</option>
                          <option value="2" @if(2 == $PurchaseInvoice->statu ) Selected @endif >To be posted</option>
                          <option value="3" @if(2 == $PurchaseInvoice->statu ) Selected @endif >Close</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>'Name of reciept', 'Value' =>  $PurchaseInvoice->label])
                      </div>

                      <div class="col-3">
                        <x-adminlte-input name="delivery_note_number" label="Delivery note number" placeholder="Delivery note number" value="{{  $PurchaseInvoice->delivery_note_number }}" label-class="text-success">
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
                      <label for="InputWebSite">Supplier information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        <label for="companies_id">Companie</label>
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
                    <button type="Submit" class="btn btn-primary">Save changes</button>
                  </div>
              </form>
            </div>
          </div>
          <!--<div class="col-md-3">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table">
                    <tr>
                    </tr>
                  </table>
                </div>
              </div>
            </div>
            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title"> Options </h3>
              </div>
              <div class="card-body">
                <table class="table">
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
                  <th>Purchase Receipt</th>
                  <th>Purchase Order</th>
                  <th>Order</th>
                  <th>Description</th>
                  <th>Supplier ref</th>
                  <th>Qty receipt</th>
                  <th>Selling price</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($PurchaseInvoice->PurchaseInvoiceLines as $PurchaseInvoiceLine)
                  <tr>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('purchase.show', ['id' => $PurchaseInvoiceLine->purchaseReceiptLines->purchaseReceipt->id ])}}">
                          <i class="fas fa-folder"></i>
                          {{ $PurchaseInvoiceLine->purchaseReceiptLines->purchaseReceipt->code }}
                      </a>
                    </td>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('purchase.show', ['id' => $PurchaseInvoiceLine->purchaseLines->purchases_id ])}}">
                          <i class="fas fa-folder"></i>
                          {{ $PurchaseInvoiceLine->purchaseLines->purchase->code }}
                      </a>
                    </td>
                    <td>
                      <x-OrderButton id="{{ $PurchaseInvoiceLine->purchaseLines->tasks->OrderLines->orders_id }}" code="{{ $PurchaseInvoiceLine->purchaseLines->tasks->OrderLines->order->code }}"  />
                    </td>
                    <td>#{{ $PurchaseInvoiceLine->purchaseLines->tasks->id }} {{ $PurchaseInvoiceLine->purchaseLines->tasks->code }} {{ $PurchaseInvoiceLine->purchaseLines->tasks->label }}</td>
                    <td>{{ $PurchaseInvoiceLine->purchaseLines->supplier_ref }}</td>
                    <td>{{ $PurchaseInvoiceLine->purchaseReceiptLines->receipt_qty }}</td>
                    <td>{{ $PurchaseInvoiceLine->purchaseLines->selling_price  }} {{ $Factory->curency }}</td>
                  </tr>
                @empty
                  <x-EmptyDataLine col="7" text="No Lines in this purchase reciept ..."  />
              @endforelse
                <tfoot>
                  <tr>
                    <th>Purchase Receipt</th>
                    <th>Purchase Order</th>
                    <th>Order</th>
                    <th>Description</th>
                    <th>Supplier ref</th>
                    <th>Qty</th>
                    <th>Selling price</th>
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