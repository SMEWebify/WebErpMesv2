@extends('adminlte::page')

@section('title', __('general_content.load_planning_trans_key'))

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-8">
        <h1>{{__('general_content.load_planning_trans_key') }}</h1>
      </div>
      <div class="col-sm-2">
        <!-- Button Modal -->
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#taskCalculationRessource">
          {{__('general_content.gantt_info_2_trans_key') }}   ({{ $countTaskNullRessource }})
        </button>
      </div>
      <div class="col-sm-2">
        <!-- Button Modal -->
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#taskCalculationDate">
          {{__('general_content.gantt_info_1_trans_key') }}  ({{ $countTaskNullDate }})
        </button>
      </div>
    </div>
@stop

@section('right-sidebar')

@section('content')
  @livewire('task-calculation-date')
  <div class="card">
    <div class="card-body">
        <x-adminlte-alert theme="info" title="Info">
          {{__('general_content.load_planning_info_1_trans_key') }}
        </x-adminlte-alert>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      @include('include.alert-result')
      <form action="{{ route('production.load.planning') }}" method="GET">
        <div class="row">
          <div class="form-group col-2">
              <label for="start_date">Date de début:</label>
              <input type="date" class="form-control" id="start_date" name="start_date" required value="{{ $startDate ?? '' }}">
              <label for="end_date">{{ __('general_content.end_date_trans_key') }}</label>
              <input type="date" class="form-control" name="end_date"  id="end_date" required value="{{ $endDate ?? '' }}">
          </div>
          <div class="form-group col-2">
            <x-adminlte-button class="btn-flat" type="submit" label="{{ __('general_content.submit_trans_key') }}" theme="danger" icon="fas fa-lg fa-save"/>
          </div>
        </div>
      </form>
    </div>
  </div>


  <div class="card">
    <div class="table-responsive">
      <table  id="tblDemo"  class="table table-hover">
        <thead>
          <tr>
              <th></th>
              <th>{{__('general_content.service_trans_key') }}</th>
              @foreach ($possibleDates as $singleDay)
                  <th>{{ $singleDay }}</th>
              @endforeach
          </tr>
        </thead>
        <tbody>
        @foreach ($services as $service)
            <tr>
              <td>
                @if($service->picture )
                <img alt="Avatar" class="profile-user-img img-fluid img-circle" src="{{ asset('/images/methods/'.$service->picture) }}">
                @endif
              </td>
              <td >{{ $service->label }}</td>
                @foreach ($possibleDates as $singleDay)
                    @if (isset($structureRateLoad[$singleDay][$service->id]))
                        <td 
                        @php
                          $tachesComposees = [];
                          if (isset($tasksPerServiceDay[$service->id][$singleDay])) {

                              foreach ($tasksPerServiceDay[$service->id][$singleDay] as $tache) {
                                  if ($tache) {
                                      $tachesComposees[] = '#'. $tache;
                                  }
                              }
                          }
                          $tooltipContent = implode(', ', $tachesComposees);
                      @endphp
                          @if($structureRateLoad[$singleDay][$service->id] >= 100) style="background-color:#ff2f00; " 
                          @elseif ($structureRateLoad[$singleDay][$service->id] >= 80) style="background-color: #d6300b ; " 
                          @elseif ($structureRateLoad[$singleDay][$service->id] >= 50) style="background-color: #db7814 ; " 
                          @elseif ($structureRateLoad[$singleDay][$service->id] >= 20) style="background-color: #d2e010 ; " 
                          @else style="background-color: #35ba23 ; " 
                          @endif
                          title="{{ $tooltipContent }}">{{ $structureRateLoad[$singleDay][$service->id] . '%' }}</td>
                    @else
                        <td style="{{ date('N', strtotime($singleDay)) >= 6 ? 'background-color:#bff0ff;' :'' }}">N/A</td>
                    @endif
                @endforeach
            </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </div>
@stop

@section('css')
@stop

@section('js')
@stop

