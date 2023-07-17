@extends('adminlte::page')

@section('title', 'Deliverys')

@section('content_header')
  <x-Content-header-previous-button  h1="Delivery : {{  $Delivery->code }}" previous="{{ $previousUrl }}" list="{{ route('deliverys') }}" next="{{ $nextUrl }}"/>
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
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Delivery">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('deliverys.update', ['id' => $Delivery->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-4">
                        <label for="code" class="text-success">External ID :</label>  {{  $Delivery->code }}
                      </div>
                      <div class="col-4">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $Delivery->statu ) Selected @endif >In progress</option>
                          <option value="2" @if(2 == $Delivery->statu ) Selected @endif >Sent</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-4">
                        @include('include.form.form-input-label',['label' =>'Name of delivery', 'Value' =>  $Delivery->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Delivery->comment }}" />
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
                <div class="table-responsive">
                  <div class="card-body">
                    Delevery created at :  {{ $Delivery->GetPrettyCreatedAttribute() }}
                  </div>
                  <div class="card-body">
                    Companie name :  <x-CompanieButton id="{{ $Delivery->companie['id'] }}" label="{{ $Delivery->companie['label'] }}"  />
                  </div>
                  <div class="card-body">
                    Adress name :   {{ $Delivery->adresse['label'] }} - {{ $Delivery->adresse['adress'] }}
                  </div>
                  <div class="card-body">
                    Contact name :  {{ $Delivery->contact['first_name'] }} - {{ $Delivery->contact['name'] }}
                  </div>
                </div>
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
                          Delivery
                        </td>
                        <td>
                          <x-ButtonTextPDF route="{{ route('pdf.delivery', ['Document' => $Delivery->id])}}" />
                        </td>
                    </tr>
                </table>
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
                      @if(1 == $DeliveryLine->invoice_status )  <span class="badge badge-info">Chargeable</span>@endif
                      @if(2 == $DeliveryLine->invoice_status )  <span class="badge badge-danger">Not chargeable</span>@endif
                      @if(3 == $DeliveryLine->invoice_status )  <span class="badge badge-warning">Partly invoiced</span>@endif
                      @if(4 == $DeliveryLine->invoice_status )  <span class="badge badge-success">Invoiced</span>@endif
                    </td>
                  </tr>
                  @empty
                    <x-EmptyDataLine col="7" text="No line in this delivery found ..."  />
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