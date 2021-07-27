@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1> {{ $Companie->LABEL }}</h1>
@stop

@section('content')
<div class="card">
  <div class="card-header">
    <h3 class="card-title">General information</h3>

    <div class="card-tools">
      <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
        <i class="fas fa-minus"></i>
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center text-muted">Total turnove </span>
                <span class="info-box-number text-center text-muted mb-0">2300</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center text-muted">Total estimated</span>
                <span class="info-box-number text-center text-muted mb-0">2000</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center text-muted">Estimated project duration</span>
                <span class="info-box-number text-center text-muted mb-0">20</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
        <h3 class="text-primary">{{ $Companie->LABEL }}</h3>
        <p class="text-muted">{{ $Companie->code }}</p>
        <br>
        <div class="text-muted">
          <p class="text-sm">Facebook
            <b class="d-block">{{ $Companie->FBSITE }}</b>
          </p>
          <p class="text-sm">Twitter
            <b class="d-block">{{ $Companie->TWITTERSITE }}n</b>
          </p>
          <p class="text-sm">Linkedin
            <b class="d-block">{{ $Companie->LKDSITE }}n</b>
          </p>
        </div>

        <h5 class="mt-5 text-muted">Project files</h5>
        <ul class="list-unstyled">
          <li>
            <a href="" class="btn-link text-secondary"><i class="far fa-fw fa-file-word"></i> Functional-requirements.docx</a>
          </li>
        </ul>
        <div class="text-center mt-5 mb-3">
          <a href="#" class="btn btn-sm btn-primary">Add files</a>
          <a href="#" class="btn btn-sm btn-warning">Report contact</a>
        </div>
      </div>
    </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
            @stop
                  
            @section('css')
              <link rel="stylesheet" href="/css/admin_custom.css">
            @stop
                             
           @section('js')
             <script> console.log('Hi!'); </script>
           @stop