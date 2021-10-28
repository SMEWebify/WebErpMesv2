@extends('adminlte::page')

@section('title', 'Products')

@section('content_header')
<div class="row mb-2">
  <div class="col-sm-6">
    <h1>Products list</h1>
  </div>
  <div class="col-sm-6">
    <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#ModalProduct">
      New product
    </button>
  </div>
</div>
 <!-- Modal -->
 <div class="modal fade" id="ModalProduct" tabindex="-1" role="dialog" aria-labelledby="ModalProductTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ModalProductTitle">New product</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('products.store')}}" enctype="multipart/form-data">
          @csrf
            <div class="row">
              <div class="col-4">
                <label for="CODE">External ID</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                  </div>
                 <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
                </div>
              </div>
              <div class="col-4">
                <label for="LABEL">Description</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label/Desciption of product">
                </div>
              </div>
              <div class="col-4">
                <label for="IND">Index</label>
                <input type="text" class="form-control" name="IND"  id="IND" placeholder="Index">
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-4">
                <label for="methods_services_id">Services</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                  </div>
                  <select class="form-control" name="methods_services_id" id="methods_services_id">
                    @forelse ($ServicesSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                    @empty
                    <option value="">No service, go to methods page for add</option>
                    @endforelse
                  </select>
                </div>
              </div>
              <div class="col-4">
                <label for="methods_families_id">Family</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-grip-horizontal"></i></span>
                  </div>
                  <select class="form-control" name="methods_families_id" id="methods_families_id">
                    @forelse ($FamiliesSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                    @empty
                    <option value="">No families, go to methods page for add</option>
                    @endforelse
                  </select>
                </div>
              </div>
              <div class="col-4">
                <label for="methods_units_id">Unit</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-ruler"></i></span>
                  </div>
                  <select class="form-control" name="methods_units_id" id="methods_units_id">
                    @forelse ($UnitsSelect as $item)
                    <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
                    @empty
                    <option value="">No units, go to methods page for add</option>
                    @endforelse
                  </select>
                </div>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-4">
                <label for="purchased">Purchased</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="purchased" id="purchased">
                    <option value="2">No</option>
                    <option value="1">Yes</option>
                  </select>
                </div>
              </div>
              <div class="col-4">
                <label for="sold">Sold</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                  </div>
                  <select class="form-control" name="sold" id="sold">
                    <option value="2">No</option>
                    <option value="1">Yes</option>
                  </select>
                </div>
              </div>
              <div class="col-4">
                <label for="tracability_type">Tracability</label>
                <select class="form-control" name="tracability_type" id="tracability_type">
                  <option value="1">No traceability</option>
                  <option value="2">With batch number</option>
                  <option value="3">With serial number</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-4">
                <input type="number" class="form-control" name="purchased_price" id="purchased_price" placeholder="Purchased price" step=".001">
              </div>
              <div class="col-4">
                <input type="number" class="form-control" name="selling_price" id="selling_price" placeholder="Selling price" step=".001">
              </div>
            </div>
            <hr>
            <div class="row">
              <label for="material">Proprieties</label>
            </div>
            <div class="row">
              <div class="col-4">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                  </div>
                  <input type="text" class="form-control" name="material" id="material" placeholder="Material">
                </div>
              </div>
              <div class="col-4">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                  </div>
                  <input type="number" class="form-control" name="thickness" id="thickness" placeholder="Thickness" step=".001">
                </div>
              </div>
              <div class="col-4">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                  </div>
                  <input type="number" class="form-control" name="weight" id="weight" placeholder="Weight" step=".001">
                </div>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-4">
                <label for="x_size">X</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                  </div>
                  <input type="number" class="form-control" name="x_size" id="x_size" placeholder="X size" step=".001">
                </div>
              </div>
              <div class="col-4">
                <label for="y_size">Y</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                  </div>
                  <input type="number" class="form-control" name="y_size" id="y_size" placeholder="Y size" step=".001">
                </div>
              </div>
              <div class="col-4">
                <label for="z_size">Z</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                  </div>
                  <input type="number" class="form-control" name="z_size" id="z_size" placeholder="Z size" step=".001">
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-4">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                  </div>
                  <input type="number" class="form-control" name="x_oversize" id="x_oversize" placeholder="X oversize" step=".001">
                </div>
              </div>
              <div class="col-4">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                  </div>
                  <input type="number" class="form-control" name="y_oversize" id="y_oversize" placeholder="Y oversize" step=".001">
                </div>
              </div>
              <div class="col-4">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                  </div>
                  <input type="number" class="form-control" name="z_oversize" id="z_oversize" placeholder="Z oversize" step=".001">
                </div>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-4">
                <input type="number" class="form-control" name="diameter" id="diameter" placeholder="Diameter" step=".001">
              </div>
              <div class="col-4">
                <input type="number" class="form-control" name="diameter_oversize" id="diameter_oversize" placeholder="Diameter_oversize" step=".001">
              </div>
              <div class="col-4">
                <input type="number" class="form-control" name="section_size" id="section_size" placeholder="Section size" step=".001">
              </div>
            </div>
            <hr>
            <div class="row">
              <label for="qty_eco_min">Other information</label>
            </div>
            <div class="row">
              <div class="col-4">
                <input type="number" class="form-control" name="qty_eco_min" id="qty_eco_min" placeholder="Qty eco min" step=".001">
              </div>
              <div class="col-4">
                <input type="number" class="form-control" name="qty_eco_max" id="qty_eco_max" placeholder="Qty eco max" step=".001">
              </div>
              <div class="col-4">
                <textarea class="form-control" rows="3" name="comment"  placeholder="Comment ..."></textarea>
              </div>
            </div>
            <hr>
            <div class="row">
              <label for="PICTURE">Logo file</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="far fa-image"></i></span>
                </div>
                <div class="custom-file">
                  <input type="file" class="custom-file-input" id="PICTURE">
                  <label class="custom-file-label" for="PICTURE">Choose file</label>
                </div>
                <div class="input-group-append">
                  <span class="input-group-text">Upload</span>
                </div>
              </div>
            </div>
            <hr>
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
                  @if($errors->count())
                    <div class="alert alert-danger">
                      <ul>
                      @foreach ( $errors->all() as $message)
                      <li> {{ $message }}</li>
                      @endforeach
                      </ul>
                    </div>
                  @endif
                  <div class="card-body">
                    <div  id="products_wrapper" class="dataTables_wrapper dt-bootstrap4">
                      <div class="col-sm-12">
                          <table id="products" class="table table-bordered table-striped dataTable dtr-inline" role="grid">
                            <thead>
                            <tr>
                              <th>Code</th>
                              <th>Label</th>
                              <th>Created At</th>
                              <th>Sold</th>
                              <th>Purchase</th>
                              <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                              @foreach ($Products as $Product)
                              <tr>
                                <td>{{ $Product->CODE }}</td>
                                <td>{{ $Product->LABEL }}</td>
                                <td>{{ $Product->GetPrettyCreatedAttribute() }}</td>
                                <td>
                                  @if($Product->sold == 1 )
                                  <i class="fas fa-check-double"></i>
                                  @else
                                  <i class="fas fa-times"></i>
                                  @endif
                                </td>
                                <td>
                                  @if($Product->purchased == 1 )
                                  <i class="fas fa-check"></i>
                                  @else
                                  <i class="fas fa-times"></i>
                                  @endif
                                </td>
                                <td>
                                  <a class="btn btn-primary btn-sm" href="{{ route('products.show', ['id' => $Product->id])}}">
                                    <i class="fas fa-folder"></i>
                                    View
                                  </a>
                                  <a class="btn btn-info btn-sm" href="#">
                                    <i class="fas fa-pencil-alt"></i>
                                    Edit
                                  </a>
                              </td>
                              </tr>
                              @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                              <th>Code</th>
                              <th>Label</th>
                              <th>Created At</th>
                              <th>Sold</th>
                              <th>Purchase</th>
                              <th>Action</th>
                            </tr>
                            </tfoot>
                          </table>
                      <!-- /.col-sm-12 -->
                      </div>
                    <!-- /.dataTables_wrapper dt-bootstrap4 -->
                    </div>
                  <!-- /.card-body -->
                  </div>
                </div>
                <!-- /.card -->

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
    $("#products").DataTable({
      dom: 'Bfrtip',
      buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    }).buttons().container().appendTo('#products_wrapper .col-md-6:eq(0)');
  } );

  
  </script>
@stop