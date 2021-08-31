@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1> {{ $Companie->LABEL }}</h1>
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
                <span class="info-box-text text-center ">Total turnove </span>
                <span class="info-box-number text-center  mb-0">2300</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Total estimated</span>
                <span class="info-box-number text-center  mb-0">2000</span>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="info-box bg-light">
              <div class="info-box-content">
                <span class="info-box-text text-center ">Estimated project duration</span>
                <span class="info-box-number text-center  mb-0">20</span>
              </div>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        <hr>
        <div class="row">
          @if($Companie->SIREN )
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">APE code
              <b class="d-block">{{ $Companie->SIREN }}</b>
            </p>
            </div>
          </div>
          @endif

          @if($Companie->APE )
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">APE code
              <b class="d-block">{{ $Companie->APE }}</b>
            </p>
            </div>
          </div>
          @endif

          @if($Companie->TVA_INTRA )
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">VAT number
              <b class="d-block">{{ $Companie->TVA_INTRA }}</b>
            </p>
            </div>
          </div>
          @endif
        <!-- /.div row -->
        </div>
        @if($Companie->STATU_CLIENT == 2 )
        <hr>
        <div class="row">
          <h3 class="card-title">Customer informations</h3>
        <!-- /.div row -->
        </div>
        <hr>
        <div class="row"> 
          <div class="col-12 col-sm-4">
            <div class="text-muted">
             <p class="text-sm">Discount default
               <b class="d-block">{{ $Companie->DISCOUNT }} %</b>
             </p>
            </div>
          </div>
      
          <div class="col-12 col-sm-4">
            <div class="text-muted">
             <p class="text-sm">General Account
               <b class="d-block">{{ $Companie->COMPTE_GEN_CLIENT }}</b>
             </p>
            </div>
          </div>
  
          <div class="col-12 col-sm-4">
            <div class="text-muted">
             <p class="text-sm">Auxiliary account
               <b class="d-block">{{ $Companie->COMPTE_AUX_CLIENT }}</b>
             </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        
        @endif

        @if($Companie->STATU_FOUR == 2 )
        <hr>
        <div class="row">
          <h3 class="card-title">Supplier informations</h3>
        <!-- /.div row -->
        </div>
        <hr>
        <div class="row">
          <div class="col-12 col-sm-4">
            <div class="text-muted">
             <p class="text-sm">General Account
               <b class="d-block">{{ $Companie->COMPTE_GEN_FOUR }}</b>
             </p>
            </div>
          </div>

          <div class="col-12 col-sm-4">
            <div class="text-muted">
             <p class="text-sm">Auxiliary account
               <b class="d-block">{{ $Companie->COMPTE_AUX_FOUR }}</b>
             </p>
            </div>
          </div>

          <div class="col-12 col-sm-4">
            <div class="text-muted">
             <p class="text-sm">Reception control
               <b class="d-block">{{ $Companie->RECEPT_CONTROLE }} %</b>
             </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif

      <!-- /.div col-12 col-md-12 -->
     </div>

     
      <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
        <p class="text-muted">External ID : {{ $Companie->CODE }} </p>
        <hr>
        <div class="mt-4 product-share">

          @if($Companie->WEBSITE )
          <a href="{{ $Companie->WEBSITE }}" class="text-gray">
            <i class="fab fa-internet-explorer fa-2x"></i>
          </a>
          @endif

          @if($Companie->FBSITE )
          <a href="{{ $Companie->FBSITE }}" class="text-gray">
            <i class="fab fa-facebook-square fa-2x"></i>
          </a>
          @endif
 
          @if($Companie->TWITTERSITE )
          <a href="{{ $Companie->TWITTERSITE }}" class="text-gray">
            <i class="fab fa-twitter-square fa-2x"></i>
          </a>
          @endif
 
          @if($Companie->LKDSITE )
          <a href="{{ $Companie->LKDSITE }}" class="text-gray">
            <i class="fab fa-linkedin fa-2x"></i>
          </a>
          @endif
         <!-- /.div row -->
        </div>
        <hr>
         <div class="col-12 col-sm-4">
          <div class="text-muted">
           <p class="text-sm">customer manager
             <b class="d-block">{{ $Companie->UserManagement['name'] }} </b>
           </p>
          </div>
         </div>
      <!-- /.div col-12 col-md-12 -->
      </div>
      <!-- /.div row -->
    </div>
    <!-- /.card-body -->
    
  </div>
</div>

<div class="card card-secondary">
  <div class="card-header">
    <h3 class="card-title">Addresses</h3>
    <div class="card-tools">
      <button onclick="window.location='{{ route('addresses.create', ['id' => $Companie->id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Add address">
        <i class="fa fa-lg fa-fw fa-address-card"></i>Add adress
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
      @forelse($Companie->Addresses as $Address)
        <x-AddressComponent :id="$Address->id" :label="$Address->LABEL" :adress="$Address->ADRESS" :zipcode="$Address->ZIPCODE" :city="$Address->CITY" :county="$Address->COUNTRY"  />
      @empty
        No address
      @endforelse
    </div>
  </div>
  <!-- /.card-body -->
</div>

<div class="card card-secondary">
  <div class="card-header">
    <h3 class="card-title">Contacts</h3>

    <div class="card-tools">
      <button onclick="window.location='{{ route('contacts.create', ['id' => $Companie->id])}}'" class="btn btn-xs btn-default text-primary mx-1 shadow" title="Add contact">
        <i class="fa fa-lg fa-fw fa-address-book"></i>Add contact
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
      @forelse($Companie->Contacts as $Contact)
          <x-ContactComponent :id="$Contact->id" :function="$Contact->FUNCTION" :name="$Contact->NAME" :firstname="$Contact->FIRST_NAME" :mail="$Contact->MAIL" :number="$Contact->NUMBER"  :mobile="$Contact->MOBILE" />
      @empty
        No Contact
      @endforelse   
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