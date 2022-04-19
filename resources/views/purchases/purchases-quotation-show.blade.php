@extends('adminlte::page')

@section('title', 'Purchase quotation')

@section('content_header')
  <x-Content-header-previous-button  h1="Purchase quotation : {{  $PurchaseQuotation->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases.quotation') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#PurchaseQuotation" data-toggle="tab">Purchase quotation info</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseQuotationLines" data-toggle="tab">Purchase quotation lines</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="PurchaseQuotation">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card">
              <form method="POST" action="{{ route('quotation.update', ['id' => $PurchaseQuotation->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">External ID :</label>  {{  $PurchaseQuotation->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $PurchaseQuotation->statu ) Selected @endif >Open</option>
                          <option value="2" @if(2 == $PurchaseQuotation->statu ) Selected @endif >In progress</option>
                          <option value="3" @if(3 == $PurchaseQuotation->statu ) Selected @endif >Delivered</option>
                          <option value="4" @if(4 == $PurchaseQuotation->statu ) Selected @endif >Partly delivered</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>'Name of quotation request', 'Value' =>  $PurchaseQuotation->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Supplier information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-companie',['companiesId' =>  $PurchaseQuotation->companies_id])
                      </div>
                      <div class="col-5">
                        
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-adress',['adressId' =>   $PurchaseQuotation->companies_addresses_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-select-contact',['contactId' =>   $PurchaseQuotation->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Date & Payment information</label>
                    </div>
                    <div class="col-5">
                        <label for="label">Validity date</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $PurchaseQuotation->validity_date }}">
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $PurchaseQuotation->comment }}" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="Submit" class="btn btn-primary">Save changes</button>
                  </div>
              </form>
            </div>
            <!-- /.card-->
          </div>
          <!-- /.col-md-9-->

          <div class="col-md-3">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"> Informations </h3>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"> Options </h3>
                </div>
                <div class="card-body">
                  <a href="{{ route('print.order', ['Document' => $PurchaseQuotation->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print quotation</a>
                </div>
              </div>
          </div>
          <!-- /.col-md-3-->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.tab-pane -->    
      <div class="tab-pane " id="PurchaseQuotationLines">
        <div class="row">
          <div class="col-12 table-responsive">
            <form method="POST" action="{{ route('purchase.orders.store')}}" >
              <table class="table table-striped">
                <thead>
                    <tr>
                      <th>Order</th>
                      <th>Description</th>
                      <th>Qty</th>
                      <th>Unit price</th>
                      <th>Total price</th>
                      <th></th>
                    </tr>
                </thead>
                <tbody>
                      @forelse($PurchaseQuotation->PurchaseQuotationLines as $PurchaseQuotationLine)
                      <tr>
                        <td>
                          <x-OrderButton id="{{ $PurchaseQuotationLine->tasks->OrderLines->orders_id }}" code="{{ $PurchaseQuotationLine->tasks->OrderLines->order->code }}"  />
                        </td>
                        <td>#{{ $PurchaseQuotationLine->tasks->id }} {{ $PurchaseQuotationLine->code }} {{ $PurchaseQuotationLine->label }}</td>
                        <td>{{ $PurchaseQuotationLine->qty_to_order }}</td>
                        <td>{{ $PurchaseQuotationLine->unit_price }} {{ $Factory->curency }}</td>
                        <td>{{ $PurchaseQuotationLine->total_price }} {{ $Factory->curency }}</td>
                        <td>
                          <div class="form-group">
                            <div class="custom-control custom-checkbox">
                              <input class="custom-control-input" value="{{ $PurchaseQuotationLine->id }}" name="PurchaseQuotationLine[]" id="PurchaseQuotationLine.{{ $PurchaseQuotationLine->id }}" type="checkbox">
                              <label for="PurchaseQuotationLine.{{ $PurchaseQuotationLine->id }}" class="custom-control-label">+</label>
                            </div>
                          </div>
                        </td>
                      </tr>
                    @empty
                      <x-EmptyDataLine col="5" text="No Lines in this purchase order ..."  />
                    @endforelse
                </tbody>
                <tfoot>
                      <tr>
                        <th>Order</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit price</th>
                        <th>Total price</th>
                        <th>
                            <button type="Submit" class="btn btn-primary">New order</button>
                        </th>
                      </tr>
                </tfoot>
              </table>
            </form>
          </div>
          <!-- /.col-12 table-responsive-->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.tab-pane -->
    </div>
    <!-- /.tab-content -->
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')

@stop