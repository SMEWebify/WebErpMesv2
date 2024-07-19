@extends('adminlte::page')

@section('title', __('general_content.dashboard_trans_key'))

@section('content_header')
    <h1>{{ __('general_content.dashboard_trans_key') }}</h1>
@stop

@section('content')
<style>
  .full-height {
      height: 100%;
  }
  .row-extend {
      display: flex;
      flex-direction: column;
      justify-content: space-between;
  }
  .team-mood-item {
      display: inline-block;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      margin-right: 10px;
      white-space: nowrap;
  }
</style>

  @if($userRoleCount < 1)
  <div class="card">
    <div class="card-body">
        <x-adminlte-alert theme="info" title="Info">
          your account currently have no role defined and the menu has a reduced display.  Contact the administrator or use user demo login for demo page =>
      LOGIN: contact@wem-project.org
      PASSWORD: password
        </x-adminlte-alert>
    </div>
  </div>
  @endif

  <div class="row ">
    <div class="col-lg-2">
      <x-adminlte-small-box title="{{$data['customers_count']}}"
                            text="{{ __('general_content.new_client_trans_key') }}" 
                            icon="far fa-building"
                            theme="info" 
                            url="{{ route('companies') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-md-2">
      <x-adminlte-small-box title="{{ $data['suppliers_count'] }}"
                            text="{{ __('general_content.suppliers_trans_key') }}" 
                            icon="far fa-building"
                            theme="success" 
                            url="{{ route('companies') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-md-2">
      <x-adminlte-small-box title="{{ $data['quotes_count'] }}" 
                            text="{{ __('general_content.quote_trans_key') }}" 
                            icon="fas fa-calculator"
                            theme="primary" 
                            url="{{ route('quotes') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-2 col-md-2">
      <x-adminlte-small-box title="{{ $data['orders_count'] }}" 
                            text="{{ __('general_content.orders_trans_key') }}" 
                            icon="fas fa-shopping-cart"
                            theme="yellow" 
                            url="{{ route('orders') }}" 
                            url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- ./col -->
    <div class="col-lg-4 col-md-4">
      <x-adminlte-card title="{{ __('general_content.niko_niko_team_trans_key') }}" theme="lime"> 
        @livewire('mood-tracker')
      </x-adminlte-card>
    </div>
    <!-- ./col -->

    <!-- TABLE: INCOMING AND LATE ORDER -->
    <div class="col-md-12">
      <x-adminlte-card title="{{ __('general_content.order_to_be_delivered_trans_key') }}" theme="orange" icon="fas fa-shopping-cart text-white" collapsible="collapsed" removable maximizable>
        <div class="row">
            <div class="col-md-12">
              <div class="row">
                <div class="col-md-1">
                  <a href="{{ route('orders') }}" class="btn btn-lg btn-warning">
                    {{ __('general_content.incoming_orders_trans_key') }}
                      <i class="icon fas fa-ban"></i><br/>
                  </a>
                </div>
                @forelse ($incomingOrders as $incomingOrder)
                <div class="col-md-1">
                  <div class="progress-group alert alert-warning">
                      <a href="{{ route('orders.show', ['id' => $incomingOrder->orders_id]) }}"><i class="fas fa-calculator"></i></a> {{ $incomingOrder->order['code'] }}<br/>
                      <a href="{{ route('production.calendar.orders') }}"><i class="fas fa-calendar-alt"></i></a> {{ $incomingOrder->delivery_date }}
                  </div>
                </div>
                <!-- /.col-md-1 -->
                @empty
                <div class="col-md-1">
                    <div class="progress-group">
                    {{ __('general_content.no_coming_orders_trans_key') }}
                    </div>
                </div>
                <!-- /.progress-group -->
                @endforelse

                @if ($incomingOrdersCount >= 1)
                <div class="col-md-1">
                  <a href="{{ route('orders') }}" class="btn btn-lg btn-warning">
                      + {{ $incomingOrdersCount }} <br/>
                      <i class="fas fa-arrow-circle-right"></i><br/>
                  </a>
                </div>
                @endif
            </div>
          </div>
          <!-- /.col-md-12 -->
        </div>
        <!-- /.row -->
        <div class="row">
          <div class="col-md-12">
            <div class="row">
              <div class="col-md-1">
                <a href="{{ route('orders') }}" class="btn btn-lg btn-danger">
                  {{ __('general_content.late_orders_trans_key') }}
                    <i class="icon fas fa-ban"></i><br/>
                </a>
              </div>
              @forelse ($LateOrders as $LateOrder)
              <div class="col-md-1">
                  <div class="progress-group alert alert-danger">
                      <a href="{{ route('orders.show', ['id' => $LateOrder->orders_id]) }}"><i class="fas fa-calculator"></i></a> {{ $LateOrder->order['code'] }}<br/>
                      <a href="{{ route('production.calendar.orders') }}"><i class="fas fa-calendar-alt"></i></a> {{ $LateOrder->delivery_date }}
                  </div>
              </div>
              <!-- /.col-md-1 -->
              @empty
              <div class="col-md-1">
                  <div class="progress-group">
                      {{ __('general_content.no_late_orders_trans_key') }}
                  </div>
              </div>
              <!-- /.col-md-12 -->
              @endforelse

              @if ($LateOrdersCount >= 1)
              <div class="col-md-1">
                <a href="{{ route('orders') }}" class="btn btn-lg btn-danger">
                    + {{ $LateOrdersCount }} <br/>
                    <i class="fas fa-arrow-circle-right"></i><br/>
                </a>
              </div>
              @endif
            </div>
          </div>
        </div>
        <!-- /.row -->
      </x-adminlte-card>
    </div>

    <!-- TABLE: LATEST ORDERS -->
    <div class="col-lg-4 col-md-12">
      <x-adminlte-card title="{{ __('general_content.latest_orders_trans_key') }}" theme="info" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
        <div class="table-responsive">
          <table class="table m-0">
            <thead>
            <tr>
              <th>{{ __('general_content.id_trans_key') }}</th>
              <th>{{ __('general_content.customer_trans_key') }}</th>
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
                <td>
                  @if(1 == $LastOrder->statu )  <span class="badge badge-info">{{ __('general_content.open_trans_key') }}</span>@endif
                  @if(2 == $LastOrder->statu )  <span class="badge badge-warning">{{ __('general_content.in_progress_trans_key') }}</span>@endif
                  @if(3 == $LastOrder->statu )  <span class="badge badge-success">{{ __('general_content.delivered_trans_key') }}</span>@endif
                  @if(4 == $LastOrder->statu )  <span class="badge badge-danger">{{ __('general_content.partly_delivered_trans_key') }}</span>@endif
                </td>
                <td>{{ $LastOrder->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                <td>{{ $LastOrder->GetPrettyCreatedAttribute() }}</td>
                <td><img src="{{ Avatar::create($LastOrder->UserManagement['name'])->toBase64() }}" /></td>
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
        <x-slot name="footerSlot">
          <a href="{{ route('orders') }}" class="btn btn-sm btn-secondary float-right">{{ __('general_content.view_all_trans_key') }}</a>
        </x-slot>
      </x-adminlte-card>
    </div>

    <!-- CHART: TOTAL OVERVIEW -->
    <div class="col-lg-8 col-md-12">
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
        <div class="card-footer">
          <div class="row">
            <div class="col-sm-3 col-3">
              <div class="description-block border-right">
                <!--<<span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 17%</span>-->
                <h5 class="description-header">{{ $OrderTotalForCast[0]->orderTotalForCast  ?? 0 }}  {{ $Factory->curency }}</h5>
                <span class="description-text">{{ __('general_content.total_order_forcasted_trans_key') }}</span>
              </div>
              <!-- /.description-block -->
          </div>
          <!-- /.col -->
            <div class="col-sm-3 col-3">
              <div class="description-block border-right">
                <!--<<span class="description-percentage text-success"><i class="fas fa-caret-up"></i> 17%</span>-->
                <h5 class="description-header">{{ $orderTotalDelivered }} {{ $Factory->curency }}</h5>
                <span class="description-text">{{ __('general_content.total_order_delivered_trans_key') }}</span>
              </div>
              <!-- /.description-block -->
            </div>
            <!-- /.col -->
            <div class="col-sm-3 col-3">
              <div class="description-block border-right">
                <!--<<span class="description-percentage text-warning"><i class="fas fa-caret-left"></i> 0%</span>-->
                <h5 class="description-header">{{ $orderTotaInvoiced }}   {{ $Factory->curency }}</h5>
                <span class="description-text">{{ __('general_content.total_invoiced_trans_key') }}</span>
              </div>
              <!-- /.description-block -->
            </div>
            <!-- /.col -->
            <div class="col-sm-3 col-3">
              <div class="description-block">
                <!--<span class="description-percentage text-danger"><i class="fas fa-caret-down"></i></span>-->
                <h5 class="description-header"> {{ $orderTotaInvoiced }}  /{{ $EstimatedBudgets }} ({{ round($orderTotaInvoiced / $EstimatedBudgets *100,2)}} %)</h5>
                <span class="description-text">{{ __('general_content.goal_trans_key') }}</span>
              </div>
              <!-- /.description-block -->
            </div>
          </div>
          <!-- /.row -->
        </div>
        <!-- /.card-footer -->
      </x-adminlte-card>
    </div>

    <!-- TABLE: DELIVERED -->
    <div class="col-lg-6 col-md-12">
      <x-adminlte-small-box title="{{ __('general_content.delivered_month_in_progress_trans_key') }}" 
                          text="{{ $data['delivered_month_in_progress'][0]->orderSum ?? 0}} {{ $Factory->curency }}" 
                          icon="icon fas fa-info"
                          theme="yellow" 
                          url="{{ route('orders') }}" 
                          url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <div class="col-lg-6 col-md-12">
      <x-adminlte-small-box title="{{ __('general_content.remaining_month_trans_key') }}" 
                          text="{{ $data['remaining_order'][0]->orderSum ?? 0 -$data['delivered_month_in_progress'][0]->orderSum   ?? 0}}  {{ $Factory->curency }}" 
                          icon="icon fas fa-info"
                          theme="danger" 
                          url="{{ route('orders') }}" 
                          url-text="{{ __('general_content.view_details_trans_key') }}"/>
    </div>
    <!-- /.col -->

    <!-- CHART: QUOTES RATE -->
    <div class="col-lg-4 col-md-12">
      <x-adminlte-card title="{{ __('general_content.quote_transformation_trans_key') }}" theme="teal" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
        <div class="row">
          <div class="col-md-12">
            <div class="chart-responsive">
              <canvas id="QuoteRate"  style="min-height: 350px; height: 100%; max-height: 100%; max-width: 100%;"></canvas>
            </div>
            <!-- ./chart-responsive -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </x-adminlte-card>
    </div>
    <!-- /.col-md-4 -->

    <!-- TABLE: LATEST QUOTES -->
    <div class="col-lg-4 col-md-12">
      <x-adminlte-card title="{{ __('general_content.latest_quotes_trans_key') }}" theme="success" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
          <div class="table-responsive">
            <table class="table m-0">
              <thead>
              <tr>
                <th>{{ __('general_content.id_trans_key') }}</th>
                <th>{{ __('general_content.customer_trans_key') }}</th>
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
                    <x-CompanieButton id="{{ $LastQuote->companies_id }}" label="{{ $LastQuote->companie['label'] }}"  />
                  </td>
                  <td>
                    @if(1 == $LastQuote->statu )   <span class="badge badge-info"> {{ __('general_content.open_trans_key') }}</span>@endif
                    @if(2 == $LastQuote->statu )  <span class="badge badge-warning">{{ __('general_content.send_trans_key') }}</span>@endif
                    @if(3 == $LastQuote->statu )  <span class="badge badge-success">{{ __('general_content.win_trans_key') }}</span>@endif
                    @if(4 == $LastQuote->statu )  <span class="badge badge-danger">{{ __('general_content.lost_trans_key') }}</span>@endif
                    @if(5 == $LastQuote->statu )  <span class="badge badge-secondary">{{ __('general_content.closed_trans_key') }}</span>@endif
                    @if(6 == $LastQuote->statu )   <span class="badge badge-secondary">{{ __('general_content.obsolete_trans_key') }}</span>@endif
                  </td>
                  <td>{{ $LastQuote->getTotalPriceAttribute() }}  {{ $Factory->curency }}</td>
                  <td>{{ $LastQuote->GetPrettyCreatedAttribute() }}</td>
                  <td><img src="{{ Avatar::create($LastQuote->UserManagement['name'])->toBase64() }}" /></td>
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
        <x-slot name="footerSlot">
          <a href="{{ route('quotes') }}" class="btn btn-sm btn-secondary float-right">{{ __('general_content.view_all_trans_key') }}</a>
        </x-slot>
      </x-adminlte-card>
    </div>
    <!-- /.col-md-4 -->

    <!-- TABLE: ANNOUNCEMENT -->
    <div class="col-lg-4 col-md-12">
      <x-adminlte-card title="{{ __('general_content.announcement_trans_key') }}" theme="lightblue" theme-mode="outline"
          icon="fas fa-lg fa-envelope" header-class="text-uppercase rounded-bottom border-info"
          removable>
          <h2><i class="icon fas fa-info"></i> {{ $Announcement->title ?? 'No announcement' }}</h2>
            <div class="disabled color-palette">
              <h5>{!! nl2br(htmlspecialchars(optional($Announcement)->comment, ENT_NOQUOTES)) ?? 'No announcement' !!}</h5>
            </div>  
      </x-adminlte-card>
    </div>
    <!-- /.col -->

    <!-- PRODUCT LIST -->
    <div class="col-lg-12 col-md-12">
      
      <x-adminlte-card title="{{ __('general_content.latest_products_trans_key') }}" theme="dark" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
        <div class="row">  
          @forelse ($LastProducts as $LastProduct)
          @php
            if($LastProduct->picture)
              $picture = asset('/images/products/'. $LastProduct->picture);
            else {
              $picture = Avatar::create($LastProduct->code)->toBase64();
            }
          @endphp
          <div class="col-md-2">
            <x-adminlte-profile-widget name="{{ $LastProduct->label }} {{ $LastProduct->ind }}" desc="{{ $LastProduct->family->label }}" theme="lightblue"
                img="{{ $picture }}"
                layout-type="classic"> 

                <x-adminlte-profile-row-item icon="fas fa-cash-register " title="{{ __('general_content.purchased_price_trans_key') }}" text=" {{ $LastProduct->purchased_price }}  {{ $Factory->curency }}"
                    url="#" badge="teal"/>
                <x-adminlte-profile-row-item icon="fas fa-shopping-cart" title="{{ __('general_content.price_trans_key') }}"
                    text="{{ $LastProduct->selling_price }}  {{ $Factory->curency }}" url="#" badge="lightblue"/>
            </x-adminlte-profile-widget>
          </div>
          <!-- /.item -->
          @empty
            <li class="item">{{ __('general_content.no_product_trans_key') }}</li>
          @endforelse
        </div>
        <x-slot name="footerSlot">
          <a href="{{ route('products') }}" class="btn btn-sm btn-secondary">{{ __('general_content.view_all_trans_key') }}</a>
        </x-slot>
      </x-adminlte-card>
    </div>

    <!-- SERVICE GOAL -->
    <div class="col-lg-12 col-md-12">
      <x-adminlte-card title="{{ __('general_content.goal_task_trans_key') }}" theme="primary" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
          <div class="row">
            <div class="col-md-12">
              @forelse ($ServiceGoals as $ServiceGoal)
              <div class="progress-group">
                {{ $ServiceGoal->label }}
                <span class="float-right">{{ $ServiceGoal->tasks_count }}</span>
                <div class="progress">
                  @php
                  foreach($Tasks as $Task){
                  if($Task->methods_id == $ServiceGoal->id){
                    $width = 100/($ServiceGoal->tasks_count/ $Task->total_task);
                    
                    if($Task->title ==  __('general_content.open_trans_key') ){ $class = 'bg-danger'; $title = __('general_content.open_trans_key');}
                    elseif($Task->title ==  __('general_content.started_trans_key') ){ $class = 'bg-warning';$title = __('general_content.started_trans_key');}
                    elseif($Task->title ==  __('general_content.in_progress_trans_key') ){ $class = 'bg-primary';$title = __('general_content.in_progress_trans_key');}
                    elseif($Task->title ==  __('general_content.finished_trans_key') ){ $class = 'bg-success';$title = __('general_content.finished_trans_key');}
                    else{ $class = 'bg-info';$title = $Task->title;}
                    echo '<div class="progress-bar  progress-bar-striped '.   $class  .'" style="width: '.  $width  .'%">'. $title .' - '. $Task->total_task .'</div>' ;
                  }
                }
                @endphp
                </div>
              </div>
              <!-- /.progress-group -->
              @empty
              <div class="progress-group">
                {{ __('general_content.no_dash_task_trans_key') }}
              </div>
              <!-- /.progress-group -->
              @endforelse
            </div>
            <!-- /.col -->
          </div>
          <!-- /.row -->
      </x-adminlte-card>
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
          label               : 'Delivered revenues',
          borderColor         : 'rgba(240, 173, 78,0.5)',
          pointRadius          : 5,
          pointColor          : '#f0ad4e',
          pointStrokeColor    : 'rgba(240, 173, 78,1)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(240, 173, 78,1)',
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
        {
          label               : 'Invoiced revenues',
          borderColor         : 'rgba(217, 83, 79,0.5)',
          pointRadius          : 5,
          pointColor          : '#d9534f',
          pointStrokeColor    : 'rgba(217, 83, 79,1)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(217, 83, 79,1)',
          data                : [
                              @php ($j = 1)
                              @for($iM =1;$iM<=12;$iM++)
                                @foreach ($data['invoiceMonthlyRecap'] as $key => $item)
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
