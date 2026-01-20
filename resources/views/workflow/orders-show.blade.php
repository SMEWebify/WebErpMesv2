@extends('adminlte::page')

@section('title', __('general_content.orders_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <script rel="stylesheet" src="{{ asset('js/switchtabNav.js') }}"></script>
  <x-Content-header-previous-button  h1="{{ __('general_content.orders_trans_key') }} : {{  $Order->code }}" previous="{{ $previousUrl }}" list="{{ route('orders') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script> 

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills" id="DocumentTabs">
      <li class="nav-item"><a class="nav-link" href="#Order" data-toggle="tab">{{ __('general_content.order_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.order_line_trans_key') }}  ({{ count($Order->OrderLines) }})</a></li>
      <li class="nav-item"><a class="nav-link" href="#Site" data-toggle="tab">{{ __('general_content.construction_site_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Charts" data-toggle="tab">{{ __('general_content.charts_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Bilan" data-toggle="tab">{{ __('general_content.business_Review_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#purchase" data-toggle="tab">{{ __('general_content.purchase_list_trans_key') }} ({{ $Order->purchase_lines_count }})</a></li>
      
      <!--<a class="nav-link" href="#Views" data-toggle="tab">{{ __('general_content.guest_page_trans_key') }}</a></li>-->
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }} ({{ count($CustomFields) }})</a></li>
      @endif
      @if($Order->type == 1)
      <li class="nav-item"><a class="nav-link" href="#LinesImport" data-toggle="tab">{{ __('general_content.lines_import_trans_key') }}</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#Logs" data-toggle="tab">Logs</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane" id="Order">
        @livewire('arrow-steps.arrow-order', ['OrderId' => $Order->id, 'OrderType' => $Order->type, 'OrderStatu' => $Order->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('orders.update', ['id' => $Order->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf
                <div class="row">
                  <div class="form-group col-md-6">
                    <input type="hidden" name="type" value="{{ $Order->type }}">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Order->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Order->GetshortCreatedAttribute() }}</p>
                    <p><label class="text-success">{{ __('general_content.total_weight_trans_key') }}</label>  {{ number_format($Order->total_weight, 3, '.', ' ') }} kg</p>
                  </div>
                  <div class="form-group col-md-6">
                    @include('include.form.form-input-label',['label' =>__('general_content.name_order_trans_key'), 'Value' =>  $Order->label])
                  </div>
                </div>
                @if($Order->type == 1)
                  @if($Order->companie['active'] == 1)
                  <div class="row">
                    <label for="companies_id" class="text-info">{{ __('general_content.customer_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      @if($Order->quotes_id  or $Order->statu != 1)
                      {{ __('general_content.companie_trans_key') }} :  <x-CompanieButton id="{{ $Order->companie['id'] }}" label="{{ $Order->companie['label'] }}"  />
                      <input type="hidden" name="companies_id" value="{{ $Order->companies_id }}">
                      @else
                        @include('include.form.form-select-companie',['companiesId' =>  $Order->companies_id])
                      @endif
                    </div>
                    <div class="form-group col-md-6">
                      @include('include.form.form-input-customerInfo',['customerReference' =>  $Order->customer_reference])
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-adress',['adressId' =>   $Order->companies_addresses_id])
                    </div>
                    <div class="col-6">
                      @include('include.form.form-select-contact',['contactId' =>   $Order->companies_contacts_id])
                    </div>
                  </div>
                  @else
                  <input type="hidden" name="companies_id" value="{{ $Order->companies_id }}">
                  <input type="hidden" name="customer_reference" value="{{ $Order->customer_reference }}">
                  <input type="hidden" name="companies_addresses_id" value="{{ $Order->companies_addresses_id }}">
                  <input type="hidden" name="companies_contacts_id" value="{{ $Order->companies_contacts_id }}">
                  <x-adminlte-alert theme="info" title="Info">
                    The customer <x-CompanieButton id="{{ $Order->companie['id'] }}" label="{{ $Order->companie['label'] }}"  /> is currently disabled, you cannot change the customer name, contact and address.
                  </x-adminlte-alert>
                  @endif
                  <div class="row">
                    <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                      @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Order->accounting_payment_conditions_id])
                    </div>
                    <div class="form-group col-md-6">
                        @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Order->accounting_payment_methods_id])
                    </div>
                  </div>
                  <div class="row">
                    <div class="form-group col-md-6">
                        @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Order->accounting_deliveries_id])
                    </div>
                    <div class="form-group col-md-6">
                      <label for="validity_date">{{ __('general_content.delivery_date_trans_key') }}</label>
                      <div class="input-group">
                        <div class="input-group-text bg-gradient-secondary">
                          <i class="fas fa-calendar-day"></i>
                        </div>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Order->validity_date }}">
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary" type="submit" name="apply_delivery_date" value="1" title="Appliquer aux lignes" aria-label="Appliquer aux lignes">
                            <i class="fas fa-level-down-alt"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                @else
                  <div class="row">
                    <div class="form-group col-md-6">
                      <label for="validity_date">{{ __('general_content.delivery_date_trans_key') }}</label>
                      <div class="input-group">
                        <div class="input-group-text bg-gradient-secondary">
                          <i class="fas fa-calendar-day"></i>
                        </div>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Order->validity_date }}">
                        <div class="input-group-append">
                          <button class="btn btn-outline-secondary" type="submit" name="apply_delivery_date" value="1" title="Appliquer aux lignes" aria-label="Appliquer aux lignes">
                            <i class="fas fa-level-down-alt"></i>
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                @endif
                <div class="row">
                  <div class="form-group col-md-6">
                    @include('include.form-select-methods-location',[
                        'methodsLocationId' => $Order->methods_locations_id,
                        'MethodsLocationsSelect' => $MethodsLocationsSelect])
                  </div>
                </div>
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Order->comment }}" />
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
                        <option value="{{ $user->id }}" @selected(old('reviewed_by', $Order->reviewed_by) == $user->id)>{{ $user->name }}</option>
                      @endforeach
                    </select>
                    @error('reviewed_by')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                  <div class="form-group col-md-6">
                    <label for="reviewed_at">{{ __('general_content.review_date_trans_key') }}</label>
                    <input type="datetime-local" class="form-control" name="reviewed_at" id="reviewed_at" value="{{ old('reviewed_at', optional($Order->reviewed_at)->format('Y-m-d\\TH:i')) }}">
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
                      <option value="pending" @selected(old('review_decision', $Order->review_decision) === 'pending')>{{ __('general_content.pending_trans_key') }}</option>
                      <option value="approved" @selected(old('review_decision', $Order->review_decision) === 'approved')>{{ __('general_content.approved_trans_key') }}</option>
                      <option value="rejected" @selected(old('review_decision', $Order->review_decision) === 'rejected')>{{ __('general_content.rejected_trans_key') }}</option>
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
                        <option value="{{ $user->id }}" @selected(old('change_requested_by', $Order->change_requested_by) == $user->id)>{{ $user->name }}</option>
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
                    <textarea class="form-control" name="change_reason" id="change_reason" rows="3">{{ old('change_reason', $Order->change_reason) }}</textarea>
                    @error('change_reason')
                      <span class="text-danger">{{ $message }}</span>
                    @enderror
                  </div>
                </div>
                <div class="row">
                  <div class="form-group col-md-6">
                    <label for="change_approved_at">{{ __('general_content.change_approved_at_trans_key') }}</label>
                    <input type="datetime-local" class="form-control" name="change_approved_at" id="change_approved_at" value="{{ old('change_approved_at', optional($Order->change_approved_at)->format('Y-m-d\\TH:i')) }}">
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
            
            <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="secondary" maximizable>
              @include('include.sub-total-price')
              @if($Order->Rating->isNotEmpty())
                @php
                    $Rating = $Order->Rating->toArray();
                @endphp
                
                <table class="table table-hover">
                  <tr>
                    <td colspan="2">
                      <label for="rating">{{ __('general_content.order_rate_trans_key') }}</label>
                      @for ($i = 1; $i <= 5; $i++)
                          @if ($i <= $Rating[0]['rating'])
                              <span class="badge badge-warning">&#9733;</span>
                          @else
                              <span class="badge badge-info">&#9734;</span>
                          @endif
                      @endfor
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                      {{ $Rating[0]['comment'] }}
                    </td>
                  </tr>
                </table>
              @endif  
            </x-adminlte-card>

            @if($Order->quotes_id)
            <x-adminlte-card title="{{ __('general_content.historical_trans_key') }}" theme="info" collapsible="collapsed" maximizable>
              {{ __('general_content.order_create_from_trans_key') }} <x-QuoteButton id="{{ $Order->quotes_id }}" code="{{ $Order->Quote->code }}"  />
            </x-adminlte-card>
            @endif
            
            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" collapsible="collapsed" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                    @php
                      $nest2prodUrl = rtrim((string) app(\App\Services\Settings\SettingsService::class)->get('n2p_base_url'), '/');
                    @endphp
                    @if($Order->type == 1)
                    <tr>
                        <td style="width:50%">{{  __('general_content.orders_trans_key') }}</td>
                        <td><x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" /></td>
                    </tr>
                    @if(config('mail.default') && config('mail.from.address'))
                    <tr>
                      <td style="width:50%">{{ __('general_content.email_trans_key') }}</td>
                      <td><x-ButtonTextEmail route="{{ route('email.create', ['type' => 'order', 'id' => $Order->id ]) }}" />
                      </td>
                    </tr>
                    @endif
                    <tr>
                      <td style="width:50%">{{  __('general_content.order_confirm_trans_key') }}</td>
                      <td><x-ButtonTextPDF route="{{ route('pdf.orders.confirm', ['Document' => $Order->id])}}" /></td>
                    </tr>
                    @endif
                    <tr>
                      <td style="width:50%">{{  __('general_content.mnaufacturing_instruction_trans_key') }}</td>
                      <td>
                        <a href="{{ route('print.manufacturing.instruction', ['Document' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-info btn-sm"><i class="fas fa-print"></i>Print</a>
                      </td>
                    </tr>
                    <tr>
                      <td style="width:50%">{{ __('general_content.calculate_date_task_trans_key') }}</td>
                      <td>
                        <form method="POST" action="{{ route('orders.calculate.task.dates', ['order' => $Order->id]) }}">
                          @csrf
                          <button class="btn btn-success btn-sm" type="submit">
                            <i class="fas fa-calendar-check"></i> {{ __('general_content.calculate_task_trans_key') }}
                          </button>
                        </form>
                      </td>
                    </tr>
                    @if($Order->statu != 1 && $nest2prodUrl !== '')
                    <tr>
                      <td style="width:50%">Nest2Prod</td>
                      <td>
                        <a href="{{ $nest2prodUrl }}/fr/orders/{{ rawurlencode($Order->code) }}" rel="noopener" target="_blank" class="btn btn-info btn-sm">
                          <i class="fas fa-external-link-alt"></i> {{ __('general_content.view_trans_key') }}
                        </a>
                      </td>
                    </tr>
                    @endif
                    
                    @if($Order->type == 1 && $Order->uuid)
                      <tr>
                        <td style="width:50%">{{ __('general_content.public_link_trans_key') }}</td>
                        <td>
                          <button class="btn btn-info btn-sm" onclick="copyToClipboard('{{ Request::root() }}/guest/order/{{ $Order->uuid }}')">
                            <i class="fas fa-copy"></i> {{ __('general_content.copy_trans_key') }} 
                          </button>
                        </td>
                      </tr>
                    @endif
                </table>
              </div>
            </x-adminlte-card>
            @include('include.file-store', ['inputName' => "orders_id",'inputValue' => $Order->id,'filesList' => $Order->files,])
            @include('include.email-list', ['mailsList'=> $Order->emailLogs,])
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="Lines">
        @livewire('order-line', ['OrderId' => $Order->id, 'OrderStatu' => $Order->statu, 'OrderDelay' => $Order->validity_date, 'OrderType' => $Order->type])
      </div>
      <div class="tab-pane" id="Site">
        @include('workflow.order-site-form', ['Order' => $Order, 'OrderSite' => $OrderSite])
        @include('workflow.order-site-implantations', ['Order' => $Order, 'OrderSite' => $OrderSite, 'OrderSiteImplantations' => $OrderSiteImplantations])
      </div>
      <div class="tab-pane" id="Charts">
        <div class="row">
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('general_content.total_product_time_by_service') }}" theme="secondary" maximizable>
              <canvas id="productDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('general_content.total_setting_time_by_service') }}" theme="secondary" maximizable>
              <canvas id="settingDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('general_content.total_cost_by_service') }}" theme="secondary" maximizable>
              <canvas id="CostDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('general_content.total_price_by_service') }}" theme="secondary" maximizable>
                <canvas id="PriceDonutChart" width="400" height="400"></canvas>
              </x-adminlte-card>
          </div>
        </div>
      </div> 
      <div class="tab-pane" id="Bilan">
        <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
          <div class="table-responsive">
            <table class="table table-bordered table-hover business-balance-table">
              <thead>
                <tr class="business-balance-table__group-row">
                    <th></th>
                    <th colspan="3" class="business-balance-table__group business-balance-table__subhead--info">{{ __('general_content.planned_trans_key') }}</th>
                    <th colspan="2" class="business-balance-table__group business-balance-table__subhead--info">{{ __('general_content.accomplished_trans_key') }}</th>
                    <th colspan="2" class="business-balance-table__group business-balance-table__subhead--info">{{ __('general_content.gap_trans_key') }}</th>
                </tr>
                  <tr class="business-balance-table__subhead-row">
                      <th class="business-balance-table__service-head">{{ __('general_content.service_trans_key') }}</th>
                      <th class="business-balance-table__subhead business-balance-table__subhead--info">{{ __('general_content.hours_trans_key') }}</th>
                      <th class="business-balance-table__subhead business-balance-table__subhead--info">{{ __('general_content.cost_trans_key') }} ({{ $Factory->curency }})</th>
                      <th class="business-balance-table__subhead business-balance-table__subhead--info">{{ __('general_content.selling_price_trans_key') }} ({{ $Factory->curency }})</th>
                      <th class="business-balance-table__subhead business-balance-table__subhead--info">{{ __('general_content.hours_trans_key') }}</th>
                      <th class="business-balance-table__subhead business-balance-table__subhead--info">{{ __('general_content.cost_trans_key') }} ({{ $Factory->curency }})</th>
                      <th class="business-balance-table__subhead business-balance-table__subhead--info">{{ __('general_content.hours_trans_key') }}</th>
                      <th class="business-balance-table__subhead business-balance-table__subhead--info">{{ __('general_content.cost_trans_key') }} ({{ $Factory->curency }})</th>
                  </tr>
              </thead>
              <tbody>
                  @forelse($businessBalance as $group => $data)
                      <tr>
                          <td class="business-balance-table__service-cell">{{ strtoupper($group) }}</td>
                          <td class="business-balance-table__cell business-balance-table__cell--info">{{ $data['total_hours'] }} h</td>
                          <td class="business-balance-table__cell business-balance-table__cell--info">{{ $data['total_display_cost'] }}</td>
                          <td class="business-balance-table__cell business-balance-table__cell--info">{{ $data['total_display_price'] }}</td>
                          <td class="business-balance-table__cell business-balance-table__cell--neutral">{{ $data['realized_hours'] }} h</td>
                          <td class="business-balance-table__cell business-balance-table__cell--neutral">{{ $data['realized_display_cost'] }}</td>
                          <td class="business-balance-table__cell {{ $data['difference_hours'] >= 0 ? 'business-balance-table__cell--gap-positive' : 'business-balance-table__cell--gap-negative' }}">{{ $data['difference_hours'] }} h</td>
                          <td class="business-balance-table__cell {{ $data['difference_cost'] >= 0 ? 'business-balance-table__cell--gap-positive' : 'business-balance-table__cell--gap-negative' }}">{{ $data['difference_display_cost'] }}</td>
                      </tr>
                  @empty
                  <x-EmptyDataLine col="14" text="{{ __('general_content.no_data_trans_key') }}"  />
                  @endforelse
              </tbody>
              <tfoot>
                <tr class="business-balance-table__total-row">
                  <td><strong>{{ __('general_content.total_trans_key') }}</strong></td>
                  <td><strong>{{ $businessBalancetotals['total_hours'] }} h</strong></td>
                  <td><strong>{{ $businessBalancetotals['total_display_cost'] }} </strong></td>
                  <td><strong>{{ $businessBalancetotals['total_display_price'] }} </strong></td>
                  <td><strong>{{ $businessBalancetotals['realized_hours'] }} h</strong></td>
                  <td><strong>{{ $businessBalancetotals['realized_display_cost'] }} </strong></td>
                  <td class="business-balance-table__cell {{ $businessBalancetotals['difference_hours'] >= 0 ? 'business-balance-table__cell--gap-positive' : 'business-balance-table__cell--gap-negative' }}"><strong>{{ $businessBalancetotals['difference_hours'] }} h</strong></td>
                  <td class="business-balance-table__cell {{ $businessBalancetotals['difference_cost'] >= 0 ? 'business-balance-table__cell--gap-positive' : 'business-balance-table__cell--gap-negative' }}"><strong>{{ $businessBalancetotals['difference_display_cost'] }} </strong></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </x-adminlte-card>
        
        @if($Order->type == 1)

        <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="warning" maximizable>
          <div class="row business-balance-info">
            <div class="col-lg-3 col-md-6">
              <div class="business-balance-info__card">
                <p class="business-balance-info__label">{{ __('general_content.progress_trans_key') }}</p>
                <p class="business-balance-info__value">{{ $Order->average_percent_progress_lines }} %</p>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="business-balance-info__card">
                <p class="business-balance-info__label">{{ __('general_content.amount_trans_key') }}</p>
                <p class="business-balance-info__value">{{ $totalPrices }}</p>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="business-balance-info__card">
                <p class="business-balance-info__label">{{ __('general_content.amount_of_invoice_trans_key') }}</p>
                <p class="business-balance-info__value">{{ $invoicedAmount }}</p>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="business-balance-info__card">
                <p class="business-balance-info__label">{{ __('general_content.still_invoiced_trans_key') }}</p>
                <p class="business-balance-info__value">
                  {{ $stillInvoiced }}
                  @if($totalPrices > 0 )<span class="business-balance-info__trend">({{ $percentageInvoiced }} %)</span>@endif
                </p>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="business-balance-info__card">
                <p class="business-balance-info__label">{{ __('general_content.payments_received_of_invoice_trans_key') }}</p>
                <p class="business-balance-info__value">{{ $receivedPayment }}</p>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="business-balance-info__card">
                <p class="business-balance-info__label">{{ __('general_content.forecast_margin_trans_key') }}</p>
                <p class="business-balance-info__value">
                  {{ $forecastMarginFormatted }}
                  @if($businessBalancetotals['total_cost'] > 0) <span class="business-balance-info__trend">({{ $forecastMarginPercentageFormatted }})</span> @endif
                </p>
              </div>
            </div>
            <div class="col-lg-3 col-md-6">
              <div class="business-balance-info__card">
                <p class="business-balance-info__label">{{ __('general_content.current_margin_trans_key') }}</p>
                <p class="business-balance-info__value">
                  {{ $currentMarginFormatted }}
                  @if($businessBalancetotals['realized_cost'] > 0) <span class="business-balance-info__trend">({{ $currentMarginPercentageFormatted }})</span> @endif
                </p>
              </div>
            </div>
          </div>
        </x-adminlte-card>
        
        <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="warning" maximizable>
          <p><strong>{{ __('Lead Time') }} :</strong> {{ $leadTime }} {{ __('general_content.days_trans_key') }}</p>   
        </x-adminlte-card>

        @endif
      </div> 
      <div class="tab-pane " id="Views">
      </div>
      <div class="tab-pane" id="purchase">
        <div class="table-responsive p-0">
          <table class="table table-hover">
              <thead>
                      <tr>
                          <th>{{ __('general_content.order_trans_key') }}</th>
                          <th>{{ __('general_content.label_trans_key') }}</th>
                          <th>{{__('general_content.task_trans_key') }}</th>
                          <th>{{ __('general_content.product_trans_key') }}</th>
                          <th>{{ __('general_content.qty_trans_key') }}</th>
                          <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                          <th>{{ __('general_content.qty_invoice_trans_key') }}</th>
                          <th>{{ __('general_content.price_trans_key') }}</th>
                          <th>{{ __('general_content.discount_trans_key') }}</th>
                          <th>{{ __('general_content.vat_trans_key') }}</th> 
                      </tr>
                  </thead>
                  <tbody>
                    @forelse ($Order->OrderLines as $orderLine)
                      @foreach ($orderLine->purchase_lines as $PurchaseLine)
                        <tr>
                          <td>
                            <a class="btn btn-primary btn-sm" href="{{ route('purchases.show', ['id' => $PurchaseLine->purchases_id])}}">
                              <i class="fas fa-folder"></i>
                              {{ $PurchaseLine->purchase->code }}
                            </a>
                          </td>
                          <td>
                              @if($PurchaseLine->tasks->OrderLines ?? null)
                                  {{ $PurchaseLine->tasks->OrderLines->label }}
                              @endif
                          </td>
                          <td>
                              @if($PurchaseLine->tasks_id ?? null)
                                  <a href="{{ route('production.task.statu.id', ['id' => $PurchaseLine->tasks->id]) }}" class="btn btn-sm btn-success">{{__('general_content.view_trans_key') }} </a>
                                  #{{ $PurchaseLine->tasks->id }} - {{ $PurchaseLine->tasks->label }}
                                  @if($PurchaseLine->tasks->component_id )
                                      - {{ $PurchaseLine->tasks->Component['label'] }}
                                  @endif
                              @else
                                  {{ $PurchaseLine->label }}
                              @endif
                          </td>
                          
                          <td>
                              @if($PurchaseLine->tasks_id ?? null)
                                  @if($PurchaseLine->tasks->component_id ) 
                                  <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseLine->tasks->component_id])}}" />
                                  @endif
                              @else
                                  @if($PurchaseLine->product_id ) 
                                      <x-ButtonTextView route="{{ route('products.show', ['id' => $PurchaseLine->product_id])}}" />
                                  @endif
                              @endif
                          </td>
                          <td>{{ $PurchaseLine->qty }}</td>
                          <td>{{ $PurchaseLine->receipt_qty }}</td>
                          <td>{{ $PurchaseLine->invoiced_qty }}</td>
                          <td>{{ $PurchaseLine->formatted_selling_price }}</td>
                          <td>{{ $PurchaseLine->discount }} %</td>
                          <td> 
                              @if($PurchaseLine->accounting_vats_id)
                              {{ $PurchaseLine->VAT['rate'] }} %
                              @else
                              -
                              @endif
                          </td>
                        </tr>
                      @endforeach
                    @empty
                    <x-EmptyDataLine col="11" text="{{ __('general_content.no_data_trans_key') }}"  />
                    @endforelse
                <tfoot>
                  <tr>
                      <th>{{ __('general_content.order_trans_key') }}</th>
                      <th>{{__('general_content.label_trans_key') }}</th>
                      <th>{{__('general_content.task_trans_key') }}</th>
                      <th>{{ __('general_content.product_trans_key') }}</th>
                      <th>{{ __('general_content.qty_trans_key') }}</th>
                      <th>{{ __('general_content.qty_reciept_trans_key') }}</th>
                      <th>{{ __('general_content.qty_invoice_trans_key') }}</th>
                      <th>{{ __('general_content.price_trans_key') }}</th>
                      <th>{{ __('general_content.discount_trans_key') }}</th>
                      <th>{{ __('general_content.vat_trans_key') }}</th>
                  </tr>
              </tfoot>
          </table>
        </div>
      </div>
      @if($CustomFields)
      <div class="tab-pane " id="CustomFields">
        @include('include.custom-fields-form', ['id' => $Order->id, 'type' => 'order'])
      </div>
      @endif
      <div class="tab-pane " id="LinesImport">
        @include('include.alert-result')
        @if($Order->statu == 1)
        <x-InfocalloutComponent note="{{ __('general_content.csv_quote_info_trans_key') }}"  />

        <form method="POST" action="{{ route('orders.lines.import', ['idOrder'=>  $Order->id]) }}" enctype="multipart/form-data">
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
                    <div class="col-4 text-right"><label class="col-form-label"> {{ __('general_content.header_line_ask_trans_key') }}</label></div>
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
                    <div class="col-4 text-right">
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
        <x-adminlte-alert theme="info" title="Info">
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
        @livewire('logs-viewer', ['subjectType' => 'App\Models\Workflow\Orders', 'subjectId' => $Order->id])
      </div>

  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
