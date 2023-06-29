@extends('adminlte::page')

@section('title', 'Order')

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <x-Content-header-previous-button  h1="Order : {{  $Order->code }}" previous="{{ $previousUrl }}" list="{{ route('orders') }}" next="{{ $nextUrl }}"/>
@stop

@section('right-sidebar')

@section('content')

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Order" data-toggle="tab">Order info</a></li>
      <li class="nav-item"><a class="nav-link" href="#Lines" data-toggle="tab">Order lines</a></li>
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="card-body">
    <div class="tab-content">
      <div class="tab-pane active" id="Order">
        @livewire('arrow-steps.arrow-order', ['OrderId' => $Order->id, 'OrderStatu' => $Order->statu])
        <div class="row">
          <div class="col-md-9">
            @include('include.alert-result')
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"> Informations </h3>
              </div>
              <form method="POST" action="{{ route('orders.update', ['id' => $Order->id]) }}" enctype="multipart/form-data">
                @csrf
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-3">
                        <label for="code" class="text-success">External ID :</label>  {{  $Order->code }}
                      </div>
                      <div class="col-3">
                        @include('include.form.form-input-label',['label' =>'Name of order', 'Value' =>  $Order->label])
                      </div>
                    </div>
                  </div>
                @if($Order->type == 1)
                  @if($Order->companie['active'] == 1)
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Customer information</label>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-companie',['companiesId' =>  $Order->companies_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-input-customerInfo',['customerReference' =>  $Order->customer_reference])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-adress',['adressId' =>   $Order->companies_addresses_id])
                      </div>
                      <div class="col-5">
                        @include('include.form.form-select-contact',['contactId' =>   $Order->companies_contacts_id])
                      </div>
                    </div>
                  </div>
                  @else
                  <x-adminlte-alert theme="info" title="Info">
                    The customer <x-CompanieButton id="{{ $Order->companie['id'] }}" label="{{ $Order->companie['label'] }}"  /> is currently disabled, you cannot change the customer and order address
                  </x-adminlte-alert>
                  @endif
                  <div class="card card-body">
                    <div class="row">
                      <label for="InputWebSite">Date & Payment information</label>
                    </div>
                    <hr>
                    <div class="row">
                      <div class="col-5">
                        @include('include.form.form-select-paymentCondition',['accountingPaymentConditionsId' =>   $Order->accounting_payment_conditions_id])
                      </div>
                      <div class="col-5">
                          @include('include.form.form-select-paymentMethods',['accountingPaymentMethodsId' =>   $Order->accounting_payment_methods_id])
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-5">
                          @include('include.form.form-select-delivery',['accountingDeliveriesId' =>   $Order->accounting_deliveries_id])
                      </div>
                      <div class="col-5">
                        <label for="label">Delevery date</label>
                        <input type="date" class="form-control" name="validity_date"  id="validity_date" value="{{  $Order->validity_date }}">
                      </div>
                    </div>
                  </div>
                  @else
                  <div class="card card-body">
                    <div class="row">
                      <div class="col-5">
                        <label for="label">Delevery date</label>
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
                  <div class="modal-footer">
                    <button type="Submit" class="btn btn-primary">Save changes</button>
                  </div>
              </form>
            </div>
          </div>
          <div class="col-md-3">
            @if($Order->quote_id)
            <div class="card card-info">
              <div class="card-header ">
                <h3 class="card-title"> Historical </h3>
              </div>
              <div class="card-body">
                Order Create from <x-QuoteButton id="{{ $Order->quote_id }}" code="{{ $Order->Quote->code }}"  />
              </div>
            </div>
            @endif
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
                    @if($Order->type == 1)
                    <tr>
                        <td style="width:50%"> 
                          Order 
                        </td>
                        <td>
                          <x-ButtonTextPDF route="{{ route('pdf.order', ['Document' => $Order->id])}}" />
                        </td>
                    </tr>
                    <tr>
                      <td style="width:50%">
                        Order confirm </td>
                      <td>
                        <x-ButtonTextPDF route="{{ route('pdf.orders.confirm', ['Document' => $Order->id])}}" />
                      </td>
                    </tr>
                    @endif
                    <tr>
                      <td style="width:50%">
                        Manufacturing instruction 
                      </td>
                      <td>
                        <a href="{{ route('print.manufacturing.instruction', ['Document' => $Order->id])}}" rel="noopener" target="_blank" class="btn btn-default"><i class="fas fa-print"></i>Print</a>
                      </td>
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
                              <input type="hidden" name="order_id" value="{{ $Order->id }}" >
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
                      @forelse ( $Order->files as $file)
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
        @livewire('order-line', ['OrderId' => $Order->id, 'OrderStatu' => $Order->statu, 'OrderDelay' => $Order->validity_date])
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