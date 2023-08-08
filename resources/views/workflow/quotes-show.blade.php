@extends('adminlte::page')

@section('title', 'Quote')

@section('content_header')
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <x-Content-header-previous-button  h1="Quote : {{  $Quote->code }}" previous="{{ $previousUrl }}" list="{{ route('quotes') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Quote" data-toggle="tab">Quote info</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">Quote lines</a></li>
      <li class="nav-item"><a class="nav-link" href="#LinesImport" data-toggle="tab">Lines Import</a></li>
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
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('quotes.update', ['id' => $Quote->id]) }}" enctype="multipart/form-data">
                @csrf 
                <div class="card card-body">
                  <div class="row">
                      <div class="col-6">
                        <label for="code" class="text-success">External ID :</label>  {{  $Quote->code }}
                      </div>
                      <div class="col-6">
                        @include('include.form.form-input-label',['label' =>'Name of quote', 'Value' =>  $Quote->label])
                      </div>
                    </div>
                  </div>
                @if($Quote->companie['active'] == 1)
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite" class="text-info">Customer information</label>
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
                  <x-adminlte-alert theme="info" title="Info">
                    The customer <x-CompanieButton id="{{ $Quote->companie['id'] }}" label="{{ $Quote->companie['label'] }}"  /> is currently disabled, you cannot change the you cannot change the customer name, contact and address.
                  </x-adminlte-alert>
                  @endif
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Date & Payment information</label>
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
                        <label for="label">Validity date</label>
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
                    <x-adminlte-button class="btn-flat" type="submit" label="Update" theme="info" icon="fas fa-lg fa-save"/>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card card-secondary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <div class="card-body">
                @include('include.sub-total-price')
              </div>
            </div>
            <div class="card card-warning">
              <div class="card-header">
                <h3 class="card-title"> Options </h3>
              </div>
              <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <tr>
                        <td><x-ButtonTextPDF route="{{ route('pdf.quote', ['Document' => $Quote->id])}}" /></td>
                    </tr>
                </table>
              </div>
            </div>
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title"> Documents </h3>
              </div>
                <div class="card-body">
                    <form action="{{ route('file.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="input-group">
                            <div class="input-group-prepend">
                              <span class="input-group-text"><i class="far fa-file"></i></span>
                            </div>
                            <div class="custom-file">
                              <input type="hidden" name="quote_id" value="{{ $Quote->id }}" >
                              <input type="file" name="file" class="custom-file-input" id="chooseFile">
                              <label class="custom-file-label" for="chooseFile">Choose file</label>
                            </div>
                            <div class="input-group-append">
                              <button type="submit" name="submit" class="btn btn-success">
                                Upload
                              </button>
                            </div>
                          </div>
                    </form>
                    <h5 class="mt-5 text-muted">Attached files</h5>
                    <ul class="list-unstyled">
                      @forelse ( $Quote->files as $file)
                      <li>
                        <a href="{{ asset('/file/'. $file->name) }}" download="{{ $file->original_file_name }}" class="btn-link text-secondary">{{ $file->original_file_name }} -  <small>{{ $file->GetPrettySize() }}</small></a>
                      </li>
                      @empty
                        No file
                      @endforelse
                    </ul>
              </div>
            </div>
          </div>
        </div>
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
      <div class="tab-pane " id="Lines">
        @livewire('quote-line', ['QuoteId' => $Quote->id, 'QuoteStatu' => $Quote->statu, 'QuoteDelay' => $Quote->validity_date])
      </div>
      <div class="tab-pane " id="LinesImport">
        @include('include.alert-result')
        @if($Quote->statu == 1)
        <x-InfocalloutComponent note="The unit value are defined in the methods section and the value of the default VAT are defined in the accounting section. If there is no discount column, the default value will be 0 %."  />

        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Select your file</h3>
            </div>
            <form method="POST" action="{{ route('quotes.import', ['idQuote'=>  $Quote->id]) }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    {{-- Placeholder, sm size and prepend icon --}}
                    <x-adminlte-input-file name="import_file" igroup-size="sm" placeholder="Choose a .csv file...">
                        <x-slot name="prependSlot">
                            <div class="input-group-text bg-lightblue">
                                <i class="fas fa-upload"></i>
                            </div>
                        </x-slot>
                    </x-adminlte-input-file>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-4 text-right"><label class="col-form-label">Header line ?</label></div>
                        <div class="col-8">
                            <x-adminlte-input-switch name="header" data-on-text="YES" data-off-text="NO" data-on-color="teal" checked/>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-right"><label class="col-form-label">External ID</label></div>
                        <div class="col-8">
                            <x-adminlte-input name="code" placeholder="set CSV col number" required type="number">
                                <x-slot name="appendSlot">
                                    <div class="input-group-text bg-red">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-right"><label class="col-form-label">Description</label></div>
                        <div class="col-8">
                            <x-adminlte-input name="label" placeholder="set CSV col number" required type="number" min=0>
                                <x-slot name="appendSlot">
                                    <div class="input-group-text bg-red">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-right"><label class="col-form-label">Qty</label></div>
                        <div class="col-8">
                            <x-adminlte-input name="qty" placeholder="set CSV col number" required type="number" min=0>
                                <x-slot name="appendSlot">
                                  <div class="input-group-text bg-red">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-right"><label class="col-form-label">Selling price</label></div>
                        <div class="col-8">
                            <x-adminlte-input name="selling_price" placeholder="set CSV col number" required type="number" min=0>
                                <x-slot name="appendSlot">
                                  <div class="input-group-text bg-red">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-right"><label class="col-form-label">Discount</label></div>
                        <div class="col-8">
                            <x-adminlte-input name="discount" placeholder="set CSV col number"  type="number" min=0>
                                <x-slot name="appendSlot">
                                    <div class="input-group-text bg-blue">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                </x-slot>
                            </x-adminlte-input>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-4 text-right"><label class="col-form-label">Delivery date</label></div>
                        <div class="col-8">
                            <x-adminlte-input name="delivery_date" placeholder="set CSV col number"  type="number" min=0>
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
                    <x-adminlte-button class="btn-flat" type="submit" label="Submit" theme="danger" icon="fas fa-lg fa-save"/>
                </div>
            </form>
        </div>
        @else
        <x-adminlte-alert theme="info" title="Info">
            The document status does not allow adding / modifying / deleting lines.
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
  //-------------
  //- PIE CHART 1 -
  //-------------
  var donutChartCanvas = $('#productDonutChart').get(0).getContext('2d')
  var donutData        = {
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
    new Chart(donutChartCanvas, {
      type: 'pie',
      data: donutData,
      options: {
                  maintainAspectRatio : false,
                  responsive : true, 
              }
    })

  //-------------
  //- PIE CHART 2 -
  //-------------
  var donutChartCanvas = $('#settingDonutChart').get(0).getContext('2d')
  var donutData        = {
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
    new Chart(donutChartCanvas, {
      type: 'pie',
      data: donutData,
      options: {
                  maintainAspectRatio : false,
                  responsive : true, 
              }
    })

  //-------------
  //- PIE CHART 3 -
  //-------------
  var donutChartCanvas = $('#CostDonutChart').get(0).getContext('2d')
  var donutData        = {
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
    new Chart(donutChartCanvas, {
      type: 'pie',
      data: donutData,
      options: {
                  maintainAspectRatio : false,
                  responsive : true, 
              }
    })

  //-------------
  //- PIE CHART 4 -
  //-------------
  var donutChartCanvas = $('#PriceDonutChart').get(0).getContext('2d')
  var donutData        = {
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
    new Chart(donutChartCanvas, {
      type: 'pie',
      data: donutData,
      options: {
                  maintainAspectRatio : false,
                  responsive : true, 
              }
    })
  </script>
@stop