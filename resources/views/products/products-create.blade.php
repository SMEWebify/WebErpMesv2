@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Create new product</h1>
@stop

@section('content')
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">New product</h3>
  </div>
  <!-- /.card-header -->
  <!-- form start -->
  <form method="POST" action="{{ route('products.create')}}" enctype="multipart/form-data">
    @csrf
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
      <div class="row">
        <div class="col-3">
          <label for="CODE">External ID</label>
          <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
        </div>
        <div class="col-3">
          <label for="LABEL">Description</label>
          <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label/Desciption of product">
        </div>
        <div class="col-3">
          <label for="IND">Index</label>
          <input type="text" class="form-control" name="IND"  id="IND" placeholder="Index">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-3">
          <label for="methods_services_id">Services</label>
          <select class="form-control" name="methods_services_id" id="methods_services_id">
            @foreach ($ServicesSelect as $item)
            <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-3">
          <label for="methods_families_id">Family</label>
          <select class="form-control" name="methods_families_id" id="methods_families_id">
            @foreach ($FamiliesSelect as $item)
            <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-3">
          <label for="methods_units_id">Unit</label>
          <select class="form-control" name="methods_units_id" id="methods_units_id">
            @foreach ($UnitsSelect as $item)
            <option value="{{ $item->id }}">{{ $item->LABEL }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-3">
          <label for="purchased">Purchased</label>
          <select class="form-control" name="purchased" id="purchased">
            <option value="2">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div class="col-3">
          <label for="sold">Sold</label>
          <select class="form-control" name="sold" id="sold">
            <option value="2">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div class="col-3">
          <label for="tracability_type">Tracability</label>
          <select class="form-control" name="tracability_type" id="tracability_type">
            <option value="1">No traceability</option>
            <option value="2">With batch number</option>
            <option value="3">With serial number</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="number" class="form-control" name="purchased_price" id="purchased_price" placeholder="Purchased price" step=".001">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="selling_price" id="selling_price" placeholder="Selling price" step=".001">
        </div>
      </div>
      <hr>
      <div class="row">
        <label for="material">Proprieties</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="text" class="form-control" name="material" id="material" placeholder="Material">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="thickness" id="thickness" placeholder="Thickness" step=".001">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="weight" id="weight" placeholder="Weight" step=".001">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-3">
          <label for="x_size">X</label>
          <input type="number" class="form-control" name="x_size" id="x_size" placeholder="X size" step=".001">
        </div>
        <div class="col-3">
          <label for="y_size">Y</label>
          <input type="number" class="form-control" name="y_size" id="y_size" placeholder="Y size" step=".001">
        </div>
        <div class="col-3">
          <label for="z_size">Z</label>
          <input type="number" class="form-control" name="z_size" id="z_size" placeholder="Z size" step=".001">
        </div>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="number" class="form-control" name="x_oversize" id="x_oversize" placeholder="X oversize" step=".001">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="y_oversize" id="y_oversize" placeholder="Y oversize" step=".001">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="z_oversize" id="z_oversize" placeholder="Z oversize" step=".001">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-3">
          <input type="number" class="form-control" name="diameter" id="diameter" placeholder="Diameter" step=".001">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="diameter_oversize" id="diameter_oversize" placeholder="Diameter_oversize" step=".001">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="section_size" id="section_size" placeholder="Section size" step=".001">
        </div>
      </div>
      <hr>
      <div class="row">
        <label for="qty_eco_min">Other information</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="number" class="form-control" name="qty_eco_min" id="qty_eco_min" placeholder="Qty eco min" step=".001">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="qty_eco_max" id="qty_eco_max" placeholder="Qty eco max" step=".001">
        </div>
      </div>
      <hr>
      <div class="row">
        <label for="PICTURE">Logo file</label>
        <div class="input-group">
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
      <div class="row">
        <div class="form-group">
          <label>Comment</label>
          <textarea class="form-control" rows="3" name="comment"  placeholder="Enter ..."></textarea>
        </div>
      </div>
    </div>
    <hr>
    <!-- /.card-body -->

    <div class="card-footer">
      <button type="submit" class="btn btn-primary">Submit</button>
    </div>
  </form>
</div>
@stop
                  
 @section('css')
   <link rel="stylesheet" href="/css/admin_custom.css">
 @stop
                  
@section('js')
  <script> console.log('Hi!'); </script>
@stop