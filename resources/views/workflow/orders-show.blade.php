@extends('adminlte::page')

@section('title', 'Order')

@section('content_header')
  <x-Content-header-previous-button  h1="Order : {{  $Order->code }}" previous="{{ $previousUrl }}" list="{{ route('orders') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Order" data-toggle="tab">Order info</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">Order lines</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Order">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('orders.update', ['id' => $Order->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">External ID :</label>  {{  $Order->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $Order->statu ) Selected @endif >Open</option>
                            <option value="2" @if(2 == $Order->statu ) Selected @endif >In progress</option>
                            <option value="3" @if(3 == $Order->statu ) Selected @endif >Delivered</option>
                            <option value="4" @if(4 == $Order->statu ) Selected @endif >Partly delivered</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>'Name of order', 'Value' =>  $Order->label])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Customer information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-companie',['companiesId' =>  $Order->companies_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-input-customerInfo',['customerReference' =>  $Order->customer_reference])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-adress',['adressId' =>   $Order->companies_addresses_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-select-contact',['contactId' =>   $Order->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Date & Payment information</label>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Order->accounting_payment_conditions_id])
                      </div>
                      <div class="col-5">
                          @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Order->accounting_payment_methods_id])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                          @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Order->accounting_deliveries_id])
                      </div>
                      <div class="col-5">
                        <label for="label">Validity date</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Order->validity_date }}">
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Order->comment }}" />
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="Submit" class="btn btn-primary">Save changes</button>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            @if($Order->quote_id)
            <div class="card card-info">
              <div class="card-header ">
                <h3 class="card-title"> Historical </h3>
              </div>
              <div class="card-body">
                Order Create from <x-QuoteButton id="{{ $Order->quote_id }}" code="{{ $Order->Quote->code }}"  />
              </div>
            </div>
            @endif
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
              <div class="table-responsive">
                <table class="table">
                    <tr>
                        <td style="width:50%"> 
                          <x-ButtonTextPrint route="{{ route('print.order', ['Document' => $Order->id])}}" />
                        </td>
                        <td>
                          <x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" />
                        </td>
                    </tr>
                    <tr>
                      <td style="width:50%">
                        <a href="{{ route('print.orders.confirm', ['Document' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print order confirm</a>
                      </td>
                      <td>
                        <x-ButtonTextPDF route="{{ route('pdf.orders.confirm', ['Document' => $Order->id])}}" />
                      </td>
                    </tr>
                    <tr>
                      <td style="width:50%">
                        <a href="{{ route('print.manufacturing.instruction', ['Document' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print Manufacturing instruction</a>
                      </td>
                      <td>
                        
                      </td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>    
      <div class="tab-pane " id="Lines">
        @livewire('order-line', ['OrderId' => $Order->id, 'OrderStatu' => $Order->statu, 'OrderDelay' => $Order->validity_date])
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