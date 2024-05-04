@extends('adminlte::page')

@section('title', __('general_content.quote_trans_key'))

@section('content_header')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <x-Content-header-previous-button  h1="{{ __('general_content.quote_trans_key') }} : {{  $Quote->code }}" previous="{{ $previousUrl }}" list="{{ route('quotes') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Quote" data-toggle="tab">{{ __('general_content.quote_info_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">{{ __('general_content.quote_line_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Charts" data-toggle="tab">{{ __('general_content.charts_trans_key') }}</a></li>
      <li class="nav-item"><a class="nav-link" href="#Views" data-toggle="tab">{{ __('general_content.guest_page_trans_key') }} ( {{  $Quote->visitsCount() }} )</a></li>
      @if(count($CustomFields)> 0)
      <li class="nav-item"><a class="nav-link" href="#CustomFields" data-toggle="tab">{{ __('general_content.custom_fields_trans_key') }}</a></li>
      @endif
      <li class="nav-item"><a class="nav-link" href="#LinesImport" data-toggle="tab">{{ __('general_content.lines_import_trans_key') }}</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Quote">
        @livewire('arrow-steps.arrow-quote', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.informations_trans_key') }}</h3>
              </div>
              <form method="POST" action="{{ route('quotes.update', ['id' => $Quote->id]) }}" enctype="multipart/form-data">
                @csrf 
                <div class="card card-body">
                  <div class="row">
                      <div class="col-6">
                        <label for="code" class="text-success">{{ __('general_content.external_id_trans_key') }}</label>  {{  $Quote->code }}
                      </div>
                      <div class="col-6">
                        @include('include.form.form-input-label',['label' =>__('general_content.name_quote_trans_key'), 'Value' =>  $Quote->label])
                      </div>
                    </div>
                  </div>
                @if($Quote->companie['active'] == 1)
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite" class="text-info">{{ __('general_content.customer_info_trans_key') }}</label>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-6">
                        @include('include.form.form-select-companie',['companiesId' =>  $Quote->companies_id])
                      </div>
                      <div class="col-6">
                        @include('include.form.form-input-customerInfo',['customerReference' =>  $Quote->customer_reference])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-6">
                        @include('include.form.form-select-adress',['adressId' =>   $Quote->companies_addresses_id])
                      </div>
                      <div class="col-6">
                        @include('include.form.form-select-contact',['contactId' =>   $Quote->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  @else
                  <input type="hidden" name="companies_id" value="{{ $Quote->companies_id }}">
                  <input type="hidden" name="customer_reference" value="{{ $Quote->customer_reference }}">
                  <input type="hidden" name="companies_addresses_id" value="{{ $Quote->companies_addresses_id }}">
                  <input type="hidden" name="companies_contacts_id" value="{{ $Quote->companies_contacts_id }}">
                  <x-adminlte-alert theme="info" title="Info">
                    The customer <x-CompanieButton id="{{ $Quote->companie['id'] }}" label="{{ $Quote->companie['label'] }}"  /> is currently disabled, you cannot change the you cannot change the customer name, contact and address.
                  </x-adminlte-alert>
                  @endif
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">{{ __('general_content.date_pay_info_trans_key') }}</label>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-6">
                        @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Quote->accounting_payment_conditions_id])
                      </div>
                      <div class="col-6">
                          @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Quote->accounting_payment_methods_id])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-6">
                          @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Quote->accounting_deliveries_id])
                      </div>
                      <div class="col-6">
                        <label for="label">{{ __('general_content.validity_date_trans_key') }}</label>
                        <div class="input-group">
                          <div class="input-group-text bg-gradient-secondary">
                            <i class="fas fa-calendar-day"></i>
                          </div>
                          <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Quote->validity_date }}">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="card card-body">
                    <div class="row">
                      <x-FormTextareaComment  comment="{{ $Quote->comment }}" />
                    </div>
                  </div>
                  <div class="card-footer">
                    <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.update_trans_key') }}" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card card-info">
              <div class="card-header ">
                <h3 class="card-title">{{ __('general_content.historical_trans_key') }}</h3>
              </div>
              <div class="card-body">
                @if($Quote->opportunities_id)
                <div class="text-muted">
                  <h3>{{__('general_content.opportunity_trans_key')}} #{{ $Quote->opportunities->label }} </h3><x-ButtonTextView route="{{ route('opportunities.show', ['id' => $Quote->opportunities_id])}}" />
                  
                  <p class="text-sm">{{ __('general_content.user_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->UserManagement['name'] }}</b>
                  </p>
                  <p class="text-sm">{{ __('general_content.probality_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->probality }} %</b> 
                  </p>
                  <p class="text-sm">{{ __('general_content.budget_trans_key') }}
                    <b class="d-block">{{ $Quote->opportunities->budget }}</b>
                  </p>
                </div>
                @endif
              </div>
            </div>
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
                  <tr>
                    <td style="width:50%">{{ __('general_content.quote_trans_key') }}</td>
                    <td><x-ButtonTextPDF route="{{ route('pdf.quote', ['Document' => $Quote->id])}}" /></td>
                  </tr>
                  @if($Quote->uuid)
                  <tr>
                    <td colspan="2">
                      <h5 class="mt-5 text-muted">{{ __('general_content.public_link_trans_key') }} </h5>
                      <p>
                        <input type="text" class="form-control"  value="{{ Request::root() }}/guest/quote/{{  $Quote->uuid }}">
                      </p>
                    </td>
                  </tr>
                  @endif
                  @forelse($Quote->Orders as $Order)
                  <tr>
                      <td style="width:50%"><x-OrderButton id="{{ $Order->id }}" code="{{ $Order->code }}"  /></td>
                      <td><x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" /></td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="2">
                        {{ __('general_content.no_data_trans_key') }}
                    </td>
                  </tr>
                  @endforelse
                </table>
              </div>
            </div>
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">{{ __('general_content.documents_trans_key') }}</h3>
              </div>
              <div class="card-body">
                <form action="{{ route('file.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="input-group">
                        <div class="input-group-prepend">
                          <span class="input-group-text"><i class="far fa-file"></i></span>
                        </div>
                        <div class="custom-file">
                          <input type="hidden" name="quotes_id" value="{{ $Quote->id }}" >
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
                  @forelse ( $Quote->files as $file)
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
        @livewire('quote-line', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu, 'QuoteDelay' => $Quote->validity_date])
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
                <h3 class="card-title">{{ __('Total price by service') }}</h3>
              </div>
              <div class="card-body">
                <canvas id="PriceDonutChart" width="400" height="400"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>  
      <div class="tab-pane " id="Views">
        <div class="card card-primary">
          <div class="card-header">
              <h3 class="card-title">{{ __('general_content.view_count_trans_key') }}</h3>
          </div>
          <div class="card-body">
              @forelse($Quote->guestVisits as $visit)
              <p>{{ __('general_content.date_trans_key') }}: {{ $visit->GetPrettyCreatedAttribute() }}</p>
              @empty
              <p>{{ __('general_content.no_data_trans_key') }}</p>
              @endforelse
          </div>
        </div>
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

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">{{ __('general_content.choose_file_trans_key') }}</h3>
            </div>
            <form method="POST" action="{{ route('quotes.import', ['idQuote'=>  $Quote->id]) }}" enctype="multipart/form-data">
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
@stop