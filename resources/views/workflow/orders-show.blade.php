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
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.order_line_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Charts" data-toggle="tab">{{ __('general_content.charts_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#LinesImport" data-toggle="tab">{{ __('general_content.lines_import_trans_key') }}</a></li>
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
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <form method="POST" action="{{ route('orders.update', ['id' => $Order->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-6">
                        <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Order->code }}
                      </div>
                      <div class="col-6">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_order_trans_key'), 'Value' =>  $Order->label])
                      </div>
                    </div>
                  </div>
                @if($Order->type == 1)
                  @if($Order->companie['active'] == 1)
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">{{ __('general_content.customer_info_trans_key') }}</label>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        @include('include.form.form-select-companie',['companiesId' =>  $Order->companies_id])
                      </div>
                      <div class="col-6">
                        @include('include.form.form-input-customerInfo',['customerReference' =>  $Order->customer_reference])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        @include('include.form.form-select-adress',['adressId' =>   $Order->companies_addresses_id])
                      </div>
                      <div class="col-6">
                        @include('include.form.form-select-contact',['contactId' =>   $Order->companies_contacts_id])
                      </div>
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
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-6">
                        @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Order->accounting_payment_conditions_id])
                      </div>
                      <div class="col-6">
                          @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Order->accounting_payment_methods_id])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-6">
                          @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Order->accounting_deliveries_id])
                      </div>
                      <div class="col-6">
                        <label for="label">{{ __('general_content.delivery_date_trans_key') }}</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Order->validity_date }}">
                      </div>
                    </div>
                  </div>
                  @else
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-6">
                        <label for="label">{{ __('general_content.delivery_date_trans_key') }}</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Order->validity_date }}">
                      </div>
                    </div>
                  </div>
                  @endif
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Order->comment }}" />
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            @if($Order->quotes_id)
            <div class="card card-info">
              <div class="card-header ">
                <h3 class="card-title">{{ __('general_content.historical_trans_key') }}</h3>
              </div>
              <div class="card-body">
                {{ __('general_content.order_create_from_trans_key') }} <x-QuoteButton id="{{ $Order->quotes_id }}" code="{{ $Order->Quote->code }}"  />
              </div>
            </div>
            @endif
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <div class="card-body table-responsive p-0">
                @include('include.sub-total-price')
              </div>
            </div>
            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.options_trans_key') }}</h3>
              </div>
              <div class="card-body table-responsive p-0">
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
                        <a href="{{ route('print.manufacturing.instruction', ['Document' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print</a>
                      </td>
                    </tr>
                    
                    @if($Order->type == 1)
                    <tr>
                      <td colspan="2">
                        @if($Order->uuid)
                        <h5 class="mt-5 text-muted">{{ __('general_content.public_link_trans_key') }} </h5>
                        <p>
                          <input type="text" class="form-control"  value="{{ Request::root() }}/guest/order/{{  $Order->uuid }}">
                        </p>
                        @endif
                      </td>
                    </tr>
                    @endif
                </table>
              </div>
            </div>
            
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title"> {{ __('general_content.documents_trans_key') }} </h3>
              </div>
                <div class="card-body">
                      <form action="{{ route('file.store') }}" method="post" enctype="multipart/form-data">
                          @csrf
                          <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="far fa-file"></i></span>
                            </div>
                            <div class="custom-file">
                              <input type="hidden" name="order_id" value="{{ $Order->id }}" >
                              <input type="file" name="file" class="custom-file-input" id="chooseFile">
                              <label class="custom-file-label" for="chooseFile">{{ __('general_content.choose_file_trans_key') }}</label>
                            </div>
                            <div class="input-group-append">
                              <button type="submit" name="submit" class="btn btn-success">
                                {{ __('general_content.upload_trans_key') }} 
                              </button>
                            </div>
                          </div>
                      </form>
                    <h5 class="mt-5 text-muted">{{ __('general_content.attached_file_trans_key') }} </h5>
                    <ul class="list-unstyled">
                      @forelse ( $Order->files as $file)
                      <li>
                        <a href="{{ asset('/file/'. $file->name) }}" download="{{ $file->original_file_name }}" class="btn-link text-secondary">{{ $file->original_file_name }} -  <small>{{ $file->GetPrettySize() }}</small></a>
                      </li>
                      @empty
                        {{ __('general_content.no_data_trans_key') }}
                      @endforelse
                    </ul>
              </div>
            </div>
          </div>
        </div>
      </div>   
      <div class="tab-pane " id="Lines">
        @livewire('order-line', ['OrderId' => $Order->id, 'OrderStatu' => $Order->statu, 'OrderDelay' => $Order->validity_date])
      </div> 
      <div class="tab-pane" id="Charts">
        <div class="row">
          <div class="col-md-6">
            <div class="card-secondary">
              <div class="card-header">
                <h3 class="card-title"> {{ __('Total product time by service') }} </h3>
              </div>
              <div class="card-body">
                <canvas id="productDonutChart" width="400" height="400"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card-secondary">
              <div class="card-header">
                <h3 class="card-title"> {{ __('Total setting time by service') }} </h3>
              </div>
              <div class="card-body">
                <canvas id="settingDonutChart" width="400" height="400"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <div class="card-secondary">
              <div class="card-header">
                <h3 class="card-title"> {{ __('Total cost by service') }} </h3>
              </div>
              <div class="card-body">
                <canvas id="CostDonutChart" width="400" height="400"></canvas>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card-secondary">
              <div class="card-header">
                <h3 class="card-title"> {{ __('Total price by service') }} </h3>
              </div>
              <div class="card-body">
                <canvas id="PriceDonutChart" width="400" height="400"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>  
      <div class="tab-pane " id="LinesImport">
        @include('include.alert-result')
        @if($Order->statu == 1)
        <x-InfocalloutComponent note="{{ __('general_content.csv_quote_info_trans_key') }}"  />

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ __('general_content.choose_file_trans_key') }}</h3>
            </div>
            <form method="POST" action="{{ route('orders.import', ['idOrder'=>  $Order->id]) }}" enctype="multipart/form-data">
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
                <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
            </form>
        </div>
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
@stop