@extends('adminlte::page')

@section('title', __('general_content.purchase_list_trans_key'))

@section('content_header')
  <div class="row mb-2">
    <h1>{{ __('general_content.purchase_list_trans_key') }}</h1>
  </div>
@stop

@section('right-sidebar')

@section('content')

<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#List" data-toggle="tab">{{ __('general_content.purchase_list_trans_key') }}</a></li> 
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Dashboard">
      <div class="row">
        <div class="col-md-3">
          <div class="card">
            <div class="card-header bg-info">
              <h3 class="card-title">{{ __('general_content.statistiques_trans_key') }}</h3>
            </div>
            <div class="card-body">
              <canvas id="donutChart" width="400" height="400"></canvas>
            </div>
          </div>
          <div class="card">
            <div class="card-header bg-warning">
              <h3 class="card-title">{{ __('general_content.statistiques_trans_key') }}</h3>
            </div>
            <div class="card-body">
              <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
            </div>
          </div>
        </div>
        
        <div class="col-md-3">
          <x-adminlte-small-box title=" {{ __('general_content.lines_count_trans_key') }}" text="{{ $totalPurchaseLineCount }}" icon="fas fa-shopping-cart text-white"
            theme="purple"/>

          <div class="card">
            <div class="card-header bg-primary">
              {{ __('general_content.top_rated_supplier_trans_key') }}
            </div>
            @foreach ($topRatedSuppliers as $supplier)
              <div class="row ">
                <div class="card-body">
                  <div class="text-center">
                      <h5>{{ $supplier->label }}</h5>
                      <p>
                      @for ($i = 1; $i <= 5; $i++)
                          @if ($i <= $supplier->averageRating())
                              <span class="badge badge-warning">&#9733;</span>
                          @else
                              <span class="badge badge-info">&#9734;</span>
                          @endif
                      @endfor
                    </p>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="col-md-3">
          <div class="card">
            <div class="card-header bg-secondary">
              {{ __('general_content.suppliers_shortest_times_trans_key') }}
            </div>
            <div class="card-body">
              <table class="table">
                  <thead>
                      <tr>
                          <th>{{ __('general_content.supplier_trans_key') }}</th>
                          <th>{{ __('general_content.delevery_time_trans_key') }}</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach ($top5FastestSuppliers as $supplier)
                      <tr>
                          <td>{{ $supplier->supplier_name }}</td>
                          <td>{{ round($supplier->avg_reception_delay, 2) }}</td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
            </div>
          </div>
          <x-adminlte-small-box title=" {{ __('general_content.total_price_trans_key') }}" text="{{ $totalPurchasesAmount }} {{ $Factory->curency }}" icon="fas fa-shopping-cart text-white"
          theme="danger"/>
          <div class="card">
            <div class="card-header bg-dark ">
              {{ __('general_content.suppliers_longest_times_trans_key') }}
            </div>
            <div class="card-body">
              <table class="table">
                  <thead>
                      <tr>
                          <th>{{ __('general_content.supplier_trans_key') }}</th>
                          <th>{{ __('general_content.delevery_time_trans_key') }}</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach ($top5SlowestSuppliers as $supplier)
                      <tr>
                          <td>{{ $supplier->supplier_name }}</td>
                          <td>{{ round($supplier->avg_reception_delay, 2) }}</td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="col-md-3">
          <x-adminlte-small-box title=" {{ __('general_content.average_purchase_price_trans_key') }}" text="{{ $averageAmount }} {{ $Factory->curency }}" icon="fas fa-chart-bar text-white"
                theme="teal"/>
          <div class="card">
            <div class="card-header bg-success">
              {{ __('general_content.most_purchased_products_trans_key') }}
            </div>
            <div class="card-body">
              <table class="table">
                  <thead>
                      <tr>
                          <th>{{ __('general_content.product_trans_key') }}</th>
                          <th>{{ __('general_content.qty_total_trans_key') }}</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach($topProducts as $product)
                      <tr>
                          <td>{{ $product->label }} </td>
                          <td>{{ $product->total_quantity }}</td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="tab-pane" id="List">
      @livewire('purchases-index')
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
          @foreach ($data['purchasesDataRate'] as $item)
                @if(1 == $item->statu )  "In progress", @endif
                @if(2 == $item->statu )  "{{ __('general_content.ordered_trans_key') }}", @endif
                @if(3 == $item->statu )  "{{ __('general_content.partly_received_trans_key') }}", @endif
                @if(4 == $item->statu )  "{{ __('general_content.rceived_trans_key') }}", @endif
                @if(5 == $item->statu )  "{{ __('general_content.canceled_trans_key') }}", @endif
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($data['purchasesDataRate'] as $item)
                  "{{ $item->PurchaseCountRate }}",
                  @endforeach
                ], 
                backgroundColor: [
                  'rgba(23, 162, 184, 1)',
                  'rgba(255, 193, 7, 1)',
                  'rgba(40, 167, 69, 1)',
                  'rgba(220, 53, 69, 1)',
                  'rgba(108, 117, 125, 1)',
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
            label               : 'Total purchase',
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
                                  @foreach ($data['purchaseMonthlyRecap'] as $key => $item)
                                  @php ($j = 1)
                                    @if($iM  == $item->month) 
                                    "{{ $item->purchaseSum }}",
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