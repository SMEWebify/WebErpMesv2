@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>{{ $adress->LABEL }}</h1>
@stop

@section('content')
<div class="card card-primary">
  <!-- /.card-header -->
  <!-- form start -->
  <div class="card-header">
    <h3 class="card-title">Edit adress</h3>
  </div>
  <!-- /.card-header -->
  <form method="POST" action="{{ route('addresses.edit', ['id' => $adress->id]) }}" >
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
          <input type="text" class="form-control" name="ORDRE" id="ORDRE" placeholder="Order" value="{{ $adress->ORDRE }}">
          <input type="hidden" name="id" value="{{ $adress->id }}">
          <input type="hidden" name="companies_id" value="{{ $adress->companies_id }}">
        </div>
        <div class="col-5">
          <label for="LABEL">Label adresse</label>
          <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label" value="{{ $adress->LABEL }}">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="ADRESS">Adress</label>
          <input type="text" class="form-control" name="ADRESS"  id="ADRESS" placeholder="Adress" value="{{ $adress->ADRESS }}">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="ZIPCODE">Zip code</label>
          <input type="text" class="form-control" name="ZIPCODE"  id="ZIPCODE" placeholder="Zip code" value="{{ $adress->ZIPCODE }}">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="CITY">City</label>
          <input type="text" class="form-control" name="CITY"  id="CITY" placeholder="City" value="{{ $adress->CITY }}">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="COUNTRY">Country</label>
          <input type="text" class="form-control" name="COUNTRY"  id="COUNTRY" placeholder="Country" value="{{ $adress->COUNTRY }}">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="NUMBER">Phone number</label>
          <input type="text" class="form-control" name="NUMBER"  id="NUMBER" placeholder="Phone number" value="{{ $adress->NUMBER }}">
        </div>
        <div class="col-5">
         <label for="MAIL">E-mail</label>
          <input type="email" class="form-control" name="MAIL"  id="MAIL" placeholder="E-mail" value="{{ $adress->MAIL }}">
       </div>
      </div>
    </div>
    <!-- /.card-body -->

    <div class="card-footer">
      <button type="reset" class="btn btn-danger pull-left">Reset</button>
      <button type="submit" class="btn btn-primary">Update</button>
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