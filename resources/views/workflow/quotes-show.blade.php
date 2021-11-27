@extends('adminlte::page')

@section('title', 'Quote')

@section('content_header')

    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Quotes </h1>
      </div>
    </div>

@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link" href="{{ route('quotes') }}">Back to lists</a></li>
      <li class="nav-item"><a class="nav-link active" href="#Quote" data-toggle="tab">Quote info</a></li>
      <li class="nav-item"><a class="nav-link" href="#QuoteLines" data-toggle="tab">Quote lines</a></li>
      <li class="nav-item"><a class="nav-link" href="#Print" data-toggle="tab">Print</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Quote">
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
        <form method="POST" action="{{ route('quote.update', ['id' => $Quote->id]) }}" enctype="multipart/form-data">
          @csrf
          
          <div class="card card-body">
            <div class="row">
                <div class="col-3">
                  <label for="CODE">External ID :</label>  {{  $Quote->CODE }}
                </div>
                <div class="col-3">
                  <label for="statu">Statu :</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                    </div>
                    <select class="form-control" name="statu" id="statu">
                      <option value="1" @if(1 == $Quote->statu ) Selected @endif >Open</option>
                      <option value="2" @if(2 == $Quote->statu ) Selected @endif >Send</option>
                      <option value="3" @if(3 == $Quote->statu ) Selected @endif >Win</option>
                      <option value="4" @if(4 == $Quote->statu ) Selected @endif >Lost</option>
                      <option value="5" @if(5 == $Quote->statu ) Selected @endif >Closed</option>
                      <option value="6" @if(6 == $Quote->statu ) Selected @endif >Obsolete</option>
                    </select>
                  </div>
                </div>
                <div class="col-3">
                  <label for="LABEL">Name of quote</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                    </div>
                    <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Name of quote" value="{{  $Quote->LABEL }}">
                  </div>
                </div>
              </div>
            </div>
            <div class="card card-body">
              <div class="row">
                <label for="InputWebSite">Customer information</label>
              </div>
              <hr>
              <div class="row">
                <div class="col-5">
                  <label for="companies_id">Companie</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-building"></i></span>
                    </div>
                    <select class="form-control" name="companies_id" id="companies_id">
                      @foreach ($CompanieSelect as $item)
                      <option value="{{ $item->id }}"  @if($item->id == $Quote->companies_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="col-5">
                  <label for="customer_reference">Customer reference</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                    </div>
                    <input type="text" class="form-control" name="customer_reference"  id="customer_reference" placeholder="Customer reference" value="{{  $Quote->customer_reference }}">
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
                      <option value="{{ $item->id }}" @if($item->id == $Quote->companies_addresses_id ) Selected @endif >{{ $item->LABEL }} - {{ $item->ADRESS }}</option>
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
                      <option value="{{ $item->id }}" @if($item->id == $Quote->companies_contacts_id ) Selected @endif >{{ $item->FIRST_NAME }} - {{ $item->NAME }}</option>
                      @endforeach
                    </select>
                  </div>
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
                  <label for="accounting_payment_conditions_id">Payment condition</label>
                  <select class="form-control" name="accounting_payment_conditions_id" id="accounting_payment_conditions_id">
                    @foreach ($AccountingConditionSelect as $item)
                    <option value="{{ $item->id }}" @if($item->id == $Quote->accounting_payment_conditions_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-5">
                  <label for="accounting_payment_methods_id">Payment methods</label>
                  <select class="form-control" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                    @foreach ($AccountingMethodsSelect as $item)
                    <option value="{{ $item->id }}" @if($item->id == $Quote->accounting_payment_methods_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-5">
                  <label for="accounting_deliveries_id">Delevery method</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-truck"></i></span>
                    </div>
                    <select class="form-control" name="accounting_deliveries_id" id="accounting_deliveries_id">
                      @foreach ($AccountingDeleveriesSelect as $item)
                      <option value="{{ $item->id }}" @if($item->id == $Quote->accounting_deliveries_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="col-5">
                  <label for="LABEL">Validity date</label>
                  <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Quote->validity_date }}">
                </div>
              </div>
            </div>
            <div class="card card-body">
              <div class="row">
                <div class="col-10">
                  <label>Comment</label>
                  <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..." >{{  $Quote->comment }}</textarea>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="Submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
      </div>   
      <div class="tab-pane " id="QuoteLines">
        @livewire('quote-line', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu])
      </div> 
      <div class="tab-pane " id="Print">
        <div class="row">
          <div class="col-12">
            <x-infocalloutComponent note="This page has been enhanced for printing. Click the print button at the bottom of the invoice to test."  />
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
                    <strong>{{ $Factory->NAME }}</strong><br>
                    {{ $Factory->ADDRESS }}<br>
                    {{ $Factory->ZIPCODE }}, {{ $Factory->CITY }}<br>
                    Phone: {{ $Factory->PHONE_NUMBER }}<br>
                    Email: {{ $Factory->MAIL }}
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  To
                  <address>
                    <strong>{{ $Quote->companie['LABEL'] }}</strong> - <strong>{{ $Quote->contact['CIVILITY'] }} - {{ $Quote->contact['FIRST_NAME'] }}  {{ $Quote->contact['NAME'] }}</strong><br>
                    {{ $Quote->adresse['ADRESS'] }}<br>
                    {{ $Quote->adresse['ZIPCODE'] }}, {{ $Quote->adresse['CITY'] }}<br>
                    {{ $Quote->adresse['COUNTRY'] }}<br>
                    Phone: {{ $Quote->contact['NUMBER'] }}<br>
                    Email: {{ $Quote->contact['MAIL'] }}
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  <b>Quote #{{  $Quote->CODE }}</b><br>
                  <b>Your Ref:</b> {{  $Quote->customer_reference }}<br>
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
                        <th>Selling price</th>
                        <th>Discount</th>
                        <th>VAT</th>
                        <th>Delivery date</th>
                      </tr>
                    </thead>
                    <tbody>
                        @forelse($Quote->QuoteLines as $QuoteLine)
                        <tr>
                          <td>{{ $QuoteLine->CODE }}</td>
                          <td>{{ $QuoteLine->LABEL }}</td>
                          <td>{{ $QuoteLine->qty }}</td>
                          <td>{{ $QuoteLine->Unit['LABEL'] }}</td>
                          <td>{{ $QuoteLine->selling_price }}  {{ $Factory->curency }}</td>
                          <td>{{ $QuoteLine->discount }} %</td>
                          <td>{{ $QuoteLine->VAT['RATE'] }} %</td>
                          @if($QuoteLine->delivery_date )
                          <td>{{ $QuoteLine->delivery_date }}</td>
                          @else
                          <td>No date</td>
                          @endif
                          
                        </tr>
                      @empty
                        <tr>
                          <td>No Lines in this quote</td>
                          <td></td> 
                          <td></td> 
                          <td></td> 
                          <td></td> 
                          <td></td>
                          <td></td>
                          <td></td>
                        </tr>
                    @endforelse
                    </tbody>
                  </table>
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
              <div class="row">
                <!-- accepted payments column -->
                <div class="col-6">
                  <p class="lead"><strong>Payment Methods:</strong> {{ $Quote->payment_condition['LABEL'] }}</p>
                  <p class="lead"><strong>Payment Conditions:</strong> {{ $Quote->payment_method['LABEL'] }}</p>
                  @if($Quote->comment)
                    <p class="lead"><strong>Comment :</strong></p>
                    <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                      {{  $Quote->comment }}
                    </p>
                  @endif
                </div>
                <!-- /.col -->
                <div class="col-6">
                  <div class="table-responsive">
                    <table class="table">
                      <tr>
                        <th style="width:50%">Subtotal:</th>
                        <td>{{ $subPrice }} {{ $Factory->curency }} </td>
                      </tr>
                      @forelse($vatPrice as $key => $value)
                      <tr>
                        <td>Tax <?= $vatPrice[$key][0] ?> %</td>
                        <td colspan="4"><?= $vatPrice[$key][1] ?> {{ $Factory->curency }}</td>
                      </tr>
                      @empty
                      <tr>
                        <td>No Tax</td>
                        <td> </td>
                      </tr>
                      @endforelse
                      <tr>
                        <th>Total:</th>
                        <td>{{ $totalPrices }} {{ $Factory->curency }}</td>
                      </tr>
                    </table>
                  </div>
                </div>
                <!-- /.col -->
              </div>
              <!-- /.row -->
              <!-- this row will not appear when printing -->
              <div class="row no-print">
                <div class="col-12">
                  <a href="{{ route('quote.print', ['id' => $Quote->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
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
  <script> 
            $('#product_id').on('change',function(){
                var val = $(this).val();
                var txt = $(this).find('option:selected').data('txt');
                $('#CODE').val( txt );
            });

          $(function(){
            var hash = window.location.hash;
            hash && $('ul.nav.nav-pills a[href="' + hash + '"]').tab('show'); 
            $('ul.nav.nav-pills a').click(function (e) {
              $(this).tab('show');
              var scrollmem = $('body').scrollTop();
              window.location.hash = this.hash;
            });
          });
  </script>
@stop