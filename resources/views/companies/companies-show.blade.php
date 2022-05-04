@extends('adminlte::page')

@section('title', 'Companies')

@section('content_header')
  <x-Content-header-previous-button  h1="{{ $Companie->label }}" previous="{{ $previousUrl }}" list="{{ route('companies') }}" next="{{ $nextUrl }}"/>
@stop

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Company" data-toggle="tab">Detail</a></li>
      <li class="nav-item"><a class="nav-link" href="#Adresses" data-toggle="tab">Adresses ({{ $Companie->getAddressesCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Contact" data-toggle="tab">Contact ({{ $Companie->geContactsCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#quote" data-toggle="tab">Quotes list ({{ $Companie->getQuotesCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#order" data-toggle="tab">Orders list ({{ $Companie->getOrdersCountAttribute() }})</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Company">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('companies.update', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                      <div class="row">
                        <label for="InputWebSite">General information</label>
                      </div>
                      <div class="row">
                        <div class="col-12 col-sm-4">
                          <div class="text-muted">
                            <label for="label">External ID</label>
                              <b class="d-block">{{ $Companie->code }}</b>
                            </p>
                          </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label for="label">Name of company</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                </div>
                                <input type="text" class="form-control" name="label"  id="label" value="{{ $Companie->label }}" placeholder="Name of company">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label for="user_id">Technical manager</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <select class="form-control" name="user_id" id="user_id" value="user_id">
                                    <option value="">Select user</option>
                                @foreach ($userSelect as $item)
                                    <option value="{{ $item->id }}" @if($Companie->user_id == $item->id ) Selected @endif >{{ $item->name }}</option>
                                @endforeach
                                </select>
                            </div>
                        </div>
                      </div>
                      <hr>
                      <div class="row">
                          <label for="InputWebSite">Site link</label>
                      </div>
                      <div class="row">
                          <div class="col-3">
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                                  </div>
                                  <input type="text" class="form-control"  name="website" id="website" value="{{ $Companie->website }}" placeholder="Web site link">
                              </div>
                          </div>
                          <div class="col-3">
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fab fa-facebook-square"></i></span>
                                  </div>
                                  <input type="text" class="form-control"  name="fbsite" id="fbsite"  value="{{ $Companie->fbsite }}"  placeholder="Facebook link">
                              </div>
                          </div>
                          <div class="col-3">
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fab fa-twitter-square"></i></span>
                                  </div>
                                  <input type="text" class="form-control"  name="twittersite" id="twittersite" value="{{ $Companie->twittersite }}"  placeholder="Twitter link">
                              </div>
                          </div>
                          <div class="col-3">
                              <div class="input-group">
                                  <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                                  </div>
                              <input type="text" class="form-control"  name="lkdsite" id="lkdsite" value="{{ $Companie->lkdsite }}"  placeholder="Linkedin link">
                              </div>
                          </div>
                      </div>
                    <hr>
                    <div class="row">
                        <label for="siren">Administrative information</label>
                    </div>
                    <div class="row">
                        <div class="col-3">
                            <input type="text" class="form-control" name="siren" id="siren"  value="{{ $Companie->siren }}" placeholder="Siren">
                            @error('siren') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                        <div class="col-3">
                            <input type="text" class="form-control" name="naf_code" id="naf_code"  value="{{ $Companie->naf_code }}" placeholder="naf_code code">
                            @error('naf_code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                        <div class="col-3">
                            <input type="text" class="form-control" name="intra_community_vat" id="intra_community_vat"  value="{{ $Companie->intra_community_vat }}" placeholder="VAT number">
                            @error('intra_community_vat') <span class="text-danger">{{ $message }}<br/></span>@enderror
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-3">
                            <label for="statu_customer">Statu client</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                </div>
                                <select class="form-control" name="statu_customer" id="statu_customer" value="statu_customer">
                                    <option value="">Select statu</option>
                                    <option value="1" @if($Companie->statu_customer == 1 ) Selected @endif>Inactive</option>
                                    <option value="2" @if($Companie->statu_customer == 2 ) Selected @endif>Active</option>
                                    <option value="3" @if($Companie->statu_customer == 3 ) Selected @endif>Prospect</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="discount">Discount :</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                                </div>
                                <input type="number" class="form-control" name="discount" id="discount" value="{{ $Companie->discount }}" placeholder="Discount">
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="account_general_customer">General Account</label>
                            <input type="number" class="form-control" name="account_general_customer" id="account_general_customer" value="{{ $Companie->account_general_customer }}" placeholder="General Account">
                        </div>
                        <div class="col-3">
                            <label for="account_auxiliary_customer">Auxiliary Account</label>
                            <input type="number" class="form-control" name="account_auxiliary_customer" id="account_auxiliary_customer" value="{{ $Companie->account_auxiliary_customer }}" placeholder="Auxiliary account">
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-3">
                            <label for="statu_supplier">Statu supplier</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                                </div>
                                <select class="form-control" name="statu_supplier" id="statu_supplier"  value="statu_supplier">
                                    <option value="">Select statu</option>
                                    <option value="1" @if($Companie->statu_supplier == 1 ) Selected @endif>Inactive</option>
                                    <option value="2" @if($Companie->statu_supplier == 2 ) Selected @endif>Active</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-3">
                            <label for="recept_controle">Reception control</label>
                            <select class="form-control" name="recept_controle" id="recept_controle" value="recept_controle">
                                <option value="">Select controle type</option>
                                <option value="1" @if($Companie->recept_controle == 1 ) Selected @endif>Yes</option>
                                <option value="2" @if($Companie->recept_controle == 2 ) Selected @endif>No</option>
                            </select>
                        </div>
                        <div class="col-3">
                            <label for="account_general_supplier">General Account</label>
                            <input type="number" class="form-control" id="account_general_supplier" name="account_general_supplier"  value="{{ $Companie->account_general_supplier }}" placeholder="General Account">
                        </div>
                        <div class="col-3">
                            <label for="account_auxiliary_supplier">Auxiliary Account</label>
                            <input type="number" class="form-control" id="account_auxiliary_supplier" name="account_auxiliary_supplier"  value="{{ $Companie->account_auxiliary_supplier }}" placeholder="Auxiliary account">
                        </div>
                    </div>
                    <hr>
                    <div class="card card-body">
                      <div class="row">
                        <x-FormTextareaComment  comment="{{ $Companie->comment }}" />
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="Submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
              </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="card card-secondary">
                <div class="card-header">
                  <h3 class="card-title"> Informations </h3>
                </div>
                <div class="card-body">
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
                </div>
              </div>
            </div>
          </div>
      </div>  
      <div class="tab-pane " id="Adresses">
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
                        <label for="ordre">Sort order:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                            </div>
                          <input type="number" class="form-control" name="ordre" id="ordre" placeholder="Order">
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
      </div>    
      <div class="tab-pane " id="Contact">
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
                        <label for="ordre">Sort order:</label>
                        <div class="input-group">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                          </div>
                          <input type="number" class="form-control" name="ordre" id="ordre" placeholder="Order">
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
      </div>
      <div class="tab-pane" id="quote">
        @livewire('quotes-index' , ['idCompanie' => $Companie->id ])
      </div>
      <div class="tab-pane" id="order">
        @livewire('orders-index' , ['idCompanie' => $Companie->id ])
      </div>
    </div>
  </div>
</div>
@stop

@section('css')
@stop

@section('js')
@stop