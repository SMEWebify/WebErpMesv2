@extends('adminlte::page')

@section('title', 'Orders list')

@section('content_header')
    

    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Orders list</h1>
      </div>
      <div class="col-sm-6">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalCompanie">
          New Order
        </button>
      </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="ModalCompanie" tabindex="-1" role="dialog" aria-labelledby="ModalCompanieTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ModalCompanieTitle">New Order</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('order.store')}}" enctype="multipart/form-data">
              @csrf
                <div class="row">
                  <div class="col-3">
                    <label for="CODE">External ID</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                      </div>
                      <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID" value="OR-{{ $LastOrder->id ?? '0' }}">
                    </div>
                  </div>
                  <div class="col-3">
                    <label for="LABEL">Name of order</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="LABEL"  id="LABEL" value="OR-{{ $LastOrder->id ?? '0' }}" placeholder="Name of order" required>
                    </div>
                  </div>
                  <div class="col-3">
                    <label for="user_id">User management</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                      </div>
                      <select class="form-control" name="user_id" id="user_id">
                        @foreach ($userSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                </div>
                <hr>
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
                        @forelse ($CompanieSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                        @empty
                        <option value="">No company, please add</option>
                        @endforelse
                      </select>
                    </div>
                  </div>
                  <div class="col-5">
                    <label for="customer_reference">Customer reference</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                      </div>
                      <input type="text" class="form-control" name="customer_reference"  id="customer_reference" placeholder="Customer reference">
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
                        @forelse ($AddressSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->LABEL }} - {{ $item->ADRESS }}</option>
                        @empty
                        <option value="">No address, please add</option>
                        @endforelse
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
                        @forelse ($ContactSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->FIRST_NAME }} - {{ $item->NAME }}</option>
                        @empty
                        <option value="">No contact, please add</option>
                        @endforelse
                      </select>
                    </div>
                  </div>
                </div>
                <hr>
                <div class="row">
                  <label for="InputWebSite">Date & Payment information</label>
                </div>
                <hr>
                <div class="row">
                  <div class="col-5">
                    <label for="accounting_payment_conditions_id">Payment condition</label>
                    <select class="form-control" name="accounting_payment_conditions_id" id="accounting_payment_conditions_id">
                      @forelse ($AccountingConditionSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @empty
                      <option value="">No payment conditions, please add in accounting page</option>
                      @endforelse
                    </select>
                  </div>
                  <div class="col-5">
                    <label for="accounting_payment_methods_id">Payment methods</label>
                    <select class="form-control" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                      @forelse ($AccountingMethodsSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @empty
                      <option value="">No payment methods, please add in accounting page</option>
                      @endforelse
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
                        @forelse ($AccountingDeleveriesSelect as $item)
                        <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                        @empty
                        <option value="">No delivery type, please add in accounting page</option>
                        @endforelse
                      </select>
                    </div>
                  </div>
                  <div class="col-5">
                    <label for="LABEL">Validity date</label>
                    <input type="date" class="form-control" name="validity_date"  id="validity_date">
                  </div>
                </div>
                <hr>
                <div class="row">
                  <div class="col-10">
                    <label>Comment</label>
                    <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..."></textarea>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                  <button type="Submit" class="btn btn-primary">Submit</button>
                </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- End Modal -->

@stop

@section('right-sidebar')

@section('content')


<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Orders" data-toggle="tab">Orders list</a></li>
      <li class="nav-item"><a class="nav-link" href="#OrdersLine" data-toggle="tab">Orders lines list</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Orders">
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
                        <div  id="orders_wrapper" class="dataTables_wrapper dt-bootstrap4">
                            <div class="col-sm-12">
                              <table id="Orders" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                                <thead>
                                  <tr>
                                    <th>Code</th>
                                    <th>Label</th>
                                    <th>Companie</th>
                                    <th>Customer reference</th>
                                    <th>Statu</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                  </tr>
                                  </thead>
                                  <tbody>
                                    @foreach ($Orderslist as $Order)
                                    <tr>
                                      <td>{{ $Order->CODE }}</td>
                                      <td>{{ $Order->LABEL }}</td>
                                      <td>{{ $Order->companie['LABEL'] }}</td>
                                      <td>{{ $Order->customer_reference }}</td>
                                      <td>
                                        @if(1 == $Order->statu )   <span class="badge badge-info"> Open</span>@endif
                                        @if(2 == $Order->statu )  <span class="badge badge-warning">Send</span>@endif
                                        @if(3 == $Order->statu )  <span class="badge badge-success">Win</span>@endif
                                        @if(4 == $Order->statu )  <span class="badge badge-danger">Lost</span>@endif
                                        @if(5 == $Order->statu )  <span class="badge badge-secondary">Closed</span>@endif
                                        @if(6 == $Order->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                                      </td>
                                      <td>{{ $Order->GetPrettyCreatedAttribute() }}</td>
                                      <td>
                                        <a class="btn btn-primary btn-sm" href="{{ route('order.show', ['id' => $Order->id])}}">
                                          <i class="fas fa-folder"></i>
                                          View
                                        </a>
                                        <a class="btn btn-success btn-sm" href="{{ route('order.print', ['id' => $Order->id])}}">
                                          <i class="fas fa-print"></i>
                                          Print
                                        </a>
                                      </td>
                                    </tr>
                                    @endforeach
                                  </tbody>
                                  <tfoot>
                                  <tr>
                                    <th>Code</th>
                                    <th>Label</th>
                                    <th>Companie</th>
                                    <th>Customer reference</th>
                                    <th>Statu</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                  </tr>
                                </tfoot>
                              </table>

                          <!-- /.row -->
                          </div>
                          
                        <!-- /.dataTables_wrapper -->
                        </div>
                        
                    <!-- /.tab-pane -->
                    </div>
                    

                    <div class="tab-pane" id="OrdersLine">
                      <div  id="orders_lines_wrapper" class="dataTables_wrapper dt-bootstrap4">
                        <div class="col-sm-12">
                          <table id="orders_lines" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                            <thead>
                              <tr>
                                <th>Order</th>
                                <th>Sort</th>
                                <th>External ID</th>
                                <th>Product</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Selling price</th>
                                <th>Discount</th>
                                <th>VAT type</th>
                                <th>Delivery date</th>
                                <th>Statu</th>
                                <th>Action</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach ($OrderLineslist as $OrderLine)
                              <tr>
                                <td>{{ $OrderLine->order['CODE'] }}</td>
                                <td>{{ $OrderLine->ORDRE }}</td>
                                <td>{{ $OrderLine->CODE }}</td>
                                <td>@if(1 == $OrderLine->product_id ) {{ $OrderLine->Product['LABEL'] }}@endif</td>
                                <td>{{ $OrderLine->LABEL }}</td>
                                <td>{{ $OrderLine->qty }}</td>
                                <td>{{ $OrderLine->Unit['LABEL'] }}</td>
                                <td>{{ $OrderLine->selling_price }}</td>
                                <td>{{ $OrderLine->discount }}</td>
                                <td>{{ $OrderLine->VAT['LABEL'] }}</td>
                                <td>{{ $OrderLine->delivery_date }}</td>
                                <td>
                                  @if(1 == $OrderLine->statu )   <span class="badge badge-info"> Open</span>@endif
                                  @if(2 == $OrderLine->statu )  <span class="badge badge-warning">Send</span>@endif
                                  @if(3 == $OrderLine->statu )  <span class="badge badge-success">Win</span>@endif
                                  @if(4 == $OrderLine->statu )  <span class="badge badge-danger">Lost</span>@endif
                                  @if(5 == $OrderLine->statu )  <span class="badge badge-secondary">Closed</span>@endif
                                  @if(6 == $OrderLine->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                                </td>
                                <td>
                                  <a class="btn btn-primary btn-sm" href="{{ route('order.show', ['id' => $OrderLine->orders_id])}}">
                                    <i class="fas fa-folder"></i>
                                    View
                                  </a>
                                </td>
                              </tr>
                              @endforeach
                            </tbody>
                            <tfoot>
                              <tr>
                                <th>Order</th>
                                <th>Sort</th>
                                <th>External ID</th>
                                <th>Product</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit</th>
                                <th>Selling price</th>
                                <th>Discount</th>
                                <th>VAT type</th>
                                <th>Delivery date</th>
                                <th>Statu</th>
                                <th>Action</th>
                              </tr>
                            </tfoot>
                          </table>
                        </div>
                      </div>
                    <!-- /.tab-pane -->
                    </div>
                    
    <!-- /.tab-content -->
    </div>
  <!-- /.card-body -->
  </div>
<!-- /.card -->
</div>

@stop
                  
 @section('css')
    
   <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.0/css/jquery.dataTables.min.css">
   <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.0/css/buttons.dataTables.min.css">
 @stop
                  
@section('js')

<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.11.0/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.html5.min.js"></script>
<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.print.min.js"></script>
  <script> 

  $(document).ready( function () {
    $("#orders").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#orders_wrapper .col-md-6:eq(0)');
  } );

  $(document).ready( function () {
    $("#orders_lines").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#orders_lines_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop