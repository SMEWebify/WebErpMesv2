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
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="teal" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
            <canvas id="donutChart" width="400" height="400"></canvas>
          </x-adminlte-card>
          <x-adminlte-card title="{{ __('general_content.statistiques_trans_key') }}" theme="warning" icon="fas fa-chart-bar text-white" collapsible removable maximizable>
            <canvas id="barChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
          </x-adminlte-card>
        </div>
        
        <div class="col-md-3">
          <x-adminlte-small-box title=" {{ __('general_content.lines_count_trans_key') }}" text="{{ $totalPurchaseLineCount }}" icon="fas fa-shopping-cart text-white"
            theme="purple"/>

            
          <x-adminlte-card title="{{ __('general_content.top_rated_supplier_trans_key') }}" theme="primary" maximizable>
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
          </x-adminlte-card>
        </div>

        <div class="col-md-3">
          <x-adminlte-card title="{{ __('general_content.suppliers_shortest_times_trans_key') }}" theme="secondary" maximizable>
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
          </x-adminlte-card>
          <x-adminlte-small-box title=" {{ __('general_content.total_price_trans_key') }}" text="{{ $totalPurchasesAmount }} {{ $Factory->curency }}" icon="fas fa-shopping-cart text-white"
          theme="danger"/>
          
          <x-adminlte-card title="{{ __('general_content.suppliers_longest_times_trans_key') }}" theme="dark" maximizable>
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
          </x-adminlte-card>
        </div>

        <div class="col-md-3">
            <!-- Modal -->
            <div wire:ignore.self class="modal fade" id="ModalPurchase" tabindex="-1" role="dialog" aria-labelledby="ModalPurchaseTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
                  <div class="modal-content ">
                      <div class="modal-header bg-success">
                          <h5 class="modal-title" id="ModalPurchaseTitle">{{ __('general_content.new_opportunities_trans_key') }}</h5>
                          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                          </button>
                      </div>
                      <div class="modal-body">
                          <form  method="POST" action="{{ route('purchases.store') }}" class="form-horizontal" enctype="multipart/form-data"> 
                          @csrf
                          <div class="card card-body">
                              @include('include.alert-result')
                              <div class="form-row">
                                <div class="form-group col-md-3">
                                  <label for="code">{{ __('general_content.external_id_trans_key') }}</label>
                                  <div class="input-group">
                                      <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-external-link-square-alt"></i></span>
                                      </div>
                                      <input type="text" class="form-control"  name="code" id="code" placeholder="{{ __('general_content.external_id_trans_key') }}" value="{{ $code }}" required>
                                  </div>
                                  @error('code') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="label">{{ __('general_content.label_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                        </div>
                                        <input type="text" class="form-control" name="label"  id="label"  placeholder="{{ __('general_content.name_purchase_trans_key') }}" value="{{ $label }}" required>
                                    </div>
                                    @error('label') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                                <div class="form-group col-md-3">
                                  <label for="companies_id">{{ __('general_content.sort_companie_trans_key') }}</label>
                                  <div class="input-group">
                                      <div class="input-group-prepend">
                                          <span class="input-group-text"><i class="fas fa-building"></i></span>
                                      </div>
                                      <select class="form-control"  name="companies_id" id="companies_id" required>
                                          <option value="">{{ __('general_content.select_company_trans_key') }}</option>
                                      @forelse ($SupplierSelect as $item)
                                          <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->label }}</option>
                                      @empty
                                          <option value="">{{ __('general_content.no_select_company_trans_key') }}</option>
                                      @endforelse
                                      </select>
                                  </div>
                                  @error('companies_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                              </div>
                                <div class="form-group col-md-3">
                                    <label for="user_id">{{ __('general_content.user_management_trans_key') }}</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <select class="form-control"  name="user_id" id="user_id" required>
                                            <option value="">{{ __('general_content.select_user_management_trans_key') }}</option>
                                            @foreach ($userSelect as $item)
                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('user_id') <span class="text-danger">{{ $message }}<br/></span>@enderror
                                </div>
                              </div>
                              <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('general_content.close_trans_key') }}</button>
                                  <button type="Submit" class="btn btn-danger btn-flat"><i class="fas fa-lg fa-save"></i>{{ __('general_content.submit_trans_key') }}</button>
                              </div>
                            </div>
                          </form>
                      </div>
                  </div>
              </div>
          </div>
          <!-- End Modal -->

          <x-adminlte-card theme="lime" theme-mode="outline">
            <div class="d-flex justify-content-center">
              <button type="button" class="btn btn-success float-sm-right" data-toggle="modal" data-target="#ModalPurchase">
                {{ __('general_content.new_purchase_document_trans_key') }}</button>
            </div>
          </x-adminlte-card>

          <x-adminlte-small-box title=" {{ __('general_content.average_purchase_price_trans_key') }}" text="{{ $averageAmount }} {{ $Factory->curency }}" icon="fas fa-chart-bar text-white" theme="teal"/>
          
          <x-adminlte-card title="{{ __('general_content.most_purchased_products_trans_key') }}" theme="success" maximizable>
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
          </x-adminlte-card>
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