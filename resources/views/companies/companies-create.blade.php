@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Create new companie</h1>
@stop

@section('content')
<div class="card card-primary">
  <div class="card-header">
    <h3 class="card-title">New companie</h3>
  </div>
  <!-- /.card-header -->
  <!-- form start -->
  <form method="POST" action="{{ route('companies.create')}}" enctype="multipart/form-data">
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
      <div class="form-group">
        <label for="CODE">External ID</label>
        <input type="text" class="form-control" name="CODE" id="CODE" placeholder="External ID">
      </div>
      <div class="form-group">
        <label for="LABEL">Name of company</label>
        <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Name of company">
      </div>
      <hr>
      <div class="row">
        <label for="InputWebSite">Site link</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="text" class="form-control"  name="WEBSITE" id="WEBSITE" placeholder="Web site link">
        </div>
        <div class="col-3">
          <input type="text" class="form-control"  name="FBSITE" id="FBSITE" placeholder="Facebook link">
        </div>
        <div class="col-3">
          <input type="text" class="form-control"  name="TWITTERSITE" id="TWITTERSITE" placeholder="Twitter link">
        </div>
        <div class="col-3">
          <input type="text" class="form-control"  name="LKDSITE" id="LKDSITE" placeholder="Linkedin link">
        </div>
      </div>
      <div class="row">
        <label for="SIREN">Administrative information</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="text" class="form-control" name="SIREN" id="SIREN" placeholder="Siren">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" name="APE" id="APE" placeholder="APE code">
        </div>
        <div class="col-3">
          <input type="text" class="form-control" name="TVA_INTRA" id="TVA_INTRA" placeholder="VAT number">
        </div>
      </div>
      
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
        <div class="col-5">
          <label for="user_id">Technical manager</label>
          <select class="form-control" name="user_id" id="user_id">
            @foreach ($userSelect as $item)
            <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-3">
          <label for="STATU_CLIENT">Statu client</label>
          <select class="form-control" name="STATU_CLIENT" id="STATU_CLIENT">
            <option value="1">Inactive</option>
            <option value="2">Active</option>
            <option value="3">Prospect</option>
          </select>
        </div>
      </div>
      <div class="row">
        <label for="DISCOUNT">Sales and accounting</label>
      </div>
      <div class="row">
        <div class="col-3">
          <input type="number" class="form-control" name="DISCOUNT" id="DISCOUNT" placeholder="Discount">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="COMPTE_GEN_CLIENT" id="COMPTE_GEN_CLIENT" placeholder="General Account">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" name="COMPTE_AUX_CLIENT" id="COMPTE_AUX_CLIENT" placeholder="Auxiliary account">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-3">
          <label for="STATU_FOUR">Statu supplier</label>
          <select class="form-control" name="STATU_FOUR" id="STATU_FOUR">
            <option value="1">Inactive</option>
            <option value="2">Active</option>
          </select>
        </div>
        <div class="col-3">
          <label for="STATU_FOUR">Reception control</label>
          <select class="form-control" name="RECEPT_CONTROLE" id="RECEPT_CONTROLE">
            <option value="1">Yes</option>
            <option value="2">No</option>
          </select>
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-3">
          <input type="number" class="form-control" id="COMPTE_GEN_FOUR" name="COMPTE_GEN_FOUR" placeholder="General Account">
        </div>
        <div class="col-3">
          <input type="number" class="form-control" id="COMPTE_AUX_FOUR" name="COMPTE_AUX_FOUR"  placeholder="Auxiliary account">
        </div>
        
      </div>
      <hr>
      <div class="row">
        <div class="form-group">
          <label>Comment</label>
          <textarea class="form-control" rows="3" name="COMMENT"  placeholder="Enter ..."></textarea>
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
    
 @stop
                  
@section('js')
  <script> console.log('Hi!'); </script>
@stop