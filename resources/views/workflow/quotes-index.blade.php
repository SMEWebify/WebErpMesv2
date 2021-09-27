@extends('adminlte::page')

@section('title', 'Quotes list')

@section('content_header')
    

    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Quotes list</h1>
      </div>
      <div class="col-sm-6">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalCompanie">
          New quote
        </button>
      </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="ModalCompanie" tabindex="-1" role="dialog" aria-labelledby="ModalCompanieTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="ModalCompanieTitle">New Quote</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('quote.store')}}" enctype="multipart/form-data">
              @csrf
                <div class="row">
                  <div class="col-3">
                    <label for="CODE">External ID</label>
                    <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID" value="QT-{{ $LastQuote->id ?? '0' }}">
                  </div>
                  <div class="col-3">
                    <label for="LABEL">Name of quote</label>
                    <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Name of quote" required>
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
                    <select class="form-control" name="companies_id" id="companies_id">
                      @foreach ($CompanieSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-5">
                    <label for="customer_reference">Customer reference</label>
                    <input type="text" class="form-control" name="customer_reference"  id="customer_reference" placeholder="Customer reference">
                  </div>
                </div>
                <div class="row">
                  <div class="col-5">
                    <label for="companies_addresses_id">Adress</label>
                    <select class="form-control" name="companies_addresses_id" id="companies_addresses_id">
                      @foreach ($AddressSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->LABEL }} - {{ $item->ADRESS }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-5">
                    <label for="companies_contacts_id">Contact</label>
                    <select class="form-control" name="companies_contacts_id" id="companies_contacts_id">
                      @foreach ($ContactSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->FIRST_NAME }} - {{ $item->NAME }}</option>
                      @endforeach
                    </select>
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
                      @foreach ($AccountingConditionSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-5">
                    <label for="accounting_payment_methods_id">Payment methods</label>
                    <select class="form-control" name="accounting_payment_methods_id" id="accounting_payment_methods_id">
                      @foreach ($AccountingMethodsSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="col-5">
                    <label for="accounting_deliveries_id">Delevery method</label>
                    <select class="form-control" name="accounting_deliveries_id" id="accounting_deliveries_id">
                      @foreach ($AccountingDeleveriesSelect as $item)
                      <option value="{{ $item->id }}">{{ $item->CODE }} - {{ $item->LABEL }}</option>
                      @endforeach
                    </select>
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
      <li class="nav-item"><a class="nav-link active" href="#Quotes" data-toggle="tab">Quotes list</a></li>
      <li class="nav-item"><a class="nav-link" href="#QuotesLine" data-toggle="tab">Quotes lines list</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
                    <div class="tab-pane active" id="Quotes">
                        <div  id="quotes_wrapper" class="dataTables_wrapper dt-bootstrap4">
                            <div class="col-sm-12">
                              <table id="quotes" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                                <thead>
                                  <tr>
                                    <th>Code</th>
                                    <th>Label</th>
                                    <th>Companie</th>
                                    <th>Customer reference</th>.
                                    <th>Statu</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                  </tr>
                                  </thead>
                                  <tbody>
                                    @foreach ($Quoteslist as $Quote)
                                    <tr>
                                      <td>{{ $Quote->CODE }}</td>
                                      <td>{{ $Quote->LABEL }}</td>
                                      <td>{{ $Quote->companie['LABEL'] }}</td>
                                      <td>{{ $Quote->customer_reference }}</td>
                                      <td>
                                        @if(1 == $Quote->STATU )   <span class="badge badge-info"> Open</span>@endif
                                        @if(2 == $Quote->STATU )  <span class="badge badge-warning">Send</span>@endif
                                        @if(3 == $Quote->STATU )  <span class="badge badge-success">Win</span>@endif
                                        @if(4 == $Quote->STATU )  <span class="badge badge-danger">Lost</span>@endif
                                        @if(5 == $Quote->STATU )  <span class="badge badge-secondary">Closed</span>@endif
                                        @if(6 == $Quote->STATU )   <span class="badge badge-secondary">Obsolete</span>@endif
                                      </td>
                                      <td>{{ $Quote->GetPrettyCreatedAttribute() }}</td>
                                      <td>
                                        <a class="btn btn-primary btn-sm" href="{{ route('quote.show', ['id' => $Quote->id])}}">
                                          <i class="fas fa-folder"></i>
                                          View
                                        </a>
                                        <a class="btn btn-success btn-sm" href="{{ route('quote.print', ['id' => $Quote->id])}}">
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
                                    <th>Customer reference</th>.
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
                    

                    <div class="tab-pane" id="QuotesLine">
                      <div  id="quotes_wrapper" class="dataTables_wrapper dt-bootstrap4">
                        <div class="col-sm-12">
                          <table id="quotes" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                            <thead>
                            <tr>
                              <th>Quote</th>
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
                              @foreach ($QuoteLineslist as $QuoteLine)
                              <tr>
                                <td>{{ $QuoteLine->quote['CODE'] }}</td>
                                <td>{{ $QuoteLine->ORDRE }}</td>
                                <td>{{ $QuoteLine->CODE }}</td>
                                <td>@if(1 == $QuoteLine->product_id ) {{ $QuoteLine->Product['LABEL'] }}@endif</td>
                                <td>{{ $QuoteLine->LABEL }}</td>
                                <td>{{ $QuoteLine->qty }}</td>
                                <td>{{ $QuoteLine->Unit['LABEL'] }}</td>
                                <td>{{ $QuoteLine->selling_price }}</td>
                                <td>{{ $QuoteLine->discount }}</td>
                                <td>{{ $QuoteLine->VAT['LABEL'] }}</td>
                                <td>{{ $QuoteLine->delivery_date }}</td>
                                <td>
                                  @if(1 == $QuoteLine->statu )   <span class="badge badge-info"> Open</span>@endif
                                  @if(2 == $QuoteLine->statu )  <span class="badge badge-warning">Send</span>@endif
                                  @if(3 == $QuoteLine->statu )  <span class="badge badge-success">Win</span>@endif
                                  @if(4 == $QuoteLine->statu )  <span class="badge badge-danger">Lost</span>@endif
                                  @if(5 == $QuoteLine->statu )  <span class="badge badge-secondary">Closed</span>@endif
                                  @if(6 == $QuoteLine->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                                </td>
                                <td>
                                  <a class="btn btn-primary btn-sm" href="{{ route('quote.show', ['id' => $QuoteLine->quotes_id])}}">
                                    <i class="fas fa-folder"></i>
                                    View
                                  </a>
                                </td>
                              </tr>
                              @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                              <th>Quote</th>
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
    $("#quotes").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#quotes_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop