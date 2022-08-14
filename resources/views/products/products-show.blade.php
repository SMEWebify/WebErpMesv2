@extends('adminlte::page')

@section('title', 'Products')

@section('content_header')
  <x-Content-header-previous-button  h1="{{ $Product->label }}" previous="{{ $previousUrl }}" list="{{ route('products') }}" next="{{ $nextUrl }}"/>
@stop

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>


<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Product" data-toggle="tab">Detail</a></li>
      <li class="nav-item"><a class="nav-link" href="#TechnicalInfo" data-toggle="tab">Technical cut + BOM ({{ $Product->getTaskCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#quote" data-toggle="tab">Quotes list</a></li>
      <li class="nav-item"><a class="nav-link" href="#order" data-toggle="tab">Orders list</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Product">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('products.update', ['id' => $Product->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                        <div class="col-4">
                          <div class="text-muted">
                            <label for="label">External ID</label>
                              <b class="d-block">{{ $Product->code }}</b>
                            </p>
                          </div>
                        </div>
                        <div class="col-4">
                            <label for="label">Description</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                </div>
                                <input type="text" class="form-control" value="{{ $Product->label }}" name="label"  id="label" placeholder="Label/Desciption of product">
                            </div>
                        </div>
                        <div class="col-4">
                            <label for="ind">Index</label>
                            <input type="text" class="form-control" value="{{ $Product->ind }}"   name="ind"  id="ind" placeholder="Index">
                        </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                        <div class="col-4">
                            <label for="methods_services_id">Services</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-list"></i></span>
                                </div>
                                <select class="form-control" name="methods_services_id" id="methods_services_id">
                                    <option value="">Select service</option>
                                    @forelse ($ServicesSelect as $item)
                                    <option value="{{ $item->id }}" @if($Product->methods_services_id == $item->id ) Selected @endif  >{{ $item->label }}</option>
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
                                    <option value="">Select familie</option>
                                    @forelse ($FamiliesSelect as $item)
                                    <option value="{{ $item->id }}" @if($Product->methods_families_id == $item->id ) Selected @endif >{{ $item->label }}</option>
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
                                    <option value="">Select unit</option>
                                    @forelse ($UnitsSelect as $item)
                                    <option value="{{ $item->id }}" @if($Product->methods_units_id == $item->id ) Selected @endif>{{ $item->label }}</option>
                                    @empty
                                        <option value="">No units, go to methods page for add</option>
                                    @endforelse
                                </select>
                            </div>
                        </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                        <div class="col-4">
                            <label for="purchased">Purchased</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                </div>
                                <select class="form-control" name="purchased" id="purchased">
                                    <option value="">Select statu</option>
                                    <option value="2" @if($Product->purchased == 2 ) Selected @endif>No</option>
                                    <option value="1" @if($Product->purchased == 1 ) Selected @endif>Yes</option>
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
                                    <option value="">Select statu</option>
                                    <option value="2" @if($Product->sold == 2 ) Selected @endif>No</option>
                                    <option value="1" @if($Product->sold == 1 ) Selected @endif>Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <label for="tracability_type">Tracability</label>
                            <select class="form-control" name="tracability_type" id="tracability_type">
                                <option value="">Select type</option>
                                <option value="1" @if($Product->tracability_type == 1 ) Selected @endif>No traceability</option>
                                <option value="2" @if($Product->tracability_type == 2 ) Selected @endif>With batch number</option>
                                <option value="3" @if($Product->tracability_type == 3 ) Selected @endif>With serial number</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ $Factory->curency }}</span>
                                </div>
                                <input type="number" class="form-control" value="{{ $Product->purchased_price }}"  name="purchased_price" id="purchased_price" placeholder="Purchased price" step=".001">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">{{ $Factory->curency }}</span>
                                </div>
                                <input type="number" class="form-control"  value="{{ $Product->selling_price }}" name="selling_price" id="selling_price" placeholder="Selling price" step=".001">
                            </div>
                        </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                        <label for="material">Proprieties</label>
                    </div>
                    <div class="row">
                        <div class="col-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fab fa-mdb"></i></span>
                                </div>
                                <input type="text" class="form-control" value="{{ $Product->material }}" name="material" id="material" placeholder="Material">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-ruler-vertical"></i></span>
                                </div>
                                <input type="number" class="form-control" value="{{ $Product->thickness }}" name="thickness" id="thickness" placeholder="Thickness" step=".001">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-weight-hanging"></i></span>
                                </div>
                                <input type="number" class="form-control" value="{{ $Product->weight }}" name="weight" id="weight" placeholder="Weight" step=".001">
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
                                <input type="number" class="form-control" value="{{ $Product->x_size }}" name="x_size" id="x_size" placeholder="X size" step=".001">
                            </div>
                        </div>
                        <div class="col-4">
                            <label for="y_size">Y</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                </div>
                                <input type="number" class="form-control" value="{{ $Product->y_size }}"  name="y_size" id="y_size" placeholder="Y size" step=".001">
                            </div>
                        </div>
                        <div class="col-4">
                            <label for="z_size">Z</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                                </div>
                                <input type="number" class="form-control" value="{{ $Product->z_size }}" name="z_size" id="z_size" placeholder="Z size" step=".001">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            </div>
                            <input type="number" class="form-control"  value="{{ $Product->x_oversize }}" name="x_oversize" id="x_oversize" placeholder="X oversize" step=".001">
                        </div></div>
                        <div class="col-4">
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->y_oversize }}" name="y_oversize" id="y_oversize" placeholder="Y oversize" step=".001">
                        </div></div>
                        <div class="col-4">
                          <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-ruler-combined"></i></span>
                            </div>
                            <input type="number" class="form-control" value="{{ $Product->z_oversize }}" name="z_oversize" id="z_oversize" placeholder="Z oversize" step=".001">
                        </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4">
                          <input type="number" class="form-control" value="{{ $Product->diameter }}" name="diameter" id="diameter" placeholder="Diameter" step=".001">
                        </div>
                        <div class="col-4">
                          <input type="number" class="form-control" value="{{ $Product->diameter_oversize }}" name="diameter_oversize" id="diameter_oversize" placeholder="Diameter_oversize" step=".001">
                        </div>
                        <div class="col-4">
                          <input type="number" class="form-control" value="{{ $Product->section_size }}" name="section_size" id="section_size" placeholder="Section size" step=".001">
                        </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                        <label for="qty_eco_min">Other information</label>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-4">
                          <input type="number" class="form-control" value="{{ $Product->qty_eco_min }}" name="qty_eco_min" id="qty_eco_min" placeholder="Qty eco min" step=".001">
                        </div>
                        <div class="col-4">
                          <input type="number" class="form-control" value="{{ $Product->qty_eco_max }}" name="qty_eco_max" id="qty_eco_max" placeholder="Qty eco max" step=".001">
                        </div>
                        <div class="col-4"></div>
                    </div>
                    <hr>
                    <div class="card card-body">
                      <div class="row">
                        <x-FormTextareaComment  comment="{{ $Product->comment }}" />
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
              <div class="card card-secondary">
                <div class="card-header">
                  <h3 class="card-title"> Informations </h3>
                </div>
                <div class="card-body">
                  <p class="text-muted">External ID : {{ $Product->code }} </p>
                  <div class="row"> 
                    <div class="col-12 col-sm-4">
                      <div class="text-muted">
                      <p class="text-sm">Unit
                        <b class="d-block">{{ $Product->Unit['label'] }}</b>
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
                        <b class="d-block">{{ $Product->selling_price }} {{ $Factory->curency }}</b>
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
                        <b class="d-block">{{ $Product->purchased_price }} {{ $Factory->curency }}</b>
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
                </div>
              </div>
              <div class="card card-info">
                <div class="card-header">
                  <h3 class="card-title"> Product image </h3>
                </div>
                <div class="card-body">
                  @if($Product->picture)
                      <img src="{{ asset('/images/products/'. $Product->picture) }}" alt="Product Image">
                  @endif
                  <form action="{{ route('products.update.image') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label for="picture">Logo file</label> (peg,png,jpg,gif,svg | max: 10 240 Ko)
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="far fa-image"></i></span>
                        </div>
                        <div class="custom-file">
                            <input type="hidden" name="id" value="{{ $Product->id }}">
                            <input type="file" class="custom-file-input" name="picture" id="picture">
                            <label class="custom-file-label" for="picture">Choose file</label>
                        </div>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-success">Upload</button>
                        </div>
                    </div>
                  </form>
                </div>
              </div>
              <div class="card card-info">
                <div class="card-header">
                  <h3 class="card-title"> Documents </h3>
                </div>
                  <div class="card-body">
                      <form action="{{ route('file.store') }}" method="post" enctype="multipart/form-data">
                          @csrf
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="far fa-file"></i></span>
                            </div>
                            <div class="custom-file">
                              <input type="hidden" name="product_id" value="{{ $Product->id }}" >
                              <input type="file" name="file" class="custom-file-input" id="chooseFile">
                              <label class="custom-file-label" for="chooseFile">Choose file</label>
                            </div>
                            <div class="input-group-append">
                              <button type="submit" name="submit" class="btn btn-success">Upload</button>
                            </div>
                          </div>
                      </form>
                      <h5 class="mt-5 text-muted">Attached files</h5>
                      <ul class="list-unstyled">
                        @forelse ( $Product->files as $file)
                        <li>
                          <a href="{{ asset('/file/'. $file->name) }}" download="{{ $file->original_file_name }}" class="btn-link text-secondary">{{ $file->original_file_name }} -  <small>{{ $file->GetPrettySize() }}</small></a>
                        </li>
                        @empty
                          No file
                        @endforelse
                      </ul>
                </div>
              </div>
              <div class="card card-warning">
                <div class="card-header">
                  <h3 class="card-title"> Options </h3>
                </div>
                <div class="card-body">
                  <a href="{{ route('products.duplicate', ['id' => $Product->id])}}" class="btn btn-default"><i class="fa fa-copy"></i> Duplicate product</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      <div class="tab-pane " id="TechnicalInfo">
        @livewire('task-manage', ['idType' => 'products_id', 'idPage' => $Product->id, 'idLine' => $Product->id, 'statu' => 1]) 
      </div>
      <div class="tab-pane" id="quote">
        @livewire('quotes-lines-index' , ['product_id' => $Product->id ])
      </div>
      <div class="tab-pane" id="order">
        @livewire('orders-lines-index' , ['product_id' => $Product->id ])
      </div>
    </div>
  </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop