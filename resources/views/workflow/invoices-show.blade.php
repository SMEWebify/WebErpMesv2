@extends('adminlte::page')

@section('title', 'Invoice')

@section('content_header')
  <x-Content-header-previous-button  h1="Invoice : {{  $Invoice->code }}" previous="{{ $previousUrl }}" list="{{ route('invoices') }}" next="{{ $nextUrl }}"/>
@stop


@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Invoice" data-toggle="tab">Invoice info</a></li>
      <li class="nav-item"><a class="nav-link" href="#InvoiceLines" data-toggle="tab">Invoice lines</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Invoice">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('invoices.update', ['id' => $Invoice->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">External ID :</label>  {{  $Invoice->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $Invoice->statu ) Selected @endif >In progress</option>
                          <option value="2" @if(2 == $Invoice->statu ) Selected @endif >Sent</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>'Name of invoice', 'Value' =>  $Invoice->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Customer information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-companie',['companiesId' =>  $Invoice->companies_id])
                      </div>
                      <div class="col-5">
                        
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-adress',['adressId' =>   $Invoice->companies_addresses_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-select-contact',['contactId' =>   $Invoice->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Invoice->comment }}" />
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
              <div class="card-body">
                @include('include.sub-total-price')
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
                          Invoice
                        </td>
                        <td>
                          <x-ButtonTextPDF route="{{ route('pdf.invoice', ['Document' => $Invoice->id])}}" />
                        </td>
                    </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>       
      <div class="tab-pane " id="InvoiceLines">
        <!-- Table row -->
        <div class="row">
          <div class="col-12 table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Order</th>
                  <th>External ID</th>
                  <th>Description</th>
                  <th>Qty</th>
                  <th>Unit</th>
                  <th>Price</th>
                  <th>Discount</th>
                  <th>VAT</th>
                  <th>Invoice status</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($Invoice->InvoiceLines as $InvoiceLine)
                  <tr>
                    <td>
                      <x-OrderButton id="{{ $InvoiceLine->orderLine->order['id'] }}" code="{{ $InvoiceLine->orderLine->order['code'] }}"  />
                    </td>
                    <td>{{ $InvoiceLine->orderLine['code'] }}</td>
                    <td>{{ $InvoiceLine->orderLine['label'] }}</td>
                    <td>{{ $InvoiceLine->qty }}</td>
                    <td>{{ $InvoiceLine->OrderLine->Unit['label'] }}</td>
                    <td>{{ $InvoiceLine->OrderLine['selling_price'] }} {{ $Factory->curency }}</td>
                    <td>{{ $InvoiceLine->OrderLine['discount'] }} %</td>
                    <td>{{ $InvoiceLine->OrderLine->VAT['rate'] }} %</td>
                    <td>
                      @if(1 == $InvoiceLine->invoice_status )  <span class="badge badge-info">In progress</span>@endif
                      @if(2 == $InvoiceLine->invoice_status )  <span class="badge badge-danger">Sent</span>@endif
                      @if(3 == $InvoiceLine->invoice_status )  <span class="badge badge-warning">Invoiced</span>@endif
                      @if(4 == $InvoiceLine->invoice_status )  <span class="badge badge-success">Partially invoiced</span>@endif
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="8" text="No line in this invoince found ..."  />
                  @endforelse
                <tfoot>
                  <tr>
                    <th>Order</th>
                    <th>External ID</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Price</th>
                    <th>Discount</th>
                    <th>VAT</th>
                    <th>Invoice status</th>
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