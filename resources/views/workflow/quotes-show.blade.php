@extends('adminlte::page')

@section('title', __('general_content.quote_trans_key'))

@section('content_header')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <script rel="stylesheet" src="{{ asset('js/switchtabNav.js') }}"></script>
    <x-Content-header-previous-button  h1="{{ __('general_content.quote_trans_key') }} : {{  $Quote->code }}" previous="{{ $previousUrl }}" list="{{ route('quotes') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills"  id="DocumentTabs">
      <li class="nav-item"><a class="nav-link " href="#Quote" data-toggle="tab">{{ __('general_content.quote_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.quote_line_trans_key') }} ({{ count($Quote->QuoteLines) }})</a></li>
      @if($Factory->enable_construction_site)
      <li class="nav-item"><a class="nav-link" href="#Construction" data-toggle="tab">{{ __('general_content.construction_site_trans_key') }} <span class="badge badge-danger right">{{ __('general_content.beta_trans_key') }}</span></a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#Charts" data-toggle="tab">{{ __('general_content.charts_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Views" data-toggle="tab">{{ __('general_content.guest_page_trans_key') }} ( {{  $Quote->visitsCount() }} )</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }} ({{ count($CustomFields) }})</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#LinesImport" data-toggle="tab">{{ __('general_content.lines_import_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Logs" data-toggle="tab">{{ __('general_content.logs_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane " id="Quote">
        @livewire('arrow-steps.arrow-quote', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('quotes.update', ['id' => $Quote->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary"  maximizable>
                @csrf 
                <div class="row">
                  <div class="form-group col-md-6">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Quote->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Quote->GetshortCreatedAttribute() }}</p>
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.name_quote_trans_key'), 'Value' =>  $Quote->label])
                  </div>
                </div>
                  @if($Quote->companie['active'] == 1)
                  <div class="row">
                    <label for="companies_id" class="text-info">{{ __('general_content.customer_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                        @if($Quote->opportunities_id or $Quote->statu != 1)
                        {{ __('general_content.companie_trans_key') }} :  <x-CompanieButton id="{{ $Quote->companie['id'] }}" label="{{ $Quote->companie['label'] }}"  />
                          <input type="hidden" name="companies_id" value="{{ $Quote->companies_id }}">
                          @else
                              @include('include.form.form-select-companie',['companiesId' =>  $Quote->companies_id])
                        @endif
                    </div>
                    <div class="form-group col-md-6">
                      @include('include.form.form-input-customerInfo',['customerReference' =>  $Quote->customer_reference])
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-adress',['adressId' =>   $Quote->companies_addresses_id])
                    </div>
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-contact',['contactId' =>   $Quote->companies_contacts_id])
                    </div>
                  </div>
                @else
                <input type="hidden" name="companies_id" value="{{ $Quote->companies_id }}">
                <input type="hidden" name="customer_reference" value="{{ $Quote->customer_reference }}">
                <input type="hidden" name="companies_addresses_id" value="{{ $Quote->companies_addresses_id }}">
                <input type="hidden" name="companies_contacts_id" value="{{ $Quote->companies_contacts_id }}">
                @php
                  $customerButton = view('components.companie-button', [
                    'id' => $Quote->companie['id'],
                    'label' => $Quote->companie['label'],
                  ])->render();
                @endphp
                <x-adminlte-alert theme="info" title="{{ __('general_content.info_trans_key') }}">
                  {!! __('general_content.customer_disabled_warning_trans_key', ['customer' => $customerButton]) !!}
                </x-adminlte-alert>
                @endif
                  <div class="row">
                    <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Quote->accounting_payment_conditions_id])
                    </div>
                    <div class="form-group col-md-6">
                        @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Quote->accounting_payment_methods_id])
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                        @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Quote->accounting_deliveries_id])
                    </div>
                    <div class="form-group col-md-6">
                      <label for="validity_date">{{ __('general_content.validity_date_trans_key') }}</label>
                      <div class="input-group">
                        <div class="input-group-text bg-gradient-secondary">
                          <i class="fas fa-calendar-day"></i>
                        </div>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Quote->validity_date }}">
                      </div>
                    </div>
                  </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Quote->comment }}" />
                </div>
                <div class="row mt-3">
                  <div class="col-12">
                    <h5 class="text-info">{{ __('general_content.review_change_tracking_trans_key') }}</h5>
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="reviewed_by">{{ __('general_content.reviewed_by_trans_key') }}</label>
                    <select class="form-control" name="reviewed_by" id="reviewed_by">
                      <option value="">{{ __('general_content.select_user_trans_key') }}</option>
                      @foreach($Reviewers as $user)
                        <option value="{{ $user->id }}" @selected(old('reviewed_by', $Quote->reviewed_by) == $user->id)>{{ $user->name }}</option>
                      @endforeach
                    </select>
                    @error('reviewed_by')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="reviewed_at">{{ __('general_content.review_date_trans_key') }}</label>
                    <input type="datetime-local" class="form-control" name="reviewed_at" id="reviewed_at" value="{{ old('reviewed_at', optional($Quote->reviewed_at)->format('Y-m-d\\TH:i')) }}">
                    @error('reviewed_at')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="review_decision">{{ __('general_content.decision_trans_key') }}</label>
                    <select class="form-control" name="review_decision" id="review_decision">
                      <option value="">{{ __('general_content.undefined_trans_key') }}</option>
                      <option value="pending" @selected(old('review_decision', $Quote->review_decision) === 'pending')>{{ __('general_content.pending_trans_key') }}</option>
                      <option value="approved" @selected(old('review_decision', $Quote->review_decision) === 'approved')>{{ __('general_content.approved_trans_key') }}</option>
                      <option value="rejected" @selected(old('review_decision', $Quote->review_decision) === 'rejected')>{{ __('general_content.rejected_trans_key') }}</option>
                    </select>
                    @error('review_decision')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="change_requested_by">{{ __('general_content.change_requested_by_trans_key') }}</label>
                    <select class="form-control" name="change_requested_by" id="change_requested_by">
                      <option value="">{{ __('general_content.select_user_trans_key') }}</option>
                      @foreach($Reviewers as $user)
                        <option value="{{ $user->id }}" @selected(old('change_requested_by', $Quote->change_requested_by) == $user->id)>{{ $user->name }}</option>
                      @endforeach
                    </select>
                    @error('change_requested_by')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-12">
                    <label for="change_reason">{{ __('general_content.change_reason_trans_key') }}</label>
                    <textarea class="form-control" name="change_reason" id="change_reason" rows="3">{{ old('change_reason', $Quote->change_reason) }}</textarea>
                    @error('change_reason')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="change_approved_at">{{ __('general_content.change_approved_at_trans_key') }}</label>
                    <input type="datetime-local" class="form-control" name="change_approved_at" id="change_approved_at" value="{{ old('change_approved_at', optional($Quote->change_approved_at)->format('Y-m-d\\TH:i')) }}">
                    @error('change_approved_at')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" collapsible maximizable>
              @include('include.sub-total-price')
            </x-adminlte-card>

            @can('scheduling-menu')
            <x-adminlte-card title="{{ __('general_content.delivery_simulation_trans_key') }}" theme="info" collapsible maximizable>
              <form action="{{ route('quotes.delivery.simulation', ['id' => $Quote->id]) }}" method="POST">
                @csrf
                <div class="form-group">
                  <label for="requested_delivery_date">{{ __('general_content.requested_delivery_date_trans_key') }}</label>
                  <input type="date" class="form-control" id="requested_delivery_date" name="requested_delivery_date" value="{{ old('requested_delivery_date') }}" required>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="fas fa-play-circle"></i> {{ __('general_content.run_simulation_trans_key') }}
                </button>
              </form>

              @if(session('delivery_simulation'))
              @php
                $simulation = session('delivery_simulation');
              @endphp
                <div class="mt-3">
                  <h6 class="text-muted mb-2">{{ __('general_content.simulation_results_trans_key') }}</h6>
                  @if($simulation['is_possible'])
                    <span class="badge badge-success">{{ __('general_content.simulation_possible_trans_key') }}</span>
                  @else
                    <span class="badge badge-danger">{{ __('general_content.simulation_not_possible_trans_key') }}</span>
                  @endif

                  <p class="mt-2 mb-1">
                    <strong>{{ __('general_content.requested_delivery_date_trans_key') }}:</strong>
                    {{ $simulation['requested_date'] }}
                  </p>

                  @if(!empty($simulation['earliest_date']))
                    <p class="mb-2">
                      <strong>{{ __('general_content.earliest_possible_date_trans_key') }}:</strong>
                      {{ $simulation['earliest_date'] }}
                    </p>
                  @endif

                  <p class="text-muted mb-2">
                    {{ __('general_content.simulation_capacity_per_day_trans_key') }} ({{ $simulation['capacity_per_day'] }} h)
                  </p>

                  <table class="table table-sm table-striped mb-0">
                    <thead>
                      <tr>
                        <th>{{ __('general_content.simulation_service_trans_key') }}</th>
                        <th class="text-end">{{ __('general_content.simulation_required_hours_trans_key') }}</th>
                        @if(!$simulation['is_possible'])
                          <th class="text-end">{{ __('general_content.simulation_remaining_hours_trans_key') }}</th>
                        @endif
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($simulation['required_by_service'] as $serviceId => $requiredHours)
                        <tr>
                          <td>{{ $simulation['service_labels'][$serviceId] ?? $serviceId }}</td>
                          <td class="text-end">{{ $requiredHours }}</td>
                          @if(!$simulation['is_possible'])
                            <td class="text-end">{{ $simulation['missing_by_service'][$serviceId] ?? 0 }}</td>
                          @endif
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </x-adminlte-card>
            @endcan

            @if($Quote->opportunities_id)
              <x-adminlte-card title="{{ __('general_content.historical_trans_key') }}" theme="info"  collapsible="collapsed" maximizable>
                <div class="text-muted">
                  <h3>{{__('general_content.opportunity_trans_key')}} #{{ $Quote->opportunities->label }} </h3><x-ButtonTextView route="{{ route('opportunities.show', ['id' => $Quote->opportunities_id])}}" />
                  
                  <p class="small">{{ __('general_content.user_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->UserManagement['name'] }}</b>
                  </p>
                  <p class="small">{{ __('general_content.probality_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->probality }} %</b> 
                  </p>
                  <p class="small">{{ __('general_content.budget_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->budget }}</b>
                  </p>
                </div>
              </x-adminlte-card>
            @endif

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" collapsible="collapsed" maximizable>
              <table class="table table-hover">
                <tr>
                  <td style="width:50%">{{ __('general_content.quote_trans_key') }}</td>
                  <td><x-ButtonTextPDF route="{{ route('pdf.quote', ['Document' => $Quote->id])}}" /></td>
                </tr>
                @if(config('mail.default') && config('mail.from.address'))
                <tr>
                  <td style="width:50%">{{ __('general_content.email_trans_key') }} </td>
                  <td><x-ButtonTextEmail route="{{ route('email.create', ['type' => 'quote', 'id' => $Quote->id]) }}" /></td>
                </tr>
                @endif
                @if($Quote->uuid)
                <tr>
                  <td style="width:50%">{{ __('general_content.public_link_trans_key') }}</td>
                  <td>
                    <button class="btn btn-info btn-sm" onclick="copyToClipboard('{{ Request::root() }}/guest/quote/{{ $Quote->uuid }}')">
                      <i class="fas fa-copy"></i> {{ __('general_content.copy_trans_key') }} 
                    </button>
                  </td>
                </tr>
                @endif
                
                @forelse($Quote->Orders as $Order)
                <tr>
                    <td style="width:50%"><x-OrderButton id="{{ $Order->id }}" code="{{ $Order->code }}"  /></td>
                    <td><x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" /></td>
                </tr>
                @empty
                <!--<tr>
                  <td colspan="2">
                      {{ __('general_content.no_data_trans_key') }}
                  </td>
                </tr>-->
                @endforelse
              </table>
            </x-adminlte-card>
            @include('include.file-store', ['inputName' => "quotes_id",'inputValue' => $Quote->id,'filesList' => $Quote->files,])
            @include('include.email-list', ['mailsList'=> $Quote->emailLogs,])
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="Lines">
        @php
          $quoteLineEndpoints = [
            'lines'           => route('quotes.lines.json.for-quote',       ['quoteId' => $Quote->id]),
            'selectData'      => route('quotes.lines.json.select-data',     ['quoteId' => $Quote->id]),
            'priceList'       => route('quotes.lines.json.price-list',      ['quoteId' => $Quote->id, 'productId' => '__PRODUCT__']),
            'store'           => route('quotes.lines.json.store',           ['quoteId' => $Quote->id]),
            'update'          => route('quotes.lines.json.update',          ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'destroy'         => route('quotes.lines.json.destroy',         ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'duplicate'       => route('quotes.lines.json.duplicate',       ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'move'            => route('quotes.lines.json.move',            ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'reorder'         => route('quotes.lines.json.reorder',         ['quoteId' => $Quote->id]),
            'createProduct'   => route('quotes.lines.json.create-product',  ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'tasks'           => route('quotes.lines.json.tasks',           ['quoteId' => $Quote->id, 'id' => '__ID__']),
            'calculatedPrice' => route('quotes.lines.json.calculated-price',['quoteId' => $Quote->id, 'id' => '__ID__']),
            'storeOrder'      => route('quotes.lines.json.store-order',     ['quoteId' => $Quote->id]),
            'priceIncrease'   => route('quotes.lines.json.price-increase',  ['quoteId' => $Quote->id]),
          ];
          if (env('RADAN_SYM_IMPORT', false)) {
            $quoteLineEndpoints['importSym'] = route('quotes.lines.json.import-sym', ['quoteId' => $Quote->id]);
          }
        @endphp
        <div class="card">
          <div class="card-body">
            <div
              id="quote-lines-page-app"
              data-quote-id="{{ $Quote->id }}"
              data-quote-statu="{{ $Quote->statu }}"
              data-endpoints="{{ json_encode($quoteLineEndpoints) }}"
            >
              <div class="text-center py-4 text-muted">
                <i class="fas fa-spinner fa-spin mr-2"></i> Chargement des lignes...
              </div>
            </div>
          </div>
        </div>
      </div>
      @if($Factory->enable_construction_site)
      <div class="tab-pane " id="Construction">
        <div
          id="construction-site-app"
          data-quote-id="{{ $Quote->id }}"
          data-save-url="{{ route('quotes.project.estimates', ['id' => $Quote->id]) }}"
          data-currency="{{ $Factory->curency }}"
          data-initial-data="{{ json_encode($projectEstimate) }}"
        ></div>
      </div>
      @endif
      <div class="tab-pane" id="Charts">
        <div
          id="quote-charts-tab-app"
          data-product-time="{{ json_encode(array_values($TotalServiceProductTime)) }}"
          data-setting-time="{{ json_encode(array_values($TotalServiceSettingTime)) }}"
          data-cost="{{ json_encode(array_values($TotalServiceCost)) }}"
          data-price="{{ json_encode(array_values($TotalServicePrice)) }}"
          data-currency="{{ $Factory->curency }}"
          data-trans="{{ json_encode([
            'productTime' => __('general_content.total_product_time_by_service'),
            'settingTime' => __('general_content.total_setting_time_by_service'),
            'cost'        => __('general_content.total_cost_by_service'),
            'price'       => __('general_content.total_price_by_service'),
          ]) }}"
        ></div>
      </div>  
      <div class="tab-pane " id="Views">
        <x-adminlte-card title="{{ __('general_content.view_count_trans_key') }}" theme="primary" maximizable>
          @forelse($Quote->guestVisits as $visit)
          <p>{{ __('general_content.date_trans_key') }}: {{ $visit->GetPrettyCreatedAttribute() }}</p>
          @empty
          <p>{{ __('general_content.no_data_trans_key') }}</p>
          @endforelse
        </x-adminlte-card>
        @livewire('ChatLive', ['idItem' => $Quote->id, 'Class' => 'Quotes'])
      </div>
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Quote->id, 'type' => 'quote'])
      </div>
      @endif
      <div class="tab-pane " id="LinesImport">
        @include('include.alert-result')
        @if($Quote->statu == 1)
        <x-InfocalloutComponent note="{{ __('general_content.csv_quote_info_trans_key') }}"  />

        <form method="POST" action="{{ route('quotes.lines.import', ['idQuote'=>  $Quote->id]) }}" enctype="multipart/form-data">
          <x-adminlte-card title="{{ __('general_content.choose_file_trans_key') }}" theme="primary" maximizable>
              @csrf
              <div class="card-body">
                  {{-- Placeholder, sm size and prepend icon --}}
                  <x-adminlte-input-file name="import_file" igroup-size="sm" placeholder="{{ __('general_content.choose_csv_trans_key') }}">
                      <x-slot name="prependSlot">
                          <div class="input-group-text bg-lightblue">
                              <i class="fas fa-upload"></i>
                          </div>
                      </x-slot>
                  </x-adminlte-input-file>
              </div>
              <div class="card-body">
                  <div class="row">
                      <div class="col-4 text-end"><label class="col-form-label"> {{ __('general_content.header_line_ask_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input-switch name="header" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" is-checked="true" />
                      </div>
                  </div>
                  
                  @php
                    $fields = [
                        ['name' => 'code', 'label' => __('general_content.external_id_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                        ['name' => 'label', 'label' => __('general_content.label_trans_key'), 'icon' => 'fas fa-hashtag', 'color' => 'bg-red', 'required' => true],
                        ['name' => 'qty', 'label' => __('general_content.qty_trans_key'), 'icon' => 'fas fa-circle', 'color' => 'bg-blue', 'type' => 'number', 'required' => false],
                        ['name' => 'selling_price', 'label' => __('general_content.price_trans_key'), 'icon' => 'fas fa-cash-register', 'color' => 'bg-purple', 'required' => true],
                        ['name' => 'discount', 'label' => __('general_content.discount_trans_key'), 'icon' => 'fas fa-percentage', 'color' => 'bg-yellow', 'required' => false],
                        ['name' => 'delivery_date', 'label' => __('general_content.delivery_date_trans_key'), 'icon' => 'fas fa-calendar-alt', 'color' => 'bg-gray', 'required' => false],
                    ];
                  @endphp

                  @foreach ($fields as $field)
                  <div class="row">
                      <div class="col-4 text-end">
                          <label class="col-form-label">{{ $field['label'] }}</label>
                      </div>
                      <div class="col-8">
                          @if($field['required'] == true)
                              <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required  type="number" min=0>
                                  <x-slot name="appendSlot">
                                      <div class="input-group-text {{ $field['color'] }}">
                                          <i class="{{ $field['icon'] }}"></i>
                                      </div>
                                  </x-slot>
                              </x-adminlte-input>
                          @else
                              <x-adminlte-input name="{{ $field['name'] }}" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                                  <x-slot name="appendSlot">
                                      <div class="input-group-text {{ $field['color'] }}">
                                          <i class="{{ $field['icon'] }}"></i>
                                      </div>
                                  </x-slot>
                              </x-adminlte-input>
                          @endif
                      </div>
                  </div>
                  @endforeach

              </div>
              <x-slot name="footerSlot">
                <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
              </x-slot>
          </x-adminlte-card>
        </form>
        @else
        <x-adminlte-alert theme="info" title="{{ __('general_content.info_trans_key') }}">
            {{ __('general_content.info_statu_trans_key') }}
        </x-adminlte-alert>
        @endif
      </div>
      <div class="tab-pane " id="Logs">
        <x-adminlte-card title="{{ __('general_content.review_timeline_trans_key') }}" theme="info" icon="fas fa-history" class="mb-4">
          @php
            $reviewersById = $Reviewers->keyBy('id');
            $fieldLabels = [
              'reviewed_by' => __('general_content.reviewed_by_trans_key'),
              'reviewed_at' => __('general_content.review_date_trans_key'),
              'review_decision' => __('general_content.decision_trans_key'),
              'change_requested_by' => __('general_content.change_requested_by_trans_key'),
              'change_reason' => __('general_content.change_reason_trans_key'),
              'change_approved_at' => __('general_content.change_approved_at_trans_key'),
            ];
            $formatReviewValue = function ($field, $value) use ($reviewersById) {
                if (is_null($value) || $value === '') {
                    return __('general_content.undefined_trans_key');
                }

                if (in_array($field, ['reviewed_by', 'change_requested_by'], true)) {
                    return optional($reviewersById->get((int) $value))->name ?? __('general_content.undefined_trans_key');
                }

                if (in_array($field, ['reviewed_at', 'change_approved_at'], true)) {
                    try {
                        return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
                    } catch (\Exception $e) {
                        return $value;
                    }
                }

                if ($field === 'review_decision') {
                    return match ($value) {
                        'approved' => __('general_content.approved_trans_key'),
                        'rejected' => __('general_content.rejected_trans_key'),
                        'pending' => __('general_content.pending_trans_key'),
                        default => $value,
                    };
                }

                return $value;
            };
          @endphp
          @if($ReviewTimeline->isEmpty())
            <p class="mb-0 text-muted">{{ __('general_content.no_data_trans_key') }}</p>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>{{ __('general_content.created_trans_key') }}</th>
                    <th>{{ __('general_content.user_trans_key') }}</th>
                    <th>{{ __('general_content.description_trans_key') }}</th>
                    <th>{{ __('general_content.changes_trans_key') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($ReviewTimeline as $entry)
                    <tr>
                      <td>{{ optional($entry['created_at'])->format('d/m/Y H:i') }}</td>
                      <td>{{ $entry['causer'] ?? __('general_content.undefined_trans_key') }}</td>
                      <td>{{ $entry['description'] }}</td>
                      <td>
                        <table class="table table-sm mb-0">
                          <thead>
                            <tr>
                              <th>{{ __('general_content.label_trans_key') }}</th>
                              <th>{{ __('general_content.previous_trans_key') }}</th>
                              <th>{{ __('general_content.new_trans_key') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($entry['changes'] as $change)
                              <tr>
                                <td>{{ $fieldLabels[$change['field']] ?? \Illuminate\Support\Str::headline($change['field']) }}</td>
                                <td>{{ $formatReviewValue($change['field'], $change['old']) }}</td>
                                <td>{{ $formatReviewValue($change['field'], $change['new']) }}</td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </x-adminlte-card>
        @livewire('logs-viewer', ['subjectType' => 'App\Models\Workflow\Quotes', 'subjectId' => $Quote->id])
      </div>
  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->

@stop

@section('css')
  @viteReactRefresh
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')

  <script type="text/javascript">
    $('.custom-file-input').on('change',function(){
      // Obtient le nom du fichier sélectionné
      var fileName = $(this).val().split('\\').pop(); 
      // Sélectionne le label correspondant et met à jour son contenu
      $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
  </script>

  <script>
    function copyToClipboard(text) {
        // Create a temporary textarea element
        var tempTextarea = document.createElement("textarea");
        tempTextarea.value = text;
        
        // Add it to the document body
        document.body.appendChild(tempTextarea);
        
        // Select the text in the textarea
        tempTextarea.select();
        tempTextarea.setSelectionRange(0, 99999); // For mobile devices
        
        // Copy the text inside the textarea to clipboard
        document.execCommand("copy");
        
        // Remove the temporary textarea
        document.body.removeChild(tempTextarea);
        
        // Optionally, you can show a message indicating that the text has been copied
        // alert("Lien copié dans le presse-papier !");
    }
  </script>
@stop
