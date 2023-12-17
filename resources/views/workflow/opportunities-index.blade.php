@extends('adminlte::page')

@section('title', __('general_content.opportunities_trans_key'))

@section('content_header')
<div class="row mb-2">
    <h1>{{ __('general_content.opportunities_trans_key')}}</h1>
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
        @livewire('opportunities-index')
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
        @foreach ($data['opportunitiesDataRate'] as $item)
              @if(1 == $item->statu )  "New", @endif
              @if(2 == $item->statu )  "Quote made", @endif
              @if(3 == $item->statu )  "Negotiation", @endif
              @if(4 == $item->statu )  "Closed-won", @endif
              @if(5 == $item->statu )  "Closed-lost", @endif
              @if(6 == $item->statu )  "Informational", @endif
        @endforeach
      ],
      datasets: [
        {
          data: [
                @foreach ($data['opportunitiesDataRate'] as $item)
                "{{ $item->OpportunitiesCountRate }}",
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
  </script>
@stop