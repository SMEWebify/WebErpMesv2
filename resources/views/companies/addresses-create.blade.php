@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>{{ $Companie->LABEL }}</h1>
@stop

@section('content')
<div class="card card-primary">
  <!-- /.card-header -->
  <!-- form start -->
  <div class="card-header">
    <h3 class="card-title">New adress</h3>
  </div>
  <!-- /.card-header -->
  <form method="POST" action="{{ route('addresses.create', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
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
        <div class="col-5">
          <label for="ORDRE">Sort order</label>
          <input type="text" class="form-control" name="ORDRE" id="ORDRE" placeholder="Order">
          <input type="hidden" name="companies_id" value="{{ $Companie->id }}">
        </div>
        <div class="col-5">
          <label for="LABEL">Label adresse</label>
          <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="ADRESS">Adress</label>
          <input type="text" class="form-control" name="ADRESS"  id="ADRESS" placeholder="Adress">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="ZIPCODE">Zip code</label>
          <input type="text" class="form-control" name="ZIPCODE"  id="ZIPCODE" placeholder="Zip code">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="CITY">City</label>
          <input type="text" class="form-control" name="CITY"  id="CITY" placeholder="City">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="COUNTRY">Country</label>
          <input type="text" class="form-control" name="COUNTRY"  id="COUNTRY" placeholder="Country">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="NUMBER">Phone number</label>
          <input type="text" class="form-control" name="NUMBER"  id="NUMBER" placeholder="Phone number">
        </div>
        <div class="col-5">
         <label for="MAIL">E-mail</label>
          <input type="email" class="form-control" name="MAIL"  id="MAIL" placeholder="E-mail">
       </div>
      </div>
    </div>
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