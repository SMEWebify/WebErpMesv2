@extends('adminlte::page')

@section('title', __('general_content.load_planning_trans_key'))

@section('content_header')
    <div class="row mb-2">
      <div class="col-sm-12">
        <h1>{{ __('general_content.load_planning_trans_key') }}</h1>
      </div>
    </div>
@stop

@section('content')
  @php
    $initialData = [
      'services'               => $services->map(fn ($s) => [
                                    'id'       => $s->id,
                                    'label'    => $s->label,
                                    'picture'  => $s->picture,
                                    'capacity' => round($s->Ressources->sum('capacity') / \App\Models\Methods\MethodsRessources::WORKING_DAYS_PER_WEEK, 2),
                                  ])->values()->toArray(),
      'possibleDates'          => $possibleDates,
      'hoursPerServiceDay'     => $hoursPerServiceDay,
      'tasksPerServiceDay'     => $tasksPerServiceDay,
      'bankHolidays'           => $bankHolidays,
      'countTaskNullDate'      => $countTaskNullDate,
      'countTaskNullRessource' => $countTaskNullRessource,
    ];
    $endpointsData = [
      'data'               => route('production.load.planning.data'),
      'task'               => route('production.task'),
      'calculateDates'     => route('production.load.planning.calculate.dates'),
      'calculateResources' => route('production.load.planning.calculate.resources'),
      'calculationStatus'  => route('production.load.planning.calculation.status'),
    ];
    $transData = [
      'start_date'          => __('general_content.start_date_trans_key'),
      'end_date'            => __('general_content.end_date_trans_key'),
      'display_hours_diff'  => __('general_content.display_hours_diff_trans_key'),
      'yes'                 => __('general_content.yes_trans_key'),
      'no'                  => __('general_content.no_trans_key'),
      'submit'              => __('general_content.submit_trans_key'),
      'service'             => __('general_content.service_trans_key'),
      'back'                => __('general_content.back_trans_key'),
      'calculate'           => __('general_content.calculate_task_trans_key'),
      'refresh'             => __('general_content.refresh_trans_key'),
      'null_date'           => __('general_content.gantt_info_1_trans_key'),
      'null_resource'       => __('general_content.gantt_info_2_trans_key'),
      'calc_date_title'     => __('general_content.calculate_date_task_trans_key'),
      'calc_resource_title' => __('general_content.calculate_ressource_task_trans_key'),
    ];
  @endphp

  <div id="load-planning-app"
    data-initial="{{ json_encode($initialData) }}"
    data-start-date="{{ $startDate }}"
    data-end-date="{{ $endDate }}"
    data-display-hours-diff="{{ $displayHoursDiff ? 'true' : 'false' }}"
    data-endpoints="{{ json_encode($endpointsData) }}"
    data-trans="{{ json_encode($transData) }}"
  ></div>
@stop

@section('css')
  @viteReactRefresh
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
@stop
