@extends('adminlte::page')

@section('title', 'Deliverys')

@section('content_header')
    
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Deliverys</h1>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link" href="{{ route('deliverys') }}">Back to lists</a></li>
      <li class="nav-item"><a class="nav-link active" href="#Delivery" data-toggle="tab">Delivery info</a></li>
      <li class="nav-item"><a class="nav-link" href="#DeliveryLines" data-toggle="tab">Delivery lines</a></li>
      <li class="nav-item"><a class="nav-link" href="#Print" data-toggle="tab">Print delivery</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Delivery">
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
              <form method="POST" action="{{ route('delivery.update', ['id' => $Delivery->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code">External ID :</label>  {{  $Delivery->code }}
                      </div>
                      <div class="col-3">
                        <label for="statu">Statu :</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                          </div>
                          <select class="form-control" name="statu" id="statu">
                            <option value="1" @if(1 == $Delivery->statu ) Selected @endif >In progress</option>
                            <option value="2" @if(2 == $Delivery->statu ) Selected @endif >Sent</option>
                          </select>
                        </div>
                      </div>
                      
                      <div class="col-3">
                        <label for="label">Name of delivery</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control" name="label"  id="label" placeholder="Name of order" value="{{  $Delivery->label }}">
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
                            <option value="{{ $item->id }}"  @if($item->id == $Delivery->companies_id ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $Delivery->companies_addresses_id ) Selected @endif >{{ $item->label }} - {{ $item->adress }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $Delivery->companies_contacts_id ) Selected @endif >{{ $item->first_name }} - {{ $item->name }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-10">
                        <label>Comment</label>
                        <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..." >{{  $Delivery->comment }}</textarea>
                      </div>
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
                <div class="table-responsive">
                </div>
              </div>
            </div>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"> Options </h3>
              </div>
              <div class="card-body">
                <a href="{{ route('order.print', ['id' => $Delivery->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
              </div>
            </div>
          </div>
        </div>
      </div>      
      <div class="tab-pane " id="DeliveryLines">
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
                  @forelse($Delivery->DeliveryLines as $DeliveryLine)
                  <tr>
                    <td>
                      <x-OrderButton id="{{ $DeliveryLine->OrderLine->order['id'] }}" code="{{ $DeliveryLine->OrderLine->order['code'] }}"  />
                    </td>
                    <td>{{ $DeliveryLine->OrderLine['code'] }}</td>
                    <td>{{ $DeliveryLine->OrderLine['label'] }}</td>
                    <td>{{ $DeliveryLine->OrderLine['qty'] }}</td>
                    <td>{{ $DeliveryLine->OrderLine->Unit['label'] }}</td>
                    <td>{{ $DeliveryLine->qty }}</td>
                    <td>{{ $DeliveryLine->OrderLine['delivered_remaining_qty'] }}</td>
                    <td>
                      @if(1 == $Delivery->invoice_status )  <span class="badge badge-info">Chargeable</span>@endif
                      @if(2 == $Delivery->invoice_status )  <span class="badge badge-danger">Not chargeable</span>@endif
                      @if(3 == $Delivery->invoice_status )  <span class="badge badge-warning">Partly invoiced</span>@endif
                      @if(4 == $Delivery->invoice_status )  <span class="badge badge-success">Invoiced</span>@endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td>No Lines in this delivery</td>
                    <td></td> 
                    <td></td> 
                    <td></td> 
                    <td></td> 
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                  </tr>
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
      <div class="tab-pane " id="Print">
        <div class="row">
          <div class="col-12">
            <x-InfocalloutComponent note="This page has been enhanced for printing. Click the print button at the bottom of the delivery note to test."  />
            <!-- Main content -->
            <div class="invoice p-3 mb-3">
              <!-- title row -->
              <div class="row">
                <div class="col-12">
                  <h4>
                    <i class="fas fa-globe"></i> WEM, Inc.
                    <small class="float-right">Date: {{ date('Y-m-d') }}</small>
                  </h4>
                </div>
                <!-- /.col -->
              </div>
              <!-- info row -->
              <div class="row invoice-info">
                <div class="col-sm-4 invoice-col">
                  From
                  <address>
                    <strong>{{ $Factory->name }}</strong><br>
                    {{ $Factory->ADDRESS }}<br>
                    {{ $Factory->zipcode }}, {{ $Factory->city }}<br>
                    Phone: {{ $Factory->PHONE_NUMBER }}<br>
                    Email: {{ $Factory->mail }}
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  To
                  <address>
                    <strong>{{ $Delivery->companie['label'] }}</strong>
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  <b>Delivery #{{  $Delivery->code }}</b><br>
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
              <!-- Table row -->
              <div class="row">
                <div class="col-12 table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>External ID</th>
                        <th>Description</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Delivered qty</th>
                        <th>Remaining qty</th>
                      </tr>
                    </thead>
                    <tbody>
                        @forelse($Delivery->DeliveryLines as $DeliveryLine)
                        <tr>
                          <td>{{ $DeliveryLine->OrderLine['code'] }}</td>
                          <td>{{ $DeliveryLine->OrderLine['label'] }}</td>
                          <td>{{ $DeliveryLine->OrderLine['qty'] }}</td>
                          <td></td>
                          <td>{{ $DeliveryLine->qty }}</td>
                          <td>{{ $DeliveryLine->OrderLine['delivered_remaining_qty'] }}</td>
                        </tr>
                      @empty
                        <tr>
                          <td>No Lines in this delivery</td>
                          <td></td> 
                          <td></td> 
                          <td></td> 
                          <td></td> 
                          <td></td>
                          <td></td>
                          <td></td>
                        </tr>
                    @endforelse
                      <tfoot>
                        <tr>
                          <th>External ID</th>
                          <th>Description</th>
                          <th>Qty</th>
                          <th>Unit</th>
                          <th>Delivered qty</th>
                          <th>Remaining qty</th>
                        </tr>
                      </tfoot>
                    </tbody>
                  </table>
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
              <div class="row">
                <!-- accepted payments column -->
                <div class="col-6">
                  @if($Delivery->comment)
                    <p class="lead"><strong>Comment :</strong></p>
                    <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                      {{  $Delivery->comment }}
                    </p>
                  @endif
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
              <!-- this row will not appear when printing -->
              <div class="row no-print">
                <div class="col-12">
                  <a href="{{ route('order.print', ['id' => $Delivery->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
                </div>
              </div>
            </div>
            <!-- /.invoice -->
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