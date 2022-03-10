@extends('adminlte::page')

@section('title', 'Quote')

@section('content_header')
  <div class="row mb-2">
    <div class="col-sm-6">
      <h1>Print document</h1>
    </div>
    <div class="col-sm-6">
      <a class="btn btn-primary btn-sm" href="{{ url()->previous() }}">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalQuote">
          Back
        </button>
      </a>
    </div>
  </div>
@stop

@section('right-sidebar')

@section('content')
<div class="container-fluid">
            <div class="row">
              <!-- this row will not appear when printing -->
              
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
                        <strong>{{ $Document->companie['label'] }}</strong> - <strong>{{ $Document->contact['civility'] }} - {{ $Document->contact['first_name'] }}  {{ $Document->contact['name'] }}</strong><br>
                        {{ $Document->adresse['adress'] }}<br>
                        {{ $Document->adresse['zipcode'] }}, {{ $Document->adresse['city'] }}<br>
                        {{ $Document->adresse['country'] }}<br>
                        Phone: {{ $Document->contact['number'] }}<br>
                        Email: {{ $Document->contact['mail'] }}
                      </address>
                    </div>
                    <!-- /.col -->
                    <div class="col-sm-4 invoice-col">
                      <h1>{{  $typeDocumentName }} #{{  $Document->code }}</h1>
                      <b>Your Ref:</b> {{  $Document->customer_reference }}<br>
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
                            @forelse($Document->Lines as $DocumentLine)
                            <tr>
                              <td>{{ $DocumentLine->code }}</td>
                              <td>{{ $DocumentLine->label }}</td>
                              <td>{{ $DocumentLine->qty }}</td>
                              <td>{{ $DocumentLine->Unit['label'] }}</td>
                              <td>{{ $DocumentLine->selling_price }}  {{ $Factory->curency }}</td>
                              <td>{{ $DocumentLine->discount }} %</td>
                              <td>{{ $DocumentLine->VAT['rate'] }} %</td>
                              @if($DocumentLine->delivery_date )
                              <td>{{ $DocumentLine->delivery_date }}</td>
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
                      <p class="lead"><strong>Payment Methods:</strong> {{ $Document->payment_condition['label'] }}</p>
                      <p class="lead"><strong>Payment Conditions:</strong> {{ $Document->payment_method['label'] }}</p>
                      @if($Document->comment)
                        <p class="lead"><strong>Comment :</strong></p>
                        <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                          {{  $Document->comment }}
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
                </div>
@stop

@section('css')
@stop

@section('js')
  <script>
    window.addEventListener("load", window.print());
  </script>
@stop