@extends('adminlte::page')

@section('title',  __('general_content.companie_trans_key')) 

@section('content_header')
  <x-Content-header-previous-button  h1="{{ $Companie->label }}" previous="{{ $previousUrl }}" list="{{ route('companies') }}" next="{{ $nextUrl }}"/>
@stop

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Company" data-toggle="tab">{{ __('general_content.detail_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Adresses" data-toggle="tab">{{ __('general_content.adress_trans_key') }} ({{ $Companie->getAddressesCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Contact" data-toggle="tab">{{ __('general_content.contacts_trans_key') }} ({{ $Companie->geContactsCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#lead" data-toggle="tab">{{ __('general_content.leads_trans_key') }} ({{ $Companie->getLeadsCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#quote" data-toggle="tab">{{ __('general_content.quotes_list_trans_key') }} ({{ $Companie->getQuotesCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#order" data-toggle="tab">{{ __('general_content.orders_list_trans_key') }} ({{ $Companie->getOrdersCountAttribute() }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#purchase" data-toggle="tab">{{ __('general_content.purchase_list_trans_key') }} ({{ $Companie->getPurchasesCountAttribute() }})</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Dashboard">
        <div class="row">
          <div class="col-lg-3 col-md-3">
            <x-adminlte-small-box title="{{ number_format($remainingInvoiceOrder->orderSum ?? 0)}}  {{ $Factory->curency }}" 
              text="{{ __('general_content.remaining_invoice_month_trans_key') }}" 
              icon="icon fas fa-info"
              theme="warning" />
          </div>

          <div class="col-lg-3 col-md-3">
            <x-adminlte-small-box title="{{ number_format($data['orderAverage'] ?? 0)}}  {{ $Factory->curency }}" 
              text="{{ __('general_content.order_average_note_trans_key') }}" 
              icon="icon fas fa-info"
              theme="orange" />
          </div>
          
          <div class="col-lg-3 col-md-3">
            <x-adminlte-card  theme="primary" theme-mode="outline">
              <p class="card-text">{{ __('general_content.bills_paid_trans_key') }} : {{ $paidInvoices }}</p>
              <p class="card-text">{{ __('general_content.bills_unpaid_trans_key') }}  : {{ $unpaidInvoices }}</p>
            </x-adminlte-card>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-3 col-md-3">
            <x-adminlte-card title="{{ __('general_content.quote_transformation_trans_key') }}" theme="teal" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
              <canvas id="donutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>

          <!-- CHART: TOTAL OVERVIEW -->
          <div class="col-lg-9 col-md-9">
            <x-adminlte-card title="{{ __('general_content.monthly_recap_report_trans_key') }}" theme="purple" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
              <div class="row">
                <div class="col-md-12">
                  <p class="text-center">
                    <strong>{{ __('general_content.sales_period_trans_key', ['year' => now()->year]) }}</strong>
                  </p>
                  <div class="chart">
                    <!-- Sales Chart Canvas -->
                      <canvas id="lineChart" style="min-height: 400px; height: 100%; max-height: 100%; max-width: 100%;"></canvas>
                  </div>
                  <!-- /.chart-responsive -->
                </div>
                <!-- /.col -->
              </div>
              <!-- ./card-body -->
            </x-adminlte-card>
          </div>
        </div>

      </div>
      <div class="tab-pane" id="Company">
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('companies.update', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
              @csrf
              <x-adminlte-card title="{{ __('general_content.general_information_trans_key') }}" theme="primary" maximizable>
                <div class="row">
                  <div class="form-group col-md-1">
                    <div class="text-muted">
                      <label for="label">{{ __('general_content.external_id_trans_key') }}</label>
                      <b class="d-block">{{ $Companie->code }}</b>
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="companies_notification">{{__('general_content.active_trans_key') }}</label>
                    <div class="input-group">
                          @if($Companie->active == 1)  
                              <x-adminlte-input-switch name="active" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"  checked />
                          @else
                              <x-adminlte-input-switch name="active" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal"  />
                          @endif
                    </div>
                  </div>
                  @if($Companie->client_type == 1) 
                  <div class="form-group col-md-4">
                    <label for="label">{{ __('general_content.name_company_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                        </div>
                        <input type="text" class="form-control" name="label"  id="label" value="{{ $Companie->label }}" placeholder="{{ __('general_content.name_company_trans_key') }}">
                    </div>
                  </div>
                  @else
                  <div class="form-group col-md-2">
                    <label for="label">{{ __('general_content.civility_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                        </div>
                        <input type="text" class="form-control" name="civility"  id="civility" value="{{ $Companie->civility }}" placeholder="{{ __('general_content.civility_trans_key') }}">
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="label">{{ __('general_content.first_name_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                        </div>
                        <input type="text" class="form-control" name="label"  id="label" value="{{ $Companie->label }}" placeholder="{{ __('general_content.first_name_trans_key') }}">
                    </div>
                  </div>
                  <div class="form-group col-md-2">
                    <label for="last_name">{{ __('general_content.contact_name_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-building"></i></span>
                        </div>
                        <input type="text" class="form-control" name="last_name"  id="last_name" value="{{ $Companie->last_name }}" placeholder="{{ __('general_content.contact_name_trans_key') }}">
                    </div>
                  </div>
                  @endif
                  <div class="form-group col-md-3">
                    @include('include.form.form-select-user',['userId' =>   $Companie->user_id])
                  </div>
                </div>
              </x-adminlte-card>

              <x-adminlte-card title="{{ __('general_content.site_link_trans_key') }}" theme="secondary" maximizable>
                <div class="row">
                  <div class="form-group col-md-3">
                      <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fab fa-internet-explorer"></i></span>
                          </div>
                          <input type="text" class="form-control"  name="website" id="website" value="{{ $Companie->website }}" placeholder="Web site">
                      </div>
                  </div>
                  <div class="form-group col-md-3">
                      <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fab fa-facebook-square"></i></span>
                          </div>
                          <input type="text" class="form-control"  name="fbsite" id="fbsite"  value="{{ $Companie->fbsite }}"  placeholder="Facebook">
                      </div>
                  </div>
                  <div class="form-group col-md-3">
                      <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fab fa-twitter-square"></i></span>
                          </div>
                          <input type="text" class="form-control"  name="twittersite" id="twittersite" value="{{ $Companie->twittersite }}"  placeholder="X">
                      </div>
                  </div>
                  <div class="form-group col-md-3">
                      <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fab fa-linkedin"></i></span>
                          </div>
                      <input type="text" class="form-control"  name="lkdsite" id="lkdsite" value="{{ $Companie->lkdsite }}"  placeholder="Linkedin">
                      </div>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-3">
                    @if($Companie->website )
                    <a href="{{ $Companie->website }}" class="text-gray">
                      <i class="fab fa-internet-explorer fa-2x"></i>
                    </a>
                    @endif
                  </div>
                  <div class="form-group col-md-3">
                    @if($Companie->fbsite )
                    <a href="{{ $Companie->fbsite }}" class="text-gray">
                      <i class="fab fa-facebook-square fa-2x"></i>
                    </a>
                    @endif
                  </div>
                  <div class="form-group col-md-3">
                    @if($Companie->twittersite )
                    <a href="{{ $Companie->twittersite }}" class="text-gray">
                      <i class="fab fa-twitter-square fa-2x"></i>
                    </a>
                    @endif
                  </div>
                  <div class="form-group col-md-3">
                    @if($Companie->lkdsite )
                    <a href="{{ $Companie->lkdsite }}" class="text-gray">
                      <i class="fab fa-linkedin fa-2x"></i>
                    </a>
                    @endif
                  </div>
                </div>
              </x-adminlte-card>

              <x-adminlte-card title="{{ __('general_content.administrative_information_trans_key') }}" theme="warning" maximizable>
                <div class="row">
                  <div class="form-group col-md-3">
                        <input type="text" class="form-control" name="siren" id="siren"  value="{{ $Companie->siren }}" placeholder="{{ __('general_content.reg_number_trans_key') }}"  @if($Companie->client_type == 2) disabled @endif>
                        @error('siren') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <input type="text" class="form-control" name="naf_code" id="naf_code"  value="{{ $Companie->naf_code }}" placeholder="{{ __('general_content.naf_code_trans_key') }}" @if($Companie->client_type == 2) disabled @endif>
                        @error('naf_code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    <div class="col-3">
                        <input type="text" class="form-control" name="intra_community_vat" id="intra_community_vat"  value="{{ $Companie->intra_community_vat }}" placeholder="{{ __('general_content.vat_number_trans_key') }}" @if($Companie->client_type == 2) disabled @endif>
                        @error('intra_community_vat') <span class="text-danger">{{ $message }}<br/></span>@enderror
                    </div>
                    
                </div>
              </x-adminlte-card>

              <x-adminlte-card theme="lime" theme-mode="outline">
                <div class="row">
                  <div class="form-group col-md-3">
                    <label for="statu_customer">{{ __('general_content.status_client_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                        </div>
                        <select class="form-control" name="statu_customer" id="statu_customer" value="statu_customer">
                            <option value="">{{__('general_content.select_status_trans_key') }}</option>
                            <option value="1" @if($Companie->statu_customer == 1 ) Selected @endif>{{__('general_content.inactive_trans_key') }}</option>
                            <option value="2" @if($Companie->statu_customer == 2 ) Selected @endif>{{__('general_content.active_trans_key') }}</option>
                            <option value="3" @if($Companie->statu_customer == 3 ) Selected @endif>{{__('general_content.prospect_trans_key') }}</option>
                        </select>
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                      <label for="discount">{{__('general_content.discount_trans_key') }} :</label>
                      <div class="input-group">
                          <div class="input-group-prepend">
                              <span class="input-group-text"><i class="fas fa-percentage"></i></span>
                          </div>
                          <input type="number" class="form-control" name="discount" id="discount" value="{{ $Companie->discount }}" placeholder="{{ __('general_content.discount_trans_key') }}">
                      </div>
                  </div>
                  <div class="form-group col-md-3">
                      <label for="account_general_customer">{{ __('general_content.general_account_trans_key') }}</label>
                      <input type="number" class="form-control" name="account_general_customer" id="account_general_customer" value="{{ $Companie->account_general_customer }}" placeholder="{{ __('general_content.general_account_trans_key') }}">
                  </div>
                  <div class="form-group col-md-3">
                      <label for="account_auxiliary_customer">{{ __('general_content.auxiliary_account_trans_key') }}</label>
                      <input type="number" class="form-control" name="account_auxiliary_customer" id="account_auxiliary_customer" value="{{ $Companie->account_auxiliary_customer }}" placeholder="{{ __('general_content.auxiliary_account_trans_key') }}">
                  </div>
                </div>
              </x-adminlte-card>

              <x-adminlte-card theme="purple" theme-mode="outline">
                <div class="row">
                  <div class="form-group col-md-3">
                    <label for="statu_supplier">{{ __('general_content.status_supplier_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                        </div>
                        <select class="form-control" name="statu_supplier" id="statu_supplier"  value="statu_supplier">
                            <option value="">{{__('general_content.select_status_trans_key') }}</option>
                            <option value="1" @if($Companie->statu_supplier == 1 ) Selected @endif>{{__('general_content.inactive_trans_key') }}</option>
                            <option value="2" @if($Companie->statu_supplier == 2 ) Selected @endif>{{__('general_content.active_trans_key') }}</option>
                        </select>
                    </div>
                  </div>
                  <div class="form-group col-md-3">
                      <label for="recept_controle">{{ __('general_content.reception_control_trans_key') }}</label>
                      <select class="form-control" name="recept_controle" id="recept_controle" value="recept_controle">
                          <option value="">{{ __('general_content.select_controle_trans_key') }}</option>
                          <option value="1" @if($Companie->recept_controle == 1 ) Selected @endif>{{ __('general_content.yes_trans_key') }}</option>
                          <option value="0" @if($Companie->recept_controle == 0 ) Selected @endif>{{ __('general_content.no_trans_key') }}</option>
                      </select>
                  </div>
                  <div class="form-group col-md-3">
                      <label for="account_general_supplier">{{ __('general_content.general_account_trans_key') }}</label>
                      <input type="number" class="form-control" id="account_general_supplier" name="account_general_supplier"  value="{{ $Companie->account_general_supplier }}" placeholder="{{ __('general_content.general_account_trans_key') }}">
                  </div>
                  <div class="form-group col-md-3">
                      <label for="account_auxiliary_supplier">{{ __('general_content.auxiliary_account_trans_key') }}</label>
                      <input type="number" class="form-control" id="account_auxiliary_supplier" name="account_auxiliary_supplier"  value="{{ $Companie->account_auxiliary_supplier }}" placeholder="{{ __('general_content.auxiliary_account_trans_key') }}">
                  </div>
                </div>
              </x-adminlte-card>

              <x-adminlte-card theme="dark" theme-mode="outline">
                <div class="row">
                  
                  <div class="form-group col-md-2">
                      <label for="latitude">{{ __('general_content.latitude_trans_key') }}</label>
                      <input type="text" class="form-control" name="latitude" id="latitude" value="{{ $Companie->latitude }}" placeholder="{{ __('general_content.latitude_trans_key') }}">
                  </div>

                  <div class="form-group col-md-2">
                    <label for="longitude">{{ __('general_content.longitude_trans_key') }}</label>
                    <input type="text" class="form-control" name="longitude" id="longitude" value="{{ $Companie->longitude }}" placeholder="{{ __('general_content.longitude_trans_key') }}">
                  </div>

                  <div class="form-group col-md-3">
                    <label for="delivery_constraint">{{ __('general_content.delivery_constraint_trans_key') }}</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-exclamation"></i></span>
                        </div>
                        <select class="form-control" name="delivery_constraint" id="delivery_constraint">
                            <option value="1" @if($Companie->delivery_constraint == 1) Selected @endif>{{ __('general_content.no_constraints_trans_key') }}</option>
                            <option value="2" @if($Companie->delivery_constraint == 2) Selected @endif>{{ __('general_content.no_tolerance_trans_key') }}</option>
                            <option value="3" @if($Companie->delivery_constraint == 3) Selected @endif>{{ __('general_content.tolerance_in_days_trans_key') }}</option>
                        </select>
                    </div>
                  </div>

                  @if($Companie->delivery_constraint == 3)
                      <div class="form-group col-md-3">
                          <label for="tolerance_days">{{ __('general_content.tolerance_days_trans_key') }}</label>
                          <input type="number" class="form-control" name="tolerance_days" id="tolerance_days" value="{{ $Companie->tolerance_days }}" placeholder="{{ __('general_content.tolerance_days_trans_key') }}">
                      </div>
                  @endif

                  <div class="form-group col-md-2">
                    <label for="quoted_delivery_note">{{ __('general_content.quoted_delivery_note_trans_key') }}</label>
                    <div class="input-group">
                        @if($Companie->quoted_delivery_note == 1)  
                            <x-adminlte-input-switch name="quoted_delivery_note" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked />
                        @else
                            <x-adminlte-input-switch name="quoted_delivery_note" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                        @endif
                    </div>
                  </div>
                </div>
              </x-adminlte-card>

              <x-adminlte-card theme="primary" theme-mode="outline">
                <x-FormTextareaComment  comment="{{ $Companie->comment }}" />
              </x-adminlte-card>

              <x-adminlte-card title="{{ __('BARECODE') }}" theme="orange" maximizable>
                <div class="row">
                  <div class="form-group col-md-12">
                      <input type="text" class="form-control" name="barcode_value" id="barcode_value"  value="{{ $Companie->barcode_value }}" >
                      @error('barcode_value') <span class="text-danger">{{ $message }}<br/></span>@enderror
                  </div>
                </div>
              </x-adminlte-card>

              <div class="card-footer">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
              </div>
            </form>
          </div>

          <div class="col-md-3">
            @include('include.file-store', ['inputName' => "companies_id",'inputValue' => $Companie->id,'filesList' => $Companie->files,])
          
            @if($Companie->barcode_value)
            <x-adminlte-card title="{{ __('BARECODE') }}" theme="orange" maximizable>
              @php echo DNS2D::getBarcodeHTML($Companie->barcode_value, 'QRCODE'); @endphp
            </x-adminlte-card>
            @endif

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="purple" maximizable>
              <p>
                <a class="btn btn-success btn-sm" href="{{ route('companies.store.quote', ['id' => $Companie->id ]) }}">
                  <i class="fas fa-folder"></i>
                  {{ __('general_content.new_quote_trans_key') }}
                </a>
              </p>
            </x-adminlte-card>

            @if($Companie->latitude && $Companie->longitude)
            <x-adminlte-card title="Map" theme="success" maximizable>
              <div id="map" style="width: 100%; height: 400px;"></div>
            </x-adminlte-card>
            @endif
          </div>
        </div>
      </div>  
      <div class="tab-pane " id="Adresses">
        <div class="row">
          <div class="form-group col-md-8">
            <x-adminlte-card title="{{ __('general_content.adress_trans_key') }}" theme="primary" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>{{ __('general_content.label_trans_key') }}</th>
                      <th>{{ __('general_content.adress_trans_key') }}</th>
                      <th>{{ __('general_content.postal_code_trans_key') }}</th>
                      <th>{{ __('general_content.city_trans_key') }}</th>
                      <th>{{ __('general_content.country_trans_key') }}</th>
                      <th>{{ __('general_content.phone_trans_key') }}</th>
                      <th>{{ __('general_content.email_trans_key') }}</th>
                      <th></th>
                      <th>{{ __('general_content.action_trans_key') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($Companie->Addresses as $Address)
                    <tr>
                      <td>{{ $Address->ordre }}</td>
                      <td>{{ $Address->label }}</td>
                      <td>{{ $Address->adress }}</td>
                      <td>{{ $Address->zipcode }}</td>
                      <td>{{ $Address->city }}</td>
                      <td>{{ $Address->country }}</td>
                      <td>{{ $Address->number }}</td>
                      <td>{{ $Address->mail }}</td>
                      <td>
                        <div class="custom-control custom-radio">
                          <input class="custom-control-input" type="radio" id="customRadioAddress{{ $Address->id }}" name="customRadioAddress{{ $Address->id }}"  @if( $Address->default == 1 ) checked @endif disabled>
                          <label for="customRadioAddress{{ $Address->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                        </div>
                      </td>
                      <td class=" py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Address{{ $Address->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $Address->id }} -->
                        <x-adminlte-modal id="Address{{ $Address->id }}" title="Update {{ $Address->label }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('addresses.edit', ['id' => $Address->id]) }}" >
                            @csrf
                            <div class="card-body">
                              <div class="row">
                                <div class="col-6">
                                  <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="ordre" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}" value="{{ $Address->ordre }}">
                                        <input type="hidden" name="id" value="{{ $Address->id }}">
                                        <input type="hidden" name="companies_id" value="{{ $Address->companies_id }}">
                                    </div>
                                </div>
                                <div class="col-6">
                                  <label for="label">{{__('general_content.label_trans_key') }}</label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}" value="{{ $Address->label }}">
                                  </div>
                                </div>
                              </div>
                              <hr>
                              <div class="row">
                                <div class="form-group col-md-12">
                                  <label for="adress">{{ __('general_content.adress_name_trans_key') }}</label>
                                  <input type="text" class="form-control" name="adress"  id="adress" placeholder="{{ __('general_content.adress_name_trans_key') }}" value="{{ $Address->adress }}">
                                </div>
                              </div>
                              <div class="row">
                                <div class="form-group col-md-12">
                                  <label for="zipcode">{{ __('general_content.postal_code_trans_key') }}</label>
                                  <input type="text" class="form-control" name="zipcode"  id="zipcode" placeholder="{{ __('general_content.postal_code_trans_key') }}" value="{{ $Address->zipcode }}">
                                </div>
                              </div>
                              <div class="row">
                                <div class="form-group col-md-12">
                                  <label for="city">{{ __('general_content.city_trans_key') }}</label>
                                  <input type="text" class="form-control" name="city"  id="city" placeholder="{{ __('general_content.city_trans_key') }}" value="{{ $Address->city }}">
                                </div>
                              </div>
                              <div class="row">
                                <div class="form-group col-md-12">
                                  <label for="country">{{ __('general_content.country_trans_key') }}</label>
                                  <input type="text" class="form-control" name="country"  id="country" placeholder="{{ __('general_content.country_trans_key') }}" value="{{ $Address->country }}">
                                </div>
                              </div>
                              
                              <div class="row">
                                <div class="form-group col-md-12">
                                  <label for="number">{{ __('general_content.phone_trans_key') }}</label>
                                  <input type="text" class="form-control" name="number"  id="number" placeholder="{{ __('general_content.phone_trans_key') }}" value="{{ $Address->number }}">
                                </div>
                              </div>
                              <div class="row">
                                <div class="form-group col-md-12">
                                  <label for="mail">{{ __('general_content.email_trans_key') }}</label>
                                  <input type="email" class="form-control" name="mail"  id="mail" placeholder="{{ __('general_content.email_trans_key') }}" value="{{ $Address->mail }}">
                                </div>
                              </div>
                              <div class="row">
                                <div class="col-4 text-left"><label for="defaultAddress{{ $Address->id }}" class="col-form-label">{{ __('general_content.by_default_trans_key') }}</label></div>
                                <div class="col-8">
                                    @if($Address->default == 1)  
                                    <x-adminlte-input-switch id="defaultAddress{{ $Address->id }}" name="defaultAdress_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                                    @else
                                    <x-adminlte-input-switch id="defaultAddress{{ $Address->id }}" name="defaultAdress_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                                    @endif
                                </div>
                              </div>
                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                            </div>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                    <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                  <tfoot>
                    <tr>
                      <th>#</th>
                      <th>{{ __('general_content.postal_code_trans_key') }}</th>
                      <th>{{ __('general_content.adress_trans_key') }}</th>
                      <th>{{ __('general_content.postal_code_trans_key') }}</th>
                      <th>{{ __('general_content.city_trans_key') }}</th>
                      <th>{{ __('general_content.country_trans_key') }}</th>
                      <th>{{ __('general_content.phone_trans_key') }}</th>
                      <th>{{ __('general_content.email_trans_key') }}</th>
                      <th></th>
                      <th>{{ __('general_content.action_trans_key') }}</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </x-adminlte-card>
          <!-- /.card secondary -->
          </div>
          <div class="col-md-4">
            <form method="POST" action="{{ route('addresses.store', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.new_address_trans_key') }}" theme="secondary" maximizable>
                @csrf
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                        </div>
                      <input type="number" class="form-control" name="ordre" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}">
                    </div>
                    <input type="hidden" name="companies_id" value="{{ $Companie->id }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="label">{{__('general_content.label_trans_key') }}</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                          <span class="input-group-text"><i class="fas fa-tags"></i></span>
                      </div>
                      <input type="text" class="form-control" name="label"  id="label" placeholder="{{__('general_content.label_trans_key') }}">
                    </div>
                  </div>
                </div>
                <hr>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="adress">{{ __('general_content.adress_name_trans_key') }}</label>
                    <input type="text" class="form-control" name="adress"  id="adress" placeholder="{{ __('general_content.adress_name_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="zipcode">{{ __('general_content.postal_code_trans_key') }}</label>
                    <input type="text" class="form-control" name="zipcode"  id="zipcode" placeholder="{{ __('general_content.postal_code_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="city">{{ __('general_content.city_trans_key') }}</label>
                    <input type="text" class="form-control" name="city"  id="city" placeholder="{{ __('general_content.city_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="country">{{ __('general_content.country_trans_key') }}</label>
                    <input type="text" class="form-control" name="country"  id="country" placeholder="{{ __('general_content.country_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="number">{{ __('general_content.phone_trans_key') }}</label>
                    <input type="text" class="form-control" name="number"  id="number" placeholder="{{ __('general_content.phone_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="mail">{{ __('general_content.email_trans_key') }}</label>
                    <input type="email" class="form-control" name="mail"  id="mail" placeholder="{{ __('general_content.email_trans_key') }}">
                  </div>
                </div>
                <x-slot name="footerSlot">
                  <button type="submit" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
        </div>
      <!-- /.row -->
      </div>    
      <div class="tab-pane " id="Contact">
        <div class="row">
          <div class="col-md-8">
            <x-adminlte-card title="{{ __('general_content.contacts_trans_key') }}" theme="primary" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>{{ __('general_content.function_trans_key') }}</th>
                      <th>{{ __('general_content.name_trans_key') }}</th>
                      <th>{{ __('general_content.first_name_trans_key') }}</th>
                      <th>{{ __('general_content.email_trans_key') }}</th>
                      <th>{{ __('general_content.phone_trans_key') }}</th>
                      <th>{{ __('general_content.mobile_phone_trans_key') }}</th>
                      <th></th>
                      <th>{{ __('general_content.action_trans_key') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($Companie->Contacts as $Contact)
                    <tr>
                      <td>{{ $Contact->ordre }}</td>
                      <td>{{ $Contact->function }}</td>
                      <td>{{ $Contact->name }}</td>
                      <td>{{ $Contact->first_name }}</td>
                      <td>{{ $Contact->mail }}</td>
                      <td>{{ $Contact->number }}</td>
                      <td>{{ $Contact->mobile }}</td>
                      <td>
                        <div class="custom-control custom-radio">
                          <input class="custom-control-input" type="radio" id="customRadioContact{{ $Contact->id }}" name="customRadioContact{{ $Contact->id }}"  @if( $Contact->default == 1 ) checked @endif disabled>
                          <label for="customRadioContact{{ $Contact->id }}" class="custom-control-label">{{__('general_content.by_default_trans_key') }}</label>
                        </div>
                      </td>
                      <td class=" py-0 align-middle">
                        <!-- Button Modal -->
                        <button type="button" class="btn bg-teal" data-toggle="modal" data-target="#Contact{{ $Contact->id }}">
                          <i class="fa fa-lg fa-fw  fa-edit"></i>
                        </button>
                        <!-- Modal {{ $Contact->id }} -->
                        <x-adminlte-modal id="Contact{{ $Contact->id }}" title="Update {{ $Contact->name }}" theme="teal" icon="fa fa-pen" size='lg' disable-animations>
                          <form method="POST" action="{{ route('contacts.edit', ['id' => $Contact->id]) }}" >
                            @csrf
                            <div class="card-body">
                              <div class="row">
                                <div class="col-6">
                                  <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                                  <div class="input-group">
                                    <div class="input-group-prepend">
                                      <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                                    </div>
                                    <input type="text" class="form-control" name="ordre" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}" value="{{ $Contact->ordre }}">
                                    <input type="hidden" name="companies_id" value="{{ $Contact->companies_id }}">
                                    <input type="hidden" name="id" value="{{ $Contact->id }}">
                                  </div>
                                </div>
                                <div class="col-6">
                                  <label for="civility">{{ __('general_content.civility_trans_key') }}</label>
                                  <select class="form-control" name="civility">
                                    <option value="{{ __('general_content.miss_trans_key') }}" @if( $Contact->civility ==__('general_content.miss_trans_key')) Selected @endIf >{{ __('general_content.miss_trans_key') }}</option>
                                    <option value="{{ __('general_content.ms_trans_key') }}" @if( $Contact->civility ==__('general_content.ms_trans_key')) Selected @endIf >{{ __('general_content.ms_trans_key') }}</option>
                                    <option value="{{ __('general_content.mr_trans_key') }}" @if( $Contact->civility ==__('general_content.mr_trans_key')) Selected @endIf >{{ __('general_content.mr_trans_key') }}</option>
                                    <option value="{{ __('general_content.mrs_trans_key') }}" @if( $Contact->civility ==__('general_content.mrs_trans_key')) Selected @endIf >{{ __('general_content.mrs_trans_key') }}</option>
                                  </select>
                                </div>
                              </div>
                              <div class="row">
                                <div class="col-6">
                                  <label for="first_name">{{ __('general_content.first_name_trans_key') }}</label>
                                  <input type="text" class="form-control" name="first_name"  id="first_name" placeholder="{{ __('general_content.first_name_trans_key') }}" value="{{ $Contact->first_name }}">
                                </div>
                                <div class="col-6">
                                  <label for="name">{{ __('general_content.name_trans_key') }}</label>
                                  <input type="text" class="form-control" name="name"  id="name" placeholder="{{ __('general_content.name_trans_key') }}" value="{{ $Contact->name }}">
                                </div>
                              </div>
                              <hr>
                              <div class="form-group">
                                <label for="function">{{ __('general_content.function_trans_key') }}</label>
                                <input type="text" class="form-control" name="function"  id="function" placeholder="{{ __('general_content.function_trans_key') }}" value="{{ $Contact->function }}">
                              </div>
                              <hr>
                              <div class="row">
                                <div class="col-6">
                                  <label for="number">{{ __('general_content.phone_trans_key') }}</label>
                                  <input type="text" class="form-control" name="number"  id="number" placeholder="{{ __('general_content.phone_trans_key') }}"  value="{{ $Contact->number }}">
                                </div>
                                <div class="col-6">
                                  <label for="mobile">{{ __('general_content.mobile_phone_trans_key') }}</label>
                                  <input type="text" class="form-control" name="mobile"  id="mobile" placeholder="{{ __('general_content.mobile_phone_trans_key') }}"  value="{{ $Contact->mobile }}">
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="mail">{{ __('general_content.email_trans_key') }}</label>
                                <input type="email" class="form-control" name="mail"  id="mail" placeholder="{{ __('general_content.email_trans_key') }}"  value="{{ $Contact->mail }}">
                              </div>
                              <div class="row">
                                <div class="col-4 text-left"><label for="defaultContact{{ $Contact->id }}" class="col-form-label">{{ __('general_content.by_default_trans_key') }}</label></div>
                                <div class="col-8">
                                    @if($Contact->default == 1)  
                                    <x-adminlte-input-switch id="defaultContact{{ $Contact->id }}" name="defaultContact_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                                    @else
                                    <x-adminlte-input-switch id="defaultContact{{ $Contact->id }}" name="defaultContact_update" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" />
                                    @endif
                                </div>
                              </div>
                            </div>
                            <!-- /.card-body -->
                            <div class="card-footer">
                              <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                            </div>
                          </form>
                        </x-adminlte-modal>
                      </td>
                    </tr>
                    @empty
                    <x-EmptyDataLine col="6" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                  </tbody>
                  <tfoot>
                    <tr>
                      <th>#</th>
                      <th>{{ __('general_content.function_trans_key') }}</th>
                      <th>{{ __('general_content.name_trans_key') }}</th>
                      <th>{{ __('general_content.first_name_trans_key') }}</th>
                      <th>{{ __('general_content.email_trans_key') }}</th>
                      <th>{{ __('general_content.phone_trans_key') }}</th>
                      <th>{{ __('general_content.mobile_phone_trans_key') }}</th>
                      <th></th>
                      <th>{{ __('general_content.action_trans_key') }}</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </x-adminlte-card>
          <!-- /.card secondary -->
          </div>
          <div class="col-md-4">
            <form method="POST" action="{{ route('contacts.store', ['id' => $Companie->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.new_contact_trans_key') }}" theme="secondary" maximizable>
                @csrf
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="ordre">{{ __('general_content.sort_trans_key') }}:</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-sort-numeric-down"></i></span>
                      </div>
                      <input type="number" class="form-control" name="ordre" id="ordre" placeholder="{{ __('general_content.sort_trans_key') }}">
                      <input type="hidden" name="companies_id" value="{{ $Companie->id }}">
                    </div>
                  </div>
                  <div class="form-group col-md-6">
                    <label for="civility">{{ __('general_content.civility_trans_key') }}</label>
                    <select class="form-control" name="civility">
                      <option value="{{ __('general_content.miss_trans_key') }}">{{ __('general_content.miss_trans_key') }}</option>
                      <option value="{{ __('general_content.ms_trans_key') }}">{{ __('general_content.ms_trans_key') }}</option>
                      <option value="{{ __('general_content.mr_trans_key') }}">{{ __('general_content.mr_trans_key') }}</option>
                      <option value="{{ __('general_content.mrs_trans_key') }}">{{ __('general_content.mrs_trans_key') }}</option>
                    </select>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="first_name">{{ __('general_content.first_name_trans_key') }}</label>
                    <input type="text" class="form-control" name="first_name"  id="first_name" placeholder="{{ __('general_content.first_name_trans_key') }}">
                  </div>
                  <div class="form-group col-md-6">
                    <label for="name">{{ __('general_content.name_trans_key') }}</label>
                    <input type="text" class="form-control" name="name"  id="name" placeholder="{{ __('general_content.name_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="function">{{ __('general_content.function_trans_key') }}</label>
                    <input type="text" class="form-control" name="function"  id="function" placeholder="{{ __('general_content.function_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="number">{{ __('general_content.phone_trans_key') }}</label>
                    <input type="text" class="form-control" name="number"  id="number" placeholder="{{ __('general_content.phone_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="mobile">{{ __('general_content.mobile_phone_trans_key') }}</label>
                    <input type="text" class="form-control" name="mobile"  id="mobile" placeholder="{{ __('general_content.mobile_phone_trans_key') }}">
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="mail">{{ __('general_content.email_trans_key') }}</label>
                    <input type="email" class="form-control" name="mail"  id="mail" placeholder="{{ __('general_content.email_trans_key') }}">
                  </div>
                </div>
                <x-slot name="footerSlot">
                  <button type="submit" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
        <!-- /.row -->
        </div> 
      </div>
      <div class="tab-pane" id="lead">
        @livewire('leads-index' , ['idCompanie' => $Companie->id ])
      </div>
      <div class="tab-pane" id="quote">
        @livewire('quotes-index' , ['idCompanie' => $Companie->id ])
      </div>
      <div class="tab-pane" id="order">
        @livewire('orders-index' , ['idCompanie' => $Companie->id ])
      </div>
      <div class="tab-pane" id="purchase">
        @livewire('purchases-index' , ['idCompanie' => $Companie->id ])
      </div>
    </div>
  </div>
</div>
@stop

@section('css')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
@stop

@section('js')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<script>
  // Vérifie que les coordonnées sont définies dans la variable PHP
  var latitude = {{ $Companie->latitude ?? '48.8588443' }};  // Remplace par des valeurs par défaut si non disponible
  var longitude = {{ $Companie->longitude ?? '2.2943506' }};  // Exemple: coordonnées de la Tour Eiffel

  // Initialise la carte
  var map = L.map('map').setView([latitude, longitude], 13);

  // Charger les tuiles de la carte (OpenStreetMap)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
  }).addTo(map);

  // Ajouter un marqueur à la position des coordonnées
  L.marker([latitude, longitude]).addTo(map)
      .bindPopup('{{ $Companie->label }}')
      .openPopup();
</script>

<script>
  //-------------
//- PIE CHART -
//-------------
  var donutChartCanvas = $('#donutChart').get(0).getContext('2d')
  var donutData        = {
      labels: [
        @foreach ($data['quotesDataRate'] as $item)
              @if(1 == $item->statu )  "Open", @endif
              @if(2 == $item->statu )  "Send", @endif
              @if(3 == $item->statu )  "Win", @endif
              @if(4 == $item->statu )  "Lost", @endif
              @if(5 == $item->statu )  "{{__('general_content.closed_trans_key') }}", @endif
              @if(6 == $item->statu )  "Obsolete", @endif
        @endforeach
      ],
      datasets: [
        {
          data: [
                @foreach ($data['quotesDataRate'] as $item)
                "{{ $item->QuoteCountRate }}",
                @endforeach
              ], 
              backgroundColor: [
                  'rgba(23, 162, 184, 1)',
                  'rgba(255, 193, 7, 1)',
                  'rgba(40, 167, 69, 1)',
                  'rgba(220, 53, 69, 1)',
                  'rgba(108, 117, 125, 1)',
                  'rgba(0, 123, 255, 1)',
              ],
        }
      ]
    }
    var donutOptions     = {
      maintainAspectRatio : false,
      responsive : true,
    }
    //Create pie or douhnut chart
    // You can switch between pie and douhnut using the method below.
    new Chart(donutChartCanvas, {
      type: 'pie',
      data: donutData,
      options: donutOptions
    })

  //--------------
  //- LINE CHART -
  //--------------
  // Get context with jQuery - using jQuery's .get() method.
  var areaChartCanvas = $('#lineChart').get(0).getContext('2d')
  var areaChartData = {
      labels  : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August','September','October','November','December' ],
      datasets: [
        {
          label               : 'Order forecast',
          borderColor         : 'rgba(60,141,188,0.5)',
          pointRadius          : 5,
          pointColor          : '#3b8bba',
          pointStrokeColor    : 'rgba(60,141,188,1)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(60,141,188,1)',
          data                : [
                              @php ($j = 1)
                              @for($iM =1;$iM<=12;$iM++)
                                @foreach ($data['orderMonthlyRecap'] as $key => $item)
                                @php ($j = 1)
                                  @if($iM  == $item->month) 
                                  "{{ $item->orderSum }}",
                                    @php ($j = 2)
                                    @break
                                  @endif
                                @endforeach
                                @if($j == 1) 
                                  0,
                                  @php ($j = 1)
                                @endif
                              @endfor ]
        },
      ]
    }
    var areaChartOptions = {
      maintainAspectRatio : false,
      responsive : true,
      legend: {
        display: true,
      },
      scales: {
        xAxes: [{
          gridLines : {
            color:'rgba(0,0,0,0.4)',
            display : true,
          }
        }],
        yAxes: [{
          gridLines : {
            color:'rgba(0,0,0,0.4)',
            display : true,
          }
        }]
      }
    }

    // This will get the first returned node in the jQuery collection.
    new Chart(areaChartCanvas, {
      type: 'line',
      data: areaChartData,
      options: areaChartOptions
    })

    var lineChartCanvas = $('#lineChart').get(0).getContext('2d')
    var lineChartOptions = $.extend(true, {}, areaChartOptions)
    var lineChartData = $.extend(true, {}, areaChartData)
    lineChartData.datasets[0].fill = true;
    lineChartData.datasets[1].fill = false;
    lineChartOptions.datasetFill = false

    var lineChart = new Chart(lineChartCanvas, {
      type: 'line',
      data: lineChartData,
      options: lineChartOptions
    })
</script>
@stop