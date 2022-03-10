@extends('adminlte::page')

@section('title', 'Purchase Receipt')

@section('content_header')
    
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Purchase Receipt</h1>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link" href="{{ route('purchases.receipt') }}">Back to lists</a></li>
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
              <form method="POST" action="{{ route('receipt.update', ['id' => $PurchaseReceipt->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code">External ID :</label>  {{  $PurchaseReceipt->code }}
                      </div>
                      <div class="col-3">
                        <label for="statu">Statu :</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                          </div>
                          <select class="form-control" name="statu" id="statu">
                            <option value="1" @if(1 == $PurchaseReceipt->statu ) Selected @endif >In progress</option>
                            <option value="2" @if(2 == $PurchaseReceipt->statu ) Selected @endif >Close</option>
                          </select>
                        </div>
                      </div>
                      
                      <div class="col-3">
                        <label for="label">Name of order</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control" name="label"  id="label" placeholder="Name of order" value="{{  $PurchaseReceipt->label }}">
                        </div>
                      </div>

                      <div class="col-3">
                        <label for="label">Delivery note number</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control" name="delivery_note_number"  id="delivery_note_number" placeholder="Delivery note number" value="{{  $PurchaseReceipt->delivery_note_number }}">
                        </div>
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
                      <div class="col-10">
                        <label>Comment</label>
                        <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..." >{{  $PurchaseReceipt->comment }}</textarea>
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
                <a href="{{ route('print.order', ['id' => $PurchaseReceipt->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print Receipt</a>
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
                  <tr>
                    <td>No Lines in this purchase order</td>
                    <td></td>
                    <td></td> 
                    <td></td> 
                    <td></td> 
                    <td></td>
                    <td></td> 
                  </tr>
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