@extends('adminlte::page')

@section('title', 'Products')

@section('content_header')
    <h1> {{ $Product->LABEL }} {{ $Product->IND }}</h1>
@stop

@section('content')
<div class="card card-primary">

  <div class="card-header">
    <h3 class="card-title">General information</h3>
    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
        <i class="fas fa-minus"></i>
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <div class="card-body">
    @if($errors->count())
      <div class="alert alert-danger">
        <ul>
        @foreach ( $errors->all() as $message)
         <li> {{ $message }}</li>
        @endforeach
        </ul>
      </div>
    @endif
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success')}}
    </div>
    @endif
    <div class="row">
      <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Service </span>
                <span class="info-box-number text-center  mb-0">{{ $Product->service['LABEL'] }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Familly</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->family['LABEL'] }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Tracability</span>
                <span class="info-box-number text-center  mb-0">
                  @if($Product->tracability_type  == 1)No traceability @endif
                  @if($Product->tracability_type  == 2)With batch number @endif
                  @if($Product->tracability_type  == 3)With serial number @endif
                </span>
              </div>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Material</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->material }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Thickness</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->thickness }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Weight</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->weight }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">X size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->x_size }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Y size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->y_size }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Z size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->z_size }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">X oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->x_oversize }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Y oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->y_oversize }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Z oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->z_oversize }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row --> 
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Diameter</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->diameter }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Diameter oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->diameter_oversize }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Section size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->section_size }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row --> 
        </div>
      <!-- /.div col-12 col-md-12 -->
      </div>
     
      <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
        <p class="text-muted">External ID : {{ $Product->CODE }} </p>

        <div class="row"> 
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Unit
              <b class="d-block">{{ $Product->Unit['LABEL'] }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>

        @if($Product->sold == 1 )
        <hr>
        <div class="row"> 
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Selling price
              <b class="d-block">{{ $Product->selling_price }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif

        @if($Product->purchased == 1 )
        <hr>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Purchased price
              <b class="d-block">{{ $Product->purchased_price }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif
        @if($Product->qty_eco_min)
        <hr>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Quantité eco min
              <b class="d-block">{{ $Product->qty_eco_min }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif

        @if($Product->qty_eco_max)
        <hr>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Quantité eco max
              <b class="d-block">{{ $Product->qty_eco_max }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif

         <!-- /.div mt-4 product-share -->
        </div>
      <!-- /.div col-12 col-md-12 -->
      </div>
    <!-- /.div row -->
    </div>
  <!-- /.card-body -->
  </div>
    

<div class="card card-secondary">
  <div class="card-header">
    <h3 class="card-title">Technical cut</h3>
    <div class="card-tools">
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#TechnicalCutModal">
        Add line to Technical cut
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
        <i class="fas fa-minus"></i>
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <!-- Modal -->
  <div class="modal fade" id="TechnicalCutModal" tabindex="-1" role="dialog" aria-labelledby="TechnicalCutModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="TechnicalCutModalTitle">Add line to Technical cut</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="POST" action="{{ route('task.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
              <div class="col-4">
                <label for="ORDER">Sort order</label>
                <input type="number" class="form-control" name="ORDER" id="ORDER" placeholder="Order">
                <input type="hidden" class="form-control" name="products_id" value="{{ $Product->id }}">
              </div>
              <div class="col-4">
                <label for="methods_services_id">Services</label>
                  <select class="form-control" name="methods_services_id" id="methods_services_id">
                    @foreach ($TechServicesSelect as $item)
                    <option value="{{ $item->id }}"  data-type="{{ $item->TYPE }}" data-txt="{{ $item->LABEL }}">{{ $item->CODE }}</option>
                    @endforeach
                  </select>
                  <input type="hidden" class="form-control" name="TYPE" id="TYPE">
              </div>
              <div class="col-4">
                  <label for="LABEL">Label</label>
                  <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
              </div>
            </div>
            <div class="row">
              <div class="col-3">
                <label for="SETING_TIME">Setting time</label>
                <input type="number" class="form-control" name="SETING_TIME"  id="SETING_TIME" placeholder="Setting time" step=".001">
              </div>
              <div class="col-3">
                <label for="UNIT_TIME">Unit time</label>
                <input type="number" class="form-control" name="UNIT_TIME"  id="UNIT_TIME" placeholder="Unit time" step=".001">
              </div>
              <div class="col-3">
                <label for="UNIT_COST">Unit cost</label>
                <input type="number" class="form-control" name="UNIT_COST"  id="UNIT_COST" placeholder="Unit cost" step=".001">
              </div>
              <div class="col-3">
                <label for="UNIT_PRICE">Unit price</label>
                <input type="number" class="form-control" name="UNIT_PRICE"  id="UNIT_PRICE" placeholder="Unit time" step=".001">
              </div>
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
  <!-- End Modal -->
  <div class="card-body">
    <div class="row">
      <table id="example1" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Sort order</th>
            <th>Label</th>
            <th>Service</th>
            <th>Setting time</th>
            <th>Unit time</th>
            <th>Unit cost</th>
            <th>Unit price</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
          @forelse ($TechProductList as $TechProduct)
          <tr>
            <td>{{ $TechProduct->ORDER }}</td>
            <td>{{ $TechProduct->LABEL }}</td>
            <td>{{ $TechProduct->service['LABEL'] }}</td>
            <td>{{ $TechProduct->SETING_TIME }}</td>
            <td>{{ $TechProduct->UNIT_TIME }}</td>
            <td>{{ $TechProduct->UNIT_COST }}</td>
            <td>{{ $TechProduct->UNIT_PRICE }}</td>
            <td class=" py-0 align-middle">
              <div class="btn-group btn-group-sm">
                <a href="#" class="btn btn-info"><i class="fa fa-lg fa-fw  fa-edit"></i></a>
              </div>
              <div class="btn-group btn-group-sm">
                <a href="#" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td>No Data</td>
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
        <tfoot>
          <tr>
            <th>Sort order</th>
            <th>Label</th>
            <th>Service</th>
            <th>Setting time</th>
            <th>Unit time</th>
            <th>Unit cost</th>
            <th>Unit price</th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <!-- /.card-body -->
</div>

<div class="card card-secondary">
  <div class="card-header">
    <h3 class="card-title">Bill of materials</h3>

    <div class="card-tools">
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#BOMModal">
        Add line to BOM
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
        <i class="fas fa-minus"></i>
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
    <!-- Modal -->
    <div class="modal fade" id="BOMModal" tabindex="-1" role="dialog" aria-labelledby="BOMModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="BOMModalTitle">Add line to BOM</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form method="POST" action="{{ route('task.store') }}" enctype="multipart/form-data">
              @csrf
              <div class="row">
                <div class="col-3">
                  <label for="ORDER">Sort order</label>
                  <input type="number" class="form-control" name="ORDER" id="ORDER" placeholder="Order">
                  <input type="hidden" class="form-control" name="products_id" value="{{ $Product->id }}">
                </div>
                <div class="col-3">
                    <label for="methods_services_id">Services</label>
                      <select class="methods_services_id form-control" name="methods_services_id" id="methods_services_id_BOM">
                        <option>Select Services</option>
                        @foreach ($BOMServicesSelect as $item)
                        <option value="{{ $item->id }}"  class="{{ $item->id }}" data-type="{{ $item->TYPE }}"  data-txt="{{ $item->LABEL }}">{{ $item->CODE }}</option>
                        @endforeach
                      </select>
                      <input type="hidden" class="form-control" name="TYPE" id="TYPE_BOM">
                </div>
                <div class="col-3">
                  <label for="component_id">Component</label>
                    <select class="component_id form-control" name="component_id" id="component_id">
                      <option>Select Component</option>
                      @foreach ($ProductSelect as $item)
                      <option value="{{ $item->id }}" class="{{ $item->methods_services_id }}">{{ $item->CODE }}</option>
                      @endforeach
                    </select>
              </div>
                <div class="col-3">
                    <label for="LABEL">Label</label>
                    <input type="text" class="form-control" name="LABEL"  id="LABEL_BOM" placeholder="Label">
                </div>
              </div>
              <div class="row">
                <div class="col-3">
                  <label for="QTY">Quantity</label>
                  <input type="number" class="form-control" name="QTY"  id="QTY" placeholder="Quantity" step=".001">
                </div>
                <div class="col-3">
                  <label for="UNIT_COST">Unit cost</label>
                  <input type="number" class="form-control" name="UNIT_COST"  id="UNIT_COST" placeholder="Unit cost" step=".001">
                </div>
                <div class="col-3">
                  <label for="UNIT_PRICE">Unit price</label>
                  <input type="number" class="form-control" name="UNIT_PRICE"  id="UNIT_PRICE" placeholder="Unit time" step=".001">
                </div>
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
    <!-- End Modal -->
  <div class="card-body">
    <div class="row">
      <table id="example1" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th>Sort order</th>
            <th>Label</th>
            <th>Service</th>
            <th>Component</th>
            <th>Quantity</th>
            <th>Unit cost</th>
            <th>Unit price</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
          @forelse ($BOMProductList as $BOMProduct)
          <tr>
            <td>{{ $BOMProduct->ORDER }}</td>
            <td>{{ $BOMProduct->LABEL }}</td>
            <td>{{ $BOMProduct->service['LABEL'] }}</td>
            <td>{{ $BOMProduct->Component['CODE'] }}</td>
            <td>{{ $BOMProduct->QTY }}</td>
            <td>{{ $BOMProduct->UNIT_COST }}</td>
            <td>{{ $BOMProduct->UNIT_PRICE }}</td>
            <td class=" py-0 align-middle">
              <div class="btn-group btn-group-sm">
                <a href="#" class="btn btn-info"><i class="fa fa-lg fa-fw fa-edit"></i></a>
              </div>
              <div class="btn-group btn-group-sm">
                <a href="#" class="btn btn-danger"><i class="fa fa-lg fa-fw fa-trash"></i></a>
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td>No Data</td>
            <td></td> 
            <td></td> 
            <td></td> 
            <td></td> 
            <td></td>
            <td></td>
          </tr>
          @endforelse
        </tbody>
        <tfoot>
          <tr>
            <th>Sort order</th>
            <th>Label</th>
            <th>Service</th>
            <th>Component</th>
            <th>Quantity</th>
            <th>Unit cost</th>
            <th>Unit price</th>
            <th></th>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->

            @stop
                  
            @section('css')
               
            @stop
                             
           @section('js')
             <script > 
             
             $('#methods_services_id').on('change',function(){
                var val = $(this).val();
                var txt = $(this).find('option:selected').data('txt');
                var type = $(this).find('option:selected').data('type');
                $('#LABEL').val( txt );
                $('#TYPE').val( type );
            });

            $('#methods_services_id_BOM').on('change',function(){
                var val = $(this).val();
                var txt = $(this).find('option:selected').data('txt');
                var type = $(this).find('option:selected').data('type');
                $('#LABEL_BOM').val( txt );
                $('#TYPE_BOM').val( type );
            });

            $('.methods_services_id').change(function () {
                var modelObj = $(this).parent().next().children(".component_id");
                var selector = "option[class="+this.value.toLowerCase()+"]";
                modelObj.children(":not("+selector+")").hide();
                modelObj.children(selector).show();
            });
            </script>

           @stop