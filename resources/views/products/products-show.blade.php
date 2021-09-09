@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1> {{ $Product->LABEL }} {{ $Product->IND }}</h1>
@stop

@section('content')
<div class="card card-primary">

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
    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success')}}
    </div>
    @endif
    <div class="row">
      <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Service </span>
                <span class="info-box-number text-center  mb-0">{{ $Product->service['LABEL'] }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Familly</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->family['LABEL'] }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Tracability</span>
                <span class="info-box-number text-center  mb-0">
                  @if($Product->tracability_type  == 1)No traceability @endif
                  @if($Product->tracability_type  == 2)With batch number @endif
                  @if($Product->tracability_type  == 3)With serial number @endif
                </span>
              </div>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Material</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->material }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Thickness</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->thickness }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Weight</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->weight }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">X size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->x_size }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Y size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->y_size }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Z size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->z_size }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">X oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->x_oversize }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Y oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->y_oversize }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Z oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->z_oversize }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row --> 
        </div>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Diameter</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->diameter }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Diameter oversize</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->diameter_oversize }}</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Section size</span>
                <span class="info-box-number text-center  mb-0">{{ $Product->section_size }}</span>
              </div>
            </div>
          </div>
        <!-- /.div row --> 
        </div>
      <!-- /.div col-12 col-md-12 -->
      </div>
     
      <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
        <p class="text-muted">External ID : {{ $Product->CODE }} </p>

        <div class="row"> 
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Unit
              <b class="d-block">{{ $Product->Unit['LABEL'] }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>

        @if($Product->sold == 1 )
        <hr>
        <div class="row"> 
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Selling price
              <b class="d-block">{{ $Product->selling_price }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif

        @if($Product->purchased == 1 )
        <hr>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Purchased price
              <b class="d-block">{{ $Product->purchased_price }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif
        @if($Product->qty_eco_min)
        <hr>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Quantité eco min
              <b class="d-block">{{ $Product->qty_eco_min }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif

        @if($Product->qty_eco_max)
        <hr>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">Quantité eco max
              <b class="d-block">{{ $Product->qty_eco_max }}</b>
            </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif

         <!-- /.div mt-4 product-share -->
        </div>
      <!-- /.div col-12 col-md-12 -->
      </div>
    <!-- /.div row -->
    </div>
  <!-- /.card-body -->
  </div>
    

<div class="card card-secondary">
  <div class="card-header">
    <h3 class="card-title">Technical cut</h3>
    <div class="card-tools">
      <button onclick="window.location='{{ route('addresses.create', ['id' => $Product->id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Add address">
        <i class="fa fa-lg fa-fw fa-address-card"></i>Add item to Technical cut
      </button>
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

    </div>
  </div>
  <!-- /.card-body -->
</div>

<div class="card card-secondary">
  <div class="card-header">
    <h3 class="card-title">Bill of materials</h3>

    <div class="card-tools">
      <button onclick="window.location='{{ route('contacts.create', ['id' => $Product->id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Add contact">
        <i class="fa fa-lg fa-fw fa-address-book"></i>Add item to BOM
      </button>
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