@extends('adminlte::page')

@section('title', __('general_content.orders_list_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
  <h1>{{ __('general_content.orders_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#List" data-toggle="tab">{{ __('general_content.orders_list_trans_key') }}</a></li> 
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Dashboard">
      <div class="row" id="dashboard-row">
        <div class="col-lg-4">
            <x-adminlte-small-box title="{{ $deliveredOrdersPercentage }}%" text="{{ __('general_content.order_delivered_trans_key') }}" icon="fas fa-shipping-fast" theme="success"/>
        </div>
        <div class="col-lg-4">
            <x-adminlte-small-box title="{{ $invoicedOrdersPercentage }} %" text="{{ __('general_content.order_invoiced_trans_key') }}" icon="fas fa-file-invoice-dollar" theme="info"/>
        </div>
        <div class="col-lg-4">
          <x-adminlte-small-box 
            title="{{ $serviceRate }}%" 
            text="{{ __('general_content.service_rate_trans_key') }}" 
            icon="fas fa-chart-line" 
            theme="primary"
              />
        </div>
      </div>
    
      <div class="row">
        <div class="col-lg-4 col-4">
          <x-adminlte-small-box title="{{ $remainingDeliveryOrder }}" 
            text="{{ __('general_content.remaining_month_trans_key') }}" 
            icon="icon fas fa-info"
            theme="danger" />

          <x-adminlte-small-box 
            title="{{ $averageProcessingTime }} {{ __('general_content.day_trans_key') }}" 
            text="{{ __('general_content.average_order_processing_time_trans_key') }}" 
            icon="icon fas fa-clock"
            theme="info" />

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
                          <p>{{ __('general_content.orders_trans_key') }}: {{ $customer->order_count }}</p> 
                      </div>
                  </div>
              @endforeach
          </div>
        </div>
        <div class="col-lg-8 col-8">
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
      </div>

      <div class="row">
        <div class="col-lg-4">
          <x-adminlte-small-box title="{{ $remainingInvoiceOrder }}" 
            text="{{ __('general_content.remaining_invoice_month_trans_key') }}" 
            icon="icon fas fa-info"
            theme="warning" />
          </div>
        <div class="col-lg-4">
          <x-adminlte-small-box title="{{ $lateOrdersCount }}" text="{{ __('general_content.late_orders_trans_key') }}" icon="fas fa-exclamation-triangle" theme="orange"/>
        </div>
        <div class="col-lg-4">
          <x-adminlte-small-box title="{{ $pendingDeliveries }}" text="{{ __('general_content.order_waiting_trans_key') }}" icon="fas fa-hourglass-half" theme="warning"/>
        </div>
    </div>

    </div>
    <div class="tab-pane" id="List">
      @livewire('orders-index')
    </div>
    <!-- /.card -->
  </div>
</div>
@stop

@section('css')
@stop

@section('js')
<script>
  
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
        {
          label               : 'Order from last year',
          borderColor         : 'rgba(240, 173, 78,0.5)',
          pointRadius          : 5,
          pointColor          : '#f0ad4e',
          pointStrokeColor    : 'rgba(240, 173, 78,1)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(240, 173, 78,1)',
          data                : [
                              @php ($j = 1)
                              @for($iM =1;$iM<=12;$iM++)
                                @foreach ($data['orderMonthlyRecapPreviousYear'] as $key => $item)
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


<!-- Drag and drop dashboard tiles -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
      const container = document.getElementById('dashboard-row');
      if (!container) return;
      // Ensure each child tile has an id
      Array.from(container.children).forEach((el, idx) => {
          if (!el.id) {
              el.id = 'tile-' + idx;
          }
      });
      // Apply saved order
      const saved = localStorage.getItem('dashboard-order');
      if (saved) {
          saved.split(',').forEach(id => {
              const el = document.getElementById(id);
              if (el) container.appendChild(el);
          });
      }
      // Init Sortable
      Sortable.create(container, {
          animation: 150,
          handle: '.card, .small-box',
          onEnd: () => {
              const order = Array.from(container.children).map(el => el.id);
              localStorage.setItem('dashboard-order', order.join(','));
          }
      });
  });
</script>

@stop