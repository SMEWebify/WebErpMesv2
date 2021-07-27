@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Companies new</h1>
@stop

@section('content')
<div class="card card-primary">
  <!-- /.card-header -->
  <!-- form start -->
  <form>
    <div class="card-body">
      <div class="form-group">
        <label for="InputCode">Code</label>
        <input type="text" class="form-control" id="InputCode" placeholder="Code">
      </div>
      <div class="form-group">
        <label for="InputLabel">Label</label>
        <input type="text" class="form-control" id="InputLabel" placeholder="Label">
      </div>
      <hr>
      <div class="row">
        <label for="InputWebSite">Site link</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="text" class="form-control" id="InputWebSite" placeholder="Web site link">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" id="InputFacebook" placeholder="Facebook link">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" id="InputTwitter" placeholder="Twitter link">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" id="InputLinkedin" placeholder="Linkedin link">
        </div>
      </div>
      <div class="row">
        <label for="InputWebSite">Administrative information</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="text" class="form-control" id="InputSiren" placeholder="Siren">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" id="InputAPE" placeholder="APE code">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" id="InputVATnumber" placeholder="VAT number">
        </div>
        <div class="col-3">
            <select class="form-control">
              <option>option 1</option>
              <option>option 2</option>
              <option>option 3</option>
              <option>option 4</option>
              <option>option 5</option>
            </select>
        </div>
      </div>
      
      <div class="row">
        <label for="exampleInputFile">File input</label>
        <div class="input-group">
          <div class="custom-file">
            <input type="file" class="custom-file-input" id="exampleInputFile">
            <label class="custom-file-label" for="exampleInputFile">Choose file</label>
          </div>
          <div class="input-group-append">
            <span class="input-group-text">Upload</span>
          </div>
        </div>
      </div>
      <hr>
        <div class="row">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input" id="customSwitch1">
            <label class="custom-control-label" for="customSwitch1">As client</label>
          </div>
        </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="exampleInputFile">Provided settlement</label>
          <select class="form-control">
            <option>option 1</option>
            <option>option 2</option>
            <option>option 3</option>
            <option>option 4</option>
            <option>option 5</option>
          </select>
        </div>
        <div class="col-5">
          <label for="exampleInputFile">Payment choice</label>
          <select class="form-control">
            <option>option 1</option>
            <option>option 2</option>
            <option>option 3</option>
            <option>option 4</option>
            <option>option 5</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="exampleInputFile">Technical Manager</label>
          <select class="form-control">
            <option>option 1</option>
            <option>option 2</option>
            <option>option 3</option>
            <option>option 4</option>
            <option>option 5</option>
          </select>
        </div>
        <div class="col-5">
          <label for="exampleInputFile">Sales manager</label>
          <select class="form-control">
            <option>option 1</option>
            <option>option 2</option>
            <option>option 3</option>
            <option>option 4</option>
            <option>option 5</option>
          </select>
        </div>
      </div>
      <div class="row">
        <label for="InputWebSite">Sales and accounting</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="text" class="form-control" id="InputDiscount" placeholder="Discount">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" id="InputGeneral" placeholder="General Account">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" id="InputAuxiliary" placeholder="Auxiliary account">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="custom-control custom-switch">
          <input type="checkbox" class="custom-control-input" id="customSwitch2">
          <label class="custom-control-label" for="customSwitch2">As Supplier</label>
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