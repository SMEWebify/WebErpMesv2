@extends('adminlte::page')

@section('title', 'Quote')

@section('content_header')

    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Quotes</h1>
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
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Quote">
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
              <form method="POST" action="{{ route('quote.update', ['id' => $Quote->id]) }}" enctype="multipart/form-data">
                @csrf 
                <div class="card card-body">
                  <div class="row">
                      <div class="col-3">
                        <label for="code">External ID :</label>  {{  $Quote->code }}
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
                        <label for="label">Name of quote</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control" name="label"  id="label" placeholder="Name of quote" value="{{  $Quote->label }}">
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
                            <option value="{{ $item->id }}"  @if($item->id == $Quote->companies_id ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $Quote->companies_addresses_id ) Selected @endif >{{ $item->label }} - {{ $item->adress }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $Quote->companies_contacts_id ) Selected @endif >{{ $item->first_name }} - {{ $item->name }}</option>
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
                          <option value="{{ $item->id }}" @if($item->id == $Quote->accounting_payment_conditions_id ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-5">
                        <label for="accounting_payment_methods_id">Payment methods</label>
                        <select class="form-control" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                          @foreach ($AccountingMethodsSelect as $item)
                          <option value="{{ $item->id }}" @if($item->id == $Quote->accounting_payment_methods_id ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $Quote->accounting_deliveries_id ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                      <div class="col-5">
                        <label for="label">Validity date</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Quote->validity_date }}">
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Quote->comment }}" />
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
                <a href="{{ route('print.quote', ['id' => $Quote->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
              </div>
            </div>
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="QuoteLines">
        @livewire('quote-line', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu, 'QuoteDelay' => $Quote->validity_date])
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
                $('#code').val( txt );
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