@extends('adminlte::page')

@section('title', '404 Error Page')

@section('content_header')
    <h1>404 Error Page</h1>
@stop

@section('content')<div class="error-page">
    <h2 class="headline text-warning"> 404</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Page not found.</h3>
            <p>
            We could not find the page you were looking for.
            Meanwhile, you may <a href="{{ route('dashboard') }}">return to dashboard</a> or try using the search form.
        </p>
    </div>
@stop

@section('css')
@stop

@section('js')
@stop