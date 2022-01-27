@extends('adminlte::page')

@section('title', 'Companies')

@section('content_header')
    <h1> {{ $Companie->label }}</h1>
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
          @if($Companie->naf_code )
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">naf_code code
              <b class="d-block">{{ $Companie->naf_code }}</b>
            </p>
            </div>
          </div>
          @endif
          @if($Companie->intra_community_vat )
          <div class="col-12 col-sm-4">
            <div class="text-muted">
            <p class="text-sm">VAT number
              <b class="d-block">{{ $Companie->intra_community_vat }}</b>
            </p>
            </div>
          </div>
          @endif
        <!-- /.div row -->
        </div>
        @if($Companie->statu_customer == 2 )
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
                <b class="d-block">{{ $Companie->discount }} %</b>
              </p>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="text-muted">
              <p class="text-sm">General Account
                <b class="d-block">{{ $Companie->account_general_customer }}</b>
              </p>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="text-muted">
              <p class="text-sm">Auxiliary account
                <b class="d-block">{{ $Companie->account_auxiliary_customer }}</b>
              </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif
        @if($Companie->statu_supplier == 2 )
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
                <b class="d-block">{{ $Companie->account_general_supplier }}</b>
              </p>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="text-muted">
              <p class="text-sm">Auxiliary account
                <b class="d-block">{{ $Companie->account_auxiliary_supplier }}</b>
              </p>
            </div>
          </div>
          <div class="col-12 col-sm-4">
            <div class="text-muted">
              <p class="text-sm">Reception control
                <b class="d-block">{{ $Companie->recept_controle }} %</b>
              </p>
            </div>
          </div>
        <!-- /.div row -->
        </div>
        @endif
      <!-- /.div col-12 col-md-12 -->
      </div>
      <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
        <p class="text-muted">External ID : {{ $Companie->code }} </p>
        <hr>
        <div class="mt-4 product-share">
          @if($Companie->website )
          <a href="{{ $Companie->website }}" class="text-gray">
            <i class="fab fa-internet-explorer fa-2x"></i>
          </a>
          @endif
          @if($Companie->fbsite )
          <a href="{{ $Companie->fbsite }}" class="text-gray">
            <i class="fab fa-facebook-square fa-2x"></i>
          </a>
          @endif
          @if($Companie->twittersite )
          <a href="{{ $Companie->twittersite }}" class="text-gray">
            <i class="fab fa-twitter-square fa-2x"></i>
          </a>
          @endif
          @if($Companie->lkdsite )
          <a href="{{ $Companie->lkdsite }}" class="text-gray">
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
                <label for="label">Label adresse</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                      <span class="input-group-text"><i class="fas fa-tags"></i></span>
                  </div>
                  <input type="text" class="form-control" name="label"  id="label" placeholder="Label">
                </div>
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-5">
                <label for="adress">Adress</label>
                <input type="text" class="form-control" name="adress"  id="adress" placeholder="Adress">
              </div>
            </div>
            <div class="row">
              <div class="col-5">
                <label for="zipcode">Zip code</label>
                <input type="text" class="form-control" name="zipcode"  id="zipcode" placeholder="Zip code">
              </div>
              <div class="col-5">
                <label for="city">City</label>
                <input type="text" class="form-control" name="city"  id="city" placeholder="City">
              </div>
            </div>
            <div class="row">
              <div class="col-5">
                <label for="country">Country</label>
                <input type="text" class="form-control" name="country"  id="country" placeholder="Country">
              </div>
            </div>
            <hr>
            <div class="row">
              <div class="col-5">
                <label for="number">Phone number</label>
                <input type="text" class="form-control" name="number"  id="number" placeholder="Phone number">
              </div>
              <div class="col-5">
                <label for="mail">E-mail</label>
                <input type="email" class="form-control" name="mail"  id="mail" placeholder="E-mail">
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
        <x-AddressComponent :id="$Address->id" :label="$Address->label" :adress="$Address->adress" :zipcode="$Address->zipcode" :city="$Address->city" :county="$Address->country"  />
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
                <label for="civility">Civility</label>
                <select class="form-control" name="civility">
                  <option value="Miss">Miss</option>
                  <option value="Ms">Ms</option>
                  <option value="Mr">Mr</option>
                  <option value="Mrs">Mrs</option>
                </select>
              </div>
            </div>
            <div class="row">
              <div class="col-5">
                <label for="first_name">First Name</label>
                <input type="text" class="form-control" name="first_name"  id="first_name" placeholder="First Name">
              </div>
              <div class="col-5">
                <label for="name">Name</label>
                <input type="text" class="form-control" name="name"  id="name" placeholder="Name">
              </div>
            </div>
            <hr>
            <div class="form-group">
              <label for="function">Function</label>
              <input type="text" class="form-control" name="function"  id="function" placeholder="Function">
            </div>
            <hr>
            <div class="row">
              <div class="col-5">
                <label for="number">Phone number</label>
                <input type="text" class="form-control" name="number"  id="number" placeholder="Phone number">
              </div>
              <div class="col-5">
                <label for="mobile">Mobile phone number</label>
                <input type="text" class="form-control" name="mobile"  id="mobile" placeholder="Mobile phone number">
              </div>
            </div>
            <div class="form-group">
              <label for="mail">E-mail</label>
              <input type="email" class="form-control" name="mail"  id="mail" placeholder="E-mail">
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
          <x-ContactComponent :id="$Contact->id" :function="$Contact->function" :name="$Contact->name" :firstname="$Contact->first_name" :mail="$Contact->mail" :number="$Contact->number"  :mobile="$Contact->mobile" />
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