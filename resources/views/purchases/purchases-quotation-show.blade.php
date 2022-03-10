@extends('adminlte::page')

@section('title', 'Purchase quotation')

@section('content_header')
    
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Purchase quotation</h1>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link" href="{{ route('purchases.quotation') }}">Back to lists</a></li>
      <li class="nav-item"><a class="nav-link active" href="#PurchaseQuotation" data-toggle="tab">Purchase quotation info</a></li>
      <li class="nav-item"><a class="nav-link" href="#PurchaseQuotationLines" data-toggle="tab">Purchase quotation lines</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="PurchaseQuotation">
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
              <form method="POST" action="{{ route('quotation.update', ['id' => $PurchaseQuotation->id]) }}" enctype="multipart/form-data">
                @csrf 
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code">External ID :</label>  {{  $PurchaseQuotation->code }}
                      </div>
                      <div class="col-3">
                        <label for="statu">Statu :</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                          </div>
                          <select class="form-control" name="statu" id="statu">
                            <option value="1" @if(1 == $PurchaseQuotation->statu ) Selected @endif >Open</option>
                            <option value="2" @if(2 == $PurchaseQuotation->statu ) Selected @endif >In progress</option>
                            <option value="3" @if(3 == $PurchaseQuotation->statu ) Selected @endif >Delivered</option>
                            <option value="4" @if(4 == $PurchaseQuotation->statu ) Selected @endif >Partly delivered</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-3">
                        <label for="label">Name of order</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-tags"></i></span>
                          </div>
                          <input type="text" class="form-control" name="label"  id="label" placeholder="Name of order" value="{{  $PurchaseQuotation->label }}">
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
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                          </div>
                          <select class="form-control" name="companies_id" id="companies_id">
                            @foreach ($CompanieSelect as $item)
                            <option value="{{ $item->id }}"  @if($item->id == $PurchaseQuotation->companies_id ) Selected @endif >{{ $item->code }} - {{ $item->label }}</option>
                            @endforeach
                          </select>
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
                            <option value="{{ $item->id }}" @if($item->id == $PurchaseQuotation->companies_addresses_id ) Selected @endif >{{ $item->label }} - {{ $item->adress }}</option>
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
                            <option value="{{ $item->id }}" @if($item->id == $PurchaseQuotation->companies_contacts_id ) Selected @endif >{{ $item->first_name }} - {{ $item->name }}</option>
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
                      <div class="col-5">
                        <label for="label">Validity date</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $PurchaseQuotation->validity_date }}">
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-10">
                        <label>Comment</label>
                        <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..." >{{  $PurchaseQuotation->comment }}</textarea>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="Submit" class="btn btn-primary">Save changes</button>
                  </div>
                </form>
              </div>
            
            <div class="col-md-3">
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"> Informations </h3>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    
                  </div>
                </div>
              </div>
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title"> Options </h3>
                </div>
                <div class="card-body">
                  <a href="{{ route('print.order', ['id' => $PurchaseQuotation->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print quotation</a>
                </div>
              </div>
            </div>
          </div>
        </div>     
        <div class="tab-pane " id="PurchaseQuotationLines">
          <!-- Table row -->
          <div class="row">
            <div class="col-12 table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Order</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Unit price</th>
                    <th>Total price</th>
                  </tr>
                </thead>
                <tbody>
                    @forelse($PurchaseQuotation->PurchaseQuotationLines as $PurchaseQuotationLine)
                    <tr>
                      <td>
                        <x-OrderButton id="{{ $PurchaseQuotationLine->tasks->OrderLines->orders_id }}" code="{{ $PurchaseQuotationLine->tasks->OrderLines->order->code }}"  />
                      </td>
                      <td>#{{ $PurchaseQuotationLine->tasks->id }} {{ $PurchaseQuotationLine->code }} {{ $PurchaseQuotationLine->label }}</td>
                      <td>{{ $PurchaseQuotationLine->qty_to_order }}</td>
                      <td>{{ $PurchaseQuotationLine->unit_price }}</td>
                      <td>{{ $PurchaseQuotationLine->total_price }}</td>
                    </tr>
                  @empty
                    <tr>
                      <td>No Lines in this purchase order</td>
                      <td></td> 
                      <td></td> 
                      <td></td> 
                      <td></td> 
                      <td></td>
                    </tr>
                @endforelse
                  <tfoot>
                    <tr>
                      <th>Order</th>
                      <th>Description</th>
                      <th>Qty</th>
                      <th>Unit price</th>
                      <th>Total price</th>
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