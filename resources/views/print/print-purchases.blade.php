@extends('adminlte::page')

@section('title', 'Print document')

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
                    
                    <x-HeaderPrint  
                      factoryName="{{ $Factory->name }}"
                      factoryAddress="{{ $Factory->address }}"
                      factoryZipcode="{{ $Factory->zipcode }}"
                      factoryCity="{{ $Factory->city }}"
                      factoryPhoneNumber="{{ $Factory->phone_number }}"
                      factoryMail="{{ $Factory->mail }}"

                      companieLabel="{{ $Document->companie['label'] }}"
                      companieCivility="{{ $Document->contact['civility'] }}"
                      companieFirstName="{{ $Document->contact['first_name'] }}"
                      companieName="{{ $Document->contact['name'] }}"
                      companieAdress="{{ $Document->adresse['adress'] }}"
                      companieZipcode="{{ $Document->adresse['zipcode'] }}"
                      companieCity="{{ $Document->adresse['city'] }}"
                      companieCountry="{{ $Document->adresse['country'] }}"
                      companieNumber="{{ $Document->contact['number'] }}"
                      companieMail="{{ $Document->contact['mail'] }}"

                      documentName="{{ $typeDocumentName}}"
                      code="{{ $Document->code }}"
                      customerReference="{{ $Document->customer_reference }}" 
                    />
                  </div>
                  <!-- /.row -->
                  
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
                            <th>Selling price</th>
                            <th>Discount</th>
                            <th>Total selling price</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($Document->Lines as $DocumentLine)
                          <tr>
                            <td>
                                {{ $DocumentLine->tasks->OrderLines->order->code }}
                            </td>
                            <td>#{{ $DocumentLine->tasks->id }} {{ $DocumentLine->code }} {{ $DocumentLine->label }}</td>
                            <td>{{ $DocumentLine->supplier_ref }}</td>
                            <td>{{ $DocumentLine->qty }}</td>
                            <td>{{ $DocumentLine->selling_price }} {{ $Factory->curency }}</td>
                            <td>{{ $DocumentLine->discount }} %</td>
                            <td>{{ $DocumentLine->total_selling_price }} {{ $Factory->curency }}</td>
                          </tr>
                            @empty
                              <x-EmptyDataLine col="6" text="No Lines in this purchase order ..."  />
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
                      @if($Document->comment)
                        <p class="lead"><strong>Comment :</strong></p>
                        <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                          {{  $Document->comment }}
                        </p>
                      @endif
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