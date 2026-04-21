@extends('adminlte::page')

@section('title', __('general_content.workshop_interface_trans_key'))

@section('content_header')
  <h1>{{ __('general_content.workshop_interface_trans_key') }}</h1>
@stop

@section('content')
  <div id="task-statu-app"
       data-kpi='@json($kpi, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-user-productivity='@json($userProductivity, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-resource-hours='@json($resourceHours, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'
       data-task-id='{{ $taskId ?? '' }}'
       data-base-statu-url='{{ route("workshop.task.statu") }}/Id'
       data-api-base-url='{{ url("production/Task/Statu/Api") }}'
       data-andon-store-url='{{ route("workshop.andon.store") }}'
       data-purchases-request-url='{{ route("purchases.request") }}'
       data-trans='@json($trans, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG)'>
  </div>
@stop

@section('css')
  @viteReactRefresh
  @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function(){
            $("body").addClass("sidebar-hidden");
        });
    </script>
@stop