@stop

@section('css')
@stop

@section('js')
  <script type="text/javascript">
  $('a[href="#Charts"]').on('shown.bs.tab', function () {
    //-------------
    //- PIE CHART 1 -
    //-------------
    var productDonutChartCanvas  = $('#productDonutChart').get(0).getContext('2d')
    var productDonutData         = {
        labels: [
          @foreach ($TotalServiceProductTime as $item)
          "{{ $item[0] }} - {{ $item[1] }}h",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServiceProductTime as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServiceProductTime as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var productDonutChart = new Chart(productDonutChartCanvas , {
        type: 'pie',
        data: productDonutData ,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

    //-------------
    //- PIE CHART 2 -
    //-------------
    var settingDonutChartCanvas  = $('#settingDonutChart').get(0).getContext('2d')
    var settingDonutData         = {
        labels: [
          @foreach ($TotalServiceSettingTime as $item)
          "{{ $item[0] }} - {{ $item[1] }}h",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServiceSettingTime as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServiceSettingTime as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var settingDonutChart = new Chart(settingDonutChartCanvas , {
        type: 'pie',
        data: settingDonutData ,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

    //-------------
    //- PIE CHART 3 -
    //-------------
    var costDonutChartCanvas  = $('#CostDonutChart').get(0).getContext('2d')
    var costDonutData         = {
        labels: [
          @foreach ($TotalServiceCost as $item)
          "{{ $item[0] }} - {{ $item[1] }}{{ $Factory->curency }}",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServiceCost as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServiceCost as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var costDonutChart = new Chart(costDonutChartCanvas , {
        type: 'pie',
        data: costDonutData ,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

    //-------------
    //- PIE CHART 4 -
    //-------------
    var priceDonutChartCanvas  = $('#PriceDonutChart').get(0).getContext('2d')
    var priceDonutData        = {
        labels: [
          @foreach ($TotalServicePrice as $item)
          "{{ $item[0] }} - {{ $item[1] }}{{ $Factory->curency }}",
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($TotalServicePrice as $item)
                  "{{ $item[1] }}",
                  @endforeach
                ], 
                backgroundColor: [
                  @foreach ($TotalServicePrice as $item)
                  "{{ $item[2] }}",
                  @endforeach
                ],
          }
        ]
      }

      //Create pie or douhnut chart
      // You can switch between pie and douhnut using the method below.
      var priceDonutChart = new Chart(priceDonutChartCanvas , {
        type: 'pie',
        data: priceDonutData,
        options: {
                    maintainAspectRatio : false,
                    responsive : true, 
                }
      })

      $('a[href="#Charts"]').on('shown.bs.tab', function () {
          productDonutChart.update();
          settingDonutChart.update();
          costDonutChart.update();
          priceDonutChart.update();
      });
    });
  </script>
  
  <script type="text/javascript">
    $('.custom-file-input').on('change',function(){
      // Obtient le nom du fichier sélectionné
      var fileName = $(this).val().split('\\').pop(); 
      // Sélectionne le label correspondant et met à jour son contenu
      $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });
  </script>
  
  <script type="text/javascript">
    $(document).ready(function(){
      $('[data-toggle="tooltip"]').tooltip(); // Active les infobulles Bootstrap pour tous les éléments qui ont l'attribut data-toggle="tooltip"
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
