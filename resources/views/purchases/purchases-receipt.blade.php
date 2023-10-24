@extends('adminlte::page')

@section('title', __('general_content.po_receipt_trans_key'))

@section('content_header')
  <div class="row mb-2">
    <h1>{{ __('general_content.po_receipt_trans_key') }}</h1>
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
          <canvas id="donutChart" width="400" height="400"></canvas>
        </div>
      </div>
    </div>
    <div class="col-md-9">
      @livewire('purchases-receipt-index')
    </div>
  </div>

<!-- /.card -->
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
          @foreach ($data['PurchaseReciepCountDataRate'] as $item)
                @if(1 == $item->statu )  "In progress", @endif
                @if(2 == $item->statu )  "Close", @endif
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($data['PurchaseReciepCountDataRate'] as $item)
                  "{{ $item->PurchaseReciepCountRate }}",
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