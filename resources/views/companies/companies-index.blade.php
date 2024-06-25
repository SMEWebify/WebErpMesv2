@extends('adminlte::page')

@section('title', __('general_content.companies_list_trans_key'))

@section('content_header')
  <div class="row mb-2">
      <h1>{{__('general_content.companies_list_trans_key') }}</h1>
  </div>
@stop

@section('right-sidebar')

@section('content')
  @include('include.alert-result')
  <div class="row">
    <div class="col-md-3">
      <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="teal" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
        <canvas id="donutChart" width="400" height="400"></canvas>
      </x-adminlte-card>
      <x-adminlte-card title="{{ __('general_content.latest_companies_trans_key') }}" theme="warning" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
        <div class="table-responsive">
          <table class="table m-0">
            <thead>
            <tr>
              <th>{{__('general_content.id_trans_key') }}</th>
              <th>{{__('general_content.label_trans_key') }}</th>
              <th></th>
            </tr>
            </thead>
            <tbody>
              @forelse ($LastComapnies as $LastComapnie)
              <tr>
                <td>{{ $LastComapnie->code }}</td>
                <td>{{ $LastComapnie->label }}</td>
                <td><x-ButtonTextView route="{{ route('companies.show', ['id' => $LastComapnie->id])}}" /></td>
              </tr>
              <!-- /.item -->
              @empty
            <tr>
              <x-EmptyDataLine col="4" text={{ __('general_content.no_data_trans_key') }}"  />
            </tr>
            @endforelse
            </tbody>
          </table>
        </div>
        <!-- /.table-responsive -->
      </x-adminlte-card>
    </div>
    <div class="col-md-9">
        @livewire('companies-lines')
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
          'Client',
          'Prospect',
          'Supplier',
          'Client & Supplier',
      ],
      datasets: [
        {
          data: ["{{ $data['ClientCountRate'] }}","{{ $data['ProspectCountRate'] }}","{{ $data['SupplierCountRate'] }}","{{ $data['ClientSupplierCountRate'] }}"], 
          backgroundColor : ['#f56954', '#00a65a', '#f39c12','#00c0ef',],
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