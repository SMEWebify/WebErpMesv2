@extends('adminlte::page')

@section('title', __('general_content.quotes_list_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <h1>{{__('general_content.quotes_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#List" data-toggle="tab">{{ __('general_content.quotes_list_trans_key') }}</a></li> 
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Dashboard">
      <div class="row">
        <div class="col-lg-4">
            <x-adminlte-small-box 
              title="{{ $averageAmount}}" 
              text="{{ __('general_content.average_quote_amount') }}" 
              icon="fas fa-shipping-fast" 
              theme="success"/>
        </div>
        <div class="col-lg-4">
            <x-adminlte-small-box 
              title="{{ $conversionRate }} %" 
              text="{{ __('general_content.quote_conversion_rate') }}" 
              icon="fas fa-file-invoice-dollar" 
              theme="info"/>
        </div>
        <div class="col-lg-4">
          <x-adminlte-small-box 
              title="{{ $responseRate }}%" 
              text="{{ __('general_content.quote_response_rate') }}" 
              icon="fas fa-chart-line" 
              theme="primary"
              />
        </div>
      </div>
      <div class="row">
        <div class="col-md-3">
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="teal" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
            <canvas id="donutChart" width="400" height="400"></canvas>
          </x-adminlte-card>

          <div class="podium">
            @foreach ($topCustomers as $index => $customer)
                <div class="podium-place place-{{ $index + 1 }}">
                    <h3 class="text-center">
                        @if ($index == 0)
                            🥇
                        @elseif ($index == 1)
                            🥈
                        @elseif ($index == 2)
                            🥉
                        @endif
                    </h3>
                    <div class="customer-details text-center">
                        @if($customer->companie)
                        <strong>{{ $customer->companie->label }}</strong>
                        @else
                        <strong>internal</strong>
                        @endif
                        <p>{{ __('general_content.quote_trans_key') }}: {{ $customer->quote_count }}</p> 
                    </div>
                </div>
            @endforeach
          </div>
        </div>
        <div class="col-lg-6 col-6">
          <!-- CHART: TOTAL OVERVIEW -->
          <div class="col-lg-12 col-md-12">
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
        <div class="col-md-3">
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="orange" icon="fas fa-users text-white" collapsible removable maximizable>
            <div class="quote-kpi-list">
              @foreach ($quotesCountByUser as $userId => $quotes)
                <div class="quote-kpi-item border rounded p-2 mb-2">
                  <div class="font-weight-bold mb-2 text-truncate">{{ $quotes->first()->UserManagement->name ?? 'N/A' }}</div>
                  <div class="d-flex flex-wrap">
                    <span class="badge badge-info m-1">{{ __('general_content.open_trans_key') }}: {{ $quotes->where('statu', 1)->sum('total') ?? 0 }}</span>
                    <span class="badge badge-warning m-1">{{ __('general_content.send_trans_key') }}: {{ $quotes->where('statu', 2)->sum('total') ?? 0 }}</span>
                    <span class="badge badge-success m-1">{{ __('general_content.win_trans_key') }}: {{ $quotes->where('statu', 3)->sum('total') ?? 0 }}</span>
                    <span class="badge badge-danger m-1">{{ __('general_content.lost_trans_key') }}: {{ $quotes->where('statu', 4)->sum('total') ?? 0 }}</span>
                    <span class="badge badge-secondary m-1">{{ __('general_content.closed_trans_key') }}: {{ $quotes->where('statu', 5)->sum('total') ?? 0 }}</span>
                    <span class="badge badge-dark m-1">{{ __('general_content.obsolete_trans_key') }}: {{ $quotes->where('statu', 6)->sum('total') ?? 0 }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          </x-adminlte-card>
        </div>
      </div>
    </div>
    <div class="tab-pane" id="List">
      @livewire('quotes-index')
    </div>
    <!-- /.card -->
  </div>
</div>
@stop

@section('css')
<style>
  .quote-kpi-item {
    background-color: #f8f9fa;
  }

  .quote-kpi-item .badge {
    font-size: 0.8rem;
    white-space: normal;
    text-align: left;
  }
</style>
@stop

@section('js')
<script>
//-------------
//- PIE CHART -
//-------------
  var donutChartCanvas = $('#donutChart').get(0).getContext('2d')
  var donutData        = {
      labels: [
        @foreach ($data['quotesDataRate'] as $item)
              @if(1 == $item->statu )  "{{__('general_content.open_trans_key') }}", @endif
              @if(2 == $item->statu )  "{{__('general_content.send_trans_key') }}", @endif
              @if(3 == $item->statu )  "{{__('general_content.win_trans_key') }}", @endif
              @if(4 == $item->statu )  "{{__('general_content.lost_trans_key') }}", @endif
              @if(5 == $item->statu )  "{{__('general_content.closed_trans_key') }}", @endif
              @if(6 == $item->statu )  "{{__('general_content.obsolete_trans_key') }}", @endif
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
          label               : 'Quote forecast',
          borderColor         : 'rgba(60,141,188,0.5)',
          pointRadius          : 5,
          pointColor          : '#3b8bba',
          pointStrokeColor    : 'rgba(60,141,188,1)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(60,141,188,1)',
          data                : [
                              @php ($j = 1)
                              @for($iM =1;$iM<=12;$iM++)
                                @foreach ($data['quoteMonthlyRecap'] as $key => $item)
                                @php ($j = 1)
                                  @if($iM  == $item->month) 
                                  "{{ $item->quoteSum }}",
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
        {
          label               : 'Quote from last year',
          borderColor         : 'rgba(240, 173, 78,0.5)',
          pointRadius          : 5,
          pointColor          : '#f0ad4e',
          pointStrokeColor    : 'rgba(240, 173, 78,1)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(240, 173, 78,1)',
          data                : [
                              @php ($j = 1)
                              @for($iM =1;$iM<=12;$iM++)
                                @foreach ($data['quoteMonthlyRecapPreviousYear'] as $key => $item)
                                @php ($j = 1)
                                  @if($iM  == $item->month) 
                                  "{{ $item->quoteSum }}",
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
