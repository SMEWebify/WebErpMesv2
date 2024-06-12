@extends('adminlte::page')

@section('title', __('general_content.opportunities_trans_key'))

@section('content_header')
<div class="row mb-2">
    <h1>{{ __('general_content.opportunities_trans_key')}}</h1>
</div>
@stop

@section('right-sidebar')

@section('content')
<div class="card">
  <div class="card-header p-2">
    <ul class="nav nav-pills">
      <li class="nav-item"><a class="nav-link active" href="#Dashboard" data-toggle="tab">{{ __('general_content.dashboard_trans_key') }}</a></li> 
      <li class="nav-item"><a class="nav-link" href="#List" data-toggle="tab">{{ __('general_content.opportunities_list_trans_key') }}</a></li> 
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
          <x-adminlte-card title="{{ __('general_content.logs_activity_trans_key') }}" theme="primary" maximizable>
            <table class="table">
              <thead>
                  <tr>
                      <th>{{__('general_content.label_trans_key') }}</th>
                      <th>{{__('general_content.created_at_trans_key') }}</th>
                      <th>{{__('general_content.opportunity_trans_key') }}</th>
                  </tr>
              </thead>
              <tbody>
                @forelse ($recentActivities as $activity)
                  <tr>
                      <td>{{ $activity->label }}</td>
                      <td>{{ $activity->GetPrettyCreatedAttribute() }}</td>
                      <td><x-ButtonTextView route="{{ route('opportunities.show', ['id' => $activity->opportunities_id])}}" /></td>
                  </tr>
                @empty
                  <x-EmptyDataLine col="2" text="{{ __('general_content.no_data_trans_key') }}"  />
                @endforelse
              </tbody>
            </table>
          </x-adminlte-card>
          <x-adminlte-small-box title="{{ __('general_content.total_amount_won_trans_key') }}" text="{{ $totalQuotesWon }} {{ $Factory->curency }}" icon="fas fa-shopping-cart text-white"
            theme="danger"/>
        </div>

      <div class="col-md-3">
        <x-adminlte-small-box title="{{ __('general_content.opportunities_count_trans_key') }}" text="{{ $opportunitiesCount }}" icon="fas fa-chart-bar text-white"
          theme="teal"/>
          
          <x-adminlte-card title="{{ __('general_content.opportunities_by_company_trans_key') }}" theme="secondary" maximizable>
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('general_content.companie_name_trans_key') }}</th>
                        <th>{{ __('general_content.opportunities_count_trans_key') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opportunitiesByCompany as $opportunity)
                        <tr>
                            <td><x-CompanieButton id="{{ $opportunity->companies_id }}" label="{{ $opportunity->companie->label }}"  /></td>
                            <td>{{ $opportunity->count }}</td>
                        </tr>
                      @empty
                        <x-EmptyDataLine col="2" text="{{ __('general_content.no_data_trans_key') }}"  />
                      @endforelse
                </tbody>
            </table>
          </x-adminlte-card>
      </div>

      <div class="col-md-3">
        <x-adminlte-card title="{{ __('general_content.opportunities_by_probability_trans_key') }}" theme="dark" maximizable>
          <table class="table">
            <thead>
                <tr>
                    <th>{{ __('general_content.probality_trans_key') }}</th>
                    <th>{{ __('general_content.amount_trans_key') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opportunitiesByAmount as $opportunity)
                    <tr>
                        <td>{{ $opportunity->probality }} %</td>
                        <td>{{ $opportunity->total_amount }}  {{ $Factory->curency }}</td>
                    </tr>
                @endforeach
            </tbody>
          </table>
        </x-adminlte-card>
        <x-adminlte-small-box title="{{ __('general_content.total_amount_lost_trans_key') }}" text="{{ $totalQuotesLost }}  {{ $Factory->curency }}" icon="fas fa-times-circle "
          theme="warning"/>
      </div>
    </div>
  </div>
  <div class="tab-pane" id="List">
    @livewire('opportunities-index')
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