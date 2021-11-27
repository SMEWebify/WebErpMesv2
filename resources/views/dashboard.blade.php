@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard</h1>
@stop

@section('content')
<div class="row">
    <div class="col-lg-2 col-6">
      <!-- small box -->
      <div class="small-box bg-info">
        <div class="inner">
          <h3>{{$data['customers_count']}}</h3>
          <p>Clients</p>
        </div>
        <div class="icon">
          <i class="ion ion-bag"></i>
        </div>
        <a href="{{ route('companies') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <!-- small box -->
      <div class="small-box bg-success">
        <div class="inner">
          <h3>{{$data['suppliers_count']}}</h3>
          
          <p>Suppliers</p>
        </div>
        <div class="icon">
          <i class="ion ion-stats-bars"></i>
        </div>
        <a href="{{ route('companies') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <!-- small box -->
      <div class="small-box bg-warning">
        <div class="inner">
          <h3>{{$data['user_count']}}</h3>
          <p>User</p>
        </div>
        <div class="icon">
          <i class="ion ion-person-add"></i>
        </div>
        <a href="{{ route('users') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <!-- small box -->
      <div class="small-box bg-danger">
        <div class="inner">
          <h3>{{$data['quotes_count']}}</h3>
          <p>Quotes</p>
        </div>
        <div class="icon">
          <i class="fas fa-calculator"></i>
        </div>
        <a href="{{ route('quotes') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <!-- small box -->
      <div class="small-box bg-secondary">
        <div class="inner">
          <h3>{{$data['orders_count']}}</h3>
          <p>Orders</p>
        </div>
        <div class="icon">
          <i class="ion ion-bag"></i>
        </div>
        <a href="{{ route('orders') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <!-- small box -->
      <div class="small-box bg-primary">
        <div class="inner">
          <h3>{{$data['quality_non_conformities_count']}}</h3>
          <p>Non conformities</p>
        </div>
        <div class="icon">
          <i class="fas fa-times-circle"></i>
        </div>
        <a href="{{ route('quality') }}" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
      </div>
    </div>
    <!-- ./col -->
  </div>
  <!-- /.row -->

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-md-8">
              <h5 class="card-title">Monthly Recap Report</h5>
            </div>
            <div class="col-md-4">
              <h5 class="card-title">Monthly Recap Task</h5>
              <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                  <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-tool" data-card-widget="remove">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <div class="row">
            <div class="col-md-8">
              <p class="text-center">
                <strong>Sales: 1 Jan, 2014 - 30 Jul, 2014</strong>
              </p>
              <div class="chart">
                <!-- Sales Chart Canvas -->
                  <canvas id="lineChart" style="min-height: 250px; height: 100%; max-height: 100%; max-width: 100%;"></canvas>
              </div>
              <!-- /.chart-responsive -->
            </div>
            <!-- /.col -->
            <div class="col-md-4 card">
              <p class="text-center">
                <strong>Goal Task Completion</strong>
              </p>
              @forelse ($ServiceGoals as $ServiceGoal)
                @php
                    $randomNum = rand(0, 99);
                    if($randomNum < 100){ $class = 'bg-danger';}
                    if($randomNum < 75){ $class = 'bg-warning';}
                    if($randomNum < 50){ $class = 'bg-primary';}
                    if($randomNum < 25){ $class = 'bg-success';}
                @endphp
              <div class="progress-group">
                {{ $ServiceGoal->LABEL }}
                <span class="float-right"> {{ $randomNum }}/100</span>
                <div class="progress progress-sm">
                  <div class="progress-bar  {{ $class }}" style="width: {{ $randomNum }}%"></div>
                </div>
              </div>
              <!-- /.progress-group -->
              @empty
              <div class="progress-group">
                No service go to Methods for add.
              </div>
              <!-- /.progress-group -->
              @endforelse
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->
        </div>
        <!-- ./card-body -->
        <div class="card-footer">
          <div class="row">
            <div class="col-sm-3 col-6">
              <div class="description-block border-right">
                <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 17%</span>
                <h5 class="description-header">$35,210.43</h5>
                <span class="description-text">TOTAL REVENUE</span>
              </div>
              <!-- /.description-block -->
            </div>
            <!-- /.col -->
            <div class="col-sm-3 col-6">
              <div class="description-block border-right">
                <span class="description-percentage text-warning"><i class="fas fa-caret-left"></i> 0%</span>
                <h5 class="description-header">$10,390.90</h5>
                <span class="description-text">TOTAL COST</span>
              </div>
              <!-- /.description-block -->
            </div>
            <!-- /.col -->
            <div class="col-sm-3 col-6">
              <div class="description-block border-right">
                <span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 20%</span>
                <h5 class="description-header">$24,813.53</h5>
                <span class="description-text">TOTAL PROFIT</span>
              </div>
              <!-- /.description-block -->
            </div>
            <!-- /.col -->
            <div class="col-sm-3 col-6">
              <div class="description-block">
                <span class="description-percentage text-danger"><i class="fas fa-caret-down"></i> 18%</span>
                <h5 class="description-header">1200</h5>
                <span class="description-text">GOAL COMPLETIONS</span>
              </div>
              <!-- /.description-block -->
            </div>
          </div>
          <!-- /.row -->
        </div>
        <!-- /.card-footer -->
      </div>
      <!-- /.card -->
    </div>
    <!-- /.col -->
  </div>
  <!-- /.row -->

  <!-- Main row -->
  <div class="row">
    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Quote transformation rate</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
              <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
          <div class="row">
            <div class="col-md-8">
              <div class="chart-responsive">
                <canvas id="QuoteRate"  style="min-height: 250px; height: 100%; max-height: 100%; max-width: 100%;"></canvas>
              </div>
              <!-- ./chart-responsive -->
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
    </div>

  <div class="col-md-8">
    <!-- TABLE: LATEST ORDERS -->
    <div class="card">
      <div class="card-header border-transparent">
        <h3 class="card-title">Latest Quotes</h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse">
            <i class="fas fa-minus"></i>
          </button>
          <button type="button" class="btn btn-tool" data-card-widget="remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <!-- /.card-header -->
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table m-0">
            <thead>
            <tr>
              <th>Quote ID</th>
              <th>Customer</th>
              <th>Status</th>
              <th>Created at</th>
              <th>Assigned</th>
            </tr>
            </thead>
            <tbody>
              @forelse ($LastQuotes as $LastQuote)
              <tr>
                <td><a href="{{ route('quote.show', ['id' => $LastQuote->id])}}">{{ $LastQuote->CODE }}</a></td>
                <td>{{ $LastQuote->companie['LABEL'] }}</td>
                <td>
                  @if(1 == $LastQuote->statu )   <span class="badge badge-info"> Open</span>@endif
                  @if(2 == $LastQuote->statu )  <span class="badge badge-warning">Send</span>@endif
                  @if(3 == $LastQuote->statu )  <span class="badge badge-success">Win</span>@endif
                  @if(4 == $LastQuote->statu )  <span class="badge badge-danger">Lost</span>@endif
                  @if(5 == $LastQuote->statu )  <span class="badge badge-secondary">Closed</span>@endif
                  @if(6 == $LastQuote->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                </td>
                <td>{{ $LastQuote->GetPrettyCreatedAttribute() }}</td>
                <td>{{ $LastQuote->user['name'] }}</td>
              </tr>
              <!-- /.item -->
              @empty
              <tr>
                <td colspan="4">No quote, go quote page for add quote</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <!-- /.table-responsive -->
      </div>
      <!-- /.card-body -->
      <div class="card-footer clearfix">
        <a href="{{ route('quotes') }}" class="btn btn-sm btn-secondary float-right">View All Quote</a>
      </div>
      <!-- /.card-footer -->
    </div>
    <!-- /.card -->
  </div>
  <!-- /.col -->
</div>
<!-- /.row -->

  <!-- Main row -->
  <div class="row">
    <div class="col-md-8">
      <!-- TABLE: LATEST ORDERS -->
      <div class="card">
        <div class="card-header border-transparent">
          <h3 class="card-title">Latest Orders</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
              <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <!-- /.card-header -->
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table m-0">
              <thead>
              <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Status</th>
                <th>Created at</th>
                <th>Assigned</th>
              </tr>
              </thead>
              <tbody>
                @forelse ($LastOrders as $LastOrder)
                <tr>
                  <td><a href="{{ route('order.show', ['id' => $LastOrder->id])}}">{{ $LastOrder->CODE }}</a></td>
                  <td>{{ $LastOrder->companie['LABEL'] }}</td>
                  <td>
                    @if(1 == $LastOrder->statu )  <span class="badge badge-info"> Open</span>@endif
                    @if(2 == $LastOrder->statu )  <span class="badge badge-warning">In progress</span>@endif
                    @if(3 == $LastOrder->statu )  <span class="badge badge-success">Delivered</span>@endif
                    @if(4 == $LastOrder->statu )  <span class="badge badge-danger">Partly delivered</span>@endif
                  </td>
                  <td>{{ $LastOrder->GetPrettyCreatedAttribute() }}</td>
                  <td>{{ $LastOrder->user['name'] }}</td>
                </tr>
                <!-- /.item -->
                @empty
              <tr>
                <td colspan="4">No order, go order page for add order</td>
              </tr>
              @endforelse
              </tbody>
            </table>
          </div>
          <!-- /.table-responsive -->
        </div>
        <!-- /.card-body -->
        <div class="card-footer clearfix">
          <a href="javascript:void(0)" class="btn btn-sm btn-secondary float-right">View All Orders</a>
        </div>
        <!-- /.card-footer -->
      </div>
      <!-- /.card -->
    </div>
    <!-- /.col -->

    <!-- PRODUCT LIST -->
    <div class="col-md-4">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Recently Added Products</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
              <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <!-- /.card-header -->
        <div class="card">
          <ul class="products-list product-list-in-card pl-2 pr-2">  
            @forelse ($LastProducts as $LastProduct)
            <li class="item">
              <div class="product-img">
                <img src="{{ asset('/storage/images/products/'. $LastProduct->PICTURE) }} alt="Product Image" class="img-size-50">
              </div>
              <div class="product-info">
                <a href="{{ route('products.show', ['id' => $LastProduct->id])}}" class="product-title">{{ $LastProduct->LABEL }} {{ $LastProduct->IND }}
                  <span class="badge badge-info float-right">{{ $LastProduct->purchased_price }}</span></a>
                <span class="product-description">
                  {{ $LastProduct->CODE }}
                  <span class="badge badge-success float-right">{{ $LastProduct->selling_price }}</span>
                </span>
              </div>
            </li>
            <!-- /.item -->
            @empty
              <li class="item">No product, go product page for add item</li>
            @endforelse
          </ul>
        </div>
        <!-- /.card-body -->
        <div class="card-footer text-center">
            <a href="{{ route('products') }}" class="btn btn-sm btn-secondary">View All Products</a>
          </div>
        </div>
        <!-- /.card-footer -->
      </div>
    </div>
  </div>
  <!-- /.row -->
@stop

@section('css')

@stop

@section('js')
<script>
  //-------------
    //- BAR CHART -
    //-------------
  var ctx = document.getElementById('QuoteRate');
  var myChart = new Chart(ctx, {
      type: 'bar',
      data: {
          labels: [
            @foreach ($data['quotesDataRate'] as $item)
              @if(1 == $item->statu )  "Open", @endif
              @if(2 == $item->statu )  "Send", @endif
              @if(3 == $item->statu )  "Win", @endif
              @if(4 == $item->statu )  "Lost", @endif
              @if(5 == $item->statu )  "Closed", @endif
              @if(6 == $item->statu )  "Obsolete", @endif
            @endforeach
            ],
          datasets: [{
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
              borderColor: [
                  'rgba(23, 162, 184, 1)',
                  'rgba(255, 193, 7, 1)',
                  'rgba(40, 167, 69, 1)',
                  'rgba(220, 53, 69, 1)',
                  'rgba(108, 117, 125, 1)',
                  'rgba(0, 123, 255, 1)',
              ],
              borderWidth: 1
          }]
      },
      options: {
        responsive: true,
            legend: {
                display: false,
                labels: {
                    boxWidth: 20,
                    padding: 20
                }
            },
            scales: {
              yAxes: [{
                  ticks: {
                      beginAtZero: true
                  }
              }]
            }
      }
  });

  //--------------
  //- LINE CHART -
  //--------------
  // Get context with jQuery - using jQuery's .get() method.
  var areaChartCanvas = $('#lineChart').get(0).getContext('2d')
  var areaChartData = {
      labels  : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August','September','October','September','December ' ],
      datasets: [
        {
          label               : 'Order revenues',
          backgroundColor     : 'rgba(60,141,188,0.9)',
          borderColor         : 'rgba(60,141,188,0.8)',
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
          label               : 'Order targets ',
          backgroundColor     : 'rgba(210, 214, 222, 1)',
          borderColor         : 'rgba(210, 214, 222, 1)',
          pointRadius         : 5,
          pointColor          : 'rgba(210, 214, 222, 1)',
          pointStrokeColor    : '#c1c7d1',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(220,220,220,1)',
          data                : [650, 5900, 8000, 8100, 5600, 5500, 4000, 5900, 8000, 8100, 5600, 5500, 4000]
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