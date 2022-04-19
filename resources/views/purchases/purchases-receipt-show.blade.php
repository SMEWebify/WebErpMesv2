@extends('adminlte::page')

@section('title', 'Purchase Receipt')

@section('content_header')
  <x-Content-header-previous-button  h1="Purchase Receipt: {{  $PurchaseReceipt->code }}" previous="{{ $previousUrl }}" list="{{ route('purchases.receipt') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Purchase" data-toggle="tab">Purchase receipt info</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseLines" data-toggle="tab">Purchase receipt lines</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Purchase">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card">
              <form method="POST" action="{{ route('receipt.update', ['id' => $PurchaseReceipt->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">External ID :</label>  {{  $PurchaseReceipt->code }}
                      </div>
                      <div class="col-3">
                        <x-adminlte-select name="statu" label="Statu" label-class="text-success" igroup-size="sm">
                          <x-slot name="prependSlot">
                              <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-exclamation"></i>
                              </div>
                          </x-slot>
                          <option value="1" @if(1 == $PurchaseReceipt->statu ) Selected @endif >In progress</option>
                          <option value="2" @if(2 == $PurchaseReceipt->statu ) Selected @endif >Close</option>
                        </x-adminlte-select>
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>'Name of reciept', 'Value' =>  $PurchaseReceipt->label])
                      </div>

                      <div class="col-3">
                        <x-adminlte-input name="delivery_note_number" label="Delivery note number" placeholder="Delivery note number" value="{{  $PurchaseReceipt->delivery_note_number }}" label-class="text-success">
                          <x-slot name="prependSlot">
                            <div class="input-group-text bg-gradient-success">
                                  <i class="fas fa-tags"></i>
                              </div>
                          </x-slot>
                        </x-adminlte-input>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Supplier information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        <label for="companies_id">Companie</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <a class="btn btn-primary btn-sm" href="{{ route('companies.show', ['id' => $PurchaseReceipt->companie->id])}}">
                              <i class="fas fa-buildin"></i>
                              {{  $PurchaseReceipt->companie->code }} - {{  $PurchaseReceipt->companie->label }}
                            </a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $PurchaseReceipt->comment }}" />
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
                <a href="{{ route('print.order', ['Document' => $PurchaseReceipt->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print Receipt</a>
              </div>
            </div>
          </div>
        </div>
      </div>    
      <div class="tab-pane " id="PurchaseLines">
        <!-- Table row -->
        <div class="row">
          <div class="col-12 table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Purchase Order</th>
                  <th>Order</th>
                  <th>Description</th>
                  <th>Supplier ref</th>
                  <th>Qty</th>
                  <th>Qty purchase</th>
                  <th>Qty receipt</th>
                </tr>
              </thead>
              <tbody>
                  @forelse($PurchaseReceipt->PurchaseReceiptLines as $PurchaseReceiptLine)
                  <tr>
                    <td>
                      <a class="btn btn-primary btn-sm" href="{{ route('purchase.show', ['id' => $PurchaseReceiptLine->purchaseLines->purchases_id ])}}">
                          <i class="fas fa-folder"></i>
                          {{ $PurchaseReceiptLine->purchaseLines->purchase->code }}
                      </a>
                    </td>
                    <td>
                      <x-OrderButton id="{{ $PurchaseReceiptLine->purchaseLines->tasks->OrderLines->orders_id }}" code="{{ $PurchaseReceiptLine->purchaseLines->tasks->OrderLines->order->code }}"  />
                    </td>
                    <td>#{{ $PurchaseReceiptLine->purchaseLines->tasks->id }} {{ $PurchaseReceiptLine->purchaseLines->tasks->code }} {{ $PurchaseReceiptLine->purchaseLines->tasks->label }}</td>
                    <td>{{ $PurchaseReceiptLine->purchaseLines->supplier_ref }}</td>
                    <td>{{ $PurchaseReceiptLine->purchaseLines->tasks->qty  }}</td>
                    <td>{{ $PurchaseReceiptLine->purchaseLines->qty  }}</td>
                    <td>{{ $PurchaseReceiptLine->receipt_qty }}</td>
                  </tr>
                @empty
                  <x-EmptyDataLine col="7" text="No Lines in this purchase reciept ..."  />
              @endforelse
                <tfoot>
                  <tr>
                    <th>Purchase Order</th>
                    <th>Order</th>
                    <th>Description</th>
                    <th>Supplier ref</th>
                    <th>Qty</th>
                    <th>Qty purchase</th>
                    <th>Qty receipt</th>
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

<script>
  $(function () {
    $('[data-toggle="tooltip"]').tooltip({
          html:true
      })
  })
</script>
@stop