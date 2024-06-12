@extends('adminlte::page')

@section('title', __('general_content.requests_for_quotation_list_trans_key'))

@section('content_header')
  <div class="row mb-2">
      <h1>{{ __('general_content.requests_for_quotation_list_trans_key') }}</h1>
  </div>
@stop

@section('right-sidebar')

@section('content')
<div class="row">  
  <div class="col-md-3">
    <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="teal" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
      <canvas id="donutChart" width="400" height="400"></canvas>
    </x-adminlte-card>
    <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="warning" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
      <canvas id="donutChart" width="400" height="400"></canvas>
    </x-adminlte-card>
  </div>
  <div class="col-md-9">
    @livewire('purchases-quotation-index')
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
          @foreach ($data['purchasesQuotationDataRate'] as $item)
                @if(1 == $item->statu )  "In progress", @endif
                @if(2 == $item->statu )  "Sent", @endif
                @if(3 == $item->statu )  "{{ __('general_content.partly_received_trans_key') }}", @endif
                @if(4 == $item->statu )  "{{ __('general_content.rceived_trans_key') }}", @endif
                @if(5 == $item->statu )  "PO partly created", @endif
                @if(5 == $item->statu )  "PO Created", @endif
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($data['purchasesQuotationDataRate'] as $item)
                  "{{ $item->PurchaseQuotationCountRate }}",
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