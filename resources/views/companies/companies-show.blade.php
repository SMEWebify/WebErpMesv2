@extends('adminlte::page')

@section('title', 'Companies')

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
            <p class="text-sm">Siren
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
        @if($Companie->statu_CLIENT == 2 )
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
        @if($Companie->statu_FOUR == 2 )
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
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalAdress">
        Add address
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
        <i class="fas fa-minus"></i>
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <!-- Modal -->
  <div class="modal fade" id="ModalAdress" tabindex="-1" role="dialog" aria-labelledby="ModalAdressTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ModalContactTitle">Add address</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="POST" action="{{ route('addresses.store', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
              <div class="col-5">
                <label for="ORDRE">Sort order:</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                    </div>
                  <input type="number" class="form-control" name="ORDRE" id="ORDRE" placeholder="Order">
                </div>
                <input type="hidden" name="companies_id" value="{{ $Companie->id }}">
              </div>
              <div class="col-5">
                <label for="LABEL">Label adresse</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control" name="LABEL"  id="LABEL" placeholder="Label">
                </div>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-5">
                <label for="ADRESS">Adress</label>
                <input type="text" class="form-control" name="ADRESS"  id="ADRESS" placeholder="Adress">
              </div>
            </div>
            <div class="row">
              <div class="col-5">
                <label for="ZIPCODE">Zip code</label>
                <input type="text" class="form-control" name="ZIPCODE"  id="ZIPCODE" placeholder="Zip code">
              </div>
              <div class="col-5">
                <label for="CITY">City</label>
                <input type="text" class="form-control" name="CITY"  id="CITY" placeholder="City">
              </div>
            </div>
            <div class="row">
              <div class="col-5">
                <label for="COUNTRY">Country</label>
                <input type="text" class="form-control" name="COUNTRY"  id="COUNTRY" placeholder="Country">
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-5">
                <label for="NUMBER">Phone number</label>
                <input type="text" class="form-control" name="NUMBER"  id="NUMBER" placeholder="Phone number">
              </div>
              <div class="col-5">
                <label for="MAIL">E-mail</label>
                <input type="email" class="form-control" name="MAIL"  id="MAIL" placeholder="E-mail">
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- End Modal -->
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
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#ModalContact">
        Add contact
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
        <i class="fas fa-minus"></i>
      </button>
      <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
  <!-- Modal -->
  <div class="modal fade" id="ModalContact" tabindex="-1" role="dialog" aria-labelledby="ModalContactTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ModalContactTitle">Add address</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="POST" action="{{ route('contacts.store', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
              <div class="col-5">
                <label for="ORDRE">Sort order:</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                  </div>
                  <input type="number" class="form-control" name="ORDRE" id="ORDRE" placeholder="Order">
                  <input type="hidden" name="companies_id" value="{{ $Companie->id }}">
                </div>
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
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Submit</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- End Modal -->
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
@stop

@section('js')
@stop