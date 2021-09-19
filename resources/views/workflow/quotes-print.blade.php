@extends('adminlte::page')



@section('content')
        <div class="row">
          <div class="col-12">
            <!-- Main content -->
            <div class="invoice p-3 mb-3">
              <!-- title row -->
              <div class="row">
                <div class="col-12">
                  <h4>
                    <i class="fas fa-globe"></i> AdminLTE, Inc.
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
                    <strong>Admin, Inc.</strong><br>
                    795 Folsom Ave, Suite 600<br>
                    San Francisco, CA 94107<br>
                    Phone: (804) 123-5432<br>
                    Email: info@almasaeedstudio.com
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  To
                  <address>
                    <strong>{{ $Quote->companie['LABEL'] }}</strong><br>
                    795 Folsom Ave, Suite 600<br>
                    San Francisco, CA 94107<br>
                    Phone: (555) 539-1037<br>
                    Email: john.doe@example.com
                  </address>
                </div>
                <!-- /.col -->
                <div class="col-sm-4 invoice-col">
                  <b>Qute #{{  $Quote->CODE }}</b><br>
                  <br>
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
                          <td>{{ $QuoteLine->selling_price }}</td>
                          <td>{{ $QuoteLine->discount }}</td>
                          <td>{{ $QuoteLine->VAT['LABEL'] }}</td>
                          <td>{{ $QuoteLine->delivery_date }}</td>
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
                  <p class="lead">Payment Methods: {{ $Quote->payment_condition['LABEL'] }}</p>
                  <p class="lead">Payment Conditions: {{ $Quote->payment_method['LABEL'] }}</p>
                  @if($Quote->comment)
                    <p class="lead">Comment :</p>
                    <p class="text-muted well well-sm shadow-none" style="margin-top: 10px;">
                      {{  $Quote->comment }}
                    </p>
                  @endif
                </div>
                <!-- /.col -->
                <div class="col-6">
                  <p class="lead">Amount Due 2/22/2014</p>

                  <div class="table-responsive">
                    <table class="table">
                      <tr>
                        <th style="width:50%">Subtotal:</th>
                        <td>$250.30</td>
                      </tr>
                      <tr>
                        <th>Tax (9.3%)</th>
                        <td>$10.34</td>
                      </tr>
                      <tr>
                        <th>Shipping:</th>
                        <td>$5.80</td>
                      </tr>
                      <tr>
                        <th>Total:</th>
                        <td>$265.24</td>
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
                  <a href="invoice-print.html" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i> Print</a>
                  <button type="button" class="btn btn-primary float-right" style="margin-right: 5px;">
                    <i class="fas fa-download"></i> Generate PDF
                  </button>
                </div>
              </div>
            </div>
            <!-- /.invoice -->
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