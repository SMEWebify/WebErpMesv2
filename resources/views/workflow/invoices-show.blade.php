@extends('adminlte::page')

@section('title', 'Invoice')

@section('content_header')
    
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Invoice</h1>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link" href="{{ route('invoices') }}">Back to lists</a></li>
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
            @if(session('success'))
            <div class="alert alert-success">
                {{ session('success')}}
            </div>
            @endif
            @if($errors->count())
              <div class="alert alert-danger">
                <ul>
                @foreach ( $errors->all() as $message)
                <li> {{ $message }}</li>
                @endforeach
                </ul>
              </div>
            @endif
            <div class="card">
              <form method="POST" action="{{ route('delivery.update', ['id' => $Invoice->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code">External ID :</label>  {{  $Invoice->code }}
                      </div>
                      <div class="col-3">
                        <label for="statu">Statu :</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                          </div>
                          <select class="form-control" name="statu" id="statu">
                            <option value="1" @if(1 == $Invoice->statu ) Selected @endif >In progress</option>
                            <option value="2" @if(2 == $Invoice->statu ) Selected @endif >Sent</option>
                          </select>
                        </div>
                      </div>
                      
                      <div class="col-3">
                        <label for="label">Name of delivery</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control" name="label"  id="label" placeholder="Name of order" value="{{  $Invoice->label }}">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Customer information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        <label for="companies_id">Companie</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                          </div>
                          <select class="form-control" name="companies_id" id="companies_id">
                            @foreach ($CompanieSelect as $item)
                            <option value="{{ $item->id }}"  @if($item->id == $Invoice->companies_id ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        <label for="companies_addresses_id">Adress</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                          </div>
                          <select class="form-control" name="companies_addresses_id" id="companies_addresses_id">
                            @foreach ($AddressSelect as $item)
                            <option value="{{ $item->id }}" @if($item->id == $Invoice->companies_addresses_id ) Selected @endif >{{ $item->label }} - {{ $item->adress }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                      <div class="col-5">
                        <label for="companies_contacts_id">Contact</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                          </div>
                          <select class="form-control" name="companies_contacts_id" id="companies_contacts_id">
                            @foreach ($ContactSelect as $item)
                            <option value="{{ $item->id }}" @if($item->id == $Invoice->companies_contacts_id ) Selected @endif >{{ $item->first_name }} - {{ $item->name }}</option>
                            @endforeach
                          </select>
                        </div>
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
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <div class="card-body">
                @include('include.sub-total-price')
              </div>
            </div>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"> Options </h3>
              </div>
              <div class="card-body">
                <a href="{{ route('print.order', ['id' => $Invoice->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
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
                  <th>Delivered qty</th>
                  <th>Remaining qty</th>
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
                    <td>{{ $InvoiceLine->orderLine['qty'] }}</td>
                    <td></td>
                    <td>{{ $InvoiceLine->qty }}</td>
                    <td>{{ $InvoiceLine->orderLine['delivered_remaining_qty'] }}</td>
                    <td>
                      @if(1 == $InvoiceLine->invoice_status )  <span class="badge badge-info">Chargeable</span>@endif
                      @if(2 == $InvoiceLine->invoice_status )  <span class="badge badge-danger">Not chargeable</span>@endif
                      @if(3 == $InvoiceLine->invoice_status )  <span class="badge badge-warning">Partly invoiced</span>@endif
                      @if(4 == $InvoiceLine->invoice_status )  <span class="badge badge-success">Invoiced</span>@endif
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
                    <th>Delivered qty</th>
                    <th>Remaining qty</th>
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