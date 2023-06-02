@extends('adminlte::page')

@section('title', 'Purchase')

@section('content_header')
  <x-Content-header-previous-button  h1="Purchase : {{  $Purchase->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-toggle="tab">Purchase info</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">Purchase lines</a></li>
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
              <form method="POST" action="{{ route('purchase.update', ['id' => $Purchase->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">External ID :</label>  {{  $Purchase->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $Purchase->statu ) Selected @endif >In progress</option>
                          <option value="2" @if(2 == $Purchase->statu ) Selected @endif >Ordered</option>
                          <option value="3" @if(3 == $Purchase->statu ) Selected @endif >Partly received</option>
                          <option value="4" @if(4 == $Purchase->statu ) Selected @endif >Received</option>
                          <option value="5" @if(5 == $Purchase->statu ) Selected @endif >Canceled</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>'Name of purchase order', 'Value' =>  $Purchase->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Supplier information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-companie',['companiesId' =>  $Purchase->companies_id])
                      </div>
                      <div class="col-5">
                        
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-adress',['adressId' =>   $Purchase->companies_addresses_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-select-contact',['contactId' =>   $Purchase->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Purchase->comment }}" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="Submit" class="btn btn-primary">Save changes</button>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                    <th>Total:</th>
                    <td>{{ $totalPrices }} {{ $Factory->curency }}</td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title"> Options </h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                  <tr>
                      <td style="width:50%"> 
                        Purchase
                      </td>
                      <td>
                        <x-ButtonTextPDF route="{{ route('pdf.purchase', ['Document' => $Purchase->id])}}" />
                      </td>
                  </tr>
                </table>
              </div>
            </div>
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
                  <th>Order</th>
                  <th>Description</th>
                  <th>Supplier ref</th>
                  <th>Qty</th>
                  <th>Qty Reciept</th>
                  <th>Qty Invoice</th>
                  <th>Selling price</th>
                  <th>Discount</th>
                  <th>Total selling price</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($Purchase->PurchaseLines as $PurchaseLine)
                  <tr>
                    <td>
                        <x-OrderButton id="{{  $PurchaseLine->tasks->OrderLines->orders_id }}" code="{{ $PurchaseLine->tasks->OrderLines->order->code }}"  />
                    </td>
                    <td>#{{ $PurchaseLine->tasks->id }} {{ $PurchaseLine->code }} {{ $PurchaseLine->label }}</td>
                    <td>{{ $PurchaseLine->supplier_ref }}</td>
                    <td>{{ $PurchaseLine->qty }}</td>
                    <td>{{ $PurchaseLine->receipt_qty }}</td>
                    <td>{{ $PurchaseLine->invoiced_qty }}</td>
                    <td>{{ $PurchaseLine->selling_price }} {{ $Factory->curency }}</td>
                    <td>{{ $PurchaseLine->discount }} %</td>
                    <td>{{ $PurchaseLine->total_selling_price }} {{ $Factory->curency }}</td>
                  </tr>
                @empty
                  <x-EmptyDataLine col="7" text="No Lines in this purchase order ..."  />
                @endforelse
                <tfoot>
                  <tr>
                    <th>Order</th>
                    <th>Description</th>
                    <th>Supplier ref</th>
                    <th>Qty</th>
                    <th>Qty Reciept</th>
                    <th>Qty Invoice</th>
                    <th>Selling price</th>
                    <th>Discount</th>
                    <th>Total selling price</th>
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