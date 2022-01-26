@extends('adminlte::page')

@section('title', 'Order')

@section('content_header')
    
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Orders </h1>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link" href="{{ route('orders') }}">Back to lists</a></li>
      <li class="nav-item"><a class="nav-link active" href="#Order" data-toggle="tab">Order info</a></li>
      <li class="nav-item"><a class="nav-link" href="#OrderLines" data-toggle="tab">Order lines</a></li>
      <li class="nav-item"><a class="nav-link" href="#Print" data-toggle="tab">Print order</a></li>
      <li class="nav-item"><a class="nav-link" href="#PrintConfirm" data-toggle="tab">Print order confirm</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Order">
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
              <form method="POST" action="{{ route('order.update', ['id' => $Order->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="CODE">External ID :</label>  {{  $Order->CODE }}
                      </div>
                      <div class="col-3">
                        <label for="statu">Statu :</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                          </div>
                          <select class="form-control" name="statu" id="statu">
                            <option value="1" @if(1 == $Order->statu ) Selected @endif >Open</option>
                            <option value="2" @if(2 == $Order->statu ) Selected @endif >In progress</option>
                            <option value="3" @if(3 == $Order->statu ) Selected @endif >Delivered</option>
                            <option value="4" @if(4 == $Order->statu ) Selected @endif >Partly delivered</option>
                          </select>
                        </div>
                      </div>
                      
                      <div class="col-3">
                        <label for="LABEL">Name of order</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Name of order" value="{{  $Order->LABEL }}">
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
                            <option value="{{ $item->id }}"  @if($item->id == $Order->companies_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
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
                          <input type="text" class="form-control" name="customer_reference"  id="customer_reference" placeholder="Customer reference" value="{{  $Order->customer_reference }}">
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
                            <option value="{{ $item->id }}" @if($item->id == $Order->companies_addresses_id ) Selected @endif >{{ $item->LABEL }} - {{ $item->ADRESS }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $Order->companies_contacts_id ) Selected @endif >{{ $item->FIRST_NAME }} - {{ $item->NAME }}</option>
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
                          <option value="{{ $item->id }}" @if($item->id == $Order->accounting_payment_conditions_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-5">
                        <label for="accounting_payment_methods_id">Payment methods</label>
                        <select class="form-control" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                          @foreach ($AccountingMethodsSelect as $item)
                          <option value="{{ $item->id }}" @if($item->id == $Order->accounting_payment_methods_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $Order->accounting_deliveries_id ) Selected @endif >{{ $item->CODE }} - {{ $item->LABEL }}</option>
                            @endforeach
                          </select>
                        </div>
                      </div>
                      <div class="col-5">
                        <label for="LABEL">Validity date</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Order->validity_date }}">
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-10">
                        <label>Comment</label>
                        <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..." >{{  $Order->comment }}</textarea>
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
            </div>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"> Options </h3>
              </div>
              <div class="card-body">
                <a href="{{ route('order.print', ['id' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print order</a>
              </div>
              <div class="card-body">
                <a href="{{ route('order.print', ['id' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print order confirm</a>
              </div>
              <div class="card-body">
                <a href="{{ route('order.print', ['id' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print Manufacturing instruction</a>
              </div>
              
            </div>
          </div>
        </div>
      </div>    
      <div class="tab-pane " id="OrderLines">
        @livewire('order-line', ['OrderId' => $Order->id, 'OrderStatu' => $Order->statu, 'OrderDelay' => $Order->validity_date])
      </div> 
      <div class="tab-pane " id="Print">
        <div class="row">
          <div class="col-12">
            <x-InfocalloutComponent note="This page has been enhanced for printing. Click the print button at the bottom of the invoice to test."  />
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
                    <strong>{{ $Order->companie['LABEL'] }}</strong> - <strong>{{ $Order->contact['CIVILITY'] }} - {{ $Order->contact['FIRST_NAME'] }}  {{ $Order->contact['NAME'] }}</strong><br>
                    {{ $Order->adresse['ADRESS'] }}<br>
                    {{ $Order->adresse['ZIPCODE'] }}, {{ $Order->adresse['CITY'] }}<br>
                    {{ $Order->adresse['COUNTRY'] }}<br>
                    Phone: {{ $Order->contact['NUMBER'] }}<br>
                    Email: {{ $Order->contact['MAIL'] }}
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  <b>Order #{{  $Order->CODE }}</b><br>
                  <b>Your Ref:</b> {{  $Order->customer_reference }}<br>
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
                        @forelse($Order->OrderLines as $OrderLine)
                        <tr>
                          <td>{{ $OrderLine->CODE }}</td>
                          <td>{{ $OrderLine->LABEL }}</td>
                          <td>{{ $OrderLine->qty }}</td>
                          <td>{{ $OrderLine->Unit['LABEL'] }}</td>
                          <td>{{ $OrderLine->selling_price }}  {{ $Factory->curency }}</td>
                          <td>{{ $OrderLine->discount }} %</td>
                          <td>{{ $OrderLine->VAT['RATE'] }} %</td>
                          @if($OrderLine->delivery_date )
                          <td>{{ $OrderLine->delivery_date }}</td>
                          @else
                          <td>No date</td>
                          @endif
                          
                        </tr>
                      @empty
                        <tr>
                          <td>No Lines in this order</td>
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
                  <p class="lead"><strong>Payment Methods:</strong> {{ $Order->payment_condition['LABEL'] }}</p>
                  <p class="lead"><strong>Payment Conditions:</strong> {{ $Order->payment_method['LABEL'] }}</p>
                  @if($Order->comment)
                    <p class="lead"><strong>Comment :</strong></p>
                    <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                      {{  $Order->comment }}
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
                  <a href="{{ route('order.print', ['id' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
                </div>
              </div>
            </div>
            <!-- /.invoice -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div> 
      <div class="tab-pane " id="PrintConfirm">
        <div class="row">
          <div class="col-12">
            <x-InfocalloutComponent note="This page has been enhanced for printing. Click the print button at the bottom of the invoice to test."  />
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
                    <strong>{{ $Order->companie['LABEL'] }}</strong> - <strong>{{ $Order->contact['CIVILITY'] }} - {{ $Order->contact['FIRST_NAME'] }}  {{ $Order->contact['NAME'] }}</strong><br>
                    {{ $Order->adresse['ADRESS'] }}<br>
                    {{ $Order->adresse['ZIPCODE'] }}, {{ $Order->adresse['CITY'] }}<br>
                    {{ $Order->adresse['COUNTRY'] }}<br>
                    Phone: {{ $Order->contact['NUMBER'] }}<br>
                    Email: {{ $Order->contact['MAIL'] }}
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  <b>Order Confirm #{{  $Order->CODE }}</b><br>
                  <b>Your Ref:</b> {{  $Order->customer_reference }}<br>
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
                        @forelse($Order->OrderLines as $OrderLine)
                        <tr>
                          <td>{{ $OrderLine->CODE }}</td>
                          <td>{{ $OrderLine->LABEL }}</td>
                          <td>{{ $OrderLine->qty }}</td>
                          <td>{{ $OrderLine->Unit['LABEL'] }}</td>
                          <td>{{ $OrderLine->selling_price }}  {{ $Factory->curency }}</td>
                          <td>{{ $OrderLine->discount }} %</td>
                          <td>{{ $OrderLine->VAT['RATE'] }} %</td>
                          @if($OrderLine->delivery_date )
                          <td>{{ $OrderLine->delivery_date }}</td>
                          @else
                          <td>No date</td>
                          @endif
                          
                        </tr>
                      @empty
                        <tr>
                          <td>No Lines in this order</td>
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
                  <p class="lead"><strong>Payment Methods:</strong> {{ $Order->payment_condition['LABEL'] }}</p>
                  <p class="lead"><strong>Payment Conditions:</strong> {{ $Order->payment_method['LABEL'] }}</p>
                  @if($Order->comment)
                    <p class="lead"><strong>Comment :</strong></p>
                    <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                      {{  $Order->comment }}
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
                  <a href="{{ route('order.print', ['id' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
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

<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip({
          html:true
      })
  })
</script>
@stop