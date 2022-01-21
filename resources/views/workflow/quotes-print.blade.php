@extends('adminlte::page')



@section('content')
            <div class="row">
              <!-- this row will not appear when printing -->
<<<<<<< HEAD
=======
              <div class="row no-print">
                <x-InfocalloutComponent note="This page has been enhanced for printing. Click the print button at the bottom of the invoice to test."  />
              </div>
>>>>>>> 9199ececfb1115f56353ae751b923167cbdbf847
              
              <div class="col-12">
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
    
@stop

@section('css')
@stop

@section('js')
  <script>
    window.addEventListener("load", window.print());
  </script>
@stop