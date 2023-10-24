@extends('adminlte::page')

@section('title', __('general_content.deliverys_notes_list_trans_key'))

@section('content_header')
  <div class="row mb-2">
    <h1>{{__('general_content.deliverys_notes_list_trans_key') }}</h1>
  </div>
@stop

@section('right-sidebar')

@section('content')
<div class="container-fluid">
  <div class="row">
    <div class="col-md-3">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.statistiques_trans_key') }}</h3>
        </div>
        <div class="card-body">
          <canvas id="donutChart" width="400" height="400"></canvas>
        </div>
      </div>
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">{{ __('general_content.statistiques_trans_key') }}</h3>
        </div>
        <div class="card-body">
          <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-9">
        @livewire('deliverys-index')
    </div>
  </div>
</div>
@stop

@section('css')
@stop

@section('js')
<script>
  //-------------
  //- PIE CHART -
  //-------------
    var donutChartCanvas = $('#donutChart').get(0).getContext('2d')
    var donutData        = {
        labels: [
          @foreach ($data['deliverysDataRate'] as $item)
                @if(1 == $item->statu )  "In progress", @endif
                @if(2 == $item->statu )  "Sent", @endif
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($data['deliverysDataRate'] as $item)
                  "{{ $item->DeliveryCountRate }}",
                  @endforeach
                ], 
                backgroundColor: [
                    'rgba(23, 162, 184, 1)',
                    'rgba(40, 167, 69, 1)',
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
  
   //-------------
      //- BAR CHART -
      //-------------
      var barChartCanvas = $('#barChart').get(0).getContext('2d')
      var barChartData =  {
        labels  : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August','September','October','September','December ' ],
        datasets: [
          {
            label               : 'Total delivered',
            backgroundColor     : 'rgba(60,141,188,0.9)',
            borderColor         : 'rgba(60,141,188,0.8)',
            pointRadius          : false,
            pointColor          : '#3b8bba',
            pointStrokeColor    : 'rgba(60,141,188,1)',
            pointHighlightFill  : '#fff',
            pointHighlightStroke: 'rgba(60,141,188,1)',
            data                : [
                                @php ($j = 1)
                                @for($iM =1;$iM<=12;$iM++)
                                  @foreach ($data['deliveryMonthlyRecap'] as $key => $item)
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
  
      var barChartOptions = {
        responsive              : true,
        maintainAspectRatio     : false,
        datasetFill             : false
      }
  
      new Chart(barChartCanvas, {
        type: 'bar',
        data: barChartData,
        options: barChartOptions
      })
    </script>
@stop