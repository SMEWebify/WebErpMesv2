@extends('adminlte::page')

@section('title', __('general_content.load_planning_trans_key'))

@section('content_header')
  <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <div class="row mb-2">
      <div class="col-sm-8">
        <h1>{{ __('general_content.load_planning_trans_key') }}</h1>
      </div>
      <div class="col-sm-2">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#taskCalculationRessource">
          {{ __('general_content.gantt_info_2_trans_key') }} ({{ $countTaskNullRessource }})
        </button>
      </div>
      <div class="col-sm-2">
        <button type="button" class="btn btn-primary float-sm-right" data-toggle="modal" data-target="#taskCalculationDate">
          {{ __('general_content.gantt_info_1_trans_key') }} ({{ $countTaskNullDate }})
        </button>
      </div>
    </div>
@stop

@section('content')
  @livewire('task-calculation-date')

  <x-adminlte-alert theme="info" title="Info">
    {{ __('general_content.load_planning_info_1_trans_key') }}
  </x-adminlte-alert>
  @php
    $initialData = [
      'services'             => $services->map(fn ($s) => ['id' => $s->id, 'label' => $s->label, 'picture' => $s->picture])->values()->toArray(),
      'possibleDates'        => $possibleDates,
      'structureRateLoad'    => $structureRateLoad,
      'tasksPerServiceDay'   => $tasksPerServiceDay,
      'bankHolidays'         => $bankHolidays,
      'countTaskNullDate'    => $countTaskNullDate,
      'countTaskNullRessource' => $countTaskNullRessource,
    ];
    $endpointsData = [
      'data' => route('production.load.planning.data'),
      'task' => route('production.task'),
    ];
    $transData = [
      'start_date'         => __('general_content.start_date_trans_key'),
      'end_date'           => __('general_content.end_date_trans_key'),
      'display_hours_diff' => __('general_content.display_hours_diff_trans_key'),
      'yes'                => __('general_content.yes_trans_key'),
      'no'                 => __('general_content.no_trans_key'),
      'submit'             => __('general_content.submit_trans_key'),
      'service'            => __('general_content.service_trans_key'),
      'back'               => __('general_content.back_trans_key'),
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
