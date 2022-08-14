@extends('adminlte::page')

@section('title', 'Contact')

@section('content_header')
    <h1>{{ $contact->first_name }} {{ $contact->name }}</h1>
@stop

@section('content')
<div class="card card-primary">
  <!-- /.card-header -->
  <!-- form start -->
  <div class="card-header">
    <h3 class="card-title">Edit contact</h3>
  </div>
  <!-- /.card-header -->
  <form method="POST" action="{{ route('contacts.edit', ['id' => $contact->id]) }}" >
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
            <input type="text" class="form-control" name="ordre" id="ordre" placeholder="Order" value="{{ $contact->ordre }}">
            <input type="hidden" name="companies_id" value="{{ $contact->companies_id }}">
            <input type="hidden" name="id" value="{{ $contact->id }}">
          </div>
        </div>
        <div class="col-5">
          <label for="civility">Civility</label>
          <select class="form-control" name="civility">
            <option value="Miss" @if( $contact->civility =="Miss") Selected @endIf >Miss</option>
            <option value="Ms" @if( $contact->civility =="Ms") Selected @endIf >Ms</option>
            <option value="Mr" @if( $contact->civility =="Mr") Selected @endIf >Mr</option>
            <option value="Mrs" @if( $contact->civility =="Mrs") Selected @endIf >Mrs</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="first_name">First Name</label>
          <input type="text" class="form-control" name="first_name"  id="first_name" placeholder="First Name" value="{{ $contact->first_name }}">
        </div>
        <div class="col-5">
          <label for="name">Name</label>
          <input type="text" class="form-control" name="name"  id="name" placeholder="Name" value="{{ $contact->name }}">
        </div>
      </div>
      <hr>
      <div class="form-group">
        <label for="function">Function</label>
        <input type="text" class="form-control" name="function"  id="function" placeholder="Function" value="{{ $contact->function }}">
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="number">Phone number</label>
          <input type="text" class="form-control" name="number"  id="number" placeholder="Phone number"  value="{{ $contact->number }}">
        </div>
        <div class="col-5">
          <label for="mobile">Mobile phone number</label>
          <input type="text" class="form-control" name="mobile"  id="mobile" placeholder="Mobile phone number"  value="{{ $contact->mobile }}">
        </div>
      </div>
      <div class="form-group">
        <label for="mail">E-mail</label>
        <input type="email" class="form-control" name="mail"  id="mail" placeholder="E-mail"  value="{{ $contact->mail }}">
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