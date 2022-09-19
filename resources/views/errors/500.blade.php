@extends('adminlte::page')

@section('title', '500 Error Page')

@section('content_header')
    <h1>500 Error Page</h1>
@stop

@section('content')<div class="error-page">
    <h2 class="headline text-danger"> 500</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-danger"></i> Oops! Internal Server Error.</h3>
        <p>
            We will work on fixing that right away. Meanwhile, you may return to <a href="{{ route('dashboard') }}">return to dashboard</a> or try using the search form.
        </p>
    </div>
@stop

@section('css')
@stop

@section('js')
@stop