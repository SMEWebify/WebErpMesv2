@extends('adminlte::page')

@section('title', __('general_content.dashboard_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.dashboard_trans_key') }}</h1>
@stop

@section('content')
<div class="row">
  
    <!-- <div class="col-lg-2 col-6">
      <x-adminlte-small-box title="{{$data['suppliers_count']}}" 
                            text="{{ __('general_content.client_trans_key') }}" 
                            icon="far fa-building"
                            theme="info" 
                            url="{{ route('companies') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div> -->
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <x-adminlte-small-box title="{{ $data['customers_count'] }}" 
                            text="{{ __('general_content.client_trans_key') }}" 
                            icon="far fa-building"
                            theme="success" 
                            url="{{ route('companies') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <x-adminlte-small-box title="{{$data['suppliers_count']}}" 
                            text="{{ __('general_content.suppliers_trans_key') }}" 
                            icon="far fa-building"
                            theme="info" 
                            url="{{ route('companies') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <x-adminlte-small-box title="{{ $data['user_count'] }}" 
                            text="{{ __('general_content.users_trans_key') }}" 
                            icon="fas fa-user-plus"
                            theme="warning" 
                            url="{{ route('users') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <x-adminlte-small-box title="{{ $data['quotes_count'] }}" 
                            text="{{ __('general_content.quote_trans_key') }}" 
                            icon="fas fa-calculator"
                            theme="danger" 
                            url="{{ route('quotes') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <x-adminlte-small-box title="{{ $data['orders_count'] }}" 
                            text="{{ __('general_content.orders_trans_key') }}" 
                            icon="fas fa-shopping-cart"
                            theme="secondary" 
                            url="{{ route('orders') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-6">
      <x-adminlte-small-box title="{{ $data['quality_non_conformities_count'] }}"
                            text="{{ __('general_content.non_conformities_trans_key') }}"
                            icon="fas fa-times-circle"
                            theme="primary" 
                            url="{{ route('quality') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
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
              <h5 class="card-title">{{ __('general_content.monthly_recap_report_trans_key') }}</h5>
            </div>
            <div class="col-md-4">
              <h5 class="card-title">{{ __('general_content.monthly_recap_task_trans_key') }}</h5>
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
            <div class="col-md-6">
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
            <!-- /.col -->
            <div class="col-md-1 border-left">
              <p class="text-center">
                <strong><i class="icon fas fa-ban"></i> {{ __('general_content.incoming_orders_trans_key') }}</strong>
              </p>
              @forelse ($incomingOrders as $incomingOrder)
              <div class="progress-group alert alert-warning">
                <a href="{{ route('orders.show', ['id' => $incomingOrder->orders_id])}}"><i class="fas fa-calculator"></i></a> {{ $incomingOrder->order['code'] }}<br/>
                <a href="{{ route('production.calendar') }}"><i class="fas fa-calendar-alt"></i></a> {{ $incomingOrder->delivery_date }}
              </div>
              @empty
              <div class="progress-group">
                {{ __('general_content.no_coming_orders_trans_key') }}
              </div>
              <!-- /.progress-group -->
              @endforelse
              @if ($incomingOrdersCount >= 1)
              <div class="small-box bg-warning">
                <a href="{{ route('orders') }}" class="small-box-footer">+ {{ $incomingOrdersCount }} <i class="fas fa-arrow-circle-right"></i></a>
              </div>
              @endif
            </div>
            <!-- /.col -->
            <div class="col-md-1 border-left">
              <p class="text-center">
                <strong><i class="icon fas fa-ban"></i> {{ __('general_content.late_orders_trans_key') }}</strong>
              </p>
              @forelse ($LateOrders as $LateOrder)
              <div class="progress-group alert alert-danger">
                <a href="{{ route('orders.show', ['id' => $LateOrder->orders_id])}}"><i class="fas fa-calculator"></i></a> {{ $LateOrder->order['code'] }}<br/>
                <a href="{{ route('production.calendar') }}"><i class="fas fa-calendar-alt"></i></a> {{ $LateOrder->delivery_date }}
              </div>
              @empty
              <div class="progress-group">
                {{ __('general_content.no_late_orders_trans_key') }}
              </div>
              <!-- /.progress-group -->
              @endforelse
              @if ($LateOrdersCount >= 1)
              <div class="small-box bg-danger">
                <a href="{{ route('orders') }}" class="small-box-footer">+ {{ $LateOrdersCount }} <i class="fas fa-arrow-circle-right"></i></a>
              </div>
              @endif
            </div>
            <!-- /.col -->
            <div class="col-md-4 card">
              <p class="text-center">
                <strong>{{ __('general_content.goal_task_trans_key') }}</strong>
              </p>
              @forelse ($ServiceGoals as $ServiceGoal)
              <div class="progress-group">
                {{ $ServiceGoal->label }}
                <span class="float-right">{{ $ServiceGoal->tasks_count }}</span>
                <div class="progress progress-sm">
                  @php
                  foreach($Tasks as $Task){
                  if($Task->methods_id == $ServiceGoal->id){
                    $width = 100/($ServiceGoal->tasks_count/ $Task->total_task);
                    
                    if($Task->title ==  __('general_content.open_trans_key') ){ $class = 'bg-danger';}
                    elseif($Task->title ==  __('general_content.started_trans_key') ){ $class = 'bg-warning';}
                    elseif($Task->title ==  __('general_content.in_progress_trans_key') ){ $class = 'bg-primary';}
                    elseif($Task->title ==  __('general_content.finished_trans_key') ){ $class = 'bg-success';}
                    else{ $class = 'bg-info';}
                    echo '<div class="progress-bar '.   $class  .'" style="width: '.  $width  .'%">'. $Task->title .' - '. $Task->total_task .'</div>' ;
                  }
                }
                @endphp
                </div>
              </div>
              <!-- /.progress-group -->
              @empty
              <div class="progress-group">
                {{ __('general_content.no_task_trans_key') }}
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
                <!--<<span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 17%</span>-->
                <h5 class="description-header">{{ $orderTotalForCast[0]->orderTotalForCast }} {{ $Factory->curency }}</h5>
                <span class="description-text">{{ __('general_content.total_order_forcasted_trans_key') }}</span>
              </div>
              <!-- /.description-block -->
          </div>
          <!-- /.col -->
            <div class="col-sm-3 col-6">
              <div class="description-block border-right">
                <!--<<span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 17%</span>-->
                <h5 class="description-header">{{ $OrderTotalRevenue[0]->orderTotalRevenue }} {{ $Factory->curency }}</h5>
                <span class="description-text">{{ __('general_content.total_order_delivered_trans_key') }}</span>
              </div>
              <!-- /.description-block -->
            </div>
            <!-- /.col -->
            <div class="col-sm-3 col-6">
              <div class="description-block border-right">
                <!--<<span class="description-percentage text-warning"><i class="fas fa-caret-left"></i> 0%</span>-->
                <h5 class="description-header">{{ $OrderTotalRevenue[0]->orderTotalRevenue }} {{ $Factory->curency }}</h5>
                <span class="description-text">{{ __('general_content.total_invoiced_trans_key') }}</span>
              </div>
              <!-- /.description-block -->
            </div>
            <!-- /.col -->
            <div class="col-sm-3 col-6">
              <div class="description-block">
                <!--<span class="description-percentage text-danger"><i class="fas fa-caret-down"></i></span>-->
                {{ round($OrderTotalRevenue[0]->orderTotalRevenue / $EstimatedBudgets *100,2)}} %
                <h5 class="description-header"> {{ $OrderTotalRevenue[0]->orderTotalRevenue }}  /{{ $EstimatedBudgets }}</h5>
                <span class="description-text">{{ __('general_content.goal_trans_key') }}</span>
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
          <h3 class="card-title">{{ __('general_content.quote_transformation_trans_key') }}</h3>
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
                <canvas id="QuoteRate"  style="min-height: 310px; height: 100%; max-height: 100%; max-width: 100%;"></canvas>
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
    <!-- TABLE: LATEST QUOTES -->
    <div class="card">
      <div class="card-header border-transparent">
        <h3 class="card-title">{{ __('general_content.latest_quotes_trans_key') }}</h3>
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
              <th>{{ __('general_content.id_trans_key') }}</th>
              <th>{{ __('general_content.customer_trans_key') }}</th>
              <th>{{ __('general_content.label_trans_key')}}</th>
              <th>{{ __('general_content.status_trans_key') }}</th>
              <th>{{ __('general_content.total_price_trans_key') }}</th>
              <th>{{ __('general_content.created_at_trans_key') }}</th>
              <th>{{ __('general_content.assigned_trans_key') }}</th>
            </tr>
            </thead>
            <tbody>
              @forelse ($LastQuotes as $LastQuote)
              <tr>
                <td>
                  <x-QuoteButton id="{{ $LastQuote->id }}" code="{{ $LastQuote->code }}"  />
                </td>
                <td>
                  {{ $LastQuote->companie['label'] }}
                </td>    
                <td>
                  {{ $LastQuote->label }}
                </td>       
                <td>
                  @if(1 == $LastQuote->statu )   <span class="badge badge-info"> Open</span>@endif
                  @if(2 == $LastQuote->statu )  <span class="badge badge-warning">Send</span>@endif
                  @if(3 == $LastQuote->statu )  <span class="badge badge-success">Win</span>@endif
                  @if(4 == $LastQuote->statu )  <span class="badge badge-danger">Lost</span>@endif
                  @if(5 == $LastQuote->statu )  <span class="badge badge-secondary">Closed</span>@endif
                  @if(6 == $LastQuote->statu )   <span class="badge badge-secondary">Obsolete</span>@endif
                </td>
                <td>{{ $LastQuote->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                <td>{{ $LastQuote->GetPrettyCreatedAttribute() }}</td>
                <td>{{ $LastQuote->UserManagement['name'] }}</td>
              </tr>
              <!-- /.item -->
              @empty
              <tr>
                <td colspan="4">{{ __('general_content.no_quote_trans_key') }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <!-- /.table-responsive -->
      </div>
      <!-- /.card-body -->
      <div class="card-footer clearfix">
        <a href="{{ route('quotes') }}" class="btn btn-sm btn-secondary float-right">{{ __('general_content.view_all_trans_key') }}</a>
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
          <h3 class="card-title">{{ __('general_content.latest_orders_trans_key') }}</h3>
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
                <th>{{ __('general_content.id_trans_key') }}</th>
                <th>{{ __('general_content.customer_trans_key') }}</th>
                <th>{{ __('general_content.label_trans_key')}}</th>
                <th>{{ __('general_content.status_trans_key') }}</th>
                <th>{{ __('general_content.total_price_trans_key') }}</th>
                <th>{{ __('general_content.created_at_trans_key') }}</th>
                <th>{{ __('general_content.assigned_trans_key') }}</th>
              </tr>
              </thead>
              <tbody>
                @forelse ($LastOrders as $LastOrder)
                <tr>
                  <td>
                    <x-OrderButton id="{{ $LastOrder->id }}" code="{{ $LastOrder->code }}"  />
                  <td>
                    @if($LastOrder->type == 1 )
                    <x-CompanieButton id="{{ $LastOrder->companies_id }}" label="{{ $LastOrder->companie['label'] }}"  />
                    @else
                    {{ __('general_content.internal_order_trans_key') }}
                    @endif
                  </td>
                  <td>{{ $LastOrder->label }}</td>
                  <td>
                    @if(1 == $LastOrder->statu )  <span class="badge badge-info">{{ __('general_content.open_trans_key') }}</span>@endif
                    @if(2 == $LastOrder->statu )  <span class="badge badge-warning">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                    @if(3 == $LastOrder->statu )  <span class="badge badge-success">{{ __('general_content.delivered_trans_key') }}</span>@endif
                    @if(4 == $LastOrder->statu )  <span class="badge badge-danger">{{ __('general_content.partly_delivered_trans_key') }}</span>@endif
                  </td>
                  <td>{{ $LastOrder->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                  <td>{{ $LastOrder->GetPrettyCreatedAttribute() }}</td>
                  <td>{{ $LastOrder->UserManagement['name'] }}</td>
                </tr>
                <!-- /.item -->
                @empty
              <tr>
                <td colspan="4">{{ __('general_content.no_order_trans_key') }}</td>
              </tr>
              @endforelse
              </tbody>
            </table>
          </div>
          <!-- /.table-responsive -->
        </div>
        <!-- /.card-body -->
        <div class="card-footer clearfix">
          <a href="{{ route('orders') }}" class="btn btn-sm btn-secondary float-right">{{ __('general_content.view_all_trans_key') }}</a>
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
          <h3 class="card-title">{{ __('general_content.latest_products_trans_key') }}</h3>
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
              @if($LastProduct->picture)
                <div class="product-img">
                  <img src="{{ asset('/images/products/'. $LastProduct->picture) }}" alt="Product Image" class="img-size-50">
                </div>
              @endif
              <div class="product-info">
                <a href="{{ route('products.show', ['id' => $LastProduct->id])}}" class="product-title">{{ $LastProduct->label }} {{ $LastProduct->ind }}
                  @if($LastProduct->purchased_price)
                  <span class="badge badge-info float-right">{{ __('general_content.purchased_price_trans_key') }} {{ $LastProduct->purchased_price }}  {{ $Factory->curency }}</span></a>
                  @endif
                <span class="product-description">
                  {{ $LastProduct->code }}
                  @if($LastProduct->selling_price)
                  <span class="badge badge-success float-right">{{ __('general_content.selling_price_trans_key') }} {{ $LastProduct->selling_price }}  {{ $Factory->curency }}</span>
                  @endif
                </span>
              </div>
            </li>
            <!-- /.item -->
            @empty
              <li class="item">{{ __('general_content.no_product_trans_key') }}</li>
            @endforelse
          </ul>
        </div>
        <!-- /.card-body -->
        <div class="card-footer text-center">
            <a href="{{ route('products') }}" class="btn btn-sm btn-secondary">{{ __('general_content.view_all_trans_key') }}</a>
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
      labels  : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August','September','October','November','December' ],
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
          backgroundColor     : 'rgba(40, 167, 69, 1)',
          borderColor         : 'rgba(40, 167, 69, 1)',
          pointRadius         : 5,
          pointColor          : 'rgba(40, 167, 69, 1)',
          pointStrokeColor    : '#c1c7d1',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(40, 167, 69, 1)',
          data                :  [
                                @foreach ($data['estimatedBudget'] as $key => $item)
                                  "{{ $item->amount1 }}",
                                  "{{ $item->amount2 }}",
                                  "{{ $item->amount3 }}",
                                  "{{ $item->amount4 }}",
                                  "{{ $item->amount5 }}",
                                  "{{ $item->amount6 }}",
                                  "{{ $item->amount7 }}",
                                  "{{ $item->amount8 }}",
                                  "{{ $item->amount9 }}",
                                  "{{ $item->amount10 }}",
                                  "{{ $item->amount11 }}",
                                  "{{ $item->amount12 }}",
                                @endforeach 
                              ]
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

<script>

  $(document).ready(function() {

      let sBox = new _AdminLTE_SmallBox('sbUpdatable');

      let updateBox = () =>
      {
          // Stop loading animation.
          sBox.toggleLoading();

          // Update data.
          let rep = Math.floor(1000 * Math.random());
          let idx = rep < 100 ? 0 : (rep > 500 ? 2 : 1);
          let text = 'Reputation - ' + ['Basic', 'Silver', 'Gold'][idx];
          let icon = 'fas fa-medal ' + ['text-primary', 'text-light', 'text-warning'][idx];
          let url = ['url1', 'url2', 'url3'][idx];

          let data = {text, title: rep, icon, url};
          sBox.update(data);
      };

      let startUpdateProcedure = () =>
      {
          // Simulate loading procedure.
          sBox.toggleLoading();

          // Wait and update the data.
          setTimeout(updateBox, 2000);
      };

      setInterval(startUpdateProcedure, 10000);
  })

</script>

@stop