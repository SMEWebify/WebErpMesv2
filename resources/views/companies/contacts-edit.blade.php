@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>{{ $contact->FIRST_NAME }} {{ $contact->NAME }}</h1>
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
          <input type="text" class="form-control" name="ORDRE" id="ORDRE" placeholder="Order" value="{{ $contact->ORDRE }}">
          <input type="hidden" name="companies_id" value="{{ $contact->companies_id }}">
          <input type="hidden" name="id" value="{{ $contact->id }}">
        </div>
        <div class="col-5">
          <label for="CIVILITY">Civility</label>
          <select class="form-control" name="CIVILITY">
            <option value="Miss" @if( $contact->CIVILITY =="Miss") Selected @endIf >Miss</option>
            <option value="Ms" @if( $contact->CIVILITY =="Ms") Selected @endIf >Ms</option>
            <option value="Mr" @if( $contact->CIVILITY =="Mr") Selected @endIf >Mr</option>
            <option value="Mrs" @if( $contact->CIVILITY =="Mrs") Selected @endIf >Mrs</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="FIRST_NAME">First Name</label>
          <input type="text" class="form-control" name="FIRST_NAME"  id="FIRST_NAME" placeholder="First Name" value="{{ $contact->FIRST_NAME }}">
        </div>
        <div class="col-5">
          <label for="NAME">Name</label>
          <input type="text" class="form-control" name="NAME"  id="NAME" placeholder="Name" value="{{ $contact->NAME }}">
        </div>
      </div>
      <hr>
      <div class="form-group">
        <label for="FUNCTION">Function</label>
        <input type="text" class="form-control" name="FUNCTION"  id="FUNCTION" placeholder="Function" value="{{ $contact->FUNCTION }}">
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="NUMBER">Phone number</label>
          <input type="text" class="form-control" name="NUMBER"  id="NUMBER" placeholder="Phone number"  value="{{ $contact->NUMBER }}">
        </div>
        <div class="col-5">
          <label for="MOBILE">Mobile phone number</label>
          <input type="text" class="form-control" name="MOBILE"  id="MOBILE" placeholder="Mobile phone number"  value="{{ $contact->MOBILE }}">
        </div>
      </div>
      <div class="form-group">
        <label for="MAIL">E-mail</label>
        <input type="email" class="form-control" name="MAIL"  id="MAIL" placeholder="E-mail"  value="{{ $contact->MAIL }}">
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
  <script> console.log('Hi!'); </script>
@stop