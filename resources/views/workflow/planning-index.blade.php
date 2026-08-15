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
      // Timeline view — :count / :hours placeholders are replaced client-side
      'capacity'                => __('general_content.capacity_trans_key'),
      'today'                   => __('general_content.load_planning_today_trans_key'),
      'weekend'                 => __('general_content.load_planning_weekend_trans_key'),
      'day_off'                 => __('general_content.banck_holiday_trans_key'),
      'compact_view'            => __('general_content.load_planning_compact_view_trans_key'),
      'detailed_view'           => __('general_content.load_planning_detailed_view_trans_key'),
      'legend'                  => __('general_content.load_planning_legend_trans_key'),
      'level_free'              => __('general_content.load_planning_level_free_trans_key'),
      'level_low'               => __('general_content.load_planning_level_low_trans_key'),
      'level_medium'            => __('general_content.load_planning_level_medium_trans_key'),
      'level_high'              => __('general_content.load_planning_level_high_trans_key'),
      'level_over'              => __('general_content.load_planning_level_over_trans_key'),
      'period_total'            => __('general_content.load_planning_period_total_trans_key'),
      'overloaded_days'         => __('general_content.load_planning_overloaded_days_trans_key'),
      'tasks_count'             => __('general_content.load_planning_tasks_count_trans_key'),
      'tasks_processed'         => __('general_content.load_planning_tasks_processed_trans_key'),
      'running'                 => __('general_content.load_planning_running_trans_key'),
      'finished'                => __('general_content.load_planning_finished_trans_key'),
      'default_capacity'        => __('general_content.load_planning_default_capacity_trans_key'),
      'default_capacity_hint'   => __('general_content.load_planning_default_capacity_hint_trans_key'),
      'capacity_from_resources' => __('general_content.load_planning_capacity_resources_trans_key'),
      'capacity_custom'         => __('general_content.load_planning_capacity_custom_trans_key'),
      'capacity_fallback'       => __('general_content.load_planning_capacity_fallback_trans_key'),
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
