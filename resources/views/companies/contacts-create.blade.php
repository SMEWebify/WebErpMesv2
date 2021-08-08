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
    <h3 class="card-title">New contact</h3>
  </div>
  <!-- /.card-header -->
  <form method="POST" action="{{ route('contacts.create', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
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
          <label for="CIVILITY">Civility</label>
          <select class="form-control" name="CIVILITY">
            <option value="Miss">Miss</option>
            <option value="Ms">Ms</option>
            <option value="Mr">Mr</option>
            <option value="Mrs">Mrs</option>
          </select>
        </div>
      </div>
      <div class="row">
        <div class="col-5">
          <label for="FIRST_NAME">First Name</label>
          <input type="text" class="form-control" name="FIRST_NAME"  id="FIRST_NAME" placeholder="First Name">
        </div>
        <div class="col-5">
          <label for="NAME">Name</label>
          <input type="text" class="form-control" name="NAME"  id="NAME" placeholder="Name">
        </div>
      </div>
      <hr>
      <div class="form-group">
        <label for="FUNCTION">Function</label>
        <input type="text" class="form-control" name="FUNCTION"  id="FUNCTION" placeholder="Function">
      </div>
      <hr>
      <div class="row">
        <div class="col-5">
          <label for="NUMBER">Phone number</label>
          <input type="text" class="form-control" name="NUMBER"  id="NUMBER" placeholder="Phone number">
        </div>
        <div class="col-5">
          <label for="MOBILE">Mobile phone number</label>
          <input type="text" class="form-control" name="MOBILE"  id="MOBILE" placeholder="Mobile phone number">
        </div>
      </div>
      <div class="form-group">
        <label for="MAIL">E-mail</label>
        <input type="email" class="form-control" name="MAIL"  id="MAIL" placeholder="E-mail">
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