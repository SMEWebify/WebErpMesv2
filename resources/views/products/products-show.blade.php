@extends('adminlte::page')

@section('title', 'Products')

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
    @if($errors->count())
      <div class="alert alert-danger">
        <ul>
        @foreach ( $errors->all() as $message)
         <li> {{ $message }}</li>
        @endforeach
        </ul>
      </div>
    @endif
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

  @include('include.Main-procces', ['route' => route('task.store', ['id' => $Product->id]), 'id_page' => $Product->id,'id_type' => 'products_id', 'id_line' => $Product->id, 'task' => $Product->Task])])
<!-- /.card -->

            @stop
                  
            @section('css')
            @stop

           @section('js')
             <script > 
             
             $('#methods_services_id').on('change',function(){
                var val = $(this).val();
                var txt = $(this).find('option:selected').data('txt');
                var type = $(this).find('option:selected').data('type');
                $('#LABEL').val( txt );
                $('#TYPE').val( type );
            });

            $('#methods_services_id_BOM').on('change',function(){
                var val = $(this).val();
                var txt = $(this).find('option:selected').data('txt');
                var type = $(this).find('option:selected').data('type');
                $('#LABEL_BOM').val( txt );
                $('#TYPE_BOM').val( type );
            });

            $('.methods_services_id').change(function () {
                var modelObj = $(this).parent().next().children(".component_id");
                var selector = "option[class="+this.value.toLowerCase()+"]";
                modelObj.children(":not("+selector+")").hide();
                modelObj.children(selector).show();
            });
            </script>

           @stop