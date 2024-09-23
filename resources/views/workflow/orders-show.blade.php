@extends('adminlte::page')

@section('title', __('general_content.orders_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <x-Content-header-previous-button  h1="{{ __('general_content.orders_trans_key') }} : {{  $Order->code }}" previous="{{ $previousUrl }}" list="{{ route('orders') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script> 

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Order" data-toggle="tab">{{ __('general_content.order_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.order_line_trans_key') }}  ({{ count($Order->OrderLines) }})</a></li>
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
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Order">
        @livewire('arrow-steps.arrow-order', ['OrderId' => $Order->id, 'OrderType' => $Order->type, 'OrderStatu' => $Order->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <form method="POST" action="{{ route('orders.update', ['id' => $Order->id]) }}" enctype="multipart/form-data">
              <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="primary" maximizable>
                @csrf
                <div class="row">
                  <div class="form-group col-md-6">
                    <p><label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Order->code }}</p>
                    <p><label for="date" class="text-success">{{ __('general_content.date_trans_key') }}</label>  {{  $Order->GetshortCreatedAttribute() }}</p>
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
                      @if($Order->quotes_id)
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
                      </div>
                    </div>
                  </div>
                @endif
                <div class="row">
                  <x-FormTextareaComment  comment="{{ $Order->comment }}" />
                </div>
                <x-slot name="footerSlot">
                  <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                </x-slot>
              </x-adminlte-card>
            </form>
          </div>
          <div class="col-md-3">
            @if($Order->quotes_id)
            <x-adminlte-card title="{{ __('general_content.historical_trans_key') }}" theme="info" maximizable>
              {{ __('general_content.order_create_from_trans_key') }} <x-QuoteButton id="{{ $Order->quotes_id }}" code="{{ $Order->Quote->code }}"  />
            </x-adminlte-card>
            @endif
            
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

            <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
              <div class="table-responsive p-0">
                <table class="table table-hover">
                    @if($Order->type == 1)
                    <tr>
                        <td style="width:50%">{{  __('general_content.orders_trans_key') }}</td>
                        <td><x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" /></td>
                    </tr>
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
            @include('include.file-store', ['inputName' => "order_id",'inputValue' => $Order->id,'filesList' => $Order->files,])
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="Lines">
        @livewire('order-line', ['OrderId' => $Order->id, 'OrderStatu' => $Order->statu, 'OrderDelay' => $Order->validity_date, 'OrderType' => $Order->type])
      </div> 
      <div class="tab-pane" id="Charts">
        <div class="row">
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total product time by service') }}" theme="secondary" maximizable>
              <canvas id="productDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total setting time by service') }}" theme="secondary" maximizable>
              <canvas id="settingDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total cost by service') }}" theme="secondary" maximizable>
              <canvas id="CostDonutChart" width="400" height="400"></canvas>
            </x-adminlte-card>
          </div>
          <div class="col-md-6">
            <x-adminlte-card title="{{ __('Total price by service') }}" theme="secondary" maximizable>
                <canvas id="PriceDonutChart" width="400" height="400"></canvas>
              </x-adminlte-card>
          </div>
        </div>
      </div> 
      <div class="tab-pane" id="Bilan">
        <x-adminlte-card title="{{ __('general_content.options_trans_key') }}" theme="warning" maximizable>
          <table class="table table-bordered table-hover">
            <thead>
              <tr class="bg-dark disabled color-palette">
                  <th></th>
                  <th colspan="3" class="bg-info disabled color-palette">{{ __('general_content.manufacturing_range_trans_key') }}</th>
                  <th colspan="2" class="bg-danger disabled color-palette">{{ __('general_content.accomplished_trans_key') }}</th>
                  <th colspan="2" class="bg-orange disabled color-palette">{{ __('general_content.gap_trans_key') }}</th>
              </tr>
                <tr >
                    <th>{{ __('general_content.service_trans_key') }}</th>
                    <th class="bg-info disabled color-palette">{{ __('general_content.hours_trans_key') }}</th>
                    <th class="bg-info disabled color-palette">{{ __('general_content.cost_trans_key') }} ({{ $Factory->curency }})</th> 
                    <th class="bg-info disabled color-palette">{{ __('general_content.selling_price_trans_key') }} ({{ $Factory->curency }})</th>
                    <th class="bg-danger disabled color-palette">{{ __('general_content.hours_trans_key') }}</th>
                    <th class="bg-danger disabled color-palette">{{ __('general_content.cost_trans_key') }} ({{ $Factory->curency }})</th>
                    <th class="bg-orange disabled color-palette">{{ __('general_content.hours_trans_key') }}</th>
                    <th class="bg-orange disabled color-palette">{{ __('general_content.cost_trans_key') }} ({{ $Factory->curency }})</th>
                </tr>
            </thead>
            <tbody>
                @forelse($businessBalance as $group => $data)
                    <tr>
                        <td>{{ strtoupper($group) }}</td>
                        <td class="bg-info disabled color-palette">{{ $data['total_hours'] }} h</td>
                        <td class="bg-info disabled color-palette">{{ $data['total_cost'] }}</td>
                        <td class="bg-info disabled color-palette">{{ $data['total_price'] }}</td>
                        <td class="bg-danger disabled color-palette">{{ $data['realized_hours'] }} h</td>
                        <td class="bg-danger disabled color-palette">{{ $data['realized_cost'] }}</td>
                        <td class="bg-orange disabled color-palette">{{ $data['difference_hours'] }} h</td>
                        <td class="bg-orange disabled color-palette">{{ $data['difference_cost'] }}</td>
                    </tr>
                @empty
                <x-EmptyDataLine col="14" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
            </tbody>
            <tfoot>
              <tr class="bg-gray disabled color-palette">
                <td><strong>{{ __('general_content.total_trans_key') }}</strong></td>
                <td><strong>{{ number_format($businessBalancetotals['total_hours'], 2) }} h</strong></td>
                <td><strong>{{ number_format($businessBalancetotals['total_cost'], 2) }} {{ $Factory->curency }}</strong></td>
                <td><strong>{{ number_format($businessBalancetotals['total_price'], 2) }} {{ $Factory->curency }}</strong></td>
                <td><strong>{{ number_format($businessBalancetotals['realized_hours'], 2) }} h</strong></td>
                <td><strong>{{ number_format($businessBalancetotals['realized_cost'], 2) }} {{ $Factory->curency }}</strong></td>
                <td><strong>{{ number_format($businessBalancetotals['difference_hours'], 2) }} h</strong></td>
                <td><strong>{{ number_format($businessBalancetotals['difference_cost'], 2) }} {{ $Factory->curency }}</strong></td>
              </tr>
            </tfoot>
          </table>
        </x-adminlte-card>
        
        @if($Order->type == 1)
        <x-adminlte-card title="{{ __('general_content.informations_trans_key') }}" theme="warning" maximizable>
          <p><strong>{{ __('general_content.progress_trans_key') }} :</strong> {{ number_format($Order->getAveragePercentProgressLinesAttribute(), 2) }} %</p>
          <p><strong>{{ __('general_content.amount_trans_key') }} :</strong> {{ number_format($totalPrices, 2)  }} {{ $Factory->curency }}</p>

          <p><strong>{{ __('general_content.amount_of_invoice_trans_key') }} :</strong> {{ number_format($invoicedAmount, 2) }} {{ $Factory->curency }}</p>
          <p><strong>{{ __('general_content.still_invoiced_trans_key') }} :</strong> {{ number_format($totalPrices - $invoicedAmount, 2) }} {{ $Factory->curency }} @if($totalPrices > 0 )({{ number_format($invoicedAmount / $totalPrices * 100, 2) }} %)@endif</p>
          <p><strong>{{ __('general_content.payments_received_of_invoice_trans_key') }} :</strong> {{ number_format($receivedPayment, 2) }} {{ $Factory->curency }}</p>          
          
          <p><strong>{{ __('general_content.forecast_margin_trans_key') }} :</strong> {{ number_format($totalPrices - $businessBalancetotals['total_cost'], 2) }} {{ $Factory->curency }} @if($businessBalancetotals['total_cost'] > 0 )({{ number_format(($totalPrices-$businessBalancetotals['total_cost'] )/ $businessBalancetotals['total_cost'] * 100, 2) }} %)@endif</p>
          <p><strong>{{ __('general_content.current_margin_trans_key') }} :</strong> {{ number_format($totalPrices - $businessBalancetotals['realized_cost'], 2) }} {{ $Factory->curency }} @if($businessBalancetotals['realized_cost'] > 0 )({{ number_format(($totalPrices -$businessBalancetotals['realized_cost'])/ $businessBalancetotals['realized_cost'] * 100, 2) }} %)@endif</p>
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
                          <td>{{ $PurchaseLine->selling_price }} {{ $Factory->curency }}</td>
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

        <form method="POST" action="{{ route('orders.import', ['idOrder'=>  $Order->id]) }}" enctype="multipart/form-data">
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
                          <x-adminlte-input-switch name="header" data-on-text="{{ __('general_content.yes_trans_key') }}" data-off-text="{{ __('general_content.no_trans_key') }}" data-on-color="teal" checked/>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.external_id_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="code" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number">
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.description_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="label" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number" min=0>
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.qty_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="qty" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number" min=0>
                              <x-slot name="appendSlot">
                                <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.price_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="selling_price" placeholder="{{ __('general_content.set_csv_col_trans_key') }}" required type="number" min=0>
                              <x-slot name="appendSlot">
                                <div class="input-group-text bg-red">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.discount_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="discount" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-blue">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
                  <div class="row">
                      <div class="col-4 text-right"><label class="col-form-label">{{ __('general_content.delivery_date_trans_key') }}</label></div>
                      <div class="col-8">
                          <x-adminlte-input name="delivery_date" placeholder="{{ __('general_content.set_csv_col_trans_key') }}"  type="number" min=0>
                              <x-slot name="appendSlot">
                                  <div class="input-group-text bg-blue">
                                      <i class="fas fa-hashtag"></i>
                                  </div>
                              </x-slot>
                          </x-adminlte-input>
                      </div>
                  </div>
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