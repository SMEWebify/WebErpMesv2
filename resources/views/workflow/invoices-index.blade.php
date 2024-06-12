@extends('adminlte::page')

@section('title', __('general_content.invoices_list_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.invoices_list_trans_key') }}</h1>
@stop

@section('right-sidebar')

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#List" data-toggle="tab">{{ __('general_content.invoices_list_trans_key') }}</a></li> 
    </ul>
  </div>
  <!-- /.card-header -->
  <div class="tab-content p-3">
    <div class="tab-pane active" id="Dashboard">
      <div class="row">
        <div class="col-md-3">
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="teal" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
            <canvas id="donutChart" width="400" height="400"></canvas>
          </x-adminlte-card>
        </div>

        <div class="col-md-3">
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="success" maximizable>
            <p class="card-text">{{ __('general_content.number_of_invoice_trans_key') }} : {{ $totalInvoices }}</p>
            <p class="card-text">{{ __('general_content.amount_of_invoice_trans_key') }} : {{ number_format($totalInvoiceAmount, 2) }} {{ $Factory->curency }}</p>
            <p class="card-text">{{ __('general_content.payments_received_of_invoice_trans_key') }} : {{ number_format($totalPaymentsReceived, 2) }} {{ $Factory->curency }} <span class="badge badge-warning right">Soon</span></p> 
          </x-adminlte-card>

          
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="warning" maximizable>
            <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
          </x-adminlte-card>
        </div>
        
        <div class="col-md-3">
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="primary" maximizable>
            <p class="card-text">{{ __('general_content.bills_paid_trans_key') }} : {{ $paidInvoices }}</p>
            <p class="card-text">{{ __('general_content.bills_unpaid_trans_key') }}  : {{ $unpaidInvoices }}</p>
            <p class="card-text">{{ __('general_content.average_payment_time_trans_key') }} : {{ round($averagePaymentDelay, 2) }} jours <span class="badge badge-warning right">Soon</span></p> 
            <p class="card-text">{{ __('general_content.late_payment_time_trans_key') }} : {{ round($latePaymentRate, 2) }} % <span class="badge badge-warning right">Soon</span></p> 
          </x-adminlte-card>
        </div>
      
  
        <div class="col-md-3">
          <x-adminlte-card title="{{ __('general_content.top_customers_trans_key') }}" theme="secondary" maximizable>
            <ul class="list-group list-group-flush">
                @foreach($topClients as $client)
                    <li class="list-group-item">
                        <strong>{{ $client->companie->label }}</strong>  {{ number_format($client->total_amount, 2) }} {{ $Factory->curency }}
                    </li>
                @endforeach
            </ul>
          </x-adminlte-card>
          
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="dark" maximizable>
            <ul class="list-group list-group-flush">
                @foreach($topProducts as $product)
                    <li class="list-group-item">
                        {{ $product->orderLine->Product->label ?? 'Produit inconnu' }} - {{ $product->total_quantity }}
                    </li>
                @endforeach
            </ul>
          </x-adminlte-card>
        </div>
      </div>
    </div>
    <div class="tab-pane" id="List">
      @livewire('invoices-index')
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
          @foreach ($data['invoicesDataRate'] as $item)
                @if(1 == $item->statu )  "In progress", @endif
                @if(2 == $item->statu )  "Sent", @endif
                @if(3 == $item->statu )  "Pending", @endif
                @if(4 == $item->statu )  "Unpaid", @endif
                @if(4 == $item->statu )  "Paid", @endif
          @endforeach
        ],
        datasets: [
          {
            data: [
                  @foreach ($data['invoicesDataRate'] as $item)
                  "{{ $item->InvoiceCountRate }}",
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
            label               : 'Total invoiced',
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