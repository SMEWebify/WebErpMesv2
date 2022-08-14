@extends('adminlte::page')

@section('title', 'Companies')

@section('content_header')
    <h1>{{ $adress->label }}</h1>
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
      @include('include.alert-result')
      <div class="row">
        <div class="col-5">
          <label for="ordre">Sort order:</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                </div>
                <input type="text" class="form-control" name="ordre" id="ordre" placeholder="Order" value="{{ $adress->ordre }}">
                <input type="hidden" name="id" value="{{ $adress->id }}">
                <input type="hidden" name="companies_id" value="{{ $adress->companies_id }}">
            </div>
        </div>
        <div class="col-5">
          <label for="label">Label adresse</label>
          <div class="input-group">
            <div class="input-group-prepend">
                <span class="input-group-text"><i class="fas fa-tags"></i></span>
            </div>
            <input type="text" class="form-control" name="label"  id="label" placeholder="Label" value="{{ $adress->label }}">
          </div>
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="adress">Adress</label>
          <input type="text" class="form-control" name="adress"  id="adress" placeholder="Adress" value="{{ $adress->adress }}">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="zipcode">Zip code</label>
          <input type="text" class="form-control" name="zipcode"  id="zipcode" placeholder="Zip code" value="{{ $adress->zipcode }}">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="city">City</label>
          <input type="text" class="form-control" name="city"  id="city" placeholder="City" value="{{ $adress->city }}">
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="country">Country</label>
          <input type="text" class="form-control" name="country"  id="country" placeholder="Country" value="{{ $adress->country }}">
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="number">Phone number</label>
          <input type="text" class="form-control" name="number"  id="number" placeholder="Phone number" value="{{ $adress->number }}">
        </div>
        <div class="col-5">
          <label for="mail">E-mail</label>
          <input type="email" class="form-control" name="mail"  id="mail" placeholder="E-mail" value="{{ $adress->mail }}">
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
@stop

@section('js')
@stop